/**
 * Admin responsive helper.
 *
 * Progressive enhancement for Orchid tables: copies each column header's text
 * onto its cells as `data-label` and tags the table with `.rt-stack`, so the
 * phone-only CSS in /css/admin-responsive.css can render every row as a
 * labeled card. The script itself changes nothing visually; on desktop (and if
 * this file fails to load) tables keep their normal horizontal-scroll layout.
 */
(function () {
    'use strict';

    var TABLE_SELECTOR = '[data-controller="table"] table.table';
    var ACTIONS_LABEL = /^(actions?|)$/i;

    function headerLabel(th) {
        var clone = th.cloneNode(true);
        var noise = clone.querySelectorAll(
            'svg, .dropdown, [data-controller="filter"], [data-controller="popover"], .popover, script, style'
        );
        for (var i = 0; i < noise.length; i++) {
            noise[i].parentNode.removeChild(noise[i]);
        }
        return (clone.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function isVisible(el) {
        return window.getComputedStyle(el).display !== 'none';
    }

    function enhanceTable(table) {
        if (table.getAttribute('data-rt-done')) {
            return;
        }
        table.setAttribute('data-rt-done', '1');

        var headers = table.querySelectorAll('thead th');
        if (!headers.length) {
            return; // header-less table: leave the default layout alone
        }

        var byColumn = {};
        var byIndex = [];
        for (var h = 0; h < headers.length; h++) {
            var text = headerLabel(headers[h]);
            byIndex.push(text);
            var key = headers[h].getAttribute('data-column');
            if (key) {
                byColumn[key] = text;
            }
        }

        var rows = table.querySelectorAll('tbody > tr');
        for (var r = 0; r < rows.length; r++) {
            var cells = [];
            for (var c = 0; c < rows[r].children.length; c++) {
                if (rows[r].children[c].tagName === 'TD') {
                    cells.push(rows[r].children[c]);
                }
            }

            var actionsCell = null;
            var mainCell = null;
            var lastVisible = null;

            for (var i = 0; i < cells.length; i++) {
                var td = cells[i];
                var col = td.getAttribute('data-column');
                var label = (col && Object.prototype.hasOwnProperty.call(byColumn, col))
                    ? byColumn[col]
                    : (byIndex[i] || '');

                var hasDropdown = !!td.querySelector('[data-bs-toggle="dropdown"]');
                var isLast = i === cells.length - 1;

                if (hasDropdown && isLast && ACTIONS_LABEL.test(label)) {
                    td.classList.add('rt-actions');
                    actionsCell = td;
                    continue; // never labeled
                }

                if (label) {
                    td.setAttribute('data-label', label);
                    if (!mainCell && isVisible(td)) {
                        mainCell = td;
                    }
                } else if (td.querySelector('input[type="checkbox"]')) {
                    td.classList.add('rt-check');
                }

                if (isVisible(td)) {
                    lastVisible = td;
                }
            }

            if (actionsCell && mainCell) {
                mainCell.classList.add('rt-main');
            }
            if (lastVisible) {
                lastVisible.classList.add('rt-last-visible');
            }
        }

        table.classList.add('rt-stack');
        var host = table.closest('.table-responsive');
        if (host) {
            host.classList.add('rt-stack-host');
        }
    }

    function enhanceAll() {
        var tables = document.querySelectorAll(TABLE_SELECTOR);
        for (var i = 0; i < tables.length; i++) {
            try {
                enhanceTable(tables[i]);
            } catch (e) {
                // Never let a presentation helper break the page.
                if (window.console && console.warn) {
                    console.warn('admin-responsive: table enhance failed', e);
                }
            }
        }
    }

    var timer = null;
    function schedule() {
        if (timer) {
            return;
        }
        timer = setTimeout(function () {
            timer = null;
            enhanceAll();
        }, 120);
    }

    document.addEventListener('DOMContentLoaded', enhanceAll);
    document.addEventListener('turbo:load', enhanceAll);
    document.addEventListener('turbo:render', enhanceAll);

    // Tables injected later (async modals, turbo streams).
    if (window.MutationObserver && document.documentElement) {
        new MutationObserver(schedule).observe(document.documentElement, {
            childList: true,
            subtree: true
        });
    }

    enhanceAll();
})();
