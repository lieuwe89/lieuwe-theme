<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Single class — the Book a Spot page (home workshops). Festivals redirect out.
 *
 * @package Lieuwe_Theme
 */

while ( have_posts() ) :
    the_post();
    $id     = get_the_ID();
    $type   = (string) get_post_meta( $id, '_te_type', true );
    $ticket = (string) get_post_meta( $id, '_te_ticket_url', true );

    // Festivals don't have a booking page — send visitors to the ticket link.
    if ( 'festival' === $type && $ticket ) {
        wp_safe_redirect( $ticket, 302 );
        exit;
    }

    get_header();

    $date_text    = (string) get_post_meta( $id, '_te_date_text', true );
    $time_text    = (string) get_post_meta( $id, '_te_time_text', true );
    $where        = (string) get_post_meta( $id, '_te_where', true );
    $includes     = (string) get_post_meta( $id, '_te_includes', true );
    $price        = (string) get_post_meta( $id, '_te_price', true );
    $blurb        = (string) get_post_meta( $id, '_te_blurb', true );
    $total        = (int) get_post_meta( $id, '_te_spots_total', true );
    $open         = (int) get_post_meta( $id, '_te_spots_open', true );
    $thumb        = get_the_post_thumbnail_url( $id, 'large' );
    $privacy_note = lieuwe_teaching_page_privacy_note();
    $booked_state = isset( $_GET['booked'] ) ? sanitize_key( wp_unslash( $_GET['booked'] ) ) : '';
    $sold_out     = $open <= 0;
    $archive_url  = get_post_type_archive_link( 'teaching_event' );
    ?>
    <main class="te te-book">
        <div class="te-container">
            <a class="te-book__back" href="<?php echo esc_url( $archive_url ); ?>">← Back to all classes</a>
            <header class="te-book__head">
                <p class="te-eyebrow">Book a spot</p>
                <h1 class="te-book__title"><?php the_title(); ?></h1>
            </header>

            <div class="te-book__grid">
                <aside class="te-summary">
                    <div class="te-summary__card">
                        <div class="te-summary__thumb">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="">
                            <?php else : ?>
                                <div class="te-summary__thumb--empty" aria-hidden="true"></div>
                            <?php endif; ?>
                        </div>
                        <span class="te-tag te-tag--home">Home workshop</span>
                        <dl class="te-summary__dl">
                            <?php if ( $date_text ) : ?><div><dt>Date</dt><dd><?php echo esc_html( $date_text ); ?></dd></div><?php endif; ?>
                            <?php if ( $time_text ) : ?><div><dt>Time</dt><dd><?php echo esc_html( $time_text ); ?></dd></div><?php endif; ?>
                            <?php if ( $where ) : ?><div><dt>Where</dt><dd><?php echo esc_html( $where ); ?></dd></div><?php endif; ?>
                            <?php if ( $includes ) : ?><div><dt>Includes</dt><dd><?php echo esc_html( $includes ); ?></dd></div><?php endif; ?>
                            <?php if ( $price ) : ?><div><dt>Price</dt><dd><?php echo esc_html( $price ); ?></dd></div><?php endif; ?>
                        </dl>
                        <?php if ( $total > 0 ) : ?>
                            <div class="te-spots">
                                <div class="te-spots__dots" aria-hidden="true">
                                    <?php for ( $i = 0; $i < $total; $i++ ) : ?>
                                        <span class="te-dot-spot <?php echo $i < $open ? 'is-open' : 'is-taken'; ?>"></span>
                                    <?php endfor; ?>
                                </div>
                                <p class="te-spots__label"><strong><?php echo esc_html( (string) $open ); ?> of <?php echo esc_html( (string) $total ); ?> spots</strong> still open</p>
                            </div>
                        <?php endif; ?>
                        <?php if ( $blurb ) : ?><p class="te-summary__blurb"><?php echo esc_html( $blurb ); ?></p><?php endif; ?>
                    </div>
                </aside>

                <div class="te-book__main">
                    <?php if ( '1' === $booked_state ) : ?>
                        <div class="te-confirm" role="status">
                            <div class="te-confirm__check" aria-hidden="true">✓</div>
                            <h2 class="te-confirm__title">Spot requested.</h2>
                            <p>Thanks — I've noted your request for <strong><?php the_title(); ?></strong>. I hold spots by hand and will be in touch by email to confirm.</p>
                            <div class="te-confirm__actions">
                                <a class="te-btn te-btn--primary" href="<?php echo esc_url( $archive_url ); ?>">Back to all classes</a>
                                <a class="te-btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get in touch</a>
                            </div>
                        </div>
                    <?php elseif ( $sold_out ) : ?>
                        <div class="te-book__full">
                            <h2 class="te-book__formtitle">This class is currently full.</h2>
                            <p>All spots are taken right now. Leave your email and I'll let you know about new dates.</p>
                            <a class="te-btn te-btn--primary" href="<?php echo esc_url( $archive_url ); ?>#te-signup">Join the list</a>
                        </div>
                    <?php else : ?>
                        <?php if ( 'err' === $booked_state ) : ?>
                            <p class="te-form-error" role="alert">Hmm, that didn't go through — please try again.</p>
                        <?php endif; ?>
                        <h2 class="te-book__formtitle">Request your spot</h2>
                        <p class="te-book__formintro">Spots are held by hand, so this sends a request rather than an instant booking. I'll confirm by email.</p>
                        <form class="te-booking" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
                            <input type="hidden" name="action" value="lieuwe_teaching_booking">
                            <input type="hidden" name="te_event_id" value="<?php echo esc_attr( (string) $id ); ?>">
                            <?php wp_nonce_field( 'lieuwe_teaching_booking', '_te_nonce' ); ?>
                            <input type="text" name="te_hp" class="te-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                            <div class="te-book__row">
                                <label class="te-field">Your name <input type="text" name="te_name" required></label>
                                <label class="te-field">Email <input type="email" name="te_email" required></label>
                            </div>
                            <div class="te-book__row">
                                <label class="te-field">Phone (optional) <input type="tel" name="te_phone"></label>
                                <label class="te-field">How many spots
                                    <select name="te_spots">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                    </select>
                                </label>
                            </div>
                            <label class="te-field">Dietary needs for lunch (optional) <input type="text" name="te_diet"></label>
                            <label class="te-field">Anything else (optional) <textarea name="te_note" rows="3"></textarea></label>
                            <?php $ts_key = function_exists( 'lieuwe_teaching_turnstile_site_key' ) ? lieuwe_teaching_turnstile_site_key() : ''; ?>
                            <?php if ( $ts_key ) : ?>
                                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $ts_key ); ?>" data-action="booking"></div>
                            <?php endif; ?>
                            <div class="te-book__submit">
                                <button type="submit" class="te-btn te-btn--primary">Request your spot</button>
                                <span class="te-book__hint">No payment today · you can change your mind anytime</span>
                            </div>
                            <?php if ( $privacy_note ) : ?>
                                <p class="te-form-note"><?php echo esc_html( $privacy_note ); ?></p>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php
    get_footer();
endwhile;
