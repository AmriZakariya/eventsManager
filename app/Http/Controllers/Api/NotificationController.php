<?php

namespace App\Http\Controllers\Api;

use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppNotification;

class NotificationController extends Controller
{
    /**
     * POST /api/notifications/device-token
     */
    public function saveDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();

        // A phone has one token. If another account logged in on this phone
        // before, remove it there so that account's pushes stop arriving here.
        User::where('fcm_token', $request->token)
            ->whereKeyNot($user->getKey())
            ->update(['fcm_token' => null]);

        // Update the user's device token.
        // Note: Make sure you have an 'fcm_token' or 'device_token' column in your users table.
        $user->update(['fcm_token' => $request->token]);

        return response()->json(['message' => 'Device token saved successfully']);
    }

    /**
     * DELETE /api/notifications/device-token
     * The user turned push notifications off in the app: stop sending to this
     * device. If a token is given, only clear it when it is still the saved
     * one, so a newer device's token is not removed.
     */
    public function deleteDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'nullable|string',
        ]);

        $user = $request->user();

        if (! $request->filled('token') || $user->fcm_token === $request->token) {
            $user->forceFill(['fcm_token' => null])->save();
        }

        return response()->json(['message' => 'Device token removed']);
    }

    /**
     * GET /api/notifications
     */
    public function index(Request $request)
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->withoutBannedSenders()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * GET /api/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->withoutBannedSenders()
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = AppNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * PUT /api/notifications/read-all
     */
    public function markAllAsRead(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All marked as read']);
    }
}
