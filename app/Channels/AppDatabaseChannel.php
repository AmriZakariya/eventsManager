<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\AppNotification;

class AppDatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        // 1. Get the data formatting method from the Notification class
        if (!method_exists($notification, 'toApp')) {
            throw new \Exception('Notification class must have toApp method');
        }

        $data = $notification->toApp($notifiable);

        // 2. Create the record in your custom table
        return AppNotification::create([
            'user_id' => $notifiable->id,
            'title'   => $data['title'],
            'body'    => $data['body'],
            'type'    => $data['type'] ?? 'info',
            'data'    => $data['data'] ?? [],
            'is_read' => false,
        ]);
    }
}
