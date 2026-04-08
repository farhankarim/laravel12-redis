<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RedisDashboardTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Authentication guard
    // -----------------------------------------------------------------------

    public function test_queue_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard/queue');

        $response->assertRedirect('/university/login');
    }

    public function test_users_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard/users');

        $response->assertRedirect('/university/login');
    }

    // -----------------------------------------------------------------------
    // Page renders
    // -----------------------------------------------------------------------

    public function test_queue_dashboard_page_loads(): void
    {
        $response = $this->dashboardRequest('/dashboard/queue');

        $response->assertOk();
        $response->assertSee('Redis Queue Summary');
    }

    public function test_users_dashboard_page_loads(): void
    {
        $response = $this->dashboardRequest('/dashboard/users');

        $response->assertOk();
        $response->assertSee('Users Data Summary');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Make an authenticated, Vite-less GET request to a dashboard URI.
     */
    private function dashboardRequest(string $uri): TestResponse
    {
        $user = User::factory()->create();

        return $this->withoutVite()->actingAs($user)->get($uri);
    }
}
