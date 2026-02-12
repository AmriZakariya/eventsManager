<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Orchid\Attachment\Attachable;

class Product extends Model
{
    use AsSource, Attachable;

    protected $fillable = [
        'company_id', 'category_id', 'name',
        'image', 'type', 'description', 'is_featured'
    ];

    protected $casts = [
        'is_featured' => 'boolean'
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
