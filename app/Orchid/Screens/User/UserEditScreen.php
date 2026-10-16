<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Orchid\Layouts\Role\RolePermissionLayout;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserPasswordLayout;
use App\Orchid\Layouts\User\UserRoleLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Orchid\Access\Impersonation;
use App\Models\User;
use App\Models\PushNotificationLog;
use Orchid\Screen\TD;
use App\Models\Company;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Switcher;

class UserEditScreen extends Screen
{
    /**
     * @var User
     */
    public $user;

    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(User $user): iterable
    {
        $user->load(['roles']);

        // Never pre-fill the password field with the stored hash. Otherwise it
        // shows the existing value and gets re-hashed on save, which breaks
        // login. The field must start empty ("Leave empty to keep current").
        $user->password = '';

        return [
            'user'       => $user,
            'permission' => $user->statusOfPermissions(),
            'avatar_url' => $user->exists && $user->avatar ? $user->adminAvatarUrl() : null,
            'push_logs'  => $user->exists
                ? PushNotificationLog::where('user_id', $user->id)->latest('created_at')->limit(10)->get()
                : collect(),
        ];
    }

    /**
     * Same country codes the mobile app stores. A stored value outside the list
     * (legacy text, "N/A") is kept as an option so it shows and is not wiped on save.
     */
    private function countryOptions(): array
    {
        $options = config('countries', []);
        asort($options);

        $current = $this->user?->country;

        if (filled($current) && ! array_key_exists($current, $options)) {
            $label = $current === 'N/A' ? 'Not provided yet (N/A)' : $current . ' (not in list)';
            $options = [$current => $label] + $options;
        }

        return $options;
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->user->exists ? 'Edit User' : 'Create User';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Manage user profile, professional details, and access rights.';
    }

