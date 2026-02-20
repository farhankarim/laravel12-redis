# Livewire + Redis Dashboard Tutorial (Step-by-Step)

This guide shows how to build two Livewire dashboards in Laravel:

- Queue summary dashboard
- Users data summary dashboard

Both dashboards use Redis snapshots with `Redis::get` / `Redis::set` and Redis Pub/Sub (`publish` + `subscribe`) for refresh signaling.

## 1) Prerequisites

Make sure your app has Redis + Livewire installed.

```bash
composer require livewire/livewire predis/predis
```

In `.env`:

```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
```

Run migrations:

```bash
php artisan migrate
```

Install and build frontend assets (required for Vite manifest):

```bash
npm install
npm run build
```

## 2) Create Livewire components

```bash
php artisan make:livewire QueueSummaryDashboard
php artisan make:livewire UsersSummaryDashboard
```

This creates component classes in `app/Livewire` and Blade views in `resources/views/livewire`.

## 3) Build a Redis summary service

Create `app/Services/RedisDashboardSummaryService.php`.

Main responsibilities:

- Cache snapshots in Redis keys
- Rebuild queue/users summaries from DB + Redis queue state
- Publish refresh + updated events over pub/sub channels
- Subscribe to refresh channel and process requested refreshes

Use these constants:

```php
public const QUEUE_SUMMARY_KEY = 'dashboard:queue_summary';
public const USERS_SUMMARY_KEY = 'dashboard:users_summary';
public const REFRESH_CHANNEL = 'dashboard.summary.refresh';
public const UPDATED_CHANNEL = 'dashboard.summary.updated';
```

Store/retrieve snapshots with Redis:

```php
$encoded = Redis::get($key);
Redis::set($key, json_encode($summary, JSON_THROW_ON_ERROR));
```

Publish refresh/update messages:

```php
Redis::publish(self::REFRESH_CHANNEL, json_encode(['type' => 'queue']));
Redis::publish(self::UPDATED_CHANNEL, json_encode(['type' => 'queue']));
```

Subscribe and process refresh messages:

```php
Redis::subscribe([self::REFRESH_CHANNEL], function (string $message): void {
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
});
```

## 4) Implement queue summary Livewire component

Update `app/Livewire/QueueSummaryDashboard.php`:

```php
<?php

namespace App\Livewire;

use App\Services\RedisDashboardSummaryService;
use Livewire\Component;

class QueueSummaryDashboard extends Component
{
    public array $summary = [];

    public function mount(RedisDashboardSummaryService $summaryService): void
    {
        $this->summary = $summaryService->getQueueSummary();
    }

    public function loadSummary(RedisDashboardSummaryService $summaryService): void
    {
        $this->summary = $summaryService->getQueueSummary();
    }

    public function refreshSummary(RedisDashboardSummaryService $summaryService): void
    {
        $summaryService->publishRefresh('queue');
        $this->summary = $summaryService->refreshQueueSummary();
    }

    public function render()
    {
        return view('livewire.queue-summary-dashboard')
            ->layout('layouts.dashboard', ['title' => 'Redis Queue Summary']);
    }
}
```

## 5) Implement users summary Livewire component

Update `app/Livewire/UsersSummaryDashboard.php`:

```php
<?php

namespace App\Livewire;

use App\Services\RedisDashboardSummaryService;
use Livewire\Component;

class UsersSummaryDashboard extends Component
{
    public array $summary = [];

    public function mount(RedisDashboardSummaryService $summaryService): void
    {
        $this->summary = $summaryService->getUsersSummary();
    }

    public function loadSummary(RedisDashboardSummaryService $summaryService): void
    {
        $this->summary = $summaryService->getUsersSummary();
    }

    public function refreshSummary(RedisDashboardSummaryService $summaryService): void
    {
        $summaryService->publishRefresh('users');
        $this->summary = $summaryService->refreshUsersSummary();
    }

    public function render()
    {
        return view('livewire.users-summary-dashboard')
            ->layout('layouts.dashboard', ['title' => 'Users Summary']);
    }
}
```

## 6) Create dashboard layout + views

Create shared layout:

- `resources/views/layouts/dashboard.blade.php`

Include:

- `@livewireStyles` in `<head>`
- `@livewireScripts` before `</body>`
- Nav links for `/dashboard/queue` and `/dashboard/users`

Create view templates:

- `resources/views/livewire/queue-summary-dashboard.blade.php`
- `resources/views/livewire/users-summary-dashboard.blade.php`

Tip: use polling to auto-refresh:

```blade
<section wire:poll.10s="loadSummary">
```

And button-triggered refresh:

```blade
<button wire:click="refreshSummary" type="button">Refresh via Redis Pub/Sub</button>
```

## 7) Register routes

Update `routes/web.php`:

```php
use App\Livewire\QueueSummaryDashboard;
use App\Livewire\UsersSummaryDashboard;

Route::get('/dashboard/queue', QueueSummaryDashboard::class)->name('dashboard.queue');
Route::get('/dashboard/users', UsersSummaryDashboard::class)->name('dashboard.users');
```

## 8) Add a Redis pub/sub listener command

Add to `routes/console.php`:

```php
use App\Services\RedisDashboardSummaryService;

Artisan::command('dashboard:redis-listen', function (RedisDashboardSummaryService $summaryService) {
    $this->info('Subscribing to Redis channel: '.RedisDashboardSummaryService::REFRESH_CHANNEL);
    $this->line('Waiting for refresh messages... Press Ctrl+C to stop.');

    $summaryService->subscribeAndProcessRefreshRequests(function (string $type): void {
        $this->info('Processed summary refresh request: '.$type.' @ '.now()->toDateTimeString());
    });

    return 0;
});
```

## 9) Run the dashboards

Single command option (recommended):

```bash
composer run dev:dashboard
```

This starts Laravel + Redis dashboard listener without requiring Vite dev server.

Or run in separate terminals:

Terminal 1 (app):

```bash
php artisan serve
```

Terminal 2 (Vite dev server, optional in local development):

```bash
npm run dev
```

Terminal 3 (pub/sub listener):

```bash
php artisan dashboard:redis-listen
```

Open:

- `http://127.0.0.1:8000/dashboard/queue`
- `http://127.0.0.1:8000/dashboard/users`

## 10) Optional: seed data for testing

Queue lots of users:

```bash
php artisan users:queue-generate --total=100000 --chunk=1000 --queue=user-imports
php artisan queue:work redis --queue=user-imports
```

The dashboards will show summary changes as Redis + DB state changes.

## 11) Quick verification checklist

- Queue page loads and shows Redis queue metrics
- Users page loads and shows user totals/latest user
- `Refresh via Redis Pub/Sub` triggers immediate summary rebuild
- Redis keys exist: `dashboard:queue_summary`, `dashboard:users_summary`
- Listener receives messages on `dashboard.summary.refresh`

---

### Files in this project

- Service: `app/Services/RedisDashboardSummaryService.php`
- Components: `app/Livewire/QueueSummaryDashboard.php`, `app/Livewire/UsersSummaryDashboard.php`
- Views: `resources/views/livewire/queue-summary-dashboard.blade.php`, `resources/views/livewire/users-summary-dashboard.blade.php`
- Layout: `resources/views/layouts/dashboard.blade.php`
- Routes: `routes/web.php`
- Listener command: `routes/console.php`
- Feature tests: `tests/Feature/RedisDashboardTest.php`