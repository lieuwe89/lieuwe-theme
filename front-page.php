<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<?php get_header(); ?>

<main>

<!-- HERO ----------------------------------------------------------------- -->
<section class="hero section-dark">
    <?php
    $video_url = lieuwe_hero_video_url();
    $image_url = lieuwe_hero_image_url();
    ?>

    <?php if ( $image_url ) : ?>
        <div
            class="hero__image"
            style="background-image: url('<?php echo esc_url( $image_url ); ?>')"
            role="img"
            aria-label="<?php esc_attr_e( 'Hero image', 'lieuwe-theme' ); ?>"
        ></div>
    <?php else : ?>
        <div class="hero__image hero__image--empty"></div>
    <?php endif; ?>

    <?php if ( $video_url ) : ?>
        <video
            class="hero__video"
            autoplay muted loop playsinline preload="auto"
            <?php if ( $image_url ) : ?>poster="<?php echo esc_url( $image_url ); ?>"<?php endif; ?>
        >
            <source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
        </video>
    <?php endif; ?>

    <div class="hero__overlay" aria-hidden="true"></div>

    <div class="hero__content">
        <h1 class="hero__title"><?php bloginfo( 'name' ); ?></h1>
        <p class="hero__tagline"><?php bloginfo( 'description' ); ?></p>
    </div>
</section>

<!-- INTRO ---------------------------------------------------------------- -->
<section class="home-intro section-light">
    <div class="container container--narrow">
        <?php
        if ( have_posts() ) {
            the_post();
            the_content();
        }
        ?>
    </div>
</section>

<?php
// Shared lookups for the sections below ---------------------------------

// Portfolio canvas page (plugin-owned template) — used by filmstrip + mixed links.
$canvas_pages = get_pages( [
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'portfolio-canvas',
    'number'     => 1,
] );
$canvas_url = $canvas_pages ? get_permalink( $canvas_pages[0]->ID ) : '';

$mix_types = [ 'post', 'portfolio_item', 'publication' ];

// Lead story: newest _lieuwe_lead-flagged item across the three types;
// fallback to the newest post when nothing is flagged.
$lead_query = new WP_Query( [
    'post_type'           => $mix_types,
    'posts_per_page'      => 1,
    'meta_query'          => [
        [
            'key'     => '_lieuwe_lead',
            'compare' => 'EXISTS',
        ],
    ],
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
] );
if ( ! $lead_query->posts ) {
    $lead_query = new WP_Query( [
        'post_type'           => 'post',
        'posts_per_page'      => 1,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ] );
}
$lead = $lead_query->posts[0] ?? null;

// Portfolio items live on the canvas page, everything else has a permalink.
$lieuwe_mix_url = static function ( WP_Post $p ) use ( $canvas_url ): string {
    if ( 'portfolio_item' === $p->post_type ) {
        return $canvas_url ? $canvas_url . '#item-' . $p->ID : '';
    }
    return (string) get_permalink( $p );
};

// Type label for rail items. Dates only for news (spec).
$lieuwe_mix_label = static function ( WP_Post $p ): string {
    if ( 'portfolio_item' === $p->post_type ) {
        return 'Portfolio';
    }
    if ( 'publication' === $p->post_type ) {
        return 'Publication';
    }
    return 'News · ' . get_the_date( 'j F', $p );
};
?>

