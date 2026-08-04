<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class UserNotificationController extends Controller
{
    /**
     * Check if the notifications table exists.
     */
    private function tableExists(): bool
    {
        return Schema::hasTable('user_notifications');
    }

    /**
     * List paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->tableExists()) {
            return response()->json([], 200);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        $notifications = UserNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Count unread notifications.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        if (! $this->tableExists()) {
            return response()->json(['unread_count' => 0]);
        }

        $count = UserNotification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, $id): JsonResponse
    {
        if (! $this->tableExists()) {
            return response()->json(['ok' => true]);
        }

        $notification = UserNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        if (! $this->tableExists()) {
            return response()->json(['ok' => true]);
        }

        UserNotification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}