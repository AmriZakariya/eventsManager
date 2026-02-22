<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;

class NewMeetingRequest extends Notification
{
    use Queueable;

    public $appointment;
    public $booker;

    public function __construct(Appointment $appointment, User $booker)
    {
        $this->appointment = $appointment;
        $this->booker = $booker;
    }

    public function via($notifiable)
    {
        // Use custom DB channel, standard DB channel (optional), and FCM Push channel
        return [AppDatabaseChannel::class, 'database', FcmChannel::class];
    }

    /**
     * Helper to generate translated content based on the target user's locale
     */
    private function getTranslatedContent($notifiable)
    {
        // Get the target user's language, default to 'en'
        $locale = $notifiable->locale ?? 'en';

        // Translate the date format based on the locale
        $date = Carbon::parse($this->appointment->scheduled_at)
            ->locale($locale)
            ->translatedFormat('M j \a\t g:i A');

        $companyName = $this->booker->company ? $this->booker->company->name : '';

        // Fetch translations explicitly using the $locale parameter
        $title = __('notifications.new_meeting_title', [], $locale);
        $body  = __('notifications.new_meeting_body', [
            'name'    => $this->booker->name,
            'company' => $companyName,
            'date'    => $date
        ], $locale);

        return ['title' => $title, 'body' => $body];
    }

    // Configuration for YOUR custom app_notifications table
    public function toApp($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'title' => $content['title'],
            'body'  => $content['body'],
            'type'  => 'info',
            'data'  => [
                'screen'       => '/b2b_detail',
                'arg'          => $this->appointment->id,
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
                'screen'       => '/b2b_detail',
                'arg'          => (string) $this->appointment->id, // FCM requires strings
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }

    // Configuration for Standard Laravel notifications table
    public function toArray($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'appointment_id' => $this->appointment->id,
            'message'        => $content['body']
        ];
    }
}
