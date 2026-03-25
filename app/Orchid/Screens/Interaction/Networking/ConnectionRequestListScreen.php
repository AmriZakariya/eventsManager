<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Interaction\Networking;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Illuminate\Support\Str;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ConnectionRequestListScreen extends Screen
{
    public $name = 'Networking Moderation';
    public $description = 'Monitor and manage connection requests between event participants.';

    public function query(Request $request): array
    {
        $status = $request->string('status')->toString();
        $search = trim((string) $request->get('search'));
        $sort = $request->get('sort', 'newest');

        $requests = Connection::with([
            'requester.company:id,name',
            'requester.roles:id,name,slug',
            'target.company:id,name',
            'target.roles:id,name,slug',
        ])
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $q) use ($search) {
                    $like = '%' . $search . '%';

                    $q->whereHas('requester', function (Builder $userQuery) use ($like) {
                        $userQuery->where(function (Builder $inner) use ($like) {
                            $inner->where('name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('job_title', 'like', $like)
                                ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', $like));
                        });
                    })->orWhereHas('target', function (Builder $userQuery) use ($like) {
                        $userQuery->where(function (Builder $inner) use ($like) {
                            $inner->where('name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('job_title', 'like', $like)
                                ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', $like));
                        });
                    });
                });
            });

        $this->applySorting($requests, $sort);

        $requests = $requests
            ->paginate(15)
            ->withQueryString();

        return [
            'requests' => $requests,
            'metrics' => [
                'pending'  => ['value' => number_format(Connection::where('status', 'pending')->count()), 'label' => 'Awaiting Action'],
                'accepted' => ['value' => number_format(Connection::where('status', 'accepted')->count()), 'label' => 'Total Matches'],
                'declined' => ['value' => number_format(Connection::where('status', 'declined')->count()), 'label' => 'Rejected'],
                'today'    => ['value' => number_format(Connection::whereDate('created_at', today())->count()), 'label' => 'Requests Today'],
                'shown'    => ['value' => number_format($requests->total()), 'label' => 'Results Shown'],
            ],
        ];
    }

    public function commandBar(): array
    {
        return [
            Link::make('All Activity')->route('platform.networking.requests')->icon('bs.collection'),
            Link::make('Pending')->route('platform.networking.requests', ['status' => 'pending'])->icon('bs.hourglass-split')->type(Color::WARNING),
            Link::make('Accepted')->route('platform.networking.requests', ['status' => 'accepted'])->icon('bs.check-all')->type(Color::SUCCESS),
            Link::make('Declined')->route('platform.networking.requests', ['status' => 'declined'])->icon('bs.x-circle')->type(Color::DANGER),
        ];
    }

    public function layout(): array
    {
        $isFiltering = request('search') || request('status') || request('sort');

        return [
            Layout::view('admin.networking.connection-request-styles'),

            Layout::metrics([
                'Pending'   => 'metrics.pending',
                'Accepted'  => 'metrics.accepted',
                'Declined'  => 'metrics.declined',
                'New Today' => 'metrics.today',
                'Visible'   => 'metrics.shown',
            ]),

            Layout::rows([
                Group::make([
                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Name, email, company, or job title...')
                        ->value(request('search'))
                        ->icon('bs.search'),

                    Select::make('status')
                        ->title('Status')
                        ->empty('All statuses')
                        ->options($this->statusOptions())
                        ->value(request('status')),

                    Select::make('sort')
                        ->title('Sort')
                        ->options($this->sortOptions())
                        ->value(request('sort', 'newest')),
                ]),

                Group::make([
                    Button::make('Apply')
                        ->icon('bs.funnel-fill')
                        ->method('applyFilters')
                        ->type(Color::PRIMARY),

                    Link::make($isFiltering ? 'Clear filters' : 'Reset')
                        ->icon($isFiltering ? 'bs.x-circle-fill' : 'bs.arrow-clockwise')
                        ->route('platform.networking.requests')
                        ->class('btn btn-link'),
                ])->autoWidth(),
            ])->title('Find the right request faster'),

            Layout::table('requests', [
                TD::make('requester', 'Requester (From)')
                    ->width('350px')
                    ->render(fn ($r) => $this->renderUserPersona($r->requester)),

                TD::make('direction', '')
                    ->align(TD::ALIGN_CENTER)
                    ->width('50px')
                    ->render(fn() => '<div class="text-muted opacity-50"><i class="icon-arrow-right-circle" style="font-size: 1.5rem;"></i></div>'),

                TD::make('target', 'Recipient (To)')
                    ->width('350px')
                    ->render(fn ($r) => $this->renderUserPersona($r->target)),

                TD::make('status', 'Moderation State')
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn ($r) => $this->renderStatusBadge($r->status)),

                TD::make('created_at', 'Timeline')
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn ($r) => sprintf(
                        '<div class="text-dark fw-semibold">%s</div><div class="small text-muted">Created %s</div><div class="small text-muted">Updated %s</div>',
                        $r->created_at->diffForHumans(),
                        $r->created_at->format('M d, H:i'),
                        $r->updated_at->diffForHumans()
                    )),

                TD::make(__('Actions'))
                    ->align(TD::ALIGN_RIGHT)
                    ->render(fn ($r) => $this->renderActionButtons($r)),
            ]),
        ];
    }

    /**
     * Helper to render a detailed User Persona Card
     */
    private function renderUserPersona(?User $user): string
    {
        if (!$user) {
            return '<span class="text-muted">Deleted User</span>';
        }

        $avatar = $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random';
        $company = optional($user->company)->name ?? 'No Company';
        $editUrl = route('platform.systems.users.edit', $user->id);
        $roleLabel = 'App: ' . $user->appRoleLabel();
        $roleColor = $user->role === User::APP_ROLE_EXHIBITOR ? '#d4af37' : '#28a745';
        $adminPanelRoles = e($user->adminPanelRolesLabel());
        $createdSource = $user->created_source ? e($user->createdSourceLabel()) : 'Unknown';
        $name = trim($user->name . ' ' . $user->last_name);
        $jobLine = trim(($user->job_title ?: 'No job title') . ' @ ' . $company);

        return sprintf(
            '<div class="connection-persona d-flex align-items-center">
                <div class="position-relative flex-shrink-0">
                    <a href="%s"><img src="%s" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #fff;"></a>
                    <span class="position-absolute bottom-0 end-0 badge rounded-pill" style="background-color:%s; width:12px; height:12px; border:2px solid #fff;" title="%s"></span>
                </div>
                <div class="ms-3" style="line-height: 1.25;">
                    <div class="font-weight-bold text-dark"><a href="%s" class="text-dark text-decoration-none">%s</a></div>
                    <div class="small text-muted">%s</div>
                    <div class="small text-muted">Admin panel: %s</div>
                    <div class="small text-muted">Created: %s</div>
                    <div class="mt-1"><span class="badge" style="background-color:%s15; color:%s; font-size: 0.65rem; border: 1px solid %s30;">%s</span></div>
                </div>
            </div>',
            $editUrl,
            $avatar,
            $roleColor,
            $roleLabel,
            $editUrl,
            e($name),
            e(Str::limit($jobLine, 44)),
            $adminPanelRoles,
            $createdSource,
            $roleColor,
            $roleColor,
            $roleColor,
            $roleLabel
        );
    }

    private function renderStatusBadge(string $status): string
    {
        $style = match ($status) {
            'pending'  => 'background: #fff3cd; color: #856404; border: 1px solid #ffeeba;',
            'accepted' => 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;',
            'declined' => 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;',
            default    => 'background: #e2e3e5; color: #383d41;'
        };

        return sprintf(
            '<span class="badge rounded-pill text-uppercase" style="padding: 6px 12px; letter-spacing: .06em; font-size: .68rem; %s">%s</span>',
            $style,
            e($status)
        );
    }

    private function renderActionButtons($r): string
    {
        $viewButton = Link::make('Details')
            ->icon('bs.eye')
            ->route('platform.networking.requests.show', $r->id)
            ->class('btn btn-sm btn-outline-primary')
            ->render();

        if ($r->status !== 'pending') {
            return '<div class="d-flex justify-content-end gap-2 align-items-center">' . $viewButton . '<span class="text-muted small">Processed</span></div>';
        }

        return '<div class="d-flex justify-content-end gap-2 align-items-center">' .
            $viewButton .
            Button::make('Approve')
                ->icon('bs.check-lg')
                ->type(Color::SUCCESS)
                ->confirm('Are you sure you want to manually accept this request?')
                ->method('forceAccept', ['id' => $r->id])
                ->render() .
            Button::make('Decline')
                ->icon('bs.x-lg')
                ->type(Color::DANGER)
                ->confirm('Are you sure you want to manually decline this request?')
                ->method('forceDecline', ['id' => $r->id])
                ->render() .
            '</div>';
    }

    private function applySorting(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'updated' => $query->orderByDesc('updated_at'),
            'requester_az' => $query
                ->join('users as requester_users', 'requester_users.id', '=', 'connections.requester_id')
                ->select('connections.*')
                ->orderBy('requester_users.name')
                ->orderBy('requester_users.last_name'),
            'recipient_az' => $query
                ->join('users as target_users', 'target_users.id', '=', 'connections.target_id')
                ->select('connections.*')
                ->orderBy('target_users.name')
                ->orderBy('target_users.last_name'),
            'status' => $query
                ->orderByRaw("case status when 'pending' then 1 when 'accepted' then 2 when 'declined' then 3 else 4 end")
                ->orderByDesc('created_at'),
            default => $query->latest(),
        };
    }

    private function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
        ];
    }

    private function sortOptions(): array
    {
        return [
            'newest' => 'Newest first',
            'oldest' => 'Oldest first',
            'updated' => 'Recently updated',
            'requester_az' => 'Requester A-Z',
            'recipient_az' => 'Recipient A-Z',
            'status' => 'Status',
        ];
    }

    /* ================= Actions ================= */

    public function applyFilters(Request $request)
    {
        return redirect()->route('platform.networking.requests', array_filter([
            'search' => trim((string) $request->get('search')),
            'status' => $request->get('status'),
            'sort' => $request->get('sort', 'newest'),
        ], fn ($value) => $value !== null && $value !== '' && $value !== 'newest'));
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
}
