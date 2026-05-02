<?php

namespace Tests\Feature;

use App\Jobs\ExportStudentsCsvJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportStudentsCsvJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_a_csv_file_on_the_local_disk(): void
    {
        Storage::fake('local');

        $exportId = 'test-export-1';
        $job = new ExportStudentsCsvJob($exportId);

        $job->handle();

        // The generated path is stored in the cache.
        $path = cache()->get(ExportStudentsCsvJob::CACHE_KEY_PREFIX.$exportId);
        $this->assertNotNull($path, 'Cache entry was not written.');

        Storage::disk('local')->assertExists($path);
    }

    public function test_handle_stores_path_in_cache_with_correct_prefix(): void
    {
        Storage::fake('local');

        $exportId = 'cache-prefix-test';
        (new ExportStudentsCsvJob($exportId))->handle();

        $this->assertNotNull(cache()->get(ExportStudentsCsvJob::CACHE_KEY_PREFIX.$exportId));
    }

    public function test_csv_file_contains_header_row(): void
    {
        Storage::fake('local');

        $exportId = 'header-test';
        (new ExportStudentsCsvJob($exportId))->handle();

        $path = cache()->get(ExportStudentsCsvJob::CACHE_KEY_PREFIX.$exportId);
        $content = Storage::disk('local')->get($path);

        $this->assertStringContainsString('student_id', $content);
        $this->assertStringContainsString('student_name', $content);
        $this->assertStringContainsString('student_email', $content);
        $this->assertStringContainsString('course_code', $content);
        $this->assertStringContainsString('grade', $content);
    }

    public function test_handle_scopes_export_to_a_single_student(): void
    {
        Storage::fake('local');

        // Seed two students so we can verify that only Alice's data is exported.
        \Illuminate\Support\Facades\DB::table('students')->insert([
            ['name' => 'Alice', 'email' => 'alice@example.test', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bob',   'email' => 'bob@example.test',   'created_at' => now(), 'updated_at' => now()],
        ]);

        $aliceId = \Illuminate\Support\Facades\DB::table('students')->where('email', 'alice@example.test')->value('id');
        $exportId = 'scoped-export';

        (new ExportStudentsCsvJob($exportId, $aliceId))->handle();

        $path = cache()->get(ExportStudentsCsvJob::CACHE_KEY_PREFIX.$exportId);
        $content = Storage::disk('local')->get($path);

        // Alice's row should be present, Bob's should not.
        $this->assertStringContainsString('alice@example.test', $content);
        $this->assertStringNotContainsString('bob@example.test', $content);
    }

    public function test_different_export_ids_produce_separate_cache_entries(): void
    {
        Storage::fake('local');

        $id1 = 'export-a';
        $id2 = 'export-b';

        (new ExportStudentsCsvJob($id1))->handle();
        (new ExportStudentsCsvJob($id2))->handle();

        $path1 = cache()->get(ExportStudentsCsvJob::CACHE_KEY_PREFIX.$id1);
        $path2 = cache()->get(ExportStudentsCsvJob::CACHE_KEY_PREFIX.$id2);

        $this->assertNotSame($path1, $path2);
    }
}
