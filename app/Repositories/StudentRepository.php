<?php
namespace App\Repositories;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StudentRepository extends BaseRepository implements StudentRepositoryInterface
{
    private const MASTER_REPORT_SELECT_MAP = [
        'student_id' => 's.id',
        'student_name' => 's.name',
        'student_email' => 's.email',
        'course_code' => 'co.course_code',
        'course_title' => 'co.title',
        'semester' => 'e.semester',
        'grade' => 'e.grade',
        'instructor_name' => 'i.name',
        'instructor_specialization' => 'i.specialization',
        'classroom_room_number' => 'cl.room_number',
        'classroom_building' => 'cl.building',
        'schedule_day' => 'cs.day_of_week',
        'schedule_start_time' => 'cs.start_time',
        'department_name' => 'd.dept_name',
    ];

    private const DEFAULT_MASTER_REPORT_COLUMNS = [
        'student_name',
        'course_title',
        'instructor_name',
        'classroom_room_number',
        'department_name',
    ];

    protected function model(): string
    {
        return Student::class;
    }

    public function enroll(int $studentId, int $courseId, string $semester): array
    {
        $targetSchedule = DB::table('course_schedules')
            ->where('course_id', $courseId)
            ->select('day_of_week', 'start_time')
            ->get();

        if ($targetSchedule->isNotEmpty()) {
            $hasConflict = DB::table('enrollments as e')
                ->join('course_schedules as cs', 'e.course_id', '=', 'cs.course_id')
                ->where('e.student_id', $studentId)
                ->where('e.semester', $semester)
                ->where('e.course_id', '!=', $courseId)
                ->where(function ($query) use ($targetSchedule) {
                    foreach ($targetSchedule as $slot) {
                        $query->orWhere(function ($slotQuery) use ($slot) {
                            $slotQuery->where('cs.day_of_week', $slot->day_of_week)
                                ->where('cs.start_time', $slot->start_time);
                        });
                    }
                })
                ->exists();

            if ($hasConflict) {
                return [
                    'enrolled' => false,
                    'conflict' => true,
                ];
            }
        }

        $inserted = DB::table('enrollments')->insertOrIgnore([
            'student_id' => $studentId,
            'course_id'  => $courseId,
            'semester'   => $semester,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'enrolled' => (bool) $inserted,
            'conflict' => false,
        ];
    }

    public function updateGrade(int $studentId, int $courseId, string $grade): bool
    {
        return (bool) DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->update(['grade' => $grade, 'updated_at' => now()]);
    }

    public function masterReport(?int $studentId = null, array $columns = [], bool $includeAllStudents = false): array
    {
        $selectedColumns = collect($columns)
            ->filter(fn ($column) => array_key_exists($column, self::MASTER_REPORT_SELECT_MAP))
            ->unique()
            ->values();

        if ($selectedColumns->isEmpty()) {
            $selectedColumns = collect(self::DEFAULT_MASTER_REPORT_COLUMNS);
        }

        $query = DB::table('students as s')
            ->join('enrollments as e', 's.id', '=', 'e.student_id')
            ->join('courses as co', 'e.course_id', '=', 'co.id')
            ->leftJoin('course_assignments as ca', 'co.id', '=', 'ca.course_id')
            ->leftJoin('instructors as i', 'ca.instructor_id', '=', 'i.id')
            ->leftJoin('department_faculty as df', 'i.id', '=', 'df.instructor_id')
            ->leftJoin('departments as d', 'df.department_id', '=', 'd.id')
            ->leftJoin('course_schedules as cs', 'co.id', '=', 'cs.course_id')
            ->leftJoin('classrooms as cl', 'cs.classroom_id', '=', 'cl.id');

        if (! $includeAllStudents && $studentId !== null) {
            $query->where('s.id', $studentId);
        }

        foreach ($selectedColumns as $column) {
            $query->addSelect(DB::raw(self::MASTER_REPORT_SELECT_MAP[$column].' as '.$column));
        }

        $rows = $query
            ->orderBy('s.id')
            ->orderBy('co.id')
            ->get();

        return $rows
            ->map(function ($row) use ($selectedColumns) {
                $normalized = [];

                foreach ($selectedColumns as $column) {
                    $normalized[$column] = $row->{$column} ?? null;
                }

                return $normalized;
            })
            ->all();
    }
}
