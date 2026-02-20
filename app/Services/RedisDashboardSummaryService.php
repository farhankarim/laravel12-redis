<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RedisDashboardSummaryService
{
    public const QUEUE_SUMMARY_KEY = 'dashboard:queue_summary';

    public const USERS_SUMMARY_KEY = 'dashboard:users_summary';

    public const REFRESH_CHANNEL = 'dashboard.summary.refresh';

    public const UPDATED_CHANNEL = 'dashboard.summary.updated';

    public function getQueueSummary(): array
    {
        return $this->getSummary(self::QUEUE_SUMMARY_KEY, fn () => $this->refreshQueueSummary());
    }

    public function getUsersSummary(): array
    {
        return $this->getSummary(self::USERS_SUMMARY_KEY, fn () => $this->refreshUsersSummary());
    }

    public function refreshQueueSummary(): array
    {
        $summary = $this->buildQueueSummary();

        $this->setSummary(self::QUEUE_SUMMARY_KEY, $summary);
        $this->publish(self::UPDATED_CHANNEL, [
            'type' => 'queue',
            'updated_at' => $summary['updated_at'],
        ]);

        return $summary;
    }

    public function refreshUsersSummary(): array
    {
        $summary = $this->buildUsersSummary();

        $this->setSummary(self::USERS_SUMMARY_KEY, $summary);
        $this->publish(self::UPDATED_CHANNEL, [
            'type' => 'users',
            'updated_at' => $summary['updated_at'],
        ]);

        return $summary;
    }

    public function publishRefresh(string $type = 'all'): void
    {
        $this->publish(self::REFRESH_CHANNEL, [
            'type' => $type,
            'requested_at' => now()->toIso8601String(),
        ]);
    }

    public function subscribeAndProcessRefreshRequests(?callable $onProcessed = null): void
    {
        Redis::subscribe([self::REFRESH_CHANNEL], function (string $message) use ($onProcessed): void {
            $payload = json_decode($message, true);
            $type = is_array($payload) ? ($payload['type'] ?? 'all') : 'all';

            if ($type === 'queue') {
                $this->refreshQueueSummary();
            } elseif ($type === 'users') {
                $this->refreshUsersSummary();
            } else {
                $this->refreshQueueSummary();
                $this->refreshUsersSummary();
            }

            if ($onProcessed) {
                $onProcessed($type);
            }
        });
    }

    private function getSummary(string $key, callable $fallback): array
    {
        $cached = $this->getCachedSummary($key);

        if ($cached !== null) {
            return $cached;
        }

        return $fallback();
    }

    private function getCachedSummary(string $key): ?array
    {
        try {
            $encoded = Redis::get($key);

            if (! is_string($encoded) || $encoded === '') {
                return null;
            }

            $decoded = json_decode($encoded, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function setSummary(string $key, array $summary): void
    {
        try {
            Redis::set($key, json_encode($summary, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
        }
    }

    private function publish(string $channel, array $payload): void
    {
        try {
            Redis::publish($channel, json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
        }
    }

    private function buildQueueSummary(): array
    {
        $queueNames = collect([
            config('queue.connections.redis.queue', 'default'),
            'default',
            'user-imports',
            'chequebook-imports',
        ])->filter()->unique()->values();

        $queues = [];
        $pendingTotal = 0;
        $reservedTotal = 0;
        $delayedTotal = 0;

        foreach ($queueNames as $queueName) {
            $redisPending = $this->redisCount('llen', "queues:{$queueName}");
            $redisReserved = $this->redisCount('zcard', "queues:{$queueName}:reserved");
            $redisDelayed = $this->redisCount('zcard', "queues:{$queueName}:delayed");

            $pendingTotal += $redisPending;
            $reservedTotal += $redisReserved;
            $delayedTotal += $redisDelayed;

            $queues[] = [
                'name' => $queueName,
                'database_jobs' => DB::table('jobs')->where('queue', $queueName)->count(),
                'redis_pending' => $redisPending,
                'redis_reserved' => $redisReserved,
                'redis_delayed' => $redisDelayed,
            ];
        }

        $batchTotals = DB::table('job_batches')
            ->selectRaw('COALESCE(SUM(total_jobs), 0) as total_jobs')
            ->selectRaw('COALESCE(SUM(pending_jobs), 0) as pending_jobs')
            ->selectRaw('COALESCE(SUM(failed_jobs), 0) as failed_jobs')
            ->first();

        return [
            'updated_at' => now()->toIso8601String(),
            'queues' => $queues,
            'totals' => [
                'redis_pending' => $pendingTotal,
                'redis_reserved' => $reservedTotal,
                'redis_delayed' => $delayedTotal,
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'batch_total_jobs' => (int) ($batchTotals->total_jobs ?? 0),
                'batch_pending_jobs' => (int) ($batchTotals->pending_jobs ?? 0),
                'batch_failed_jobs' => (int) ($batchTotals->failed_jobs ?? 0),
            ],
        ];
    }

    private function buildUsersSummary(): array
    {
        $user = new User();
        $table = $user->getTable();
        $primaryKey = $user->getKeyName();

        if (! Schema::hasColumn($table, $primaryKey)) {
            $primaryKey = Schema::hasColumn($table, 'id')
                ? 'id'
                : (Schema::hasColumn($table, 'user_id') ? 'user_id' : 'created_at');
        }

        $hasEmailVerifiedAt = Schema::hasColumn($table, 'email_verified_at');
        $hasName = Schema::hasColumn($table, 'name');
        $hasEmail = Schema::hasColumn($table, 'email');
        $hasCreatedAt = Schema::hasColumn($table, 'created_at');

        $latestUser = User::query()->latest($primaryKey)->first();

        $latestUserId = $latestUser?->getAttribute($primaryKey);
        $totalUsers = User::query()->count();
        $verifiedUsers = $hasEmailVerifiedAt
            ? User::query()->whereNotNull('email_verified_at')->count()
            : 0;
        $unverifiedUsers = $hasEmailVerifiedAt
            ? User::query()->whereNull('email_verified_at')->count()
            : $totalUsers;

        return [
            'updated_at' => now()->toIso8601String(),
            'total_users' => $totalUsers,
            'verified_users' => $verifiedUsers,
            'unverified_users' => $unverifiedUsers,
            'latest_user' => $latestUser ? [
                'id' => $latestUserId,
                'name' => $hasName ? $latestUser->name : null,
                'email' => $hasEmail ? $latestUser->email : null,
                'created_at' => $hasCreatedAt ? optional($latestUser->created_at)->toIso8601String() : null,
            ] : null,
        ];
    }

    private function redisCount(string $command, string $key): int
    {
        try {
            return (int) Redis::command($command, [$key]);
        } catch (Throwable) {
            return 0;
        }
    }
}
