<?php
/**
 * Who looked at you.
 *
 * The other half of view counting. The number on a profile says how
 * much interest there is; this says where it came from.
 *
 * Why a week for everybody
 * ------------------------
 * A worker who has just been looked at by four employers has something
 * to act on this week. Holding that back entirely would make the
 * counter on their profile a tease, and a number nobody can act on is
 * worse than no number.
 *
 * Why a year is worth paying for
 * ------------------------------
 * Hiring here is seasonal. An employer who looked in March is the one
 * to ring in March next year, and that is the thing a week cannot tell
 * anybody. The paid window is not a bigger version of the free one; it
 * answers a different question.
 *
 * Only people who were signed in
 * ------------------------------
 * kaamase_views_of_mine already refuses to return strangers. A line
 * reading "somebody looked at you" tells nobody anything they can act
 * on, and counting strangers into a list of names would be a way of
 * pretending to know more than this site does.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;


/** How far back a free account sees. */
if ( ! defined( 'KAAMASE_WHO_LOOKED_FREE_DAYS' ) ) {
	define( 'KAAMASE_WHO_LOOKED_FREE_DAYS', 7 );
}

/** How far back a paid account sees. */
if ( ! defined( 'KAAMASE_WHO_LOOKED_PAID_DAYS' ) ) {
	define( 'KAAMASE_WHO_LOOKED_PAID_DAYS', 365 );
}


/* ==========================================================================
   1. HOW FAR BACK
   ========================================================================== */

if ( ! function_exists( 'kaamase_who_looked_window' ) ) {
	/**
	 * How many days back this account may look.
	 *
	 * Never longer than the rows are kept. Offering a year to somebody
	 * whose oldest row is ninety days old would be selling a window on
	 * an empty wall.
	 *
	 * @since 1.5.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return int Days.
	 */
	function kaamase_who_looked_window( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		$paid = function_exists( 'kaamase_pay_is_active' ) && kaamase_pay_is_active( $user_id );
		$days = $paid ? KAAMASE_WHO_LOOKED_PAID_DAYS : KAAMASE_WHO_LOOKED_FREE_DAYS;

		if ( defined( 'KAAMASE_VIEWS_KEEP_DAYS' ) ) {
			$days = min( $days, (int) KAAMASE_VIEWS_KEEP_DAYS );
		}

		/**
		 * Filter how far back an account may see who looked at it.
		 *
		 * @since 1.5.0
		 * @param int  $days    Days.
		 * @param int  $user_id Whose list.
		 * @param bool $paid    Whether their plan is running.
		 */
		return (int) apply_filters( 'kaamase_who_looked_window', $days, $user_id, $paid );
	}
}


/* ==========================================================================
   2. THE PEOPLE

   Fetched in two queries for the whole list rather than two per row. A
   busy week is two hundred rows, and four hundred queries to print
   names is not something to put on this hosting.
   ========================================================================== */

if ( ! function_exists( 'kaamase_who_looked_people' ) ) {
	/**
	 * Names and profile addresses for a set of viewers.
	 *
	 * Name and public profile only. Never a telephone number and never
	 * an email: contact runs through the same per profile reveal as
	 * everywhere else, and a list that printed numbers would be a way
	 * around the daily cap rather than a feature of the list.
	 *
	 * @since 1.5.0
	 * @param int[] $viewer_ids Viewer user IDs.
	 * @return array[] Keyed by user ID, each with name and url.
	 */
	function kaamase_who_looked_people( $viewer_ids ) {

		$viewer_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $viewer_ids ) ) ) );

		if ( empty( $viewer_ids ) ) {
			return array();
		}

		$people = array();

		foreach ( get_users( array( 'include' => $viewer_ids, 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$people[ (int) $user->ID ] = array(
				'name' => (string) $user->display_name,
				'url'  => '',
			);
		}

		/*
		 * One query for every viewer's public profile, whichever kind it
		 * is. A worker who looked and an employer who looked are both
		 * worth linking to, and neither is worth a query of its own.
		 */
		$profiles = get_posts(
			array(
				'post_type'      => array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' ),
				'author__in'     => $viewer_ids,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'no_found_rows'  => true,
			)
		);

		foreach ( $profiles as $profile ) {

			$author = (int) $profile->post_author;

			if ( isset( $people[ $author ] ) && '' === $people[ $author ]['url'] ) {
				$people[ $author ]['url'] = (string) get_permalink( $profile->ID );
			}
		}

		return $people;
	}
}

