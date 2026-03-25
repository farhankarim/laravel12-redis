<?php

namespace Tests\Feature;

use App\Jobs\InsertUsersChunkJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsertUsersChunkJobTest extends TestCase
{
    use RefreshDatabase;

    private array $fullColumnFlags = [
        'name' => true,
        'first_name' => false,
        'last_name' => false,
        'email' => true,
        'email_address' => false,
        'email_verified_at' => true,
        'password' => true,
        'remember_token' => true,
        'created_at' => true,
        'updated_at' => true,
    ];

    public function test_handle_inserts_correct_number_of_rows(): void
    {
        $job = new InsertUsersChunkJob(
            startIndex: 1,
            chunkSize: 5,
            runId: 'test-run',
            passwordHash: bcrypt('secret'),
            columnFlags: $this->fullColumnFlags,
        );

        $job->handle();

        $this->assertDatabaseCount('users', 5);
    }

    public function test_handle_generates_deterministic_emails(): void
    {
        $job = new InsertUsersChunkJob(
            startIndex: 1,
            chunkSize: 3,
            runId: 'abc',
            passwordHash: bcrypt('secret'),
            columnFlags: $this->fullColumnFlags,
        );

        $job->handle();

        for ($i = 1; $i <= 3; $i++) {
            $this->assertDatabaseHas('users', ['email' => "queued-abc-{$i}@example.test"]);
        }
    }

    public function test_handle_sets_correct_start_index_offset(): void
    {
        $job = new InsertUsersChunkJob(
            startIndex: 101,
            chunkSize: 2,
            runId: 'run',
            passwordHash: bcrypt('secret'),
            columnFlags: $this->fullColumnFlags,
        );

        $job->handle();

        $this->assertDatabaseHas('users', ['email' => 'queued-run-101@example.test']);
        $this->assertDatabaseHas('users', ['email' => 'queued-run-102@example.test']);
    }

    public function test_handle_inserts_nothing_when_chunk_size_is_zero(): void
    {
        $job = new InsertUsersChunkJob(
            startIndex: 1,
            chunkSize: 0,
            runId: 'empty',
            passwordHash: bcrypt('secret'),
            columnFlags: $this->fullColumnFlags,
        );

        $job->handle();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_handle_skips_optional_column_when_flag_is_false(): void
    {
        // email_verified_at is nullable so excluding it via flags is safe to test.
        $flags = array_merge($this->fullColumnFlags, ['email_verified_at' => false]);

        $job = new InsertUsersChunkJob(
            startIndex: 1,
            chunkSize: 1,
            runId: 'noverify',
            passwordHash: bcrypt('secret'),
            columnFlags: $flags,
        );

        $job->handle();

        $user = \Illuminate\Support\Facades\DB::table('users')->first();
        $this->assertNull($user->email_verified_at);
    }

    public function test_handle_falls_back_to_schema_detection_when_flags_are_empty(): void
    {
        $job = new InsertUsersChunkJob(
            startIndex: 1,
            chunkSize: 2,
            runId: 'fallback',
            passwordHash: bcrypt('secret'),
            columnFlags: [],  // trigger fallback path
        );

        $job->handle();

        $this->assertDatabaseCount('users', 2);
    }
}
