<?php
/*
 * Template Name: Contact Page
 *
 * Assign via WP Admin → Edit Page → Page Attributes → Template.
 * Uses identical page structure to page.php — only the form receives
 * special styling via the .contact-page class on <main>.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<main class="static-page contact-page">

    <div class="static-page__header section-dark">
        <div class="container">
            <h1 class="static-page__title"><?php the_title(); ?></h1>
        </div>
    </div>

    <div class="static-page__content section-light">
        <div class="container container--narrow">
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </div>
    </div>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
