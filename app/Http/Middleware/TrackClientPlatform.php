<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records which phone (android / ios) and app version a logged-in user is on,
 * from the X-Platform and X-App-Version headers the app sends. Writes only
 * when something changed or twice a day, not on every request.
 */
class TrackClientPlatform
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $platform = User::normalizePlatform($request->header('X-Platform'));
            $user = $request->user();

            if ($platform !== null && $user instanceof User) {
                $version = substr((string) $request->header('X-App-Version'), 0, 20) ?: null;

                $stale = ! $user->platform_seen_at || $user->platform_seen_at->lt(now()->subHours(12));

                if ($stale || $user->platform !== $platform || ($version && $user->app_version !== $version)) {
                    $user->forceFill([
                        'platform' => $platform,
                        'app_version' => $version ?? $user->app_version,
                        'platform_seen_at' => now(),
                    ])->saveQuietly();
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Could not record client platform: ' . $e->getMessage());
        }

        return $response;
    }
}
