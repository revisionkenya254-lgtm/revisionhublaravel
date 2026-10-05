<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'instructor_id',
        'product_id',
        'product_note_id',
        'source_type',
        'source_name',
        'original_path',
        'storage_disk',
        'bunny_folder_path',
        'mime_type',
        'file_extension',
        'file_hash',
        'status',
        'progress',
        'page_count',
        'character_count',
        'extracted_text_path',
        'extracted_text_excerpt',
        'metadata',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
        'progress' => 'integer',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productNote(): BelongsTo
    {
        return $this->belongsTo(ProductNote::class, 'product_note_id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiDocumentChunk::class);
    }

    public function processingLogs(): HasMany
    {
        return $this->hasMany(AiDocumentProcessingLog::class);
    }

    public function questionRegions(): HasMany
    {
        return $this->hasMany(AiDocumentQuestionRegion::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AiDocumentQuestion::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRequiresOcr($query)
    {
        return $query->where('status', 'requires_ocr');
    }
}
