<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
