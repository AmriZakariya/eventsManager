<?php

namespace App\Orchid\Screens\Feature;

use App\Models\AwardCategory;
use App\Models\AwardNominee;
use App\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;
use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Support\Facades\Layout;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Actions\Button;

class AwardListScreen extends Screen
{
    public $name = 'HCE Awards Management';
    public $description = 'Manage award categories, nominees, and winners.';

    public function query(Request $request): array
    {
        $filters = $this->awardFilters($request);

        $categories = AwardCategory::with([
            'nominees' => fn ($query) => $query
                ->with('company')
                ->orderByDesc('is_winner')
                ->orderBy('product_name'),
        ])
            ->withCount('nominees')
            ->orderBy('name')
            ->get();

        $awardGroups = AwardCategory::query()
            ->when(is_numeric($filters['category_id']), fn ($query) => $query->whereKey($filters['category_id']))
            ->when($filters['category_id'] === 'uncategorized', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($this->hasNomineeFilters($filters), function ($query) use ($filters) {
                $query->whereHas('nominees', fn ($nomineeQuery) => $this->applyNomineeFilters($nomineeQuery, $filters));
            })
            ->with([
                'nominees' => fn ($query) => $this->applyNomineeFilters($query->with('company'), $filters)
                    ->orderByDesc('is_winner')
                    ->orderBy('product_name'),
            ])
            ->orderBy('name')
            ->get();

        $awardGroups = $this->paginateAwardGroups($awardGroups, $request);

        $uncategorized = AwardNominee::query()
            ->with('company')
            ->whereNull('award_category_id')
            ->when(is_numeric($filters['category_id']), fn ($query) => $query->whereRaw('1 = 0'))
            ->tap(fn ($query) => $this->applyNomineeFilters($query, $filters))
            ->orderByDesc('is_winner')
            ->orderBy('product_name')
            ->get();

        return [
            'categories' => $categories,
            'awardGroups' => $awardGroups,
            'uncategorizedNominees' => $uncategorized,
            'awardFilters' => $filters,
            'awardCategoryOptions' => $this->awardCategoryOptions(),
        ];
    }

    public function commandBar(): array
    {
        return [
            ModalToggle::make('Add Category')
                ->modal('categoryModal')
                ->method('saveCategory')
                ->icon('bs.plus-circle'),

            ModalToggle::make('Add Nominee')
                ->modal('nomineeModal')
                ->method('saveNominee')
                ->icon('bs.trophy'),
        ];
    }

    public function layout(): array
    {
        $awardData = $this->query(request());

        return [
            Layout::view('orchid.awards.categories-management', [
                'managedCategories' => $awardData['categories'],
            ]),

            Layout::rows([
                Group::make([
                    Input::make('search')
                        ->title('Search')
                        ->placeholder('Nominee or company...')
                        ->value($awardData['awardFilters']['search']),

                    Select::make('award_category_id')
                        ->title('Category')
                        ->options($awardData['awardCategoryOptions'])
                        ->empty('All Categories', '')
                        ->value($awardData['awardFilters']['category_id']),

                    Select::make('winner_status')
                        ->title('Status')
                        ->options([
                            'winner' => 'Winners',
                            'nominee' => 'Nominees',
                        ])
                        ->empty('All Statuses', '')
                        ->value($awardData['awardFilters']['winner_status']),
                ]),

                Group::make([
                    Button::make('Apply')
                        ->icon('bs.funnel')
                        ->method('applyAwardFilters')
                        ->class('btn btn-primary'),

                    Button::make('Reset')
                        ->icon('bs.x-circle')
                        ->method('clearAwardFilters')
                        ->class('btn btn-outline-secondary'),
                ])->autoWidth(),
            ])->title('Search & Filter Nominees'),

            Layout::view('orchid.awards.category-groups', [
                'awardGroups' => $awardData['awardGroups'],
                'uncategorizedNominees' => $awardData['uncategorizedNominees'],
                'filters' => $awardData['awardFilters'],
            ]),

            // Modals
            Layout::modal('categoryModal', Layout::rows([
                Input::make('category.id')->type('hidden'),
                Input::make('category.name')->title('Category Name')->required(),
                Input::make('category.description')->title('Description'),
            ]))->async('asyncGetCategory')->title('Award Category'),

            Layout::modal('nomineeModal', Layout::rows([
                Input::make('nominee.id')->type('hidden'),

                Relation::make('nominee.award_category_id')
                    ->fromModel(AwardCategory::class, 'name')
                    ->title('Category')
                    ->empty('Uncategorized'),

                Relation::make('nominee.company_id')
                    ->fromModel(Company::class, 'name')
                    ->title('Company'),

                Input::make('nominee.product_name')->title('Product Name'),
                Input::make('nominee.image')->title('Image URL'),

                CheckBox::make('nominee.is_winner')
                    ->title('Is this the Winner?')
                    ->sendTrueOrFalse(),
            ]))->async('asyncGetNominee')->title('Award Nominee')
        ];
    }

    public function asyncGetCategory(AwardCategory $category): array
    {
        return ['category' => $category];
    }

    public function asyncGetNominee(AwardNominee $nominee): array
    {
        return ['nominee' => $nominee];
    }

    public function applyAwardFilters(Request $request)
    {
        return redirect()->route('platform.awards.list', array_filter([
            'search' => $request->get('search'),
            'award_category_id' => $request->get('award_category_id'),
            'winner_status' => $request->get('winner_status'),
        ], fn ($value) => filled($value)));
    }

    public function clearAwardFilters()
    {
        return redirect()->route('platform.awards.list');
    }

    public function saveCategory(Request $request): void
    {
        $data = $request->validate([
            'category.id' => ['nullable', 'integer', 'exists:award_categories,id'],
            'category.name' => ['required', 'string', 'max:255'],
            'category.description' => ['nullable', 'string', 'max:1000'],
        ])['category'];

        $category = isset($data['id'])
            ? AwardCategory::findOrFail($data['id'])
            : new AwardCategory();

        $category->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ])->save();