    public function permission(): ?iterable
    {
        return [
            'platform.systems.users',
        ];
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        return [
            Button::make(__('Impersonate user'))
                ->icon('bg.box-arrow-in-right')
                ->confirm(__('You can revert to your original state by logging out.'))
                ->method('loginAs')
                ->canSee($this->user->exists && $this->user->id !== \request()->user()->id),

            Button::make(__('Remove'))
                ->icon('bs.trash3')
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted.'))
                ->method('remove')
                ->canSee($this->user->exists),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    /**
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [

            // 0. CURRENT PROFILE PICTURE PREVIEW (read-only)
            Layout::view('admin.user.avatar-preview'),

            // 1. IDENTITY & CONTACT (Standard Info)
            Layout::block(Layout::rows([
                // Avatar Cropper
                Cropper::make('user.avatar')
                    ->title('Profile Picture')
                    ->targetRelativeUrl(),

                // Group: Names
                Group::make([
                    Input::make('user.name')
                        ->type('text')
                        ->title('First Name')
                        ->required()
                        ->placeholder('First Name'),

                    Input::make('user.last_name')
                        ->type('text')
                        ->title('Last Name')
                        ->required()
                        ->placeholder('Last Name'),
                ]),

                Input::make('user.email')
                    ->type('email')
                    ->title('Email')
                    ->required()
                    ->placeholder('Email Address'),

                // Group: Contact
                Group::make([
                    Input::make('user.phone')
                        ->type('tel')
                        ->title('Phone Number')
                        ->placeholder('+1 234 567 890'),

                    Input::make('user.linkedin_url')
                        ->type('url')
                        ->title('LinkedIn URL')
                        ->placeholder('https://linkedin.com/in/...'),
                ]),

                // Group: Location
                Group::make([
                    Select::make('user.country')
                        ->title('Country')
                        ->options($this->countryOptions())
                        ->empty('Select Country'),

                    Input::make('user.city')
                        ->title('City')
                        ->placeholder('City Name'),
                ]),
            ]))
                ->title(__('Identity & Contact'))
                ->description(__('Basic profile information and contact details.')),

            // 2. PROFESSIONAL DETAILS (Exhibitor/Visitor Context)
            Layout::block(Layout::rows([
                Group::make([
                    Input::make('user.job_title')
                        ->title('Job Title')
                        ->placeholder('e.g. Sales Manager'),

                    Select::make('user.company_sector')
                        ->title('Sector')
                        ->options([
                            'Cleaning Chemicals & Agents' => 'Cleaning Chemicals & Agents',
                            'Washroom Hygiene & Waste Disposal' => 'Washroom Hygiene & Waste Disposal',
                            'Dry-cleaning & Laundry' => 'Dry-cleaning & Laundry',
                            'Cleaning Equipment Machinery' => 'Cleaning Equipment Machinery',
                            'Car Care Systems & Accessories' => 'Car Care Systems & Accessories',
                            'Packaging Materials & Processing' => 'Packaging Materials & Processing',
                            'Climbing Aid & Protection' => 'Climbing Aid & Protection',
                            'Municipal Solutions' => 'Municipal Solutions',
                            'Waste Management' => 'Waste Management',
                            'Other' => 'Other',
                        ])
                        ->empty('Select Sector'),
                ]),

                // Logic: Exhibitor vs Visitor
                Group::make([
                    Relation::make('user.company_id')
                        ->title('Assigned Company (Exhibitors)')
                        ->fromModel(Company::class, 'name')
                        ->empty('No Company Assigned')
                        ->help('Select a company if this user is an Exhibitor Team Member.'),

                    Input::make('user.company_name')
                        ->title('Company Name (Visitors)')
                        ->placeholder('Enter company name')
                        ->help('For visitors who do not belong to a registered exhibitor company.'),
                ]),

                // System Fields
                Group::make([
                    Select::make('user.app_role')
                        ->title('App Role')
                        ->options(User::appRoleOptions())
                        ->required()
                        ->help('Used by the mobile app, API, signup flow, and app sections.'),

                    Input::make('user.badge_code')
                        ->title('Badge Code')
                        ->disabled() // Usually auto-generated, keep read-only
                        ->help('Auto-generated unique ID'),

                    Switcher::make('user.is_visible')
                        ->title('Public Profile')
                        ->sendTrueOrFalse()
                        ->help('Show this user in the public networking list?'),
                ]),
            ]))
                ->title(__('Professional Info'))
                ->description(__('Employment details and categorization.')),

            // Push notifications: device token and the latest attempts.
            Layout::block(Layout::table('push_logs', [
                TD::make('created_at', 'Time')
                    ->render(fn (PushNotificationLog $log) => $log->created_at?->format('Y-m-d H:i')),
                TD::make('notification', 'Type'),
                TD::make('status', 'Status')
                    ->render(fn (PushNotificationLog $log) => '<span class="badge bg-'
                        . match ($log->status) { 'sent' => 'success', 'failed' => 'danger', default => 'secondary' }
                        . '">' . e($log->status) . '</span>'),
                TD::make('error_code', 'Reason')
                    ->render(fn (PushNotificationLog $log) => $log->error_code ? '<code>' . e($log->error_code) . '</code>' : ''),
            ]))
                ->title(__('Push Notifications'))
                ->description($this->user->fcm_token
                    ? __('Device registered for push notifications. Latest 10 attempts.')
                    : __('No device token: this user cannot receive push notifications until they open the app and allow notifications.'))
                ->canSee($this->user->exists),

            // 3. AUTHENTICATION (Password, Roles)
            Layout::block(UserPasswordLayout::class)
                ->title(__('Security'))
                ->description(__('Manage password and security settings.')),

            Layout::block(UserRoleLayout::class)
                ->title(__('Roles'))
                ->description(__('Assign roles to define user permissions.')),

            Layout::block(RolePermissionLayout::class)
                ->title(__('Permissions'))
                ->description(__('Granular permission overrides.')),
        ];
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(User $user, Request $request)
    {
        $request->validate([
            'user.email' => [
                'required',
                Rule::unique(User::class, 'email')->ignore($user),
            ],
            'user.name' => 'required|string',
            'user.last_name' => 'required|string',
        ]);

        $permissions = collect($request->get('permissions'))
            ->map(fn ($value, $key) => [base64_decode($key) => $value])
            ->collapse()
            ->toArray();

        // Handle Password Update — set directly on the model so it actually
        // persists on save(). ($user->when(...) operated on a throwaway query
        // model, so the new password was never saved.)
        if ($request->filled('user.password')) {
            $user->password = Hash::make($request->input('user.password'));
        }

        // Save User Data (New fields are handled automatically via $fillable in User model)
        $userData = $request->collect('user')->except(['password', 'permissions', 'roles'])->toArray();

        $user->fill($userData)
            ->forceFill(['permissions' => $permissions])
            ->save();

        // Sync Orchid roles separately from the app role field.
        $user->replaceRoles($request->input('user.roles'));

        Toast::info(__('User details saved successfully.'));

        return redirect()->route('platform.systems.users');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(User $user)
    {
        $user->delete();
        Toast::info(__('User was removed'));
        return redirect()->route('platform.systems.users');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginAs(User $user)
    {
        Impersonation::loginAs($user);
        Toast::info(__('You are now impersonating this user'));
        return redirect()->route(config('platform.index'));
    }
}
