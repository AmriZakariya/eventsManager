<?php

namespace App\Orchid\Screens\Company;

use App\Models\Company;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Code; // For JSON Map
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class CompanyEditScreen extends Screen
{
    public $company;

    public function query(Company $company): array
    {
        return [
            'company' => $company
        ];
    }

    public function name(): ?string
    {
        return $this->company->exists ? 'Edit Exhibitor' : 'Create Exhibitor';
    }

    public function description(): ?string
    {
        return 'Manage company profile, booth assignment, and partnership types.';
    }

    public function commandBar(): array
    {
        return [
            Button::make('Save Changes')
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save'),

            Button::make('Delete')
                ->icon('bs.trash3')
                ->type(Color::DANGER)
                ->method('remove')
                ->canSee($this->company->exists)
                ->confirm('Are you sure you want to delete this company?'),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::tabs([
                // TAB 1: OVERVIEW
                'Company Profile' => Layout::rows([
                    // Row 1: Logo & Basic Info
                    Group::make([
                        Cropper::make('company.logo')
                            ->title('Logo')
                            ->targetRelativeUrl()
                            ->width(300)
                            ->height(300),

                        Input::make('company.name')
                            ->title('Company Name')
                            ->placeholder('e.g. Acme Corp')
                            ->required(),

                        Input::make('catalog_upload') // distinct name to handle manually
                        ->type('file')
                            ->title('Company Catalog (PDF)')
                            ->accepted('.pdf')
                            ->help($this->company->catalog_file
                                ? "Current: <a href='".asset($this->company->catalog_file)."' target='_blank'>View Catalog</a>"
                                : 'Upload a PDF brochure or catalog.'),
                    ]),

                    // Row 2: Categorization
                    Group::make([
                        Select::make('company.type')
                            ->title('Partnership Type(s)')
                            ->multiple() // Allow selecting multiple types
                            ->options(Company::TYPES)
                            ->help('A company can have multiple roles (e.g. Sponsor AND Exhibitor).'),

                        Input::make('company.category')
                            ->title('Industry Category')
                            ->placeholder('e.g. Technology, Healthcare'),
                    ]),

                    TextArea::make('company.description')
                        ->title('About the Company')
                        ->rows(5)
                        ->placeholder('Short bio...'),
                ]),

                // TAB 2: LOGISTICS
                'Location & Booth' => Layout::rows([
                    Group::make([
                        Input::make('company.booth_number')
                            ->title('Booth Number')
                            ->placeholder('e.g. A-101')
                            ->help('Physical location ID.'),

                        Input::make('company.country')
                            ->title('Country')
                            ->placeholder('e.g. Morocco'),
                    ]),

                    Input::make('company.address')
                        ->title('Full Address')
                        ->placeholder('123 Business Blvd, City'),

                    // JSON Input for Map
                    Code::make('company.map_coordinates')
                        ->title('Interactive Map Coordinates (JSON)')
                        ->language('json')
                        ->placeholder('{"x": 100, "y": 200}')
                        ->help('Coordinates for the floor plan.'),
                ]),

                // TAB 3: CONTACT
                'Contact Details' => Layout::rows([
                    Group::make([
                        Input::make('company.email')
                            ->type('email')
                            ->title('Email Address')
                            ->placeholder('contact@company.com'),

                        Input::make('company.phone')
                            ->type('tel')
                            ->title('Phone Number')
                            ->placeholder('+1 234 567 890'),
                    ]),

                    Input::make('company.website_url')
                        ->type('url')
                        ->title('Website URL')
                        ->placeholder('https://...'),
                ]),

                // TAB 4: SETTINGS
                'Visibility' => Layout::rows([
                    CheckBox::make('company.is_active')
                        ->title('Active Status')
                        ->placeholder('Visible in App')
                        ->sendTrueOrFalse(),

                    CheckBox::make('company.is_featured')
                        ->title('Featured')
                        ->placeholder('Highlight on Homepage')
                        ->sendTrueOrFalse(),
                ]),
            ])
        ];
    }

    public function save(Company $company, Request $request)
    {
        $request->validate([
            'company.name' => 'required|max:255',
            'catalog_upload' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
            'company.email' => 'nullable|email',
            'company.type' => 'nullable|array', // Ensure array validation
        ]);

        $data = $request->get('company');

        if ($request->hasFile('catalog_upload')) {
            // Store in storage/app/public/catalogs
            $path = $request->file('catalog_upload')->store('catalogs', 'public');
            $data['catalog_file'] = 'storage/' . $path;
        }

        // Handle Map Coordinates (Ensure valid JSON or null)
        if (!empty($data['map_coordinates']) && is_string($data['map_coordinates'])) {
            $decoded = json_decode($data['map_coordinates'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['map_coordinates'] = $decoded;
            }
        }

        $company->fill($data)->save();

        Toast::info('Company saved successfully.');
        return redirect()->route('platform.companies.list');
    }

    public function remove(Company $company)
    {
        $company->delete();
        Toast::info('Company deleted.');
        return redirect()->route('platform.companies.list');
    }
}
