<?php

namespace Modules\BasicPayment\app\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MpesaStkPushService
{
    public function __construct(private readonly object $paymentSetting)
    {
    }

    public function initiate(string $phoneNumber, float $amount, string $accountReference, string $description): array
    {
        $credentials = $this->getCredentials();
        $timestamp = now()->format('YmdHis');
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
        $roundedAmount = (int) ceil($amount);
        $transactionDescription = trim($description) !== '' ? $description : "Order {$accountReference}";

        $payload = [
            'BusinessShortCode' => $credentials['shortcode'],
            'Password' => base64_encode($credentials['shortcode'] . $credentials['passkey'] . $timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $credentials['transaction_type'],
            'Amount' => $roundedAmount,
            'PartyA' => $phoneNumber,
            'PartyB' => $credentials['party_b'],
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => $credentials['callback_url'],
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDescription,
            'Remark' => $transactionDescription,
        ];

        Log::info('M-Pesa STK Push payload', [
            'mode' => $this->paymentSetting->mpesa_stk_push_account_mode ?? 'production',
            'payload' => $this->maskPayload($payload),
        ]);

        try {
            $response = Http::withToken($this->getAccessToken())
                ->acceptJson()
                ->post($this->baseUrl() . '/mpesa/stkpush/v1/processrequest', $payload)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            Log::error('M-Pesa STK Push request failed', [
                'mode' => $this->paymentSetting->mpesa_stk_push_account_mode ?? 'production',
                'payload' => $this->maskPayload($payload),
                'status' => $e->response?->status(),
                'response_body' => $e->response?->body(),
            ]);

            throw $e;
        }

        Log::info('M-Pesa STK Push response', [
            'mode' => $this->paymentSetting->mpesa_stk_push_account_mode ?? 'production',
            'response' => $response,
        ]);

        return [
            'request' => $payload,
            'response' => $response,
        ];
    }

    private function getAccessToken(): string
    {
        $credentials = $this->getCredentials();

        $response = Http::withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
            ->acceptJson()
            ->get($this->baseUrl() . '/oauth/v1/generate?grant_type=client_credentials')
            ->throw()
            ->json();

        if (empty($response['access_token'])) {
            throw new RuntimeException('M-Pesa access token was not returned.');
        }

        return $response['access_token'];
    }

    private function getCredentials(): array
    {
        $isSandbox = ($this->paymentSetting->mpesa_stk_push_account_mode ?? 'production') === 'sandbox';
        $mode = $isSandbox ? 'sandbox' : 'production';
        $config = config("basicpayment.mpesa_stk_push.{$mode}", []);
        $settingsPrefix = $isSandbox ? 'mpesa_stk_sandbox_' : 'mpesa_stk_production_';

        $credentials = [
            'consumer_key' => $this->resolveCredential(
                data_get($config, 'consumer_key', ''),
                $settingsPrefix . 'consumer_key'
            ),
            'consumer_secret' => $this->resolveCredential(
                data_get($config, 'consumer_secret', ''),
                $settingsPrefix . 'consumer_secret'
            ),
            'shortcode' => $this->resolveCredential(
                data_get($config, 'shortcode', ''),
                $settingsPrefix . 'shortcode'
            ),
            'party_b' => $this->resolveCredential(
                data_get($config, 'party_b', ''),
                $settingsPrefix . 'party_b'
            ),
            'passkey' => $this->resolveCredential(
                data_get($config, 'passkey', ''),
                $settingsPrefix . 'passkey'
            ),
            'transaction_type' => $this->resolveCredential(
                data_get($config, 'transaction_type', ''),
                $settingsPrefix . 'transaction_type',
                $isSandbox ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline'
            ),
            'callback_url' => $this->resolveCallbackUrl(),
        ];

        foreach ($credentials as $key => $value) {
            if (blank($value)) {
                throw new RuntimeException("Missing M-Pesa STK Push credential: {$key}");
            }
        }

        return $credentials;
    }

    private function resolveCredential(string $configuredValue, string $settingKey, string $default = ''): string
    {
        if (!blank($configuredValue)) {
            return $configuredValue;
        }

        $storedValue = data_get($this->paymentSetting, $settingKey, $default);

        return blank($storedValue) ? $default : (string) $storedValue;
    }

    private function resolveCallbackUrl(): string
    {
        $storedValue = data_get($this->paymentSetting, 'mpesa_stk_push_callback_url');

        if (!blank($storedValue)) {
            return (string) $storedValue;
        }

        return (string) config('basicpayment.mpesa_stk_push.callback_url', route('mpesa.stkpush.callback'));
    }

    private function baseUrl(): string
    {
        return ($this->paymentSetting->mpesa_stk_push_account_mode ?? 'production') === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = normalizeMpesaPhoneNumber($phoneNumber);

        if ($phoneNumber === null) {
            throw new RuntimeException('Invalid M-Pesa phone number format.');
        }

        return $phoneNumber;
    }

    private function maskPayload(array $payload): array
    {
        if (!empty($payload['Password'])) {
            $payload['Password'] = substr($payload['Password'], 0, 8) . '...[masked]';
        }

        return $payload;
    }
}
