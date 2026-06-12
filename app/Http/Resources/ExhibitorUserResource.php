<?php

namespace App\Http\Resources;

use App\Support\Locale;
use Illuminate\Http\Resources\Json\JsonResource;

class ExhibitorUserResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = Locale::fromRequest($request);

        return [
            'id'             => $this->id,
            'name'           => trim($this->name . ' ' . $this->last_name),
            'email'          => $this->email,

            // Ensure role is passed so Flutter knows to show the "EXHIBITOR" badge
            'role'           => $this->role,
            'password_is_set' => (bool) $this->password_is_set,
            'profile_completed' => $this->profile_completed,
            'needs_profile_completion' => $this->needs_profile_completion,

            'job_title'      => $this->job_title,
            'avatar'         => $this->avatar ? asset($this->avatar) : null,
            'bio'            => $this->bio,

            // Location info for the Professional Info card
            'country'        => $this->country,
            'city'           => $this->city,
            'badge_code'     => $this->badge_code,
            'connection_status' => $this->connection_status, // Calls the attribute above

            // Company association
            'company_id'     => $this->company_id,

            // Use whenLoaded to prevent N+1 query issues if accessed directly,
            // but still provide the data if the relationship is loaded.
            'company_name'   => $this->whenLoaded('company', fn() => $this->company->localized('name', $locale)),
            'company_sector' => $this->whenLoaded('company', fn() => $this->company->localized('category', $locale)),
            'company_sectors' => $this->whenLoaded('company', fn() => $this->company->localizedCategoryItems($locale)),
        ];
    }
}
