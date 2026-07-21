<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
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
            <?php
            $current_year = null;
            $year_buckets = [];
            // First pass — collect per-year counts so each year header can show "N publications".
            foreach ( $query->posts as $p ) {
                $y = (int) get_post_meta( $p->ID, '_pub_year', true );
                $year_buckets[ $y ] = ( $year_buckets[ $y ] ?? 0 ) + 1;
            }

            while ( $query->have_posts() ) :
                $query->the_post();
                $post_id  = get_the_ID();
                $year     = (int)    get_post_meta( $post_id, '_pub_year',           true );
                $subtitle = (string) get_post_meta( $post_id, '_pub_subtitle',       true );
                $venue    = (string) get_post_meta( $post_id, '_pub_venue',          true );
                $type     = (string) get_post_meta( $post_id, '_pub_type',           true );
                $author   = (string) get_post_meta( $post_id, '_pub_author',         true );
                $pages    = (int)    get_post_meta( $post_id, '_pub_pages',          true );
                $abstract = (string) get_post_meta( $post_id, '_pub_abstract',       true );
                $paper    = (string) get_post_meta( $post_id, '_pub_paper_color',    true );
                $accent   = (string) get_post_meta( $post_id, '_pub_accent_color',   true );
                $allow_dl = '0' !== (string) get_post_meta( $post_id, '_pub_allow_download', true );
                $has_pdf  = lieuwe_pub_has_pdf( $post_id );
                $pdf_id   = (int) get_post_meta( $post_id, '_pub_pdf_id', true );
                $pdf_url  = $has_pdf ? wp_get_attachment_url( $pdf_id ) : '';
                $cover_side = (string) get_post_meta( $post_id, '_pub_cover_side', true );
                if ( ! in_array( $cover_side, [ 'right', 'left' ], true ) ) { $cover_side = 'right'; }
                $slug     = get_post_field( 'post_name', $post_id );

                $search_blob = strtolower( trim( implode( ' ', array_filter( [
                    get_the_title(), $subtitle, $venue, $abstract, $type, $author,
                ] ) ) ) );

                if ( $year !== $current_year ) :
                    $current_year = $year;
                    $count        = $year_buckets[ $year ];
                    $label        = ( 1 === $count ) ? '1 publication' : ( $count . ' publications' );
                    ?>
                    <header class="pub-yearhead" data-year="<?php echo esc_attr( (string) $year ); ?>">
                        <span class="pub-yearhead__year"><?php echo esc_html( (string) $year ); ?></span>
                        <span class="pub-yearhead__rule" aria-hidden="true"></span>
                        <span class="pub-yearhead__count"><?php echo esc_html( $label ); ?></span>
                    </header>
                <?php endif; ?>

                <article
                    class="pub-row"
                    id="pub-row-<?php echo esc_attr( $slug ); ?>"
                    data-id="<?php echo esc_attr( $slug ); ?>"
                    data-type="<?php echo esc_attr( $type ); ?>"
                    data-year="<?php echo esc_attr( (string) $year ); ?>"
                    data-has-pdf="<?php echo $has_pdf ? 'true' : 'false'; ?>"
                    data-allow-download="<?php echo $allow_dl ? 'true' : 'false'; ?>"
                    data-pdf-url="<?php echo esc_url( $pdf_url ); ?>"
                    data-pages="<?php echo esc_attr( (string) $pages ); ?>"
                    data-author="<?php echo esc_attr( $author ); ?>"
                    data-venue="<?php echo esc_attr( $venue ); ?>"
                    data-title="<?php echo esc_attr( get_the_title() ); ?>"
                    data-subtitle="<?php echo esc_attr( $subtitle ); ?>"
                    data-abstract="<?php echo esc_attr( $abstract ); ?>"
                    data-paper-color="<?php echo esc_attr( $paper ); ?>"
                    data-accent-color="<?php echo esc_attr( $accent ); ?>"
                    data-cover-side="<?php echo esc_attr( $cover_side ); ?>"
                    data-permalink="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
                    data-search="<?php echo esc_attr( $search_blob ); ?>"
                    style="--pub-paper: <?php echo esc_attr( $paper ); ?>; --pub-accent-ink: <?php echo esc_attr( $accent ); ?>;">
                    <div class="pub-row__main">
                        <h3 class="pub-row__title"><?php the_title(); ?></h3>
                        <?php if ( $subtitle ) : ?>
                            <p class="pub-row__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="pub-row__venue"><?php echo esc_html( $venue ); ?></div>
                    <div class="pub-row__type">
                        <span><?php echo esc_html( $type ); ?></span>
                        <span class="pub-row__pages" data-pages-fallback>
                            <?php echo $pages ? esc_html( $pages . 'pp' ) : '—'; ?>
                        </span>
                    </div>
                    <button
                        type="button"
                        class="pub-row__toggle"
                        aria-expanded="false"
                        aria-controls="pub-panel-<?php echo esc_attr( $slug ); ?>">
                        <span class="visually-hidden">Expand</span>
                        <span class="pub-row__toggle-glyph" aria-hidden="true"></span>
                    </button>
                </article>

                <section
                    id="pub-panel-<?php echo esc_attr( $slug ); ?>"
                    class="pub-panel"
                    role="region"
                    aria-labelledby="pub-row-<?php echo esc_attr( $slug ); ?>"
                    hidden>
                    <div class="pub-panel__spread" data-spread-mount>
                        <div class="pub-panel__placeholder" style="background-color: var(--pub-paper);">
                            <span class="pub-panel__loading">Loading…</span>
                        </div>
                        <button type="button" class="pub-panel__nav pub-panel__nav--prev" aria-label="Previous spread" hidden>‹</button>
                        <button type="button" class="pub-panel__nav pub-panel__nav--next" aria-label="Next spread" hidden>›</button>
                        <p class="pub-panel__caption" data-spread-caption hidden></p>
                    </div>
                    <div class="pub-panel__body">
                        <p class="pub-panel__eyebrow">ABSTRACT</p>
                        <?php if ( $abstract ) : ?>
                            <p class="pub-panel__abstract"><?php echo esc_html( $abstract ); ?></p>
                        <?php endif; ?>
                        <dl class="pub-panel__meta">
                            <div><dt>Author</dt><dd><?php echo esc_html( $author ); ?></dd></div>
                            <div><dt>Length</dt><dd><span data-pages-fallback><?php echo $pages ? esc_html( $pages . ' pages' ) : '—'; ?></span></dd></div>
                        </dl>
                        <div class="pub-panel__actions">
                            <?php if ( $has_pdf ) : ?>
                                <button type="button" class="pub-btn pub-btn--primary" data-action="open-reader">Open in reader</button>
                                <?php if ( $allow_dl ) : ?>
                                    <a class="pub-btn" href="<?php echo esc_url( $pdf_url ); ?>" download>Download PDF</a>
                                <?php endif; ?>
                            <?php else : ?>
                                <button type="button" class="pub-btn pub-btn--disabled" disabled>Open in reader — soon</button>
                            <?php endif; ?>
                            <button type="button" class="pub-btn" data-action="copy-permalink">Copy permalink</button>
                        </div>
                    </div>
                </section>
            <?php endwhile; ?>
        </div>
    </section>

</main>

<?php
wp_reset_postdata();
get_footer();
