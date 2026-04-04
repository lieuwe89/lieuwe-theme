# lieuwe-theme

Custom classic PHP WordPress theme for [lieuwejongsma.nl](https://lieuwejongsma.nl) — a personal portfolio site for a curator, historian, and craftsman.

No page builder. No framework dependencies. No build step.

## Stack

- PHP (WordPress template hierarchy)
- Vanilla CSS (`style.css`, custom properties)
- Vanilla JS (`assets/js/main.js`)
- Self-hosted fonts: Playfair Display, DM Sans (woff2)

## Local development

**Option A — Docker:**
```bash
docker-compose up
# WordPress at http://localhost:8080
```

**Option B — Local by Flywheel (preferred):**
```bash
# Create a site at ~/Local Sites/lieuwe-theme-dev/ in Local, then:
ln -s /Users/lieuwejongsma/projects/lieuwe-theme \
  ~/Local\ Sites/lieuwe-theme-dev/app/public/wp-content/themes/lieuwe-theme
# Admin: http://lieuwe-theme-dev.local/wp-admin
```

Edits to PHP/CSS/JS take effect immediately on page reload.

## Structure

```
lieuwe-theme/
├── style.css               # All styles + theme header
├── functions.php           # Theme setup, CPT, enqueue
├── inc/
│   └── customizer.php      # Hero video/image source setting
├── assets/
│   ├── js/main.js          # Nav toggle, scroll behavior
│   └── fonts/              # Self-hosted woff2 files
├── front-page.php          # Homepage
├── archive-portfolio.php   # Portfolio grid
├── single-portfolio.php    # Single portfolio item
├── archive.php             # News/blog listing
├── single.php              # Single news/blog post
├── page.php                # Static pages (About, Contact)
├── header.php / footer.php # Sitewide chrome
├── search.php              # Search results
└── 404.php                 # 404 page
```

## Design tokens

Defined as CSS custom properties in `:root`:

| Token | Value | Use |
|---|---|---|
| `--color-bg` | `#F5F0E8` | Light cream background |
| `--color-surface` | `#EDE8DC` | Cards, alternate sections |
| `--color-bg-dark` | `#1A1612` | Footer, hero, dark sections |
| `--color-accent` | `#C1633A` | Links, dates, decorative |
| `--font-heading` | Playfair Display | H1–H6, hero, blockquotes |
| `--font-body` | DM Sans | Body, nav, labels |

## Docs

- [`docs/superpowers/specs/2026-04-03-wordpress-theme-design.md`](docs/superpowers/specs/2026-04-03-wordpress-theme-design.md) — full visual design spec
- [`docs/superpowers/plans/2026-04-03-wordpress-theme.md`](docs/superpowers/plans/2026-04-03-wordpress-theme.md) — implementation plan and task checklist
