<?php

namespace App\Orchid\Screens\Company;

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

class CompanyListScreen extends Screen
{
    public function name(): ?string
    {
        return 'Exhibitors & Partners';
    }

    public function description(): ?string
    {
        return 'Directory of all participating companies, sponsors, and partners.';
    }

    /**
     * Query data.
     */
    public function query(Request $request): iterable
    {
        $query = Company::query();

        // 1. Keyword Search (Name, Email, Booth)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('booth_number', 'like', "%{$search}%");
            });
        }

        // 2. Filter by Partner Type (JSON Column)
        if ($type = $request->get('type')) {
            $query->whereJsonContains('type', $type);
        }

        // 3. Filter by Country
        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        // 4. Filter by Category
        if ($category = $request->get('category')) {
            $query->where('category', 'like', "%{$category}%");
        }

        return [
            // Get unique countries for the filter dropdown
            'countries' => Company::distinct()->whereNotNull('country')->pluck('country', 'country')->toArray(),

            'companies' => $query->latest()->paginate(15),

            'metrics' => [
                'total'    => Company::count(),
                'sponsors' => Company::whereJsonContains('type', 'SPONSOR')->count(),
                'active'   => Company::where('is_active', 1)->count(),
            ]
        ];
    }

    /**
     * Button commands.
     */
    public function commandBar(): array
    {
        return [
            Link::make('Add Company')
                ->icon('bs.plus-lg')
                ->type(Color::PRIMARY)
                ->route('platform.companies.create'),
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
                'Total Companies' => 'metrics.total',
                'Active Sponsors' => 'metrics.sponsors',
                'Live on App'     => 'metrics.active',
            ]),

            // 2. SEARCH & FILTER BAR
            Layout::rows([
                Group::make([
                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Name, Booth or Email...')
                        ->value(request('search'))
                        ->icon('bs.search'),

                    Select::make('type')
                        ->title('Partner Type')
                        ->options([
                            'EXHIBITOR'             => 'Exhibitor',
                            'SPONSOR'               => 'Sponsor',
                            'INSTITUTIONAL_PARTNER' => 'Institutional Partner',
                            'MEDIA_PARTNER'         => 'Media Partner',
                        ])
                        ->empty('All Types')
                        ->value(request('type')),

                    Select::make('country')
                        ->title('Country')
                        ->options($this->query(request())['countries']) // Use data from query
                        ->empty('All Countries')
                        ->value(request('country')),

                    Input::make('category')
                        ->title('Category')
                        ->placeholder('e.g. Technology')
                        ->value(request('category')),
                ]),

                Group::make([
                    Button::make('Apply Filters')
                        ->icon('bs.funnel')
                        ->type(Color::PRIMARY)
                        ->method('applyFilters'),

                    Link::make('Reset')
                        ->icon('bs.x-circle')
                        ->route('platform.companies.list'), // Clears URL params
                ])->autoWidth(),
            ]),

            // 3. DATA TABLE
            Layout::table('companies', [

                // COLUMN: LOGO & IDENTITY
                TD::make('name', 'Company')
                    ->sort()
                    ->width('300px')
                    ->render(function (Company $company) {
                        $logo = $company->logo
                            ? "<img src='".asset($company->logo)."' class='rounded border bg-white me-3' width='48' height='48' style='object-fit:contain;'>"
                            : "<div class='rounded bg-light text-secondary d-flex align-items-center justify-content-center border me-3' style='width:48px; height:48px; font-weight:bold; font-size:1.2em;'>".substr($company->name, 0, 1)."</div>";

                        return "<div class='d-flex align-items-center py-2'>
                                    {$logo}
                                    <div class='lh-sm'>
                                        <div class='fw-bold text-dark' style='font-size:1.05em;'>{$company->name}</div>
                                        <div class='small text-muted'>{$company->email}</div>
                                    </div>
                                </div>";
                    }),

                // COLUMN: ROLES / BADGES
                TD::make('type', 'Roles')
                    ->width('200px')
                    ->render(function (Company $company) {
                        if (empty($company->type)) return '<span class="text-muted small fst-italic">No Role</span>';

                        $html = '<div class="d-flex flex-wrap gap-1">';
                        foreach ($company->type as $type) {
                            $style = match($type) {
                                'SPONSOR'               => 'background-color:#FFF7ED; color:#C2410C; border:1px solid #FFEDD5;', // Orange
                                'INSTITUTIONAL_PARTNER' => 'background-color:#ECFDF5; color:#047857; border:1px solid #D1FAE5;', // Green
                                'MEDIA_PARTNER'         => 'background-color:#FDF4FF; color:#C026D3; border:1px solid #FAE8FF;', // Purple
                                'EXHIBITOR'             => 'background-color:#EFF6FF; color:#1D4ED8; border:1px solid #DBEAFE;', // Blue
                                default                 => 'background-color:#F3F4F6; color:#374151; border:1px solid #E5E7EB;', // Gray
                            };

                            $label = ucwords(strtolower(str_replace('_', ' ', $type)));
                            $html .= "<span style='{$style} font-size:0.75rem; padding: 2px 8px; border-radius:12px; font-weight:600;'>{$label}</span>";
                        }
                        $html .= '</div>';
                        return $html;
                    }),

                // COLUMN: LOCATION
                TD::make('location', 'Location')
                    ->render(fn(Company $c) =>
                        "<div class='lh-sm'>
                            <div class='text-dark'><i class='bs.geo-alt me-1 text-muted'></i>" . ($c->country ?? '-') . "</div>
                            <div class='small text-muted mt-1'><i class='bs.shop me-1'></i>Booth: " . ($c->booth_number ?? 'N/A') . "</div>
                        </div>"
                    ),

                // COLUMN: STATUS
                TD::make('status', 'Status')
                    ->width('100px')
                    ->alignCenter()
                    ->render(fn (Company $c) =>
                        "<div class='d-flex flex-column align-items-center gap-1'>" .
                        ($c->is_active
                            ? '<span class="badge bg-success" style="width:100%;">Active</span>'
                            : '<span class="badge bg-secondary" style="width:100%;">Hidden</span>') .
                        ($c->is_featured
                            ? '<span class="badge bg-warning text-dark" style="width:100%;">Featured</span>'
                            : '') .
                        "</div>"
                    ),

                // COLUMN: ACTIONS
                TD::make(__('Actions'))
                    ->alignRight()
                    ->width('70px')
                    ->render(fn (Company $company) => DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            Link::make('Edit Details')
                                ->route('platform.companies.edit', $company->id)
                                ->icon('bs.pencil'),

                            Button::make('Delete')
                                ->icon('bs.trash3')
                                ->confirm('Are you sure you want to delete this company? This action cannot be undone.')
                                ->method('remove', ['id' => $company->id])
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
        return redirect()->route('platform.companies.list', [
            'search'   => $request->get('search'),
            'type'     => $request->get('type'),
            'country'  => $request->get('country'),
            'category' => $request->get('category'),
        ]);
    }

    /**
     * Logic: Delete
     */
    public function remove(Request $request)
    {
        Company::findOrFail($request->get('id'))->delete();
        Toast::info('Company deleted successfully.');
    }
}
