(function () {
    'use strict';

    var state = {
        filter:     'All',
        query:      '',
        sortAsc:    false,
        expandedId: null,
    };

    var root, rows, yearHeads, chips, searchInput, sortBtn,
        statusCount, noresults, total;

    var SEARCH_DEBOUNCE = 150;

    function init() {
        root = document.getElementById('pub');
        if (!root) { return; }

        rows         = Array.prototype.slice.call(root.querySelectorAll('.pub-row'));
        yearHeads    = Array.prototype.slice.call(root.querySelectorAll('.pub-yearhead'));
        chips        = Array.prototype.slice.call(root.querySelectorAll('.pub-chip'));
        searchInput  = root.querySelector('.pub-search__input');
        sortBtn      = root.querySelector('.pub-sort');
        statusCount  = root.querySelector('.pub-status__count');
        noresults    = root.querySelector('.pub-noresults');
        total        = parseInt(root.getAttribute('data-total'), 10) || rows.length;

        initFilters();
        initSearch();
        initSort();
        initRows();
        initDeepLink();
        applyFilterState();
    }

    function setState(patch) {
        Object.assign(state, patch);
        applyFilterState();
    }

    function initFilters() {
        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (c) {
                    c.classList.toggle('is-active', c === chip);
                    c.setAttribute('aria-pressed', c === chip ? 'true' : 'false');
                });
                setState({ filter: chip.getAttribute('data-filter') });
            });
        });

        var clearBtn = root.querySelector('.pub-noresults__clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                chips.forEach(function (c) {
                    var isAll = c.getAttribute('data-filter') === 'All';
                    c.classList.toggle('is-active', isAll);
                    c.setAttribute('aria-pressed', isAll ? 'true' : 'false');
                });
                if (searchInput) { searchInput.value = ''; }
                setState({ filter: 'All', query: '' });
            });
        }
    }

    function initSearch() {
        if (!searchInput) { return; }
        var t;
        searchInput.addEventListener('input', function () {
            clearTimeout(t);
            t = setTimeout(function () {
                setState({ query: searchInput.value.toLowerCase().trim() });
            }, SEARCH_DEBOUNCE);
        });
    }

    function initSort() {
        if (!sortBtn) { return; }
        sortBtn.addEventListener('click', function () {
            var asc = !state.sortAsc;
            sortBtn.setAttribute('data-sort-asc', String(asc));
            var arrow = sortBtn.querySelector('.pub-sort__arrow');
            if (arrow) { arrow.textContent = asc ? '↑' : '↓'; }
            reorderDom(asc);
            setState({ sortAsc: asc });
        });
    }

    function reorderDom(asc) {
        var parent = root.querySelector('.pub-list .pub-container');
        if (!parent) { return; }

        var groups = {};
        yearHeads.forEach(function (head) {
            var year = head.getAttribute('data-year');
            groups[year] = { head: head, rows: [], panels: [] };
        });
        rows.forEach(function (row) {
            var year = row.getAttribute('data-year');
            if (!groups[year]) { return; }
            groups[year].rows.push(row);
            var panel = document.getElementById('pub-panel-' + row.getAttribute('data-id'));
            if (panel) { groups[year].panels.push(panel); }
        });

        var years = Object.keys(groups).map(Number);
        years.sort(function (a, b) { return asc ? a - b : b - a; });

        // Detach + reattach in correct order.
        years.forEach(function (year) {
            var g = groups[year];
            parent.appendChild(g.head);
            for (var i = 0; i < g.rows.length; i++) {
                parent.appendChild(g.rows[i]);
                if (g.panels[i]) { parent.appendChild(g.panels[i]); }
            }
        });
    }

    function applyFilterState() {
        var q = state.query;
        var f = state.filter;
        var visibleByYear = {};

        rows.forEach(function (row) {
            var matchType  = (f === 'All') || (row.getAttribute('data-type') === f);
            var matchQuery = (q === '')    || (row.getAttribute('data-search').indexOf(q) !== -1);
            var visible    = matchType && matchQuery;
            row.classList.toggle('is-hidden', !visible);

            var panel = document.getElementById('pub-panel-' + row.getAttribute('data-id'));
            if (panel && !visible) {
                panel.classList.add('is-hidden');
                panel.classList.remove('is-open');
                panel.setAttribute('hidden', '');
                row.classList.remove('is-expanded');
                row.querySelector('.pub-row__toggle').setAttribute('aria-expanded', 'false');
                if (state.expandedId === row.getAttribute('data-id')) { state.expandedId = null; }
            }

            if (visible) {
                var year = row.getAttribute('data-year');
                visibleByYear[year] = (visibleByYear[year] || 0) + 1;
            }
        });

        var visibleTotal = 0;
        yearHeads.forEach(function (head) {
            var year = head.getAttribute('data-year');
            var count = visibleByYear[year] || 0;
            head.classList.toggle('is-hidden', count === 0);
            if (count > 0) {
                visibleTotal += count;
                var label = (count === 1) ? '1 publication' : (count + ' publications');
                var labelEl = head.querySelector('.pub-yearhead__count');
                if (labelEl) { labelEl.textContent = label; }
            }
        });

        if (statusCount) {
            statusCount.textContent = visibleTotal + ' of ' + total + ' publications';
        }
        if (noresults) {
            noresults.hidden = (visibleTotal > 0);
        }
    }

    function initRows() {
        rows.forEach(function (row) {
            row.addEventListener('click', function (e) {
                if (e.target.closest('.pub-row__toggle')
                    || e.target === row
                    || e.target.closest('.pub-row__main')
                    || e.target.closest('.pub-row__venue')
                    || e.target.closest('.pub-row__type')) {
                    e.preventDefault();
                    toggleExpand(row.getAttribute('data-id'));
                }
            });
        });
    }

    function toggleExpand(id) {
        var opening = state.expandedId !== id;
        if (state.expandedId) { collapsePanel(state.expandedId); }
        state.expandedId = opening ? id : null;
        if (opening) { expandPanel(id); }
    }

    function collapsePanel(id) {
        var panel = document.getElementById('pub-panel-' + id);
        var row   = document.getElementById('pub-row-'   + id);
        if (panel) {
            panel.classList.remove('is-open');
            panel.setAttribute('hidden', '');
        }
        if (row) {
            row.classList.remove('is-expanded');
            row.querySelector('.pub-row__toggle').setAttribute('aria-expanded', 'false');
        }
    }

    function expandPanel(id) {
        var panel = document.getElementById('pub-panel-' + id);
        var row   = document.getElementById('pub-row-'   + id);
        if (!panel || !row) { return; }
        panel.removeAttribute('hidden');
        // Force a reflow so the max-height transition runs from 0.
        // eslint-disable-next-line no-unused-expressions
        panel.offsetHeight;
        panel.classList.add('is-open');
        row.classList.add('is-expanded');
        row.querySelector('.pub-row__toggle').setAttribute('aria-expanded', 'true');
        // PDF rendering (Task 15) is wired here.
        if (window.LieuwePublicationsRender) {
            window.LieuwePublicationsRender.mountPanel(panel, row);
        }
    }

    function initDeepLink() {
        var hash = window.location.hash;
        if (!hash || hash.indexOf('#pub-') !== 0) { return; }
        var id = hash.slice('#pub-'.length);
        var row = document.getElementById('pub-row-' + id);
        if (!row) { return; }
        // Wait one frame so layout (incl. sticky header) is settled.
        requestAnimationFrame(function () {
            row.scrollIntoView({ behavior: 'smooth', block: 'start' });
            toggleExpand(id);
        });
    }

    window.LieuwePublications = {
        state:        state,
        toggleExpand: toggleExpand,
        collapsePanel:collapsePanel,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
