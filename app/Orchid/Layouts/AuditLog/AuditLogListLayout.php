<?php

namespace App\Orchid\Layouts\AuditLog;

use App\Models\AuditLog;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class AuditLogListLayout extends Table
{
    public $target = 'auditLogs';

    public function columns(): array
    {
        return [
            TD::make('created_at', 'Date')
                ->usingComponent(DateTimeSplit::class)
                ->sort()
                ->cantHide(),

            TD::make('user', 'User')
                ->render(function (AuditLog $log) {
                    if (! $log->user) {
                        return '<span class="text-muted">System</span>';
                    }

                    $name = trim($log->user->name . ' ' . ($log->user->last_name ?? ''));

                    return '<div class="fw-semibold">' . e($name) . '</div>'
                        . '<small class="text-muted">' . e($log->user->email) . '</small>';
                }),

            TD::make('action', 'Action')
                ->sort()
                ->render(fn (AuditLog $log) => $this->actionBadge($log->action)),

            TD::make('resource_type', 'Resource')
                ->render(fn (AuditLog $log) => $log->resource_type
                    ? e($log->resource_type) . ($log->resource_id ? ' #' . $log->resource_id : '')
                    : '<span class="text-muted">—</span>'),

            TD::make('source', 'Source')
                ->sort()
                ->render(fn (AuditLog $log) => '<span class="badge bg-light text-dark border">' . e($log->source) . '</span>'),

            TD::make('ip_address', 'IP')
                ->defaultHidden()
                ->render(fn (AuditLog $log) => e($log->ip_address ?? '—')),

            TD::make('description', 'Description')
                ->width('30%')
                ->render(fn (AuditLog $log) => '<span class="text-break">' . e($log->description ?? '—') . '</span>'),

            TD::make('details', '')
                ->align(TD::ALIGN_CENTER)
                ->width('80px')
                ->render(fn (AuditLog $log) => ModalToggle::make('View')
                    ->icon('bs.eye')
                    ->modal('auditLogDetailModal')
                    ->modalTitle('Audit Log #' . $log->id)
                    ->asyncParameters(['log' => $log->id])),
        ];
    }

    private function actionBadge(string $action): string
    {
        $color = match ($action) {
            'login' => 'bg-success',
            'logout' => 'bg-secondary',
            'create' => 'bg-primary',
            'update' => 'bg-info',
            'delete' => 'bg-danger',
            'api_request' => 'bg-warning text-dark',
            default => 'bg-light text-dark border',
        };

        return '<span class="badge ' . $color . '">' . e($action) . '</span>';
    }
}
