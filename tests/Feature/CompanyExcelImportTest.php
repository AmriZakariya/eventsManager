<?php

namespace Tests\Feature;

use App\Imports\CompaniesImport;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CompanyExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_excel_import_creates_updates_and_validates_rows(): void
    {
        $existing = Company::create([
            'name' => 'Old Company',
            'is_active' => true,
            'is_featured' => false,
        ]);

        $path = 'testing/companies-import.xlsx';

        Excel::store(new class($existing->id) implements FromArray {
            public function __construct(private readonly int $companyId)
            {
            }

            public function array(): array
            {
                return [
                    [
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
                    ],
                    [
                        $this->companyId,
                        'Updated Company',
                        'storage/logos/updated.png',
                        'B-202',
                        '{"x":10,"y":20}',
                        'Morocco',
                        'Energy',
                        'updated@example.com',
                        'https://updated.example.com',
                        'Exhibitor, Sponsors',
                        'storage/catalogs/updated.pdf',
                        '+212 611 111 111',
                        'Casablanca',
                        'Updated profile.',
                        'yes',
                        'no',
                        'UPD-001',
                    ],
                    [
                        null,
                        'New Partner',
                        null,
                        'C-303',
                        '{"x":30,"y":40}',
                        'France',
                        'Media',
                        'new@example.com',
                        'https://new.example.com',
                        'Media Partners; Exhibitions Partners',
                        null,
                        '+33 1 22 33 44',
                        'Paris',
                        'New profile.',
                        0,
                        1,
                        'NEW-001',
                    ],
                    [
                        null,
                        'Invalid Partner',
                        null,
                        null,
                        '{bad-json',
                        null,
                        null,
                        'not-an-email',
                        null,
                        'UNKNOWN_TYPE',
                        null,
                        null,
                        null,
                        null,
                        0,
                        1,
                        null,
                    ],
                ];
            }
        }, $path);

        $import = new CompaniesImport();
        Excel::import($import, Storage::path($path));

        Storage::delete($path);

        $existing->refresh();

        $this->assertSame(1, $import->createdCount());
        $this->assertSame(1, $import->updatedCount());
        $this->assertSame(1, $import->skippedCount());

        $this->assertSame('Updated Company', $existing->name);
        $this->assertSame(['x' => 10, 'y' => 20], $existing->map_coordinates);
        $this->assertSame(['EXHIBITOR', 'SPONSOR'], $existing->type);
        $this->assertFalse($existing->is_active);
        $this->assertTrue($existing->is_featured);
        $this->assertSame('UPD-001', $existing->passcode);

        $created = Company::where('name', 'New Partner')->firstOrFail();

        $this->assertSame(['MEDIA_PARTNER', 'EXHIBITION_PARTNER'], $created->type);
        $this->assertSame(['x' => 30, 'y' => 40], $created->map_coordinates);
        $this->assertTrue($created->is_active);
        $this->assertFalse($created->is_featured);
        $this->assertDatabaseMissing('companies', ['name' => 'Invalid Partner']);
    }

    public function test_company_import_accepts_template_type_values(): void
    {
        $path = 'testing/companies-template-import.xlsx';

        Excel::store(new class implements FromArray {
            public function array(): array
            {
                return [
                    [
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
                    ],
                    [
                        null,
                        'Template Company',
                        null,
                        'A-101',
                        '{"x":100,"y":200}',
                        'Morocco',
                        'Technology',
                        'contact@example.com',
                        'https://example.com',
                        'EXHIBITOR,SPONSOR',
                        null,
                        '+212 600 000 000',
                        'Casablanca',
                        'Template import row.',
                        0,
                        1,
                        'TPL-001',
                    ],
                ];
            }
        }, $path);

        $import = new CompaniesImport();
        Excel::import($import, Storage::path($path));

        Storage::delete($path);

        $company = Company::where('name', 'Template Company')->firstOrFail();

        $this->assertSame(1, $import->createdCount());
        $this->assertSame(0, $import->skippedCount());
        $this->assertSame(['EXHIBITOR', 'SPONSOR'], $company->type);
    }
}
