<?php
/**
 * Publications catalogue — CPT, meta box, enqueue, helpers.
 *
 * @package Lieuwe_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the publication CPT (URL slug: /writing/).
 */
function lieuwe_register_publication_cpt(): void {
    if ( post_type_exists( 'publication' ) ) {
        return;
    }

    register_post_type( 'publication', [
        'labels' => [
            'name'          => 'Publications',
            'singular_name' => 'Publication',
            'add_new_item'  => 'Add New Publication',
            'edit_item'     => 'Edit Publication',
            'view_item'     => 'View Publication',
            'all_items'     => 'All Publications',
            'menu_name'     => 'Publications',
        ],
        'public'        => true,
        'has_archive'   => true,
        'supports'      => [ 'title', 'editor', 'thumbnail' ],
        'show_in_rest'  => true,
        'rewrite'       => [ 'slug' => 'writing' ],
        'menu_icon'     => 'dashicons-book-alt',
        'menu_position' => 6,
    ] );
}
add_action( 'init', 'lieuwe_register_publication_cpt', 5 );

/**
 * Flush rewrite rules once on theme activation so /writing/ resolves.
 */
function lieuwe_publications_flush_rewrites(): void {
    lieuwe_register_publication_cpt();
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'lieuwe_publications_flush_rewrites' );

/**
 * WP 6.5+ required for wp_enqueue_script_module (used by PDF.js).
 */
