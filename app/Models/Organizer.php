<?php

namespace App\Models;

use App\Models\Concerns\AuditableModel;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;

class Organizer extends Model
{
    use AsSource, Attachable, AuditableModel, Filterable;

    protected $fillable = [
        'first_name',
        'last_name',
        'job_function',
        'description',
        'photo' // Virtual attribute
    ];

    public function events() {
        return $this->belongsToMany(Event::class, 'event_organizer');
    }
}
