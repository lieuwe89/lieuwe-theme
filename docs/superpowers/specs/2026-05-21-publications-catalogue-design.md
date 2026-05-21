# Publications Catalogue — Design Spec

Date: 2026-05-21
Target release: theme `v1.8.0`
Status: approved (brainstorming)

## Summary

A new `/writing/` page for **lieuwejongsma.nl** — a year-grouped typographic catalogue of Lieuwe's writing (Catalogues, Monographs, Essays, Features, Papers). Each row expands inline to show a PDF spread preview, abstract, and actions. A fullscreen reader modal (real two-page spread, filmstrip nav, sidebar metadata, keyboard nav, dim toggle, cite popover) launches from the row.

The visual design and interaction model are fully specified in the design handoff at `design_handoff_publications_catalogue/README.md` and the working prototype at `design_handoff_publications_catalogue/Prototype.html`. This document **does not re-state** the visual spec — it captures the project-specific decisions made during brainstorming and the architecture for porting the prototype into the theme.

**Read this with the handoff README open** — the README is the design source of truth, this doc is the build plan.

## Approved decisions (from brainstorming)

| Decision | Choice |
|---|---|
| Scope | Full spec, one shot (CPT, archive, inline expand, fullscreen reader, cite popover, permalinks) |
| PDF.js delivery | Self-hosted under `assets/js/vendor/` |
| Nav link | Theme registers post type + auto-includes `/writing/` in primary-menu fallback. Document in `CLAUDE.md` that user adds proper menu item via Appearance → Menus once content exists. |
| Seed data | None — empty install. User creates publications via WP admin. |
| Mobile | Full responsive parity. Phone uses single-page (not spread), sheet-style sidebar, native pinch-zoom in modal. |
| Code layout | "C+" — PHP in `inc/publications.php`, CSS in `assets/css/publications.css` conditionally enqueued, JS split into list + reader modules. |
| Page count meta field | Auto-fills client-side from `pdf.numPages` if left blank in admin. |
| Download button | Toggle-able via `_pub_allow_download` checkbox (default on). Hidden when off. |
| Author field | Free text. Co-authors as comma-separated string ("Lieuwe Jongsma, Aike Lestestuiver et al."). Cite generator splits on comma. |
| Hero title + intro | Both editable via WP Customizer ("Publications page" section). |
| Pagination | None — `posts_per_page=-1`. Reasonable cap is ~100 publications; not expected to exceed. |
| Pre-fetch PDFs | No. Lazy fetch on first row expand. |
| Plugin vs theme | Theme — perf and manageability handled by conditional enqueue + module split, not by decoupling. |

## File layout

```
lieuwe-theme/
├── functions.php                       # MODIFY: require_once 'inc/publications.php';
├── inc/
│   ├── customizer.php                  # MODIFY: add "Publications page" section (title 1, title 2, intro)
│   └── publications.php                # NEW: CPT + meta box + save + enqueue
├── archive-publication.php             # NEW: /writing/ catalogue
├── single-publication.php              # NEW: /writing/<slug>/ permalink target
├── style.css                           # MODIFY: bump Version 1.7.0 → 1.8.0 (no other changes)
├── assets/
│   ├── css/
│   │   └── publications.css            # NEW: all publications styles
│   └── js/
│       ├── main.js                     # unchanged
│       ├── publications.js             # NEW: list / filter / search / expand / PDF.js wrapper
│       ├── publications-reader.js      # NEW: fullscreen modal + cite
│       └── vendor/
│           ├── pdf.min.mjs             # NEW: self-hosted PDF.js v4.7.76
│           └── pdf.worker.min.mjs      # NEW: worker
└── docs/superpowers/specs/
    └── 2026-05-21-publications-catalogue-design.md
```

`functions.php` and `style.css` stay near their current size. All publications-specific bulk lives in `inc/publications.php`, `assets/css/publications.css`, and the two JS files.

## Data model

### Custom post type

