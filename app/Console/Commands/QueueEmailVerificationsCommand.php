<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailVerificationChunkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueEmailVerificationsCommand extends Command
{
    protected $signature = 'users:queue-email-verifications
        {--chunk=1000 : Number of users processed per queued notification job}
        {--connection=redis : Queue connection name}
        {--queue=email-verifications : Queue name}
        {--id-column=user_id : Users table primary key column}
        {--email-column=email_address : Users table email column}
        {--email-like= : Optional filter users by email pattern}
        {--limit=0 : Optional max users to include (0 = no limit)}
        {--only-unverified=0 : 1 to target only users with null email_verified_at}';

    protected $description = 'Dispatch a queued batch of email verification notifications for users';

    public function handle(): int
    {
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

            return self::FAILURE;
        }

        if (! in_array($idColumn, SendEmailVerificationChunkJob::ALLOWED_ID_COLUMNS, true)) {
            $this->error("Column '{$idColumn}' is not an allowed id column. Allowed: ".implode(', ', SendEmailVerificationChunkJob::ALLOWED_ID_COLUMNS));

            return self::FAILURE;
        }

        if (! in_array($emailColumn, SendEmailVerificationChunkJob::ALLOWED_EMAIL_COLUMNS, true)) {
            $this->error("Column '{$emailColumn}' is not an allowed email column. Allowed: ".implode(', ', SendEmailVerificationChunkJob::ALLOWED_EMAIL_COLUMNS));

            return self::FAILURE;
        }

        if (! Schema::hasColumn('users', $idColumn)) {
            $this->error("Column '{$idColumn}' does not exist on users table.");

            return self::FAILURE;
        }

        if (! Schema::hasColumn('users', $emailColumn)) {
            $this->error("Column '{$emailColumn}' does not exist on users table.");

            return self::FAILURE;
        }

        if ($limit < 0) {
            $this->error('--limit cannot be negative.');

            return self::FAILURE;
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

            return self::SUCCESS;
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

        return self::SUCCESS;
    }
}
