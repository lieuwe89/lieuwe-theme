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

Classic WordPress template hierarchy. All styles are in `style.css` (no preprocessor). One vanilla JS file handles nav toggle and scroll behavior.

**Template → content type mapping:**

| Template | Content |
|---|---|
| `front-page.php` | Homepage: video hero, intro, portfolio preview, news preview |
| `archive-portfolio.php` | Portfolio grid (2-col desktop, 1-col mobile) |
| `single-portfolio.php` | Single portfolio item |
| `archive.php` | News/blog listing |
| `single.php` | Single news/blog post |
| `page.php` | Static pages (About, Contact) |
| `search.php` | Search results |
| `404.php` | 404 page |
| `header.php` / `footer.php` | Sitewide chrome |

**`functions.php` registers:**
- Theme support (title-tag, post-thumbnails, html5)
- Two nav menus: `menu-1` (primary) and `footer`
- `portfolio_item` custom post type (URL slug: `/portfolio/`) — note: post type name is `portfolio_item`, not `portfolio`
- Stylesheet + main.js enqueue; fonts are self-hosted woff2 files in `assets/fonts/`
- Customizer section delegated to `inc/customizer.php` (hero video/image source)

## Design system

CSS custom properties (defined in `:root` in `style.css`):

| Token | Value | Use |
|---|---|---|
| `--color-bg` | `#F5F0E8` | Light cream background |
| `--color-surface` | `#EDE8DC` | Cards, alternate sections |
| `--color-bg-dark` | `#1A1612` | Dark sections, footer, hero |
| `--color-text` | `#1C1917` | Body text on light |
| `--color-text-light` | `#F5F0E8` | Body text on dark |
| `--color-muted` | `#6B6560` | Secondary text |
| `--color-accent` | `#C1633A` | Links, dates, decorative |
| `--font-heading` | Playfair Display | H1–H6, hero title, blockquotes |
| `--font-body` | DM Sans | Body, nav, labels |

Layout utilities: `.container` (720px max), `.container-wide` (1200px max), `.section-spacing` (6rem vertical), `.bg-dark`, `.bg-light`, `.bg-surface`.

The site alternates light and dark sections for visual rhythm. The header is fixed and gains `.scrolled-dark` (dark bg) via JS after 100px of scroll.

## Docs

- `docs/superpowers/specs/2026-04-03-wordpress-theme-design.md` — full visual design spec (colors, typography, layout principles, page-by-page breakdown, edge cases)
- `docs/superpowers/plans/2026-04-03-wordpress-theme.md` — implementation plan with file map and task checklist
