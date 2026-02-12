<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Message extends Model
{
    use HasFactory, AsSource, Filterable;

    /**
     * The attributes that are mass assignable.
     * matches the schema: sender_id, receiver_id, content, attachment_url
     */
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'attachment_url',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * The user who sent the message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The user who received the message.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // =========================================================================
    // HELPER SCOPES
    // =========================================================================

    /**
     * Scope to get messages only between two specific users (bidirectional).
     * Usage: Message::between($userA, $userB)->get();
     */
    public function scopeBetween($query, $user1Id, $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user1Id)
                ->where('receiver_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user2Id)
                ->where('receiver_id', $user1Id);
        });
    }

    /**
     * Scope to filter unread messages.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
