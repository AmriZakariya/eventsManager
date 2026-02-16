<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;

class ConversationRoleFilter extends Filter
{
    /**
     * The displayable name of the filter.
     */
    public string $name = 'Filter by Role';

    /**
     * The array of matched parameters.
     */
    public array $parameters = ['role'];

    /**
     * Apply filter to the query.
     */
    public function run(Builder $query): Builder
    {
        $role = $this->request->get('role');

        if (!$role || $role === 'all') {
            return $query;
        }

        // Filter conversations where at least one participant has the specified role
        return $query->where(function ($q) use ($role) {
            $q->whereHas('sender.roles', function ($roleQuery) use ($role) {
                $roleQuery->where('slug', $role);
            })->orWhereHas('receiver.roles', function ($roleQuery) use ($role) {
                $roleQuery->where('slug', $role);
            });
        });
    }

    /**
     * Get the display fields for the filter.
     */
    public function display(): iterable
    {
        return [
            Select::make('role')
                ->options([
                    'all' => 'All Roles',
                    'exhibitor' => 'Exhibitor Conversations',
                    'visitor' => 'Visitor Conversations',
                ])
                ->empty('All Roles', 'all')
                ->title('Participant Role')
                ->help('Filter conversations by participant role type'),
        ];
    }
}
