<?php

namespace App\Orchid\Screens\Product;

use App\Models\Product;
use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\CheckBox;
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
            Layout::columns([
                Layout::rows([
                    Input::make('product.name')
                        ->title('Product Name')
                        ->required()
                        ->placeholder('Enter product name')
                        ->help('The display name for this product.'),

                    Relation::make('product.company_id')
                        ->title('Company')
                        ->required()
                        ->fromModel(Company::class, 'name')
                        ->searchColumns('name', 'email')
                        ->help('Select the company that owns this product.'),

                    Relation::make('product.category_id')
                        ->title('Category')
                        ->fromModel(ProductCategory::class, 'name')
                        ->empty('No category')
                        ->help('Select a category for this product.'),

                    Quill::make('product.description')
                        ->title('Description')
                        ->placeholder('Enter detailed product description')
                        ->help('Provide a comprehensive description of the product.'),
                ]),

                Layout::rows([
                    Cropper::make('product.image')
                        ->title('Product Image')
                        ->targetRelativeUrl()
                        ->width(800)
                        ->height(600)
                        ->help('Recommended size: 800x600px.'),

                    Input::make('product.type')
                        ->title('Product Type')
                        ->placeholder('e.g., Chemicals, Machines')
                        ->help('Specify the type of product (optional).'),

                    CheckBox::make('product.is_featured')
                        ->title('Featured Product')
                        ->placeholder('Mark this product as featured')
                        ->help('Featured products will be highlighted in listings.')
                        ->sendTrueOrFalse(),
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
        $request->validate([
            'product.company_id' => 'required|exists:companies,id',
            'product.name'       => 'required|max:255',
            'product.category_id' => 'nullable|exists:product_categories,id',
            'product.type'       => 'nullable|max:100',
            'product.description' => 'nullable',
            'product.is_featured' => 'boolean',
        ]);

        $productData = $request->get('product');

        // Handle category_id - convert empty string to null if necessary
        if (isset($productData['category_id']) && $productData['category_id'] === '') {
            $productData['category_id'] = null;
        }

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
