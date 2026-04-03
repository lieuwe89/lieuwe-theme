# WordPress Theme Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a custom classic PHP WordPress theme for lieuwejongsma.nl — cinematic dark/warm-cream design, full-screen video hero, Playfair Display headings, all existing content compatible without migration.

**Architecture:** Classic PHP WordPress theme, no page builder. All CSS lives in `style.css`, built incrementally per component. JS is a single vanilla file. Customizer settings control hero media. Portfolio CPT is registered conditionally (skipped if a plugin already registers it).

**Tech Stack:** PHP 8+, CSS3 (custom properties, grid, flexbox), vanilla JS, WordPress 6.4+, Playfair Display + DM Sans (self-hosted woff2 for GDPR compliance).

---

## File Map

| File | Responsibility |
|---|---|
| `style.css` | Theme declaration header + all CSS (built incrementally) |
| `functions.php` | Theme support, menu registration, enqueue, CPT registration |
| `inc/customizer.php` | Customizer settings for hero + helper functions |
| `header.php` | `<head>`, fixed nav bar, hamburger button |
| `footer.php` | Footer markup + `wp_footer()` |
| `index.php` | WordPress fallback template |
| `front-page.php` | Homepage: hero, intro, portfolio preview, news preview |
| `page.php` | Generic static page (About, Contact) |
| `archive.php` | News/blog listing |
| `single.php` | Single news/blog post |
| `archive-portfolio.php` | Portfolio grid (2 columns) |
| `single-portfolio.php` | Single portfolio item |
| `search.php` | Search results |
| `404.php` | 404 error page |
| `assets/fonts/` | Self-hosted woff2 font files |
| `assets/js/main.js` | Hamburger nav toggle + scroll-triggered nav background |

---

## Task 1: Local WordPress environment

**Files:** none (environment setup)

- [ ] **Step 1: Install Local by Flywheel**

  Download from https://localwp.com and install. Open it and create a new WordPress site:
  - Site name: `lieuwe-theme-dev`
  - PHP: 8.2
  - WordPress: latest

- [ ] **Step 2: Symlink theme directory into Local's WordPress**

  Find your Local site's themes directory. It will be at a path like:
  `~/Local Sites/lieuwe-theme-dev/app/public/wp-content/themes/`

  Symlink your repo into it:
  ```bash
  ln -s /Users/lieuwejongsma/projects/lieuwe-theme \
    ~/Local\ Sites/lieuwe-theme-dev/app/public/wp-content/themes/lieuwe-theme
  ```

- [ ] **Step 3: Import existing content (optional but recommended)**

  In your live WordPress admin go to Tools → Export → All content. Download the XML.
  In Local WP admin go to Tools → Import → WordPress → upload the XML. This lets you test with real content.

- [ ] **Step 4: Verify environment**

  Open Local's admin URL (e.g. `http://lieuwe-theme-dev.local/wp-admin`). Log in. Go to Appearance → Themes — you won't see the theme yet (no `style.css` with theme header). That's expected. Continue to Task 2.

---

## Task 2: Theme scaffold — style.css header + file structure

**Files:**
- Create: `style.css`
- Create: `index.php`
- Create: `functions.php` (stub)
- Create: `inc/customizer.php` (stub)
- Create: `header.php` (stub)
- Create: `footer.php` (stub)
- Create: `front-page.php` (stub)
- Create: `page.php` (stub)
- Create: `archive.php` (stub)
- Create: `single.php` (stub)
- Create: `archive-portfolio.php` (stub)
- Create: `single-portfolio.php` (stub)
- Create: `search.php` (stub)
- Create: `404.php` (stub)
- Create: `assets/js/main.js` (stub)

- [ ] **Step 1: Create directory structure**

  ```bash
  cd /Users/lieuwejongsma/projects/lieuwe-theme
  mkdir -p inc assets/fonts assets/js
  ```

- [ ] **Step 2: Create style.css with theme header**

  Create `style.css`:
  ```css
  /*
  Theme Name: Lieuwe Jongsma
  Author: Lieuwe Jongsma
  Description: Custom portfolio theme for lieuwejongsma.nl
  Version: 1.0.0
  License: GNU General Public License v2 or later
  Text Domain: lieuwe-theme
  */
  ```

- [ ] **Step 3: Create stub PHP files**

  `index.php`:
  ```php
  <?php get_header(); ?>
  <main><p>Index fallback.</p></main>
  <?php get_footer(); ?>
  ```

  `functions.php`:
  ```php
  <?php
  // Theme setup — full implementation in Task 4
  ```

  `inc/customizer.php`:
  ```php
  <?php
  // Customizer — full implementation in Task 5
  ```

  `header.php`:
  ```php
  <!DOCTYPE html>
  <html <?php language_attributes(); ?>>
  <head>
      <meta charset="<?php bloginfo('charset'); ?>">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
  <?php wp_body_open(); ?>
  <header class="site-header" id="site-header">stub</header>
  ```

  `footer.php`:
  ```php
  <footer class="site-footer">stub</footer>
  <?php wp_footer(); ?>
  </body>
  </html>
  ```

  `front-page.php`, `page.php`, `archive.php`, `single.php`, `archive-portfolio.php`, `single-portfolio.php`, `search.php`, `404.php` — all the same stub:
  ```php
  <?php get_header(); ?>
  <main><p><?php echo basename(__FILE__); ?> stub</p></main>
  <?php get_footer(); ?>
  ```

  `assets/js/main.js`:
  ```js
  // Navigation — full implementation in Task 7
  ```

- [ ] **Step 4: Verify theme is visible in WordPress admin**

  Go to Appearance → Themes in Local WP admin. You should see "Lieuwe Jongsma" as an available theme. Activate it. Visit the front end — you'll see stub text. Expected.

- [ ] **Step 5: Commit**

  ```bash
  git add -A
  git commit -m "feat: theme scaffold — all stub files, theme activates"
  ```

---

## Task 3: Self-host fonts

**Files:**
- Create: `assets/fonts/PlayfairDisplay-Regular.woff2`
- Create: `assets/fonts/PlayfairDisplay-Italic.woff2`
- Create: `assets/fonts/PlayfairDisplay-Bold.woff2`
- Create: `assets/fonts/DMSans-Regular.woff2`
- Create: `assets/fonts/DMSans-Medium.woff2`

- [ ] **Step 1: Download font files**

  Go to https://fonts.google.com/specimen/Playfair+Display — click "Get font" → "Download all".
  Unzip. From the static/woff2 folder, copy:
  - `PlayfairDisplay-Regular.woff2`
  - `PlayfairDisplay-Italic.woff2`
  - `PlayfairDisplay-Bold.woff2`

  Go to https://fonts.google.com/specimen/DM+Sans — repeat. Copy:
  - `DMSans-Regular.woff2`
  - `DMSans-Medium.woff2`

  Place all 5 files in `assets/fonts/`.

