<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gets each user's badge code (HCX-<uuid>) from the WordPress site, so the
 * app badge and PDF carry the same QR as the website badge.
 */
class WordPressBadgeService
{
    /** Minimum time between two lookups for a user who still has no code. */
    private const RETRY_AFTER_MINUTES = 10;

    public static function isValidCode(?string $code): bool
    {
        return is_string($code)
            && preg_match('/^HCX-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $code) === 1;
    }

    /**
     * Makes sure the user has a website badge code, looking it up when missing.
     * Never throws: without a code, the badge keeps the app's own code.
     */
    public function ensure(User $user): ?string
    {
        if ($user->web_badge_code) {
            return $user->web_badge_code;
        }

        if ($user->web_badge_checked_at && $user->web_badge_checked_at->gt(now()->subMinutes(self::RETRY_AFTER_MINUTES))) {
            return null;
        }

        return $this->refresh($user);
    }

    /**
     * Looks the code up now and stores it. Returns the code, or null when the
     * website has no badge for this email or could not be reached.
     */
    public function refresh(User $user): ?string
    {
        $badge = $this->lookupByEmail($user->email);
        $code = $badge['uid'] ?? $badge['badge_code'] ?? null;

        $user->forceFill([
            'web_badge_code'       => self::isValidCode($code) ? $code : $user->web_badge_code,
            'web_badge_checked_at' => now(),
        ])->saveQuietly();

        return $user->web_badge_code;
    }

    /**
     * Newest badge registered with this email on the website, or null.
     */
    public function lookupByEmail(?string $email): ?array
    {
        $token = (string) config('services.wordpress_sync.outbound_token', '');

        if (blank($email) || $token === '') {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(5)
                ->post((string) config('services.wordpress_sync.badge_lookup_url'), [
                    'email' => strtolower(trim($email)),
                ]);
        } catch (\Throwable $e) {
            Log::warning('WP badge lookup failed.', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning('WP badge lookup failed.', ['status' => $response->status()]);

            return null;
        }

        // The API returns registrations newest first.
        $badges = $response->json('badges');

        return is_array($badges) && isset($badges[0]) && is_array($badges[0]) ? $badges[0] : null;
    }
}
