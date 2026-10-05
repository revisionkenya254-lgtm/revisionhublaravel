<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailsResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $subTotal = $this->calculateSubTotal();
        $discount = data_get($this->resource, 'coupon_discount_amount', 0);
        $gatewayCharge = data_get($this->resource, 'gateway_charge', 0);
        $conversionRate = data_get($this->resource, 'conversion_rate', 1);
        $payableCurrency = data_get($this->resource, 'payable_currency');
        $paidAmount = data_get($this->resource, 'paid_amount');
        $invoiceId = data_get($this->resource, 'invoice_id');
        $paymentMethod = data_get($this->resource, 'payment_method');
        $paymentStatus = data_get($this->resource, 'payment_status');
        $status = data_get($this->resource, 'status');
        $createdAt = data_get($this->resource, 'created_at');
        $user = data_get($this->resource, 'user');
        $orderItems = collect(data_get($this->resource, 'orderItems', []));
        $total = ($subTotal - $discount + $gatewayCharge) * $conversionRate;
        $sub_total_conversion = $subTotal * $conversionRate;

        $data = [
            'invoice_id'     => (string) $invoiceId,
            'payment_method' => (string) $paymentMethod,
            'paid_amount'    => $this->formatCurrency($paidAmount, $payableCurrency),
            'payment_status' => (string) $paymentStatus,
            'status'         => (string) $status,
            'created_at'     => (string) formatDate($createdAt),
            'billed_to'      => [
                'name'    => (string) data_get($user, 'name'),
                'email'   => (string) data_get($user, 'email'),
                'address' => (string) data_get($user, 'address'),
                'phone'   => (string) data_get($user, 'phone'),
            ],
            'order_items'    => $this->transformOrderItems($orderItems, $conversionRate, $payableCurrency),
            'summary'        => [
                'sub_total'      => $this->formatCurrency($sub_total_conversion, $payableCurrency),
                'discount'       => $this->formatCurrency($discount, $payableCurrency),
                'gateway_charge' => $this->formatCurrency($gatewayCharge, $payableCurrency),
                'total'          => $this->formatCurrency($total, $payableCurrency),
            ],
        ];
        return $data;
    }
    /**
     * Calculate the subtotal.
     */
    private function calculateSubTotal(): float {
        return collect(data_get($this->resource, 'orderItems', []))->sum('price');
    }
    /**
     * Format currency with value and currency code.
     */
    private function formatCurrency($value, $currency) {
        return (string) "{$value} {$currency}";
    }
    /**
     * Transform order items into an array.
     */
    private function transformOrderItems($orderItems, $conversionRate, $payableCurrency) {
        return $orderItems->map(function ($item) use ($conversionRate, $payableCurrency) {
            $price = data_get($item, 'price', 0) * $conversionRate;
            return [
                'price'        => $this->formatCurrency($price, $payableCurrency),
                'course_title' => (string) data_get($item, 'course.title', 'N/A'),
                'instructor'   => (string) data_get($item, 'course.instructor.name', 'N/A'),
            ];
        })->toArray();
    }
}
