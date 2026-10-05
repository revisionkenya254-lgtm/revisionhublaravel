<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Assignment\app\Models\Assignment;
use Modules\Course\app\Models\CourseCategory;
use Modules\Course\app\Service\CourseCacheClear;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    public function scopeActive()
    {
        return $this->where(['is_approved' => 'approved', 'status' => 'active']);
    }
    public function getFavoriteByClientAttribute()
    {
        if (auth()->guard('web')->check()) {
            return $this->relationLoaded('favoriteBy') ? in_array(userAuth()->id, $this->favoriteBy->pluck('id')->toArray()) : false;
        }

        return false;
    }

    public function favoriteBy()
    {
        return $this->belongsToMany(User::class, 'favorite_course_user')->withTimestamps();
    }

    public function partnerInstructors(): HasMany
    {
        return $this->hasMany(CoursePartnerInstructor::class, 'course_id', 'id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(CourseSelectedLevel::class, 'course_id', 'id');
    }
    public function languages(): HasMany
    {
        return $this->hasMany(CourseSelectedLanguage::class, 'course_id', 'id');
    }

    public function filtersOptions(): HasMany
    {
        return $this->hasMany(CourseSelectedFilterOption::class, 'course_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id', 'id')->withDefault();
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id', 'id')->withDefault();
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(CourseChapter::class, 'course_id', 'id');
    }
    public function chapterItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            CourseChapterItem::class,
            CourseChapter::class,
            'course_id',
            'chapter_id',
            'id',
            'id'
        );
    }
    public function orders()
    {
        return $this->hasManyThrough(
            Order::class,
            OrderItem::class,
            'course_id',
            'id',
            'id',
            'order_id'
        );
    }
    public function progresses(): HasMany
    {
        return $this->hasMany(CourseProgress::class, 'course_id', 'id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CourseReview::class, 'course_id', 'id');
    }
    public function lessons(): HasMany
    {
        return $this->hasMany(CourseChapterLesson::class, 'course_id', 'id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id', 'id');
    }
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'course_id', 'id');
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'course_id', 'id');
    }
    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($course) {
            // Delete related chapters
            $course->chapters()->each(function ($chapter) {
                $chapter->delete();
            });

            // Delete related partner instructors
            $course->partnerInstructors()->each(function ($instructor) {
                $instructor->delete();
            });

            // Delete related levels
            $course->levels()->each(function ($level) {
                $level->delete();
            });

            // Delete related languages
            $course->languages()->each(function ($language) {
                $language->delete();
            });

            // Delete related filter options
            $course->filtersOptions()->each(function ($filterOption) {
                $filterOption->delete();
            });

            // Delete related reviews
            $course->reviews()->each(function ($review) {
                $review->delete();
            });

            // Delete related assignments
            $course->assignments()->each(function ($assignment) {
                $assignment->delete();
            });
        });
    }
    protected static function booted()
    {
        static::saved(function ($model) {
            CourseCacheClear::clear();
        });

        static::deleted(function ($model) {
            CourseCacheClear::clear();
        });
    }
}
