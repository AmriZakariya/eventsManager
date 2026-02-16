<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Interaction;

use App\Models\Message;
use App\Models\User;
use App\Orchid\Filters\ConversationSearchFilter;
use App\Orchid\Filters\ConversationRoleFilter;
use App\Orchid\Filters\ConversationDateFilter;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Layouts\Selection;
use Orchid\Support\Color;

class ConversationListScreen extends Screen
{
    public $name = 'Conversations';
    public $description = 'Monitor and manage user interactions across the platform';
    public $permission = 'platform.systems.users';

    public function query(): iterable
    {
        // Enhanced metrics with additional insights
        $totalConversations = DB::table(function ($query) {
            $query->from('messages')
                ->selectRaw('LEAST(sender_id, receiver_id) as u1')
                ->selectRaw('GREATEST(sender_id, receiver_id) as u2')
                ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');
        }, 'conversation_pairs')->count();

        $totalMessages = Message::count();

        // Active conversations (last 24 hours)
        $activeConversations = DB::table(function ($query) {
            $query->from('messages')
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw('LEAST(sender_id, receiver_id) as u1')
                ->selectRaw('GREATEST(sender_id, receiver_id) as u2')
                ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');
        }, 'active_pairs')->count();

        // Average messages per conversation
        $avgMessages = $totalConversations > 0 ? round($totalMessages / $totalConversations, 1) : 0;

        // Main query with enhanced data
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

        // Apply sorting
        $sortField = request('sort', 'last_message_at');
        $sortDirection = request('direction', 'desc');

        if ($sortField === 'total_messages') {
            $subQuery->orderBy('total_messages', $sortDirection);
        } else {
            $subQuery->orderBy('last_message_at', $sortDirection);
        }

        $conversations = $subQuery->paginate(15);

        // Eager load user data
        $userIds = $conversations->getCollection()
            ->flatMap(fn($c) => [$c->user_1_id, $c->user_2_id])
            ->unique()
            ->filter();

        $users = User::whereIn('id', $userIds)
            ->with(['company', 'roles'])
            ->get()
            ->keyBy('id');

        // Attach user objects and calculate additional metrics
        $conversations->getCollection()->transform(function ($c) use ($users) {
            $c->p1 = $users[$c->user_1_id] ?? null;
            $c->p2 = $users[$c->user_2_id] ?? null;

            // Calculate conversation duration
            $firstDate = \Carbon\Carbon::parse($c->first_message_at);
            $lastDate = \Carbon\Carbon::parse($c->last_message_at);
            $c->duration_days = $firstDate->diffInDays($lastDate);

            // Activity level
            $c->activity_level = $this->calculateActivityLevel($c);

            return $c;
        });

        return [
            'conversations' => $conversations,
            'metrics' => [
                'total_convos' => number_format($totalConversations),
                'total_messages' => number_format($totalMessages),
                'active_today' => number_format($activeConversations),
                'avg_messages' => number_format($avgMessages, 1),
            ],
        ];
    }

