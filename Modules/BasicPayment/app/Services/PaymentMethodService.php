<?php

namespace Modules\BasicPayment\app\Services;

use App\Traits\GetGlobalInformationTrait;
use Illuminate\Support\Facades\Session;
use Modules\BasicPayment\app\Enums\BasicPaymentSupportedCurrencyListEnum;
use Modules\BasicPayment\app\Interfaces\PaymentMethodInterface;

class PaymentMethodService implements PaymentMethodInterface
{
    use GetGlobalInformationTrait;

    const PAYPAL = 'paypal';
    const MPESA_STK_PUSH = 'mpesa_stk_push';

    protected static array $supportedPayments = [
        self::PAYPAL,
        self::MPESA_STK_PUSH,
    ];

    protected static array $multiCurrencySupported = [
        self::PAYPAL,
        self::MPESA_STK_PUSH,
    ];

    public function getSupportedPayments(): array
    {
        return self::$supportedPayments;
    }

    public function getValue($currentGateway): ?string
    {
        return in_array($currentGateway, self::$supportedPayments, true) ? $currentGateway : null;
    }

    public function isSupportedGateway(string $gatewayName): bool
    {
        return in_array(strtolower($gatewayName), self::$supportedPayments, true);
    }

    public function isSupportsMultiCurrency(string $gatewayName): bool
    {
        return in_array(strtolower($gatewayName), self::$multiCurrencySupported, true);
    }

    public function getPaymentName(string $gatewayName): ?string
    {
        return match ($gatewayName) {
            self::PAYPAL => 'PayPal',
            self::MPESA_STK_PUSH => 'Mpesa STK Push',
            default => null,
        };
    }

    public function getGatewayDetails(string $gatewayName): ?object
    {
        $basicPayment = $this->get_basic_payment_info();

        return match ($gatewayName) {
            self::PAYPAL => (object) [
                'paypal_client_id' => $basicPayment->paypal_client_id ?? null,
                'paypal_secret_key' => $basicPayment->paypal_secret_key ?? null,
                'paypal_account_mode' => $basicPayment->paypal_account_mode ?? null,
                'currency_id' => $basicPayment->paypal_currency_id ?? null,
                'charge' => $basicPayment->paypal_charge ?? 0,
                'paypal_status' => $basicPayment->paypal_status ?? null,
                'paypal_image' => $basicPayment->paypal_image ?? null,
            ],
            self::MPESA_STK_PUSH => (object) [
                'mpesa_stk_push_account_mode' => $basicPayment->mpesa_stk_push_account_mode ?? 'production',
                'charge' => $basicPayment->mpesa_stk_push_charge ?? 0,
                'mpesa_stk_push_charge' => $basicPayment->mpesa_stk_push_charge ?? 0,
                'mpesa_stk_push_image' => $basicPayment->mpesa_stk_push_image ?? null,
                'mpesa_stk_push_status' => $basicPayment->mpesa_stk_push_status ?? 'inactive',
                'mpesa_stk_push_callback_url' => $basicPayment->mpesa_stk_push_callback_url ?? null,
                'mpesa_stk_sandbox_consumer_key' => $basicPayment->mpesa_stk_sandbox_consumer_key ?? null,
                'mpesa_stk_sandbox_consumer_secret' => $basicPayment->mpesa_stk_sandbox_consumer_secret ?? null,
                'mpesa_stk_sandbox_shortcode' => $basicPayment->mpesa_stk_sandbox_shortcode ?? null,
                'mpesa_stk_sandbox_party_b' => $basicPayment->mpesa_stk_sandbox_party_b ?? null,
                'mpesa_stk_sandbox_passkey' => $basicPayment->mpesa_stk_sandbox_passkey ?? null,
                'mpesa_stk_sandbox_transaction_type' => $basicPayment->mpesa_stk_sandbox_transaction_type ?? 'CustomerPayBillOnline',
                'mpesa_stk_production_consumer_key' => $basicPayment->mpesa_stk_production_consumer_key ?? null,
                'mpesa_stk_production_consumer_secret' => $basicPayment->mpesa_stk_production_consumer_secret ?? null,
                'mpesa_stk_production_shortcode' => $basicPayment->mpesa_stk_production_shortcode ?? null,
                'mpesa_stk_production_party_b' => $basicPayment->mpesa_stk_production_party_b ?? null,
                'mpesa_stk_production_passkey' => $basicPayment->mpesa_stk_production_passkey ?? null,
                'mpesa_stk_production_transaction_type' => $basicPayment->mpesa_stk_production_transaction_type ?? 'CustomerBuyGoodsOnline',
            ],
            default => (object) false,
        };
    }

