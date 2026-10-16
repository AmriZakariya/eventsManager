<?php

namespace App\Console\Commands;

use App\Services\PushMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails PUSH_ALERT_EMAIL when notifications are stuck in the queue or most
 * pushes are failing. Each alert is sent at most once per hour.
 */
class CheckPushHealth extends Command
{
    protected $signature = 'push:check-health';

    protected $description = 'Alert by email when push notifications are stuck or failing';

    private const STUCK_MINUTES = 10;
    private const MIN_ATTEMPTS = 10;
    private const MAX_FAILURE_RATE = 0.5;

    public function handle(): int
    {
        $problems = [];

        $queue = PushMonitor::queue();
        if ($queue['oldest_seconds'] > self::STUCK_MINUTES * 60) {
            $problems['stuck'] = sprintf(
                '%d notification job(s) are waiting; the oldest for %d minutes. The cron job (php artisan schedule:run) may have stopped.',
                $queue['waiting'],
                intdiv($queue['oldest_seconds'], 60),
            );
        }

        $hour = PushMonitor::totals(now()->subHour());
        $attempts = $hour['sent'] + $hour['failed'];
        if ($attempts >= self::MIN_ATTEMPTS && $hour['failed'] / $attempts > self::MAX_FAILURE_RATE) {
            $top = collect(PushMonitor::reasons())->first();
            $problems['failing'] = sprintf(
                '%d of %d pushes failed in the last hour. Most common reason: %s. %s',
                $hour['failed'],
                $attempts,
                $top['error_code'] ?? 'unknown',
                $top['hint'] ?? '',
            );
        }

        if ($problems === []) {
            $this->info('Push notifications look healthy.');

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->warn($problem);
        }

        $to = config('services.push_monitor.alert_email');
        if (! $to) {
            $this->line('PUSH_ALERT_EMAIL is not set; no email sent.');

            return self::SUCCESS;
        }

        // Once per hour per kind of problem, so a long outage is not a flood of emails.
        $fresh = array_filter($problems, fn ($key) => Cache::add("push-alert:{$key}", true, now()->addHour()), ARRAY_FILTER_USE_KEY);
        if ($fresh === []) {
            return self::SUCCESS;
        }

        try {
            Mail::raw(
                implode("\n\n", $fresh) . "\n\nDetails: " . route('platform.push-monitor'),
                fn ($message) => $message->to($to)->subject('[' . config('app.name') . '] Push notification problem'),
            );
        } catch (\Throwable $e) {
            Log::error('Push health alert email failed.', ['message' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }
}
