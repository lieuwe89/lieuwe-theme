<?php
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
                <div class="news-rows">
                    <?php $i = 0; while ( have_posts() ) : the_post(); ?>
                        <article class="news-row<?php echo ( $i % 2 !== 0 ) ? ' news-row--reverse' : ''; ?>">

                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'full', [
                                    'class' => 'news-row__img',
                                    'alt'   => esc_attr( get_the_title() ),
                                ] ); ?>
                            <?php else : ?>
                                <div class="news-row__img-placeholder" aria-hidden="true"></div>
                            <?php endif; ?>

                            <div class="news-row__body">
                                <time class="news-row__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                    <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                                </time>
                                <a href="<?php the_permalink(); ?>" class="news-row__title">
                                    <?php the_title(); ?>
                                </a>
                                <a href="<?php the_permalink(); ?>" class="news-row__link" aria-hidden="true" tabindex="-1">
                                    Read &rarr;
                                </a>
                            </div>

                        </article>
                    <?php $i++; endwhile; ?>
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
                <p class="archive-empty">No posts found.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
