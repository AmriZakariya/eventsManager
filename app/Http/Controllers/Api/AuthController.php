<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Connection;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Socialite;
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
            'phone' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'company_sector' => 'required|string|max:100',
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096', // Max 4MB
            'role' => ['required', 'string', Rule::in(['visitor', 'exhibitor'])],

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
            'avatar' => $avatarPath,
            'is_visible' => true,
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
            'user' => $this->formatUser($user),
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
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * GET PROFILE
     */
    public function me(Request $request)
    {
        return response()->json([
            'data' => $this->formatUser($request->user())
        ]);
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
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // 2. Store new avatar
        if ($request->hasFile('avatar')) {
            $path = date('Y/m/d');
            $avatarPath = $request->file('avatar')->store($path, 'public');

            $user->update(['avatar' => $avatarPath]);
        }

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'user' => $this->formatUser($user),
        ]);
    }

    public function getStats(Request $request)
    {
        $userId = $request->user()->id;

        // Count Confirmed Connections
        $connectionsCount = Connection::where(function ($q) use ($userId) {
            $q->where('requester_id', $userId)
                ->orWhere('target_id', $userId);
        })->where('status', 'accepted')->count();

        // Count Active Meetings
        $meetingsCount = Appointment::where(function ($q) use ($userId) {
            $q->where('booker_id', $userId)
                ->orWhere('target_user_id', $userId);
        })->whereIn('status', ['confirmed'])->count();

        return response()->json([
            'connections' => $connectionsCount,
            'meetings' => $meetingsCount,
        ]);
    }

    public function updateLocale(Request $request)
    {
        $request->validate([
            'locale' => 'required|string|in:en,fr,ar'
        ]);

        $user = $request->user();
        $user->update([
            'locale' => $request->locale
        ]);

        return response()->json([
            'message' => 'Language updated successfully',
            'user' => $this->formatUser($user)
        ]);
    }

    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:google,facebook,apple',
            'token' => 'required|string',
        ]);

        $provider = $request->provider;
        $token = $request->token;

        try {
            // Verify token with Socialite (Stateless handles mobile tokens)
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid or expired token.',
                'error' => $e->getMessage()
            ], 401);
        }

        // 1. Check if user already exists by email
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // 2. If user doesn't exist, create a new one
            // Apple sometimes hides the name, so we fallback to the name sent from Flutter
            $fullName = $socialUser->getName() ?? $request->name ?? 'User';
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Generate Badge Code
            $prefix = 'VIS-';
            do {
                $badgeCode = $prefix . strtoupper(Str::random(6));
            } while (User::where('badge_code', $badgeCode)->exists());

            $user = User::create([
                'name' => $firstName,
                'last_name' => $lastName,
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(24)), // Random secure password
                'avatar' => $socialUser->getAvatar(),      // Store external URL directly
                'badge_code' => $badgeCode,
                'is_visible' => true,

                // Provide defaults for required fields in your DB schema
                'phone' => 'N/A',
                'country' => 'N/A',
                'city' => 'N/A',
                'company_sector' => 'N/A',
            ]);

            // Assign default visitor role
            $defaultRole = Role::where('slug', 'visitor')->first();
            if ($defaultRole) {
                $user->addRole($defaultRole);
            }
        } else {
            // Optional: Update avatar if it was previously empty
            if (empty($user->avatar) && $socialUser->getAvatar()) {
                $user->update(['avatar' => $socialUser->getAvatar()]);
            }
        }

        // 3. Generate Sanctum token
        $authToken = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $authToken,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Helper to format User Data safely without relying on UserResource
     */
    private function formatUser(User $user)
    {
        // Ensure relationships are loaded to prevent errors
        $user->loadMissing(['company', 'roles']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,

            // --- ADDED MISSING FIELDS FROM REGISTRATION ---
            'country' => $user->country,
            'city' => $user->city,
            'company_sector' => $user->company_sector,
            'is_visible' => (bool) $user->is_visible,
            'about_me' => $user->about_me,
            // ----------------------------------------------

            'job_title' => $user->job_title,
            'job_function' => $user->job_function,

            // Critical for Visitor Identity
            'badge_code' => $user->badge_code,

            // Avatar (Returns full URL if it exists)
            'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'locale' => $user->locale ?? 'en',

            // Role Helper (returns 'visitor', 'exhibitor', or 'admin')
            'role' => $user->roles->first()?->slug ?? 'visitor',

            // Critical for Exhibitor Logic
            'company_id' => $user->company_id,
            // Prioritize the company table name, fallback to user's manual entry
            'company_name' => $user->company ? $user->company->name : $user->company_name,

            'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * COMPLETE PROFILE (OAuth users who signed in without full data)
     *
     * Called after Google / Facebook / Apple login when:
     *   - user->phone === 'N/A'  (sentinel set in socialLogin)
     *   - user has not yet chosen their role / filled required fields
     *
     * Route: POST /api/auth/complete-profile   (middleware: auth:sanctum)
     */
    public function completeProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'country'        => 'required|string|max:100',
            'city'           => 'required|string|max:100',
            'job_title'      => 'nullable|string|max:100',
            'company_sector' => 'required|string|max:100',
            'role'           => ['required', 'string', Rule::in(['visitor', 'exhibitor'])],

            // Exhibitor: pick from companies table
            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
                Rule::requiredIf(fn () => $request->role === 'exhibitor'),
            ],

            // Visitor: free-text company name
            'company_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $request->role === 'visitor'),
            ],

            // Avatar is optional — if omitted we keep the existing OAuth avatar URL
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // ── Avatar handling ────────────────────────────────────────────────────
        $avatarValue = $user->avatar; // default: keep existing (Google URL or old path)

        if ($request->hasFile('avatar')) {
            // Delete old stored avatar if it was a local file (not an http URL)
            if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                if (Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
            }

            $path = date('Y/m/d');
            $avatarValue = $request->file('avatar')->store($path, 'public');
        }

        // ── Badge code: reassign prefix based on chosen role ──────────────────
        $prefix = $request->role === 'exhibitor' ? 'EXH-' : 'VIS-';

        // Only regenerate if the current badge code has the wrong prefix
        // (social login always creates VIS- codes)
        $badgeCode = $user->badge_code;
        if (!str_starts_with($badgeCode, $prefix)) {
            do {
                $badgeCode = $prefix . strtoupper(Str::random(6));
            } while (User::where('badge_code', $badgeCode)->where('id', '!=', $user->id)->exists());
        }

        // ── Update user ────────────────────────────────────────────────────────
        $user->update([
            'name'           => $validated['name'],
            'last_name'      => $validated['last_name'],
            'phone'          => $validated['phone'],
            'country'        => $validated['country'],
            'city'           => $validated['city'],
            'job_title'      => $validated['job_title'] ?? $user->job_title,
            'company_sector' => $validated['company_sector'],
            'avatar'         => $avatarValue,
            'badge_code'     => $badgeCode,
            'company_id'     => $request->role === 'exhibitor' ? $validated['company_id'] : null,
            'company_name'   => $request->role === 'visitor'   ? $validated['company_name'] : null,
            'is_visible'     => true,
        ]);

        // ── Role: sync to the newly chosen role ───────────────────────────────
        $newRole = Role::where('slug', $request->role)->first();

        if ($newRole) {
            // Remove all existing roles then assign the correct one
            foreach ($user->roles as $existingRole) {
                $user->removeRole($existingRole);
            }
            $user->addRole($newRole);
        }

        return response()->json([
            'message' => 'Profile completed successfully',
            'user'    => $this->formatUser($user->fresh(['company', 'roles'])),
        ]);
    }
}
