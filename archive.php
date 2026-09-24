<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>

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
        <div class="container-wide">
            <?php if ( have_posts() ) : ?>
                <div class="news-ledger">
                    <?php $current_year = ''; while ( have_posts() ) : the_post(); ?>
                        <?php $year = get_the_date( 'Y' ); ?>
                        <?php if ( $year !== $current_year ) : $current_year = $year; ?>
                            <h2 class="news-year"><?php echo esc_html( $year ); ?></h2>
                        <?php endif; ?>
                        <article class="news-entry">
                            <time class="news-entry__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date( 'j F' ) ); ?>
                            </time>
                            <div class="news-entry__body">
                                <h3><a href="<?php the_permalink(); ?>" class="news-entry__title"><?php the_title(); ?></a></h3>
                                <p class="news-entry__dek"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
                            </div>
                            <?php if ( has_post_thumbnail() ) : ?>
                                <a href="<?php the_permalink(); ?>" class="news-entry__plate" tabindex="-1" aria-hidden="true">
                                    <?php the_post_thumbnail( 'medium', [ 'alt' => '' ] ); ?>
                                </a>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                </div>

                <div class="archive-pagination">
                    <?php
                    the_posts_pagination( [
                        'prev_text' => 'Newer',
                        'next_text' => 'Older',
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
