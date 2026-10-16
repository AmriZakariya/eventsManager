<?php

namespace App\Channels;

use App\Models\PushNotificationLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    public function send($notifiable, Notification $notification)
    {
        $context = [
            'notifiable_id'   => $notifiable->getKey(),
            'notifiable_type' => get_class($notifiable),
            'notification'    => get_class($notification),
        ];

        if (!method_exists($notification, 'toFcm')) {
            Log::warning('FCM: Notification does not implement toFcm(), skipping.', $context);
            return;
        }

        $token = $notifiable->fcm_token;
        if (!$token) {
            Log::warning('FCM: Notifiable has no FCM token, skipping.', $context);
            $this->record($notifiable, $notification, PushNotificationLog::STATUS_SKIPPED, null, 'no_token', 'User has no device token');
            return;
        }

        $context['fcm_token_preview'] = substr($token, 0, 10) . '...';

        $message = $notification->toFcm($notifiable);
        $projectId = env('FIREBASE_PROJECT_ID');

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error('FCM: Failed to generate OAuth access token, aborting send.', $context);
            $this->record($notifiable, $notification, PushNotificationLog::STATUS_FAILED, $message['title'] ?? null, 'oauth_token', 'Could not get a Firebase access token');
            return;
        }

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $message['title'] ?? 'New Notification',
                    'body'  => $message['body'] ?? '',
                ],
                'data' => $message['data'] ?? [],
            ]
        ];

        Log::debug('FCM: Sending push notification.', array_merge($context, [
            'title'      => $payload['message']['notification']['title'],
            'data_keys'  => array_keys($payload['message']['data']),
            'project_id' => $projectId,
        ]));

        $response = Http::timeout(5)->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if ($response->successful()) {
            Log::info('FCM: Push notification sent successfully.', array_merge($context, [
                'message_id' => $response->json('name'),
            ]));
            $this->record($notifiable, $notification, PushNotificationLog::STATUS_SENT, $payload['message']['notification']['title']);
        } else {
            Log::error('FCM: Push notification failed.', array_merge($context, [
                'http_status'  => $response->status(),
                'fcm_error'    => $response->json('error.message'),
                'fcm_status'   => $response->json('error.status'),
                'response_body' => $response->body(),
            ]));

            $error = $response->json('error') ?? [];
            $this->record(
                $notifiable,
                $notification,
                PushNotificationLog::STATUS_FAILED,
                $payload['message']['notification']['title'],
                collect($error['details'] ?? [])->pluck('errorCode')->filter()->first() ?? ($error['status'] ?? null),
                $error['message'] ?? null,
                $response->status(),
            );

            $this->forgetDeadToken($notifiable, $token, $error, $context);
        }
    }

    /**
     * FCM reports a token as dead when the app was uninstalled or the token
     * belongs to another Firebase project. Clear it so we stop retrying it on
     * every notification. Other errors (e.g. APNs credentials) leave it alone.
     */
    private function forgetDeadToken($notifiable, string $token, array $error, array $context): void
    {
        $codes = collect($error['details'] ?? [])->pluck('errorCode')->filter()->all();
        $dead = array_intersect($codes, ['UNREGISTERED', 'SENDER_ID_MISMATCH'])
            || (($error['status'] ?? null) === 'NOT_FOUND');

        // Only clear it if the user has not registered a new token meanwhile.
        if (!$dead || $notifiable->fcm_token !== $token) {
            return;
        }

        $notifiable->forceFill(['fcm_token' => null])->saveQuietly();
        Log::info('FCM: Removed dead token.', array_merge($context, ['error_codes' => $codes]));
    }

    /**
     * Saves the attempt for the admin monitor. Never lets a logging problem
     * (e.g. the table not migrated yet) stop the notification itself.
     */
    private function record(
        $notifiable,
        Notification $notification,
        string $status,
        ?string $title = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?int $httpStatus = null,
    ): void {
        try {
            PushNotificationLog::create([
                'user_id'       => $notifiable instanceof \App\Models\User ? $notifiable->getKey() : null,
                'notification'  => class_basename($notification),
                'title'         => $title !== null ? mb_substr($title, 0, 255) : null,
                'status'        => $status,
                'error_code'    => $errorCode,
                'error_message' => $errorMessage !== null ? mb_substr($errorMessage, 0, 255) : null,
                'http_status'   => $httpStatus,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM: Could not record push log.', ['message' => $e->getMessage()]);
        }
    }

    private function getAccessToken(): ?string
    {
        try {
            if ($accessToken = Cache::get('fcm:oauth_access_token')) {
                return $accessToken;
            }

            $credentialsFilePath = storage_path('app/firebase-credentials.json');

            $client = new \Google_Client();
            $client->setAuthConfig($credentialsFilePath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->fetchAccessTokenWithAssertion();

            $token = $client->getAccessToken();

            if (empty($token['access_token'])) {
                Log::error('FCM: Access token response was empty or malformed.', [
                    'token_keys' => array_keys($token ?? []),
                ]);
                return null;
            }

            Cache::put(
                'fcm:oauth_access_token',
                $token['access_token'],
                now()->addSeconds(max(60, (int) ($token['expires_in'] ?? 3600) - 60))
            );

            Log::debug('FCM: OAuth access token generated successfully.', [
                'expires_in' => $token['expires_in'] ?? 'unknown',
            ]);

            return $token['access_token'];
        } catch (\Exception $e) {
            Log::error('FCM: Exception while generating OAuth access token.', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);
            return null;
        }
    }
}
