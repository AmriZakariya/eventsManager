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

class NetworkingController extends Controller
{
    /**
     * TAB 1: Discover
     * Users I have NO relationship with
     */
    public function discover(Request $request): JsonResponse
    {
        $authId = Auth::id();

        /**
         * 1. EXCLUSION LIST
         * Get IDs of people I am already connected with (Pending, Accepted, Declined)
         * + My Own ID
         */
        $excludedIds = Connection::where('requester_id', $authId)
            ->orWhere('target_id', $authId)
            ->get()
            ->map(fn ($c) => $c->requester_id === $authId ? $c->target_id : $c->requester_id)
            ->push($authId)
            ->toBase()   // 👈 key line
            ->unique()
            ->values()
            ->all();


        /**
         * 2. BASE QUERY
         * 'with(company)' will return null for Visitors, which is fine.
         * Ensure your User model does NOT have a 'has("company")' global scope.
         */
        $query = User::with('company')
            ->whereNotIn('id', $excludedIds)
            ->where('is_visible', true);

        /**
         * 3. SEARCH LOGIC
         * Improved to search by Job Title (important for Visitors).
         */
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%") // ✅ Added for Visitors
                    ->orWhereHas('company', fn ($c) =>
                    $c->where('name', 'like', "%{$search}%")
                    );
            });
        }

        // 4. ORDERING & PAGINATION
        // Random order is good for discovery, or order by created_at
        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * TAB 2: My Network
     */
    public function myNetwork(): JsonResponse
    {
        $authId = Auth::id();

        /**
         * Incoming pending requests (they sent → me)
         */
        $incoming = Connection::where('target_id', $authId)
            ->where('status', 'pending')
            ->with('requester.company')
            ->get()
            ->map(function ($c) {
                $user = $c->requester;
                $user->connection_status = 'incoming';
                return $user;
            });

        /**
         * Outgoing pending requests (I sent → them)
         */
        $outgoing = Connection::where('requester_id', $authId)
            ->where('status', 'pending')
            ->with('target.company')
            ->get()
            ->map(function ($c) {
                $user = $c->target;
                $user->connection_status = 'outgoing';
                return $user;
            });

        /**
         * Accepted connections
         */
        $accepted = Connection::where('status', 'accepted')
            ->where(function ($q) use ($authId) {
                $q->where('requester_id', $authId)
                    ->orWhere('target_id', $authId);
            })
            ->with(['requester.company', 'target.company'])
            ->get()
            ->map(function ($c) use ($authId) {
                $user = $c->requester_id === $authId
                    ? $c->target
                    : $c->requester;

                $user->connection_status = 'accepted';
                return $user;
            });

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
            'target_id' => 'required|exists:users,id',
            'action'    => 'required|in:connect,accept,decline,cancel',
        ]);

        $authId   = Auth::id();
        $targetId = (int) $request->target_id;
        $user     = Auth::user(); // Me

        if ($authId === $targetId) {
            return response()->json(['error' => 'Cannot connect to yourself'], 422);
        }

        switch ($request->action) {

            case 'connect':
                // 1. Create Connection
                $connection = Connection::firstOrCreate(
                    [
                        'requester_id' => $authId,
                        'target_id'    => $targetId,
                    ],
                    ['status' => 'pending']
                );

                // 2. Notify Target (Only if it's a new request)
                if ($connection->wasRecentlyCreated) {
                    $target = User::find($targetId);
                    if ($target) {
                        $target->notify(new NewConnectionRequest($user));
                    }
                }
                break;

            case 'accept':
                // 1. Find the request sent TO me
                $connection = Connection::where('requester_id', $targetId)
                    ->where('target_id', $authId)
                    ->first();

                if ($connection && $connection->status !== 'accepted') {
                    // 2. Update Status
                    $connection->update(['status' => 'accepted']);

                    // 3. Notify the Original Requester
                    $requester = User::find($targetId);
                    if ($requester) {
                        $requester->notify(new ConnectionAccepted($user));
                    }
                }
                break;

            case 'decline':
            case 'cancel':
                Connection::where(function ($q) use ($authId, $targetId) {
                    $q->where('requester_id', $authId)->where('target_id', $targetId);
                })->orWhere(function ($q) use ($authId, $targetId) {
                    $q->where('requester_id', $targetId)->where('target_id', $authId);
                })->delete();
                break;
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Legacy list (fallback / old clients)
     */
    public function index(): JsonResponse
    {
        return response()->json(
            User::with('company')
                ->where('id', '!=', Auth::id())
                ->paginate(20)
        );
    }
}
