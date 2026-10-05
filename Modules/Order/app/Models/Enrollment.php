<?php

namespace Modules\Order\app\Models;

use App\Models\Admin;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'course_id',
        'has_access',
        'enrolled_by',
        'enrollment_note',
    ];

    public function course(): BelongsTo {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function enrolledBy(): BelongsTo {
        return $this->belongsTo(Admin::class, 'enrolled_by', 'id');
    }
}
