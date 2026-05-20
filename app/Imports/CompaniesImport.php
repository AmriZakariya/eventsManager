<?php

namespace App\Imports;

use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class CompaniesImport implements OnEachRow, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    private int $created = 0;
    private int $updated = 0;
    private int $skipped = 0;

    private array $failures = [];

    public function onRow(Row $row): void
    {
        $rowNumber = $row->getIndex();
        $raw = $row->toArray();

        if ($this->isEmptyRow($raw)) {
            return;
        }

        $data = $this->normalizeRow($raw);

        $validator = Validator::make($data, [
            'id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'booth_number' => ['nullable', 'string', 'max:255'],
            'map_coordinates' => ['nullable', 'array'],
            'country' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'type' => ['nullable', 'array'],
            'type.*' => ['string', 'in:' . implode(',', array_keys(Company::TYPES))],
            'catalog_file' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
            'passcode' => ['nullable', 'string', 'max:255'],
        ], [
            'type.*.in' => 'Type must be one of: ' . implode(', ', array_keys(Company::TYPES)),
        ]);

        $validator->after(function ($validator) use ($data) {
            if (isset($data['map_coordinates']['_invalid'])) {
                $validator->errors()->add('map_coordinates', 'Map coordinates must be valid JSON, for example {"x":100,"y":200}.');
            }
        });

        if ($validator->fails()) {
            $this->skipped++;
            $this->failures[] = [
                'row' => $rowNumber,
                'name' => $data['name'] ?? null,
                'errors' => $validator->errors()->all(),
            ];

            return;
        }

        $company = $this->resolveCompany($data);
        $company->fill($this->companyPayload($data));
        $company->save();

        $company->wasRecentlyCreated ? $this->created++ : $this->updated++;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function updatedCount(): int
    {
        return $this->updated;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }

    public function failures(): array
    {
        return $this->failures;
    }

    private function resolveCompany(array $data): Company
    {
        if (!empty($data['id'])) {
            return Company::findOrFail($data['id']);
        }

        return Company::firstOrNew(['name' => $data['name']]);
    }

    private function companyPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'logo' => $data['logo'],
            'booth_number' => $data['booth_number'],
            'map_coordinates' => $data['map_coordinates'],
            'country' => $data['country'],
            'category' => $data['category'],
            'email' => $data['email'],
            'website_url' => $data['website_url'],
            'type' => $data['type'],
            'catalog_file' => $data['catalog_file'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'description' => $data['description'],
            'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'],
            'passcode' => $data['passcode'],
        ];
    }

    private function normalizeRow(array $row): array
    {
        return [
            'id' => $this->nullableInt($row['id'] ?? null),
            'name' => $this->cleanString($row['name'] ?? null),
            'logo' => $this->cleanString($row['logo'] ?? null),
            'booth_number' => $this->cleanString($row['booth_number'] ?? null),
            'map_coordinates' => $this->parseMapCoordinates($row['map_coordinates'] ?? null),
            'country' => $this->cleanString($row['country'] ?? null),
            'category' => $this->cleanString($row['category'] ?? null),
            'email' => $this->cleanString($row['email'] ?? null),
            'website_url' => $this->cleanString($row['website_url'] ?? null),
            'type' => $this->parseTypes($row['type'] ?? null),
            'catalog_file' => $this->cleanString($row['catalog_file'] ?? null),
            'phone' => $this->cleanString($row['phone'] ?? null),
            'address' => $this->cleanString($row['address'] ?? null),
            'description' => $this->cleanString($row['description'] ?? null),
            'is_featured' => $this->parseBoolean($row['is_featured'] ?? null, false),
            'is_active' => $this->parseBoolean($row['is_active'] ?? null, true),
            'passcode' => $this->cleanString($row['passcode'] ?? null),
        ];
    }

    private function parseTypes(mixed $value): ?array
    {
        $value = $this->cleanString($value);

        if ($value === null) {
            return null;
        }

        $types = collect(preg_split('/[,;|]/', $value))
            ->map(fn(string $type) => $this->typeToken($type))
            ->filter()
            ->map(fn(string $type) => $this->normalizeType($type) ?? $type)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($types) ? null : $types;
    }

    private function normalizeType(string $type): ?string
    {
        $aliases = [
            'EXHIBITION_PARTNERS' => 'EXHIBITION_PARTNER',
            'EXHIBITIONS_PARTNERS' => 'EXHIBITION_PARTNER',
            'EXHIBITIONS_PARTNER' => 'EXHIBITION_PARTNER',
            'MEDIA_PARTNERS' => 'MEDIA_PARTNER',
            'INSTITUTIONAL_PARTNERS' => 'INSTITUTIONAL_PARTNER',
            'SPONSORS' => 'SPONSOR',
            'EXHIBITORS' => 'EXHIBITOR',
        ];

        $type = $aliases[$type] ?? $type;

        return array_key_exists($type, Company::TYPES) ? $type : null;
    }

    private function typeToken(string $type): string
    {
        $type = trim($type);

        if ($type === '') {
            return '';
        }

        return Str::upper(preg_replace('/[^A-Za-z0-9]+/', '_', $type));
    }

    private function parseMapCoordinates(mixed $value): ?array
    {
        $value = $this->cleanString($value);

        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : ['_invalid' => $value];
    }

    private function parseBoolean(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'active', 'featured'], true);
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = $this->cleanString($value);

        return $value === null ? null : (int) $value;
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanString($value) !== null) {
                return false;
            }
        }

        return true;
    }
}
