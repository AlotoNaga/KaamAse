<?php
/**
 * Post types.
 *
 * Four: workers, teams, employers and jobs.
 *
 * Two things in this file carry more weight than the registration
 * arrays around them.
 *
 * The first is capabilities. Every type uses its own capability set with
 * meta capability mapping switched on, which is what makes it possible
 * for a worker to edit their own profile and nobody else's. The lazy
 * alternative, capability_type of post, would mean any user who can edit
 * a post can edit every worker profile on the platform. On a site
 * holding photographs and villages of people who cannot defend
 * themselves, that is not a configuration detail.
 *
 * The second is job expiry. A job board where nothing ever closes is a
 * job board full of work that was filled three weeks ago. Workers apply,
 * hear nothing, and stop believing the listings. One round of that and
 * they are gone. Jobs close themselves on a schedule at the bottom of
 * this file, and urgent jobs close faster because urgent means today.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE TYPES
   ========================================================================== */

if ( ! function_exists( 'kaamase_post_types' ) ) {
	/**
	 * Every post type this plugin owns.
	 *
	 * @since 1.0.0
	 * @return string[]
	 */
	function kaamase_post_types() {
		return array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer', 'kaamase_job' );
	}
}

if ( ! function_exists( 'kaamase_register_post_types' ) ) {
	/**
	 * Register workers, teams, employers and jobs.
	 *
	 * Runs after taxonomies, which register at priority 5.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_register_post_types() {

		/* --------------------------------------------------------------
		 * Worker
		 *
		 * One profile per person. The URL carries their name because
		 * being findable by name is the point of registering, and a
		 * profile nobody can link to is worth nothing to the person who
		 * made it.
		 * ------------------------------------------------------------ */
		register_post_type(
			'kaamase_worker',
			array(
				'labels'              => array(
					'name'                  => _x( 'Workers', 'post type general name', 'kaamase-core' ),
					'singular_name'         => _x( 'Worker', 'post type singular name', 'kaamase-core' ),
					'add_new'               => __( 'Add worker', 'kaamase-core' ),
					'add_new_item'          => __( 'Add worker', 'kaamase-core' ),
					'edit_item'             => __( 'Edit worker', 'kaamase-core' ),
					'new_item'              => __( 'New worker', 'kaamase-core' ),
					'view_item'             => __( 'View profile', 'kaamase-core' ),
					'search_items'          => __( 'Search workers', 'kaamase-core' ),
					'not_found'             => __( 'No workers found.', 'kaamase-core' ),
					'not_found_in_trash'    => __( 'No workers in trash.', 'kaamase-core' ),
					'all_items'             => __( 'All workers', 'kaamase-core' ),
					'menu_name'             => __( 'Workers', 'kaamase-core' ),
					'featured_image'        => __( 'Profile photo', 'kaamase-core' ),
					'set_featured_image'    => __( 'Set profile photo', 'kaamase-core' ),
					'remove_featured_image' => __( 'Remove profile photo', 'kaamase-core' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'rest_base'           => 'workers',
				'menu_icon'           => 'dashicons-hammer',
				'menu_position'       => 26,
				'hierarchical'        => false,
				'has_archive'         => 'workers',
				'exclude_from_search' => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'author' ),
				'taxonomies'          => array( 'kaamase_trade', 'kaamase_district', 'kaamase_language' ),
				'capability_type'     => array( 'kaamase_worker', 'kaamase_workers' ),
				'map_meta_cap'        => true,
				'delete_with_user'    => true,
				'rewrite'             => array(
					'slug'       => 'worker',
					'with_front' => false,
				),
			)
		);

		/* --------------------------------------------------------------
		 * Team
		 *
		 * A leader plus a headcount. The object a contractor actually
		 * hires, and the one every national hiring app fails to model.
		 * ------------------------------------------------------------ */
		register_post_type(
			'kaamase_gang',
			array(
				'labels'              => array(
					'name'               => _x( 'Teams', 'post type general name', 'kaamase-core' ),
					'singular_name'      => _x( 'Team', 'post type singular name', 'kaamase-core' ),
					'add_new'            => __( 'Add team', 'kaamase-core' ),
					'add_new_item'       => __( 'Add team', 'kaamase-core' ),
					'edit_item'          => __( 'Edit team', 'kaamase-core' ),
					'new_item'           => __( 'New team', 'kaamase-core' ),
					'view_item'          => __( 'View team', 'kaamase-core' ),
					'search_items'       => __( 'Search teams', 'kaamase-core' ),
					'not_found'          => __( 'No teams found.', 'kaamase-core' ),
					'not_found_in_trash' => __( 'No teams in trash.', 'kaamase-core' ),
					'all_items'          => __( 'All teams', 'kaamase-core' ),
					'menu_name'          => __( 'Teams', 'kaamase-core' ),
					'featured_image'     => __( 'Team photo', 'kaamase-core' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'rest_base'           => 'teams',
				'menu_icon'           => 'dashicons-groups',
				'menu_position'       => 27,
				'hierarchical'        => false,
				'has_archive'         => 'teams',
				'exclude_from_search' => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'author' ),
				'taxonomies'          => array( 'kaamase_trade', 'kaamase_district', 'kaamase_language' ),
				'capability_type'     => array( 'kaamase_gang', 'kaamase_gangs' ),
				'map_meta_cap'        => true,
				'delete_with_user'    => true,
				'rewrite'             => array(
					'slug'       => 'team',
					'with_front' => false,
				),
			)
		);

		/* --------------------------------------------------------------
		 * Employer
		 *
		 * No archive. Nobody browses a list of employers, but a worker
		 * deciding whether to take a job absolutely wants to look one up
		 * and see how other workers rated them. Single pages are public,
		 * the index is not.
		 * ------------------------------------------------------------ */
		register_post_type(
			'kaamase_employer',
			array(
				'labels'              => array(
					'name'               => _x( 'Employers', 'post type general name', 'kaamase-core' ),
					'singular_name'      => _x( 'Employer', 'post type singular name', 'kaamase-core' ),
					'add_new'            => __( 'Add employer', 'kaamase-core' ),
					'add_new_item'       => __( 'Add employer', 'kaamase-core' ),
					'edit_item'          => __( 'Edit employer', 'kaamase-core' ),
					'new_item'           => __( 'New employer', 'kaamase-core' ),
					'view_item'          => __( 'View employer', 'kaamase-core' ),
					'search_items'       => __( 'Search employers', 'kaamase-core' ),
					'not_found'          => __( 'No employers found.', 'kaamase-core' ),
					'not_found_in_trash' => __( 'No employers in trash.', 'kaamase-core' ),
					'all_items'          => __( 'All employers', 'kaamase-core' ),
					'menu_name'          => __( 'Employers', 'kaamase-core' ),
					'featured_image'     => __( 'Logo', 'kaamase-core' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => true,
				'rest_base'           => 'employers',
				'menu_icon'           => 'dashicons-building',
				'menu_position'       => 28,
				'hierarchical'        => false,
				'has_archive'         => false,
				'exclude_from_search' => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'author' ),
				'taxonomies'          => array( 'kaamase_district' ),
				'capability_type'     => array( 'kaamase_employer', 'kaamase_employers' ),
				'map_meta_cap'        => true,
				'delete_with_user'    => true,
				'rewrite'             => array(
					'slug'       => 'employer',
					'with_front' => false,
				),
			)
		);

		/* --------------------------------------------------------------
		 * Job
		 *
		 * delete_with_user is false here on purpose. When an employer
		 * closes their account, their jobs are anonymised rather than
		 * deleted, because a worker's rating history is attached to
		 * those jobs and deleting them would silently wipe part of that
		 * worker's record. privacy.php handles it properly.
		 * ------------------------------------------------------------ */
		register_post_type(
			'kaamase_job',
			array(
				'labels'              => array(
					'name'               => _x( 'Jobs', 'post type general name', 'kaamase-core' ),
					'singular_name'      => _x( 'Job', 'post type singular name', 'kaamase-core' ),
					'add_new'            => __( 'Post a job', 'kaamase-core' ),
					'add_new_item'       => __( 'Post a job', 'kaamase-core' ),
					'edit_item'          => __( 'Edit job', 'kaamase-core' ),
					'new_item'           => __( 'New job', 'kaamase-core' ),
					'view_item'          => __( 'View job', 'kaamase-core' ),
					'search_items'       => __( 'Search jobs', 'kaamase-core' ),
					'not_found'          => __( 'No jobs found.', 'kaamase-core' ),
					'not_found_in_trash' => __( 'No jobs in trash.', 'kaamase-core' ),
					'all_items'          => __( 'All jobs', 'kaamase-core' ),
					'menu_name'          => __( 'Jobs', 'kaamase-core' ),
					'featured_image'     => __( 'Job photo', 'kaamase-core' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'rest_base'           => 'jobs',
				'menu_icon'           => 'dashicons-clipboard',
				'menu_position'       => 25,
				'hierarchical'        => false,
				'has_archive'         => 'jobs',
				'exclude_from_search' => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'author' ),
				'taxonomies'          => array( 'kaamase_trade', 'kaamase_district' ),
				'capability_type'     => array( 'kaamase_job', 'kaamase_jobs' ),
				'map_meta_cap'        => true,
				'delete_with_user'    => false,
				'rewrite'             => array(
					'slug'       => 'job',
					'with_front' => false,
				),
			)
		);
	}
}
add_action( 'init', 'kaamase_register_post_types', 10 );


/* ==========================================================================
   2. CLOSED STATUS

   A closed job is not a draft and not deleted. It is a real thing that
   happened and it stays in the employer's history, in the worker's
   rating record and in the admin list. It just stops appearing anywhere
   public.
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_post_status' ) ) {
	/**
	 * Register the closed job status.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_register_post_status() {

		register_post_status(
			'kaamase_closed',
			array(
				'label'                     => _x( 'Closed', 'job status', 'kaamase-core' ),
				'public'                    => false,
				'internal'                  => false,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of closed jobs */
				'label_count'               => _n_noop(
					'Closed <span class="count">(%s)</span>',
					'Closed <span class="count">(%s)</span>',
					'kaamase-core'
				),
			)
		);
	}
}
add_action( 'init', 'kaamase_register_post_status', 6 );


