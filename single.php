<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>

<main class="single-post">

    <?php if ( has_post_thumbnail() ) : ?>
        <div class="single-post__hero">
            <?php the_post_thumbnail( 'full', [ 'class' => 'single-post__hero-image' ] ); ?>
        </div>
    <?php endif; ?>

    <article class="single-post__content section-light">
        <div class="container container--narrow">
            <header class="single-post__header">
                <time class="single-post__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                    <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                </time>
                <h1 class="single-post__title"><?php the_title(); ?></h1>
            </header>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>

            <footer class="single-post__footer">
                <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>" class="back-link">
                    &larr; All news
                </a>
            </footer>
        </div>
    </article>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