- [ ] **Step 2: Add @font-face declarations to style.css**

  Append to `style.css` after the theme header comment:
  ```css
  /* ==========================================================================
     Fonts
     ========================================================================== */

  @font-face {
      font-family: 'Playfair Display';
      src: url('assets/fonts/PlayfairDisplay-Regular.woff2') format('woff2');
      font-weight: 400;
      font-style: normal;
      font-display: swap;
  }

  @font-face {
      font-family: 'Playfair Display';
      src: url('assets/fonts/PlayfairDisplay-Italic.woff2') format('woff2');
      font-weight: 400;
      font-style: italic;
      font-display: swap;
  }

  @font-face {
      font-family: 'Playfair Display';
      src: url('assets/fonts/PlayfairDisplay-Bold.woff2') format('woff2');
      font-weight: 700;
      font-style: normal;
      font-display: swap;
  }

  @font-face {
      font-family: 'DM Sans';
      src: url('assets/fonts/DMSans-Regular.woff2') format('woff2');
      font-weight: 400;
      font-style: normal;
      font-display: swap;
  }

  @font-face {
      font-family: 'DM Sans';
      src: url('assets/fonts/DMSans-Medium.woff2') format('woff2');
      font-weight: 500;
      font-style: normal;
      font-display: swap;
  }
  ```

- [ ] **Step 3: Add CSS custom properties and base reset to style.css**

  Append to `style.css`:
  ```css
  /* ==========================================================================
     Design System
     ========================================================================== */

  :root {
      --color-bg:         #F5F0E8;
      --color-surface:    #EDE8DC;
      --color-bg-dark:    #1A1612;
      --color-text:       #1C1917;
      --color-text-light: #F5F0E8;
      --color-muted:      #6B6560;
      --color-accent:     #C1633A;

      --font-display: 'Playfair Display', Georgia, serif;
      --font-body:    'DM Sans', system-ui, sans-serif;

      --max-width:      720px;
      --max-width-wide: 1200px;

      --space-xs:  0.5rem;
      --space-sm:  1rem;
      --space-md:  2rem;
      --space-lg:  4rem;
      --space-xl:  8rem;

      --nav-height: 64px;
  }

  /* ==========================================================================
     Reset
     ========================================================================== */

  *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
  }

  html {
      scroll-behavior: smooth;
  }

  body {
      background-color: var(--color-bg);
      color: var(--color-text);
      font-family: var(--font-body);
      font-size: 1rem;
      line-height: 1.7;
      -webkit-font-smoothing: antialiased;
  }

  img {
      display: block;
      max-width: 100%;
      height: auto;
  }

  a {
      color: inherit;
      text-decoration: none;
  }

  ul {
      list-style: none;
  }

  /* ==========================================================================
     Typography
     ========================================================================== */

  h1, h2, h3, h4, h5, h6 {
      font-family: var(--font-display);
      font-weight: 700;
      line-height: 1.15;
  }

  h1 { font-size: clamp(2.5rem, 6vw, 5rem); }
  h2 { font-size: clamp(1.75rem, 4vw, 3rem); }
  h3 { font-size: clamp(1.25rem, 3vw, 2rem); }

  p + p {
      margin-top: 1rem;
  }

  /* ==========================================================================
     Layout Utilities
     ========================================================================== */

  .container {
      width: 100%;
      max-width: var(--max-width-wide);
      margin-inline: auto;
      padding-inline: var(--space-md);
  }

  .container--narrow {
      max-width: var(--max-width);
  }

  .section-light {
      background-color: var(--color-bg);
      color: var(--color-text);
  }

  .section-dark {
      background-color: var(--color-bg-dark);
      color: var(--color-text-light);
  }
  ```

- [ ] **Step 4: Verify fonts load**

  Reload the site in browser. Open DevTools → Network → filter "Font". You should see the woff2 files loading. Open the Console — no 404s.

- [ ] **Step 5: Commit**

  ```bash
  git add style.css assets/fonts/
  git commit -m "feat: self-host Playfair Display + DM Sans, add design system CSS"
  ```

---

## Task 4: functions.php — theme bootstrap

**Files:**
- Modify: `functions.php`

- [ ] **Step 1: Write functions.php**

  Replace the stub `functions.php` entirely:
  ```php
  <?php

  require_once get_template_directory() . '/inc/customizer.php';

  /**
   * Theme setup.
   */
  function lieuwe_setup(): void {
      add_theme_support( 'title-tag' );
      add_theme_support( 'post-thumbnails' );
      add_theme_support( 'html5', [
          'search-form', 'comment-form', 'comment-list',
          'gallery', 'caption', 'style', 'script',
      ] );

      register_nav_menus( [
          'primary' => 'Primary Menu',
          'footer'  => 'Footer Menu',
      ] );
  }
  add_action( 'after_setup_theme', 'lieuwe_setup' );

  /**
   * Enqueue styles and scripts.
   */
  function lieuwe_enqueue_assets(): void {
      wp_enqueue_style(
          'lieuwe-theme',
          get_stylesheet_uri(),
          [],
          '1.0.0'
      );

      wp_enqueue_script(
          'lieuwe-main',
          get_template_directory_uri() . '/assets/js/main.js',
          [],
          '1.0.0',
          true
      );
  }
  add_action( 'wp_enqueue_scripts', 'lieuwe_enqueue_assets' );

  /**
   * Register portfolio custom post type.
   * Skipped if already registered by a plugin (e.g. Elementor Pro).
   */
  function lieuwe_register_portfolio_cpt(): void {
      if ( post_type_exists( 'portfolio' ) ) {
          return;
      }

      register_post_type( 'portfolio', [
          'labels' => [
              'name'          => 'Portfolio',
              'singular_name' => 'Portfolio Item',
              'add_new_item'  => 'Add New Portfolio Item',
              'edit_item'     => 'Edit Portfolio Item',
              'view_item'     => 'View Portfolio Item',
              'all_items'     => 'All Portfolio Items',
          ],
          'public'       => true,
          'has_archive'  => true,
          'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
          'show_in_rest' => true,
          'rewrite'      => [ 'slug' => 'portfolio' ],
          'menu_icon'    => 'dashicons-portfolio',
      ] );
  }
  add_action( 'init', 'lieuwe_register_portfolio_cpt' );
  ```

- [ ] **Step 2: Verify in WP admin**

  - Go to Settings → Permalinks → Save Changes (flushes rewrite rules so /portfolio/ works).
  - If portfolio items exist from import: check that Appearance → Menus can see "Portfolio" as a post type.
  - In browser DevTools → Network → verify `style.css` and `main.js` load (200 status, no 404).

- [ ] **Step 3: Commit**

  ```bash
  git add functions.php
  git commit -m "feat: functions.php — theme setup, enqueue, portfolio CPT"
  ```

---

## Task 5: inc/customizer.php — hero settings + helper functions

**Files:**
- Modify: `inc/customizer.php`

