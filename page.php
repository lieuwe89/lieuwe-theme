<?php get_header(); ?>
<main id="primary" class="site-main section-spacing bg-light">
	<?php while ( have_posts() ) : the_post(); ?>
		<div class="container page-header">
			<h1 class="page-title"><?php the_title(); ?></h1>
		</div>
		
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="container-wide" style="margin-bottom: 3rem;">
				<?php the_post_thumbnail('full'); ?>
			</div>
		<?php endif; ?>

		<div class="container entry-content">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
