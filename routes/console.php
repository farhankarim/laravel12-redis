<?php

use App\Jobs\InsertUsersChunkJob;
use App\Services\RedisDashboardSummaryService;
use App\Services\ChequebookImportBatchDispatcher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
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

Artisan::command('chequebook:import
    {--company-prefix= : Company prefix (defaults to config chequebook.company_prefix)}
    {--batch-size= : Number of pending requests per queued job}
    {--queue= : Queue name override}
    {--created-by= : Optional user_id used as created_by}', function () {
    /** @var ChequebookImportBatchDispatcher $dispatcher */
    $dispatcher = app(ChequebookImportBatchDispatcher::class);

    $companyPrefix = (string) ($this->option('company-prefix') ?: config('chequebook.company_prefix', 'qr'));
    $batchSize = (int) ($this->option('batch-size') ?: config('chequebook.batch_size', 500));
    $queue = (string) ($this->option('queue') ?: config('chequebook.queue', 'chequebook-imports'));
    $createdBy = $this->option('created-by');

    try {
        $result = $dispatcher->dispatch(
            companyPrefix: $companyPrefix,
            batchSize: $batchSize,
            queue: $queue,
            createdBy: $createdBy !== null ? (int) $createdBy : null,
        );
    } catch (\InvalidArgumentException $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    if (! $result['queued']) {
        $this->info($result['message']);

        return 0;
    }

    $batch = $result['batch'];

    $this->info($result['message']);
    $this->line("Batch ID: {$batch->id}");
    $this->line('Pending requests: '.$result['pending_requests']);
    $this->line('Jobs dispatched: '.$result['jobs_dispatched']);
    $this->line('Queue: '.$result['queue']);
    $this->line('Track status via HTTP endpoint: /chequebook/import/'.$batch->id);

    return 0;
})->purpose('Dispatch pending cheque_book_requests as Redis batched jobs');
