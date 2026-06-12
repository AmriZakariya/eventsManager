<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log a user action to the audit log
     */
    public static function log(
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?int $userId = null,
        ?string $source = null
    ): AuditLog {
        $userId = $userId ?? auth()->id();
        $source = $source ?? self::determineSource();

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => self::getClientIp(),
            'user_agent' => Request::userAgent(),
            'source' => $source,
            'description' => $description,
        ]);
    }

    /**
     * Log user login
     */
    public static function logLogin(?int $userId = null): AuditLog
    {
        return self::log(
            action: 'login',
            description: 'User logged in',
            userId: $userId
        );
    }

    /**
     * Log user logout
     */
    public static function logLogout(?int $userId = null): AuditLog
    {
        $userId = $userId ?? auth()->id();
        return self::log(
            action: 'logout',
            description: 'User logged out',
            userId: $userId
        );
    }

    /**
     * Log model creation
     */
    public static function logCreate(
        string $resourceType,
        int $resourceId,
        array $newValues,
        ?int $userId = null
    ): AuditLog {
        return self::log(
            action: 'create',
            resourceType: $resourceType,
            resourceId: $resourceId,
            newValues: self::sanitizeValues($newValues),
            description: "{$resourceType} #{$resourceId} created",
            userId: $userId
        );
    }

    /**
     * Log model update
     */
    public static function logUpdate(
        string $resourceType,
        int $resourceId,
        array $oldValues,
        array $newValues,
        ?int $userId = null
    ): AuditLog {
        return self::log(
            action: 'update',
            resourceType: $resourceType,
            resourceId: $resourceId,
            oldValues: self::sanitizeValues($oldValues),
            newValues: self::sanitizeValues($newValues),
            description: "{$resourceType} #{$resourceId} updated",
            userId: $userId
        );
    }

    /**
     * Log model deletion
     */
    public static function logDelete(
        string $resourceType,
        int $resourceId,
        array $deletedValues,
        ?int $userId = null
    ): AuditLog {
        return self::log(
            action: 'delete',
            resourceType: $resourceType,
            resourceId: $resourceId,
            oldValues: self::sanitizeValues($deletedValues),
            description: "{$resourceType} #{$resourceId} deleted",
            userId: $userId
        );
    }

    /**
     * Log API request
     */
    public static function logApiRequest(
        string $method,
        string $endpoint,
        int $statusCode,
        ?int $userId = null,
        ?array $requestData = null,
        ?array $responseData = null
    ): AuditLog {
        return self::log(
            action: 'api_request',
            resourceType: 'API',
            description: "{$method} {$endpoint} - {$statusCode}",
            userId: $userId,
            newValues: $responseData,
            oldValues: $requestData,
            source: 'api'
        );
    }

    /**
     * Log a generic admin action
     */
    public static function logAdminAction(
        string $action,
        string $description,
        ?int $userId = null,
        ?array $metadata = null
    ): AuditLog {
        return self::log(
            action: $action,
            description: $description,
            userId: $userId,
            newValues: $metadata,
            source: 'admin'
        );
    }

    /**
     * Determine the source of the request (web, api, admin)
     */
    private static function determineSource(): string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        if (Request::is('api/*')) {
            return 'api';
        }

        if (Request::is('admin/*') || Request::is('admin')) {
            return 'admin';
        }

        return 'web';
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): ?string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return Request::ip();
    }

    /**
     * Get changed attributes between old and new models
     */
    public static function getChangedAttributes(array $original, array $changes): array
    {
        $changed = [];
        foreach ($changes as $key => $value) {
            if (!isset($original[$key]) || $original[$key] !== $value) {
                $changed[$key] = [
                    'old' => $original[$key] ?? null,
                    'new' => $value,
                ];
            }
        }
        return $changed;
    }

    /**
     * Prune old audit logs (optional - can be called via scheduled command)
     * Keeps logs for 90 days by default
     */
    public static function prune(int $days = 90): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Build a filtered query for listing/export.
     */
    public static function buildFilteredQuery(\Illuminate\Http\Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = AuditLog::query()
            ->with(['user:id,name,email,last_name'])
            ->latest('created_at');

        if ($request->filled('date_from')) {
            $query->fromDate($request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->toDate($request->input('date_to'));
        }

        if ($request->filled('user_id')) {
            $query->byUser((int) $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->byAction($request->input('action'));
        }

        if ($request->filled('resource_type')) {
            $query->byResourceType($request->input('resource_type'));
        }

        if ($request->filled('source')) {
            $query->bySource($request->input('source'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function sanitizeValues(array $values): array
    {
        $sensitive = ['password', 'token', 'secret', 'card', 'cvv', 'pin', 'passcode', 'remember_token', 'fcm_token'];

        return array_filter(
            $values,
            fn ($key) => ! in_array($key, $sensitive, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
