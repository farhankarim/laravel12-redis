<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ClassroomRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ClassroomController extends Controller
{
    public function __construct(protected ClassroomRepositoryInterface $classrooms) {}

    #[OA\Get(
        path: '/v1/classrooms',
        summary: 'List all classrooms',
        security: [['sanctum' => []]],
        tags: ['Classrooms'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Array of classrooms',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Classroom'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json($this->classrooms->all(['*'], ['courses']));
    }

    #[OA\Post(
        path: '/v1/classrooms',
        summary: 'Create a new classroom',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['room_number', 'building'],
                properties: [
                    new OA\Property(property: 'room_number', type: 'string', example: 'A101'),
                    new OA\Property(property: 'building', type: 'string', example: 'Engineering Block'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Classrooms'],
        responses: [
            new OA\Response(response: 201, description: 'Classroom created', content: new OA\JsonContent(ref: '#/components/schemas/Classroom')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['room_number' => 'required|string|max:50', 'building' => 'required|string|max:255']);
        return response()->json($this->classrooms->create($data), 201);
    }

    #[OA\Get(
        path: '/v1/classrooms/{id}',
        summary: 'Get a classroom by ID',
        security: [['sanctum' => []]],
        tags: ['Classrooms'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Classroom with courses', content: new OA\JsonContent(ref: '#/components/schemas/Classroom')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $classroom = $this->classrooms->find($id, ['*'], ['courses']);
        return $classroom ? response()->json($classroom) : response()->json(['message' => 'Not found'], 404);
    }

    #[OA\Put(
        path: '/v1/classrooms/{id}',
        summary: 'Update a classroom',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'room_number', type: 'string', example: 'B202'),
                    new OA\Property(property: 'building', type: 'string', example: 'Science Block'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Classrooms'],
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
        $data = $request->validate(['room_number' => 'sometimes|string|max:50', 'building' => 'sometimes|string|max:255']);
        return response()->json(['updated' => $this->classrooms->update($id, $data)]);
    }

    #[OA\Delete(
        path: '/v1/classrooms/{id}',
        summary: 'Delete a classroom',
        security: [['sanctum' => []]],
        tags: ['Classrooms'],
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
        return response()->json(['deleted' => $this->classrooms->delete($id)]);
    }
}
