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
