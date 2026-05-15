<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel;
use App\Models\User;

class ConnectionAccepted extends Notification
{
    use Queueable;

    public $accepter;

    public function __construct(User $accepter)
    {
        $this->accepter = $accepter;
    }

    public function via($notifiable)
    {
        return [AppDatabaseChannel::class, FcmChannel::class];
    }

    private function getTranslatedContent($notifiable): array
    {
        $locale = $notifiable->locale ?? 'en';

        return [
            'title' => __('connection_accepted_title', [], $locale),
            'body' => __('connection_accepted_body', [
                'name' => $this->accepter->full_name,
            ], $locale),
        ];
    }

    public function toApp($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'title' => $content['title'],
            'body'  => $content['body'],
            'type'  => 'success',
            'data'  => [
                'screen' => '/chat',
                'arg'    => (string)$this->accepter->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }

    // Firebase Cloud Messaging Payload
    public function toFcm($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'title' => $content['title'],
            'body'  => $content['body'],
            'data'  => [
                'screen' => '/chat',
                'arg'    => (string)$this->accepter->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }
}
