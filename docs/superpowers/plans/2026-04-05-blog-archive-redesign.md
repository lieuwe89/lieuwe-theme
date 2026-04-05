# Blog Archive Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the plain date-column news list with a cinematic staggered-row layout — alternating image-left / image-right rows with natural aspect ratios, capped at 500px height.

**Architecture:** Two files change — `style.css` gets a new `.news-rows` CSS block (and the old `.news-list` block removed), and `archive.php` gets its loop rewritten to emit `<article class="news-row">` markup. No JS changes. No new files.

**Tech Stack:** PHP (WordPress template tags), vanilla CSS (custom properties already defined in `:root`)

---

## File Map

| File | Change |
|---|---|
| `style.css` | Add new `.news-rows` block (lines ~677); remove old `.news-list` block (lines 677–723) |
| `archive.php` | Rewrite the post loop — replace `<ul class="news-list">` structure with `<div class="news-rows">` structure |

---

### Task 1: Add the new `.news-rows` CSS block

**Files:**
- Modify: `style.css` (after line 675, inside the `News Archive` section)

The new block is additive — the old `.news-list` styles stay in place for now so nothing breaks mid-implementation.

- [ ] **Step 1: Open `style.css` and locate the `News Archive` section**

  It starts around line 663:
  ```css
  /* ==========================================================================
     News Archive
     ========================================================================== */
  ```
  The `.news-archive__header` and `.news-archive__title` rules come first, then the `.news-list` block starts at approximately line 677. Insert the new block **before** the `.news-list` block.

- [ ] **Step 2: Insert the new `.news-rows` CSS block**

  Add this immediately after `.news-archive__title { … }` and before `.news-list { … }`:

  ```css
  /* --- Staggered rows (replaces .news-list) --- */

  .news-rows {
      padding-block: var(--space-md);
  }

  .news-row {
      display: flex;
      align-items: stretch;
      border-bottom: 1px solid var(--color-surface);
  }

  .news-row--reverse {
      flex-direction: row-reverse;
  }

  .news-row__img {
      display: block;
      width: 55%;
      flex-shrink: 0;
      height: auto;
      max-height: 500px;
      object-fit: cover;
  }

  .news-row__img-placeholder {
      width: 55%;
      flex-shrink: 0;
      height: 300px;
      background: var(--color-surface);
  }

  .news-row__body {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 0.625rem;
      padding: 2.5rem 3rem;
  }

  .news-row__date {
      font-size: 0.6875rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--color-accent);
  }

  .news-row__title {
      display: block;
      font-family: var(--font-display);
      font-size: 1.25rem;
      font-weight: 400;
      color: var(--color-text);
      line-height: 1.4;
      transition: color 0.2s ease;
  }

  .news-row:hover .news-row__title {
      color: var(--color-accent);
  }

  .news-row__link {
      display: block;
      font-size: 0.6875rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--color-accent);
  }

  @media (max-width: 1024px) {
      .news-row__img,
      .news-row__img-placeholder {
          width: 50%;
      }

      .news-row__body {
          padding: 1.75rem 2rem;
      }
  }

  @media (max-width: 768px) {
      .news-row,
      .news-row--reverse {
          flex-direction: column;
      }

      .news-row__img {
          width: 100%;
          max-height: none;
      }

      .news-row__img-placeholder {
          width: 100%;
          height: 200px;
      }

      .news-row__body {
          padding: 1.25rem 1rem;
      }

      .news-row__title {
          font-size: 1.125rem;
      }
  }
  ```

- [ ] **Step 3: Commit**

  ```bash
  git add style.css
  git commit -m "style: add news-rows CSS block"
  ```

---

### Task 2: Rewrite `archive.php`

**Files:**
- Modify: `archive.php`

Replace the entire file. The outer structure (header dark band, `.section-light` wrapper) is preserved. The post loop switches from `<ul>/<li>` to `<div>/<article>`. The container changes from `.container` (720px) to `.container-wide` (1200px) to give the 55/45 image-text split adequate room.

