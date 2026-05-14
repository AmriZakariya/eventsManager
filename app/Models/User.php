<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Orchid\Platform\Models\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Platform\Models\Role as OrchidRole;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Notifiable;

    public const APP_ROLE_ADMIN = 'admin';
    public const APP_ROLE_EXHIBITOR = 'exhibitor';
    public const APP_ROLE_VISITOR = 'visitor';
    public const CREATED_SOURCE_APP = 'app';
    public const CREATED_SOURCE_WORDPRESS = 'wordpress';
    public const WORDPRESS_SYNC_SOURCE_APP = 'app';
    public const WORDPRESS_SYNC_SOURCE_WORDPRESS = 'wordpress';

    public static function appRoleOptions(): array
    {
        return [
            self::APP_ROLE_VISITOR => 'Visitor',
            self::APP_ROLE_EXHIBITOR => 'Exhibitor',
        ];
    }

    public static function createdSourceOptions(): array
    {
        return [
            self::CREATED_SOURCE_APP => 'App',
            self::CREATED_SOURCE_WORDPRESS => 'WordPress',
        ];
    }

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
        'password_is_set',
        'permissions',
        'locale',
        'app_role',
        'created_source',
        'is_wordpress_synced',
        'wordpress_sync_source',
        'wordpress_synced_at',
        'wordpress_sync_error',

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
        'password_is_set'      => 'boolean',
        'is_wordpress_synced'  => 'boolean',
        'wordpress_synced_at'  => 'datetime',
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
        return $query->where('app_role', self::APP_ROLE_VISITOR);
    }

    /**
     * Scope: Users who are Exhibitors (Attached to a Company)
     */
    public function scopeExhibitors(Builder $query)
    {
        return $query->where('app_role', self::APP_ROLE_EXHIBITOR);
    }

    public function scopeAppRole(Builder $query, string $role)
    {
        return $query->where('app_role', $role);
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

        return Storage::url($this->avatar);
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
        return $this->app_role ?: $this->resolveLegacyAppRole();
    }

    public function syncAppRole(?string $role = null): void
    {
        $this->forceFill([
            'app_role' => $role ?: $this->resolveLegacyAppRole(),
        ])->save();
    }

    public function syncOrchidRoleFromAppRole(): void
    {
        $roleSlug = $this->role;
        $role = OrchidRole::where('slug', $roleSlug)->first();

        if (!$role) {
            return;
        }

        foreach ($this->roles as $existingRole) {
            $this->removeRole($existingRole);
        }

        $this->addRole($role);
        $this->unsetRelation('roles');
    }

    public function syncRoleSystems(?string $role = null): void
    {
        $this->syncAppRole($role);
        $this->loadMissing('roles');
        $this->syncOrchidRoleFromAppRole();
    }

    public function resolveLegacyAppRole(): string
    {
        $roleSlug = $this->roles->first()?->slug;

        if ($roleSlug) {
            return $roleSlug;
        }

        return $this->company_id ? self::APP_ROLE_EXHIBITOR : self::APP_ROLE_VISITOR;
    }

    public function appRoleLabel(): string
    {
        return self::appRoleOptions()[$this->role] ?? ucfirst($this->role);
    }

    public function orchidRoleSlugs(): array
    {
        return $this->roles->pluck('slug')->all();
    }

    public function orchidRoleNames(): array
    {
        return $this->roles->pluck('name')->all();
    }

    public function adminPanelRolesLabel(): string
    {
        $roles = collect($this->orchidRoleNames())
            ->filter()
            ->unique()
            ->values()
            ->implode(' / ');

        return $roles !== '' ? $roles : 'none';
    }

    public function createdSourceLabel(): string
    {
        return self::createdSourceOptions()[$this->created_source] ?? ucfirst((string) $this->created_source);
    }

    public function markWordPressSyncSuccess(string $source = self::WORDPRESS_SYNC_SOURCE_APP): void
    {
        $this->forceFill([
            'is_wordpress_synced' => true,
            'wordpress_sync_source' => $source,
            'wordpress_synced_at' => now(),
            'wordpress_sync_error' => null,
        ])->save();
    }

    public function markWordPressSyncFailure(?string $error = null, string $source = self::WORDPRESS_SYNC_SOURCE_APP): void
    {
        $this->forceFill([
            'is_wordpress_synced' => false,
            'wordpress_sync_source' => $source,
            'wordpress_sync_error' => $error,
        ])->save();
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
