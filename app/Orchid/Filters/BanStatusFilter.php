<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class BanStatusFilter extends Filter
{
    public function name(): string
    {
        return __('Ban status');
    }

    public function parameters(): array
    {
        return ['ban'];
    }

    private function options(): array
    {
        return [
            'banned' => __('Banned'),
            'active' => __('Not banned'),
        ];
    }

    public function run(Builder $builder): Builder
    {
        return $this->request->get('ban') === 'banned'
            ? $builder->whereNotNull('banned_at')
            : $builder->whereNull('banned_at');
    }

    public function display(): array
    {
        return [
            Select::make('ban')
                ->options($this->options())
                ->empty()
                ->value($this->request->get('ban'))
                ->title(__('Ban status')),
        ];
    }

    public function value(): string
    {
        $key = $this->request->get('ban');
        return $this->name().': '.($this->options()[$key] ?? $key);
    }
}
