<div class="mb-4">
    <style>
        .award-nominees-scroll {
            max-height: 360px;
            overflow-y: auto;
        }

        .award-category-toggle {
            text-align: left;
            text-decoration: none;
        }

        .award-category-toggle:hover {
            text-decoration: none;
        }

        .award-pagination {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
            padding: 10px 14px;
        }

        .award-pagination nav {
            margin: 0;
        }

        .award-pagination .pagination {
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
            margin: 0;
        }

        .award-pagination .page-link {
            align-items: center;
            border-radius: 999px !important;
            box-shadow: none !important;
            display: inline-flex;
            font-size: 14px;
            height: 36px;
            justify-content: center;
            min-width: 36px;
            padding: 0 12px;
        }

        .award-pagination .page-item.active .page-link {
            font-weight: 700;
        }

        .award-pagination svg {
            height: 14px !important;
            width: 14px !important;
        }

        @media (max-width: 576px) {
            .award-pagination .pagination {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 mb-1">Nominees by Category</h2>
            <p class="text-muted mb-0">Each category shows its selected winner first, followed by the remaining nominees.</p>
        </div>
    </div>

    @if($uncategorizedNominees->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header bg-white">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <button
                        class="btn btn-link p-0 flex-grow-1 award-category-toggle"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#award_uncategorized"
                        aria-expanded="true"
                        aria-controls="award_uncategorized"
                    >
                        <h3 class="h6 mb-1 text-body">
                            <i class="bi bi-chevron-down me-1"></i>Uncategorized
                        </h3>
                        <div class="text-muted small">Nominees waiting to be assigned to an award category.</div>
                    </button>
                    <span class="badge bg-light text-dark border">{{ $uncategorizedNominees->count() }} nominees</span>
                </div>
            </div>

            <div id="award_uncategorized" class="collapse show">
                <div class="table-responsive award-nominees-scroll">
                    <table class="table mb-0 align-middle">
                        <thead class="position-sticky top-0 bg-white">
                        <tr>
                            <th>Nominee</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($uncategorizedNominees as $nominee)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $nominee->product_name }}</div>
                                    @if($nominee->image)
                                        <a class="small" href="{{ $nominee->image }}" target="_blank" rel="noopener">Image</a>
                                    @endif
                                </td>
                                <td>{{ $nominee->company->name ?? 'N/A' }}</td>
                                <td><span class="text-muted">Uncategorized</span></td>
                                <td class="text-end">
                                    {!! \Orchid\Screen\Actions\ModalToggle::make('Assign')
                                        ->modal('nomineeModal')
                                        ->method('saveNominee')
                                        ->asyncParameters(['nominee' => $nominee->id])
                                        ->icon('bs.pencil')
                                        ->class('btn btn-sm btn-outline-primary') !!}

                                    {!! \Orchid\Screen\Actions\Button::make('Delete')
                                        ->icon('bs.trash')
                                        ->confirm('Delete this nominee?')
                                        ->method('deleteNominee', ['id' => $nominee->id])
                                        ->class('btn btn-sm btn-outline-danger ms-1') !!}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($awardGroups->hasPages())
        <div class="award-pagination mb-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="text-muted small">
                    Showing {{ $awardGroups->firstItem() }} to {{ $awardGroups->lastItem() }} of {{ $awardGroups->total() }} results
                </div>

                <div>
                    {{ $awardGroups->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif

    @foreach($awardGroups as $category)
        @php($collapseId = 'award_category_' . $category->id)
        <div class="card mb-3">
            <div class="card-header bg-white">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <button
                        class="btn btn-link p-0 flex-grow-1 award-category-toggle"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $collapseId }}"
                        aria-expanded="true"
                        aria-controls="{{ $collapseId }}"
                    >
                        <h3 class="h6 mb-1 text-body">
                            <i class="bi bi-chevron-down me-1"></i>{{ $category->name }}
                        </h3>
                        @if($category->description)
                            <div class="text-muted small">{{ $category->description }}</div>
                        @endif
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">{{ $category->nominees->count() }} nominees</span>
                        @php($winner = $category->nominees->firstWhere('is_winner', true))
                        @if($winner)
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-trophy-fill me-1"></i>{{ $winner->product_name }}
                            </span>
                        @else
                            <span class="badge bg-secondary">No winner</span>
                        @endif
                    </div>
                </div>
            </div>

            <div id="{{ $collapseId }}" class="collapse show">
                <div class="table-responsive award-nominees-scroll">
                    <table class="table mb-0 align-middle">
                        <thead class="position-sticky top-0 bg-white">
                        <tr>
                            <th>Nominee</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($category->nominees as $nominee)
                            <tr class="{{ $nominee->is_winner ? 'table-warning' : '' }}">
                                <td>
                                    <div class="fw-semibold">{{ $nominee->product_name }}</div>
                                    @if($nominee->image)
                                        <a class="small" href="{{ $nominee->image }}" target="_blank" rel="noopener">Image</a>
                                    @endif
                                </td>
                                <td>{{ $nominee->company->name ?? 'N/A' }}</td>
                                <td>
                                    @if($nominee->is_winner)
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-trophy-fill me-1"></i>Winner
                                        </span>
                                    @else
                                        <span class="text-muted">Nominee</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {!! \Orchid\Screen\Actions\ModalToggle::make('Edit')
                                        ->modal('nomineeModal')
                                        ->method('saveNominee')
                                        ->asyncParameters(['nominee' => $nominee->id])
                                        ->icon('bs.pencil')
                                        ->class('btn btn-sm btn-outline-primary') !!}

                                    {!! \Orchid\Screen\Actions\Button::make('Delete')
                                        ->icon('bs.trash')
                                        ->confirm('Delete this nominee?')
                                        ->method('deleteNominee', ['id' => $nominee->id])
                                        ->class('btn btn-sm btn-outline-danger ms-1') !!}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No nominees in this category yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    @if($awardGroups->hasPages())
        <div class="award-pagination mt-2">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div class="text-muted small">
                    Showing {{ $awardGroups->firstItem() }} to {{ $awardGroups->lastItem() }} of {{ $awardGroups->total() }} results
                </div>

                <div>
                    {{ $awardGroups->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    @endif

    @if($awardGroups->count() === 0 && $uncategorizedNominees->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                No nominees match the current filters.
            </div>
        </div>
    @endif
</div>
