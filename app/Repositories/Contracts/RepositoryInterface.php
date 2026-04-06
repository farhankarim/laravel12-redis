<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []): Collection;
    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model;
    public function findByField(string $field, mixed $value, array $columns = ['*'], array $relations = []): Collection;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
