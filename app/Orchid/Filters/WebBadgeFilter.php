<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class WebBadgeFilter extends Filter
{
    public function name(): string
    {
        return __('Website badge');
    }

    public function parameters(): array
    {
        return ['web_badge'];
    }

    private function options(): array
    {
        return [
            'received' => __('Received'),
            'missing'  => __('Not received'),
        ];
    }

    public function run(Builder $builder): Builder
    {
        return $this->request->get('web_badge') === 'received'
            ? $builder->whereNotNull('web_badge_code')
            : $builder->whereNull('web_badge_code');
    }

    public function display(): array
    {
        return [
            Select::make('web_badge')
                ->options($this->options())
                ->empty()
                ->value($this->request->get('web_badge'))
                ->title(__('Website badge')),
        ];
    }

    public function value(): string
    {
        $key = $this->request->get('web_badge');
        return $this->name().': '.($this->options()[$key] ?? $key);
    }
}
