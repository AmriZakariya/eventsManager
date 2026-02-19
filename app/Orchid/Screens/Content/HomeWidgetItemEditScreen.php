<?php

namespace App\Orchid\Screens\Content;

use App\Models\HomeWidgetItem;
use App\Models\HomeWidget;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Color;

class HomeWidgetItemEditScreen extends Screen
{
    public $item;
    public $widget;

    public function query(HomeWidgetItem $item, Request $request): iterable
    {
        // Get widget ID from route parameter
        $widgetId = $request->route('widget');
        $this->widget = HomeWidget::find($widgetId);

        // If widget not found from route, try from item
        if (!$this->widget && $item->exists) {
            $this->widget = $item->widget;
        }

        // If duplicating an item
        if ($request->get('duplicate') && !$item->exists) {
            $original = HomeWidgetItem::find($request->get('duplicate'));
            if ($original) {
                $item->fill($original->toArray());
                $item->id = null;
                $item->exists = false;
            }
        }

        return [
            'item' => $item,
            'widget' => $this->widget,
            // Bind manual URL to the field if the current image is a valid web URL
            'manual_image_url' => filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : null,
        ];
    }

    public function name(): ?string
    {
        $widgetTitle = $this->widget->title ?? 'Section';
        return $this->item->exists
            ? "Edit: {$this->item->title}"
            : "Add to: {$widgetTitle}";
    }

    public function description(): ?string
    {
        $type = $this->widget->widget_type ?? 'general';
        $descriptions = [
            'slider' => 'Slide in image carousel',
            'menu_grid' => 'Grid menu item with icon',
            'logo_cloud' => 'Partner/Client logo',
            'single_banner' => 'Promotional banner',
            'dynamic_list' => 'Content list item',
        ];

        $desc = $descriptions[$type] ?? 'Content item';
        return $this->item->exists ? "Edit {$desc}" : "Create new {$desc}";
    }

    public function commandBar(): array
    {
        $widgetId = $this->widget->id ?? 1;

        return [
            Link::make('← Back to Section')
                ->icon('bs.arrow-left')
                ->route('platform.content.widgets.edit', $widgetId)
                ->class('btn btn-outline-secondary'),

            Button::make('Save')
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save'),

            Button::make('Save & Add Another')
                ->icon('bs.plus-circle')
                ->type(Color::SUCCESS)
                ->method('saveAndAddAnother')
                ->canSee(!$this->item->exists),

            Button::make('Delete')
                ->icon('bs.trash3')
                ->type(Color::DANGER)
                ->method('remove')
                ->confirm('Are you sure you want to delete this item? This action cannot be undone.')
                ->canSee($this->item->exists),
        ];
    }

    public function layout(): iterable
    {
        $type = $this->widget->widget_type ?? 'slider';
        $isGrid = $type === 'menu_grid';
        $isLogo = $type === 'logo_cloud';
        $isSlider = $type === 'slider';
        $isBanner = $type === 'single_banner';

        $iconHelp = $isGrid
            ? 'Required for menu items. <a href="https://fonts.google.com/icons" target="_blank" class="text-decoration-none">Browse Material Icons →</a>'
            : 'Optional icon for visual reference';

        $imageHelp = match($type) {
            'logo_cloud' => 'Logo image. Recommended: 300×200px, PNG with transparent background',
            'slider' => 'Slide image. Recommended: 800×500px, JPG or PNG',
            'single_banner' => 'Banner image. Recommended: 1200×400px',
            default => 'Optional image. Recommended: 800×500px'
        };

        return [
            Layout::columns([
                // Left Column: Content
                Layout::rows([
                    Input::make('item.home_widget_id')
                        ->type('hidden')
                        ->value($this->widget->id),

                    Input::make('item.title')
                        ->title('Title')
                        ->placeholder('Enter a descriptive title')
                        ->required()
                        ->help('Main text that will be displayed'),

                    Input::make('item.identifier')
                        ->title('Title Translation Key')
                        ->placeholder('e.g. slide_1_title'),

                    TextArea::make('item.subtitle')
                        ->title('Description / Subtitle')
                        ->rows(3)
                        ->placeholder('Optional supporting text or description')
                        ->canSee($isSlider || $isBanner)
                        ->help('Secondary text shown below the title'),

                    Input::make('item.action_url')
                        ->title('Link URL')
                        ->placeholder($isGrid ? '/dashboard' : 'https://example.com')
                        ->help('Where users will be directed when clicking this item'),
                ])->title('📝 Content Details'),

                // Right Column: Visual Elements
                Layout::rows([
                    Input::make('item.icon')
                        ->title('Material Icon')
                        ->placeholder('calendar_today, person, home, business, etc.')
                        ->required($isGrid)
                        ->help($iconHelp),

                    Input::make('manual_image_url')
                        ->title('Manual Image URL')
                        ->type('url')
                        ->placeholder('https://example.com/image.jpg')
                        ->help('Provide a direct URL. (If used, this will override the upload box below)'),

                    Cropper::make('item.image')
                        ->title('Upload Image')
                        ->targetRelativeUrl()
                        ->maxFileSize(2)
                        ->help($imageHelp . ' - Leave empty if using the Manual URL above.')
                        ->storage('public'),

                    Input::make('item.order')
                        ->type('number')
                        ->title('Display Order')
                        ->required()
                        ->min(0)
                        ->value($this->item->order ?? $this->getNextOrder())
                        ->help('Items are sorted by this number (lower = first)'),
                ])->title('🎨 Visual Elements'),
            ]),

            // Preview Section
            Layout::block(
                Layout::view('orchid.item-preview', [
                    'item' => $this->item,
                    'widget' => $this->widget,
                    'widgetType' => $type,
                ])
            )
                ->title('👁️ Preview')
                ->description('See how this item will look on the website'),
        ];
    }

