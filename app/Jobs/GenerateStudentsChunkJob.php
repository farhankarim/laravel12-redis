<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class GenerateStudentsChunkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    private const SEMESTERS = ['Fall 2024', 'Spring 2025', 'Fall 2025', 'Spring 2026'];

    private const GRADES = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F', null];

    /**
     * @param  int  $startIndex  1-based index for deterministic student email generation.
     * @param  array<int>  $courseIds  Pool of pre-created course IDs to enrol each student in.
     * @param  int  $enrollmentsPerStudent  How many courses each student is enrolled in.
     */
    public function __construct(
        public int $startIndex,
        public int $chunkSize,
        public string $runId,
        public array $courseIds,
        public array $courseScheduleMap,
        public int $enrollmentsPerStudent = 4,
    ) {}

    public function handle(): void
    {
        if (empty($this->courseIds)) {
            return;
        }

        $timestamp = now();
        $coursePool = $this->courseIds;
        $poolSize = count($coursePool);
        $semesters = self::SEMESTERS;
        $grades = self::GRADES;
        $enrolCount = min($this->enrollmentsPerStudent, $poolSize);

        // Build student rows for bulk insert.
        $studentRows = [];
        for ($offset = 0; $offset < $this->chunkSize; $offset++) {
            $index = $this->startIndex + $offset;
            $studentRows[] = [
                'name' => "Student {$index}",
                'email' => "student-{$this->runId}-{$index}@university.edu",
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('students')->insert($studentRows);

        // Retrieve the IDs of the just-inserted students using the deterministic emails.
        $emails = array_column($studentRows, 'email');
        $studentIds = DB::table('students')
            ->whereIn('email', $emails)
            ->pluck('id')
            ->all();

        if (empty($studentIds)) {
            return;
        }

        // Build enrollment rows — each student gets $enrolCount distinct random courses.
        $enrollmentRows = [];
        foreach ($studentIds as $studentId) {
            $picked = $this->pickDistinct($coursePool, $poolSize, $enrolCount);
            $usedSlotsBySemester = [];

            foreach ($picked as $courseId) {
                $semester = $this->pickNonConflictingSemester($courseId, $semesters, $usedSlotsBySemester);

                if ($semester === null) {
                    continue;
                }

                $enrollmentRows[] = [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'semester' => $semester,
                    'grade' => $grades[array_rand($grades)],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                foreach (($this->courseScheduleMap[$courseId] ?? []) as $slot) {
                    $usedSlotsBySemester[$semester][$slot['day_of_week'].'|'.$slot['start_time']] = true;
                }
            }
        }

        if (! empty($enrollmentRows)) {
            DB::table('enrollments')->insert($enrollmentRows);
        }
    }

    /**
     * Pick $count distinct elements from $pool without replacement.
     *
     * @param  array<int>  $pool
     * @return array<int>
     */
    private function pickDistinct(array $pool, int $poolSize, int $count): array
    {
        if ($count >= $poolSize) {
            return $pool;
        }

        $keys = array_rand($pool, $count);

        return array_map(fn ($k) => $pool[$k], (array) $keys);
    }

    private function pickNonConflictingSemester(int $courseId, array $semesters, array $usedSlotsBySemester): ?string
    {
        $shuffledSemesters = $semesters;
        shuffle($shuffledSemesters);

        $courseSlots = $this->courseScheduleMap[$courseId] ?? [];

        foreach ($shuffledSemesters as $semester) {
            $hasConflict = false;

            foreach ($courseSlots as $slot) {
                $slotKey = $slot['day_of_week'].'|'.$slot['start_time'];
                if (! empty($usedSlotsBySemester[$semester][$slotKey])) {
                    $hasConflict = true;
                    break;
                }
            }

            if (! $hasConflict) {
                return $semester;
            }
        }

        return null;
    }
}
