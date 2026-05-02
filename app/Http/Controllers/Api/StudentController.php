<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExportStudentsCsvJob;
use App\Repositories\Contracts\StudentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class StudentController extends Controller
{
    private const MASTER_REPORT_COLUMNS = [
        'student_id', 'student_name', 'student_email', 'course_code', 'course_title',
        'semester', 'grade', 'instructor_name', 'instructor_specialization',
        'classroom_room_number', 'classroom_building', 'schedule_day',
        'schedule_start_time', 'department_name',
    ];

    public function __construct(protected StudentRepositoryInterface $students) {}

    #[OA\Get(
        path: '/v1/students',
        summary: 'List all students',
        security: [['sanctum' => []]],
        tags: ['Students'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Array of students',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Student'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json($this->students->all(['*'], ['courses']));
    }

    #[OA\Post(
        path: '/v1/students',
        summary: 'Create a new student',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Alice Smith'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'alice@university.edu'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Students'],
        responses: [
            new OA\Response(response: 201, description: 'Student created', content: new OA\JsonContent(ref: '#/components/schemas/Student')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:students']);

        return response()->json($this->students->create($data), 201);
    }

    #[OA\Get(
        path: '/v1/students/{id}',
        summary: 'Get a student by ID',
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Student with courses', content: new OA\JsonContent(ref: '#/components/schemas/Student')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $student = $this->students->find($id, ['*'], ['courses.instructors.departments', 'courses.classrooms']);

        return $student ? response()->json($student) : response()->json(['message' => 'Not found'], 404);
    }

    #[OA\Put(
        path: '/v1/students/{id}',
        summary: 'Update a student',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Alice Updated'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'alice.updated@university.edu'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Students'],
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
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'email' => 'sometimes|email|unique:students,email,'.$id]);

        return response()->json(['updated' => $this->students->update($id, $data)]);
    }

    #[OA\Delete(
        path: '/v1/students/{id}',
        summary: 'Delete a student',
        security: [['sanctum' => []]],
        tags: ['Students'],
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
        return response()->json(['deleted' => $this->students->delete($id)]);
    }

    #[OA\Post(
        path: '/v1/students/{id}/enroll',
        summary: 'Enroll a student in a course',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['course_id', 'semester'],
                properties: [
                    new OA\Property(property: 'course_id', type: 'integer', example: 2),
                    new OA\Property(property: 'semester', type: 'string', example: '2024-S1'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Enrolled successfully',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Enrolled successfully')])
            ),
            new OA\Response(response: 422, description: 'Schedule conflict or validation error'),
        ]
    )]
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

    #[OA\Patch(
        path: '/v1/students/{id}/grade',
        summary: "Update a student's grade for a course",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['course_id', 'grade'],
                properties: [
                    new OA\Property(property: 'course_id', type: 'integer', example: 2),
                    new OA\Property(property: 'grade', type: 'string', maxLength: 2, example: 'A'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Grade updated',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'updated', type: 'boolean', example: true)])
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateGrade(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['course_id' => 'required|integer|exists:courses,id', 'grade' => 'required|string|max:2']);

        return response()->json(['updated' => $this->students->updateGrade($id, $data['course_id'], $data['grade'])]);
    }

    #[OA\Get(
        path: '/v1/students/{id}/report',
        summary: "Get a student's master academic report",
        security: [['sanctum' => []]],
        tags: ['Students'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Student master report'),
        ]
    )]
    public function masterReport(int $id): JsonResponse
    {
        return response()->json($this->students->masterReport($id));
    }

    #[OA\Post(
        path: '/v1/reports/master',
        summary: 'Build a custom master report with selected columns',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['scope', 'columns'],
                properties: [
                    new OA\Property(property: 'scope', type: 'string', enum: ['all', 'student'], example: 'student'),
                    new OA\Property(property: 'student_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(
                        property: 'columns',
                        type: 'array',
                        items: new OA\Items(
                            type: 'string',
                            enum: [
                                'student_id', 'student_name', 'student_email', 'course_code', 'course_title',
                                'semester', 'grade', 'instructor_name', 'instructor_specialization',
                                'classroom_room_number', 'classroom_building', 'schedule_day',
                                'schedule_start_time', 'department_name',
                            ]
                        ),
                        example: ['student_name', 'course_title', 'grade']
                    ),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Students'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Custom master report',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'scope', type: 'string'),
                                new OA\Property(property: 'student_id', type: 'integer', nullable: true),
                                new OA\Property(property: 'columns', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'total_rows', type: 'integer'),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'rows', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    /**
     * Dispatch a background job to generate a students CSV export.
     * Returns an export ID that the client can poll to retrieve the download URL.
     */
    public function exportCsv(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => 'required|in:all,student',
            'student_id' => 'nullable|required_if:scope,student|integer|exists:students,id',
        ]);

        $scope = $data['scope'];
        $studentId = $scope === 'student' ? (int) $data['student_id'] : null;
        $exportId = (string) Str::uuid();

        ExportStudentsCsvJob::dispatch($exportId, $studentId)
            ->onQueue('default');

        return response()->json([
            'message' => 'CSV export queued.',
            'export_id' => $exportId,
        ], 202);
    }

    /**
     * Return the temporary download URL for a completed CSV export.
     */
    public function csvDownloadUrl(Request $request, string $exportId): JsonResponse
    {
        $path = cache()->get(ExportStudentsCsvJob::CACHE_KEY_PREFIX.$exportId);

        if (! $path) {
            return response()->json(['message' => 'Export not ready or not found.'], 404);
        }

        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';

        try {
            $url = \Illuminate\Support\Facades\Storage::disk($disk)
                ->temporaryUrl($path, now()->addMinutes(30));
        } catch (\RuntimeException) {
            // Local disk does not support temporary URLs — return the stored path.
            $url = $path;
        }

        return response()->json(['url' => $url]);
    }
}
