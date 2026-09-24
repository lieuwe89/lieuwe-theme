<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>

<main class="error-404 section-dark">
    <div class="container error-404__inner">
        <h1 class="error-404__title">Page not found</h1>
        <p class="error-404__message">That page doesn't exist, or it has moved. One of these might be what you were after:</p>
        <p class="error-404__links">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="home-section-link">Home</a>
            <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="home-section-link">News</a>
            <a href="<?php echo esc_url( home_url( '/writing/' ) ); ?>" class="home-section-link">Publications</a>
            <a href="<?php echo esc_url( home_url( '/portfolio/' ) ); ?>" class="home-section-link">Portfolio</a>
            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="home-section-link">Contact</a>
        </p>
    </div>
</main>

<?php get_footer(); ?>
