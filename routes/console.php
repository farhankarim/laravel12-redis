<?php

use App\Jobs\InsertUsersChunkJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
