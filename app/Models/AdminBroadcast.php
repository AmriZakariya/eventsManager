<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

/** A push notification composed in the admin and sent to an audience. */
class AdminBroadcast extends Model
{
    use AsSource;

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_VISITORS = 'visitors';
    public const AUDIENCE_EXHIBITORS = 'exhibitors';
    public const AUDIENCE_SPECIFIC = 'specific';

    public const AUDIENCES = [
        self::AUDIENCE_ALL        => 'All users',
        self::AUDIENCE_VISITORS   => 'Visitors',
        self::AUDIENCE_EXHIBITORS => 'Exhibitors',
        self::AUDIENCE_SPECIFIC   => 'Specific users',
    ];

    protected $fillable = ['sent_by', 'audience', 'user_ids', 'titles', 'bodies', 'recipients_count'];

    protected $casts = [
        'user_ids' => 'array',
        'titles'   => 'array',
        'bodies'   => 'array',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PushNotificationLog::class, 'broadcast_id');
    }

    public function audienceLabel(): string
    {
        return self::AUDIENCES[$this->audience] ?? $this->audience;
    }

    /**
     * Users an audience reaches. Banned accounts never receive broadcasts.
     */
    public static function audienceQuery(string $audience, array $userIds = [])
    {
        return User::query()
            ->notBanned()
            ->when($audience === self::AUDIENCE_VISITORS, fn ($q) => $q->appRole(User::APP_ROLE_VISITOR))
            ->when($audience === self::AUDIENCE_EXHIBITORS, fn ($q) => $q->appRole(User::APP_ROLE_EXHIBITOR))
            ->when($audience === self::AUDIENCE_SPECIFIC, fn ($q) => $q->whereIn('id', $userIds));
    }
}
