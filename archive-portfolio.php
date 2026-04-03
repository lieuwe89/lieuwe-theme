<?php get_header(); ?>
<main id="primary" class="site-main section-spacing bg-dark">
    <div class="container-wide">
        <header class="page-header" style="color: var(--color-text-light);">
            <h1 class="page-title">Portfolio</h1>
        </header>

        <?php if ( have_posts() ) : ?>
            <div class="portfolio-grid">
                <?php while ( have_posts() ) : the_post(); ?>
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
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p style="color: var(--color-text-light);">No portfolio items available at the moment.</p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
