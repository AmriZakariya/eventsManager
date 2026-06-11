<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpeakerResource;
use App\Models\Speaker;
use Illuminate\Http\Request;

class SpeakerController extends Controller
{
    /**
     * GET /api/speakers
     * Supports ?search=name
     */
    public function index(Request $request)
    {
        $query = Speaker::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        // Return paginated list, ordered by name
        $speakers = $query->orderBy('full_name')->paginate(20);
        $speakers->getCollection()->transform(fn (Speaker $speaker) => (new SpeakerResource($speaker))->resolve($request));

        return response()->json($speakers);
    }

    /**
     * GET /api/speakers/{id}
     * Returns speaker details + their sessions
     */
    public function show($id)
    {
        $speaker = Speaker::with(['conferences' => function($q) {
            $q->orderBy('start_time', 'asc');
        }])->findOrFail($id);

        return response()->json((new SpeakerResource($speaker))->resolve(request()));
    }
}
