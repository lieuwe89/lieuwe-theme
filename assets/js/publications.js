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

(function () {
    'use strict';

    var PDF_CACHE  = new Map();   // url -> Promise<PDFDocumentProxy>
    var PAGE_CACHE = new Map();   // 'url|page|width' -> Promise<HTMLCanvasElement>
    var pdfjsReady = !!window.pdfjsLib;

    if (!pdfjsReady) {
        window.addEventListener('lieuwe-pdfjs-ready', function () { pdfjsReady = true; }, { once: true });
    }

    function whenPdfjsReady() {
        if (pdfjsReady && window.pdfjsLib) { return Promise.resolve(window.pdfjsLib); }
        return new Promise(function (resolve, reject) {
            var timeout = setTimeout(function () { reject(new Error('PDF.js failed to load')); }, 8000);
            window.addEventListener('lieuwe-pdfjs-ready', function () {
                clearTimeout(timeout);
                resolve(window.pdfjsLib);
            }, { once: true });
        });
    }

    function loadPdf(url) {
        if (!PDF_CACHE.has(url)) {
            PDF_CACHE.set(url, whenPdfjsReady().then(function (lib) {
                return lib.getDocument({ url: url }).promise;
            }));
        }
        return PDF_CACHE.get(url);
    }

    function renderPdfPage(url, pageNumber, targetWidth) {
        var w = Math.round(targetWidth / 40) * 40;
        var key = url + '|' + pageNumber + '|' + w;
        if (PAGE_CACHE.has(key)) { return PAGE_CACHE.get(key); }

        var promise = (function () {
            return loadPdf(url).then(function (pdf) {
                return pdf.getPage(pageNumber).then(function (page) {
                    var dpr   = Math.min(2, window.devicePixelRatio || 1);
                    var base  = page.getViewport({ scale: 1 });
                    var scale = (w * dpr) / base.width;
                    var vp    = page.getViewport({ scale: scale });
                    var canvas = document.createElement('canvas');
                    canvas.width  = Math.floor(vp.width);
                    canvas.height = Math.floor(vp.height);
                    canvas.style.width  = w + 'px';
                    canvas.style.height = (w * vp.height / vp.width) + 'px';
                    return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise
                        .then(function () { return canvas; });
                });
            });
        })();

        PAGE_CACHE.set(key, promise);
        promise.catch(function () { PAGE_CACHE.delete(key); });
        return promise;
    }

    /** Total number of spreads for an N-page PDF in the project's spread convention. */
    function spreadCount(pages) { return Math.ceil((pages + 1) / 2); }

    /**
     * Pages in spread (1-indexed), or `null` for the blank cover-left.
     * Spread 0 -> [null, 1]. Spread N>0 -> [2N, 2N+1].
     */
    function pagesInSpread(spreadIndex, totalPages) {
        if (spreadIndex === 0) { return [ null, 1 ]; }
        var left  = 2 * spreadIndex;
        var right = 2 * spreadIndex + 1;
        return [
            (left  <= totalPages) ? left  : null,
            (right <= totalPages) ? right : null,
        ];
    }

    /**
     * Mount the inline spread previewer inside a row's expand panel. Idempotent —
     * if already mounted, do nothing.
     */
    function mountPanel(panel, row) {
        if (panel.dataset.mounted === '1') { return; }
        panel.dataset.mounted = '1';

        var hasPdf = row.getAttribute('data-has-pdf') === 'true';
        var pdfUrl = row.getAttribute('data-pdf-url');
        if (!hasPdf || !pdfUrl) { return; }  // placeholder div is already rendered by PHP

        var mount = panel.querySelector('[data-spread-mount]');
        var prev  = panel.querySelector('.pub-panel__nav--prev');
        var next  = panel.querySelector('.pub-panel__nav--next');
        var capt  = panel.querySelector('[data-spread-caption]');
        if (!mount) { return; }

        var spreadIndex = 0;
        var totalSpreads = null;

        function paint() {
            renderSpread(mount, pdfUrl, spreadIndex)
                .then(function (result) {
                    totalSpreads = result.spreadCount;
                    if (capt) {
                        capt.hidden = false;
                        capt.textContent = 'Spread ' + (spreadIndex + 1) + ' / ' + totalSpreads + ' · click to enlarge';
                    }
                    if (prev) { prev.hidden = totalSpreads <= 1; prev.disabled = spreadIndex === 0; }
                    if (next) { next.hidden = totalSpreads <= 1; next.disabled = spreadIndex >= (totalSpreads - 1); }

                    // Auto-fill page count column if it was blank
                    if (row.getAttribute('data-pages') === '0' || row.getAttribute('data-pages') === '') {
                        row.setAttribute('data-pages', String(result.totalPages));
                        var rowPages   = row.querySelector('.pub-row__pages');
                        var panelPages = panel.querySelector('.pub-panel__meta dd [data-pages-fallback]');
                        if (rowPages   && rowPages.textContent.trim() === '—')   { rowPages.textContent   = result.totalPages + 'pp'; }
                        if (panelPages && panelPages.textContent.trim() === '—') { panelPages.textContent = result.totalPages + ' pages'; }
                    }
                })
                .catch(function (err) {
                    showLoadError(mount, err);
                });
        }

        if (prev) { prev.addEventListener('click', function (e) { e.stopPropagation(); if (spreadIndex > 0) { spreadIndex--; paint(); } }); }
        if (next) { next.addEventListener('click', function (e) { e.stopPropagation(); spreadIndex++; paint(); }); }

        mount.addEventListener('click', function (e) {
            if (e.target.closest('.pub-panel__nav')) { return; }
            if (window.LieuwePublicationsReader) {
                window.LieuwePublicationsReader.open(row.getAttribute('data-id'), spreadIndex);
            }
        });

        paint();
    }

    /**
     * Render a spread into `mount`. Returns { spreadCount, totalPages }.
     */
    function renderSpread(mount, pdfUrl, spreadIndex) {
        return loadPdf(pdfUrl).then(function (pdf) {
            var pages = pdf.numPages;
            var pair  = pagesInSpread(spreadIndex, pages);

            var box = document.createElement('div');
            box.className = 'pub-panel__spread-inner';
            box.style.display  = 'flex';
            box.style.gap      = '0';
            box.style.alignItems = 'center';

            return Promise.all(pair.map(function (pageNum) {
                if (pageNum === null) {
                    var blank = document.createElement('div');
                    blank.className = 'pub-panel__placeholder';
                    blank.style.width  = '220px';
                    blank.style.height = '316px';
                    return Promise.resolve(blank);
                }
                return renderPdfPage(pdfUrl, pageNum, 220).then(function (canvas) {
                    canvas.classList.add('pub-panel__page');
                    return canvas;
                });
            })).then(function (els) {
                els.forEach(function (el) { box.appendChild(el); });

                // Swap in
                var existing = mount.querySelector('.pub-panel__spread-inner, .pub-panel__placeholder');
                if (existing) { existing.replaceWith(box); } else { mount.prepend(box); }

                return { spreadCount: spreadCount(pages), totalPages: pages };
            });
        });
    }

    function showLoadError(mount, err) {
        var existing = mount.querySelector('.pub-panel__placeholder, .pub-panel__spread-inner');
        var msg = (err && /failed to load/i.test(err.message)) ? 'PDF preview unavailable — download the PDF to read.' : 'This PDF could not load.';
        var note = document.createElement('div');
        note.className = 'pub-panel__placeholder pub-panel__placeholder--error';
        note.style.width  = '220px';
        note.style.height = '316px';
        note.textContent  = msg;
        if (existing) { existing.replaceWith(note); } else { mount.prepend(note); }
        if (typeof console !== 'undefined') { console.warn('[publications]', err); }
    }

    window.LieuwePublicationsRender = {
        loadPdf:        loadPdf,
        renderPdfPage:  renderPdfPage,
        renderSpread:   renderSpread,
        mountPanel:     mountPanel,
        spreadCount:    spreadCount,
        pagesInSpread:  pagesInSpread,
    };
})();
