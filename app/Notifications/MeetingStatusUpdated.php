<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\AppDatabaseChannel;
use App\Models\Appointment;

class MeetingStatusUpdated extends Notification
{
    use Queueable;

    public $appointment;
    public $status; // 'confirmed' or 'declined'

    public function __construct(Appointment $appointment, $status)
    {
        $this->appointment = $appointment;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return [AppDatabaseChannel::class];
    }

    public function toApp($notifiable)
    {
        $title = match($this->status) {
            'confirmed' => 'Meeting Confirmed! ✅',
            'declined'  => 'Meeting Declined ❌',
            'cancelled' => 'Meeting Cancelled ⚠️',
            default     => 'Meeting Update'
        };

        $type = match($this->status) {
            'confirmed' => 'success',
            'declined'  => 'alert',
            'cancelled' => 'alert',
            default     => 'info'
        };

        return [
            'title' => $title,
            'body'  => "The status of your meeting has changed to {$this->status}.",
            'type'  => $type,
            'data'  => [
                'screen' => '/b2b_detail',
                'arg'    => $this->appointment->id,
            ]
        ];
    }
}
