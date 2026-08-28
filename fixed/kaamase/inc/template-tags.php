<?php
/**
 * Template tags.
 *
 * Every card and display fragment used across the theme. Templates call
 * these rather than building markup inline, so a change to how a worker
 * card looks happens in one place instead of nine.
 *
 * Two rules run through this whole file.
 *
 * First, nothing here assumes the core plugin exists. Every field goes
 * through kaamase_field(), which returns a default when there is no
 * data. A card with missing information renders as a smaller card, never
 * as a broken one, and never as a fatal error.
 *
 * Second, and more important: no worker's phone number is ever printed
 * into the page. Not in a tel: link, not in a data attribute, not in a
 * comment. The moment a number appears in the HTML it can be scraped by
 * anyone with a script, and a list of every worker's phone number is
 * exactly the asset a scammer would want most. Calling goes through the
 * plugin so the platform stays between the two parties.
 *
 * @package Kaamase
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. DATA ACCESS
   ========================================================================== */

if ( ! function_exists( 'kaamase_field' ) ) {
	/**
	 * Read a Kaam Ase field from a post.
	 *
	 * Single point of access for every custom field. The core plugin
	 * hooks the filter to serve values from wherever it actually stores
	 * them, which means the templates never need to know.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key without the prefix.
	 * @param mixed  $default Value returned when the field is empty.
	 * @return mixed Field value.
	 */
	function kaamase_field( $post_id, $key, $default = '' ) {

		$post_id = absint( $post_id );
		$value   = $post_id ? get_post_meta( $post_id, '_kaamase_' . $key, true ) : '';

		if ( '' === $value || null === $value || array() === $value ) {
			$value = $default;
		}

		/**
		 * Filter a Kaam Ase field value.
		 *
		 * @since 1.0.0
		 * @param mixed  $value   Field value.
		 * @param int    $post_id Post ID.
		 * @param string $key     Field key.
		 * @param mixed  $default Default value.
		 */
		return apply_filters( 'kaamase_field', $value, $post_id, $key, $default );
	}
}

if ( ! function_exists( 'kaamase_user_type' ) ) {
	/**
	 * What kind of user is viewing the page.
	 *
	 * @since 1.0.0
	 * @return string One of worker, employer, staff or guest.
	 */
	function kaamase_user_type() {

		$type = 'guest';

		if ( is_user_logged_in() ) {
			$type = current_user_can( 'edit_posts' ) ? 'staff' : 'worker';
		}

		/**
		 * Filter the current user type.
		 *
		 * The core plugin hooks this to distinguish workers from
		 * employers, which the theme alone cannot know.
		 *
		 * @since 1.0.0
		 * @param string $type Detected user type.
		 */
		return (string) apply_filters( 'kaamase_user_type', $type );
	}
}


/* ==========================================================================
   2. DISPLAY HELPERS
   ========================================================================== */

if ( ! function_exists( 'kaamase_avatar' ) ) {
	/**
	 * Profile photo with an initials fallback.
	 *
	 * Most workers will register without a photo, at least at first.
	 * A grid of grey placeholder silhouettes looks abandoned. Initials on
	 * a tinted block looks intentional, and it distinguishes one card
	 * from another at a glance.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $size    Registered image size.
	 * @param string $class   Extra class for the element.
	 * @return string Markup.
	 */
	function kaamase_avatar( $post_id, $size = 'kaamase-avatar', $class = '' ) {

		$post_id = absint( $post_id );
		$classes = trim( 'ka-avatar ' . $class );

		if ( $post_id && has_post_thumbnail( $post_id ) ) {
			return get_the_post_thumbnail(
				$post_id,
				$size,
				array(
					'class'    => $classes,
					'loading'  => 'lazy',
					'decoding' => 'async',
					'alt'      => '',
				)
			);
		}

		$name = $post_id ? get_the_title( $post_id ) : '';

		return sprintf(
			'<span class="%1$s ka-avatar--empty" aria-hidden="true">%2$s</span>',
			esc_attr( $classes ),
			esc_html( kaamase_initials( $name ) )
		);
	}
}

