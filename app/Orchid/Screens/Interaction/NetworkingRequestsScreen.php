<?php

namespace App\Orchid\Screens\Interaction;

use App\Models\Connection;
use App\Models\User;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;

class NetworkingRequestsScreen extends Screen
{
    public $name = 'Networking Requests';
    public $description = 'Track connection requests between users.';

    public function query(): array
    {
        return [
            'requests' => Connection::with(['requester.company', 'target.company'])
                ->orderByDesc('created_at')
                ->paginate(20)
        ];
    }

    public function commandBar(): array
    {
        return [];
    }

    public function layout(): array
    {
        return [
            Layout::table('requests', [

                TD::make('requester', 'Requester')
                    ->render(fn($c) => $this->renderSimpleUser($c->requester)),

                TD::make('arrow', '')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn() => '➡️'),

                TD::make('target', 'Target')
                    ->render(fn($c) => $this->renderSimpleUser($c->target)),

                TD::make('status', 'Status')
                    ->render(function ($c) {
                        return match ($c->status) {
                            'pending' =>
                            '<span class="badge bg-warning text-dark">Pending</span>',
                            'accepted' =>
                            '<span class="badge bg-success">Accepted</span>',
                            'declined' =>
                            '<span class="badge bg-danger">Declined</span>',
                            default =>
                            '<span class="badge bg-secondary">Unknown</span>',
                        };
                    }),

                TD::make('created_at', 'Requested')
                    ->render(fn($c) => $c->created_at->diffForHumans()),

                TD::make('updated_at', 'Last Update')
                    ->render(fn($c) => $c->updated_at->diffForHumans()),

                TD::make('Actions')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn($c) =>
                    Link::make('View Users')
                        ->icon('bs.person-lines-fill')
                        ->route('platform.users.edit', $c->requester_id)
                    ),
            ])
        ];
    }

    private function renderSimpleUser(?User $user): string
    {
        if (! $user) {
            return '<span class="text-muted">Deleted user</span>';
        }

        $company = $user->company ? ' <span class="text-muted">(' . e($user->company->name) . ')</span>' : '';

        return e(trim($user->name . ' ' . $user->last_name))
            . ' '
            . $user->profileCompletionBadgeHtml('ms-1')
            . $company;
    }
}
