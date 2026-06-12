<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AuditLogService;
use App\Models\Company;
use App\Models\Connection;
use App\Service\WordPressUserSyncService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Socialite;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

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

            'company_code' => [
                'nullable', 'string',
                Rule::requiredIf(fn () => $request->role === 'exhibitor')
            ],
        ]);

        // 👇 ADD THIS SECURITY CHECK BEFORE CREATING THE USER 👇
        if ($request->role === 'exhibitor') {
            $company = Company::find($request->company_id);

            // Check if the company exists, has a passcode, and matches what the user typed
            if (!$company || empty($company->passcode) || $company->passcode !== $request->company_code) {
                return response()->json([
                    'message' => 'Invalid company access code.',
                    'errors' => [
                        'company_code' => ['The access code provided for this company is incorrect.']
                    ]
                ], 422); // 422 Unprocessable Entity (Validation Error)
            }
        }

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
            'password_is_set' => true,
            'created_source' => User::CREATED_SOURCE_APP,
            'phone' => $request->phone,
            'country' => $request->country,
            'city' => $request->city,
            'company_sector' => $request->company_sector,
            'job_title' => $request->job_title,
            'badge_code' => $badgeCode,
            'avatar' => $avatarPath,
            'app_role' => $request->role,
            'is_visible' => true,
            'company_id' => ($request->role === 'exhibitor') ? $request->company_id : null,
            'company_name' => ($request->role === 'visitor') ? $request->company_name : null,
        ]);
        $user->syncOrchidRoleFromAppRole();

        // Generate Token
        $token = $user->createToken('mobile_app')->plainTextToken;

        app()->terminating(function () use ($user, $request) {
            try {
                app(WordPressUserSyncService::class)->syncUser(
                    $user->fresh('company'),
                    $request->password,
                    User::WORDPRESS_SYNC_SOURCE_APP
                );
            } catch (\Exception $e) {
                Log::error('WP Sync Failed: ' . $e->getMessage());
            }
        });
        // 👆 END FIRE AND FORGET

        AuditLogService::logLogin($user->id);

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

    public function reservationAccess(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'We could not find a reservation for this email.',
            ], 404);
        }

        if ($user->password_is_set) {
            return response()->json([
                'message' => 'This account already has a password. Please log in normally.',
            ], 422);
        }

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Profile completion started.',
            'access_token' => $user->createToken('reservation_profile_completion')->plainTextToken,
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
        $userId = $request->user()->id;
        $request->user()->currentAccessToken()->delete();

        AuditLogService::logLogout($userId);

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Throwable $exception) {
            \Log::error('Password reset email failed to send.', [
                'email' => $request->email,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send reset email right now. Please try again later.',
            ], 503);
        }

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
            'provider'   => 'required|string|in:google,facebook,apple,linkedin',
            'token'      => 'required|string',
            'token_type' => 'nullable|string|in:id_token,access_token',
            'name'       => 'nullable|string|max:255',
            'avatar_url' => 'nullable|string|url|max:2048',
        ]);

        $provider   = $request->provider;
        $token      = $request->token;
        $tokenType  = $request->input('token_type', 'access_token');

        // ── Resolve social user ───────────────────────────────────────────────
        try {
            // Google sends an id_token, everyone else sends an access_token
            if ($provider === 'google' && $tokenType === 'id_token') {
                $socialUser = Socialite::driver('google')
                    ->stateless()
                    ->userFromToken($token);
            } elseif ($provider === 'linkedin') {
                // linkedin_login package gives us an OAuth access_token.
                // Use the openid-connect driver shipped with Socialite extras,
                // or fall back to manual resolution using the data Flutter already sent.
                // Since the Flutter SDK already resolved the profile (name + picture),
                // we trust the request data and skip a Socialite round-trip.
                $socialUser = null; // handled below
            } else {
                $socialUser = Socialite::driver($provider)
                    ->stateless()
                    ->userFromToken($token);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid or expired token.',
                'error'   => $e->getMessage(),
            ], 401);
        }

        // ── Resolve email + avatar from social user or request fallback ───────
        $email     = $socialUser?->getEmail() ?? $request->email;
        // Prefer the avatar URL sent from Flutter (already resolved by the SDK),
        // fall back to whatever Socialite returns (may be null for LinkedIn).
        $avatarUrl = $request->input('avatar_url')
            ?? $socialUser?->getAvatar()
            ?? null;

        if (!$email) {
            return response()->json([
                'message' => 'Could not retrieve email from social provider.',
            ], 422);
        }

        // ── Find or create user ───────────────────────────────────────────────
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Resolve name: Socialite name → request name → fallback
            $fullName  = $socialUser?->getName() ?? $request->input('name', 'User');
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName  = $nameParts[1] ?? '';

            // Generate Badge Code
            $prefix = 'VIS-';
            do {
                $badgeCode = $prefix . strtoupper(Str::random(6));
            } while (User::where('badge_code', $badgeCode)->exists());

            $user = User::create([
                'name'           => $firstName,
                'last_name'      => $lastName,
                'email'          => $email,
                'password'       => Hash::make(Str::random(24)),
                'password_is_set' => true,
                'created_source' => User::CREATED_SOURCE_APP,
                'avatar'         => $avatarUrl,   // ✅ URL stored directly
                'badge_code'     => $badgeCode,
                'app_role'       => User::APP_ROLE_VISITOR,
                'is_visible'     => true,
                'phone'          => 'N/A',
                'country'        => 'N/A',
                'city'           => 'N/A',
                'company_sector' => 'N/A',
            ]);
            $user->syncOrchidRoleFromAppRole();
        } else {
            // Update avatar if missing or outdated
            if ($avatarUrl && (empty($user->avatar) || str_starts_with($user->avatar, 'http'))) {
                $user->update(['avatar' => $avatarUrl]);
            }
        }

        $authToken = $user->createToken('mobile_app')->plainTextToken;

        AuditLogService::logLogin($user->id);

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $authToken,
            'token_type'   => 'Bearer',
            'user'         => $this->formatUser($user),
        ]);
    }

    /**
     * Helper to format User Data safely without relying on UserResource
     */
    private function formatUser(User $user)
    {
        // Ensure relationships are loaded to prevent errors
        $user->loadMissing('company');

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
            'bio' => $user->bio,
            // ----------------------------------------------

            'job_title' => $user->job_title,
            'password_is_set' => (bool) $user->password_is_set,
            'profile_completed' => $user->profile_completed,
            'needs_profile_completion' => $user->needs_profile_completion,

            // Critical for Visitor Identity
            'badge_code' => $user->badge_code,

            // Avatar (Returns full URL if it exists)
            'avatar_url' => $user->avatar
                ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar))
                : null,
            'locale' => $user->locale ?? 'en',

            // Role Helper (returns 'visitor', 'exhibitor', or 'admin')
            'role' => $user->role,
            'connection_status' => $user->connection_status,
            'created_source' => $user->created_source,

            // Critical for Exhibitor Logic
            'company_id' => $user->company_id,
            // Prioritize the company table name, fallback to user's manual entry
            'company_name' => $user->company ? $user->company->name : $user->company_name,
            'is_wordpress_synced' => (bool) $user->is_wordpress_synced,
            'wordpress_sync_source' => $user->wordpress_sync_source,
            'wordpress_synced_at' => $user->wordpress_synced_at?->format('Y-m-d H:i:s'),
            'wordpress_sync_error' => $user->wordpress_sync_error,

            'created_at' => $user->created_at ? $user->created_at->format('Y-m-d H:i') : null,
        ];
    }

    /**
     * COMPLETE PROFILE (OAuth users who signed in without full data)
     *
     * Called after Google / Facebook / Apple login when:
     * - user->phone === 'N/A'  (sentinel set in socialLogin)
     * - user has not yet chosen their role / filled required fields
     *
     * Route: POST /api/auth/complete-profile   (middleware: auth:sanctum)
     */
    public function completeProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'password'       => [
                Rule::requiredIf(fn () => !$user->password_is_set),
                'nullable',
                'string',
                'min:6',
                'confirmed',
            ],
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

            // 👇 ADDED VALIDATION RULE FOR COMPANY CODE 👇
            'company_code' => [
                'nullable', 'string',
                Rule::requiredIf(fn () => $request->role === 'exhibitor')
            ],

            // Avatar is optional — if omitted we keep the existing OAuth avatar URL
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'keep_avatar' => 'nullable|string|in:0,1',
        ]);

        // 👇 ADD THIS SECURITY CHECK BEFORE UPDATING THE USER 👇
        if ($request->role === 'exhibitor') {
            $company = Company::find($request->company_id);

            // Check if the company exists, has a passcode, and matches what the user typed
            if (!$company || empty($company->passcode) || $company->passcode !== $request->company_code) {
                return response()->json([
                    'message' => 'Invalid company access code.',
                    'errors' => [
                        'company_code' => ['The access code provided for this company is incorrect.']
                    ]
                ], 422); // 422 Unprocessable Entity
            }
        }

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
        } elseif ($request->input('keep_avatar') !== '1') {
            // No file AND no keep_avatar flag → user explicitly cleared the avatar
            // (Shouldn't happen in normal flow, but handles edge cases safely)
            $avatarValue = $user->avatar;
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
            'app_role'       => $request->role,
            'company_id'     => $request->role === 'exhibitor' ? $validated['company_id'] : null,
            'company_name'   => $request->role === 'visitor'   ? $validated['company_name'] : null,
            'is_visible'     => true,
        ]);

        if (!empty($validated['password'])) {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'password_is_set' => true,
            ])->save();
        }

        $user->syncOrchidRoleFromAppRole();

        if (!empty($validated['password'])) {
            app()->terminating(function () use ($user, $validated) {
                try {
                    app(WordPressUserSyncService::class)->syncUser(
                        $user->fresh('company'),
                        $validated['password'],
                        User::WORDPRESS_SYNC_SOURCE_APP
                    );
                } catch (\Exception $e) {
                    Log::error('WP Sync Failed after complete profile: ' . $e->getMessage());
                }
            });
        }

        return response()->json([
            'message' => 'Profile completed successfully',
            'user'    => $this->formatUser($user->fresh('company')),
        ]);
    }

    public function syncFromWordPress(Request $request)
    {
        // 1. Validate Secret Token
        $expectedToken = (string) config('services.wordpress_sync.inbound_token', '');

        if ($expectedToken === '' || !hash_equals($expectedToken, (string) $request->bearerToken())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $email = $request->input('email');
        if (!$email) {
            return response()->json(['error' => 'Email required'], 400);
        }

        // 2. Find or Create User
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Generate Badge Code for new WP registrations
            $prefix = ($request->input('role') === 'exhibitor') ? 'EXH-' : 'VIS-';
            do {
                $badgeCode = $prefix . strtoupper(Str::random(6));
            } while (User::where('badge_code', $badgeCode)->exists());

            $user = new User();
            $user->email = $email;
            $user->badge_code = $badgeCode;
            $user->app_role = $request->input('role', User::APP_ROLE_VISITOR);
            $user->created_source = User::CREATED_SOURCE_WORDPRESS;
            $user->is_visible = true;

            // Set password if provided, otherwise generate a secure random one
            $user->password = Hash::make($request->input('password') ?? Str::random(24));
            $user->password_is_set = $request->filled('password');
        } elseif ($request->filled('password')) {
            // Update password if WP sends a new one
            $user->password = Hash::make($request->input('password'));
            $user->password_is_set = true;
        }

        // 3. Update Profile Data
        $user->name = $request->input('first_name', 'Unknown');
        $user->last_name = $request->input('last_name', '');
        $user->phone = $request->input('phone', $user->phone ?? 'N/A');
        $user->job_title = $request->input('job_title', $user->job_title);
        $user->company_name = $request->input('company_name', $user->company_name);
        $user->company_sector = $request->input('company_sector', $user->company_sector ?? 'N/A');
        $user->country = $request->input('country', $user->country ?? 'N/A');
        $user->city = $request->input('city', $user->city ?? 'N/A');
        $user->app_role = $request->input('role', $user->app_role ?: User::APP_ROLE_VISITOR);

        $user->save();
        $user->syncOrchidRoleFromAppRole();
        $user->markWordPressSyncSuccess(User::WORDPRESS_SYNC_SOURCE_WORDPRESS);

        return response()->json(['status' => 'success', 'user_id' => $user->id]);
    }

    /**
     * UPDATE PROFILE
     * Route: POST /api/auth/update-profile   (middleware: auth:sanctum)
     *
     * Allows authenticated users to update their personal, professional,
     * and location details. Email and role are intentionally not editable here.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'           => 'sometimes|required|string|max:255',
            'last_name'      => 'sometimes|required|string|max:255',
            'phone'          => 'sometimes|nullable|string|max:20',
            'job_title'      => 'sometimes|nullable|string|max:100',
            'city'           => 'sometimes|nullable|string|max:100',
            'country'        => 'sometimes|nullable|string|max:100',
            'company_name'   => 'sometimes|nullable|string|max:255',
            'company_sector' => 'sometimes|nullable|string|max:100',
            'bio'       => 'sometimes|nullable|string|max:1000',
        ]);

        // Only update fields that were actually sent in the request
        $user->update(array_filter($validated, fn($v) => $v !== null));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $this->formatUser($user->fresh('company')),
        ]);
    }
}