/* ==========================================================================
   3. JOB EXPIRY

   The single most effective thing you can do for trust on a job board,
   and the thing most of them skip because it reduces the listing count.
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_lifespan' ) ) {
	/**
	 * How many days a job stays open.
	 *
	 * Urgent jobs get three days. Urgent means today or tomorrow, and a
	 * listing that still says Urgent two weeks later teaches everybody
	 * to ignore the tag.
	 *
	 * @since 1.0.0
	 * @param int $post_id Job ID.
	 * @return int Days.
	 */
	function kaamase_job_lifespan( $post_id ) {

		$urgent = (bool) get_post_meta( $post_id, KAAMASE_META_PREFIX . 'urgent', true );
		$days   = $urgent ? 3 : 21;

		/**
		 * Filter how long a job stays open.
		 *
		 * @since 1.0.0
		 * @param int $days    Number of days.
		 * @param int $post_id Job ID.
		 */
		return absint( apply_filters( 'kaamase_job_lifespan', $days, $post_id ) );
	}
}

if ( ! function_exists( 'kaamase_set_job_expiry' ) ) {
	/**
	 * Stamp an expiry date when a job is published.
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 * @return void
	 */
	function kaamase_set_job_expiry( $post_id, $post, $update ) {

		unset( $update );

		if ( 'kaamase_job' !== $post->post_type || 'publish' !== $post->post_status ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$existing = get_post_meta( $post_id, KAAMASE_META_PREFIX . 'expires', true );

		// Do not move an expiry the employer has already extended.
		if ( $existing ) {
			return;
		}

		$expires = time() + ( kaamase_job_lifespan( $post_id ) * DAY_IN_SECONDS );

		update_post_meta( $post_id, KAAMASE_META_PREFIX . 'expires', $expires );
	}
}
add_action( 'wp_insert_post', 'kaamase_set_job_expiry', 10, 3 );

if ( ! function_exists( 'kaamase_close_expired_jobs' ) ) {
	/**
	 * Close jobs whose expiry has passed.
	 *
	 * Runs daily. Batched, because on cheap shared hosting an unbounded
	 * query that grows with the site is a timeout waiting to happen once
	 * there are a few thousand jobs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_close_expired_jobs() {

		kaamase_backfill_job_expiry();

		/*
		 * Keeps going until there is nothing left, rather than stopping
		 * after one batch.
		 *
		 * This said "Batched" and took a hundred, then returned. The
		 * task runs once a day, so the day more than a hundred jobs
		 * expired was the day a backlog started that could never clear:
		 * a hundred a day off a pile that grows faster than that stays
		 * a pile for ever, and every job in it is still sitting in front
		 * of workers as though it were open.
		 *
		 * The wall clock guard is what keeps it safe on cheap shared
		 * hosting. Whatever is left is picked up on the next run.
		 */
		$deadline = time() + 20;

		do {

			$jobs = get_posts(
				array(
					'post_type'      => 'kaamase_job',
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => KAAMASE_META_PREFIX . 'expires',
							'value'   => time(),
							'compare' => '<',
							'type'    => 'NUMERIC',
						),
					),
				)
			);

			if ( empty( $jobs ) ) {
				return;
			}

			foreach ( $jobs as $job_id ) {

				wp_update_post(
					array(
						'ID'          => $job_id,
						'post_status' => 'kaamase_closed',
					)
				);

				update_post_meta( $job_id, KAAMASE_META_PREFIX . 'job_status', 'expired' );

				/**
				 * Fires when a job closes because it expired.
				 *
				 * Hooked later to email the employer and offer a one tap
				 * repost, which is the cheapest reactivation you will get.
				 *
				 * @since 1.0.0
				 * @param int $job_id Job ID.
				 */
				do_action( 'kaamase_job_expired', $job_id );
			}
		} while ( time() < $deadline );
	}
}

