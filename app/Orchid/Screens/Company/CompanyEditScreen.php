<?php

namespace App\Orchid\Screens\Company;

use App\Models\Company;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Cropper; // Use Cropper for images!
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Illuminate\Http\Request;
use Orchid\Support\Color;

class CompanyEditScreen extends Screen
{
    public $name = 'Manage Exhibitor';
    public $description = 'Edit company details, visibility, and booth info.';
    public $company;

    public function query(Company $company): array
    {
        return ['company' => $company];
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
                // TAB 1: ESSENTIAL INFO
                'General Info' => Layout::rows([
                    Input::make('company.name')
                        ->title('Company Name')
                        ->placeholder('e.g. Acme Corp')
                        ->required(),

                    // Use Cropper for image upload handling
                    Cropper::make('company.logo')
                        ->title('Company Logo')
//                        ->width(300)
//                        ->height(300)
                        ->targetRelativeUrl(),

                    TextArea::make('company.description')
                        ->title('Description')
                        ->rows(5)
                        ->placeholder('Short bio about the company...'),

                    Input::make('company.category')
                        ->title('Industry / Category')
                        ->placeholder('e.g. Technology, Healthcare')
                        ->help('Used for app filters'),
                ]),

                // TAB 2: LOCATION & LOGISTICS
                'Location & Booth' => Layout::rows([
                    Input::make('company.booth_number')
                        ->title('Booth Number')
                        ->placeholder('e.g. A-101'),

                    Input::make('company.country')
                        ->title('Country')
                        ->placeholder('e.g. Morocco')
                        ->help('Used for country filter in the app'),

                    Input::make('company.map_coordinates')
                        ->title('Map Coordinates (JSON)')
                        ->placeholder('{"x": 100, "y": 200}')
                        ->help('X/Y coordinates for the interactive floor plan.'),
                ]),

                // TAB 3: CONTACT DETAILS
                'Contact Info' => Layout::rows([
                    Input::make('company.email')
                        ->type('email')
                        ->title('Email Address'),

                    Input::make('company.phone')
                        ->title('Phone Number'),

                    Input::make('company.website_url')
                        ->title('Website URL')
                        ->placeholder('https://example.com'),

                    Input::make('company.address')
                        ->title('Physical Address'),
                ]),

                // TAB 4: VISIBILITY & SETTINGS
                'Visibility Settings' => Layout::rows([
                    CheckBox::make('company.is_active')
                        ->title('Active Status')
                        ->placeholder('Show in Notification')
                        ->help('If unchecked, this company will be hidden from the API.')
                        ->sendTrueOrFalse(),

                    CheckBox::make('company.is_featured')
                        ->title('Featured Exhibitor')
                        ->placeholder('Mark as Featured')
                        ->help('Featured companies appear at the top of the list.')
                        ->sendTrueOrFalse(),
                ]),
            ])
        ];
    }

    public function save(Company $company, Request $request)
    {
        $request->validate([
            'company.name' => 'required|max:255',
        ]);

        $company->fill($request->get('company'))->save();

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
