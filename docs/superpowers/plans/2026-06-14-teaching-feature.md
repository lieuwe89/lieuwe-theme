# Teaching / Classes Feature Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship the `/teaching/` experience for lieuwejongsma.nl — a month-grouped schedule of classes, an email-signup band with a confirmation popup, and a per-class "Book a spot" request page — backed by a standalone `lieuwe-teaching` plugin (data) and theme templates/styling (presentation).

**Architecture:** A new **plugin `lieuwe-teaching`** registers a public `teaching_event` CPT plus two private CPTs (`class_signup`, `booking_request`), owns all form handling (stored in WP + emailed, with visitor auto-reply), reCAPTCHA v3, CSV export, and GDPR export/erase hooks. The **theme** provides `archive-teaching_event.php` (landing: intro + signup band + schedule) and `single-teaching_event.php` (booking page), the popup/form JS, and CSS adapted to the theme's Goudy/Jost + oklch palette. The plugin ships bare fallback templates used only if the active theme lacks them.

**Tech Stack:** WordPress classic theme + standalone plugin · PHP 7.4+ · vanilla ES2017 JS · CSS custom properties (no preprocessor) · Google reCAPTCHA v3 · `wp_mail` · WP privacy tools.

**Spec:** `docs/superpowers/specs/2026-06-14-teaching-feature-design.md`
**Design source of truth:** `docs/design_handoff_teaching_feature/README.md` + `prototypes/teaching-page.html` + `prototypes/book-a-spot.html`

**Two repos:** Phase A (Tasks 1–12) is the **plugin** — its own git repo at `~/projects/lieuwe-teaching/`, committed there. Phase B (Tasks 13–23) is the **theme** — committed in `~/projects/lieuwe-theme/`. Each task's commit step says which repo.

**No automated test suite** exists in this codebase, and the spec keeps one out of scope. Verification is manual: each task ends with a focused manual check + a commit. The full acceptance checklist is Task 23.

**Naming locked across all tasks** (use verbatim):
- Function prefix `lieuwe_teaching_`; constants `LIEUWE_TEACHING_*`; text domain / slug `lieuwe-teaching`.
- CPTs: `teaching_event` (public), `class_signup` (private), `booking_request` (private).
- Event meta: `_te_type`, `_te_start_date`, `_te_date_text`, `_te_time_text`, `_te_where`, `_te_blurb`, `_te_price`, `_te_includes`, `_te_spots_total`, `_te_spots_open`, `_te_ticket_url`.
- Signup meta: `_cs_email`, `_cs_interests`. Booking meta: `_br_name`, `_br_email`, `_br_phone`, `_br_spots`, `_br_diet`, `_br_note`, `_br_event_id`.
- Settings option: `lieuwe_teaching_settings` (array keys `recaptcha_site`, `recaptcha_secret`, `notify_email`).
- Interest keys: `spoon-carving`, `japanese-lacquering`, `sandalmaking`, `general`.
- Nonce actions/field: signup `lieuwe_teaching_signup`, booking `lieuwe_teaching_booking`, field name `_te_nonce`.
- admin-post actions (priv + nopriv): `lieuwe_teaching_signup`, `lieuwe_teaching_booking`. JS sets a `te_ajax=1` field to get JSON instead of a redirect.
- reCAPTCHA script handle `lieuwe-teaching-recaptcha`. Theme asset handles: style `lieuwe-teaching`, script `lieuwe-teaching`; JS localize object `lieuweTeaching` (`ajaxUrl`, `recaptchaKey`).

---

## PHASE A — Plugin `lieuwe-teaching`

### Task 1: Plugin scaffold — repo, bootstrap, stub files, activation flush

**Files:**
- Create: `~/projects/lieuwe-teaching/lieuwe-teaching.php`
- Create (empty guarded stubs): `inc/helpers.php`, `inc/cpt.php`, `inc/event-meta.php`, `inc/settings.php`, `inc/recaptcha.php`, `inc/forms.php`, `inc/mail.php`, `inc/admin-lists.php`, `inc/privacy.php`, `inc/template-loader.php`
- Create: `templates/.gitkeep`

- [ ] **Step 1: Create the plugin repo and symlink it into the Local site**

```bash
mkdir -p ~/projects/lieuwe-teaching/inc ~/projects/lieuwe-teaching/templates
cd ~/projects/lieuwe-teaching
git init
# Symlink into the Local by Flywheel site so WP can load it (adjust the site path if different):
ln -s ~/projects/lieuwe-teaching \
  ~/Local\ Sites/lieuwe-theme-dev/app/public/wp-content/plugins/lieuwe-teaching
```

- [ ] **Step 2: Write the bootstrap file**

Create `~/projects/lieuwe-teaching/lieuwe-teaching.php`:

```php
<?php
/**
 * Plugin Name: Lieuwe Teaching
 * Description: Classes & workshops for lieuwejongsma.nl — teaching_event CPT, email signup + per-class booking capture (stored in WP and emailed, with visitor auto-reply), reCAPTCHA v3, CSV export, and GDPR export/erase. Templates and styling live in the theme.
 * Version: 1.0.0
 * Author: Lieuwe Jongsma
 * License: GPL-2.0-or-later
 * Text Domain: lieuwe-teaching
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LIEUWE_TEACHING_VERSION', '1.0.0' );
define( 'LIEUWE_TEACHING_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIEUWE_TEACHING_URL', plugin_dir_url( __FILE__ ) );

require_once LIEUWE_TEACHING_DIR . 'inc/helpers.php';
require_once LIEUWE_TEACHING_DIR . 'inc/cpt.php';
require_once LIEUWE_TEACHING_DIR . 'inc/event-meta.php';
require_once LIEUWE_TEACHING_DIR . 'inc/settings.php';
require_once LIEUWE_TEACHING_DIR . 'inc/recaptcha.php';
require_once LIEUWE_TEACHING_DIR . 'inc/forms.php';
require_once LIEUWE_TEACHING_DIR . 'inc/mail.php';
require_once LIEUWE_TEACHING_DIR . 'inc/admin-lists.php';
require_once LIEUWE_TEACHING_DIR . 'inc/privacy.php';
require_once LIEUWE_TEACHING_DIR . 'inc/template-loader.php';

/**
 * On activation, register the CPTs then flush rewrites so /teaching/ resolves.
 * Guarded with function_exists in case files are added incrementally during dev.
 */
register_activation_hook( __FILE__, function () {
    if ( function_exists( 'lieuwe_teaching_register_cpts' ) ) {
        lieuwe_teaching_register_cpts();
    }
    flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
```

- [ ] **Step 3: Create the ten guarded stub files**

Run this once to create every `inc/*.php` stub with the standard guard so the requires above don't fatal:

```bash
cd ~/projects/lieuwe-teaching
for f in helpers cpt event-meta settings recaptcha forms mail admin-lists privacy template-loader; do
  printf '<?php\n/**\n * %s — populated in later tasks.\n *\n * @package Lieuwe_Teaching\n */\n\nif ( ! defined( '"'"'ABSPATH'"'"' ) ) {\n    exit;\n}\n' "inc/$f.php" > "inc/$f.php"
done
touch templates/.gitkeep
```

- [ ] **Step 4: Verify the plugin activates clean**

WP admin → Plugins → activate **Lieuwe Teaching**. No fatal error; the plugin shows as active. (No menu yet — that arrives in Task 2.)

- [ ] **Step 5: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add -A
git commit -m "chore: scaffold lieuwe-teaching plugin (bootstrap, stubs, activation flush)"
```

---

### Task 2: Register the three CPTs + static helper maps

**Files:**
- Modify: `inc/cpt.php`
- Modify: `inc/helpers.php`

- [ ] **Step 1: Write the CPT registration in `inc/cpt.php`**

Append below the guard:

```php
/**
 * Register all three post types. Called on init and on activation.
 */
function lieuwe_teaching_register_cpts(): void {
    // Public events.
    if ( ! post_type_exists( 'teaching_event' ) ) {
        register_post_type( 'teaching_event', [
            'labels' => [
                'name'          => 'Classes',
                'singular_name' => 'Class',
                'add_new_item'  => 'Add New Class',
                'edit_item'     => 'Edit Class',
                'view_item'     => 'View Class',
                'all_items'     => 'All Classes',
                'menu_name'     => 'Classes',
            ],
            'public'        => true,
            'has_archive'   => true,
            'supports'      => [ 'title', 'editor', 'thumbnail' ],
            'show_in_rest'  => true,
            'rewrite'       => [ 'slug' => 'teaching' ],
            'menu_icon'     => 'dashicons-hammer',
            'menu_position' => 7,
        ] );
    }

    // Shared args for the two private submission types — visible to editors,
    // never public, never in search/REST.
    $private = [
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=teaching_event',
        'show_in_rest'        => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,
        'supports'            => [ 'title' ],
        'capability_type'     => 'post',
    ];

    if ( ! post_type_exists( 'class_signup' ) ) {
        register_post_type( 'class_signup', array_merge( $private, [
            'labels' => [
                'name'          => 'Signups',
                'singular_name' => 'Signup',
                'all_items'     => 'Signups',
                'menu_name'     => 'Signups',
            ],
        ] ) );
    }

    if ( ! post_type_exists( 'booking_request' ) ) {
        register_post_type( 'booking_request', array_merge( $private, [
            'labels' => [
                'name'          => 'Bookings',
                'singular_name' => 'Booking',
                'all_items'     => 'Bookings',
                'menu_name'     => 'Bookings',
            ],
        ] ) );
    }
}
add_action( 'init', 'lieuwe_teaching_register_cpts', 5 );
```

- [ ] **Step 2: Write the static maps in `inc/helpers.php`**

Append below the guard:

```php
/**
 * Event type keys → admin/display labels.
 *
 * @return array<string,string>
 */
function lieuwe_teaching_event_types(): array {
    return [
        'home_workshop' => 'Home workshop',
        'festival'      => 'Festival',
    ];
}

/**
 * Signup interest keys → human labels (used in the band, the popup, and emails).
 *
 * @return array<string,string>
 */
function lieuwe_teaching_interest_labels(): array {
    return [
        'spoon-carving'        => 'spoon carving',
        'japanese-lacquering'  => 'Japanese lacquering',
        'sandalmaking'         => 'sandalmaking',
        'general'              => 'general updates',
    ];
}

/**
 * True on the Teaching archive or any single class — used to scope assets.
 */
function lieuwe_teaching_is_teaching_page(): bool {
    return is_post_type_archive( 'teaching_event' ) || is_singular( 'teaching_event' );
}
```

- [ ] **Step 3: Flush rewrites and verify**

WP admin → Settings → Permalinks → **Save Changes** (flushes rules). Then confirm: the sidebar shows **Classes** (hammer icon) with **Signups** and **Bookings** nested under it. Visiting `/teaching/` no longer 404s (it renders the theme's fallback archive — blank list until Task 16; that's expected).

- [ ] **Step 4: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/cpt.php inc/helpers.php
git commit -m "feat: register teaching_event + class_signup + booking_request CPTs"
```

---

### Task 3: Event meta box — UI render

**Files:**
- Modify: `inc/event-meta.php`

- [ ] **Step 1: Append the meta box registration + render to `inc/event-meta.php`**

```php
/**
 * Register the event meta box.
 */
function lieuwe_teaching_add_event_meta_box(): void {
    add_meta_box(
        'lieuwe_teaching_event',
        'Class Details',
        'lieuwe_teaching_render_event_meta_box',
        'teaching_event',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'lieuwe_teaching_add_event_meta_box' );

/**
 * Render the event meta box.
 */
function lieuwe_teaching_render_event_meta_box( WP_Post $post ): void {
    $type        = (string) get_post_meta( $post->ID, '_te_type',        true );
    $start_date  = (string) get_post_meta( $post->ID, '_te_start_date',  true );
    $date_text   = (string) get_post_meta( $post->ID, '_te_date_text',   true );
    $time_text   = (string) get_post_meta( $post->ID, '_te_time_text',   true );
    $where       = (string) get_post_meta( $post->ID, '_te_where',       true );
    $blurb       = (string) get_post_meta( $post->ID, '_te_blurb',       true );
    $price       = (string) get_post_meta( $post->ID, '_te_price',       true );
    $includes    = (string) get_post_meta( $post->ID, '_te_includes',    true );
    $spots_total = (string) get_post_meta( $post->ID, '_te_spots_total', true );
    $spots_open  = (string) get_post_meta( $post->ID, '_te_spots_open',  true );
    $ticket_url  = (string) get_post_meta( $post->ID, '_te_ticket_url',  true );

    if ( '' === $type ) { $type = 'home_workshop'; }

    wp_nonce_field( 'lieuwe_teaching_event_meta', 'lieuwe_teaching_event_nonce' );
    ?>
    <style>
        .lt-fields { display:grid; gap:18px; }
        .lt-fields label { display:grid; gap:6px; font-weight:600; }
        .lt-fields input[type="text"],
        .lt-fields input[type="url"],
        .lt-fields input[type="date"],
        .lt-fields input[type="number"],
        .lt-fields textarea,
        .lt-fields select { width:100%; }
        .lt-fields textarea { min-height:80px; }
        .lt-fields__row { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .lt-fields__hint { color:#646970; font-weight:400; font-size:12px; margin:0; }
        @media (max-width:782px) { .lt-fields__row { grid-template-columns:1fr; } }
    </style>
    <div class="lt-fields">
        <p class="lt-fields__hint">The featured image (right sidebar) is the class photo. Use the editor below the title for an optional longer description on the booking page.</p>

        <div class="lt-fields__row">
            <label>
                Type
                <select name="lt[type]">
                    <?php foreach ( lieuwe_teaching_event_types() as $key => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>><?php echo esc_html( $lbl ); ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="lt-fields__hint">Home workshop = bookable here. Festival = links out to a ticket URL.</span>
            </label>
            <label>
                Start date
                <input type="date" name="lt[start_date]" value="<?php echo esc_attr( $start_date ); ?>">
                <span class="lt-fields__hint">Required. Sorts the schedule and groups by month. For multi-date classes, use the first date.</span>
            </label>
        </div>

        <label>
            Date (display text)
            <input type="text" name="lt[date_text]" value="<?php echo esc_attr( $date_text ); ?>" placeholder="e.g. Two Saturdays · 12 &amp; 19 Sep 2026">
            <span class="lt-fields__hint">What visitors read. Free text, so it can describe multi-day classes.</span>
        </label>

        <div class="lt-fields__row">
            <label>
                Time
                <input type="text" name="lt[time_text]" value="<?php echo esc_attr( $time_text ); ?>" placeholder="10:00–16:00">
            </label>
            <label>
                Where
                <input type="text" name="lt[where]" value="<?php echo esc_attr( $where ); ?>" placeholder="Home workshop, Groningen">
            </label>
        </div>

        <label>
            Blurb
            <textarea name="lt[blurb]" rows="3"><?php echo esc_textarea( $blurb ); ?></textarea>
            <span class="lt-fields__hint">1–2 sentences shown on the schedule card and the booking summary.</span>
        </label>

        <div class="lt-fields__row">
            <label>
                Price
                <input type="text" name="lt[price]" value="<?php echo esc_attr( $price ); ?>" placeholder="€120 — incl. materials &amp; lunch">
            </label>
            <label>
                Includes
                <input type="text" name="lt[includes]" value="<?php echo esc_attr( $includes ); ?>" placeholder="Tools, materials, lunch">
            </label>
        </div>

        <div class="lt-fields__row">
            <label>
                Spots — total
                <input type="number" name="lt[spots_total]" value="<?php echo esc_attr( $spots_total ); ?>" min="0">
            </label>
            <label>
                Spots — open
                <input type="number" name="lt[spots_open]" value="<?php echo esc_attr( $spots_open ); ?>" min="0">
                <span class="lt-fields__hint">You set this by hand — bookings do not change it. 0 = fully booked.</span>
            </label>
        </div>

        <label>
            Festival ticket URL
            <input type="url" name="lt[ticket_url]" value="<?php echo esc_attr( $ticket_url ); ?>" placeholder="https://…">
            <span class="lt-fields__hint">Only for Festival type — visitors are sent here instead of the booking page.</span>
        </label>
    </div>
    <?php
}
```

