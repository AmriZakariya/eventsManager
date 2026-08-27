<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Http\Resources\CompanyResource;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies (Exhibitors).
     * Handles: Search, Pagination, Sort, and Filters (Category, Country, Types).
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        // 🚀 Eager Load 'team' to avoid N+1 queries when loading the list
        $query = Company::query()
            ->with([
                'team' => fn ($q) => $q->profileCompleted()->withConnectionStatusFor($user?->id),
            ])
            ->where('is_active', true);

        if ($user) {
            $query->withExists([
                'favoritedBy as is_favorited' => fn ($q) => $q->where('user_id', $user->id),
            ]);
        }

        // 1. Search Filter (Encapsulated in a closure to protect the 'is_active' rule)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('booth_number', 'like', "%{$search}%");
            });
        }

        // 2. Category Filter
        if ($category = $request->query('category')) {
            $query->whereCategoryToken($category);
        }

        // 3. Country Filter
        if ($country = $request->query('country')) {
            $query->where('country', $country);
        }

        // 4. Types Filter (Handles comma-separated string from Flutter: "SPONSOR,PARTNER")
        if ($types = $request->query('types')) {
            $typesArray = explode(',', $types);
            $query->where(function ($q) use ($typesArray) {
                foreach ($typesArray as $type) {
                    // Uses whereJsonContains because 'type' is a JSON array in the database
                    $q->orWhereJsonContains('type', trim($type));
                }
            });
        }

        // 5. Dynamic Sorting (Matches Flutter App options)
        $sort = $request->query('sort', 'name'); // Default to name
        switch ($sort) {
            case 'booth':
                // Sorts alphabetically/numerically by booth number
                $query->orderBy('booth_number', 'asc');
                break;
            case 'featured':
                // Featured first, then alphabetical
                $query->orderBy('is_featured', 'desc')->orderBy('name', 'asc');
                break;
            case 'recent':
                // Newest companies first
                $query->orderBy('created_at', 'desc');
                break;
            case 'name':
            default:
                // Standard A-Z
                $query->orderBy('name', 'asc');
                break;
        }

        // ?all=true returns the full list without pagination — used by the
        // signup / complete-profile company dropdowns, which need every company.
        if ($request->boolean('all')) {
            return CompanyResource::collection($query->get());
        }

        // Paginated resource. Defaults to 20 per page; callers (e.g. the home
        // partner-logo section) may request more via ?per_page=, capped at 100.
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        return CompanyResource::collection($query->paginate($perPage));
    }

    /**
     * Display the specified company details.
     */
    public function show($id)
    {
        $user = auth('sanctum')->user();

        $query = Company::with([
            'team' => fn ($q) => $q->profileCompleted()->withConnectionStatusFor($user?->id),
        ]);

        if ($user) {
            $query->withExists([
                'favoritedBy as is_favorited' => fn ($q) => $q->where('user_id', $user->id),
            ]);
        }

        $company = $query->findOrFail($id);

        return new CompanyResource($company);
    }

    /**
     * Toggle Favorite Status.
     */
    public function toggleFavorite($id)
    {
        $user = auth()->user();
        $company = Company::findOrFail($id);

        // The 'favoritedBy' relation now comes from the polymorphic Trait
        $company->favoritedBy()->toggle($user->id);

        // Check status
        $isFavorited = $company->isFavoritedBy($user);

        return response()->json([
            'status' => 'success',
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Added to favorites' : 'Removed from favorites'
        ]);
    }
}
