<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

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
 * Register portfolio custom post type.
 * Skipped if already registered by a plugin (e.g. Elementor Pro).
 */
function lieuwe_register_portfolio_cpt(): void {
    if ( post_type_exists( 'portfolio_item' ) ) {
        return;
    }

    register_post_type( 'portfolio_item', [
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
add_action( 'init', 'lieuwe_register_portfolio_cpt', 5 );

/**
 * Add meta box for Portfolio Items.
 */
function lieuwe_add_portfolio_meta_boxes(): void {
    add_meta_box(
        'lieuwe_portfolio_settings',
        'Portfolio Settings',
        'lieuwe_render_portfolio_meta_box',
        'portfolio_item',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'lieuwe_add_portfolio_meta_boxes' );

/**
 * Render portfolio meta box.
 */
function lieuwe_render_portfolio_meta_box( WP_Post $post ): void {
    $featured  = get_post_meta( $post->ID, '_lieuwe_featured', true );
    $video_url = get_post_meta( $post->ID, 'portfolio_video', true );
    wp_nonce_field( 'lieuwe_portfolio_meta_box', 'lieuwe_portfolio_meta_box_nonce' );
    ?>
    <p>
        <label>
            <input type="checkbox" name="lieuwe_featured" value="1" <?php checked( $featured, '1' ); ?>>
            <?php esc_html_e( 'Feature on Front Page', 'lieuwe-theme' ); ?>
        </label>
    </p>
    <p>
        <label for="portfolio_video" style="display:block; margin-bottom:5px;"><?php esc_html_e( 'Portfolio Video URL (MP4)', 'lieuwe-theme' ); ?></label>
        <input type="url" id="portfolio_video" name="portfolio_video" value="<?php echo esc_url( $video_url ); ?>" class="widefat">
    </p>
    <?php
}

/**
 * Save portfolio meta box data.
 */
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

    if ( isset( $_POST['portfolio_video'] ) ) {
        update_post_meta( $post_id, 'portfolio_video', esc_url_raw( $_POST['portfolio_video'] ) );
    }
}
add_action( 'save_post_portfolio_item', 'lieuwe_save_portfolio_meta_box' );

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
        header( 'X-XSS-Protection: 1; mode=block' );
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    }
}
add_action( 'send_headers', 'lieuwe_add_security_headers' );

/**
 * Security: Disable XML-RPC to prevent brute force attacks and DDoS.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Security: Prevent user enumeration via /?author=N
 */
function lieuwe_block_user_enumeration(): void {
    if ( is_admin() ) {
        return;
    }

    // Check if the author query string parameter is set and is numeric
    if ( isset( $_REQUEST['author'] ) && is_numeric( $_REQUEST['author'] ) ) {
        wp_die( 'Forbidden', 'Forbidden', [ 'response' => 403 ] );
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
 * Security: Remove WordPress version generation to prevent version leakage.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
