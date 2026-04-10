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
     * Bulk assign students to a course with semester.
     */
    public function bulkAssignStudents(int $courseId, array $studentIds, string $semester): int
    {
        $records = collect($studentIds)
            ->map(fn ($studentId) => [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'semester' => $semester,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        return DB::table('enrollments')->insertOrIgnore($records);
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
        return DB::table('instructors')
            ->whereNotIn('id', function ($query) use ($courseId) {
                $query->select('instructor_id')
                    ->from('course_assignments')
                    ->where('course_id', $courseId);
            })
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
     * Bulk assign instructors to a course.
     */
    public function bulkAssignInstructors(int $courseId, array $instructorIds): int
    {
        $records = collect($instructorIds)
            ->map(fn ($instructorId) => [
                'course_id' => $courseId,
                'instructor_id' => $instructorId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        return DB::table('course_assignments')->insertOrIgnore($records);
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