    public function layout(): iterable
    {
        return [
            // Enhanced Metrics Dashboard
            Layout::view('orchid.conversations.metrics'),

            // Filters and Actions Bar
            Layout::rows([
                Layout::columns([
                    new class extends Selection {
                        public function filters(): iterable {
                            return [
                                ConversationSearchFilter::class,
                                ConversationRoleFilter::class,
                                ConversationDateFilter::class,
                            ];
                        }
                    },
                ]),
            ]),

            // Action Buttons
            Layout::rows([
                Button::make('Export Conversations')
                    ->icon('cloud-download')
                    ->method('export')
                    ->rawClick()
                    ->class('btn btn-outline-primary mb-3'),
            ]),

            // Modern Conversation Table
            Layout::table('conversations', [

                // Checkbox for bulk actions
                TD::make('select', '')
                    ->width('40px')
                    ->render(fn($c) => sprintf(
                        '<input type="checkbox" class="conversation-select" data-id="%s-%s">',
                        $c->user_1_id,
                        $c->user_2_id
                    )),

                // Participant 1
                TD::make('p1', 'Initiator')
                    ->width('38%')
                    ->sort()
                    ->render(fn($c) => $this->renderEnhancedUserCard($c->p1, 'initiator')),

                // Enhanced Connection Visual
                TD::make('flow', 'Activity')
                    ->width('8%')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn($c) => $this->renderConnectionIndicator($c)),

                // Participant 2
                TD::make('p2', 'Recipient')
                    ->width('38%')
                    ->sort()
                    ->render(fn($c) => $this->renderEnhancedUserCard($c->p2, 'recipient')),

                // Enhanced Stats & Actions
                TD::make('stats', 'Insights')
                    ->align(TD::ALIGN_RIGHT)
                    ->width('16%')
                    ->sort()
                    ->render(fn($c) => $this->renderConversationStats($c)),
            ]),

            // Enhanced pagination info
            Layout::view('orchid.conversations.pagination-info'),
        ];
    }

    /**
     * Calculate activity level based on message frequency
     */
    private function calculateActivityLevel($conversation): string
    {
        $hoursAgo = \Carbon\Carbon::parse($conversation->last_message_at)->diffInHours();

        if ($hoursAgo < 1) return 'very-high';
        if ($hoursAgo < 6) return 'high';
        if ($hoursAgo < 24) return 'medium';
        if ($hoursAgo < 168) return 'low';
        return 'inactive';
    }

    /**
     * Render enhanced user card with better visual hierarchy
     */
    private function renderEnhancedUserCard(?User $user, string $role): string
    {
        if (!$user) {
            return '
                <div class="user-card deleted-user">
                    <div class="user-avatar-wrapper">
                        <div class="user-avatar deleted">
                            <i class="icon-user"></i>
                        </div>
                    </div>
                    <div class="user-info">
                        <span class="user-name deleted">Deleted User</span>
                        <span class="user-meta">Account removed</span>
                    </div>
                </div>
                <style>
                    .user-card { display: flex; align-items: center; padding: 8px 0; }
                    .user-avatar-wrapper { position: relative; margin-right: 14px; }
                    .user-avatar {
                        width: 52px; height: 52px; border-radius: 12px;
                        display: flex; align-items: center; justify-content: center;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                        transition: all 0.3s ease;
                    }
                    .user-avatar.deleted { background: #e0e0e0; box-shadow: none; }
                    .user-avatar img { width: 100%; height: 100%; border-radius: 12px; object-fit: cover; }
                    .user-avatar i { color: white; font-size: 20px; }
                    .user-avatar.deleted i { color: #9e9e9e; }
                    .user-info { flex: 1; min-width: 0; line-height: 1.4; }
                    .user-name {
                        display: block; font-weight: 600; font-size: 0.95rem;
                        color: #2c3e50; margin-bottom: 2px;
                        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                    }
                    .user-name.deleted { color: #9e9e9e; font-style: italic; }
                    .user-meta {
                        display: block; font-size: 0.75rem; color: #7f8c8d;
                        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                    }
                    .user-badge {
                        display: inline-block; padding: 2px 10px; border-radius: 12px;
                        font-size: 0.65rem; font-weight: 600; letter-spacing: 0.5px;
                        text-transform: uppercase; margin-top: 4px;
                    }
                    .user-badge.exhibitor { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
                    .user-badge.visitor { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
                    .user-card:hover .user-avatar { transform: scale(1.05); }
                    .user-status-dot {
                        position: absolute; bottom: 2px; right: 2px;
                        width: 12px; height: 12px; border-radius: 50%;
                        background: #2ecc71; border: 2px solid white;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                    }
                </style>';
        }

        $avatar = $user->avatar_url ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp&s=200';
        $isExhibitor = $user->roles->contains('slug', 'exhibitor');
        $roleLabel = $isExhibitor ? 'Exhibitor' : 'Visitor';
        $roleBadgeClass = $isExhibitor ? 'exhibitor' : 'visitor';
        $companyName = optional($user->company)->name ?? 'Independent';
        $editUrl = route('platform.systems.users.edit', $user->id);

        // Check if user was active recently (within 30 minutes)
        $isOnline = $user->last_active_at && $user->last_active_at->diffInMinutes() < 30;
        $statusDot = $isOnline ? '<div class="user-status-dot"></div>' : '';

        return sprintf(
            '<div class="user-card">
                <div class="user-avatar-wrapper">
                    <a href="%s" class="user-avatar">
                        <img src="%s" alt="%s" loading="lazy">
                    </a>
                    %s
                </div>
                <div class="user-info">
                    <a href="%s" class="user-name" title="%s">
                        %s
                    </a>
                    <span class="user-meta">
                        <i class="icon-briefcase" style="opacity: 0.6; font-size: 0.7rem;"></i> %s
                    </span>
                    <span class="user-badge %s">%s</span>
                </div>
            </div>',
            $editUrl,
            $avatar,
            e($user->name),
            $statusDot,
            $editUrl,
            e($user->name . ' ' . $user->last_name . ' - ' . $user->email),
            e($user->name . ' ' . $user->last_name),
            e($companyName),
            $roleBadgeClass,
            $roleLabel
        );
    }

    /**
     * Render enhanced connection indicator with activity visualization
     */
    private function renderConnectionIndicator($conversation): string
    {
        $activityClass = $conversation->activity_level;
        $messageCount = $conversation->total_messages;

        // Determine pulse animation based on activity
        $pulseAnimation = in_array($activityClass, ['very-high', 'high']) ? 'pulse-active' : '';

        return sprintf(
            '<div class="connection-indicator %s">
                <div class="connection-line">
                    <div class="message-bubble %s" title="%d messages">
                        <i class="icon-bubble"></i>
                        <span class="bubble-count">%d</span>
                    </div>
                </div>
            </div>
            <style>
                .connection-indicator {
                    position: relative; height: 60px;
                    display: flex; align-items: center; justify-content: center;
                }
                .connection-line {
                    width: 100%%; height: 2px;
                    background: linear-gradient(90deg, #e0e0e0 0%%, #bdbdbd 50%%, #e0e0e0 100%%);
                    position: relative;
                }
                .message-bubble {
                    position: absolute; top: 50%%; left: 50%%;
                    transform: translate(-50%%, -50%%);
                    width: 44px; height: 44px; border-radius: 50%%;
                    display: flex; flex-direction: column;
                    align-items: center; justify-content: center;
                    background: white; border: 2px solid #667eea;
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
                    transition: all 0.3s ease;
                }
                .message-bubble:hover {
                    transform: translate(-50%%, -50%%) scale(1.1);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                }
                .message-bubble i {
                    font-size: 14px; color: #667eea; margin-bottom: 2px;
                }
                .bubble-count {
                    font-size: 0.7rem; font-weight: 700;
                    color: #667eea; line-height: 1;
                }
                .message-bubble.very-high {
                    border-color: #2ecc71; animation: pulse-glow 2s infinite;
                }
                .message-bubble.very-high i,
                .message-bubble.very-high .bubble-count { color: #2ecc71; }
                .message-bubble.high { border-color: #3498db; }
                .message-bubble.high i,
                .message-bubble.high .bubble-count { color: #3498db; }
                .message-bubble.medium { border-color: #f39c12; }
                .message-bubble.medium i,
                .message-bubble.medium .bubble-count { color: #f39c12; }
                .message-bubble.low,
                .message-bubble.inactive { border-color: #95a5a6; }
                .message-bubble.low i,
                .message-bubble.low .bubble-count,
                .message-bubble.inactive i,
                .message-bubble.inactive .bubble-count { color: #95a5a6; }

                @keyframes pulse-glow {
                    0%%, 100%% { box-shadow: 0 4px 12px rgba(46, 204, 113, 0.25); }
                    50%% { box-shadow: 0 4px 20px rgba(46, 204, 113, 0.5); }
                }
            </style>',
            $activityClass,
            $activityClass,
            $messageCount,
            $messageCount
        );
    }

    /**
     * Render comprehensive conversation statistics
     */
    private function renderConversationStats($conversation): string
    {
        $lastDate = \Carbon\Carbon::parse($conversation->last_message_at);
        $firstDate = \Carbon\Carbon::parse($conversation->first_message_at);

        $isRecent = $lastDate->diffInHours() < 24;
        $timeClass = $isRecent ? 'text-success' : 'text-muted';

        $chatUrl = route('platform.conversations.view', [
            'user1' => $conversation->user_1_id,
            'user2' => $conversation->user_2_id,
        ]);

        // Duration badge
        $durationDays = $conversation->duration_days;
        $durationText = $durationDays == 0 ? 'Today' : ($durationDays == 1 ? '1 day' : "{$durationDays} days");

        return sprintf(
            '<div class="conversation-stats">
                <a href="%s" class="btn-view-chat" title="View Full Conversation">
                    <i class="icon-eye"></i>
                    <span>View Chat</span>
                </a>

                <div class="stat-row">
                    <div class="stat-item">
                        <i class="icon-bubbles text-primary"></i>
                        <span class="stat-value">%d</span>
                        <span class="stat-label">messages</span>
                    </div>
                </div>

                <div class="stat-row">
                    <div class="stat-item %s">
                        <i class="icon-clock"></i>
                        <span class="stat-label">%s</span>
                    </div>
                </div>

                <div class="stat-row">
                    <div class="duration-badge">
                        <i class="icon-calendar"></i> %s
                    </div>
                </div>
            </div>
            <style>
                .conversation-stats {
                    display: flex; flex-direction: column;
                    gap: 10px; padding: 4px 0;
                }
                .btn-view-chat {
                    display: inline-flex; align-items: center; gap: 6px;
                    padding: 8px 16px; border-radius: 8px;
                    background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%);
                    color: white; text-decoration: none;
                    font-size: 0.85rem; font-weight: 600;
                    border: none; cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                }
                .btn-view-chat:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    color: white;
                }
                .btn-view-chat i { font-size: 14px; }
                .stat-row {
                    display: flex; align-items: center;
                    justify-content: flex-end;
                }
                .stat-item {
                    display: flex; align-items: center; gap: 4px;
                    font-size: 0.8rem; color: #7f8c8d;
                }
                .stat-item i { font-size: 12px; opacity: 0.7; }
                .stat-value {
                    font-weight: 700; color: #2c3e50;
                    font-size: 0.9rem;
                }
                .stat-label { font-size: 0.75rem; }
                .text-success { color: #2ecc71 !important; font-weight: 600; }
                .duration-badge {
                    display: inline-flex; align-items: center; gap: 4px;
                    padding: 4px 10px; border-radius: 12px;
                    background: #ecf0f1; color: #7f8c8d;
                    font-size: 0.7rem; font-weight: 600;
                }
                .duration-badge i { font-size: 10px; }
            </style>',
            $chatUrl,
            $conversation->total_messages,
            $timeClass,
            $lastDate->diffForHumans(),
            $durationText
        );
    }

    /**
     * Export conversations data
     */
    public function export()
    {
        // Implementation for exporting conversation data
        // This would typically generate a CSV or Excel file
    }
}
