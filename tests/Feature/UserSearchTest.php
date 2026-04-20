<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_search_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/users/search?name=ali');

        $response->assertUnauthorized();
    }

    public function test_user_search_returns_matching_users_by_name(): void
    {
        config(['elasticsearch.enabled' => false]);

        Sanctum::actingAs(User::factory()->create());
        User::factory()->create(['name' => 'Alice Johnson', 'email' => 'alice@example.test']);
        User::factory()->create(['name' => 'Ali Khan', 'email' => 'ali@example.test']);
        User::factory()->create(['name' => 'Bob Smith', 'email' => 'bob@example.test']);

        $response = $this->getJson('/api/v1/users/search?name=Ali');

        $response->assertOk();
        $response->assertJsonPath('meta.query', 'Ali');
        $response->assertJsonPath('meta.count', 2);
        $response->assertJsonCount(2, 'data');
    }
}
