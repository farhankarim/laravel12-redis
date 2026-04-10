<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(protected CourseRepositoryInterface $courses) {}

    public function index(): JsonResponse
    {
        return response()->json($this->courses->all(['*'], ['instructors', 'classrooms']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['course_code' => 'required|string|unique:courses', 'title' => 'required|string|max:255']);
        return response()->json($this->courses->create($data), 201);
    }

    public function show(int $id): JsonResponse
    {
        $course = $this->courses->find($id, ['*'], ['students', 'instructors', 'classrooms']);
        return $course ? response()->json($course) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['course_code' => 'sometimes|string|unique:courses,course_code,'.$id, 'title' => 'sometimes|string|max:255']);
        return response()->json(['updated' => $this->courses->update($id, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->courses->delete($id)]);
    }

    public function getAvailableStudents(int $courseId): JsonResponse
    {
        $students = $this->courses->getAvailableStudents($courseId);
        return response()->json($students);
    }

    public function getAssignedStudents(int $courseId): JsonResponse
    {
        $students = $this->courses->getAssignedStudents($courseId);
        return response()->json($students);
    }

    public function assignStudents(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'semester' => 'required|string|max:20',
        ]);

        $count = $this->courses->bulkAssignStudents($courseId, $data['student_ids'], $data['semester']);

        return response()->json([
            'message' => "Assigned {$count} student(s) to course.",
            'count' => $count,
        ], 201);
    }

    public function revokeStudents(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        $count = $this->courses->revokeStudents($courseId, $data['student_ids']);

        return response()->json([
            'message' => "Revoked {$count} student(s) from course.",
            'count' => $count,
        ]);
    }

    public function getAvailableInstructors(int $courseId): JsonResponse
    {
        $instructors = $this->courses->getAvailableInstructors($courseId);
        return response()->json($instructors);
    }

    public function getAssignedInstructors(int $courseId): JsonResponse
    {
        $instructors = $this->courses->getAssignedInstructors($courseId);
        return response()->json($instructors);
    }

    public function assignInstructors(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'instructor_ids' => 'required|array|min:1',
            'instructor_ids.*' => 'required|integer|exists:instructors,id',
        ]);

        $count = $this->courses->bulkAssignInstructors($courseId, $data['instructor_ids']);

        return response()->json([
            'message' => "Assigned {$count} instructor(s) to course.",
            'count' => $count,
        ], 201);
    }

    public function revokeInstructors(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'instructor_ids' => 'required|array|min:1',
            'instructor_ids.*' => 'required|integer|exists:instructors,id',
        ]);

        $count = $this->courses->revokeInstructors($courseId, $data['instructor_ids']);

        return response()->json([
            'message' => "Revoked {$count} instructor(s) from course.",
            'count' => $count,
        ]);
    }
}
