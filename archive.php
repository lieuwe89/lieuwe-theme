<?php get_header(); ?>
<main id="primary" class="site-main section-spacing bg-light">
    <div class="container border-bottom">
        <header class="page-header">
            <h1 class="page-title">
				<?php
				if ( is_category() ) :
					single_cat_title();
				elseif ( is_tag() ) :
					single_tag_title();
				elseif ( is_author() ) :
					the_author();
				elseif ( is_day() || is_month() || is_year() ) :
					echo get_the_date();
				else :
					echo 'Archive';
				endif;
				?>
			</h1>
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
            <p>No posts published yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
