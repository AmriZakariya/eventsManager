<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel;
use App\Models\Appointment;
use Carbon\Carbon;

class MeetingStatusUpdated extends Notification
{
    use Queueable;

    public $appointment;
    public $status;

    public function __construct(Appointment $appointment, $status)
    {
        $this->appointment = $appointment;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        // Save to DB AND send via FCM Push Notification
        return [AppDatabaseChannel::class, FcmChannel::class];
    }

    /**
     * Helper to generate translated content based on the target user's locale
     */
    private function getTranslatedContent($notifiable)
    {
        $locale = $notifiable->locale ?? 'en';
        $dateFormat = __('notification_datetime_format', [], $locale);
        if ($dateFormat === 'notification_datetime_format') {
            $dateFormat = 'M j \a\t g:i A';
        }

        $date = Carbon::parse($this->appointment->scheduled_at)
            ->locale($locale)
            ->translatedFormat($dateFormat);

        // Dynamic keys without the 'notifications.' prefix
        $titleKey = "meeting_{$this->status}_title";
        $bodyKey  = "meeting_{$this->status}_body";

        $title = __($titleKey, [], $locale);
        $body  = __($bodyKey, ['date' => $date], $locale);

        // Fallbacks in case the specific status translation doesn't exist
        if ($title === $titleKey || $body === $bodyKey) {
            $statusKey = "status_{$this->status}";
            $translatedStatus = __($statusKey, [], $locale);

            if ($translatedStatus === $statusKey) {
                $translatedStatus = $this->status;
            }

            $title = __('meeting_update_title', [], $locale);
            if ($title === 'meeting_update_title') {
                $title = 'Meeting Update';
            }

            $body = __('meeting_update_body', [
                'status' => $translatedStatus,
                'date' => $date,
            ], $locale);

            if ($body === 'meeting_update_body') {
                $body = "Your meeting on {$date} is now {$translatedStatus}.";
            }
        }

        $type = match($this->status) {
            'confirmed' => 'success',
            default => 'alert'
        };

        return [
            'title' => $title,
            'body'  => $body,
            'type'  => $type,
        ];
    }

    // Configuration for YOUR custom app_notifications table
    public function toApp($notifiable)
    {
        $content = $this->getTranslatedContent($notifiable);

        return [
            'title' => $content['title'],
            'body'  => $content['body'],
            'type'  => $content['type'],
            'data'  => [
                'screen' => '/b2b_detail',
                'arg'    => $this->appointment->id,
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
            'data' => [
                'screen' => '/b2b_detail',
                'arg'    => (string) $this->appointment->id, // FCM data values MUST be strings
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]
        ];
    }
}
