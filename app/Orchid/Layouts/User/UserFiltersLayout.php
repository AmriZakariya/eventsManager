<?php

namespace App\Orchid\Layouts\User;

use App\Orchid\Filters\GeneralSearchFilter;
use App\Orchid\Filters\RoleFilter;
use App\Orchid\Filters\ProfileCompletionFilter;
use App\Orchid\Filters\SourceFilter;
use App\Orchid\Filters\VisibilityFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class UserFiltersLayout extends Selection
{
    /**
     * @return string[]|Filter[]
     */
    public function filters(): array
    {
        return [
            GeneralSearchFilter::class,
            RoleFilter::class,
            ProfileCompletionFilter::class,
            SourceFilter::class,
            VisibilityFilter::class,
        ];
    }
}
