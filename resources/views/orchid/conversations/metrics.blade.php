@if($conversations->total() > 0)
    <div class="pagination-info-wrapper">
        <div class="pagination-summary">
            <div class="summary-text">
                <i class="icon-layers"></i>
                Showing <strong>{{ $conversations->firstItem() }}</strong> to
                <strong>{{ $conversations->lastItem() }}</strong> of
                <strong>{{ number_format($conversations->total()) }}</strong> conversations
            </div>

            <div class="pagination-actions">
                <div class="per-page-selector">
                    <label for="perPage">Show:</label>
                    <select id="perPage" class="form-control form-control-sm" onchange="updatePerPage(this.value)">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ request('per_page') == 15 || !request('per_page') ? 'selected' : '' }}>15</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>

                <div class="sort-selector">
                    <label for="sortBy">Sort by:</label>
                    <select id="sortBy" class="form-control form-control-sm" onchange="updateSort(this.value)">
                        <option value="last_message_at-desc" {{ request('sort') == 'last_message_at' && request('direction') == 'desc' ? 'selected' : '' }}>
                            Recent Activity
                        </option>
                        <option value="last_message_at-asc" {{ request('sort') == 'last_message_at' && request('direction') == 'asc' ? 'selected' : '' }}>
                            Oldest Activity
                        </option>
                        <option value="total_messages-desc" {{ request('sort') == 'total_messages' && request('direction') == 'desc' ? 'selected' : '' }}>
                            Most Messages
                        </option>
                        <option value="total_messages-asc" {{ request('sort') == 'total_messages' && request('direction') == 'asc' ? 'selected' : '' }}>
                            Fewest Messages
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <style>
        .pagination-info-wrapper {
            margin-top: 24px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }

        .pagination-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .summary-text {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #495057;
        }

        .summary-text i {
            color: #667eea;
            font-size: 16px;
        }

        .summary-text strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .pagination-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .per-page-selector,
        .sort-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .per-page-selector label,
        .sort-selector label {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
            white-space: nowrap;
        }

        .per-page-selector select,
        .sort-selector select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 6px 12px;
            font-size: 0.85rem;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 140px;
        }

        .per-page-selector select:focus,
        .sort-selector select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        @media (max-width: 768px) {
            .pagination-summary {
                flex-direction: column;
                align-items: flex-start;
            }

            .pagination-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .per-page-selector,
            .sort-selector {
                width: 100%;
            }

            .per-page-selector select,
            .sort-selector select {
                flex: 1;
            }
        }
    </style>

    <script>
        function updatePerPage(value) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', value);
            url.searchParams.delete('page'); // Reset to first page
            window.location.href = url.toString();
        }

        function updateSort(value) {
            const [sortField, direction] = value.split('-');
            const url = new URL(window.location);
            url.searchParams.set('sort', sortField);
            url.searchParams.set('direction', direction);
            url.searchParams.delete('page'); // Reset to first page
            window.location.href = url.toString();
        }
    </script>
@else
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="icon-bubbles"></i>
        </div>
        <h3>No Conversations Found</h3>
        <p>There are no conversations matching your current filters.</p>
        <button class="btn btn-primary" onclick="clearFilters()">
            <i class="icon-refresh"></i> Clear Filters
        </button>
    </div>

    <style>
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.3;
        }

        .empty-state-icon i {
            font-size: 40px;
            color: white;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 12px;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: #7f8c8d;
            margin-bottom: 24px;
            font-size: 1rem;
        }
    </style>

    <script>
        function clearFilters() {
            const url = new URL(window.location);
            // Keep only the base path
            window.location.href = url.pathname;
        }
    </script>
@endif
