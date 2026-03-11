<?php

namespace App\Orchid\Screens\Product;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Select;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductListScreen extends Screen
{
    public function query(Request $request): iterable
    {
        $query = $this->buildProductsQuery($request);

        return [
            'products' => $query->paginate(15)->withQueryString(),
            'categories' => ProductCategory::pluck('name', 'id'),
            'types' => Product::distinct()->whereNotNull('type')->pluck('type', 'type'),
            // Persist filter values in the form inputs
            'category_id' => $request->get('category_id'),
            'type' => $request->get('type'),
            'is_featured' => $request->get('is_featured'),
            'search' => $request->get('search'),
            'filters' => [
                'category_id' => $request->get('category_id'),
                'is_featured' => $request->get('is_featured'),
                'type' => $request->get('type'),
                'search' => $request->get('search'),
            ]
        ];
    }

    private function buildProductsQuery(Request $request): Builder
    {
        $query = Product::query()->with(['company', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->get('is_featured') === '1');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%');
            });
        }

        $sort = $request->get('sort');
        if (is_array($sort)) {
            $sort = $sort[0] ?? null;
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $column = ltrim($sort, '-');

            if ($column === 'company.name') {
                $query->leftJoin('companies', 'products.company_id', '=', 'companies.id')
                    ->select('products.*')
                    ->orderBy('companies.name', $direction);
            } elseif ($column === 'category.name') {
                $query->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
                    ->select('products.*')
                    ->orderBy('product_categories.name', $direction);
            } elseif (in_array($column, ['id', 'name', 'type', 'is_featured', 'created_at'], true)) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->orderByDesc('id');
        }

        return $query;
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

            Button::make('Export CSV')
                ->icon('bs.download')
                ->method('export')
                ->class('btn btn-outline-secondary'),

            Link::make('Add Product')
                ->icon('bs.plus-circle')
                ->href(route('platform.products.create'))
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Group::make([
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
                            '' => 'All',
                            '1' => 'Featured',
                            '0' => 'Not Featured',
                        ])
                        ->empty('All', ''),

                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Name or type...'),
                ]),

                Group::make([
                    Button::make('Apply')
                        ->icon('bs.funnel')
                        ->method('applyFilter')
                        ->class('btn btn-primary'),

                    Button::make('Reset')
                        ->icon('bs.x-circle')
                        ->method('clearFilters')
                        ->class('btn btn-outline-secondary'),
                ])->autoWidth(),
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

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildProductsQuery($request);

        $filename = 'products-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Company', 'Category', 'Type', 'Featured', 'Created At']);

            $query->chunk(500, function ($products) use ($out) {
                foreach ($products as $p) {
                    fputcsv($out, [
                        $p->id,
                        $p->name,
                        $p->company?->name,
                        $p->category?->name,
                        $p->type,
                        $p->is_featured ? 'Yes' : 'No',
                        optional($p->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
