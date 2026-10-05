<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDocumentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_document_id',
        'sort_order',
        'section_label',
        'question_number',
        'question_label',
        'part_label',
        'marks',
        'marks_label',
        'content',
        'raw_text',
        'metadata',
    ];

    protected $casts = [
        'question_number' => 'integer',
        'sort_order' => 'integer',
        'marks' => 'integer',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'ai_document_id');
    }
}
