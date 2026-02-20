<?php

namespace Tests\Feature;

use Tests\TestCase;

class RedisDashboardTest extends TestCase
{
    public function test_queue_dashboard_page_loads(): void
    {
        $response = $this->get('/dashboard/queue');

        $response->assertOk();
        $response->assertSee('Redis Queue Summary');
    }

    public function test_users_dashboard_page_loads(): void
    {
        $response = $this->get('/dashboard/users');

        $response->assertOk();
        $response->assertSee('Users Data Summary');
    }
}
