<?php
namespace App\Repositories;

use App\Models\Instructor;
use App\Repositories\Contracts\InstructorRepositoryInterface;

class InstructorRepository extends BaseRepository implements InstructorRepositoryInterface
{
    protected function model(): string { return Instructor::class; }
}
