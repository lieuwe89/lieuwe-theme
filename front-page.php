<?php get_header(); ?>

<main>

<!-- HERO ----------------------------------------------------------------- -->
<section class="hero section-dark">
    <?php $video_url = lieuwe_hero_video_url(); ?>

    <?php if ( $video_url ) : ?>
        <video class="hero__video" autoplay muted loop playsinline>
            <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
        </video>
    <?php else : ?>
        <?php $image_url = lieuwe_hero_image_url(); ?>
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
        $portfolio_query = new WP_Query( [
            'post_type'      => 'portfolio_item',
            'posts_per_page' => 4,
            'no_found_rows'  => true,
        ] );
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

        <?php if ( $portfolio_query->have_posts() ) : ?>
            <div class="home-portfolio__grid">
                <?php while ( $portfolio_query->have_posts() ) : $portfolio_query->the_post(); ?>
                    <a href="<?php echo esc_url( $canvas_url . '#item-' . get_the_ID() ); ?>" class="portfolio-card">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large', [ 'class' => 'portfolio-card__image' ] ); ?>
                        <?php else : ?>
                            <?php $video_url = get_post_meta( get_the_ID(), 'portfolio_video', true ); ?>
                            <?php if ( $video_url ) : ?>
                                <div class="portfolio-card__image portfolio-card__video-thumb" data-video="<?php echo esc_url( $video_url ); ?>"></div>
                            <?php else : ?>
                                <div class="portfolio-card__image portfolio-card__image--empty"></div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <span class="portfolio-card__title"><?php the_title(); ?></span>
                    </a>
                <?php endwhile; ?>
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
                        <time class="home-news__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                            <?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
                        </time>
                        <a href="<?php the_permalink(); ?>" class="home-news__title">
                            <?php the_title(); ?>
                        </a>
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
