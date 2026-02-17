<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
// Import Notifications
use App\Notifications\NewMeetingRequest;
use App\Notifications\MeetingStatusUpdated;

class B2BController extends Controller
{
    /**
     * 1. GET /api/my-appointments
     * Query Params: ?all=true (for full list), ?page=1 (for pagination)
     */
    public function myAppointments(Request $request)
    {
        $userId = $request->user()->id;

        $query = Appointment::with(['booker', 'targetUser.company'])
            ->where(function($q) use ($userId) {
                $q->where('booker_id', $userId)
                    ->orWhere('target_user_id', $userId);
            })
            ->orderBy('scheduled_at', 'desc');

        // Optional Pagination
        if ($request->boolean('all')) {
            return response()->json(['data' => $query->get()]);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * 2. GET /api/user-availability/{userId}
     * Returns slots where the target user is busy.
     */
    public function userAvailability($userId)
    {
        $appointments = Appointment::with(['targetUser', 'booker'])
            ->where('target_user_id', $userId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json(['data' => $appointments]);
    }

    /**
     * 3. GET /api/exhibitors
     * Fetches users who belong to a company.
     * Query Params: ?search=xxx, ?all=true
     */
    public function exhibitors(Request $request)
    {
        // Assuming "Exhibitors" are users with a company_id
        $query = User::with('company')
            ->whereNotNull('company_id')
            ->orderBy('name', 'asc');

        // Search Filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {

                // 1. Use your new Model Scope here!
                $q->searchFullName($search)

                    // 2. Continue with other OR conditions
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhereHas('company', fn ($c) =>
                    $c->where('name', 'like', "%{$search}%")
                    );
            });
        }

        // Optional Pagination
        if ($request->boolean('all')) {
            return response()->json(['data' => $query->get()]);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * 4. POST /api/book-meeting
     */
    public function bookMeeting(Request $request)
    {
        $request->validate([
            'target_user_id' => 'required|exists:users,id',
            'scheduled_at'   => 'required|date|after:now',
            'notes'          => 'nullable|string',
            'table_location' => 'nullable|string',
        ]);

        $visitor = $request->user();
        $targetUser = User::with('company')->findOrFail($request->target_user_id);

        $date = Carbon::parse($request->scheduled_at)->startOfDay();

        // --- RULE: One meeting per company per day ---
        if ($targetUser->company_id) {
            $existingMeeting = Appointment::where('booker_id', $visitor->id)
                ->whereDate('scheduled_at', $date)
                ->where('status', '!=', 'cancelled')
                ->whereHas('targetUser', function ($q) use ($targetUser) {
                    $q->where('company_id', $targetUser->company_id);
                })
                ->exists();

            if ($existingMeeting) {
                $companyName = $targetUser->company ? $targetUser->company->name : 'this company';
                return response()->json([
                    'message' => "You already have a meeting with $companyName on this day."
                ], 422);
            }
        }

        // Create
        $appointment = Appointment::create([
            'booker_id'      => $visitor->id,
            'target_user_id' => $targetUser->id,
            'scheduled_at'   => $request->scheduled_at,
            'duration_minutes' => 30,
            'status'         => 'pending',
            'notes'          => $request->notes,
            'table_location' => $request->table_location,
        ]);

        // --- NOTIFICATION TRIGGER ---
        // Notify the Target User (Exhibitor)
        $targetUser->notify(new NewMeetingRequest($appointment, $visitor));

        $appointment->load(['targetUser.company', 'booker']);

        return response()->json([
            'message' => 'Meeting request sent successfully!',
            'data' => $appointment
        ]);
    }

    /**
     * 5. PUT /api/appointments/{id}/respond
     * Accept or Decline a meeting request.
     */
    public function respondToMeeting(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,declined'
        ]);

        $appointment = Appointment::with('booker')->findOrFail($id);

        if ($appointment->target_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->update([
            'status' => $request->status
        ]);

        // --- NOTIFICATION TRIGGER ---
        // Notify the Booker (Visitor) about the decision
        $appointment->booker->notify(new MeetingStatusUpdated($appointment, $request->status));

        return response()->json([
            'message' => 'Meeting status updated.',
            'data' => $appointment
        ]);
    }

    /**
     * 6. DELETE /api/appointments/{id}
     * Cancel a meeting (Soft delete logic via status).
     */
    public function cancelAppointment(Request $request, $id)
    {
        $appointment = Appointment::with(['booker', 'targetUser'])->findOrFail($id);
        $user = $request->user();

        if ($appointment->booker_id !== $user->id && $appointment->target_user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->update(['status' => 'cancelled']);

        // --- NOTIFICATION TRIGGER ---
        // Determine who cancelled and notify the OTHER party
        $recipient = ($user->id === $appointment->booker_id)
            ? $appointment->targetUser
            : $appointment->booker;

        // Reuse status update notification or create a specific 'MeetingCancelled' one
        // Here we reuse for simplicity, passing 'cancelled' logic manually or via class
        // Ideally: $recipient->notify(new MeetingCancelled($appointment));

        return response()->json([
            'message' => 'Meeting cancelled successfully.',
            'data' => $appointment
        ]);
    }
}
