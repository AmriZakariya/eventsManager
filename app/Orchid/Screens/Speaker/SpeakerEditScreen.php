<?php

namespace App\Orchid\Screens\Speaker;

use App\Models\Speaker;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SpeakerEditScreen extends Screen
{
    public $speaker;

    public function query(Speaker $speaker): iterable
    {
        $speaker->hydrateTranslationInputs(['job_title', 'bio']);

        return [
            'speaker' => $speaker,
        ];
    }

    public function name(): ?string
    {
        return $this->speaker->exists ? 'Edit Speaker' : 'Create Speaker';
    }

    public function commandBar(): array
    {
        return [
            Button::make('Save')
                ->icon('bs.check-circle')
                ->method('save'),

            Button::make('Remove')
                ->icon('bs.trash3')
                ->method('remove')
                ->canSee($this->speaker->exists),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('speaker.full_name') // Matches DB: full_name
                ->title('Full Name')
                    ->placeholder('e.g. John Doe')
                    ->required(),

                Input::make('speaker.company_name') // Matches DB: company_name
                ->title('Company Name')
                    ->placeholder('e.g. Tech Corp'),

                Cropper::make('speaker.photo') // Matches DB: photo
                ->title('Profile Photo')
                    ->targetRelativeUrl(),
            ]),

            Layout::tabs([
                'English' => Layout::rows([
                    Input::make('speaker.job_title_translations.en')
                        ->title('Job Title')
                        ->value($this->speaker->translationInput('job_title')['en'] ?? null)
                        ->placeholder('e.g. Senior Engineer'),

                    TextArea::make('speaker.bio_translations.en')
                        ->title('Biography')
                        ->value($this->speaker->plainText($this->speaker->translationInput('bio')['en'] ?? null))
                        ->rows(5)
                        ->placeholder('Short bio about the speaker...'),
                ]),

                'Français' => Layout::rows([
                    Input::make('speaker.job_title_translations.fr')
                        ->title('Poste')
                        ->value($this->speaker->translationInput('job_title')['fr'] ?? null),

                    TextArea::make('speaker.bio_translations.fr')
                        ->title('Biographie')
                        ->value($this->speaker->plainText($this->speaker->translationInput('bio')['fr'] ?? null))
                        ->rows(5),
                ]),

                'العربية' => Layout::rows([
                    Input::make('speaker.job_title_translations.ar')
                        ->title('المسمى الوظيفي')
                        ->value($this->speaker->translationInput('job_title')['ar'] ?? null),

                    TextArea::make('speaker.bio_translations.ar')
                        ->title('السيرة الذاتية')
                        ->value($this->speaker->plainText($this->speaker->translationInput('bio')['ar'] ?? null))
                        ->rows(5),
                ]),
            ])
        ];
    }

    public function save(Speaker $speaker, Request $request)
    {
        $request->validate([
            'speaker.full_name' => 'required|string|max:255',
            'speaker.company_name' => 'nullable|string|max:255',
            'speaker.job_title_translations.*' => 'nullable|string|max:255',
            'speaker.bio_translations.*' => 'nullable|string',
        ]);

        $data = $speaker->prepareTranslatedData($request->get('speaker', []), ['job_title', 'bio'], ['bio']);

        $speaker->fill($data)->save();
        Toast::info('Speaker saved successfully.');
        return redirect()->route('platform.speakers.list');
    }

    public function remove(Speaker $speaker)
    {
        $speaker->delete();
        Toast::info('Speaker deleted.');
        return redirect()->route('platform.speakers.list');
    }
}
