<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Product;
use App\Models\User;
use Modules\Order\app\Models\OrderItem;

/**
 * The single source of truth for access to paid catalogue content.
 * AI credit purchases intentionally do not pass through this service.
 */
class CatalogAccessService
{
    public function product(User $user, Product $product): array
    {
        if (hasActiveSubscription($user)) {
            return $this->grant('subscription');
        }

        if ((float) $product->effective_price === 0.0) {
            return $this->grant('free');
        }

        $purchased = OrderItem::query()
            ->where('item_type', 'product')
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('buyer_id', $user->id)
                ->where('payment_status', 'paid')
                ->where('status', 'completed'))
            ->exists();

        return $purchased ? $this->grant('purchase') : $this->deny();
    }

    public function course(User $user, Course $course): array
    {
        if (hasActiveSubscription($user)) {
            return $this->grant('subscription');
        }

        $purchased = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('has_access', 1)
            ->exists();

        return $purchased ? $this->grant('purchase') : $this->deny();
    }

    private function grant(string $source): array
    {
        return ['allowed' => true, 'source' => $source, 'payment_required' => false];
    }

    private function deny(): array
    {
        return ['allowed' => false, 'source' => null, 'payment_required' => true];
    }
}
