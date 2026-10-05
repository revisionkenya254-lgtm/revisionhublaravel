<?php

namespace App\Notifications;

use App\Models\LessonQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLessonQuestionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly LessonQuestion $question)
    {
        $this->afterCommit();
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
            ->action(__('View lesson questions'), $payload['url']);
    }

    private function payload(): array
    {
        $this->question->loadMissing('lesson:id,title,lecture_number', 'course:id,title');

        return [
            'type' => 'lesson_question',
            'title' => __('New lesson question'),
            'message' => __('A student asked a question about :lesson.', [
                'lesson' => $this->question->lesson?->title ?: __('your lesson'),
            ]),
            'question_id' => $this->question->id,
            'course_id' => $this->question->course_id,
            'lesson_id' => $this->question->lesson_id,
            'question' => $this->question->question_title,
            'url' => route('instructor.lesson-questions.index'),
            'created_at' => optional($this->question->created_at)->toIso8601String(),
        ];
    }
}
