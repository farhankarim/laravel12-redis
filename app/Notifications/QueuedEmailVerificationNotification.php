<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class QueuedEmailVerificationNotification extends Notification
{
    use Queueable;

    /**
     * The link is valid for 24 hours and is HMAC-signed so callers cannot
     * forge a verification URL for an arbitrary user identifier.
     */
    private const EXPIRES_IN_HOURS = 24;

    public function __construct(
        public string $userIdentifier,
        public string $email,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'email.verify',
            now()->addHours(self::EXPIRES_IN_HOURS),
            ['user' => $this->userIdentifier],
        );

        return (new MailMessage)
            ->subject('Verify your email address')
            ->line('Please verify your email address to complete account verification.')
            ->line('User reference: '.$this->userIdentifier)
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not request this, you can ignore this email.')
            ->line('This link will expire in '.self::EXPIRES_IN_HOURS.' hours.');
    }
}
