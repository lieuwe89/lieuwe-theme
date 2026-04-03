<?php get_header(); ?>

<main class="news-archive">
    <div class="news-archive__header section-dark">
        <div class="container">
            <h1 class="news-archive__title">
                <?php
                if ( is_category() ) {
                    single_cat_title();
                } elseif ( is_tag() ) {
                    single_tag_title();
                } else {
                    echo 'News';
                }
                ?>
            </h1>
        </div>
    </div>

    <div class="section-light">
        <div class="container">
            <?php if ( have_posts() ) : ?>
                <ul class="news-list">
                    <?php while ( have_posts() ) : the_post(); ?>
                        <li class="news-list__item">
                            <time class="news-list__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                            </time>
                            <div class="news-list__body">
                                <a href="<?php the_permalink(); ?>" class="news-list__title">
                                    <?php the_title(); ?>
                                </a>
                                <?php if ( has_excerpt() ) : ?>
                                    <p class="news-list__excerpt"><?php the_excerpt(); ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <div class="archive-pagination">
                    <?php
                    the_posts_pagination( [
                        'prev_text' => '&larr; Previous',
                        'next_text' => 'Next &rarr;',
                    ] );
                    ?>
                </div>

            <?php else : ?>
                <p class="archive-empty">No posts found.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
