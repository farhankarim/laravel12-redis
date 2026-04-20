<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ElasticsearchUserSearchService;

class UserObserver
{
    public function created(User $user): void
    {
        app(ElasticsearchUserSearchService::class)->indexUser($user);
    }

    public function updated(User $user): void
    {
        app(ElasticsearchUserSearchService::class)->indexUser($user);
    }

    public function deleted(User $user): void
    {
        app(ElasticsearchUserSearchService::class)->deleteUser($user->id);
    }
}
