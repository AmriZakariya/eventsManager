<?php

namespace App\Exports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompaniesExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public const HEADINGS = [
        'id',
        'name',
        'logo',
        'booth_number',
        'map_coordinates',
        'country',
        'category',
        'email',
        'website_url',
        'type',
        'catalog_file',
        'phone',
        'address',
        'description',
        'is_featured',
        'is_active',
        'passcode',
    ];

    public function __construct(
        private readonly Collection $companies,
        private readonly bool $template = false
    ) {
    }

    public static function template(): self
    {
        return new self(collect(), true);
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function array(): array
    {
        if ($this->template) {
            return [$this->exampleRow()];
        }

        return $this->companies
            ->map(fn(Company $company) => $this->mapCompany($company))
            ->values()
            ->all();
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
            ],
        ];
    }

    private function mapCompany(Company $company): array
    {
        return [
            $company->id,
            $company->name,
            $company->logo,
            $company->booth_number,
            $company->map_coordinates ? json_encode($company->map_coordinates, JSON_UNESCAPED_SLASHES) : null,
            $company->country,
            $company->category,
            $company->email,
            $company->website_url,
            is_array($company->type) ? implode(',', $company->type) : $company->type,
            $company->catalog_file,
            $company->phone,
            $company->address,
            $company->description,
            $company->is_featured ? 1 : 0,
            $company->is_active ? 1 : 0,
            $company->passcode,
        ];
    }

    private function exampleRow(): array
    {
        return [
            null,
            'Example Company',
            'storage/logos/example.png',
            'A-101',
            '{"x":100,"y":200}',
            'Morocco',
            'Technology',
            'contact@example.com',
            'https://example.com',
            'EXHIBITOR,SPONSOR',
            'storage/catalogs/example.pdf',
            '+212 600 000 000',
            '123 Business Avenue, Casablanca',
            'Short company profile shown in the app.',
            0,
            1,
            'EXAMPLE-001',
        ];
    }
}
