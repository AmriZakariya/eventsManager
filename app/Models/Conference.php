<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class Conference extends Model
{
    use AsSource, HasLocalizedContent;

    protected $fillable = [
        'title', 'title_translations', 'start_time', 'end_time', 'location', 'description', 'description_translations', 'type'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'title_translations' => 'array',
        'description_translations' => 'array',
    ];

    public function speakers()
    {
        return $this->belongsToMany(Speaker::class, 'conference_speaker');
    }

    // Who registered for this talk?
    public function attendees()
    {
        return $this->belongsToMany(User::class, 'conference_registrations', 'conference_id', 'user_id');
    }
}
