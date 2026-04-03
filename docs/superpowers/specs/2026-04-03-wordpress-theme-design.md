# WordPress Theme Design: lieuwejongsma.nl

**Date:** 2026-04-03
**Site:** www.lieuwejongsma.nl
**Type:** Custom classic PHP WordPress theme

---

## Overview

A custom WordPress theme for the personal portfolio of Lieuwe Jongsma — curator, historian, and craftsman. The goal is a fresh visual direction: cinematic, editorial, and warm. No page builder dependency. All existing content (posts, pages, portfolio items) remains compatible without migration.

---

## Visual Design

### Color Palette

| Role | Token | Value |
|---|---|---|
| Background (light) | `--color-bg` | `#F5F0E8` |
| Surface (cards, sections) | `--color-surface` | `#EDE8DC` |
| Background (dark) | `--color-bg-dark` | `#1A1612` |
| Text (light sections) | `--color-text` | `#1C1917` |
| Text (dark sections) | `--color-text-light` | `#F5F0E8` |
| Muted text | `--color-muted` | `#6B6560` |
| Accent | `--color-accent` | `#C1633A` |

The site alternates between light (warm cream) and dark (deep warm charcoal) sections for visual rhythm and contrast. The hero is always dark/cinematic.

### Typography

- **Headings:** Playfair Display — high stroke contrast, editorial, dramatic. Loaded via Google Fonts.
- **Body:** DM Sans — clean, modern, highly readable sans-serif. Loaded via Google Fonts.
- **Nav links:** DM Sans in small caps, spaced tracking.
- **Hero title:** Playfair Display, very large scale (clamp-based fluid type), loose letter-spacing — poster-like presence.

### Layout Principles

- Generous whitespace throughout.
- Body content constrained to ~720px max-width for readability.
- Portfolio grid: 2 columns on desktop, 1 on mobile.
- Full-bleed images and hero sections.
- Light/dark section alternation creates visual rhythm without relying on color complexity.

---

## Architecture

Classic PHP WordPress theme. No page builder. No layout plugin dependencies.

### File Structure

```
lieuwe-theme/
├── style.css                  # Theme declaration + base styles
├── functions.php              # Enqueue scripts/styles, menus, theme support
├── index.php                  # Fallback template
├── front-page.php             # Homepage
├── page.php                   # Generic static page (About, Contact)
├── archive.php                # News/blog listing
├── single.php                 # Single news/blog post
├── archive-portfolio.php      # Portfolio grid
├── single-portfolio.php       # Single portfolio item
├── search.php                 # Search results
├── 404.php                    # 404 error page
├── header.php                 # Sitewide header + navigation
├── footer.php                 # Sitewide footer
└── assets/
    ├── css/                   # Compiled/processed stylesheets
    ├── js/                    # Scripts (nav toggle, video handling)
    └── fonts/                 # Self-hosted fonts if needed
```

### Content Compatibility

All existing WordPress content maps directly to theme templates:

| Content type | Template |
|---|---|
| Static pages (About, Contact) | `page.php` |
| Blog/news posts | `single.php`, `archive.php` |
| Portfolio items (custom post type) | `single-portfolio.php`, `archive-portfolio.php` |
| Search | `search.php` |
| 404 | `404.php` |

No content migration required. The theme reads standard WordPress data.

---

## Page Templates

### Homepage (`front-page.php`)

1. **Hero** — full-screen video (autoplay, muted, looped) with a dark overlay. Falls back to a static full-bleed image until video is available. Site name and tagline ("Curator, historian and craftsman") centered in white Playfair Display.
2. **Intro** — brief paragraph in the light cream section below the hero.
3. **Portfolio preview** — 3 featured portfolio items in a full-width grid.
4. **News preview** — 2–3 latest posts in a simple list with date.

The video source is set via the WordPress Customizer (a theme setting) — switching from image to video requires no code change.

### Portfolio Archive (`archive-portfolio.php`)

2-column grid on desktop, 1 column on mobile. Large featured images, title overlay on hover. Clicking through to single item.

### Single Portfolio Item (`single-portfolio.php`)

Full-bleed hero image at top. Content in a constrained centered column (~720px) below — mix of text and images as entered in the WordPress editor.

### News Archive (`archive.php`)

Clean editorial list: date, title, excerpt. Minimal, no sidebar.

### Single Post (`single.php`)

Centered column, generous line-height (~1.8). Large featured image at top. No sidebar.

### Navigation

- Fixed top bar: site name/logo left, menu links right in small caps.
- On scroll past hero: bar gains a subtle warm dark background to remain readable over both light and dark sections.
- Mobile: hamburger icon → full-screen overlay menu.

### Footer

Dark charcoal (`#1A1612`), simple layout: name left, nav links center, Instagram + copyright right.

### 404 Page

Dark section. Large "404" in Playfair Display. Short message. Link back to homepage.

---

## Edge Cases

- **No video:** Static full-screen image is the default hero. Video drops in via a theme setting — no code changes needed.
- **No featured image:** Portfolio and news items without a featured image fall back to a plain dark or cream title section. No broken layouts.
- **Long titles / short excerpts:** Typography tested against real existing content at edge lengths.
- **Empty archive:** If portfolio or news has no posts, a simple message is shown rather than an empty grid.

---

## Testing Plan

- Cross-browser: Chrome, Firefox, Safari (desktop and mobile).
- All existing content types reviewed with real data before launch.
- Responsive breakpoints: mobile (< 768px), tablet (768–1024px), desktop (> 1024px).
- Video hero fallback verified with and without video source set.
- Navigation tested on mobile (hamburger overlay).

---

## Out of Scope

- Video production (to be handled separately).
- E-commerce or booking functionality.
- Any new content types beyond existing portfolio + blog + static pages.
- Backend/CMS changes — theme only.
