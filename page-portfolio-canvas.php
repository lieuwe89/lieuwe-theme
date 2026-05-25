<?php
/**
 * Template Name: Portfolio Canvas
 *
 * Renders all portfolio_item posts in a single scrollable grid with
 * id="item-{ID}" anchors so the home page's portfolio cards can
 * deep-link straight to the matching item.
 */
get_header(); ?>

<main class="portfolio-canvas">
    <div class="portfolio-canvas__header section-dark">
        <div class="container">
            <h1 class="portfolio-canvas__title"><?php the_title(); ?></h1>
            <?php
            while ( have_posts() ) :
                the_post();
                $intro = get_the_content();
                if ( trim( wp_strip_all_tags( $intro ) ) !== '' ) :
                    ?>
                    <div class="portfolio-canvas__intro">
                        <?php the_content(); ?>
                    </div>
                    <?php
                endif;
            endwhile;
            ?>
        </div>
    </div>

    <div class="section-light">
        <div class="container">
            <?php
            $items = new WP_Query( [
                'post_type'      => 'portfolio_item',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            ] );
            ?>

            <?php if ( $items->have_posts() ) : ?>
                <div class="portfolio-grid portfolio-canvas__grid">
                    <?php while ( $items->have_posts() ) : $items->the_post(); ?>
                        <article id="item-<?php echo (int) get_the_ID(); ?>" class="portfolio-card portfolio-canvas__card">
                            <a href="<?php the_permalink(); ?>" class="portfolio-card__link">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'large', [ 'class' => 'portfolio-card__image' ] ); ?>
                                <?php else : ?>
                                    <?php $video_url = get_post_meta( get_the_ID(), 'portfolio_video', true ); ?>
                                    <?php if ( $video_url ) : ?>
                                        <div class="portfolio-card__image portfolio-card__video-thumb" data-video="<?php echo esc_url( $video_url ); ?>"></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span class="portfolio-card__title"><?php the_title(); ?></span>
                            </a>
                            <?php if ( has_excerpt() ) : ?>
                                <div class="portfolio-card__excerpt"><?php the_excerpt(); ?></div>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="archive-empty">No portfolio items yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
