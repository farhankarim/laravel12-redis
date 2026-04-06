<?php

namespace App\Repositories;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    abstract protected function model(): string;

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    public function find(int $id, array $columns = ['*'], array $relations = [], array $appends = []): ?Model
    {
        $model = $this->model->select($columns)->with($relations)->find($id);
        if ($model && !empty($appends)) {
            $model->append($appends);
        }
        return $model;
    }

    public function findByField(string $field, mixed $value, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->select($columns)->with($relations)->where($field, $value)->get();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->model->find($id);
        if (!$record) {
            return false;
        }
        return $record->update($data);
    }

    public function delete(int $id): bool
    {
        $record = $this->model->find($id);
        if (!$record) {
            return false;
        }
        return (bool) $record->delete();
    }
}
