<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;

class Appointment extends Model
{
    use AsSource, Filterable;

    protected $fillable = [
        'booker_id', 'target_user_id',
        'scheduled_at', 'duration_minutes', 'table_location',
        'status', 'notes', 'rating', 'feedback', 'history'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'history' => 'array',
    ];

    // ✅ ADDED: Allowed fields for sorting
    protected $allowedSorts = [
        'id',
        'status',
        'scheduled_at',
        'created_at',
        'table_location',
        // These are handled manually in the Screen, but good to list
        'booker.name',
        'targetUser.name',
    ];

    // ✅ ADDED: Allowed fields for filtering
    protected $allowedFilters = [
        'id',
        'status',
        'scheduled_at',
        'table_location',
        'created_at',
        // Note: Relationship filters (booker.name) are handled in the Screen query manually
    ];

    public function booker()
    {
        return $this->belongsTo(User::class, 'booker_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function logAction(string $action, ?string $reason = null)
    {
        // 1. Grab current history (or start an empty array if null)
        $currentHistory = $this->history ?? [];

        // 2. Add the new event
        $currentHistory[] = [
            'action' => $action,
            'actor_id' => auth()->id(), // Who did it?
            'reason' => $reason,
            'timestamp' => now()->toDateTimeString(), // When?
        ];

        // 3. Save it back to the database
        $this->update(['history' => $currentHistory]);
    }
}
