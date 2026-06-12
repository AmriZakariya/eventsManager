<?php

namespace App\Orchid\Layouts\AuditLog;

use App\Orchid\Filters\AuditLogActionFilter;
use App\Orchid\Filters\AuditLogDateFilter;
use App\Orchid\Filters\AuditLogSearchFilter;
use App\Orchid\Filters\AuditLogSourceFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class AuditLogFiltersLayout extends Selection
{
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            AuditLogSearchFilter::class,
            AuditLogActionFilter::class,
            AuditLogSourceFilter::class,
            AuditLogDateFilter::class,
        ];
    }
}
