<?php
/**
 * Professional numbers.
 *
 * A screen in wp-admin, Kaam Ase → Professional numbers, that counts how
 * the professional side is doing: jobs, profiles, the people who joined
 * for it, and how often employers and job seekers asked for a number.
 * Opened once a week, it answers the only question that decides what to
 * build next: is it growing.
 *
 * Read only
 * ---------
 * Nothing here writes anything. No form, no button that changes a
 * record, no new data kept. Every figure is counted, when the screen is
 * opened, from what the site already stores: the jobs and profiles
 * themselves, the date each person joined, and the record kept on every
 * job and profile of who was given its number and when.
 *
 * What it does not count
 * ----------------------
 * Visits. A page view is not stored anywhere by Kaam Ase, and Google
 * Search Console already counts them better. Lookups are counted from the
 * record each job and profile keeps, which holds the most recent 200 and
 * nothing older than a year, more than enough for these windows.
 *
 * With this file removed, the screen is gone and nothing else changes.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. COUNTING
   ========================================================================== */

if ( ! function_exists( 'kaamase_pnum_ready' ) ) {
	/**
	 * Whether the professional side is installed.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_pnum_ready() {

		return defined( 'KAAMASE_PRO_GROUP' )
			&& function_exists( 'kaamase_pro_trade_slugs' )
			&& defined( 'KAAMASE_PROF_TYPE' );
	}
}

if ( ! function_exists( 'kaamase_pnum_when' ) ) {
	/**
	 * When a post was made, as a timestamp.
	 *
	 * Through WordPress rather than read from the date column, because a
	 * draft stores no GMT date and would otherwise count as made in 1970.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post.
	 * @return int
	 */
	function kaamase_pnum_when( $post ) {

		$time = function_exists( 'get_post_timestamp' ) ? get_post_timestamp( $post ) : strtotime( (string) $post->post_date_gmt . ' UTC' );

		return $time ? (int) $time : 0;
	}
}

if ( ! function_exists( 'kaamase_pnum_week' ) ) {
	/**
	 * Which of the last eight weeks a time falls in: 0 is the last seven
	 * days, 7 is seven to eight weeks ago, -1 is older.
	 *
	 * @since 1.0.0
	 * @param int $time Timestamp.
	 * @param int $now  Now.
	 * @return int
	 */
	function kaamase_pnum_week( $time, $now ) {

		if ( $time <= 0 || $time > $now ) {
			return -1;
		}

		$week = (int) floor( ( $now - $time ) / WEEK_IN_SECONDS );

		return $week < 8 ? $week : -1;
	}
}

if ( ! function_exists( 'kaamase_pnum_lookups' ) ) {
	/**
	 * Add one post's lookups to the running totals.
	 *
	 * @since 1.0.0
	 * @param int   $post_id Job or profile.
	 * @param array $totals  Totals: d7 and d30 (people), weeks (times).
	 * @param int   $now     Now.
	 * @return array
	 */
	function kaamase_pnum_lookups( $post_id, $totals, $now ) {

		$log = get_post_meta( (int) $post_id, KAAMASE_META_PREFIX . 'contact_log', true );

		foreach ( is_array( $log ) ? $log : array() as $entry ) {

			if ( ! is_array( $entry ) || empty( $entry['time'] ) ) {
				continue;
			}

			$time = (int) $entry['time'];
			$user = absint( $entry['user'] ?? 0 );

			// Keyed by person: somebody who asked about three jobs is one person.
			if ( $time >= $now - 7 * DAY_IN_SECONDS ) {
				$totals['d7'][ $user ] = true;
			}

			if ( $time >= $now - 30 * DAY_IN_SECONDS ) {
				$totals['d30'][ $user ] = true;
			}

			$week = kaamase_pnum_week( $time, $now );

			if ( $week >= 0 ) {
				$totals['weeks'][ $week ]++;
			}
		}

		return $totals;
	}
}

