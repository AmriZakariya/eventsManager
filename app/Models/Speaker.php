<?php

namespace App\Models;

use App\Models\Concerns\AuditableModel;
use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Types\Like; // <--- 1. IMPORT THIS

class Speaker extends Model
{
    use AsSource, Attachable, AuditableModel, Filterable, HasLocalizedContent;

    protected $fillable = [
        'full_name', 'job_title', 'job_title_translations', 'company_name', 'photo', 'bio', 'bio_translations'
    ];

    protected $casts = [
        'job_title_translations' => 'array',
        'bio_translations' => 'array',
    ];

    protected $allowedSorts = [
        'full_name',
        'company_name',
        'created_at',
    ];

    // 2. UPDATE THIS ARRAY
    // Map the column name (key) to the Filter Class (value)
    protected $allowedFilters = [
        'full_name'    => Like::class,
        'company_name' => Like::class,
        'job_title'    => Like::class,
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) return null;
        if (str_starts_with($this->photo, 'http')) return $this->photo;
        return asset($this->photo);
    }

    public function conferences()
    {
        return $this->belongsToMany(Conference::class, 'conference_speaker');
    }
}
