<?php

namespace Modules\BasicPayment\app\Enums;

use Illuminate\Support\Str;

enum BasicPaymentSupportedCurrencyListEnum
{
    public static function getPaypalSupportedCurrencies(): array
    {
        return ['AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'THB', 'USD'];
    }

    public static function isPaypalSupportedCurrencies($code): bool
    {
        return in_array(Str::upper($code), self::getPaypalSupportedCurrencies(), true);
    }
}
