<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type', // 'alert', 'info', 'promo'
        'data',
        'is_read'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Hides "new message" notifications sent by banned accounts. */
    public function scopeWithoutBannedSenders(Builder $query): Builder
    {
        $bannedIds = User::whereNotNull('banned_at')->pluck('id')->map(fn ($id) => (string) $id)->all();

        if ($bannedIds === []) {
            return $query;
        }

        // Each branch is NULL-safe, so notifications without these JSON keys stay visible.
        return $query->where(function (Builder $q) use ($bannedIds) {
            $q->whereNull('data->screen')
                ->orWhere('data->screen', '!=', '/chat')
                ->orWhereNull('data->arg')
                ->orWhereNotIn('data->arg', $bannedIds);
        });
    }
}
