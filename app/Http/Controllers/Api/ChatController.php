<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * GET /api/chat/conversations
     * List all users I have chatted with, ordered by latest message.
     */
    public function conversations(Request $request)
    {
        $validated = $request->validate([
            'after_message_id' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $userId = $request->user()->id;
        $limit = $validated['limit'] ?? 50;
        $afterMessageId = $validated['after_message_id'] ?? null;

        $conversations = Message::query()
            ->selectRaw(
                'CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id, MAX(id) as latest_message_id, MAX(created_at) as last_message_at',
                [$userId]
            )
            ->where(function($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->when($afterMessageId, fn ($query) => $query->where('id', '>', $afterMessageId))
            ->groupBy('other_user_id')
            ->orderByDesc('latest_message_id')
            ->limit($limit)
            ->get();

        $users = User::with('company')
            ->withConnectionStatusFor($userId)
            ->whereIn('id', $conversations->pluck('other_user_id'))
            ->get()
            ->keyBy('id');

        $latestMessages = Message::whereIn('id', $conversations->pluck('latest_message_id'))
            ->get()
            ->keyBy('id');

        $unreadCounts = Message::query()
            ->select('sender_id', DB::raw('COUNT(*) as unread_count'))
            ->where('receiver_id', $userId)
            ->whereIn('sender_id', $conversations->pluck('other_user_id'))
            ->whereNull('read_at')
            ->groupBy('sender_id')
            ->pluck('unread_count', 'sender_id');

        $results = $conversations->map(function ($item) use ($users, $latestMessages, $unreadCounts) {
            $user = $users->get($item->other_user_id);
            $lastMsg = $latestMessages->get($item->latest_message_id);

            if (!$user || !$lastMsg) {
                return null;
            }

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => trim($user->name . ' ' . $user->last_name),
                    'avatar_url' => $user->avatar_url ?? "https://ui-avatars.com/api/?name={$user->name}",
                    'company' => $user->company->name ?? 'Visitor'
                ],
                'latest_message_id' => $lastMsg->id,
                'last_message' => $lastMsg->content ?? '[Attachment]',
                'last_message_at' => $lastMsg->created_at,
                'unread_count' => (int) ($unreadCounts[$item->other_user_id] ?? 0),
            ];
        })->filter()->values();

        return response()->json($results);
    }

    /**
     * GET /api/chat/unread-counts
     * Returns unread chat totals for mobile badges.
     */
    public function unreadCounts(Request $request)
    {
        $userId = $request->user()->id;

        $unreadMessagesCount = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->count();

        $unreadConversationsCount = Message::where('receiver_id', $userId)
            ->whereNull('read_at')
            ->distinct('sender_id')
            ->count('sender_id');

        return response()->json([
            'unread_messages_count' => $unreadMessagesCount,
            'unread_conversations_count' => $unreadConversationsCount,
        ]);
    }

    /**
     * GET /api/chat/messages/{userId}
     * Returns messages NEWEST first (for reverse scrolling).
     */
    public function messages(Request $request, $otherUserId)
    {
        $validated = $request->validate([
            'after_id' => 'nullable|integer|min:1',
            'before_id' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if (!empty($validated['after_id']) && !empty($validated['before_id'])) {
            return response()->json([
                'message' => 'Use either after_id or before_id, not both.',
            ], 422);
        }

        $myId = $request->user()->id;
        $limit = $validated['limit'] ?? 50;
        $afterId = $validated['after_id'] ?? null;
        $beforeId = $validated['before_id'] ?? null;

        $query = Message::where(function ($query) use ($myId, $otherUserId) {
            $query->where(function ($q) use ($myId, $otherUserId) {
                $q->where('sender_id', $myId)->where('receiver_id', $otherUserId);
            })->orWhere(function ($q) use ($myId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $myId);
            });
        });

        if ($afterId) {
            $query->where('id', '>', $afterId)->orderBy('id');
        } else {
            $query->when($beforeId, fn ($q) => $q->where('id', '<', $beforeId))
                ->orderByDesc('id');
        }

        return $query->paginate($limit);
    }

    /**
     * POST /api/chat/messages/{userId}/read
     * Marks incoming messages in a conversation as read.
     */
    public function markRead(Request $request, $otherUserId)
    {
        $myId = $request->user()->id;

        $updated = Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $myId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'marked_read_count' => $updated,
        ]);
    }

    /**
     * POST /api/chat/send
     * Send text or file.
     */
    // ChatController.php - Update send() validation

    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content'     => 'nullable|string',
            'file'        => 'nullable|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mp3,wav,pdf,doc,docx,txt',
            // 50MB max, supports images, videos, audio, documents
        ]);

        if (!$request->content && !$request->hasFile('file')) {
            return response()->json(['message' => 'Message cannot be empty'], 422);
        }

        $path = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Organize by type
            $folder = match(true) {
                str_starts_with($file->getMimeType(), 'image/') => 'chat_files/images',
                str_starts_with($file->getMimeType(), 'video/') => 'chat_files/videos',
                str_starts_with($file->getMimeType(), 'audio/') => 'chat_files/audio',
                default => 'chat_files/documents'
            };

            $path = $file->store($folder, 'public');
            $path = asset('storage/' . $path);
        }

        $receiver = User::findOrFail($request->receiver_id);

        $message = Message::create([
            'sender_id'   => $request->user()->id,
            'receiver_id' => $receiver->id,
            'content'     => $request->content ?? '',
            'attachment_url' => $path,
        ]);

        if ($receiver->id !== $request->user()->id) {
            $receiver->notify(new NewMessageReceived($message, $request->user()));
        }

        return response()->json($message);
    }
}
