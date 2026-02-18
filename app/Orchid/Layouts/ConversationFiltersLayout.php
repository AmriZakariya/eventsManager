<?php

declare(strict_types=1);

namespace App\Orchid\Layouts;

use App\Orchid\Filters\ConversationSearchFilter;
use App\Orchid\Filters\ConversationRoleFilter;
use App\Orchid\Filters\ConversationDateFilter;
use Orchid\Screen\Layouts\Selection;

class ConversationFiltersLayout extends Selection
{
    /**
     * Return the filter classes to display in the selection bar.
     */
    public function filters(): iterable
    {
        return [
            ConversationSearchFilter::class,
            ConversationRoleFilter::class,
            ConversationDateFilter::class,
        ];
    }
}
