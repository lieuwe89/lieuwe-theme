<footer class="site-footer section-terracotta">
    <div class="site-footer__inner container">
        <?php
        wp_nav_menu( [
            'theme_location' => 'footer',
            'container'      => false,
            'menu_class'     => 'site-footer__nav',
            'fallback_cb'    => false,
        ] );
        ?>

        <div class="site-footer__right">
            <a href="https://playground.lieuwejongsma.nl" class="site-footer__playground" target="_blank" rel="noopener noreferrer">Playground</a>
            <a
                href="https://www.instagram.com/lieuwe_jongsma/"
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
