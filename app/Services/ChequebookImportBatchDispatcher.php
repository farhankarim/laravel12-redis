<?php

namespace App\Services;

use App\Jobs\ProcessChequebookChunkJob;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChequebookImportBatchDispatcher
{
    public function dispatch(
        string $companyPrefix,
        int $batchSize,
        string $queue,
        ?int $createdBy = null,
    ): array {
        $maxBatchSize = (int) config('chequebook.max_batch_size', 50000);

        if ($batchSize < 1 || $batchSize > $maxBatchSize) {
            throw new InvalidArgumentException("Batch size must be between 1 and {$maxBatchSize}.");
        }

        if (! DB::table('companies')->where('prefix', $companyPrefix)->exists()) {
            throw new InvalidArgumentException("Company prefix [{$companyPrefix}] not found in companies table.");
        }

        $pendingIds = DB::table('cheque_book_requests')
            ->where('status', 'pending')
            ->orderBy('record_id')
            ->pluck('record_id');

        if ($pendingIds->isEmpty()) {
            return [
                'queued' => false,
                'message' => 'No pending cheque book requests found.',
                'batch' => null,
                'pending_requests' => 0,
                'jobs_dispatched' => 0,
                'queue' => $queue,
            ];
        }

        $jobs = $pendingIds
            ->chunk($batchSize)
            ->map(fn ($ids) => new ProcessChequebookChunkJob(
                requestIds: $ids->values()->all(),
                companyPrefix: $companyPrefix,
                createdBy: $createdBy,
            ))
            ->all();

        $batch = Bus::batch($jobs)
            ->name('chequebook-import-'.now()->format('YmdHis'))
            ->onConnection('redis')
            ->onQueue($queue)
            ->dispatch();

        return [
            'queued' => true,
            'message' => 'Chequebook import batch queued on Redis.',
            'batch' => $batch,
            'pending_requests' => $pendingIds->count(),
            'jobs_dispatched' => count($jobs),
            'queue' => $queue,
        ];
    }
}
