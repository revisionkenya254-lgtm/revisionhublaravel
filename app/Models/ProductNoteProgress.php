<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNoteProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'topic_id',
        'last_block_id',
        'completion_percent',
        'last_read_at',
    ];

    protected $casts = [
        'completion_percent' => 'float',
        'last_read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ProductNoteTopic::class, 'topic_id');
    }

    public function lastBlock(): BelongsTo
    {
        return $this->belongsTo(ProductNoteBlock::class, 'last_block_id');
    }
}
