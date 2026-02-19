<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class InsertUsersChunkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    /**
     * @param int $startIndex 1-based index used for deterministic user labels and emails.
     */
    public function __construct(
        public int $startIndex,
        public int $chunkSize,
        public string $runId,
        public string $passwordHash,
    ) {
    }

    public function handle(): void
    {
        $timestamp = now();
        $rows = [];

        for ($offset = 0; $offset < $this->chunkSize; $offset++) {
            $index = $this->startIndex + $offset;

            $rows[] = [
                'name' => "Queued User {$index}",
                'email' => "queued-{$this->runId}-{$index}@example.test",
                'email_verified_at' => $timestamp,
                'password' => $this->passwordHash,
                'remember_token' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('users')->insert($rows);
    }
}