- [ ] **Step 2: Verify the editor UI**

WP admin → Classes → Add New. The "Class Details" box renders below the editor with every field. The date field shows a native date picker. The featured-image panel is in the right sidebar.

- [ ] **Step 3: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/event-meta.php
git commit -m "feat: class details meta box UI"
```

---

### Task 4: Event meta box — save handler, admin notices, upcoming-events query

**Files:**
- Modify: `inc/event-meta.php`
- Modify: `inc/helpers.php`

- [ ] **Step 1: Append the save handler + admin notices to `inc/event-meta.php`**

```php
/**
 * Save the event meta box.
 */
function lieuwe_teaching_save_event_meta( int $post_id ): void {
    if ( ! isset( $_POST['lieuwe_teaching_event_nonce'] )
         || ! wp_verify_nonce( $_POST['lieuwe_teaching_event_nonce'], 'lieuwe_teaching_event_meta' ) ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $raw = isset( $_POST['lt'] ) && is_array( $_POST['lt'] ) ? wp_unslash( $_POST['lt'] ) : [];

    // Type — whitelist, default home_workshop.
    $type = $raw['type'] ?? 'home_workshop';
    if ( ! array_key_exists( $type, lieuwe_teaching_event_types() ) ) {
        $type = 'home_workshop';
    }
    update_post_meta( $post_id, '_te_type', $type );

    // Start date — keep only a valid Y-m-d, else clear (so it's excluded from the schedule).
    $start = sanitize_text_field( $raw['start_date'] ?? '' );
    $d     = DateTime::createFromFormat( 'Y-m-d', $start );
    if ( $d && $d->format( 'Y-m-d' ) === $start ) {
        update_post_meta( $post_id, '_te_start_date', $start );
    } else {
        delete_post_meta( $post_id, '_te_start_date' );
    }

    // Plain text fields.
    update_post_meta( $post_id, '_te_date_text', sanitize_text_field( $raw['date_text'] ?? '' ) );
    update_post_meta( $post_id, '_te_time_text', sanitize_text_field( $raw['time_text'] ?? '' ) );
    update_post_meta( $post_id, '_te_where',     sanitize_text_field( $raw['where']     ?? '' ) );
    update_post_meta( $post_id, '_te_blurb',     sanitize_textarea_field( $raw['blurb']  ?? '' ) );
    update_post_meta( $post_id, '_te_price',     sanitize_text_field( $raw['price']     ?? '' ) );
    update_post_meta( $post_id, '_te_includes',  sanitize_text_field( $raw['includes']  ?? '' ) );

    // Spots — ints; clamp open ≤ total.
    $total = isset( $raw['spots_total'] ) && '' !== $raw['spots_total'] ? absint( $raw['spots_total'] ) : 0;
    $open  = isset( $raw['spots_open'] )  && '' !== $raw['spots_open']  ? absint( $raw['spots_open'] )  : 0;
    if ( $open > $total ) { $open = $total; }
    update_post_meta( $post_id, '_te_spots_total', $total );
    update_post_meta( $post_id, '_te_spots_open',  $open );

    // Ticket URL.
    update_post_meta( $post_id, '_te_ticket_url', esc_url_raw( $raw['ticket_url'] ?? '' ) );
}
add_action( 'save_post_teaching_event', 'lieuwe_teaching_save_event_meta' );

/**
 * Edit-screen notices: missing start date, or festival without a ticket URL.
 */
function lieuwe_teaching_event_admin_notices(): void {
    $screen = get_current_screen();
    if ( ! $screen || 'teaching_event' !== $screen->post_type || 'post' !== $screen->base ) {
        return;
    }
    global $post;
    if ( ! $post || 'auto-draft' === $post->post_status ) {
        return;
    }
    if ( '' === (string) get_post_meta( $post->ID, '_te_start_date', true ) ) {
        echo '<div class="notice notice-warning"><p>Set a <strong>Start date</strong> so this class appears on the schedule.</p></div>';
    }
    if ( 'festival' === get_post_meta( $post->ID, '_te_type', true )
         && '' === (string) get_post_meta( $post->ID, '_te_ticket_url', true ) ) {
        echo '<div class="notice notice-warning"><p>This is a <strong>Festival</strong> class but has no <strong>ticket URL</strong> — its button will fall back to the contact page until you add one.</p></div>';
    }
}
add_action( 'admin_notices', 'lieuwe_teaching_event_admin_notices' );
```

- [ ] **Step 2: Append the upcoming-events query helper to `inc/helpers.php`**

```php
/**
 * Query upcoming classes (start date today or later), soonest first.
 * Today is computed in the site timezone.
 */
function lieuwe_teaching_get_upcoming_events(): WP_Query {
    $today = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d' );

    return new WP_Query( [
        'post_type'      => 'teaching_event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => '_te_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => '_te_start_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ],
    ] );
}
```

- [ ] **Step 3: Round-trip every field**

Create a class titled "Test", set type Home workshop, start date, all text fields, spots total 8 / open 3, set a featured image. Save → reload → every field persists. Set spots open 99 with total 8 → save → open clamps to 8. Blank the start date → save → the "Set a Start date" notice shows. Switch type to Festival with no ticket URL → the festival notice shows.

- [ ] **Step 4: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/event-meta.php inc/helpers.php
git commit -m "feat: event meta save handler, admin notices, upcoming-events query"
```

---

### Task 5: Settings page — reCAPTCHA keys + notify-to email

**Files:**
- Modify: `inc/settings.php`

- [ ] **Step 1: Append the settings accessor + page to `inc/settings.php`**

```php
/**
 * Read plugin settings with defaults.
 *
 * @return array{recaptcha_site:string,recaptcha_secret:string,notify_email:string}
 */
function lieuwe_teaching_settings(): array {
    $defaults = [
        'recaptcha_site'   => '',
        'recaptcha_secret' => '',
        'notify_email'     => get_option( 'admin_email' ),
    ];
    $stored = get_option( 'lieuwe_teaching_settings', [] );
    return wp_parse_args( is_array( $stored ) ? $stored : [], $defaults );
}

/**
 * Register the setting + sanitizer.
 */
function lieuwe_teaching_register_settings(): void {
    register_setting( 'lieuwe_teaching', 'lieuwe_teaching_settings', [
        'type'              => 'array',
        'sanitize_callback' => 'lieuwe_teaching_sanitize_settings',
        'default'           => [],
    ] );
}
add_action( 'admin_init', 'lieuwe_teaching_register_settings' );

/**
 * Sanitize settings on save.
 *
 * @param mixed $input
 * @return array
 */
function lieuwe_teaching_sanitize_settings( $input ): array {
    $input = is_array( $input ) ? $input : [];
    $email = sanitize_email( $input['notify_email'] ?? '' );
    return [
        'recaptcha_site'   => sanitize_text_field( $input['recaptcha_site'] ?? '' ),
        'recaptcha_secret' => sanitize_text_field( $input['recaptcha_secret'] ?? '' ),
        'notify_email'     => is_email( $email ) ? $email : get_option( 'admin_email' ),
    ];
}

/**
 * Add the Settings submenu under Classes.
 */
function lieuwe_teaching_settings_menu(): void {
    add_submenu_page(
        'edit.php?post_type=teaching_event',
        'Teaching Settings',
        'Settings',
        'manage_options',
        'lieuwe-teaching-settings',
        'lieuwe_teaching_render_settings_page'
    );
}
add_action( 'admin_menu', 'lieuwe_teaching_settings_menu' );

/**
 * Render the settings page.
 */
function lieuwe_teaching_render_settings_page(): void {
    $s = lieuwe_teaching_settings();
    ?>
    <div class="wrap">
        <h1>Teaching Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'lieuwe_teaching' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="lt_rc_site">reCAPTCHA v3 site key</label></th>
                    <td><input name="lieuwe_teaching_settings[recaptcha_site]" id="lt_rc_site" type="text" class="regular-text" value="<?php echo esc_attr( $s['recaptcha_site'] ); ?>">
                        <p class="description">Paste the same v3 keys you use on the contact form. Leave blank to disable reCAPTCHA (honeypot still applies).</p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lt_rc_secret">reCAPTCHA v3 secret key</label></th>
                    <td><input name="lieuwe_teaching_settings[recaptcha_secret]" id="lt_rc_secret" type="text" class="regular-text" value="<?php echo esc_attr( $s['recaptcha_secret'] ); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lt_notify">Notify email</label></th>
                    <td><input name="lieuwe_teaching_settings[notify_email]" id="lt_notify" type="email" class="regular-text" value="<?php echo esc_attr( $s['notify_email'] ); ?>">
                        <p class="description">Where signup + booking notifications are sent.</p></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
```

- [ ] **Step 2: Verify**

WP admin → Classes → Settings. The page renders three fields. Enter a notify email + (optionally) your reCAPTCHA v3 keys → Save Changes → values persist on reload.

- [ ] **Step 3: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/settings.php
git commit -m "feat: settings page (reCAPTCHA keys + notify email)"
```

---

### Task 6: reCAPTCHA — front-end enqueue + server-side verify

**Files:**
- Modify: `inc/recaptcha.php`

- [ ] **Step 1: Append to `inc/recaptcha.php`**

```php
/**
 * The configured reCAPTCHA v3 site key (empty string when unset).
 */
function lieuwe_teaching_recaptcha_site_key(): string {
    return lieuwe_teaching_settings()['recaptcha_site'];
}

/**
 * Enqueue the Google reCAPTCHA v3 script on Teaching pages only, under our own
 * handle so the theme's CF7 'google-recaptcha' dequeue never touches it.
 */
function lieuwe_teaching_enqueue_recaptcha(): void {
    $key = lieuwe_teaching_recaptcha_site_key();
    if ( '' === $key || ! lieuwe_teaching_is_teaching_page() ) {
        return;
    }
    wp_enqueue_script(
        'lieuwe-teaching-recaptcha',
        'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $key ),
        [],
        null,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'lieuwe_teaching_enqueue_recaptcha' );

/**
 * Verify a reCAPTCHA v3 token server-side.
 *
 * Returns true when reCAPTCHA is not configured (so honeypot+nonce remain the
 * floor) or when the token verifies with score >= 0.5. Returns false only when
 * a secret IS configured and verification fails.
 */
function lieuwe_teaching_verify_recaptcha( string $token ): bool {
    $secret = lieuwe_teaching_settings()['recaptcha_secret'];
    if ( '' === $secret ) {
        return true; // Not configured — don't block.
    }
    if ( '' === $token ) {
        return false; // Configured but no token — reject (JS should always send one).
    }

    $resp = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
        'timeout' => 8,
        'body'    => [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
    ] );
    if ( is_wp_error( $resp ) ) {
        return true; // Network hiccup — fall back to honeypot/nonce rather than block a real visitor.
    }

    $data = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( empty( $data['success'] ) ) {
        return false;
    }
    // v3 returns a score 0.0–1.0.
    return ! isset( $data['score'] ) || (float) $data['score'] >= 0.5;
}
```

- [ ] **Step 2: Verify**

With keys set in Settings, load `/teaching/` (after Task 16 ships the template; for now the fallback archive is fine) → View Source → the `https://www.google.com/recaptcha/api.js?render=…` script tag is present. Load the homepage `/` → it is **absent**. Clear the keys → it disappears from `/teaching/` too.

- [ ] **Step 3: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/recaptcha.php
git commit -m "feat: reCAPTCHA v3 enqueue (own handle, teaching pages) + server verify"
```

---

### Task 7: Signup form handler (admin-post + AJAX)

**Files:**
- Modify: `inc/forms.php`

- [ ] **Step 1: Append the shared form helpers + the signup handler to `inc/forms.php`**

```php
/**
 * True when the current request is the JS (fetch) path.
 */
function lieuwe_teaching_is_ajax_submit(): bool {
    return ! empty( $_POST['te_ajax'] );
}

/**
 * Send a success response: JSON for the AJAX path, redirect for no-JS.
 *
 * @param array  $data     Extra data for the JSON payload.
 * @param string $redirect No-JS redirect URL (already including any flag/hash).
 */
function lieuwe_teaching_respond_ok( array $data, string $redirect ): void {
    if ( lieuwe_teaching_is_ajax_submit() ) {
        wp_send_json_success( $data );
    }
    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Send an error response: JSON for AJAX, redirect for no-JS.
 */
function lieuwe_teaching_respond_err( string $message, string $redirect ): void {
    if ( lieuwe_teaching_is_ajax_submit() ) {
        wp_send_json_error( [ 'message' => $message ] );
    }
    wp_safe_redirect( $redirect );
    exit;
}

/**
 * Shared anti-spam gate. Returns 'ok', 'drop' (honeypot — pretend success,
 * store nothing), or 'fail' (nonce/reCAPTCHA failure).
 */
function lieuwe_teaching_antispam( string $nonce_action ): string {
    $nonce = isset( $_POST['_te_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_te_nonce'] ) ) : '';
    if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
        return 'fail';
    }
    if ( ! empty( $_POST['te_hp'] ) ) {
        return 'drop';
    }
    $token = isset( $_POST['te_token'] ) ? sanitize_text_field( wp_unslash( $_POST['te_token'] ) ) : '';
    if ( function_exists( 'lieuwe_teaching_verify_recaptcha' )
         && ! lieuwe_teaching_verify_recaptcha( $token ) ) {
        return 'fail';
    }
    return 'ok';
}

