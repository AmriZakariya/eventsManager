<?php
namespace App\Console\Commands;

use App\Models\Appointment;
use App\Events\AppointmentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Send reminders for upcoming appointments';

    public function handle()
    {
        // Send reminders for appointments in 1 hour
        $appointments = Appointment::whereIn('status', ['confirmed'])
            ->whereBetween('scheduled_at', [
                Carbon::now()->addMinutes(55),
                Carbon::now()->addMinutes(65),
            ])
            ->with(['booker', 'targetUser'])
            ->get();

        foreach ($appointments as $appointment) {
            event(new AppointmentReminder($appointment));
            $this->info("Reminder sent for appointment ID: {$appointment->id}");
        }

        // Send reminders for tomorrow's appointments (at 9 AM)
        if (Carbon::now()->hour === 9) {
            $tomorrowAppointments = Appointment::whereIn('status', ['confirmed'])
                ->whereDate('scheduled_at', Carbon::tomorrow())
                ->with(['booker', 'targetUser'])
                ->get();

            foreach ($tomorrowAppointments as $appointment) {
                event(new AppointmentReminder($appointment));
                $this->info("Tomorrow reminder sent for appointment ID: {$appointment->id}");
            }
        }

        $this->info('Appointment reminders sent successfully!');
    }
}
