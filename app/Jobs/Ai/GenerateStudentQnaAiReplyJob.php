<?php

namespace App\Jobs\Ai;

use App\Models\LessonQuestion;
use App\Services\Ai\StudentQnaAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateStudentQnaAiReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public readonly int $questionId)
    {
    }

    public function handle(StudentQnaAiService $aiService): void
    {
        $question = LessonQuestion::find($this->questionId);

        if (! $question) {
            return;
        }

        try {
            $aiService->answerQuestion($question);
        } catch (Throwable $throwable) {
            if (! app()->isLocal()) {
                report($throwable);
            }
        }
    }
}
