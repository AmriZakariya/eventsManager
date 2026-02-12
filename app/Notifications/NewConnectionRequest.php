<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
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
        return [AppDatabaseChannel::class];
    }

    public function toApp($notifiable)
    {
        return [
            'title' => 'New Connection Request 👥',
            'body'  => "{$this->requester->name} wants to connect with you.",
            'type'  => 'info',
            'data'  => [
                'screen' => '/networking', // Navigate to networking hub
                'arg'    => 'requests_tab', // Optional arg to switch tabs automatically
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }
}
