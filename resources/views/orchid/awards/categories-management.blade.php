<div class="card mb-3">
    <style>
        .award-section-toggle {
            text-align: left;
            text-decoration: none;
        }

        .award-section-toggle:hover {
            text-decoration: none;
        }

        .award-categories-scroll {
            max-height: 320px;
            overflow-y: auto;
        }
    </style>

    <div class="card-header bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <button
                class="btn btn-link p-0 flex-grow-1 award-section-toggle"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#award_categories_management"
                aria-expanded="true"
                aria-controls="award_categories_management"
            >
                <h2 class="h5 mb-1 text-body">
                    <i class="bi bi-chevron-down me-1"></i>Award Categories
                </h2>
                <div class="text-muted small">Manage award category names, descriptions, and assignments.</div>
            </button>
            <span class="badge bg-light text-dark border">{{ $managedCategories->count() }} categories</span>
        </div>
    </div>

    <div id="award_categories_management" class="collapse show">
        <div class="table-responsive award-categories-scroll">
            <table class="table mb-0 align-middle">
                <thead class="position-sticky top-0 bg-white">
                <tr>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-center">Nominees</th>
                    <th>Winner</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($managedCategories as $category)
                    @php($winner = $category->nominees->firstWhere('is_winner', true))
                    <tr>
                        <td class="fw-semibold">{{ $category->name }}</td>
                        <td>{{ $category->description ?: '-' }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary">{{ (int) $category->nominees_count }}</span>
                        </td>
                        <td>
                            @if($winner)
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-trophy-fill me-1"></i>{{ $winner->product_name }}
                                </span>
                            @else
                                <span class="text-muted">Not selected</span>
                            @endif
                        </td>
                        <td class="text-end">
                            {!! \Orchid\Screen\Actions\ModalToggle::make('Edit')
                                ->modal('categoryModal')
                                ->method('saveCategory')
                                ->asyncParameters(['category' => $category->id])
                                ->icon('bs.pencil')
                                ->class('btn btn-sm btn-outline-primary') !!}

                            {!! \Orchid\Screen\Actions\Button::make('Delete')
                                ->icon('bs.trash')
                                ->confirm('Delete this award category? Its nominees will move to Uncategorized.')
                                ->method('deleteCategory', ['id' => $category->id])
                                ->class('btn btn-sm btn-outline-danger ms-1') !!}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No award categories yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
