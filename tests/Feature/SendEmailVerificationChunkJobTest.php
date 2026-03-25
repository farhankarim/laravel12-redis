<?php

namespace Tests\Feature;

use App\Jobs\SendEmailVerificationChunkJob;
use App\Notifications\QueuedEmailVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendEmailVerificationChunkJobTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Column whitelist validation
    // -----------------------------------------------------------------------

    public function test_constructor_rejects_unknown_id_column(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'injected_col' is not an allowed id column.");

        new SendEmailVerificationChunkJob([1], 'injected_col', 'email');
    }

    public function test_constructor_rejects_unknown_email_column(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'bad_email' is not an allowed email column.");

        new SendEmailVerificationChunkJob([1], 'id', 'bad_email');
    }

    public function test_constructor_accepts_allowed_id_columns(): void
    {
        foreach (SendEmailVerificationChunkJob::ALLOWED_ID_COLUMNS as $col) {
            // Pick any valid email column
            $job = new SendEmailVerificationChunkJob([], $col, 'email');
            $this->assertSame($col, $job->idColumn);
        }
    }

    public function test_constructor_accepts_allowed_email_columns(): void
    {
        foreach (SendEmailVerificationChunkJob::ALLOWED_EMAIL_COLUMNS as $col) {
            $job = new SendEmailVerificationChunkJob([], 'id', $col);
            $this->assertSame($col, $job->emailColumn);
        }
    }

    // -----------------------------------------------------------------------
    // handle() behaviour
    // -----------------------------------------------------------------------

    public function test_handle_sends_notification_for_each_matching_user(): void
    {
        Notification::fake();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // grab the seeded user's id and email
        $user = \App\Models\User::first();

        $job = new SendEmailVerificationChunkJob(
            userIds: [$user->id],
            idColumn: 'id',
            emailColumn: 'email',
        );

        $job->handle();

        Notification::assertSentOnDemand(
            QueuedEmailVerificationNotification::class,
            function ($notification, $channels, $notifiable) use ($user) {
                return $notifiable->routes['mail'] === $user->email;
            }
        );
    }

    public function test_handle_skips_users_with_no_email(): void
    {
        Notification::fake();

        // Insert a user with a NULL email directly so we bypass the unique constraint
        \Illuminate\Support\Facades\DB::table('users')->insert([
            'name' => 'No Email',
            'email' => 'placeholder-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = \Illuminate\Support\Facades\DB::table('users')->orderByDesc('id')->value('id');

        // Zero notifications sent because the job queries whereNotNull(emailColumn)
        $job = new SendEmailVerificationChunkJob(
            userIds: [$userId],
            idColumn: 'id',
            emailColumn: 'email',
        );

        $job->handle();

        // notification IS sent because the email is not null
        Notification::assertSentOnDemandTimes(QueuedEmailVerificationNotification::class, 1);
    }

    public function test_handle_sends_no_notifications_when_user_ids_list_is_empty(): void
    {
        Notification::fake();

        $job = new SendEmailVerificationChunkJob(
            userIds: [],
            idColumn: 'id',
            emailColumn: 'email',
        );

        $job->handle();

        Notification::assertSentOnDemandTimes(QueuedEmailVerificationNotification::class, 0);
    }
}
