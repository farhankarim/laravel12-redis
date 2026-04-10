<?php
namespace App\Repositories\Contracts;

interface CourseRepositoryInterface extends RepositoryInterface
{
	public function getAvailableStudents(int $courseId): array;
	public function getAssignedStudents(int $courseId): array;
	public function validateStudentAssignmentConflicts(int $courseId, array $studentIds, string $semester): array;
	public function bulkAssignStudents(int $courseId, array $studentIds, string $semester): array;
	public function revokeStudents(int $courseId, array $studentIds): int;
	public function getAvailableInstructors(int $courseId): array;
	public function getAssignedInstructors(int $courseId): array;
	public function bulkAssignInstructors(int $courseId, array $instructorIds): int;
	public function revokeInstructors(int $courseId, array $instructorIds): int;
}
