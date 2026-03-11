<?php

namespace App\Orchid\Screens\ProductCategory;

use App\Models\ProductCategory;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;

class ProductCategoryListScreen extends Screen
{
    public function query(Request $request): iterable
    {
        $query = ProductCategory::withCount('products');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return [
            'categories' => $query->latest()->paginate(15),
            'search' => $request->get('search', ''),
        ];
    }

    public function name(): ?string
    {
        return 'Product Categories';
    }

    public function description(): ?string
    {
        return 'Organize your products with categories.';
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Back to Products')
                ->icon('bs.arrow-left')
                ->class('btn btn-link')
                ->href(route('platform.products.list')),

            Link::make('Add Category')
                ->icon('bs.plus-circle')
                ->class('btn btn-primary')
                ->href(route('platform.product-categories.create')),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('search')
                    ->title('Search Categories')
                    ->placeholder('Search by name or slug...')
                    ->help('Press enter or click "Search" to filter'),

                Button::make('Search')
                    ->icon('bs.search')
                    ->method('applySearch')
                    ->class('btn btn-primary'),

                Button::make('Clear')
                    ->icon('bs.x-circle')
                    ->method('clearSearch')
                    ->class('btn btn-outline-secondary'),
            ])->title('Search & Filters'),

            Layout::rows([
                Button::make('Delete Selected')
                    ->icon('bs.trash')
                    ->confirm('Are you sure you want to delete the selected categories? Only categories without products will be deleted.')
                    ->method('bulkDelete')
                    ->class('btn btn-danger'),

                Button::make('Export Selected')
                    ->icon('bs.download')
                    ->method('bulkExport')
                    ->class('btn btn-outline-secondary'),
            ])->title('Bulk Actions'),

            Layout::table('categories', [
                TD::make('__checkbox', '')
                    ->width('40px')
                    ->cantHide()
                    ->render(fn(ProductCategory $category) =>
                    $category->products_count === 0
                        ? "<input type='checkbox' class='form-check-input' name='categories[]' value='{$category->id}'>"
                        : "<span title='Category has products' class='text-muted'><i class='bi bi-lock'></i></span>"
                    ),

                TD::make('name', 'Category Name')
                    ->sort()
                    ->render(function (ProductCategory $category) {
                        $href = route('platform.product-categories.edit', $category->id);
                        $name = e($category->name);

                        return '<div class="d-flex align-items-center">'
                            . '<div class="bg-primary rounded-circle me-2" style="width: 10px; height: 10px;"></div>'
                            . "<a class='fw-bold text-decoration-none' href='{$href}'>{$name}</a>"
                            . '</div>';
                    }),

                TD::make('slug', 'Slug')
                    ->sort()
                    ->render(fn (ProductCategory $category) =>
                    "<code class='px-2 py-1 bg-light rounded small'>{$category->slug}</code>"
                    ),

                TD::make('products_count', 'Products')
                    ->sort()
                    ->alignCenter()
                    ->width('120px')
                    ->render(function (ProductCategory $category) {
                        if ($category->products_count > 0) {
                            $href = route('platform.products.list', ['category_id' => $category->id]);
                            $count = (int) $category->products_count;

                            return "<a class='text-decoration-none' href='{$href}'><span class='badge bg-primary fs-6'>{$count}</span></a>";
                        }

                        return "<span class='badge bg-secondary fs-6'>0</span>";
                    }),

                TD::make('created_at', 'Created')
                    ->sort()
                    ->width('150px')
                    ->render(fn (ProductCategory $category) =>
                        '<div class="text-muted small">' .
                        '<div><i class="bi bi-calendar3"></i> ' . $category->created_at->format('M d, Y') . '</div>' .
                        '<div><i class="bi bi-clock"></i> ' . $category->created_at->format('H:i') . '</div>' .
                        '</div>'
                    ),

                TD::make('updated_at', 'Last Updated')
                    ->sort()
                    ->width('150px')
                    ->defaultHidden()
                    ->render(fn (ProductCategory $category) =>
                        '<span class="text-muted small">' . $category->updated_at->diffForHumans() . '</span>'
                    ),

                TD::make('Actions')
                    ->alignRight()
                    ->cantHide()
                    ->width('100px')
                    ->render(fn (ProductCategory $category) =>
                    DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->class('btn btn-sm btn-link')
                        ->list([
                            Link::make('Edit')
                                ->route('platform.product-categories.edit', $category->id)
                                ->icon('bs.pencil'),

                            Link::make('View Products')
                                ->route('platform.products.list', ['category_id' => $category->id])
                                ->icon('bs.box-seam')
                                ->canSee($category->products_count > 0),

                            Button::make('Duplicate')
                                ->icon('bs.files')
                                ->method('duplicate', ['id' => $category->id]),

                            Button::make('Delete')
                                ->icon('bs.trash')
                                ->confirm('Are you sure? This category will be removed.')
                                ->method('remove', ['id' => $category->id])
                                ->canSee($category->products_count === 0)
                        ])
                    ),
            ])
        ];
    }

    public function applySearch(Request $request)
    {
        return redirect()->route('platform.product-categories.list', [
            'search' => $request->get('search')
        ]);
    }

    public function clearSearch()
    {
        return redirect()->route('platform.product-categories.list');
    }

    public function remove(Request $request)
    {
        $category = ProductCategory::findOrFail($request->get('id'));

        if ($category->products()->count() > 0) {
            Toast::warning('Cannot delete category with assigned products.');
            return;
        }

        $category->delete();
        Toast::success('Category deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $categoryIds = $request->get('categories', []);

        if (empty($categoryIds)) {
            Toast::warning('No categories selected.');
            return;
        }

        // Only delete categories without products
        $categories = ProductCategory::whereIn('id', $categoryIds)
            ->doesntHave('products')
            ->get();

        $deletedCount = $categories->count();
        $skippedCount = count($categoryIds) - $deletedCount;

        foreach ($categories as $category) {
            $category->delete();
        }

        if ($deletedCount > 0) {
            $plural = $deletedCount === 1 ? 'category' : 'categories';
            Toast::success("Deleted {$deletedCount} {$plural}.");
        }

        if ($skippedCount > 0) {
            $plural = $skippedCount === 1 ? 'category' : 'categories';
            Toast::warning("{$skippedCount} {$plural} skipped (contains products).");
        }
    }

    public function bulkExport(Request $request)
    {
        $categoryIds = $request->get('categories', []);

        if (empty($categoryIds)) {
            Toast::warning('No categories selected.');
            return;
        }

        $categories = ProductCategory::whereIn('id', $categoryIds)
            ->withCount('products')
            ->get();

        $csvData = "Name,Slug,Products Count,Created At\n";
        foreach ($categories as $category) {
            $csvData .= "\"{$category->name}\",\"{$category->slug}\",{$category->products_count},\"{$category->created_at->format('Y-m-d H:i:s')}\"\n";
        }

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="categories-' . now()->format('Y-m-d') . '.csv"');
    }

    public function duplicate(Request $request)
    {
        $category = ProductCategory::findOrFail($request->get('id'));

        $newCategory = $category->replicate();
        $newCategory->name = $category->name . ' (Copy)';
        $newCategory->slug = $category->slug . '-copy-' . uniqid();
        $newCategory->save();

        Toast::success('Category duplicated successfully.');
        return redirect()->route('platform.product-categories.edit', $newCategory->id);
    }
}
