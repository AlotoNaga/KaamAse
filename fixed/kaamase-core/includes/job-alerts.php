<?php
/**
 * Job alerts.
 *
 * One notification a day, to workers who match work that was posted.
 *
 * Why a day and not a minute
 * --------------------------
 * A job board that pings on every post is a job board people mute, and a
 * muted app is worse than a silent one because muting is permanent and
 * nobody ever turns it back on. The evening somebody copies twenty jobs
 * across from a group chat is the evening every mason in Dimapur gets
 * twenty notifications and a good number uninstall.
 *
 * Gathering them into one message a day makes that impossible by
 * construction rather than by a limit somebody has to remember to set.
 * The cost is that urgent work waits until morning, which is a real cost
 * and is the reason this decision was worth making deliberately.
 *
 * Nobody is asked to choose anything
 * ----------------------------------
 * Every worker profile already carries a trade and a district, because
 * they are what a worker fills in to be found at all. A mason in Dimapur
 * hears about mason work in Dimapur. There is no screen to configure and
 * no step to skip, which matters: a preferences screen is a step most
 * people never open, and the ones who never open it are the ones who
 * would end up receiving everything.
 *
 * The one switch there is only ever governs this. Somebody who turns off
 * job alerts still hears that a person looked up their number and still
 * gets asked whether they hired somebody, because those are about them
 * and there are a handful of them a month. Silencing the useful few to
 * escape the noisy many is the mistake this separation exists to
 * prevent.
 *
 * Sent in batches
 * ---------------
 * push.php sends one request per person, which is right for a message to
 * one person and wrong here. A trade with three hundred workers in it
 * would be three hundred requests, and the run would time out somewhere
 * in the middle with no way of knowing who had been reached. These go a
 * hundred to a request, and the day is written down as done per person
 * so a run that dies halfway cannot send anybody the same digest twice.
 *
 * Opening the job from the notification
 * --------------------------------------
 * Each message carries the newest job in its group as id, the way the
 * other notifications carry theirs, and every job in the group as ids.
 * With one job the app opens it; with several it opens the list for the
 * trade and district.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/** Whether the daily digest runs at all. */
define( 'KAAMASE_ALERTS_ON_OPTION', 'kaamase_job_alerts_on' );

/** Which hour of the morning it goes out, in site time. */
define( 'KAAMASE_ALERTS_HOUR_OPTION', 'kaamase_job_alerts_hour' );

/** The day a person last had one, so nobody gets two. */
define( 'KAAMASE_ALERTS_SENT_KEY', '_kaamase_alert_sent' );

/** Set to no by somebody who does not want them. */
define( 'KAAMASE_ALERTS_USER_KEY', 'kaamase_job_alerts' );

/** The cron hook. */
define( 'KAAMASE_ALERTS_HOOK', 'kaamase_job_alerts_run' );

/** How many messages go in one request to Expo. */
define( 'KAAMASE_ALERTS_BATCH', 100 );


/* ==========================================================================
   1. SETTINGS AND SCHEDULE
   ========================================================================== */

if ( ! function_exists( 'kaamase_alerts_settings' ) ) {
	/**
	 * Register the two settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_alerts_settings() {

		register_setting(
			'kaamase_job_alerts',
			KAAMASE_ALERTS_ON_OPTION,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);

		register_setting(
			'kaamase_job_alerts',
			KAAMASE_ALERTS_HOUR_OPTION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'kaamase_alerts_clean_hour',
				'default'           => 8,
			)
		);
	}
}
add_action( 'admin_init', 'kaamase_alerts_settings' );

if ( ! function_exists( 'kaamase_alerts_clean_hour' ) ) {
	/**
	 * Keep the hour somewhere civilised and reschedule when it moves.
	 *
	 * @since 1.0.0
	 * @param mixed $raw What was chosen.
	 * @return int
	 */
	function kaamase_alerts_clean_hour( $raw ) {

		$hour = max( 0, min( 23, absint( $raw ) ) );

		wp_clear_scheduled_hook( KAAMASE_ALERTS_HOOK );

		return $hour;
	}
}

