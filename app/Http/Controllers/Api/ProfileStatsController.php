<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Networking; // Assuming you have this model
// use App\Models\BadgeScan; // If you track scans separately

class ProfileStatsController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // 1. Count Meetings (Appointments)
        // Count where user is Booker OR Target, and status is NOT cancelled
        $meetingsCount = Appointment::where(function ($q) use ($userId) {
            $q->where('booker_id', $userId)
                ->orWhere('target_user_id', $userId);
        })->where('status', '!=', 'cancelled')->count();

        // 2. Count Connections (Networking)
        // Assuming 'status' = 'accepted' means a connection
        // Adjust table/model names based on your actual networking implementation
        $connectionsCount = \DB::table('networking_connections') // or use Model
        ->where(function ($q) use ($userId) {
            $q->where('requester_id', $userId)
                ->orWhere('target_id', $userId);
        })
            ->where('status', 'accepted')
            ->count();

        // 3. Count Scans
        // Assuming you have a table tracking who scanned whom
        // If you don't have a specific table, you might return 0 or
        // count how many people viewed their profile.
        $scansCount = \DB::table('badge_scans')
            ->where('scanner_id', $userId)
            ->count();

        return response()->json([
            'connections' => $connectionsCount,
            'meetings' => $meetingsCount,
            'scans' => $scansCount,
        ]);
    }
}
