<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNoteBookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'topic_id',
        'block_id',
        'label',
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

    public function block(): BelongsTo
    {
        return $this->belongsTo(ProductNoteBlock::class, 'block_id');
    }
}