if ( ! function_exists( 'kaamase_backfill_job_expiry' ) ) {
	/**
	 * Give an expiry to any open job that never got one.
	 *
	 * A job with no expires row was immortal. The closing query only
	 * matches rows that exist and are in the past, and kaamase_job_is_open
	 * treats a missing expiry as open, so anything created outside the
	 * publish path that stamps it, an import, a direct write, or anything
	 * predating the field, sat in front of workers for ever.
	 *
	 * Dated from when the job was published rather than from now, so an
	 * old one closes on the next pass instead of being handed another
	 * three weeks for having slipped through.
	 *
	 * @since 1.3.3
	 * @return void
	 */
	function kaamase_backfill_job_expiry() {

		$jobs = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => KAAMASE_META_PREFIX . 'expires',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		foreach ( (array) $jobs as $job_id ) {

			$published = get_post_time( 'U', true, $job_id );
			$published = $published ? (int) $published : time();

			update_post_meta(
				$job_id,
				KAAMASE_META_PREFIX . 'expires',
				$published + ( kaamase_job_lifespan( $job_id ) * DAY_IN_SECONDS )
			);
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_close_expired_jobs' );

if ( ! function_exists( 'kaamase_schedule_events' ) ) {
	/**
	 * Make sure the daily task is scheduled.
	 *
	 * Checked on every load rather than only on activation, because a
	 * missing cron event is invisible until somebody notices that
	 * nothing has expired in a month.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_schedule_events() {

		if ( ! wp_next_scheduled( 'kaamase_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'kaamase_daily' );
		}
	}
}
add_action( 'init', 'kaamase_schedule_events' );


/* ==========================================================================
   4. OWNERSHIP HELPERS
   ========================================================================== */

if ( ! function_exists( 'kaamase_get_user_profile' ) ) {
	/**
	 * The profile post belonging to a user.
	 *
	 * One worker profile per person and one employer profile per
	 * account. Anything else and ratings, jobs and contact history
	 * scatter across duplicates.
	 *
	 * @since 1.0.0
	 * @param int    $user_id  User ID. Defaults to the current user.
	 * @param string $type     Post type. Defaults to kaamase_worker.
	 * @return int Post ID, or 0 when there is none.
	 */
	function kaamase_get_user_profile( $user_id = 0, $type = 'kaamase_worker' ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id || ! in_array( $type, kaamase_post_types(), true ) ) {
			return 0;
		}

		$cache_key = 'kaamase_profile_' . $type . '_' . $user_id;
		$cached    = wp_cache_get( $cache_key, 'kaamase' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$found = get_posts(
			array(
				'post_type'      => $type,
				'author'         => $user_id,
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'draft', 'pending', 'kaamase_closed' ),
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$id = ! empty( $found ) ? (int) $found[0] : 0;

		wp_cache_set( $cache_key, $id, 'kaamase', HOUR_IN_SECONDS );

		return $id;
	}
}

if ( ! function_exists( 'kaamase_clear_profile_cache' ) ) {
	/**
	 * Drop the cached profile lookup when a profile changes.
	 *
	 * @since 1.0.0
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    The post, when the hook supplies it.
	 * @return void
	 */
	function kaamase_clear_profile_cache( $post_id, $post = null ) {

		/*
		 * The post is taken from the hook when it offers one.
		 *
		 * deleted_post fires after the row is gone, so get_post() here
		 * returned null and the function gave up before clearing
		 * anything. Deleting a worker profile left the lookup pointing
		 * at it for an hour, and the dashboard went on believing the
		 * person still had a profile.
		 */
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post_id );
		}

		if ( ! $post || ! in_array( $post->post_type, kaamase_post_types(), true ) ) {
			return;
		}

		wp_cache_delete( 'kaamase_profile_' . $post->post_type . '_' . $post->post_author, 'kaamase' );
	}
}
add_action( 'save_post', 'kaamase_clear_profile_cache', 10, 2 );
add_action( 'deleted_post', 'kaamase_clear_profile_cache', 10, 2 );
add_action( 'trashed_post', 'kaamase_clear_profile_cache' );

if ( ! function_exists( 'kaamase_user_owns' ) ) {
	/**
	 * Whether a user owns a given post.
	 *
	 * Used everywhere a front end screen decides whether to show an edit
	 * control. Never trust it alone for a write. The capability check is
	 * what protects the data, this only decides what gets drawn.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return bool
	 */
	function kaamase_user_owns( $post_id, $user_id = 0 ) {

		$post    = get_post( $post_id );
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $post || ! $user_id ) {
			return false;
		}

		return (int) $post->post_author === $user_id;
	}
}

if ( ! function_exists( 'kaamase_job_is_open' ) ) {
	/**
	 * Whether a job is still accepting workers.
	 *
	 * @since 1.0.0
	 * @param int $post_id Job ID.
	 * @return bool
	 */
	function kaamase_job_is_open( $post_id ) {

		$post = get_post( $post_id );

		if ( ! $post || 'kaamase_job' !== $post->post_type || 'publish' !== $post->post_status ) {
			return false;
		}

		$status = get_post_meta( $post_id, KAAMASE_META_PREFIX . 'job_status', true );

		if ( in_array( $status, array( 'filled', 'closed', 'expired' ), true ) ) {
			return false;
		}

		$expires = (int) get_post_meta( $post_id, KAAMASE_META_PREFIX . 'expires', true );

		return ! $expires || $expires > time();
	}
}
