# Homepage "Periodical" Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the generic below-hero homepage (intro / portfolio grid / news list) with a periodical front page: lead story + mixed "Meanwhile" rail + workshop filmstrip + Teaching/Services foot blocks.

**Architecture:** Classic WP theme, no build step. All queries live inline in `front-page.php` (existing pattern). One new meta flag (`_lieuwe_lead`) drives lead selection; one new Customizer setting supplies the Teaching block image. All styling in `style.css` using existing tokens; scroll-reveal machinery reused with updated selectors.

**Tech Stack:** WordPress classic theme (PHP 8), vanilla CSS custom properties, vanilla JS.

**Spec:** `docs/superpowers/specs/2026-07-31-homepage-periodical-design.md`

**Verification reality check:** this theme has no test framework and no build. Per-task verification is `php -l` (syntax) plus a visual pass in the local WP environment at the end (`docker-compose up` → http://localhost:8080, or the Local by Flywheel site). Do not invent a PHPUnit setup for this.

**Versioning:** bump `style.css` version `1.18.3 → 1.19.0` in Task 1 (per project convention: once per release, in the first task). Tag `v1.19.0` only in the final task, after user sign-off — pushing the tag deploys to production.

**Spec deviation (resolved):** the spec says "stack at ≤900px" and also "no new breakpoints". The theme's breakpoints are 1024/768. Use **768** for all stacking. No new breakpoints.

---

### Task 1: Version bump + `_lieuwe_lead` meta box

**Files:**
- Modify: `style.css:5` (Version header)
- Modify: `functions.php` (insert after line 109, i.e. after `add_action( 'save_post_portfolio_item', … )`)

- [ ] **Step 1: Bump version**

In `style.css` line 5, change `Version: 1.18.3` → `Version: 1.19.0`.

- [ ] **Step 2: Add the lead meta box to functions.php**

Insert after the `add_action( 'save_post_portfolio_item', 'lieuwe_save_portfolio_meta_box' );` line:

```php
/**
 * "Homepage lead" flag — posts, portfolio items, and publications.
 *
 * front-page.php shows the newest flagged item as the lead story; when
 * nothing is flagged it falls back to the newest post. Several flags at
 * once is fine — newest wins, no warning UI.
 */
function lieuwe_add_lead_meta_box(): void {
    add_meta_box(
        'lieuwe_lead_settings',
        'Homepage',
        'lieuwe_render_lead_meta_box',
        [ 'post', 'portfolio_item', 'publication' ],
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'lieuwe_add_lead_meta_box' );

function lieuwe_render_lead_meta_box( WP_Post $post ): void {
    $lead = get_post_meta( $post->ID, '_lieuwe_lead', true );
    wp_nonce_field( 'lieuwe_lead_meta_box', 'lieuwe_lead_meta_box_nonce' );
    ?>
    <p>
        <label>
            <input type="checkbox" name="lieuwe_lead" value="1" <?php checked( $lead, '1' ); ?>>
            <?php esc_html_e( 'Show as homepage lead', 'lieuwe-theme' ); ?>
        </label>
    </p>
    <?php
}

function lieuwe_save_lead_meta_box( int $post_id ): void {
    if ( ! isset( $_POST['lieuwe_lead_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['lieuwe_lead_meta_box_nonce'], 'lieuwe_lead_meta_box' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['lieuwe_lead'] ) ) {
        update_post_meta( $post_id, '_lieuwe_lead', '1' );
    } else {
        delete_post_meta( $post_id, '_lieuwe_lead' );
    }
}
add_action( 'save_post', 'lieuwe_save_lead_meta_box' );
```

Note: the save hook is generic `save_post` (three post types); the nonce check makes it a no-op everywhere the box wasn't rendered. This mirrors the existing `_lieuwe_featured` box directly above it — keep both, they are independent flags.

- [ ] **Step 3: Lint**

Run: `php -l functions.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add style.css functions.php
git commit -m "feat(home): version 1.19.0 + homepage-lead meta flag"
```

---

### Task 2: Customizer — Teaching block image

**Files:**
- Modify: `inc/customizer.php` (section registration inside `lieuwe_customizer_register()`, accessor at end of file)

- [ ] **Step 1: Register section + setting**

In `inc/customizer.php`, inside `lieuwe_customizer_register()`, directly after the hero-image control block (after the `WP_Customize_Media_Control … 'hero_image'` call, before `// Publications page`), insert:

```php
    // Homepage (below-hero)
    $wp_customize->add_section( 'lieuwe_homepage', [
        'title'    => 'Homepage',
        'priority' => 31,
    ] );

    $wp_customize->add_setting( 'home_teaching_image', [
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control(
        new \WP_Customize_Media_Control( $wp_customize, 'home_teaching_image', [
            'label'       => 'Teaching block image',
            'description' => 'Shown at low opacity behind the Teaching block at the foot of the homepage. Leave empty for plain terracotta.',
            'section'     => 'lieuwe_homepage',
            'mime_type'   => 'image',
        ] )
    );
```

- [ ] **Step 2: Add accessor**

At the end of `inc/customizer.php` (after `lieuwe_business_details()`), add:

```php
/**
 * Homepage Teaching block background image URL. Empty string when unset.
 */
function lieuwe_home_teaching_image_url(): string {
    $id = (int) get_theme_mod( 'home_teaching_image', 0 );
    if ( $id <= 0 ) {
        return '';
    }
    return (string) ( wp_get_attachment_image_url( $id, 'large' ) ?: '' );
}
```

- [ ] **Step 3: Lint**

Run: `php -l inc/customizer.php`
Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add inc/customizer.php
git commit -m "feat(home): customizer setting for teaching block image"
```

---

### Task 3: Rewrite `front-page.php` below the hero

**Files:**
- Modify: `front-page.php` — keep lines 1–51 (header, HERO section, INTRO section) byte-identical; replace everything from `<!-- PORTFOLIO PREVIEW` (line 53) through the `<!-- NEWS PREVIEW` section's closing `</section>` (line 180) with the code below. `</main>` and `get_footer()` stay.

- [ ] **Step 1: Replace the portfolio + news sections**

New content between the INTRO section and `</main>`:

```php
<?php
// Shared lookups for the sections below ---------------------------------

// Portfolio canvas page (plugin-owned template) — used by filmstrip + mixed links.
$canvas_pages = get_pages( [
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'portfolio-canvas',
    'number'     => 1,
] );
$canvas_url = $canvas_pages ? get_permalink( $canvas_pages[0]->ID ) : '';

$mix_types = [ 'post', 'portfolio_item', 'publication' ];

// Lead story: newest _lieuwe_lead-flagged item across the three types;
// fallback to the newest post when nothing is flagged.
$lead_query = new WP_Query( [
    'post_type'           => $mix_types,
    'posts_per_page'      => 1,
    'meta_query'          => [
        [
            'key'     => '_lieuwe_lead',
            'compare' => 'EXISTS',
        ],
    ],
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
] );
if ( ! $lead_query->posts ) {
    $lead_query = new WP_Query( [
        'post_type'           => 'post',
        'posts_per_page'      => 1,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ] );
}
$lead = $lead_query->posts[0] ?? null;

// Portfolio items live on the canvas page, everything else has a permalink.
$lieuwe_mix_url = static function ( WP_Post $p ) use ( $canvas_url ): string {
    if ( 'portfolio_item' === $p->post_type ) {
        return $canvas_url ? $canvas_url . '#item-' . $p->ID : '';
    }
    return (string) get_permalink( $p );
};

// Type label for rail items. Dates only for news (spec).
$lieuwe_mix_label = static function ( WP_Post $p ): string {
    if ( 'portfolio_item' === $p->post_type ) {
        return 'Portfolio';
    }
    if ( 'publication' === $p->post_type ) {
        return 'Publication';
    }
    return 'News · ' . get_the_date( 'j F', $p );
};
?>

<!-- LEAD STORY + MEANWHILE RAIL ------------------------------------------ -->
<?php if ( $lead ) : ?>
<section class="home-lead section-light">
    <div class="container home-lead__grid">
        <article class="home-lead__main">
            <p class="home-lead__eyebrow">Latest — <?php echo esc_html( get_the_date( 'j F Y', $lead ) ); ?></p>
            <h2 class="home-lead__title">
                <a href="<?php echo esc_url( $lieuwe_mix_url( $lead ) ); ?>"><?php echo esc_html( get_the_title( $lead ) ); ?></a>
            </h2>
            <?php if ( has_post_thumbnail( $lead ) ) : ?>
                <a class="home-lead__image" href="<?php echo esc_url( $lieuwe_mix_url( $lead ) ); ?>" tabindex="-1" aria-hidden="true">
                    <?php echo get_the_post_thumbnail( $lead, 'large' ); ?>
                </a>
            <?php endif; ?>
            <?php $lead_excerpt = get_the_excerpt( $lead ); ?>
            <?php if ( $lead_excerpt ) : ?>
                <p class="home-lead__excerpt"><?php echo esc_html( $lead_excerpt ); ?></p>
            <?php endif; ?>
            <a class="home-section-link" href="<?php echo esc_url( $lieuwe_mix_url( $lead ) ); ?>">Read on</a>
        </article>

        <aside class="home-rail">
            <p class="home-lead__eyebrow">Meanwhile</p>
            <?php
            $rail_query = new WP_Query( [
                'post_type'           => $mix_types,
                'posts_per_page'      => 4,
                'post__not_in'        => [ $lead->ID ],
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            ] );
            ?>
            <?php if ( $rail_query->posts ) : ?>
                <ul class="home-rail__list">
                    <?php foreach ( $rail_query->posts as $rail_post ) : ?>
                        <li class="home-rail__item">
                            <a href="<?php echo esc_url( $lieuwe_mix_url( $rail_post ) ); ?>"><?php echo esc_html( get_the_title( $rail_post ) ); ?></a>
                            <span class="home-rail__meta"><?php echo esc_html( $lieuwe_mix_label( $rail_post ) ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="home-section-link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">All news</a>
        </aside>
    </div>
</section>
<?php endif; ?>

<!-- FROM THE WORKSHOP ----------------------------------------------------- -->
<?php
// Featured-first ordering: a single WP_Query with orderby on a named
// meta clause silently falls back to date DESC, because WP renders
// EXISTS/NOT EXISTS as a subquery (no meta_value column to sort on).
// Run two queries and merge so the featured flag actually drives order.
$strip_limit = 4;

$featured_query = new WP_Query( [
    'post_type'      => 'portfolio_item',
    'posts_per_page' => $strip_limit,
    'meta_query'     => [
        [
            'key'     => '_lieuwe_featured',
            'compare' => 'EXISTS',
        ],
    ],
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
] );

$strip_posts = $featured_query->posts;
$remaining   = $strip_limit - count( $strip_posts );

if ( $remaining > 0 ) {
    $fill_query = new WP_Query( [
        'post_type'      => 'portfolio_item',
        'posts_per_page' => $remaining,
        'post__not_in'   => wp_list_pluck( $strip_posts, 'ID' ),
        'meta_query'     => [
            [
                'key'     => '_lieuwe_featured',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ] );
    $strip_posts = array_merge( $strip_posts, $fill_query->posts );
}
?>

<?php if ( $strip_posts ) : ?>
<section class="home-strip">
    <div class="container">
        <div class="home-strip__head">
            <p class="home-lead__eyebrow">From the workshop</p>
            <?php if ( $canvas_url ) : ?>
                <a class="home-section-link" href="<?php echo esc_url( $canvas_url ); ?>">View all work</a>
            <?php endif; ?>
        </div>
        <div class="home-strip__grid">
            <?php global $post; foreach ( $strip_posts as $post ) : setup_postdata( $post ); ?>
                <a href="<?php echo esc_url( $canvas_url . '#item-' . get_the_ID() ); ?>" class="strip-card">
                    <span class="strip-card__image">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large' ); ?>
                        <?php else : ?>
                            <?php $strip_video_url = get_post_meta( get_the_ID(), 'portfolio_video', true ); ?>
                            <?php if ( $strip_video_url ) : ?>
                                <span class="portfolio-card__video-thumb" data-video="<?php echo esc_url( $strip_video_url ); ?>"></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span>
                    <span class="strip-card__title"><?php the_title(); ?></span>
                </a>
            <?php endforeach; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- TEACHING + SERVICES FOOT ---------------------------------------------- -->
<?php
// Teaching archive link is false when the lieuwe-teaching plugin is inactive.
$teaching_url = get_post_type_archive_link( 'teaching_event' );

// Services page: template lookup first; the template may also be applied by
// slug (page-services.php via hierarchy), so fall back to the slug.
$services_pages = get_pages( [
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'page-services.php',
    'number'     => 1,
] );
$services_page = $services_pages ? $services_pages[0] : get_page_by_path( 'services' );
$services_url  = $services_page ? get_permalink( $services_page ) : '';

$teaching_img = lieuwe_home_teaching_image_url();
?>

<?php if ( $teaching_url || $services_url ) : ?>
<section class="home-foot">
    <?php if ( $teaching_url ) : ?>
        <a class="home-foot__block home-foot__block--teaching" href="<?php echo esc_url( $teaching_url ); ?>">
            <?php if ( $teaching_img ) : ?>
                <span class="home-foot__bg" style="background-image: url('<?php echo esc_url( $teaching_img ); ?>')" aria-hidden="true"></span>
            <?php endif; ?>
            <span class="home-foot__inner">
                <span class="home-foot__eyebrow">Teaching</span>
                <span class="home-foot__title">Courses &amp; workshops</span>
                <span class="home-foot__link">See what&rsquo;s coming</span>
            </span>
        </a>
    <?php endif; ?>
    <?php if ( $services_url ) : ?>
        <a class="home-foot__block home-foot__block--services" href="<?php echo esc_url( $services_url ); ?>">
            <span class="home-foot__inner">
                <span class="home-foot__eyebrow">Services</span>
                <span class="home-foot__title">Work with me</span>
                <span class="home-foot__link">What I offer</span>
            </span>
        </a>
    <?php endif; ?>
</section>
<?php endif; ?>
```

Notes for the implementer:
- The HERO and INTRO sections at the top of the file are untouched — do not reformat them.
- `.section-dark` / `.section-light` classes are dropped for the new sections; backgrounds are set by the new section classes in Task 4.
- The video-thumb element keeps the exact class `portfolio-card__video-thumb` — `main.js` selects on it and replaces it with a `<canvas>`; the surrounding `.strip-card__image` wrapper keeps the aspect ratio either way. It's a `<span>` now (must be inline-capable inside `<a>` for valid HTML; `display:block` comes from CSS).
- When only one foot block renders, the CSS in Task 4 makes it span the full width.

- [ ] **Step 2: Lint**

Run: `php -l front-page.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add front-page.php
git commit -m "feat(home): periodical layout — lead story, meanwhile rail, filmstrip, foot blocks"
```

---

### Task 4: style.css — new section styles, delete old ones

**Files:**
- Modify: `style.css` — the "Homepage Sections" block (lines ~455–710), the reveal-delay rules (~732–735), the 1024px media query (~2030), keep everything else.

- [ ] **Step 1: Delete old section styles**

Delete these rule blocks entirely (keep `.home-intro *`, `.home-section-link`, `.home-empty`):
- `.home-portfolio` through `.portfolio-card__title` (lines ~534–596)
- `.home-news` through `.home-news__title:hover` (lines ~598–664)
- The whole `@media (max-width: 768px)` block at ~688–710 (its four rules all target deleted classes)
- In the `@media (max-width: 1024px)` block near line ~2030: remove only the `.home-portfolio__grid { grid-template-columns: repeat(2, 1fr); }` rule; keep the `:root` spacing overrides.

- [ ] **Step 2: Add new section styles**

In the space left by the deleted portfolio/news rules (between the `.home-intro` rules and the `.home-section-link` rule), add:

```css
/* Lead story + Meanwhile rail */
.home-lead {
    padding-block: var(--space-lg) var(--space-xl);
}

.home-lead__grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--space-lg);
    align-items: start;
}

.home-lead__eyebrow {
    font-size: 0.6875rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--color-accent);
    border-top: 2px solid var(--color-text);
    padding-top: 0.75rem;
    margin-bottom: var(--space-sm);
}

.home-lead__title {
    font-size: clamp(1.6rem, 3vw, 2.25rem);
    line-height: 1.15;
    margin-bottom: var(--space-sm);
}

.home-lead__title a {
    color: inherit;
    transition: color 0.2s ease;
}

.home-lead__title a:hover {
    color: var(--color-accent);
}

.home-lead__image {
    display: block;
    overflow: hidden;
    background-color: var(--color-surface);
}

.home-lead__image img {
    display: block;
    width: 100%;
    height: auto;
    max-height: 420px;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
}

.home-lead__image:hover img {
    transform: scale(1.03);
}

.home-lead__excerpt {
    margin-block: var(--space-sm);
    font-size: 1.0625rem;
    line-height: 1.7;
    color: var(--color-muted);
    max-width: 60ch;
}

.home-rail {
    border-left: 1px solid var(--color-surface);
    padding-left: var(--space-md);
}

.home-rail__list {
    list-style: none;
    padding: 0;
    margin: 0 0 var(--space-md);
}

.home-rail__item {
    padding-block: 0.85rem;
    border-top: 1px solid var(--color-surface);
}

.home-rail__item:first-child {
    border-top: none;
    padding-top: 0;
}

.home-rail__item a {
    display: block;
    margin-bottom: 0.3rem;
    font-family: var(--font-display);
    font-size: 1.0625rem;
    color: var(--color-text);
    transition: color 0.2s ease;
}

.home-rail__item a:hover {
    color: var(--color-accent);
}

.home-rail__meta {
    font-size: 0.6875rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--color-muted);
}

