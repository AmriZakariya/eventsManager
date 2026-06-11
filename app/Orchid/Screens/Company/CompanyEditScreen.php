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
        $company->hydrateTranslationInputs(['name', 'category', 'description', 'address']);

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
                            ->targetRelativeUrl(),

                        Input::make('company.passcode')
                            ->title('Access Code (Passcode)')
                            ->placeholder('Ex: HYGIE-A1B2')
                            ->help('Secret code for employees to register as exhibitors on the app.'),

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
                    ]),
                ]),

                'Localized Content' => Layout::tabs([
                    'English' => Layout::rows([
                        Input::make('company.name_translations.en')
                            ->title('Company Name')
                            ->placeholder('e.g. Acme Corp')
                            ->value($this->company->translationInput('name')['en'] ?? null)
                            ->required(),

                        Input::make('company.category_translations.en')
                            ->title('Industry Category')
                            ->placeholder('e.g. Technology, Healthcare')
                            ->value($this->company->translationInput('category')['en'] ?? null)
                            ->help('Use commas to add multiple domains. Each one will appear as a separate category button in the app.'),

                        Input::make('company.address_translations.en')
                            ->title('Full Address')
                            ->value($this->company->translationInput('address')['en'] ?? null)
                            ->placeholder('123 Business Blvd, City'),

                        TextArea::make('company.description_translations.en')
                            ->title('About the Company')
                            ->value($this->company->plainText($this->company->translationInput('description')['en'] ?? null))
                            ->rows(5)
                            ->placeholder('Short bio...'),
                    ]),

                    'Français' => Layout::rows([
                        Input::make('company.name_translations.fr')
                            ->title('Nom de l’entreprise')
                            ->value($this->company->translationInput('name')['fr'] ?? null),

                        Input::make('company.category_translations.fr')
                            ->title('Catégorie d’activité')
                            ->value($this->company->translationInput('category')['fr'] ?? null)
                            ->placeholder('Ex: Technologie, Santé')
                            ->help('Séparez plusieurs domaines par des virgules.'),

                        Input::make('company.address_translations.fr')
                            ->title('Adresse complète')
                            ->value($this->company->translationInput('address')['fr'] ?? null),

                        TextArea::make('company.description_translations.fr')
                            ->title('À propos de l’entreprise')
                            ->value($this->company->plainText($this->company->translationInput('description')['fr'] ?? null))
                            ->rows(5),
                    ]),

                    'العربية' => Layout::rows([
                        Input::make('company.name_translations.ar')
                            ->title('اسم الشركة')
                            ->value($this->company->translationInput('name')['ar'] ?? null),

                        Input::make('company.category_translations.ar')
                            ->title('قطاع الشركة')
                            ->value($this->company->translationInput('category')['ar'] ?? null)
                            ->placeholder('مثال: التكنولوجيا، الصحة')
                            ->help('افصل بين المجالات المتعددة بفواصل.'),

                        Input::make('company.address_translations.ar')
                            ->title('العنوان الكامل')
                            ->value($this->company->translationInput('address')['ar'] ?? null),

                        TextArea::make('company.description_translations.ar')
                            ->title('نبذة عن الشركة')
                            ->value($this->company->plainText($this->company->translationInput('description')['ar'] ?? null))
                            ->rows(5),
                    ]),
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
            'company.name_translations.en' => 'required|max:255',
            'company.name_translations.fr' => 'nullable|max:255',
            'company.name_translations.ar' => 'nullable|max:255',
            'company.category_translations.*' => 'nullable|max:255',
            'company.address_translations.*' => 'nullable|max:255',
            'company.description_translations.*' => 'nullable|string',
            'company.passcode' => 'nullable|string|max:255',
            'catalog_upload' => 'nullable|file|mimes:pdf|max:10240', // Max 10MB
            'company.email' => 'nullable|email',
            'company.type' => 'nullable|array', // Ensure array validation
        ]);

        $data = $request->get('company');
        $data = $company->prepareTranslatedData($data, ['name', 'category', 'description', 'address'], ['description']);

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
