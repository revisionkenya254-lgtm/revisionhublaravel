<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\CatalogCacheClear;

class ProductNoteTopic extends Model
{
    use HasFactory;

    public const TYPE_CHAPTER = 'chapter';
    public const TYPE_TOPIC = 'topic';
    public const TYPE_READING = 'reading';
    public const TYPE_RESOURCE = 'resource';

    protected $fillable = [
        'product_note_id',
        'parent_topic_id',
        'parent_id',
        'node_type',
        'title',
        'slug',
        'summary',
        'sort_order',
        'estimated_read_minutes',
        'is_published',
        'content_json',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'content_json' => 'array',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(ProductNote::class, 'product_note_id');
    }

    public function parentTopic(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childTopics(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(ProductNoteBlock::class, 'topic_id')->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ProductNoteResource::class, 'topic_id')->orderBy('sort_order');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isChapter(): bool
    {
        return $this->node_type === self::TYPE_CHAPTER;
    }

    public function isTopic(): bool
    {
        return $this->node_type === self::TYPE_TOPIC;
    }

    public function isReading(): bool
    {
        return $this->node_type === self::TYPE_READING;
    }

    public function isResource(): bool
    {
        return $this->node_type === self::TYPE_RESOURCE;
    }

    public function isLeaf(): bool
    {
        return in_array($this->node_type, [self::TYPE_READING, self::TYPE_RESOURCE], true);
    }

    public static function allowedNodeTypes(): array
    {
        return [
            self::TYPE_CHAPTER,
            self::TYPE_TOPIC,
            self::TYPE_READING,
            self::TYPE_RESOURCE,
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CatalogCacheClear::clear());
        static::deleted(fn () => CatalogCacheClear::clear());
    }
}
