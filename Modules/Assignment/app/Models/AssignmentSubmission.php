<?php

namespace Modules\Assignment\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'file_paths',
        'text_submission',
        'link_submission',
        'status',
        'is_late',
    ];

    protected $casts = [
        'file_paths' => 'array',
        'is_late' => 'boolean',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function feedback()
    {
        return $this->hasOne(AssignmentFeedback::class, 'submission_id');
    }
}
