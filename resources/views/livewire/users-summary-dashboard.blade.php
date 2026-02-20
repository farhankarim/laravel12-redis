<section wire:poll.10s="loadSummary" class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Users Data Summary</h2>
            <p class="text-sm text-gray-600">Updated: {{ $summary['updated_at'] ?? 'n/a' }}</p>
        </div>

        <button
            wire:click="refreshSummary"
            type="button"
            class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white"
        >
            Refresh via Redis Pub/Sub
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Total Users</p>
            <p class="text-2xl font-semibold">{{ $summary['total_users'] ?? 0 }}</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Verified Users</p>
            <p class="text-2xl font-semibold">{{ $summary['verified_users'] ?? 0 }}</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Unverified Users</p>
            <p class="text-2xl font-semibold">{{ $summary['unverified_users'] ?? 0 }}</p>
        </div>
    </div>

    <div class="rounded border bg-white p-4">
        <h3 class="mb-2 text-sm font-semibold text-gray-700">Latest User</h3>

        @if (! empty($summary['latest_user']))
            <ul class="space-y-1 text-sm text-gray-700">
                <li><span class="font-medium">ID:</span> {{ $summary['latest_user']['id'] }}</li>
                <li><span class="font-medium">Name:</span> {{ $summary['latest_user']['name'] }}</li>
                <li><span class="font-medium">Email:</span> {{ $summary['latest_user']['email'] }}</li>
                <li><span class="font-medium">Created:</span> {{ $summary['latest_user']['created_at'] ?? 'n/a' }}</li>
            </ul>
        @else
            <p class="text-sm text-gray-500">No users found.</p>
        @endif
    </div>
</section>
