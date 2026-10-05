<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HeaderNotificationController extends Controller
{
    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        if (! Schema::hasTable('notifications') || ! $request->user()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Notification already handled.'),
            ]);
        }

        $notification = $request->user()
            ->unreadNotifications()
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return response()->json([
                'status' => 'success',
                'message' => __('Notification already handled.'),
            ]);
        }

        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => __('Notification marked as read.'),
        ]);
    }
}
