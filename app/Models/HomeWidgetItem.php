<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeWidgetItem extends Model
{
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
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset($this->image);
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
    }
}
