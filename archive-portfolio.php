<?php get_header(); ?>

<main class="portfolio-archive">
    <div class="portfolio-archive__header section-dark">
        <div class="container">
            <h1 class="portfolio-archive__title">Portfolio</h1>
        </div>
    </div>

    <div class="section-light">
        <div class="container">
            <?php if ( have_posts() ) : ?>
                <div class="portfolio-grid">
                    <?php while ( have_posts() ) : the_post(); ?>
                        <a href="<?php the_permalink(); ?>" class="portfolio-card">
                            <?php the_post_thumbnail( 'large', [ 'class' => 'portfolio-card__image' ] ); ?>
                            <span class="portfolio-card__title"><?php the_title(); ?></span>
                        </a>
                    <?php endwhile; ?>
                </div>

                <div class="archive-pagination">
                    <?php
                    the_posts_pagination( [
                        'prev_text' => '&larr; Previous',
                        'next_text' => 'Next &rarr;',
                    ] );
                    ?>
                </div>

            <?php else : ?>
                <p class="archive-empty">No portfolio items found.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
