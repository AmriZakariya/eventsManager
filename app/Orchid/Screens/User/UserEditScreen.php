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

        return [
            'user'       => $user,
            'permission' => $user->statusOfPermissions(),
        ];
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

            // 1. IDENTITY & CONTACT (Standard Info)
            Layout::block(Layout::rows([
                // Avatar Cropper
                Cropper::make('user.avatar')
                    ->title('Profile Picture')
                    ->targetRelativeUrl()
                    ->width(500)
                    ->height(500),

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
                        ->options([
                            'MA' => 'Morocco',
                            'FR' => 'France',
                            'US' => 'United States',
                            'UK' => 'United Kingdom',
                            'ES' => 'Spain',
                            'DE' => 'Germany',
                            'IT' => 'Italy',
                            'AE' => 'United Arab Emirates',
                            'SA' => 'Saudi Arabia',
                        ])
                        ->empty('Select Country'),

                    Input::make('user.city')
                        ->title('City')
                        ->placeholder('City Name'),
                ]),
            ]))
                ->title(__('Identity & Contact'))
                ->description(__('Basic profile information and contact details.'))
                ->commands(
                    Button::make(__('Save Changes'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->method('save')
                ),

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
                ->description(__('Employment details and categorization.'))
                ->commands(
                    Button::make(__('Save Changes'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->method('save')
                ),

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

        // Handle Password Update
        $user->when($request->filled('user.password'), function (Builder $builder) use ($request) {
            $builder->getModel()->password = Hash::make($request->input('user.password'));
        });

        // Save User Data (New fields are handled automatically via $fillable in User model)
        $userData = $request->collect('user')->except(['password', 'permissions', 'roles'])->toArray();

        $user->fill($userData)
            ->forceFill(['permissions' => $permissions])
            ->save();

        // Sync Roles
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
