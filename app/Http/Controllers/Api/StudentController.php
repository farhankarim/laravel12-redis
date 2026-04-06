<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(protected StudentRepositoryInterface $students) {}

    public function index(): JsonResponse
    {
        return response()->json($this->students->all(['*'], ['courses']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:students']);
        return response()->json($this->students->create($data), 201);
    }

    public function show(int $id): JsonResponse
    {
        $student = $this->students->find($id, ['*'], ['courses.instructors.departments', 'courses.classrooms']);
        return $student ? response()->json($student) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'email' => 'sometimes|email|unique:students,email,'.$id]);
        return response()->json(['updated' => $this->students->update($id, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->students->delete($id)]);
    }

    public function enroll(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['course_id' => 'required|integer|exists:courses,id', 'semester' => 'required|string|max:20']);
        $this->students->enroll($id, $data['course_id'], $data['semester']);
        return response()->json(['message' => 'Enrolled successfully']);
    }

    public function updateGrade(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['course_id' => 'required|integer|exists:courses,id', 'grade' => 'required|string|max:2']);
        return response()->json(['updated' => $this->students->updateGrade($id, $data['course_id'], $data['grade'])]);
    }

    public function masterReport(int $id): JsonResponse
    {
        return response()->json($this->students->masterReport($id));
    }
}
