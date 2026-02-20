<?php

namespace App\Jobs;

use App\Services\ChequebookImportService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessChequebookChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public array $requestIds,
        public string $companyPrefix,
        public ?int $createdBy = null,
    ) {
        $this->onConnection('redis');
        $this->onQueue(config('chequebook.queue', 'chequebook-imports'));
    }

    public function handle(ChequebookImportService $service): void
    {
        if ($this->batch()?->cancelled()) {
            Log::warning('Chequebook chunk skipped because batch is cancelled.', $this->context());

            return;
        }

        Log::info('Chequebook chunk job started.', $this->context());

        $startedAt = microtime(true);

        try {
            $processed = $service->processChunk(
                requestIds: $this->requestIds,
                companyPrefix: $this->companyPrefix,
                createdBy: $this->createdBy,
            );

            Log::info('Chequebook chunk job completed.', $this->context([
                'processed_count' => $processed,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]));
        } catch (Throwable $exception) {
            Log::error('Chequebook chunk job failed in handle.', $this->context([
                'error' => $exception->getMessage(),
            ]));

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('Chequebook chunk job marked as failed.', $this->context([
            'error' => $exception->getMessage(),
        ]));
    }

    private function context(array $extra = []): array
    {
        $base = [
            'batch_id' => $this->batch()?->id,
            'request_count' => count($this->requestIds),
            'first_request_id' => $this->requestIds[0] ?? null,
            'last_request_id' => $this->requestIds[array_key_last($this->requestIds)] ?? null,
            'company_prefix' => $this->companyPrefix,
            'created_by' => $this->createdBy,
            'queue' => config('chequebook.queue', 'chequebook-imports'),
        ];

        return array_merge($base, $extra);
    }
}
