<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Orchid\Platform\Models\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Notifiable;

    protected $fillable = [
        'name', 'last_name', 'email', 'password', 'phone', 'avatar', 'bio',
        'job_title', 'company_id',
        'linkedin_url', 'linkedin_id', 'google_id',
        'badge_code', 'fcm_token', 'is_visible'
    ];

    protected $hidden = ['password', 'remember_token', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
        'email_verified_at' => 'datetime',
        'is_visible' => 'boolean',
    ];

    // --- Relationships ---

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function appointmentsBooked()
    {
        return $this->hasMany(Appointment::class, 'booker_id');
    }

    public function appointmentsReceived()
    {
        return $this->hasMany(Appointment::class, 'target_user_id');
    }

    // --- Scopes for Orchid Filtering ---

    /**
     * Scope: Users who are Visitors (No Company attached)
     */
    public function scopeVisitors(Builder $query)
    {
        return $query->whereNull('company_id');
    }

    /**
     * Scope: Users who are Exhibitors (Attached to a Company)
     */
    public function scopeExhibitors(Builder $query)
    {
        return $query->whereNotNull('company_id');
    }

    // --- Accessors for Orchid Display ---

    /**
     * Accessor: full_name
     * Usage: $user->full_name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Accessor: full_name_with_company
     * Usage: $user->full_name_with_company
     */
    public function getFullNameWithCompanyAttribute(): string
    {
        $name = $this->full_name;
        if ($this->company) {
            return "{$name} ({$this->company->name})";
        }
        return $name;
    }
}
