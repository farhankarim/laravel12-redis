<?php

namespace App\Livewire;

use App\Services\RedisDashboardSummaryService;
use Illuminate\Support\Facades\RateLimiter;
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
        $key = 'refresh-queue-summary:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            return;
        }

        RateLimiter::hit($key, decaySeconds: 60);

        $summaryService->publishRefresh('queue');
        $this->summary = $summaryService->refreshQueueSummary();
    }

    public function render()
    {
        return view('livewire.queue-summary-dashboard')
            ->layout('layouts.dashboard', ['title' => 'Redis Queue Summary']);
    }
}
