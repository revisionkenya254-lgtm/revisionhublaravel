<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('basic_payments')->whereIn('key', [
            'stripe_key',
            'stripe_secret',
            'stripe_currency_id',
            'stripe_status',
            'stripe_charge',
            'stripe_image',
            'bank_information',
            'bank_status',
            'bank_image',
            'bank_charge',
            'bank_currency_id',
            'offline_status',
            'offline_image',
            'offline_charge',
            'offline_currency_id',
            'braintree_account_mode',
            'braintree_merchant_id',
            'braintree_public_key',
            'braintree_private_key',
            'braintree_charge',
            'braintree_image',
            'braintree_currency_id',
            'braintree_currency',
            'braintree_status',
            'mpesa_account_mode',
            'mpesa_market',
            'mpesa_country',
            'mpesa_origin',
            'mpesa_shortcode',
            'mpesa_api_key',
            'mpesa_public_key',
            'mpesa_charge',
            'mpesa_image',
            'mpesa_status',
            'two_checkout_account_mode',
            'two_checkout_sellerId',
            'two_checkout_secretKey',
            'two_checkout_buyLinkSecretWord',
            'two_checkout_charge',
            'two_checkout_image',
            'two_checkout_status',
        ])->delete();

        Schema::dropIfExists('bkash_p_g_models');
        Schema::dropIfExists('crypto_p_g');
        Schema::dropIfExists('mercadopagopg');
    }

    public function down(): void
    {
        if (!Schema::hasTable('bkash_p_g_models')) {
            Schema::create('bkash_p_g_models', function (Blueprint $table) {
                $table->id();
                $table->string('key')->nullable();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crypto_p_g')) {
            Schema::create('crypto_p_g', function (Blueprint $table) {
                $table->id();
                $table->string('key')->nullable();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mercadopagopg')) {
            Schema::create('mercadopagopg', function (Blueprint $table) {
                $table->id();
                $table->string('key')->nullable();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }
    }
};
