<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes CSV export files that are older than a configurable number of days.
 * Runs weekly via the scheduler (see routes/console.php).
 */
class PruneStaleExportsCommand extends Command
{
    protected $signature = 'storage:prune-stale-exports
                            {--days=7 : Delete files older than this many days}
                            {--disk= : Storage disk to use (defaults to FILESYSTEM_DISK)}';

    protected $description = 'Delete stale CSV export files from the exports/students directory';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $disk = $this->option('disk') ?: (config('filesystems.default') === 's3' ? 's3' : 'local');

        $storage = Storage::disk($disk);
        $directory = 'exports/students';

        if (! $storage->exists($directory)) {
            $this->info("Directory [{$directory}] does not exist on disk [{$disk}]. Nothing to prune.");

            return self::SUCCESS;
        }

        $files = $storage->files($directory);
        $cutoff = now()->subDays($days)->timestamp;
        $pruned = 0;

        foreach ($files as $file) {
            if ($storage->lastModified($file) < $cutoff) {
                $storage->delete($file);
                $pruned++;
            }
        }

        $this->info("Pruned {$pruned} stale export file(s) from [{$disk}::{$directory}].");

        return self::SUCCESS;
    }
}
