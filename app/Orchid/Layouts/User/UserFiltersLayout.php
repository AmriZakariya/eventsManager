<?php

namespace App\Orchid\Layouts\User;

use App\Orchid\Filters\GeneralSearchFilter;
use App\Orchid\Filters\RoleFilter;
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
            GeneralSearchFilter::class, // Add this line
            RoleFilter::class,
        ];
    }
}
