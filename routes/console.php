<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('audit-logs:prune')->weekly();

// Queued notifications (new message, meeting request, ...) are only sent when a
// worker runs. Hostinger has no long-running processes, so the scheduler
// (cron: * * * * * php artisan schedule:run) drains the queue every minute.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping(5);
