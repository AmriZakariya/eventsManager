<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\Locale;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = Locale::fromRequest($request);

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'category_id' => $this->category_id,
            'name' => $this->localized('name', $locale),
            'type' => $this->localized('type', $locale),
            'description' => $this->localizedPlainText('description', $locale),
            'image' => $this->image,
            'image_url' => $this->image_url,
            'is_featured' => (bool) $this->is_featured,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->localized('name', $locale),
                'slug' => $this->category->slug,
            ] : null),
            'company' => $this->whenLoaded('company', fn () => new CompanyResource($this->company)),
        ];
    }
}
