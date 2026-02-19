<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
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
            return;
        }

        $context['fcm_token_preview'] = substr($token, 0, 10) . '...';

        $message = $notification->toFcm($notifiable);
        $projectId = env('FIREBASE_PROJECT_ID');

        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            Log::error('FCM: Failed to generate OAuth access token, aborting send.', $context);
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

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

        if ($response->successful()) {
            Log::info('FCM: Push notification sent successfully.', array_merge($context, [
                'message_id' => $response->json('name'),
            ]));
        } else {
            Log::error('FCM: Push notification failed.', array_merge($context, [
                'http_status'  => $response->status(),
                'fcm_error'    => $response->json('error.message'),
                'fcm_status'   => $response->json('error.status'),
                'response_body' => $response->body(),
            ]));
        }
    }

    private function getAccessToken(): ?string
    {
        try {
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
