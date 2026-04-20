<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ElasticsearchUserSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __construct(private readonly ElasticsearchUserSearchService $users) {}

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $results = $this->users->searchByName(
            $data['name'],
            (int) ($data['limit'] ?? 20)
        );

        return response()->json([
            'meta' => [
                'query' => $data['name'],
                'count' => count($results),
            ],
            'data' => $results,
        ]);
    }
}
