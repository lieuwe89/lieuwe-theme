<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<main class="static-page">
    <div class="static-page__header section-dark">
        <div class="container">
            <h1 class="static-page__title"><?php the_title(); ?></h1>
        </div>
    </div>

    <div class="static-page__content section-light">
        <div class="container container--narrow">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'large', [ 'class' => 'static-page__image' ] ); ?>
            <?php endif; ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </div>
    </div>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
