<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CatalogCacheClear;
use App\Services\MenuCacheService;
use Modules\Course\app\Models\CourseCategory;
use Modules\Order\app\Models\OrderItem;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_COURSE = 'course';
    public const TYPE_PAST_PAPER = 'past_paper';
    public const TYPE_PREDICTION = 'prediction';
    public const TYPE_NOTE = 'note';
    public const TYPE_QUIZ = 'quiz';
    public const YEAR_ALL = 'all';
    public const YEAR_MISSING = 'missing';

    protected $fillable = [
        'course_id',
        'instructor_id',
        'category_id',
        'type',
        'title',
        'slug',
        'submission_signature',
        'thumbnail',
        'description',
        'price',
        'discount',
        'status',
        'is_approved',
        'metadata',
        'file_path',
        'file_type',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_approved', 'approved')->where('status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', 'approved');
    }

    public function scopeInstructorOwned($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeNonCourse($query)
    {
        return $query->where('type', '!=', 'course');
    }

    public function scopeForPaperYear(Builder $query, ?string $year): Builder
    {
        if ($year === self::YEAR_MISSING) {
            return $query->where(function (Builder $query) {
                $query->whereNull('metadata->year')
                    ->orWhere('metadata->year', '');
            });
        }

        return filled($year)
            ? $query->where('metadata->year', $year)
            : $query;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id', 'id')->withDefault();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id', 'id')->withDefault();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }
    
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function note(): HasOne
    {
        return $this->hasOne(ProductNote::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(ProductQuiz::class);
    }

    public function noteProgress(): HasMany
    {
        return $this->hasMany(ProductNoteProgress::class);
    }

    public function noteBookmarks(): HasMany
    {
        return $this->hasMany(ProductNoteBookmark::class);
    }

    public function aiDocuments(): HasMany
    {
        return $this->hasMany(AiDocument::class);
    }

    public function latestAiDocument(): HasOne
    {
        return $this->hasOne(AiDocument::class)->latestOfMany();
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(ProductQuizAttempt::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->discount !== null && (float) $this->discount > 0
            ? (float) $this->discount
            : (float) $this->price;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_COURSE => __('Course'),
            self::TYPE_PAST_PAPER => __('Past Paper'),
            self::TYPE_PREDICTION => __('Prediction'),
            self::TYPE_NOTE => __('Note'),
            self::TYPE_QUIZ => __('Quiz'),
            default => __('Product'),
        };
    }

    public function getAccessLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PAST_PAPER => __('Read online'),
            self::TYPE_PREDICTION => __('Read online'),
            self::TYPE_NOTE => __('Read online'),
            self::TYPE_QUIZ => __('Start quiz'),
            default => __('Open'),
        };
    }

    public function getPaperYearAttribute(): ?string
    {
        return self::normalizePaperYear($this->metadata['year'] ?? null);
    }

    public static function normalizePaperYear(mixed $year): ?string
    {
        $year = trim((string) $year);

        return preg_match('/^\d{4}$/', $year) === 1 ? $year : null;
    }

    public static function normalizePaperYearFilter(mixed $year): ?string
    {
        $year = trim((string) $year);

        if ($year === '' || $year === self::YEAR_ALL) {
            return null;
        }

        return $year === self::YEAR_MISSING
            ? self::YEAR_MISSING
            : self::normalizePaperYear($year);
    }

    public static function paperYearOptions(iterable $products, array $fallbackYears = []): array
    {
        return collect($fallbackYears)
            ->merge(collect($products)->map(fn (self $product) => $product->paper_year))
            ->map(fn ($year) => self::normalizePaperYear($year))
            ->filter()
            ->unique()
            ->sortByDesc(fn (string $year) => (int) $year)
            ->values()
            ->all();
    }

    public function getTopicCountAttribute(): int
    {
        if ($this->relationLoaded('note') && $this->note && $this->note->relationLoaded('topics')) {
            return $this->note->topics->where('node_type', ProductNoteTopic::TYPE_TOPIC)->count();
        }

        return $this->note?->topics()->where('node_type', ProductNoteTopic::TYPE_TOPIC)->count() ?? 0;
    }

    public function getEstimatedReadMinutesAttribute(): ?int
    {
        if ($this->relationLoaded('note') && $this->note) {
            return $this->note->estimated_read_minutes;
        }

        return $this->note?->estimated_read_minutes;
    }

    protected static function booted(): void
    {
        static::saved(function () {
            CatalogCacheClear::clear();
            app(MenuCacheService::class)->clearMenuCache();
        });
        static::deleted(function () {
            CatalogCacheClear::clear();
            app(MenuCacheService::class)->clearMenuCache();
        });
        static::restored(function () {
            CatalogCacheClear::clear();
            app(MenuCacheService::class)->clearMenuCache();
        });
    }
}