/**
 * Handle a class-list signup.
 */
function lieuwe_teaching_handle_signup(): void {
    $back = get_post_type_archive_link( 'teaching_event' ) ?: home_url( '/' );

    $gate = lieuwe_teaching_antispam( 'lieuwe_teaching_signup' );
    if ( 'fail' === $gate ) {
        lieuwe_teaching_respond_err(
            "Hmm, that didn't go through — please try again, or email me directly.",
            add_query_arg( 'te_signup', 'err', $back ) . '#te-signup'
        );
    }

    $email = sanitize_email( wp_unslash( $_POST['te_email'] ?? '' ) );

    if ( 'drop' === $gate ) {
        // Honeypot tripped — look successful, store nothing.
        lieuwe_teaching_respond_ok( [ 'email' => $email, 'interests' => [] ], add_query_arg( 'te_signup', 'ok', $back ) . '#te-signup' );
    }

    if ( ! is_email( $email ) ) {
        lieuwe_teaching_respond_err(
            'Please enter a valid email address.',
            add_query_arg( 'te_signup', 'err', $back ) . '#te-signup'
        );
    }

    // Validate interests against the known keys.
    $valid     = array_keys( lieuwe_teaching_interest_labels() );
    $posted    = isset( $_POST['te_interests'] ) && is_array( $_POST['te_interests'] )
        ? array_map( 'sanitize_text_field', wp_unslash( $_POST['te_interests'] ) )
        : [];
    $interests = array_values( array_intersect( $valid, $posted ) );

    $post_id = wp_insert_post( [
        'post_type'   => 'class_signup',
        'post_status' => 'publish',
        'post_title'  => $email,
    ], true );

    if ( is_wp_error( $post_id ) ) {
        lieuwe_teaching_respond_err(
            'Something went wrong saving your details. Please try again.',
            add_query_arg( 'te_signup', 'err', $back ) . '#te-signup'
        );
    }

    update_post_meta( $post_id, '_cs_email', $email );
    update_post_meta( $post_id, '_cs_interests', $interests );

    if ( function_exists( 'lieuwe_teaching_send_signup_mails' ) ) {
        lieuwe_teaching_send_signup_mails( $email, $interests );
    }

    lieuwe_teaching_respond_ok(
        [ 'email' => $email, 'interests' => $interests ],
        add_query_arg( 'te_signup', 'ok', $back ) . '#te-signup'
    );
}
add_action( 'admin_post_lieuwe_teaching_signup',        'lieuwe_teaching_handle_signup' );
add_action( 'admin_post_nopriv_lieuwe_teaching_signup', 'lieuwe_teaching_handle_signup' );
```

- [ ] **Step 2: Verify the handler with a manual POST (no template yet)**

From a terminal, confirm the endpoint rejects a bad nonce (proves it's wired):

```bash
curl -s -i "http://lieuwe-theme-dev.local/wp-admin/admin-post.php" \
  --data "action=lieuwe_teaching_signup&te_ajax=1&te_email=test@example.com&_te_nonce=bad" | head -20
```

Expected: an HTTP 200 with a JSON body `{"success":false,...}` (nonce failed). A real success path is exercised in Task 16 once the form exists.

- [ ] **Step 3: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/forms.php
git commit -m "feat: signup form handler (admin-post + AJAX, nonce/honeypot/reCAPTCHA)"
```

---

### Task 8: Booking form handler (admin-post + AJAX)

**Files:**
- Modify: `inc/forms.php`

- [ ] **Step 1: Append the booking handler to `inc/forms.php`**

```php
/**
 * Handle a per-class booking request.
 */
function lieuwe_teaching_handle_booking(): void {
    $event_id = isset( $_POST['te_event_id'] ) ? absint( $_POST['te_event_id'] ) : 0;
    $back     = $event_id && get_post_type( $event_id ) === 'teaching_event'
        ? get_permalink( $event_id )
        : ( get_post_type_archive_link( 'teaching_event' ) ?: home_url( '/' ) );

    $gate = lieuwe_teaching_antispam( 'lieuwe_teaching_booking' );
    if ( 'fail' === $gate ) {
        lieuwe_teaching_respond_err(
            "Hmm, that didn't go through — please try again, or email me directly.",
            add_query_arg( 'booked', 'err', $back )
        );
    }

    $name  = sanitize_text_field( wp_unslash( $_POST['te_name'] ?? '' ) );
    $email = sanitize_email( wp_unslash( $_POST['te_email'] ?? '' ) );

    if ( 'drop' === $gate ) {
        lieuwe_teaching_respond_ok( [ 'name' => $name, 'email' => $email ], add_query_arg( 'booked', '1', $back ) );
    }

    // Validate the target event.
    if ( ! $event_id || 'teaching_event' !== get_post_type( $event_id ) || 'publish' !== get_post_status( $event_id ) ) {
        lieuwe_teaching_respond_err( 'That class could not be found.', add_query_arg( 'booked', 'err', $back ) );
    }
    if ( (int) get_post_meta( $event_id, '_te_spots_open', true ) <= 0 ) {
        lieuwe_teaching_respond_err( 'Sorry — this class is now full.', add_query_arg( 'booked', 'err', $back ) );
    }
    if ( '' === $name || ! is_email( $email ) ) {
        lieuwe_teaching_respond_err( 'Please give your name and a valid email address.', add_query_arg( 'booked', 'err', $back ) );
    }

    $spots = isset( $_POST['te_spots'] ) ? absint( $_POST['te_spots'] ) : 1;
    $spots = min( 3, max( 1, $spots ) );

    $post_id = wp_insert_post( [
        'post_type'   => 'booking_request',
        'post_status' => 'publish',
        'post_title'  => $name . ' — ' . get_the_title( $event_id ),
    ], true );

    if ( is_wp_error( $post_id ) ) {
        lieuwe_teaching_respond_err( 'Something went wrong saving your request. Please try again.', add_query_arg( 'booked', 'err', $back ) );
    }

    update_post_meta( $post_id, '_br_name',     $name );
    update_post_meta( $post_id, '_br_email',    $email );
    update_post_meta( $post_id, '_br_phone',    sanitize_text_field( wp_unslash( $_POST['te_phone'] ?? '' ) ) );
    update_post_meta( $post_id, '_br_spots',    $spots );
    update_post_meta( $post_id, '_br_diet',     sanitize_textarea_field( wp_unslash( $_POST['te_diet'] ?? '' ) ) );
    update_post_meta( $post_id, '_br_note',     sanitize_textarea_field( wp_unslash( $_POST['te_note'] ?? '' ) ) );
    update_post_meta( $post_id, '_br_event_id', $event_id );

    if ( function_exists( 'lieuwe_teaching_send_booking_mails' ) ) {
        lieuwe_teaching_send_booking_mails( $post_id );
    }

    lieuwe_teaching_respond_ok(
        [ 'name' => $name, 'email' => $email ],
        add_query_arg( 'booked', '1', $back )
    );
}
add_action( 'admin_post_lieuwe_teaching_booking',        'lieuwe_teaching_handle_booking' );
add_action( 'admin_post_nopriv_lieuwe_teaching_booking', 'lieuwe_teaching_handle_booking' );
```

- [ ] **Step 2: Verify the endpoint is wired**

```bash
curl -s -i "http://lieuwe-theme-dev.local/wp-admin/admin-post.php" \
  --data "action=lieuwe_teaching_booking&te_ajax=1&_te_nonce=bad" | head -20
```

Expected: HTTP 200 with `{"success":false,...}` (nonce failed). The success path is exercised in Task 17.

- [ ] **Step 3: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/forms.php
git commit -m "feat: booking form handler (validation, sold-out guard, store request)"
```

---

### Task 9: Email — admin notification + visitor auto-reply

**Files:**
- Modify: `inc/mail.php`
- Modify: `inc/helpers.php`

- [ ] **Step 1: Append two copy helpers to `inc/helpers.php`**

```php
/**
 * The dynamic "I'll let you know…" line, by number of interests picked.
 * Shared by the popup copy and the signup auto-reply.
 *
 * @param string[] $interests Validated interest keys.
 */
function lieuwe_teaching_interest_line( array $interests ): string {
    $labels = lieuwe_teaching_interest_labels();
    $names  = [];
    foreach ( $interests as $k ) {
        if ( isset( $labels[ $k ] ) ) {
            $names[] = $labels[ $k ];
        }
    }
    switch ( count( $names ) ) {
        case 0:
            return "I'll give you a shout the moment new dates go up.";
        case 1:
            return "I'll let you know as soon as new {$names[0]} dates go up.";
        case 2:
            return "I'll let you know when new {$names[0]} or {$names[1]} dates go up.";
        default:
            return "I'll give you a shout the moment new dates go up across the crafts you picked.";
    }
}

/**
 * Strip a trailing 4-digit year from a date string for the short confirmation
 * line ("12 & 19 Sep 2026" → "12 & 19 Sep"). Falls back to the original.
 */
function lieuwe_teaching_strip_year( string $text ): string {
    $short = trim( (string) preg_replace( '/[\s,·–—-]*\b(19|20)\d{2}\b\s*$/u', '', $text ) );
    return '' !== $short ? $short : $text;
}
```

- [ ] **Step 2: Append the mail senders to `inc/mail.php`**

```php
/**
 * Site-name-prefixed subject.
 */
function lieuwe_teaching_subject( string $line ): string {
    return '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] ' . $line;
}

/**
 * Send signup notification (to admin) + auto-reply (to visitor).
 *
 * @param string[] $interests Validated interest keys.
 */
function lieuwe_teaching_send_signup_mails( string $email, array $interests ): void {
    $settings = lieuwe_teaching_settings();
    $labels   = lieuwe_teaching_interest_labels();
    $names    = array_map( static fn( $k ) => $labels[ $k ] ?? $k, $interests );
    $picked   = $names ? implode( ', ', $names ) : '(none picked)';

    // Admin notification — reply goes to the visitor.
    wp_mail(
        $settings['notify_email'],
        lieuwe_teaching_subject( 'New class signup' ),
        "New signup for the class list.\n\nEmail: {$email}\nInterests: {$picked}\n",
        [ 'Reply-To: ' . $email ]
    );

    // Visitor auto-reply — reply goes to you.
    wp_mail(
        $email,
        lieuwe_teaching_subject( "You're on the list" ),
        "Right, you're on the list.\n\n" . lieuwe_teaching_interest_line( $interests ) . "\n\n— Lieuwe",
        [ 'Reply-To: ' . $settings['notify_email'] ]
    );
}

/**
 * Send booking notification (to admin) + auto-reply (to visitor).
 */
function lieuwe_teaching_send_booking_mails( int $booking_id ): void {
    $settings = lieuwe_teaching_settings();

    $name     = (string) get_post_meta( $booking_id, '_br_name',  true );
    $email    = (string) get_post_meta( $booking_id, '_br_email', true );
    $phone    = (string) get_post_meta( $booking_id, '_br_phone', true );
    $spots    = (string) get_post_meta( $booking_id, '_br_spots', true );
    $diet     = (string) get_post_meta( $booking_id, '_br_diet',  true );
    $note     = (string) get_post_meta( $booking_id, '_br_note',  true );
    $event_id = (int)    get_post_meta( $booking_id, '_br_event_id', true );

    $event_title = $event_id ? get_the_title( $event_id ) : '(unknown class)';
    $date_text   = $event_id ? (string) get_post_meta( $event_id, '_te_date_text', true ) : '';
    $edit_link   = admin_url( 'post.php?post=' . $booking_id . '&action=edit' );
    $first_name  = trim( explode( ' ', $name )[0] );
    $short_date  = lieuwe_teaching_strip_year( $date_text );

    // Admin notification — reply goes to the visitor.
    $admin_body  = "New booking request.\n\n"
        . "Class: {$event_title}\n"
        . "Date: {$date_text}\n"
        . "Name: {$name}\n"
        . "Email: {$email}\n"
        . "Phone: " . ( $phone ?: '—' ) . "\n"
        . "Spots: {$spots}\n"
        . "Dietary: " . ( $diet ?: '—' ) . "\n"
        . "Note: " . ( $note ?: '—' ) . "\n\n"
        . "Manage: {$edit_link}\n";
    wp_mail(
        $settings['notify_email'],
        lieuwe_teaching_subject( "New booking: {$event_title} — {$name}" ),
        $admin_body,
        [ 'Reply-To: ' . $email ]
    );

    // Visitor auto-reply — reply goes to you.
    $visitor_body = "Spot requested, {$first_name}.\n\n"
        . "Thanks — I've noted your request for {$event_title}"
        . ( $short_date ? " on {$short_date}" : '' ) . ".\n\n"
        . "I hold spots by hand, so I'll be in touch personally at {$email} to confirm.\n\n— Lieuwe";
    wp_mail(
        $email,
        lieuwe_teaching_subject( "Spot requested — {$event_title}" ),
        $visitor_body,
        [ 'Reply-To: ' . $settings['notify_email'] ]
    );
}
```

- [ ] **Step 3: Verify mail fires (uses the wired handlers from Tasks 7–8)**

Temporarily add `add_filter('wp_mail', function($a){ error_log('wp_mail to: '.(is_array($a['to'])?implode(',',$a['to']):$a['to']).' subj: '.$a['subject']); return $a; });` to `inc/mail.php`, then POST a valid signup via curl with a real nonce — easier to confirm fully in Task 16 once the form exists. For now, confirm no PHP errors when the file loads (reload any admin page). **Remove the debug filter before committing.**

- [ ] **Step 4: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/mail.php inc/helpers.php
git commit -m "feat: admin notification + visitor auto-reply for signup and booking"
```