/* Workshop filmstrip */
.home-strip {
    padding-block: var(--space-lg);
    background-color: var(--color-surface);
}

.home-strip__head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: var(--space-md);
}

.home-strip__head .home-lead__eyebrow {
    margin-bottom: 0;
}

.home-strip__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-md);
}

.strip-card {
    display: block;
}

.strip-card__image {
    display: block;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background-color: var(--color-warm);
}

.strip-card__image img,
.strip-card__image video,
.strip-card__image canvas,
.strip-card__image .portfolio-card__video-thumb {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
}

.strip-card:hover .strip-card__image img,
.strip-card:hover .strip-card__image canvas {
    transform: scale(1.04);
}

.strip-card__title {
    display: block;
    margin-top: 0.6rem;
    font-family: var(--font-display);
    font-size: 1rem;
    color: var(--color-text);
}

/* Teaching + Services foot */
.home-foot {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

/* A lone block (teaching plugin inactive, or no services page) spans full width. */
.home-foot__block:only-child {
    grid-column: 1 / -1;
}

.home-foot__block {
    position: relative;
    display: block;
    padding: var(--space-lg) var(--space-md);
    min-height: 200px;
}

.home-foot__block--teaching {
    background-color: var(--color-accent);
    color: var(--color-text-light);
}

.home-foot__block--services {
    background-color: var(--color-blush);
    color: var(--color-text);
}

.home-foot__bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    opacity: 0.25;
}

