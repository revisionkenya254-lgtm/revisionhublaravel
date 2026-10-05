<?php

namespace Modules\Assignment\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class AssignmentFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'instructor_id',
        'marks',
        'written_feedback',
        'file_feedback',
        'allow_resubmission',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'allow_resubmission' => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
