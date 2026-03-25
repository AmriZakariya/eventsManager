<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Interaction\Networking;

use App\Models\Connection;
use App\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ConnectionRequestDetailScreen extends Screen
{
    public ?Connection $connection = null;

    public function query(Connection $connection): array
    {
        $connection->load([
            'requester.company:id,name',
            'requester.roles:id,name,slug',
            'target.company:id,name',
            'target.roles:id,name,slug',
        ]);
        $this->connection = $connection;

        return [
            'connection' => $connection,
            'timeline' => $this->timeline($connection),
        ];
    }

    public function name(): ?string
    {
        return 'Connection Request Details';
    }

    public function description(): ?string
    {
        return 'Review both participants, request status, and moderation history in one place.';
    }

    public function commandBar(): array
    {
        $actions = [
            Link::make('Back to Requests')
                ->icon('bs.arrow-left')
                ->route('platform.networking.requests'),
        ];

        if (($this->connection->status ?? null) === 'pending') {
            $actions[] = Button::make('Approve')
                ->icon('bs.check-lg')
                ->type(Color::SUCCESS)
                ->confirm('Approve this connection request?')
                ->method('forceAccept', ['id' => $this->connection->id]);

            $actions[] = Button::make('Decline')
                ->icon('bs.x-lg')
                ->type(Color::DANGER)
                ->confirm('Decline this connection request?')
                ->method('forceDecline', ['id' => $this->connection->id]);
        }

        return $actions;
    }

    public function layout(): array
    {
        return [
            Layout::view('admin.networking.connection-request-styles'),

            Layout::columns([
                Layout::legend('connection', [
                    Sight::make('status', 'Moderation status')
                        ->render(fn (Connection $connection) => $this->renderStatusBadge($connection->status)),

                    Sight::make('requester_id', 'Requester')
                        ->render(fn (Connection $connection) => $this->renderUserCard($connection->requester, 'Sent the request')),

                    Sight::make('target_id', 'Recipient')
                        ->render(fn (Connection $connection) => $this->renderUserCard($connection->target, 'Received the request')),

                    Sight::make('created_at', 'Created')
                        ->render(fn (Connection $connection) => $this->renderDateBlock($connection->created_at, 'Request submitted')),

                    Sight::make('updated_at', 'Last updated')
                        ->render(fn (Connection $connection) => $this->renderDateBlock($connection->updated_at, 'Latest moderation event')),
                ])->title('Request overview'),

                Layout::view('admin.networking.connection-request-timeline'),
            ]),
        ];
    }

    public function forceAccept(int $id)
    {
        Connection::whereKey($id)->update(['status' => 'accepted']);
        Toast::success('Connection request approved.');
    }

    public function forceDecline(int $id)
    {
        Connection::whereKey($id)->update(['status' => 'declined']);
        Toast::info('Connection request rejected.');
    }

    private function timeline(Connection $connection): array
    {
        $timeline = [[
            'title' => 'Connection request created',
            'description' => sprintf(
                '%s sent a networking request to %s.',
                e($this->userDisplayName($connection->requester)),
                e($this->userDisplayName($connection->target))
            ),
            'date' => $connection->created_at->diffForHumans(),
            'icon' => 'bi-person-plus',
            'color' => '#2563eb',
        ]];

        if ($connection->updated_at->gt($connection->created_at->copy()->addSeconds(10))) {
            $timeline[] = [
                'title' => 'Moderation updated',
                'description' => 'Request status changed to <strong>' . e($connection->status) . '</strong>.',
                'date' => $connection->updated_at->diffForHumans(),
                'icon' => $connection->status === 'accepted' ? 'bi-check-circle' : 'bi-x-circle',
                'color' => $connection->status === 'accepted' ? '#16a34a' : '#dc2626',
            ];
        } elseif ($connection->status === 'pending') {
            $timeline[] = [
                'title' => 'Awaiting moderation',
                'description' => 'This request is still pending review and can be approved or declined.',
                'date' => 'Open now',
                'icon' => 'bi-hourglass-split',
                'color' => '#f59e0b',
                'is_future' => true,
            ];
        }

        return array_reverse($timeline);
    }

    private function renderDateBlock($date, string $label): string
    {
        return sprintf(
            '<div class="small text-muted text-uppercase mb-1">%s</div><div class="fw-semibold text-dark">%s</div><div class="small text-muted">%s</div>',
            e($label),
            $date->format('M d, Y \\a\\t H:i'),
            $date->diffForHumans()
        );
    }

    private function renderUserCard(?User $user, string $caption): string
    {
        if (!$user) {
            return '<span class="text-muted">Deleted user</span>';
        }

        $avatar = $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random';
        $company = $user->company?->name ?? 'No company';
        $url = route('platform.systems.users.edit', $user->id);
        $name = e($this->userDisplayName($user));

        return "
            <a href=\"{$url}\" class=\"connection-detail-card text-decoration-none\">
                <img src=\"{$avatar}\" alt=\"{$name}\" class=\"connection-detail-avatar\">
                <div>
                    <div class=\"small text-muted text-uppercase mb-1\">{$caption}</div>
                    <div class=\"fw-semibold text-dark\">{$name}</div>
                    <div class=\"small text-muted\">" . e($user->email) . "</div>
                    <div class=\"small text-muted\">" . e(($user->job_title ?: 'No job title') . ' @ ' . $company) . "</div>
                </div>
            </a>
        ";
    }

    private function renderStatusBadge(string $status): string
    {
        $style = match ($status) {
            'pending' => 'background: #fff7ed; color: #9a3412; border: 1px solid #fdba74;',
            'accepted' => 'background: #dcfce7; color: #166534; border: 1px solid #86efac;',
            'declined' => 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;',
            default => 'background: #e5e7eb; color: #374151; border: 1px solid #d1d5db;',
        };

        return sprintf(
            '<span class="badge rounded-pill text-uppercase" style="padding: 8px 14px; letter-spacing: .08em; font-size: .72rem; %s">%s</span>',
            $style,
            e($status)
        );
    }

    private function userDisplayName(?User $user): string
    {
        return $user ? trim($user->name . ' ' . $user->last_name) : 'Deleted user';
    }
}
