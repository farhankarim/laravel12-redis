<?php
namespace App\Repositories;

use App\Models\Classroom;
use App\Repositories\Contracts\ClassroomRepositoryInterface;

class ClassroomRepository extends BaseRepository implements ClassroomRepositoryInterface
{
    protected function model(): string { return Classroom::class; }
}
