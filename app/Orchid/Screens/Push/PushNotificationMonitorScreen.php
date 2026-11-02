<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Push;

use App\Models\AdminBroadcast;
use App\Models\PushNotificationLog;
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
            'queue' => $queue,
            'audiences' => $this->audienceCounts(),
            'broadcasts' => $this->recentBroadcasts(),
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
        return 'Send announcements to the app and follow their delivery.';
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
            Layout::tabs([
                'Compose' => [
                    Layout::view('admin.push.compose'),
                ],
                'Sent broadcasts' => [
                    Layout::table('broadcasts', [
                        TD::make('created_at', 'Sent')
                            ->render(fn (AdminBroadcast $b) => '<span title="' . e($b->created_at?->format('M j, Y H:i')) . '">'
                                . e($b->created_at?->diffForHumans()) . '</span>'),
                        TD::make('title', 'Notification')
                            ->render(fn (AdminBroadcast $b) => '<div class="fw-semibold">' . e($b->titles['en'] ?? '') . '</div>'
                                . '<div class="text-muted small text-truncate" style="max-width:360px;">' . e($b->bodies['en'] ?? '') . '</div>'
                                . '<div class="small mt-1">' . collect(array_keys($b->titles))->map(fn ($l) => '<span class="badge bg-light text-dark border me-1">' . e(strtoupper($l)) . '</span>')->implode('') . '</div>'),
                        TD::make('audience', 'Audience')
                            ->render(fn (AdminBroadcast $b) => e($b->audienceLabel())
                                . '<div class="text-muted small">' . number_format($b->recipients_count) . ' recipient(s)</div>'),
                        TD::make('delivered', 'Delivered')->alignRight()
                            ->render(fn (AdminBroadcast $b) => $this->badge((int) $b->delivered_count, 'success')),
                        TD::make('failed', 'Failed')->alignRight()
                            ->render(fn (AdminBroadcast $b) => $this->badge((int) $b->failed_count, 'danger')),
                        TD::make('skipped', 'No device')->alignRight()
                            ->render(fn (AdminBroadcast $b) => '<span title="Notifications off, or never opened the app on a phone">'
                                . number_format((int) $b->skipped_count) . '</span>'),
                        TD::make('sender', 'By')
                            ->render(fn (AdminBroadcast $b) => e($b->sender ? trim($b->sender->name . ' ' . $b->sender->last_name) : '—')),
                    ])->title('Latest 50 broadcasts · delivery numbers fill in within a minute, details kept 30 days'),
                ],
                'Delivery monitor' => [
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
                            ->render(fn (PushNotificationLog $log) => '<span title="' . e($log->created_at?->format('Y-m-d H:i:s')) . '">'
                                . e($log->created_at?->diffForHumans()) . '</span>'),
                        TD::make('user', 'User')
                            ->render(function (PushNotificationLog $log) {
                                if (! $log->user) {
                                    return '<span class="text-muted">#' . e((string) $log->user_id) . '</span>';
                                }

                                $name = trim($log->user->name . ' ' . $log->user->last_name);

                                return '<a href="' . e(route('platform.systems.users.view', $log->user_id)) . '">'
                                    . e($name ?: $log->user->email) . '</a> ' . $log->user->bannedBadgeHtml();
                            }),
                        TD::make('notification', 'Type'),
                        TD::make('title', 'Title')->width('260px'),
                        TD::make('status', 'Status')
                            ->render(fn (PushNotificationLog $log) => $this->statusBadge($log->status)),
                        TD::make('error_code', 'Reason')
                            ->render(fn (PushNotificationLog $log) => $log->error_code ? '<code>' . e($log->error_code) . '</code>' : ''),
                    ])->title('Latest 50 attempts'),
                ],
            ]),
        ];
    }

    public function sendBroadcast(): void
    {
        if (! $content = $this->validatedBroadcast()) {
            return;
        }

        [$audience, $userIds, $titles, $bodies] = $content;

        $broadcast = AdminBroadcast::create([
            'sent_by'          => request()->user()->id,
            'audience'         => $audience,
            'user_ids'         => $audience === AdminBroadcast::AUDIENCE_SPECIFIC ? $userIds : null,
            'titles'           => $titles,
            'bodies'           => $bodies,
            'recipients_count' => 0,
        ]);

        $count = 0;
        AdminBroadcast::audienceQuery($audience, $userIds)
            ->chunkById(200, function ($users) use ($titles, $bodies, $broadcast, &$count) {
                foreach ($users as $user) {
                    $user->notify(new AdminBroadcastNotification($titles, $bodies, $broadcast->id));
                    $count++;
                }
            });

        $broadcast->update(['recipients_count' => $count]);

        Toast::success("Notification queued for {$count} user(s). Follow its delivery in \"Sent broadcasts\".");
    }

    /** Sends the composed notification only to the admin's own account, right away. */
    public function sendTest(): void
    {
        if (! $content = $this->validatedBroadcast(requireAudience: false)) {
            return;
        }

        [, , $titles, $bodies] = $content;
        $me = request()->user();

        if (blank($me->fcm_token)) {
            session()->flashInput(request()->all());
            Toast::warning('Your account has no phone registered. Log into the app with this account (notifications allowed), then try again.');

            return;
        }

        $me->notifyNow(new AdminBroadcastNotification($titles, $bodies));
        session()->flashInput(request()->all());

        Toast::info('Test sent to your phone. Your text is kept below.');
    }

    /**
     * Validates the compose form. On error, keeps the typed text and shows why.
     *
     * @return array{0:string,1:array,2:array,3:array}|null
     */
    private function validatedBroadcast(bool $requireAudience = true): ?array
    {
        $data = request()->input('broadcast', []);

        $validator = Validator::make($data, [
            'audience'   => ['required', 'in:' . implode(',', array_keys(AdminBroadcast::AUDIENCES))],
            'user_ids'   => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'title.en'   => ['required', 'string', 'max:255'],
            'body.en'    => ['required', 'string', 'max:2000'],
            'title.fr'   => ['nullable', 'string', 'max:255', 'required_with:body.fr'],
            'body.fr'    => ['nullable', 'string', 'max:2000', 'required_with:title.fr'],
            'title.ar'   => ['nullable', 'string', 'max:255', 'required_with:body.ar'],
            'body.ar'    => ['nullable', 'string', 'max:2000', 'required_with:title.ar'],
        ], [
            'title.en.required'      => 'Add an English title.',
            'body.en.required'       => 'Add an English message.',
            'title.fr.required_with' => 'The French translation needs a title too.',
            'body.fr.required_with'  => 'The French translation needs a message too.',
            'title.ar.required_with' => 'The Arabic translation needs a title too.',
            'body.ar.required_with'  => 'The Arabic translation needs a message too.',
        ]);

        $error = $validator->fails() ? $validator->errors()->first() : null;
        $audience = $data['audience'] ?? AdminBroadcast::AUDIENCE_ALL;
        $userIds = array_map('intval', $data['user_ids'] ?? []);

        if (! $error && $requireAudience && $audience === AdminBroadcast::AUDIENCE_SPECIFIC && $userIds === []) {
            $error = 'Choose at least one user, or pick another audience.';
        }

        if ($error) {
            session()->flashInput(request()->all());
            Toast::error($error);

            return null;
        }

        $titles = ['en' => trim($data['title']['en'])];
        $bodies = ['en' => trim($data['body']['en'])];
        foreach (['fr', 'ar'] as $locale) {
            if (filled($data['title'][$locale] ?? null)) {
                $titles[$locale] = trim($data['title'][$locale]);
                $bodies[$locale] = trim($data['body'][$locale]);
            }
        }

        return [$audience, $userIds, $titles, $bodies];
    }

    /** Recipients per audience, and how many of them have a phone registered. */
    private function audienceCounts(): array
    {
        $counts = [];
        foreach (array_keys(AdminBroadcast::AUDIENCES) as $audience) {
            if ($audience === AdminBroadcast::AUDIENCE_SPECIFIC) {
                continue;
            }
            $query = AdminBroadcast::audienceQuery($audience);
            $counts[$audience] = [
                'total'     => (clone $query)->count(),
                'reachable' => (clone $query)->whereNotNull('fcm_token')->where('fcm_token', '!=', '')->count(),
            ];
        }

        return $counts;
    }

    private function recentBroadcasts()
    {
        $count = fn (string $status) => fn ($q) => $q->where('status', $status);

        return AdminBroadcast::with('sender:id,name,last_name')
            ->withCount([
                'logs as delivered_count' => $count(PushNotificationLog::STATUS_SENT),
                'logs as failed_count'    => $count(PushNotificationLog::STATUS_FAILED),
                'logs as skipped_count'   => $count(PushNotificationLog::STATUS_SKIPPED),
            ])
            ->latest()
            ->limit(50)
            ->get();
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
