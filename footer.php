<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<footer class="site-footer section-terracotta">
    <div class="site-footer__inner container">
        <div class="site-footer__brand">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-footer__name">
                <?php bloginfo( 'name' ); ?>
            </a>
            <?php if ( get_bloginfo( 'description' ) ) : ?>
                <p class="site-footer__tagline"><?php bloginfo( 'description' ); ?></p>
            <?php endif; ?>
        </div>

        <div class="site-footer__links">
            <?php
            wp_nav_menu( [
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'site-footer__nav',
                'fallback_cb'    => false,
            ] );
            ?>

            <div class="site-footer__social">
                <a href="https://www.instagram.com/lieuwe_jongsma/" class="site-footer__instagram" target="_blank" rel="noopener noreferrer">Instagram</a>
                <a href="https://playground.lieuwejongsma.nl" class="site-footer__playground" target="_blank" rel="noopener noreferrer">Playground</a>
                <?php if ( $lieuwe_privacy_url = get_privacy_policy_url() ) : ?>
                    <a href="<?php echo esc_url( $lieuwe_privacy_url ); ?>" class="site-footer__privacy">Privacy</a>
                <?php endif; ?>
            </div>

            <?php
            $lieuwe_business = function_exists( 'lieuwe_business_details' ) ? lieuwe_business_details() : [];
            if ( $lieuwe_business ) :
                $lieuwe_colofon = [];
                if ( ! empty( $lieuwe_business['name'] ) )  { $lieuwe_colofon[] = $lieuwe_business['name']; }
                if ( ! empty( $lieuwe_business['kvk'] ) )   { $lieuwe_colofon[] = 'KvK ' . $lieuwe_business['kvk']; }
                if ( ! empty( $lieuwe_business['btw'] ) )   { $lieuwe_colofon[] = 'BTW ' . $lieuwe_business['btw']; }
                if ( ! empty( $lieuwe_business['email'] ) ) { $lieuwe_colofon[] = $lieuwe_business['email']; }
                ?>
                <p class="site-footer__colofon"><?php echo esc_html( implode( ' · ', $lieuwe_colofon ) ); ?></p>
            <?php endif; ?>

            <span class="site-footer__copy">
                &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>
            </span>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