    private function getNextOrder()
    {
        if (!$this->widget) {
            return 0;
        }

        $lastItem = HomeWidgetItem::where('home_widget_id', $this->widget->id)
            ->orderBy('order', 'desc')
            ->first();

        return $lastItem ? $lastItem->order + 1 : 0;
    }

    public function save(HomeWidgetItem $item, Request $request)
    {
        $validated = $request->validate([
            'item.home_widget_id' => 'required|exists:home_widgets,id',
            'item.title' => 'required|string|max:255',
            'item.identifier' => 'nullable|string|max:255',
            'item.subtitle' => 'nullable|string|max:500',
            'item.action_url' => 'nullable|string|max:500',
            'item.icon' => 'nullable|string|max:50',
            'item.image' => 'nullable|string',
            'item.order' => 'required|integer|min:0',
            'manual_image_url' => 'nullable|string', // Safe manual URL injection
        ]);

        $data = $validated['item'];

        // If user provided a manual URL, use it instead of the uploaded image
        if (!empty($validated['manual_image_url'])) {
            $data['image'] = $validated['manual_image_url'];
        }

        // Ensure we have the widget ID
        if (empty($data['home_widget_id'])) {
            $data['home_widget_id'] = $this->widget->id;
        }

        $item->fill($data)->save();

        Toast::success($this->item->exists ? 'Item updated successfully!' : 'Item created successfully!');
        return redirect()->route('platform.content.widgets.edit', $data['home_widget_id']);
    }

    public function saveAndAddAnother(Request $request)
    {
        $validated = $request->validate([
            'item.home_widget_id' => 'required|exists:home_widgets,id',
            'item.title' => 'required|string|max:255',
            'item.icon' => 'nullable|string|max:50',
            'item.image' => 'nullable|string',
            'item.order' => 'required|integer|min:0',
            'manual_image_url' => 'nullable|string', // Safe manual URL injection
        ]);

        $data = $validated['item'];

        // If user provided a manual URL, use it instead of the uploaded image
        if (!empty($validated['manual_image_url'])) {
            $data['image'] = $validated['manual_image_url'];
        }

        // Ensure we have the widget ID
        if (empty($data['home_widget_id'])) {
            $data['home_widget_id'] = $this->widget->id;
        }

        HomeWidgetItem::create($data);

        Toast::success('Item saved! You can now add another.');
        return redirect()->route('platform.content.items.create', [
            'widget' => $data['home_widget_id']
        ]);
    }

    public function remove(HomeWidgetItem $item)
    {
        $widgetId = $item->home_widget_id;
        $item->delete();

        Toast::info('Item deleted.');
        return redirect()->route('platform.content.widgets.edit', $widgetId);
    }
}
