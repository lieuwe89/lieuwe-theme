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

    // Business details (footer / legal identification — fill once KvK-registered)
    $wp_customize->add_section( 'lieuwe_business', [
        'title'    => 'Business details (footer)',
        'priority' => 37,
    ] );

    $business_fields = [
        'business_name'  => 'Business name',
        'business_kvk'   => 'KvK number',
        'business_btw'   => 'BTW / VAT number',
        'business_email' => 'Contact email',
    ];
    foreach ( $business_fields as $id => $label ) {
        $wp_customize->add_setting( $id, [
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( $id, [
            'label'   => $label,
            'section' => 'lieuwe_business',
            'type'    => 'text',
        ] );
    }
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

/**
 * Business details for the footer colofon. Returns only the filled fields
 * (keys: name, kvk, btw, email). Empty until set in Customizer → Business details.
 *
 * @return array<string,string>
 */
function lieuwe_business_details(): array {
    return array_filter( [
        'name'  => (string) get_theme_mod( 'business_name', '' ),
        'kvk'   => (string) get_theme_mod( 'business_kvk', '' ),
        'btw'   => (string) get_theme_mod( 'business_btw', '' ),
        'email' => (string) get_theme_mod( 'business_email', '' ),
    ] );
}
