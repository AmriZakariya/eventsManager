<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class ConversationRoleFilter extends Filter
{
    /**
     * The displayable name of the filter.
     */
    public $name = 'Participant Role';

    /**
     * The query parameters this filter handles.
     */
    public $parameters = ['role'];

    /**
     * Apply the filter to the query.
     *
     * Filters conversations where at least one participant holds the selected role.
     * Requires Message model to have `sender` and `receiver` BelongsTo relationships,
     * each with a `roles` HasMany/BelongsToMany through Orchid.
     */
    public function run(Builder $query): Builder
    {
        $role = $this->request->get('role', 'all');

        if (!$role || $role === 'all') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($role) {
            $q->whereHas('sender.roles', fn(Builder $r) => $r->where('slug', $role))
                ->orWhereHas('receiver.roles', fn(Builder $r) => $r->where('slug', $role));
        });
    }

    /**
     * The display fields for this filter.
     */
    public function display(): iterable
    {
        return [
            Select::make('role')
                ->options([
                    'all'       => 'All Roles',
                    'exhibitor' => 'Exhibitors Only',
                    'visitor'   => 'Visitors Only',
                ])
                ->value($this->request->get('role', 'all'))
                ->title('Participant Role')
                ->help('Show conversations where at least one participant has this role'),
        ];
    }
}
