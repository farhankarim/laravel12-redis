<?php

use App\Jobs\InsertUsersChunkJob;
use App\Jobs\SendEmailVerificationChunkJob;
use App\Services\RedisDashboardSummaryService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dashboard:redis-listen', function (RedisDashboardSummaryService $summaryService) {
    $this->info('Subscribing to Redis channel: '.RedisDashboardSummaryService::REFRESH_CHANNEL);
    $this->line('Waiting for refresh messages... Press Ctrl+C to stop.');

    while (true) {
        try {
            $summaryService->subscribeAndProcessRefreshRequests(function (string $type): void {
                $this->info('Processed summary refresh request: '.$type.' @ '.now()->toDateTimeString());
            });
        } catch (\Throwable $exception) {
            $this->error('Redis listener failed: '.$exception->getMessage());
            $this->line('Retrying in 2 seconds...');
            sleep(2);
        }
    }

    return 0;
})->purpose('Listen to Redis pub/sub refresh requests and refresh dashboard summary keys');

Artisan::command('users:queue-generate
    {--total=1000000 : Total users to generate}
    {--chunk=1000 : Users inserted per queued job}
    {--connection=redis : Queue connection name}
    {--queue=user-imports : Queue name}
    {--run-id= : Optional run id used in generated emails}', function () {
    $total = (int) $this->option('total');
    $chunk = (int) $this->option('chunk');
    $connection = (string) $this->option('connection');
    $queue = (string) $this->option('queue');
    $runId = (string) ($this->option('run-id') ?: Str::lower(Str::ulid()->toBase32()));

    if ($total < 1) {
        $this->error('--total must be greater than 0.');

        return 1;
    }

    if ($chunk < 1 || $chunk > 10000) {
        $this->error('--chunk must be between 1 and 10000.');

        return 1;
    }

    $passwordHash = Hash::make('password');
    $jobs = (int) ceil($total / $chunk);

    for ($index = 1; $index <= $total; $index += $chunk) {
        $chunkSize = min($chunk, $total - $index + 1);

        InsertUsersChunkJob::dispatch($index, $chunkSize, $runId, $passwordHash)
            ->onConnection($connection)
            ->onQueue($queue);
    }

    $this->info("Queued {$total} users as {$jobs} jobs on {$connection}:{$queue}.");
    $this->line("Run id: {$runId}");
    $this->line("Start workers with: php artisan queue:work {$connection} --queue={$queue}");

    return 0;
})->purpose('Queue generation of many users in chunked jobs');

Artisan::command('users:queue-email-verifications
    {--chunk=1000 : Number of users processed per queued notification job}
    {--connection=redis : Queue connection name}
    {--queue=email-verifications : Queue name}
    {--id-column=user_id : Users table primary key column}
    {--email-column=email_address : Users table email column}
    {--email-like= : Optional filter users by email pattern}
    {--limit=0 : Optional max users to include (0 = no limit)}
    {--only-unverified=0 : 1 to target only users with null email_verified_at}', function () {
    $chunk = (int) $this->option('chunk');
    $connection = (string) $this->option('connection');
    $queue = (string) $this->option('queue');
    $idColumn = (string) $this->option('id-column');
    $emailColumn = (string) $this->option('email-column');
    $emailLike = (string) $this->option('email-like');
    $limit = (int) $this->option('limit');
    $onlyUnverified = (bool) ((int) $this->option('only-unverified'));

    if ($chunk < 1 || $chunk > 10000) {
        $this->error('--chunk must be between 1 and 10000.');

        return 1;
    }

    if (! Schema::hasColumn('users', $idColumn)) {
        $this->error("Column '{$idColumn}' does not exist on users table.");

        return 1;
    }

    if (! Schema::hasColumn('users', $emailColumn)) {
        $this->error("Column '{$emailColumn}' does not exist on users table.");

        return 1;
    }

    if ($limit < 0) {
        $this->error('--limit cannot be negative.');

        return 1;
    }

    $query = DB::table('users')->select($idColumn);

    if ($emailLike !== '') {
        $query->where($emailColumn, 'like', $emailLike);
    }

    if ($onlyUnverified && Schema::hasColumn('users', 'email_verified_at')) {
        $query->whereNull('email_verified_at');
    } elseif ($onlyUnverified && Schema::hasColumn('users', 'email_verification_status')) {
        $query->where('email_verification_status', '!=', 'verified');
    } elseif ($onlyUnverified) {
        $this->warn('No known verification status column found; only-unverified filter skipped.');
    }

    if ($limit > 0) {
        $query->limit($limit);
    }

    $total = (clone $query)->count();

    if ($total === 0) {
        $this->warn('No users matched your filters; no batch dispatched.');

        return 0;
    }

    $batch = Bus::batch([])
        ->name('email-verification-notifications')
        ->allowFailures()
        ->onConnection($connection)
        ->onQueue($queue)
        ->dispatch();

    $jobCount = 0;

    $query
        ->orderBy($idColumn)
        ->chunkById($chunk, function ($users) use (&$jobCount, $batch, $idColumn, $emailColumn): void {
            $ids = $users->pluck($idColumn)->map(fn ($id) => (int) $id)->all();
            $batch->add([new SendEmailVerificationChunkJob($ids, $idColumn, $emailColumn)]);
            $jobCount++;
        }, $idColumn);

    $this->info("Dispatched batch {$batch->id} for {$total} users as {$jobCount} jobs on {$connection}:{$queue}.");
    $this->line("Start workers with: php artisan queue:work {$connection} --queue={$queue}");

    return 0;
})->purpose('Dispatch a queued batch of email verification notifications for users');
