<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\Locale;

class ConferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user('sanctum');
        $locale = Locale::fromRequest($request);

        return [
            'id' => $this->id,
            'title' => $this->localized('title', $locale),
            'description' => $this->localizedPlainText('description', $locale),
            'start_time' => optional($this->start_time)->format('Y-m-d H:i'),
            'end_time' => optional($this->end_time)->format('Y-m-d H:i'),
            'location' => $this->location,
            'type' => $this->type,
            'is_attending' => $user ? $this->attendees()->where('users.id', $user->id)->exists() : false,
            'speakers' => SpeakerResource::collection($this->whenLoaded('speakers')),
        ];
    }
}
