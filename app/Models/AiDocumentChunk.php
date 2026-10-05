<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_document_id',
        'chunk_index',
        'page_start',
        'page_end',
        'heading',
        'content',
        'content_hash',
        'token_count',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'ai_document_id');
    }
}
