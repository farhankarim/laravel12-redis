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
