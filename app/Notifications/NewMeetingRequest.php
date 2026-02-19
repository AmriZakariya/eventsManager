<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
use App\Channels\FcmChannel;
use App\Models\Appointment;
use App\Models\User;

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
        // Added FcmChannel::class here
        return [AppDatabaseChannel::class, 'database', FcmChannel::class];
    }

    // Configuration for YOUR custom app_notifications table
    public function toApp($notifiable)
    {
        return [
            'title' => 'New Meeting Request',
            'body'  => "{$this->booker->name} wants to meet with you.",
            'type'  => 'info',
            'data'  => [
                'screen' => '/b2b_detail',
                'arg'    => $this->appointment->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }

    // Firebase Cloud Messaging Payload
    public function toFcm($notifiable)
    {
        return [
            'title' => 'New Meeting Request',
            'body'  => "{$this->booker->name} wants to meet with you.",
            'data'  => [
                'screen' => '/b2b_detail',
                'arg'    => (string) $this->appointment->id, // FCM requires strings
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]
        ];
    }

    // Configuration for Standard Laravel notifications table (UUID)
    public function toArray($notifiable)
    {
        return [
            'appointment_id' => $this->appointment->id,
            'message' => "New meeting request from {$this->booker->name}"
        ];
    }
}
