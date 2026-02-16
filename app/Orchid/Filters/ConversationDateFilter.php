<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class ConversationDateFilter extends Filter
{
    /**
     * The displayable name of the filter.
     */
    public string $name = 'Filter by Activity';

    /**
     * The array of matched parameters.
     */
    public array $parameters = ['activity'];

    /**
     * Apply filter to the query.
     */
    public function run(Builder $query): Builder
    {
        $activity = $this->request->get('activity');

        if (!$activity || $activity === 'all') {
            return $query;
        }

        return match ($activity) {
            'today' => $query->whereDate('created_at', '>=', now()->startOfDay()),
            'week' => $query->where('created_at', '>=', now()->subWeek()),
            'month' => $query->where('created_at', '>=', now()->subMonth()),
            'quarter' => $query->where('created_at', '>=', now()->subQuarter()),
            'year' => $query->where('created_at', '>=', now()->subYear()),
            default => $query,
        };
    }

    /**
     * Get the display fields for the filter.
     */
    public function display(): iterable
    {
        return [
            Select::make('activity')
                ->options([
                    'all' => 'All Time',
                    'today' => 'Today',
                    'week' => 'Last 7 Days',
                    'month' => 'Last 30 Days',
                    'quarter' => 'Last 3 Months',
                    'year' => 'Last Year',
                ])
                ->empty('All Time', 'all')
                ->title('Activity Period')
                ->help('Filter conversations by when they were last active'),
        ];
    }
}
