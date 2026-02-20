<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $table = 'users';
        $hasName = Schema::hasColumn($table, 'name');
        $hasFirstName = Schema::hasColumn($table, 'first_name');
        $hasLastName = Schema::hasColumn($table, 'last_name');
        $hasEmail = Schema::hasColumn($table, 'email');
        $hasEmailAddress = Schema::hasColumn($table, 'email_address');
        $hasEmailVerifiedAt = Schema::hasColumn($table, 'email_verified_at');
        $hasPassword = Schema::hasColumn($table, 'password');
        $hasRememberToken = Schema::hasColumn($table, 'remember_token');
        $hasCreatedAt = Schema::hasColumn($table, 'created_at');
        $hasUpdatedAt = Schema::hasColumn($table, 'updated_at');

        $timestamp = now();
        $rows = [];

        for ($offset = 0; $offset < $this->chunkSize; $offset++) {
            $index = $this->startIndex + $offset;
            $email = "queued-{$this->runId}-{$index}@example.test";

            $row = [];

            if ($hasName) {
                $row['name'] = "Queued User {$index}";
            }

            if ($hasFirstName) {
                $row['first_name'] = 'Queued';
            }

            if ($hasLastName) {
                $row['last_name'] = "User {$index}";
            }

            if ($hasEmail) {
                $row['email'] = $email;
            }

            if ($hasEmailAddress) {
                $row['email_address'] = $email;
            }

            if ($hasEmailVerifiedAt) {
                $row['email_verified_at'] = $timestamp;
            }

            if ($hasPassword) {
                $row['password'] = $this->passwordHash;
            }

            if ($hasRememberToken) {
                $row['remember_token'] = null;
            }

            if ($hasCreatedAt) {
                $row['created_at'] = $timestamp;
            }

            if ($hasUpdatedAt) {
                $row['updated_at'] = $timestamp;
            }

            if (! empty($row)) {
                $rows[] = $row;
            }
        }

        if (! empty($rows)) {
            DB::table($table)->insert($rows);
        }
    }
}
