<?php

namespace App\Console\Commands;

use App\Jobs\GenerateStudentsChunkJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueGenerateStudentsCommand extends Command
{
    protected $signature = 'students:queue-generate
        {--total=1000000 : Total students to generate}
        {--chunk=500 : Students inserted per queued job}
        {--connection=redis : Queue connection name}
        {--queue=student-imports : Queue name}
        {--run-id= : Optional run id used in generated emails}
        {--departments=10 : Number of departments to seed}
        {--instructors=50 : Number of instructors to seed}
        {--classrooms=30 : Number of classrooms to seed}
        {--courses=100 : Number of courses to seed}
        {--enrollments-per-student=4 : Courses each student is enrolled in}';

    protected $description = 'Seed shared university entities then queue generation of 1 million students with all linked data';

    private const DEPARTMENT_NAMES = [
        'Computer Science', 'Mathematics', 'Physics', 'Chemistry',
        'Biology', 'History', 'English Literature', 'Economics',
        'Psychology', 'Mechanical Engineering', 'Electrical Engineering',
        'Civil Engineering', 'Business Administration', 'Philosophy',
        'Political Science', 'Sociology', 'Art & Design', 'Music',
        'Architecture', 'Law',
    ];

    private const SPECIALIZATIONS = [
        'Algorithms', 'Machine Learning', 'Data Structures', 'Operating Systems',
        'Networking', 'Calculus', 'Linear Algebra', 'Quantum Physics',
        'Organic Chemistry', 'Genetics', 'Medieval History', 'Modern Literature',
        'Microeconomics', 'Cognitive Psychology', 'Fluid Mechanics',
        'Circuit Theory', 'Structural Analysis', 'Marketing', 'Ethics',
        'Constitutional Law', 'Thermodynamics', 'Compiler Design',
        'Database Systems', 'Software Engineering', 'Cryptography',
    ];

    private const BUILDINGS = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Omega'];

    private const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    private const START_TIMES = ['08:00:00', '09:30:00', '11:00:00', '13:00:00', '14:30:00', '16:00:00'];

    public function handle(): int
    {
        $total = (int) $this->option('total');
        $chunk = (int) $this->option('chunk');
        $connection = (string) $this->option('connection');
        $queue = (string) $this->option('queue');
        $runId = (string) ($this->option('run-id') ?: Str::lower(Str::ulid()->toBase32()));
        $deptCount = (int) $this->option('departments');
        $instrCount = (int) $this->option('instructors');
        $classCount = (int) $this->option('classrooms');
        $courseCount = (int) $this->option('courses');
        $enrolPerStudent = (int) $this->option('enrollments-per-student');

        if ($total < 1) {
            $this->error('--total must be greater than 0.');

            return self::FAILURE;
        }

        if ($chunk < 1 || $chunk > 10000) {
            $this->error('--chunk must be between 1 and 10000.');

            return self::FAILURE;
        }

        $this->info("Run ID: {$runId}");

        // ── 1. Seed shared entities ────────────────────────────────────────────

        $this->info('Seeding departments…');
        $departmentIds = $this->seedDepartments($deptCount, $runId);

        $this->info('Seeding instructors…');
        $instructorIds = $this->seedInstructors($instrCount, $runId);

        $this->info('Linking instructors ↔ departments…');
        $this->seedDepartmentFaculty($departmentIds, $instructorIds);

        $this->info('Seeding classrooms…');
        $classroomIds = $this->seedClassrooms($classCount, $runId);

        $this->info('Seeding courses…');
        $courseIds = $this->seedCourses($courseCount, $runId);

        $this->info('Assigning instructors → courses…');
        $this->seedCourseAssignments($courseIds, $instructorIds);

        $this->info('Scheduling courses in classrooms…');
        $this->seedCourseSchedules($courseIds, $classroomIds);

        // ── 2. Dispatch student chunk jobs ─────────────────────────────────────

        $jobs = (int) ceil($total / $chunk);
        $this->info("Queuing {$total} students as {$jobs} jobs on {$connection}:{$queue}…");

        for ($index = 1; $index <= $total; $index += $chunk) {
            $chunkSize = min($chunk, $total - $index + 1);

            GenerateStudentsChunkJob::dispatch($index, $chunkSize, $runId, $courseIds, $enrolPerStudent)
                ->onConnection($connection)
                ->onQueue($queue);
        }

        $this->info("Done. Start workers with:");
        $this->line("  php artisan queue:work {$connection} --queue={$queue}");

        return self::SUCCESS;
    }

    // ── Entity seeders ─────────────────────────────────────────────────────────

    /** @return array<int> */
    private function seedDepartments(int $count, string $runId): array
    {
        $timestamp = now();
        $names = array_slice(self::DEPARTMENT_NAMES, 0, $count);

        // Pad with generated names if $count exceeds predefined list.
        for ($i = count($names); $i < $count; $i++) {
            $names[] = "Department-{$runId}-{$i}";
        }

        $rows = array_map(fn ($name) => [
            'dept_name' => $name,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $names);

        DB::table('departments')->insert($rows);

        return DB::table('departments')
            ->orderByDesc('id')
            ->limit($count)
            ->pluck('id')
            ->all();
    }

    /** @return array<int> */
    private function seedInstructors(int $count, string $runId): array
    {
        $timestamp = now();
        $specs = self::SPECIALIZATIONS;
        $specCount = count($specs);

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'name' => "Instructor {$runId}-{$i}",
                'specialization' => $specs[($i - 1) % $specCount],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('instructors')->insert($rows);

        return DB::table('instructors')
            ->orderByDesc('id')
            ->limit($count)
            ->pluck('id')
            ->all();
    }

    /** Link each instructor to 1–2 departments. */
    private function seedDepartmentFaculty(array $departmentIds, array $instructorIds): void
    {
        $timestamp = now();
        $deptCount = count($departmentIds);
        $rows = [];
        $seen = [];

        foreach ($instructorIds as $idx => $instrId) {
            // Primary department (round-robin).
            $deptId = $departmentIds[$idx % $deptCount];
            $key = "{$deptId}-{$instrId}";
            if (! isset($seen[$key])) {
                $rows[] = ['department_id' => $deptId, 'instructor_id' => $instrId,
                    'created_at' => $timestamp, 'updated_at' => $timestamp];
                $seen[$key] = true;
            }

            // Secondary department (offset by half).
            $deptId2 = $departmentIds[($idx + (int) ceil($deptCount / 2)) % $deptCount];
            $key2 = "{$deptId2}-{$instrId}";
            if ($deptId2 !== $deptId && ! isset($seen[$key2])) {
                $rows[] = ['department_id' => $deptId2, 'instructor_id' => $instrId,
                    'created_at' => $timestamp, 'updated_at' => $timestamp];
                $seen[$key2] = true;
            }
        }

        foreach (array_chunk($rows, 1000) as $batch) {
            DB::table('department_faculty')->insert($batch);
        }
    }

    /** @return array<int> */
    private function seedClassrooms(int $count, string $runId): array
    {
        $timestamp = now();
        $buildings = self::BUILDINGS;
        $buildCount = count($buildings);

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'room_number' => "R{$i}",
                'building' => $buildings[($i - 1) % $buildCount],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('classrooms')->insert($rows);

        return DB::table('classrooms')
            ->orderByDesc('id')
            ->limit($count)
            ->pluck('id')
            ->all();
    }

    /** @return array<int> */
    private function seedCourses(int $count, string $runId): array
    {
        $timestamp = now();
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'course_code' => strtoupper("CRS-{$runId}-{$i}"),
                'title' => "Course {$runId} #{$i}",
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table('courses')->insert($rows);

        return DB::table('courses')
            ->orderByDesc('id')
            ->limit($count)
            ->pluck('id')
            ->all();
    }

    /** Assign one instructor per course (round-robin). */
    private function seedCourseAssignments(array $courseIds, array $instructorIds): void
    {
        $timestamp = now();
        $instrCount = count($instructorIds);
        $rows = [];

        foreach ($courseIds as $idx => $courseId) {
            $instrId = $instructorIds[$idx % $instrCount];
            $rows[] = ['instructor_id' => $instrId, 'course_id' => $courseId,
                'created_at' => $timestamp, 'updated_at' => $timestamp];
        }

        foreach (array_chunk($rows, 1000) as $batch) {
            DB::table('course_assignments')->insert($batch);
        }
    }

    /** Schedule each course in one classroom on one weekday. */
    private function seedCourseSchedules(array $courseIds, array $classroomIds): void
    {
        $timestamp = now();
        $classCount = count($classroomIds);
        $days = self::DAYS;
        $times = self::START_TIMES;
        $rows = [];
        $seen = [];

        foreach ($courseIds as $idx => $courseId) {
            $classroomId = $classroomIds[$idx % $classCount];
            $day = $days[$idx % count($days)];
            $time = $times[$idx % count($times)];

            $key = "{$courseId}-{$classroomId}-{$day}";
            if (! isset($seen[$key])) {
                $rows[] = ['course_id' => $courseId, 'classroom_id' => $classroomId,
                    'day_of_week' => $day, 'start_time' => $time,
                    'created_at' => $timestamp, 'updated_at' => $timestamp];
                $seen[$key] = true;
            }
        }

        foreach (array_chunk($rows, 1000) as $batch) {
            DB::table('course_schedules')->insert($batch);
        }
    }
}
