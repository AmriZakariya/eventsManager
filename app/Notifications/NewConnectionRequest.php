<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel;
use App\Models\User;

class NewConnectionRequest extends Notification
{
    use Queueable;

    public $requester;

    public function __construct(User $requester)
    {
        $this->requester = $requester;
    }

    public function via($notifiable)
    {
        return [AppDatabaseChannel::class, FcmChannel::class];
    }

    private function getTranslatedContent($notifiable): array
    {
        $locale = $notifiable->locale ?? 'en';

        return [
            'title' => __('new_connection_request_title', [], $locale),
            'body' => __('new_connection_request_body', [
                'name' => $this->requester->full_name,
            ], $locale),
        ];
    }

    public function toApp($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'title' => $content['title'],
            'body'  => $content['body'],
            'type'  => 'info',
            'data'  => [
                'screen' => '/networking',
                'arg'    => 'requests_tab',
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
                'screen' => '/networking',
                'arg'    => 'requests_tab',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }
}
