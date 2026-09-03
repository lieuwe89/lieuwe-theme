<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>

<main class="error-404 section-dark">
    <div class="container error-404__inner">
        <span class="error-404__number">404</span>
        <p class="error-404__message">This page doesn't exist.</p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="home-section-link">
            Go home
        </a>
    </div>
</main>

<?php get_footer(); ?>
