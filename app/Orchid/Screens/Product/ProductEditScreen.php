<?php

namespace App\Orchid\Screens\Product;

use App\Models\Product;
use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ProductEditScreen extends Screen
{
    /**
     * @var Product
     */
    public $product;

    /**
     * Query data.
     *
     * @param Product $product
     *
     * @return array
     */
    public function query(Product $product): iterable
    {
        $product->hydrateTranslationInputs(['name', 'type', 'description']);

        return [
            'product' => $product
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->product->exists ? 'Edit Product' : 'Create Product';
    }

    /**
     * Display header description.
     *
     * @return string|null
     */
    public function description(): ?string
    {
        return $this->product->exists
            ? 'Update product details and settings'
            : 'Add a new product to the catalog';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Back to Products')
                ->icon('bs.arrow-left')
                ->route('platform.products.list'),

            Button::make('Save')
                ->icon('bs.check-circle')
                ->method('save'),

            Button::make('Remove')
                ->icon('bs.trash3')
                ->method('remove')
                ->confirm('Are you sure you want to delete this product?')
                ->canSee($this->product->exists),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Product Details' => Layout::columns([
                    Layout::rows([
                    Group::make([
                        Relation::make('product.company_id')
                            ->title('Company')
                            ->required()
                            ->fromModel(Company::class, 'name')
                            ->searchColumns('name', 'email'),
                    ]),

                    Group::make([
                        Relation::make('product.category_id')
                            ->title('Category')
                            ->fromModel(ProductCategory::class, 'name')
                            ->empty('No category')
                            ->help('Pick from the list, or type a new one.'),

                        Input::make('product.category_name')
                            ->title('Create Category')
                            ->placeholder('Type a new category name (optional)')
                            ->help('If filled, it will create/use this category and override the selection.'),
                    ]),
                    ]),

                    Layout::rows([
                    Cropper::make('product.image')
                        ->title('Product Image')
                        ->targetRelativeUrl()
                        ->help('Recommended size: 800×600px.'),

                    CheckBox::make('product.is_featured')
                        ->title('Featured Product')
                        ->placeholder('Highlight this product')
                        ->help('Featured products will be highlighted in listings.')
                        ->sendTrueOrFalse(),
                    ]),
                ]),

                'Localized Content' => Layout::tabs([
                    'English' => Layout::rows([
                        Input::make('product.name_translations.en')
                            ->title('Product Name')
                            ->value($this->product->translationInput('name')['en'] ?? null)
                            ->required()
                            ->placeholder('Enter product name'),

                        Input::make('product.type_translations.en')
                            ->title('Product Type')
                            ->value($this->product->translationInput('type')['en'] ?? null)
                            ->placeholder('e.g., Chemicals, Machines')
                            ->help('Optional: used for filtering.'),

                        TextArea::make('product.description_translations.en')
                            ->title('Description')
                            ->value($this->product->plainText($this->product->translationInput('description')['en'] ?? null))
                            ->rows(5)
                            ->placeholder('Enter detailed product description'),
                    ]),

                    'Français' => Layout::rows([
                        Input::make('product.name_translations.fr')
                            ->title('Nom du produit')
                            ->value($this->product->translationInput('name')['fr'] ?? null),

                        Input::make('product.type_translations.fr')
                            ->title('Type de produit')
                            ->value($this->product->translationInput('type')['fr'] ?? null),

                        TextArea::make('product.description_translations.fr')
                            ->title('Description')
                            ->value($this->product->plainText($this->product->translationInput('description')['fr'] ?? null))
                            ->rows(5),
                    ]),

                    'العربية' => Layout::rows([
                        Input::make('product.name_translations.ar')
                            ->title('اسم المنتج')
                            ->value($this->product->translationInput('name')['ar'] ?? null),

                        Input::make('product.type_translations.ar')
                            ->title('نوع المنتج')
                            ->value($this->product->translationInput('type')['ar'] ?? null),

                        TextArea::make('product.description_translations.ar')
                            ->title('الوصف')
                            ->value($this->product->plainText($this->product->translationInput('description')['ar'] ?? null))
                            ->rows(5),
                    ]),
                ]),
            ]),
        ];
    }

    /**
     * @param Product $product
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Product $product, Request $request)
    {
        $productData = $request->get('product', []);

        // Normalize inputs before validation
        if (isset($productData['category_id']) && $productData['category_id'] === '') {
            $productData['category_id'] = null;
        }
        if (isset($productData['category_name'])) {
            $productData['category_name'] = trim((string) $productData['category_name']);
        }

        $request->merge(['product' => $productData]);

        $request->validate([
            'product.company_id' => 'required|exists:companies,id',
            'product.name_translations.en' => 'required|max:255',
            'product.name_translations.fr' => 'nullable|max:255',
            'product.name_translations.ar' => 'nullable|max:255',
            'product.category_id' => 'nullable|exists:product_categories,id',
            'product.category_name' => 'nullable|string|max:255',
            'product.type_translations.*' => 'nullable|max:100',
            'product.description_translations.*' => 'nullable|string',
            'product.is_featured' => 'boolean',
        ]);

        $productData = $product->prepareTranslatedData($productData, ['name', 'type', 'description'], ['description']);

        // Create/find category if user entered it manually (overrides selected category)
        $categoryName = $productData['category_name'] ?? '';
        if ($categoryName !== '') {
            $category = ProductCategory::whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])->first();

            if (!$category) {
                $baseSlug = Str::slug($categoryName);
                $slug = $baseSlug !== '' ? $baseSlug : Str::random(8);
                $suffix = 2;

                while (ProductCategory::where('slug', $slug)->exists()) {
                    $slug = ($baseSlug !== '' ? $baseSlug : 'category') . '-' . $suffix;
                    $suffix++;
                }

                $category = ProductCategory::create([
                    'name' => $categoryName,
                    'slug' => $slug,
                ]);
            }

            $productData['category_id'] = $category->id;
        }

        unset($productData['category_name']);

        $product->fill($productData)->save();

        Toast::info(__('Product was saved.'));

        return redirect()->route('platform.products.list');
    }

    /**
     * @param Product $product
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function remove(Product $product)
    {
        $product->delete();

        Toast::info(__('Product was removed'));

        return redirect()->route('platform.products.list');
    }
}
