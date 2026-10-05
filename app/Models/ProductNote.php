<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\CatalogCacheClear;

class ProductNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'excerpt',
        'intro_html',
        'estimated_read_minutes',
        'difficulty',
        'show_resources',
        'show_discussion',
        'prerequisites',
    ];

    protected $casts = [
        'show_resources' => 'boolean',
        'show_discussion' => 'boolean',
        'prerequisites' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(ProductNoteTopic::class)->orderBy('sort_order');
    }

    public function curriculumRoots(): HasMany
    {
        return $this->hasMany(ProductNoteTopic::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ProductNoteResource::class)->whereNull('topic_id')->orderBy('sort_order');
    }

    public function aiDocuments(): HasMany
    {
        return $this->hasMany(AiDocument::class);
    }

    protected static function booted(): void
    {
        static::saved(fn () => CatalogCacheClear::clear());
        static::deleted(fn () => CatalogCacheClear::clear());
    }
}
