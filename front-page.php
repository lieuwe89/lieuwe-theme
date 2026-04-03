<?php get_header(); ?>
<main id="primary" class="site-main">

    <?php 
    $hero_video = get_theme_mod('hero_video_url'); 
    ?>
    <div class="hero-section bg-dark">
        <?php if ( $hero_video ) : ?>
            <video class="hero-media" autoplay muted loop playsinline src="<?php echo esc_url($hero_video); ?>"></video>
        <?php endif; ?>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title"><?php bloginfo('name'); ?></h1>
            <p class="hero-subtitle"><?php bloginfo('description'); ?></p>
        </div>
    </div>

    <section class="section-spacing bg-light text-center">
        <div class="container" style="font-size: 1.25rem;">
            <?php 
            if ( have_posts() ) :
                while ( have_posts() ) : the_post();
                    the_content();
                endwhile;
            endif;
            ?>
        </div>
    </section>

    <section class="section-spacing bg-dark">
        <div class="container-wide">
            <h2 class="section-heading" style="color: var(--color-text-light); text-align: center; margin-bottom: 3rem; font-family: var(--font-body); text-transform: uppercase; letter-spacing: 0.1em; font-size: 1rem;">Selected Works</h2>
            <div class="portfolio-grid" style="margin-bottom: 3rem;">
                <?php
                $portfolio_query = new WP_Query(array(
                    'post_type' => 'portfolio',
                    'posts_per_page' => 3
                ));
                
                if ( $portfolio_query->have_posts() ) :
                    while ( $portfolio_query->have_posts() ) : $portfolio_query->the_post(); ?>
                        <a href="<?php the_permalink(); ?>" class="portfolio-card">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail('large'); ?>
                            <?php else : ?>
                                <div style="width: 100%; height: 100%; background: var(--color-surface);"></div>
                            <?php endif; ?>
                            
                            <div class="portfolio-card-overlay">
                                <h2 class="portfolio-card-title"><?php the_title(); ?></h2>
                            </div>
                        </a>
                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p style="color: var(--color-text-light); text-align: center; width: 100%;">More works coming soon.</p>
                <?php endif; ?>
            </div>
            <div style="text-align: center;">
                <a href="<?php echo get_post_type_archive_link('portfolio'); ?>" style="color: var(--color-text-light); border-bottom: 1px solid var(--color-accent); padding-bottom: 5px;">View all works</a>
            </div>
        </div>
    </section>

    <section class="section-spacing bg-light">
        <div class="container">
            <h2 class="section-heading text-muted" style="margin-bottom: 2rem; font-family: var(--font-body); text-transform: uppercase; letter-spacing: 0.1em; font-size: 1rem;">Recent News</h2>
            <ul class="post-list">
                <?php
                $news_query = new WP_Query(array(
                    'post_type' => 'post',
                    'posts_per_page' => 3
                ));
                
                if ( $news_query->have_posts() ) :
                    while ( $news_query->have_posts() ) : $news_query->the_post(); ?>
                        <li class="post-item">
                            <span class="post-item-date"><?php echo get_the_date(); ?></span>
                            <h3 class="post-item-title" style="font-size: 1.5rem;"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        </li>
                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p>No news items found.</p>
                <?php endif; ?>
            </ul>
        </div>
    </section>

</main>
<?php get_footer(); ?>
