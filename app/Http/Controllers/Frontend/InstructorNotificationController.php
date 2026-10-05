<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Notifications\AiDocumentProcessedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InstructorNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('notifications') || ! $request->user()) {
            return response()->json([
                'status' => 'success',
                'notifications' => [],
            ]);
        }

        $notifications = $request->user()
            ->unreadNotifications()
            ->where('type', AiDocumentProcessedNotification::class)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => data_get($notification->data, 'title', __('AI document processed')),
                    'message' => data_get($notification->data, 'message', __('Your AI document is ready.')),
                    'url' => data_get($notification->data, 'url'),
                    'document_id' => data_get($notification->data, 'document_id'),
                    'document_name' => data_get($notification->data, 'document_name'),
                    'document_type' => data_get($notification->data, 'document_type'),
                    'created_at' => optional($notification->created_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all() ?? [];

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications,
        ]);
    }

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
            ->where('type', AiDocumentProcessedNotification::class)
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
