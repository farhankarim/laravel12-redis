<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentRepositoryInterface $departments) {}

    #[OA\Get(
        path: '/v1/departments',
        summary: 'List all departments',
        security: [['sanctum' => []]],
        tags: ['Departments'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Array of departments',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Department'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json($this->departments->all(['*'], ['instructors']));
    }

    #[OA\Post(
        path: '/v1/departments',
        summary: 'Create a new department',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['dept_name'],
                properties: [
                    new OA\Property(property: 'dept_name', type: 'string', example: 'Computer Science'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Departments'],
        responses: [
            new OA\Response(response: 201, description: 'Department created', content: new OA\JsonContent(ref: '#/components/schemas/Department')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['dept_name' => 'required|string|unique:departments|max:255']);
        return response()->json($this->departments->create($data), 201);
    }

    #[OA\Get(
        path: '/v1/departments/{id}',
        summary: 'Get a department by ID',
        security: [['sanctum' => []]],
        tags: ['Departments'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))],
        responses: [
            new OA\Response(response: 200, description: 'Department with instructors', content: new OA\JsonContent(ref: '#/components/schemas/Department')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $department = $this->departments->find($id, ['*'], ['instructors.courses']);
        return $department ? response()->json($department) : response()->json(['message' => 'Not found'], 404);
    }

    #[OA\Put(
        path: '/v1/departments/{id}',
        summary: 'Update a department',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'dept_name', type: 'string', example: 'Computer Engineering'),
                ]
            )
        ),
        security: [['sanctum' => []]],
        tags: ['Departments'],
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
        $data = $request->validate(['dept_name' => 'sometimes|string|unique:departments,dept_name,'.$id.'|max:255']);
        return response()->json(['updated' => $this->departments->update($id, $data)]);
    }

    #[OA\Delete(
        path: '/v1/departments/{id}',
        summary: 'Delete a department',
        security: [['sanctum' => []]],
        tags: ['Departments'],
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
        return response()->json(['deleted' => $this->departments->delete($id)]);
    }
}
