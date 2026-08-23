<?php

namespace App\Models;

use App\Models\Concerns\AuditableModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class HomeWidget extends Model
{
    use AuditableModel;

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
        'sponsor_banner' => 'Sponsor Banner',
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

        $url = str_starts_with($this->image, 'http')
            ? $this->image
            : asset($this->image);

        // Cache-busting so the app fetches the replaced photo (see HomeWidgetItem).
        $version = $this->updated_at?->timestamp;
        if ($version) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $version;
        }

        return $url;
    }

    protected static function booted(): void
    {
        static::saved(fn() => self::flushHomeCache());
        static::deleted(fn() => self::flushHomeCache());
    }

    /**
     * Clear the cached home layout so admin changes appear immediately instead
     * of waiting for the 5-minute response cache to expire.
     */
    public static function flushHomeCache(): void
    {
        foreach (['en', 'fr', 'ar'] as $locale) {
            Cache::forget("api:config:home:{$locale}");
        }
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
