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
        $search = trim((string) $this->request->get('search'));

        if ($search === '') {
            return $builder;
        }

        // "#498" = exactly user 498.
        if (preg_match('/^#(\d+)$/', $search, $m)) {
            return $builder->where('users.id', (int) $m[1]);
        }

        return $builder->where(function ($q) use ($search) {
            // A plain number also matches the user ID.
            if (ctype_digit($search)) {
                $q->where('users.id', (int) $search)->orWhere(fn ($q) => $this->matchText($q, $search));

                return;
            }

            $this->matchText($q, $search);
        });
    }

    private function matchText(Builder $q, string $search): void
    {
        $q->where(function ($q) use ($search) {
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
                ->popover('Search by ID (#498 for that user only), Name, Email, Phone, Job or Company'),
        ];
    }
}
