<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function __construct(protected InstructorRepositoryInterface $instructors) {}

    public function index(): JsonResponse
    {
        return response()->json($this->instructors->all(['*'], ['courses', 'departments']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'specialization' => 'nullable|string|max:255']);
        return response()->json($this->instructors->create($data), 201);
    }

    public function show(int $id): JsonResponse
    {
        $instructor = $this->instructors->find($id, ['*'], ['courses', 'departments']);
        return $instructor ? response()->json($instructor) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'specialization' => 'nullable|string|max:255']);
        return response()->json(['updated' => $this->instructors->update($id, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->instructors->delete($id)]);
    }
}
