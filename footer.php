	<footer class="site-footer bg-dark">
		<div class="container-wide footer-inner">
			<div class="footer-copy">
				&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.
			</div>
			
			<div class="footer-nav">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</div>
			
			<div class="footer-social">
				<a href="#" target="_blank" rel="noopener noreferrer">Instagram</a>
			</div>
		</div>
	</footer>

	<?php wp_footer(); ?>
</body>
</html>
