<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $usdCurrency = DB::table('multi_currencies')
            ->where('currency_code', 'USD')
            ->first();

        if (! $usdCurrency) {
            $usdId = DB::table('multi_currencies')->insertGetId([
                'currency_name' => 'USD',
                'country_code' => 'US',
                'currency_code' => 'USD',
                'currency_icon' => '$',
                'is_default' => 'no',
                'currency_rate' => 1,
                'currency_position' => 'before_price',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $usdId = $usdCurrency->id;
        }

        DB::table('basic_payments')->updateOrInsert(
            ['key' => 'paypal_currency_id'],
            ['value' => (string) $usdId, 'updated_at' => $now, 'created_at' => $now]
        );
    }

    public function down(): void
    {
        // Keep gateway currency data on rollback.
    }
};
