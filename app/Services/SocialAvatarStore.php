<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Keeps our own copy of social provider avatars. Provider URLs expire
 * (LinkedIn ones return 403 after a few weeks) and then break images in the app.
 */
class SocialAvatarStore
{
    /** Only these image hosts are fetched, since a URL can come from the request. */
    private const ALLOWED_HOSTS = ['licdn.com', 'googleusercontent.com', 'fbcdn.net', 'fbsbx.com', 'facebook.com'];

    private const EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private const MAX_BYTES = 4 * 1024 * 1024;

    /**
     * Used at sign-in: the stored path, or the original URL if it cannot be
     * stored (unknown host or failed download), which matches the old behaviour.
     */
    public static function store(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        return self::download($url) ?? $url;
    }

    /** True for an https URL on a known provider image host. */
    public static function isProviderUrl(?string $url): bool
    {
        if (!$url || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return collect(self::ALLOWED_HOSTS)->contains(fn ($h) => $host === $h || str_ends_with($host, '.' . $h));
    }

    /**
     * LinkedIn puts the expiry timestamp in the "e" query parameter.
     * Null when the URL has none (other providers).
     */
    public static function expiresAt(string $url): ?int
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return isset($query['e']) ? (int) $query['e'] : null;
    }

    /** Downloads a provider avatar to the public disk; the stored path, or null on failure. */
    public static function download(string $url): ?string
    {
        if (!self::isProviderUrl($url)) {
            return null;
        }

        try {
            $response = Http::timeout(8)->withOptions(['allow_redirects' => false])->get($url);
            $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            $body = $response->body();

            if (!$response->successful() || !isset(self::EXTENSIONS[$type]) || $body === '' || strlen($body) > self::MAX_BYTES) {
                return null;
            }

            $path = date('Y/m/d') . '/' . Str::random(40) . '.' . self::EXTENSIONS[$type];

            return Storage::disk('public')->put($path, $body) ? $path : null;
        } catch (\Throwable $e) {
            Log::warning('Could not store social avatar', [
                'host'  => parse_url($url, PHP_URL_HOST),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
