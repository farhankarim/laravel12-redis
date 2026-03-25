<?php

namespace App\Livewire;

use App\Services\RedisDashboardSummaryService;
use Illuminate\Support\Facades\RateLimiter;
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
        $key = 'refresh-users-summary:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 5)) {
            return;
        }

        RateLimiter::hit($key, decaySeconds: 60);

        $summaryService->publishRefresh('users');
        $this->summary = $summaryService->refreshUsersSummary();
    }

    public function render()
    {
        return view('livewire.users-summary-dashboard')
            ->layout('layouts.dashboard', ['title' => 'Users Summary']);
    }
}
