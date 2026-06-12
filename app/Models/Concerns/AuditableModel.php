<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\AuditLogService;

trait AuditableModel
{
    public static function bootAuditableModel(): void
    {
        static::created(function ($model) {
            if (! static::shouldAudit($model)) {
                return;
            }

            self::logModelEvent($model, 'create', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            if (! static::shouldAudit($model)) {
                return;
            }

            $changes = self::getModelChanges($model);
            if ($changes === []) {
                return;
            }

            self::logModelEvent(
                $model,
                'update',
                self::filterAuditAttributes($model, $model->getOriginal()),
                self::filterAuditAttributes($model, $model->getAttributes())
            );
        });

        static::deleted(function ($model) {
            if (! static::shouldAudit($model)) {
                return;
            }

            self::logModelEvent(
                $model,
                'delete',
                self::filterAuditAttributes($model, $model->getOriginal()),
                null
            );
        });
    }

    protected static function shouldAudit(object $model): bool
    {
        if ($model instanceof AuditLog) {
            return false;
        }

        if (app()->runningInConsole()) {
            return true;
        }

        // API mutations are captured by AuditApiRequests middleware.
        return ! request()->is('api/*');
    }

    protected static function logModelEvent(object $model, string $action, ?array $oldValues, ?array $newValues): void
    {
        try {
            $modelClass = class_basename($model);
            $resourceId = (int) $model->getKey();

            match ($action) {
                'create' => AuditLogService::logCreate($modelClass, $resourceId, $newValues ?? []),
                'update' => AuditLogService::logUpdate($modelClass, $resourceId, $oldValues ?? [], $newValues ?? []),
                'delete' => AuditLogService::logDelete($modelClass, $resourceId, $oldValues ?? []),
                default => null,
            };
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getModelChanges(object $model): array
    {
        $changes = [];
        $original = $model->getOriginal();
        $ignored = ['updated_at'];

        foreach ($model->getAttributes() as $key => $value) {
            if (in_array($key, $ignored, true)) {
                continue;
            }

            if (! array_key_exists($key, $original) || $original[$key] !== $value) {
                $changes[$key] = $value;
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function filterAuditAttributes(object $model, array $attributes): array
    {
        $hidden = array_merge(
            ['password', 'remember_token', 'fcm_token', 'passcode'],
            method_exists($model, 'auditHiddenFields') ? $model->auditHiddenFields() : []
        );

        return array_diff_key($attributes, array_flip($hidden));
    }
}
