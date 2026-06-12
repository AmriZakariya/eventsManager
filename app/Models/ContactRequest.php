<?php

namespace App\Models;

use App\Models\Concerns\AuditableModel;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;

class ContactRequest extends Model
{
    use AsSource, AuditableModel, Filterable;

    protected $fillable = ['name', 'email', 'subject', 'message', 'is_handled'];

    protected $casts = [
        'is_handled' => 'boolean',
    ];
}
