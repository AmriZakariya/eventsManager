<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\User;
use App\Service\WordPressUserSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class UserWordPressSyncScreen extends Screen
{
    public function query(Request $request): iterable
    {
        $query = User::query()
            ->with(['company', 'roles'])
            ->when($request->filled('status'), function (Builder $builder) use ($request) {
                return $builder->where('is_wordpress_synced', $request->get('status') === 'synced');
            })
            ->when($request->filled('source'), function (Builder $builder) use ($request) {
                return $builder->where('wordpress_sync_source', $request->get('source'));
            })
            ->when($request->filled('search'), function (Builder $builder) use ($request) {
                $search = trim((string) $request->get('search'));

                $builder->where(function (Builder $query) use ($search) {
                    $query->searchFullName($search)
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id');

        return [
            'users' => $query->paginate(20)->withQueryString(),
            'metrics' => [
                'total' => User::count(),
                'synced' => User::where('is_wordpress_synced', true)->count(),
                'unsynced' => User::where('is_wordpress_synced', false)->count(),
                'wordpress_source' => User::where('wordpress_sync_source', User::WORDPRESS_SYNC_SOURCE_WORDPRESS)->count(),
            ],
        ];
    }

    public function name(): ?string
    {
        return 'WordPress User Sync';
    }

    public function description(): ?string
    {
        return 'Track synced and unsynced users, see the sync source, and trigger manual sync.';
    }

    public function permission(): ?iterable
    {
        return ['platform.systems.users'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Sync Unsynced Users')
                ->icon('bs.arrow-repeat')
                ->method('syncUnsyncedUsers'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Total Users' => 'metrics.total',
                'Synced' => 'metrics.synced',
                'Unsynced' => 'metrics.unsynced',
                'From WordPress' => 'metrics.wordpress_source',
            ]),

            Layout::rows([
                \Orchid\Screen\Fields\Group::make([
                    \Orchid\Screen\Fields\Input::make('search')
                        ->title('Search')
                        ->placeholder('Name, email, company...')
                        ->value(request('search')),

                    \Orchid\Screen\Fields\Select::make('status')
                        ->title('Sync Status')
                        ->options([
                            '' => 'All',
                            'synced' => 'Synced',
                            'unsynced' => 'Unsynced',
                        ])
                        ->value(request('status')),

                    \Orchid\Screen\Fields\Select::make('source')
                        ->title('Source')
                        ->options([
                            '' => 'All',
                            User::WORDPRESS_SYNC_SOURCE_APP => 'App',
                            User::WORDPRESS_SYNC_SOURCE_WORDPRESS => 'WordPress',
                        ])
                        ->value(request('source')),

                    Button::make('Apply')
                        ->icon('bs.funnel')
                        ->method('applyFilters'),

                    Link::make('Reset')
                        ->icon('bs.x-circle')
                        ->route('platform.systems.users.wordpress-sync'),
                ])->autoWidth(),
            ]),

            Layout::table('users', [
                TD::make('user', 'User')
                    ->render(function (User $user) {
                        $name = e(trim($user->name.' '.$user->last_name));
                        $email = e($user->email);
                        $company = e($user->company?->name ?? $user->company_name ?? 'No company');
                        $url = route('platform.systems.users.edit', $user->id);

                        return "<div>
                            <div><a href=\"{$url}\" class=\"fw-bold text-decoration-none\">{$name}</a></div>
                            <div class=\"text-muted small\">{$email}</div>
                            <div class=\"text-muted small\">{$company}</div>
                        </div>";
                    }),

                TD::make('app_role', 'Roles')
                    ->render(function (User $user) {
                        $appRole = e($user->appRoleLabel());
                        $orchidRoles = e(implode(' / ', $user->orchidRoleNames()) ?: 'none');

                        return "<div><span class='badge bg-primary'>App: {$appRole}</span></div>
                            <div class='small text-muted mt-1'>Orchid: {$orchidRoles}</div>";
                    }),

                TD::make('is_wordpress_synced', 'Status')
                    ->render(function (User $user) {
                        if ($user->is_wordpress_synced) {
                            return "<span class='badge bg-success'>Synced</span>";
                        }

                        return "<span class='badge bg-warning text-dark'>Unsynced</span>";
                    }),

                TD::make('wordpress_sync_source', 'Source')
                    ->render(fn (User $user) => match ($user->wordpress_sync_source) {
                        User::WORDPRESS_SYNC_SOURCE_APP => "<span class='badge bg-info text-dark'>App</span>",
                        User::WORDPRESS_SYNC_SOURCE_WORDPRESS => "<span class='badge bg-secondary'>WordPress</span>",
                        default => "<span class='text-muted'>—</span>",
                    }),

                TD::make('wordpress_synced_at', 'Last Sync')
                    ->render(fn (User $user) => $user->wordpress_synced_at?->format('Y-m-d H:i') ?? '<span class="text-muted">Never</span>'),

                TD::make('wordpress_sync_error', 'Last Error')
                    ->defaultHidden()
                    ->render(fn (User $user) => $user->wordpress_sync_error ? e($user->wordpress_sync_error) : '<span class="text-muted">—</span>'),

                TD::make('actions', 'Actions')
                    ->alignRight()
                    ->render(function (User $user) {
                        return Button::make('Sync Now')
                            ->icon('bs.arrow-repeat')
                            ->method('syncUser', ['user' => $user->id])
                            ->render();
                    }),
            ]),
        ];
    }

    public function applyFilters(Request $request)
    {
        return redirect()->route('platform.systems.users.wordpress-sync', array_filter([
            'search' => $request->get('search') ?: null,
            'status' => $request->get('status') ?: null,
            'source' => $request->get('source') ?: null,
        ]));
    }

    public function syncUser(User $user, WordPressUserSyncService $service): void
    {
        $ok = $service->syncUser($user->fresh('company'), null, User::WORDPRESS_SYNC_SOURCE_APP);

        if ($ok) {
            Toast::success("User {$user->email} synced successfully.");
            return;
        }

        Toast::warning("Sync failed for {$user->email}.");
    }

    public function syncUnsyncedUsers(WordPressUserSyncService $service): void
    {
        $users = User::query()
            ->where('is_wordpress_synced', false)
            ->limit(50)
            ->get();

        $success = 0;

        foreach ($users as $user) {
            if ($service->syncUser($user->fresh('company'), null, User::WORDPRESS_SYNC_SOURCE_APP)) {
                $success++;
            }
        }

        Toast::info("Synced {$success} user(s) out of {$users->count()} unsynced attempts.");
    }
}
