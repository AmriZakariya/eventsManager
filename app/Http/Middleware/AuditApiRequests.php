<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $startTime;

        // Log API request asynchronously (don't block response)
        try {
            $this->logRequest($request, $response, $duration);
        } catch (\Exception $e) {
            \Log::error('Failed to log API request: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Log the API request
     */
    private function logRequest(Request $request, Response $response, float $duration): void
    {
        // Skip logging for certain endpoints to reduce noise
        $skipPaths = [
            'api/health',
            'api/heartbeat',
            'api/up',
            'api/admin/audit-logs',
            'api/admin/audit-logs/*',
            'api/config/*',
            'api/languages',
            'api/languages/*',
            'api/auth/me',
            'api/notifications/unread-count',
            'api/chat/unread-count',
            'api/chat/unread-counts',
        ];

        foreach ($skipPaths as $path) {
            if ($request->is($path)) {
                return;
            }
        }

        $method = $request->getMethod();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        // Only log failed requests and important mutations to reduce database load
        if ($statusCode >= 400 || in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            AuditLogService::logApiRequest(
                method: $method,
                endpoint: $path,
                statusCode: $statusCode,
                userId: auth()->id(),
                requestData: $this->getSafeRequestData($request),
                responseData: [
                    'status_code' => $statusCode,
                    'duration_ms' => round($duration * 1000, 2),
                ]
            );
        }
    }

    /**
     * Get safe request data (exclude sensitive fields)
     */
    private function getSafeRequestData(Request $request): array
    {
        $sensitive = ['password', 'token', 'secret', 'card', 'cvv', 'pin'];
        $data = $request->except($sensitive);

        return array_map(function ($value) use ($sensitive) {
            if (is_array($value)) {
                return array_filter($value, fn ($k) => !in_array($k, $sensitive), ARRAY_FILTER_USE_KEY);
            }
            return $value;
        }, $data);
    }
}

