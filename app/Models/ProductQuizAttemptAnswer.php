<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuizAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_option_id',
        'typed_answer',
        'is_correct',
        'awarded_marks',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ProductQuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ProductQuizQuestion::class, 'question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(ProductQuizOption::class, 'selected_option_id');
    }

    public function snapshotQuestion(): ?array
    {
        return $this->attempt?->snapshotQuestionById($this->question_id);
    }

    public function snapshotSelectedOption(): ?array
    {
        $selectedOptionId = $this->selected_option_id;

        if ($selectedOptionId === null) {
            return null;
        }

        return collect($this->snapshotQuestion()['options'] ?? [])
            ->first(fn (array $option) => (int) ($option['id'] ?? 0) === (int) $selectedOptionId);
    }
}
