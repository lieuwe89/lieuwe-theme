<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="primary" class="site-main section-spacing bg-light">
    <div class="container">
        <header class="page-header">
            <h1 class="page-title"><?php single_post_title(); ?></h1>
        </header>

        <?php if ( have_posts() ) : ?>
            <ul class="post-list">
                <?php while ( have_posts() ) : the_post(); ?>
                    <li class="post-item">
                        <span class="post-item-date"><?php echo get_the_date(); ?></span>
                        <h2 class="post-item-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <div class="post-item-excerpt text-muted"><?php the_excerpt(); ?></div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else : ?>
            <p>No content found.</p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
