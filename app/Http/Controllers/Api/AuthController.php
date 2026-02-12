<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Resources\UserResource;
use Orchid\Platform\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * REGISTER NEW USER (Visitor or Exhibitor)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'job_title' => 'nullable|string|max:100',

            // 1. Role Validation
            'role' => ['required', 'string', Rule::in(['visitor', 'exhibitor'])],

            // 2. Company Validation (Required only for Exhibitors)
            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
                Rule::requiredIf(fn () => $request->role === 'exhibitor')
            ],
        ]);

        // 3. Generate Badge Code based on Role
        // Exhibitors get "EXH-", Visitors get "VIS-"
        $prefix = ($request->role === 'exhibitor') ? 'EXH-' : 'VIS-';

        do {
            $badgeCode = $prefix . strtoupper(Str::random(6));
        } while (User::where('badge_code', $badgeCode)->exists());

        // 4. Create User
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'job_title' => $request->job_title,
            'badge_code' => $badgeCode,
            'is_visible' => true,
            // Only assign company_id if it's an exhibitor
            'company_id' => ($request->role === 'exhibitor') ? $request->company_id : null,
        ]);

        // 5. Assign Orchid Role
        // Ensure you have roles with slugs 'visitor' and 'exhibitor' in your `roles` table
        $roleSlug = $request->role; // 'visitor' or 'exhibitor'
        $role = Role::where('slug', $roleSlug)->first();

        if ($role) {
            $user->addRole($role);
        } else {
            // Fallback: If 'exhibitor' role doesn't exist, default to visitor permissions
            $defaultRole = Role::where('slug', 'visitor')->first();
            if ($defaultRole) $user->addRole($defaultRole);
        }

        // 6. Generate Token
        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        return response()->json([
            'access_token' => $user->createToken('mobile_app')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * GET PROFILE
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
