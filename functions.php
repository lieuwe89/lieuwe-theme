<?php
/**
 * Lieuwe Theme functions and definitions
 */

if ( ! function_exists( 'lieuwe_theme_setup' ) ) :
	function lieuwe_theme_setup() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );

		register_nav_menus(
			array(
				'menu-1' => esc_html__( 'Primary', 'lieuwe-theme' ),
				'footer' => esc_html__( 'Footer Menu', 'lieuwe-theme' ),
			)
		);

		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
		);
	}
endif;
add_action( 'after_setup_theme', 'lieuwe_theme_setup' );

function lieuwe_theme_scripts() {
	// Google Fonts
	wp_enqueue_style( 'lieuwe-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap', array(), null );
	
	// Main theme stylesheet
	wp_enqueue_style( 'lieuwe-theme-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version') );

	// JS Scripts
	wp_enqueue_script( 'lieuwe-theme-main', get_template_directory_uri() . '/assets/js/main.js', array(), wp_get_theme()->get('Version'), true );
}
add_action( 'wp_enqueue_scripts', 'lieuwe_theme_scripts' );

// Register Portfolio Custom Post Type
function lieuwe_register_portfolio_cpt() {
	$args = array(
		'labels'      => array(
			'name'          => 'Portfolio',
			'singular_name' => 'Portfolio Item',
		),
		'public'      => true,
		'has_archive' => true,
		'menu_icon'   => 'dashicons-portfolio',
		'rewrite'     => array( 'slug' => 'portfolio' ),
		'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
	);
	register_post_type( 'portfolio', $args );
}
add_action( 'init', 'lieuwe_register_portfolio_cpt' );

// Customizer section for Hero Video
function lieuwe_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'lieuwe_hero_section', array(
		'title'    => __( 'Hero Section', 'lieuwe-theme' ),
		'priority' => 30,
	) );

	$wp_customize->add_setting( 'hero_video_url', array(
		'default'   => '',
		'transport' => 'refresh',
	) );

	$wp_customize->add_control( 'hero_video_url', array(
		'label'    => __( 'Video MP4 URL', 'lieuwe-theme' ),
		'section'  => 'lieuwe_hero_section',
		'type'     => 'url',
	) );
}
add_action( 'customize_register', 'lieuwe_customize_register' );
