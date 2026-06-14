# Teaching / Classes Feature — Design Spec

Date: 2026-06-14
Target release: plugin `lieuwe-teaching` `v1.0.0` + theme `v1.14.0`
Status: approved (brainstorming)

## Summary

A new `/teaching/` experience for **lieuwejongsma.nl** — Lieuwe's classes and workshops. Three connected surfaces:

1. **Teaching landing** — intro header, an email-signup band ("Hear about new classes" with interest checkboxes), and a month-grouped schedule of upcoming classes (home workshops + festival appearances). Has a populated state and an empty state.
2. **Book a Spot page** — a per-class booking *request* page (spots are held by hand, not instant checkout). Submits a request, then swaps to an in-page confirmation.
3. **Email-confirmation popup** — a modal fired when the signup form is submitted; echoes the crafts ticked and the email captured.

The visual design, copy, exact colours/type/spacing, and interaction model are fully specified in the design handoff at `docs/design_handoff_teaching_feature/README.md` and the working prototypes (`prototypes/teaching-page.html`, `prototypes/book-a-spot.html`). **This document does not re-state the visual spec** — it captures the project-specific decisions made during brainstorming and the architecture for porting the prototypes into a plugin + theme.

**Read this with the handoff README open** — the README is the design source of truth; this doc is the build plan. The one deliberate divergence from the handoff: fonts and palette are adapted to the existing theme (see "Styling").

## Approved decisions (from brainstorming)

| Decision | Choice |
|---|---|
| Where it lives | **Standalone plugin `lieuwe-teaching`** owns CPTs, form handlers, storage, admin screens, email, CSV export, GDPR hooks. **Theme** owns templates, CSS, popup/form JS. Data survives a theme change. Mirrors how the Portfolio Canvas plugin is split from the theme. |
| Submission storage | **Private CPTs** (`class_signup`, `booking_request`) — browsable, searchable, and exportable in wp-admin for free. Not custom DB tables. |
| Notification | Every submission is **stored in WP *and* emailed** to the admin via `wp_mail`. |
| Visitor auto-reply | **Yes** — a confirmation email is also sent to the visitor, for both signup and booking. |
| Spam protection | **reCAPTCHA v3** (verified server-side) as the gate, **+ honeypot + nonce** as a universal floor. Plugin loads reCAPTCHA under its own script handle on Teaching pages only. |
| Fonts / palette | **Adapt to the theme** — Sorts Mill Goudy + Jost + oklch warm palette. No new font families, no Google Fonts CDN. One new token added: `--color-forest` (festival accent). |
| Spots | Manual `spots_total` / `spots_open` meta. Bookings **do not** auto-decrement — Lieuwe holds spots by hand. |
| Event dates | **Structured start date** (machine-sortable; drives month-grouping, ordering, upcoming/empty logic) **+ free-text display date** (covers multi-date classes like "two Saturdays"). |
| Festival events | CTA links to an external ticket URL; no booking page. The CPT single view 302-redirects to that URL. |
| Teaching landing | The **CPT archive** (`archive-teaching_event.php`). Intro + signup band copy editable via Customizer. |
| Booking page | The **CPT single** (`single-teaching_event.php`), for home workshops. Each event *is* the booking-page parameters. |
| Sold-out classes | When `spots_open <= 0`: schedule card shows a "Fully booked" tag and the CTA points to the signup band instead of the booking page; booking page shows a "currently full — join the list" notice in place of the form. (Addition beyond the handoff, which has no full state.) |
| Menu | Plugin registers the CPT + auto-includes `/teaching/` in the primary-menu fallback (same pattern as Publications). Document that the user adds a proper menu item via Appearance → Menus. |
| Seed data | None — empty install. User creates events in admin; archive shows the empty state until then. |
| Newsletter sync | Out of scope. Signups are a stored, exportable list; Mailchimp/etc. is a clean future add. |

## Architecture: plugin + theme split

