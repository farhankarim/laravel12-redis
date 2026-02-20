<section wire:poll.10s="loadSummary" class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Redis Queue Summary</h2>
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
            <p class="text-sm text-gray-600">Redis Pending</p>
            <p class="text-2xl font-semibold">{{ $summary['totals']['redis_pending'] ?? 0 }}</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Redis Reserved</p>
            <p class="text-2xl font-semibold">{{ $summary['totals']['redis_reserved'] ?? 0 }}</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Redis Delayed</p>
            <p class="text-2xl font-semibold">{{ $summary['totals']['redis_delayed'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Failed Jobs</p>
            <p class="text-2xl font-semibold">{{ $summary['totals']['failed_jobs'] ?? 0 }}</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Batch Total Jobs</p>
            <p class="text-2xl font-semibold">{{ $summary['totals']['batch_total_jobs'] ?? 0 }}</p>
        </div>
        <div class="rounded border bg-white p-4">
            <p class="text-sm text-gray-600">Batch Pending Jobs</p>
            <p class="text-2xl font-semibold">{{ $summary['totals']['batch_pending_jobs'] ?? 0 }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded border bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Queue</th>
                    <th class="px-4 py-3 font-medium">DB jobs</th>
                    <th class="px-4 py-3 font-medium">Redis pending</th>
                    <th class="px-4 py-3 font-medium">Redis reserved</th>
                    <th class="px-4 py-3 font-medium">Redis delayed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse (($summary['queues'] ?? []) as $queue)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $queue['name'] }}</td>
                        <td class="px-4 py-3">{{ $queue['database_jobs'] }}</td>
                        <td class="px-4 py-3">{{ $queue['redis_pending'] }}</td>
                        <td class="px-4 py-3">{{ $queue['redis_reserved'] }}</td>
                        <td class="px-4 py-3">{{ $queue['redis_delayed'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">No queue rows yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
