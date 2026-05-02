<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ElasticsearchUserSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserSearchController extends Controller
{
    public function __construct(private readonly ElasticsearchUserSearchService $users) {}

    #[OA\Get(
        path: '/v1/users/search',
        summary: 'Search users by name using Elasticsearch',
        description: 'Full-text search on the users index. Falls back to database LIKE search when Elasticsearch is unavailable.',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'query',
                required: true,
                description: 'Name query string',
                schema: new OA\Schema(type: 'string', minLength: 1, maxLength: 255, example: 'farhan')
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Maximum number of results (1-100, default 20)',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'query', type: 'string', example: 'farhan'),
                                new OA\Property(property: 'count', type: 'integer', example: 3),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Farhan Karim'),
                                    new OA\Property(property: 'email', type: 'string', example: 'farhan@example.com'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
