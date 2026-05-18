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
