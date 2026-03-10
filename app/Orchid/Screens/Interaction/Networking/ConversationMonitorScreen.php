<?php

namespace App\Orchid\Screens\Interaction\Networking;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;

class ConversationMonitorScreen extends Screen
{
    public $name = 'Chat Monitoring';
    public $description = 'Monitor conversations & detect abuse';

    public function query(): array
    {
        // Portable "conversation pair" keys across DBs (SQLite may not support LEAST/GREATEST).
        $pairUser1Expr = 'CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END';
        $pairUser2Expr = 'CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END';

        $conversations = Message::select(
            DB::raw("{$pairUser1Expr} as user_a"),
            DB::raw("{$pairUser2Expr} as user_b"),
            DB::raw('COUNT(*) as messages'),
            DB::raw('MAX(created_at) as last_message')
        )
            ->groupByRaw("{$pairUser1Expr}, {$pairUser2Expr}")
            ->orderByDesc('last_message')
            ->paginate();

        $users = User::whereIn('id',
            $conversations->pluck('user_a')
                ->merge($conversations->pluck('user_b'))
        )->get()->keyBy('id');

        $conversations->getCollection()->transform(function ($c) use ($users) {
            $c->a = $users[$c->user_a] ?? null;
            $c->b = $users[$c->user_b] ?? null;
            $c->is_abusive = $c->messages > 50; // simple spam rule
            return $c;
        });

        return [
            'conversations' => $conversations,
            'stats' => [
                'requests_today' => 0,
                'pending_requests' => 0,
                'active_chats' => $conversations->total(),
                'flagged_users' => $conversations->where('is_abusive', true)->count(),
            ]
        ];
    }

    public function layout(): array
    {
        return [
            NetworkingStatsLayout::class,

            Layout::table('conversations', [
                TD::make('a', 'User A')->render(fn($c) => $c->a?->name),
                TD::make('b', 'User B')->render(fn($c) => $c->b?->name),

                TD::make('messages', 'Msgs'),

                TD::make('last_message')
                    ->render(fn($c) => now()->diffForHumans($c->last_message)),

                TD::make('Abuse')
                    ->render(fn($c) =>
                    $c->is_abusive
                        ? '<span class="badge bg-danger">🚩 Spam</span>'
                        : '<span class="badge bg-success">OK</span>'
                    ),

                TD::make('View')
                    ->render(fn($c) =>
                    Link::make('Open')
                        ->icon('bs.chat')
                        ->route('platform.conversations.view', [
                            'user1' => $c->user_a,
                            'user2' => $c->user_b
                        ])
                    )
            ])
        ];
    }
}
