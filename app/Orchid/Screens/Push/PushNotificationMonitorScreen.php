<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Push;

use App\Models\PushNotificationLog;
use App\Models\EventSetting;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use App\Services\PushMonitor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PushNotificationMonitorScreen extends Screen
{
    /** Oldest waiting job age (seconds) from which the queue counts as stuck. */
    private const STUCK_AFTER = 300;

    public function query(): iterable
    {
        $queue = PushMonitor::queue();
        $day = PushMonitor::totals(now()->subDay());
        $week = PushMonitor::totals(now()->subDays(7));

        $rows = fn (array $items) => collect($items)->map(fn ($item) => new Repository($item));

        return [
            'metrics' => [
                'waiting' => ['value' => number_format($queue['waiting'])],
                'oldest'  => ['value' => $this->formatAge($queue['oldest_seconds'])],
                'failedJobs' => ['value' => number_format($queue['failed'])],
                'sent24'  => ['value' => number_format($day['sent'])],
                'failed24' => ['value' => number_format($day['failed'])],
                'rate7'   => ['value' => $this->successRate($week)],
            ],
            'queueStuck' => $queue['oldest_seconds'] > self::STUCK_AFTER,
            'users' => User::query()->orderBy('name')->limit(5000)->get()->mapWithKeys(fn (User $user) => [$user->id => trim($user->name . ' ' . $user->last_name) . ' — ' . $user->email])->all(),
            'byType'  => $rows(PushMonitor::byType()),
            'reasons' => $rows(PushMonitor::reasons()),
            'recent'  => PushNotificationLog::with('user:id,name,last_name,email,banned_at,ban_reason')
                ->latest('created_at')
                ->limit(50)
                ->get(),
        ];
    }

    public function name(): ?string
    {
        return 'Push Notifications';
    }

    public function description(): ?string
    {
        return 'Queue health and delivery of app push notifications (last 30 days kept).';
    }

    public function permission(): ?iterable
    {
        return ['platform.push-monitor'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Process queue now')
                ->icon('bs.play-circle')
                ->type(Color::PRIMARY)
                ->method('processQueue')
                ->confirm('Send all waiting notifications now? This can take up to 25 seconds.'),

            Button::make('Retry failed jobs')
                ->icon('bs.arrow-clockwise')
                ->method('retryFailed')
                ->confirm('Put all failed jobs back in the queue?'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                CheckBox::make('broadcast.all_users')->title('Send to all users')->value(true),
                \Orchid\Screen\Fields\Select::make('broadcast.user_ids')->title('Selected users')->multiple()->options($this->userOptions())->help('Choose users when sending to a specific audience.'),
                Input::make('broadcast.title.en')->title('English title')->required(),
                TextArea::make('broadcast.body.en')->title('English message')->rows(3)->required(),
                Input::make('broadcast.title.fr')->title('French title')->required(),
                TextArea::make('broadcast.body.fr')->title('French message')->rows(3)->required(),
                Input::make('broadcast.title.ar')->title('Arabic title')->required(),
                TextArea::make('broadcast.body.ar')->title('Arabic message')->rows(3)->required(),
                Button::make('Send notification')->icon('bs.send')->type(Color::PRIMARY)->method('sendBroadcast')->confirm('Send this notification to the selected recipients?'),
            ])->title('Compose notification'),
            Layout::view('admin.push.monitor-alert'),

            Layout::metrics([
                'Waiting in queue' => 'metrics.waiting',
                'Oldest waiting'   => 'metrics.oldest',
                'Failed jobs'      => 'metrics.failedJobs',
                'Sent (24h)'       => 'metrics.sent24',
                'Failed (24h)'     => 'metrics.failed24',
                'Success rate (7d)' => 'metrics.rate7',
            ]),

            Layout::table('byType', [
                TD::make('notification', 'Notification'),
                TD::make('sent', 'Sent')->alignRight(),
                TD::make('failed', 'Failed')->alignRight()
                    ->render(fn ($row) => $this->badge((int) $row->get('failed'), 'danger')),
                TD::make('skipped', 'Skipped (no token)')->alignRight(),
                TD::make('last_at', 'Last attempt')->alignRight(),
            ])->title('By notification type (7 days)'),

            Layout::table('reasons', [
                TD::make('error_code', 'Reason')
                    ->render(fn ($row) => '<code>' . e($row->get('error_code')) . '</code>'),
                TD::make('hint', 'What it means')
                    ->render(fn ($row) => e($row->get('hint') ?: $row->get('error_message'))),
                TD::make('total', 'Count')->alignRight(),
                TD::make('last_at', 'Last seen')->alignRight(),
            ])->title('Failures and skips (7 days)'),

            Layout::table('recent', [
                TD::make('created_at', 'Time')
                    ->render(fn (PushNotificationLog $log) => $log->created_at?->format('Y-m-d H:i:s')),
                TD::make('user', 'User')
                    ->render(function (PushNotificationLog $log) {
                        if (! $log->user) {
                            return '<span class="text-muted">#' . e((string) $log->user_id) . '</span>';
                        }

                        $name = trim($log->user->name . ' ' . $log->user->last_name);

                        return '<a href="' . e(route('platform.systems.users.edit', $log->user_id)) . '">'
                            . e($name ?: $log->user->email) . '</a> ' . $log->user->bannedBadgeHtml();
                    }),
                TD::make('notification', 'Type'),
                TD::make('title', 'Title')->width('260px'),
                TD::make('status', 'Status')
                    ->render(fn (PushNotificationLog $log) => $this->statusBadge($log->status)),
                TD::make('error_code', 'Reason')
                    ->render(fn (PushNotificationLog $log) => $log->error_code ? '<code>' . e($log->error_code) . '</code>' : ''),
            ])->title('Latest 50 attempts'),
        ];
    }

    public function sendBroadcast(): void
    {
        $data = request()->input('broadcast', []);
        $validator = Validator::make($data, [
            'all_users' => ['nullable', 'boolean'], 'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'title.en' => ['required', 'string', 'max:255'], 'body.en' => ['required', 'string', 'max:5000'],
            'title.fr' => ['required', 'string', 'max:255'], 'body.fr' => ['required', 'string', 'max:5000'],
            'title.ar' => ['required', 'string', 'max:255'], 'body.ar' => ['required', 'string', 'max:5000'],
        ]);
        if ($validator->fails()) { Toast::error($validator->errors()->first()); return; }
        $all = filter_var($data['all_users'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$all && empty($data['user_ids'])) { Toast::error('Select at least one user or choose all users.'); return; }
        $users = User::query()->when(!$all, fn ($q) => $q->whereIn('id', $data['user_ids']))->get();
        foreach ($users as $user) $user->notify(new AdminBroadcastNotification($data['title'], $data['body']));
        Toast::success('Notification queued for ' . $users->count() . ' user(s).');
    }

    private function userOptions(): array
    {
        return User::query()->orderBy('name')->limit(5000)->get()->mapWithKeys(fn (User $user) => [$user->id => trim($user->name . ' ' . $user->last_name) . ' — ' . $user->email])->all();
    }

    /**
     * Runs the queue worker inside this request (no shell needed), bounded so
     * the page does not hit PHP's execution time limit.
     */
    public function processQueue(): void
    {
        $before = DB::table('jobs')->count();

        @set_time_limit(60);
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 25,
            '--tries' => 3,
        ]);

        $after = DB::table('jobs')->count();
        $processed = max(0, $before - $after);

        if ($after > 0) {
            Toast::warning("Processed {$processed} job(s). {$after} still waiting: click again to continue.");

            return;
        }

        Toast::success("Processed {$processed} job(s). The queue is empty.");
    }

    public function retryFailed(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        Toast::info('Failed jobs were put back in the queue. Use "Process queue now" or wait for the next minute.');
    }

    private function formatAge(int $seconds): string
    {
        if ($seconds === 0) {
            return '—';
        }

        if ($seconds < 3600) {
            return max(1, intdiv($seconds, 60)) . ' min';
        }

        if ($seconds < 86400) {
            return intdiv($seconds, 3600) . ' h';
        }

        return intdiv($seconds, 86400) . ' days';
    }

    private function successRate(array $totals): string
    {
        $attempts = $totals['sent'] + $totals['failed'];

        return $attempts === 0 ? '—' : round($totals['sent'] / $attempts * 100) . '%';
    }

    private function badge(int $value, string $color): string
    {
        return $value > 0
            ? '<span class="badge bg-' . $color . '">' . $value . '</span>'
            : (string) $value;
    }

    private function statusBadge(string $status): string
    {
        $color = match ($status) {
            PushNotificationLog::STATUS_SENT => 'success',
            PushNotificationLog::STATUS_FAILED => 'danger',
            default => 'secondary',
        };

        return '<span class="badge bg-' . $color . '">' . e($status) . '</span>';
    }
}
