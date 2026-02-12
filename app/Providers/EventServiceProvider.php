<?php
namespace App\Providers;

use App\Events\AppointmentBooked;
use App\Events\AppointmentResponded;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentReminder;
use App\Listeners\SendAppointmentNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AppointmentBooked::class => [
            [SendAppointmentNotification::class, 'handleAppointmentBooked'],
        ],
        AppointmentResponded::class => [
            [SendAppointmentNotification::class, 'handleAppointmentResponded'],
        ],
        AppointmentCancelled::class => [
            [SendAppointmentNotification::class, 'handleAppointmentCancelled'],
        ],
        AppointmentReminder::class => [
            [SendAppointmentNotification::class, 'handleAppointmentReminder'],
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
