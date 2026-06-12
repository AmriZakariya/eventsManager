<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Get audit logs with filters.
     */
    public function index(Request $request)
    {
        $this->authorize('access-admin-dashboard');

        $perPage = min((int) $request->input('per_page', 50), 100);
        $auditLogs = AuditLogService::buildFilteredQuery($request)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $auditLogs->items(),
            'pagination' => [
                'current_page' => $auditLogs->currentPage(),
                'per_page' => $auditLogs->perPage(),
                'total' => $auditLogs->total(),
                'last_page' => $auditLogs->lastPage(),
            ],
        ]);
    }

    /**
     * Export filtered audit logs as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('access-admin-dashboard');

        $logs = AuditLogService::buildFilteredQuery($request)
            ->limit(10000)
            ->get();

        $filename = 'audit-logs-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Date',
                'User',
                'Email',
                'Action',
                'Resource Type',
                'Resource ID',
                'Source',
                'IP Address',
                'Description',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->format('Y-m-d H:i:s'),
                    trim(($log->user?->name ?? '') . ' ' . ($log->user?->last_name ?? '')) ?: 'System',
                    $log->user?->email ?? '',
                    $log->action,
                    $log->resource_type,
                    $log->resource_id,
                    $log->source,
                    $log->ip_address,
                    $log->description,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
