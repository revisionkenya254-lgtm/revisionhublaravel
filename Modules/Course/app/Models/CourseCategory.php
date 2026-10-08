<?php

namespace Modules\Course\app\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Course\app\Service\CourseCategoryCacheClear;

class CourseCategory extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'icon',
        'status',
        'order',
        'parent_id',
    ];

    function scopeActive() {
        return $this->where(['status' => 1]);
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
            CourseCategoryCacheClear::clear();
        });

        static::deleted(function ($model) {
            CourseCategoryCacheClear::clear();
        });
    }
}
