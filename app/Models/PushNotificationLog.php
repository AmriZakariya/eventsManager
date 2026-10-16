<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

/**
 * One row per push attempt made by FcmChannel. Shown on the admin
 * "Push Notifications" page and on each user's edit page.
 */
class PushNotificationLog extends Model
{
    use AsSource, Prunable;

    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'notification', 'title', 'status',
        'error_code', 'error_message', 'http_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Kept for 30 days; removed by the scheduled model:prune. */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(30));
    }
}
