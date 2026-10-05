<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Keep pre-refresh-token clients working through a controlled migration
     * window. New mobile access tokens always receive their own 20-minute
     * expires_at value from TokenSessionService.
     */
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()->addDays((int) config('auth.legacy_access_token_grace_days', 30))]);
    }

    public function down(): void
    {
        // A security cutoff must not be removed by rolling back application code.
    }
};
