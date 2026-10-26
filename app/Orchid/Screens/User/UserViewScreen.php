<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\Appointment;
use App\Models\Connection;
use App\Models\Message;
use App\Models\User;
use App\Orchid\Screens\User\Concerns\BansUsers;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;

/**
 * Read-only profile of a user: everything the admin may need at a glance,
 * without the risk of the edit form. Secrets (password, push token) are never
 * shown.
 */
class UserViewScreen extends Screen
{
    use BansUsers;

    public ?User $user = null;

    public function query(User $user): iterable
    {
        $user->load(['company:id,name', 'roles:id,name,slug']);
        $bannedBy = $user->banned_by ? User::find($user->banned_by, ['id', 'name', 'last_name']) : null;
        $this->user = $user;

        $pairExpr = 'CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END, '
            . 'CASE WHEN sender_id < receiver_id THEN receiver_id ELSE sender_id END';

        $userMessages = Message::query()
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id));

        $activity = [
            'messages_sent'     => Message::where('sender_id', $user->id)->count(),
            'messages_received' => Message::where('receiver_id', $user->id)->count(),
            'conversations'     => DB::query()->fromSub(
                (clone $userMessages)->selectRaw('1')->groupByRaw($pairExpr),
                'pairs'
            )->count(),
            'meetings_booked'   => Appointment::where('booker_id', $user->id)->count(),
            'meetings_received' => Appointment::where('target_user_id', $user->id)->count(),
            'connections'       => Connection::where('status', 'accepted')
                ->where(fn ($q) => $q->where('requester_id', $user->id)->orWhere('target_id', $user->id))
                ->count(),
            'pending_requests'  => Connection::where('status', 'pending')
                ->where(fn ($q) => $q->where('requester_id', $user->id)->orWhere('target_id', $user->id))
                ->count(),
        ];

        return [
            'user'     => $user,
            'avatar'   => $user->adminAvatarUrl(),
            'activity' => $activity,
            'bannedBy' => $bannedBy,
        ];
    }

    public function name(): ?string
    {
        return 'User profile';
    }

    public function description(): ?string
    {
        return 'Read-only view of everything we know about this user';
    }

    public function permission(): ?iterable
    {
        return ['platform.systems.users'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('All users')
                ->icon('bs.arrow-left')
                ->route('platform.systems.users'),

            ...$this->banActions($this->user),

            Link::make('Edit')
                ->icon('bs.pencil')
                ->type(Color::PRIMARY)
                ->route('platform.systems.users.edit', $this->user),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.user.profile-view'),
            $this->banModal(),
        ];
    }
}
