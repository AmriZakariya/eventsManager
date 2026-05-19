<?php

namespace App\Notifications;

use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public User $sender
    ) {
    }

    public function via($notifiable)
    {
        return [AppDatabaseChannel::class, FcmChannel::class];
    }

    private function getTranslatedContent($notifiable): array
    {
        $locale = $notifiable->locale ?? 'en';
        $bodyKey = $this->message->content ? 'new_message_body' : 'new_message_attachment_body';

        return [
            'title' => __('new_message_title', [], $locale),
            'body' => __($bodyKey, [
                'name' => $this->sender->full_name,
            ], $locale),
        ];
    }

    public function toApp($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'title' => $content['title'],
            'body' => $content['body'],
            'type' => 'info',
            'data' => [
                'screen' => '/chat',
                'arg' => (string) $this->sender->id,
                'message_id' => (string) $this->message->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }

    public function toFcm($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'title' => $content['title'],
            'body' => $content['body'],
            'data' => [
                'screen' => '/chat',
                'arg' => (string) $this->sender->id,
                'message_id' => (string) $this->message->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ];
    }
}
