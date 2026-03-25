<?php

use App\Services\RedisDashboardSummaryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dashboard:redis-listen', function (RedisDashboardSummaryService $summaryService) {
    $this->info('Subscribing to Redis channel: '.RedisDashboardSummaryService::REFRESH_CHANNEL);
    $this->line('Waiting for refresh messages... Press Ctrl+C to stop.');

    while (true) {
        try {
            $summaryService->subscribeAndProcessRefreshRequests(function (string $type): void {
                $this->info('Processed summary refresh request: '.$type.' @ '.now()->toDateTimeString());
            });
        } catch (\Throwable $exception) {
            $this->error('Redis listener failed: '.$exception->getMessage());
            $this->line('Retrying in 2 seconds...');
            sleep(2);
        }
    }

    return 0;
})->purpose('Listen to Redis pub/sub refresh requests and refresh dashboard summary keys');
