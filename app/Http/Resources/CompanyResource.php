<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Check if the current logged-in user has bookmarked this company
        // We use the 'sanctum' guard because the request might be public or auth
        $user = auth('sanctum')->user();
        $isFavorited = $user ? $this->favoritedBy()->where('user_id', $user->id)->exists() : false;

        return [
            'id'            => $this->id,
            'name'          => $this->name,

            // Image Helpers (ensure full URL)
            'logo'          => $this->logo ? asset($this->logo) : null,

            // Metadata
            'booth_number'  => $this->booth_number,
            'country'       => $this->country,
            'category'      => $this->category,
            'type'      => $this->type,
            'catalog_file'      => $this->catalog_file,
            'is_featured'   => (bool) $this->is_featured,

            // Contact
            'email'         => $this->email,
            'phone'         => $this->phone,
            'website_url'   => $this->website_url,
            'address'       => $this->address,
            'description'   => $this->description,

            // Dynamic State
            'is_favorited'  => $this->isFavoritedBy($user),

            // Relationships
            // We only return the team if it's loaded to save performance on large lists
            'team'          => ExhibitorUserResource::collection($this->whenLoaded('team')),
        ];
    }
}
