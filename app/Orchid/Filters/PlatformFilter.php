<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class PlatformFilter extends Filter
{
    public function name(): string
    {
        return __('Platform');
    }

    public function parameters(): array
    {
        return ['platform'];
    }

    public function run(Builder $builder): Builder
    {
        $platform = $this->request->get('platform');

        return $platform === 'unknown'
            ? $builder->whereNull('platform')
            : $builder->where('platform', $platform);
    }

    public function display(): array
    {
        return [
            Select::make('platform')
                ->options(self::options())
                ->empty()
                ->value($this->request->get('platform'))
                ->title(__('Platform')),
        ];
    }

    public function value(): string
    {
        $key = $this->request->get('platform');

        return $this->name().': '.(self::options()[$key] ?? $key);
    }

    private static function options(): array
    {
        return User::PLATFORMS + ['unknown' => 'Unknown'];
    }
}
