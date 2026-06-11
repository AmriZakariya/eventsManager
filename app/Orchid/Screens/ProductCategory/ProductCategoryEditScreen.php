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
        $category->hydrateTranslationInputs(['name']);

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
            Layout::tabs([
                'English' => Layout::rows([
                    Input::make('category.name_translations.en')
                        ->title('Category Name')
                        ->value($this->category->translationInput('name')['en'] ?? null)
                        ->required(),
                ]),

                'Français' => Layout::rows([
                    Input::make('category.name_translations.fr')
                        ->title('Nom de la catégorie')
                        ->value($this->category->translationInput('name')['fr'] ?? null),
                ]),

                'العربية' => Layout::rows([
                    Input::make('category.name_translations.ar')
                        ->title('اسم الفئة')
                        ->value($this->category->translationInput('name')['ar'] ?? null),
                ]),
            ]),

            Layout::rows([
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
        $request->validate([
            'category.name_translations.en' => 'required|string|max:255',
            'category.name_translations.fr' => 'nullable|string|max:255',
            'category.name_translations.ar' => 'nullable|string|max:255',
            'category.slug' => 'nullable|string|max:255',
        ]);

        $data = $category->prepareTranslatedData($data, ['name']);

        // Simple slug generation
        if (empty($data['slug'])) {
            $data['slug'] = \Str::slug($data['name']);
        }

        $category->fill($data)->save();
        Toast::info('Category saved.');
        return redirect()->route('platform.product-categories.list');
    }

    public function remove(ProductCategory $category)
    {
        $category->delete();
        Toast::info('Category deleted.');
        return redirect()->route('platform.product-categories.list');
    }
}
