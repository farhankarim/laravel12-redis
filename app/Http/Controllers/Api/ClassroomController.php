<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ClassroomRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function __construct(protected ClassroomRepositoryInterface $classrooms) {}

    public function index(): JsonResponse
    {
        return response()->json($this->classrooms->all(['*'], ['courses']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['room_number' => 'required|string|max:50', 'building' => 'required|string|max:255']);
        return response()->json($this->classrooms->create($data), 201);
    }

    public function show(int $id): JsonResponse
    {
        $classroom = $this->classrooms->find($id, ['*'], ['courses']);
        return $classroom ? response()->json($classroom) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['room_number' => 'sometimes|string|max:50', 'building' => 'sometimes|string|max:255']);
        return response()->json(['updated' => $this->classrooms->update($id, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->classrooms->delete($id)]);
    }
}
