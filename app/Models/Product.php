<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Orchid\Attachment\Attachable;

class Product extends Model
{
    use AsSource, Attachable, HasLocalizedContent;

    protected $fillable = [
        'company_id', 'category_id', 'name', 'name_translations',
        'image', 'type', 'type_translations', 'description', 'description_translations', 'is_featured'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'name_translations' => 'array',
        'type_translations' => 'array',
        'description_translations' => 'array',
    ];

    // Automatically append 'image_url' to JSON
    protected $appends = ['image_url'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    // Accessor for image_url
    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset($this->image);
    }
}
