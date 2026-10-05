<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Proxies remote avatar images (e.g. LinkedIn) for the admin panel.
 *
 * LinkedIn's CDN blocks cross-site browser hotlinking (via Sec-Fetch headers),
 * so an inline <img> pointing at media.licdn.com never loads. A server-side
 * fetch works fine, so we fetch + cache the image here and stream it back from
 * our own origin. This route lives under the Orchid auth middleware, so only
 * logged-in admins can use it (prevents open-proxy abuse), and the host is
 * allow-listed to avoid SSRF.
 */
class AvatarProxyController extends Controller
{
    /** Hosts we are willing to proxy (suffix match). */
    private const ALLOWED_HOST_SUFFIXES = [
        'licdn.com',
        'googleusercontent.com',
        'fbcdn.net',
    ];

    public function show(Request $request)
    {
        $url = (string) $request->query('u', '');

        if ($url === '' || ! Str::startsWith($url, ['http://', 'https://'])) {
            abort(404);
        }

        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if (! $this->hostAllowed($host)) {
            abort(404);
        }

        $cacheKey = 'avatar_proxy_' . md5($url);

        try {
            // Cached value is base64 so it is safe for any cache driver
            // (a raw JPEG in a MySQL text column throws an encoding error).
            $cached = Cache::get($cacheKey);

            if (!is_array($cached)) {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; HygieCleanExpoAdmin/1.0)'])
                    ->get($url);

                if (! $response->successful()) {
                    abort(404);
                }

                $contentType = $response->header('Content-Type') ?: 'image/jpeg';
                if (! Str::startsWith($contentType, 'image/')) {
                    abort(404);
                }

                $cached = [
                    'body' => base64_encode($response->body()),
                    'type' => $contentType,
                ];

                Cache::put($cacheKey, $cached, now()->addHours(24));
            }
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e; // preserve the 404 from abort()
        } catch (\Throwable $e) {
            // Network blocked, cache failure, etc. — degrade to 404 so the UI
            // falls back to initials instead of returning a 500.
            abort(404);
        }

        return response(base64_decode($cached['body']), 200)
            ->header('Content-Type', $cached['type'])
            ->header('Cache-Control', 'private, max-age=86400');
    }

    private function hostAllowed(string $host): bool
    {
        $host = strtolower($host);

        foreach (self::ALLOWED_HOST_SUFFIXES as $suffix) {
            if ($host === $suffix || Str::endsWith($host, '.' . $suffix)) {
                return true;
            }
        }

        return false;
    }
}