- [ ] **Step 1: Write customizer.php**

  Replace the stub `inc/customizer.php`:
  ```php
  <?php

  /**
   * Register Customizer settings for the hero section.
   */
  function lieuwe_customizer_register( \WP_Customize_Manager $wp_customize ): void {
      $wp_customize->add_section( 'lieuwe_hero', [
          'title'    => 'Hero',
          'priority' => 30,
      ] );

      // Hero video URL
      $wp_customize->add_setting( 'hero_video_url', [
          'default'           => '',
          'sanitize_callback' => 'esc_url_raw',
          'transport'         => 'refresh',
      ] );
      $wp_customize->add_control( 'hero_video_url', [
          'label'       => 'Hero Video URL (MP4)',
          'description' => 'When set, displays a looping video. Leave blank to show the fallback image.',
          'section'     => 'lieuwe_hero',
          'type'        => 'url',
      ] );

      // Hero fallback image
      $wp_customize->add_setting( 'hero_image', [
          'default'           => 0,
          'sanitize_callback' => 'absint',
          'transport'         => 'refresh',
      ] );
      $wp_customize->add_control(
          new \WP_Customize_Media_Control( $wp_customize, 'hero_image', [
              'label'     => 'Hero Fallback Image',
              'section'   => 'lieuwe_hero',
              'mime_type' => 'image',
          ] )
      );
  }
  add_action( 'customize_register', 'lieuwe_customizer_register' );

  /**
   * Get hero video URL from Customizer. Returns empty string if not set.
   */
  function lieuwe_hero_video_url(): string {
      return (string) get_theme_mod( 'hero_video_url', '' );
  }

  /**
   * Get hero fallback image URL from Customizer. Returns empty string if not set.
   */
  function lieuwe_hero_image_url(): string {
      $image_id = (int) get_theme_mod( 'hero_image', 0 );
      if ( $image_id <= 0 ) {
          return '';
      }
      $url = wp_get_attachment_image_url( $image_id, 'full' );
      return $url ?: '';
  }
  ```

- [ ] **Step 2: Verify Customizer settings appear**

  Go to Appearance → Customize in WP admin. You should see a "Hero" section in the left panel with two controls: "Hero Video URL" and "Hero Fallback Image". No PHP errors in browser console.

- [ ] **Step 3: Commit**

  ```bash
  git add inc/customizer.php
  git commit -m "feat: Customizer hero settings and helper functions"
  ```

---

## Task 6: header.php + navigation CSS

**Files:**
- Modify: `header.php`
- Modify: `style.css` (append navigation CSS)

- [ ] **Step 1: Write header.php**

  Replace the stub `header.php`:
  ```php
  <!DOCTYPE html>
  <html <?php language_attributes(); ?>>
  <head>
      <meta charset="<?php bloginfo( 'charset' ); ?>">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?php wp_head(); ?>
  </head>
  <body <?php body_class(); ?>>
  <?php wp_body_open(); ?>

  <header class="site-header" id="site-header">
      <div class="site-header__inner">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__name">
              <?php bloginfo( 'name' ); ?>
          </a>

          <button
              class="nav-toggle"
              id="nav-toggle"
              aria-label="<?php esc_attr_e( 'Toggle navigation', 'lieuwe-theme' ); ?>"
              aria-expanded="false"
              aria-controls="site-nav"
          >
              <span class="nav-toggle__bar"></span>
              <span class="nav-toggle__bar"></span>
              <span class="nav-toggle__bar"></span>
          </button>

          <nav class="site-nav" id="site-nav" aria-label="Primary navigation">
              <?php
              wp_nav_menu( [
                  'theme_location' => 'primary',
                  'container'      => false,
                  'menu_class'     => 'site-nav__list',
                  'fallback_cb'    => false,
              ] );
              ?>
          </nav>
      </div>
  </header>
  ```

- [ ] **Step 2: Append navigation CSS to style.css**

  ```css
  /* ==========================================================================
     Header & Navigation
     ========================================================================== */

  .site-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      height: var(--nav-height);
      transition: background-color 0.3s ease;
  }

  .site-header.is-scrolled {
      background-color: var(--color-bg-dark);
  }

  .site-header__inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 100%;
      padding-inline: var(--space-md);
      max-width: var(--max-width-wide);
      margin-inline: auto;
  }

  .site-header__name {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--color-text-light);
      letter-spacing: 0.02em;
      white-space: nowrap;
  }

  /* Nav links */
  .site-nav__list {
      display: flex;
      gap: var(--space-md);
      align-items: center;
  }

  .site-nav__list a {
      font-family: var(--font-body);
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--color-text-light);
      transition: color 0.2s ease;
  }

  .site-nav__list a:hover {
      color: var(--color-accent);
  }

  /* Hamburger button */
  .nav-toggle {
      display: none;
      flex-direction: column;
      justify-content: center;
      gap: 5px;
      width: 32px;
      height: 32px;
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
  }

  .nav-toggle__bar {
      display: block;
      width: 100%;
      height: 2px;
      background-color: var(--color-text-light);
      transition: transform 0.3s ease, opacity 0.3s ease;
  }

  /* Hamburger open state */
  .nav-toggle[aria-expanded="true"] .nav-toggle__bar:nth-child(1) {
      transform: translateY(7px) rotate(45deg);
  }

  .nav-toggle[aria-expanded="true"] .nav-toggle__bar:nth-child(2) {
      opacity: 0;
  }

  .nav-toggle[aria-expanded="true"] .nav-toggle__bar:nth-child(3) {
      transform: translateY(-7px) rotate(-45deg);
  }

  /* Mobile overlay nav */
  @media (max-width: 768px) {
      .nav-toggle {
          display: flex;
      }

      .site-nav {
          position: fixed;
          inset: 0;
          background-color: var(--color-bg-dark);
          display: flex;
          align-items: center;
          justify-content: center;
          opacity: 0;
          pointer-events: none;
          transition: opacity 0.3s ease;
          z-index: 99;
      }

      .site-nav.is-open {
          opacity: 1;
          pointer-events: auto;
      }

      .site-nav__list {
          flex-direction: column;
          gap: var(--space-md);
          text-align: center;
      }

      .site-nav__list a {
          font-size: 1rem;
          letter-spacing: 0.15em;
      }
  }
  ```

- [ ] **Step 3: Verify visually**

  Reload the site. You should see:
  - A fixed header bar across the top (transparent background until you scroll)
  - Site name on the left in white
  - Navigation links on the right in small caps, white
  - On mobile (resize browser to < 768px): links are hidden, hamburger appears — but clicking it does nothing yet (JS comes next)

- [ ] **Step 4: Commit**

  ```bash
  git add header.php style.css
  git commit -m "feat: header and navigation HTML + CSS"
  ```

---

## Task 7: assets/js/main.js — nav toggle + scroll behavior

**Files:**
- Modify: `assets/js/main.js`

