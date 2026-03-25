<?php

namespace App\Console\Commands;

use App\Jobs\InsertUsersChunkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QueueGenerateUsersCommand extends Command
{
    protected $signature = 'users:queue-generate
        {--total=1000000 : Total users to generate}
        {--chunk=1000 : Users inserted per queued job}
        {--connection=redis : Queue connection name}
        {--queue=user-imports : Queue name}
        {--run-id= : Optional run id used in generated emails}';

    protected $description = 'Queue generation of many users in chunked jobs';

    public function handle(): int
    {
        $total = (int) $this->option('total');
        $chunk = (int) $this->option('chunk');
        $connection = (string) $this->option('connection');
        $queue = (string) $this->option('queue');
        $runId = (string) ($this->option('run-id') ?: Str::lower(Str::ulid()->toBase32()));

        if ($total < 1) {
            $this->error('--total must be greater than 0.');

            return self::FAILURE;
        }

        if ($chunk < 1 || $chunk > 10000) {
            $this->error('--chunk must be between 1 and 10000.');

            return self::FAILURE;
        }

        // Detect column availability once here so each queued job does not
        // need to perform schema introspection on execution.
        $columnFlags = $this->detectColumnFlags();

        $passwordHash = Hash::make('password');
        $jobs = (int) ceil($total / $chunk);

        for ($index = 1; $index <= $total; $index += $chunk) {
            $chunkSize = min($chunk, $total - $index + 1);

            InsertUsersChunkJob::dispatch($index, $chunkSize, $runId, $passwordHash, $columnFlags)
                ->onConnection($connection)
                ->onQueue($queue);
        }

        $this->info("Queued {$total} users as {$jobs} jobs on {$connection}:{$queue}.");
        $this->line("Run id: {$runId}");
        $this->line("Start workers with: php artisan queue:work {$connection} --queue={$queue}");

        return self::SUCCESS;
    }

    /**
     * Detect which columns exist in the users table once before dispatching
     * any jobs, avoiding thousands of schema introspection queries.
     *
     * @return array<string, bool>
     */
    private function detectColumnFlags(): array
    {
        $table = 'users';

        return [
            'name' => Schema::hasColumn($table, 'name'),
            'first_name' => Schema::hasColumn($table, 'first_name'),
            'last_name' => Schema::hasColumn($table, 'last_name'),
            'email' => Schema::hasColumn($table, 'email'),
            'email_address' => Schema::hasColumn($table, 'email_address'),
            'email_verified_at' => Schema::hasColumn($table, 'email_verified_at'),
            'password' => Schema::hasColumn($table, 'password'),
            'remember_token' => Schema::hasColumn($table, 'remember_token'),
            'created_at' => Schema::hasColumn($table, 'created_at'),
            'updated_at' => Schema::hasColumn($table, 'updated_at'),
        ];
    }
}
