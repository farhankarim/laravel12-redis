<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    private const MASTER_REPORT_COLUMNS = [
        'student_id',
        'student_name',
        'student_email',
        'course_code',
        'course_title',
        'semester',
        'grade',
        'instructor_name',
        'instructor_specialization',
        'classroom_room_number',
        'classroom_building',
        'schedule_day',
        'schedule_start_time',
        'department_name',
    ];

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
        $result = $this->students->enroll($id, $data['course_id'], $data['semester']);

        if ($result['conflict']) {
            return response()->json([
                'message' => 'Enrollment blocked due to schedule overlap in the same semester.',
            ], 422);
        }

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

    public function masterReportBuilder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => 'required|in:all,student',
            'student_id' => 'nullable|required_if:scope,student|integer|exists:students,id',
            'columns' => 'required|array|min:1',
            'columns.*' => 'required|string|in:'.implode(',', self::MASTER_REPORT_COLUMNS),
        ]);

        $scope = $data['scope'];
        $studentId = $scope === 'student' ? (int) $data['student_id'] : null;
        $columns = array_values(array_unique($data['columns']));

        $rows = $this->students->masterReport(
            $studentId,
            $columns,
            $scope === 'all',
        );

        return response()->json([
            'meta' => [
                'scope' => $scope,
                'student_id' => $studentId,
                'columns' => $columns,
                'total_rows' => count($rows),
            ],
            'rows' => $rows,
        ]);
    }
}