<!-- LEAD STORY + MEANWHILE RAIL ------------------------------------------ -->
<?php if ( $lead ) : ?>
<section class="home-lead section-light">
    <div class="container home-lead__grid">
        <article class="home-lead__main">
            <p class="home-lead__eyebrow">Latest — <?php echo esc_html( get_the_date( 'j F Y', $lead ) ); ?></p>
            <h2 class="home-lead__title">
                <a href="<?php echo esc_url( $lieuwe_mix_url( $lead ) ); ?>"><?php echo esc_html( get_the_title( $lead ) ); ?></a>
            </h2>
            <?php if ( has_post_thumbnail( $lead ) ) : ?>
                <a class="home-lead__image" href="<?php echo esc_url( $lieuwe_mix_url( $lead ) ); ?>" tabindex="-1" aria-hidden="true">
                    <?php echo get_the_post_thumbnail( $lead, 'large' ); ?>
                </a>
            <?php endif; ?>
            <?php $lead_excerpt = get_the_excerpt( $lead ); ?>
            <?php if ( $lead_excerpt ) : ?>
                <p class="home-lead__excerpt"><?php echo wp_kses_post( $lead_excerpt ); ?></p>
            <?php endif; ?>
            <a class="home-section-link" href="<?php echo esc_url( $lieuwe_mix_url( $lead ) ); ?>">Read on</a>
        </article>

        <aside class="home-rail">
            <p class="home-lead__eyebrow">Meanwhile</p>
            <?php
            $rail_query = new WP_Query( [
                'post_type'           => $mix_types,
                'posts_per_page'      => 4,
                'post__not_in'        => [ $lead->ID ],
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            ] );
            ?>
            <?php if ( $rail_query->posts ) : ?>
                <ul class="home-rail__list">
                    <?php foreach ( $rail_query->posts as $rail_post ) : ?>
                        <li class="home-rail__item">
                            <a href="<?php echo esc_url( $lieuwe_mix_url( $rail_post ) ); ?>"><?php echo esc_html( get_the_title( $rail_post ) ); ?></a>
                            <span class="home-rail__meta"><?php echo esc_html( $lieuwe_mix_label( $rail_post ) ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="home-section-link" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">All news</a>
        </aside>
    </div>
</section>
<?php endif; ?>

<!-- FROM THE WORKSHOP ----------------------------------------------------- -->
<?php
// Featured-first ordering: a single WP_Query with orderby on a named
// meta clause silently falls back to date DESC, because WP renders
// EXISTS/NOT EXISTS as a subquery (no meta_value column to sort on).
// Run two queries and merge so the featured flag actually drives order.
$strip_limit = 4;

$featured_query = new WP_Query( [
    'post_type'      => 'portfolio_item',
    'posts_per_page' => $strip_limit,
    'meta_query'     => [
        [
            'key'     => '_lieuwe_featured',
            'compare' => 'EXISTS',
        ],
    ],
    'orderby'        => 'date',
    'order'          => 'DESC',
    'no_found_rows'  => true,
] );

$strip_posts = $featured_query->posts;
$remaining   = $strip_limit - count( $strip_posts );

if ( $remaining > 0 ) {
    $fill_query = new WP_Query( [
        'post_type'      => 'portfolio_item',
        'posts_per_page' => $remaining,
        'post__not_in'   => wp_list_pluck( $strip_posts, 'ID' ),
        'meta_query'     => [
            [
                'key'     => '_lieuwe_featured',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ] );
    $strip_posts = array_merge( $strip_posts, $fill_query->posts );
}
?>

<?php if ( $strip_posts ) : ?>
<section class="home-strip">
    <div class="container">
        <div class="home-strip__head">
            <p class="home-lead__eyebrow">From the workshop</p>
            <?php if ( $canvas_url ) : ?>
                <a class="home-section-link" href="<?php echo esc_url( $canvas_url ); ?>">View all work</a>
            <?php endif; ?>
        </div>
        <div class="home-strip__grid">
            <?php global $post; foreach ( $strip_posts as $post ) : setup_postdata( $post ); ?>
                <a href="<?php echo esc_url( $canvas_url . '#item-' . get_the_ID() ); ?>" class="strip-card">
                    <span class="strip-card__image">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large' ); ?>
                        <?php else : ?>
                            <?php $strip_video_url = get_post_meta( get_the_ID(), 'portfolio_video', true ); ?>
                            <?php if ( $strip_video_url ) : ?>
                                <span class="portfolio-card__video-thumb" data-video="<?php echo esc_url( $strip_video_url ); ?>"></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span>
                    <span class="strip-card__title"><?php the_title(); ?></span>
                </a>
            <?php endforeach; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- TEACHING + SERVICES FOOT ---------------------------------------------- -->
<?php
// Teaching archive link is false when the lieuwe-teaching plugin is inactive.
$teaching_url = get_post_type_archive_link( 'teaching_event' );

// Services page: template lookup first; the template may also be applied by
// slug (page-services.php via hierarchy), so fall back to the slug.
$services_pages = get_pages( [
    'meta_key'   => '_wp_page_template',
    'meta_value' => 'page-services.php',
    'number'     => 1,
] );
$services_page = $services_pages ? $services_pages[0] : get_page_by_path( 'services' );
$services_url  = $services_page ? get_permalink( $services_page ) : '';

$teaching_img = lieuwe_home_teaching_image_url();
?>

<?php if ( $teaching_url || $services_url ) : ?>
<section class="home-foot">
    <?php if ( $teaching_url ) : ?>
        <a class="home-foot__block home-foot__block--teaching" href="<?php echo esc_url( $teaching_url ); ?>">
            <?php if ( $teaching_img ) : ?>
                <span class="home-foot__bg" style="background-image: url('<?php echo esc_url( $teaching_img ); ?>')" aria-hidden="true"></span>
            <?php endif; ?>
            <span class="home-foot__inner">
                <span class="home-foot__eyebrow">Teaching</span>
                <span class="home-foot__title">Courses &amp; workshops</span>
                <span class="home-foot__link">See what&rsquo;s coming</span>
            </span>
        </a>
    <?php endif; ?>
    <?php if ( $services_url ) : ?>
        <a class="home-foot__block home-foot__block--services" href="<?php echo esc_url( $services_url ); ?>">
            <span class="home-foot__inner">
                <span class="home-foot__eyebrow">Services</span>
                <span class="home-foot__title">Work with me</span>
                <span class="home-foot__link">What I offer</span>
            </span>
        </a>
    <?php endif; ?>
</section>
<?php endif; ?>

</main>

<?php get_footer(); ?>
