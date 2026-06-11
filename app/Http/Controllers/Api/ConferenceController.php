<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConferenceResource;
use App\Models\Conference;

class ConferenceController extends Controller
{
    /**
     * Returns all sessions ordered by start time, with speakers included.
     */
    public function index()
    {
        $sessions = Conference::with('speakers')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json(ConferenceResource::collection($sessions)->resolve(request()));
    }
}