if ( ! function_exists( 'kaamase_who_looked_what' ) ) {
	/**
	 * What of yours they were looking at.
	 *
	 * @since 1.5.0
	 * @param string $type    worker, gang, employer or job.
	 * @param string $title   The post title, for a job.
	 * @return string
	 */
	function kaamase_who_looked_what( $type, $title ) {

		switch ( $type ) {

			case 'job':
				/* translators: %s: the job title */
				return sprintf( __( 'your job, %s', 'kaamase-core' ), $title );

			case 'gang':
				return __( 'your team', 'kaamase-core' );

			case 'employer':
				return __( 'your employer profile', 'kaamase-core' );

			default:
				return __( 'your profile', 'kaamase-core' );
		}
	}
}


/* ==========================================================================
   3. THE PAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_who_looked_notice' ) ) {
	/**
	 * A card with one thing to do next.
	 *
	 * @since 1.5.0
	 * @param string $title  Heading.
	 * @param string $body   Explanation.
	 * @param string $url    Where the button goes.
	 * @param string $button Its label.
	 * @return string
	 */
	function kaamase_who_looked_notice( $title, $body, $url, $button ) {

		ob_start();
		?>
		<div class="ka-card ka-card--pad-lg ka-center">
			<h2><?php echo esc_html( $title ); ?></h2>
			<p class="ka-soft ka-mt-4"><?php echo esc_html( $body ); ?></p>
			<a class="ka-btn ka-btn--primary ka-mt-6" href="<?php echo esc_url( $url ); ?>">
				<?php echo esc_html( $button ); ?>
			</a>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_who_looked_shortcode' ) ) {
	/**
	 * The list itself.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	function kaamase_who_looked_shortcode() {

		if ( ! is_user_logged_in() ) {
			return kaamase_who_looked_notice(
				__( 'Sign in to see who looked at you', 'kaamase-core' ),
				__( 'Kaam Ase keeps a note of who opened your profile and your jobs. Sign in and it is yours.', 'kaamase-core' ),
				wp_login_url( kaamase_page_url( 'who-looked' ) ),
				__( 'Sign in', 'kaamase-core' )
			);
		}

		if ( ! function_exists( 'kaamase_views_of_mine' ) ) {
			return '';
		}

		$user_id = get_current_user_id();
		$days    = kaamase_who_looked_window( $user_id );
		$paid    = function_exists( 'kaamase_pay_is_active' ) && kaamase_pay_is_active( $user_id );
		$rows    = kaamase_views_of_mine( $user_id, $days, 200 );

		$viewers = array();
		$subject = array();

		foreach ( $rows as $row ) {
			$viewers[] = (int) $row['viewer_id'];
			$subject[] = (int) $row['subject_id'];
		}

		$people = kaamase_who_looked_people( $viewers );

		// Primes every title in one query instead of one per line.
		if ( ! empty( $subject ) ) {
			_prime_post_caches( array_unique( $subject ), false, false );
		}

		$heads = count( array_unique( $viewers ) );

		/*
		 * "In the last year" rather than "in the last 365 days".
		 * Nobody says the second one out loud.
		 */
		$window = $days >= 365
			? __( 'year', 'kaamase-core' )
			: sprintf(
				/* translators: %s: number of days */
				_n( '%s day', '%s days', $days, 'kaamase-core' ),
				number_format_i18n( $days )
			);

		/*
		 * An empty week is the one moment the longer window is worth
		 * mentioning, and only if there is actually something in it. So
		 * the question is asked, once, rather than assumed either way:
		 * offering a year of nothing is selling a window on an empty
		 * wall, and staying quiet when four employers looked last month
		 * hides the very thing they would want.
		 */
		$older = 0;

		if ( empty( $rows ) && ! $paid ) {

			$deep = kaamase_views_of_mine( $user_id, KAAMASE_WHO_LOOKED_PAID_DAYS, 200 );
			$seen = array();

			foreach ( $deep as $row ) {
				$seen[] = (int) $row['viewer_id'];
			}

			$older = count( array_unique( $seen ) );
		}

		ob_start();
		?>
		<div class="ka-stack">

			<?php if ( empty( $rows ) ) : ?>

				<div class="ka-card ka-card--pad-lg ka-center">
					<h2>
						<?php
						/*
						 * "Nobody yet" means never. When somebody did
						 * look, just further back than a free account
						 * sees, that heading is simply untrue.
						 */
						echo esc_html(
							$older > 0
								? __( 'Nobody this week', 'kaamase-core' )
								: __( 'Nobody yet', 'kaamase-core' )
						);
						?>
					</h2>
					<p class="ka-soft ka-mt-4">
						<?php
						printf(
							/* translators: %s: a length of time, for example 7 days */
							esc_html__( 'Nobody signed in has opened your profile in the last %s. Names appear here as soon as they do.', 'kaamase-core' ),
							esc_html( $window )
						);
						?>
					</p>
				</div>

			<?php else : ?>

				<p class="ka-lead">
					<?php
					printf(
						esc_html(
							/* translators: 1: number of people, 2: a length of time */
							_n(
								'%1$s person looked at you in the last %2$s.',
								'%1$s people looked at you in the last %2$s.',
								$heads,
								'kaamase-core'
							)
						),
						'<strong>' . esc_html( number_format_i18n( $heads ) ) . '</strong>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						esc_html( $window )
					);
					?>
				</p>

				<ul class="ka-looked">
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$viewer = (int) $row['viewer_id'];
						$who    = isset( $people[ $viewer ] ) ? $people[ $viewer ] : null;

						// The account went between the view and this page.
						if ( ! $who ) {
							continue;
						}

						$post = get_post( (int) $row['subject_id'] );

						if ( ! $post ) {
							continue;
						}

						$hits = max( 1, (int) $row['hits'] );
						?>
						<li class="ka-looked__row">

							<?php
							/*
							 * The theme's own initials block, not a
							 * second one of the same thing. It already
							 * exists for a profile with no photograph.
							 */
							if ( function_exists( 'kaamase_initials' ) ) :
								?>
								<span class="ka-avatar ka-avatar--sm ka-avatar--empty" aria-hidden="true">
									<?php echo esc_html( kaamase_initials( $who['name'] ) ); ?>
								</span>
							<?php endif; ?>

							<div class="ka-looked__what">
								<p class="ka-looked__who">
									<?php if ( $who['url'] ) : ?>
										<a href="<?php echo esc_url( $who['url'] ); ?>"><?php echo esc_html( $who['name'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $who['name'] ); ?>
									<?php endif; ?>
								</p>

								<p class="ka-small ka-soft">
									<?php
									printf(
										esc_html__( 'Looked at %s', 'kaamase-core' ),
										'<a href="' . esc_url( (string) get_permalink( $post ) ) . '">' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											. esc_html( kaamase_who_looked_what( (string) $row['subject_type'], get_the_title( $post ) ) )
											. '</a>'
									);
									?>
								</p>

								<p class="ka-small ka-mute">
									<?php
									printf(
										esc_html(
											/* translators: 1: number of times, 2: how long ago */
											_n( '%1$s time, %2$s ago', '%1$s times, %2$s ago', $hits, 'kaamase-core' )
										),
										esc_html( number_format_i18n( $hits ) ),
										esc_html( human_time_diff( (int) $row['last_seen'], time() ) )
									);
									?>
								</p>
							</div>

						</li>
					<?php endforeach; ?>
				</ul>

			<?php endif; ?>

			<?php
			/*
			 * Only to somebody who is not already paying, and worded as
			 * what the longer window answers rather than as more of the
			 * same. Hiring here is seasonal.
			 *
			 * The address is read first and the whole card is dropped if
			 * there is nowhere to send them. An offer with a button that
			 * goes nowhere is worse than no offer.
			 */
			$plans = function_exists( 'kaamase_pay_plans_url' ) ? (string) kaamase_pay_plans_url() : '';

			/*
			 * Nothing this week and nothing before it either: say
			 * nothing. Asking somebody to pay for a longer view of an
			 * empty list is the kind of offer that teaches people to
			 * distrust every later one.
			 */
			$offer = ! $paid && '' !== $plans && ( ! empty( $rows ) || $older > 0 );

			if ( $offer ) :
				?>
				<div class="ka-card ka-card--pad-lg ka-looked__more">

					<?php if ( empty( $rows ) ) : ?>

						<h2>
							<?php
							printf(
								esc_html(
									/* translators: %s: number of people */
									_n(
										'%s person looked at you before this week',
										'%s people looked at you before this week',
										$older,
										'kaamase-core'
									)
								),
								esc_html( number_format_i18n( $older ) )
							);
							?>
						</h2>
						<p class="ka-soft ka-mt-4">
							<?php
							esc_html_e(
								'Their names are held for a year. A free account sees the last week, which is why this one looks empty. Rich Manu opens the rest of it.',
								'kaamase-core'
							);
							?>
						</p>

					<?php else : ?>

						<h2><?php esc_html_e( 'See a whole year', 'kaamase-core' ); ?></h2>
						<p class="ka-soft ka-mt-4">
							<?php
							esc_html_e(
								'Work here comes back around. The employer who looked at you last March is the one to ring this March, and a week cannot tell you that. Rich Manu keeps the list for a year.',
								'kaamase-core'
							);
							?>
						</p>

					<?php endif; ?>

					<a class="ka-btn ka-btn--action ka-mt-6" href="<?php echo esc_url( $plans ); ?>">
						<?php esc_html_e( 'See Rich Manu', 'kaamase-core' ); ?>
					</a>
				</div>
			<?php endif; ?>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_who_looked', 'kaamase_who_looked_shortcode' );
