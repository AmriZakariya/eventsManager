<?php

namespace App\Models;

use App\Models\Concerns\AuditableModel;
use App\Models\Concerns\HasLocalizedContent;
use App\Traits\Favoritable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Orchid\Attachment\Attachable;

class Company extends Model
{
    use AsSource, Attachable, AuditableModel, Favoritable, Filterable, HasLocalizedContent;

    public const TYPES = [
        'ORGANIZER'             => 'Organizer',
        'INSTITUTIONAL_PARTNER' => 'Institutional Partner',
        'SPONSOR'               => 'Sponsor',
        'MEDIA_PARTNER'         => 'Media Partner',
        'EXHIBITION_PARTNER'    => 'Exhibition Partner',
        'EXHIBITOR'             => 'Exhibitor',
    ];

    protected $fillable = [
        'name', 'name_translations', 'logo', 'booth_number', 'map_coordinates',
        'country', 'category', 'category_translations', 'email', 'website_url', 'type', 'catalog_file',
        'phone', 'address', 'address_translations', 'description', 'description_translations',
        'is_featured', 'is_active', 'passcode'
    ];

    // ✅ REQUIRED FOR SORTING
    protected $allowedSorts = [
        'name',
        'booth_number',
        'category',
        'country',
        'is_active',
        'is_featured',
        'created_at'
    ];

    protected $casts = [
        'map_coordinates' => 'array', // JSON {x:10, y:20}
        'type'            => 'array', // Automatic JSON conversion
        'name_translations' => 'array',
        'category_translations' => 'array',
        'address_translations' => 'array',
        'description_translations' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ✅ REQUIRED FOR SEARCH/FILTERING
    protected $allowedFilters = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function team()
    {
        // A Company has many Users (Exhibitors)
        return $this->hasMany(User::class, 'company_id');
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) return null;
        if (str_starts_with($this->logo, 'http')) return $this->logo;
        return asset($this->logo);
    }

    public function getCatalogUrlAttribute()
    {
        if (!$this->catalog_file) return null;
        if (str_starts_with($this->catalog_file, 'http')) return $this->catalog_file;
        return asset($this->catalog_file);
    }

    public function localizedCategoryItems(?string $locale = null): array
    {
        $labels = $this->splitCategoryList($this->localized('category', $locale));
        $values = $this->splitCategoryList($this->category);

        if (empty($values)) {
            $values = $labels;
        }

        return collect($labels)
            ->map(function (string $label, int $index) use ($values) {
                $value = $values[$index] ?? $label;

                return [
                    'label' => $label,
                    'value' => $value,
                    'slug' => Str::slug($value),
                    'url' => url('/api/companies') . '?category=' . rawurlencode($value),
                ];
            })
            ->unique(fn (array $item) => Str::lower($item['value']))
            ->values()
            ->all();
    }

    public function scopeWhereCategoryToken(Builder $query, string $category): Builder
    {
        $category = trim($category);

        if ($category === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($category) {
            $q->where('category', $category)
                ->orWhere('category', 'like', "{$category},%")
                ->orWhere('category', 'like', "{$category} ,%")
                ->orWhere('category', 'like', "%,{$category},%")
                ->orWhere('category', 'like', "%, {$category},%")
                ->orWhere('category', 'like', "%,{$category}")
                ->orWhere('category', 'like', "%, {$category}");
        });
    }

    private function splitCategoryList(?string $category): array
    {
        if (!is_string($category) || trim($category) === '') {
            return [];
        }

        return collect(preg_split('/\s*[,،]\s*/u', $category) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
