<?php
namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Events\AppointmentResponded;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentReminder;
use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAppointmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';
    public $delay = 5;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handleAppointmentBooked(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;
        $booker = $appointment->booker;
        $targetUser = $appointment->targetUser;

        $date = $appointment->scheduled_at->format('M d, Y H:i');
        $bookerName = trim($booker->name . ' ' . ($booker->last_name ?? ''));
        $targetName = trim($targetUser->name . ' ' . ($targetUser->last_name ?? ''));

        // 1. Notify the target user (exhibitor)
        $notification = $this->notificationService->sendToUser(
            $targetUser,
            '📅 New Meeting Request',
            "{$bookerName} wants to meet you on {$date}",
            [
                'type' => 'appointment',
                'action' => 'booked',
                'appointment_id' => $appointment->id,
                'screen' => '/appointments',
                'target_user_id' => $targetUser->id,
                'booker_name' => $bookerName,
            ]
        );

        // Attach notification to event for broadcasting
        $event->notification = $notification;

        // 2. Notify the booker (visitor) - confirmation
        $this->notificationService->sendToUser(
            $booker,
            '✅ Meeting Request Sent',
            "Your meeting request with {$targetName} has been sent",
            [
                'type' => 'appointment',
                'action' => 'sent',
                'appointment_id' => $appointment->id,
                'screen' => '/appointments',
                'status' => 'pending'
            ]
        );

        // 3. Notify other team members (optional)
        if ($targetUser->company_id) {
            $this->notificationService->sendToCompany(
                $targetUser->company_id,
                '📊 New Meeting Request',
                "{$bookerName} requested to meet {$targetName}",
                [
                    'type' => 'appointment',
                    'action' => 'team_notification',
                    'appointment_id' => $appointment->id,
                    'screen' => '/appointments',
                    'team_member_id' => $targetUser->id
                ],
                'info'
            );
        }
    }

    public function handleAppointmentResponded(AppointmentResponded $event): void
    {
        $appointment = $event->appointment;
        $booker = $appointment->booker;
        $responder = $appointment->targetUser;
        $action = $event->action;

        $statusEmoji = $action === 'confirmed' ? '✅' : '❌';
        $statusText = $action === 'confirmed' ? 'confirmed' : 'declined';
        $responderName = trim($responder->name . ' ' . ($responder->last_name ?? ''));

        $message = $action === 'confirmed'
            ? "{$responderName} confirmed your meeting"
            : "{$responderName} declined your meeting request";

        // Notify the booker
        $notification = $this->notificationService->sendToUser(
            $booker,
            "{$statusEmoji} Meeting {$statusText}",
            $message,
            [
                'type' => 'appointment',
                'action' => $action,
                'appointment_id' => $appointment->id,
                'screen' => '/appointments',
                'responder_name' => $responderName,
            ]
        );

        $event->notification = $notification;

        // If confirmed, send additional details
        if ($action === 'confirmed') {
            $this->sendMeetingConfirmationDetails($appointment);
        }
    }

    public function handleAppointmentCancelled(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;
        $cancelledBy = $event->cancelledBy;

        $otherParty = $cancelledBy->id === $appointment->booker_id
            ? $appointment->targetUser
            : $appointment->booker;

        $cancellerName = trim($cancelledBy->name . ' ' . ($cancelledBy->last_name ?? ''));
        $date = $appointment->scheduled_at->format('M d, Y H:i');

        // Notify the other party
        $notification = $this->notificationService->sendToUser(
            $otherParty,
            '⚠️ Meeting Cancelled',
            "{$cancellerName} cancelled the meeting scheduled for {$date}",
            [
                'type' => 'appointment',
                'action' => 'cancelled',
                'appointment_id' => $appointment->id,
                'screen' => '/appointments',
                'cancelled_by' => $cancellerName
            ]
        );

        $event->notification = $notification;
    }

    public function handleAppointmentReminder(AppointmentReminder $event): void
    {
        $appointment = $event->appointment;
        $date = $appointment->scheduled_at->format('H:i');
        $day = $appointment->scheduled_at->isToday() ? 'Today' : 'Tomorrow';

        // Remind both parties
        $users = [$appointment->booker, $appointment->targetUser];

        foreach ($users as $user) {
            $otherParty = $user->id === $appointment->booker_id
                ? $appointment->targetUser
                : $appointment->booker;

            $otherPartyName = trim($otherParty->name . ' ' . ($otherParty->last_name ?? ''));

            $notification = $this->notificationService->sendToUser(
                $user,
                '⏰ Upcoming Meeting',
                "You have a meeting with {$otherPartyName} {$day} at {$date}",
                [
                    'type' => 'appointment',
                    'action' => 'reminder',
                    'appointment_id' => $appointment->id,
                    'screen' => '/appointments',
                    'scheduled_at' => $appointment->scheduled_at->toISOString()
                ],
                'info'
            );
        }

        $event->notification = $notification;
    }

    protected function sendMeetingConfirmationDetails(Appointment $appointment): void
    {
        $booker = $appointment->booker;
        $targetUser = $appointment->targetUser;

        $date = $appointment->scheduled_at->format('l, M d, Y');
        $time = $appointment->scheduled_at->format('H:i');
        $targetName = trim($targetUser->name . ' ' . ($targetUser->last_name ?? ''));

        $details = [];
        if ($appointment->table_location) {
            $details[] = "📍 Table: {$appointment->table_location}";
        }

        $message = "Meeting with {$targetName} confirmed for {$date} at {$time}";
        if (!empty($details)) {
            $message .= "\n" . implode("\n", $details);
        }

        // Send to booker
        $this->notificationService->sendToUser(
            $booker,
            '📋 Meeting Details',
            $message,
            [
                'type' => 'appointment',
                'action' => 'confirmed_details',
                'appointment_id' => $appointment->id,
                'screen' => '/appointments',
                'table_location' => $appointment->table_location,
                'scheduled_at' => $appointment->scheduled_at->toISOString()
            ]
        );
    }

    public function failed($event, $exception)
    {
        Log::error('Notification failed: ' . $exception->getMessage(), [
            'event' => get_class($event),
            'exception' => $exception
        ]);
    }
}