if ( ! function_exists( 'kaamase_pnum_count' ) ) {
	/**
	 * Every figure on the screen.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	function kaamase_pnum_count() {

		$now   = time();
		$d7    = $now - 7 * DAY_IN_SECONDS;
		$d30   = $now - 30 * DAY_IN_SECONDS;
		$empty = array_fill( 0, 8, 0 );

		$out = array(
			'jobs'     => array(
				'open'      => 0,
				'checking'  => 0,
				'email'     => 0,
				'posted7'   => 0,
				'posted30'  => 0,
				'filled'    => 0,
				'employers' => 0,
				'weeks'     => $empty,
			),
			'job_look' => array( 'd7' => array(), 'd30' => array(), 'weeks' => $empty ),
			'profiles' => array(
				'all'        => 0,
				'unfinished' => 0,
				'listed'     => 0,
				'hidden'     => 0,
				'waiting'    => 0,
				'on_hold'    => 0,
				'public'     => 0,
				'new7'       => 0,
				'new30'      => 0,
				'weeks'      => $empty,
			),
			'pro_look' => array( 'd7' => array(), 'd30' => array(), 'weeks' => $empty ),
			'people'   => array(
				'joined'    => 0,
				'joined7'   => 0,
				'joined30'  => 0,
				'quiet'     => 0,
				'weeks'     => $empty,
			),
		);

		/* ---- Professional jobs ---- */

		$slugs = kaamase_pro_trade_slugs();

		if ( $slugs ) {

			$ids = get_posts(
				array(
					'post_type'        => 'kaamase_job',
					'post_status'      => array( 'publish', 'pending', 'draft', 'kaamase_closed' ),
					'posts_per_page'   => 5000,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
					'orderby'          => 'ID',
					'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'kaamase_trade',
							'field'    => 'slug',
							'terms'    => $slugs,
						),
					),
				)
			);

			if ( $ids ) {
				_prime_post_caches( $ids, false, true );
			}

			$employers = array();

			foreach ( $ids as $id ) {

				$post = get_post( $id );

				if ( ! $post ) {
					continue;
				}

				$when = kaamase_pnum_when( $post );

				if ( 'pending' === $post->post_status ) {
					$out['jobs']['checking']++;
				} elseif ( 'draft' === $post->post_status ) {
					$out['jobs']['email']++;
				} elseif ( function_exists( 'kaamase_job_is_open' ) && kaamase_job_is_open( $id ) ) {
					$out['jobs']['open']++;
				}

				if ( 'filled' === (string) get_post_meta( $id, KAAMASE_META_PREFIX . 'job_status', true ) ) {
					$out['jobs']['filled']++;
				}

				if ( $when >= $d7 ) {
					$out['jobs']['posted7']++;
				}

				if ( $when >= $d30 ) {
					$out['jobs']['posted30']++;
				}

				$week = kaamase_pnum_week( $when, $now );

				if ( $week >= 0 ) {
					$out['jobs']['weeks'][ $week ]++;
				}

				$employers[ (int) $post->post_author ] = true;

				$out['job_look'] = kaamase_pnum_lookups( $id, $out['job_look'], $now );
			}

			$out['jobs']['employers'] = count( $employers );
		}

		/* ---- Professional profiles ---- */

		$profiles = get_posts(
			array(
				'post_type'        => KAAMASE_PROF_TYPE,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'   => 5000,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'orderby'          => 'ID',
			)
		);

		if ( $profiles ) {
			_prime_post_caches( $profiles, false, true );
		}

		foreach ( $profiles as $id ) {

			$post = get_post( $id );

			if ( ! $post ) {
				continue;
			}

			$out['profiles']['all']++;

			$when = kaamase_pnum_when( $post );

			if ( $when >= $d7 ) {
				$out['profiles']['new7']++;
			}

			if ( $when >= $d30 ) {
				$out['profiles']['new30']++;
			}

			$week = kaamase_pnum_week( $when, $now );

			if ( $week >= 0 ) {
				$out['profiles']['weeks'][ $week ]++;
			}

			$out['pro_look'] = kaamase_pnum_lookups( $id, $out['pro_look'], $now );

			// Begun at sign-up and never saved in full: counted on its own, not as hidden.
			if ( function_exists( 'kaamase_prof_is_complete' ) && ! kaamase_prof_is_complete( $id ) ) {
				$out['profiles']['unfinished']++;
				continue;
			}

			$state = function_exists( 'kaamase_prof_state' ) ? kaamase_prof_state( $id ) : '';

			if ( 'listed' === $state ) {

				$out['profiles']['listed']++;

				if ( function_exists( 'kaamase_prof_is_public' ) && kaamase_prof_is_public( $id ) ) {
					$out['profiles']['public']++;
				}
			} elseif ( 'waiting_for_email' === $state ) {
				$out['profiles']['waiting']++;
			} elseif ( 'on_hold' === $state ) {
				$out['profiles']['on_hold']++;
			} else {
				$out['profiles']['hidden']++;
			}
		}

		/* ---- People ---- */

		if ( defined( 'KAAMASE_JOIN_KEY' ) ) {

			$joined = get_users(
				array(
					'meta_key'   => KAAMASE_JOIN_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => 'professional', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'fields'     => array( 'ID', 'user_registered' ),
					'number'     => 20000,
				)
			);

			foreach ( $joined as $user ) {

				$when = (int) strtotime( (string) $user->user_registered . ' UTC' );

				$out['people']['joined']++;

				if ( $when >= $d7 ) {
					$out['people']['joined7']++;
				}

				if ( $when >= $d30 ) {
					$out['people']['joined30']++;
				}

				$week = kaamase_pnum_week( $when, $now );

				if ( $week >= 0 ) {
					$out['people']['weeks'][ $week ]++;
				}
			}
		}

		if ( defined( 'KAAMASE_MATCH_ALERTS_USER_KEY' ) ) {
			$out['people']['quiet'] = count(
				get_users(
					array(
						'meta_key'   => KAAMASE_MATCH_ALERTS_USER_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value' => 'no', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
						'fields'     => 'ID',
						'number'     => 20000,
					)
				)
			);
		}

		// People, not times: somebody who asked twice, or about two, is one.
		foreach ( array( 'job_look', 'pro_look' ) as $key ) {
			$out[ $key ]['d7']  = count( $out[ $key ]['d7'] );
			$out[ $key ]['d30'] = count( $out[ $key ]['d30'] );
		}

		return $out;
	}
}


