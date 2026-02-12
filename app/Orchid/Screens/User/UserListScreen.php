<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserFiltersLayout;
use App\Orchid\Layouts\User\UserListLayout;
use App\Orchid\Filters\GeneralSearchFilter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Company;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserListScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(Request $request): iterable
    {
        // Logic is now clean. Search is handled automatically by UserFiltersLayout -> GeneralSearchFilter
        $query = User::with(['roles', 'company'])
            ->filters(UserFiltersLayout::class)
            ->defaultSort('id', 'desc');

        return [
            // FIXED: Added withQueryString() to persist search/filters across pages
            'users'   => $query->paginate(20)->withQueryString(),
            'metrics' => $this->getUserMetrics(),
        ];
    }

    /**
     * Metrics Query
     */
    private function getUserMetrics(): array
    {
        $stats = User::selectRaw("
            count(*) as total,
            count(case when company_id is not null then 1 end) as with_company,
            count(case when is_visible = 1 then 1 end) as visible
        ")->first();

        $exhibitors = User::whereHas('roles', fn($q) => $q->where('slug', 'exhibitor'))->count();
        $visitors   = User::whereHas('roles', fn($q) => $q->where('slug', 'visitor'))->count();

        return [
            'total'      => ['value' => number_format($stats->total), 'diff' => 0],
            'exhibitors' => ['value' => number_format($exhibitors), 'diff' => 0],
            'visitors'   => ['value' => number_format($visitors), 'diff' => 0],
            'companies'  => ['value' => number_format($stats->with_company), 'diff' => 0],
        ];
    }

    public function name(): ?string
    {
        return '👥 User Management';
    }

    public function description(): ?string
    {
        return 'User administration with advanced search.';
    }

    public function permission(): ?iterable
    {
        return ['platform.systems.users'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Export CSV')
                ->icon('bs.download')
                ->method('exportUsers')
                ->class('btn btn-outline-secondary'),

            Button::make('Quick Add')
                ->icon('bs.lightning-charge')
                ->method('quickAddUser')
                ->modal('quickAddUserModal')
                ->class('btn btn-outline-primary'),

            Link::make('Full Create')
                ->icon('bs.plus-circle')
                ->route('platform.systems.users.create')
                ->type(Color::PRIMARY),
        ];
    }

    public function layout(): iterable
    {
        return [
            // 1. Statistics
            Layout::metrics([
                'Total Users'  => 'metrics.total',
                'Exhibitors'   => 'metrics.exhibitors',
                'Visitors'     => 'metrics.visitors',
                'With Company' => 'metrics.companies',
            ]),

            // 2. Filters & Search (This renders your new GeneralSearchFilter)
            UserFiltersLayout::class,

            // 3. Bulk Actions Toolbar
            // We place this separately so it doesn't clutter the search bar
            Layout::rows([
                Group::make([
                    DropDown::make('Bulk Actions')
                        ->icon('bs.gear')
                        ->class('btn btn-secondary btn-block')
                        ->list([
                            Button::make('Toggle Visibility')->icon('bs.eye')->confirm('Confirm toggle?')->method('bulkToggleVisibility'),
                            Button::make('Assign Role')->icon('bs.person-badge')->modal('bulkAssignRoleModal')->method('bulkAssignRole'),
                            Button::make('Assign Company')->icon('bs.building')->modal('bulkAssignCompanyModal')->method('bulkAssignCompany'),
                            Button::make('Send Email')->icon('bs.envelope')->modal('bulkEmailModal')->method('bulkSendEmail'),
                            Button::make('Delete Selected')->icon('bs.trash')->confirm('Cannot be undone.')->method('bulkDelete')->class('text-danger'),
                        ]),
                ])->autoWidth(),
            ]),

            // 4. Data Table
            UserListLayout::class,

            // 5. Modals
            ...$this->getModals(),
        ];
    }

    // ... (Your getModals(), asyncGetUser(), and Action methods remain exactly the same) ...
    // ... Copy them from your previous code ...

    private function getModals(): array
    {
        return [
            Layout::modal('quickAddUserModal', [
                Layout::rows([
                    Input::make('user.email')->title('Email')->type('email')->required(),
                    Group::make([
                        Input::make('user.name')->title('First Name')->required(),
                        Input::make('user.last_name')->title('Last Name')->required(),
                    ]),
                    Select::make('user.roles')->title('Role')->required()->options($this->getRoleOptions()),
                ])
            ])->title('Quick Add')->applyButton('Create'),

            Layout::modal('bulkAssignRoleModal', [
                Layout::rows([Select::make('role')->title('Select Role')->required()->options($this->getRoleOptions())])
            ])->title('Bulk Assign Role')->applyButton('Assign'),

            Layout::modal('bulkAssignCompanyModal', [
                Layout::rows([Select::make('company_id')->title('Select Company')->fromModel(Company::class, 'name')->required()->empty('No Company')])
            ])->title('Bulk Assign Company')->applyButton('Update'),

            Layout::modal('bulkEmailModal', [
                Layout::rows([
                    Input::make('email_subject')->title('Subject')->required(),
                    TextArea::make('email_message')->title('Message')->required()->rows(5),
                ])
            ])->title('Bulk Email')->applyButton('Send'),

            Layout::modal('editUserModal', UserEditLayout::class)->async('asyncGetUser')->title('Edit User'),
        ];
    }

    // ... [Include your existing Action methods: quickAddUser, saveUser, exportUsers, etc.] ...

    public function quickAddUser(Request $request): void
    {
        $validated = $request->validate([
            'user.email' => 'required|email|unique:users,email',
            'user.name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.roles' => 'required',
        ]);

        $userData = $validated['user'];
        $tempPassword = Str::random(10);

        $user = User::create([
            'name' => $userData['name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'password' => bcrypt($tempPassword),
            'is_visible' => true,
        ]);

        // Assign role logic...
        $roleSlug = $userData['roles'];
        $role = \Orchid\Platform\Models\Role::where('slug', $roleSlug)->first();
        if($role) {
            $user->addRole($role);
        }

        Toast::info("User created. Password: {$tempPassword}")->delay(10000);
    }

    // [Keep your other methods: exportUsers, bulk methods, etc.]

    private function getRoleOptions(): array
    {
        return [
            'admin'     => 'Administrator',
            'exhibitor' => 'Exhibitor',
            'visitor'   => 'Visitor',
            'moderator' => 'Moderator',
        ];
    }

    // Helper needed for Async modal
    public function asyncGetUser(User $user): iterable
    {
        return [
            'user' => $user,
        ];
    }
}
