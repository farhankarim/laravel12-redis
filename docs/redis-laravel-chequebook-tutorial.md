# Redis + Laravel Tutorial (Project-Specific)

This tutorial explains the Redis concepts used in this project and how they are wired into Laravel for the chequebook import flow.

## 1) Why Redis in this project

The chequebook import can process thousands of requests (`cheque_book_requests`).
Running this inline in HTTP would be slow and fragile, so this project uses:

- Redis as a fast queue backend
- Laravel jobs for chunked background processing
- Laravel batches for grouping many jobs under one batch id

Result: imports are asynchronous, traceable, and restart-safe.

---

## 2) Redis concepts used here

### Queue (FIFO work stream)

A queue is a named list of pending jobs. In this project:

- Queue connection: `redis`
- Queue name: `chequebook-imports`

Relevant config:

- `QUEUE_CONNECTION=redis`
- `REDIS_CLIENT=predis`
- `CHEQUEBOOK_QUEUE=chequebook-imports`

### Worker

A worker is a long-running process that pulls jobs from Redis and executes them.

Command used:

```bash
php artisan queue:work redis --queue=chequebook-imports --tries=1 -v
```

### Batch

A batch groups multiple jobs and gives one batch id for progress/failure tracking.
This project dispatches chunk jobs with `Bus::batch(...)`.

- Job class in the batch: `App\Jobs\ProcessChequebookChunkJob`
- Batch metadata is stored in `job_batches`

### Failure and cancellation behavior

By default, one failed job in a batch marks the batch as cancelled. Remaining jobs are skipped.
That is why logs may show:

- `Chequebook chunk skipped because batch is cancelled.`

This is expected behavior when any chunk fails.

---

## 3) Laravel integration used in this repo

### Dispatch entry points

You can dispatch chequebook imports from:

- HTTP endpoint: [app/Http/Controllers/ChequebookImportController.php](../app/Http/Controllers/ChequebookImportController.php)
- Artisan command: [routes/console.php](../routes/console.php)

Both use the shared dispatcher service:

- [app/Services/ChequebookImportBatchDispatcher.php](../app/Services/ChequebookImportBatchDispatcher.php)

### Job execution

Each chunk is processed by:

- [app/Jobs/ProcessChequebookChunkJob.php](../app/Jobs/ProcessChequebookChunkJob.php)

The job uses:

- `ShouldQueue` (async execution)
- `Batchable` (required for `Bus::batch`)
- structured logging for start/success/failure

Business logic lives in:

- [app/Services/ChequebookImportService.php](../app/Services/ChequebookImportService.php)

---

## 4) End-to-end flow

1. Dispatch command/endpoint reads pending request IDs.
2. IDs are chunked (for example, 500 each).
3. One Redis job is queued per chunk.
4. Worker pulls a chunk job and runs import logic.
5. Batch progress/failures are tracked in `job_batches`.
6. Logs are written to `storage/logs/laravel.log`.

---

## 5) Practical commands

## Setup

```bash
php artisan migrate
```

## Dispatch import (CLI)

```bash
php artisan chequebook:import --company-prefix=qr --batch-size=500 --queue=chequebook-imports
```

## Start worker

```bash
php artisan queue:work redis --queue=chequebook-imports --tries=1 -v
```

## Restart daemon workers after code changes

```bash
php artisan queue:restart
```

## See failed jobs

```bash
php artisan queue:failed
php artisan queue:show <failed-job-uuid>
php artisan queue:retry <failed-job-uuid>
```

## Watch logs

```bash
tail -f storage/logs/laravel.log
```

---

## 6) Debugging checklist

If jobs are not processing:

1. Verify Redis is up:

```bash
redis-cli ping
```

2. Verify queue worker is running on the correct queue name.
3. Check `failed_jobs` table and `laravel.log`.
4. Restart workers after code changes (`queue:restart`).

If you see `batch is cancelled` warnings:

- Find the first `ERROR` before those warnings.
- Fix that root exception.
- Re-dispatch a new batch.

---

## 7) Notes from this project’s real incident

A real failure observed during import:

- `cheque_book.product` is numeric (`BIGINT`)
- incoming request value was code string (`NB`)

Fix applied:

- map product codes/names to numeric `company_products.record_id` in import service before inserting into `cheque_book`.

This is a common queue pattern: use logs to identify the first failing payload, then add normalization before write.

---

## 8) Optional improvements

If you want to harden this further:

- Add dedicated log channel for chequebook jobs
- Add dead-letter strategy for repeatedly failing payloads
- Add metrics (processed per minute, fail rate)
- Allow batch processing to continue on failures (if business-acceptable)

