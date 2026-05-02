<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class InstructorController extends Controller
{
    public function __construct(protected InstructorRepositoryInterface $instructors) {}

    #[OA\Get(
        path: '/v1/instructors',
        summary: 'List all instructors',
        security: [['sanctum' => []]],
        tags: ['Instructors'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Array of instructors',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Instructor'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json($this->instructors->all(['*'], ['courses', 'departments']));
    }

    #[OA\Post(
        path: '/v1/instructors',
        summary: 'Create a new instructor',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Dr. Jane Doe'),
                    new OA\Property(property: 'specialization', type: 'string', nullable: true, example: 'Algorithms'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Instructors'],
        responses: [
            new OA\Response(response: 201, description: 'Instructor created', content: new OA\JsonContent(ref: '#/components/schemas/Instructor')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'specialization' => 'nullable|string|max:255']);
        return response()->json($this->instructors->create($data), 201);
    }

    #[OA\Get(
        path: '/v1/instructors/{id}',
        summary: 'Get an instructor by ID',
        security: [['sanctum' => []]],
        tags: ['Instructors'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Instructor with courses and departments', content: new OA\JsonContent(ref: '#/components/schemas/Instructor')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $instructor = $this->instructors->find($id, ['*'], ['courses', 'departments']);
        return $instructor ? response()->json($instructor) : response()->json(['message' => 'Not found'], 404);
    }

    #[OA\Put(
        path: '/v1/instructors/{id}',
        summary: 'Update an instructor',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Dr. Jane Smith'),
                    new OA\Property(property: 'specialization', type: 'string', nullable: true, example: 'Machine Learning'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Instructors'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Update result',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'updated', type: 'boolean', example: true)])
            ),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'specialization' => 'nullable|string|max:255']);
        return response()->json(['updated' => $this->instructors->update($id, $data)]);
    }

    #[OA\Delete(
        path: '/v1/instructors/{id}',
        summary: 'Delete an instructor',
        security: [['sanctum' => []]],
        tags: ['Instructors'],
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
        return response()->json(['deleted' => $this->instructors->delete($id)]);
    }
}
