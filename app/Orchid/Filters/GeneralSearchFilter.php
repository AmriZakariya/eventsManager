<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;
use Illuminate\Support\Facades\DB;

class GeneralSearchFilter extends Filter
{
    /**
     * The display name of the filter.
     */
    public function name(): string
    {
        return '🔍 Search';
    }

    /**
     * The array of parameters used by the filter.
     */
    public function parameters(): ?array
    {
        return ['search'];
    }

    /**
     * Apply the filter to the database query.
     */
    public function run(Builder $builder): Builder
    {
        $search = $this->request->get('search');

        if (!$search) {
            return $builder;
        }

        return $builder->where(function ($q) use ($search) {
            $q->where(DB::raw("CONCAT(name, ' ', last_name)"), 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('job_title', 'like', "%{$search}%")
                ->orWhereHas('company', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Render the filter input in the UI.
     */
    public function display(): iterable
    {
        return [
            Input::make('search')
                ->type('search')
                ->value($this->request->get('search'))
                ->placeholder('Search users...')
                ->title('Search')
                ->popover('Search by Name, Email, Phone, Job or Company'),
        ];
    }
}
