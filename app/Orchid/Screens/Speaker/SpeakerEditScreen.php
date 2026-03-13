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

                Input::make('speaker.job_title')
                    ->title('Job Title')
                    ->placeholder('e.g. Senior Engineer'),

                Input::make('speaker.company_name') // Matches DB: company_name
                ->title('Company Name')
                    ->placeholder('e.g. Tech Corp'),

                Cropper::make('speaker.photo') // Matches DB: photo
                ->title('Profile Photo')
                    ->targetRelativeUrl(),

                TextArea::make('speaker.bio')
                    ->title('Biography')
                    ->rows(5)
                    ->placeholder('Short bio about the speaker...'),
            ])
        ];
    }

    public function save(Speaker $speaker, Request $request)
    {
        $speaker->fill($request->get('speaker'))->save();
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