---

### Task 10: Admin list columns + CSV export

**Files:**
- Modify: `inc/admin-lists.php`

- [ ] **Step 1: Append list columns + CSV export to `inc/admin-lists.php`**

```php
// ---- Signups list columns ------------------------------------------------

add_filter( 'manage_class_signup_posts_columns', function ( array $cols ): array {
    return [
        'cb'           => $cols['cb'] ?? '',
        'title'        => 'Email',
        'lt_interests' => 'Interests',
        'date'         => $cols['date'] ?? 'Date',
    ];
} );

add_action( 'manage_class_signup_posts_custom_column', function ( string $col, int $post_id ): void {
    if ( 'lt_interests' === $col ) {
        $labels = lieuwe_teaching_interest_labels();
        $keys   = (array) get_post_meta( $post_id, '_cs_interests', true );
        $names  = array_map( static fn( $k ) => $labels[ $k ] ?? $k, $keys );
        echo $names ? esc_html( implode( ', ', $names ) ) : '—';
    }
}, 10, 2 );

// ---- Bookings list columns -----------------------------------------------

add_filter( 'manage_booking_request_posts_columns', function ( array $cols ): array {
    return [
        'cb'       => $cols['cb'] ?? '',
        'title'    => 'Request',
        'lt_email' => 'Email',
        'lt_phone' => 'Phone',
        'lt_spots' => 'Spots',
        'lt_class' => 'Class',
        'date'     => $cols['date'] ?? 'Date',
    ];
} );

add_action( 'manage_booking_request_posts_custom_column', function ( string $col, int $post_id ): void {
    switch ( $col ) {
        case 'lt_email':
            echo esc_html( get_post_meta( $post_id, '_br_email', true ) ?: '—' );
            break;
        case 'lt_phone':
            echo esc_html( get_post_meta( $post_id, '_br_phone', true ) ?: '—' );
            break;
        case 'lt_spots':
            echo esc_html( (string) get_post_meta( $post_id, '_br_spots', true ) ?: '—' );
            break;
        case 'lt_class':
            $eid = (int) get_post_meta( $post_id, '_br_event_id', true );
            if ( $eid ) {
                printf( '<a href="%s">%s</a>', esc_url( (string) get_edit_post_link( $eid ) ), esc_html( get_the_title( $eid ) ) );
            } else {
                echo '—';
            }
            break;
    }
}, 10, 2 );

// ---- CSV export ----------------------------------------------------------

add_action( 'restrict_manage_posts', function ( string $post_type ): void {
    if ( ! in_array( $post_type, [ 'class_signup', 'booking_request' ], true ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $url = wp_nonce_url(
        admin_url( 'admin-post.php?action=lieuwe_teaching_export_csv&type=' . $post_type ),
        'lieuwe_teaching_export'
    );
    printf( ' <a href="%s" class="button">Export CSV</a>', esc_url( $url ) );
} );

add_action( 'admin_post_lieuwe_teaching_export_csv', function (): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient permissions.' );
    }
    check_admin_referer( 'lieuwe_teaching_export' );

    $type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
    if ( ! in_array( $type, [ 'class_signup', 'booking_request' ], true ) ) {
        wp_die( 'Unknown export type.' );
    }

    $posts = get_posts( [
        'post_type'   => $type,
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC',
    ] );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $type . '-' . gmdate( 'Ymd-His' ) . '.csv"' );
    $out = fopen( 'php://output', 'w' );

    if ( 'class_signup' === $type ) {
        fputcsv( $out, [ 'Date', 'Email', 'Interests' ] );
        $labels = lieuwe_teaching_interest_labels();
        foreach ( $posts as $p ) {
            $keys  = (array) get_post_meta( $p->ID, '_cs_interests', true );
            $names = array_map( static fn( $k ) => $labels[ $k ] ?? $k, $keys );
            fputcsv( $out, [
                get_the_date( 'Y-m-d H:i', $p ),
                get_post_meta( $p->ID, '_cs_email', true ),
                implode( '; ', $names ),
            ] );
        }
    } else {
        fputcsv( $out, [ 'Date', 'Name', 'Email', 'Phone', 'Spots', 'Class', 'Dietary', 'Note' ] );
        foreach ( $posts as $p ) {
            $eid = (int) get_post_meta( $p->ID, '_br_event_id', true );
            fputcsv( $out, [
                get_the_date( 'Y-m-d H:i', $p ),
                get_post_meta( $p->ID, '_br_name', true ),
                get_post_meta( $p->ID, '_br_email', true ),
                get_post_meta( $p->ID, '_br_phone', true ),
                get_post_meta( $p->ID, '_br_spots', true ),
                $eid ? get_the_title( $eid ) : '',
                get_post_meta( $p->ID, '_br_diet', true ),
                get_post_meta( $p->ID, '_br_note', true ),
            ] );
        }
    }
    fclose( $out );
    exit;
} );
```

- [ ] **Step 2: Verify**

Manually create one `class_signup` (Classes → Signups → Add New, title = an email) and one `booking_request`. The list screens show the custom columns. Click **Export CSV** on each → a valid CSV downloads with the right headers + your test row.

- [ ] **Step 3: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/admin-lists.php
git commit -m "feat: admin list columns + CSV export for signups and bookings"
```

---

### Task 11: GDPR — privacy exporter + eraser

**Files:**
- Modify: `inc/privacy.php`

- [ ] **Step 1: Append exporter + eraser to `inc/privacy.php`**

```php
add_filter( 'wp_privacy_personal_data_exporters', function ( array $exporters ): array {
    $exporters['lieuwe-teaching'] = [
        'exporter_friendly_name' => 'Teaching signups & bookings',
        'callback'               => 'lieuwe_teaching_privacy_exporter',
    ];
    return $exporters;
} );

/**
 * Export signup + booking records for an email address.
 *
 * @return array{data:array,done:bool}
 */
function lieuwe_teaching_privacy_exporter( string $email_address, int $page = 1 ): array {
    $items = [];

    foreach ( get_posts( [ 'post_type' => 'class_signup', 'numberposts' => -1, 'meta_key' => '_cs_email', 'meta_value' => $email_address ] ) as $p ) {
        $items[] = [
            'group_id'    => 'lt_signups',
            'group_label' => 'Class signups',
            'item_id'     => 'signup-' . $p->ID,
            'data'        => [
                [ 'name' => 'Email',     'value' => get_post_meta( $p->ID, '_cs_email', true ) ],
                [ 'name' => 'Interests', 'value' => implode( ', ', (array) get_post_meta( $p->ID, '_cs_interests', true ) ) ],
                [ 'name' => 'Date',      'value' => get_the_date( 'c', $p ) ],
            ],
        ];
    }

    foreach ( get_posts( [ 'post_type' => 'booking_request', 'numberposts' => -1, 'meta_key' => '_br_email', 'meta_value' => $email_address ] ) as $p ) {
        $eid     = (int) get_post_meta( $p->ID, '_br_event_id', true );
        $items[] = [
            'group_id'    => 'lt_bookings',
            'group_label' => 'Booking requests',
            'item_id'     => 'booking-' . $p->ID,
            'data'        => [
                [ 'name' => 'Name',    'value' => get_post_meta( $p->ID, '_br_name', true ) ],
                [ 'name' => 'Email',   'value' => get_post_meta( $p->ID, '_br_email', true ) ],
                [ 'name' => 'Phone',   'value' => get_post_meta( $p->ID, '_br_phone', true ) ],
                [ 'name' => 'Spots',   'value' => get_post_meta( $p->ID, '_br_spots', true ) ],
                [ 'name' => 'Class',   'value' => $eid ? get_the_title( $eid ) : '' ],
                [ 'name' => 'Dietary', 'value' => get_post_meta( $p->ID, '_br_diet', true ) ],
                [ 'name' => 'Note',    'value' => get_post_meta( $p->ID, '_br_note', true ) ],
                [ 'name' => 'Date',    'value' => get_the_date( 'c', $p ) ],
            ],
        ];
    }

    return [ 'data' => $items, 'done' => true ];
}

add_filter( 'wp_privacy_personal_data_erasers', function ( array $erasers ): array {
    $erasers['lieuwe-teaching'] = [
        'eraser_friendly_name' => 'Teaching signups & bookings',
        'callback'             => 'lieuwe_teaching_privacy_eraser',
    ];
    return $erasers;
} );

/**
 * Erase signup + booking records for an email address.
 *
 * @return array{items_removed:bool,items_retained:bool,messages:array,done:bool}
 */
function lieuwe_teaching_privacy_eraser( string $email_address, int $page = 1 ): array {
    $removed = 0;
    foreach ( [ 'class_signup' => '_cs_email', 'booking_request' => '_br_email' ] as $pt => $key ) {
        foreach ( get_posts( [ 'post_type' => $pt, 'numberposts' => -1, 'meta_key' => $key, 'meta_value' => $email_address ] ) as $p ) {
            wp_delete_post( $p->ID, true );
            $removed++;
        }
    }
    return [
        'items_removed'  => $removed > 0,
        'items_retained' => false,
        'messages'       => [],
        'done'           => true,
    ];
}
```

- [ ] **Step 2: Verify**

WP admin → Tools → Export Personal Data → enter the test booking's email → Send/Download → the report includes the "Class signups" / "Booking requests" groups. Then Tools → Erase Personal Data for the same email → confirms the records were removed (re-check the Bookings list).

- [ ] **Step 3: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/privacy.php
git commit -m "feat: GDPR exporter + eraser for signups and bookings"
```

---

### Task 12: Fallback templates + template loader

**Files:**
- Modify: `inc/template-loader.php`
- Create: `templates/archive-teaching_event.php`
- Create: `templates/single-teaching_event.php`

- [ ] **Step 1: Append the loader to `inc/template-loader.php`**

```php
/**
 * Use the plugin's bare templates only when the active theme provides none.
 * The lieuwe-theme ships styled archive/single templates that win via
 * locate_template(); a foreign theme falls back to these so data still shows.
 */
function lieuwe_teaching_template_loader( string $template ): string {
    if ( is_post_type_archive( 'teaching_event' ) && '' === locate_template( [ 'archive-teaching_event.php' ] ) ) {
        return LIEUWE_TEACHING_DIR . 'templates/archive-teaching_event.php';
    }
    if ( is_singular( 'teaching_event' ) && '' === locate_template( [ 'single-teaching_event.php' ] ) ) {
        return LIEUWE_TEACHING_DIR . 'templates/single-teaching_event.php';
    }
    return $template;
}
add_filter( 'template_include', 'lieuwe_teaching_template_loader' );
```

- [ ] **Step 2: Create `templates/archive-teaching_event.php`**

```php
<?php
/** Bare fallback Teaching archive (used only under a theme without its own). */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
$q = function_exists( 'lieuwe_teaching_get_upcoming_events' ) ? lieuwe_teaching_get_upcoming_events() : null;
?>
<main style="max-width:760px;margin:40px auto;padding:0 20px;">
    <h1><?php post_type_archive_title(); ?></h1>
    <?php if ( $q && $q->have_posts() ) : while ( $q->have_posts() ) : $q->the_post(); ?>
        <article style="margin:24px 0;border-bottom:1px solid #ccc;padding-bottom:16px;">
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <p><?php echo esc_html( get_post_meta( get_the_ID(), '_te_date_text', true ) ); ?>
               · <?php echo esc_html( get_post_meta( get_the_ID(), '_te_where', true ) ); ?></p>
            <p><?php echo esc_html( get_post_meta( get_the_ID(), '_te_blurb', true ) ); ?></p>
        </article>
    <?php endwhile; wp_reset_postdata(); else : ?>
        <p>No upcoming classes right now.</p>
    <?php endif; ?>
</main>
<?php get_footer();
```

- [ ] **Step 3: Create `templates/single-teaching_event.php`**

```php
<?php
/** Bare fallback single class (used only under a theme without its own). */
if ( ! defined( 'ABSPATH' ) ) { exit; }
while ( have_posts() ) :
    the_post();
    $ticket = (string) get_post_meta( get_the_ID(), '_te_ticket_url', true );
    if ( 'festival' === get_post_meta( get_the_ID(), '_te_type', true ) && $ticket ) {
        wp_safe_redirect( $ticket, 302 );
        exit;
    }
    get_header();
    ?>
    <main style="max-width:680px;margin:40px auto;padding:0 20px;">
        <h1><?php the_title(); ?></h1>
        <p><?php echo esc_html( get_post_meta( get_the_ID(), '_te_date_text', true ) ); ?>
           · <?php echo esc_html( get_post_meta( get_the_ID(), '_te_time_text', true ) ); ?></p>
        <p><?php echo esc_html( get_post_meta( get_the_ID(), '_te_where', true ) ); ?></p>
        <p><?php echo esc_html( get_post_meta( get_the_ID(), '_te_blurb', true ) ); ?></p>
        <p>To request a spot, please <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">get in touch</a>.</p>
    </main>
    <?php
    get_footer();
endwhile;
```

- [ ] **Step 4: Verify the fallback**

Temporarily activate a stock theme (Appearance → Themes → Twenty Twenty-Four). Visit `/teaching/` → the bare archive lists your test class. Open the class → bare single renders; a Festival class with a ticket URL redirects out. **Switch back to the Lieuwe theme.**

- [ ] **Step 5: Commit (plugin repo)**

```bash
cd ~/projects/lieuwe-teaching
git add inc/template-loader.php templates/archive-teaching_event.php templates/single-teaching_event.php
git commit -m "feat: fallback templates + template_include loader"
```

**Plugin (Phase A) complete.** Activating the plugin now gives a working data layer + admin + capture (rendered via the bare fallbacks). Phase B styles it into the real site.

---

## PHASE B — Theme `lieuwe-theme`

> All Phase B commits are in `~/projects/lieuwe-theme`.

### Task 13: Theme scaffold — version bump, forest token, enqueue, stubs

**Files:**
- Modify: `style.css:5` (Version), `style.css:86` (add token)
- Create: `inc/teaching.php`
- Modify: `functions.php:8` (require)
- Create (stubs): `assets/css/teaching.css`, `assets/js/teaching.js`