function lieuwe_publications_wp_version_notice(): void {
    if ( version_compare( get_bloginfo( 'version' ), '6.5', '>=' ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p><strong>Publications catalogue:</strong> requires WordPress 6.5 or later for ES module support. PDF previews will not load on the current version.</p></div>';
}
add_action( 'admin_notices', 'lieuwe_publications_wp_version_notice' );

/**
 * Whitelist of valid publication types.
 *
 * @return string[]
 */
function lieuwe_publication_types(): array {
    return [ 'Catalogue', 'Monograph', 'Essay', 'Feature', 'Paper' ];
}

/**
 * Register the publication meta box.
 */
function lieuwe_add_publication_meta_boxes(): void {
    add_meta_box(
        'lieuwe_publication_settings',
        'Publication Details',
        'lieuwe_render_publication_meta_box',
        'publication',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'lieuwe_add_publication_meta_boxes' );

/**
 * Render the publication meta box.
 */
function lieuwe_render_publication_meta_box( WP_Post $post ): void {
    $subtitle       = (string) get_post_meta( $post->ID, '_pub_subtitle', true );
    $year           = (string) get_post_meta( $post->ID, '_pub_year', true );
    $venue          = (string) get_post_meta( $post->ID, '_pub_venue', true );
    $type           = (string) get_post_meta( $post->ID, '_pub_type', true );
    $author         = (string) get_post_meta( $post->ID, '_pub_author', true );
    $pages          = (string) get_post_meta( $post->ID, '_pub_pages', true );
    $abstract       = (string) get_post_meta( $post->ID, '_pub_abstract', true );
    $pdf_id         = (int)    get_post_meta( $post->ID, '_pub_pdf_id', true );
    $allow_download = get_post_meta( $post->ID, '_pub_allow_download', true );
    $allow_download = ( '' === $allow_download ) ? '1' : $allow_download; // default on
    $paper_color    = (string) get_post_meta( $post->ID, '_pub_paper_color', true );
    $accent_color   = (string) get_post_meta( $post->ID, '_pub_accent_color', true );

    if ( '' === $paper_color )  { $paper_color  = '#f5ecd9'; }
    if ( '' === $accent_color ) { $accent_color = '#3a2a1f'; }
    if ( '' === $author )       { $author       = 'Lieuwe Jongsma'; }

    $pdf_url = $pdf_id ? wp_get_attachment_url( $pdf_id ) : '';

    wp_nonce_field( 'lieuwe_publication_meta_box', 'lieuwe_publication_meta_box_nonce' );
    ?>
    <style>
        .lieuwe-pub-fields { display:grid; gap:18px; }
        .lieuwe-pub-fields label { display:grid; gap:6px; font-weight:600; }
        .lieuwe-pub-fields input[type="text"],
        .lieuwe-pub-fields input[type="number"],
        .lieuwe-pub-fields textarea,
        .lieuwe-pub-fields select { width:100%; }
        .lieuwe-pub-fields textarea { min-height:96px; }
        .lieuwe-pub-fields__row { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .lieuwe-pub-fields__pdf { display:flex; gap:10px; align-items:center; }
        .lieuwe-pub-fields__pdf input[readonly] { background:#f6f7f7; }
        .lieuwe-pub-fields__hint { color:#646970; font-weight:400; font-size:12px; margin:0; }
        @media (max-width:782px) {
            .lieuwe-pub-fields__row { grid-template-columns:1fr; }
        }
    </style>
    <div class="lieuwe-pub-fields">
        <label>
            Subtitle
            <input type="text" name="lieuwe_pub[subtitle]" value="<?php echo esc_attr( $subtitle ); ?>">
            <p class="lieuwe-pub-fields__hint">Italic sub-line shown under the title in the catalogue.</p>
        </label>

        <div class="lieuwe-pub-fields__row">
            <label>
                Year
                <input type="number" name="lieuwe_pub[year]" value="<?php echo esc_attr( $year ); ?>" min="1900" max="<?php echo esc_attr( (string) ( (int) gmdate( 'Y' ) + 5 ) ); ?>">
                <p class="lieuwe-pub-fields__hint">Required — publications without a year are excluded from /writing/.</p>
            </label>
            <label>
                Venue
                <input type="text" name="lieuwe_pub[venue]" value="<?php echo esc_attr( $venue ); ?>">
                <p class="lieuwe-pub-fields__hint">e.g. "Apollo Magazine", "Mauritshuis".</p>
            </label>
        </div>

        <div class="lieuwe-pub-fields__row">
            <label>
                Type
                <select name="lieuwe_pub[type]">
                    <option value="">— select —</option>
                    <?php foreach ( lieuwe_publication_types() as $t ) : ?>
                        <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $type, $t ); ?>><?php echo esc_html( $t ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Author(s)
                <input type="text" name="lieuwe_pub[author]" value="<?php echo esc_attr( $author ); ?>">
                <p class="lieuwe-pub-fields__hint">Comma-separate co-authors: "Lieuwe Jongsma, Aike Lestestuiver".</p>
            </label>
        </div>

        <div class="lieuwe-pub-fields__row">
            <label>
                Pages
                <input type="number" name="lieuwe_pub[pages]" value="<?php echo esc_attr( $pages ); ?>" min="0">
                <p class="lieuwe-pub-fields__hint">Leave blank to auto-fill from the PDF at render time.</p>
            </label>
            <label>
                <input type="checkbox" name="lieuwe_pub[allow_download]" value="1" <?php checked( $allow_download, '1' ); ?>>
                Allow PDF download
                <p class="lieuwe-pub-fields__hint">When off, download button is hidden everywhere; the reader still works.</p>
            </label>
        </div>

        <label>
            Abstract
            <textarea name="lieuwe_pub[abstract]" rows="3"><?php echo esc_textarea( $abstract ); ?></textarea>
            <p class="lieuwe-pub-fields__hint">1–3 sentences. Shown in the row expansion and the reader sidebar.</p>
        </label>

        <label>
            PDF
            <div class="lieuwe-pub-fields__pdf">
                <input type="hidden" name="lieuwe_pub[pdf_id]" id="lieuwe_pub_pdf_id" value="<?php echo esc_attr( (string) $pdf_id ); ?>">
                <input type="text" readonly value="<?php echo esc_attr( $pdf_url ); ?>" id="lieuwe_pub_pdf_url" placeholder="No PDF selected">
                <button type="button" class="button" id="lieuwe_pub_pdf_pick">Select PDF</button>
                <button type="button" class="button" id="lieuwe_pub_pdf_clear">Clear</button>
            </div>
            <p class="lieuwe-pub-fields__hint">Upload a .pdf via the Media Library. Non-PDF attachments are ignored at render time.</p>
        </label>

        <div class="lieuwe-pub-fields__row">
            <label>
                Paper color
                <input type="color" name="lieuwe_pub[paper_color]" value="<?php echo esc_attr( $paper_color ); ?>">
            </label>
            <label>
                Accent color
                <input type="color" name="lieuwe_pub[accent_color]" value="<?php echo esc_attr( $accent_color ); ?>">
            </label>
        </div>
    </div>

    <script>
    (function ($) {
        if (!window.wp || !wp.media) { return; }
        var frame;
        $('#lieuwe_pub_pdf_pick').on('click', function (e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title:    'Select PDF',
                button:   { text: 'Use this PDF' },
                library:  { type: 'application/pdf' },
                multiple: false
            });
            frame.on('select', function () {
                var att = frame.state().get('selection').first().toJSON();
                $('#lieuwe_pub_pdf_id').val(att.id);
                $('#lieuwe_pub_pdf_url').val(att.url);
            });
            frame.open();
        });
        $('#lieuwe_pub_pdf_clear').on('click', function (e) {
            e.preventDefault();
            $('#lieuwe_pub_pdf_id').val('');
            $('#lieuwe_pub_pdf_url').val('');
        });
    })(jQuery);
    </script>
    <?php
}

/**
 * Ensure the Media Library JS is available on the publication editor.
 */
function lieuwe_publication_admin_enqueue( string $hook ): void {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || 'publication' !== $screen->post_type ) {
        return;
    }
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'lieuwe_publication_admin_enqueue' );
