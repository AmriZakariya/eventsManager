<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * Supports filtering by category, featured status, and search terms.
     */
    public function index(Request $request)
    {
        $authId = $request->user()?->id;

        // 1. Start Query with Eager Loading
        $query = Product::with([
            'category',
            'company' => fn ($q) => $q->with([
                'team' => fn ($q) => $q->profileCompleted()->withConnectionStatusFor($authId),
            ]),
        ]);

        // 2. Filter by Category
        if ($request->has('category_id') && $request->category_id != null) {
            $query->where('category_id', $request->category_id);
        }

        // 3. Filter by Featured (expects boolean or 1/0)
        if ($request->boolean('is_featured')) {
            $query->where('is_featured', true);
        }

        // 4. Search (Name, Description, or Company Name)
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('company', function($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 5. Sorting
        $query->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc');

        // 6. Return paginated result with the same paginator shape, but localized rows.
        $products = $query->paginate(20);
        $products->getCollection()->transform(fn (Product $product) => (new ProductResource($product))->resolve($request));

        return response()->json($products);
    }

    /**
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $authId = auth()->id();

        $product = Product::with([
            'category',
            'company' => fn ($q) => $q->with([
                'team' => fn ($q) => $q->profileCompleted()->withConnectionStatusFor($authId),
            ]),
        ])->findOrFail($id);
        return response()->json((new ProductResource($product))->resolve(request()));
    }

    /**
     * GET /api/products/categories
     * Returns categories with the count of associated products.
     */
    public function categories(Request $request)
    {
        $locale = Locale::fromRequest($request);

        $categories = Cache::remember("api:products:categories:{$locale}", now()->addMinutes(30), fn () => ProductCategory::withCount('products')
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category) => [
                'id' => $category->id,
                'name' => $category->localized('name', $locale),
                'slug' => $category->slug,
                'products_count' => $category->products_count,
            ]));

        return response()->json($categories);
    }
}