        Toast::info('Category saved.');
    }

    public function deleteCategory(Request $request): void
    {
        $category = AwardCategory::findOrFail($request->get('id'));
        $category->nominees()->update([
            'award_category_id' => null,
            'is_winner' => false,
        ]);

        $category->delete();

        Toast::info('Category deleted. Its nominees are now uncategorized.');
    }

    public function saveNominee(Request $request): void
    {
        $data = $request->validate([
            'nominee.id' => ['nullable', 'integer', 'exists:award_nominees,id'],
            'nominee.award_category_id' => ['nullable', 'integer', 'exists:award_categories,id'],
            'nominee.company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'nominee.product_name' => ['required', 'string', 'max:255'],
            'nominee.image' => ['nullable', 'string', 'max:2048'],
            'nominee.is_winner' => ['nullable', 'boolean'],
        ])['nominee'];

        $data['is_winner'] = (bool) ($data['is_winner'] ?? false);

        $data['award_category_id'] = $data['award_category_id'] ?? null;

        if ($data['is_winner'] && $data['award_category_id']) {
            AwardNominee::query()
                ->where('award_category_id', $data['award_category_id'])
                ->when($data['id'] ?? null, fn ($query, $id) => $query->where('id', '!=', $id))
                ->update(['is_winner' => false]);
        }

        $nominee = isset($data['id'])
            ? AwardNominee::findOrFail($data['id'])
            : new AwardNominee();

        $nominee->fill($data)->save();

        Toast::info('Nominee saved.');
    }

    public function deleteNominee(Request $request): void
    {
        AwardNominee::findOrFail($request->get('id'))->delete();

        Toast::info('Nominee deleted.');
    }

    private function awardFilters(Request $request): array
    {
        return [
            'search' => trim((string) $request->get('search', '')),
            'category_id' => $request->get('award_category_id', ''),
            'winner_status' => $request->get('winner_status', ''),
        ];
    }

    private function hasNomineeFilters(array $filters): bool
    {
        return $filters['search'] !== '' || in_array($filters['winner_status'], ['winner', 'nominee'], true);
    }

    private function applyNomineeFilters($query, array $filters)
    {
        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($query) use ($search) {
                $query->where('product_name', 'like', "%{$search}%")
                    ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($filters['winner_status'] === 'winner') {
            $query->where('is_winner', true);
        }

        if ($filters['winner_status'] === 'nominee') {
            $query->where('is_winner', false);
        }

        return $query;
    }

    private function paginateAwardGroups($categories, Request $request): LengthAwarePaginator
    {
        $perPage = 4;
        $pageName = 'award_page';
        $page = max(1, (int) $request->get($pageName, 1));

        return (new LengthAwarePaginator(
            $categories->forPage($page, $perPage)->values(),
            $categories->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ]
        ))->appends($request->except($pageName));
    }

    private function awardCategoryOptions(): array
    {
        return ['uncategorized' => 'Uncategorized'] + AwardCategory::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
