<?php

namespace App\Notifications;

use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel;
use App\Support\Locale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private array $titles, private array $bodies)
    {
    }

    public function via($notifiable): array
    {
        return [AppDatabaseChannel::class, FcmChannel::class];
    }

    private function content($notifiable): array
    {
        $locale = Locale::normalize($notifiable->locale ?? null);
        return [
            'title' => $this->titles[$locale] ?? $this->titles['en'],
            'body' => $this->bodies[$locale] ?? $this->bodies['en'],
        ];
    }

    public function toApp($notifiable): array
    {
        return [...$this->content($notifiable), 'type' => 'announcement', 'data' => ['screen' => '/notifications']];
    }

    public function toFcm($notifiable): array
    {
        return [...$this->content($notifiable), 'data' => ['screen' => '/notifications']];
    }
}
