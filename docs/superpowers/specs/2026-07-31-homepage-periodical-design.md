# Homepage below-hero redesign — "The Periodical"

**Date:** 2026-07-31
**Status:** Approved direction (mockup: `.superpowers/brainstorm/872-1785527821/content/periodical-v2.html`)

## Goal

The hero is strong; everything below it is generic (intro + portfolio grid + news list). Rebuild the below-hero homepage as a periodical front page that shows the breadth of the site — craft, writing, publications, teaching, services — in one editorial composition. Same palette, same type, same copy. The hero is untouched.

## What stays

- Hero section: unchanged, byte for byte.
- Intro copy: the user's existing text, verbatim, from the front page's post content. No copy rewrites anywhere — all visible text is either existing content or short functional labels ("Meanwhile", "From the workshop").
- Design tokens: existing custom properties in `style.css` (bg / surface / warm / blush / accent, Goudy + Jost). No new colors, no new fonts.
- Scroll-reveal behavior (`.home-reveal` / IntersectionObserver) applied to the new sections the same way it is today.

## Page structure (top to bottom, all inside `front-page.php`)

### 1. Intro — slimmed

Centered, `container--narrow`, on `--color-bg`. Renders the front page post content as today, **but** the template no longer gives images special prominence; the editor should remove the large tent photo from the page content (it moves to the Teaching block). Eyebrow line ("Craftsman · Historian · …") comes from the existing content/tagline as authored.

### 2. Lead story + "Meanwhile" rail

Two-column grid (8/4) in `.container`, on `--color-bg`.

**Lead (left):**
- Eyebrow: `Latest — {date}` (terracotta, Jost caps, above a 2px dark rule).
- Title in Goudy, ~clamp(1.6rem, 3vw, 2.2rem), links to the item.
- Featured image (large size), then excerpt (`get_the_excerpt`, default trim), then a `Read on` link in the existing `.home-section-link` style.

**Lead selection:** a `_lieuwe_lead` meta checkbox ("Show as homepage lead") available on `post`, `portfolio_item`, and `publication` edit screens — same meta-box pattern as the existing `_lieuwe_featured` box in `functions.php`. Query: newest item across those three types with `_lieuwe_lead` set; if none, fall back to the newest `post`. If several are flagged, newest flagged wins (no warning UI).

**Meanwhile rail (right):**
- Left border separator, heading "Meanwhile" above a matching 2px rule.
- 4 items: newest across `post`, `portfolio_item`, `publication` by date, excluding the lead. Each item: title (Goudy, ~0.95rem) + type label line (Jost caps, muted): `News · 10 June` / `Portfolio` / `Publication`. Dates shown only for news.
- Footer link "All news" → posts page.
- Links: news → permalink; publication → permalink; portfolio → canvas URL + `#item-{ID}` (existing pattern).

### 3. "From the workshop" filmstrip

Full-width band on `--color-surface`, `.container` inside.
- Header row: eyebrow "From the workshop" left, "View all work" link right (→ canvas page).
- 4 portfolio items, featured-first ordering (reuse the existing two-query merge in `front-page.php`).
- Each: image (fixed-height, `object-fit: cover`) + title below in Goudy. Video-only items keep the existing `portfolio-card__video-thumb` handling.
- If the lead or a rail item is a portfolio piece it may also appear here; no dedup (the filmstrip is a stable "shop window", not a feed).

### 4. Foot: Teaching + Services blocks

Two equal blocks side by side, full-bleed color, no container gap between them.

**Teaching (left):** `--color-accent` background, cream text, the tent photo (`DSC02469`, set via Customizer — see below) as a low-opacity (≈0.25) cover image behind the text. Eyebrow "Teaching", title "Courses & workshops", link → `get_post_type_archive_link( 'teaching_event' )`.
**Fallback:** when the teaching plugin is inactive the archive link returns false → hide the block and let Services span full width.

**Services (right):** `--color-blush` background, dark text. Eyebrow "Services", title + link → the page using `page-services.php` (lookup by `_wp_page_template`, same as the canvas-page lookup). If no services page exists, hide; if both blocks are missing, the whole foot section disappears.

**Customizer:** one new setting in the existing theme Customizer section — "Homepage teaching image" (media upload) for the Teaching block background. Empty = plain terracotta.

## Responsive

- ≤ 900px: lead + rail stack (rail below lead, borders switch from left rule to top rules); filmstrip 2×2; foot blocks stack full-width.
- Existing spacing-scale tightening at the theme's breakpoints applies; no new breakpoints beyond the ones already in `style.css`.

## Implementation notes

- Files touched: `front-page.php` (rewrite below hero), `style.css` (replace `.home-portfolio` / `.home-news` styles with the new sections; keep `.home-reveal` machinery), `functions.php` (extend meta-box registration to the `_lieuwe_lead` checkbox on three post types), `inc/customizer.php` (teaching image setting).
- No new files, no JS changes (`main.js` reveal targets updated by selector only if needed).
- Old `.home-portfolio` / `.home-news` CSS gets deleted, not deprecated.
- Version bump in `style.css` in the first implementation commit; tag once at the end, per project convention.

## Out of scope

- Hero, header, footer, all other templates.
- Copy changes of any kind.
- The annual-rings prototype (rejected as too different).
