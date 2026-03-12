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
        // 🚀 Eager Load 'team' to avoid N+1 queries when loading the list
        $query = Company::query()
            ->with('team')
            ->where('is_active', true);

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
            $query->where('category', $category);
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

        // Returns Standard Laravel Paginated Resource (20 per page)
        return CompanyResource::collection($query->paginate(20));
    }

    /**
     * Display the specified company details.
     */
    public function show($id)
    {
        $company = Company::with('team')->findOrFail($id);

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
