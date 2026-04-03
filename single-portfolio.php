<?php get_header(); ?>
<main id="primary" class="site-main">
	<?php while ( have_posts() ) : the_post(); ?>
        
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="hero-section">
                <?php the_post_thumbnail('full', array('class' => 'hero-media')); ?>
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <h1 class="hero-title"><?php the_title(); ?></h1>
                </div>
            </div>
        <?php else : ?>
            <div class="hero-section bg-dark">
                <div class="hero-content">
                    <h1 class="hero-title"><?php the_title(); ?></h1>
                </div>
            </div>
        <?php endif; ?>

		<div class="section-spacing bg-light">
            <div class="container entry-content">
                <?php the_content(); ?>
            </div>
        </div>

	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
