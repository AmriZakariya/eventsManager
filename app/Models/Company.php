<?php

namespace App\Models;

use App\Traits\Favoritable;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Orchid\Attachment\Attachable;

class Company extends Model
{
    use AsSource, Attachable, Favoritable, Filterable;

    public const TYPES = [
        'INSTITUTIONAL_PARTNER' => 'Institutional Partner',
        'SPONSOR'               => 'Sponsor',
        'MEDIA_PARTNER'         => 'Media Partner',
        'EXHIBITION_PARTNER'    => 'Exhibition Partner',
        'EXHIBITOR'             => 'Exhibitor',
    ];

    protected $fillable = [
        'name', 'logo', 'booth_number', 'map_coordinates',
        'country', 'category', 'email', 'website_url', 'type', 'catalog_file',
        'phone', 'address', 'description',
        'is_featured', 'is_active'
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
        'is_featured' => 'boolean'
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
}
