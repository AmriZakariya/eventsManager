<?php

namespace App\Orchid\Screens\Content;

use App\Models\HomeWidget;
use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;
use Illuminate\Http\Request;

class HomeWidgetListScreen extends Screen
{
    public function query(): iterable
    {
        $widgets = HomeWidget::withCount('items')
            ->orderBy('order')
            ->get();

        return [
            'widgets' => $widgets,
            'stats' => [
                'total' => $widgets->count(),
                'active' => $widgets->where('is_active', true)->count(),
                'hidden' => $widgets->where('is_active', false)->count(),
                'dynamic' => $widgets->whereNotNull('data_source')->count(),
            ],
        ];
    }

    public function name(): ?string
    {
        return 'Homepage Layout Manager';
    }

    public function description(): ?string
    {
        return 'Drag to reorder sections, edit content, and control visibility of homepage elements';
    }

    public function commandBar(): array
    {
        return [
            Button::make('Preview Layout')
                ->icon('bs.eye')
                ->type(Color::SECONDARY)
                ->method('previewLayout'),

            Link::make('Add Section')
                ->icon('bs.plus-circle')
                ->type(Color::PRIMARY)
                ->route('platform.content.widgets.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            // Statistics Dashboard
            Layout::view('orchid.content.widget-stats', [
                'stats' => $this->query()['stats']
            ]),

            // Info Banner (FIXED - removed Layout::rows wrapper)
            Layout::view('orchid.content.widget-info'),

            // Sortable Widget List
            Layout::table('widgets', [
                TD::make('drag', '')
                    ->width('40px')
                    ->cantHide()
                    ->render(function (HomeWidget $widget) {
                        return '<div class="drag-handle" style="cursor: move; color: #999;">
                                <i class="bi bi-grip-vertical" style="font-size: 20px;"></i>
                            </div>';
                    }),

                TD::make('order', '#')
                    ->sort()
                    ->width('60px')
                    ->alignCenter()
                    ->render(function (HomeWidget $widget) {
                        return "<div class='badge bg-secondary' style='font-size: 14px; padding: 6px 10px;'>{$widget->order}</div>";
                    }),

                TD::make('preview', 'Preview')
                    ->width('100px')
                    ->render(function (HomeWidget $widget) {
                        return $this->renderPreview($widget);
                    }),

                TD::make('title', 'Section')
                    ->sort()
                    ->cantHide()
                    ->render(function (HomeWidget $widget) {
                        return $this->renderTitle($widget);
                    }),

                TD::make('type_info', 'Type & Content')
                    ->width('250px')
                    ->render(function (HomeWidget $widget) {
                        return $this->renderTypeInfo($widget);
                    }),

                TD::make('status', 'Status')
                    ->width('120px')
                    ->alignCenter()
                    ->render(function (HomeWidget $widget) {
                        return $this->renderStatus($widget);
                    }),

                TD::make('actions', 'Actions')
                    ->alignRight()
                    ->cantHide()
                    ->width('200px')
                    ->render(function (HomeWidget $widget) {
                        return $this->renderActions($widget);
                    }),
            ]),

            // Reorder Script
            Layout::view('orchid.content.widget-sortable-script'),
        ];
    }
    private function renderPreview(HomeWidget $widget): string
    {
        $html = '<div class="d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">';

        if ($widget->image_url) {
            $html .= "<div class='position-relative'>
                        <img src='{$widget->image_url}'
                             style='width: 70px; height: 70px; object-fit: cover; border-radius: 12px;'
                             class='border shadow-sm'
                             alt='Preview'>";

            if ($widget->icon) {
                $html .= "<div class='position-absolute' style='top: -5px; right: -5px;'>
                            <span class='badge bg-primary rounded-circle p-2'>
                                <i class='bi bi-{$widget->icon}' style='font-size: 12px;'></i>
                            </span>
                          </div>";
            }

            $html .= "</div>";
        } elseif ($widget->icon) {
            $iconClass = $this->getIconClass($widget->widget_type);
            $html .= "<div class='d-flex align-items-center justify-content-center {$iconClass} rounded-3 shadow-sm'
                         style='width: 70px; height: 70px;'>
                        <i class='bi bi-{$widget->icon}' style='font-size: 28px;'></i>
                      </div>";
        } else {
            $iconClass = $this->getIconClass($widget->widget_type);
            $defaultIcon = $this->getDefaultIcon($widget->widget_type);
            $html .= "<div class='d-flex align-items-center justify-content-center {$iconClass} rounded-3 shadow-sm'
                         style='width: 70px; height: 70px;'>
                        <i class='bi bi-{$defaultIcon}' style='font-size: 28px;'></i>
                      </div>";
        }

        $html .= '</div>';
        return $html;
    }

    private function renderTitle(HomeWidget $widget): string
    {
        $title = Link::make($widget->title)
            ->route('platform.content.widgets.edit', $widget->id)
            ->class('text-decoration-none');

        $subtitle = $widget->subtitle
            ? "<div class='small text-muted mt-1'>{$widget->subtitle}</div>"
            : '';

        return "<div class='fw-bold'>{$title}{$subtitle}</div>";
    }

    private function renderTypeInfo(HomeWidget $widget): string
    {
        // Define types mapping
        $types = [
            'slider' => 'Image Slider',
            'menu_grid' => 'Menu Grid',
            'dynamic_list' => 'Dynamic List',
            'banner' => 'Banner',
            'logo_cloud' => 'Logo Cloud',
        ];

        // Define data sources mapping
        $dataSources = [
            'companies' => 'Companies',
            'products' => 'Products',
            'featured_companies' => 'Featured Companies',
            'featured_products' => 'Featured Products',
        ];

        $type = $types[$widget->widget_type] ?? ucfirst(str_replace('_', ' ', $widget->widget_type));
        $typeColor = $this->getTypeColor($widget->widget_type);

        $html = "<div>";
        $html .= "<span class='badge {$typeColor} mb-2'><i class='bi bi-{$this->getTypeIcon($widget->widget_type)} me-1'></i>{$type}</span>";

        if ($widget->data_source) {
            $source = $dataSources[$widget->data_source] ?? ucfirst(str_replace('_', ' ', $widget->data_source));
            $html .= "<div class='small'>
                    <span class='badge bg-info bg-opacity-10 text-info'>
                        <i class='bi bi-lightning-charge-fill me-1'></i>Dynamic: {$source}
                    </span>
                  </div>";
        } else {
            $itemCount = $widget->items_count ?? 0;
            $countColor = $itemCount > 0 ? 'bg-primary' : 'bg-secondary';
            $textColor = $itemCount > 0 ? 'primary' : 'secondary';
            $html .= "<div class='small'>
                    <span class='badge {$countColor} bg-opacity-10 text-{$textColor}'>
                        <i class='bi bi-collection me-1'></i>{$itemCount} " . ($itemCount === 1 ? 'item' : 'items') . "
                    </span>
                  </div>";
        }

        $html .= "</div>";
        return $html;
    }

    private function renderStatus(HomeWidget $widget): string
    {
        if ($widget->is_active) {
            return '<div class="d-flex flex-column align-items-center">
                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 mb-1">
                            <i class="bi bi-eye-fill me-1"></i>Active
                        </span>
                        <small class="text-muted">Visible on app</small>
                    </div>';
        } else {
            return '<div class="d-flex flex-column align-items-center">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 mb-1">
                            <i class="bi bi-eye-slash-fill me-1"></i>Hidden
                        </span>
                        <small class="text-muted">Not visible</small>
                    </div>';
        }
    }

    private function renderActions(HomeWidget $widget): string
    {
        $toggleIcon = $widget->is_active ? 'eye-slash' : 'eye';
        $toggleText = $widget->is_active ? 'Hide' : 'Show';
        $toggleColor = $widget->is_active ? 'warning' : 'success';

        return \Orchid\Screen\Actions\DropDown::make()
            ->icon('bs.three-dots-vertical')
            ->list([
                Link::make('Edit Section')
                    ->icon('bs.pencil-square')
                    ->route('platform.content.widgets.edit', $widget->id),

                Link::make('Manage Items')
                    ->icon('bs.list-ul')
                    ->route('platform.content.widgets.edit', ['widget' => $widget->id])
                    ->canSee(!$widget->data_source),

                Button::make($toggleText)
                    ->icon("bs.{$toggleIcon}")
                    ->method('toggleActive', ['id' => $widget->id])
                    ->type(Color::{$toggleColor}() ?? Color::DEFAULT),

                Button::make('Duplicate')
                    ->icon('bs.files')
                    ->method('duplicate', ['id' => $widget->id])
                    ->confirm('Create a copy of this section?'),

                Button::make('Delete')
                    ->icon('bs.trash3')
                    ->method('remove', ['id' => $widget->id])
                    ->confirm('Delete this section and all its items permanently?')
                    ->type(Color::DANGER),
            ]);
    }

    private function getIconClass(string $type): string
    {
        return match($type) {
            'slider' => 'bg-info bg-opacity-10 text-info',
            'menu_grid' => 'bg-warning bg-opacity-10 text-warning',
            'dynamic_list' => 'bg-primary bg-opacity-10 text-primary',
            'banner' => 'bg-success bg-opacity-10 text-success',
            default => 'bg-secondary bg-opacity-10 text-secondary',
        };
    }

    private function getDefaultIcon(string $type): string
    {
        return match($type) {
            'slider' => 'images',
            'menu_grid' => 'grid-3x3',
            'dynamic_list' => 'lightning-charge',
            'banner' => 'megaphone',
            'logo_cloud' => 'building',
            default => 'layout-text-window',
        };
    }

    private function getTypeColor(string $type): string
    {
        return match($type) {
            'slider' => 'bg-info text-dark',
            'menu_grid' => 'bg-warning text-dark',
            'dynamic_list' => 'bg-primary',
            'banner' => 'bg-success',
            'logo_cloud' => 'bg-purple text-white',
            default => 'bg-secondary',
        };
    }

    private function getTypeIcon(string $type): string
    {
        return match($type) {
            'slider' => 'images',
            'menu_grid' => 'grid-3x3',
            'dynamic_list' => 'lightning-charge-fill',
            'banner' => 'megaphone-fill',
            'logo_cloud' => 'building',
            default => 'layout-text-window-reverse',
        };
    }

    public function toggleActive($id)
    {
        $widget = HomeWidget::findOrFail($id);
        $widget->is_active = !$widget->is_active;
        $widget->save();

        $status = $widget->is_active ? 'activated' : 'hidden';
        Toast::success("Section '{$widget->title}' is now {$status}.");
    }

    public function duplicate($id)
    {
        $original = HomeWidget::with('items')->findOrFail($id);

        $duplicate = $original->replicate();
        $duplicate->title = $original->title . ' (Copy)';
        $duplicate->order = HomeWidget::max('order') + 1;
        $duplicate->is_active = false;
        $duplicate->save();

        foreach ($original->items as $item) {
            $newItem = $item->replicate();
            $newItem->widget_id = $duplicate->id;
            $newItem->save();
        }

        Toast::success("Section duplicated successfully.");
    }

    public function remove($id)
    {
        $widget = HomeWidget::findOrFail($id);
        $title = $widget->title;

        $widget->items()->delete();
        $widget->delete();

        Toast::success("Section '{$title}' deleted successfully.");
    }

    public function updateOrder(Request $request)
    {
        $order = $request->get('order', []);

        foreach ($order as $index => $id) {
            HomeWidget::where('id', $id)->update(['order' => $index + 1]);
        }

        Toast::info('Order updated successfully.');
    }

    public function previewLayout()
    {
        Toast::info('Preview feature coming soon!');
    }
}
