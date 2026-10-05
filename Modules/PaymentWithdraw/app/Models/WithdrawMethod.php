<?php

namespace Modules\PaymentWithdraw\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\BasicPayment\app\Services\PaymentMethodService;
use Modules\PaymentWithdraw\Database\factories\WithrawMethodFactory;

class WithdrawMethod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    protected static function newFactory(): WithrawMethodFactory
    {
        //return WithrawMethodFactory::new();
    }

    public static function enabledPaymentGateways(): Collection
    {
        $paymentService = app(PaymentMethodService::class);
        $withdrawMethods = self::where('status', 'active')->get()
            ->keyBy(fn (self $method) => self::normalizeGatewayName($method->name));

        return collect($paymentService->getActiveGatewaysWithDetails())
            ->map(function (array $gateway, string $key) use ($paymentService, $withdrawMethods) {
                $names = self::gatewayNameVariants($key, $gateway, $paymentService);
                $withdrawMethod = collect($names)
                    ->map(fn ($name) => self::normalizeGatewayName($name))
                    ->map(fn ($name) => $withdrawMethods->get($name))
                    ->filter()
                    ->first();

                return (object) [
                    'id' => $withdrawMethod?->id,
                    'name' => $withdrawMethod?->name ?? ($gateway['name'] ?? $paymentService->getPaymentName($key) ?? Str::headline($key)),
                    'min_amount' => $withdrawMethod?->min_amount ?? 0,
                    'max_amount' => $withdrawMethod?->max_amount ?? 999999999,
                    'description' => $withdrawMethod?->description ?? __('Provide the account details for this payout method.'),
                    'status' => 'active',
                    'gateway_key' => $key,
                ];
            })
            ->values();
    }

    private static function gatewayNameVariants(string $key, array $gateway, PaymentMethodService $paymentService): array
    {
        $displayName = $gateway['name'] ?? null;
        $paymentName = $paymentService->getPaymentName($key);

        return array_filter([
            $key,
            $displayName,
            $paymentName,
            Str::headline($key),
            Str::of($displayName)->replace(' Payment', '')->value(),
            Str::of($paymentName)->replace(' Payment', '')->value(),
        ]);
    }

    private static function normalizeGatewayName(string $name): string
    {
        return Str::of($name)->lower()->replaceMatches('/[^a-z0-9]/', '')->value();
    }
}
