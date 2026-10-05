<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseChapterLesson extends Model {
    use HasFactory;

    protected $fillable = [
        'title',
        'lecture_number',
        'description',
        'course_id',
        'chapter_id',
        'chapter_item_id',
        'file_path',
        'video_original_name',
        'video_size',
        'video_mime_type',
        'storage',
        'file_type',
        'volume',
        'instructor_id',
        'duration',
        'is_free',
        'include_in_curriculum',
        'qna_enabled',
        'qna_allow_questions',
        'qna_allow_replies',
        'qna_notify_instructor',
        'qna_instructions',
        'status',
    ];

    protected $casts = [
        'video_size' => 'integer',
        'is_free' => 'boolean',
        'include_in_curriculum' => 'boolean',
        'qna_enabled' => 'boolean',
        'qna_allow_questions' => 'boolean',
        'qna_allow_replies' => 'boolean',
        'qna_notify_instructor' => 'boolean',
    ];

    function lessonProgress(): HasOne {
        return $this->hasOne(CourseProgress::class, 'lesson_id', 'id');
    }
    function course(): BelongsTo {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }
    function chapterItem(): BelongsTo {
        return $this->belongsTo(CourseChapterItem::class, 'chapter_item_id', 'id');
    }
    function live(): HasOne {
        return $this->hasOne(CourseLiveClass::class, 'lesson_id', 'id');
    }
    function resources(): HasMany {
        return $this->hasMany(CourseLessonResource::class, 'lesson_id', 'id')->orderBy('order');
    }
    protected static function boot() {
        parent::boot();

        static::deleting(function ($courseChapterLesson) {
            $courseChapterLesson->lessonProgress()->delete();
        });
    }
}
