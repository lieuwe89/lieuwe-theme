(function () {
    'use strict';

    var activeModal = null;
    var keyHandler  = null;

    function readDataset(el) {
        return {
            id:           el.getAttribute('data-id'),
            title:        el.getAttribute('data-title'),
            subtitle:     el.getAttribute('data-subtitle'),
            type:         el.getAttribute('data-type'),
            year:         el.getAttribute('data-year'),
            author:       el.getAttribute('data-author'),
            venue:        el.getAttribute('data-venue'),
            pages:        parseInt(el.getAttribute('data-pages'), 10) || 0,
            abstract:     el.getAttribute('data-abstract'),
            pdfUrl:       el.getAttribute('data-pdf-url'),
            allowDownload:el.getAttribute('data-allow-download') === 'true',
            permalink:    el.getAttribute('data-permalink'),
            paperColor:   el.getAttribute('data-paper-color') || '#f5ecd9',
            accentColor:  el.getAttribute('data-accent-color') || '#3a2a1f',
            coverSide:    el.getAttribute('data-cover-side') === 'left' ? 'left' : 'right',
        };
    }

    function open(pubId, spreadIndex) {
        if (typeof spreadIndex !== 'number') { spreadIndex = 0; }
        if (activeModal) { close(true); }

        var source = document.getElementById('pub-row-' + pubId)
                   || document.querySelector('.pub-single[data-id="' + pubId + '"]');
        if (!source) { return; }
        var pub = readDataset(source);
        if (!pub.pdfUrl) { return; }

        activeModal = buildModal(pub, spreadIndex);
        document.body.appendChild(activeModal.root);
        document.body.style.overflow = 'hidden';
        attachKeyHandlers();
        requestAnimationFrame(function () { activeModal.root.classList.add('is-open'); });
    }

    function close(immediate) {
        if (!activeModal) { return; }
        detachKeyHandlers();
        var modal = activeModal;
        activeModal = null;
        modal.root.classList.remove('is-open');
        var cleanup = function () {
            modal.root.remove();
            document.body.style.overflow = '';
        };
        if (immediate) { cleanup(); }
        else { modal.root.addEventListener('transitionend', cleanup, { once: true }); }
    }

    function buildModal(pub, initialSpread) {
        var spreadIndex = initialSpread;
        var totalSpreads = 1;
        var totalPages   = pub.pages || 0;
        var dim = false;

        var root = el('div', 'pub-modal', { role: 'dialog', 'aria-modal': 'true', 'aria-label': pub.title });
        var backdrop = el('div', 'pub-modal__backdrop');
        backdrop.addEventListener('click', function () { close(); });

        var grid = el('div', 'pub-modal__grid');

        // ----- reader pane -----
        var pane = el('div', 'pub-modal__pane');
        var topbar = el('div', 'pub-modal__topbar');
        topbar.appendChild(textBlock(pub.type + ' · ' + pub.year, 'pub-modal__eyebrow'));
        topbar.appendChild(textBlock(pub.title,                    'pub-modal__title'));

        var topRight = el('div', 'pub-modal__topright');
        var dimBtn = el('button', 'pub-modal__chip', { type: 'button' });
        dimBtn.textContent = 'Dim';
        dimBtn.addEventListener('click', function () {
            dim = !dim;
            backdrop.classList.toggle('is-dim', dim);
            dimBtn.textContent = dim ? 'Lights up' : 'Dim';
        });
        var closeBtn = el('button', 'pub-modal__close', { type: 'button', 'aria-label': 'Close' });
        closeBtn.textContent = '×';
        closeBtn.addEventListener('click', function () { close(); });
        topRight.appendChild(dimBtn);
        topRight.appendChild(closeBtn);
        topbar.appendChild(topRight);

        pane.appendChild(topbar);

        var stageWrap = el('div', 'pub-modal__stage');
        var stage = el('div', 'pub-modal__spread');
        stage.style.touchAction = 'pinch-zoom';
        stageWrap.appendChild(stage);

        var prevBtn = el('button', 'pub-modal__arrow pub-modal__arrow--prev', { type: 'button', 'aria-label': 'Previous spread' });
        prevBtn.textContent = '‹';
        var nextBtn = el('button', 'pub-modal__arrow pub-modal__arrow--next', { type: 'button', 'aria-label': 'Next spread' });
        nextBtn.textContent = '›';
        stageWrap.appendChild(prevBtn);
        stageWrap.appendChild(nextBtn);
        pane.appendChild(stageWrap);

        var stripLabel = el('div', 'pub-modal__striplabel');
        var stripLabelLeft  = el('span', null);
        var stripLabelRight = el('span', null);
        stripLabel.appendChild(stripLabelLeft);
        stripLabel.appendChild(stripLabelRight);
        pane.appendChild(stripLabel);

        var strip = el('div', 'pub-modal__strip');
        pane.appendChild(strip);

        // ----- sidebar -----
        var sidebar = el('aside', 'pub-modal__sidebar');
        sidebar.appendChild(textBlock('ABOUT THIS PUBLICATION', 'pub-modal__sidebar-eyebrow'));
        sidebar.appendChild(textBlock(pub.title,    'pub-modal__sidebar-title'));
        if (pub.subtitle) {
            sidebar.appendChild(textBlock(pub.subtitle, 'pub-modal__sidebar-subtitle'));
        }
        sidebar.appendChild(el('div', 'pub-modal__sidebar-rule'));
        if (pub.abstract) {
            sidebar.appendChild(textBlock(pub.abstract, 'pub-modal__sidebar-abstract'));
        }
        sidebar.appendChild(metaGrid(pub));
        var sidebarActions = el('div', 'pub-modal__sidebar-actions');
        if (pub.allowDownload) {
            var dl = el('a', 'pub-btn pub-btn--primary', { href: pub.pdfUrl, download: '' });
            dl.textContent = 'Download PDF';
            sidebarActions.appendChild(dl);
        }
        var citeBtn = el('button', 'pub-btn', { type: 'button' });
        citeBtn.textContent = 'Cite';
        sidebarActions.appendChild(citeBtn);
        sidebar.appendChild(sidebarActions);

        grid.appendChild(pane);
        grid.appendChild(sidebar);
        root.appendChild(backdrop);
        root.appendChild(grid);

        // ----- mobile: bottom-sheet sidebar toggle + start-collapsed -----
        var isPhone = function () { return window.matchMedia('(max-width: 639px)').matches; };
        if (isPhone()) {
            sidebar.classList.remove('is-open');
            sidebar.addEventListener('click', function (e) {
                // tap the drag handle area (top 28px) or eyebrow to toggle
                var r = sidebar.getBoundingClientRect();
                if (e.clientY - r.top < 32) {
                    sidebar.classList.toggle('is-open');
                }
            });
        } else {
            sidebar.classList.add('is-open');
        }

        // ----- spread rendering -----
        var renderer = window.LieuwePublicationsRender;
        function paint() {
            // Compute target page width to fit the stage.
            var stageW = stageWrap.clientWidth  || window.innerWidth - (isPhone() ? 0 : 360);
            var stageH = stageWrap.clientHeight || window.innerHeight - 220;
            var phone  = isPhone();
            var pageW  = phone
                ? Math.min(stageW * 0.92, stageH * 0.72)
                : Math.min(stageW * 0.45, stageH * 0.72, 460);

            if (!renderer) { return; }
            renderer.loadPdf(pub.pdfUrl).then(function (pdf) {
                totalPages   = pdf.numPages;
                totalSpreads = renderer.spreadCount(totalPages, pub.coverSide);
                if (spreadIndex < 0) { spreadIndex = 0; }
                if (spreadIndex >= totalSpreads) { spreadIndex = totalSpreads - 1; }
                var pair;
                if (phone) {
                    // Phone: map "spread" 1:1 to PDF pages, cover stays at index 0.
                    var pageNum = spreadIndex + 1;
                    pair = [ (pageNum <= totalPages) ? pageNum : null ];
                    totalSpreads = totalPages;
                } else {
                    pair = renderer.pagesInSpread(spreadIndex, totalPages, pub.coverSide);
                }

                var newInner = el('div', 'pub-modal__spread-inner');
                Promise.all(pair.map(function (n) {
                    if (n === null) {
                        var blank = el('div', 'pub-modal__page-blank');
                        blank.style.width  = pageW + 'px';
                        blank.style.height = (pageW / 0.72) + 'px';
                        blank.style.background = pub.paperColor;
                        return Promise.resolve(blank);
                    }
                    return renderer.renderPdfPage(pub.pdfUrl, n, pageW);
                })).then(function (els) {
                    els.forEach(function (e) { newInner.appendChild(e); });
                    stage.replaceChildren(newInner);

                    // labels
                    if (spreadIndex === 0 && pub.coverSide === 'right') {
                        stripLabelLeft.textContent = 'Cover';
                    } else if (phone) {
                        stripLabelLeft.textContent = 'Page ' + (pair[0] || spreadIndex + 1);
                    } else {
                        var lo = pair[0] || pair[1];
                        var hi = pair[1] || pair[0];
                        stripLabelLeft.textContent = 'Pages ' + lo + (hi && hi !== lo ? ' – ' + hi : '');
                    }
                    stripLabelRight.textContent = 'Spread ' + (spreadIndex + 1) + ' / ' + totalSpreads;

                    prevBtn.disabled = (spreadIndex === 0);
                    nextBtn.disabled = (spreadIndex >= totalSpreads - 1);

                    buildFilmstrip();
                });
            });
        }

        function buildFilmstrip() {
            strip.replaceChildren();
            for (var i = 0; i < totalSpreads; i++) {
                (function (i) {
                    var btn = el('button', 'pub-modal__strip-item', { type: 'button', 'aria-label': 'Go to spread ' + (i + 1) });
                    if (i === spreadIndex) { btn.classList.add('is-current'); }
                    btn.style.background = pub.paperColor;
                    btn.addEventListener('click', function () { spreadIndex = i; paint(); });
                    strip.appendChild(btn);
                })(i);
            }
        }

        prevBtn.addEventListener('click', function () { if (spreadIndex > 0) { spreadIndex--; paint(); } });
        nextBtn.addEventListener('click', function () { if (spreadIndex < totalSpreads - 1) { spreadIndex++; paint(); } });

        // ----- cite popover -----
        var citeOpen = false;
        var citePop  = null;
        citeBtn.addEventListener('click', function () {
            if (citeOpen) { citePop.remove(); citePop = null; citeOpen = false; return; }
            citePop = buildCitePopover(pub);
            sidebarActions.appendChild(citePop);
            citeOpen = true;
        });

        // initial paint after one frame so dims are real
        requestAnimationFrame(paint);

        return {
            root:     root,
            stage:    stage,
            setSpread:function (i) { spreadIndex = i; paint(); },
            getSpread:function () { return spreadIndex; },
            getTotal: function () { return totalSpreads; },
        };
    }

    /** Build a cite popover. Returns the element to append. */
    function buildCitePopover(pub) {
        var pop = el('div', 'pub-cite');
        var tabs = el('div', 'pub-cite__tabs');
        var pre  = el('pre', 'pub-cite__output');
        var copyBtn = el('button', 'pub-cite__copy', { type: 'button' });
        copyBtn.textContent = 'Copy to clipboard';
        var format = 'apa';

        function render() {
            pre.textContent = generateCitation(pub, format);
            Array.prototype.forEach.call(tabs.children, function (t) {
                t.classList.toggle('is-active', t.dataset.format === format);
            });
        }

        [ 'apa', 'mla', 'bibtex' ].forEach(function (f) {
            var t = el('button', 'pub-cite__tab', { type: 'button' });
            t.dataset.format = f;
            t.textContent = f.toUpperCase();
            t.addEventListener('click', function () { format = f; render(); });
            tabs.appendChild(t);
        });

        copyBtn.addEventListener('click', function () {
            var text = pre.textContent;
            var done = function () {
                copyBtn.textContent = 'Copied ✓';
                copyBtn.classList.add('is-copied');
                setTimeout(function () {
                    copyBtn.textContent = 'Copy to clipboard';
                    copyBtn.classList.remove('is-copied');
                }, 1800);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text, done); });
            } else {
                fallbackCopy(text, done);
            }
        });

        pop.appendChild(tabs);
        pop.appendChild(pre);
        pop.appendChild(copyBtn);
        render();
        return pop;
    }

    function fallbackCopy(text, done) {
        var area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity  = '0';
        document.body.appendChild(area);
        area.select();
        try { document.execCommand('copy'); done(); }
        catch (e) { done(); }
        finally { area.remove(); }
    }

    function parseAuthors(raw) {
        return (raw || '').split(',')
            .map(function (s) { return s.trim(); })
            .filter(Boolean)
            .map(function (name) {
                var parts = name.split(/\s+/);
                if (parts.length === 1) { return { first: '', last: parts[0] }; }
                return { last: parts[parts.length - 1], first: parts.slice(0, -1).join(' ') };
            });
    }

    function citeAPA(pub) {
        var authors = parseAuthors(pub.author);
        var apaAuthor = authors.map(function (a) {
            var initials = a.first ? a.first.split(/\s+/).map(function (p) { return p[0] + '.'; }).join(' ') : '';
            return a.last + (initials ? ', ' + initials : '');
        }).join(', ');
        var titlePart = pub.title + (pub.subtitle ? ': ' + pub.subtitle : '');
        return apaAuthor + ' (' + pub.year + '). ' + titlePart + '. ' + pub.venue + '.';
    }

    function citeMLA(pub) {
        var authors = parseAuthors(pub.author);
        var mlaAuthor = authors.map(function (a, i) {
            if (i === 0) { return a.last + (a.first ? ', ' + a.first : ''); }
            return (a.first ? a.first + ' ' : '') + a.last;
        }).join(', ');
        var titlePart = pub.title + (pub.subtitle ? ': ' + pub.subtitle : '');
        return mlaAuthor + '. "' + titlePart + '." ' + pub.venue + ', ' + pub.year + '.';
    }

    function citeBibTeX(pub) {
        var titlePart = pub.title + (pub.subtitle ? ': ' + pub.subtitle : '');
        return '@article{' + pub.id + ',\n'
             + '  author  = {' + pub.author + '},\n'
             + '  title   = {' + titlePart + '},\n'
             + '  year    = {' + pub.year + '},\n'
             + '  journal = {' + pub.venue + '},\n'
             + (pub.pages ? '  pages   = {1--' + pub.pages + '}\n' : '')
             + '}';
    }

    function generateCitation(pub, format) {
        if (format === 'mla')    { return citeMLA(pub); }
        if (format === 'bibtex') { return citeBibTeX(pub); }
        return citeAPA(pub);
    }

    function metaGrid(pub) {
        var grid = el('dl', 'pub-modal__sidebar-meta');
        var rows = [
            [ 'Published by', pub.venue  || '—' ],
            [ 'Year',         pub.year   || '—' ],
            [ 'Author',       pub.author || '—' ],
            [ 'Pages',        pub.pages  || '—' ],
        ];
        rows.forEach(function (row) {
            var d  = el('div',  null);
            var dt = el('dt',   null); dt.textContent = row[0];
            var dd = el('dd',   null); dd.textContent = String(row[1]);
            d.appendChild(dt); d.appendChild(dd);
            grid.appendChild(d);
        });
        return grid;
    }

    function textBlock(text, cls) {
        var p = el('p', cls);
        p.textContent = text;
        return p;
    }

    function el(tag, cls, attrs) {
        var node = document.createElement(tag);
        if (cls) { node.className = cls; }
        if (attrs) {
            Object.keys(attrs).forEach(function (k) { node.setAttribute(k, attrs[k]); });
        }
        return node;
    }

    function attachKeyHandlers() {
        keyHandler = function (e) {
            if (!activeModal) { return; }
            if (e.key === 'Escape') { close(); }
            else if (e.key === 'ArrowRight') {
                var n = activeModal.getSpread() + 1;
                if (n < activeModal.getTotal()) { activeModal.setSpread(n); }
            } else if (e.key === 'ArrowLeft') {
                var p = activeModal.getSpread() - 1;
                if (p >= 0) { activeModal.setSpread(p); }
            }
        };
        document.addEventListener('keydown', keyHandler);
    }
    function detachKeyHandlers() {
        if (keyHandler) { document.removeEventListener('keydown', keyHandler); keyHandler = null; }
    }

    function bindButtons() {
        document.addEventListener('click', function (e) {
            var openBtn = e.target.closest('[data-action="open-reader"]');
            if (openBtn) {
                e.preventDefault();
                e.stopPropagation();
                var row = openBtn.closest('[data-id]') || openBtn.closest('.pub-row, .pub-single');
                if (!row && openBtn.closest('.pub-panel')) {
                    var panel = openBtn.closest('.pub-panel');
                    var slug  = panel.id.replace(/^pub-panel-/, '');
                    row = document.getElementById('pub-row-' + slug);
                }
                if (row) { open(row.getAttribute('data-id'), 0); }
                return;
            }
            var copyBtn = e.target.closest('[data-action="copy-permalink"]');
            if (copyBtn) {
                e.preventDefault();
                e.stopPropagation();
                var src = copyBtn.closest('.pub-panel');
                var slug = src ? src.id.replace(/^pub-panel-/, '') : null;
                var rowEl = slug ? document.getElementById('pub-row-' + slug) : null;
                if (!rowEl) { return; }
                var url = rowEl.getAttribute('data-permalink') || (location.origin + location.pathname + '#pub-' + slug);
                var done = function () {
                    var orig = copyBtn.textContent;
                    copyBtn.textContent = 'Link copied ✓';
                    setTimeout(function () { copyBtn.textContent = orig; }, 1600);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(done, function () { fallbackCopy(url, done); });
                } else {
                    fallbackCopy(url, done);
                }
            }
        });
    }

    function maybeAutoOpenOnSingle() {
        var single = document.querySelector('.pub-single[data-auto-open-reader="true"]');
        if (!single) { return; }
        var attemptOpen = function () { open(single.getAttribute('data-id'), 0); };
        if (window.pdfjsLib) { attemptOpen(); }
        else { window.addEventListener('lieuwe-pdfjs-ready', attemptOpen, { once: true }); }
    }

    function init() {
        bindButtons();
        maybeAutoOpenOnSingle();
    }

    window.LieuwePublicationsReader = { open: open, close: close };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
