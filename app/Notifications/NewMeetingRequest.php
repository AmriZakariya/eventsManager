<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
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
        // We use our custom channel AND standard database (optional, for admin panel)
        return [AppDatabaseChannel::class, 'database'];
    }

    // Configuration for YOUR custom app_notifications table
    public function toApp($notifiable)
    {
        return [
            'title' => 'New Meeting Request',
            'body'  => "{$this->booker->name} wants to meet with you.",
            'type'  => 'info', // Enum: info, success, alert, promo
            'data'  => [
                'screen' => '/b2b_detail', // The Flutter route name
                'arg'    => $this->appointment->id, // The ID to fetch details
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK' // Standard FCM key
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
