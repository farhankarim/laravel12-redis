<?php
namespace App\Repositories;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;

class DepartmentRepository extends BaseRepository implements DepartmentRepositoryInterface
{
    protected function model(): string { return Department::class; }
}
