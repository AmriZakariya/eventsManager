<?php

namespace App\Orchid\Screens\Company;

use App\Models\Company;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Group;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class CompanyListScreen extends Screen
{
    public function name(): ?string
    {
        return 'Exhibitors';
    }

    public function description(): ?string
    {
        return 'Manage participating companies and booths.';
    }

    public function query(Request $request): iterable
    {
        // 1. Initialize Query
        $query = Company::query();

        // 2. Manual Filtering (Fixes the "Target class does not exist" error)
        // This connects the UI Input filters to the Database Query
        if ($name = $request->input('filter.name')) {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($booth = $request->input('filter.booth_number')) {
            $query->where('booth_number', 'like', "%{$booth}%");
        }
        if ($country = $request->input('filter.country')) {
            $query->where('country', 'like', "%{$country}%");
        }

        // 3. Sorting & Pagination
        return [
            'metrics' => [
                'total'    => Company::count(),
                'active'   => Company::where('is_active', true)->count(),
                'featured' => Company::where('is_featured', true)->count(),
            ],
            'companies' => $query->defaultSort('created_at', 'desc')->paginate(15),
        ];
    }

    public function commandBar(): array
    {
        return [
            // Download Template Button
            Button::make('Download CSV Template')
                ->icon('bs.file-earmark-arrow-down')
                ->method('downloadTemplate')
                ->rawClick()
                ->novalidate(),

            // Import Button
            ModalToggle::make('Import Data')
                ->modal('importModal')
                ->method('importFile')
                ->icon('bs.upload')
                ->type(Color::SUCCESS),

            // Add Button
            Link::make('Add Exhibitor')
                ->icon('bs.plus-lg')
                ->type(Color::PRIMARY)
                ->route('platform.companies.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            // Top Metrics
            Layout::metrics([
                'Total Exhibitors' => 'metrics.total',
                'Active Profiles'  => 'metrics.active',
                'Featured'         => 'metrics.featured',
            ]),

            // The Table
            Layout::table('companies', [

                // Name Column with Search Filter
                TD::make('name', 'Company')
                    ->sort()
                    ->filter(Input::make()->placeholder('Search Company...'))
                    ->width('250px')
                    ->render(function (Company $company) {
                        // Visual Avatar Logic
                        $avatar = $company->logo
                            ? "<img src='".asset($company->logo)."' class='rounded border bg-white' width='40' height='40' style='object-fit:contain; margin-right:10px;'>"
                            : "<div class='rounded bg-secondary text-white d-flex align-items-center justify-content-center' style='width:40px; height:40px; margin-right:10px; font-weight:bold;'>".substr($company->name, 0, 1)."</div>";

                        return "<div class='d-flex align-items-center'>
                                    $avatar
                                    <div>
                                        <div class='fw-bold text-dark'>{$company->name}</div>
                                        <div class='small text-muted'>{$company->email}</div>
                                    </div>
                                </div>";
                    }),

                // Booth Column with Search Filter
                TD::make('booth_number', 'Booth')
                    ->sort()
                    ->filter(Input::make()->placeholder('e.g. A10'))
                    ->render(fn($c) =>
                        "<div><span class='badge bg-light text-dark border'>{$c->booth_number}</span></div>" .
                        "<div class='small text-muted mt-1'>" . ($c->category ?? 'General') . "</div>"
                    ),

                // Country Column with Search Filter
                TD::make('country', 'Location')
                    ->sort()
                    ->filter(Input::make()->placeholder('Search Country...'))
                    ->render(fn($c) => $c->country ?? '-'),

                // Status Column
                TD::make('status', 'Status')
                    ->sort()
                    ->render(fn (Company $c) =>
                        ($c->is_active
                            ? '<span class="badge bg-success me-1">Active</span>'
                            : '<span class="badge bg-danger me-1">Hidden</span>') .
                        ($c->is_featured
                            ? '<span class="badge bg-warning text-dark">Featured</span>'
                            : '')
                    ),

                // Actions Column
                TD::make(__('Actions'))
                    ->alignRight()
                    ->width('100px')
                    ->render(fn (Company $company) => DropDown::make()
                        ->icon('bs.three-dots')
                        ->list([
                            Link::make('Edit Details')
                                ->route('platform.companies.edit', $company->id)
                                ->icon('bs.pencil'),

                            Button::make('Delete')
                                ->icon('bs.trash3')
                                ->confirm('Delete this company permanently?')
                                ->method('remove', ['id' => $company->id])
                                ->class('text-danger'),
                        ])),
            ]),

            // Import Modal Layout
            Layout::modal('importModal', Layout::rows([
                Group::make([
                    Input::make('file')
                        ->type('file')
                        ->title('Upload CSV File')
                        ->accepted('.csv')
                        ->help('Please use the "Download CSV Template" button to get the correct format.')
                        ->required(),
                ]),
            ]))
                ->title('Import Companies')
                ->applyButton('Upload & Process'),
        ];
    }

    // --- LOGIC: Download Template ---
    public function downloadTemplate()
    {
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=exhibitors_template.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['Name', 'Email', 'Booth Number', 'Category', 'Country', 'Website'];
        $example = ['Tech Solutions', 'info@tech.com', 'A-101', 'IT Services', 'USA', 'https://tech.com'];

        $callback = function() use ($columns, $example) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $example); // Example row
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- LOGIC: Import ---
    public function importFile(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), "r");

        // Skip Header
        fgetcsv($handle);

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            // Row Format: Name[0], Email[1], Booth[2], Category[3], Country[4], Website[5]
            if (empty($row[0])) continue; // Skip empty names

            Company::updateOrCreate(
                ['email' => $row[1] ?? null], // Match by email to avoid duplicates
                [
                    'name'         => $row[0],
                    'booth_number' => $row[2] ?? null,
                    'category'     => $row[3] ?? null,
                    'country'      => $row[4] ?? null,
                    'website'      => $row[5] ?? null,
                    'is_active'    => true,
                ]
            );
            $count++;
        }
        fclose($handle);

        Toast::success("Successfully processed $count companies.");
    }

    // --- LOGIC: Delete ---
    public function remove(Request $request)
    {
        Company::findOrFail($request->get('id'))->delete();
        Toast::info('Exhibitor deleted.');
    }
}
