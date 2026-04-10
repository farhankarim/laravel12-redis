<?php
namespace App\Repositories;

use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    protected function model(): string { return Course::class; }

    /**
     * Get students not yet enrolled in this course.
     */
    public function getAvailableStudents(int $courseId): array
    {
        return DB::table('students')
            ->whereNotIn('id', function ($query) use ($courseId) {
                $query->select('student_id')
                    ->from('enrollments')
                    ->where('course_id', $courseId);
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get students already enrolled in this course.
     */
    public function getAssignedStudents(int $courseId): array
    {
        return DB::table('students')
            ->join('enrollments', 'students.id', '=', 'enrollments.student_id')
            ->where('enrollments.course_id', $courseId)
            ->select('students.id', 'students.name', 'students.email')
            ->orderBy('students.name')
            ->get()
            ->toArray();
    }

    /**
     * Return conflict analysis for candidate student assignment.
     */
    public function validateStudentAssignmentConflicts(int $courseId, array $studentIds, string $semester): array
    {
        $targetStudentIds = collect($studentIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($targetStudentIds->isEmpty()) {
            return [
                'requested_count' => 0,
                'assignable_count' => 0,
                'conflict_count' => 0,
                'assignable_student_ids' => [],
                'conflict_students' => [],
            ];
        }

        $targetSchedule = DB::table('course_schedules')
            ->where('course_id', $courseId)
            ->select('day_of_week', 'start_time')
            ->get();

        $conflictStudentIds = collect();

        if ($targetSchedule->isNotEmpty()) {
            $conflictRows = DB::table('enrollments as e')
                ->join('course_schedules as cs', 'e.course_id', '=', 'cs.course_id')
                ->whereIn('e.student_id', $targetStudentIds->all())
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
                ->select('e.student_id')
                ->distinct()
                ->get();

            $conflictStudentIds = $conflictRows
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->values();
        }

        $assignableStudentIds = $targetStudentIds
            ->reject(fn ($id) => $conflictStudentIds->contains($id))
            ->values();

        $conflictStudents = [];

        if ($conflictStudentIds->isNotEmpty()) {
            $conflictStudents = DB::table('students')
                ->whereIn('id', $conflictStudentIds->all())
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn ($student) => [
                    'id' => (int) $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ])
                ->all();
        }

        return [
            'requested_count' => $targetStudentIds->count(),
            'assignable_count' => $assignableStudentIds->count(),
            'conflict_count' => $conflictStudentIds->count(),
            'assignable_student_ids' => $assignableStudentIds->all(),
            'conflict_students' => $conflictStudents,
        ];
    }

    /**
     * Bulk assign students with strict same-semester overlap prevention.
     */
    public function bulkAssignStudents(int $courseId, array $studentIds, string $semester): array
    {
        $validation = $this->validateStudentAssignmentConflicts($courseId, $studentIds, $semester);
        $assignableStudentIds = collect($validation['assignable_student_ids'] ?? []);

        $assignedCount = 0;

        if ($assignableStudentIds->isNotEmpty()) {
            $records = $assignableStudentIds
                ->map(fn ($studentId) => [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'semester' => $semester,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->toArray();

            $assignedCount = DB::table('enrollments')->insertOrIgnore($records);
        }

        return [
            'assigned_count' => (int) $assignedCount,
            'conflict_count' => (int) ($validation['conflict_count'] ?? 0),
            'conflict_students' => $validation['conflict_students'] ?? [],
        ];
    }

    /**
     * Revoke students from a course.
     */
    public function revokeStudents(int $courseId, array $studentIds): int
    {
        return DB::table('enrollments')
            ->where('course_id', $courseId)
            ->whereIn('student_id', $studentIds)
            ->delete();
    }

    /**
     * Get instructors not yet assigned to this course.
     */
    public function getAvailableInstructors(int $courseId): array
    {
        $assignedInstructorId = DB::table('course_assignments')
            ->where('course_id', $courseId)
            ->value('instructor_id');

        return DB::table('instructors')
            ->when($assignedInstructorId, fn ($query) => $query->where('id', '!=', $assignedInstructorId))
            ->select('id', 'name', 'specialization')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get instructors already assigned to this course.
     */
    public function getAssignedInstructors(int $courseId): array
    {
        return DB::table('instructors')
            ->join('course_assignments', 'instructors.id', '=', 'course_assignments.instructor_id')
            ->where('course_assignments.course_id', $courseId)
            ->select('instructors.id', 'instructors.name', 'instructors.specialization')
            ->orderBy('instructors.name')
            ->get()
            ->toArray();
    }

    /**
     * Assign one instructor to a course (replace any existing assignment).
     */
    public function bulkAssignInstructors(int $courseId, array $instructorIds): int
    {
        $instructorId = (int) ($instructorIds[0] ?? 0);
        if ($instructorId <= 0) {
            return 0;
        }

        $now = now();
        DB::table('course_assignments')->updateOrInsert(
            ['course_id' => $courseId],
            [
                'instructor_id' => $instructorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return 1;
    }

    /**
     * Revoke instructors from a course.
     */
    public function revokeInstructors(int $courseId, array $instructorIds): int
    {
        return DB::table('course_assignments')
            ->where('course_id', $courseId)
            ->whereIn('instructor_id', $instructorIds)
            ->delete();
    }
}
