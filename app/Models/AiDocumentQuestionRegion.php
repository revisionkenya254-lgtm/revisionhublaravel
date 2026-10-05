<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDocumentQuestionRegion extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_document_id',
        'question_number',
        'question_label',
        'page_number',
        'x',
        'y',
        'width',
        'height',
        'content',
        'line_payload',
        'metadata',
    ];

    protected $casts = [
        'question_number' => 'integer',
        'page_number' => 'integer',
        'x' => 'float',
        'y' => 'float',
        'width' => 'float',
        'height' => 'float',
        'line_payload' => 'array',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'ai_document_id');
    }
}
