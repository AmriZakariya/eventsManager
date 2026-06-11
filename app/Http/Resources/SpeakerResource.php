<?php

namespace App\Http\Resources;

use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = Locale::fromRequest($request);

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'job_title' => $this->localized('job_title', $locale),
            'company_name' => $this->company_name,
            'photo' => $this->photo,
            'photo_url' => $this->photo_url,
            'bio' => $this->localizedPlainText('bio', $locale),
            'conferences' => ConferenceResource::collection($this->whenLoaded('conferences')),
        ];
    }
}
