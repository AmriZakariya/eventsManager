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
use Orchid\Screen\Actions\ModalToggle;
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
use Illuminate\Support\Facades\Mail;

class UserListScreen extends Screen
{
    /**
     * Query data.
     */
    public function query(Request $request): iterable
    {
        // Logic is now clean. Search is handled automatically by UserFiltersLayout -> GeneralSearchFilter
        $query = User::with([
            'roles:id,name,slug',
            'company:id,name',
        ])
            ->filters(UserFiltersLayout::class);

        $this->sortByCompanyName($query, $request);
        $query->defaultSort('id', 'desc');

        return [
            // FIXED: Added withQueryString() to persist search/filters across pages
            'users'   => $query->paginate(20)->withQueryString(),
            'metrics' => $this->getUserMetrics(),
            'filterBar' => $this->filterBarData($request),
        ];
    }

    /**
     * The Company column sorts by company name; users without a company come last.
     */
    private function sortByCompanyName($query, Request $request): void
    {
        $sort = collect($request->input('sort', []))->first(fn ($value) => ltrim((string) $value, '-') === 'company_id');

        if ($sort === null) {
            return;
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $query->orderByRaw('company_id is null')
            ->orderBy(
                Company::select('name')->whereColumn('companies.id', 'users.company_id')->limit(1),
                $direction,
            );
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

        $exhibitors = User::appRole(User::APP_ROLE_EXHIBITOR)->count();
        $visitors   = User::appRole(User::APP_ROLE_VISITOR)->count();

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
                ->method('exportUsers', request()->query())
                ->rawClick()
                ->class('btn btn-outline-secondary'),

            ModalToggle::make('Quick Add')
                ->icon('bs.lightning-charge')
                ->modal('quickAddUserModal')
                ->method('quickAddUser')
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

            // 2. Search, role tabs, more filters and active-filter chips.
            //    The filter classes in UserFiltersLayout still apply the query
            //    (same URL parameters); only the display changed.
            Layout::view('admin.users.filter-bar'),

            // 3. Floating bar shown when users are selected (admin-user-list.js).
            Layout::view('admin.users.bulk-bar'),

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
                    Select::make('user.roles')->title('Role')->required()->options($this->bulkRoleOptions()),
                ])
            ])->title('Quick Add')->applyButton('Create'),

            Layout::modal('bulkAssignRoleModal', [
                Layout::rows([
                    Input::make('selected_users')->type('hidden')->set('data-bulk-selected', ''),
                    Select::make('role')->title('New role')->required()->options($this->bulkRoleOptions()),
                ]),
            ])->title('Change role')->applyButton('Change role'),

            Layout::modal('bulkAssignCompanyModal', [
                Layout::rows([
                    Input::make('selected_users')->type('hidden')->set('data-bulk-selected', ''),
                    Select::make('company_id')->title('Company')->fromModel(Company::class, 'name')->empty('No company')
                        ->help('Exhibitors are linked to a company. Change their role to Exhibitor too if needed.'),
                ]),
            ])->title('Assign company')->applyButton('Assign'),

            Layout::modal('bulkEmailModal', [
                Layout::rows([
                    Input::make('selected_users')->type('hidden')->set('data-bulk-selected', ''),
                    Input::make('email_subject')->title('Subject')->required()->maxlength(150),
                    TextArea::make('email_message')->title('Message')->required()->rows(6)
                        ->help('Sent as plain text from the platform address, one email per user, in the background.'),
                ]),
            ])->title('Email selected users')->applyButton('Send'),

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
            'app_role' => $userData['roles'],
            'is_visible' => true,
        ]);
        $user->forceFill(['signup_method' => User::SIGNUP_ADMIN])->saveQuietly();
        $user->syncOrchidRoleFromAppRole();

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

    /**
     * Delete a single user from the list row action.
     * The row button calls ->method('remove', ['id' => $user->id]).
     */
    public function remove(Request $request): void
    {
        $user = User::findOrFail($request->get('id'));

        if ((int) $user->id === (int) auth()->id()) {
            Toast::warning(__('You cannot delete your own account.'));
            return;
        }

        $user->delete();
        Toast::info(__('User was removed.'));
    }

    /**
     * Delete all users selected via the list checkboxes (name="users[]").
     */
    public function bulkDelete(Request $request): void
    {
        $ids = array_filter((array) $request->get('users', []));

        // Never delete the currently authenticated admin.
        $ids = array_filter($ids, fn ($id) => (int) $id !== (int) auth()->id());

        if (empty($ids)) {
            Toast::warning(__('No users selected.'));
            return;
        }

        // Delete per-model so foreign-key cascades run just like the single
        // delete on the detail screen.
        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $user) {
            $user->delete();
        }

        Toast::info(__(':count user(s) were removed.', ['count' => $users->count()]));
    }

    /**
     * Data for the filter bar view: current values, chips and role counts.
     */
    private function filterBarData(Request $request): array
    {
        $selects = [
            'profile'    => ['label' => 'Profile', 'options' => ['complete' => 'Profile complete', 'incomplete' => 'Needs profile']],
            'platform'   => ['label' => 'Platform', 'options' => User::PLATFORMS + ['unknown' => 'Unknown']],
            'signup'     => ['label' => 'Sign-up method', 'options' => User::signupMethodOptions() + ['unknown' => 'Unknown (before tracking)']],
            'source'     => ['label' => 'Source', 'options' => User::createdSourceOptions()],
            'visibility' => ['label' => 'Visibility', 'options' => ['visible' => 'Visible in app', 'hidden' => 'Hidden from app']],
        ];

        $roles = [
            User::APP_ROLE_VISITOR   => 'Visitors',
            User::APP_ROLE_EXHIBITOR => 'Exhibitors',
            User::APP_ROLE_ADMIN     => 'Admins',
        ];

        $counts = User::query()->selectRaw('app_role, count(*) as total')->groupBy('app_role')->pluck('total', 'app_role');

        $chips = [];
        foreach ($selects as $key => $select) {
            $value = $request->query($key);
            if (filled($value) && isset($select['options'][$value])) {
                $chips[] = ['key' => $key, 'label' => $select['label'] . ': ' . $select['options'][$value]];
            }
        }

        return [
            'search'      => (string) $request->query('search', ''),
            'role'        => (string) $request->query('role', ''),
            'roles'       => $roles,
            'roleCounts'  => $counts->all(),
            'total'       => (int) $counts->sum(),
            'selects'     => $selects,
            'chips'       => $chips,
            'activeCount' => count($chips),
        ];
    }

    private function bulkRoleOptions(): array
    {
        return [
            User::APP_ROLE_VISITOR   => 'Visitor',
            User::APP_ROLE_EXHIBITOR => 'Exhibitor',
            User::APP_ROLE_ADMIN     => 'Administrator',
        ];
    }

    /**
     * Ids selected in the list: ticked checkboxes (users[]) for buttons that
     * submit the main form, or the hidden selected_users field of a modal.
     */
    private function selectedIds(Request $request, bool $excludeSelf = false): array
    {
        $ids = array_merge(
            (array) $request->input('users', []),
            explode(',', (string) $request->input('selected_users', '')),
        );

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($excludeSelf) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== (int) auth()->id()));
        }

        return $ids;
    }

    public function bulkSetVisibility(Request $request): void
    {
        $ids = $this->selectedIds($request);
        if ($ids === []) {
            Toast::warning(__('No users selected.'));
            return;
        }

        $visible = $request->boolean('visible');
        $updated = 0;
        foreach (User::whereIn('id', $ids)->get() as $user) {
            $user->is_visible = $visible;
            $user->save();
            $updated++;
        }

        Toast::success($visible
            ? __(':count user(s) are now visible in the app.', ['count' => $updated])
            : __(':count user(s) are now hidden from the app.', ['count' => $updated]));
    }

    public function bulkAssignRole(Request $request): void
    {
        $request->validate(['role' => ['required', Rule::in(array_keys($this->bulkRoleOptions()))]]);

        // Never change your own role here: it could lock you out of the admin.
        $ids = $this->selectedIds($request, excludeSelf: true);
        if ($ids === []) {
            Toast::warning(__('No users selected (your own account is skipped).'));
            return;
        }

        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $user) {
            $user->app_role = $request->input('role');
            $user->save();
            $user->syncOrchidRoleFromAppRole();
        }

        Toast::success(__('Role changed to :role for :count user(s).', [
            'role'  => $this->bulkRoleOptions()[$request->input('role')],
            'count' => $users->count(),
        ]));
    }

    public function bulkAssignCompany(Request $request): void
    {
        $request->validate(['company_id' => ['nullable', 'integer', 'exists:companies,id']]);

        $ids = $this->selectedIds($request);
        if ($ids === []) {
            Toast::warning(__('No users selected.'));
            return;
        }

        $companyId = $request->input('company_id') ?: null;
        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $user) {
            $user->company_id = $companyId;
            $user->save();
        }

        $company = $companyId ? Company::find($companyId)?->name : null;
        Toast::success($company
            ? __(':count user(s) linked to :company.', ['count' => $users->count(), 'company' => $company])
            : __('Company removed from :count user(s).', ['count' => $users->count()]));
    }

    public function bulkSendEmail(Request $request): void
    {
        $data = $request->validate([
            'email_subject' => ['required', 'string', 'max:150'],
            'email_message' => ['required', 'string', 'max:10000'],
        ]);

        $ids = $this->selectedIds($request);
        if ($ids === []) {
            Toast::warning(__('No users selected.'));
            return;
        }

        if (count($ids) > 500) {
            Toast::warning(__('Select at most 500 users per email.'));
            return;
        }

        $recipients = User::whereIn('id', $ids)->whereNotNull('email')->pluck('email')->unique();
        $subject = $data['email_subject'];
        $message = $data['email_message'];

        // One queued job per email, sent by the queue worker (cron), so the page
        // does not wait and one bad address does not stop the others.
        foreach ($recipients as $email) {
            dispatch(function () use ($email, $subject, $message) {
                Mail::raw($message, fn ($mail) => $mail->to($email)->subject($subject));
            });
        }

        Toast::success(__('Email queued for :count user(s). It is sent within a few minutes.', ['count' => $recipients->count()]));
    }

    /**
     * CSV of the users matching the current search and filters.
     */
    public function exportUsers(Request $request): StreamedResponse
    {
        $users = User::with('company:id,name')
            ->filters(UserFiltersLayout::class)
            ->orderByDesc('id')
            ->limit(20000)
            ->get();

        $filename = 'users-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($users) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'First name', 'Last name', 'Email', 'Phone', 'Job title', 'Company', 'Role', 'Profile', 'Sign-up method', 'Platform', 'Visible', 'Joined']);

            foreach ($users as $user) {
                fputcsv($out, [
                    $user->id,
                    $user->name,
                    $user->last_name,
                    $user->email,
                    $user->phone,
                    $user->job_title,
                    $user->company->name ?? $user->company_name,
                    $user->role,
                    $user->profileCompletionLabel(),
                    $user->signupMethodLabel(),
                    $user->platformLabel(),
                    $user->is_visible ? 'Yes' : 'No',
                    $user->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
