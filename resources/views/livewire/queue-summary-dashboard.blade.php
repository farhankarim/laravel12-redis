<div wire:poll.10s="loadSummary">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">Redis Queue Summary</h4>
            <small class="text-body-secondary">Updated: {{ $summary['updated_at'] ?? 'n/a' }}</small>
        </div>
        <button wire:click="refreshSummary" type="button" class="btn btn-dark btn-sm">
            Refresh via Redis Pub/Sub
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['totals']['redis_pending'] ?? 0 }}</div>
                    <div>Redis Pending</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['totals']['redis_reserved'] ?? 0 }}</div>
                    <div>Redis Reserved</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['totals']['redis_delayed'] ?? 0 }}</div>
                    <div>Redis Delayed</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['totals']['failed_jobs'] ?? 0 }}</div>
                    <div>Failed Jobs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card bg-body-secondary">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['totals']['batch_total_jobs'] ?? 0 }}</div>
                    <div>Batch Total Jobs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card bg-body-secondary">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['totals']['batch_pending_jobs'] ?? 0 }}</div>
                    <div>Batch Pending Jobs</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Per-Queue Breakdown</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Queue</th>
                            <th>DB Jobs</th>
                            <th>Redis Pending</th>
                            <th>Redis Reserved</th>
                            <th>Redis Delayed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($summary['queues'] ?? []) as $queue)
                            <tr>
                                <td class="fw-medium">{{ $queue['name'] }}</td>
                                <td>{{ $queue['database_jobs'] }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $queue['redis_pending'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $queue['redis_reserved'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark">{{ $queue['redis_delayed'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-body-secondary py-4">No queue rows yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
