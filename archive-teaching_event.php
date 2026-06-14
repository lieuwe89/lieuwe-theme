<?php
/**
 * Teaching archive — /teaching/ : intro, signup band, schedule.
 *
 * @package Lieuwe_Theme
 */

get_header();

$signup_state = isset( $_GET['te_signup'] ) ? sanitize_key( wp_unslash( $_GET['te_signup'] ) ) : '';
$hero_img     = lieuwe_teaching_page_hero_image_url();
$intro_p1     = lieuwe_teaching_page_intro_p1();
$intro_p2     = lieuwe_teaching_page_intro_p2();
$privacy_note = lieuwe_teaching_page_privacy_note();
?>

<main class="te">

    <section class="te-intro">
        <div class="te-container te-intro__grid">
            <div class="te-intro__copy">
                <p class="te-eyebrow"><?php echo esc_html( lieuwe_teaching_page_eyebrow() ); ?></p>
                <h1 class="te-intro__title"><?php echo esc_html( lieuwe_teaching_page_title() ); ?></h1>
                <?php if ( $intro_p1 ) : ?><p class="te-intro__p"><?php echo esc_html( $intro_p1 ); ?></p><?php endif; ?>
                <?php if ( $intro_p2 ) : ?><p class="te-intro__p"><?php echo esc_html( $intro_p2 ); ?></p><?php endif; ?>
            </div>
            <figure class="te-intro__figure">
                <?php if ( $hero_img ) : ?>
                    <img class="te-intro__img" src="<?php echo esc_url( $hero_img ); ?>" alt="">
                <?php else : ?>
                    <div class="te-intro__img te-intro__img--empty" aria-hidden="true"></div>
                <?php endif; ?>
                <?php if ( $cap = lieuwe_teaching_page_hero_caption() ) : ?>
                    <figcaption class="te-intro__caption"><?php echo esc_html( $cap ); ?></figcaption>
                <?php endif; ?>
            </figure>
        </div>
    </section>

    <section class="te-band" id="te-signup">
        <div class="te-container te-band__grid">
            <div class="te-band__lead">
                <h2 class="te-band__heading"><?php echo esc_html( lieuwe_teaching_page_signup_heading() ); ?></h2>
                <?php if ( $intro = lieuwe_teaching_page_signup_intro() ) : ?>
                    <p class="te-band__intro"><?php echo esc_html( $intro ); ?></p>
                <?php endif; ?>
            </div>
            <div class="te-band__form-wrap">
                <?php if ( 'ok' === $signup_state ) : ?>
                    <div class="te-confirm-inline" role="status">
                        <p>Right, you're on the list. I'll be in touch when new dates go up.</p>
                    </div>
                <?php else : ?>
                    <?php if ( 'err' === $signup_state ) : ?>
                        <p class="te-form-error" role="alert">Hmm, that didn't go through — please try again.</p>
                    <?php endif; ?>
                    <form class="te-signup" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
                        <input type="hidden" name="action" value="lieuwe_teaching_signup">
                        <?php wp_nonce_field( 'lieuwe_teaching_signup', '_te_nonce' ); ?>
                        <input type="text" name="te_hp" class="te-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <div class="te-signup__row">
                            <label class="te-field te-field--grow">
                                <span class="visually-hidden">Email address</span>
                                <input type="email" name="te_email" required placeholder="you@example.com">
                            </label>
                            <button type="submit" class="te-btn te-btn--primary">Keep me posted</button>
                        </div>
                        <fieldset class="te-signup__interests">
                            <legend class="visually-hidden">What are you interested in?</legend>
                            <?php foreach ( lieuwe_teaching_interest_labels() as $key => $label ) : ?>
                                <label class="te-check">
                                    <input type="checkbox" name="te_interests[]" value="<?php echo esc_attr( $key ); ?>">
                                    <span><?php echo esc_html( ucfirst( $label ) ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                        <?php $ts_key = function_exists( 'lieuwe_teaching_turnstile_site_key' ) ? lieuwe_teaching_turnstile_site_key() : ''; ?>
                        <?php if ( $ts_key ) : ?>
                            <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $ts_key ); ?>" data-action="signup"></div>
                        <?php endif; ?>
                        <?php if ( $privacy_note ) : ?>
                            <p class="te-form-note"><?php echo esc_html( $privacy_note ); ?></p>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="te-schedule">
        <div class="te-container">
            <div class="te-schedule__head">
                <h2 class="te-schedule__title">Upcoming classes</h2>
                <p class="te-legend">
                    <span class="te-dot te-dot--home" aria-hidden="true"></span> Home workshop
                    <span class="te-dot te-dot--festival" aria-hidden="true"></span> Festival
                </p>
            </div>

            <?php
            $events = lieuwe_teaching_get_upcoming_events();
            if ( ! $events->have_posts() ) :
                ?>
                <div class="te-empty">
                    <div class="te-empty__glyph" aria-hidden="true">🪚</div>
                    <h3 class="te-empty__title">Nothing on the calendar right now</h3>
                    <p class="te-empty__text">New classes go up through the year. Leave your email above and I'll let you know as soon as the next dates are set.</p>
                    <a class="te-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Or get in touch →</a>
                </div>
                <?php
            else :
                $current_month = '';
                $first         = true;
                while ( $events->have_posts() ) :
                    $events->the_post();
                    $id          = get_the_ID();
                    $type        = (string) get_post_meta( $id, '_te_type', true );
                    $is_festival = 'festival' === $type;
                    $start       = (string) get_post_meta( $id, '_te_start_date', true );
                    $month       = $start ? date_i18n( 'F Y', strtotime( $start ) ) : '';
                    $date_text   = (string) get_post_meta( $id, '_te_date_text', true );
                    $time_text   = (string) get_post_meta( $id, '_te_time_text', true );
                    $where       = (string) get_post_meta( $id, '_te_where', true );
                    $blurb       = (string) get_post_meta( $id, '_te_blurb', true );
                    $open        = (int) get_post_meta( $id, '_te_spots_open', true );
                    $ticket      = (string) get_post_meta( $id, '_te_ticket_url', true );
                    $thumb       = get_the_post_thumbnail_url( $id, 'medium' );
                    $sold_out    = ! $is_festival && $open <= 0;

                    if ( $month !== $current_month ) :
                        if ( ! $first ) {
                            echo '</div></div>'; // close prev .te-month__events + .te-month
                        }
                        $current_month = $month;
                        $first         = false;
                        echo '<div class="te-month"><div class="te-month__label">' . esc_html( $month ) . '</div><div class="te-month__events">';
                    endif;
                    ?>
                    <article class="te-event te-event--<?php echo $is_festival ? 'festival' : 'home'; ?>">
                        <div class="te-event__thumb">
                            <?php if ( $thumb ) : ?>
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="">
                            <?php else : ?>
                                <div class="te-event__thumb--empty" aria-hidden="true"></div>
                            <?php endif; ?>
                        </div>
                        <div class="te-event__body">
                            <div class="te-event__titlerow">
                                <h3 class="te-event__title"><?php the_title(); ?></h3>
                                <?php if ( $sold_out ) : ?>
                                    <span class="te-tag te-tag--full">Fully booked</span>
                                <?php elseif ( $is_festival ) : ?>
                                    <span class="te-tag te-tag--festival">Festival</span>
                                <?php else : ?>
                                    <span class="te-tag te-tag--home">Home workshop</span>
                                <?php endif; ?>
                            </div>
                            <p class="te-event__meta">
                                <?php echo esc_html( implode( ' · ', array_filter( [ $date_text, $time_text, $where ] ) ) ); ?>
                            </p>
                            <?php if ( $blurb ) : ?>
                                <p class="te-event__blurb"><?php echo esc_html( $blurb ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="te-event__cta">
                            <?php if ( $is_festival ) : ?>
                                <a class="te-btn te-btn--festival" href="<?php echo esc_url( $ticket ?: home_url( '/contact/' ) ); ?>" target="_blank" rel="noopener">Festival tickets ↗</a>
                            <?php elseif ( $sold_out ) : ?>
                                <a class="te-btn te-btn--ghost" href="#te-signup">Join the list</a>
                            <?php else : ?>
                                <a class="te-btn te-btn--primary" href="<?php echo esc_url( get_permalink( $id ) ); ?>">Book a spot</a>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php
                endwhile;
                if ( ! $first ) {
                    echo '</div></div>'; // close final month
                }
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </section>

</main>

<?php
get_footer();
