<footer class="site-footer section-dark">
    <div class="site-footer__inner container">
        <span class="site-footer__name">
            <?php bloginfo( 'name' ); ?>
        </span>

        <?php
        wp_nav_menu( [
            'theme_location' => 'footer',
            'container'      => false,
            'menu_class'     => 'site-footer__nav',
            'fallback_cb'    => false,
        ] );
        ?>

        <div class="site-footer__right">
            <a
                href="https://www.instagram.com/lieuwejongsma"
                class="site-footer__instagram"
                target="_blank"
                rel="noopener noreferrer"
            >Instagram</a>
            <span class="site-footer__copy">
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                <?php bloginfo( 'name' ); ?>
            </span>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
