<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;
use App\Models\User;

class ConversationSearchFilter extends Filter
{
    public function name(): string
    {
        return '🔍 Search';
    }

    public function parameters(): ?array
    {
        return ['search'];
    }

    public function run(Builder $builder): Builder
    {
        $search = $this->request->get('search');

        if (!$search) {
            return $builder;
        }

        // 1. Find users matching the search term
        $matchingUserIds = User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->pluck('id');

        // 2. Filter messages where EITHER participant matches
        return $builder->where(function ($query) use ($matchingUserIds) {
            $query->whereIn('sender_id', $matchingUserIds)
                ->orWhereIn('receiver_id', $matchingUserIds);
        });
    }

    public function display(): iterable
    {
        return [
            Input::make('search')
                ->type('search')
                ->value($this->request->get('search'))
                ->placeholder('Search by name or email...')
                ->title('Search Participants'),
        ];
    }
}
