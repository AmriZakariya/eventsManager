<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\DateTimer;

class AuditLogDateFilter extends Filter
{
    public function name(): string
    {
        return 'Date Range';
    }

    public function parameters(): ?array
    {
        return ['date_from', 'date_to'];
    }

    public function run(Builder $builder): Builder
    {
        if ($dateFrom = $this->request->get('date_from')) {
            $builder->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $this->request->get('date_to')) {
            $builder->whereDate('created_at', '<=', $dateTo);
        }

        return $builder;
    }

    public function display(): iterable
    {
        return [
            DateTimer::make('date_from')
                ->title('From')
                ->format('Y-m-d')
                ->value($this->request->get('date_from')),

            DateTimer::make('date_to')
                ->title('To')
                ->format('Y-m-d')
                ->value($this->request->get('date_to')),
        ];
    }
}
