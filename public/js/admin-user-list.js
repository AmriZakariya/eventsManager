(function () {
    // Admin user list: filter bar (resources/views/admin/users/filter-bar.blade.php)
    // and selection + floating bulk bar (admin/users/bulk-bar.blade.php).
    // Everything is delegated on document so it survives Turbo page changes.

    var SEARCH_FOCUS_KEY = 'ul-focus-search';
    var searchTimer = null;

    function rowBoxes() {
        return Array.prototype.slice.call(document.querySelectorAll('input[name="users[]"]'));
    }

    // ── Filters ──────────────────────────────────────────────────────────

    function navigateWith(key, value) {
        var url = new URL(window.location.href);
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        url.searchParams.delete('page');

        if (window.Turbo && typeof window.Turbo.visit === 'function') {
            window.Turbo.visit(url.toString());
        } else {
            window.location.href = url.toString();
        }
    }

    function restoreSearchFocus() {
        var input = document.querySelector('[data-user-filters] input[data-filter="search"]');
        var wanted = false;
        try {
            wanted = sessionStorage.getItem(SEARCH_FOCUS_KEY) === '1';
            sessionStorage.removeItem(SEARCH_FOCUS_KEY);
        } catch (e) { /* storage unavailable */ }

        if (input && wanted) {
            input.focus();
            var end = input.value.length;
            input.setSelectionRange(end, end);
        }
    }

    document.addEventListener('input', function (event) {
        var input = event.target;
        if (!input.matches || !input.matches('[data-user-filters] input[data-filter="search"]')) return;

        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            try { sessionStorage.setItem(SEARCH_FOCUS_KEY, '1'); } catch (e) { /* ignore */ }
            navigateWith('search', input.value.trim());
        }, 450);
    });

    document.addEventListener('keydown', function (event) {
        var input = event.target;
        if (event.key !== 'Enter' || !input.matches || !input.matches('[data-user-filters] input[data-filter="search"]')) return;

        // The bar sits inside Orchid's main form: never let Enter submit it.
        event.preventDefault();
        clearTimeout(searchTimer);
        navigateWith('search', input.value.trim());
    }, true);

    document.addEventListener('change', function (event) {
        var select = event.target;
        if (select.matches && select.matches('[data-user-filters] select[data-filter]')) {
            navigateWith(select.getAttribute('data-filter'), select.value);
        }
    });

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest && event.target.closest('[data-filters-toggle]');
        if (!toggle) return;

        var panel = document.querySelector('[data-filters-panel]');
        if (!panel) return;

        var open = panel.hasAttribute('hidden');
        panel.toggleAttribute('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // ── Selection & bulk bar ─────────────────────────────────────────────

    function injectSelectAll() {
        var first = rowBoxes()[0];
        if (!first) return;

        var cell = first.closest('td');
        var table = first.closest('table');
        if (!cell || !table || table.querySelector('[data-select-all]')) return;

        var headerCell = table.querySelectorAll('thead th')[cell.cellIndex];
        if (!headerCell) return;

        var box = document.createElement('input');
        box.type = 'checkbox';
        box.className = 'form-check-input';
        box.setAttribute('data-select-all', '');
        box.title = 'Select all on this page';
        headerCell.innerHTML = '';
        headerCell.appendChild(box);
    }

    function decorateBulkButtons() {
        // Phones show icons only; keep the label as a tooltip.
        document.querySelectorAll('.ul-bulk-btn').forEach(function (button) {
            if (!button.title) button.title = button.textContent.trim();
        });
    }

    function refresh() {
        var boxes = rowBoxes();
        var checked = boxes.filter(function (box) { return box.checked; });
        var count = checked.length;
        var ids = checked.map(function (box) { return box.value; });

        boxes.forEach(function (box) {
            var row = box.closest('tr');
            if (row) row.classList.toggle('ul-selected', box.checked);
        });

        var all = document.querySelector('[data-select-all]');
        if (all) {
            all.checked = count > 0 && count === boxes.length;
            all.indeterminate = count > 0 && count < boxes.length;
        }

        var bar = document.querySelector('[data-bulk-bar]');
        if (bar) {
            bar.toggleAttribute('hidden', count === 0);
            var label = bar.querySelector('[data-bulk-count]');
            if (label) label.textContent = String(count);
        }
        document.body.classList.toggle('ul-has-selection', count > 0);

        document.querySelectorAll('[data-confirm-template]').forEach(function (wrapper) {
            var button = wrapper.querySelector('[data-button-confirm]');
            if (button) {
                button.setAttribute(
                    'data-button-confirm',
                    wrapper.getAttribute('data-confirm-template').replace('{count}', String(count))
                );
            }
        });

        // Modals have their own form: pass the selection through a hidden field.
        document.querySelectorAll('[data-bulk-selected]').forEach(function (input) {
            input.value = ids.join(',');
        });
    }

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (!target.matches) return;

        if (target.matches('[data-select-all]')) {
            rowBoxes().forEach(function (box) { box.checked = target.checked; });
            refresh();
        } else if (target.matches('input[name="users[]"]')) {
            refresh();
        }
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest && event.target.closest('[data-bulk-clear]')) {
            rowBoxes().forEach(function (box) { box.checked = false; });
            refresh();
            return;
        }

        // Clicking anywhere in the checkbox cell toggles the row.
        var cell = event.target.closest && event.target.closest('td');
        if (cell && event.target.tagName !== 'INPUT') {
            var box = cell.querySelector('input[name="users[]"]');
            if (box) {
                box.checked = !box.checked;
                refresh();
            }
        }
    });

    // Re-sync the hidden ids right before a bulk modal opens.
    document.addEventListener('show.bs.modal', refresh);

    function init() {
        if (!document.querySelector('[data-user-filters], input[name="users[]"]')) {
            document.body.classList.remove('ul-has-selection');
            return;
        }
        injectSelectAll();
        decorateBulkButtons();
        refresh();
        restoreSearchFocus();
    }

    document.addEventListener('turbo:load', init);
    document.addEventListener('turbo:render', init);
    if (document.readyState !== 'loading') init();
})();
