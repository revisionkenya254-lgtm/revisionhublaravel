<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNoteBlock extends Model
{
    use HasFactory;

    public const TYPE_RICH_TEXT = 'rich_text';
    public const TYPE_HEADING = 'heading';
    public const TYPE_IMAGE = 'image';
    public const TYPE_EMBED = 'embed';
    public const TYPE_CODE = 'code';
    public const TYPE_TABLE = 'table';
    public const TYPE_CALLOUT = 'callout';
    public const TYPE_BULLET_LIST = 'bullet_list';
    public const TYPE_QUOTE = 'quote';
    public const TYPE_DIVIDER = 'divider';

    protected $fillable = [
        'topic_id',
        'block_type',
        'content_json',
        'sort_order',
    ];

    protected $casts = [
        'content_json' => 'array',
    ];

    public static function allowedTypes(): array
    {
        return [
            self::TYPE_RICH_TEXT,
            self::TYPE_HEADING,
            self::TYPE_IMAGE,
            self::TYPE_EMBED,
            self::TYPE_CODE,
            self::TYPE_TABLE,
            self::TYPE_CALLOUT,
            self::TYPE_BULLET_LIST,
            self::TYPE_QUOTE,
            self::TYPE_DIVIDER,
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ProductNoteTopic::class, 'topic_id');
    }
}
