<?php

namespace Modules\Course\app\Models;

use App\Models\Course;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Course\app\Service\CourseCategoryCacheClear;

class CourseCategory extends Model {
    use HasFactory, SoftDeletes;

    public const TYPE_EDUCATION_LEVEL = 'education_level';
    public const TYPE_GRADE = 'grade';
    public const TYPE_FORM = 'form';
    public const TYPE_NATIONAL_EXAM = 'national_exam';
    public const TYPE_PATHWAY = 'pathway';
    public const TYPE_SUBJECT = 'subject';
    public const TYPE_SCHOOL = 'school';
    public const TYPE_COURSE = 'course';
    public const TYPE_RESOURCE = 'resource';
    public const TYPE_LEGACY = 'legacy';

    public const NODE_TYPES = [
        self::TYPE_EDUCATION_LEVEL,
        self::TYPE_GRADE,
        self::TYPE_FORM,
        self::TYPE_NATIONAL_EXAM,
        self::TYPE_PATHWAY,
        self::TYPE_SUBJECT,
        self::TYPE_SCHOOL,
        self::TYPE_COURSE,
        self::TYPE_RESOURCE,
        self::TYPE_LEGACY,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'icon',
        'status',
        'order',
        'parent_id',
        'node_type',
    ];

    protected $casts = [
        'status' => 'boolean',
        'show_at_trending' => 'boolean',
        'order' => 'integer',
    ];

    function scopeActive() {
        return $this->where(['status' => 1]);
    }

    public function scopeOfType($query, string $type) {
        return $query->where('node_type', $type);
    }
    public function getNameAttribute(): ?string {
        if (!empty($this->translation_name)) {
            return $this->translation_name;
        }

        if ($this->relationLoaded('translation')) {
            return $this->getRelation('translation')?->name ?? $this->slug;
        }

        if ($this->relationLoaded('translations')) {
            return $this->getRelation('translations')->first()?->name ?? $this->slug;
        }

        return $this->translation()->first()?->name ?? $this->slug;
    }

    public function getTranslation($code): ?CourseCategoryTranslation {
        return $this->hasOne(CourseCategoryTranslation::class)->where('lang_code', $code)->first();
    }
    public function translation(): ?HasOne {
        return $this->hasOne(CourseCategoryTranslation::class)->where('lang_code', getSessionLanguage());
    }
    public function translations(): ?HasMany {
        return $this->hasMany(CourseCategoryTranslation::class, 'course_category_id');
    }

    public function subCategories() {
        return $this->hasMany(CourseCategory::class, 'parent_id');
    }

    public function parentCategory() {
        return $this->belongsTo(CourseCategory::class, 'parent_id');
    }

    function filters(): HasMany {
        return $this->hasMany(CourseFilter::class, 'course_category_id');
    }

    public function courses() {
        return $this->hasMany(Course::class, 'category_id');
    }

    public function products() {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function hasDependents(): bool {
        return $this->subCategories()->exists()
            || $this->courses()->exists()
            || $this->products()->exists();
    }

    public function allCourses() {
        $subCategories = $this->subCategories()->with('courses')->get();
        $courses = $this->courses()->get();

        foreach ($subCategories as $subCategory) {
            $courses = $courses->merge($subCategory->courses);
        }

        return $courses;
    }

    protected static function booted() {
        static::saved(function ($model) {
            app(\App\Services\Taxonomy\TaxonomyRevisionService::class)->touch();
            CourseCategoryCacheClear::clear();
        });

        static::deleted(function ($model) {
            app(\App\Services\Taxonomy\TaxonomyRevisionService::class)->touch();
            CourseCategoryCacheClear::clear();
        });

        static::restored(function ($model) {
            app(\App\Services\Taxonomy\TaxonomyRevisionService::class)->touch();
            CourseCategoryCacheClear::clear();
        });
    }
}