.home-foot__inner {
    position: relative;
    display: block;
    max-width: 32rem;
    margin-inline: auto;
}

.home-foot__eyebrow {
    display: block;
    font-size: 0.6875rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    opacity: 0.75;
}

.home-foot__title {
    display: block;
    margin: 0.4rem 0 0.75rem;
    font-family: var(--font-display);
    font-size: clamp(1.25rem, 2vw, 1.6rem);
}

.home-foot__link {
    display: inline-block;
    font-size: 0.6875rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    border-bottom: 1px solid currentColor;
    padding-bottom: 2px;
}

@media (max-width: 768px) {
    .home-lead__grid {
        grid-template-columns: 1fr;
    }

    .home-rail {
        border-left: none;
        padding-left: 0;
    }

    .home-strip__grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .home-foot {
        grid-template-columns: 1fr;
    }
}
```

- [ ] **Step 3: Update the reveal-delay rules**

Replace (at ~line 732):

```css
.home-portfolio__grid .home-reveal:nth-child(2) { transition-delay: 0.08s; }
.home-portfolio__grid .home-reveal:nth-child(3) { transition-delay: 0.16s; }
.home-news__list .home-reveal:nth-child(2) { transition-delay: 0.06s; }
.home-news__list .home-reveal:nth-child(3) { transition-delay: 0.12s; }
```

with:

```css
.home-strip__grid .home-reveal:nth-child(2) { transition-delay: 0.08s; }
.home-strip__grid .home-reveal:nth-child(3) { transition-delay: 0.16s; }
.home-strip__grid .home-reveal:nth-child(4) { transition-delay: 0.24s; }
```

- [ ] **Step 4: Sanity check for stale selectors**

Run: `grep -n "home-portfolio\|home-news\|portfolio-card" style.css front-page.php assets/js/main.js`
Expected: only `portfolio-card__video-thumb` hits (in `front-page.php`, `style.css`, `main.js`). Any other hit is a missed deletion — fix it.

- [ ] **Step 5: Commit**

```bash
git add style.css
git commit -m "feat(home): periodical styles, drop old portfolio/news section CSS"
```

---

### Task 5: main.js — update scroll-reveal targets

**Files:**
- Modify: `assets/js/main.js` (~line 75, the `homeRevealTargets` selector)

- [ ] **Step 1: Replace the selector**

Replace:

```js
  const homeRevealTargets = document.querySelectorAll(
    '.home-intro .wp-block-group, .home-intro .wp-block-image, ' +
    '.home-portfolio__heading, .home-portfolio .portfolio-card, .home-portfolio .home-section-link, ' +
    '.home-news__heading, .home-news__item, .home-news .home-section-link'
  );
