<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class ProductQuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_quiz_id',
        'product_id',
        'user_id',
        'attempt_number',
        'score',
        'total_marks',
        'percentage',
        'status',
        'submitted_at',
        'quiz_snapshot',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'quiz_snapshot' => 'array',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(ProductQuiz::class, 'product_quiz_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ProductQuizAttemptAnswer::class, 'attempt_id');
    }

    public function snapshotQuestionById(?int $questionId): ?array
    {
        if ($questionId === null) {
            return null;
        }

        return collect(Arr::get($this->quiz_snapshot, 'questions', []))
            ->first(fn (array $question) => (int) ($question['id'] ?? 0) === $questionId);
    }
}
