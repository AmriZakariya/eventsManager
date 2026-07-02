<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AwardCategory;
use App\Models\AwardNominee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AwardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = AwardCategory::with([
            'nominees' => fn ($query) => $query
                ->with('company')
                ->orderByDesc('is_winner')
                ->orderBy('product_name'),
        ])
            ->orderBy('name')
            ->get();

        $nomineesCount = $categories->sum(fn (AwardCategory $category) => $category->nominees->count());
        $winnersCount = $categories->sum(fn (AwardCategory $category) => $category->nominees->where('is_winner', true)->count());

        return response()->json([
            'summary' => [
                'categories_count' => $categories->count(),
                'nominees_count' => $nomineesCount,
                'winners_count' => $winnersCount,
            ],
            'categories' => $categories->map(fn (AwardCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'nominees_count' => $category->nominees->count(),
                'winners_count' => $category->nominees->where('is_winner', true)->count(),
                'nominees' => $category->nominees->map(fn (AwardNominee $nominee) => [
                    'id' => $nominee->id,
                    'product_name' => $nominee->product_name,
                    'image' => $nominee->image,
                    'image_url' => $this->assetUrl($nominee->image),
                    'is_winner' => (bool) $nominee->is_winner,
                    'company' => $nominee->company ? [
                        'id' => $nominee->company->id,
                        'name' => $nominee->company->name,
                        'logo' => $this->assetUrl($nominee->company->logo),
                        'booth_number' => $nominee->company->booth_number,
                        'country' => $nominee->company->country,
                    ] : null,
                ])->values(),
            ])->values(),
        ]);
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : asset($path);
    }
}