```
wp-content/
├── plugins/
│   └── lieuwe-teaching/                 # NEW plugin — its own repo, its own version + zip
│       ├── lieuwe-teaching.php          # Bootstrap: plugin header, constants, requires, activation hook
│       ├── inc/
│       │   ├── cpt.php                  # Register teaching_event (public) + class_signup, booking_request (private)
│       │   ├── event-meta.php           # Event meta box (type/dates/time/where/blurb/price/includes/spots/ticket URL) + save
│       │   ├── forms.php                # admin-post + AJAX handlers: signup + booking (nonce, honeypot, reCAPTCHA, sanitize, store)
│       │   ├── mail.php                 # wp_mail: admin notification + visitor auto-reply (signup + booking variants)
│       │   ├── recaptcha.php            # v3 enqueue (own handle, Teaching pages only) + server-side verify helper
│       │   ├── admin-lists.php          # Custom list columns for the two submission CPTs + "Export CSV"
│       │   ├── privacy.php              # WP privacy exporter + eraser for both submission CPTs
│       │   ├── settings.php             # Settings screen: reCAPTCHA site/secret keys, notify-to email
│       │   ├── helpers.php              # upcoming-events query, interest label map, sanitizers
│       │   └── template-loader.php      # Fallback templates if active theme lacks them (template_include filter)
│       └── templates/                   # Bare fallback archive/single — theme overrides these
│           ├── archive-teaching_event.php
│           └── single-teaching_event.php
└── themes/
    └── lieuwe-theme/                    # MODIFY
        ├── archive-teaching_event.php   # NEW: /teaching/ landing (intro + signup band + month schedule + empty state)
        ├── single-teaching_event.php    # NEW: Book a Spot page + confirmation state
        ├── inc/customizer.php           # MODIFY: add "Teaching page" section
        ├── style.css                    # MODIFY: bump Version 1.13.0 → 1.14.0; add --color-forest token(s)
        └── assets/
            ├── css/teaching.css         # NEW: all teaching styles (adapted palette)
            └── js/teaching.js           # NEW: signup AJAX + popup modal + booking AJAX + confirmation swap
```

