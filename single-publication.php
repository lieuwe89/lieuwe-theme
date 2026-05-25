<?php
/**
 * Single-publication permalink template — /writing/<slug>/
 * Renders the publication's hero copy + actions. Optionally auto-opens the reader (?reader=1).
 */

get_header();

while ( have_posts() ) :
    the_post();
    $post_id  = get_the_ID();
    $subtitle = (string) get_post_meta( $post_id, '_pub_subtitle',     true );
    $venue    = (string) get_post_meta( $post_id, '_pub_venue',        true );
    $type     = (string) get_post_meta( $post_id, '_pub_type',         true );
    $year     = (int)    get_post_meta( $post_id, '_pub_year',         true );
    $author   = (string) get_post_meta( $post_id, '_pub_author',       true );
    $pages    = (int)    get_post_meta( $post_id, '_pub_pages',        true );
    $abstract = (string) get_post_meta( $post_id, '_pub_abstract',     true );
    $paper    = (string) get_post_meta( $post_id, '_pub_paper_color',  true );
    $accent   = (string) get_post_meta( $post_id, '_pub_accent_color', true );
    $allow_dl = '0' !== (string) get_post_meta( $post_id, '_pub_allow_download', true );
    $has_pdf  = lieuwe_pub_has_pdf( $post_id );
    $pdf_id   = (int) get_post_meta( $post_id, '_pub_pdf_id', true );
    $pdf_url  = $has_pdf ? wp_get_attachment_url( $pdf_id ) : '';
    $cover_side = (string) get_post_meta( $post_id, '_pub_cover_side', true );
    if ( ! in_array( $cover_side, [ 'right', 'left' ], true ) ) { $cover_side = 'right'; }
    $auto_open = isset( $_GET['reader'] ) && '1' === $_GET['reader'] && $has_pdf;
    $slug     = get_post_field( 'post_name', $post_id );
    ?>
    <main class="pub pub-single"
          data-auto-open-reader="<?php echo $auto_open ? 'true' : 'false'; ?>"
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
          data-permalink="<?php echo esc_url( get_permalink( $post_id ) ); ?>">

        <article class="pub-single__article pub-container">
            <p class="pub-single__eyebrow"><?php echo esc_html( strtoupper( trim( $type . ' · ' . $year, ' ·' ) ) ); ?></p>
            <h1 class="pub-single__title"><?php the_title(); ?></h1>
            <?php if ( $subtitle ) : ?>
                <p class="pub-single__subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
            <?php if ( $abstract ) : ?>
                <p class="pub-single__abstract"><?php echo esc_html( $abstract ); ?></p>
            <?php endif; ?>
            <dl class="pub-single__meta">
                <div><dt>Published by</dt><dd><?php echo esc_html( $venue ); ?></dd></div>
                <div><dt>Year</dt><dd><?php echo esc_html( (string) $year ); ?></dd></div>
                <div><dt>Author</dt><dd><?php echo esc_html( $author ); ?></dd></div>
                <div><dt>Pages</dt><dd><?php echo $pages ? esc_html( (string) $pages ) : '—'; ?></dd></div>
            </dl>
            <div class="pub-single__actions">
                <?php if ( $has_pdf ) : ?>
                    <button type="button" class="pub-btn pub-btn--primary" data-action="open-reader">Open in reader</button>
                    <?php if ( $allow_dl ) : ?>
                        <a class="pub-btn" href="<?php echo esc_url( $pdf_url ); ?>" download>Download PDF</a>
                    <?php endif; ?>
                <?php endif; ?>
                <a class="pub-btn" href="<?php echo esc_url( get_post_type_archive_link( 'publication' ) ); ?>">All writing →</a>
            </div>
        </article>
    </main>
<?php endwhile;
get_footer();