- [ ] **Step 1: Write main.js**

  Replace the stub `assets/js/main.js`:
  ```js
  (function () {
    'use strict';

    const header   = document.getElementById('site-header');
    const toggle   = document.getElementById('nav-toggle');
    const nav      = document.getElementById('site-nav');
    const heroEl   = document.querySelector('.hero');

    // ── Scroll: add dark background to header once past hero ──────────────────
    function onScroll() {
      const threshold = heroEl ? heroEl.offsetHeight * 0.8 : 80;
      if (window.scrollY > threshold) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // run once on load

    // ── Mobile nav toggle ──────────────────────────────────────────────────────
    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        const isOpen = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
        document.body.style.overflow = isOpen ? 'hidden' : '';
      });

      // Close nav when a link is clicked
      nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
          nav.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
        });
      });
    }
  }());
  ```

- [ ] **Step 2: Verify scroll behavior**

  - On pages without a hero, scroll down 80px — header should go dark.
  - On mobile: tap hamburger → overlay opens. Tap a link → overlay closes.
  - No console errors.

- [ ] **Step 3: Commit**

  ```bash
  git add assets/js/main.js
  git commit -m "feat: nav toggle and scroll-triggered header background"
  ```

---

## Task 8: footer.php + footer CSS

**Files:**
- Modify: `footer.php`
- Modify: `style.css` (append footer CSS)

- [ ] **Step 1: Write footer.php**

  Replace the stub `footer.php`:
  ```php
  <footer class="site-footer section-dark">
      <div class="site-footer__inner container">
          <span class="site-footer__name">
              <?php bloginfo( 'name' ); ?>
          </span>

          <?php
          wp_nav_menu( [
              'theme_location' => 'footer',
              'container'      => false,
              'menu_class'     => 'site-footer__nav',
              'fallback_cb'    => false,
          ] );
          ?>

          <div class="site-footer__right">
              <a
                  href="https://www.instagram.com/lieuwejongsma"
                  class="site-footer__instagram"
                  target="_blank"
                  rel="noopener noreferrer"
              >Instagram</a>
              <span class="site-footer__copy">
                  &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                  <?php bloginfo( 'name' ); ?>
              </span>
          </div>
      </div>
  </footer>

  <?php wp_footer(); ?>
  </body>
  </html>
  ```

- [ ] **Step 2: Append footer CSS to style.css**

  ```css
  /* ==========================================================================
     Footer
     ========================================================================== */

  .site-footer {
      padding-block: var(--space-lg);
      margin-top: var(--space-xl);
  }

  .site-footer__inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: var(--space-md);
      flex-wrap: wrap;
  }

  .site-footer__name {
      font-family: var(--font-display);
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--color-text-light);
  }

  .site-footer__nav {
      display: flex;
      gap: var(--space-md);
      flex-wrap: wrap;
  }

  .site-footer__nav a {
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--color-muted);
      transition: color 0.2s ease;
  }

  .site-footer__nav a:hover {
      color: var(--color-accent);
  }

  .site-footer__right {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: var(--space-xs);
  }

  .site-footer__instagram {
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--color-accent);
      transition: opacity 0.2s ease;
  }

  .site-footer__instagram:hover {
      opacity: 0.75;
  }

  .site-footer__copy {
      font-size: 0.75rem;
      color: var(--color-muted);
  }

  @media (max-width: 768px) {
      .site-footer__inner {
          flex-direction: column;
          align-items: flex-start;
      }

      .site-footer__right {
          align-items: flex-start;
      }
  }
  ```

- [ ] **Step 3: Verify visually**

  Reload. Scroll to the bottom. You should see:
  - Dark charcoal footer
  - Site name on the left
  - Footer nav links (if menu assigned in WP admin under Appearance → Menus → Footer Menu)
  - Instagram link + copyright on the right (or below on mobile)

- [ ] **Step 4: Commit**

  ```bash
  git add footer.php style.css
  git commit -m "feat: footer HTML and CSS"
  ```

---

## Task 9: front-page.php — hero section

**Files:**
- Modify: `front-page.php`
- Modify: `style.css` (append hero CSS)