if ( ! function_exists( 'kaamase_initials' ) ) {
	/**
	 * First letters of the first two words of a name.
	 *
	 * @since 1.0.0
	 * @param string $name Full name.
	 * @return string One or two characters.
	 */
	function kaamase_initials( $name ) {

		$name = trim( wp_strip_all_tags( (string) $name ) );

		if ( '' === $name ) {
			return '?';
		}

		$parts    = preg_split( '/\s+/', $name );
		$initials = '';

		foreach ( array_slice( (array) $parts, 0, 2 ) as $part ) {
			$initials .= mb_substr( $part, 0, 1 );
		}

		return mb_strtoupper( $initials );
	}
}

if ( ! function_exists( 'kaamase_status' ) ) {
	/**
	 * Availability pill.
	 *
	 * Carries a word as well as a colour. A meaningful share of users
	 * cannot distinguish the colours, and a meaningful share will not
	 * read the word, so both are present.
	 *
	 * @since 1.0.0
	 * @param string $status One of live, busy or leave.
	 * @return string Markup, or an empty string for an unknown status.
	 */
	function kaamase_status( $status ) {

		$map = array(
			'live'  => array( 'ka-status--live', __( 'Available now', 'kaamase' ) ),
			'busy'  => array( 'ka-status--busy', __( 'Working', 'kaamase' ) ),
			'leave' => array( 'ka-status--leave', __( 'On leave', 'kaamase' ) ),
		);

		$status = (string) $status;

		if ( ! isset( $map[ $status ] ) ) {
			return '';
		}

		return sprintf(
			'<span class="ka-status %1$s">%2$s</span>',
			esc_attr( $map[ $status ][0] ),
			esc_html( $map[ $status ][1] )
		);
	}
}

if ( ! function_exists( 'kaamase_rating' ) ) {
	/**
	 * Rating with review count, or a New badge.
	 *
	 * This matters more than it looks. For the first months of this
	 * platform almost nobody will have a rating. Showing five empty stars
	 * makes every worker look bad and makes the site look dead. A New
	 * badge is honest and reads as fresh rather than as rejected.
	 *
	 * @since 1.0.0
	 * @param float $score Average score out of five.
	 * @param int   $count Number of completed jobs rated.
	 * @return string Markup.
	 */
	function kaamase_rating( $score, $count ) {

		$score = (float) $score;
		$count = absint( $count );

		if ( $count < 1 || $score <= 0 ) {
			return sprintf(
				'<span class="ka-badge ka-badge--new">%s</span>',
				esc_html__( 'New', 'kaamase' )
			);
		}

		return sprintf(
			'<span class="ka-rating"><span class="ka-rating__star" aria-hidden="true">&#9733;</span>'
				. '<span>%1$s</span><span class="ka-rating__count">(%2$s)</span>'
				. '<span class="ka-sr">%3$s</span></span>',
			esc_html( number_format_i18n( $score, 1 ) ),
			esc_html( number_format_i18n( $count ) ),
			esc_html(
				sprintf(
					/* translators: 1: rating out of five, 2: number of reviews */
					_n( 'Rated %1$s out of 5 from %2$s job', 'Rated %1$s out of 5 from %2$s jobs', $count, 'kaamase' ),
					number_format_i18n( $score, 1 ),
					number_format_i18n( $count )
				)
			)
		);
	}
}