- [ ] **Step 1: Bump the theme version**

Edit `style.css` line 5: `Version: 1.13.0` → `Version: 1.14.0`.

- [ ] **Step 2: Add the festival token after `--color-accent`**

In `style.css`, the `:root` block ends with the accent line. Add `--color-forest` directly after it:

```css
    --color-accent:     oklch(55% 0.12 48);    /* terracotta */
    --color-forest:     oklch(48% 0.055 145);  /* festival green (handoff #56633f) */
```

- [ ] **Step 3: Create `inc/teaching.php` (conditional enqueue + localize)**

```php
<?php
/**
 * Teaching pages — conditional asset enqueue (templates live in this theme,
 * data lives in the lieuwe-teaching plugin).
 *
 * @package Lieuwe_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue teaching CSS + JS only on the Teaching archive / single class.
 * These conditions are false when the plugin is inactive (CPT unregistered),
 * so nothing loads and nothing fatals.
 */
function lieuwe_theme_teaching_enqueue(): void {
    if ( ! is_post_type_archive( 'teaching_event' ) && ! is_singular( 'teaching_event' ) ) {
        return;
    }

    $ver = wp_get_theme()->get( 'Version' );
    $uri = get_template_directory_uri();

    wp_enqueue_style(
        'lieuwe-teaching',
        $uri . '/assets/css/teaching.css',
        [ 'lieuwe-theme' ],
        $ver
    );

    wp_enqueue_script(
        'lieuwe-teaching',
        $uri . '/assets/js/teaching.js',
        [],
        $ver,
        true
    );

    $key = function_exists( 'lieuwe_teaching_recaptcha_site_key' )
        ? lieuwe_teaching_recaptcha_site_key()
        : '';

    wp_localize_script( 'lieuwe-teaching', 'lieuweTeaching', [
        'ajaxUrl'      => admin_url( 'admin-post.php' ),
        'recaptchaKey' => $key,
    ] );
}
add_action( 'wp_enqueue_scripts', 'lieuwe_theme_teaching_enqueue' );
```

- [ ] **Step 4: Require it from `functions.php`**

After the existing `require_once get_template_directory() . '/inc/publications.php';` (line 7), add:

```php
require_once get_template_directory() . '/inc/publications.php';
require_once get_template_directory() . '/inc/teaching.php';
```

- [ ] **Step 5: Create the two asset stubs**

```bash
cd ~/projects/lieuwe-theme
printf '/* Teaching pages — populated in later tasks */\n' > assets/css/teaching.css
printf "(function () { 'use strict'; /* teaching.js — populated in later tasks */ })();\n" > assets/js/teaching.js
```

- [ ] **Step 6: Verify**

WP admin → Themes shows Version 1.14.0, no fatal. Visit `/teaching/` (still the plugin fallback archive) → DevTools → Network: `teaching.css` + `teaching.js` load there. Visit `/` → they do **not** load.

