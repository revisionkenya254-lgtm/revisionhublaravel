<?php

namespace Modules\BasicPayment\database\seeders;

use Illuminate\Database\Seeder;
use Modules\BasicPayment\app\Models\BasicPayment;
use Modules\Currency\app\Models\MultiCurrency;

class BasicPaymentInfoSeeder extends Seeder
{
    public function run(): void
    {
        $basic_payment_info = [
            'paypal_client_id' => 'paypal_client_id',
            'paypal_secret_key' => 'paypal_secret_key',
            'paypal_account_mode' => 'sandbox',
            'paypal_currency_id' => MultiCurrency::where('currency_code', 'USD')->first()?->id,
            'paypal_charge' => 0.00,
            'paypal_status' => 'active',
            'paypal_image' => 'uploads/website-images/paypal.jpg',
            'mpesa_stk_push_account_mode' => 'production',
            'mpesa_stk_push_status' => 'inactive',
            'mpesa_stk_push_charge' => 0.00,
            'mpesa_stk_push_image' => 'uploads/website-images/mpesa.webp',
            'mpesa_stk_push_callback_url' => '',
            'mpesa_stk_sandbox_consumer_key' => '',
            'mpesa_stk_sandbox_consumer_secret' => '',
            'mpesa_stk_sandbox_shortcode' => '',
            'mpesa_stk_sandbox_party_b' => '',
            'mpesa_stk_sandbox_passkey' => '',
            'mpesa_stk_sandbox_transaction_type' => 'CustomerPayBillOnline',
            'mpesa_stk_production_consumer_key' => '',
            'mpesa_stk_production_consumer_secret' => '',
            'mpesa_stk_production_shortcode' => '',
            'mpesa_stk_production_party_b' => '',
            'mpesa_stk_production_passkey' => '',
            'mpesa_stk_production_transaction_type' => 'CustomerBuyGoodsOnline',
        ];

        foreach ($basic_payment_info as $index => $payment_item) {
            $new_item = new BasicPayment();
            $new_item->key = $index;
            $new_item->value = $payment_item;
            $new_item->save();
        }
    }
}
