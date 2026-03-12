<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Connection;
use App\Notifications\ConnectionAccepted;
use App\Notifications\NewConnectionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class NetworkingController extends Controller
{
    /**
     * TAB 1: Discover
     * Users I have NO relationship with (Optimized)
     */
    public function discover(Request $request): JsonResponse
    {
        $authId = Auth::id();

        $query = User::with('company')
            ->where('id', '!=', $authId)
            ->where('is_visible', true)
            ->addSelect([
                'connection_status' => Connection::selectRaw('
                CASE
                    WHEN status = "accepted" THEN "accepted"
                    WHEN status = "pending" AND requester_id = ' . $authId . ' THEN "outgoing"
                    WHEN status = "pending" AND target_id = ' . $authId . ' THEN "incoming"
                    ELSE "none"
                END
            ')
                    ->where(function($q) use ($authId) {
                        $q->whereColumn('requester_id', 'users.id')->where('target_id', $authId)
                            ->orWhereColumn('target_id', 'users.id')->where('requester_id', $authId);
                    })
                    ->limit(1)
            ]);

        // ... rest of search logic and pagination
        return response()->json($query->paginate(20));
    }

    /**
     * TAB 2: My Network
     * Fixed duplicate user issue
     */
    public function myNetwork(): JsonResponse
    {
        $authId = Auth::id();

        /**
         * 1. Incoming (They sent -> Me)
         */
        $incoming = Connection::with('requester.company')
            ->where('target_id', $authId)
            ->where('status', 'pending')
            ->get()
            ->map(function ($c) {
                $user = $c->requester;
                $user->connection_status = 'incoming';
                $user->connection_id = $c->id; // Useful for UI actions
                return $user;
            })
            ->unique('id') // 🛡️ Fix: Prevent duplicates
            ->values();

        /**
         * 2. Outgoing (I sent -> Them)
         */
        $outgoing = Connection::with('target.company')
            ->where('requester_id', $authId)
            ->where('status', 'pending')
            ->get()
            ->map(function ($c) {
                $user = $c->target;
                $user->connection_status = 'outgoing';
                $user->connection_id = $c->id;
                return $user;
            })
            ->unique('id') // 🛡️ Fix: Prevent duplicates
            ->values();

        /**
         * 3. Accepted (Bidirectional)
         */
        $accepted = Connection::with(['requester.company', 'target.company'])
            ->where('status', 'accepted')
            ->where(function ($q) use ($authId) {
                $q->where('requester_id', $authId)
                    ->orWhere('target_id', $authId);
            })
            ->get()
            ->map(function ($c) use ($authId) {
                // Determine which user is the "other" person
                $isRequesterMe = $c->requester_id === $authId;
                $user = $isRequesterMe ? $c->target : $c->requester;

                $user->connection_status = 'accepted';
                $user->connection_id = $c->id;
                return $user;
            })
            ->unique('id') // 🛡️ Fix: Prevents "Same User Twice" if DB has dirty data
            ->values();

        return response()->json([
            'incoming_requests' => $incoming,
            'outgoing_requests' => $outgoing,
            'connections'       => $accepted,
        ]);
    }

    /**
     * ACTIONS: connect | accept | decline | cancel
     */
    public function toggleConnection(Request $request): JsonResponse
    {
        $request->validate([
            'target_id' => 'required|integer|exists:users,id',
            'action'    => 'required|string|in:connect,accept,decline,cancel',
        ]);

        $authId   = Auth::id();
        $targetId = (int) $request->target_id;
        $user     = Auth::user();

        if ($authId === $targetId) {
            return response()->json(['message' => 'Cannot connect to yourself'], 422);
        }

        // Handle Actions
        switch ($request->action) {
            case 'connect':
                // Check if ANY connection exists (pending or accepted) to prevent duplicates
                $exists = Connection::where(function($q) use ($authId, $targetId){
                    $q->where('requester_id', $authId)->where('target_id', $targetId);
                })->orWhere(function($q) use ($authId, $targetId){
                    $q->where('requester_id', $targetId)->where('target_id', $authId);
                })->exists();

                if (!$exists) {
                    $conn = Connection::create([
                        'requester_id' => $authId,
                        'target_id'    => $targetId,
                        'status'       => 'pending'
                    ]);

                    // Notify Target
                    $targetUser = User::find($targetId);
                    if ($targetUser) {
                        $targetUser->notify(new NewConnectionRequest($user));
                    }
                }
                break;

            case 'accept':
                // Find the specific pending request sent TO me
                $connection = Connection::where('requester_id', $targetId)
                    ->where('target_id', $authId)
                    ->where('status', 'pending')
                    ->first();

                if ($connection) {
                    $connection->update(['status' => 'accepted']);

                    // Notify Requester
                    $requester = User::find($targetId);
                    if ($requester) {
                        $requester->notify(new ConnectionAccepted($user));
                    }
                }
                break;

            case 'decline':
                // Delete request sent TO me
                Connection::where('requester_id', $targetId)
                    ->where('target_id', $authId)
                    ->delete();
                break;

            case 'cancel':
                // Delete request sent BY me
                Connection::where('requester_id', $authId)
                    ->where('target_id', $targetId)
                    ->where('status', 'pending')
                    ->delete();
                break;
        }

        return response()->json(['message' => 'Action successful']);
    }
}