if ( ! function_exists( 'kaamase_alerts_hour' ) ) {
	/**
	 * Which hour the digest goes out.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	function kaamase_alerts_hour() {

		return max( 0, min( 23, (int) get_option( KAAMASE_ALERTS_HOUR_OPTION, 8 ) ) );
	}
}

if ( ! function_exists( 'kaamase_alerts_schedule' ) ) {
	/**
	 * Make sure the daily run is booked.
	 *
	 * Booked against the site's own timezone rather than the server's.
	 * A digest that says good morning at half past two because the
	 * machine is on another continent is the sort of thing nobody
	 * notices until a worker complains.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_alerts_schedule() {

		if ( ! get_option( KAAMASE_ALERTS_ON_OPTION, false ) ) {

			wp_clear_scheduled_hook( KAAMASE_ALERTS_HOOK );

			return;
		}

		if ( wp_next_scheduled( KAAMASE_ALERTS_HOOK ) ) {
			return;
		}

		$zone = wp_timezone();
		$now  = new DateTime( 'now', $zone );
		$next = new DateTime( 'now', $zone );

		$next->setTime( kaamase_alerts_hour(), 0, 0 );

		if ( $next <= $now ) {
			$next->modify( '+1 day' );
		}

		wp_schedule_event( $next->getTimestamp(), 'daily', KAAMASE_ALERTS_HOOK );
	}
}
add_action( 'init', 'kaamase_alerts_schedule', 30 );


/* ==========================================================================
   2. WHO WANTS THEM
   ========================================================================== */

if ( ! function_exists( 'kaamase_alerts_wanted_by' ) ) {
	/**
	 * Whether this person wants job alerts.
	 *
	 * Missing counts as yes. Somebody who has never touched the setting
	 * is somebody who has not asked to be left out, and a worker looking
	 * for work is the whole reason this exists.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_alerts_wanted_by( $user_id ) {

		return 'no' !== (string) get_user_meta( (int) $user_id, KAAMASE_ALERTS_USER_KEY, true );
	}
}

if ( ! function_exists( 'kaamase_alerts_set_wanted' ) ) {
	/**
	 * Record what somebody chose.
	 *
	 * @since 1.0.0
	 * @param int  $user_id User ID.
	 * @param bool $wanted  Whether they want them.
	 * @return void
	 */
	function kaamase_alerts_set_wanted( $user_id, $wanted ) {

		if ( $wanted ) {
			delete_user_meta( (int) $user_id, KAAMASE_ALERTS_USER_KEY );

			return;
		}

		update_user_meta( (int) $user_id, KAAMASE_ALERTS_USER_KEY, 'no' );
	}
}


/* ==========================================================================
   3. THE RUN
   ========================================================================== */

if ( ! function_exists( 'kaamase_alerts_new_jobs' ) ) {
	/**
	 * Work published since yesterday and still open.
	 *
	 * @since 1.0.0
	 * @return WP_Post[]
	 */
	function kaamase_alerts_new_jobs() {

		$found = get_posts(
			array(
				'post_type'        => 'kaamase_job',
				'post_status'      => 'publish',
				'posts_per_page'   => 200,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'date_query'       => array(
					array( 'after' => '24 hours ago' ),
				),
			)
		);

		return is_array( $found ) ? $found : array();
	}
}

if ( ! function_exists( 'kaamase_alerts_group_jobs' ) ) {
	/**
	 * Sort the new work into trade and district pairs.
	 *
	 * Jobs first, workers second. There are a handful of new jobs a day
	 * and there may be thousands of workers, so grouping the small side
	 * and then asking only for the workers who match means the run
	 * touches nobody who was never going to hear anything.
	 *
	 * @since 1.0.0
	 * @param WP_Post[] $jobs The new work.
	 * @return array[] Keyed by trade and district slug.
	 */
	function kaamase_alerts_group_jobs( $jobs ) {

		$groups = array();

		foreach ( $jobs as $job ) {

			$trades    = get_the_terms( $job->ID, 'kaamase_trade' );
			$districts = get_the_terms( $job->ID, 'kaamase_district' );

			if ( ! $trades || is_wp_error( $trades ) || ! $districts || is_wp_error( $districts ) ) {
				continue;
			}

			$key = $trades[0]->slug . '|' . $districts[0]->slug;

			if ( ! isset( $groups[ $key ] ) ) {

				$groups[ $key ] = array(
					'trade'    => $trades[0],
					'district' => $districts[0],
					'jobs'     => array(),
				);
			}

			$groups[ $key ]['jobs'][] = $job;
		}

		return $groups;
	}
}

if ( ! function_exists( 'kaamase_alerts_workers_for' ) ) {
	/**
	 * Available workers in one trade and district.
	 *
	 * @since 1.0.0
	 * @param string $trade    Trade slug.
	 * @param string $district District slug.
	 * @return int[] Profile IDs.
	 */
	function kaamase_alerts_workers_for( $trade, $district ) {

		$found = get_posts(
			array(
				'post_type'        => array( 'kaamase_worker', 'kaamase_gang' ),
				'post_status'      => 'publish',
				'posts_per_page'   => 2000,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'tax_query'        => array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'kaamase_trade',
						'field'    => 'slug',
						'terms'    => $trade,
					),
					array(
						'taxonomy' => 'kaamase_district',
						'field'    => 'slug',
						'terms'    => $district,
					),
				),
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'       => array(
					'relation' => 'OR',
					array(
						'key'   => KAAMASE_META_PREFIX . 'availability',
						'value' => 'live',
					),
					array(
						'key'     => KAAMASE_META_PREFIX . 'availability',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return is_array( $found ) ? array_map( 'absint', $found ) : array();
	}
}

if ( ! function_exists( 'kaamase_alerts_words' ) ) {
	/**
	 * What the notification says.
	 *
	 * The trade and the place go in the title, because that is what
	 * makes somebody open it rather than swipe it away, and both are
	 * visible on a locked screen without unlocking anything.
	 *
	 * @since 1.0.0
	 * @param array $group One trade and district group.
	 * @return array Title and body.
	 */
	function kaamase_alerts_words( $group ) {

		$count = count( $group['jobs'] );
		$first = $group['jobs'][0];

		if ( 1 === $count ) {

			return array(
				'title' => sprintf(
					/* translators: 1: trade, 2: district */
					__( 'New %1$s work in %2$s', 'kaamase-core' ),
					$group['trade']->name,
					$group['district']->name
				),
				'body'  => wp_strip_all_tags( get_the_title( $first->ID ) ),
			);
		}

		return array(
			'title' => sprintf(
				/* translators: 1: how many jobs, 2: trade, 3: district */
				__( '%1$s new %2$s jobs in %3$s', 'kaamase-core' ),
				number_format_i18n( $count ),
				$group['trade']->name,
				$group['district']->name
			),
			'body'  => sprintf(
				/* translators: %s: the newest job title */
				__( 'Newest: %s. Open Kaam Ase to see the rest.', 'kaamase-core' ),
				wp_strip_all_tags( get_the_title( $first->ID ) )
			),
		);
	}
}

if ( ! function_exists( 'kaamase_alerts_send' ) ) {
	/**
	 * Send a pile of messages, a hundred at a time.
	 *
	 * @since 1.0.0
	 * @param array[] $messages Expo messages.
	 * @return void
	 */
	function kaamase_alerts_send( $messages ) {

		if ( empty( $messages ) || ! function_exists( 'kaamase_push_endpoint' ) ) {
			return;
		}

		foreach ( array_chunk( $messages, KAAMASE_ALERTS_BATCH ) as $chunk ) {

			wp_remote_post(
				kaamase_push_endpoint(),
				array(
					'headers'  => array(
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json',
					),
					'body'     => wp_json_encode( $chunk ),
					'timeout'  => 10,
					'blocking' => false,
				)
			);
		}
	}
}

if ( ! function_exists( 'kaamase_alerts_run' ) ) {
	/**
	 * The daily digest.
	 *
	 * The day is written against each person before anything is sent.
	 * A run that dies partway through, which on shared hosting it
	 * eventually will, then resumes tomorrow rather than sending the
	 * same digest to the first half of the list all over again.
	 *
	 * @since 1.0.0
	 * @return int How many people were sent something.
	 */
	function kaamase_alerts_run() {

		if ( ! get_option( KAAMASE_ALERTS_ON_OPTION, false ) ) {
			return 0;
		}

		if ( function_exists( 'kaamase_push_enabled' ) && ! kaamase_push_enabled() ) {
			return 0;
		}

		$groups = kaamase_alerts_group_jobs( kaamase_alerts_new_jobs() );

		if ( empty( $groups ) ) {

			update_option( 'kaamase_job_alerts_last', array( 'when' => time(), 'sent' => 0 ), false );

			return 0;
		}

		$today    = gmdate( 'Ymd' );
		$messages = array();
		$reached  = 0;

		foreach ( $groups as $group ) {

			$words   = kaamase_alerts_words( $group );
			$posters = array();

			/*
			 * Newest first, because the new work was fetched newest first
			 * and grouping keeps that order. Twenty is plenty to open a
			 * list with and keeps the message well inside Expo's size.
			 */
			$job_ids = array_slice( array_map( 'intval', wp_list_pluck( $group['jobs'], 'ID' ) ), 0, 20 );

			foreach ( $group['jobs'] as $job ) {
				$posters[] = (int) $job->post_author;
			}

			foreach ( kaamase_alerts_workers_for( $group['trade']->slug, $group['district']->slug ) as $profile_id ) {

				$user_id = (int) get_post_field( 'post_author', $profile_id );

				if ( ! $user_id || in_array( $user_id, $posters, true ) ) {
					continue;
				}

				if ( ! kaamase_alerts_wanted_by( $user_id ) ) {
					continue;
				}

				if ( (string) get_user_meta( $user_id, KAAMASE_ALERTS_SENT_KEY, true ) === $today ) {
					continue;
				}

				$tokens = function_exists( 'kaamase_meta_array' )
					? kaamase_meta_array( get_user_meta( $user_id, 'kaamase_push_tokens', true ) )
					: array();

				if ( empty( $tokens ) ) {
					continue;
				}

				update_user_meta( $user_id, KAAMASE_ALERTS_SENT_KEY, $today );

				++$reached;

				foreach ( $tokens as $token ) {

					$messages[] = array(
						'to'        => (string) $token,
						'title'     => $words['title'],
						'body'      => $words['body'],
						'sound'     => 'default',
						'priority'  => 'high',
						'channelId' => 'default',
						'data'      => array(
							'type'     => 'job_alerts',
							'id'       => $job_ids[0],
							'ids'      => $job_ids,
							'trade'    => $group['trade']->slug,
							'district' => $group['district']->slug,
							'count'    => count( $group['jobs'] ),
						),
					);
				}
			}
		}

		kaamase_alerts_send( $messages );

		update_option( 'kaamase_job_alerts_last', array( 'when' => time(), 'sent' => $reached ), false );

		return $reached;
	}
}
add_action( KAAMASE_ALERTS_HOOK, 'kaamase_alerts_run' );


/* ==========================================================================
   4. THE SWITCH, WHEREVER SOMEBODY LOOKS FOR IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_alerts_card' ) ) {
	/**
	 * The switch on the dashboard.
	 *
	 * Says plainly what turning it off does not do. Somebody switching
	 * off job alerts is usually trying to stop noise, not to stop
	 * hearing that an employer wants to ring them, and if they cannot
	 * tell the difference they will reach for the phone's own setting
	 * and silence everything.
	 *
	 * @since 1.0.0
	 * @param int    $user_id Who is looking.
	 * @param int    $profile Their profile.
	 * @param string $type    Which kind of profile.
	 * @return void
	 */
	function kaamase_alerts_card( $user_id, $profile, $type ) {

		unset( $profile );

		if ( ! in_array( $type, array( 'kaamase_worker', 'kaamase_gang' ), true ) ) {
			return;
		}

		if ( ! get_option( KAAMASE_ALERTS_ON_OPTION, false ) ) {
			return;
		}

		$wanted = kaamase_alerts_wanted_by( $user_id );
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">

			<h2><?php esc_html_e( 'Telling you about new work', 'kaamase-core' ); ?></h2>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'Once a day, if new work has been posted in your trade and your district, we send one message with all of it. Never more than one a day, whatever gets posted.', 'kaamase-core' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ka-mt-4">

				<input type="hidden" name="action" value="kaamase_alerts_choose">
				<input type="hidden" name="wanted" value="<?php echo $wanted ? '0' : '1'; ?>">
				<?php wp_nonce_field( 'kaamase_alerts_choose' ); ?>

				<p>
					<strong>
						<?php
						echo $wanted
							? esc_html__( 'These are on.', 'kaamase-core' )
							: esc_html__( 'These are off.', 'kaamase-core' );
						?>
					</strong>
				</p>

				<button class="ka-btn ka-btn--outline ka-btn--sm" type="submit">
					<?php
					echo $wanted
						? esc_html__( 'Stop telling me about new work', 'kaamase-core' )
						: esc_html__( 'Tell me about new work', 'kaamase-core' );
					?>
				</button>

			</form>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'This only covers new work. You will still hear when somebody looks up your number and when we ask whether you were hired, because those are about you and there are only a few of them.', 'kaamase-core' ); ?>
			</p>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_alerts_card', 58, 3 );

if ( ! function_exists( 'kaamase_alerts_choose' ) ) {
	/**
	 * Take the choice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_alerts_choose() {

		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Sign in first.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_alerts_choose' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		$wanted = ! empty( $_POST['wanted'] );

		kaamase_alerts_set_wanted( get_current_user_id(), $wanted );

		$back = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : '';

		wp_safe_redirect( $back ? $back : home_url( '/' ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_alerts_choose', 'kaamase_alerts_choose' );

if ( ! function_exists( 'kaamase_alerts_shape_me' ) ) {
	/**
	 * Tell the app where the switch stands.
	 *
	 * @since 1.0.0
	 * @param array $me      The account object.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_alerts_shape_me( $me, $user_id ) {

		$me['job_alerts'] = array(
			'available' => (bool) get_option( KAAMASE_ALERTS_ON_OPTION, false ),
			'wanted'    => kaamase_alerts_wanted_by( $user_id ),
			'note'      => __( 'Once a day, one message with any new work in your trade and district. This does not affect messages about you, like somebody looking up your number.', 'kaamase-core' ),
		);

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_alerts_shape_me', 28, 2 );

if ( ! function_exists( 'kaamase_alerts_route' ) ) {
	/**
	 * Let the app set it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_alerts_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/job-alerts',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_alerts_rest',
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'wanted' => array(
						'required' => true,
						'type'     => 'boolean',
					),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_alerts_route' );

if ( ! function_exists( 'kaamase_alerts_rest' ) ) {
	/**
	 * Set it from the app.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_alerts_rest( $request ) {

		$wanted = (bool) $request->get_param( 'wanted' );

		kaamase_alerts_set_wanted( get_current_user_id(), $wanted );

		return rest_ensure_response(
			array(
				'ok'     => true,
				'wanted' => $wanted,
			)
		);
	}
}


/* ==========================================================================
   5. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_alerts_menu' ) ) {
	/**
	 * Add it under Kaam Ase.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_alerts_menu() {

		add_submenu_page(
			'kaamase',
			__( 'Job alerts', 'kaamase-core' ),
			__( 'Job alerts', 'kaamase-core' ),
			'manage_options',
			'kaamase-job-alerts',
			'kaamase_alerts_screen'
		);
	}
}
add_action( 'admin_menu', 'kaamase_alerts_menu', 27 );

if ( ! function_exists( 'kaamase_alerts_screen' ) ) {
	/**
	 * Render the screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_alerts_screen() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$ran = isset( $_GET['ran'] ) ? absint( $_GET['ran'] ) : -1;

		$last = (array) get_option( 'kaamase_job_alerts_last', array() );
		$next = wp_next_scheduled( KAAMASE_ALERTS_HOOK );
		$jobs = count( kaamase_alerts_new_jobs() );
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Job alerts', 'kaamase-core' ); ?></h1>

			<?php if ( $ran > -1 ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: how many people were sent one */
								_n( 'Sent to %s person.', 'Sent to %s people.', $ran, 'kaamase-core' ),
								number_format_i18n( $ran )
							)
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<p style="max-width:45em">
				<?php esc_html_e( 'Once a day, every worker who has new work posted in their trade and their district gets one message about all of it. Never more than one a day each, whatever gets posted. Nobody chooses a trade or a district for this: it uses what is already on their profile.', 'kaamase-core' ); ?>
			</p>

			<form method="post" action="options.php">

				<?php settings_fields( 'kaamase_job_alerts' ); ?>

				<table class="form-table" role="presentation">

					<tr>
						<th scope="row"><?php esc_html_e( 'Send them', 'kaamase-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" value="1"
									name="<?php echo esc_attr( KAAMASE_ALERTS_ON_OPTION ); ?>"
									<?php checked( (bool) get_option( KAAMASE_ALERTS_ON_OPTION, false ) ); ?>>
								<?php esc_html_e( 'Yes, send the daily message', 'kaamase-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Off until you switch it on. Leave it off until there is enough work being posted that a message is worth sending, because the first one somebody gets decides whether they keep the next.', 'kaamase-core' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="ka-alert-hour"><?php esc_html_e( 'What time', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<select id="ka-alert-hour" name="<?php echo esc_attr( KAAMASE_ALERTS_HOUR_OPTION ); ?>">
								<?php for ( $hour = 0; $hour < 24; $hour++ ) : ?>
									<option value="<?php echo esc_attr( $hour ); ?>" <?php selected( kaamase_alerts_hour(), $hour ); ?>>
										<?php echo esc_html( sprintf( '%02d:00', $hour ) ); ?>
									</option>
								<?php endfor; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Site time. Early morning works best: somebody deciding whether to look for work today decides it before they leave the house.', 'kaamase-core' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<?php submit_button(); ?>

			</form>

			<h2><?php esc_html_e( 'How it stands', 'kaamase-core' ); ?></h2>

			<table class="widefat striped" style="max-width:40em">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Work posted in the last day', 'kaamase-core' ); ?></th>
						<td><?php echo esc_html( number_format_i18n( $jobs ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Next run', 'kaamase-core' ); ?></th>
						<td>
							<?php
							echo esc_html(
								$next
									? wp_date( get_option( 'date_format' ) . ' H:i', $next )
									: __( 'Not booked. Switch it on and save.', 'kaamase-core' )
							);
							?>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Last run', 'kaamase-core' ); ?></th>
						<td>
							<?php
							echo esc_html(
								! empty( $last['when'] )
									? sprintf(
										/* translators: 1: date and time, 2: how many people */
										__( '%1$s, sent to %2$s', 'kaamase-core' ),
										wp_date( get_option( 'date_format' ) . ' H:i', (int) $last['when'] ),
										number_format_i18n( (int) ( $last['sent'] ?? 0 ) )
									)
									: __( 'Never', 'kaamase-core' )
							);
							?>
						</td>
					</tr>
				</tbody>
			</table>

			<p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kaamase_alerts_now' ), 'kaamase_alerts_now' ) ); ?>">
					<?php esc_html_e( 'Send it now', 'kaamase-core' ); ?>
				</a>
			</p>

			<p class="description" style="max-width:45em">
				<?php esc_html_e( 'Sending now counts for today, so anybody reached will not get the scheduled one as well. Use it once to see that it works, not to test repeatedly.', 'kaamase-core' ); ?>
			</p>

		</div>
		<?php
	}
}

if ( ! function_exists( 'kaamase_alerts_now' ) ) {
	/**
	 * Run it by hand.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_alerts_now() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_alerts_now' );

		$sent = kaamase_alerts_run();

		wp_safe_redirect( admin_url( 'admin.php?page=kaamase-job-alerts&ran=' . absint( $sent ) ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_alerts_now', 'kaamase_alerts_now' );