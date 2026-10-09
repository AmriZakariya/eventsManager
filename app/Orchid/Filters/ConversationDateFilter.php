<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class ConversationDateFilter extends Filter
{
    /**
     * The displayable name of the filter.
     */
    public $name = 'Activity Period';

    /**
     * The query parameters this filter handles.
     */
    public $parameters = ['activity'];

    /**
     * Apply the filter to the query.
     *
     * Match latest activity while retaining the full conversation history.
     */
    public function run(Builder $query): Builder
    {
        $activity = $this->request->get('activity', 'all');

        return match ($activity) {
            'today'   => $query->havingRaw('MAX(created_at) >= ?', [now()->startOfDay()]),
            'week'    => $query->havingRaw('MAX(created_at) >= ?', [now()->subWeek()]),
            'month'   => $query->havingRaw('MAX(created_at) >= ?', [now()->subDays(30)]),
            'quarter' => $query->havingRaw('MAX(created_at) >= ?', [now()->subQuarter()]),
            'year'    => $query->havingRaw('MAX(created_at) >= ?', [now()->subYear()]),
            default   => $query,   // 'all' or any unknown value → no filter
        };
    }

    /**
     * The display fields for this filter.
     */
    public function display(): iterable
    {
        return [
            Select::make('activity')
                ->options([
                    'all'     => 'All Time',
                    'today'   => 'Today',
                    'week'    => 'Last 7 Days',
                    'month'   => 'Last 30 Days',
                    'quarter' => 'Last 3 Months',
                    'year'    => 'Last Year',
                ])
                ->value($this->request->get('activity', 'all'))
                ->title('Activity Period')
                ->help('Show conversations that had messages within this period'),
        ];
    }
}
