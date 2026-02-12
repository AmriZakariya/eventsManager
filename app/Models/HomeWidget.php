<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeWidget extends Model
{
    protected $fillable = [
        'title',
        'identifier',
        'widget_type',
        'image',
        'icon',
        'data_source',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public const TYPES = [
        'slider' => 'Image Slider',
        'menu_grid' => 'Menu Grid',
        'logo_cloud' => 'Logo Cloud',
        'single_banner' => 'Single Banner',
        'dynamic_list' => 'Dynamic List',
    ];

    public const DATA_SOURCES = [
        'companies' => 'Companies',
        'products' => 'Products',
        'speakers' => 'Speakers',
        'events' => 'Events',
        'articles' => 'Articles',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(HomeWidgetItem::class);
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset($this->image);
    }

    // Add this method to fix the error
    public function getContentAttribute()
    {
        if ($this->data_source) {
            return 'Dynamic: ' . (self::DATA_SOURCES[$this->data_source] ?? $this->data_source);
        }

        $itemCount = $this->items()->count();
        return $itemCount . ' item' . ($itemCount !== 1 ? 's' : '');
    }
}
