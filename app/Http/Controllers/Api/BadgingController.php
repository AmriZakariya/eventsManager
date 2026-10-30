<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class BadgingController extends Controller
{
    public function index(Request $request)
    {
        $expectedToken = (string) config('services.badging.token', '');
        if ($expectedToken === '' || !hash_equals($expectedToken, (string) $request->bearerToken())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->query(), [
            'profile_complete' => ['sometimes', Rule::in(['true', 'false', '1', '0'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'search' => ['sometimes', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $users = User::query()
            ->with('company:id,name')
            ->select(['id', 'name', 'last_name', 'company_id', 'job_title'])
            ->when($request->boolean('profile_complete'), fn ($query) => $query->where('password_is_set', true)->whereNot('phone', User::PROFILE_PLACEHOLDER)->whereNot('city', User::PROFILE_PLACEHOLDER)->whereNot('country', User::PROFILE_PLACEHOLDER))
            ->when($request->filled('company_id'), fn ($query) => $query->where('company_id', $request->integer('company_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . addcslashes($request->query('search'), '%_\\') . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('job_title', 'like', $term)->orWhereHas('company', fn ($company) => $company->where('name', 'like', $term)));
            })
            ->orderBy('id')
            ->paginate((int) $request->query('per_page', 100));

        return response()->json([
            'success' => true,
            'data' => $users->getCollection()->map(fn (User $user) => [
                'name' => $user->name,
                'last_name' => $user->last_name,
                'company_name' => $user->company?->name,
                'job_title' => $user->job_title,
            ]),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }
}
