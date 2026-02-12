<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Interaction;

use App\Models\Message;
use App\Models\User;
use App\Orchid\Filters\ConversationSearchFilter; // Ensure this file exists from previous step
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Layouts\Selection;

class ConversationListScreen extends Screen
{
    public $name = 'Conversations';
    public $description = 'Monitor user interactions and chat history.';
    public $permission = 'platform.systems.users';

    public function query(): iterable
    {
        // 1. Correct "Total Conversations" Metric
        // We count unique pairs of (MinID, MaxID) to accurately count threads.
        $totalConversations = DB::table(function ($query) {
            $query->from('messages')
                ->selectRaw('LEAST(sender_id, receiver_id) as u1')
                ->selectRaw('GREATEST(sender_id, receiver_id) as u2')
                ->groupByRaw('LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)');
        }, 'conversation_pairs')->count();


        $totalMessages = Message::count();

        // 2. Main Query for Table
        // We select the latest message for each pair to order by "Last Active"
        $subQuery = Message::filters([ConversationSearchFilter::class])
            ->select(
                DB::raw('CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END as user_1_id'),
                DB::raw('CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END as user_2_id'),
                DB::raw('MAX(created_at) as last_message_at'),
                DB::raw('COUNT(*) as total_messages')
            )
            ->groupBy('user_1_id', 'user_2_id')
            ->orderByDesc('last_message_at');

        $conversations = $subQuery->paginate(10); // Keeping page size small for taller rows

        // 3. Eager Load User Data
        // Collect all user IDs from the result set
        $userIds = $conversations->getCollection()
            ->flatMap(fn($c) => [$c->user_1_id, $c->user_2_id])
            ->unique()
            ->filter();

        // Fetch users with their Roles and Company (prevents N+1 problem)
        $users = User::whereIn('id', $userIds)
            ->with(['company', 'roles'])
            ->get()
            ->keyBy('id');

        // Attach User objects to the conversation row
        $conversations->getCollection()->transform(function ($c) use ($users) {
            $c->p1 = $users[$c->user_1_id] ?? null;
            $c->p2 = $users[$c->user_2_id] ?? null;
            return $c;
        });

        return [
            'conversations' => $conversations,
            'metrics' => [
                'total_convos' => number_format($totalConversations),
                'total_messages' => number_format($totalMessages),
            ],
        ];
    }

    public function layout(): iterable
    {
        return [
            // 1. Metrics Bar
            Layout::metrics([
                'Total Conversations' => 'metrics.total_convos',
                'Total Messages'      => 'metrics.total_messages',
            ]),

            // 2. Search Filter
            new class extends Selection {
                public function filters(): iterable {
                    return [ConversationSearchFilter::class];
                }
            },

            // 3. Modern Table
            Layout::table('conversations', [

                // PARTICIPANT A (Left)
                TD::make('p1', 'Initiator')
                    ->width('42%')
                    ->render(fn($c) => $this->renderModernUserCard($c->p1)),

                // CONNECTOR ARROW
                TD::make('flow', '')
                    ->width('6%')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn() => '<div class="text-muted opacity-50" style="font-size: 1.5rem; line-height: 1;">&rarr;</div>'),

                // PARTICIPANT B (Right)
                TD::make('p2', 'Recipient')
                    ->width('42%')
                    ->render(fn($c) => $this->renderModernUserCard($c->p2)),

                // STATUS & ACTIONS (Combined for cleaner look)
                TD::make('stats', 'Status')
                    ->align(TD::ALIGN_RIGHT)
                    ->width('10%')
                    ->render(function ($c) {
                        $date = \Carbon\Carbon::parse($c->last_message_at);
                        $isRecent = $date->diffInHours() < 24;
                        $timeClass = $isRecent ? 'text-success font-weight-bold' : 'text-muted';

                        // Action URL
                        $chatUrl = route('platform.conversations.view', [
                            'user1' => $c->user_1_id,
                            'user2' => $c->user_2_id,
                        ]);

                        return "
                            <div class='d-flex flex-column align-items-end'>
                                <a href='{$chatUrl}' class='btn btn-sm btn-light border shadow-sm mb-2' title='Open Chat'>
                                    <i class='icon-bubble text-primary mr-1'></i> {$c->total_messages}
                                </a>
                                <div class='{$timeClass} small' style='white-space: nowrap;'>{$date->diffForHumans()}</div>
                            </div>
                        ";
                    }),
            ]),
        ];
    }

    /**
     * Renders a modern, detailed User Card.
     */
    private function renderModernUserCard(?User $user): string
    {
        // Handle Deleted Users
        if (!$user) {
            return '
                <div class="d-flex align-items-center p-2 opacity-50">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center border" style="width: 48px; height: 48px;">
                        <i class="icon-user text-muted"></i>
                    </div>
                    <div class="ml-3">
                        <span class="text-muted font-italic">Deleted User</span>
                    </div>
                </div>';
        }

        // 1. Avatar
        $avatar = $user->avatar_url ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp';

        // 2. Role Determination
        $isExhibitor = $user->roles->contains('slug', 'exhibitor');
        $roleLabel = $isExhibitor ? 'Exhibitor' : 'Visitor';
        $roleColor = $isExhibitor ? '#007bff' : '#28a745'; // Blue vs Green
        $roleBadgeStyle = "background-color: {$roleColor}; color: white; font-size: 0.65rem; padding: 2px 8px; border-radius: 10px; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600;";

        // 3. Context Info
        $companyName = optional($user->company)->name ?? 'No Company';
        $email = $user->email;
        $editUrl = route('platform.systems.users.edit', $user->id);

        return sprintf(
            '<div class="d-flex align-items-center py-2">
                <a href="%s" class="position-relative mr-3">
                    <img src="%s" class="rounded-circle shadow-sm border-white"
                         style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #fff;">
                </a>

                <div style="line-height: 1.35;">
                    <div class="d-flex align-items-center mb-1">
                        <a href="%s" class="text-dark font-weight-bold text-decoration-none mr-2" style="font-size: 0.95rem;">
                            %s
                        </a>
                        <span style="%s">%s</span>
                    </div>

                    <div class="text-muted small text-truncate" style="max-width: 200px;">
                        <i class="icon-building mr-1 opacity-50"></i> %s
                    </div>
                    <div class="text-muted small text-truncate" style="font-size: 0.75rem; max-width: 200px;">
                        %s
                    </div>
                </div>
            </div>',
            $editUrl,
            $avatar,
            $editUrl,
            e($user->name . ' ' . $user->last_name),
            $roleBadgeStyle,
            $roleLabel,
            e($companyName),
            e($email)
        );
    }
}
