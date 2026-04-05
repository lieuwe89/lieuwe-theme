# Blog Archive Redesign — Design Spec

**Date:** 2026-04-05
**Scope:** `archive.php` and the related CSS block in `style.css`

---

## Goal

Replace the current date-column list with a cinematic staggered-row layout. Each post occupies a full-width horizontal row: featured image on one side, date + title + read link on the other, alternating left/right down the page. Inspired by vitaarchitecture.com/journal — adapted to the existing lieuwe-theme design language.

---

## Layout

### Row structure

Each post renders as a `.news-row` flex container:

- **Odd rows** (1st, 3rd, 5th…): image left, text right
- **Even rows** (2nd, 4th, 6th…): image right, text left — via `.news-row--reverse` (or CSS `:nth-child(even)` + `flex-direction: row-reverse`)
- A thin 1px `--color-surface` border separates rows

### Image column

- Width: **55%** of the row
- Aspect ratio: **3 / 2**
- `object-fit: cover`, no caption
- If the post has no featured image: render a solid `--color-surface` block at the same dimensions (no alt text placeholder, no icon)

### Text column

- Width: **45%** (flex: 1)
- Vertically centred within the row
- Contents, top to bottom:
  1. **Date** — DM Sans, 11px, uppercase, `letter-spacing: 0.1em`, `--color-accent`
  2. **Title** — Playfair Display, 20px, weight 400, `--color-text`, line-height 1.4; the entire title is a link to the post
  3. **Read link** — "Read →", DM Sans, 11px, uppercase, `letter-spacing: 0.08em`, `--color-accent`; shown below the title with ~10px gap
- Padding: 40px 48px (desktop)
- No excerpt is shown

### Hover state

- On row hover: title colour shifts to `--color-accent`; a smooth `color` transition (0.2s ease)
- No other movement or shadow

---

## Page header

Unchanged from current: dark band (`.section-dark`, `--color-bg-dark` background), `<h1>` in Playfair Display with the dynamic title ("News", or category/tag name). No subtitle line.

---

## Responsive behaviour

### Tablet (≤ 1024px)

- Image width narrows to 50%; text padding reduces to 28px 32px
- Alternation continues

### Mobile (≤ 768px)

- Rows stack vertically: image on top, text below
- Alternation is dropped — all rows are image-top, text-bottom
- Image: full width, aspect ratio 16/9
- Text padding: 20px 16px
- Title font size: 18px

---

## Pagination

Unchanged — `.archive-pagination` below the rows, same styles as now.

---

## CSS changes

**Remove** (or supersede) the current `.news-list` block:
- `.news-list`, `.news-list__item`, `.news-list__date`, `.news-list__body`, `.news-list__title`, `.news-list__excerpt`

**Add** a new `.news-rows` block with:
- `.news-rows` — container (no extra padding; rows are full-width within `.container`)
- `.news-row` — flex row, border-bottom
- `.news-row--reverse` — `flex-direction: row-reverse` (or use `:nth-child(even)`)
- `.news-row__img` — the `<img>` or placeholder `<div>`
- `.news-row__img-placeholder` — fallback block, `background: var(--color-surface)`
- `.news-row__body` — text column
- `.news-row__date` — date label
- `.news-row__title` — Playfair heading-link
- `.news-row__link` — "Read →" label

---

## PHP changes (`archive.php`)

- Replace `<ul class="news-list">` / `<li class="news-list__item">` structure with `<div class="news-rows">` / `<article class="news-row [news-row--reverse]">` structure
- Alternate the `news-row--reverse` class using a counter (`$i % 2 === 0`)
- Use `has_post_thumbnail()` to conditionally render the image or the placeholder div
- The entire title renders as `<a href="<?php the_permalink(); ?>">` — no separate "Read →" href needed (both point to the same URL)

---

## Out of scope

- Single post template (`single.php`) — not changed
- Homepage news preview — not changed
- Category/tag filter UI — not changed
- Any JS changes
