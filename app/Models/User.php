<?php

namespace App\Models;

use App\Models\Concerns\AuditableModel;
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
    use AuditableModel, HasApiTokens, HasFactory, Notifiable;

    /**
     * Columns the admin user list may sort by. Company is sorted by name in
     * UserListScreen, so it is not listed here.
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'job_title',
        'is_visible',
        'updated_at',
        'created_at',
    ];

    public const APP_ROLE_ADMIN = 'admin';
    public const APP_ROLE_EXHIBITOR = 'exhibitor';
    public const APP_ROLE_VISITOR = 'visitor';
    public const CREATED_SOURCE_APP = 'app';
    public const CREATED_SOURCE_WORDPRESS = 'wordpress';
    public const WORDPRESS_SYNC_SOURCE_APP = 'app';
    public const WORDPRESS_SYNC_SOURCE_WORDPRESS = 'wordpress';

    public const SIGNUP_EMAIL = 'email';
    public const SIGNUP_GOOGLE = 'google';
    public const SIGNUP_APPLE = 'apple';
    public const SIGNUP_LINKEDIN = 'linkedin';
    public const SIGNUP_FACEBOOK = 'facebook';
    public const SIGNUP_SOCIAL = 'social'; // social, provider not recorded (before tracking)
    public const SIGNUP_WORDPRESS = 'wordpress';
    public const SIGNUP_ADMIN = 'admin';

    /**
     * Social sign-up creates the account with this placeholder in the fields
     * below; the app asks for them on its Complete Profile screen.
     */
    public const PROFILE_PLACEHOLDER = 'N/A';
    public const PROFILE_REQUIRED_FIELDS = ['phone', 'city', 'country'];

    public static function appRoleOptions(): array
    {
        return [
            self::APP_ROLE_VISITOR => 'Visitor',
            self::APP_ROLE_EXHIBITOR => 'Exhibitor',
        ];
    }

    public const PLATFORMS = ['android' => 'Android', 'ios' => 'iOS'];

    /** "Android" / "iOS" header value from the app, or null if unknown. */
    public static function normalizePlatform(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return array_key_exists($value, self::PLATFORMS) ? $value : null;
    }

    public function platformLabel(): string
    {
        return self::PLATFORMS[$this->platform] ?? 'Unknown';
    }

    /** How the account was created; set once, when the user is created. */
    public static function signupMethodOptions(): array
    {
        return [
            self::SIGNUP_EMAIL     => 'Email',
            self::SIGNUP_GOOGLE    => 'Google',
            self::SIGNUP_APPLE     => 'Apple',
            self::SIGNUP_LINKEDIN  => 'LinkedIn',
            self::SIGNUP_FACEBOOK  => 'Facebook',
            self::SIGNUP_SOCIAL    => 'Social (provider unknown)',
            self::SIGNUP_WORDPRESS => 'WordPress',
            self::SIGNUP_ADMIN     => 'Admin',
        ];
    }

    public function signupMethodLabel(): string
    {
        return self::signupMethodOptions()[$this->signup_method] ?? 'Unknown';
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
        'banned_at',
        'ban_reason',
        'banned_by',
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
        'platform_seen_at'     => 'datetime',
        'banned_at'            => 'datetime',
        'wordpress_synced_at'  => 'datetime',
    ];

    protected $appends = [
        'avatar_url',
        'role',
        'connection_status',
        'profile_completed',
        'needs_profile_completion',
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

    /**
     * Complete = a password is set (WordPress/reservation users have none yet)
     * and the social sign-up placeholders were replaced. Same rule as the app's
     * Complete Profile screen. NULL values count as filled, as in the app.
     */
    public function scopeNotBanned(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('banned_at'));
    }

    /** Subquery of banned user IDs, for whereNotIn() on foreign keys. */
    public static function bannedIds(): Builder
    {
        return static::query()->whereNotNull('banned_at')->select('id');
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * Block the account: logs it out everywhere and stops its push
     * notifications. Its messages are hidden by the chat endpoints, not deleted.
     */
    public function ban(string $reason, ?int $bannedBy = null): void
    {
        $this->forceFill([
            'banned_at'  => now(),
            'ban_reason' => $reason,
            'banned_by'  => $bannedBy,
            'fcm_token'  => null,
        ])->save();

        $this->tokens()->delete();
    }

    public function unban(): void
    {
        $this->forceFill([
            'banned_at'  => null,
            'ban_reason' => null,
            'banned_by'  => null,
        ])->save();
    }

    public function scopeProfileCompleted(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('password_is_set'), true)
            ->where(function (Builder $query) {
                foreach (self::PROFILE_REQUIRED_FIELDS as $field) {
                    $column = $query->qualifyColumn($field);
                    $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, '!=', self::PROFILE_PLACEHOLDER));
                }
            });
    }

    public function scopeProfileIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where($query->qualifyColumn('password_is_set'), false)
                ->orWhereIn($query->qualifyColumn('phone'), [self::PROFILE_PLACEHOLDER])
                ->orWhereIn($query->qualifyColumn('city'), [self::PROFILE_PLACEHOLDER])
                ->orWhereIn($query->qualifyColumn('country'), [self::PROFILE_PLACEHOLDER]);
        });
    }

    public function scopeWithConnectionStatusFor(Builder $query, ?int $authId): Builder
    {
        if (!$authId) {
            return $query;
        }

        return $query->addSelect([
            'connection_status' => Connection::query()
                ->selectRaw(
                    "CASE
                        WHEN status = 'accepted' THEN 'accepted'
                        WHEN status = 'pending' AND requester_id = ? THEN 'outgoing'
                        WHEN status = 'pending' AND target_id = ? THEN 'incoming'
                        ELSE 'none'
                    END",
                    [$authId, $authId]
                )
                ->where(function ($q) use ($authId) {
                    $q->where(function ($q) use ($authId) {
                        $q->whereColumn('requester_id', 'users.id')
                            ->where('target_id', $authId);
                    })->orWhere(function ($q) use ($authId) {
                        $q->whereColumn('target_id', 'users.id')
                            ->where('requester_id', $authId);
                    });
                })
                ->limit(1),
        ]);
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

    public function getProfileCompletedAttribute(): bool
    {
        return (bool) $this->password_is_set && ! $this->hasProfilePlaceholders();
    }

    /** True when social sign-up placeholders (N/A) are still in the profile. */
    public function hasProfilePlaceholders(): bool
    {
        foreach (self::PROFILE_REQUIRED_FIELDS as $field) {
            if ($this->getAttribute($field) === self::PROFILE_PLACEHOLDER) {
                return true;
            }
        }

        return false;
    }

    public function getNeedsProfileCompletionAttribute(): bool
    {
        return !$this->profile_completed;
    }

    public function profileCompletionLabel(): string
    {
        return $this->profile_completed ? 'Profile Complete' : 'Needs Profile';
    }

    public function profileCompletionBadgeClass(): string
    {
        return $this->profile_completed ? 'bg-success' : 'bg-warning text-dark';
    }

    public function profileCompletionBadgeTitle(): string
    {
        if ($this->profile_completed) {
            return 'This user has completed signup and has a password.';
        }

        return $this->password_is_set
            ? 'Signed up with Google, Apple or LinkedIn but has not finished the Complete Profile step (phone, city or country still N/A).'
            : 'No password is set. The user probably came from WordPress or a reservation and still needs to complete their profile.';
    }

    /**
     * Avatar URL for the ADMIN PANEL. Remote hosts that block cross-site
     * hotlinking (LinkedIn) are routed through our image proxy so they render
     * inline; local and freely-hotlinkable URLs are returned untouched. The
     * mobile API keeps using `avatar_url` directly (never the proxy).
     */
    public function adminAvatarUrl(): ?string
    {
        $url = $this->avatar_url;

        if (!$url) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host !== '' && (\Illuminate\Support\Str::endsWith($host, 'licdn.com') || $host === 'licdn.com')) {
            return route('platform.avatar-proxy', ['u' => $url]);
        }

        return $url;
    }

    /**
     * Reusable admin avatar thumbnail: real profile photo when available,
     * otherwise a colored circle with the user's initial (or a fallback icon).
     *
     * Uses the `avatar_url` accessor (pure string building, no extra query) and
     * lazy-loads the image so long lists don't fetch every photo at once.
     */
    public function avatarThumbHtml(int $size = 36, string $fallbackBg = 'bg-secondary', ?string $fallbackIcon = null): string
    {
        if ($adminUrl = $this->adminAvatarUrl()) {
            return sprintf(
                '<img src="%1$s" alt="" loading="lazy" referrerpolicy="no-referrer" '
                . 'style="width:%2$dpx;height:%2$dpx;border-radius:50%%;object-fit:cover;'
                . 'border:1px solid #e2e8f0;flex-shrink:0;background:#f1f5f9;" '
                . 'onerror="this.style.display=\'none\'">',
                e($adminUrl),
                $size
            );
        }

        $inner = $fallbackIcon ?? e(strtoupper(mb_substr($this->full_name ?: ($this->name ?: '?'), 0, 1)));
        $fontSize = (int) round($size * 0.42);

        return sprintf(
            '<span class="%1$s text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0" '
            . 'style="width:%2$dpx;height:%2$dpx;border-radius:50%%;font-size:%3$dpx;">%4$s</span>',
            e($fallbackBg),
            $size,
            $fontSize,
            $inner
        );
    }

    public function profileCompletionBadgeHtml(string $extraClass = ''): string
    {
        $class = trim('badge '.$this->profileCompletionBadgeClass().' '.$extraClass);

        return sprintf(
            '<span class="%s" title="%s">%s</span>',
            e($class),
            e($this->profileCompletionBadgeTitle()),
            e($this->profileCompletionLabel())
        );
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
        return $this->attributes['connection_status'] ?? 'none';
    }
}
