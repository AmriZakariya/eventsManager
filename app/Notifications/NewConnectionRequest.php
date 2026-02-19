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
        // Added FcmChannel::class here
        return [AppDatabaseChannel::class, FcmChannel::class];
    }

    public function toApp($notifiable)
    {
        return [
            'title' => 'New Connection Request 👥',
            'body'  => "{$this->requester->name} wants to connect with you.",
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
        return [
            'title' => 'New Connection Request 👥',
            'body'  => "{$this->requester->name} wants to connect with you.",
            'data'  => [
                'screen' => '/networking',
                'arg'    => 'requests_tab',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }
}
