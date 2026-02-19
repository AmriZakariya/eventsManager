<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel; // <-- Add your FCM Channel here
use App\Models\Appointment;

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
        // Tell Laravel to save to DB AND send via FCM Push Notification
        return [AppDatabaseChannel::class, FcmChannel::class];
    }

    // This handles the Database save (already working)
    public function toApp($notifiable)
    {
        $title = match($this->status) {
            'confirmed' => 'Meeting Confirmed! ✅',
            'declined'  => 'Meeting Declined ❌',
            'cancelled' => 'Meeting Cancelled ⚠️',
            default     => 'Meeting Update'
        };

        return [
            'title' => $title,
            'body'  => "The status of your meeting has changed to {$this->status}.",
            'type'  => match($this->status) { 'confirmed' => 'success', default => 'alert' },
            'data'  => [
                'screen' => '/b2b_detail',
                'arg'    => $this->appointment->id,
            ]
        ];
    }

    // NEW: This formats the payload specifically for Firebase Cloud Messaging
    public function toFcm($notifiable)
    {
        $title = match($this->status) {
            'confirmed' => 'Meeting Confirmed! ✅',
            'declined'  => 'Meeting Declined ❌',
            'cancelled' => 'Meeting Cancelled ⚠️',
            default     => 'Meeting Update'
        };

        return [
            'title' => $title,
            'body'  => "The status of your meeting has changed to {$this->status}.",
            // Pass the routing data to Flutter when the user taps the push notification
            'data' => [
                'screen' => '/b2b_detail',
                'arg'    => (string) $this->appointment->id, // FCM data values MUST be strings
            ]
        ];
    }
}
