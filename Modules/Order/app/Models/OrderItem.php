<?php

namespace Modules\Order\app\Models;

use App\Models\Course;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Order\Database\factories\OrderItemFactory;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id',
        'qty',
        'price',
        'item_type',
        'product_id',
        'course_id',
        'commission_rate',
    ];

    public function course() {
        return $this->belongsTo(Course::class, 'course_id', 'id')->withTrashed();
    }

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id')->withTrashed();
    }

    public function purchasable() {
        return $this->item_type === 'product' ? $this->product : $this->course;
    }

    function order() : HasOne{
        return $this->hasOne(Order::class, 'id', 'order_id');
    }
}
