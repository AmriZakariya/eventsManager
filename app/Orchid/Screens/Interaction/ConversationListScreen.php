<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Interaction;

use App\Models\Message;
use App\Models\User;
use App\Orchid\Filters\ConversationSearchFilter;
use App\Orchid\Filters\ConversationRoleFilter;
use App\Orchid\Filters\ConversationDateFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Button;

class ConversationListScreen extends Screen
{
    public $name = 'Conversations';
    public $description = 'Explore participant conversations and their latest activity';
    public $permission = 'platform.systems.users';

    /**
     * Inject CSS only once per page render using a static flag.
     */
    private static bool $stylesInjected = false;

    public function query(): iterable
    {
        // Reset styles flag on each fresh query
        self::$stylesInjected = false;
        $styles = $this->getStyles();

        // Portable "conversation pair" keys across DBs (SQLite may not support LEAST/GREATEST).
        $pairUser1Expr = 'CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END';
        $pairUser2Expr = 'CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END';

        $totalConversations = DB::table(function ($query) use ($pairUser1Expr, $pairUser2Expr) {
            $query->from('messages')
                ->selectRaw("{$pairUser1Expr} as u1")
                ->selectRaw("{$pairUser2Expr} as u2")
                ->groupByRaw("{$pairUser1Expr}, {$pairUser2Expr}");
        }, 'conversation_pairs')->count();

        $totalMessages = Message::count();

        $activeConversations = DB::table(function ($query) use ($pairUser1Expr, $pairUser2Expr) {
            $query->from('messages')
                ->where('created_at', '>=', now()->startOfDay())
                ->selectRaw("{$pairUser1Expr} as u1")
                ->selectRaw("{$pairUser2Expr} as u2")
                ->groupByRaw("{$pairUser1Expr}, {$pairUser2Expr}");
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
                DB::raw("{$pairUser1Expr} as user_1_id"),
                DB::raw("{$pairUser2Expr} as user_2_id"),
                DB::raw('MAX(created_at) as last_message_at'),
                DB::raw('MIN(created_at) as first_message_at'),
                DB::raw('COUNT(*) as total_messages')
            )
            ->groupByRaw("{$pairUser1Expr}, {$pairUser2Expr}");

        // Sorting — only allow valid columns to avoid SQL injection
        $allowedSorts = ['last_message_at', 'total_messages', 'first_message_at'];
        $sortField = in_array(request('sort'), $allowedSorts, true)
            ? request('sort')
            : 'last_message_at';
        $sortDirection = request('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $subQuery->orderBy($sortField, $sortDirection);

        $conversations = $subQuery->paginate(15)->withQueryString();

        // Eager-load users for all conversations in one query
        $userIds = $conversations->getCollection()
            ->flatMap(fn($c) => [$c->user_1_id, $c->user_2_id])
            ->unique()
            ->filter();

        $users = User::whereIn('id', $userIds)
            ->select(['id', 'name', 'last_name', 'email', 'avatar', 'company_id', 'app_role', 'created_source', 'password_is_set'])
            ->with([
                'company:id,name',
                'roles:id,name,slug',
            ])
            ->get()
            ->keyBy('id');

        // Batched first/last message fetch for the page's conversations (no N+1):
        // MIN(id) => who actually started the conversation (true initiator),
        // MAX(id) => the newest message (preview).
        $latestMessages = collect();
        $firstMessages  = collect();
        if ($userIds->isNotEmpty()) {
            $pairKey = fn ($m) => min($m->sender_id, $m->receiver_id) . '-' . max($m->sender_id, $m->receiver_id);

            $latestIds = Message::query()
                ->whereIn('sender_id', $userIds)
                ->whereIn('receiver_id', $userIds)
                ->selectRaw('MAX(id) as mid')
                ->groupByRaw("{$pairUser1Expr}, {$pairUser2Expr}")
                ->pluck('mid');

            $firstIds = Message::query()
                ->whereIn('sender_id', $userIds)
                ->whereIn('receiver_id', $userIds)
                ->selectRaw('MIN(id) as mid')
                ->groupByRaw("{$pairUser1Expr}, {$pairUser2Expr}")
                ->pluck('mid');

            $latestMessages = Message::whereIn('id', $latestIds)
                ->get(['id', 'sender_id', 'receiver_id', 'content', 'attachment_url', 'created_at'])
                ->keyBy($pairKey);

            $firstMessages = Message::whereIn('id', $firstIds)
                ->get(['id', 'sender_id', 'receiver_id'])
                ->keyBy($pairKey);
        }

        $conversations->getCollection()->transform(function ($c) use ($users, $latestMessages, $firstMessages) {
            $pairKey = $c->user_1_id . '-' . $c->user_2_id;

            // Order participants by who actually started the conversation.
            $firstMsg = $firstMessages[$pairKey] ?? null;
            if ($firstMsg) {
                $initiatorId = $firstMsg->sender_id;
                $recipientId = $firstMsg->receiver_id;
            } else {
                $initiatorId = $c->user_1_id;
                $recipientId = $c->user_2_id;
            }

            $c->p1 = $users[$initiatorId] ?? null; // Initiator (sent the first message)
            $c->p2 = $users[$recipientId] ?? null; // Recipient
            $c->last_message = $latestMessages[$pairKey] ?? null;

            $firstDate = Carbon::parse($c->first_message_at);
            $lastDate  = Carbon::parse($c->last_message_at);

            $c->duration_days   = $firstDate->diffInDays($lastDate);
            $c->initiator_card = $this->renderUserCard($c->p1);
            $c->recipient_card = $this->renderUserCard($c->p2);
            $c->preview_card = $this->renderLastMessage($c);
            $c->activity_card = $this->renderStats($c);

            return $c;
        });

        return [
            'styles' => $styles,
            'conversations' => $conversations,
            'metrics' => [
                'total_convos'   => number_format($totalConversations),
                'total_messages' => number_format($totalMessages),
                'active_today'   => number_format($activeConversations),
                'avg_messages'   => number_format($avgMessages, 1),
            ],
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Export all conversations')
                ->icon('bs.download')
                ->method('export')
                ->rawClick()
                ->class('btn btn-outline-primary'),
        ];
    }

    public function layout(): iterable
    {
        return [Layout::view('admin.conversations-history')];
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

            /* ── Last Message Preview ──────────────────────── */
            .lm { display:flex; flex-direction:column; gap:5px; max-width:100%; }
            .lm-head {
                display:flex; align-items:center; gap:6px;
                font-size:.72rem; color:#95a5a6; font-weight:600;
            }
            .lm-dir {
                display:inline-flex; align-items:center; gap:3px;
                padding:1px 7px; border-radius:20px;
                background:#eef2ff; color:#4f46e5;
                font-size:.65rem; font-weight:700;
                max-width:160px; overflow:hidden;
                text-overflow:ellipsis; white-space:nowrap;
            }
            .lm-body {
                font-size:.84rem; color:#2c3e50; line-height:1.4;
                display:-webkit-box; -webkit-line-clamp:2;
                -webkit-box-orient:vertical; overflow:hidden;
                background:#f8fafc; border:1px solid #eef0f3;
                border-radius:10px; padding:8px 11px;
            }
            .lm-attach { color:#7f8c8d; font-style:italic; }
            .lm-empty  { color:#bdc3c7; font-style:italic; font-size:.8rem; }

            /* ── Row hover ─────────────────────────────────── */

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
                        <div class="uc-avatar deleted"><i class="bi bi-person-x"></i></div>
                    </div>
                    <div class="uc-info">
                        <span class="uc-name deleted">Deleted User</span>
                        <span class="uc-meta">Account removed</span>
                    </div>
                </div>';
        }

        $avatar = $user->adminAvatarUrl();
        $initials = e($this->initialsForUser($user));
        $badgeClass  = $user->role === User::APP_ROLE_EXHIBITOR ? 'exhibitor' : 'visitor';
        $badgeLabel  = $user->appRoleLabel();
        $company     = optional($user->company)->name ?? 'Independent';
        $editUrl     = route('platform.systems.users.edit', $user->id);
        $isOnline    = isset($user->last_active_at) && $user->last_active_at?->diffInMinutes() < 30;
        $onlineDot   = $isOnline ? '<div class="uc-online"></div>' : '';
        $fullName    = e(trim($user->name . ' ' . $user->last_name));

        // Extra detail is moved into the name tooltip to keep rows scannable.
        $tooltip = e(sprintf(
            '%s — %s · Admin: %s · Created: %s',
            trim($user->name . ' ' . $user->last_name),
            $user->email,
            $user->adminPanelRolesLabel(),
            $user->created_source ? $user->createdSourceLabel() : 'Unknown'
        ));

        return $styles . sprintf(
                '<div class="uc">
                <div class="uc-avatar-wrap">
                    <a href="%s" class="uc-avatar">
                        %s
                    </a>
                    %s
                </div>
                <div class="uc-info">
                    <a href="%s" class="uc-name" title="%s">%s</a>
                    <span class="uc-meta" title="%s"><i class="bi bi-briefcase" style="opacity:.6;font-size:.68rem;"></i> %s</span>
                    <span class="uc-badge %s">%s</span>
                </div>
            </div>',
                $editUrl,
                $avatar
                    ? '<img src="' . e($avatar) . '" alt="' . $fullName . '" loading="lazy" referrerpolicy="no-referrer">'
                    : '<span style="font-weight:700;font-size:.78rem;letter-spacing:.04em;">' . $initials . '</span>',
                $onlineDot,
                $editUrl, $tooltip,
                $fullName,
                e($company), e($company),
                $badgeClass, $badgeLabel
            );
    }

    private function renderLastMessage(object $conversation): string
    {
        $msg = $conversation->last_message ?? null;

        if (!$msg) {
            return '<span class="lm-empty">No message content</span>';
        }

        // Resolve the sender's display name from the already-loaded participants.
        $sender = null;
        if ($conversation->p1 && $msg->sender_id === $conversation->p1->id) {
            $sender = $conversation->p1;
        } elseif ($conversation->p2 && $msg->sender_id === $conversation->p2->id) {
            $sender = $conversation->p2;
        }
        $senderName = $sender ? trim($sender->name . ' ' . $sender->last_name) : 'Unknown';

        // Body: text, or an attachment indicator when empty.
        $content = trim((string) $msg->content);
        if ($content !== '') {
            $body = '<div class="lm-body">' . e($content) . '</div>';
        } elseif ($msg->attachment_url) {
            $body = '<div class="lm-body lm-attach"><i class="bi bi-paperclip"></i> Attachment</div>';
        } else {
            $body = '<span class="lm-empty">Empty message</span>';
        }

        return sprintf(
            '<div class="lm">
                <div class="lm-head">
                    <span class="lm-dir"><i class="bi bi-arrow-return-right"></i> %s</span>
                    <time title="%s" datetime="%s">Last message · %s</time>
                </div>
                %s
            </div>',
            e($senderName),
            e(Carbon::parse($msg->created_at)->format('M j, Y \a\t H:i')),
            e(Carbon::parse($msg->created_at)->toIso8601String()),
            e(Carbon::parse($msg->created_at)->diffForHumans()),
            $body
        );
    }

    private function initialsForUser(User $user): string
    {
        $fullName = trim($user->name . ' ' . $user->last_name);
        $parts = preg_split('/\s+/', $fullName) ?: [];
        $first = $parts[0][0] ?? '';
        $last = $parts[1][0] ?? '';

        return strtoupper($first . $last) ?: 'NA';
    }

    private function renderStats(object $conversation): string
    {
        $chatUrl  = route('platform.conversations.view', [
            'user1' => $conversation->user_1_id,
            'user2' => $conversation->user_2_id,
        ]);

        $days        = (int) $conversation->duration_days;
        $durationTxt = match (true) {
            $days === 0 => 'Less than 1 day',
            $days === 1 => '1 day',
            default     => "{$days} days",
        };

        $count = (int) $conversation->total_messages;
        $participants = trim(($conversation->p1?->name ?? 'Deleted user') . ' and ' . ($conversation->p2?->name ?? 'Deleted user'));

        return sprintf(
            '<div class="cs">
                <div class="cs-row"><span class="cs-val">%d %s</span></div>
                <div class="cs-dur" title="Time between the first and latest messages. First: %s. Latest: %s.">Conversation span: %s</div>
                <a href="%s" class="cs-btn" aria-label="%s">View chat <span aria-hidden="true">→</span></a>
            </div>',
            $count,
            $count === 1 ? 'message' : 'messages',
            e(Carbon::parse($conversation->first_message_at)->format('M j, Y H:i')),
            e(Carbon::parse($conversation->last_message_at)->format('M j, Y H:i')),
            e($durationTxt),
            e($chatUrl),
            e('View chat between ' . $participants)
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
