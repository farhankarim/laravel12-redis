<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\RedisDashboardSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisDashboardSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    private RedisDashboardSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RedisDashboardSummaryService;
    }

    // -----------------------------------------------------------------------
    // Queue summary
    // -----------------------------------------------------------------------

    public function test_get_queue_summary_returns_array_with_expected_keys(): void
    {
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('pipeline')->andReturn(array_fill(0, 9, 0));
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $summary = $this->service->getQueueSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('updated_at', $summary);
        $this->assertArrayHasKey('queues', $summary);
        $this->assertArrayHasKey('totals', $summary);
        $this->assertIsArray($summary['queues']);
        $this->assertIsArray($summary['totals']);
    }

    public function test_get_queue_summary_totals_contain_expected_keys(): void
    {
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('pipeline')->andReturn(array_fill(0, 9, 0));
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $totals = $this->service->getQueueSummary()['totals'];

        foreach (['redis_pending', 'redis_reserved', 'redis_delayed', 'failed_jobs', 'batch_total_jobs', 'batch_pending_jobs', 'batch_failed_jobs'] as $key) {
            $this->assertArrayHasKey($key, $totals, "Missing totals key: {$key}");
        }
    }

    public function test_get_queue_summary_returns_cached_value_when_available(): void
    {
        $cached = ['updated_at' => '2024-01-01T00:00:00+00:00', 'queues' => [], 'totals' => []];

        Redis::shouldReceive('get')
            ->once()
            ->andReturn(json_encode($cached));

        $result = $this->service->getQueueSummary();

        $this->assertSame($cached, $result);
    }

    public function test_refresh_queue_summary_stores_result_with_ttl(): void
    {
        Redis::shouldReceive('pipeline')->andReturn(array_fill(0, 9, 0));
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function ($key, $ttl) {
                return $key === RedisDashboardSummaryService::QUEUE_SUMMARY_KEY && $ttl === 3600;
            })
            ->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $this->service->refreshQueueSummary();
    }

    // -----------------------------------------------------------------------
    // Users summary
    // -----------------------------------------------------------------------

    public function test_get_users_summary_returns_array_with_expected_keys(): void
    {
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $summary = $this->service->getUsersSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('updated_at', $summary);
        $this->assertArrayHasKey('total_users', $summary);
        $this->assertArrayHasKey('verified_users', $summary);
        $this->assertArrayHasKey('unverified_users', $summary);
        $this->assertArrayHasKey('latest_user', $summary);
    }

    public function test_get_users_summary_counts_are_accurate(): void
    {
        User::factory()->count(3)->create();
        User::factory()->count(2)->unverified()->create();

        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $summary = $this->service->getUsersSummary();

        $this->assertSame(5, $summary['total_users']);
        $this->assertSame(3, $summary['verified_users']);
        $this->assertSame(2, $summary['unverified_users']);
    }

    public function test_users_summary_latest_user_is_most_recent(): void
    {
        $first = User::factory()->create(['name' => 'Alice']);
        $latest = User::factory()->create(['name' => 'Bob']);

        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $summary = $this->service->getUsersSummary();

        $this->assertSame($latest->id, $summary['latest_user']['id']);
    }

    public function test_refresh_users_summary_stores_result_with_ttl(): void
    {
        Redis::shouldReceive('setex')
            ->once()
            ->withArgs(function ($key, $ttl) {
                return $key === RedisDashboardSummaryService::USERS_SUMMARY_KEY && $ttl === 3600;
            })
            ->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $this->service->refreshUsersSummary();
    }

    // -----------------------------------------------------------------------
    // Cache resilience
    // -----------------------------------------------------------------------

    public function test_cached_summary_falls_back_to_rebuild_when_redis_returns_null(): void
    {
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('pipeline')->andReturn(array_fill(0, 9, 0));
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $result = $this->service->getQueueSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('updated_at', $result);
    }

    public function test_cached_summary_ignores_malformed_json(): void
    {
        Redis::shouldReceive('get')->andReturn('NOT_JSON');
        Redis::shouldReceive('pipeline')->andReturn(array_fill(0, 9, 0));
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('publish')->andReturn(1);

        $result = $this->service->getQueueSummary();

        $this->assertIsArray($result);
    }
}
