<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_plan_id')->nullable()->after('google_refresh_token');
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_plan_id');
            $table->timestamp('subscription_expires_at')->nullable()->index()->after('subscription_started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['subscription_expires_at']);
            $table->dropColumn([
                'subscription_plan_id',
                'subscription_started_at',
                'subscription_expires_at',
            ]);
        });
    }
};
