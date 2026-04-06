<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentRepositoryInterface $departments) {}

    public function index(): JsonResponse
    {
        return response()->json($this->departments->all(['*'], ['instructors']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['dept_name' => 'required|string|unique:departments|max:255']);
        return response()->json($this->departments->create($data), 201);
    }

    public function show(int $id): JsonResponse
    {
        $department = $this->departments->find($id, ['*'], ['instructors.courses']);
        return $department ? response()->json($department) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['dept_name' => 'sometimes|string|unique:departments,dept_name,'.$id.'|max:255']);
        return response()->json(['updated' => $this->departments->update($id, $data)]);
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json(['deleted' => $this->departments->delete($id)]);
    }
}
