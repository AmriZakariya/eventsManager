<?php

namespace App\Orchid\Screens\Conference;

use App\Models\Conference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Orchid\Attachment\Models\Attachment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConferenceListScreen extends Screen
{
    public $name = 'Conference Agenda';
    public $description = 'Manage talks, panels, and workshops.';

    public function query(Request $request): array
    {
        return [
            'conferences' => $this->buildQuery($request)->paginate(20)->withQueryString(),
            'search'      => $request->get('search'),
            'type'        => $request->get('type'),
            'date'        => $request->get('date'),
        ];
    }

    public function buildQuery(Request $request): Builder
    {
        $query = Conference::query()->withCount(['speakers', 'attendees']);

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($start = $request->input('date.start')) {
            $query->whereDate('start_time', '>=', $start);
        }
        if ($end = $request->input('date.end')) {
            $query->whereDate('start_time', '<=', $end);
        }

        $sort = $request->get('sort');
        if (is_array($sort)) $sort = $sort[0] ?? null;

        $sortable = ['title', 'start_time', 'end_time', 'location', 'type', 'created_at', 'speakers_count', 'attendees_count'];

        if (is_string($sort) && $sort !== '' && in_array(ltrim($sort, '-'), $sortable, true)) {
            $query->orderBy(ltrim($sort, '-'), str_starts_with($sort, '-') ? 'desc' : 'asc');
        } else {
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

            ModalToggle::make('Import Excel')
                ->icon('bs.upload')
                ->modal('importModal')
                ->method('import')
                ->class('btn btn-outline-primary'),

            // 👇 Changed to Button, calls method directly, and uses rawClick()
            Button::make('Template')
                ->icon('bs.file-earmark-arrow-down')
                ->method('downloadTemplate')
                ->rawClick()
                ->class('btn btn-link btn-sm'),

            // 👇 Changed to Button, passes current filters, and uses rawClick()
            Button::make('Export Excel')
                ->icon('bs.file-earmark-spreadsheet')
                ->method('export', request()->query()) // Passes current filters/sorts
                ->rawClick()
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
                            ''           => 'All',
                            'conference' => 'Conference',
                            'workshop'   => 'Workshop',
                            'panel'      => 'Panel',
                            'keynote'    => 'Keynote',
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
                        $href  = route('platform.conferences.edit', $c->id);
                        $title = e($c->title);
                        $meta  = $c->location
                            ? '<div class="text-muted small"><i class="bi bi-geo-alt"></i> ' . e($c->location) . '</div>'
                            : '';
                        return "<div><a class='fw-semibold text-decoration-none' href='{$href}'>{$title}</a>{$meta}</div>";
                    }),

                TD::make('start_time', 'Time')
                    ->sort()
                    ->render(function (Conference $c) {
                        $start = $c->start_time?->format('M d, H:i');
                        $end   = $c->end_time?->format('H:i');
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
                        $class = match ($c->type) {
                            'keynote'  => 'bg-warning text-dark',
                            'panel'    => 'bg-info text-dark',
                            'workshop' => 'bg-success',
                            default    => 'bg-primary',
                        };
                        return "<span class='badge {$class}'>" . e(ucfirst((string) $c->type)) . "</span>";
                    }),

                TD::make('speakers_count', 'Speakers')
                    ->alignCenter()->sort()->width('110px')
                    ->render(fn(Conference $c) => "<span class='badge bg-secondary'>{$c->speakers_count}</span>"),

                TD::make('attendees_count', 'Registrations')
                    ->alignCenter()->sort()->width('140px')
                    ->render(fn(Conference $c) => "<span class='badge bg-secondary'>{$c->attendees_count}</span>"),

                TD::make('Actions')
                    ->alignRight()->cantHide()->width('100px')
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
            ]),

            Layout::modal('importModal', [
                Layout::rows([
                    Upload::make('import_file')
                        ->title('Upload Excel File (.xlsx)')
                        ->acceptedFiles('.xlsx')
                        ->maxFiles(1)
                        ->required(),
                ]),
            ])
                ->title('Import Conferences')
                ->applyButton('Import'),
        ];
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    public function applyFilter(Request $request)
    {
        return redirect()->route('platform.conferences.list', array_filter([
            'search' => $request->get('search') ?: null,
            'type'   => $request->get('type') ?: null,
            'date'   => $request->get('date') ?: null,
        ]));
    }

    public function clearFilters()
    {
        return redirect()->route('platform.conferences.list');
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public function remove(Request $request)
    {
        Conference::findOrFail($request->get('id'))->delete();
        Toast::success('Session deleted.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->get('ids', []);

        if (empty($ids)) {
            Toast::warning('No sessions selected.');
            return;
        }

        Toast::success('Deleted ' . Conference::whereIn('id', $ids)->delete() . ' session(s).');
    }

    // -------------------------------------------------------------------------
    // Export — called via GET route (Links bypass Orchid AJAX)
    // -------------------------------------------------------------------------

    public function export(Request $request)
    {
        $query = $this->buildQuery($request);

        $export = new class($query) implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize {
            public function __construct(private Builder $query) {}
            public function query(): Builder { return $this->query; }
            public function headings(): array
            {
                return ['ID', 'Title', 'Type', 'Start', 'End', 'Location', 'Speakers', 'Registrations'];
            }
            public function map($c): array
            {
                return [
                    $c->id,
                    $c->title,
                    ucfirst((string) $c->type),
                    optional($c->start_time)->format('Y-m-d H:i:s'),
                    optional($c->end_time)->format('Y-m-d H:i:s'),
                    $c->location,
                    $c->speakers_count,
                    $c->attendees_count,
                ];
            }
            public function styles(Worksheet $sheet): array
            {
                return [1 => ['font' => ['bold' => true]]];
            }
        };

        return Excel::download($export, 'conferences-' . now()->format('Y-m-d') . '.xlsx');
    }

    // -------------------------------------------------------------------------
    // Template download — called via GET route
    // -------------------------------------------------------------------------

    public function downloadTemplate()
    {
        $template = new class implements FromArray, WithStyles, ShouldAutoSize {
            public function array(): array
            {
                return [
                    ['title', 'type', 'start', 'end', 'location'],
                    ['Example Talk', 'keynote', '2025-09-01 09:00:00', '2025-09-01 10:00:00', 'Hall A'],
                ];
            }
            public function styles(Worksheet $sheet): array
            {
                return [1 => ['font' => ['bold' => true]]];
            }
        };

        return Excel::download($template, 'conferences-template.xlsx');
    }

    // -------------------------------------------------------------------------
    // Import — called via Orchid modal POST
    // -------------------------------------------------------------------------

    public function import(Request $request)
    {
        $request->validate(['import_file' => ['required', 'array']]);

        $ids = $request->input('import_file', []);

        if (empty($ids)) {
            Toast::warning('Please upload a file.');
            return;
        }

        /** @var Attachment $attachment */
        $attachment = Attachment::findOrFail($ids[0]);

        // Use Storage::disk() so it works regardless of local/public/S3 config
        $path = Storage::disk($attachment->disk)
            ->path($attachment->path . $attachment->name . '.' . $attachment->extension);

        if (! file_exists($path)) {
            Toast::error('Uploaded file not found on disk. Please re-upload and try again.');
            return;
        }

        $import = new class implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, WithBatchInserts, WithChunkReading {
            use SkipsErrors;

            public function model(array $row): ?Conference
            {
                return new Conference([
                    'title'      => $row['title'],
                    'type'       => strtolower($row['type'] ?? 'conference'),
                    'location'   => $row['location'] ?? null,
                    'start_time' => $row['start'] ?? null,
                    'end_time'   => $row['end'] ?? null,
                ]);
            }

            public function rules(): array
            {
                return [
                    'title' => ['required', 'string', 'max:255'],
                    'type'  => ['nullable', 'in:conference,workshop,panel,keynote'],
                    'start' => ['nullable', 'date'],
                    'end'   => ['nullable', 'date'],
                ];
            }

            public function batchSize(): int { return 200; }
            public function chunkSize(): int { return 500; }
        };

        try {
            Excel::import($import, $path);

            $errorCount = count($import->errors());

            $errorCount > 0
                ? Toast::warning("Import done — {$errorCount} row(s) skipped due to validation errors.")
                : Toast::success('Conferences imported successfully.');

        } catch (\Exception $e) {
            Toast::error('Import failed: ' . $e->getMessage());
        }
    }
}