- [ ] **Step 7: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add style.css functions.php inc/teaching.php assets/css/teaching.css assets/js/teaching.js
git commit -m "chore(teaching): scaffold enqueue + forest token, bump theme to 1.14.0"
```

---

### Task 14: Customizer — "Teaching page" section + accessors

**Files:**
- Modify: `inc/customizer.php`

- [ ] **Step 1: Add the section before the closing brace of `lieuwe_customizer_register`**

In `inc/customizer.php`, immediately after the `pub_hero_intro` control block and before the `}` that closes `lieuwe_customizer_register` (currently line 81), insert:

```php
    // Teaching page
    $wp_customize->add_section( 'lieuwe_teaching', [
        'title'    => 'Teaching page',
        'priority' => 36,
    ] );

    $teaching_fields = [
        'teaching_eyebrow'      => [ 'label' => 'Eyebrow',            'type' => 'text',     'default' => 'Classes & workshops' ],
        'teaching_title'        => [ 'label' => 'Title',              'type' => 'text',     'default' => 'Teaching' ],
        'teaching_intro_p1'     => [ 'label' => 'Intro paragraph 1',  'type' => 'textarea', 'default' => 'I teach traditional crafts — spoon carving, leatherwork, Japanese lacquerwork, and now and then sandalmaking — at festivals, at the archives, and at small workshops I host myself.' ],
        'teaching_intro_p2'     => [ 'label' => 'Intro paragraph 2',  'type' => 'textarea', 'default' => 'Classes are practical and unhurried. Beginners are welcome, and so are people who have been at it longer than I have.' ],
        'teaching_hero_caption' => [ 'label' => 'Hero caption',       'type' => 'text',     'default' => '' ],
        'signup_heading'        => [ 'label' => 'Signup heading',     'type' => 'text',     'default' => 'Hear about new classes' ],
        'signup_intro'          => [ 'label' => 'Signup intro',       'type' => 'textarea', 'default' => 'New dates go up through the year. Leave your email and I will let you know when the next ones are set — no more than a handful of messages a year.' ],
        'teaching_privacy_note' => [ 'label' => 'Privacy note (under forms)', 'type' => 'text', 'default' => 'Your details are only used to contact you about classes. Nothing else.' ],
    ];

    foreach ( $teaching_fields as $id => $cfg ) {
        $wp_customize->add_setting( $id, [
            'default'           => $cfg['default'],
            'sanitize_callback' => 'textarea' === $cfg['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( $id, [
            'label'   => $cfg['label'],
            'section' => 'lieuwe_teaching',
            'type'    => $cfg['type'],
        ] );
    }

    $wp_customize->add_setting( 'teaching_hero_image', [
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control(
        new \WP_Customize_Media_Control( $wp_customize, 'teaching_hero_image', [
            'label'     => 'Hero image',
            'section'   => 'lieuwe_teaching',
            'mime_type' => 'image',
        ] )
    );
```

- [ ] **Step 2: Add accessors at the bottom of `inc/customizer.php`**

```php
/**
 * Teaching page copy accessors (theme side; distinct prefix from the plugin).
 */
function lieuwe_teaching_page_eyebrow(): string       { return (string) get_theme_mod( 'teaching_eyebrow', 'Classes & workshops' ); }
function lieuwe_teaching_page_title(): string         { return (string) get_theme_mod( 'teaching_title', 'Teaching' ); }
function lieuwe_teaching_page_intro_p1(): string      { return (string) get_theme_mod( 'teaching_intro_p1', '' ); }
function lieuwe_teaching_page_intro_p2(): string      { return (string) get_theme_mod( 'teaching_intro_p2', '' ); }
function lieuwe_teaching_page_hero_caption(): string  { return (string) get_theme_mod( 'teaching_hero_caption', '' ); }
function lieuwe_teaching_page_signup_heading(): string{ return (string) get_theme_mod( 'signup_heading', 'Hear about new classes' ); }
function lieuwe_teaching_page_signup_intro(): string  { return (string) get_theme_mod( 'signup_intro', '' ); }
function lieuwe_teaching_page_privacy_note(): string  { return (string) get_theme_mod( 'teaching_privacy_note', '' ); }
function lieuwe_teaching_page_hero_image_url(): string {
    $id = (int) get_theme_mod( 'teaching_hero_image', 0 );
    if ( $id <= 0 ) {
        return '';
    }
    return (string) ( wp_get_attachment_image_url( $id, 'large' ) ?: '' );
}
```

- [ ] **Step 3: Verify**

WP admin → Appearance → Customize → "Teaching page" section shows all fields incl. the image picker. Editing persists on save. (Live preview connects once templates land in Tasks 15–17.)

- [ ] **Step 4: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add inc/customizer.php
git commit -m "feat(teaching): customizer section + accessors for the Teaching page"
```

---

### Task 15: `archive-teaching_event.php` — intro header + signup band

**Files:**
- Create: `archive-teaching_event.php`

- [ ] **Step 1: Write the template top (intro + band; schedule placeholder for Task 16)**

```php
<?php
/**
 * Teaching archive — /teaching/ : intro, signup band, schedule.
 *
 * @package Lieuwe_Theme
 */

get_header();

$signup_state = isset( $_GET['te_signup'] ) ? sanitize_key( wp_unslash( $_GET['te_signup'] ) ) : '';
$hero_img     = lieuwe_teaching_page_hero_image_url();
$intro_p1     = lieuwe_teaching_page_intro_p1();
$intro_p2     = lieuwe_teaching_page_intro_p2();
$privacy_note = lieuwe_teaching_page_privacy_note();
?>

<main class="te">

    <section class="te-intro">
        <div class="te-container te-intro__grid">
            <div class="te-intro__copy">
                <p class="te-eyebrow"><?php echo esc_html( lieuwe_teaching_page_eyebrow() ); ?></p>
                <h1 class="te-intro__title"><?php echo esc_html( lieuwe_teaching_page_title() ); ?></h1>
                <?php if ( $intro_p1 ) : ?><p class="te-intro__p"><?php echo esc_html( $intro_p1 ); ?></p><?php endif; ?>
                <?php if ( $intro_p2 ) : ?><p class="te-intro__p"><?php echo esc_html( $intro_p2 ); ?></p><?php endif; ?>
            </div>
            <figure class="te-intro__figure">
                <?php if ( $hero_img ) : ?>
                    <img class="te-intro__img" src="<?php echo esc_url( $hero_img ); ?>" alt="">
                <?php else : ?>
                    <div class="te-intro__img te-intro__img--empty" aria-hidden="true"></div>
                <?php endif; ?>
                <?php if ( $cap = lieuwe_teaching_page_hero_caption() ) : ?>
                    <figcaption class="te-intro__caption"><?php echo esc_html( $cap ); ?></figcaption>
                <?php endif; ?>
            </figure>
        </div>
    </section>

    <section class="te-band" id="te-signup">
        <div class="te-container te-band__grid">
            <div class="te-band__lead">
                <h2 class="te-band__heading"><?php echo esc_html( lieuwe_teaching_page_signup_heading() ); ?></h2>
                <?php if ( $intro = lieuwe_teaching_page_signup_intro() ) : ?>
                    <p class="te-band__intro"><?php echo esc_html( $intro ); ?></p>
                <?php endif; ?>
            </div>
            <div class="te-band__form-wrap">
                <?php if ( 'ok' === $signup_state ) : ?>
                    <div class="te-confirm-inline" role="status">
                        <p>Right, you're on the list. I'll be in touch when new dates go up.</p>
                    </div>
                <?php else : ?>
                    <?php if ( 'err' === $signup_state ) : ?>
                        <p class="te-form-error" role="alert">Hmm, that didn't go through — please try again.</p>
                    <?php endif; ?>
                    <form class="te-signup" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
                        <input type="hidden" name="action" value="lieuwe_teaching_signup">
                        <?php wp_nonce_field( 'lieuwe_teaching_signup', '_te_nonce' ); ?>
                        <input type="text" name="te_hp" class="te-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <input type="hidden" name="te_token" class="te-token" value="">
                        <div class="te-signup__row">
                            <label class="te-field te-field--grow">
                                <span class="visually-hidden">Email address</span>
                                <input type="email" name="te_email" required placeholder="you@example.com">
                            </label>
                            <button type="submit" class="te-btn te-btn--primary">Keep me posted</button>
                        </div>
                        <fieldset class="te-signup__interests">
                            <legend class="visually-hidden">What are you interested in?</legend>
                            <?php foreach ( lieuwe_teaching_interest_labels() as $key => $label ) : ?>
                                <label class="te-check">
                                    <input type="checkbox" name="te_interests[]" value="<?php echo esc_attr( $key ); ?>">
                                    <span><?php echo esc_html( ucfirst( $label ) ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <?php if ( $privacy_note ) : ?>
                            <p class="te-form-note"><?php echo esc_html( $privacy_note ); ?></p>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php /* TODO Task 16: schedule section (month groups + cards + empty state) */ ?>

</main>

<?php
get_footer();
```

- [ ] **Step 2: Verify**

Visit `/teaching/`. The intro header (eyebrow, "Teaching", paragraphs) and the signup band render (unstyled — CSS in Task 18). The four interest checkboxes appear. The schedule area is empty (Task 16). No PHP errors.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add archive-teaching_event.php
git commit -m "feat(teaching): archive template — intro header + signup band"
```

---

### Task 16: `archive-teaching_event.php` — schedule (month groups, cards, empty state)

**Files:**
- Modify: `archive-teaching_event.php` (replace the `/* TODO Task 16 */` placeholder)

- [ ] **Step 1: Replace the TODO with the schedule section**

```php
    <section class="te-schedule">
        <div class="te-container">
            <div class="te-schedule__head">
                <h2 class="te-schedule__title">Upcoming classes</h2>
                <p class="te-legend">
                    <span class="te-dot te-dot--home" aria-hidden="true"></span> Home workshop
                    <span class="te-dot te-dot--festival" aria-hidden="true"></span> Festival
                </p>
            </div>

            <?php
            $events = lieuwe_teaching_get_upcoming_events();
            if ( ! $events->have_posts() ) :
                ?>
                <div class="te-empty">
                    <div class="te-empty__glyph" aria-hidden="true">🪚</div>
                    <h3 class="te-empty__title">Nothing on the calendar right now</h3>
                    <p class="te-empty__text">New classes go up through the year. Leave your email above and I'll let you know as soon as the next dates are set.</p>
                    <a class="te-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Or get in touch →</a>
                </div>
                <?php
            else :
                $current_month = '';
                $first         = true;
                while ( $events->have_posts() ) :
                    $events->the_post();
                    $id          = get_the_ID();
                    $type        = (string) get_post_meta( $id, '_te_type', true );
                    $is_festival = 'festival' === $type;
                    $start       = (string) get_post_meta( $id, '_te_start_date', true );
                    $month       = $start ? date_i18n( 'F Y', strtotime( $start ) ) : '';
                    $date_text   = (string) get_post_meta( $id, '_te_date_text', true );
                    $time_text   = (string) get_post_meta( $id, '_te_time_text', true );
                    $where       = (string) get_post_meta( $id, '_te_where', true );
                    $blurb       = (string) get_post_meta( $id, '_te_blurb', true );
                    $open        = (int) get_post_meta( $id, '_te_spots_open', true );
                    $ticket      = (string) get_post_meta( $id, '_te_ticket_url', true );
                    $thumb       = get_the_post_thumbnail_url( $id, 'medium' );
                    $sold_out    = ! $is_festival && $open <= 0;

                    if ( $month !== $current_month ) :
                        if ( ! $first ) {
                            echo '</div></div>'; // close prev .te-month__events + .te-month
                        }
                        $current_month = $month;
                        $first         = false;
                        echo '<div class="te-month"><div class="te-month__label">' . esc_html( $month ) . '</div><div class="te-month__events">';
                    endif;
                    ?>
                    <article class="te-event te-event--<?php echo $is_festival ? 'festival' : 'home'; ?>">
                        <div class="te-event__thumb">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="">
                            <?php else : ?>
                                <div class="te-event__thumb--empty" aria-hidden="true"></div>
                            <?php endif; ?>
                        </div>
                        <div class="te-event__body">
                            <div class="te-event__titlerow">
                                <h3 class="te-event__title"><?php the_title(); ?></h3>
                                <?php if ( $sold_out ) : ?>
                                    <span class="te-tag te-tag--full">Fully booked</span>
                                <?php elseif ( $is_festival ) : ?>
                                    <span class="te-tag te-tag--festival">Festival</span>
                                <?php else : ?>
                                    <span class="te-tag te-tag--home">Home workshop</span>
                                <?php endif; ?>
                            </div>
                            <p class="te-event__meta">
                                <?php
                                echo esc_html( implode( ' · ', array_filter( [ $date_text, $time_text, $where ] ) ) );
                                ?>
                            </p>
                            <?php if ( $blurb ) : ?>
                                <p class="te-event__blurb"><?php echo esc_html( $blurb ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="te-event__cta">
                            <?php if ( $is_festival ) : ?>
                                <a class="te-btn te-btn--festival" href="<?php echo esc_url( $ticket ?: home_url( '/contact/' ) ); ?>" target="_blank" rel="noopener">Festival tickets ↗</a>
                            <?php elseif ( $sold_out ) : ?>
                                <a class="te-btn te-btn--ghost" href="#te-signup">Join the list</a>
                            <?php else : ?>
                                <a class="te-btn te-btn--primary" href="<?php echo esc_url( get_permalink( $id ) ); ?>">Book a spot</a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php
                endwhile;
                if ( ! $first ) {
                    echo '</div></div>'; // close final month
                }
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </section>
```

- [ ] **Step 2: Verify**

Create three published classes: a home workshop in the current/next month (spots open), a festival (with ticket URL), and a home workshop with `spots_open = 0`. Visit `/teaching/`:
- They group under month labels in date order.
- Home → "Book a spot" links to its single page; festival → "Festival tickets ↗" opens the external URL; sold-out → "Fully booked" tag + "Join the list" jumps to `#te-signup`.
- Delete/unpublish all → the empty state renders.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add archive-teaching_event.php
git commit -m "feat(teaching): schedule — month grouping, event cards, empty state"
```

---

### Task 17: `single-teaching_event.php` — booking page (summary, form, sold-out, confirmation)

**Files:**
- Create: `single-teaching_event.php`

- [ ] **Step 1: Write the template**

```php
<?php
/**
 * Single class — the Book a Spot page (home workshops). Festivals redirect out.
 *
 * @package Lieuwe_Theme
 */

while ( have_posts() ) :
    the_post();
    $id     = get_the_ID();
    $type   = (string) get_post_meta( $id, '_te_type', true );
    $ticket = (string) get_post_meta( $id, '_te_ticket_url', true );

    // Festivals don't have a booking page — send visitors to the ticket link.
    if ( 'festival' === $type && $ticket ) {
        wp_safe_redirect( $ticket, 302 );
        exit;
    }

    get_header();

    $date_text    = (string) get_post_meta( $id, '_te_date_text', true );
    $time_text    = (string) get_post_meta( $id, '_te_time_text', true );
    $where        = (string) get_post_meta( $id, '_te_where', true );
    $includes     = (string) get_post_meta( $id, '_te_includes', true );
    $price        = (string) get_post_meta( $id, '_te_price', true );
    $blurb        = (string) get_post_meta( $id, '_te_blurb', true );
    $total        = (int) get_post_meta( $id, '_te_spots_total', true );
    $open         = (int) get_post_meta( $id, '_te_spots_open', true );
    $thumb        = get_the_post_thumbnail_url( $id, 'large' );
    $privacy_note = lieuwe_teaching_page_privacy_note();
    $booked_state = isset( $_GET['booked'] ) ? sanitize_key( wp_unslash( $_GET['booked'] ) ) : '';
    $sold_out     = $open <= 0;
    $archive_url  = get_post_type_archive_link( 'teaching_event' );
    ?>
    <main class="te te-book">
        <div class="te-container">
            <a class="te-book__back" href="<?php echo esc_url( $archive_url ); ?>">← Back to all classes</a>
            <header class="te-book__head">
                <p class="te-eyebrow">Book a spot</p>
                <h1 class="te-book__title"><?php the_title(); ?></h1>
            </header>

            <div class="te-book__grid">
                <aside class="te-summary">
                    <div class="te-summary__card">
                        <div class="te-summary__thumb">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="">
                            <?php else : ?>
                                <div class="te-summary__thumb--empty" aria-hidden="true"></div>
                            <?php endif; ?>
                        </div>
                        <span class="te-tag te-tag--home">Home workshop</span>
                        <dl class="te-summary__dl">
                            <?php if ( $date_text ) : ?><div><dt>Date</dt><dd><?php echo esc_html( $date_text ); ?></dd></div><?php endif; ?>
                            <?php if ( $time_text ) : ?><div><dt>Time</dt><dd><?php echo esc_html( $time_text ); ?></dd></div><?php endif; ?>
                            <?php if ( $where ) : ?><div><dt>Where</dt><dd><?php echo esc_html( $where ); ?></dd></div><?php endif; ?>
                            <?php if ( $includes ) : ?><div><dt>Includes</dt><dd><?php echo esc_html( $includes ); ?></dd></div><?php endif; ?>
                            <?php if ( $price ) : ?><div><dt>Price</dt><dd><?php echo esc_html( $price ); ?></dd></div><?php endif; ?>
                        </dl>
                        <?php if ( $total > 0 ) : ?>
                            <div class="te-spots">
                                <div class="te-spots__dots" aria-hidden="true">
                                    <?php for ( $i = 0; $i < $total; $i++ ) : ?>
                                        <span class="te-dot-spot <?php echo $i < $open ? 'is-open' : 'is-taken'; ?>"></span>
                                    <?php endfor; ?>
                                </div>
                                <p class="te-spots__label"><strong><?php echo esc_html( (string) $open ); ?> of <?php echo esc_html( (string) $total ); ?> spots</strong> still open</p>
                            </div>
                        <?php endif; ?>
                        <?php if ( $blurb ) : ?><p class="te-summary__blurb"><?php echo esc_html( $blurb ); ?></p><?php endif; ?>
                    </div>
                </aside>

                <div class="te-book__main">
                    <?php if ( '1' === $booked_state ) : ?>
                        <div class="te-confirm" role="status">
                            <div class="te-confirm__check" aria-hidden="true">✓</div>
                            <h2 class="te-confirm__title">Spot requested.</h2>
                            <p>Thanks — I've noted your request for <strong><?php the_title(); ?></strong>. I hold spots by hand and will be in touch by email to confirm.</p>
                            <div class="te-confirm__actions">
                                <a class="te-btn te-btn--primary" href="<?php echo esc_url( $archive_url ); ?>">Back to all classes</a>
                                <a class="te-btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get in touch</a>
                            </div>
                        </div>
                    <?php elseif ( $sold_out ) : ?>
                        <div class="te-book__full">
                            <h2 class="te-book__formtitle">This class is currently full.</h2>
                            <p>All spots are taken right now. Leave your email and I'll let you know about new dates.</p>
                            <a class="te-btn te-btn--primary" href="<?php echo esc_url( $archive_url ); ?>#te-signup">Join the list</a>
                        </div>
                    <?php else : ?>
                        <?php if ( 'err' === $booked_state ) : ?>
                            <p class="te-form-error" role="alert">Hmm, that didn't go through — please try again.</p>
                        <?php endif; ?>
                        <h2 class="te-book__formtitle">Request your spot</h2>
                        <p class="te-book__formintro">Spots are held by hand, so this sends a request rather than an instant booking. I'll confirm by email.</p>
                        <form class="te-booking" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
                            <input type="hidden" name="action" value="lieuwe_teaching_booking">
                            <input type="hidden" name="te_event_id" value="<?php echo esc_attr( (string) $id ); ?>">
                            <?php wp_nonce_field( 'lieuwe_teaching_booking', '_te_nonce' ); ?>
                            <input type="text" name="te_hp" class="te-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                            <input type="hidden" name="te_token" class="te-token" value="">
                            <div class="te-book__row">
                                <label class="te-field">Your name <input type="text" name="te_name" required></label>
                                <label class="te-field">Email <input type="email" name="te_email" required></label>
                            </div>
                            <div class="te-book__row">
                                <label class="te-field">Phone (optional) <input type="tel" name="te_phone"></label>
                                <label class="te-field">How many spots
                                    <select name="te_spots">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                    </select>
                                </label>
                            </div>
                            <label class="te-field">Dietary needs for lunch (optional) <input type="text" name="te_diet"></label>
                            <label class="te-field">Anything else (optional) <textarea name="te_note" rows="3"></textarea></label>
                            <div class="te-book__submit">
                                <button type="submit" class="te-btn te-btn--primary">Request your spot</button>
                                <span class="te-book__hint">No payment today · you can change your mind anytime</span>
                            </div>
                            <?php if ( $privacy_note ) : ?>
                                <p class="te-form-note"><?php echo esc_html( $privacy_note ); ?></p>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php
    get_footer();
endwhile;
```

- [ ] **Step 2: Verify**

From `/teaching/`, click a home workshop's "Book a spot". The booking page renders: summary card with the dl, the spots dots (open vs taken count matches meta), and the request form. Append `?booked=1` to the URL → the confirmation block shows instead of the form. Set the class's `spots_open` to 0 → the form is replaced by the "currently full" notice. Create a Festival class and visit its single URL → it 302-redirects to the ticket URL.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add single-teaching_event.php
git commit -m "feat(teaching): single class booking page (summary, form, sold-out, confirmation)"
```

---

### Task 18: CSS — tokens, buttons, intro header, signup band

**Files:**
- Modify: `assets/css/teaching.css`

- [ ] **Step 1: Replace the stub with the base layer**

```css
/* Teaching pages
   Loaded only on /teaching/ + single classes, so :root extras don't leak.
   Adapts the handoff palette onto the theme's oklch tokens.
   ========================================================================== */

:root {
    --te-card:            color-mix(in oklch, var(--color-bg) 70%, white);
    --te-rust-hover:      color-mix(in oklch, var(--color-accent), black 22%);
    --te-border:          color-mix(in oklch, var(--color-text) 16%, var(--color-bg));
    --te-border-soft:     color-mix(in oklch, var(--color-text) 9%, var(--color-bg));
    --te-faint:           color-mix(in oklch, var(--color-muted) 78%, var(--color-bg));
    --te-forest-border:   color-mix(in oklch, var(--color-forest) 55%, var(--color-bg));
    --te-tag-rust-border: color-mix(in oklch, var(--color-accent) 50%, var(--color-bg));
    --te-empty-border:    color-mix(in oklch, var(--color-text) 22%, var(--color-bg));
    --te-dot-empty:       color-mix(in oklch, var(--color-warm), var(--color-text) 18%);
    --te-scrim:           rgba(40, 30, 20, 0.55);
    --te-chip-bg:         color-mix(in oklch, var(--color-warm) 60%, var(--color-bg));
    --te-chip-border:     color-mix(in oklch, var(--color-accent) 30%, var(--color-bg));
}

.te { background: var(--color-bg); color: var(--color-text); font-family: var(--font-body); }
.te-container { max-width: 1060px; margin: 0 auto; padding: 0 28px; }
.visually-hidden {
    position: absolute !important; width: 1px; height: 1px;
    padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0;
}
.te-eyebrow {
    font: 700 13px/1 var(--font-body); letter-spacing: 0.18em;
    text-transform: uppercase; color: var(--color-accent); margin: 0 0 18px;
}

/* Buttons --------------------------------------------------------------- */
.te-btn {
    display: inline-flex; align-items: center; gap: 6px;
    font: 600 15px/1 var(--font-body); text-decoration: none;
    border: 1px solid transparent; border-radius: 3px; padding: 12px 22px;
    cursor: pointer; transition: background .18s, color .18s, border-color .18s;
}
.te-btn--primary  { background: var(--color-accent); color: var(--color-text-light); }
.te-btn--primary:hover { background: var(--te-rust-hover); }
.te-btn--festival { background: transparent; border: 1.5px solid var(--color-forest); color: var(--color-forest); }
.te-btn--festival:hover { background: var(--color-forest); color: var(--color-text-light); }
.te-btn--ghost    { background: transparent; border: 1px solid var(--te-border); color: var(--color-text); }
.te-btn--ghost:hover { border-color: var(--color-accent); color: var(--color-accent); }

/* Intro header ---------------------------------------------------------- */
.te-intro { padding: 150px 0 56px; }
.te-intro__grid {
    display: grid; grid-template-columns: minmax(0,1.1fr) minmax(0,0.9fr);
    gap: 56px; align-items: center;
}
.te-intro__title { font: 400 clamp(44px,6vw,58px)/1.05 var(--font-display); letter-spacing: -0.01em; margin: 0 0 24px; }
.te-intro__p { font: 400 19px/1.65 var(--font-display); color: var(--color-text); max-width: 52ch; margin: 0 0 16px; }
.te-intro__figure { margin: 0; }
.te-intro__img { width: 100%; height: 400px; object-fit: cover; border-radius: 6px; display: block; }
.te-intro__img--empty { background: var(--color-warm); }
.te-intro__caption { font: italic 400 14px/1.5 var(--font-display); color: var(--color-muted); margin: 10px 0 0; }

/* Signup band ----------------------------------------------------------- */
.te-band {
    background: var(--color-surface);
    border-top: 1px solid var(--te-border); border-bottom: 1px solid var(--te-border);
    padding: 52px 0; margin: 32px 0;
}
.te-band__grid { display: grid; grid-template-columns: minmax(0,0.85fr) minmax(0,1.15fr); gap: 48px; align-items: start; }
.te-band__heading { font: 400 32px/1.1 var(--font-display); margin: 0 0 14px; }
.te-band__intro { font: 400 17px/1.6 var(--font-display); color: var(--color-text); max-width: 46ch; margin: 0; }

.te-signup__row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.te-field { display: grid; gap: 6px; font: 700 13px/1 var(--font-body); letter-spacing: 0.04em; color: var(--te-faint); }
.te-field--grow { flex: 1 1 260px; }
.te-signup input[type="email"],
.te-booking input[type="text"],
.te-booking input[type="email"],
.te-booking input[type="tel"],
.te-booking textarea,
.te-booking select {
    width: 100%; font: 400 16px/1.3 var(--font-body); color: var(--color-text);
    background: var(--te-card); border: 1px solid var(--te-border); border-radius: 3px; padding: 13px 16px;
}
.te-signup input:focus,
.te-booking input:focus,
.te-booking textarea:focus,
.te-booking select:focus { outline: none; border-color: var(--color-accent); background: #fff; }
.te-signup__interests { border: 0; padding: 0; margin: 0; display: flex; gap: 18px; flex-wrap: wrap; }
.te-check { display: inline-flex; align-items: center; gap: 8px; font: 400 14px/1 var(--font-body); color: var(--color-text); }
.te-check input { width: 17px; height: 17px; accent-color: var(--color-accent); }
.te-hp { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
.te-form-note { font: 400 12px/1.5 var(--font-body); color: var(--te-faint); margin: 14px 0 0; }
.te-form-error { font: 400 14px var(--font-body); color: var(--color-accent); margin: 0 0 12px; }
.te-confirm-inline {
    background: var(--te-card); border: 1px solid var(--te-border); border-radius: 5px;
    padding: 18px 20px; font: 400 16px/1.5 var(--font-display); color: var(--color-text);
}
```

- [ ] **Step 2: Verify**

Reload `/teaching/`. Intro header sits in two columns with the photo (or warm placeholder) on the right. The band is a full-width ochre block with the form; the email input + "Keep me posted" button sit on one row, checkboxes wrap below. Buttons show the terracotta fill.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add assets/css/teaching.css
git commit -m "feat(teaching): CSS tokens, buttons, intro header, signup band"
```

---

### Task 19: CSS — schedule, event cards, tags, empty state

**Files:**
- Modify: `assets/css/teaching.css`

- [ ] **Step 1: Append the schedule styles**

```css
/* Schedule -------------------------------------------------------------- */
.te-schedule { padding: 24px 0 80px; }
.te-schedule__head {
    display: flex; justify-content: space-between; align-items: baseline; gap: 24px;
    border-bottom: 2px solid var(--color-text); padding-bottom: 14px; margin-bottom: 4px; flex-wrap: wrap;
}
.te-schedule__title { font: 400 34px/1 var(--font-display); margin: 0; }
.te-legend { font: 400 13px/1 var(--font-body); color: var(--color-muted); display: flex; align-items: center; gap: 8px; }
.te-dot { width: 9px; height: 9px; border-radius: 999px; display: inline-block; }
.te-dot--home { background: var(--color-accent); }
.te-dot--festival { background: var(--color-forest); margin-left: 12px; }

.te-month { display: grid; grid-template-columns: 120px minmax(0,1fr); gap: 24px; border-bottom: 1px solid var(--te-border); }
.te-month__label { font: 700 13px/1.4 var(--font-body); letter-spacing: 0.16em; text-transform: uppercase; color: var(--te-faint); padding-top: 32px; }
.te-month__events { display: flex; flex-direction: column; }

.te-event { display: grid; grid-template-columns: 124px minmax(0,1fr) auto; gap: 26px; align-items: center; padding: 28px 0; border-top: 1px solid var(--te-border-soft); }
.te-month__events .te-event:first-child { border-top: 0; }
.te-event__thumb img, .te-event__thumb--empty { width: 124px; height: 124px; object-fit: cover; border-radius: 6px; display: block; }
.te-event__thumb--empty { background: var(--color-warm); }
.te-event__titlerow { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 6px; }
.te-event__title { font: 400 24px/1.1 var(--font-display); margin: 0; }
.te-event__meta { font: 400 14px/1.4 var(--font-body); color: var(--color-muted); margin: 0 0 8px; }
.te-event__blurb { font: 400 17px/1.55 var(--font-display); color: var(--color-text); max-width: 52ch; margin: 0; }

.te-tag { font: 700 11.5px/1 var(--font-body); letter-spacing: 0.1em; text-transform: uppercase; padding: 5px 10px; border-radius: 999px; border: 1px solid; }
.te-tag--home { color: var(--color-accent); border-color: var(--te-tag-rust-border); }
.te-tag--festival { color: var(--color-forest); border-color: var(--te-forest-border); }
.te-tag--full { color: var(--color-muted); border-color: var(--te-border); }

.te-empty { border: 1px dashed var(--te-empty-border); border-radius: 6px; padding: 64px; text-align: center; margin-top: 24px; }
.te-empty__glyph { font-size: 32px; margin-bottom: 12px; }
.te-empty__title { font: 400 26px/1.1 var(--font-display); margin: 0 0 10px; }
.te-empty__text { font: 400 17px/1.6 var(--font-display); color: var(--color-muted); max-width: 42ch; margin: 0 auto 16px; }
.te-link { color: var(--color-accent); text-decoration: none; font: 400 15px var(--font-body); }
.te-link:hover { color: var(--te-rust-hover); }
```

- [ ] **Step 2: Verify**

Reload `/teaching/` with the three test classes. Month labels sit in the left column; cards stack on the right with hairline separators. The 124px thumbnail, title + tag row, meta line, and right-aligned CTA all line up. Festival tag is green; sold-out tag is muted. With no classes, the dashed empty panel is centered.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add assets/css/teaching.css
git commit -m "feat(teaching): CSS schedule, event cards, tags, empty state"
```

---

### Task 20: CSS — booking page + confirmations

**Files:**
- Modify: `assets/css/teaching.css`

- [ ] **Step 1: Append the booking + confirmation styles**

```css
/* Booking page ---------------------------------------------------------- */
.te-book { padding: 140px 0 80px; }
.te-book__back { font: 400 14px var(--font-body); color: var(--color-muted); text-decoration: none; display: inline-block; margin-bottom: 24px; }
.te-book__back:hover { color: var(--color-accent); }
.te-book__head { margin-bottom: 36px; }
.te-book__title { font: 400 clamp(36px,5vw,46px)/1.05 var(--font-display); margin: 6px 0 0; }
.te-book__grid { display: grid; grid-template-columns: minmax(0,0.9fr) minmax(0,1.1fr); gap: 52px; align-items: start; }

.te-summary { position: sticky; top: 88px; }
.te-summary__card { background: var(--te-card); border: 1px solid var(--te-border); border-radius: 5px; padding: 24px; }
.te-summary__thumb img, .te-summary__thumb--empty { width: 100%; height: 220px; object-fit: cover; border-radius: 4px; display: block; margin-bottom: 16px; }
.te-summary__thumb--empty { background: var(--color-warm); }
.te-summary__dl { margin: 16px 0 0; }
.te-summary__dl > div { display: grid; grid-template-columns: 92px 1fr; gap: 10px; padding: 8px 0; border-top: 1px solid var(--te-border-soft); }
.te-summary__dl > div:first-of-type { border-top: 0; }
.te-summary__dl dt { font: 700 13px/1.4 var(--font-body); letter-spacing: 0.04em; color: var(--te-faint); margin: 0; }
.te-summary__dl dd { font: 400 14.5px/1.4 var(--font-body); color: var(--color-text); margin: 0; }
.te-spots { margin: 18px 0 0; }
.te-spots__dots { display: flex; gap: 6px; margin-bottom: 8px; flex-wrap: wrap; }
.te-dot-spot { width: 9px; height: 9px; border-radius: 999px; }
.te-dot-spot.is-open { background: var(--color-accent); }
.te-dot-spot.is-taken { background: var(--te-dot-empty); }
.te-spots__label { font: 400 14px var(--font-body); color: var(--color-muted); margin: 0; }
.te-summary__blurb { font: italic 400 16px/1.55 var(--font-display); color: var(--color-muted); margin: 16px 0 0; }

.te-book__formtitle { font: 400 28px/1.1 var(--font-display); margin: 0 0 8px; }
.te-book__formintro { font: 400 16px/1.6 var(--font-display); color: var(--color-muted); max-width: 52ch; margin: 0 0 24px; }
.te-booking .te-field { margin-bottom: 18px; }
.te-book__row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.te-booking select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238a4b22' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 40px;
}
.te-book__submit { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
.te-book__hint { font: 400 13px var(--font-body); color: var(--te-faint); }

.te-book__full { background: var(--te-card); border: 1px solid var(--te-border); border-radius: 5px; padding: 32px; }
.te-book__full p { font: 400 16px/1.6 var(--font-display); color: var(--color-muted); margin: 8px 0 20px; }

.te-confirm { background: var(--te-card); border: 1px solid var(--te-border); border-radius: 5px; padding: 36px; }
.te-confirm__check { width: 46px; height: 46px; border-radius: 999px; background: var(--color-accent); color: var(--color-text-light); display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 18px; }
.te-confirm__title { font: 400 31px/1.1 var(--font-display); margin: 0 0 12px; }
.te-confirm p { font: 400 17px/1.6 var(--font-display); color: var(--color-text); margin: 0 0 20px; }
.te-confirm__actions { display: flex; gap: 12px; flex-wrap: wrap; }
```

- [ ] **Step 2: Verify**

Open a home-workshop booking page. The sticky summary card (photo, "Home workshop" pill, the Date/Time/Where/Includes/Price list, the spot dots showing open vs taken, blurb) sits left; the request form sits right with the custom rust select chevron. Append `?booked=1` → the confirmation card renders with the rust check circle. Set `spots_open = 0` → the "currently full" panel renders.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add assets/css/teaching.css
git commit -m "feat(teaching): CSS booking page, summary card, spot dots, confirmation"
```

---

### Task 21: CSS — popup modal, animations, responsive

**Files:**
- Modify: `assets/css/teaching.css`

- [ ] **Step 1: Append the modal + animations + breakpoints**

```css
/* Popup modal (built by teaching.js) ------------------------------------ */
.te-modal { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
.te-modal__scrim { position: absolute; inset: 0; background: var(--te-scrim); backdrop-filter: blur(2px); animation: te-scrim-in .22s ease-out; }
.te-modal__card {
    position: relative; width: 100%; max-width: 460px;
    background: var(--te-card); border: 1px solid var(--te-border); border-radius: 8px;
    box-shadow: 0 24px 60px rgba(40,30,20,0.32); padding: 40px;
    animation: te-card-in .28s cubic-bezier(0.16,1,0.3,1);
}
.te-modal__close { position: absolute; top: 14px; right: 14px; width: 34px; height: 34px; border: 0; background: transparent; border-radius: 999px; cursor: pointer; font-size: 16px; color: var(--color-muted); }
.te-modal__close:hover { background: var(--color-surface); }
.te-modal__check { width: 52px; height: 52px; border-radius: 999px; background: var(--color-accent); color: var(--color-text-light); display: flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 18px; }
.te-modal__title { font: 400 31px/1.1 var(--font-display); margin: 0 0 12px; }
.te-modal__text { font: 400 17px/1.6 var(--font-display); color: var(--color-text); margin: 0 0 18px; }
.te-modal__email { font-family: var(--font-body); font-weight: 600; }
.te-modal__chips { display: flex; gap: 8px; flex-wrap: wrap; margin: 0 0 20px; }
.te-chip { font: 400 13px var(--font-body); background: var(--te-chip-bg); border: 1px solid var(--te-chip-border); border-radius: 999px; padding: 5px 12px; }
.te-modal__footer { border-top: 1px solid var(--te-border-soft); padding-top: 18px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.te-modal__hint { font: 400 12px var(--font-body); color: var(--te-faint); }
body.te-modal-open { overflow: hidden; }

@keyframes te-scrim-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes te-card-in { from { opacity: 0; transform: translateY(14px) scale(.985); } to { opacity: 1; transform: none; } }
@media (prefers-reduced-motion: reduce) {
    .te-modal__scrim, .te-modal__card { animation: none; }
}

/* Responsive ------------------------------------------------------------ */
@media (max-width: 880px) {
    .te-intro { padding-top: 120px; }
    .te-intro__grid,
    .te-band__grid,
    .te-book__grid { grid-template-columns: 1fr; }
    .te-summary { position: static; }
    .te-month { grid-template-columns: 1fr; gap: 0; }
    .te-month__label { padding-top: 24px; padding-bottom: 4px; }
    .te-event { grid-template-columns: 1fr; gap: 14px; }
    .te-event__cta .te-btn { width: 100%; justify-content: center; }
    .te-book__row { grid-template-columns: 1fr; }
}
```

- [ ] **Step 2: Verify (visual only; the popup fires once JS lands in Task 22)**

No regression: `/teaching/` and a booking page still render correctly. Narrow the viewport below 880px → the two-column grids collapse to one column, the event card stacks (thumb, text, full-width CTA), the summary card un-sticks.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add assets/css/teaching.css
git commit -m "feat(teaching): CSS popup modal, animations, responsive breakpoints"
```

---

### Task 22: JS — reCAPTCHA token, signup AJAX, popup modal

**Files:**
- Modify: `assets/js/teaching.js`

- [ ] **Step 1: Replace the stub with the shared helpers + signup + popup (booking stub included)**

```js
(function () {
    'use strict';

    var CFG = window.lieuweTeaching || {};
    var LABELS = {
        'spoon-carving':       'spoon carving',
        'japanese-lacquering': 'Japanese lacquering',
        'sandalmaking':        'sandalmaking',
        'general':             'general updates'
    };

    // Resolve a reCAPTCHA v3 token for an action; '' when reCAPTCHA isn't present.
    function token(action) {
        return new Promise(function (resolve) {
            if (!CFG.recaptchaKey || !window.grecaptcha || !window.grecaptcha.execute) {
                resolve('');
                return;
            }
            window.grecaptcha.ready(function () {
                window.grecaptcha.execute(CFG.recaptchaKey, { action: action })
                    .then(resolve)
                    .catch(function () { resolve(''); });
            });
        });
    }

    function postForm(form) {
        var data = new FormData(form);
        data.append('te_ajax', '1');
        return fetch(CFG.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function showFormError(form, msg) {
        var box = form.parentNode.querySelector('.te-form-error');
        if (!box) {
            box = document.createElement('p');
            box.className = 'te-form-error';
            box.setAttribute('role', 'alert');
            form.parentNode.insertBefore(box, form);
        }
        box.textContent = msg;
    }

    function interestLine(keys) {
        var names = keys.map(function (k) { return LABELS[k] || k; });
        if (names.length === 0) { return "I'll give you a shout the moment new dates go up."; }
        if (names.length === 1) { return "I'll let you know as soon as new " + names[0] + " dates go up."; }
        if (names.length === 2) { return "I'll let you know when new " + names[0] + " or " + names[1] + " dates go up."; }
        return "I'll give you a shout the moment new dates go up across the crafts you picked.";
    }

    // ---- Popup ----
    var lastFocus = null;
    function openPopup(email, interests) {
        lastFocus = document.activeElement;

        var modal = document.createElement('div');
        modal.className = 'te-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-label', 'Signup confirmed');

        var scrim = document.createElement('div');
        scrim.className = 'te-modal__scrim';
        modal.appendChild(scrim);

        var card = document.createElement('div');
        card.className = 'te-modal__card';
        modal.appendChild(card);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'te-modal__close';
        close.setAttribute('aria-label', 'Close');
        close.textContent = '✕';
        card.appendChild(close);

        var check = document.createElement('div');
        check.className = 'te-modal__check';
        check.setAttribute('aria-hidden', 'true');
        check.textContent = '✓';
        card.appendChild(check);

        var h = document.createElement('h2');
        h.className = 'te-modal__title';
        h.textContent = "Right, you're on the list.";
        card.appendChild(h);

        var p = document.createElement('p');
        p.className = 'te-modal__text';
        p.appendChild(document.createTextNode(interestLine(interests) + ' '));
        if (email) {
            p.appendChild(document.createTextNode('I’ll write to '));
            var em = document.createElement('span');
            em.className = 'te-modal__email';
            em.textContent = email;
            p.appendChild(em);
            p.appendChild(document.createTextNode('.'));
        }
        card.appendChild(p);

        if (interests.length) {
            var chips = document.createElement('div');
            chips.className = 'te-modal__chips';
            interests.forEach(function (k) {
                var c = document.createElement('span');
                c.className = 'te-chip';
                c.textContent = LABELS[k] || k;
                chips.appendChild(c);
            });
            card.appendChild(chips);
        }

        var footer = document.createElement('div');
        footer.className = 'te-modal__footer';
        var ok = document.createElement('button');
        ok.type = 'button';
        ok.className = 'te-btn te-btn--primary';
        ok.textContent = 'Lovely, thanks';
        footer.appendChild(ok);
        var hint = document.createElement('span');
        hint.className = 'te-modal__hint';
        hint.textContent = 'No spam — just class dates.';
        footer.appendChild(hint);
        card.appendChild(footer);

        document.body.appendChild(modal);
        document.body.classList.add('te-modal-open');
        ok.focus();

        function destroy() {
            document.body.classList.remove('te-modal-open');
            modal.remove();
            document.removeEventListener('keydown', onKey);
            if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        }
        function onKey(e) {
            if (e.key === 'Escape') {
                destroy();
            } else if (e.key === 'Tab') {
                // Trap focus between the only two focusables: close + ok.
                if (e.shiftKey && document.activeElement === close) { e.preventDefault(); ok.focus(); }
                else if (!e.shiftKey && document.activeElement === ok) { e.preventDefault(); close.focus(); }
            }
        }
        close.addEventListener('click', destroy);
        ok.addEventListener('click', destroy);
        scrim.addEventListener('click', destroy);
        card.addEventListener('click', function (e) { e.stopPropagation(); });
        document.addEventListener('keydown', onKey);
    }

    // ---- Signup ----
    function initSignup() {
        var form = document.querySelector('.te-signup');
        if (!form) { return; }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.reportValidity && !form.reportValidity()) { return; }

            var email = (form.querySelector('input[name="te_email"]') || {}).value || '';
            var interests = Array.prototype.slice
                .call(form.querySelectorAll('input[name="te_interests[]"]:checked'))
                .map(function (i) { return i.value; });

            token('signup').then(function (t) {
                var field = form.querySelector('.te-token');
                if (field) { field.value = t; }
                postForm(form).then(function (res) {
                    if (res && res.success) {
                        var data = res.data || {};
                        var box = document.createElement('div');
                        box.className = 'te-confirm-inline';
                        box.setAttribute('role', 'status');
                        var msg = document.createElement('p');
                        msg.textContent = "Right, you're on the list. I'll be in touch when new dates go up.";
                        box.appendChild(msg);
                        form.parentNode.replaceChild(box, form);
                        openPopup(data.email || email, data.interests || interests);
                    } else {
                        showFormError(form, (res && res.data && res.data.message) || 'Something went wrong. Please try again.');
                    }
                }).catch(function () { showFormError(form, 'Network error — please try again.'); });
            });
        });
    }

    // Filled in Task 23.
    function initBooking() {}

    document.addEventListener('DOMContentLoaded', function () {
        initSignup();
        initBooking();
    });

    // Expose for Task 23's implementation to reference shared helpers.
    window.LieuweTeachingInternal = { token: token, postForm: postForm, showFormError: showFormError };
})();
```

- [ ] **Step 2: Verify**

On `/teaching/`, submit the signup form with a valid email and 0–3 interests checked. The form is replaced by the inline "you're on the list" card **and** the popup appears with the correct dynamic interest line, the captured email in bold, and a chip per interest. Esc / the ✕ / clicking the scrim / "Lovely, thanks" all close it; focus returns to the form area. Check **Classes → Signups** in admin — a record exists with the right email + interests; the notification + auto-reply emails are sent (check your mail / mail log).

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add assets/js/teaching.js
git commit -m "feat(teaching): JS — reCAPTCHA token, signup AJAX, confirmation popup"
```

---

### Task 23: JS — booking AJAX + confirmation swap

**Files:**
- Modify: `assets/js/teaching.js` (replace the `function initBooking() {}` stub)

- [ ] **Step 1: Replace the empty `initBooking` stub with the full implementation**

Find `// Filled in Task 23.` + `function initBooking() {}` and replace that one line of stub with:

```js
    // ---- Booking ----
    function initBooking() {
        var form = document.querySelector('.te-booking');
        if (!form) { return; }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (form.reportValidity && !form.reportValidity()) { return; }

            var name = (form.querySelector('input[name="te_name"]') || {}).value || '';

            token('booking').then(function (t) {
                var field = form.querySelector('.te-token');
                if (field) { field.value = t; }
                postForm(form).then(function (res) {
                    if (res && res.success) {
                        swapBookingConfirm(form, (res.data && res.data.name) || name);
                    } else {
                        showFormError(form, (res && res.data && res.data.message) || 'Something went wrong. Please try again.');
                    }
                }).catch(function () { showFormError(form, 'Network error — please try again.'); });
            });
        });
    }

    function swapBookingConfirm(form, name) {
        var first = (name || '').split(' ')[0];
        var titleEl = document.querySelector('.te-book__title');
        var classTitle = titleEl ? titleEl.textContent : '';
        var backEl = document.querySelector('.te-book__back');
        var main = form.closest('.te-book__main') || form.parentNode;

        var box = document.createElement('div');
        box.className = 'te-confirm';
        box.setAttribute('role', 'status');

        var check = document.createElement('div');
        check.className = 'te-confirm__check';
        check.setAttribute('aria-hidden', 'true');
        check.textContent = '✓';
        box.appendChild(check);

        var h = document.createElement('h2');
        h.className = 'te-confirm__title';
        h.textContent = 'Spot requested' + (first ? ', ' + first : '') + '.';
        box.appendChild(h);

        var p = document.createElement('p');
        p.textContent = "Thanks — I've noted your request" + (classTitle ? ' for ' + classTitle : '')
            + '. I hold spots by hand and will be in touch by email to confirm.';
        box.appendChild(p);

        var actions = document.createElement('div');
        actions.className = 'te-confirm__actions';
        var a = document.createElement('a');
        a.className = 'te-btn te-btn--primary';
        a.textContent = 'Back to all classes';
        a.href = backEl ? backEl.getAttribute('href') : '/teaching/';
        actions.appendChild(a);
        box.appendChild(actions);

        main.innerHTML = '';
        main.appendChild(box);
    }
```

- [ ] **Step 2: Verify**

On a home-workshop booking page, fill name + email and submit. The form is replaced in place by the confirmation card ("Spot requested, {firstName}." + the class title). Check **Classes → Bookings** — the record exists with name, email, phone, spots, the linked class, dietary, note. Notification + auto-reply emails fire. Disable JS in the browser and submit again → the page reloads to the server-rendered confirmation (`?booked=1`), and a record is still created. Tamper with the honeypot (`document.querySelector('.te-hp').value='x'` before submit) → response looks successful but **no** new record is stored.

- [ ] **Step 3: Commit (theme repo)**

```bash
cd ~/projects/lieuwe-theme
git add assets/js/teaching.js
git commit -m "feat(teaching): JS — booking AJAX + in-page confirmation swap"
```

---

### Task 24: Full acceptance pass + versioning + tags + deploy notes

**Files:** none (verification + release)

- [ ] **Step 1: Run the full acceptance checklist (from the spec)**

Work through every item; fix and re-verify any failure before tagging:

1. Activate plugin → "Classes" in admin (hammer icon); `/teaching/` resolves.
2. Home-workshop event with all fields + featured image → card under its month, in date order.
3. "Book a spot" → single page renders summary card (dl + dots + "N of M spots") + form.
4. Booking with JS on → confirmation swap (first name, class title); record in Bookings; admin + visitor emails arrive.
5. Booking with JS off → server-side confirmation renders; record + emails still created.
6. Festival event with ticket URL → green tag + "Festival tickets ↗"; single URL 302-redirects out.
7. Signup with 0/1/2/3 interests → popup shows correct dynamic line each time; chips correct; record stored; both emails arrive.
8. Popup: Esc, scrim, "Lovely, thanks" all close; clicks inside don't; focus returns; body scroll locked while open.
9. Honeypot: filled `te_hp` → looks successful, **nothing stored**.
10. reCAPTCHA: with keys set, normal submit passes; confirm a low score is rejected with the friendly message.
11. Sold-out: `spots_open = 0` → "Fully booked" tag + booking page "join the list" notice.
12. Empty state: no upcoming events → intro + band + empty panel.
13. Admin lists: Signups + Bookings columns correct; Bookings "Class" links to the event; "Export CSV" downloads valid CSV.
14. Customizer "Teaching page": change every field → preview + persists.
15. Privacy: Tools → Export/Erase Personal Data for a test email → signup + booking records included/erased.
16. Styling: colours/type read as the same site; festival green via `--color-forest`.
17. Mobile (iOS Safari + Android Chrome): grids + event card collapse to one column; popup usable; forms submit.
18. Perf: `teaching.css` / `teaching.js` / reCAPTCHA load **only** on Teaching pages, not the homepage.

- [ ] **Step 2: Tag the plugin (v1.0.0) and push**

The plugin needs a GitHub repo for the downloadable zip (mirrors how the theme deploys). Create it once, then:

```bash
cd ~/projects/lieuwe-teaching
gh repo create lieuwe89/lieuwe-teaching --private --source=. --remote=origin --push
git tag -a v1.0.0 HEAD -m "Release v1.0.0 — Teaching plugin (events, signup + booking capture, CSV, GDPR)"
git push origin main --tags
```

- [ ] **Step 3: Tag the theme (v1.14.0) and push**

`style.css` was bumped to 1.14.0 in Task 13 — do **not** bump again. Tag once, now:

```bash
cd ~/projects/lieuwe-theme
git tag -a v1.14.0 HEAD -m "Release v1.14.0 — Teaching pages (templates, styling) for the lieuwe-teaching plugin"
git push origin main --tags
```

- [ ] **Step 4: Deploy notes (record in the PR / handoff, no code change)**

Deploy order on the live site:
1. **Plugins → Add New → Upload** the `lieuwe-teaching` v1.0.0 zip from the GitHub release; activate it (creates the CPT + `/teaching/` route, flushes rewrites on activation).
2. **Appearance → Themes → Upload** the theme v1.14.0 zip (provides the styled templates).
3. In **Classes → Settings**, paste the reCAPTCHA v3 site + secret keys (same as the contact form) and confirm the notify email.
4. **Appearance → Menus**: add a "Teaching" item pointing at the `/teaching/` archive (the plugin only auto-injects it when the primary menu is otherwise empty).
5. Add the first real classes; set spots by hand.

---

## Self-review notes (author check against the spec)

- **Plugin owns data, theme owns looks** — Tasks 1–12 (plugin) vs 13–23 (theme). ✓
- **Three CPTs** — Task 2. **Event meta** — Tasks 3–4. **Private submissions** — Task 2 (registration) + stored in Tasks 7–8. ✓
- **Store in WP + email + visitor auto-reply** — Tasks 7–9. ✓
- **reCAPTCHA v3 (own handle) + honeypot + nonce** — Tasks 5–8. ✓
- **CSV export** — Task 10. **GDPR export/erase** — Task 11. **Fallback templates** — Task 12. ✓
- **Adapt to theme fonts/palette + `--color-forest`** — Tasks 13, 18–21. ✓
- **Archive (intro+band+schedule+empty), single (festival redirect, summary, form, sold-out, confirmation)** — Tasks 15–17. ✓
- **Popup with dynamic interest line, focus trap, dismiss** — Tasks 21–22. ✓
- **Progressive enhancement (no-JS PRG + JS AJAX)** — handlers Tasks 7–8, JS Tasks 22–23, templates read `?te_signup` / `?booked`. ✓
- **Versioning: theme bumped once (Task 13), tagged at end (Task 24); plugin v1.0.0** — ✓ (matches the "bump once, tag at end" convention).
- Names (functions, meta keys, nonce actions, handles, interest keys) are consistent across plugin handlers, templates, and JS per the locked list in the header. ✓




