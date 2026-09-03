<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if ( ! defined( 'ABSPATH' ) ) { exit; }
/*
 * Template Name: Services Page
 *
 * Assign via WP Admin -> Edit Page -> Page Attributes -> Template.
 * Also loads automatically for a page with the slug "services".
 */
get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
    <?php $services_page = lieuwe_get_services_page_data( get_the_ID() ); ?>

    <main class="static-page services-page">
        <div class="static-page__header section-dark">
            <div class="container">
                <h1 class="static-page__title">
                    <?php echo esc_html( get_the_title() ); ?><em>.</em>
                </h1>

                <?php if ( '' !== trim( $services_page['subtitle'] ) ) : ?>
                    <p class="static-page__subtitle">
                        <?php echo esc_html( $services_page['subtitle'] ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="static-page__content section-light">
            <?php if ( '' !== trim( $services_page['intro'] ) ) : ?>
                <div class="container container--narrow">
                    <div class="entry-content services-intro">
                        <?php echo wpautop( wp_kses_post( $services_page['intro'] ) ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="container services-section">
                <div class="services-list">
                    <?php foreach ( $services_page['services'] as $service ) : ?>
                        <article class="service">
                            <div class="service__media">
                                <?php if ( '' !== trim( $service['image_url'] ) ) : ?>
                                    <img
                                        class="service__image"
                                        src="<?php echo esc_url( $service['image_url'] ); ?>"
                                        alt="<?php echo esc_attr( $service['image_alt'] ); ?>"
                                        loading="lazy"
                                    >
                                <?php else : ?>
                                    <div class="service__image service__image--empty" aria-hidden="true"></div>
                                <?php endif; ?>

                                <?php if ( '' !== trim( $service['caption'] ) ) : ?>
                                    <p class="service__caption"><?php echo esc_html( $service['caption'] ); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="service__text">
                                <?php if ( '' !== trim( $service['number'] ) ) : ?>
                                    <span class="service__num"><?php echo esc_html( $service['number'] ); ?></span>
                                <?php endif; ?>

                                <?php if ( '' !== trim( $service['tagline'] ) ) : ?>
                                    <p class="service__tagline"><?php echo esc_html( $service['tagline'] ); ?></p>
                                <?php endif; ?>

                                <?php if ( '' !== trim( $service['title'] ) ) : ?>
                                    <h2 class="service__title"><?php echo esc_html( $service['title'] ); ?></h2>
                                <?php endif; ?>

                                <?php if ( '' !== trim( $service['body'] ) ) : ?>
                                    <div class="service__body">
                                        <?php echo wpautop( wp_kses_post( $service['body'] ) ); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( '' !== trim( $service['pullquote'] ) ) : ?>
                                    <blockquote class="service__pullquote">
                                        <?php echo wpautop( wp_kses_post( $service['pullquote'] ) ); ?>
                                    </blockquote>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ( '' !== trim( $services_page['coda'] ) || '' !== trim( $services_page['coda_mark'] ) ) : ?>
                    <div class="services-coda">
                        <?php if ( '' !== trim( $services_page['coda_mark'] ) ) : ?>
                            <span class="services-coda__mark"><?php echo esc_html( $services_page['coda_mark'] ); ?></span>
                        <?php endif; ?>

                        <?php echo wp_kses_post( $services_page['coda'] ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
<?php endwhile; ?>

<?php get_footer(); ?>
