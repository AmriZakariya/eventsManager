<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
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
        return [AppDatabaseChannel::class];
    }

    public function toApp($notifiable)
    {
        return [
            'title' => 'Connection Accepted! 🤝',
            'body'  => "You are now connected with {$this->accepter->name}. Start chatting!",
            'type'  => 'success',
            'data'  => [
                'screen' => '/chat', // Direct to chat
                'arg'    => (string)$this->accepter->id, // Pass User ID to open chat
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }
}
