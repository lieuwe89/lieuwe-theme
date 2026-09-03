<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>

<main class="search-page">
    <div class="search-page__header section-dark">
        <div class="container">
            <h1 class="search-page__title">
                Search results for: <em><?php echo esc_html( get_search_query() ); ?></em>
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
                                <p class="news-list__excerpt"><?php the_excerpt(); ?></p>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <div class="archive-pagination">
                    <?php the_posts_pagination( [ 'prev_text' => '&larr;', 'next_text' => '&rarr;' ] ); ?>
                </div>
            <?php else : ?>
                <p class="archive-empty">No results found for "<?php echo esc_html( get_search_query() ); ?>".</p>
                <?php get_search_form(); ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