```php
register_post_type( 'publication', [
    'labels' => [
        'name'          => 'Publications',
        'singular_name' => 'Publication',
        'add_new_item'  => 'Add New Publication',
        'edit_item'     => 'Edit Publication',
        'all_items'     => 'All Publications',
        'menu_name'     => 'Publications',
    ],
    'public'       => true,
    'has_archive'  => true,
    'supports'     => [ 'title', 'editor', 'thumbnail' ],
    'show_in_rest' => true,
    'rewrite'      => [ 'slug' => 'writing' ],
    'menu_icon'    => 'dashicons-book-alt',
    'menu_position'=> 6,
] );
```

Modeled on the existing `portfolio_item` registration in `functions.php`.

### Meta fields

| Meta key | Type | Sanitize | UI | Notes |
|---|---|---|---|---|
| `_pub_subtitle` | text | `sanitize_text_field` | text input | Italic sub-line on row |
| `_pub_year` | int | `absint`, 1900–current+5 | number input | Required for grouping. Missing year → post excluded from archive. |
| `_pub_venue` | text | `sanitize_text_field` | text input | "Apollo Magazine", "Mauritshuis" |
| `_pub_type` | enum | whitelist | `<select>` | One of: `Catalogue`, `Monograph`, `Essay`, `Feature`, `Paper` |
| `_pub_author` | text | `sanitize_text_field` | text input | Default placeholder: "Lieuwe Jongsma". Free text for co-authors: "Lieuwe Jongsma, Jane Smith". |
| `_pub_pages` | int | `absint` | number input | **Leave blank to auto-fill from PDF at render time** |
| `_pub_abstract` | textarea | `sanitize_textarea_field` | textarea | 1–3 sentences |
| `_pub_pdf_id` | int | `absint` (must resolve to PDF attachment) | WP media frame button | Upload via Media Library |
| `_pub_allow_download` | bool | `(bool)` | checkbox, default `true` | When false, hide download button even if PDF exists. Reader still works. |
| `_pub_paper_color` | hex | `sanitize_hex_color` | color input | Default `#f5ecd9` |
| `_pub_accent_color` | hex | `sanitize_hex_color` | color input | Default `#3a2a1f` |

Save handler: WP nonce check + `current_user_can( 'edit_post', $post_id )` + per-key sanitize. Pattern from `lieuwe_save_portfolio_meta_box` in `functions.php`.

### "Has PDF" rule

```php
function lieuwe_pub_has_pdf( int $post_id ): bool {
    $id = (int) get_post_meta( $post_id, '_pub_pdf_id', true );
    if ( $id <= 0 ) return false;
    $url = wp_get_attachment_url( $id );
    return $url && str_ends_with( strtolower( $url ), '.pdf' );
}
```

If false → row's expand panel shows disabled "Open in reader — soon" with dashed-border treatment, no download link.

### Archive query

```php
$q = new WP_Query( [
    'post_type'      => 'publication',
    'posts_per_page' => -1,
    'meta_key'       => '_pub_year',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
    'meta_query'     => [ [ 'key' => '_pub_year', 'compare' => 'EXISTS' ] ],
] );
```

Year-grouping happens in PHP as the loop runs (track `$current_year`, emit year header when it changes).

## Templates

### `archive-publication.php`

```
get_header();
  Hero (Customizer-driven title line 1 + line 2 italic + intro paragraph)
  Filter bar (type chips, search, year-sort toggle, status line)
  WP_Query loop:
    on year change → emit year-header markup
    emit row markup with data-* attributes pre-baked for JS
    emit expand-panel markup hidden (lazy-rendered by JS)
get_footer();
```

**Row markup contract** — JS reads these `data-*` attrs:

