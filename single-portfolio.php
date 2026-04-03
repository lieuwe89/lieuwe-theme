<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<main class="single-portfolio">

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="single-portfolio__hero">
            <?php the_post_thumbnail( 'full', [ 'class' => 'single-portfolio__hero-image' ] ); ?>
        </div>
    <?php else : ?>
        <div class="single-portfolio__hero single-portfolio__hero--empty section-dark"></div>
    <?php endif; ?>

    <article class="single-portfolio__content section-light">
        <div class="container container--narrow">
            <h1 class="single-portfolio__title"><?php the_title(); ?></h1>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'portfolio_item' ) ); ?>" class="back-link">
                &larr; All work
            </a>
        </div>
    </article>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