/* ==========================================================================
   2. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_pnum_menu' ) ) {
	/**
	 * Kaam Ase → Professional numbers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pnum_menu() {

		if ( ! kaamase_pnum_ready() ) {
			return;
		}

		add_submenu_page(
			'kaamase',
			__( 'Professional numbers', 'kaamase-core' ),
			__( 'Professional numbers', 'kaamase-core' ),
			'manage_options',
			'kaamase-professional-numbers',
			'kaamase_pnum_screen'
		);
	}
}
add_action( 'admin_menu', 'kaamase_pnum_menu', 29 );

if ( ! function_exists( 'kaamase_pnum_rows' ) ) {
	/**
	 * A two-column table of labels and figures.
	 *
	 * @since 1.0.0
	 * @param array $rows Label => figure, or label => array( figure, link ).
	 * @return void
	 */
	function kaamase_pnum_rows( $rows ) {
		?>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
				<?php foreach ( $rows as $label => $value ) : ?>
					<?php
					$link   = is_array( $value ) ? (string) $value[1] : '';
					$figure = number_format_i18n( (int) ( is_array( $value ) ? $value[0] : $value ) );
					?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td style="width:8em;text-align:right">
							<strong>
								<?php if ( '' !== $link ) : ?>
									<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $figure ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $figure ); ?>
								<?php endif; ?>
							</strong>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