- [ ] **Step 1: Write front-page.php with hero section (partial)**

  Replace the stub with the hero section only (below-fold comes in Task 10):
  ```php
  <?php get_header(); ?>

  <main>

  <!-- HERO ----------------------------------------------------------------- -->
  <section class="hero section-dark">
      <?php $video_url = lieuwe_hero_video_url(); ?>

      <?php if ( $video_url ) : ?>
          <video class="hero__video" autoplay muted loop playsinline>
              <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
          </video>
      <?php else : ?>
          <?php $image_url = lieuwe_hero_image_url(); ?>
          <?php if ( $image_url ) : ?>
              <div
                  class="hero__image"
                  style="background-image: url('<?php echo esc_url( $image_url ); ?>')"
                  role="img"
                  aria-label="<?php esc_attr_e( 'Hero image', 'lieuwe-theme' ); ?>"
              ></div>
          <?php else : ?>
              <div class="hero__image hero__image--empty"></div>
          <?php endif; ?>
      <?php endif; ?>

      <div class="hero__overlay" aria-hidden="true"></div>

      <div class="hero__content">
          <h1 class="hero__title"><?php bloginfo( 'name' ); ?></h1>
          <p class="hero__tagline"><?php bloginfo( 'description' ); ?></p>
      </div>
  </section>

  <!-- below-fold sections come in Task 10 -->

  </main>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Append hero CSS to style.css**

  ```css
  /* ==========================================================================
     Hero
     ========================================================================== */

  .hero {
      position: relative;
      height: 100svh;
      min-height: 500px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
  }

  .hero__video {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
  }

  .hero__image {
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center;
  }

  .hero__image--empty {
      background-color: var(--color-bg-dark);
  }

  .hero__overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
  }

  .hero__content {
      position: relative;
      z-index: 1;
      text-align: center;
      padding-inline: var(--space-md);
      max-width: 900px;
  }

  .hero__title {
      font-family: var(--font-display);
      font-size: clamp(3rem, 8vw, 7rem);
      font-weight: 700;
      color: var(--color-text-light);
      letter-spacing: 0.04em;
      line-height: 1.05;
  }

  .hero__tagline {
      margin-top: var(--space-sm);
      font-family: var(--font-body);
      font-size: clamp(0.875rem, 2vw, 1.125rem);
      font-weight: 400;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: rgba(245, 240, 232, 0.75);
  }
  ```

- [ ] **Step 3: Test with fallback image**

  In WP Customizer → Hero → upload any image as "Hero Fallback Image". Verify:
  - Image fills the full viewport
  - Dark overlay on top
  - Site name and tagline centered, white
  - On mobile: still fills full screen

- [ ] **Step 4: Test with video (if available)**

  If you have an MP4, set the Hero Video URL in Customizer. Verify:
  - Video autoplays, muted, looping, fills the screen
  - Overlay visible on top

- [ ] **Step 5: Commit**

  ```bash
  git add front-page.php style.css
  git commit -m "feat: homepage hero section — video/image with dark overlay"
  ```

---

## Task 10: front-page.php — below-fold sections

**Files:**
- Modify: `front-page.php`
- Modify: `style.css` (append homepage sections CSS)

- [ ] **Step 1: Update front-page.php with intro + portfolio preview + news preview**

  Replace `front-page.php` (keeping the hero section from Task 9):
  ```php
  <?php get_header(); ?>

  <main>

  <!-- HERO ----------------------------------------------------------------- -->
  <section class="hero section-dark">
      <?php $video_url = lieuwe_hero_video_url(); ?>

      <?php if ( $video_url ) : ?>
          <video class="hero__video" autoplay muted loop playsinline>
              <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
          </video>
      <?php else : ?>
          <?php $image_url = lieuwe_hero_image_url(); ?>
          <?php if ( $image_url ) : ?>
              <div
                  class="hero__image"
                  style="background-image: url('<?php echo esc_url( $image_url ); ?>')"
                  role="img"
                  aria-label="<?php esc_attr_e( 'Hero image', 'lieuwe-theme' ); ?>"
              ></div>
          <?php else : ?>
              <div class="hero__image hero__image--empty"></div>
          <?php endif; ?>
      <?php endif; ?>

      <div class="hero__overlay" aria-hidden="true"></div>

      <div class="hero__content">
          <h1 class="hero__title"><?php bloginfo( 'name' ); ?></h1>
          <p class="hero__tagline"><?php bloginfo( 'description' ); ?></p>
      </div>
  </section>

  <!-- INTRO ---------------------------------------------------------------- -->
  <section class="home-intro section-light">
      <div class="container container--narrow">
          <?php
          if ( have_posts() ) {
              the_post();
              the_content();
          }
          ?>
      </div>
  </section>

  <!-- PORTFOLIO PREVIEW ---------------------------------------------------- -->
  <section class="home-portfolio section-dark">
      <div class="container">
          <h2 class="home-portfolio__heading">Portfolio</h2>

          <?php
          $portfolio_query = new WP_Query( [
              'post_type'      => 'portfolio',
              'posts_per_page' => 3,
              'no_found_rows'  => true,
          ] );
          ?>

          <?php if ( $portfolio_query->have_posts() ) : ?>
              <div class="home-portfolio__grid">
                  <?php while ( $portfolio_query->have_posts() ) : $portfolio_query->the_post(); ?>
                      <a href="<?php the_permalink(); ?>" class="portfolio-card">
                          <?php if ( has_post_thumbnail() ) : ?>
                              <?php the_post_thumbnail( 'large', [ 'class' => 'portfolio-card__image' ] ); ?>
                          <?php else : ?>
                              <div class="portfolio-card__image portfolio-card__image--empty"></div>
                          <?php endif; ?>
                          <span class="portfolio-card__title"><?php the_title(); ?></span>
                      </a>
                  <?php endwhile; ?>
                  <?php wp_reset_postdata(); ?>
              </div>
              <a href="<?php echo esc_url( get_post_type_archive_link( 'portfolio' ) ); ?>" class="home-section-link">
                  View all work
              </a>
          <?php else : ?>
              <p class="home-empty">No portfolio items yet.</p>
          <?php endif; ?>
      </div>
  </section>

  <!-- NEWS PREVIEW --------------------------------------------------------- -->
  <section class="home-news section-light">
      <div class="container">
          <h2 class="home-news__heading">News</h2>

          <?php
          $news_query = new WP_Query( [
              'post_type'      => 'post',
              'posts_per_page' => 3,
              'no_found_rows'  => true,
          ] );
          ?>

          <?php if ( $news_query->have_posts() ) : ?>
              <ul class="home-news__list">
                  <?php while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
                      <li class="home-news__item">
                          <time class="home-news__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                              <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                          </time>
                          <a href="<?php the_permalink(); ?>" class="home-news__title">
                              <?php the_title(); ?>
                          </a>
                      </li>
                  <?php endwhile; ?>
                  <?php wp_reset_postdata(); ?>
              </ul>
              <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>" class="home-section-link">
                  All news
              </a>
          <?php else : ?>
              <p class="home-empty">No news yet.</p>
          <?php endif; ?>
      </div>
  </section>

  </main>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Append homepage sections CSS to style.css**

  ```css
  /* ==========================================================================
     Homepage Sections
     ========================================================================== */

  /* Intro */
  .home-intro {
      padding-block: var(--space-xl);
  }

  .home-intro p {
      font-size: 1.125rem;
      line-height: 1.8;
      color: var(--color-text);
  }

  /* Portfolio preview */
  .home-portfolio {
      padding-block: var(--space-xl);
  }

  .home-portfolio__heading,
  .home-news__heading {
      font-family: var(--font-display);
      font-size: clamp(1.5rem, 3vw, 2.5rem);
      margin-bottom: var(--space-lg);
      color: inherit;
  }

  .home-portfolio__grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: var(--space-md);
      margin-bottom: var(--space-lg);
  }

  .portfolio-card {
      display: block;
      position: relative;
      overflow: hidden;
      aspect-ratio: 3 / 4;
      background-color: var(--color-surface);
  }

  .portfolio-card__image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
  }

  .portfolio-card__image--empty {
      background-color: rgba(255, 255, 255, 0.05);
  }

  .portfolio-card:hover .portfolio-card__image {
      transform: scale(1.04);
  }

  .portfolio-card__title {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: var(--space-sm);
      background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
      color: var(--color-text-light);
      font-family: var(--font-display);
      font-size: 1rem;
      font-weight: 400;
      opacity: 0;
      transform: translateY(4px);
      transition: opacity 0.3s ease, transform 0.3s ease;
  }

  .portfolio-card:hover .portfolio-card__title {
      opacity: 1;
      transform: translateY(0);
  }

  /* News preview */
  .home-news {
      padding-block: var(--space-xl);
  }

  .home-news__list {
      margin-bottom: var(--space-lg);
      border-top: 1px solid var(--color-surface);
  }

  .home-news__item {
      display: flex;
      align-items: baseline;
      gap: var(--space-md);
      padding-block: var(--space-md);
      border-bottom: 1px solid var(--color-surface);
  }

  .home-news__date {
      flex-shrink: 0;
      font-size: 0.75rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--color-muted);
      min-width: 130px;
  }

  .home-news__title {
      font-family: var(--font-display);
      font-size: 1.125rem;
      font-weight: 400;
      color: var(--color-text);
      transition: color 0.2s ease;
  }

  .home-news__title:hover {
      color: var(--color-accent);
  }

  /* Shared: section link + empty state */
  .home-section-link {
      display: inline-block;
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--color-accent);
      border-bottom: 1px solid currentColor;
      padding-bottom: 2px;
      transition: opacity 0.2s ease;
  }

  .home-section-link:hover {
      opacity: 0.7;
  }

  .home-empty {
      color: var(--color-muted);
      font-style: italic;
  }

  @media (max-width: 768px) {
      .home-portfolio__grid {
          grid-template-columns: 1fr;
      }

      .home-news__item {
          flex-direction: column;
          gap: var(--space-xs);
      }
  }
  ```

