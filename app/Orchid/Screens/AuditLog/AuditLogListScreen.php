<?php

declare(strict_types=1);

namespace App\Orchid\Screens\AuditLog;

use App\Models\AuditLog;
use App\Orchid\Layouts\AuditLog\AuditLogFiltersLayout;
use App\Orchid\Layouts\AuditLog\AuditLogListLayout;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Code;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class AuditLogListScreen extends Screen
{
    public function query(Request $request): iterable
    {
        $query = AuditLog::with(['user:id,name,email,last_name,password_is_set'])
            ->filters(AuditLogFiltersLayout::class)
            ->defaultSort('created_at', 'desc');

        $today = now()->toDateString();
        $stats = AuditLog::query()
            ->whereDate('created_at', $today)
            ->selectRaw("
                count(*) as total,
                sum(case when action = 'login' then 1 else 0 end) as logins,
                sum(case when action in ('create', 'update', 'delete') then 1 else 0 end) as mutations,
                sum(case when action = 'api_request' then 1 else 0 end) as api
            ")
            ->first();

        return [
            'auditLogs' => $query->paginate(30)->withQueryString(),
            'metrics' => [
                'today' => ['value' => number_format((int) ($stats->total ?? 0))],
                'logins' => ['value' => number_format((int) ($stats->logins ?? 0))],
                'mutations' => ['value' => number_format((int) ($stats->mutations ?? 0))],
                'api' => ['value' => number_format((int) ($stats->api ?? 0))],
            ],
        ];
    }

    public function name(): ?string
    {
        return 'Audit Logs';
    }

    public function description(): ?string
    {
        return 'Track authentication, admin changes, and API activity.';
    }

    public function permission(): ?iterable
    {
        return ['platform.audit-logs'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Export CSV')
                ->icon('bs.download')
                ->method('export', request()->query())
                ->rawClick(),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Today' => 'metrics.today',
                'Logins Today' => 'metrics.logins',
                'CRUD Today' => 'metrics.mutations',
                'API Today' => 'metrics.api',
            ]),

            AuditLogFiltersLayout::class,
            AuditLogListLayout::class,

            Layout::modal('auditLogDetailModal', [
                Layout::rows([
                    Code::make('log.old_values')
                        ->title('Previous Values'),

                    Code::make('log.new_values')
                        ->title('New Values'),
                ]),
            ])->async('asyncGetLog'),
        ];
    }

    public function asyncGetLog(AuditLog $log): iterable
    {
        return [
            'log' => [
                'old_values' => json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}',
                'new_values' => json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}',
            ],
        ];
    }

    public function export(Request $request)
    {
        $logs = AuditLogService::buildFilteredQuery($request)
            ->limit(10000)
            ->get();

        $filename = 'audit-logs-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'Date', 'User', 'Email', 'Action',
                'Resource Type', 'Resource ID', 'Source', 'IP Address', 'Description',
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
        }, $filename);
    }
}
