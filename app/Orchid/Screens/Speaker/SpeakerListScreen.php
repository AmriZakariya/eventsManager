<?php

namespace App\Orchid\Screens\Speaker;

use App\Models\Speaker;
use App\Orchid\Layouts\Speaker\SpeakerListLayout;
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
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Upload;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SpeakerListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'speakers' => Speaker::filters()
                ->defaultSort('full_name')
                ->paginate(),
        ];
    }

    public function name(): ?string
    {
        return 'Speakers';
    }

    public function commandBar(): array
    {
        return [
            Link::make('Add Speaker')
                ->icon('bs.person-plus')
                ->route('platform.speakers.create'),

            ModalToggle::make('Import Excel')
                ->icon('bs.upload')
                ->modal('importModal')
                ->method('import')
                ->class('btn btn-outline-primary'),

            // 👇 Natively downloads the template via POST, bypassing Turbo
            Button::make('Template')
                ->icon('bs.file-earmark-arrow-down')
                ->method('downloadTemplate')
                ->rawClick()
                ->class('btn btn-link btn-sm'),

            // 👇 Natively downloads the export via POST, bypassing Turbo
            Button::make('Export Excel')
                ->icon('bs.file-earmark-spreadsheet')
                ->method('export', request()->query())
                ->rawClick()
                ->class('btn btn-outline-secondary'),
        ];
    }

    public function layout(): iterable
    {
        return [
            SpeakerListLayout::class,

            Layout::modal('importModal', [
                Layout::rows([
                    Upload::make('import_file')
                        ->title('Upload Excel File (.xlsx)')
                        ->acceptedFiles('.xlsx')
                        ->maxFiles(1)
                        ->required(),
                ]),
            ])
                ->title('Import Speakers')
                ->applyButton('Import'),
        ];
    }

    // -------------------------------------------------------------------------
    // Export — bypasses Turbo using rawClick()
    // -------------------------------------------------------------------------

    public function export(Request $request)
    {
        $query = Speaker::filters()->defaultSort('full_name');

        $export = new class($query) implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize {
            public function __construct(private Builder $query) {}
            public function query(): Builder { return $this->query; }
            public function headings(): array
            {
                return ['ID', 'Full Name', 'Job Title', 'Company', 'Created At'];
            }
            public function map($speaker): array
            {
                return [
                    $speaker->id,
                    $speaker->full_name,
                    $speaker->job_title,
                    $speaker->company_name,
                    optional($speaker->created_at)->format('Y-m-d H:i:s'),
                ];
            }
            public function styles(Worksheet $sheet): array
            {
                return [1 => ['font' => ['bold' => true]]];
            }
        };

        return Excel::download($export, 'speakers-' . now()->format('Y-m-d') . '.xlsx');
    }

    // -------------------------------------------------------------------------
    // Template download — bypasses Turbo using rawClick()
    // -------------------------------------------------------------------------

    public function downloadTemplate()
    {
        $template = new class implements FromArray, WithStyles, ShouldAutoSize {
            public function array(): array
            {
                return [
                    ['full_name', 'job_title', 'company_name', 'bio'],
                    ['Jane Doe', 'Chief Innovation Officer', 'TechCorp', 'Expert in AI and Machine Learning.'],
                ];
            }
            public function styles(Worksheet $sheet): array
            {
                return [1 => ['font' => ['bold' => true]]];
            }
        };

        return Excel::download($template, 'speakers-template.xlsx');
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

        // Support for any disk (local, s3, public)
        $path = Storage::disk($attachment->disk)
            ->path($attachment->path . $attachment->name . '.' . $attachment->extension);

        if (! file_exists($path)) {
            Toast::error('Uploaded file not found on disk. Please re-upload and try again.');
            return;
        }

        $import = new class implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, WithBatchInserts, WithChunkReading {
            use SkipsErrors;

            public function model(array $row): ?Speaker
            {
                return new Speaker([
                    'full_name'    => $row['full_name'],
                    'job_title'    => $row['job_title'] ?? null,
                    'company_name' => $row['company_name'] ?? null,
                    'bio'          => $row['bio'] ?? null,
                ]);
            }

            public function rules(): array
            {
                return [
                    'full_name'    => ['required', 'string', 'max:255'],
                    'job_title'    => ['nullable', 'string', 'max:255'],
                    'company_name' => ['nullable', 'string', 'max:255'],
                    'bio'          => ['nullable', 'string'],
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
                : Toast::success('Speakers imported successfully.');

        } catch (\Exception $e) {
            Toast::error('Import failed: ' . $e->getMessage());
        }
    }
}
