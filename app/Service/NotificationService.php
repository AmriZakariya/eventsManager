<?php
namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Send notification to a specific user
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], string $type = 'alert'): ?AppNotification
    {
        // Save to database
        $notification = $this->storeInDatabase($user, $title, $body, $data, $type);

        // Send push if FCM token exists
        if ($user->fcm_token) {
            $this->sendPushNotification($user->fcm_token, $title, $body, $data);
        }

        return $notification;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(iterable $users, string $title, string $body, array $data = [], string $type = 'alert'): array
    {
        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = $this->sendToUser($user, $title, $body, $data, $type);
        }

        return $notifications;
    }

    /**
     * Send notification to a company's team members
     */
    public function sendToCompany(int $companyId, string $title, string $body, array $data = [], string $type = 'alert'): array
    {
        $users = User::where('company_id', $companyId)
            ->whereNotNull('fcm_token')
            ->get();

        return $this->sendToUsers($users, $title, $body, $data, $type);
    }

    /**
     * Send notification to all exhibitors
     */
    public function sendToAllExhibitors(string $title, string $body, array $data = [], string $type = 'alert'): array
    {
        $users = User::whereNotNull('company_id')
            ->whereNotNull('fcm_token')
            ->get();

        return $this->sendToUsers($users, $title, $body, $data, $type);
    }

    /**
     * Store notification in database
     */
    protected function storeInDatabase(User $user, string $title, string $body, array $data = [], string $type = 'alert'): AppNotification
    {
        return AppNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
            'is_read' => false
        ]);
    }

    /**
     * Send push notification via FCM HTTP v1 API
     */
    protected function sendPushNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        // Skip if no FCM server key configured
        if (!config('services.fcm.server_key')) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]),
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // Check if token is invalid
                if (isset($result['results'][0]['error']) &&
                    in_array($result['results'][0]['error'], ['NotRegistered', 'InvalidRegistration'])) {
                    User::where('fcm_token', $fcmToken)->update(['fcm_token' => null]);
                }

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FCM Push Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(AppNotification $notification): bool
    {
        return $notification->update(['is_read' => true]);
    }

    /**
     * Mark all user notifications as read
     */
    public function markAllAsRead(User $user): bool
    {
        return AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(User $user): int
    {
        return AppNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Delete old notifications
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        return AppNotification::where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
