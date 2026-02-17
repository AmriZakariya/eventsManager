<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
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

            // New Fields
            'phone' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'company_sector' => 'required|string|max:100',

            // Profile Picture (Required)
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096', // Max 4MB

            // Role Validation
            'role' => ['required', 'string', Rule::in(['visitor', 'exhibitor'])],

            // CONDITIONAL VALIDATION:

            // 1. Exhibitors need a valid company_id from DB
            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
                Rule::requiredIf(fn () => $request->role === 'exhibitor')
            ],

            // 2. Visitors need a manual company_name text
            'company_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $request->role === 'visitor')
            ],
        ]);

        // Handle File Upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            // Store using YYYY/MM/DD structure on 'public' disk
            // This matches the structure: /storage/2026/02/16/filename.png
            $path = date('Y/m/d');
            $avatarPath = $request->file('avatar')->store($path, 'public');
        }

        // Generate Badge Code
        $prefix = ($request->role === 'exhibitor') ? 'EXH-' : 'VIS-';
        do {
            $badgeCode = $prefix . strtoupper(Str::random(6));
        } while (User::where('badge_code', $badgeCode)->exists());

        // Create User
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'country' => $request->country,
            'city' => $request->city,
            'company_sector' => $request->company_sector,
            'job_title' => $request->job_title,
            'badge_code' => $badgeCode,
            'avatar' => $avatarPath, // Save path
            'is_visible' => true,

            // Assign Company Data based on Role
            'company_id' => ($request->role === 'exhibitor') ? $request->company_id : null,
            'company_name' => ($request->role === 'visitor') ? $request->company_name : null,
        ]);

        // Assign Orchid Role
        $roleSlug = $request->role;
        $role = Role::where('slug', $roleSlug)->first();

        if ($role) {
            $user->addRole($role);
        } else {
            $defaultRole = Role::where('slug', 'visitor')->first();
            if ($defaultRole) $user->addRole($defaultRole);
        }

        // Generate Token
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

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // We will send the password reset link to this user.
        // Once we have attempted to send the link, we will examine the response
        // then return the message back to the application.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 200);
        }

        return response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        // Attempt to reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 400);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096', // Max 4MB
        ]);

        $user = $request->user();

        // 1. Delete old avatar if it exists to save space
        // Note: We check if it exists on the public disk
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // 2. Store new avatar
        if ($request->hasFile('avatar')) {
            // Store using YYYY/MM/DD structure on 'public' disk
            $path = date('Y/m/d');
            $avatarPath = $request->file('avatar')->store($path, 'public');

            $user->update(['avatar' => $avatarPath]);
        }

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'user' => new UserResource($user),
        ]);
    }
}
