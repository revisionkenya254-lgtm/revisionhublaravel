<?php

namespace Modules\Assignment\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseChapterItem;
use App\Models\User;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'chapter_id',
        'chapter_item_id',
        'instructor_id',
        'title',
        'description',
        'instructions',
        'submission_type',
        'due_date',
        'late_submission_allowed',
        'late_penalty',
        'max_marks',
        'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'late_submission_allowed' => 'boolean',
        'max_marks' => 'decimal:2',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function chapter()
    {
        return $this->belongsTo(CourseChapter::class);
    }

    public function chapterItem()
    {
        return $this->belongsTo(CourseChapterItem::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
