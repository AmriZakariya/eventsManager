<?php
namespace App\Events;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentBooked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $notification;

    public function __construct(Appointment $appointment, $notification = null)
    {
        $this->appointment = $appointment;
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->appointment->target_user_id);
    }

    public function broadcastAs()
    {
        return 'appointment.booked';
    }

    public function broadcastWith()
    {
        return [
            'appointment_id' => $this->appointment->id,
            'notification' => $this->notification,
            'timestamp' => now()->toISOString()
        ];
    }
}

class AppointmentResponded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $action;
    public $notification;

    public function __construct(Appointment $appointment, string $action, $notification = null)
    {
        $this->appointment = $appointment;
        $this->action = $action;
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->appointment->booker_id);
    }

    public function broadcastAs()
    {
        return 'appointment.responded';
    }
}

class AppointmentCancelled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $cancelledBy;
    public $notification;

    public function __construct(Appointment $appointment, User $cancelledBy, $notification = null)
    {
        $this->appointment = $appointment;
        $this->cancelledBy = $cancelledBy;
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        $userId = $this->cancelledBy->id === $this->appointment->booker_id
            ? $this->appointment->target_user_id
            : $this->appointment->booker_id;

        return new PrivateChannel('user.' . $userId);
    }
}

class AppointmentReminder implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $appointment;
    public $notification;

    public function __construct(Appointment $appointment, $notification = null)
    {
        $this->appointment = $appointment;
        $this->notification = $notification;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('user.' . $this->appointment->booker_id),
            new PrivateChannel('user.' . $this->appointment->target_user_id),
        ];
    }
}
