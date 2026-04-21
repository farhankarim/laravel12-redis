<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CourseController extends Controller
{
    public function __construct(protected CourseRepositoryInterface $courses) {}

    #[OA\Get(
        path: '/v1/courses',
        summary: 'List all courses',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Array of courses',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Course'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json($this->courses->all(['*'], ['instructors', 'classrooms']));
    }

    #[OA\Post(
        path: '/v1/courses',
        summary: 'Create a new course',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['course_code', 'title', 'credit_hours'],
                properties: [
                    new OA\Property(property: 'course_code', type: 'string', example: 'CS101'),
                    new OA\Property(property: 'title', type: 'string', example: 'Introduction to Computer Science'),
                    new OA\Property(property: 'credit_hours', type: 'integer', minimum: 1, maximum: 6, example: 3),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Courses'],
        responses: [
            new OA\Response(response: 201, description: 'Course created', content: new OA\JsonContent(ref: '#/components/schemas/Course')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course_code' => 'required|string|unique:courses',
            'title' => 'required|string|max:255',
            'credit_hours' => 'required|integer|min:1|max:6',
        ]);
        return response()->json($this->courses->create($data), 201);
    }

    #[OA\Get(
        path: '/v1/courses/{id}',
        summary: 'Get a course by ID',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Course with relationships', content: new OA\JsonContent(ref: '#/components/schemas/Course')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $course = $this->courses->find($id, ['*'], ['students', 'instructors', 'classrooms']);
        return $course ? response()->json($course) : response()->json(['message' => 'Not found'], 404);
    }

    #[OA\Put(
        path: '/v1/courses/{id}',
        summary: 'Update a course',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'course_code', type: 'string', example: 'CS101B'),
                    new OA\Property(property: 'title', type: 'string', example: 'Intro to CS (Updated)'),
                    new OA\Property(property: 'credit_hours', type: 'integer', minimum: 1, maximum: 6, example: 4),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Update result',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'updated', type: 'boolean', example: true)])
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'course_code' => 'sometimes|string|unique:courses,course_code,'.$id,
            'title' => 'sometimes|string|max:255',
            'credit_hours' => 'sometimes|integer|min:1|max:6',
        ]);
        return response()->json(['updated' => $this->courses->update($id, $data)]);
    }

    #[OA\Delete(
        path: '/v1/courses/{id}',
        summary: 'Delete a course',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Delete result',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'deleted', type: 'boolean', example: true)])
            ),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->courses->delete($id)]);
    }

    #[OA\Get(
        path: '/v1/courses/{id}/available-students',
        summary: 'List students not yet enrolled in the course',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Array of available students'),
        ]
    )]
    public function getAvailableStudents(int $courseId): JsonResponse
    {
        $students = $this->courses->getAvailableStudents($courseId);
        return response()->json($students);
    }

    #[OA\Get(
        path: '/v1/courses/{id}/assigned-students',
        summary: 'List students enrolled in the course',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Array of assigned students'),
        ]
    )]
    public function getAssignedStudents(int $courseId): JsonResponse
    {
        $students = $this->courses->getAssignedStudents($courseId);
        return response()->json($students);
    }

    #[OA\Post(
        path: '/v1/courses/{id}/assign-students',
        summary: 'Enroll multiple students in a course',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['student_ids', 'semester'],
                properties: [
                    new OA\Property(property: 'student_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                    new OA\Property(property: 'semester', type: 'string', example: '2024-S1'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Assignment result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'assigned_count', type: 'integer'),
                        new OA\Property(property: 'conflict_count', type: 'integer'),
                        new OA\Property(property: 'conflict_students', type: 'array', items: new OA\Items(type: 'integer')),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function assignStudents(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'semester' => 'required|string|max:20',
        ]);

        $result = $this->courses->bulkAssignStudents($courseId, $data['student_ids'], $data['semester']);

        return response()->json([
            'message' => "Assigned {$result['assigned_count']} student(s) to course.",
            'assigned_count' => $result['assigned_count'],
            'conflict_count' => $result['conflict_count'],
            'conflict_students' => $result['conflict_students'],
        ], 201);
    }

    #[OA\Post(
        path: '/v1/courses/{id}/validate-students-assignment',
        summary: 'Validate student assignments for schedule conflicts',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['student_ids', 'semester'],
                properties: [
                    new OA\Property(property: 'student_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
                    new OA\Property(property: 'semester', type: 'string', example: '2024-S1'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Conflict validation result'),
        ]
    )]
    public function validateStudentsAssignment(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'semester' => 'required|string|max:20',
        ]);

        $result = $this->courses->validateStudentAssignmentConflicts(
            $courseId,
            $data['student_ids'],
            $data['semester'],
        );

        return response()->json($result);
    }

    #[OA\Post(
        path: '/v1/courses/{id}/revoke-students',
        summary: 'Remove students from a course',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['student_ids'],
                properties: [
                    new OA\Property(property: 'student_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2]),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Revoke result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'count', type: 'integer'),
                    ]
                )
            ),
        ]
    )]
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

    #[OA\Get(
        path: '/v1/courses/{id}/available-instructors',
        summary: 'List instructors not yet assigned to the course',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Array of available instructors'),
        ]
    )]
    public function getAvailableInstructors(int $courseId): JsonResponse
    {
        $instructors = $this->courses->getAvailableInstructors($courseId);
        return response()->json($instructors);
    }

    #[OA\Get(
        path: '/v1/courses/{id}/assigned-instructors',
        summary: 'List instructors assigned to the course',
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Array of assigned instructors'),
        ]
    )]
    public function getAssignedInstructors(int $courseId): JsonResponse
    {
        $instructors = $this->courses->getAssignedInstructors($courseId);
        return response()->json($instructors);
    }

    #[OA\Post(
        path: '/v1/courses/{id}/assign-instructors',
        summary: 'Assign an instructor to a course (single instructor enforced)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['instructor_ids'],
                properties: [
                    new OA\Property(
                        property: 'instructor_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: 'Exactly one instructor ID',
                        example: [1]
                    ),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Assignment result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'count', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function assignInstructors(Request $request, int $courseId): JsonResponse
    {
        $data = $request->validate([
            'instructor_ids' => 'required|array|size:1',
            'instructor_ids.*' => 'required|integer|exists:instructors,id',
        ]);

        $count = $this->courses->bulkAssignInstructors($courseId, $data['instructor_ids']);

        return response()->json([
            'message' => $count > 0
                ? 'Instructor assigned to course.'
                : 'No instructor assignment was applied.',
            'count' => $count,
        ], 201);
    }

    #[OA\Post(
        path: '/v1/courses/{id}/revoke-instructors',
        summary: 'Remove instructors from a course',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['instructor_ids'],
                properties: [
                    new OA\Property(property: 'instructor_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1]),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Courses'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Revoke result',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'count', type: 'integer'),
                    ]
                )
            ),
        ]
    )]
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
