<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class SourceFilter extends Filter
{
    public function name(): string
    {
        return __('Source');
    }

    public function parameters(): array
    {
        return ['source'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->where('created_source', $this->request->get('source'));
    }

    public function display(): array
    {
        return [
            Select::make('source')
                ->options(User::createdSourceOptions())
                ->empty()
                ->value($this->request->get('source'))
                ->title(__('Source')),
        ];
    }

    public function value(): string
    {
        $key = $this->request->get('source');
        return $this->name().': '.(User::createdSourceOptions()[$key] ?? $key);
    }
}
