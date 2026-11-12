<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_started_at')->nullable()->after('subscription_expires_at');
            $table->timestamp('trial_expires_at')->nullable()->index()->after('trial_started_at');
            $table->timestamp('trial_claimed_at')->nullable()->after('trial_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['trial_expires_at']);
            $table->dropColumn([
                'trial_started_at',
                'trial_expires_at',
                'trial_claimed_at',
            ]);
        });
    }
};