**Why fallback templates in the plugin:** the entire reason for the plugin is that the feature survives a theme swap. The theme provides the real, styled templates (WP's hierarchy finds `archive-teaching_event.php` / `single-teaching_event.php` automatically). The plugin ships bare fallbacks used **only** when the active theme provides none (checked with `locate_template()` inside a `template_include` filter). Under the current theme, the plugin fallbacks are never used.

**reCAPTCHA & the existing theme dequeue:** `functions.php` strips the `google-recaptcha` handle (registered by Contact Form 7) on every page except `/contact/`. The plugin registers reCAPTCHA under its own handle (`lieuwe-teaching-recaptcha`), enqueued only on Teaching pages, so the existing dequeue never touches it and **no theme change is required** for reCAPTCHA to load. Teaching pages don't load CF7, so there's no double-load.

## Data model — three CPTs (registered in the plugin)

### `teaching_event` — public

```php
register_post_type( 'teaching_event', [
    'labels'        => [ 'name' => 'Classes', 'singular_name' => 'Class', /* … */ ],
    'public'        => true,
    'has_archive'   => true,
    'supports'      => [ 'title', 'editor', 'thumbnail' ],
    'show_in_rest'  => true,
    'rewrite'       => [ 'slug' => 'teaching' ],
    'menu_icon'     => 'dashicons-hammer',
    'menu_position' => 7,
] );
```

Featured image = the photo (124×124 schedule thumb, 220px booking-summary hero). Post editor content = optional longer description on the booking page. Activation hook flushes rewrite rules so `/teaching/` resolves.

**Event meta fields** (meta box, save handler modeled on `lieuwe_save_publication_meta_box`):

| Meta key | Type | Sanitize | UI | Notes |
|---|---|---|---|---|
| `_te_type` | enum | whitelist `home_workshop` \| `festival` | `<select>` | Drives accent colour + which CTA |
| `_te_start_date` | date `Y-m-d` | validate format | date input | **Required.** Drives sort, month-group, upcoming/empty. Missing → excluded from schedule. |
| `_te_date_text` | text | `sanitize_text_field` | text input | Human display, e.g. "Two Saturdays · 12 & 19 Sep 2026" |
| `_te_time_text` | text | `sanitize_text_field` | text input | e.g. "10:00–16:00" |
| `_te_where` | text | `sanitize_text_field` | text input | e.g. "Home workshop, Groningen" |
| `_te_blurb` | textarea | `sanitize_textarea_field` | textarea | Card + booking-summary blurb (~52ch lines) |
| `_te_price` | text | `sanitize_text_field` | text input | Free text, e.g. "€120 — incl. materials & lunch" |
| `_te_includes` | text | `sanitize_text_field` | text input | e.g. "Tools, materials, lunch" |
| `_te_spots_total` | int | `absint` | number input | |
| `_te_spots_open` | int | `absint` (≤ total) | number input | Manual. **Not** auto-decremented. `0` → sold-out state. |
| `_te_ticket_url` | url | `esc_url_raw` | url input | Festival external tickets; required when type = festival |

### `class_signup` — private (no public UI)

| Field | Storage |
|---|---|
| Email | post_title (for admin scanning) + `_cs_email` meta |
| Interests | `_cs_interests` meta (array of keys: `spoon-carving`, `japanese-lacquering`, `sandalmaking`, `general`) |
| Submitted | `post_date` |

### `booking_request` — private (no public UI)

| Field | Storage |
|---|---|
| Display | post_title = "{name} — {event title}" |
| Name / Email / Phone | `_br_name` / `_br_email` / `_br_phone` |
| Spots requested | `_br_spots` (1–3) |
| Dietary needs | `_br_diet` |
| Note | `_br_note` |
| Class booked | `_br_event_id` (the `teaching_event` post ID) |
| Submitted | `post_date` |

Both submission CPTs: `public => false`, `show_ui => true`, `show_in_menu => 'edit.php?post_type=teaching_event'` (nested under Classes), `capability_type` mapped so only editors/admins see them, `exclude_from_search => true`, `publicly_queryable => false`. No `editor` support — display is read-only via custom columns.

## Forms & data flow

Both forms use **progressive enhancement**:

- **Baseline (no JS):** form POSTs to `admin-post.php` (`action=lieuwe_teaching_signup` / `lieuwe_teaching_booking`). Handler verifies nonce + honeypot empty (+ reCAPTCHA *if a token is present*), sanitizes, stores the CPT, sends both emails, then 303-redirects back with a success flag. The template renders a server-side confirmation on return.
- **Enhanced (JS):** `teaching.js` intercepts submit, fetches a reCAPTCHA v3 token, POSTs via `fetch` (no reload), and on success shows the inline confirmation + fires the popup (signup) or swaps to the confirmation card (booking).

**Validation & anti-spam order in the handler:**
1. Verify nonce → fail = reject.
2. Honeypot field must be empty → filled = silently accept-and-drop (return success, store nothing).
3. If a reCAPTCHA token is present, verify server-side; score `< 0.5` = reject with a friendly message. (Token absent only for the rare no-JS visitor — honeypot + nonce remain the floor.)
4. Required fields: signup needs a valid `email`; booking needs `name` + valid `email`. Server-side `is_email()` + length caps.
5. Sanitize every field, store, mail.

Mail never blocks success: if `wp_mail` returns false the submission is still stored and the visitor still sees confirmation; the failure is logged.

### Emails (`mail.php`)

| Email | To | Contents |
|---|---|---|
| Signup → admin | notify-to (setting, default `admin_email`) | Email captured + interests ticked. `Reply-To` = visitor. |
| Signup → visitor (auto-reply) | visitor | "Right, you're on the list." + the dynamic interest line (same copy rules as the popup). `Reply-To` = admin. |
| Booking → admin | notify-to | All fields + class title/date + link to the `booking_request` in wp-admin. `Reply-To` = visitor. |
| Booking → visitor (auto-reply) | visitor | "Spot requested, {firstName}." + class title + short date + "I hold spots by hand and will be in touch at {email}." `Reply-To` = admin. |

Plain-text bodies built with a small template helper; subjects prefixed with the site name. Deliverability depends on the site's existing mail (CF7 mail works today); an SMTP plugin is the remedy if needed — not built here.

## Admin experience

- **Classes** (events): the normal post editor + meta box. Add / edit / reorder like Publications.
- **Signups** and **Bookings:** each a filterable admin list (free, because they're CPTs) under the Classes menu, with custom columns:
  - Signups: Email · Interests · Date.
  - Bookings: Name · Email · Phone · Spots · Class (links to the event) · Dietary · Date.
- **Export CSV:** a button above each list → `admin-post.php` endpoint (capability + nonce checked) that streams a `text/csv` download of all rows for that CPT.
- **Settings** (under Classes → Settings): reCAPTCHA v3 site key + secret key (paste the same keys used for the contact form), and the notify-to email. Keys stored as options; the plugin is decoupled from CF7's own settings.

## Customizer additions (theme `inc/customizer.php`)

New section **"Teaching page"**:

| Setting ID | Type | Default (placeholder — user overwrites) |
|---|---|---|
| `teaching_eyebrow` | text | `Classes & workshops` |
| `teaching_title` | text | `Teaching` |
| `teaching_intro_p1` | textarea | (serif intro paragraph 1) |
| `teaching_intro_p2` | textarea | (serif intro paragraph 2) |
| `teaching_hero_image` | image (control) | empty → drop-zone placeholder |
| `teaching_hero_caption` | text | (faint italic caption) |
| `signup_heading` | text | `Hear about new classes` |
| `signup_intro` | textarea | (explanatory paragraph) |
| `teaching_privacy_note` | text | small print under both forms; supports a link to the privacy policy |

## Templates (theme)

### `archive-teaching_event.php`

```
get_header();
  Intro header  — two-col: Customizer eyebrow/title/2×paragraph  |  hero figure (teaching_hero_image + caption)
  Signup band   — Customizer heading + intro  |  signup form (+ hidden inline-confirmation card)
  Upcoming classes:
    $q = upcoming events (start_date >= today, orderby start_date ASC)
    if none → empty-state panel (dashed border, saw glyph, copy, get-in-touch link)
    else → loop, emit a month header when the month changes, then event card(s)
  Popup modal markup — built/echoed for JS to populate (see teaching.js)
get_footer();
```

**Signup form contract** (JS reads/writes these):

```html
<form class="te-signup" data-action="lieuwe_teaching_signup" method="post"
      action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
  <input type="hidden" name="action" value="lieuwe_teaching_signup">
  <?php wp_nonce_field( 'lieuwe_teaching_signup', '_te_nonce' ); ?>
  <input type="text" name="te_hp" class="te-hp" tabindex="-1" autocomplete="off" aria-hidden="true"><!-- honeypot -->
  <input type="hidden" name="te_token" class="te-recaptcha-token"><!-- set by JS -->
  <input type="email" name="te_email" required placeholder="you@example.com">
  <button type="submit">Keep me posted</button>
  <!-- four checkboxes: spoon-carving · japanese-lacquering · sandalmaking · general -->
</form>
```

**Event-card markup contract** — home-workshop CTA → `get_permalink()` (the single/booking page); festival CTA → `_te_ticket_url` (`target="_blank" rel="noopener"`); sold-out → CTA jumps to `#te-signup`.

### `single-teaching_event.php`

```
if ( _te_type === 'festival' && _te_ticket_url ) { wp_safe_redirect( ticket_url, 302 ); exit; }
get_header();
  "← Back to all classes" breadcrumb (archive link)
  header: eyebrow "Book a spot" + H1 = class title
  two-col:
    LEFT (sticky): summary card — featured image, "Home workshop" pill,
                   dl Date/Time/Where/Includes/Price (from meta), spots dots (spots_open/total),
                   "N of M spots still open", blurb
    RIGHT: if spots_open <= 0 → "currently full — join the list" notice + link to /teaching/#te-signup
           else → booking form (name, email, phone, spots 1–3, diet, note) + hint
  confirmation state — rendered when ?booked=1 (no-JS) or swapped in by JS
get_footer();
```

Booking form mirrors the signup contract (nonce `lieuwe_teaching_booking`, honeypot, reCAPTCHA token) plus a hidden `te_event_id` = current post ID.

### `header.php` / `footer.php`

Unchanged. Global header + scroll behaviour apply. The "Teaching" nav link picks up `.current-menu-item` automatically.

## Styling — adapt to the theme

Port layout, structure, spacing, and copy from the handoff faithfully, rendered in the theme's fonts (Goudy display / Jost UI) and oklch palette. The handoff's hex palette is already close to the theme's warm tones:

| Handoff hex | Role | Theme mapping |
|---|---|---|
| `#f6f1e8` paper | page bg | `--color-bg` |
| `#ece2cf` band | signup band | `--color-surface` |
| `#faf6ee` card/input | cards, inputs, modal | `--color-bg` (or a light `--te-card` derived from it) |
| `#2b2316` ink | headings, footer | `--color-text` |
| `#41372a` body | paragraphs | `--color-text` |
| `#6b5d46` muted | meta lines | `--color-muted` |
| `#8d7e64` faint | labels, month labels | `--color-muted` (lighter) |
| `#8a4b22` rust | primary accent | `--color-accent` |
| `#6e3a18` rust hover | accent hover | `--te-accent-hover` (darker oklch of accent) |
| `#56633f` forest | **festival accent** | **`--color-forest`** (NEW global token) |
| `#9aa886` festival border | festival tag outline | `--te-forest-border` |

**New tokens:** `--color-forest` (oklch, warm green ≈ `oklch(46% 0.05 135)`) added to `:root` in `style.css`; the few other deltas (`--te-accent-hover`, `--te-forest-border`, `--te-card`) are scoped to `teaching.css`. All teaching CSS lives in `assets/css/teaching.css`, conditionally enqueued only on Teaching pages (pattern from `lieuwe_pub_enqueue`). Fonts: no `@font-face` additions — reuse the theme's self-hosted Goudy + Jost.

## JavaScript — `assets/js/teaching.js`

```js
(function () {
  'use strict';

  const RECAPTCHA_SITE_KEY = window.lieuweTeaching?.recaptchaKey || '';
  const AJAX = window.lieuweTeaching?.ajaxUrl;

  const INTEREST_LABELS = {
    'spoon-carving':      'spoon carving',
    'japanese-lacquering':'Japanese lacquering',
    'sandalmaking':       'sandalmaking',
    'general':            'general updates',
  };

  // reCAPTCHA v3 token for a named action (resolves '' if reCAPTCHA absent)
  function token(action) { /* grecaptcha.execute(KEY, {action}) → string */ }

  // ---- Signup ----
  function initSignup() {
    // intercept submit → token('signup') → fetch POST →
    //   success: show inline confirmation card, openPopup(email, interests)
    //   error: inline error message, form stays usable
  }

  // ---- Popup modal ----
  function openPopup(email, interests) {
    // build modal via DOM (textContent only), echo email + interest chips,
    // dynamic interest line:
    //   0   → "I'll give you a shout the moment new dates go up."
    //   1   → "…as soon as new {craft} dates go up."
    //   2   → "…when new {a} or {b} dates go up."
    //   3+  → "…the moment new dates go up across the crafts you picked."
    // role=dialog + aria-modal, focus trap, Esc-to-close, scrim click, body-scroll lock,
    // return focus to the trigger. Animations: lj-scrim-in / lj-card-in (from handoff).
  }

  // ---- Booking ----
  function initBooking() {
    // intercept submit → token('booking') → fetch POST →
    //   success: swap form → confirmation card (firstName, class title, short date, email)
    //            (short date = dateText with a trailing year stripped)
    //   error: inline error
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('.te-signup'))  initSignup();
    if (document.querySelector('.te-booking')) initBooking();
  });
})();
```

Enqueued (with `wp_localize_script` for `ajaxUrl` + `recaptchaKey`) only on Teaching pages, alongside `teaching.css` and the plugin's `lieuwe-teaching-recaptcha` script.

## Responsive behaviour

Breakpoints in `teaching.css` (align with the theme's existing tablet/mobile breakpoints):

| Region | Tablet | Phone |
|---|---|---|
| Intro header (2-col) | gap tightens | single column, image below copy |
| Signup band (2-col) | gap tightens | single column; email + button wrap, checkboxes wrap to 2×2 |
| Schedule month grid `120px / 1fr` | label narrows | label stacks above events |
| Event card `124px / 1fr / auto` | gap tightens | single column: thumb, then title+tag, meta, blurb, full-width CTA |
| Booking (2-col, sticky summary) | summary un-sticks earlier | single column: summary card first, then form; fields stack |
| Popup | max-width 460 holds | near-full-width with margins; chips wrap |

## Error handling

| Failure | Behaviour |
|---|---|
| `wp_mail` fails | Submission still stored; visitor still sees confirmation; error logged. |
| reCAPTCHA script fails to load | JS submits with empty token; server falls back to honeypot + nonce. No hard block. |
| reCAPTCHA score `< 0.5` | Reject with: "Hmm, that didn't go through — please try again, or email me directly." |
| Missing required field | Native HTML validation + server-side guard; inline message on AJAX path. |
| `_te_start_date` missing | Event excluded from the schedule. Admin edit-screen notice: "Set a start date to list this class." |
| Festival event with no `_te_ticket_url` | Admin notice; card CTA falls back to the contact page until set. |
| `spots_open <= 0` | Sold-out state (card tag + signup CTA; booking form replaced by "join the list" notice). |
| Plugin deactivated | Theme templates simply go unused (no CPT, no archive); stored data intact, returns when reactivated. |
| No upcoming events | Archive renders intro + signup band + empty-state panel. |
| Duplicate signup (same email) | Accepted; stored as a new record. (Dedup is a future nicety, not required.) |

## GDPR / personal data (Dutch site)

- Both submission CPTs are `public => false` / `exclude_from_search` / not REST-exposed.
- `privacy.php` registers a `wp_privacy_personal_data_exporters` callback and a `wp_privacy_personal_data_erasers` callback, both keyed by email, covering `class_signup` + `booking_request`, so WP's native export/erase tools just work.
- A privacy note (Customizer `teaching_privacy_note`) sits under both forms, linking to the privacy policy.
- Store the minimum. Dietary needs (possible health data) live only on the `booking_request` record.
- Retention: documented manual purge guidance; an optional wp-cron auto-trash of bookings older than N months is noted as a future toggle (not built initially).

## Verification checklist (manual — no test suite in this theme/plugin)

1. Activate plugin → "Classes" appears in admin with hammer icon; `/teaching/` resolves (rewrite flush on activation).
2. Create a home-workshop event with all fields + featured image → card appears under its month on `/teaching/`, ordered by start date.
3. Click "Book a spot" → single page renders summary card (dl + dots + "N of M spots") + request form.
4. Submit booking with JS on → form swaps to confirmation card (correct first name, class title, short date, email). A `booking_request` appears in admin; admin + visitor emails arrive.
5. Submit booking with JS off (disable JS) → server-side confirmation renders; record + emails still created.
6. Create a festival event with a ticket URL → card shows green "Festival" tag + outlined "Festival tickets ↗"; CTA opens the external URL; visiting the single URL 302-redirects out.
7. Signup band: tick 0 / 1 / 2 / 3 interests and submit → popup shows the correct dynamic interest line each time; chips match; inline confirmation shows; `class_signup` stored; both emails arrive.
8. Popup: Esc, scrim click, and "Lovely, thanks" all close it; clicks inside don't; focus returns to the form; body scroll is locked while open.
9. Honeypot: fill `te_hp` via devtools and submit → request succeeds visually but **nothing is stored**.
10. reCAPTCHA: with keys set, a normal submit passes; confirm a low score is rejected with the friendly message.
11. Sold-out: set `spots_open = 0` → card shows "Fully booked", booking page shows the "join the list" notice.
12. Empty state: no upcoming events → intro + band + empty-state panel render.
13. Admin lists: Signups and Bookings show correct columns; Bookings "Class" links to the event; "Export CSV" downloads valid CSV for each.
14. Customizer "Teaching page": change every field → live preview + persisted on save.
15. Privacy: run Tools → Export/Erase Personal Data for a test email → signup + booking records are included/erased.
16. Styling: colours/type read as the same site (Goudy/Jost, warm palette); festival green renders via `--color-forest`.
17. Mobile (iOS Safari + Android Chrome): all 2-col grids and the event card collapse to single column; popup is usable; forms submit.
18. Perf: confirm `teaching.css` / `teaching.js` / reCAPTCHA load **only** on Teaching pages (not the homepage).

## Versioning & deployment

- **Plugin** `lieuwe-teaching` starts at `1.0.0` (version in the plugin header). Its own repo → its own GitHub Release zip, uploaded under **Plugins → Add New → Upload**. For local dev, symlink the plugin folder into the Local site's `wp-content/plugins/`.
- **Theme** `style.css` `Version:` bumped `1.13.0 → 1.14.0` (templates + CSS + JS + Customizer + the `--color-forest` token). Per the multi-task convention: bump once in the first theme task, tag once at the end.
  ```
  git tag -a v1.14.0 HEAD -m "Release v1.14.0 — Teaching pages (templates, styling) for the lieuwe-teaching plugin"
  git push origin main --tags
  ```
- Order of deploy: install/activate the plugin first (creates the CPT + `/teaching/` route), then upload the theme zip (provides the templates).

## Out of scope (deferred)

- Payment / instant checkout (spots are held by hand by design).
- Auto-decrementing `spots_open` on booking.
- External newsletter integration (Mailchimp etc.) — signups are a stored, exportable list.
- iCal / calendar feed of upcoming classes.
- Automated test suite (none exists in this codebase; not introducing one for this feature).
- Duplicate-signup dedup and wp-cron retention auto-purge (documented, not built initially).
- Multi-language (NL/EN) copy toggling for the Teaching pages.
