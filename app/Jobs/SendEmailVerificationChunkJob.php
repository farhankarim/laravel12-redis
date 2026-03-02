<?php

namespace App\Jobs;

use App\Notifications\QueuedEmailVerificationNotification;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendEmailVerificationChunkJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $timeout = 120;

    /**
     * @param list<int> $userIds
     */
    public function __construct(
        public array $userIds,
        public string $idColumn,
        public string $emailColumn,
    ) {
    }

    public function handle(): void
    {
        DB::table('users')
            ->whereIn($this->idColumn, $this->userIds)
            ->whereNotNull($this->emailColumn)
            ->orderBy($this->idColumn)
            ->get([$this->idColumn, $this->emailColumn])
            ->each(function (object $user): void {
                $email = (string) data_get($user, $this->emailColumn);
                $identifier = (string) data_get($user, $this->idColumn);

                if ($email === '') {
                    return;
                }

                Notification::route('mail', $email)
                    ->notify(new QueuedEmailVerificationNotification($identifier, $email));
            });
    }
}
