<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNoteResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_note_id',
        'topic_id',
        'title',
        'resource_type',
        'url_or_path',
        'meta_json',
        'sort_order',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(ProductNote::class, 'product_note_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ProductNoteTopic::class, 'topic_id');
    }
}
