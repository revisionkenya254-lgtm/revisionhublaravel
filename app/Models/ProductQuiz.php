<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'tier',
        'difficulty',
        'duration_minutes',
        'attempt_limit',
        'pass_mark',
        'question_count_cache',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuizQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ProductQuizAttempt::class)->orderByDesc('id');
    }

    public function getTotalMarksAttribute(): int
    {
        if ($this->relationLoaded('questions')) {
            return (int) $this->questions->sum('marks');
        }

        return (int) $this->questions()->sum('marks');
    }
}
