<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

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

    // Publications page
    $wp_customize->add_section( 'lieuwe_publications', [
        'title'    => 'Publications page',
        'priority' => 35,
    ] );

    $wp_customize->add_setting( 'pub_hero_title_line1', [
        'default'           => 'Here are some of',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'pub_hero_title_line1', [
        'label'   => 'Hero title — line 1 (roman)',
        'section' => 'lieuwe_publications',
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'pub_hero_title_line2', [
        'default'           => 'my recent publications',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'pub_hero_title_line2', [
        'label'       => 'Hero title — line 2 (italic)',
        'description' => 'Rendered in italic on a new line.',
        'section'     => 'lieuwe_publications',
        'type'        => 'text',
    ] );

    $wp_customize->add_setting( 'pub_hero_intro', [
        'default'           => 'Catalogues, essays, one slow monograph. Written across museum residencies, magazine commissions, and the bench. Click a title to open it — pages render in place.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'pub_hero_intro', [
        'label'   => 'Hero intro paragraph',
        'section' => 'lieuwe_publications',
        'type'    => 'textarea',
    ] );
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

/**
 * Hero copy accessors for the Publications page.
 */
function lieuwe_publications_hero_title_line1(): string {
    return (string) get_theme_mod( 'pub_hero_title_line1', 'Here are some of' );
}
function lieuwe_publications_hero_title_line2(): string {
    return (string) get_theme_mod( 'pub_hero_title_line2', 'my recent publications' );
}
function lieuwe_publications_hero_intro(): string {
    return (string) get_theme_mod( 'pub_hero_intro', 'Catalogues, essays, one slow monograph. Written across museum residencies, magazine commissions, and the bench. Click a title to open it — pages render in place.' );
}
