<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class SignupMethodFilter extends Filter
{
    public function name(): string
    {
        return __('Sign-up method');
    }

    public function parameters(): array
    {
        return ['signup'];
    }

    public function run(Builder $builder): Builder
    {
        $method = $this->request->get('signup');

        return $method === 'unknown'
            ? $builder->whereNull('signup_method')
            : $builder->where('signup_method', $method);
    }

    public function display(): array
    {
        return [
            Select::make('signup')
                ->options(self::options())
                ->empty()
                ->value($this->request->get('signup'))
                ->title(__('Sign-up method')),
        ];
    }

    public function value(): string
    {
        $key = $this->request->get('signup');

        return $this->name().': '.(self::options()[$key] ?? $key);
    }

    private static function options(): array
    {
        return User::signupMethodOptions() + ['unknown' => 'Unknown (before tracking)'];
    }
}
