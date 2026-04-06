<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    protected function model(): string
    {
        return User::class;
    }
}
