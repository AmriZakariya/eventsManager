<?php

namespace App\Orchid\Screens\Conference;

use App\Models\Conference;
use App\Models\Speaker;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class ConferenceEditScreen extends Screen
{
    public $conference;

    public function query(Conference $conference): array
    {
        $conference->load('speakers');
        return ['conference' => $conference];
    }

    public function name(): ?string
    {
        return $this->conference->exists ? 'Edit Session' : 'Create Session';
    }

    public function description(): ?string
    {
        return $this->conference->exists
            ? 'Update agenda details, speakers, and scheduling.'
            : 'Add a new session to the agenda.';
    }

    public function commandBar(): array
    {
        return [
            Link::make('Back to Agenda')
                ->icon('bs.arrow-left')
                ->route('platform.conferences.list'),

            Button::make('Save')
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save'),

            Button::make('Delete')
                ->icon('bs.trash3')
                ->type(Color::DANGER)
                ->confirm('Are you sure you want to delete this session?')
                ->method('remove')
                ->canSee($this->conference->exists),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::columns([
                Layout::rows([
                    Input::make('conference.title')
                        ->title('Session Title')
                        ->placeholder('e.g. Future of AI in Events')
                        ->required(),

                    Group::make([
                        Select::make('conference.type')
                            ->title('Type')
                            ->options([
                                'conference' => 'Conference',
                                'workshop' => 'Workshop',
                                'panel' => 'Panel Discussion',
                                'keynote' => 'Keynote',
                            ])
                            ->required(),

                        Input::make('conference.location')
                            ->title('Location')
                            ->placeholder('e.g. Hall A / Room 3'),
                    ]),

                    TextArea::make('conference.description')
                        ->title('Description')
                        ->rows(6)
                        ->placeholder('What is this session about?'),

                    Relation::make('conference.speakers')
                        ->fromModel(Speaker::class, 'full_name')
                        ->multiple()
                        ->title('Speakers')
                        ->help('Select one or more speakers.'),
                ]),

                Layout::rows([
                    Group::make([
                        DateTimer::make('conference.start_time')
                            ->title('Start')
                            ->enableTime()
                            ->required()
                            ->help('Local time. Make sure it matches the published agenda.'),

                        DateTimer::make('conference.end_time')
                            ->title('End')
                            ->enableTime()
                            ->required()
                            ->help('Must be after the start time.'),
                    ]),
                ]),
            ]),
        ];
    }

    public function save(Conference $conference, Request $request)
    {
        $request->validate([
            'conference.title' => 'required|string|max:255',
            'conference.type' => 'required|in:conference,workshop,panel,keynote',
            'conference.start_time' => 'required|date',
            'conference.end_time' => 'required|date|after:conference.start_time',
            'conference.location' => 'nullable|string|max:255',
            'conference.description' => 'nullable|string',
            'conference.speakers' => 'array',
            'conference.speakers.*' => 'integer|exists:speakers,id',
        ]);

        $conference->fill($request->get('conference', []))->save();

        $conference->speakers()->sync($request->input('conference.speakers', []));

        Toast::info('Session saved.');
        return redirect()->route('platform.conferences.list');
    }

    public function remove(Conference $conference)
    {
        $conference->speakers()->detach();
        $conference->delete();
        Toast::info('Session deleted.');
        return redirect()->route('platform.conferences.list');
    }
}
