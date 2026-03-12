<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Orchid\Platform\Models\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'permissions',
        'locale',

        // Extended Profile Fields
        'phone',
        'avatar',
        'bio',
        'job_title',
        'country',       // New
        'city',          // New
        'company_sector', // New
        'company_name',   // New (For Visitors)

        // Relations & IDs
        'company_id',
        'linkedin_url',
        'linkedin_id',
        'google_id',

        // System Fields
        'badge_code',
        'fcm_token',
        'is_visible'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions'          => 'array',
        'email_verified_at'    => 'datetime',
        'is_visible'           => 'boolean',
    ];

    protected $appends = [
        'avatar_url',
        'role',
        'connection_status'
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

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        // If already full URL
        if (Str::startsWith($this->avatar, ['http://', 'https://'])) {
            return $this->avatar;
        }

        return asset($this->avatar);
    }

    public function scopeSearchFullName(Builder $query, $term)
    {
        $term = "%{$term}%";

        return $query->where(function ($q) use ($term) {
            // Match "First"
            $q->where('name', 'like', $term)
                // Match "Last"
                ->orWhere('last_name', 'like', $term)
                // Match "First Last" (e.g. "John Doe")
                ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", [$term]);
        });
    }

    public function getRoleAttribute(): string
    {
        // If they are attached to a company, they are an exhibitor.
        // Otherwise, they are a visitor.
        return $this->company_id ? 'exhibitor' : 'visitor';
    }

    public function getConnectionStatusAttribute(): string
    {
        // Get the ID of the person currently logged in
        $authId = auth('sanctum')->id() ?? auth()->id();

        // If not logged in or looking at own profile
        if (!$authId || $authId === $this->id) {
            return 'none authid nulll';
        }

        // Search for a connection in either direction
        $connection = Connection::where(function ($q) use ($authId) {
            $q->where('requester_id', $authId)->where('target_id', $this->id);
        })
            ->orWhere(function ($q) use ($authId) {
                $q->where('requester_id', $this->id)->where('target_id', $authId);
            })
            ->first();

        if (!$connection) {
            return 'none';
        }

        if ($connection->status === 'accepted') {
            return 'accepted';
        }

        if ($connection->status === 'pending') {
            // Did I send it, or did they?
            return ($connection->requester_id === $authId) ? 'outgoing' : 'incoming';
        }

        return 'none';
    }
}
