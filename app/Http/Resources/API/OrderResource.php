<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $invoiceId = data_get($this->resource, 'invoice_id');
        $paymentMethod = data_get($this->resource, 'payment_method');
        $paidAmount = data_get($this->resource, 'paid_amount');
        $payableCurrency = data_get($this->resource, 'payable_currency');
        $paymentStatus = data_get($this->resource, 'payment_status');
        $status = data_get($this->resource, 'status');

        return [
            'invoice_id'     => (string) $invoiceId,
            'payment_method' => (string) $paymentMethod,
            'paid_amount'    => (string) "{$paidAmount} {$payableCurrency}",
            'payment_status' => (string) $paymentStatus,
            'status'         => (string) $status,
        ];
    }
}
