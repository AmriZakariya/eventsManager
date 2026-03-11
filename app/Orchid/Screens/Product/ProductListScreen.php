<?php

namespace App\Orchid\Screens\Product;

use App\Models\Product;
use App\Models\ProductCategory;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Select;
use Illuminate\Http\Request;

class ProductListScreen extends Screen
{
    public function query(Request $request): iterable
    {
        $query = Product::with(['company', 'category']);

        // Apply category filter if selected
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Apply featured filter if selected
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->get('is_featured') === '1');
        }

        // Apply type filter if selected
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->get('search') . '%');
        }

        return [
            'products' => $query->latest()->paginate(15),
            'categories' => ProductCategory::pluck('name', 'id'),
            'types' => Product::distinct()->whereNotNull('type')->pluck('type', 'type'),
            'filters' => [
                'category_id' => $request->get('category_id'),
                'is_featured' => $request->get('is_featured'),
                'type' => $request->get('type'),
                'search' => $request->get('search'),
            ]
        ];
    }

    public function name(): ?string
    {
        return 'Products Management';
    }

    public function description(): ?string
    {
        return 'Manage products displayed by exhibitors.';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Manage Categories')
                ->icon('bs.tags')
                ->class('btn btn-secondary')
                ->href(route('platform.product-categories.list')),

            Link::make('Add Product')
                ->icon('bs.plus-circle')
                ->href(route('platform.products.create'))
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('category_id')
                    ->title('Category')
                    ->fromQuery(ProductCategory::query(), 'name')
                    ->empty('All Categories', ''),

                Select::make('type')
                    ->title('Type')
                    ->fromQuery(Product::distinct()->whereNotNull('type'), 'type', 'type')
                    ->empty('All Types', ''),

                Select::make('is_featured')
                    ->title('Featured')
                    ->options([
                        '' => 'All Products',
                        '1' => 'Featured Only',
                        '0' => 'Not Featured'
                    ])
                    ->empty('All Products', ''),

                Button::make('Apply Filters')
                    ->icon('bs.funnel')
                    ->method('applyFilter')
                    ->class('btn btn-primary'),

                Button::make('Clear Filters')
                    ->icon('bs.x-circle')
                    ->method('clearFilters')
                    ->class('btn btn-outline-secondary'),
            ])->title('Filters'),

            Layout::table('products', [
                // 1. Product Image (Thumbnail)
                TD::make('image', 'Image')
                    ->width('80px')
                    ->cantHide()
                    ->render(fn (Product $p) => $p->image_url
                        ? "<img src='{$p->image_url}' alt='product' class='mw-100 d-block img-fluid rounded' style='max-height: 60px; width: 60px; object-fit: cover;'>"
                        : "<div class='bg-light rounded d-flex align-items-center justify-content-center text-muted' style='width: 60px; height: 60px; font-size: 24px;'><i class='bi bi-image'></i></div>"),

                // 2. Product Name with Featured Badge
                TD::make('name', 'Product Name')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(function (Product $p) {
                        $href = route('platform.products.edit', $p->id);
                        $name = e($p->name);
                        $badge = $p->is_featured
                            ? " <span class='badge bg-warning text-dark ms-2'><i class='bi bi-star-fill'></i> Featured</span>"
                            : '';

                        return "<div class='d-flex align-items-center'><a class='text-decoration-none' href='{$href}'>{$name}</a>{$badge}</div>";
                    }),

                // 3. Type
                TD::make('type', 'Type')
                    ->sort()
                    ->render(fn(Product $p) => $p->type
                        ? "<span class='badge bg-info text-dark'>" . e($p->type) . "</span>"
                        : "<span class='text-muted'>—</span>"),

                // 4. Category
                TD::make('category.name', 'Category')
                    ->sort()
                    ->render(fn($p) => $p->category
                        ? "<span class='badge bg-primary'>{$p->category->name}</span>"
                        : "<span class='badge bg-secondary'>Uncategorized</span>"),

                // 5. Related Company
                TD::make('company.name', 'Company')
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn($p) => $p->company
                        ? e($p->company->name)
                        : '<span class="text-muted">N/A</span>'),

                // 6. Created Date
                TD::make('created_at', 'Created')
                    ->sort()
                    ->render(fn(Product $p) => $p->created_at->format('M d, Y')),

                // 7. Actions
                TD::make('Actions')
                    ->alignRight()
                    ->cantHide()
                    ->width('100px')
                    ->render(fn (Product $p) =>
                    DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            Link::make('Edit')
                                ->route('platform.products.edit', $p->id)
                                ->icon('bs.pencil'),

                            Button::make($p->is_featured ? 'Unfeature' : 'Feature')
                                ->icon($p->is_featured ? 'bs.star' : 'bs.star-fill')
                                ->method('toggleFeatured', ['id' => $p->id]),

                            Button::make('Delete')
                                ->icon('bs.trash')
                                ->confirm('Are you sure you want to delete this product?')
                                ->method('remove', ['id' => $p->id])
                        ])
                    ),
            ])
        ];
    }

    public function applyFilter(Request $request)
    {
        return redirect()->route('platform.products.list', array_filter([
            'category_id' => $request->get('category_id') ?: null,
            'is_featured' => $request->get('is_featured') !== '' ? $request->get('is_featured') : null,
            'type' => $request->get('type') ?: null,
            'search' => $request->get('search') ?: null,
        ]));
    }

    public function clearFilters()
    {
        return redirect()->route('platform.products.list');
    }

    public function toggleFeatured(Request $request)
    {
        $product = Product::findOrFail($request->get('id'));
        $product->is_featured = !$product->is_featured;
        $product->save();

        \Orchid\Support\Facades\Toast::success($product->is_featured ? 'Product featured successfully!' : 'Product unfeatured.');
    }

    public function remove(Request $request)
    {
        Product::findOrFail($request->get('id'))->delete();

        \Orchid\Support\Facades\Toast::success('Product deleted successfully.');
    }
}
