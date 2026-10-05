<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code')->nullable()->after('is_banned');
            $table->string('otp_purpose')->nullable()->after('otp_code');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_purpose');
            $table->dropColumn('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_code', 'otp_purpose', 'otp_expires_at']);
            $table->string('verification_token')->nullable();
        });
    }
};
