<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use App\Support\Locale;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = Locale::fromRequest($request);
        // Check if the current logged-in user has bookmarked this company
        // We use the 'sanctum' guard because the request might be public or auth
        $user = auth('sanctum')->user();
        $isFavorited = $user ? (bool) ($this->is_favorited ?? $this->isFavoritedBy($user)) : false;

        return [
            'id'            => $this->id,
            'name'          => $this->localized('name', $locale),

            // Image Helpers (ensure full URL)
            'logo'          => $this->logo ? asset($this->logo) : null,

            // Metadata
            'booth_number'  => $this->booth_number,
            'country'       => $this->country,
            'category'      => $this->localized('category', $locale),
            'categories'    => $this->localizedCategoryItems($locale),
            'type'      => $this->type,
            'catalog_file' => $this->catalog_file
                ? (Str::startsWith($this->catalog_file, ['http://', 'https://'])
                    ? $this->catalog_file
                    : asset($this->catalog_file))
                : null,
            'is_featured'   => (bool) $this->is_featured,

            // Contact
            'email'         => $this->email,
            'phone'         => $this->phone,
            'website_url'   => $this->website_url,
            'address'       => $this->localized('address', $locale),
            'description'   => $this->localizedPlainText('description', $locale),
            'is_active'   => $this->is_active,

            // Dynamic State
            'is_favorited'  => $isFavorited,

            // Relationships
            // We only return the team if it's loaded to save performance on large lists
            'team'          => ExhibitorUserResource::collection($this->whenLoaded('team')),
        ];
    }
}
