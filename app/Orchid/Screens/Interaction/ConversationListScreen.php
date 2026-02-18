<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Interaction;

use App\Models\Message;
use App\Models\User;
use App\Orchid\Filters\ConversationSearchFilter;
use App\Orchid\Filters\ConversationRoleFilter;
use App\Orchid\Filters\ConversationDateFilter;
use App\Orchid\Layouts\ConversationFiltersLayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Color;

class ConversationListScreen extends Screen
{
    public $name = 'Conversations';
    public $description = 'Monitor and manage user interactions across the platform';
    public $permission = 'platform.systems.users';

    /**
     * Inject CSS only once per page render using a static flag.
     */
    private static bool $stylesInjected = false;

    public function query(): iterable
    {
        // Reset styles flag on each fresh query
        self::$stylesInjected = false;

        $totalConversations = DB::table(function ($query) {
            $query->from('messages')
                ->selectRaw('LEAST(sender_id, receiver_id) as u1')
                ->selectRaw('GREATEST(sender_id, receiver_id) as u2')
                ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');
        }, 'conversation_pairs')->count();

        $totalMessages = Message::count();

        $activeConversations = DB::table(function ($query) {
            $query->from('messages')
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw('LEAST(sender_id, receiver_id) as u1')
                ->selectRaw('GREATEST(sender_id, receiver_id) as u2')
                ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');
        }, 'active_pairs')->count();

        $avgMessages = $totalConversations > 0
            ? round($totalMessages / $totalConversations, 1)
            : 0;

        // Build main conversation query with filters applied
        $subQuery = Message::filters([
            ConversationSearchFilter::class,
            ConversationRoleFilter::class,
            ConversationDateFilter::class,
        ])
            ->select(
                DB::raw('CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END as user_1_id'),
                DB::raw('CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END as user_2_id'),
                DB::raw('MAX(created_at) as last_message_at'),
                DB::raw('MIN(created_at) as first_message_at'),
                DB::raw('COUNT(*) as total_messages')
            )
            ->groupBy('user_1_id', 'user_2_id');

        // Sorting — only allow valid columns to avoid SQL injection
        $allowedSorts = ['last_message_at', 'total_messages', 'first_message_at'];
        $sortField = in_array(request('sort'), $allowedSorts, true)
            ? request('sort')
            : 'last_message_at';
        $sortDirection = request('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $subQuery->orderBy($sortField, $sortDirection);

        $conversations = $subQuery->paginate(15);

        // Eager-load users for all conversations in one query
        $userIds = $conversations->getCollection()
            ->flatMap(fn($c) => [$c->user_1_id, $c->user_2_id])
            ->unique()
            ->filter();

        $users = User::whereIn('id', $userIds)
            ->with(['company', 'roles'])
            ->get()
            ->keyBy('id');

        $conversations->getCollection()->transform(function ($c) use ($users) {
            $c->p1 = $users[$c->user_1_id] ?? null;
            $c->p2 = $users[$c->user_2_id] ?? null;

            $firstDate = Carbon::parse($c->first_message_at);
            $lastDate  = Carbon::parse($c->last_message_at);

            $c->duration_days   = $firstDate->diffInDays($lastDate);
            $c->activity_level  = $this->calculateActivityLevel($c);

            return $c;
        });

        return [
            'conversations' => $conversations,
            'metrics' => [
                'total_convos'   => number_format($totalConversations),
                'total_messages' => number_format($totalMessages),
                'active_today'   => number_format($activeConversations),
                'avg_messages'   => number_format($avgMessages, 1),
            ],
        ];
    }

    public function layout(): iterable
    {
        return [
            // Metrics summary bar
            Layout::metrics([
                'Total Conversations' => 'metrics.total_convos',
                'Total Messages'      => 'metrics.total_messages',
                'Active Today'        => 'metrics.active_today',
                'Avg. Messages'       => 'metrics.avg_messages',
            ]),

            // Filters
            ConversationFiltersLayout::class,

            // Export action
            Layout::rows([
                Button::make('Export CSV')
                    ->icon('cloud-download')
                    ->method('export')
                    ->rawClick(),
            ]),

            // Conversation table
            Layout::table('conversations', [

                TD::make('p1', 'Initiator')
                    ->width('38%')
                    ->render(fn($c) => $this->renderUserCard($c->p1)),

                TD::make('flow', 'Activity')
                    ->width('10%')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn($c) => $this->renderActivityBadge($c)),

                TD::make('p2', 'Recipient')
                    ->width('38%')
                    ->render(fn($c) => $this->renderUserCard($c->p2)),

                TD::make('last_message_at', 'Insights')
                    ->align(TD::ALIGN_RIGHT)
                    ->width('14%')
                    ->sort()
                    ->render(fn($c) => $this->renderStats($c)),
            ]),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    private function calculateActivityLevel(object $conversation): string
    {
        $hoursAgo = Carbon::parse($conversation->last_message_at)->diffInHours();

        return match (true) {
            $hoursAgo < 1   => 'very-high',
            $hoursAgo < 6   => 'high',
            $hoursAgo < 24  => 'medium',
            $hoursAgo < 168 => 'low',
            default         => 'inactive',
        };
    }

    private function getStyles(): string
    {
        if (self::$stylesInjected) {
            return '';
        }
        self::$stylesInjected = true;

        return <<<'CSS'
        <style>
            /* ── User Card ─────────────────────────────────── */
            .uc { display:flex; align-items:center; gap:12px; padding:6px 0; }
            .uc-avatar-wrap { position:relative; flex-shrink:0; }
            .uc-avatar {
                width:48px; height:48px; border-radius:12px; overflow:hidden;
                display:flex; align-items:center; justify-content:center;
                background:linear-gradient(135deg,#667eea,#764ba2);
                box-shadow:0 3px 10px rgba(102,126,234,.25);
                transition:transform .2s;
            }
            .uc-avatar:hover { transform:scale(1.06); }
            .uc-avatar img { width:100%; height:100%; object-fit:cover; }
            .uc-avatar.deleted { background:#ecf0f1; box-shadow:none; }
            .uc-avatar.deleted i { color:#bdc3c7; font-size:18px; }
            .uc-online {
                position:absolute; bottom:1px; right:1px;
                width:11px; height:11px; border-radius:50%;
                background:#2ecc71; border:2px solid #fff;
            }
            .uc-info { flex:1; min-width:0; }
            .uc-name {
                display:block; font-weight:600; font-size:.9rem;
                color:#2c3e50; white-space:nowrap; overflow:hidden;
                text-overflow:ellipsis; text-decoration:none;
                transition:color .15s;
            }
            .uc-name:hover { color:#667eea; }
            .uc-name.deleted { color:#bdc3c7; font-style:italic; }
            .uc-meta {
                display:block; font-size:.72rem; color:#95a5a6;
                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
                margin-top:2px;
            }
            .uc-badge {
                display:inline-block; margin-top:4px;
                padding:2px 9px; border-radius:20px;
                font-size:.62rem; font-weight:700;
                letter-spacing:.4px; text-transform:uppercase; color:#fff;
            }
            .uc-badge.exhibitor { background:linear-gradient(135deg,#667eea,#764ba2); }
            .uc-badge.visitor   { background:linear-gradient(135deg,#f093fb,#f5576c); }

            /* ── Activity Badge ────────────────────────────── */
            .ab {
                display:inline-flex; flex-direction:column;
                align-items:center; justify-content:center;
                width:52px; height:52px; border-radius:50%;
                border:2px solid #e0e0e0;
                background:#fff; font-size:.65rem; font-weight:700;
                color:#bdc3c7; line-height:1.2;
                box-shadow:0 2px 8px rgba(0,0,0,.07);
                transition:transform .2s, box-shadow .2s;
                cursor:default;
            }
            .ab:hover { transform:scale(1.08); box-shadow:0 4px 14px rgba(0,0,0,.12); }
            .ab i { font-size:13px; margin-bottom:2px; }
            .ab.very-high { border-color:#2ecc71; color:#2ecc71; animation:glow-green 2s infinite; }
            .ab.high      { border-color:#3498db; color:#3498db; }
            .ab.medium    { border-color:#f39c12; color:#f39c12; }
            .ab.low,
            .ab.inactive  { border-color:#bdc3c7; color:#bdc3c7; }

            @keyframes glow-green {
                0%,100% { box-shadow:0 2px 8px rgba(46,204,113,.2); }
                50%      { box-shadow:0 2px 16px rgba(46,204,113,.5); }
            }

            /* ── Conversation Stats ────────────────────────── */
            .cs { display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
            .cs-btn {
                display:inline-flex; align-items:center; gap:6px;
                padding:7px 14px; border-radius:8px;
                background:linear-gradient(135deg,#667eea,#764ba2);
                color:#fff; text-decoration:none;
                font-size:.8rem; font-weight:600;
                box-shadow:0 3px 10px rgba(102,126,234,.3);
                transition:transform .2s, box-shadow .2s;
            }
            .cs-btn:hover {
                transform:translateY(-2px);
                box-shadow:0 5px 16px rgba(102,126,234,.45);
                color:#fff;
            }
            .cs-row { display:flex; align-items:center; gap:5px; font-size:.75rem; color:#7f8c8d; }
            .cs-val { font-weight:700; color:#2c3e50; font-size:.85rem; }
            .cs-dur {
                display:inline-flex; align-items:center; gap:4px;
                padding:3px 9px; border-radius:20px;
                background:#f4f6f8; color:#7f8c8d;
                font-size:.68rem; font-weight:600;
            }
        </style>
        CSS;
    }

    private function renderUserCard(?User $user): string
    {
        $styles = $this->getStyles();

        if (!$user) {
            return $styles . '
                <div class="uc">
                    <div class="uc-avatar-wrap">
                        <div class="uc-avatar deleted"><i class="icon-user"></i></div>
                    </div>
                    <div class="uc-info">
                        <span class="uc-name deleted">Deleted User</span>
                        <span class="uc-meta">Account removed</span>
                    </div>
                </div>';
        }

        $avatar    = $user->avatar_url
            ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp&s=200';
        $isExhibitor = $user->roles->contains('slug', 'exhibitor');
        $badgeClass  = $isExhibitor ? 'exhibitor' : 'visitor';
        $badgeLabel  = $isExhibitor ? 'Exhibitor' : 'Visitor';
        $company     = optional($user->company)->name ?? 'Independent';
        $editUrl     = route('platform.systems.users.edit', $user->id);
        $isOnline    = isset($user->last_active_at) && $user->last_active_at?->diffInMinutes() < 30;
        $onlineDot   = $isOnline ? '<div class="uc-online"></div>' : '';
        $fullName    = e($user->name . ' ' . $user->last_name);

        return $styles . sprintf(
                '<div class="uc">
                <div class="uc-avatar-wrap">
                    <a href="%s" class="uc-avatar">
                        <img src="%s" alt="%s" loading="lazy">
                    </a>
                    %s
                </div>
                <div class="uc-info">
                    <a href="%s" class="uc-name" title="%s — %s">%s</a>
                    <span class="uc-meta"><i class="icon-briefcase" style="opacity:.6;font-size:.68rem;"></i> %s</span>
                    <span class="uc-badge %s">%s</span>
                </div>
            </div>',
                $editUrl, $avatar, $fullName,
                $onlineDot,
                $editUrl, $fullName, e($user->email),
                $fullName,
                e($company),
                $badgeClass, $badgeLabel
            );
    }

    private function renderActivityBadge(object $conversation): string
    {
        $level = $conversation->activity_level;
        $count = (int) $conversation->total_messages;

        return sprintf(
            '<div class="ab %s" title="%d messages total"><i class="icon-bubble"></i>%d</div>',
            $level,
            $count,
            $count
        );
    }

    private function renderStats(object $conversation): string
    {
        $lastDate  = Carbon::parse($conversation->last_message_at);
        $isRecent  = $lastDate->diffInHours() < 24;
        $timeClass = $isRecent ? 'color:#27ae60;font-weight:600;' : '';

        $chatUrl  = route('platform.conversations.view', [
            'user1' => $conversation->user_1_id,
            'user2' => $conversation->user_2_id,
        ]);

        $days        = (int) $conversation->duration_days;
        $durationTxt = match (true) {
            $days === 0 => 'Today',
            $days === 1 => '1 day',
            default     => "{$days} days",
        };

        return sprintf(
            '<div class="cs">
                <a href="%s" class="cs-btn"><i class="icon-eye"></i> View Chat</a>
                <div class="cs-row">
                    <i class="icon-bubbles text-primary"></i>
                    <span class="cs-val">%d</span>
                    <span>messages</span>
                </div>
                <div class="cs-row" style="%s">
                    <i class="icon-clock"></i>
                    <span>%s</span>
                </div>
                <div class="cs-dur">
                    <i class="icon-calendar" style="font-size:.65rem;"></i> %s
                </div>
            </div>',
            $chatUrl,
            (int) $conversation->total_messages,
            $timeClass,
            $lastDate->diffForHumans(),
            $durationTxt
        );
    }

    /**
     * Export conversations as CSV.
     */
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'conversations_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['User 1 ID', 'User 2 ID', 'Total Messages', 'First Message', 'Last Message']);

            Message::query()
                ->select(
                    DB::raw('CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END as user_1_id'),
                    DB::raw('CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END as user_2_id'),
                    DB::raw('COUNT(*) as total_messages'),
                    DB::raw('MIN(created_at) as first_message_at'),
                    DB::raw('MAX(created_at) as last_message_at')
                )
                ->groupBy('user_1_id', 'user_2_id')
                ->orderByDesc('last_message_at')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->user_1_id,
                            $row->user_2_id,
                            $row->total_messages,
                            $row->first_message_at,
                            $row->last_message_at,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
