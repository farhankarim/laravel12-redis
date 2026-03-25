<?php

namespace Tests\Feature;

use App\Notifications\QueuedEmailVerificationNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class QueuedEmailVerificationNotificationTest extends TestCase
{
    public function test_notification_sends_via_mail(): void
    {
        $notification = new QueuedEmailVerificationNotification('42', 'user@example.com');

        $this->assertSame(['mail'], $notification->via(new \stdClass));
    }

    public function test_notification_mail_message_contains_signed_url(): void
    {
        $notification = new QueuedEmailVerificationNotification('42', 'user@example.com');

        $mail = $notification->toMail(new \stdClass);

        $this->assertInstanceOf(MailMessage::class, $mail);

        // The action URL must be a signed route — it must contain 'signature' and 'expires'
        $actionUrl = collect($mail->actionUrl)->first() ?? $mail->actionUrl;
        $this->assertStringContainsString('signature=', $actionUrl);
        $this->assertStringContainsString('expires=', $actionUrl);
    }

    public function test_notification_mail_subject_is_set(): void
    {
        $notification = new QueuedEmailVerificationNotification('99', 'test@example.com');
        $mail = $notification->toMail(new \stdClass);

        $this->assertSame('Verify your email address', $mail->subject);
    }

    public function test_email_verification_route_rejects_unsigned_request(): void
    {
        $response = $this->get('/email/verify?user=1');

        $response->assertForbidden();
    }

    public function test_email_verification_route_accepts_valid_signed_url(): void
    {
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'email.verify',
            now()->addHour(),
            ['user' => '1'],
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertJson(['message' => 'Email verified successfully.']);
    }

    public function test_email_verification_route_rejects_expired_signature(): void
    {
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'email.verify',
            now()->subSecond(),
            ['user' => '1'],
        );

        $response = $this->get($url);

        $response->assertForbidden();
    }
}
