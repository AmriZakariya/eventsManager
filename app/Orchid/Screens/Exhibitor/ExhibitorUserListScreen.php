<?php

namespace App\Orchid\Screens\Exhibitor;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Group;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class ExhibitorUserListScreen extends Screen
{
    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Exhibitor Team';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return 'Manage exhibitor representatives and their company assignments.';
    }

    /**
     * Query data.
     */
    public function query(Request $request): iterable
    {
        // 1. Base Query: Get Users with 'exhibitor' role
        $query = User::query()
            ->with(['company', 'roles'])
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'exhibitor');
            });

        // 2. Filter by Search Term (Name, Email, Job Title)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        // 3. Filter by Company
        if ($companyId = $request->get('company_id')) {
            $query->where('company_id', $companyId);
        }

        // 4. Sorting
        // Manual sorting to prevent Model configuration errors
        $sortCol = $request->get('sort', 'created_at');
        $sortDir = $request->get('order', 'desc');

        // Simple security check for sort column
        if (in_array($sortCol, ['name', 'email', 'created_at', 'job_title'])) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // 5. Metrics Calculation
        $total    = (clone $query)->count();
        $verified = (clone $query)->whereNotNull('email_verified_at')->count();

        return [
            'exhibitors' => $query->paginate(15),
            'metrics'    => [
                'total'    => $total,
                'verified' => $verified,
                'pending'  => $total - $verified,
            ],
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): array
    {
        return [
            // Export Button
            Button::make('Export CSV')
                ->icon('bs.cloud-download')
                ->method('export')
                ->rawClick(),

            // Add Button
            Link::make('Add Member')
                ->icon('bs.person-plus')
                ->type(Color::PRIMARY)
                ->route('platform.systems.users.create'),
        ];
    }

    /**
     * Views.
     */
    public function layout(): iterable
    {
        return [
            // 1. TOP METRICS
            Layout::metrics([
                'Total Members'   => 'metrics.total',
                'Verified Accounts' => 'metrics.verified',
                'Pending Review'    => 'metrics.pending',
            ]),

            // 2. FILTERS ROW
            Layout::rows([
                Group::make([
                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Name, Email or Job...')
                        ->value(request('search'))
                        ->help('Find users instantly'),

                    Select::make('company_id')
                        ->title('Filter by Company')
                        ->fromModel(Company::class, 'name')
                        ->empty('All Companies')
                        ->value(request('company_id'))
                        ->help('Show users from specific company'),

                    Button::make('Filter')
                        ->class('btn btn-primary')
                        ->style('margin-top: 30px;') // Align with inputs
                        ->icon('bs.funnel')
                        ->method('applyFilters'),

                    Button::make('Clear')
                        ->class('btn btn-outline-secondary')
                        ->style('margin-top: 30px;')
                        ->icon('bs.x-lg')
                        ->route('platform.exhibitors.team'), // Resets URL
                ])->autoWidth(),
            ]),

            // 3. DATA TABLE
            Layout::table('exhibitors', [

                // USER PROFILE COLUMN
                TD::make('name', 'User')
                    ->sort()
                    ->width('300px')
                    ->render(function (User $user) {
                        $avatar = $user->avatar
                            ? "<img src='{$user->avatar}' class='rounded-circle me-2 border' width='40' height='40' style='object-fit:cover;'>"
                            : "<div class='rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2' style='width:40px;height:40px;font-weight:bold;'>" . substr($user->name, 0, 1) . "</div>";

                        return "<div class='d-flex align-items-center'>
                                    {$avatar}
                                    <div class='lh-sm'>
                                        <div class='fw-bold text-dark'>{$user->name} {$user->last_name}</div>
                                        <div class='small text-muted'>{$user->email}</div>
                                    </div>
                                </div>";
                    }),

                // COMPANY COLUMN
                TD::make('company_id', 'Company')
                    ->render(fn(User $user) => $user->company
                        ? "<span class='badge bg-light text-dark border'><i class='bs.building me-1'></i>{$user->company->name}</span>"
                        : "<span class='text-muted fst-italic'>No Company</span>"
                    ),

                // JOB TITLE
                TD::make('job_title', 'Job Title')
                    ->sort()
                    ->render(fn($user) => $user->job_title ?? '-'),

                // STATUS
                TD::make('email_verified_at', 'Status')
                    ->sort()
                    ->alignCenter()
                    ->render(fn($user) => $user->email_verified_at
                        ? '<span class="badge bg-success">Verified</span>'
                        : '<span class="badge bg-warning text-dark">Pending</span>'
                    ),

                // CREATED AT
                TD::make('created_at', 'Joined')
                    ->sort()
                    ->alignRight()
                    ->render(fn($user) => $user->created_at->format('M d, Y')),

                // ACTIONS
                TD::make('Actions')
                    ->alignRight()
                    ->width('100px')
                    ->render(fn(User $user) => DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            Link::make('Edit Profile')
                                ->route('platform.systems.users.edit', $user->id)
                                ->icon('bs.pencil'),

                            Button::make('Delete User')
                                ->method('remove', ['id' => $user->id])
                                ->confirm('Are you sure? This action cannot be undone.')
                                ->icon('bs.trash3')
                                ->class('text-danger'),
                        ])),
            ]),
        ];
    }

    /**
     * Logic: Apply Filters (Reloads page with GET params)
     */
    public function applyFilters(Request $request)
    {
        return redirect()->route('platform.exhibitors.team', [
            'search' => $request->get('search'),
            'company_id' => $request->get('company_id'),
        ]);
    }

    /**
     * Logic: Delete User
     */
    public function remove(Request $request)
    {
        User::findOrFail($request->get('id'))->delete();
        Toast::info('User removed successfully.');
    }

    /**
     * Logic: Export to CSV
     */
    public function export(Request $request)
    {
        // Re-run the query logic to get full dataset for export
        $query = User::with('company')
            ->whereHas('roles', fn($q) => $q->where('slug', 'exhibitor'));

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($companyId = $request->get('company_id')) {
            $query->where('company_id', $companyId);
        }

        $users = $query->get();
        $filename = 'exhibitors_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['ID', 'Name', 'Email', 'Company', 'Job Title', 'Status', 'Joined Date']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name . ' ' . $user->last_name,
                    $user->email,
                    $user->company?->name ?? 'N/A',
                    $user->job_title ?? '',
                    $user->email_verified_at ? 'Verified' : 'Pending',
                    $user->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, $filename);
    }
}
