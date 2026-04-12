<?php
/*
 * Template Name: Contact Page
 *
 * Assign this template to the Contact page via
 * WP Admin → Edit Page → Page Attributes → Template.
 */
get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<main class="contact-page">

    <div class="contact-page__hero section-dark">
        <div class="container">
            <h1 class="contact-page__title"><?php the_title(); ?></h1>
        </div>
    </div>

    <div class="contact-page__body section-light">
        <div class="container">
            <div class="contact-page__layout">

                <aside class="contact-page__sidebar" aria-hidden="true">
                    <p class="contact-page__lead">Get in<br>touch.</p>
                    <span class="contact-page__rule"></span>
                </aside>

                <div class="contact-page__form-wrap">
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