```

with:

```js
  const homeRevealTargets = document.querySelectorAll(
    '.home-intro .wp-block-group, .home-intro .wp-block-image, ' +
    '.home-lead__main, .home-rail, ' +
    '.home-strip__head, .home-strip .strip-card, ' +
    '.home-foot__block'
  );
```

- [ ] **Step 2: Verify no other references**

Run: `grep -n "home-portfolio\|home-news" assets/js/main.js`
Expected: no output.

- [ ] **Step 3: Commit**

```bash
git add assets/js/main.js
git commit -m "feat(home): point scroll-reveal at new periodical sections"
```

---

### Task 6: Local visual verification + release

**Files:** none (verification + release mechanics)

- [ ] **Step 1: Boot the local site**

Run: `docker-compose up -d` (repo root) → http://localhost:8080, or use the Local by Flywheel site if it's already set up with content. A site with a few posts/portfolio items gives the truest picture.

- [ ] **Step 2: Verify against this checklist**

- Hero unchanged (video plays, title over it).
- Lead story: newest post appears with eyebrow `Latest — {date}`, title, image, excerpt, `Read on`.
- Flag a portfolio item via its new "Homepage" meta box → it takes the lead spot; unflag → newest post returns.
- Meanwhile rail: 4 items, mixed types, correct labels (`News · {date}` / `Portfolio` / `Publication`), lead not repeated; portfolio links go to the canvas page `#item-{ID}`.
- Filmstrip: 4 portfolio items, featured-first; video-only item shows a first-frame thumb; titles below images.
- Foot: Teaching (terracotta) + Services (blush) blocks link correctly; with the teaching plugin deactivated the Services block spans full width.
- Customizer → Homepage → set Teaching block image → appears at low opacity behind the teaching text.
- ≤768px: lead+rail stacked, filmstrip 2×2, foot stacked.
- Scroll-reveal fires on the new sections; with `prefers-reduced-motion` everything is visible immediately.
- Browser console: no JS errors.

- [ ] **Step 3: STOP — user sign-off**

Show the user the result (screenshots or their own local site). Do not tag or push until they approve: **pushing the `v1.19.0` tag deploys to production** via GitHub Actions.

- [ ] **Step 4: Tag and push (after approval only)**

```bash
git tag -a v1.19.0 HEAD -m "Release v1.19.0 — periodical homepage: lead story, meanwhile rail, filmstrip, teaching/services foot"
git push origin main --tags
```

- [ ] **Step 5: Editorial follow-up (user, in WP admin — remind them)**

- Remove the large tent photo from the front page's content (Pages → the front page) — it now lives behind the Teaching block.
- Customizer → Homepage → set the Teaching block image (the tent photo).
- Optionally flag one item as homepage lead.
