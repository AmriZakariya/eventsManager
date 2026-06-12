<?php

namespace App\Orchid\Filters;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class AuditLogSourceFilter extends Filter
{
    public function name(): string
    {
        return 'Source';
    }

    public function parameters(): ?array
    {
        return ['source'];
    }

    public function run(Builder $builder): Builder
    {
        $source = $this->request->get('source');

        if (! $source) {
            return $builder;
        }

        return $builder->where('source', $source);
    }

    public function display(): iterable
    {
        return [
            Select::make('source')
                ->options([
                    'web' => 'Web',
                    'api' => 'API',
                    'admin' => 'Admin',
                    'console' => 'Console',
                ])
                ->empty('All sources')
                ->value($this->request->get('source'))
                ->title('Source'),
        ];
    }
}