- [ ] **Step 1: Replace `archive.php` with the new template**

  ```php
  <?php get_header(); ?>

  <main class="news-archive">
      <div class="news-archive__header section-dark">
          <div class="container">
              <h1 class="news-archive__title">
                  <?php
                  if ( is_category() ) {
                      single_cat_title();
                  } elseif ( is_tag() ) {
                      single_tag_title();
                  } else {
                      echo 'News';
                  }
                  ?>
              </h1>
          </div>
      </div>

      <div class="section-light">
          <div class="container-wide">
              <?php if ( have_posts() ) : ?>
                  <div class="news-rows">
                      <?php $i = 0; while ( have_posts() ) : the_post(); ?>
                          <article class="news-row<?php echo ( $i % 2 !== 0 ) ? ' news-row--reverse' : ''; ?>">

                              <?php if ( has_post_thumbnail() ) : ?>
                                  <?php the_post_thumbnail( 'full', [
                                      'class' => 'news-row__img',
                                      'alt'   => esc_attr( get_the_title() ),
                                  ] ); ?>
                              <?php else : ?>
                                  <div class="news-row__img-placeholder" aria-hidden="true"></div>
                              <?php endif; ?>

                              <div class="news-row__body">
                                  <time class="news-row__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                      <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                                  </time>
                                  <a href="<?php the_permalink(); ?>" class="news-row__title">
                                      <?php the_title(); ?>
                                  </a>
                                  <a href="<?php the_permalink(); ?>" class="news-row__link" aria-hidden="true" tabindex="-1">
                                      Read &rarr;
                                  </a>
                              </div>

                          </article>
                      <?php $i++; endwhile; ?>
                  </div>

                  <div class="archive-pagination">
                      <?php
                      the_posts_pagination( [
                          'prev_text' => '&larr; Previous',
                          'next_text' => 'Next &rarr;',
                      ] );
                      ?>
                  </div>

              <?php else : ?>
                  <p class="archive-empty">No posts found.</p>
              <?php endif; ?>
          </div>
      </div>
  </main>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Load the archive page in your browser and confirm it renders**

  - Start your local environment (Docker or Local by Flywheel — see CLAUDE.md)
  - Navigate to the News/blog archive page
  - Expected: staggered rows visible, images showing, alternation working (row 1 image left, row 2 image right, etc.)
  - If a post has no featured image: a cream/surface-coloured block appears in place of the image

- [ ] **Step 3: Commit**

  ```bash
  git add archive.php
  git commit -m "feat: rewrite archive page with cinematic staggered row layout"
  ```

---

### Task 3: Remove the old `.news-list` CSS

**Files:**
- Modify: `style.css` (lines ~677–723, the `.news-list` block)

Now that `archive.php` no longer emits `.news-list` markup, the old styles are dead code.

- [ ] **Step 1: Delete the `.news-list` block from `style.css`**

  In `style.css`, find and delete the old `.news-list` block — it sits **after** the new `.news-rows` block you added in Task 1. Delete these rules exactly (leave the new `.news-rows` block above it untouched):

  ```css
  .news-list {
      padding-block: var(--space-xl);
      border-top: 1px solid var(--color-surface);
  }

  .news-list__item {
      display: grid;
      grid-template-columns: 180px 1fr;
      gap: var(--space-lg);
      padding-block: var(--space-lg);
      border-bottom: 1px solid var(--color-surface);
  }

  .news-list__date {
      font-size: 0.75rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--color-muted);
      padding-top: 0.3em;
  }

  .news-list__title {
      display: block;
      font-family: var(--font-display);
      font-size: clamp(1.25rem, 2.5vw, 1.75rem);
      font-weight: 400;
      color: var(--color-text);
      transition: color 0.2s ease;
  }

  .news-list__title:hover {
      color: var(--color-accent);
  }

  .news-list__excerpt {
      margin-top: var(--space-xs);
      font-size: 0.9375rem;
      color: var(--color-muted);
      line-height: 1.6;
  }

  @media (max-width: 768px) {
      .news-list__item {
          grid-template-columns: 1fr;
          gap: var(--space-xs);
      }
  }
  ```

- [ ] **Step 2: Reload the archive page and confirm nothing is broken**

  The page should look identical to how it looked after Task 2.

- [ ] **Step 3: Commit**

  ```bash
  git add style.css
  git commit -m "style: remove deprecated news-list CSS"
  ```

---

### Task 4: Visual QA checklist

No code changes — work through this checklist in the browser. Fix anything that fails before marking done.

- [ ] **Desktop (> 1024px)**
  - [ ] Row 1: image on the left
  - [ ] Row 2: image on the right
  - [ ] Row 3: image on the left (alternation continues correctly)
  - [ ] Hovering a row turns the title terracotta (`#C1633A`) — no other movement
  - [ ] A post without a featured image shows a cream block, not a broken image
  - [ ] A portrait image taller than 500px is cropped, not stretched or overflowing

- [ ] **Tablet (768px – 1024px)** — use browser DevTools to resize
  - [ ] Image column narrows to ~50%; rows remain side-by-side
  - [ ] Text padding feels comfortable (not cramped)

- [ ] **Mobile (< 768px)**
  - [ ] Rows stack: image on top, text below
  - [ ] No alternation — all rows are image-top
  - [ ] Image is full-width at natural ratio (no fixed crop)
  - [ ] Title is slightly smaller (18px / 1.125rem)

- [ ] **Pagination** (if you have enough posts)
  - [ ] "← Previous" / "Next →" links appear below the rows

- [ ] **Push to remote**

  ```bash
  git push
  ```