- [ ] **Step 3: Verify visually**

  Reload homepage. Scrolling down from hero you should see:
  - Intro: cream background, readable paragraph
  - Portfolio preview: dark background, 3-column card grid with hover effect
  - News preview: cream background, list of posts with date on left

  If portfolio/news has no content: "No portfolio items yet." / "No news yet." appears cleanly.

- [ ] **Step 4: Commit**

  ```bash
  git add front-page.php style.css
  git commit -m "feat: homepage below-fold — intro, portfolio preview, news preview"
  ```

---

## Task 11: archive-portfolio.php — portfolio grid

**Files:**
- Modify: `archive-portfolio.php`
- Modify: `style.css` (append portfolio archive CSS)

- [ ] **Step 1: Write archive-portfolio.php**

  ```php
  <?php get_header(); ?>

  <main class="portfolio-archive">
      <div class="portfolio-archive__header section-dark">
          <div class="container">
              <h1 class="portfolio-archive__title">Portfolio</h1>
          </div>
      </div>

      <div class="section-light">
          <div class="container">
              <?php if ( have_posts() ) : ?>
                  <div class="portfolio-grid">
                      <?php while ( have_posts() ) : the_post(); ?>
                          <a href="<?php the_permalink(); ?>" class="portfolio-card">
                              <?php if ( has_post_thumbnail() ) : ?>
                                  <?php the_post_thumbnail( 'large', [ 'class' => 'portfolio-card__image' ] ); ?>
                              <?php else : ?>
                                  <div class="portfolio-card__image portfolio-card__image--empty"></div>
                              <?php endif; ?>
                              <span class="portfolio-card__title"><?php the_title(); ?></span>
                          </a>
                      <?php endwhile; ?>
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
                  <p class="archive-empty">No portfolio items found.</p>
              <?php endif; ?>
          </div>
      </div>
  </main>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Append portfolio archive CSS to style.css**

  ```css
  /* ==========================================================================
     Portfolio Archive
     ========================================================================== */

  .portfolio-archive__header {
      padding-block: var(--space-xl);
      padding-top: calc(var(--nav-height) + var(--space-xl));
  }

  .portfolio-archive__title {
      font-size: clamp(2.5rem, 6vw, 5rem);
      color: var(--color-text-light);
  }

  .portfolio-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: var(--space-md);
      padding-block: var(--space-xl);
  }

  .archive-pagination {
      padding-bottom: var(--space-xl);
      display: flex;
      gap: var(--space-sm);
  }

  .archive-pagination .page-numbers {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--color-muted);
      transition: color 0.2s ease;
  }

  .archive-pagination .page-numbers.current,
  .archive-pagination .page-numbers:hover {
      color: var(--color-accent);
  }

  .archive-empty {
      padding-block: var(--space-xl);
      color: var(--color-muted);
      font-style: italic;
  }

  @media (max-width: 768px) {
      .portfolio-grid {
          grid-template-columns: 1fr;
      }
  }
  ```

- [ ] **Step 3: Verify visually**

  Go to `/portfolio/`. You should see:
  - Dark header section with "Portfolio" title
  - 2-column grid of portfolio items on cream background
  - Hover: image scales slightly, title appears overlaid
  - Empty state shown if no items

- [ ] **Step 4: Commit**

  ```bash
  git add archive-portfolio.php style.css
  git commit -m "feat: portfolio archive — 2-column grid"
  ```

---

## Task 12: single-portfolio.php — single portfolio item

**Files:**
- Modify: `single-portfolio.php`
- Modify: `style.css` (append single portfolio CSS)

- [ ] **Step 1: Write single-portfolio.php**

  ```php
  <?php get_header(); ?>

  <?php while ( have_posts() ) : the_post(); ?>

  <main class="single-portfolio">

      <?php if ( has_post_thumbnail() ) : ?>
          <div class="single-portfolio__hero">
              <?php the_post_thumbnail( 'full', [ 'class' => 'single-portfolio__hero-image' ] ); ?>
          </div>
      <?php else : ?>
          <div class="single-portfolio__hero single-portfolio__hero--empty section-dark"></div>
      <?php endif; ?>

      <article class="single-portfolio__content section-light">
          <div class="container container--narrow">
              <h1 class="single-portfolio__title"><?php the_title(); ?></h1>
              <div class="entry-content">
                  <?php the_content(); ?>
              </div>
              <a href="<?php echo esc_url( get_post_type_archive_link( 'portfolio' ) ); ?>" class="back-link">
                  &larr; All work
              </a>
          </div>
      </article>

  </main>

  <?php endwhile; ?>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Append single portfolio CSS to style.css**

  ```css
  /* ==========================================================================
     Single Portfolio Item
     ========================================================================== */

  .single-portfolio__hero {
      width: 100%;
      height: 70vh;
      min-height: 400px;
      overflow: hidden;
  }

  .single-portfolio__hero--empty {
      display: flex;
  }

  .single-portfolio__hero-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center;
  }

  .single-portfolio__content {
      padding-block: var(--space-xl);
  }

  .single-portfolio__title {
      font-size: clamp(2rem, 5vw, 4rem);
      margin-bottom: var(--space-lg);
      color: var(--color-text);
  }

  .back-link {
      display: inline-block;
      margin-top: var(--space-lg);
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--color-muted);
      transition: color 0.2s ease;
  }

  .back-link:hover {
      color: var(--color-accent);
  }
  ```

- [ ] **Step 3: Append shared entry-content CSS to style.css**

  ```css
  /* ==========================================================================
     Entry Content (shared by single-portfolio, single, page)
     ========================================================================== */

  .entry-content {
      font-size: 1.0625rem;
      line-height: 1.8;
      color: var(--color-text);
  }

  .entry-content h2 {
      margin-top: var(--space-lg);
      margin-bottom: var(--space-sm);
  }

  .entry-content h3 {
      margin-top: var(--space-md);
      margin-bottom: var(--space-xs);
  }

  .entry-content p {
      margin-top: var(--space-sm);
  }

  .entry-content img {
      width: 100%;
      height: auto;
      margin-block: var(--space-lg);
  }

  .entry-content a {
      color: var(--color-accent);
      text-decoration: underline;
      text-underline-offset: 3px;
  }

  .entry-content ul,
  .entry-content ol {
      margin-block: var(--space-sm);
      padding-left: var(--space-md);
      list-style: disc;
  }

  .entry-content ol {
      list-style: decimal;
  }

  .entry-content blockquote {
      margin-block: var(--space-lg);
      padding-left: var(--space-md);
      border-left: 3px solid var(--color-accent);
      font-style: italic;
      color: var(--color-muted);
  }
  ```

