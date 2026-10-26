<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects every API request from a banned account. Answers 401, so all app
 * versions treat it as a lost session; `code` lets newer versions explain why.
 */
class EnsureNotBanned
{
    public const CODE = 'account_banned';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBanned()) {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Your account has been suspended.',
                'code'    => self::CODE,
            ], 401);
        }

        return $next($request);
    }
}