if ( ! function_exists( 'kaamase_wage' ) ) {
	/**
	 * Format a wage figure.
	 *
	 * @since 1.0.0
	 * @param int|float $amount Amount in rupees.
	 * @param string    $unit   One of day, month or job.
	 * @return string Markup, or an empty string when there is no figure.
	 */
	function kaamase_wage( $amount, $unit = 'day' ) {

		$amount = (float) $amount;

		if ( $amount <= 0 ) {
			return sprintf(
				'<span class="ka-mute ka-small">%s</span>',
				esc_html__( 'Rate on asking', 'kaamase' )
			);
		}

		$units = array(
			'day'   => __( 'per day', 'kaamase' ),
			'month' => __( 'per month', 'kaamase' ),
			'job'   => __( 'for the job', 'kaamase' ),
			'hour'  => __( 'per hour', 'kaamase' ),
		);

		$label = isset( $units[ $unit ] ) ? $units[ $unit ] : $units['day'];

		return sprintf(
			'<span class="ka-wage">&#8377;%1$s</span> <span class="ka-wage__unit">%2$s</span>',
			esc_html( number_format_i18n( $amount ) ),
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'kaamase_place' ) ) {
	/**
	 * Town and district as a single line.
	 *
	 * District comes from a fixed list in the core plugin, never from
	 * free text. Two spellings of the same district silently split every
	 * search result in half.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Markup.
	 */
	function kaamase_place( $post_id ) {

		$town     = (string) kaamase_field( $post_id, 'town' );
		$district = kaamase_district_label( (string) kaamase_field( $post_id, 'district' ) );

		$parts = array_filter( array( $town, $district ) );

		if ( empty( $parts ) ) {
			return '';
		}

		return sprintf(
			'<span class="ka-place ka-small ka-mute">%s</span>',
			esc_html( implode( ', ', $parts ) )
		);
	}
}

if ( ! function_exists( 'kaamase_district_label' ) ) {
	/**
	 * Turn a stored district slug into the name people use.
	 *
	 * Districts are stored as slugs, so a card reading the field directly
	 * prints "mokokchung" where it means "Mokokchung". Every card, every
	 * profile and every search result on the site showed the lower case
	 * slug until this existed.
	 *
	 * The core plugin holds the canonical list, so ask it first. The
	 * fallback covers a theme only install and gets the common case right,
	 * since no Nagaland district name is a compound word.
	 *
	 * @since 1.1.0
	 * @param string $slug District slug.
	 * @return string Display name, or an empty string.
	 */
	function kaamase_district_label( $slug ) {

		$slug = trim( (string) $slug );

		if ( '' === $slug ) {
			return '';
		}

		if ( function_exists( 'kaamase_district_name' ) ) {

			$name = kaamase_district_name( $slug );

			if ( $name ) {
				return $name;
			}
		}

		return ucwords( str_replace( '-', ' ', $slug ) );
	}
}

if ( ! function_exists( 'kaamase_badges' ) ) {
	/**
	 * Trust badges for a profile.
	 *
	 * Vouched sits above verified on purpose. In Nagaland a named colony
	 * or village authority standing behind somebody carries more weight
	 * than any automated check this platform could run.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Markup.
	 */
	function kaamase_badges( $post_id ) {

		$out = '';

		if ( kaamase_field( $post_id, 'vouched_by' ) ) {
			$out .= sprintf(
				'<span class="ka-badge ka-badge--vouched">%s</span>',
				esc_html__( 'Vouched', 'kaamase' )
			);
		}

		if ( kaamase_field( $post_id, 'verified' ) ) {
			$out .= sprintf(
				'<span class="ka-badge ka-badge--verified">%s</span>',
				esc_html__( 'Verified', 'kaamase' )
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_trades' ) ) {
	/**
	 * Trade chips for a profile or job.
	 *
	 * @since 1.0.0
	 * @param int  $post_id Post ID.
	 * @param int  $limit   Maximum chips shown before a plus count.
	 * @param bool $link    Whether chips link to the trade archive.
	 * @return string Markup.
	 */
	function kaamase_trades( $post_id, $limit = 3, $link = false ) {

		$terms = get_the_terms( absint( $post_id ), 'kaamase_trade' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$out       = '<span class="ka-cluster">';
		$shown     = array_slice( $terms, 0, absint( $limit ) );
		$remaining = count( $terms ) - count( $shown );

		foreach ( $shown as $term ) {

			if ( $link ) {
				$url  = get_term_link( $term );
				$href = is_wp_error( $url ) ? '' : $url;

				$out .= sprintf(
					'<a class="ka-chip" href="%1$s">%2$s</a>',
					esc_url( $href ),
					esc_html( $term->name )
				);

				continue;
			}

			$out .= sprintf( '<span class="ka-chip">%s</span>', esc_html( $term->name ) );
		}

		if ( $remaining > 0 ) {
			$out .= sprintf(
				'<span class="ka-chip ka-mute">%s</span>',
				esc_html( sprintf( '+%s', number_format_i18n( $remaining ) ) )
			);
		}

		return $out . '</span>';
	}
}

if ( ! function_exists( 'kaamase_posted_ago' ) ) {
	/**
	 * How long ago something was posted.
	 *
	 * Freshness is the main signal on a job board. A two week old job is
	 * usually filled, and a worker who applies to it and hears nothing
	 * stops trusting the platform.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Markup.
	 */
	function kaamase_posted_ago( $post_id ) {

		$time = get_post_time( 'U', true, absint( $post_id ) );

		if ( ! $time ) {
			return '';
		}

		$diff = time() - (int) $time;

		if ( $diff < HOUR_IN_SECONDS ) {
			$label = __( 'Just posted', 'kaamase' );
		} else {
			/* translators: %s: human readable time difference, for example 3 hours */
			$label = sprintf( __( '%s ago', 'kaamase' ), human_time_diff( (int) $time, time() ) );
		}

		return sprintf(
			'<span class="ka-small ka-mute">%s</span>',
			esc_html( $label )
		);
	}
}

if ( ! function_exists( 'kaamase_contact_button' ) ) {
	/**
	 * Contact button for a worker or employer.
	 *
	 * Deliberately never a tel: link containing a real number.
	 *
	 * A phone number printed into the page is a phone number that can be
	 * scraped in bulk by anyone. The whole list of registered workers,
	 * with names and villages attached, is precisely what a scammer or a
	 * predatory labour agent would pay for. It goes through the plugin,
	 * which can mask the call, log it, and cut off an abusive account.
	 *
	 * With no plugin active, this falls back to the profile page. It
	 * never falls back to exposing the number.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $label   Button label.
	 * @return string Markup.
	 */
	function kaamase_contact_button( $post_id, $label = '' ) {

		$post_id = absint( $post_id );
		$label   = $label ? $label : __( 'Call', 'kaamase' );

		/**
		 * Filter the contact URL for a profile.
		 *
		 * @since 1.0.0
		 * @param string $url     Destination. Default is the profile page.
		 * @param int    $post_id Post ID.
		 */
		$url = apply_filters( 'kaamase_contact_url', get_permalink( $post_id ), $post_id );

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<a class="ka-btn ka-btn--outline ka-btn--sm" href="%1$s">%2$s</a>',
				esc_url( wp_login_url( get_permalink( $post_id ) ) ),
				esc_html__( 'Sign in to contact', 'kaamase' )
			);
		}

		return sprintf(
			'<a class="ka-btn ka-btn--action ka-btn--sm" href="%1$s" rel="nofollow">%2$s</a>',
			esc_url( $url ),
			esc_html( $label )
		);
	}
}


/* ==========================================================================
   3. CARDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_worker_card' ) ) {
	/**
	 * Render a worker card.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID. Defaults to the current post.
	 * @return void
	 */
	function kaamase_worker_card( $post_id = 0 ) {

		$post_id = $post_id ? absint( $post_id ) : get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$experience = absint( kaamase_field( $post_id, 'years_experience' ) );
		?>
		<article class="ka-card ka-card--link ka-worker-card">

			<div class="ka-card__head">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="ka-worker-card__photo" tabindex="-1" aria-hidden="true">
					<?php echo kaamase_avatar( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>

				<div class="ka-worker-card__id">
					<h3 class="ka-worker-card__name">
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
							<?php echo esc_html( get_the_title( $post_id ) ); ?>
						</a>
					</h3>

					<?php echo kaamase_place( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<div class="ka-cluster ka-mt-4">
						<?php
						echo kaamase_rating( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							kaamase_field( $post_id, 'rating_average', 0 ),
							kaamase_field( $post_id, 'rating_count', 0 )
						);
						echo kaamase_badges( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>

				<?php echo kaamase_status( kaamase_field( $post_id, 'availability' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-card__body">
				<?php echo kaamase_trades( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php if ( $experience ) : ?>
					<p class="ka-small ka-soft ka-mt-4">
						<?php
						printf(
							esc_html(
								/* translators: %s: number of years */
								_n( '%s year of experience', '%s years of experience', $experience, 'kaamase' )
							),
							esc_html( number_format_i18n( $experience ) )
						);
						?>
					</p>
				<?php endif; ?>
			</div>

			<div class="ka-card__foot ka-cluster ka-cluster--between">
				<?php
				echo kaamase_wage( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					kaamase_field( $post_id, 'day_rate', 0 ),
					'day'
				);
				echo kaamase_contact_button( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>

		</article>
		<?php
	}
}

if ( ! function_exists( 'kaamase_employer_card' ) ) {
	/**
	 * Render an employer card.
	 *
	 * What a worker actually wants to know about whoever posted a job:
	 * where they are, whether anybody has worked for them before, and
	 * what those people said afterwards. All three have been stored on
	 * every employer profile since the beginning and none of them were
	 * ever shown.
	 *
	 * No phone number, deliberately. Contact runs through the same
	 * per-profile reveal as everywhere else, counted against the daily
	 * cap. A directory that printed numbers would be a way around that
	 * cap rather than a feature of the directory.
	 *
	 * @since 1.4.1
	 * @param int $post_id Post ID. Defaults to the current post.
	 * @return void
	 */
	function kaamase_employer_card( $post_id = 0 ) {

		$post_id = $post_id ? absint( $post_id ) : get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$hires = absint( kaamase_field( $post_id, 'hires_made', 0 ) );
		$kind  = (string) kaamase_field( $post_id, 'employer_type', 'individual' );

		$kinds = array(
			'individual' => __( 'Individual', 'kaamase' ),
			'contractor' => __( 'Contractor', 'kaamase' ),
			'company'    => __( 'Company', 'kaamase' ),
		);
		?>
		<article class="ka-card ka-card--link ka-employer-card">

			<div class="ka-card__head">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" class="ka-employer-card__logo" tabindex="-1" aria-hidden="true">
					<?php echo kaamase_avatar( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>

				<div class="ka-employer-card__id">
					<h3 class="ka-employer-card__name">
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
							<?php echo esc_html( get_the_title( $post_id ) ); ?>
						</a>
					</h3>

					<?php echo kaamase_place( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<div class="ka-cluster ka-mt-4">
						<?php
						echo kaamase_rating( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							kaamase_field( $post_id, 'rating_average', 0 ),
							kaamase_field( $post_id, 'rating_count', 0 )
						);
						echo kaamase_badges( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
			</div>

			<div class="ka-card__body">
				<?php if ( isset( $kinds[ $kind ] ) ) : ?>
					<div class="ka-cluster">
						<span class="ka-chip"><?php echo esc_html( $kinds[ $kind ] ); ?></span>
					</div>
				<?php endif; ?>

				<p class="ka-small ka-soft ka-mt-4">
					<?php
					if ( $hires ) {
						printf(
							esc_html(
								/* translators: %s: number of workers hired */
								_n( '%s worker hired through Kaam Ase', '%s workers hired through Kaam Ase', $hires, 'kaamase' )
							),
							esc_html( number_format_i18n( $hires ) )
						);
					} else {
						/*
						 * Said plainly rather than left blank. A new
						 * employer with no history is not a warning, and
						 * an empty space where every other card has a
						 * number reads like one.
						 */
						esc_html_e( 'New here, nobody hired yet', 'kaamase' );
					}
					?>
				</p>
			</div>

			<div class="ka-card__foot ka-cluster ka-cluster--between">
				<a class="ka-btn ka-btn--outline ka-btn--sm" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
					<?php esc_html_e( 'See this employer', 'kaamase' ); ?>
				</a>
			</div>

		</article>
		<?php
	}
}

if ( ! function_exists( 'kaamase_gang_card' ) ) {
	/**
	 * Render a gang card.
	 *
	 * A gang is a team with a leader and a headcount. This is the object
	 * a contractor actually hires. Nobody hires one mason, they hire a
	 * mason who brings four helpers, and every hiring platform that only
	 * models individuals forces contractors back onto the phone.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID. Defaults to the current post.
	 * @return void
	 */
	function kaamase_gang_card( $post_id = 0 ) {

		$post_id = $post_id ? absint( $post_id ) : get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$size   = absint( kaamase_field( $post_id, 'headcount' ) );
		$leader = (string) kaamase_field( $post_id, 'leader_name' );
		?>
		<article class="ka-card ka-card--link ka-gang-card">

			<div class="ka-card__head">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo kaamase_avatar( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>

				<div class="ka-gang-card__id">
					<h3>
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
							<?php echo esc_html( get_the_title( $post_id ) ); ?>
						</a>
					</h3>

					<?php if ( $size ) : ?>
						<p class="ka-small ka-soft">
							<?php
							printf(
								esc_html(
									/* translators: %s: number of workers in the team */
									_n( 'Team of %s worker', 'Team of %s workers', $size, 'kaamase' )
								),
								esc_html( number_format_i18n( $size ) )
							);
							?>
						</p>
					<?php endif; ?>

					<?php echo kaamase_place( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php echo kaamase_status( kaamase_field( $post_id, 'availability' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-card__body">
				<?php echo kaamase_trades( $post_id, 4 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php if ( $leader ) : ?>
					<p class="ka-small ka-mute ka-mt-4">
						<?php
						printf(
							/* translators: %s: name of the team leader */
							esc_html__( 'Led by %s', 'kaamase' ),
							esc_html( $leader )
						);
						?>
					</p>
				<?php endif; ?>
			</div>

			<div class="ka-card__foot ka-cluster ka-cluster--between">
				<?php
				echo kaamase_wage( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					kaamase_field( $post_id, 'day_rate', 0 ),
					'day'
				);
				echo kaamase_contact_button( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>

		</article>
		<?php
	}
}

if ( ! function_exists( 'kaamase_job_card' ) ) {
	/**
	 * Render a job card.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID. Defaults to the current post.
	 * @return void
	 */
	function kaamase_job_card( $post_id = 0 ) {

		$post_id = $post_id ? absint( $post_id ) : get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$needed  = absint( kaamase_field( $post_id, 'workers_needed' ) );
		$urgent  = (bool) kaamase_field( $post_id, 'urgent' );
		$food    = (bool) kaamase_field( $post_id, 'food_provided' );
		$stay    = (bool) kaamase_field( $post_id, 'stay_provided' );
		$company = (string) kaamase_field( $post_id, 'employer_name' );
		?>
		<article class="ka-card ka-card--link ka-job-card">

			<div class="ka-cluster ka-cluster--between">
				<?php echo kaamase_trades( $post_id, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $urgent ) : ?>
					<span class="ka-badge ka-badge--new"><?php esc_html_e( 'Urgent', 'kaamase' ); ?></span>
				<?php endif; ?>
			</div>

			<h3 class="ka-mt-4">
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
					<?php echo esc_html( get_the_title( $post_id ) ); ?>
				</a>
			</h3>

			<?php if ( $company ) : ?>
				<p class="ka-small ka-soft">
					<?php
					echo esc_html( $company );

					/*
					 * The mark, when the person hiring is on Kaam Ase.
					 *
					 * Never on a job that staff put up for an outside
					 * employer. Those carry somebody else's name and
					 * have no account behind them, so the mark would be
					 * the poster's rather than the hirer's, which is
					 * the one way this badge could tell a lie.
					 */
					if ( function_exists( 'kaamase_called_badge' )
						&& ! ( function_exists( 'kaamase_job_is_posted_for' ) && kaamase_job_is_posted_for( $post_id ) ) ) {

						echo kaamase_called_badge( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							(int) get_post_field( 'post_author', $post_id ),
							true
						);
					}
					?>
				</p>
			<?php endif; ?>

			<div class="ka-cluster ka-mt-4">
				<?php
				echo kaamase_place( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo kaamase_posted_ago( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>

			<?php if ( $needed || $food || $stay ) : ?>
				<div class="ka-cluster ka-mt-4">
					<?php if ( $needed ) : ?>
						<span class="ka-badge ka-badge--neutral">
							<?php
							printf(
								esc_html(
									/* translators: %s: number of workers wanted */
									_n( '%s worker wanted', '%s workers wanted', $needed, 'kaamase' )
								),
								esc_html( number_format_i18n( $needed ) )
							);
							?>
						</span>
					<?php endif; ?>

					<?php if ( $food ) : ?>
						<span class="ka-badge ka-badge--neutral"><?php esc_html_e( 'Food provided', 'kaamase' ); ?></span>
					<?php endif; ?>

					<?php if ( $stay ) : ?>
						<span class="ka-badge ka-badge--neutral"><?php esc_html_e( 'Stay provided', 'kaamase' ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="ka-card__foot ka-cluster ka-cluster--between">
				<?php
				echo kaamase_wage( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					kaamase_field( $post_id, 'pay_amount', 0 ),
					(string) kaamase_field( $post_id, 'pay_unit', 'day' )
				);
				?>
				<a class="ka-btn ka-btn--primary ka-btn--sm" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
					<?php esc_html_e( 'View job', 'kaamase' ); ?>
				</a>
			</div>

		</article>
		<?php
	}
}


/* ==========================================================================
   4. EMPTY STATES

   These get used constantly in the first months, when most screens have
   nothing on them. An empty screen that explains itself and offers a next
   step is the difference between a user who comes back and a user who
   decides the site is broken.
   ========================================================================== */

if ( ! function_exists( 'kaamase_empty' ) ) {
	/**
	 * Render an empty state.
	 *
	 * @since 1.0.0
	 * @param array $args title, text, button_label, button_url.
	 * @return void
	 */
	function kaamase_empty( $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'title'        => __( 'Nothing here yet', 'kaamase' ),
				'text'         => __( 'Try a different district or trade.', 'kaamase' ),
				'button_label' => '',
				'button_url'   => '',
			)
		);
		?>
		<div class="ka-empty">
			<h2 class="ka-empty__title"><?php echo esc_html( $args['title'] ); ?></h2>
			<p class="ka-empty__text"><?php echo esc_html( $args['text'] ); ?></p>

			<?php if ( $args['button_label'] && $args['button_url'] ) : ?>
				<a class="ka-btn ka-btn--primary" href="<?php echo esc_url( $args['button_url'] ); ?>">
					<?php echo esc_html( $args['button_label'] ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}
}


/* ==========================================================================
   5. PAGINATION
   ========================================================================== */

if ( ! function_exists( 'kaamase_pagination' ) ) {
	/**
	 * Render pagination.
	 *
	 * Numbered links rather than infinite scroll. Infinite scroll on a
	 * slow connection loads content the user did not ask for and burns
	 * their data pack, and it makes it impossible to get back to a
	 * worker they saw two screens ago.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pagination() {

		$links = paginate_links(
			array(
				'mid_size'  => 1,
				'end_size'  => 1,
				'prev_text' => esc_html__( 'Back', 'kaamase' ),
				'next_text' => esc_html__( 'Next', 'kaamase' ),
				'type'      => 'array',
			)
		);

		if ( empty( $links ) ) {
			return;
		}
		?>
		<nav class="ka-pagination ka-mt-6" aria-label="<?php esc_attr_e( 'Page navigation', 'kaamase' ); ?>">
			<ul class="ka-cluster">
				<?php foreach ( $links as $link ) : ?>
					<li><?php echo wp_kses_post( $link ); ?></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}
}


/* ==========================================================================
   6. BOTTOM TAB BAR

   Built in code from the user type rather than from a menu, because it
   changes completely depending on who is looking. Five slots maximum.
   A sixth item makes every target too small for a thumb.
   ========================================================================== */

if ( ! function_exists( 'kaamase_tabbar' ) ) {
	/**
	 * Render the mobile tab bar.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_tabbar() {

		if ( ! kaamase_is_app_screen() ) {
			return;
		}

		$type = kaamase_user_type();

		$tabs = array(
			array(
				'url'   => home_url( '/jobs/' ),
				'label' => __( 'Jobs', 'kaamase' ),
				'icon'  => 'briefcase',
			),
			array(
				'url'   => home_url( '/workers/' ),
				'label' => __( 'Workers', 'kaamase' ),
				'icon'  => 'users',
			),
			array(
				'url'   => home_url( '/saved/' ),
				'label' => __( 'Saved', 'kaamase' ),
				'icon'  => 'heart',
			),
			array(
				'url'   => home_url( '/dashboard/' ),
				'label' => __( 'Me', 'kaamase' ),
				'icon'  => 'user',
			),
		);

		/**
		 * Filter the tab bar items.
		 *
		 * The core plugin replaces this per user type, so an employer
		 * sees Post a Job where a worker sees Saved.
		 *
		 * @since 1.0.0
		 * @param array  $tabs Tab definitions.
		 * @param string $type Current user type.
		 */
		$tabs = (array) apply_filters( 'kaamase_tabbar_items', $tabs, $type );
		$tabs = array_slice( $tabs, 0, 5 );

		if ( empty( $tabs ) ) {
			return;
		}

		$current = home_url( add_query_arg( array() ) );
		?>
		<nav class="ka-tabbar" aria-label="<?php esc_attr_e( 'Main', 'kaamase' ); ?>">
			<?php
			foreach ( $tabs as $tab ) :

				$is_current = untrailingslashit( $tab['url'] ) === untrailingslashit( $current );
				?>
				<a class="ka-tabbar__item"
					href="<?php echo esc_url( $tab['url'] ); ?>"
					<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
					<?php
					if ( ! empty( $tab['icon'] ) && function_exists( 'kaamase_svg' ) ) {
						echo kaamase_svg( $tab['icon'], array( 'size' => 24, 'class' => 'ka-tabbar__icon' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
					<span><?php echo esc_html( $tab['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
	}
}