- [ ] **Step 4: Verify visually**

  Click into a portfolio item. You should see:
  - Full-width featured image (70% viewport height)
  - Title below in large Playfair Display
  - Body content with proper spacing
  - "← All work" link at bottom

- [ ] **Step 5: Commit**

  ```bash
  git add single-portfolio.php style.css
  git commit -m "feat: single portfolio item — full-bleed hero + reading column"
  ```

---

## Task 13: archive.php — news listing

**Files:**
- Modify: `archive.php`
- Modify: `style.css` (append news archive CSS)

- [ ] **Step 1: Write archive.php**

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
          <div class="container">
              <?php if ( have_posts() ) : ?>
                  <ul class="news-list">
                      <?php while ( have_posts() ) : the_post(); ?>
                          <li class="news-list__item">
                              <time class="news-list__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                  <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                              </time>
                              <div class="news-list__body">
                                  <a href="<?php the_permalink(); ?>" class="news-list__title">
                                      <?php the_title(); ?>
                                  </a>
                                  <?php if ( has_excerpt() ) : ?>
                                      <p class="news-list__excerpt"><?php the_excerpt(); ?></p>
                                  <?php endif; ?>
                              </div>
                          </li>
                      <?php endwhile; ?>
                  </ul>

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

- [ ] **Step 2: Append news archive CSS to style.css**

  ```css
  /* ==========================================================================
     News Archive
     ========================================================================== */

  .news-archive__header {
      padding-block: var(--space-xl);
      padding-top: calc(var(--nav-height) + var(--space-xl));
  }

  .news-archive__title {
      font-size: clamp(2.5rem, 6vw, 5rem);
      color: var(--color-text-light);
  }

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

- [ ] **Step 3: Verify visually**

  Go to `/news/` or wherever your posts archive lives. You should see:
  - Dark header with "News" title
  - Editorial two-column list (date left, title + excerpt right)
  - Hover on title: turns accent orange

- [ ] **Step 4: Commit**

  ```bash
  git add archive.php style.css
  git commit -m "feat: news archive — editorial list layout"
  ```

---

## Task 14: single.php — single post

**Files:**
- Modify: `single.php`
- Modify: `style.css` (append single post CSS)

- [ ] **Step 1: Write single.php**

  ```php
  <?php get_header(); ?>

  <?php while ( have_posts() ) : the_post(); ?>

  <main class="single-post">

      <?php if ( has_post_thumbnail() ) : ?>
          <div class="single-post__hero">
              <?php the_post_thumbnail( 'full', [ 'class' => 'single-post__hero-image' ] ); ?>
          </div>
      <?php endif; ?>

      <article class="single-post__content section-light">
          <div class="container container--narrow">
              <header class="single-post__header">
                  <time class="single-post__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                      <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                  </time>
                  <h1 class="single-post__title"><?php the_title(); ?></h1>
              </header>

              <div class="entry-content">
                  <?php the_content(); ?>
              </div>

              <footer class="single-post__footer">
                  <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>" class="back-link">
                      &larr; All news
                  </a>
              </footer>
          </div>
      </article>

  </main>

  <?php endwhile; ?>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Append single post CSS to style.css**

  ```css
  /* ==========================================================================
     Single Post
     ========================================================================== */

  .single-post__hero {
      width: 100%;
      max-height: 60vh;
      overflow: hidden;
  }

  .single-post__hero-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center top;
  }

  .single-post__content {
      padding-block: var(--space-xl);
  }

  .single-post__header {
      margin-bottom: var(--space-lg);
  }

  .single-post__date {
      display: block;
      font-size: 0.75rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--color-muted);
      margin-bottom: var(--space-sm);
  }

  .single-post__title {
      font-size: clamp(2rem, 5vw, 3.5rem);
      font-weight: 700;
      color: var(--color-text);
      line-height: 1.1;
  }

  .single-post__footer {
      margin-top: var(--space-xl);
      padding-top: var(--space-lg);
      border-top: 1px solid var(--color-surface);
  }
  ```

- [ ] **Step 3: Verify visually**

  Open a single blog post. You should see:
  - Large featured image at top
  - Date in small caps above title
  - Large title in Playfair Display
  - Body text with generous line-height
  - "← All news" at bottom

- [ ] **Step 4: Commit**

  ```bash
  git add single.php style.css
  git commit -m "feat: single post — reading layout"
  ```

---

## Task 15: page.php — generic static pages

**Files:**
- Modify: `page.php`
- Modify: `style.css` (append page CSS)

