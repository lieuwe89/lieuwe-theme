<?php
/**
 * Publications catalogue — CPT, meta box, enqueue, helpers.
 *
 * @package Lieuwe_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the publication CPT (URL slug: /writing/).
 */
function lieuwe_register_publication_cpt(): void {
    if ( post_type_exists( 'publication' ) ) {
        return;
    }

    register_post_type( 'publication', [
        'labels' => [
            'name'          => 'Publications',
            'singular_name' => 'Publication',
            'add_new_item'  => 'Add New Publication',
            'edit_item'     => 'Edit Publication',
            'view_item'     => 'View Publication',
            'all_items'     => 'All Publications',
            'menu_name'     => 'Publications',
        ],
        'public'        => true,
        'has_archive'   => true,
        'supports'      => [ 'title', 'editor', 'thumbnail' ],
        'show_in_rest'  => true,
        'rewrite'       => [ 'slug' => 'writing' ],
        'menu_icon'     => 'dashicons-book-alt',
        'menu_position' => 6,
    ] );
}
add_action( 'init', 'lieuwe_register_publication_cpt', 5 );

/**
 * Flush rewrite rules once on theme activation so /writing/ resolves.
 */
function lieuwe_publications_flush_rewrites(): void {
    lieuwe_register_publication_cpt();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'lieuwe_publications_flush_rewrites' );

/**
 * WP 6.5+ required for wp_enqueue_script_module (used by PDF.js).
 */
function lieuwe_publications_wp_version_notice(): void {
    if ( version_compare( get_bloginfo( 'version' ), '6.5', '>=' ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p><strong>Publications catalogue:</strong> requires WordPress 6.5 or later for ES module support. PDF previews will not load on the current version.</p></div>';
}
add_action( 'admin_notices', 'lieuwe_publications_wp_version_notice' );
