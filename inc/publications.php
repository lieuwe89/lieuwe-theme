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

/**
 * Save publication meta box.
 */
function lieuwe_save_publication_meta_box( int $post_id ): void {
    if ( ! isset( $_POST['lieuwe_publication_meta_box_nonce'] )
         || ! wp_verify_nonce( $_POST['lieuwe_publication_meta_box_nonce'], 'lieuwe_publication_meta_box' ) ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $raw = isset( $_POST['lieuwe_pub'] ) && is_array( $_POST['lieuwe_pub'] )
        ? wp_unslash( $_POST['lieuwe_pub'] )
        : [];

    // Strings
    update_post_meta( $post_id, '_pub_subtitle', sanitize_text_field( $raw['subtitle'] ?? '' ) );
    update_post_meta( $post_id, '_pub_venue',    sanitize_text_field( $raw['venue']    ?? '' ) );
    update_post_meta( $post_id, '_pub_author',   sanitize_text_field( $raw['author']   ?? '' ) );
    update_post_meta( $post_id, '_pub_abstract', sanitize_textarea_field( $raw['abstract'] ?? '' ) );

    // Year — empty stays empty so meta_query EXISTS excludes it
    if ( isset( $raw['year'] ) && '' !== $raw['year'] ) {
        $year = absint( $raw['year'] );
        $max  = (int) gmdate( 'Y' ) + 5;
        if ( $year >= 1900 && $year <= $max ) {
            update_post_meta( $post_id, '_pub_year', $year );
        } else {
            delete_post_meta( $post_id, '_pub_year' );
        }
    } else {
        delete_post_meta( $post_id, '_pub_year' );
    }

    // Type — whitelist
    $type    = $raw['type'] ?? '';
    $allowed = lieuwe_publication_types();
    if ( in_array( $type, $allowed, true ) ) {
        update_post_meta( $post_id, '_pub_type', $type );
    } else {
        delete_post_meta( $post_id, '_pub_type' );
    }

    // Pages — int or blank
    if ( isset( $raw['pages'] ) && '' !== $raw['pages'] ) {
        update_post_meta( $post_id, '_pub_pages', absint( $raw['pages'] ) );
    } else {
        delete_post_meta( $post_id, '_pub_pages' );
    }

    // PDF attachment
    $pdf_id = isset( $raw['pdf_id'] ) ? absint( $raw['pdf_id'] ) : 0;
    if ( $pdf_id > 0 && 'attachment' === get_post_type( $pdf_id ) ) {
        update_post_meta( $post_id, '_pub_pdf_id', $pdf_id );
    } else {
        delete_post_meta( $post_id, '_pub_pdf_id' );
    }

    // Allow download checkbox (default on when meta missing)
    update_post_meta( $post_id, '_pub_allow_download', isset( $raw['allow_download'] ) ? '1' : '0' );

    // Colors — sanitize_hex_color returns null for invalid
    $paper  = sanitize_hex_color( $raw['paper_color']  ?? '' );
    $accent = sanitize_hex_color( $raw['accent_color'] ?? '' );
    update_post_meta( $post_id, '_pub_paper_color',  $paper  ?: '#f5ecd9' );
    update_post_meta( $post_id, '_pub_accent_color', $accent ?: '#3a2a1f' );
}
add_action( 'save_post_publication', 'lieuwe_save_publication_meta_box' );

/**
 * True iff the publication has a valid PDF attachment.
 */
function lieuwe_pub_has_pdf( int $post_id ): bool {
    $id = (int) get_post_meta( $post_id, '_pub_pdf_id', true );
    if ( $id <= 0 ) {
        return false;
    }
    $url = wp_get_attachment_url( $id );
    return $url && '.pdf' === substr( strtolower( $url ), -4 );
}

/**
 * Admin notice on the publication edit screen when _pub_year is missing.
 */
function lieuwe_publication_year_notice(): void {
    $screen = get_current_screen();
    if ( ! $screen || 'publication' !== $screen->post_type || 'post' !== $screen->base ) {
        return;
    }
    global $post;
    if ( ! $post || 'auto-draft' === $post->post_status ) {
        return;
    }
    $year = get_post_meta( $post->ID, '_pub_year', true );
    if ( '' === $year ) {
        echo '<div class="notice notice-warning"><p>Set a <strong>Year</strong> for this publication to publish it on the catalogue.</p></div>';
    }
}
add_action( 'admin_notices', 'lieuwe_publication_year_notice' );

/**
 * Conditionally enqueue the catalogue CSS + JS + PDF.js on /writing/ and single-publication URLs.
 */
function lieuwe_pub_enqueue(): void {
    if ( ! is_post_type_archive( 'publication' ) && ! is_singular( 'publication' ) ) {
        return;
    }

    $ver        = wp_get_theme()->get( 'Version' );
    $template   = get_template_directory_uri();
    $worker_url = $template . '/assets/js/vendor/pdf.worker.min.mjs';

    wp_enqueue_style(
        'lieuwe-publications',
        $template . '/assets/css/publications.css',
        [ 'lieuwe-theme' ],
        $ver
    );

    wp_enqueue_script_module(
        'pdfjs',
        $template . '/assets/js/vendor/pdf.min.mjs',
        [],
        '4.7.76'
    );

    // Bridge: PDF.js v4 ESM exports its global as `pdfjsLib` when loaded as a module
    // we explicitly import. We import it inline + set the worker source before any
    // consumer module runs.
    wp_add_inline_script_module(
        'lieuwe-publications-pdfjs-bridge',
        "import * as pdfjsLib from '" . esc_js( $template . '/assets/js/vendor/pdf.min.mjs' ) . "';\n"
        . "window.pdfjsLib = pdfjsLib;\n"
        . "pdfjsLib.GlobalWorkerOptions.workerSrc = '" . esc_js( $worker_url ) . "';\n"
        . "window.dispatchEvent(new CustomEvent('lieuwe-pdfjs-ready'));"
    );

    wp_enqueue_script(
        'lieuwe-publications',
        $template . '/assets/js/publications.js',
        [],
        $ver,
        true
    );
    wp_enqueue_script(
        'lieuwe-publications-reader',
        $template . '/assets/js/publications-reader.js',
        [ 'lieuwe-publications' ],
        $ver,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'lieuwe_pub_enqueue' );

/**
 * If a primary menu is assigned but contains no /writing/ item, append one
 * automatically — only when the publication archive resolves to a real URL.
 */
function lieuwe_publications_menu_fallback( $items, $args ) {
    if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
        return $items;
    }

    $archive_url = get_post_type_archive_link( 'publication' );
    if ( ! $archive_url ) {
        return $items;
    }

    // If the menu already contains /writing/ (any variant), skip.
    if ( false !== stripos( (string) $items, $archive_url ) ) {
        return $items;
    }

    $current = is_post_type_archive( 'publication' ) || is_singular( 'publication' );
    $li = '<li class="menu-item' . ( $current ? ' current-menu-item' : '' ) . '">'
        . '<a href="' . esc_url( $archive_url ) . '">Writing</a>'
        . '</li>';

    return $items . $li;
}
add_filter( 'wp_nav_menu_items', 'lieuwe_publications_menu_fallback', 10, 2 );