```html
<article class="pub-row"
         id="pub-row-<slug>"
         data-id="<slug>"
         data-type="Essay"
         data-year="2024"
         data-has-pdf="true"
         data-allow-download="true"
         data-pdf-url="<escaped attachment URL>"
         data-pages="48"
         data-author="Lieuwe Jongsma"
         data-venue="Apollo Magazine"
         data-paper-color="#f5ecd9"
         data-accent-color="#3a2a1f"
         data-search="<lowercased title + subtitle + venue + abstract>">
  <div class="pub-row__main">
    <h3 class="pub-row__title"><?php the_title(); ?></h3>
    <p class="pub-row__subtitle"><?php echo esc_html( $subtitle ); ?></p>
  </div>
  <div class="pub-row__venue"><?php echo esc_html( $venue ); ?></div>
  <div class="pub-row__type">
    <span><?php echo esc_html( $type ); ?></span>
    <span class="pub-row__pages" data-pages-fallback>
      <?php echo $pages ? esc_html( $pages . 'pp' ) : '—'; ?>
    </span>
  </div>
  <button class="pub-row__toggle" aria-expanded="false" aria-controls="pub-panel-<slug>">
    <span class="visually-hidden">Expand</span>
  </button>
</article>
<section id="pub-panel-<slug>" class="pub-row__panel" hidden>
  <?php /* spread placeholder + abstract + actions (server-rendered HTML, no canvas yet) */ ?>
</section>
```

`data-search` is pre-computed server-side as a single lowercased string joining title + subtitle + venue + abstract with a single space (e.g. `"hoe van gogh stilte en kleur apollo magazine an exhibition essay reconsidering..."`). JS does plain `indexOf(state.query.toLowerCase())` against it — no per-keystroke recomputation, no per-row property access.

**Empty / error states:**

| Condition | Render |
|---|---|
| Zero publications | Hero renders. Filter bar hidden. Body: italic muted line *"Publications coming soon."* |
| Filter/search returns zero rows | Year groups hidden via CSS. Status line shows "0 of N publications". Below filter bar: italic muted line *"No publications match. Try clearing the filter or search."* + small "Clear" text button. |

### `single-publication.php`

Permalink target for share links (`/writing/hoe-van-gogh/`). Renders the publication's hero + abstract + download/cite buttons in a focused single-page layout (same typography as archive but no list).

Auto-opens the reader modal on load if `?reader=1` is present in the URL.

### `header.php` / `footer.php`

Unchanged. The existing global header + scroll behavior in `assets/js/main.js` apply. The "Writing" nav link picks up `.current-menu-item` styling automatically via WP.

## Customizer additions (in `inc/customizer.php`)

New section: `"Publications page"` (panel: theme defaults section).

| Setting ID | Type | Default |
|---|---|---|
| `pub_hero_title_line1` | text | `"Here are some of"` |
| `pub_hero_title_line2` | text (rendered italic) | `"my recent publications"` |
| `pub_hero_intro` | textarea | `"Catalogues, essays, one slow monograph. Written across museum residencies, magazine commissions, and the bench. Click a title to open it — pages render in place."` (placeholder copy from the handoff; user will overwrite via Customizer) |

Two title fields rather than parsing a `/` separator — keeps the italic styling clean and predictable.

## JavaScript modules

### `assets/js/publications.js` (~250 lines)

