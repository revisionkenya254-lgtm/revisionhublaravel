<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MultiCurrencyResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $currencyIcon = data_get($this->resource, 'currency_icon');
        $currencyName = data_get($this->resource, 'currency_name');
        $currencyCode = data_get($this->resource, 'currency_code');
        $countryCode = data_get($this->resource, 'country_code');
        $currencyRate = data_get($this->resource, 'currency_rate');
        $currencyPosition = data_get($this->resource, 'currency_position');
        $isDefault = data_get($this->resource, 'is_default');
        $status = data_get($this->resource, 'status');

        return [
            'currency_icon'     => (bool) $currencyIcon,
            'currency_name'     => (string) $currencyName,
            'currency_code'     => (string) $currencyCode,
            'country_code'      => (string) $countryCode,
            'currency_rate'     => (float) $currencyRate,
            'currency_position' => (string) $currencyPosition,
            'is_default'        => (string) $isDefault,
            'status'            => (string) $status,
        ];
    }
}
