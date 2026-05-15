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
        $userId = $request->user()->id;

        // Complex Query: Find the latest message for every unique pair involving the user
        // This effectively gets the "Inbox" list
        $conversations = Message::select(
            DB::raw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id'),
            DB::raw('MAX(created_at) as last_message_at')
        )
            ->setBindings([$userId])
            ->where(function($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->groupBy('other_user_id')
            ->orderBy('last_message_at', 'desc')
            ->get();

        // Hydrate the User models and attach the last message content
        $results = $conversations->map(function ($item) use ($userId) {
            $user = User::find($item->other_user_id);

            // Fetch actual last message content
            $lastMsg = Message::where(function($q) use ($userId, $item) {
                $q->where('sender_id', $userId)->where('receiver_id', $item->other_user_id);
            })
                ->orWhere(function($q) use ($userId, $item) {
                    $q->where('sender_id', $item->other_user_id)->where('receiver_id', $userId);
                })
                ->latest()
                ->first();

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name . ' ' . $user->last_name,
                    'avatar_url' => $user->avatar_url ?? "https://ui-avatars.com/api/?name={$user->name}",
                    'company' => $user->company->name ?? 'Visitor'
                ],
                'last_message' => $lastMsg->content ?? '[Attachment]',
                'last_message_at' => $lastMsg->created_at,
                'unread_count' => Message::where('sender_id', $item->other_user_id)
                    ->where('receiver_id', $userId)
                    ->whereNull('read_at')
                    ->count()
            ];
        });

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
        $myId = $request->user()->id;

        // Mark incoming messages as read
        Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $myId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Message::where(function($q) use ($myId, $otherUserId) {
            $q->where('sender_id', $myId)->where('receiver_id', $otherUserId);
        })
            ->orWhere(function($q) use ($myId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $myId);
            })
            // IMPORTANT: Get newest first for the chat UI
            ->orderBy('created_at', 'desc')
            ->paginate(50);
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
