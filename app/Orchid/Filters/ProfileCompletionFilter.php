<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class ProfileCompletionFilter extends Filter
{
    public function name(): string
    {
        return __('Profile Status');
    }

    public function parameters(): array
    {
        return ['profile'];
    }

    private function options(): array
    {
        return [
            'complete'   => __('Profile Complete'),
            'incomplete' => __('Needs Profile'),
        ];
    }

    public function run(Builder $builder): Builder
    {
        // "Profile complete" == the user has set a password (password_is_set).
        return $builder->where(
            'password_is_set',
            $this->request->get('profile') === 'complete'
        );
    }

    public function display(): array
    {
        return [
            Select::make('profile')
                ->options($this->options())
                ->empty()
                ->value($this->request->get('profile'))
                ->title(__('Profile Status')),
        ];
    }

    public function value(): string
    {
        $key = $this->request->get('profile');
        return $this->name().': '.($this->options()[$key] ?? $key);
    }
}
