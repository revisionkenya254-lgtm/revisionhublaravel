<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_quiz_id',
        'prompt',
        'question_type',
        'marks',
        'sort_order',
        'correct_text_answer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(ProductQuiz::class, 'product_quiz_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductQuizOption::class, 'question_id')->orderBy('sort_order')->orderBy('id');
    }
}
