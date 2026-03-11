<?php

namespace App\Orchid\Screens\ProductCategory;

use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\TD;

class ProductCategoryEditScreen extends Screen
{
    public $category;

    public function query(ProductCategory $category): iterable
    {
        // Load products linked to this category
        $category->load('products');

        return [
            'category' => $category,
            'products' => $category->products,
        ];
    }

    public function name(): ?string
    {
        return $this->category->exists ? 'Edit Category' : 'Create Category';
    }

    public function commandBar(): array
    {
        return [
            Button::make('Save')
                ->icon('bs.check-circle')
                ->method('save'),

            Button::make('Delete')
                ->icon('bs.trash3')
                ->method('remove')
                ->canSee($this->category->exists),
        ];
    }

    public function layout(): iterable
    {
        return [
            // 1. Edit Category Form
            Layout::rows([
                Input::make('category.name')
                    ->title('Category Name')
                    ->required(),

                Input::make('category.slug')
                    ->title('Slug')
                    ->help('Unique identifier for APIs (auto-generated if empty).'),
            ]),

            // 2. List of Linked Products (Only visible if category exists)
            Layout::block(
                Layout::table('products', [
                    TD::make('name', 'Product Name')
                        ->render(function (Product $product) {
                            $href = route('platform.products.edit', $product->id);
                            $name = e($product->name);

                            return "<a class='text-primary' href='{$href}'>{$name}</a>";
                        }),

                    TD::make('type', 'Type'),

                    TD::make('created_at', 'Created')
                        ->render(fn($p) => $p->created_at->format('M d, Y')),
                ])
            )
                ->title('Linked Products')
                ->description('Products currently assigned to this category.')
                ->vertical()
                ->canSee($this->category->exists),
        ];
    }

    public function save(ProductCategory $category, Request $request)
    {
        $data = $request->get('category');

        // Simple slug generation
        if (empty($data['slug'])) {
            $data['slug'] = \Str::slug($data['name']);
        }

        $category->fill($data)->save();
        Toast::info('Category saved.');
        return redirect()->route('platform.categories.list');
    }

    public function remove(ProductCategory $category)
    {
        $category->delete();
        Toast::info('Category deleted.');
        return redirect()->route('platform.categories.list');
    }
}
