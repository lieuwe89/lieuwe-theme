<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php // Preload the critical self-hosted fonts (latin) to remove FOUT on first paint. ?>
    <link rel="preload" href="<?php echo esc_url( get_theme_file_uri( 'assets/fonts/SortsMillGoudy-Regular.latin.woff2' ) ); ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo esc_url( get_theme_file_uri( 'assets/fonts/Jost.latin.woff2' ) ); ?>" as="font" type="font/woff2" crossorigin>

    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" id="site-header">
    <div class="site-header__inner">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__name">
            <?php bloginfo( 'name' ); ?>
        </a>

        <button
            class="nav-toggle"
            id="nav-toggle"
            aria-label="<?php esc_attr_e( 'Toggle navigation', 'lieuwe-theme' ); ?>"
            aria-expanded="false"
            aria-controls="site-nav"
        >
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
        </button>

        <nav class="site-nav" id="site-nav" aria-label="Primary navigation">
            <?php
            wp_nav_menu( [
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'site-nav__list',
                'fallback_cb'    => false,
            ] );
            ?>
        </nav>
    </div>
</header>
