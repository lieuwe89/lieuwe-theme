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

    // Turnstile (managed mode) reads its site key from the widget's data-sitekey
    // attribute, so the JS only needs the AJAX endpoint.
    wp_localize_script( 'lieuwe-teaching', 'lieuweTeaching', [
        'ajaxUrl' => admin_url( 'admin-post.php' ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'lieuwe_theme_teaching_enqueue' );
