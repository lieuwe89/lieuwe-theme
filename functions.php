<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/publications.php';
require_once get_template_directory() . '/inc/teaching.php';
// PUC GitHub updater — this is the REAL path code takes to production: the
// site is updated by clicking "Update" in WP admin, which this powers. (The
// GitHub Actions deploy.yml writes to a DEPLOY_PATH that is not the active
// theme dir, so it does not update the live site.) Do NOT remove this require
// or the WP-admin update path stops working.
require_once get_template_directory() . '/inc/updater.php';

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
    $version = wp_get_theme()->get( 'Version' );

    wp_enqueue_style(
        'lieuwe-theme',
        get_stylesheet_uri(),
        [],
        $version
    );

    wp_enqueue_script(
        'lieuwe-main',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $version,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'lieuwe_enqueue_assets' );

/**
 * Theme-side meta box for portfolio_item posts.
 *
 * The Portfolio Canvas plugin owns the portfolio_item CPT and its primary
 * meta (year, video URL, gallery). The theme only adds the "feature on
 * front page" flag, which drives ordering in front-page.php's portfolio
 * preview query.
 */
function lieuwe_add_portfolio_meta_boxes(): void {
    add_meta_box(
        'lieuwe_portfolio_settings',
        'Front Page',
        'lieuwe_render_portfolio_meta_box',
        'portfolio_item',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'lieuwe_add_portfolio_meta_boxes' );

function lieuwe_render_portfolio_meta_box( WP_Post $post ): void {
    $featured = get_post_meta( $post->ID, '_lieuwe_featured', true );
    wp_nonce_field( 'lieuwe_portfolio_meta_box', 'lieuwe_portfolio_meta_box_nonce' );
    ?>
    <p>
        <label>
            <input type="checkbox" name="lieuwe_featured" value="1" <?php checked( $featured, '1' ); ?>>
            <?php esc_html_e( 'Feature on Front Page', 'lieuwe-theme' ); ?>
        </label>
    </p>
    <?php
}

function lieuwe_save_portfolio_meta_box( int $post_id ): void {
    if ( ! isset( $_POST['lieuwe_portfolio_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['lieuwe_portfolio_meta_box_nonce'], 'lieuwe_portfolio_meta_box' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['lieuwe_featured'] ) ) {
        update_post_meta( $post_id, '_lieuwe_featured', '1' );
    } else {
        delete_post_meta( $post_id, '_lieuwe_featured' );
    }
}
add_action( 'save_post_portfolio_item', 'lieuwe_save_portfolio_meta_box' );

/**
 * "Homepage lead" flag — posts, portfolio items, and publications.
 *
 * front-page.php shows the newest flagged item as the lead story; when
 * nothing is flagged it falls back to the newest post. Several flags at
 * once is fine — newest wins, no warning UI.
 */
function lieuwe_add_lead_meta_box(): void {
    add_meta_box(
        'lieuwe_lead_settings',
        'Homepage',
        'lieuwe_render_lead_meta_box',
        [ 'post', 'portfolio_item', 'publication' ],
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'lieuwe_add_lead_meta_box' );

function lieuwe_render_lead_meta_box( WP_Post $post ): void {
    $lead = get_post_meta( $post->ID, '_lieuwe_lead', true );
    wp_nonce_field( 'lieuwe_lead_meta_box', 'lieuwe_lead_meta_box_nonce' );
    ?>
    <p>
        <label>
            <input type="checkbox" name="lieuwe_lead" value="1" <?php checked( $lead, '1' ); ?>>
            <?php esc_html_e( 'Show as homepage lead', 'lieuwe-theme' ); ?>
        </label>
    </p>
    <?php
}

function lieuwe_save_lead_meta_box( int $post_id ): void {
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! isset( $_POST['lieuwe_lead_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['lieuwe_lead_meta_box_nonce'], 'lieuwe_lead_meta_box' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['lieuwe_lead'] ) ) {
        update_post_meta( $post_id, '_lieuwe_lead', '1' );
    } else {
        delete_post_meta( $post_id, '_lieuwe_lead' );
    }
}
add_action( 'save_post', 'lieuwe_save_lead_meta_box' );

/**
 * Default copy and image slots for the Services page template.
 */
function lieuwe_get_services_page_defaults(): array {
    return [
        'subtitle'  => 'Four things I am occasionally asked to do, beyond making spoons and shelving boxes.',
        'intro'     => 'Once in a while people ask whether I’m available for things outside of my day job at the archives. The honest answer is: sometimes, yes. Below are the four kinds of work I’m asked to do most often — what they tend to look like in practice, and how I usually approach them.' . "\n\n" . 'If any of this is useful to you, <a href="' . esc_url( home_url( '/contact/' ) ) . '">send me a note</a>. I try to reply within a week, and I’ll say so plainly if it isn’t the right fit.',
        'coda_mark' => '⁂',
        'coda'      => 'Otherwise — thank you for reading this far.',
        'services'  => [
            'moderator'  => [
                'number'    => 'I',
                'tagline'   => 'Of conferences, panels, and the occasional symposium.',
                'title'     => 'Event moderator',
                'body'      => 'I moderate conferences, panels, and symposia. Most of them sit in the worlds of heritage, archives, and craft, though I’ve ended up at adjacent events too — on data, regional history, and once a long evening on permaculture.' . "\n\n" . 'What I bring is preparation. I read everything in advance, I sit with the speakers beforehand if there’s time, and I write a fresh set of questions rather than recycle the ones already in the programme. I work in Dutch and in English.',
                'pullquote' => 'I’m happiest at events where something is actually at stake, however small — less so at the kind that exist mainly to be photographed.',
                'caption'   => 'Optional caption.',
                'image_url' => '',
                'image_alt' => 'Event moderator',
            ],
            'speaker'    => [
                'number'    => 'II',
                'tagline'   => 'Of archives, craft, and the corners where they overlap.',
                'title'     => 'Public speaker',
                'body'      => 'I give talks about the work I actually do: archives and information management, the philosophy of craft, regional history, and the odd corners where those three overlap.' . "\n\n" . 'The most-requested talks at the moment are about the role of archives in an age of generative AI, about teaching crafts that aren’t (yet) on the verge of disappearing, and about the digital lives of regional collections. I write each talk for the room it’s going into.',
                'pullquote' => 'I’d rather show up with the right thirty minutes than the same forty I gave somewhere else last month.',
                'caption'   => 'Optional caption.',
                'image_url' => '',
                'image_alt' => 'Public speaker',
            ],
            'educator'   => [
                'number'    => 'III',
                'tagline'   => 'Of spoons, leather, urushi, and patience.',
                'title'     => 'Educator',
                'body'      => 'I teach traditional crafts — spoon carving, leatherwork, Japanese lacquerwork (urushi), and now and then sandalmaking. I teach at festivals like Spoonfest and Von Hand, at the Groninger Archieven, and at smaller workshops I host myself.' . "\n\n" . 'My classes are practical and unhurried. Beginners are welcome, and so are people who’ve been at it longer than I have. For organisations or schools wanting something more tailored — a multi-day course, a series of evenings, a single deep day — I’m happy to design a curriculum from scratch.',
                'pullquote' => 'I’d rather you finish one thing well than start three you don’t know what to do with.',
                'caption'   => 'Optional caption.',
                'image_url' => '',
                'image_alt' => 'Educator',
            ],
            'consultant' => [
                'number'    => 'IV',
                'tagline'   => 'Of data, policy, and the unglamorous middle.',
                'title'     => 'Consultant',
                'body'      => 'I advise organisations on digital innovation, collection data, and information management. By day I lead the Information and Advisory services department at the Groninger Archieven, which is also where most of my thinking on these subjects gets tested against reality.' . "\n\n" . 'Outside of that day job, the consulting I take on tends to be small and well-defined: a strategy that needs a second pair of eyes, a data migration that needs a plan, a policy that needs to actually work for the people stuck applying it.',
                'pullquote' => 'Most useful in the unglamorous middle of the work — where strategy meets a spreadsheet, or where a good idea has to survive its first contact with the org chart.',
                'caption'   => 'Optional caption.',
                'image_url' => '',
                'image_alt' => 'Consultant',
            ],
        ],
    ];
}

/**
 * Merge saved Services page fields with defaults so new fields appear safely.
 */
function lieuwe_get_services_page_data( int $post_id ): array {
    $defaults = lieuwe_get_services_page_defaults();
    $stored   = get_post_meta( $post_id, '_lieuwe_services_page', true );

    if ( ! is_array( $stored ) ) {
        return $defaults;
    }

    $data             = array_merge( $defaults, $stored );
    $data['services'] = [];

    foreach ( $defaults['services'] as $service_key => $service_defaults ) {
        $stored_service                  = isset( $stored['services'][ $service_key ] ) && is_array( $stored['services'][ $service_key ] )
            ? $stored['services'][ $service_key ]
            : [];
        $data['services'][ $service_key ] = array_merge( $service_defaults, $stored_service );
    }

    return $data;
}

/**
 * Check whether a page should expose Services page fields.
 */
function lieuwe_is_services_page_post( WP_Post $post ): bool {
    $template = get_page_template_slug( $post );

    return 'page' === $post->post_type && ( 'page-services.php' === $template || 'services' === $post->post_name );
}

/**
 * Add editable copy fields for the Services page template.
 */
function lieuwe_add_services_page_meta_box( string $post_type, WP_Post $post ): void {
    if ( 'page' !== $post_type ) {
        return;
    }

    if ( ! lieuwe_is_services_page_post( $post ) ) {
        return;
    }

    add_meta_box(
        'lieuwe_services_page_content',
        'Services Page Content',
        'lieuwe_render_services_page_meta_box',
        'page',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'lieuwe_add_services_page_meta_box', 10, 2 );

/**
 * Render the Services page meta box.
 */
function lieuwe_render_services_page_meta_box( WP_Post $post ): void {
    $data = lieuwe_get_services_page_data( $post->ID );
    wp_nonce_field( 'lieuwe_services_page_meta_box', 'lieuwe_services_page_meta_box_nonce' );
    ?>
    <style>
        .lieuwe-services-fields {
            display: grid;
            gap: 18px;
        }
        .lieuwe-services-fields label {
            display: grid;
            gap: 6px;
            font-weight: 600;
        }
        .lieuwe-services-fields input[type="text"],
        .lieuwe-services-fields input[type="url"],
        .lieuwe-services-fields textarea {
            width: 100%;
        }
        .lieuwe-services-fields textarea {
            min-height: 96px;
        }
        .lieuwe-services-fields__service {
            border: 1px solid #dcdcde;
            padding: 16px;
            background: #fff;
        }
        .lieuwe-services-fields__service h3 {
            margin-top: 0;
        }
        .lieuwe-services-fields__row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 12px;
        }
        @media (max-width: 782px) {
            .lieuwe-services-fields__row {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="lieuwe-services-fields">
        <p>These fields feed the Services Page template. Paragraph fields allow simple HTML such as links and emphasis.</p>

        <label>
            Header subtitle
            <input type="text" name="lieuwe_services[subtitle]" value="<?php echo esc_attr( $data['subtitle'] ); ?>">
        </label>

        <label>
            Intro copy
            <textarea name="lieuwe_services[intro]" rows="6"><?php echo esc_textarea( $data['intro'] ); ?></textarea>
        </label>

        <?php foreach ( $data['services'] as $service_key => $service ) : ?>
            <section class="lieuwe-services-fields__service">
                <h3><?php echo esc_html( $service['title'] ); ?></h3>

                <div class="lieuwe-services-fields__row">
                    <label>
                        Numeral
                        <input type="text" name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][number]" value="<?php echo esc_attr( $service['number'] ); ?>">
                    </label>
                    <label>
                        Title
                        <input type="text" name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][title]" value="<?php echo esc_attr( $service['title'] ); ?>">
                    </label>
                </div>

                <label>
                    Tagline
                    <input type="text" name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][tagline]" value="<?php echo esc_attr( $service['tagline'] ); ?>">
                </label>

                <label>
                    Body
                    <textarea name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][body]" rows="7"><?php echo esc_textarea( $service['body'] ); ?></textarea>
                </label>

                <label>
                    Pullquote
                    <textarea name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][pullquote]" rows="3"><?php echo esc_textarea( $service['pullquote'] ); ?></textarea>
                </label>

                <label>
                    Image URL
                    <input type="url" name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][image_url]" value="<?php echo esc_url( $service['image_url'] ); ?>">
                </label>

                <div class="lieuwe-services-fields__row">
                    <label>
                        Image alt text
                        <input type="text" name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][image_alt]" value="<?php echo esc_attr( $service['image_alt'] ); ?>">
                    </label>
                    <label>
                        Caption
                        <input type="text" name="lieuwe_services[services][<?php echo esc_attr( $service_key ); ?>][caption]" value="<?php echo esc_attr( $service['caption'] ); ?>">
                    </label>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="lieuwe-services-fields__row">
            <label>
                Coda mark
                <input type="text" name="lieuwe_services[coda_mark]" value="<?php echo esc_attr( $data['coda_mark'] ); ?>">
            </label>
            <label>
                Coda text
                <input type="text" name="lieuwe_services[coda]" value="<?php echo esc_attr( $data['coda'] ); ?>">
            </label>
        </div>
    </div>
    <?php
}

