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