    public function isActive(string $gatewayName): bool
    {
        $gatewayDetails = $this->getGatewayDetails($gatewayName);
        $activeStatus = config('basicpayment.default_status.active_text');

        return match ($gatewayName) {
            self::PAYPAL => $gatewayDetails->paypal_status == $activeStatus,
            self::MPESA_STK_PUSH => $gatewayDetails->mpesa_stk_push_status == $activeStatus,
            default => false,
        };
    }

    public function getIcon(string $gatewayName): string
    {
        return match ($gatewayName) {
            self::PAYPAL => 'fa-cc-paypal',
            self::MPESA_STK_PUSH => 'fa-building-columns',
            default => '',
        };
    }

    public function getLogo($gatewayName): ?string
    {
        $basicPayment = $this->get_basic_payment_info();

        return match ($gatewayName) {
            self::PAYPAL => $basicPayment->paypal_image ? asset($basicPayment->paypal_image) : asset('uploads/website-images/paypal.png'),
            self::MPESA_STK_PUSH => $basicPayment->mpesa_stk_push_image ? asset($basicPayment->mpesa_stk_push_image) : asset('uploads/website-images/mpesa.webp'),
            default => null,
        };
    }

    public function getActiveGatewaysWithDetails(): array
    {
        $basicPayment = $this->get_basic_payment_info();
        $activeStatus = config('basicpayment.default_status.active_text');

        $gateways = [
            self::PAYPAL => [
                'name' => 'PayPal',
                'logo' => asset($basicPayment->paypal_image ?? 'uploads/website-images/paypal.png'),
                'status' => ($basicPayment->paypal_status ?? 'inactive') == $activeStatus,
            ],
            self::MPESA_STK_PUSH => [
                'name' => 'Mpesa(StkPush)',
                'logo' => asset($basicPayment->mpesa_stk_push_image ?? 'uploads/website-images/mpesa.webp'),
                'status' => ($basicPayment->mpesa_stk_push_status ?? 'inactive') == $activeStatus,
            ],
        ];

        return array_filter($gateways, fn ($gateway) => $gateway['status'] === true);
    }

    public function isCurrencySupported($gatewayName, $code = null): bool
    {
        if (is_null($code)) {
            $code = getSessionCurrency();
        }

        return match ($gatewayName) {
            self::PAYPAL => BasicPaymentSupportedCurrencyListEnum::isPaypalSupportedCurrencies($code),
            self::MPESA_STK_PUSH => str($code)->upper() == 'KES',
            default => false,
        };
    }

    public function getSupportedCurrencies($gatewayName): array
    {
        return match ($gatewayName) {
            self::PAYPAL => BasicPaymentSupportedCurrencyListEnum::getPaypalSupportedCurrencies(),
            self::MPESA_STK_PUSH => ['KES'],
            default => [],
        };
    }

    public function getGatewayCurrencyCode(string $gatewayName): ?string
    {
        return match ($gatewayName) {
            self::PAYPAL => data_get($this->getCurrencyDetails($this->getGatewayDetails($gatewayName)?->currency_id), 'currency_code'),
            self::MPESA_STK_PUSH => 'KES',
            default => null,
        };
    }

    public function getBladeView(string $gatewayName): ?string
    {
        return match ($gatewayName) {
            self::PAYPAL => 'basicpayment::gateway-actions.paypal',
            self::MPESA_STK_PUSH => 'basicpayment::gateway-actions.mpesa-stk-push',
            default => null,
        };
    }

    public function getPayableAmount($gatewayName, $amount, $currency_code = null): object
    {
        return $this->calculate_payable_charge($amount, $gatewayName, $currency_code);
    }

    public static function removeSessions(): void
    {
        Session::forget([
            'after_success_url',
            'after_failed_url',
            'order',
            'payable_amount',
            'gateway_charge',
            'after_success_gateway',
            'after_success_transaction',
            'subscription_plan_id',
            'payable_with_charge',
            'payable_currency',
            'subscription_plan_id',
            'paid_amount',
            'payment_details',
            'cart',
            'coupon_code',
            'offer_percentage',
            'coupon_discount_amount',
            'gateway_charge_in_usd',
        ]);
    }
}