/**
 * Save Services page meta fields.
 */
function lieuwe_save_services_page_meta_box( int $post_id ): void {
    if ( ! isset( $_POST['lieuwe_services_page_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['lieuwe_services_page_meta_box_nonce'], 'lieuwe_services_page_meta_box' ) ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        return;
    }

    if ( 'page' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post || ! lieuwe_is_services_page_post( $post ) ) {
        return;
    }

    $defaults = lieuwe_get_services_page_defaults();
    $raw      = isset( $_POST['lieuwe_services'] ) && is_array( $_POST['lieuwe_services'] )
        ? wp_unslash( $_POST['lieuwe_services'] )
        : [];

    $clean = [
        'subtitle'  => sanitize_text_field( $raw['subtitle'] ?? '' ),
        'intro'     => wp_kses_post( $raw['intro'] ?? '' ),
        'coda_mark' => sanitize_text_field( $raw['coda_mark'] ?? '' ),
        'coda'      => wp_kses_post( $raw['coda'] ?? '' ),
        'services'  => [],
    ];

    foreach ( $defaults['services'] as $service_key => $service_defaults ) {
        $raw_service = isset( $raw['services'][ $service_key ] ) && is_array( $raw['services'][ $service_key ] )
            ? $raw['services'][ $service_key ]
            : [];

        $clean['services'][ $service_key ] = [
            'number'    => sanitize_text_field( $raw_service['number'] ?? '' ),
            'tagline'   => sanitize_text_field( $raw_service['tagline'] ?? '' ),
            'title'     => sanitize_text_field( $raw_service['title'] ?? '' ),
            'body'      => wp_kses_post( $raw_service['body'] ?? '' ),
            'pullquote' => wp_kses_post( $raw_service['pullquote'] ?? '' ),
            'caption'   => sanitize_text_field( $raw_service['caption'] ?? '' ),
            'image_url' => esc_url_raw( $raw_service['image_url'] ?? '' ),
            'image_alt' => sanitize_text_field( $raw_service['image_alt'] ?? $service_defaults['image_alt'] ),
        ];
    }

    update_post_meta( $post_id, '_lieuwe_services_page', $clean );
}
add_action( 'save_post_page', 'lieuwe_save_services_page_meta_box' );

/**
 * Load CF7 scripts/styles only on the contact page, not sitewide.
 */
function lieuwe_cf7_load_on_contact_only(): void {
    if ( ! is_page( 'contact' ) && ! is_page( 'kontakt' ) ) {
        add_filter( 'wpcf7_load_js',  '__return_false' );
        add_filter( 'wpcf7_load_css', '__return_false' );
    }
}
add_action( 'wp_enqueue_scripts', 'lieuwe_cf7_load_on_contact_only', 1 );

function lieuwe_dequeue_recaptcha_sitewide(): void {
    if ( ! is_page( 'contact' ) && ! is_page( 'kontakt' ) ) {
        wp_dequeue_script( 'google-recaptcha' );
        wp_deregister_script( 'google-recaptcha' );
    }
}
add_action( 'wp_enqueue_scripts', 'lieuwe_dequeue_recaptcha_sitewide', 99 );

/**
 * Add page-specific body classes.
 */
function lieuwe_body_classes( array $classes ): array {
    if ( is_page( 'about' ) ) {
        $classes[] = 'page-about';
    }
    if ( is_page( 'contact' ) || is_page( 'kontakt' ) ) {
        $classes[] = 'page-contact';
    }
    if ( is_page( 'services' ) || is_page_template( 'page-services.php' ) ) {
        $classes[] = 'page-services';
    }
    return $classes;
}
add_filter( 'body_class', 'lieuwe_body_classes' );

/**
 * Add security headers.
 */
function lieuwe_add_security_headers(): void {
    if ( ! is_admin() ) {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()' );

        /*
         * Content-Security-Policy — Report-Only for now so an unforeseen
         * violation never breaks the page (esp. reCAPTCHA v3 on the contact
         * form and Cloudflare Turnstile on the teaching form). Watch the
         * browser console / any report endpoint, then promote to an enforcing
         * `Content-Security-Policy` header once the report log is clean.
         */
        $csp = implode(
            '; ',
            array(
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://recaptcha.net https://challenges.cloudflare.com",
                "frame-src https://www.google.com https://recaptcha.net https://challenges.cloudflare.com",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https://s.w.org",
                "font-src 'self'",
                "connect-src 'self' https://www.google.com https://challenges.cloudflare.com",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
            )
        );
        header( 'Content-Security-Policy-Report-Only: ' . $csp );
    }
}
add_action( 'send_headers', 'lieuwe_add_security_headers' );

/**
 * Security: Disable XML-RPC to prevent brute force attacks and DDoS.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Security: Prevent user enumeration via /?author=N and /author/username/
 *
 * Why we check REQUEST_URI directly instead of `is_author()`: WP misparses
 * unknown single-segment URLs (e.g. a CPT archive slug whose pretty-permalink
 * rewrite hasn't matched) as `?author_name=...`, which makes `is_author()`
 * return true even though the visitor never typed `/author/`. Matching the
 * literal `/author/` path segment blocks real enumeration attempts without
 * catching legitimate top-level pages and CPT archives.
 */
function lieuwe_block_user_enumeration(): void {
    if ( is_admin() ) {
        return;
    }

    $request_uri       = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $is_numeric_author = isset( $_REQUEST['author'] ) && is_numeric( $_REQUEST['author'] );
    $is_author_path    = (bool) preg_match( '#(^|/)author/[^/]+#i', $request_uri );

    if ( $is_numeric_author || $is_author_path ) {
        wp_redirect( home_url() );
        exit;
    }
}
add_action( 'template_redirect', 'lieuwe_block_user_enumeration' );

/**
 * Security: Disable user endpoint in REST API for non-authenticated users
 */
function lieuwe_disable_rest_endpoints( array $endpoints ): array {
    if ( isset( $endpoints['/wp/v2/users'] ) && ! is_user_logged_in() ) {
        unset( $endpoints['/wp/v2/users'] );
    }
    if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) && ! is_user_logged_in() ) {
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
}
add_filter( 'rest_endpoints', 'lieuwe_disable_rest_endpoints' );

/**
 * Security: Remove users from the XML sitemap to prevent enumeration.
 */
function lieuwe_disable_sitemap_users( $provider, $name ) {
    return ( 'users' === $name ) ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'lieuwe_disable_sitemap_users', 10, 2 );

/**
 * Security: Remove WordPress version generation to prevent version leakage.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * Security: Strip the `?ver=` query string from CORE-versioned assets only.
 *
 * WordPress appends its own core version to bundled assets (e.g.
 * `wp-includes/...css?ver=7.0`), which leaks the exact WP version for
 * CVE matching. Only that version is stripped: theme/plugin assets keep
 * their own `?ver=`, which is the sole cache-buster for style.css/main.js —
 * the server marks static files `immutable` for 30 days, so without a
 * changing query string, returning visitors keep stale CSS across releases
 * (this broke the v1.19.0 homepage rollout).
 * Runs at a late priority so it wins over any earlier version filters.
 *
 * @param string $src Asset URL.
 * @return string
 */
function lieuwe_remove_asset_version( $src ) {
    if ( is_string( $src ) && str_contains( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'lieuwe_remove_asset_version', 9999 );
add_filter( 'script_loader_src', 'lieuwe_remove_asset_version', 9999 );

/**
 * SEO: Output a per-page `<meta name="description">`.
 *
 * The theme ships no SEO plugin, so search engines and social cards fall back
 * to scraped page text. This emits an in-voice-neutral description: the manual
 * excerpt when set, otherwise the first ~30 words of the content, otherwise the
 * site tagline. Guarded by a filter so a future SEO plugin can short-circuit it.
 */
function lieuwe_meta_description(): void {
    if ( ! apply_filters( 'lieuwe_output_meta_description', true ) ) {
        return;
    }

    if ( is_singular() ) {
        $id  = get_queried_object_id();
        $raw = has_excerpt( $id )
            ? get_the_excerpt( $id )
            : (string) get_post_field( 'post_content', $id );
        // Clean before trimming: strip tags, decode entities, then normalise
        // whitespace (incl. &nbsp; / U+00A0) so the description never starts
        // with a stray space or shows raw entities. Trim on a word boundary.
        $raw  = wp_strip_all_tags( $raw );
        $raw  = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $raw  = str_replace( "\xc2\xa0", ' ', $raw );
        $raw  = trim( (string) preg_replace( '/\s+/u', ' ', $raw ) );
        $desc = wp_trim_words( $raw, 30, '…' );
    } else {
        $desc = get_bloginfo( 'description' );
    }

    $desc = trim( (string) preg_replace( '/\s+/u', ' ', (string) $desc ) );
    if ( '' === $desc ) {
        return;
    }

    echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
}
add_action( 'wp_head', 'lieuwe_meta_description', 1 );

/**
 * Security: Remove author data from oEmbed responses to prevent user enumeration.
 */
function lieuwe_remove_oembed_author_data( $data ) {
    if ( isset( $data['author_name'] ) ) {
        unset( $data['author_name'] );
    }
    if ( isset( $data['author_url'] ) ) {
        unset( $data['author_url'] );
    }
    return $data;
}
add_filter( 'oembed_response_data', 'lieuwe_remove_oembed_author_data' );
