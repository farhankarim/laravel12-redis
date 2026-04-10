<?php
namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface StudentRepositoryInterface extends RepositoryInterface
{
    public function enroll(int $studentId, int $courseId, string $semester): array;
    public function updateGrade(int $studentId, int $courseId, string $grade): bool;
    public function masterReport(?int $studentId = null, array $columns = [], bool $includeAllStudents = false): array;
}
