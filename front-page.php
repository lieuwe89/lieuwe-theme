<?php get_header(); ?>

<main>

<!-- HERO ----------------------------------------------------------------- -->
<section class="hero section-dark">
    <?php
    $video_url = lieuwe_hero_video_url();
    $image_url = lieuwe_hero_image_url();
    ?>

    <?php if ( $image_url ) : ?>
        <div
            class="hero__image"
            style="background-image: url('<?php echo esc_url( $image_url ); ?>')"
            role="img"
            aria-label="<?php esc_attr_e( 'Hero image', 'lieuwe-theme' ); ?>"
        ></div>
    <?php else : ?>
        <div class="hero__image hero__image--empty"></div>
    <?php endif; ?>

    <?php if ( $video_url ) : ?>
        <video
            class="hero__video"
            autoplay muted loop playsinline preload="auto"
            <?php if ( $image_url ) : ?>poster="<?php echo esc_url( $image_url ); ?>"<?php endif; ?>
        >
            <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
        </video>
    <?php endif; ?>

    <div class="hero__overlay" aria-hidden="true"></div>

    <div class="hero__content">
        <h1 class="hero__title"><?php bloginfo( 'name' ); ?></h1>
        <p class="hero__tagline"><?php bloginfo( 'description' ); ?></p>
    </div>
</section>

<!-- INTRO ---------------------------------------------------------------- -->
<section class="home-intro section-light">
    <div class="container container--narrow">
        <?php
        if ( have_posts() ) {
            the_post();
            the_content();
        }
        ?>
    </div>
</section>

<!-- PORTFOLIO PREVIEW ---------------------------------------------------- -->
<section class="home-portfolio section-dark">
    <div class="container">
        <h2 class="home-portfolio__heading">Portfolio</h2>

        <?php
        // Featured-first ordering: a single WP_Query with orderby on a named
        // meta clause silently falls back to date DESC, because WP renders
        // EXISTS/NOT EXISTS as a subquery (no meta_value column to sort on).
        // Run two queries and merge so the featured flag actually drives order.
        $portfolio_limit = 4;

        $featured_query = new WP_Query( [
            'post_type'      => 'portfolio_item',
            'posts_per_page' => $portfolio_limit,
            'meta_query'     => [
                [
                    'key'     => '_lieuwe_featured',
                    'compare' => 'EXISTS',
                ],
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ] );

        $portfolio_posts = $featured_query->posts;
        $remaining       = $portfolio_limit - count( $portfolio_posts );

        if ( $remaining > 0 ) {
            $fill_query = new WP_Query( [
                'post_type'      => 'portfolio_item',
                'posts_per_page' => $remaining,
                'post__not_in'   => wp_list_pluck( $portfolio_posts, 'ID' ),
                'meta_query'     => [
                    [
                        'key'     => '_lieuwe_featured',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            ] );
            $portfolio_posts = array_merge( $portfolio_posts, $fill_query->posts );
        }
        ?>

        <?php
        // Find the canvas page URL once, before the loop
        $canvas_pages = get_pages( [
            'meta_key'   => '_wp_page_template',
            'meta_value' => 'portfolio-canvas',
            'number'     => 1,
        ] );
        $canvas_url = $canvas_pages ? get_permalink( $canvas_pages[0]->ID ) : '';
        ?>

        <?php if ( ! empty( $portfolio_posts ) ) : ?>
            <div class="home-portfolio__grid">
                <?php global $post; foreach ( $portfolio_posts as $post ) : setup_postdata( $post ); ?>
                    <a href="<?php echo esc_url( $canvas_url . '#item-' . get_the_ID() ); ?>" class="portfolio-card">
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
                <?php endforeach; ?>
                <?php wp_reset_postdata(); ?>
            </div>
            <a href="<?php echo esc_url( $canvas_url ); ?>" class="home-section-link">
                View all work
            </a>
        <?php else : ?>
            <p class="home-empty">No portfolio items yet.</p>
        <?php endif; ?>
    </div>
</section>

<!-- NEWS PREVIEW --------------------------------------------------------- -->
<section class="home-news section-light">
    <div class="container">
        <h2 class="home-news__heading">News</h2>

        <?php
        $news_query = new WP_Query( [
            'post_type'      => 'post',
            'posts_per_page' => 3,
            'no_found_rows'  => true,
        ] );
        ?>

        <?php if ( $news_query->have_posts() ) : ?>
            <ul class="home-news__list">
                <?php while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
                    <li class="home-news__item">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <a href="<?php the_permalink(); ?>" class="home-news__thumb" aria-hidden="true" tabindex="-1">
                                <?php the_post_thumbnail( 'thumbnail' ); ?>
                            </a>
                        <?php else : ?>
                            <div class="home-news__thumb home-news__thumb--placeholder" aria-hidden="true"></div>
                        <?php endif; ?>
                        <div class="home-news__text">
                            <time class="home-news__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                            </time>
                            <a href="<?php the_permalink(); ?>" class="home-news__title">
                                <?php the_title(); ?>
                            </a>
                        </div>
                    </li>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </ul>
            <a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>" class="home-section-link">
                All news
            </a>
        <?php else : ?>
            <p class="home-empty">No news yet.</p>
        <?php endif; ?>
    </div>
</section>

</main>

<?php get_footer(); ?>