```js
(function () {
  'use strict';

  const PDF_CACHE  = new Map();   // url → Promise<PDFDocumentProxy>
  const PAGE_CACHE = new Map();   // 'url|page|width' → Promise<HTMLCanvasElement>

  const state = {
    filter: 'All',
    query: '',
    sortAsc: false,
    expandedId: null,
  };

  function init() {
    initFilters();   // type-chip click → setState({ filter })
    initSearch();    // input listener, 150ms debounce → setState({ query })
    initSort();      // year-sort button → setState({ sortAsc }) + reorder DOM
    initRows();      // delegated click on .pub-row → toggleExpand(id)
    initDeepLink();  // window.location.hash → auto-expand row
    applyFilterState();
  }

  function setState(patch) { Object.assign(state, patch); applyFilterState(); }

  function applyFilterState() {
    // walk all rows once, toggle .is-hidden based on (filter match) && (query substring match against data-search)
    // update "N of M publications" status line
    // no innerHTML rewrites
  }

  function toggleExpand(id) {
    const opening = state.expandedId !== id;
    if (state.expandedId) collapsePanel(state.expandedId);
    state.expandedId = opening ? id : null;
    if (opening) expandPanel(id);
  }

  function expandPanel(id) {
    const panel = document.getElementById('pub-panel-' + id);
    panel.hidden = false;
    panel.classList.add('is-open');
    renderSpread(panel, 0);   // lazy — first PDF fetch happens here
    setupPanelControls(panel);
  }

  async function loadPdf(url) {
    if (!PDF_CACHE.has(url)) {
      PDF_CACHE.set(url, pdfjsLib.getDocument({ url }).promise);
    }
    return PDF_CACHE.get(url);
  }

  async function renderPdfPage(url, pageNumber, targetWidth) {
    const w = Math.round(targetWidth / 40) * 40;  // snap to 40px to limit cache churn
    const key = url + '|' + pageNumber + '|' + w;
    if (PAGE_CACHE.has(key)) return PAGE_CACHE.get(key);
    // ... render to canvas, cache, return
  }

  async function renderSpread(container, spreadIndex) {
    const pdfUrl = container.dataset.pdfUrl;
    // spread 0 = [blank, page 1] (cover-right)
    // spread N>0 = [page 2N, page 2N+1]
    // total spreads = Math.ceil((pages + 1) / 2)
    // render placeholder div (paper color + dot pattern) until canvas resolves
    // after pdf load: if [data-pages-fallback] is "—", update to pdf.numPages + 'pp'
  }

  window.LieuwePublications = { loadPdf, renderPdfPage, renderSpread, state };

  document.addEventListener('DOMContentLoaded', init);
})();
```

### `assets/js/publications-reader.js` (~350 lines)

```js
(function () {
  'use strict';

  let activeModal = null;

  function open(pubId, spreadIndex = 0) {
    if (activeModal) close();
    const row = document.getElementById('pub-row-' + pubId);
    const pub = readDataset(row);
    activeModal = buildModal(pub, spreadIndex);
    document.body.appendChild(activeModal.root);
    document.body.style.overflow = 'hidden';
    attachKeyHandlers();
    requestAnimationFrame(() => activeModal.root.classList.add('is-open'));
  }

  function close() {
    if (!activeModal) return;
    detachKeyHandlers();
    activeModal.root.classList.remove('is-open');
    activeModal.root.addEventListener('transitionend', () => {
      activeModal.root.remove();
      document.body.style.overflow = '';
      activeModal = null;
    }, { once: true });
  }

  function buildModal(pub, spread) {
    // pure DOM construction. No innerHTML except safely-escaped text via .textContent.
    // sections: backdrop, reader pane (top bar / spread area / arrows / filmstrip), sidebar
    // returns { root, setSpread, setDim, openCite }
  }

  function attachKeyHandlers() {
    // Escape → close
    // ArrowLeft / ArrowRight → prev / next spread (suppressed when modal scale > 1)
  }

  // Cite generators
  function citeAPA(pub)    { /* "Jongsma, L. (2024). Title. Venue." */ }
  function citeMLA(pub)    { /* "Jongsma, Lieuwe. \"Title.\" Venue, 2024." */ }
  function citeBibTeX(pub) { /* @article{slug, author={...}, title={...}, ... } */ }

  // Phone: bottom-sheet sidebar with drag-to-dismiss (~50 lines of pointer handlers)
  // Phone: native pinch-zoom via CSS touch-action: pinch-zoom on spread wrapper
  //        when scale > 1, swipe-nav disabled (arrow buttons still work)
  //        double-tap resets scale to 1

  window.LieuwePublicationsReader = { open, close };
})();
```

### Module bridge

`publications.js` exposes `window.LieuwePublications`. `publications-reader.js` exposes `window.LieuwePublicationsReader`. The "Open in reader" button calls `LieuwePublicationsReader.open(id, currentSpread)`. The reader, when rendering, calls `LieuwePublications.renderPdfPage` so cache is shared between inline preview and modal.

