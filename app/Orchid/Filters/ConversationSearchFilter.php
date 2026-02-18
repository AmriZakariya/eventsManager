<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class ConversationSearchFilter extends Filter
{
    /**
     * The displayable name of the filter.
     * Fixed: was corrupt UTF-8 encoding ('ðŸ" Search').
     */
    public $name = 'Search';

    /**
     * The query parameters this filter handles.
     */
    public $parameters = ['search'];

    /**
     * Apply the filter to the query.
     */
    public function run(Builder $builder): Builder
    {
        $search = trim((string) $this->request->get('search', ''));

        if ($search === '') {
            return $builder;
        }

        // Find all users whose name or email matches the search term
        $matchingUserIds = User::where('name', 'like', "%{$search}%")
            ->orWhere('last_name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->pluck('id');

        // Filter messages where either participant matches
        return $builder->where(function ($query) use ($matchingUserIds) {
            $query->whereIn('sender_id', $matchingUserIds)
                ->orWhereIn('receiver_id', $matchingUserIds);
        });
    }

    /**
     * The display fields for this filter.
     */
    public function display(): iterable
    {
        return [
            Input::make('search')
                ->type('search')
                ->value($this->request->get('search', ''))
                ->placeholder('Search by name or email…')
                ->title('Search Participants'),
        ];
    }
}
