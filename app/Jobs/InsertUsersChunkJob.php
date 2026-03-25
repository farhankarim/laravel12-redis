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
     * @param  int  $startIndex  1-based index used for deterministic user labels and emails.
     * @param  array<string, bool>  $columnFlags  Pre-computed column existence flags from
     *                                            QueueGenerateUsersCommand::detectColumnFlags().
     *                                            Avoids repeated schema introspection inside workers.
     */
    public function __construct(
        public int $startIndex,
        public int $chunkSize,
        public string $runId,
        public string $passwordHash,
        public array $columnFlags = [],
    ) {}

    public function handle(): void
    {
        $flags = $this->columnFlags;

        // Fallback: if flags were not pre-computed (e.g. job was dispatched by
        // an older version of the code), detect them here.
        if (empty($flags)) {
            $table = 'users';
            $flags = [
                'name' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'name'),
                'first_name' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'first_name'),
                'last_name' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'last_name'),
                'email' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'email'),
                'email_address' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'email_address'),
                'email_verified_at' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'email_verified_at'),
                'password' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'password'),
                'remember_token' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'remember_token'),
                'created_at' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'created_at'),
                'updated_at' => \Illuminate\Support\Facades\Schema::hasColumn($table, 'updated_at'),
            ];
        }

        $timestamp = now();
        $rows = [];

        for ($offset = 0; $offset < $this->chunkSize; $offset++) {
            $index = $this->startIndex + $offset;
            $email = "queued-{$this->runId}-{$index}@example.test";

            $row = [];

            if ($flags['name'] ?? false) {
                $row['name'] = "Queued User {$index}";
            }

            if ($flags['first_name'] ?? false) {
                $row['first_name'] = 'Queued';
            }

            if ($flags['last_name'] ?? false) {
                $row['last_name'] = "User {$index}";
            }

            if ($flags['email'] ?? false) {
                $row['email'] = $email;
            }

            if ($flags['email_address'] ?? false) {
                $row['email_address'] = $email;
            }

            if ($flags['email_verified_at'] ?? false) {
                $row['email_verified_at'] = $timestamp;
            }

            if ($flags['password'] ?? false) {
                $row['password'] = $this->passwordHash;
            }

            if ($flags['remember_token'] ?? false) {
                $row['remember_token'] = null;
            }

            if ($flags['created_at'] ?? false) {
                $row['created_at'] = $timestamp;
            }

            if ($flags['updated_at'] ?? false) {
                $row['updated_at'] = $timestamp;
            }

            if (! empty($row)) {
                $rows[] = $row;
            }
        }

        if (! empty($rows)) {
            DB::table('users')->insert($rows);
        }
    }
}
