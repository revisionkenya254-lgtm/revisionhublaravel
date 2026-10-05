<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLessonResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id', 'name', 'file_path', 'storage', 'mime_type', 'extension',
        'file_size', 'order', 'status', 'uploaded_by_type', 'uploaded_by_id',
    ];

    protected $casts = ['file_size' => 'integer', 'order' => 'integer'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseChapterLesson::class, 'lesson_id');
    }
}
