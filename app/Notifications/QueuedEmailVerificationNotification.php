<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QueuedEmailVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $userIdentifier,
        public string $email,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = rtrim((string) config('app.url'), '/').'/email/verify?user='.$this->userIdentifier;

        return (new MailMessage)
            ->subject('Verify your email address')
            ->line('Please verify your email address to complete account verification.')
            ->line('User reference: '.$this->userIdentifier)
            ->action('Verify Email', $verificationUrl)
            ->line('If you did not request this, you can ignore this email.');
    }
}
