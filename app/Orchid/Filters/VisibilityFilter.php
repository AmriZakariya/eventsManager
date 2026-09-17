<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class VisibilityFilter extends Filter
{
    public function name(): string
    {
        return __('Visibility');
    }

    public function parameters(): array
    {
        return ['visibility'];
    }

    private function options(): array
    {
        return [
            'visible' => __('Public'),
            'hidden'  => __('Hidden'),
        ];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->where(
            'is_visible',
            $this->request->get('visibility') === 'visible'
        );
    }

    public function display(): array
    {
        return [
            Select::make('visibility')
                ->options($this->options())
                ->empty()
                ->value($this->request->get('visibility'))
                ->title(__('Visibility')),
        ];
    }

    public function value(): string
    {
        $key = $this->request->get('visibility');
        return $this->name().': '.($this->options()[$key] ?? $key);
    }
}
