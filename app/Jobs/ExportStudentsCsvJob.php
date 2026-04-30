<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Exports the full student master report to a CSV file and stores it on the
 * configured filesystem disk (S3 in production, local otherwise).
 *
 * The job stores the generated path in the cache so callers can later resolve
 * a temporary download URL via Storage::disk($disk)->temporaryUrl($path, …).
 */
class ExportStudentsCsvJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    /** Cache key prefix used to store the generated file path. */
    public const CACHE_KEY_PREFIX = 'student_csv_export:';

    public function __construct(
        /** Unique identifier so the caller can poll for completion. */
        public readonly string $exportId = '',
        /** Optional filter: when set only this student's rows are exported. */
        public readonly ?int $studentId = null,
    ) {}

    public function handle(): void
    {
        $disk = $this->resolveDisk();

        $rows = $this->fetchRows();

        $csv = $this->buildCsv($rows);

        $directory = 'exports/students';
        $filename = "students_{$this->exportId}_".now()->format('Ymd_His').'.csv';
        $path = "{$directory}/{$filename}";

        Storage::disk($disk)->put($path, $csv, 'private');

        // Store the path in the cache for 24 hours so the requester can
        // generate a temporary signed URL once the job completes.
        cache()->put(
            self::CACHE_KEY_PREFIX.$this->exportId,
            $path,
            now()->addHours(24),
        );
    }

    /**
     * @return array<int, object>
     */
    private function fetchRows(): array
    {
        $query = DB::table('students as s')
            ->leftJoin('enrollments as e', 's.id', '=', 'e.student_id')
            ->leftJoin('courses as c', 'e.course_id', '=', 'c.id')
            ->leftJoin('course_assignments as ca', 'c.id', '=', 'ca.course_id')
            ->leftJoin('instructors as i', 'ca.instructor_id', '=', 'i.id')
            ->leftJoin('course_schedules as cs', 'c.id', '=', 'cs.course_id')
            ->leftJoin('classrooms as cl', 'cs.classroom_id', '=', 'cl.id')
            ->leftJoin('department_faculty as df', 'i.id', '=', 'df.instructor_id')
            ->leftJoin('departments as d', 'df.department_id', '=', 'd.id')
            ->select([
                's.id as student_id',
                's.name as student_name',
                's.email as student_email',
                'c.course_code as course_code',
                'c.title as course_title',
                'e.semester',
                'e.grade',
                'i.name as instructor_name',
                'i.specialization as instructor_specialization',
                'cl.room_number as classroom_room_number',
                'cl.building as classroom_building',
                'cs.day_of_week as schedule_day',
                'cs.start_time as schedule_start_time',
                'd.dept_name as department_name',
            ]);

        if ($this->studentId !== null) {
            $query->where('s.id', $this->studentId);
        }

        return $query->orderBy('s.id')->orderBy('e.semester')->get()->all();
    }

    /**
     * @param  array<int, object>  $rows
     */
    private function buildCsv(array $rows): string
    {
        $headers = [
            'student_id',
            'student_name',
            'student_email',
            'course_code',
            'course_title',
            'semester',
            'grade',
            'instructor_name',
            'instructor_specialization',
            'classroom_room_number',
            'classroom_building',
            'schedule_day',
            'schedule_start_time',
            'department_name',
        ];

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $col) {
                $line[] = data_get($row, $col) ?? '';
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function resolveDisk(): string
    {
        return config('filesystems.default') === 's3' ? 's3' : 'local';
    }
}
