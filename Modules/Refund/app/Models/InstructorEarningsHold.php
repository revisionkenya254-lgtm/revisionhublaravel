<?php

namespace Modules\Refund\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Order\app\Models\Order;

class InstructorEarningsHold extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'instructor_id',
        'order_id',
        'amount',
        'status',
    ];
    public function instructor():BelongsTo {
        return $this->belongsTo(User::class, 'instructor_id');
    }
    public function order():BelongsTo {
        return $this->belongsTo(Order::class, 'order_id');
    }

}
