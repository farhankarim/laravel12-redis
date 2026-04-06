<?php
namespace App\Repositories;

use App\Models\Student;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StudentRepository extends BaseRepository implements StudentRepositoryInterface
{
    protected function model(): string
    {
        return Student::class;
    }

    public function enroll(int $studentId, int $courseId, string $semester): void
    {
        DB::table('enrollments')->insertOrIgnore([
            'student_id' => $studentId,
            'course_id'  => $courseId,
            'semester'   => $semester,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updateGrade(int $studentId, int $courseId, string $grade): bool
    {
        return (bool) DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->update(['grade' => $grade, 'updated_at' => now()]);
    }

    public function masterReport(int $studentId): array
    {
        return DB::select("
            SELECT
                s.name       AS student,
                co.title     AS course,
                i.name       AS instructor,
                cl.room_number AS room,
                d.dept_name  AS department
            FROM students s
            JOIN enrollments e       ON s.id = e.student_id
            JOIN courses co          ON e.course_id = co.id
            JOIN course_assignments ca ON co.id = ca.course_id
            JOIN instructors i       ON ca.instructor_id = i.id
            JOIN department_faculty df ON i.id = df.instructor_id
            JOIN departments d       ON df.department_id = d.id
            JOIN course_schedules cs ON co.id = cs.course_id
            JOIN classrooms cl       ON cs.classroom_id = cl.id
            WHERE s.id = ?
        ", [$studentId]);
    }
}
