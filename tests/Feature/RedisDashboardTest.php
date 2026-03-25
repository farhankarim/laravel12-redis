<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedisDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard/queue');

        $response->assertRedirect('/login');
    }

    public function test_users_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard/users');

        $response->assertRedirect('/login');
    }

    public function test_queue_dashboard_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->withoutVite()->actingAs($user)->get('/dashboard/queue');

        $response->assertOk();
        $response->assertSee('Redis Queue Summary');
    }

    public function test_users_dashboard_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->withoutVite()->actingAs($user)->get('/dashboard/users');

        $response->assertOk();
        $response->assertSee('Users Data Summary');
    }
}
