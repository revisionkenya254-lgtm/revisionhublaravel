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
        Schema::table('admins', function (Blueprint $table) {
            $table->string('otp_code')->nullable()->after('status');
            $table->string('otp_purpose')->nullable()->after('otp_code');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_purpose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['otp_code', 'otp_purpose', 'otp_expires_at']);
        });
    }
};