### Enqueue (in `inc/publications.php`)

```php
function lieuwe_pub_enqueue() {
    if ( ! is_post_type_archive( 'publication' ) && ! is_singular( 'publication' ) ) {
        return;
    }
    $ver        = wp_get_theme()->get( 'Version' );
    $worker_url = get_template_directory_uri() . '/assets/js/vendor/pdf.worker.min.mjs';

    wp_enqueue_style(
        'lieuwe-publications',
        get_template_directory_uri() . '/assets/css/publications.css',
        [ 'lieuwe-style' ],
        $ver
    );

    wp_enqueue_script_module(
        'pdfjs',
        get_template_directory_uri() . '/assets/js/vendor/pdf.min.mjs',
        [],
        '4.7.76'
    );
    wp_add_inline_script(
        'pdfjs',
        "window.pdfjsLib && (pdfjsLib.GlobalWorkerOptions.workerSrc = '" . esc_url( $worker_url ) . "');",
        'after'
    );

    wp_enqueue_script(
        'lieuwe-publications',
        get_template_directory_uri() . '/assets/js/publications.js',
        [ 'pdfjs' ],
        $ver,
        true
    );
    wp_enqueue_script(
        'lieuwe-publications-reader',
        get_template_directory_uri() . '/assets/js/publications-reader.js',
        [ 'lieuwe-publications' ],
        $ver,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'lieuwe_pub_enqueue' );
```

PDF.js v4 ships as ES module. `wp_enqueue_script_module` requires WordPress **6.5+** (released April 2024). Site is on current WP, so this is fine — but if WP is somehow ≤ 6.4, the script module call fails silently and PDF.js never loads. Implementation should `add_action` a one-time admin notice if `version_compare( get_bloginfo( 'version' ), '6.5', '<' )` is true. Worker URL must be set before any `getDocument` call — inline script handles that.

## Responsive behaviour

Three breakpoints in `publications.css`:

```
≥ 960px   desktop (default — matches handoff spec exactly)
640–959px tablet
< 640px   phone
```

| Region | Tablet (640–959) | Phone (<640) |
|---|---|---|
| Hero | H1 uses `clamp(56,9vw,108)`. Padding top 180 → 120. | Padding top 96. Intro paragraph full width. |
| Filter bar | Wraps to 2 rows: chips on row 1, search + sort + status on row 2. | Chips horizontally scroll (`overflow-x: auto`). Search full width. Year-sort + status stack below. |
| Year header | Year font 56 → 44. | Year font 36. "N publications" count moves below year. |
| Row | Grid `1fr 180px 100px 28px`. Title font 38 → 32. | Single column. Title, subtitle, meta line "VENUE · TYPE · 96pp" in Jost 10px, toggle pinned right. |
| Expand panel | Two cols stay. Spread pages 220×316 → 180×260. | Single column. Spread shows **one page at a time** (see "Mobile spread → page selection" below). Then abstract + actions stacked. |
| Reader modal | Sidebar 360 → 300. Spread pages recomputed. | Sidebar becomes a **bottom sheet** — slides up from bottom (60vh), drag-handle at top, swipe down or × dismisses sheet. Spread = single page (see below), swipe left/right to navigate. |
| Cite popover | Same — popover above the Cite button. | Bottom sheet instead. |

**Mobile spread → page selection:**

On phone, the "spread" concept collapses to a single page, but JS still tracks spreadIndex (so filmstrip + arrow + swipe semantics stay identical to desktop). The page rendered for each spread:

- Spread 0 → render page 1 (the cover; the "blank left" half is dropped).
- Spread N>0 → render page 2N (the left page of the spread; right page is reached by swiping to spread N+0.5… i.e. the user does *two* swipes per desktop spread). Simpler alternative considered and rejected: render only left pages and let user double-tap to flip-within-spread — adds gesture complexity for little gain.

Concretely: a 32-page PDF (16 spreads desktop) becomes 32 "pages" on phone (spread 0…31 maps 1:1 to PDF page 1…32, with the cover being spread 0).

