<?php

namespace App\Http\Controllers;

use App\Services\ChequebookImportBatchDispatcher;
use App\Services\ChequebookImportService;
use Illuminate\Bus\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChequebookImportController extends Controller
{
    public function dispatch(Request $request, ChequebookImportBatchDispatcher $dispatcher): JsonResponse
    {
        $maxBatchSize = (int) config('chequebook.max_batch_size', 50000);

        $payload = $request->validate([
            'company_prefix' => ['nullable', 'string', 'max:20'],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:'.$maxBatchSize],
            'queue' => ['nullable', 'string', 'max:100'],
            'created_by' => ['nullable', 'integer', 'min:1'],
        ]);

        $companyPrefix = $payload['company_prefix'] ?? config('chequebook.company_prefix', 'qr');
        $batchSize = (int) ($payload['batch_size'] ?? config('chequebook.batch_size', 500));
        $queue = $payload['queue'] ?? config('chequebook.queue', 'chequebook-imports');
        $createdBy = $payload['created_by'] ?? null;

        try {
            $result = $dispatcher->dispatch(
                companyPrefix: $companyPrefix,
                batchSize: $batchSize,
                queue: $queue,
                createdBy: $createdBy,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        if (! $result['queued']) {
            return response()->json([
                'message' => $result['message'],
            ]);
        }

        /** @var Batch $batch */
        $batch = $result['batch'];

        return response()->json([
            'message' => $result['message'],
            'batch_id' => $batch->id,
            'pending_requests' => $result['pending_requests'],
            'jobs_dispatched' => $result['jobs_dispatched'],
            'queue' => $result['queue'],
        ], 202);
    }

    public function dispatchSync(Request $request, ChequebookImportService $service): JsonResponse
    {
        $maxBatchSize = (int) config('chequebook.max_batch_size', 50000);

        $payload = $request->validate([
            'company_prefix' => ['nullable', 'string', 'max:20'],
            'batch_size' => ['nullable', 'integer', 'min:1', 'max:'.$maxBatchSize],
            'created_by' => ['nullable', 'integer', 'min:1'],
        ]);

        $companyPrefix = $payload['company_prefix'] ?? config('chequebook.company_prefix', 'qr');
        $batchSize = (int) ($payload['batch_size'] ?? config('chequebook.batch_size', 500));
        $createdBy = $payload['created_by'] ?? null;

        if ($batchSize < 1 || $batchSize > $maxBatchSize) {
            return response()->json([
                'message' => "Batch size must be between 1 and {$maxBatchSize}.",
            ], 422);
        }

        if (! DB::table('companies')->where('prefix', $companyPrefix)->exists()) {
            return response()->json([
                'message' => "Company prefix [{$companyPrefix}] not found in companies table.",
            ], 422);
        }

        $pendingIds = DB::table('cheque_book_requests')
            ->where('status', 'pending')
            ->orderBy('record_id')
            ->pluck('record_id');

        if ($pendingIds->isEmpty()) {
            return response()->json([
                'message' => 'No pending cheque book requests found.',
                'processed_count' => 0,
            ]);
        }

        $startedAt = microtime(true);
        $processedCount = 0;

        foreach ($pendingIds->chunk($batchSize) as $chunk) {
            $processedCount += $service->processChunk(
                requestIds: $chunk->values()->all(),
                companyPrefix: $companyPrefix,
                createdBy: $createdBy,
            );
        }

        return response()->json([
            'message' => 'Chequebook import completed synchronously.',
            'processed_count' => $processedCount,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);
    }

    public function status(string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch instanceof Batch) {
            return response()->json([
                'message' => 'Batch not found.',
            ], 404);
        }

        return response()->json([
            'id' => $batch->id,
            'name' => $batch->name,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'processed_jobs' => $batch->processedJobs(),
            'failed_jobs' => $batch->failedJobs,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
            'created_at' => $batch->createdAt,
            'finished_at' => $batch->finishedAt,
        ]);
    }
}
