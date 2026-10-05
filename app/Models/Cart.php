<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model {
    use HasFactory;
    protected $fillable = ['user_id', 'course_id', 'product_id', 'item_type', 'qty', 'guest_id'];

    // Relationship with the Course model
    public function course() {
        return $this->belongsTo(Course::class);
    }
    public function product() {
        return $this->belongsTo(Product::class);
    }
    public function user() {
        return $this->belongsTo(User::class,'user_id');
    }

    public function purchasable() {
        return $this->item_type === 'product' ? $this->product : $this->course;
    }
    public function getPriceAttribute() {
        return $this->purchasable()?->price ?? 0;
    }
    public function getDiscountPriceAttribute() {
        return $this->purchasable()?->discount;
    }
    public function getTotalPriceAttribute() {
        $discountPrice = $this->discount_price ?? $this->price;
        return $discountPrice * $this->qty;
    }
}
