<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Refund\app\Models\InstructorEarningsHold;

class InstructorPayoutService {
    public function releaseHeldAmounts() {
        $holdDays = cache()->get('setting')?->instructor_earning_hold ?? 7; // default 7 days
        $cutoffDate = now()->subDays($holdDays);

        // Get all pending holds older than the hold period
        $holds = InstructorEarningsHold::where('status', 'pending')->where('created_at', '<=', $cutoffDate)->get();

        foreach ($holds as $hold) {
            DB::transaction(function () use ($hold) {
                $hold->instructor->increment('wallet_balance', $hold->amount);
                $hold->update(['status' => 'released']);
            });
        }
    }
}