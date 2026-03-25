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

    /**
     * Columns that are permitted as the id/email column arguments.
     * Prevents column-injection attacks when job payloads are replayed or
     * manipulated in the queue store.
     *
     * @var list<string>
     */
    public const ALLOWED_ID_COLUMNS = ['id', 'user_id', 'uuid'];

    public const ALLOWED_EMAIL_COLUMNS = ['email', 'email_address'];

    public int $timeout = 120;

    /**
     * @param  list<int>  $userIds
     */
    public function __construct(
        public array $userIds,
        public string $idColumn,
        public string $emailColumn,
    ) {
        if (! in_array($idColumn, self::ALLOWED_ID_COLUMNS, true)) {
            throw new \InvalidArgumentException(
                "Column '{$idColumn}' is not an allowed id column."
            );
        }

        if (! in_array($emailColumn, self::ALLOWED_EMAIL_COLUMNS, true)) {
            throw new \InvalidArgumentException(
                "Column '{$emailColumn}' is not an allowed email column."
            );
        }
    }

    public function handle(): void
    {
        // Use lazy() / cursor-based iteration so a large chunk does not load
        // all rows into memory at once.
        DB::table('users')
            ->whereIn($this->idColumn, $this->userIds)
            ->whereNotNull($this->emailColumn)
            ->orderBy($this->idColumn)
            ->lazy(100, $this->idColumn)
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
