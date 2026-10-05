<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'question_id',
        'reply',
        'is_ai',
        'ai_provider',
        'ai_model',
        'ai_context',
        'metadata',
    ];

    protected $casts = [
        'is_ai' => 'boolean',
        'metadata' => 'array',
    ];

    function user() : BelongsTo{
       return $this->belongsTo(User::class, 'user_id', 'id'); 
    }
}