- [ ] **Step 1: Write page.php**

  ```php
  <?php get_header(); ?>

  <?php while ( have_posts() ) : the_post(); ?>

  <main class="static-page">
      <div class="static-page__header section-dark">
          <div class="container">
              <h1 class="static-page__title"><?php the_title(); ?></h1>
          </div>
      </div>

      <div class="static-page__content section-light">
          <div class="container container--narrow">
              <?php if ( has_post_thumbnail() ) : ?>
                  <?php the_post_thumbnail( 'large', [ 'class' => 'static-page__image' ] ); ?>
              <?php endif; ?>
              <div class="entry-content">
                  <?php the_content(); ?>
              </div>
          </div>
      </div>
  </main>

  <?php endwhile; ?>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Append page CSS to style.css**

  ```css
  /* ==========================================================================
     Static Page (About, Contact, etc.)
     ========================================================================== */

  .static-page__header {
      padding-block: var(--space-xl);
      padding-top: calc(var(--nav-height) + var(--space-xl));
  }

  .static-page__title {
      font-size: clamp(2.5rem, 6vw, 5rem);
      color: var(--color-text-light);
  }

  .static-page__content {
      padding-block: var(--space-xl);
  }

  .static-page__image {
      width: 100%;
      height: auto;
      margin-bottom: var(--space-lg);
  }
  ```

- [ ] **Step 3: Verify visually**

  Open the About and Contact pages. You should see:
  - Dark header with page title
  - Cream content area with body text
  - Featured image above content (if set)

- [ ] **Step 4: Commit**

  ```bash
  git add page.php style.css
  git commit -m "feat: static page template (About, Contact)"
  ```

---

## Task 16: search.php + 404.php

**Files:**
- Modify: `search.php`
- Modify: `404.php`
- Modify: `style.css` (append search + 404 CSS)

- [ ] **Step 1: Write search.php**

  ```php
  <?php get_header(); ?>

  <main class="search-page">
      <div class="search-page__header section-dark">
          <div class="container">
              <h1 class="search-page__title">
                  Search results for: <em><?php echo esc_html( get_search_query() ); ?></em>
              </h1>
          </div>
      </div>

      <div class="section-light">
          <div class="container">
              <?php if ( have_posts() ) : ?>
                  <ul class="news-list">
                      <?php while ( have_posts() ) : the_post(); ?>
                          <li class="news-list__item">
                              <time class="news-list__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                  <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                              </time>
                              <div class="news-list__body">
                                  <a href="<?php the_permalink(); ?>" class="news-list__title">
                                      <?php the_title(); ?>
                                  </a>
                                  <p class="news-list__excerpt"><?php the_excerpt(); ?></p>
                              </div>
                          </li>
                      <?php endwhile; ?>
                  </ul>
                  <div class="archive-pagination">
                      <?php the_posts_pagination( [ 'prev_text' => '&larr;', 'next_text' => '&rarr;' ] ); ?>
                  </div>
              <?php else : ?>
                  <p class="archive-empty">No results found for "<?php echo esc_html( get_search_query() ); ?>".</p>
                  <?php get_search_form(); ?>
              <?php endif; ?>
          </div>
      </div>
  </main>

  <?php get_footer(); ?>
  ```

- [ ] **Step 2: Write 404.php**

  ```php
  <?php get_header(); ?>

  <main class="error-404 section-dark">
      <div class="container error-404__inner">
          <span class="error-404__number">404</span>
          <p class="error-404__message">This page doesn't exist.</p>
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="home-section-link">
              Go home
          </a>
      </div>
  </main>

  <?php get_footer(); ?>
  ```

- [ ] **Step 3: Append 404 + search CSS to style.css**

  ```css
  /* ==========================================================================
     Search Page
     ========================================================================== */

  .search-page__header {
      padding-block: var(--space-xl);
      padding-top: calc(var(--nav-height) + var(--space-xl));
  }

  .search-page__title {
      font-size: clamp(1.5rem, 4vw, 3rem);
      color: var(--color-text-light);
      font-weight: 400;
  }

  .search-page__title em {
      font-style: italic;
      color: var(--color-accent);
  }

  /* ==========================================================================
     404 Page
     ========================================================================== */

  .error-404 {
      min-height: 100svh;
      display: flex;
      align-items: center;
      padding-top: var(--nav-height);
  }

  .error-404__inner {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: var(--space-md);
  }

  .error-404__number {
      font-family: var(--font-display);
      font-size: clamp(6rem, 20vw, 16rem);
      font-weight: 700;
      color: var(--color-text-light);
      line-height: 1;
      opacity: 0.15;
  }

  .error-404__message {
      font-size: 1.25rem;
      color: var(--color-text-light);
      margin-top: calc(-1 * var(--space-xl));
  }
  ```

- [ ] **Step 4: Verify 404**

  Go to `/nonexistent-page/`. You should see:
  - Full-screen dark background
  - Large faint "404" 
  - "This page doesn't exist." message
  - "Go home" link in accent orange

- [ ] **Step 5: Verify search**

  Use the WordPress search. Try searching for something that returns no results — you should see the empty state and a search form. Try one that returns results — you should see the news-list style layout.

- [ ] **Step 6: Commit**

  ```bash
  git add search.php 404.php style.css
  git commit -m "feat: search results and 404 page"
  ```

---

## Task 17: Responsive polish + page-top spacing

**Files:**
- Modify: `style.css` (append responsive rules + spacing fix)

- [ ] **Step 1: Add page-top spacing for fixed header**

  Pages with a dark header section already account for nav height. But `page.php`, `archive.php` etc. should not have content hidden behind the fixed nav. Append to style.css:
  ```css
  /* ==========================================================================
     Responsive + Global Fixes
     ========================================================================== */

  /* Prevent content from hiding behind fixed nav on pages without a dark header */
  .single-post__content,
  .single-post__hero {
      /* hero image starts at top of viewport — no offset needed */
  }

  /* Tablet adjustments */
  @media (max-width: 1024px) {
      :root {
          --space-xl: 5rem;
          --space-lg: 3rem;
      }

      .home-portfolio__grid {
          grid-template-columns: repeat(2, 1fr);
      }
  }

  /* Mobile adjustments */
  @media (max-width: 768px) {
      :root {
          --space-xl: 4rem;
          --space-lg: 2rem;
          --space-md: 1.25rem;
      }

      h1 { font-size: clamp(2rem, 8vw, 3rem); }
      h2 { font-size: clamp(1.5rem, 6vw, 2rem); }

      .site-footer__inner {
          gap: var(--space-lg);
      }

      .error-404__message {
          margin-top: calc(-1 * var(--space-md));
      }
  }

  /* Focus styles for keyboard navigation */
  a:focus-visible,
  button:focus-visible {
      outline: 2px solid var(--color-accent);
      outline-offset: 3px;
  }
  ```

- [ ] **Step 2: Test responsive breakpoints**

  Use browser DevTools Device Toolbar. Test at:
  - 375px (mobile): nav collapses to hamburger, grids go single column, text scales down
  - 768px (tablet): two-column portfolio grid, footer wraps
  - 1280px (desktop): all grids at full width, three-column portfolio preview

- [ ] **Step 3: Test with real content at edge lengths**

  Check these specific cases with your real imported content:
  - A portfolio item with a very long title — does it wrap without breaking the card layout?
  - A news post with no excerpt — does the list item look clean?
  - A page with no featured image — does the header stand alone without empty space?

- [ ] **Step 4: Commit**

  ```bash
  git add style.css
  git commit -m "feat: responsive styles and global polish"
  ```

---

## Task 18: Cross-browser + launch checklist

**Files:** none (verification only)

- [ ] **Step 1: Check Chrome (desktop + mobile simulation)**
  - All pages load without console errors
  - Hero video autoplays on desktop
  - Mobile nav overlay opens/closes

- [ ] **Step 2: Check Firefox**
  - Fonts render correctly (Playfair Display stroke contrast visible)
  - Grid layouts match Chrome

- [ ] **Step 3: Check Safari**
  - `100svh` height on hero works
  - Video autoplay works (Safari requires `muted` + `playsinline` — both set)
  - `font-display: swap` doesn't cause flash of wrong font

- [ ] **Step 4: Check all content types with real data**
  - Homepage: video/image hero, portfolio preview, news preview
  - Portfolio archive: all items show, pagination works
  - Single portfolio: full-bleed image, content renders
  - News archive: list with dates, pagination
  - Single post: featured image, body content
  - About page: dark header, content
  - Contact page: dark header, content (form if present)
  - 404: go to `/doesnotexist/`
  - Search: search for a word you know exists in content

- [ ] **Step 5: Assign menus in WP admin**
  - Appearance → Menus: assign Primary Menu (Home, Portfolio, News, About, Contact)
  - Assign Footer Menu (same links, or subset)

- [ ] **Step 6: Set WordPress Reading settings**
  - Settings → Reading → "Your homepage displays" → A static page → Homepage: your homepage, Posts page: your news page

- [ ] **Step 7: Final commit**

  ```bash
  git add -A
  git commit -m "chore: final cross-browser check complete, theme ready for deployment"
  ```

- [ ] **Step 8: Deploy**

  Copy the `lieuwe-theme` folder to your live server's `wp-content/themes/` via SFTP or git. Activate in Appearance → Themes. Set the Customizer hero image. Verify on mobile.
