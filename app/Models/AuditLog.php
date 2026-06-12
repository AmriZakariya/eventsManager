<?php

namespace App\Models;

use App\Orchid\Filters\AuditLogActionFilter;
use App\Orchid\Filters\AuditLogDateFilter;
use App\Orchid\Filters\AuditLogSearchFilter;
use App\Orchid\Filters\AuditLogSourceFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class AuditLog extends Model
{
    use AsSource, Filterable, HasFactory;

    protected $allowedFilters = [
        AuditLogSearchFilter::class,
        AuditLogActionFilter::class,
        AuditLogSourceFilter::class,
        AuditLogDateFilter::class,
    ];

    protected $allowedSorts = [
        'created_at',
        'action',
        'source',
    ];

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'source',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Audit log belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Filter by action type
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by resource type
     */
    public function scopeByResourceType($query, $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by source (web, api, admin)
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeFromDate($query, $date)
    {
        return $query->whereDate('created_at', '>=', $date);
    }

    /**
     * Scope: Filter by date range (to date)
     */
    public function scopeToDate($query, $date)
    {
        return $query->whereDate('created_at', '<=', $date);
    }

    /**
     * Scope: Get latest records first
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}

