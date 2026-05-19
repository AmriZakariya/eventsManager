<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
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
                'team' => fn ($q) => $q->withConnectionStatusFor($authId),
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

        // 6. Return Paginated Result
        return response()->json($query->paginate(20));
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
                'team' => fn ($q) => $q->withConnectionStatusFor($authId),
            ]),
        ])->findOrFail($id);
        return response()->json($product);
    }

    /**
     * GET /api/products/categories
     * Returns categories with the count of associated products.
     */
    public function categories()
    {
        $categories = Cache::remember('api:products:categories', now()->addMinutes(30), fn () => ProductCategory::withCount('products')
            ->orderBy('name')
            ->get());

        return response()->json($categories);
    }
}