**Touch interactions (phone):**

- Row tap = expand. Hover state suppressed via `@media (hover: hover)`.
- Single-finger swipe left/right on spread = prev/next (inside expand panel and modal).
- Pinch-zoom on spread inside modal = native via `touch-action: pinch-zoom`. When scale > 1: swipe-nav disabled, single-finger pan moves the zoomed page, arrow buttons still navigate, double-tap resets to 1×.

## Error handling

| Failure | Behavior |
|---|---|
| PDF.js fails to load | Site still works. Expand still toggles. Spread shows paper placeholder + *"PDF preview unavailable — download the PDF to read."* Download still works (plain `<a>`). "Open in reader" disabled. Console warns once. |
| Specific PDF fails to fetch (404, corrupt) | Spread shows paper placeholder + *"This PDF could not load."* Download still works. Other PDFs unaffected. |
| `_pub_year` missing | Post excluded from archive (`meta_query EXISTS`). Admin notice on edit screen: "Set a year for this publication to publish it on the catalogue." |
| `_pub_pdf_id` points to non-PDF | Treated as "no PDF". Warning under media picker. |
| `_pub_allow_download = false` | Download buttons hidden in row and modal. Reader and cite still work. |
| Clipboard write fails (no HTTPS, old browser) | "Link copied ✓" label replaced with "Copy this URL: [auto-selected text in tiny input]" for manual ⌘C. |
| Reader modal opened twice rapidly | Guarded — `if (activeModal) close()` runs synchronously before mount. |

## Verification checklist (manual — no test suite in this theme)

1. Theme activates clean. `Publications` appears in admin sidebar with book icon.
2. Create publication with all fields + PDF → row on `/writing/`. Expand renders PDF spread. Modal opens. APA/MLA/BibTeX strings correct. Clipboard copy works.
3. Create publication with no PDF → disabled "soon" state, dashed border.
4. Create publication, leave `_pub_pages` blank → after PDF loads in expand, page count auto-fills in column 3.
5. Toggle `_pub_allow_download` off → download buttons hide in row + modal. Reader still works.
6. Filter chips + search + year sort combine correctly. "N of M" status accurate.
7. Single-row expand: opening one collapses any other open.
8. `/writing/#pub-<slug>` URL → scroll + auto-expand.
9. `/writing/<slug>/?reader=1` → modal auto-opens.
10. Customizer: change all three Publications fields → live preview updates, save persists.
11. Keyboard: Tab through filter bar + rows. Enter on row expands. Esc closes modal. Arrow keys navigate spreads.
12. Mobile (iOS Safari + Android Chrome): row reflows to single column, spread shows single page, sheet sidebar slides up, pinch-zoom works, swipe nav at 1× scale, double-tap resets zoom.
13. Lighthouse on `/` (homepage): no regression — confirms conditional enqueue worked.
14. Lighthouse on `/writing/`: PDF.js and publications JS appear only here.
15. Empty state: deactivate all publications → "Publications coming soon." shows.
16. Zero-results state: search for "qzxyz" → "No publications match" + Clear button.

## Versioning

- `style.css` `Version:` bumped `1.7.0 → 1.8.0`.
- After all commits, create annotated tag and push:
  ```
  git tag -a v1.8.0 HEAD -m "Release v1.8.0 — Publications catalogue at /writing/"
  git push origin main --tags
  ```
- WordPress upload zip generated by GitHub Release for `v1.8.0`.

## Out of scope (deferred)

- Automated test suite for the theme (none today; not introducing one for this feature).
- Pagination on the archive (`posts_per_page=-1` is fine until >100 publications).
- Server-side PDF metadata parsing (page count is the only one that matters and JS handles it).
- Co-author as a separate field (free text + comma split is enough).
- Pinch-zoom on inline expand panel (only inside the reader modal).
- Pre-fetching PDFs on page load (lazy on first expand).
- Plugin extraction of CPT (single site, theme-baked is fine).
