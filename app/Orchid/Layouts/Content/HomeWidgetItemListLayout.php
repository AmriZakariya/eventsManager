<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Content;

use App\Models\HomeWidgetItem;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Screen\Fields\Group;

class HomeWidgetItemListLayout extends Table
{
    public $target = 'items';

    public function columns(): array
    {
        return [
            // 1. PREVIEW IMAGE
            TD::make('image', 'Preview')
                ->width('80px')
                ->align(TD::ALIGN_CENTER)
                ->render(fn (HomeWidgetItem $item) => $item->image_url
                    ? "<img src='{$item->image_url}' style='height:40px; border-radius:4px;'>"
                    : ($item->icon ? "<i class='material-icons text-muted'>{$item->icon}</i>" : '')),

            // 2. TITLE (Clickable)
            TD::make('title', 'Title')
                ->render(fn (HomeWidgetItem $item) => Link::make($item->title ?? 'Untitled')
                    ->route('platform.content.items.edit', $item->id)
                    ->class('fw-bold')),

            // 3. TARGET / DESTINATION
            TD::make('action_url', 'Link')
                ->render(function (HomeWidgetItem $item) {
                    $url = $item->action_url;
                    if (!$url) {
                        return "<span class='text-muted text-xs'>—</span>";
                    }
                    // External web link
                    if (str_starts_with($url, 'http')) {
                        return "<span class='text-info text-xs'>🔗 " . e($url) . "</span>";
                    }
                    // Internal app screen — show its friendly label
                    $label = HomeWidgetItem::navTargets()[$url] ?? $url;
                    return "<span class='text-muted text-xs'>📱 " . e($label) . "</span>";
                }),

            // 4. ORDER
            TD::make('order', 'Order')->sort()->width('60px'),

            // 5. DIRECT ACTION BUTTONS (Fixes "No Action Button" issue)
            TD::make('Actions')
                ->align(TD::ALIGN_RIGHT)
                ->width('120px')
                ->render(fn (HomeWidgetItem $item) => Group::make([

                    // EDIT BUTTON (Visible Pencil)
                    Link::make('')
                        ->route('platform.content.items.edit', $item->id)
                        ->icon('bs.pencil')
                        ->class('btn btn-sm btn-light text-primary me-2'), // Light button styling

                    // DELETE BUTTON (Visible Trash)
                    Button::make('')
                        ->icon('bs.trash3')
                        ->method('removeItem', ['id' => $item->id])
                        ->confirm('Delete this item?')
                        ->class('btn btn-sm btn-light text-danger'),
                ])),
        ];
    }
}
