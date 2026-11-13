<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells admins a contact message arrived. Queued, so a slow mail server never slows the form.
 * Locally MAIL_MAILER=log writes it to storage/logs/laravel.log.
 */
class ContactMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New contact message: '.$this->contactMessage->subject)
            ->line("From: {$this->contactMessage->name} <{$this->contactMessage->email}>")
            ->line($this->contactMessage->message)
            ->action('Open messages', route('admin.messages.index'))
            ->replyTo($this->contactMessage->email, $this->contactMessage->name);
    }
}
