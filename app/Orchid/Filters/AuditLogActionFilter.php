<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class AuditLogActionFilter extends Filter
{
    public function name(): string
    {
        return 'Action';
    }

    public function parameters(): ?array
    {
        return ['action'];
    }

    public function run(Builder $builder): Builder
    {
        $action = $this->request->get('action');

        if (! $action) {
            return $builder;
        }

        return $builder->where('action', $action);
    }

    public function display(): iterable
    {
        return [
            Select::make('action')
                ->options([
                    'login' => 'Login',
                    'logout' => 'Logout',
                    'create' => 'Create',
                    'update' => 'Update',
                    'delete' => 'Delete',
                    'api_request' => 'API Request',
                ])
                ->empty('All actions')
                ->value($this->request->get('action'))
                ->title('Action'),
        ];
    }
}
