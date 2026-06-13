# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Custom classic PHP WordPress theme for [lieuwejongsma.nl](https://lieuwejongsma.nl) — a personal portfolio site for a curator, historian, and craftsman. No page builder. No framework dependencies. All CSS lives in `style.css`, all JS in `assets/js/main.js`.

## Local development

Two options are documented in this project:

**Option A — Docker (docker-compose.yml in repo root):**
```bash
docker-compose up
# WordPress at http://localhost:8080
# Theme auto-mounted at /var/www/html/wp-content/themes/lieuwe-theme
```

**Option B — Local by Flywheel (preferred, per the implementation plan):**
```bash
# After creating a Local site at ~/Local Sites/lieuwe-theme-dev/
ln -s /Users/lieuwejongsma/projects/lieuwe-theme \
  ~/Local\ Sites/lieuwe-theme-dev/app/public/wp-content/themes/lieuwe-theme
# Admin: http://lieuwe-theme-dev.local/wp-admin
```

There is no build step — edits to PHP/CSS/JS take effect immediately on page reload.

## Architecture

Classic WordPress template hierarchy. All styles are in `style.css` (no preprocessor). One vanilla JS file (`assets/js/main.js`) handles the nav toggle, the header scroll state, and IntersectionObserver scroll-reveal on the homepage and services pages.

**Template → content type mapping:**

| Template | Content |
|---|---|
| `front-page.php` | Homepage: video hero, intro, portfolio preview, news preview |
| `archive.php` | News/blog listing |
| `single.php` | Single news/blog post |
| `archive-publication.php` | Publications listing (`/writing/`) |
| `single-publication.php` | Single publication |
| `page.php` | Static pages (About, Contact) |
| `page-services.php` | Services page |
| `search.php` | Search results |
| `404.php` | 404 page |
| `header.php` / `footer.php` | Sitewide chrome |

> Portfolio (`portfolio_item` CPT) archive and single views are owned by the **Portfolio Canvas plugin**, not the theme — there are no `archive-portfolio.php` / `single-portfolio.php` files. The theme only renders the homepage portfolio *preview* in `front-page.php`.

**`functions.php` registers:**
- Theme support (title-tag, post-thumbnails, html5)
- Two nav menus: `menu-1` (primary) and `footer`
- Theme-side meta box for the `portfolio_item` CPT — note: the CPT itself is registered by the **Portfolio Canvas plugin**, not the theme (`functions.php` only adds the meta box). Post type name is `portfolio_item`, not `portfolio`
- `publication` custom post type (URL slug: `/writing/`) and its archive/single rendering (see `inc/publications.php`)
- Stylesheet + main.js enqueue; fonts are self-hosted woff2 (Sorts Mill Goudy + Jost) in `assets/fonts/` — no Google Fonts CDN
- Customizer section delegated to `inc/customizer.php` (hero video/image source)

## Design system

CSS custom properties (defined in `:root` in `style.css`). The palette is a warm tonal range expressed in **oklch** — everything lives in the light; there is no dark mode:

| Token | Value | Use |
|---|---|---|
| `--color-bg` | `oklch(96% 0.012 79)` | Natural linen — page background |
| `--color-surface` | `oklch(93% 0.016 72)` | Ochre-tinted surface — cards, alt sections |
| `--color-warm` | `oklch(89% 0.022 67)` | Dusty ochre — portfolio preview, archive headers |
| `--color-blush` | `oklch(92% 0.020 52)` | Terracotta wash — news archive header |
| `--color-hero-empty` | `oklch(28% 0.018 65)` | Warm dark — fallback for an empty hero |
| `--color-text` | `oklch(22% 0.012 68)` | Warm dark brown — body text |
| `--color-text-light` | `oklch(96% 0.012 79)` | Cream — text on terracotta surfaces |
| `--color-muted` | `oklch(56% 0.014 72)` | Warm medium — secondary text, captions |
| `--color-accent` | `oklch(55% 0.12 48)` | Terracotta — links, dates, decorative, footer bg |
| `--font-display` | Sorts Mill Goudy (serif) | H1–H6, hero title, blockquotes, italic accents |
| `--font-body` | Jost (geometric sans) | Body, nav, labels |

Fonts are **self-hosted** (`assets/fonts/`, `@font-face` in `style.css`) — no Google Fonts CDN. Jost is a variable font (weight axis 100–900); Goudy ships static regular + italic.

Spacing scale: `--space-xs … --space-xl` (0.5 / 1 / 2 / 4 / 8 rem), tightened at the tablet/mobile breakpoints. `--nav-height: 64px`.

Layout & section utilities:
- `.container` (max **1200px**), `.container--narrow` (max **720px**), `.container-wide` (max 1200px)
- `.section-light` (bg), `.section-dark` (warm surface — *not* charcoal; "dark" is historical naming), `.section-terracotta` (accent bg, cream text)

The site builds rhythm by alternating warm tones (bg → surface → warm → blush → terracotta), not light-vs-dark. The header is fixed and transparent over the hero; JS adds `.is-scrolled` (cream bg + dark text) once scrolled past ~80% of the hero height (or after 80px on pages without a hero).

## Versioning

The theme version is set in the `Version:` field at the top of `style.css`. **Always bump this before committing and pushing any meaningful change.**

Use semantic versioning:
- `1.0.0 → 1.1.0` — new features or bug fixes
- `1.1.0 → 1.1.1` — minor tweaks or copy changes

**After committing a version bump, always create a matching annotated git tag:**

```bash
git tag -a v1.2.3 HEAD -m "Release v1.2.3 — short description of changes"
git push origin main --tags
```

GitHub creates a downloadable `.zip` for every tag automatically (under Releases → Tags). This is what gets uploaded to WordPress.

**Why this matters:** The live site is deployed by downloading a zip from GitHub and uploading it via WordPress admin (Appearance → Themes → Upload Theme). If the version number is unchanged, WordPress will not replace existing theme files — meaning new files will never reach the server. Tags make each deployable version permanently addressable.

## Publications page (`/writing/`)

Theme registers a `publication` CPT (URL slug: `/writing/`). The archive template auto-appends a "Writing" item to the primary menu when an archive URL exists. For full control, add a proper menu item via **Appearance → Menus** once you have publications.

Hero copy is editable via **Appearance → Customize → Publications page** (two title lines + intro paragraph).

PDF previews use self-hosted PDF.js v4.7.76 (`assets/js/vendor/pdf.min.mjs` + worker). They lazy-load on first row expand.

## Docs

- `docs/superpowers/specs/2026-04-03-wordpress-theme-design.md` — full visual design spec (colors, typography, layout principles, page-by-page breakdown, edge cases)
- `docs/superpowers/plans/2026-04-03-wordpress-theme.md` — implementation plan with file map and task checklist
