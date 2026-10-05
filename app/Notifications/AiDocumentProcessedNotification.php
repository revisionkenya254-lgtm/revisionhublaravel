<?php

namespace App\Notifications;

use App\Models\AiDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AiDocumentProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AiDocument $document)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->payload();

        return (new MailMessage)
            ->subject($payload['title'])
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('Instructor')]))
            ->line($payload['message'])
            ->action(__('View document'), $payload['url'])
            ->line(__('You can open the AI documents page to review the extracted text and chunks.'));
    }

    private function payload(): array
    {
        return [
            'type' => 'ai_document_processed',
            'title' => __('AI document processed'),
            'message' => __('Your document ":name" has finished processing and is ready to review.', [
                'name' => $this->document->source_name,
            ]),
            'document_id' => $this->document->id,
            'document_name' => $this->document->source_name,
            'document_type' => strtolower((string) ($this->document->file_extension ?: pathinfo($this->document->original_path, PATHINFO_EXTENSION))),
            'url' => route('instructor.ai-documents.show', $this->document->id),
            'created_at' => optional($this->document->processed_at ?? $this->document->updated_at)->toIso8601String(),
        ];
    }
}
