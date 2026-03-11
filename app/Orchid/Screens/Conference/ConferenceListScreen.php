<?php

namespace App\Orchid\Screens\Conference;

use App\Models\Conference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConferenceListScreen extends Screen
{
    public $name = 'Conference Agenda';
    public $description = 'Manage talks, panels, and workshops.';

    public function query(Request $request): array
    {
        $query = $this->buildConferencesQuery($request);

        return [
            'conferences' => $query->paginate(20)->withQueryString(),
            // Persist filter values
            'search' => $request->get('search'),
            'type' => $request->get('type'),
            'date' => $request->get('date'),
        ];
    }

    private function buildConferencesQuery(Request $request): Builder
    {
        $query = Conference::query()
            ->withCount(['speakers', 'attendees']);

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%');
            });
        }

        $start = $request->input('date.start');
        $end = $request->input('date.end');
        if ($start) {
            $query->whereDate('start_time', '>=', $start);
        }
        if ($end) {
            $query->whereDate('start_time', '<=', $end);
        }

        $sort = $request->get('sort');
        if (is_array($sort)) {
            $sort = $sort[0] ?? null;
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column = ltrim($sort, '-');

            if (in_array($column, ['title', 'start_time', 'end_time', 'location', 'type', 'created_at', 'speakers_count', 'attendees_count'], true)) {
                $query->orderBy($column, $direction);
            }
        } else {
            // Default agenda ordering
            $query->orderBy('start_time', 'asc');
        }

        return $query;
    }

    public function commandBar(): array
    {
        return [
            Link::make('Add Session')
                ->icon('bs.plus-circle')
                ->route('platform.conferences.create'),

            Button::make('Export CSV')
                ->icon('bs.download')
                ->method('export')
                ->class('btn btn-outline-secondary'),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::rows([
                Group::make([
                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Title or location...'),

                    Select::make('type')
                        ->title('Type')
                        ->options([
                            '' => 'All',
                            'conference' => 'Conference',
                            'workshop' => 'Workshop',
                            'panel' => 'Panel',
                            'keynote' => 'Keynote',
                        ])
                        ->empty('All', ''),

                    DateRange::make('date')
                        ->title('Date range'),
                ]),

                Group::make([
                    Button::make('Apply')
                        ->icon('bs.funnel')
                        ->method('applyFilter')
                        ->class('btn btn-primary'),

                    Button::make('Reset')
                        ->icon('bs.x-circle')
                        ->method('clearFilters')
                        ->class('btn btn-outline-secondary'),

                    Button::make('Delete Selected')
                        ->icon('bs.trash3')
                        ->confirm('Delete selected sessions?')
                        ->method('bulkDelete')
                        ->class('btn btn-outline-danger'),
                ])->autoWidth(),
            ])->title('Filters'),

            Layout::table('conferences', [
                TD::make('__checkbox', '')
                    ->width('40px')
                    ->cantHide()
                    ->render(fn(Conference $c) => "<input type='checkbox' class='form-check-input' name='ids[]' value='{$c->id}'>"),

                TD::make('title', 'Title')
                    ->sort()
                    ->render(function (Conference $c) {
                        $href = route('platform.conferences.edit', $c->id);
                        $title = e($c->title);
                        $meta = $c->location ? '<div class="text-muted small"><i class="bi bi-geo-alt"></i> ' . e($c->location) . '</div>' : '';

                        return "<div><a class='fw-semibold text-decoration-none' href='{$href}'>{$title}</a>{$meta}</div>";
                    }),

                TD::make('start_time', 'Time')
                    ->sort()
                    ->render(function (Conference $c) {
                        $start = $c->start_time?->format('M d, H:i');
                        $end = $c->end_time?->format('H:i');
                        $range = trim(($start ?? '') . ($end ? " – {$end}" : ''));

                        return $range !== '' ? e($range) : '<span class="text-muted">—</span>';
                    }),

                TD::make('location', 'Room/Location')
                    ->sort()
                    ->defaultHidden()
                    ->render(fn(Conference $c) => $c->location ? e($c->location) : '<span class="text-muted">—</span>'),

                TD::make('type', 'Format')
                    ->sort()
                    ->render(function (Conference $c) {
                        $label = ucfirst((string) $c->type);
                        $class = match ($c->type) {
                            'keynote' => 'bg-warning text-dark',
                            'panel' => 'bg-info text-dark',
                            'workshop' => 'bg-success',
                            default => 'bg-primary',
                        };

                        return "<span class='badge {$class}'>" . e($label) . "</span>";
                    }),

                TD::make('speakers_count', 'Speakers')
                    ->alignCenter()
                    ->sort()
                    ->width('110px')
                    ->render(fn(Conference $c) => "<span class='badge bg-secondary'>{$c->speakers_count}</span>"),

                TD::make('attendees_count', 'Registrations')
                    ->alignCenter()
                    ->sort()
                    ->width('140px')
                    ->render(fn(Conference $c) => "<span class='badge bg-secondary'>{$c->attendees_count}</span>"),

                TD::make('Actions')
                    ->alignRight()
                    ->cantHide()
                    ->width('100px')
                    ->render(fn(Conference $c) =>
                        DropDown::make()
                            ->icon('bs.three-dots-vertical')
                            ->class('btn btn-sm btn-link')
                            ->list([
                                Link::make('Edit')
                                    ->icon('bs.pencil')
                                    ->route('platform.conferences.edit', $c->id),

                                Button::make('Delete')
                                    ->icon('bs.trash3')
                                    ->confirm('Delete this session?')
                                    ->method('remove', ['id' => $c->id]),
                            ])
                    ),
            ])
        ];
    }

    public function applyFilter(Request $request)
    {
        return redirect()->route('platform.conferences.list', array_filter([
            'search' => $request->get('search') ?: null,
            'type' => $request->get('type') ?: null,
            'date' => $request->get('date') ?: null,
        ]));
    }

    public function clearFilters()
    {
        return redirect()->route('platform.conferences.list');
    }

    public function remove(Request $request)
    {
        $conference = Conference::findOrFail($request->get('id'));
        $conference->delete();

        Toast::success('Session deleted.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->get('ids', []);

        if (empty($ids)) {
            Toast::warning('No sessions selected.');
            return;
        }

        $deleted = Conference::whereIn('id', $ids)->delete();
        Toast::success("Deleted {$deleted} session(s).");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildConferencesQuery($request);

        $filename = 'conferences-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Title', 'Type', 'Start', 'End', 'Location', 'Speakers', 'Registrations']);

            $query->chunk(500, function ($items) use ($out) {
                foreach ($items as $c) {
                    fputcsv($out, [
                        $c->id,
                        $c->title,
                        $c->type,
                        optional($c->start_time)->format('Y-m-d H:i:s'),
                        optional($c->end_time)->format('Y-m-d H:i:s'),
                        $c->location,
                        $c->speakers_count,
                        $c->attendees_count,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
