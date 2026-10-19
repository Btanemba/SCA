<?php

namespace App\Notifications;

use App\Models\StaffDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffDocumentPublished extends Notification
{
    use Queueable;

    public function __construct(private StaffDocument $document)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A Springcare Academy document is ready')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new document, "'.$this->document->title.'", is ready in your staff portal.')
            ->action('View my documents', route('staff-documents.mine'))
            ->line('Sign in to your Springcare Academy account to download it.');
    }
}
