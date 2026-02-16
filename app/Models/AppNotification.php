<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'phone',
        'avatar',
        'job_title',
        'company_id',     // Exhibitor Relation
        'company_name',   // Visitor Text Input
        'country',
        'city',
        'company_sector',
        'badge_code',
        'is_visible',
        'permissions',    // Orchid permissions
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
