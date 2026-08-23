<?php

namespace App\Models;

use App\Models\Concerns\AuditableModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeWidgetItem extends Model
{
    use AuditableModel;

    protected $fillable = [
        'home_widget_id',
        'title',
        'identifier',
        'subtitle',
        'action_url',
        'image',
        'icon',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function widget(): BelongsTo
    {
        return $this->belongsTo(HomeWidget::class, 'home_widget_id');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;

        $url = str_starts_with($this->image, 'http')
            ? $this->image
            : asset($this->image);

        // Cache-busting: mobile clients cache images by URL. When an admin
        // replaces the photo but reuses the same file path, the URL is
        // unchanged and the app keeps showing the stale cached image. Appending
        // the record's last-updated timestamp changes the URL whenever the item
        // is edited, forcing the app to fetch the new photo.
        $version = $this->updated_at?->timestamp;
        if ($version) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $version;
        }

        return $url;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order)) {
                $maxOrder = static::where('home_widget_id', $model->home_widget_id)
                    ->max('order');
                $model->order = $maxOrder ? $maxOrder + 1 : 1;
            }
        });

        // Clear the cached home layout whenever an item changes so replaced
        // photos appear immediately instead of after the 5-minute cache expiry.
        static::saved(fn() => HomeWidget::flushHomeCache());
        static::deleted(fn() => HomeWidget::flushHomeCache());
    }
}
