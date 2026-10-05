<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDocumentProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_document_id',
        'stage',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(AiDocument::class, 'ai_document_id');
    }
}
