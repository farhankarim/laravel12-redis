<div wire:poll.10s="loadSummary">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-1">Users Data Summary</h4>
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
                    <div class="fs-4 fw-semibold">{{ $summary['total_users'] ?? 0 }}</div>
                    <div>Total Users</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['verified_users'] ?? 0 }}</div>
                    <div>Verified Users</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $summary['unverified_users'] ?? 0 }}</div>
                    <div>Unverified Users</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-semibold">Latest User</div>
        <div class="card-body">
            @if (! empty($summary['latest_user']))
                <table class="table table-borderless mb-0" style="max-width: 480px;">
                    <tbody>
                        <tr>
                            <th class="ps-0 text-body-secondary fw-medium" style="width: 110px;">ID</th>
                            <td>{{ $summary['latest_user']['id'] }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-body-secondary fw-medium">Name</th>
                            <td>{{ $summary['latest_user']['name'] }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-body-secondary fw-medium">Email</th>
                            <td>{{ $summary['latest_user']['email'] }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-body-secondary fw-medium">Created</th>
                            <td>{{ $summary['latest_user']['created_at'] ?? 'n/a' }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p class="text-body-secondary mb-0">No users found.</p>
            @endif
        </div>
    </div>
</div>
