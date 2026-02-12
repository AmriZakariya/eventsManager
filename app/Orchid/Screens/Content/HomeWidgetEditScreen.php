<?php

namespace App\Orchid\Screens\Content;

use App\Models\HomeWidget;
use App\Models\HomeWidgetItem;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class HomeWidgetEditScreen extends Screen
{
    public $widget;

    public function query(HomeWidget $widget): iterable
    {
        $widget->load(['items' => function($query) {
            $query->orderBy('order');
        }]);

        return [
            'widget' => $widget,
            'items' => $widget->items,
        ];
    }

    public function name(): ?string
    {
        return $this->widget->exists
            ? "Edit Section: {$this->widget->title}"
            : 'Create New Section';
    }

    public function description(): ?string
    {
        if ($this->widget->exists) {
            $type = HomeWidget::TYPES[$this->widget->widget_type] ?? $this->widget->widget_type;
            $itemCount = $this->widget->items->count();
            return "{$type} • {$itemCount} items";
        }
        return 'Create a new content section for your homepage';
    }

    public function commandBar(): array
    {
        $canManageContent = $this->widget->exists && !$this->widget->data_source;

        $commands = [
            Link::make('← All Sections')
                ->icon('bs.grid-3x3-gap')
                ->route('platform.content.widgets.list')
                ->class('btn btn-outline-secondary'),

            Button::make('Save Changes')
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save'),
        ];

        // Only add the Add Item button if widget exists
        if ($this->widget->exists && $canManageContent) {
            $commands[] = Link::make('+ Add Item')
                ->icon('bs.plus-lg')
                ->type(Color::SUCCESS)
                ->route('platform.content.items.create', ['widget' => $this->widget->id]);
        }

        if ($this->widget->exists) {
            $commands[] = Button::make('Delete')
                ->icon('bs.trash3')
                ->type(Color::DANGER)
                ->method('remove')
                ->confirm('Delete this section and all its items? This cannot be undone.');
        }

        return $commands;
    }

    public function layout(): iterable
    {
        $canManageContent = $this->widget->exists && !$this->widget->data_source;

        // Create tabs array
        $tabs = [
            '⚙️ Configuration' => Layout::block(
                Layout::columns([
                    Layout::rows([
                        Input::make('widget.title')
                            ->title('Section Title')
                            ->placeholder('e.g., Hero Slider, Our Services, Partners')
                            ->required()
                            ->help('Internal name for this section'),

                        Input::make('widget.identifier')
                            ->title('Translation Identifier')
                            ->placeholder('e.g. home_exhibitors_section')
                            ->help('The key used in the Flutter app (en.json) for the section title.'),

                        Select::make('widget.widget_type')
                            ->options(HomeWidget::TYPES)
                            ->title('Layout Type')
                            ->required()
                            ->help('How this section will be displayed'),

                        Input::make('widget.order')
                            ->type('number')
                            ->title('Display Order')
                            ->required()
                            ->min(0)
                            ->value($this->widget->order ?? 0)
                            ->help('Lower numbers appear first'),

                        Select::make('widget.data_source')
                            ->options(HomeWidget::DATA_SOURCES)
                            ->title('Content Source')
                            ->empty('Manual Content')
                            ->help('Leave empty to manually add items below'),

                        CheckBox::make('widget.is_active')
                            ->sendTrueOrFalse()
                            ->title('Active Status')
                            ->placeholder('Visible on website')
                            ->value(true),
                    ]),
                ])
            )->title('General Settings'),

            '🖼️ Appearance' => Layout::block(
                Layout::rows([
                    Cropper::make('widget.image')
                        ->title('Section Image')
                        ->targetRelativeUrl()
//                        ->width(1200)
//                        ->height(400)
                        ->maxFileSize(2)
                        ->help('Optional background or header image. Recommended: 1200×400px')
                        ->storage('public'),

                    Input::make('widget.icon')
                        ->title('Section Icon')
                        ->placeholder('dashboard, widgets, view_module, collections')
                        ->help('Material Icon name for this section'),
                ])
            )->title('Visual Elements'),
        ];

        // Add Content Items tab only if widget exists
        if ($this->widget->exists) {
            $contentTabContent = [];

            // Add "Add Item" button for manual content
            if ($canManageContent) {
                $contentTabContent[] = Layout::rows([
                    Link::make('➕ Add New Item')
                        ->icon('bs.plus-circle')
                        ->type(Color::PRIMARY)
                        ->route('platform.content.items.create', ['widget' => $this->widget->id])
                        ->class('btn-lg w-100 mb-4'),
                ]);
            }

            // Add either dynamic info or items table
            if ($this->widget->data_source) {
                $contentTabContent[] = Layout::view('orchid.widget-dynamic-info', [
                    'source' => $this->widget->data_source,
                    'type' => HomeWidget::DATA_SOURCES[$this->widget->data_source] ?? $this->widget->data_source,
                ]);
            } else {
                $contentTabContent[] = Layout::table('items', [
                    \Orchid\Screen\TD::make('order', '#')
                        ->sort()
                        ->width('70px')
                        ->render(function (HomeWidgetItem $item) {
                            return "<span class='badge bg-dark'>{$item->order}</span>";
                        }),

                    \Orchid\Screen\TD::make('preview', 'Preview')
                        ->width('100px')
                        ->render(function (HomeWidgetItem $item) {
                            if ($item->image_url) {
                                return "<img src='{$item->image_url}'
                                         style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;'
                                         class='border'
                                         alt='Preview'>";
                            } elseif ($item->icon) {
                                return "<div class='d-flex align-items-center justify-content-center
                                          bg-primary bg-opacity-10 rounded p-2'
                                         style='width: 50px; height: 50px;'>
                                        <i class='material-icons text-primary' style='font-size: 20px;'>{$item->icon}</i>
                                    </div>";
                            }
                            return '';
                        }),

                    \Orchid\Screen\TD::make('title', 'Content')
                        ->render(function (HomeWidgetItem $item) {
                            $title = $item->title ?: '<span class="text-muted">No title</span>';
                            $subtitle = $item->subtitle ?
                                "<div class='text-muted small mt-1'>{$item->subtitle}</div>" : '';

                            return "<div>{$title}{$subtitle}</div>";
                        }),

                    \Orchid\Screen\TD::make('actions', '')
                        ->alignRight()
                        ->width('100px')
                        ->render(function (HomeWidgetItem $item) {
                            return \Orchid\Screen\Actions\DropDown::make()
                                ->icon('bs.three-dots-vertical')
                                ->list([
                                    Link::make('Edit')
                                        ->icon('bs.pencil')
                                        ->route('platform.content.items.edit', [
                                            'widget' => $this->widget->id,
                                            'item' => $item->id
                                        ]),

                                    Button::make('Delete')
                                        ->icon('bs.trash3')
                                        ->method('removeItem', ['id' => $item->id])
                                        ->confirm('Delete this item?')
                                        ->type(Color::DANGER),
                                ]);
                        }),
                ]);
            }

            // Add the Content Items tab
            $tabs['📦 Content Items'] = Layout::block($contentTabContent)
                ->title($this->widget->data_source ? 'Dynamic Content' : 'Manual Items')
                ->description($this->widget->data_source
                    ? 'Content is automatically populated from database'
                    : 'Manage individual items in this section');
        }

        return [
            Layout::tabs($tabs),
        ];
    }

    public function save(HomeWidget $widget, Request $request)
    {
        $request->validate([
            'widget.title' => 'required|string|max:255',
            'widget.identifier' => 'nullable|string|max:255', // <--- ADD THIS
            'widget.widget_type' => 'required|string',
            'widget.order' => 'required|integer|min:0',
        ]);

        $widget->fill($request->get('widget'))->save();
        Toast::success('Section saved successfully!');
        return redirect()->route('platform.content.widgets.edit', $widget->id);
    }

    public function remove(HomeWidget $widget)
    {
        $widget->items()->delete();
        $widget->delete();
        Toast::info('Section and all its items have been deleted.');
        return redirect()->route('platform.content.widgets.list');
    }

    public function removeItem(Request $request)
    {
        $item = HomeWidgetItem::findOrFail($request->get('id'));
        $widgetId = $item->home_widget_id;
        $item->delete();

        Toast::info('Item removed successfully.');
        return redirect()->route('platform.content.widgets.edit', $widgetId);
    }
}
