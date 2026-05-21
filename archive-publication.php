<?php
/**
 * Archive template for the publication CPT — /writing/
 */

get_header();

$hero_line1 = lieuwe_publications_hero_title_line1();
$hero_line2 = lieuwe_publications_hero_title_line2();
$hero_intro = lieuwe_publications_hero_intro();

$query = new WP_Query( [
    'post_type'      => 'publication',
    'posts_per_page' => -1,
    'meta_key'       => '_pub_year',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
    'meta_query'     => [ [ 'key' => '_pub_year', 'compare' => 'EXISTS' ] ],
] );

$total      = $query->found_posts;
$with_pdf   = 0;
$year_first = null;
$year_last  = null;
if ( $query->have_posts() ) {
    foreach ( $query->posts as $p ) {
        if ( lieuwe_pub_has_pdf( $p->ID ) ) {
            $with_pdf++;
        }
        $y = (int) get_post_meta( $p->ID, '_pub_year', true );
        if ( null === $year_first || $y < $year_first ) { $year_first = $y; }
        if ( null === $year_last  || $y > $year_last )  { $year_last  = $y; }
    }
}
$eyebrow = ( $year_first && $year_last )
    ? sprintf( 'Writing — %d to %s', $year_first, ( $year_last >= (int) gmdate( 'Y' ) ) ? 'today' : (string) $year_last )
    : 'Writing';
?>

<main class="pub" id="pub" data-total="<?php echo esc_attr( (string) $total ); ?>" data-with-pdf="<?php echo esc_attr( (string) $with_pdf ); ?>">

    <section class="pub-hero">
        <div class="pub-container">
            <p class="pub-hero__eyebrow"><?php echo esc_html( strtoupper( $eyebrow ) ); ?></p>
            <h1 class="pub-hero__title">
                <span class="pub-hero__line1"><?php echo esc_html( $hero_line1 ); ?></span>
                <span class="pub-hero__line2"><?php echo esc_html( $hero_line2 ); ?></span>
            </h1>
            <?php if ( $hero_intro ) : ?>
                <p class="pub-hero__intro"><?php echo esc_html( $hero_intro ); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ( 0 === $total ) : ?>
        <section class="pub-container pub-empty">
            <p class="pub-empty__line"><em>Publications coming soon.</em></p>
        </section>
        <?php get_footer(); return; ?>
    <?php endif; ?>

    <section class="pub-filterbar" aria-label="Filters">
        <div class="pub-container">
            <div class="pub-filterbar__row">
                <div class="pub-chips" role="tablist" aria-label="Filter by type">
                    <?php $chip_types = array_merge( [ 'All' ], lieuwe_publication_types() ); ?>
                    <?php foreach ( $chip_types as $chip ) : ?>
                        <button
                            type="button"
                            class="pub-chip <?php echo 'All' === $chip ? 'is-active' : ''; ?>"
                            data-filter="<?php echo esc_attr( $chip ); ?>"
                            aria-pressed="<?php echo 'All' === $chip ? 'true' : 'false'; ?>">
                            <?php echo esc_html( $chip ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="pub-filterbar__tools">
                    <label class="pub-search">
                        <span class="visually-hidden">Search publications</span>
                        <span class="pub-search__icon" aria-hidden="true">⌕</span>
                        <input type="search" class="pub-search__input" placeholder="Search title, venue, abstract…" autocomplete="off">
                    </label>
                    <button type="button" class="pub-sort" data-sort-asc="false" aria-label="Toggle year sort">
                        <span>Year</span> <span class="pub-sort__arrow">↓</span>
                    </button>
                </div>
            </div>
            <div class="pub-filterbar__status" aria-live="polite">
                <span class="pub-status__count"><?php echo esc_html( $total ); ?> of <?php echo esc_html( $total ); ?> publications</span>
                <span class="pub-status__detail"><?php echo esc_html( $with_pdf ); ?> with PDF · <?php echo esc_html( $total - $with_pdf ); ?> placeholder</span>
            </div>
            <p class="pub-noresults" hidden>
                <em>No publications match. Try clearing the filter or search.</em>
                <button type="button" class="pub-noresults__clear">Clear</button>
            </p>
        </div>
    </section>

    <section class="pub-list">
        <div class="pub-container">
            <?php /* TODO Task 9: year-grouped row loop */ ?>
        </div>
    </section>

</main>

<?php
wp_reset_postdata();
get_footer();