if ( ! function_exists( 'kaamase_pnum_screen' ) ) {
	/**
	 * Draw the screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pnum_screen() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot see this page.', 'kaamase-core' ) );
		}

		if ( ! kaamase_pnum_ready() ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Professional numbers', 'kaamase-core' ) . '</h1><p>' . esc_html__( 'Professional jobs and profiles are not installed.', 'kaamase-core' ) . '</p></div>';
			return;
		}

		$n   = kaamase_pnum_count();
		$now = time();

		$checking = add_query_arg(
			array(
				'post_type'   => 'kaamase_job',
				'post_status' => 'pending',
			),
			admin_url( 'edit.php' )
		);

		$categories = function_exists( 'kaamase_pro_category_counts' ) ? kaamase_pro_category_counts() : array();
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Professional numbers', 'kaamase-core' ); ?></h1>

			<p>
				<?php esc_html_e( 'How the professional side is doing. Counted from what the site already keeps, each time this screen is opened. Nothing here changes anything.', 'kaamase-core' ); ?>
			</p>

			<h2><?php esc_html_e( 'Professional jobs', 'kaamase-core' ); ?></h2>

			<?php
			kaamase_pnum_rows(
				array(
					__( 'Open now', 'kaamase-core' )                                    => $n['jobs']['open'],
					__( 'Waiting for your check', 'kaamase-core' )                      => array( $n['jobs']['checking'], $n['jobs']['checking'] ? $checking : '' ),
					__( 'Waiting for the employer to confirm their email', 'kaamase-core' ) => $n['jobs']['email'],
					__( 'Posted in the last 7 days', 'kaamase-core' )                   => $n['jobs']['posted7'],
					__( 'Posted in the last 30 days', 'kaamase-core' )                  => $n['jobs']['posted30'],
					__( 'Marked filled by the employer, ever', 'kaamase-core' )         => $n['jobs']['filled'],
					__( 'Employers who have posted one, ever', 'kaamase-core' )         => $n['jobs']['employers'],
					__( 'People given a job\'s number or email, last 7 days', 'kaamase-core' )  => $n['job_look']['d7'],
					__( 'People given a job\'s number or email, last 30 days', 'kaamase-core' ) => $n['job_look']['d30'],
				)
			);
			?>

			<?php if ( $categories ) : ?>
				<h2><?php esc_html_e( 'Open jobs by category', 'kaamase-core' ); ?></h2>
				<?php
				$rows = array();

				foreach ( array_slice( $categories, 0, 15, true ) as $slug => $count ) {
					$term                  = get_term_by( 'slug', (string) $slug, 'kaamase_trade' );
					$rows[ $term ? $term->name : (string) $slug ] = (int) $count;
				}

				kaamase_pnum_rows( $rows );
				?>
				<p class="description"><?php esc_html_e( 'Recounted every ten minutes.', 'kaamase-core' ); ?></p>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Professional profiles', 'kaamase-core' ); ?></h2>

			<?php
			kaamase_pnum_rows(
				array(
					__( 'All profiles', 'kaamase-core' )                                  => $n['profiles']['all'],
					__( 'Begun at sign-up, not finished yet', 'kaamase-core' )           => $n['profiles']['unfinished'],
					__( 'Finished and shown to employers', 'kaamase-core' )              => $n['profiles']['listed'],
					__( 'Of those, open to everyone, Google included', 'kaamase-core' )  => $n['profiles']['public'],
					__( 'Finished but hidden by their owner', 'kaamase-core' )           => $n['profiles']['hidden'],
					__( 'Waiting for the owner to confirm their email', 'kaamase-core' ) => $n['profiles']['waiting'],
					__( 'On hold', 'kaamase-core' )                                      => $n['profiles']['on_hold'],
					__( 'New in the last 7 days', 'kaamase-core' )                       => $n['profiles']['new7'],
					__( 'New in the last 30 days', 'kaamase-core' )                      => $n['profiles']['new30'],
					__( 'Employers given a professional\'s number, last 7 days', 'kaamase-core' )  => $n['pro_look']['d7'],
					__( 'Employers given a professional\'s number, last 30 days', 'kaamase-core' ) => $n['pro_look']['d30'],
				)
			);
			?>

			<h2><?php esc_html_e( 'People', 'kaamase-core' ); ?></h2>

			<?php
			kaamase_pnum_rows(
				array(
					__( 'Joined as a professional, ever', 'kaamase-core' )                 => $n['people']['joined'],
					__( 'Joined as a professional, last 7 days', 'kaamase-core' )          => $n['people']['joined7'],
					__( 'Joined as a professional, last 30 days', 'kaamase-core' )         => $n['people']['joined30'],
					__( 'Turned off the daily message about new jobs', 'kaamase-core' )    => $n['people']['quiet'],
				)
			);
			?>

			<h2><?php esc_html_e( 'Week by week', 'kaamase-core' ); ?></h2>

			<div style="max-width:720px;overflow-x:auto">
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="white-space:nowrap"><?php esc_html_e( 'Week', 'kaamase-core' ); ?></th>
						<th style="text-align:right"><?php esc_html_e( 'Jobs posted', 'kaamase-core' ); ?></th>
						<th style="text-align:right"><?php esc_html_e( 'Profiles made', 'kaamase-core' ); ?></th>
						<th style="text-align:right"><?php esc_html_e( 'Joined as a professional', 'kaamase-core' ); ?></th>
						<th style="text-align:right"><?php esc_html_e( 'Job numbers given', 'kaamase-core' ); ?></th>
						<th style="text-align:right"><?php esc_html_e( 'Profile numbers given', 'kaamase-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php for ( $week = 0; $week < 8; $week++ ) : ?>
						<?php
						$from = $now - ( $week + 1 ) * WEEK_IN_SECONDS;
						$to   = $now - $week * WEEK_IN_SECONDS;
						?>
						<tr>
							<td style="white-space:nowrap">
								<?php
								echo esc_html(
									0 === $week
										? __( 'The last 7 days', 'kaamase-core' )
										: wp_date( 'j M', $from ) . ' – ' . wp_date( 'j M', $to )
								);
								?>
							</td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( $n['jobs']['weeks'][ $week ] ) ); ?></td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( $n['profiles']['weeks'][ $week ] ) ); ?></td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( $n['people']['weeks'][ $week ] ) ); ?></td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( $n['job_look']['weeks'][ $week ] ) ); ?></td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( $n['pro_look']['weeks'][ $week ] ) ); ?></td>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>
			</div>

			<p class="description" style="max-width:720px">
				<?php esc_html_e( '"Jobs posted" counts a job posted again by its employer in the week it went back up. "Numbers given" counts every time, including the same person twice; the lines above count people.', 'kaamase-core' ); ?>
			</p>

		</div>
		<?php
	}
}
