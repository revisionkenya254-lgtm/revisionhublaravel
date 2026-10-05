<?php

namespace Modules\Order\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Refund\app\Models\InstructorEarningsHold;
use Modules\Refund\app\Models\RefundRequest;

class Order extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'invoice_id',
        'buyer_id',
        'status',
        'has_coupon',
        'coupon_code',
        'coupon_discount_percent',
        'coupon_discount_amount',
        'payment_method',
        'payment_status',
        'payable_amount',
        'gateway_charge',
        'payable_with_charge',
        'paid_amount',
        'conversion_rate',
        'payable_currency',
        'payment_details',
        'transaction_id',
        'commission_rate',
        'order_type',
        'order_details',
    ];
    protected $casts = [
        'order_details' => 'array',
    ];
    public function getOrderDetailsAttribute($value): object | null {
        return json_decode($value);
    }

    public const ORDER_TYPE_BUNDLE = 'bundle';
    public function isBundleOrder(): bool {
        return $this->order_type == self::ORDER_TYPE_BUNDLE;
    }

    public const ORDER_TYPE_GIFT = 'gift';
    public function isGiftOrder(): bool {
        return $this->order_type == self::ORDER_TYPE_GIFT;
    }
    public function scopeGiftOrder($query) {
        return $query->where('order_type', self::ORDER_TYPE_GIFT);
    }

    public const ORDER_TYPE_SUBSCRIPTION = 'subscription';
    public function isSubscriptionOrder(): bool {
        return $this->order_type == self::ORDER_TYPE_SUBSCRIPTION;
    }

    public const ORDER_TYPE_AI_CREDITS = 'ai_credits';
    public function isAiCreditsOrder(): bool {
        return $this->order_type == self::ORDER_TYPE_AI_CREDITS;
    }

    public function user() {
        return $this->belongsTo(User::class, 'buyer_id', 'id')->select('id', 'name', 'email', 'phone', 'address', 'image');
    }

    public function orderItems() {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }
    public function instructorEarningsHolds() {
        return $this->hasMany(InstructorEarningsHold::class, 'order_id', 'id');
    }
    public function refundRequest() {
        return $this->hasOne(RefundRequest::class, 'invoice_id', 'invoice_id');
    }
}
