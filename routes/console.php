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

// Push monitoring: alert email when notifications are stuck or failing, and
// keep only 30 days of push logs (PushNotificationLog::prunable()).
Schedule::command('push:check-health')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [\App\Models\PushNotificationLog::class]])->daily();
