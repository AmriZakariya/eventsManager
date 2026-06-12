<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class AuditLogSearchFilter extends Filter
{
    public function name(): string
    {
        return 'Search';
    }

    public function parameters(): ?array
    {
        return ['search'];
    }

    public function run(Builder $builder): Builder
    {
        $search = $this->request->get('search');

        if (! $search) {
            return $builder;
        }

        return $builder->where(function ($q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    public function display(): iterable
    {
        return [
            Input::make('search')
                ->type('search')
                ->value($this->request->get('search'))
                ->placeholder('Search description or user...')
                ->title('Search'),
        ];
    }
}
