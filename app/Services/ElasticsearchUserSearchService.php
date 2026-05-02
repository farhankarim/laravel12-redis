<?php

namespace App\Services;

use App\Models\User;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Throwable;

class ElasticsearchUserSearchService
{
    public function __construct(private readonly Client $client) {}

    /**
     * @return array<int, array{id:int,name:string,email:string}>
     */
    public function searchByName(string $name, int $limit = 20): array
    {
        $query = trim($name);
        if ($query === '') {
            return [];
        }

        $limit = max(1, min($limit, 100));

        if (! $this->isEnabled()) {
            return $this->fallbackDatabaseSearch($query, $limit);
        }

        try {
            $this->ensureIndexExists();

            $response = $this->client->search([
                'index' => $this->indexName(),
                'body' => [
                    'size' => $limit,
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name^3', 'email'],
                            'type' => 'bool_prefix',
                        ],
                    ],
                ],
            ]);

            $hits = $response->asArray()['hits']['hits'] ?? [];

            return array_values(array_map(function (array $hit): array {
                $source = $hit['_source'] ?? [];

                return [
                    'id' => (int) ($source['id'] ?? $hit['_id'] ?? 0),
                    'name' => (string) ($source['name'] ?? ''),
                    'email' => (string) ($source['email'] ?? ''),
                ];
            }, $hits));
        } catch (Throwable) {
            return $this->fallbackDatabaseSearch($query, $limit);
        }
    }

    public function indexUser(User $user): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            $this->ensureIndexExists();

            $this->client->index([
                'index' => $this->indexName(),
                'id' => (string) $user->id,
                'body' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'refresh' => true,
            ]);
        } catch (Throwable) {
            // Ignore indexing failures and allow app flow to continue.
        }
    }

    public function deleteUser(int $userId): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            $this->client->delete([
                'index' => $this->indexName(),
                'id' => (string) $userId,
                'refresh' => true,
            ]);
        } catch (ClientResponseException $exception) {
            if ($exception->getCode() !== 404) {
                throw $exception;
            }
        } catch (Throwable) {
            // Ignore indexing failures and allow app flow to continue.
        }
    }

    private function ensureIndexExists(): void
    {
        $exists = $this->client->indices()->exists([
            'index' => $this->indexName(),
        ])->asBool();

        if ($exists) {
            return;
        }

        $this->client->indices()->create([
            'index' => $this->indexName(),
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => ['type' => 'keyword'],
                            ],
                        ],
                        'email' => ['type' => 'keyword'],
                    ],
                ],
            ],
        ]);

        $this->bulkIndexAllUsers();
    }

    private function bulkIndexAllUsers(): void
    {
        User::query()
            ->select(['id', 'name', 'email'])
            ->orderBy('id')
            ->chunkById(500, function ($users): void {
                $operations = [];

                foreach ($users as $user) {
                    $operations[] = [
                        'index' => [
                            '_index' => $this->indexName(),
                            '_id' => (string) $user->id,
                        ],
                    ];
                    $operations[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }

                if ($operations !== []) {
                    $this->client->bulk([
                        'body' => $operations,
                        'refresh' => true,
                    ]);
                }
            });
    }

    /**
     * @return array<int, array{id:int,name:string,email:string}>
     */
    private function fallbackDatabaseSearch(string $name, int $limit): array
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->where('name', 'like', '%'.$name.'%')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();
    }

    private function indexName(): string
    {
        return (string) config('elasticsearch.users_index', 'users');
    }

    private function isEnabled(): bool
    {
        return (bool) config('elasticsearch.enabled', true);
    }
}
