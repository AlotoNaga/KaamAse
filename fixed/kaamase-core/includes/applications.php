<?php
/**
 * Applications.
 *
 * Apply on Kaam Ase: a third way to apply for a professional job, beside
 * a phone call and an email. The job seeker sends their professional
 * profile and a short note; the employer gets everybody who applied in
 * one list, marks each one Shortlisted or Not selected, and the job
 * seeker is told.
 *
 * Why
 * ---
 * A bank or a school posting one job gets fifty phone calls, at any hour,
 * from people it cannot tell apart. It stops posting here. A list it can
 * read when it has time, with the qualification and the experience of
 * each person on one screen, is the reason for a bigger employer to come
 * back. The job seeker gains too: an answer, instead of a number that
 * rings out.
 *
 * Only for professional jobs, and only when the employer picks it in the
 * professional job form. Everyday work stays a phone call, as it should:
 * nobody hiring a mason for tomorrow wants to read applications.
 *
 * The rules
 * ---------
 *   - Who can apply: signed in, email confirmed, with a finished
 *     professional profile that is not on hold. Hidden or kept off Google
 *     does not matter: applying shows the profile to that one employer.
 *   - One application per job. A withdrawn one, or one closed with its
 *     job, may be sent again if the job is open again.
 *   - At most KAAMASE_APP_DAILY in a day, so nobody sprays every job.
 *   - The employer's number stays private on such a job. The contact
 *     gate refuses it, the same gate every number goes through, so a
 *     phone cannot be reached around the list.
 *   - Applying shares the applicant's phone number and email with that
 *     employer, and the form says so before they send.
 *
 * Who is told what
 * ----------------
 *   - The employer: at once for the first applicant of a job, then at
 *     most once a day with how many more came in.
 *   - The applicant: when shortlisted, when not selected, and when the
 *     job closes before anybody decided.
 * Through kaamase_notify_user(): a phone when there is one, an email when
 * there is not, never both, in the reader's own language.
 *
 * What is kept
 * ------------
 * Each application is a private record (the kaamase_application type):
 * never public, never listed, never in a sitemap, no screen in wp-admin.
 * The applicant is its author, the job its parent. They go:
 *   - six months after their job closes (KAAMASE_APP_KEEP_DAYS);
 *   - with the job, when the job is deleted;
 *   - with the applicant's professional profile, or their account;
 *   - in "Erase personal data", and they are in "Export personal data".
 *
 * With this file removed, a job set to take applications goes back to
 * the employer's phone number, and nothing else changes.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 *
 * Changelog
 *   1.1.0  The CV (cv.php): sent with an application when the applicant
 *          chooses, which they do by default when they have one; added
 *          from the apply form; opened by the employer from the list.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KAAMASE_APP_TYPE' ) ) {
	define( 'KAAMASE_APP_TYPE', 'kaamase_application' );
}

if ( ! defined( 'KAAMASE_APP_DAILY' ) ) {
	define( 'KAAMASE_APP_DAILY', 20 );
}

if ( ! defined( 'KAAMASE_APP_NOTE_MAX' ) ) {
	define( 'KAAMASE_APP_NOTE_MAX', 500 );
}

if ( ! defined( 'KAAMASE_APP_KEEP_DAYS' ) ) {
	define( 'KAAMASE_APP_KEEP_DAYS', 180 );
}


/* ==========================================================================
   1. THE RECORD
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_register_type' ) ) {
	/**
	 * The private record behind each application.
	 *
	 * Like reports: not public, not queryable, no archive, no address.
	 * Unlike reports, no admin screen either. What is in one is between
	 * a job seeker and one employer.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_apps_register_type() {

		register_post_type(
			KAAMASE_APP_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Applications',
					'singular_name' => 'Application',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
			)
		);
	}
}
add_action( 'init', 'kaamase_apps_register_type', 11 );

if ( ! function_exists( 'kaamase_apps_ready' ) ) {
	/**
	 * Whether everything this leans on is installed.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_apps_ready() {

		return defined( 'KAAMASE_PROF_TYPE' )
			&& defined( 'KAAMASE_META_PREFIX' )
			&& function_exists( 'kaamase_pro_is_job' )
			&& function_exists( 'kaamase_prof_id' )
			&& function_exists( 'kaamase_prof_is_complete' )
			&& function_exists( 'kaamase_job_is_open' );
	}
}

if ( ! function_exists( 'kaamase_apps_key' ) ) {
	/**
	 * A meta key of this file.
	 *
	 * @since 1.0.0
	 * @param string $name Short name.
	 * @return string
	 */
	function kaamase_apps_key( $name ) {
		return KAAMASE_META_PREFIX . $name;
	}
}

if ( ! function_exists( 'kaamase_apps_takes' ) ) {
	/**
	 * Whether a job takes applications on Kaam Ase.
	 *
	 * The stored answer, not the field's default: a job is never put on
	 * applications by a default.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return bool
	 */
	function kaamase_apps_takes( $job_id ) {

		$job_id = (int) $job_id;

		if ( ! $job_id || ! kaamase_apps_ready() || 'kaamase_job' !== get_post_type( $job_id ) || ! kaamase_pro_is_job( $job_id ) ) {
			return false;
		}

		return 'kaamase' === (string) get_post_meta( $job_id, kaamase_apps_key( 'pro_apply_method' ), true );
	}
}

if ( ! function_exists( 'kaamase_apps_job_is_over' ) ) {
	/**
	 * Whether a job has finished taking people, for good or until reposted.
	 *
	 * Not the same as not open: a job held for a check after an edit is
	 * not open for a few hours, and closing every application on it then
	 * would tell everybody who applied that the job had gone.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return bool
	 */
	function kaamase_apps_job_is_over( $job_id ) {

		$post = get_post( (int) $job_id );

		if ( ! $post || 'kaamase_job' !== $post->post_type ) {
			return true;
		}

		if ( in_array( $post->post_status, array( 'kaamase_closed', 'trash', 'draft', 'private' ), true ) ) {
			return true;
		}

		if ( in_array( (string) get_post_meta( $post->ID, kaamase_apps_key( 'job_status' ), true ), array( 'filled', 'closed', 'expired' ), true ) ) {
			return true;
		}

		$expires = (int) get_post_meta( $post->ID, kaamase_apps_key( 'expires' ), true );

		return $expires && $expires <= time();
	}
}

if ( ! function_exists( 'kaamase_apps_labels' ) ) {
	/**
	 * What each state is called, to the person reading.
	 *
	 * The same state reads differently to each side: an application the
	 * employer has not opened yet is Sent to the job seeker and New to
	 * the employer.
	 *
	 * @since 1.0.0
	 * @param string $side applicant or employer.
	 * @return string[]
	 */
	function kaamase_apps_labels( $side = 'applicant' ) {

		if ( 'employer' === $side ) {
			return array(
				'sent'         => __( 'New', 'kaamase-core' ),
				'seen'         => __( 'Seen', 'kaamase-core' ),
				'shortlisted'  => __( 'Shortlisted', 'kaamase-core' ),
				'not_selected' => __( 'Not selected', 'kaamase-core' ),
				'closed'       => __( 'Job closed', 'kaamase-core' ),
				'withdrawn'    => __( 'Withdrawn', 'kaamase-core' ),
			);
		}

		return array(
			'sent'         => __( 'Sent', 'kaamase-core' ),
			'seen'         => __( 'Seen by the employer', 'kaamase-core' ),
			'shortlisted'  => __( 'Shortlisted', 'kaamase-core' ),
			'not_selected' => __( 'Not selected', 'kaamase-core' ),
			'closed'       => __( 'Job closed', 'kaamase-core' ),
			'withdrawn'    => __( 'Withdrawn', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_apps_status' ) ) {
	/**
	 * Where an application stands.
	 *
	 * @since 1.0.0
	 * @param int $app_id Application.
	 * @return string
	 */
	function kaamase_apps_status( $app_id ) {

		$status = (string) get_post_meta( (int) $app_id, kaamase_apps_key( 'app_status' ), true );

		return isset( kaamase_apps_labels()[ $status ] ) ? $status : 'sent';
	}
}

if ( ! function_exists( 'kaamase_apps_get' ) ) {
	/**
	 * One application, or null when the ID is not one.
	 *
	 * @since 1.0.0
	 * @param int $app_id Application.
	 * @return WP_Post|null
	 */
	function kaamase_apps_get( $app_id ) {

		$post = get_post( (int) $app_id );

		return ( $post && KAAMASE_APP_TYPE === $post->post_type ) ? $post : null;
	}
}

if ( ! function_exists( 'kaamase_apps_query' ) ) {
	/**
	 * Application IDs, newest first.
	 *
	 * @since 1.0.0
	 * @param array $args Extra get_posts arguments.
	 * @return int[]
	 */
	function kaamase_apps_query( $args ) {

		$ids = array_map(
			'intval',
			get_posts(
				array_merge(
					array(
						'post_type'        => KAAMASE_APP_TYPE,
						'post_status'      => 'private',
						'posts_per_page'   => 500,
						'fields'           => 'ids',
						'no_found_rows'    => true,
						'suppress_filters' => true,
						'orderby'          => 'date',
						'order'            => 'DESC',
					),
					$args
				)
			)
		);

		// Every caller reads each one's state next: one query, not one each.
		if ( $ids ) {
			update_postmeta_cache( $ids );
		}

		return $ids;
	}
}

if ( ! function_exists( 'kaamase_apps_parents' ) ) {
	/**
	 * Applications and their jobs, application ID => job ID, newest first.
	 *
	 * @since 1.0.0
	 * @param array $args Extra get_posts arguments.
	 * @return int[]
	 */
	function kaamase_apps_parents( $args ) {

		$map = get_posts(
			array_merge(
				array(
					'post_type'        => KAAMASE_APP_TYPE,
					'post_status'      => 'private',
					'posts_per_page'   => 2000,
					'fields'           => 'id=>parent',
					'no_found_rows'    => true,
					'suppress_filters' => true,
					'orderby'          => 'date',
					'order'            => 'DESC',
				),
				$args
			)
		);

		$out = array();

		foreach ( (array) $map as $app_id => $job_id ) {
			$out[ (int) $app_id ] = (int) $job_id;
		}

		if ( $out ) {
			update_postmeta_cache( array_keys( $out ) );
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_apps_of_job' ) ) {
	/**
	 * Applications to one job, withdrawn ones left out.
	 *
	 * @since 1.0.0
	 * @param int      $job_id   Job.
	 * @param string[] $statuses Only these, or every one but withdrawn.
	 * @return int[]
	 */
	function kaamase_apps_of_job( $job_id, $statuses = array() ) {

		$ids = kaamase_apps_query( array( 'post_parent' => (int) $job_id ) );

		return array_values(
			array_filter(
				$ids,
				static function ( $id ) use ( $statuses ) {
					$status = kaamase_apps_status( $id );
					return $statuses ? in_array( $status, $statuses, true ) : 'withdrawn' !== $status;
				}
			)
		);
	}
}

if ( ! function_exists( 'kaamase_apps_of_user' ) ) {
	/**
	 * Every application one person has sent, newest first.
	 *
	 * @since 1.0.0
	 * @param int $user_id Applicant.
	 * @return int[]
	 */
	function kaamase_apps_of_user( $user_id ) {

		$user_id = (int) $user_id;

		return $user_id ? kaamase_apps_query( array( 'author' => $user_id ) ) : array();
	}
}

if ( ! function_exists( 'kaamase_apps_user_index' ) ) {
	/**
	 * Which jobs somebody has applied for, by job: job ID => application ID.
	 *
	 * Held for the request, because a list of twenty jobs asks twenty
	 * times and the answer is one query.
	 *
	 * @since 1.0.0
	 * @param int  $user_id Applicant.
	 * @param bool $fresh   Ask again rather than use what is held.
	 * @return int[]
	 */
	function kaamase_apps_user_index( $user_id, $fresh = false ) {

		static $held = array();

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return array();
		}

		if ( $fresh || ! isset( $held[ $user_id ] ) ) {

			$index = array();

			foreach ( kaamase_apps_parents( array( 'author' => $user_id ) ) as $app_id => $job ) {
				if ( $job && ! isset( $index[ $job ] ) ) {
					$index[ $job ] = $app_id;
				}
			}

			$held[ $user_id ] = $index;
		}

		return $held[ $user_id ];
	}
}

if ( ! function_exists( 'kaamase_apps_find' ) ) {
	/**
	 * Somebody's application to one job, or 0.
	 *
	 * @since 1.0.0
	 * @param int $job_id  Job.
	 * @param int $user_id Applicant.
	 * @return int
	 */
	function kaamase_apps_find( $job_id, $user_id ) {

		$index = kaamase_apps_user_index( $user_id );

		return isset( $index[ (int) $job_id ] ) ? (int) $index[ (int) $job_id ] : 0;
	}
}

if ( ! function_exists( 'kaamase_apps_counts' ) ) {
	/**
	 * How many applied to a job, and how they stand.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return int[] total, new, shortlisted, not_selected.
	 */
	function kaamase_apps_counts( $job_id ) {

		$out = array(
			'total'        => 0,
			'new'          => 0,
			'shortlisted'  => 0,
			'not_selected' => 0,
		);

		foreach ( kaamase_apps_of_job( $job_id ) as $app_id ) {

			$status = kaamase_apps_status( $app_id );

			$out['total']++;

			if ( 'sent' === $status ) {
				$out['new']++;
			} elseif ( 'shortlisted' === $status ) {
				$out['shortlisted']++;
			} elseif ( 'not_selected' === $status ) {
				$out['not_selected']++;
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_apps_employer_jobs' ) ) {
	/**
	 * An employer's jobs that take applications or have some.
	 *
	 * A job switched back to the phone keeps the list it already had.
	 *
	 * @since 1.0.0
	 * @param int $user_id Employer.
	 * @return int[] Newest first.
	 */
	function kaamase_apps_employer_jobs( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || ! kaamase_apps_ready() ) {
			return array();
		}

		$base = array(
			'post_type'        => 'kaamase_job',
			'post_status'      => array( 'publish', 'pending', 'kaamase_closed' ),
			'author'           => $user_id,
			'posts_per_page'   => 200,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'orderby'          => 'date',
			'order'            => 'DESC',
		);

		$jobs = array_map( 'intval', get_posts( $base ) );

		if ( ! $jobs ) {
			return array();
		}

		// The ones set to take applications, and the ones that have some: two queries, not two per job.
		$takes = array_map(
			'intval',
			get_posts(
				array_merge(
					$base,
					array(
						'post__in'   => $jobs,
						'meta_key'   => kaamase_apps_key( 'pro_apply_method' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value' => 'kaamase', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					)
				)
			)
		);

		$have = array_values( kaamase_apps_parents( array( 'post_parent__in' => $jobs ) ) );

		return array_values(
			array_filter(
				$jobs,
				static function ( $job_id ) use ( $takes, $have ) {
					return in_array( $job_id, $takes, true ) || in_array( $job_id, $have, true );
				}
			)
		);
	}
}

if ( ! function_exists( 'kaamase_apps_owns_job' ) ) {
	/**
	 * Whether somebody may read and decide the applications to a job.
	 *
	 * The employer who posted it, and the site's administrators.
	 *
	 * @since 1.0.0
	 * @param int $job_id  Job.
	 * @param int $user_id Account.
	 * @return bool
	 */
	function kaamase_apps_owns_job( $job_id, $user_id ) {

		$post    = get_post( (int) $job_id );
		$user_id = (int) $user_id;

		if ( ! $post || 'kaamase_job' !== $post->post_type || ! $user_id ) {
			return false;
		}

		return (int) $post->post_author === $user_id || user_can( $user_id, 'manage_options' );
	}
}


/* ==========================================================================
   2. APPLYING
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_refusal' ) ) {
	/**
	 * Why somebody may not apply for a job, or null when they may.
	 *
	 * In the order a person can fix them: there is no point telling
	 * somebody to finish a profile for a job that has closed.
	 *
	 * @since 1.0.0
	 * @param int $job_id  Job.
	 * @param int $user_id Applicant.
	 * @return WP_Error|null
	 */
	function kaamase_apps_refusal( $job_id, $user_id ) {

		$job_id  = (int) $job_id;
		$user_id = (int) $user_id;

		if ( ! kaamase_apps_takes( $job_id ) ) {
			return new WP_Error( 'kaamase_apply_not_here', __( 'This job does not take applications on Kaam Ase. Open the job to see how to apply.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		if ( ! $user_id ) {
			return new WP_Error( 'kaamase_signed_out', __( 'Sign in to apply.', 'kaamase-core' ), array( 'status' => 401 ) );
		}

		if ( ! kaamase_job_is_open( $job_id ) ) {
			return new WP_Error( 'kaamase_apply_closed', __( 'This job is no longer taking applications.', 'kaamase-core' ), array( 'status' => 410 ) );
		}

		if ( (int) get_post_field( 'post_author', $job_id ) === $user_id ) {
			return new WP_Error( 'kaamase_apply_own', __( 'This is your own job.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		$mine = kaamase_apps_find( $job_id, $user_id );

		if ( $mine && ! in_array( kaamase_apps_status( $mine ), array( 'withdrawn', 'closed' ), true ) ) {
			return new WP_Error(
				'kaamase_apply_already',
				__( 'You have already applied for this job.', 'kaamase-core' ),
				array(
					'status'      => 409,
					'application' => $mine,
				)
			);
		}

		if ( function_exists( 'kaamase_is_blocked' ) && kaamase_is_blocked( (int) get_post_field( 'post_author', $job_id ), $user_id ) ) {
			return new WP_Error( 'kaamase_apply_blocked', __( 'You cannot apply for this job.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( $user_id ) ) {
			return new WP_Error( 'kaamase_apply_unverified', __( 'Confirm your email first. We sent you a link when you registered.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		$profile = kaamase_prof_id( $user_id );

		if ( ! $profile ) {
			return new WP_Error( 'kaamase_apply_no_profile', __( 'Make your professional profile first. The employer sees it when you apply, so it is what they judge you on.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		if ( function_exists( 'kaamase_prof_state' ) && 'on_hold' === kaamase_prof_state( $profile ) ) {
			return new WP_Error( 'kaamase_apply_on_hold', __( 'Your professional profile is being checked by Kaam Ase. You can apply once it is back.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		if ( ! kaamase_prof_is_complete( $profile ) ) {
			return new WP_Error( 'kaamase_apply_unfinished', __( 'Finish your professional profile first: your headline, qualification and experience. The employer sees it when you apply.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		if ( kaamase_apps_sent_today( $user_id ) >= (int) KAAMASE_APP_DAILY ) {
			return new WP_Error(
				'kaamase_apply_limit',
				sprintf(
					/* translators: %s: how many applications a day */
					__( 'You have sent %s applications today, the most in one day. Try again tomorrow.', 'kaamase-core' ),
					number_format_i18n( (int) KAAMASE_APP_DAILY )
				),
				array( 'status' => 429 )
			);
		}

		return null;
	}
}

if ( ! function_exists( 'kaamase_apps_sent_today' ) ) {
	/**
	 * How many applications somebody sent in the last day.
	 *
	 * Withdrawn ones count. Otherwise sending and withdrawing would be a
	 * way round the limit.
	 *
	 * @since 1.0.0
	 * @param int $user_id Applicant.
	 * @return int
	 */
	function kaamase_apps_sent_today( $user_id ) {

		$since = time() - DAY_IN_SECONDS;
		$count = 0;

		foreach ( kaamase_apps_of_user( $user_id ) as $app_id ) {
			if ( (int) get_post_meta( $app_id, kaamase_apps_key( 'app_sent_at' ), true ) > $since ) {
				$count++;
			}
		}

		return $count;
	}
}

if ( ! function_exists( 'kaamase_apps_clean_note' ) ) {
	/**
	 * A note as it is kept: plain text, no longer than allowed.
	 *
	 * @since 1.0.0
	 * @param mixed $note Typed note.
	 * @return string
	 */
	function kaamase_apps_clean_note( $note ) {

		$note = is_scalar( $note ) ? sanitize_textarea_field( (string) $note ) : '';

		return trim( function_exists( 'mb_substr' ) ? mb_substr( $note, 0, (int) KAAMASE_APP_NOTE_MAX ) : substr( $note, 0, (int) KAAMASE_APP_NOTE_MAX ) );
	}
}

if ( ! function_exists( 'kaamase_apps_apply' ) ) {
	/**
	 * Send an application.
	 *
	 * @since 1.0.0
	 * @param int       $job_id  Job.
	 * @param int       $user_id Applicant.
	 * @param string    $note    What they wrote, or ''.
	 * @param bool|null $with_cv Send their CV; null sends it when they have one.
	 * @return int|WP_Error Application ID.
	 */
	function kaamase_apps_apply( $job_id, $user_id, $note = '', $with_cv = null ) {

		$job_id  = (int) $job_id;
		$user_id = (int) $user_id;
		$refusal = kaamase_apps_refusal( $job_id, $user_id );

		if ( is_wp_error( $refusal ) ) {
			return $refusal;
		}

		$profile = kaamase_prof_id( $user_id );
		$note    = kaamase_apps_clean_note( $note );
		$now     = time();
		$app_id  = kaamase_apps_find( $job_id, $user_id );

		if ( ! $app_id ) {

			$user = get_userdata( $user_id );

			$app_id = wp_insert_post(
				array(
					'post_type'      => KAAMASE_APP_TYPE,
					'post_status'    => 'private',
					'post_author'    => $user_id,
					'post_parent'    => $job_id,
					'post_title'     => sprintf( '%s / %s', $user ? $user->display_name : $user_id, get_the_title( $job_id ) ),
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				),
				true
			);

			if ( is_wp_error( $app_id ) || ! $app_id ) {
				return new WP_Error( 'kaamase_apply_failed', __( 'That did not go through. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
			}
		}

		$app_id = (int) $app_id;

		// Sent again after a withdrawal or a closed job: as if new.
		update_post_meta( $app_id, kaamase_apps_key( 'app_status' ), 'sent' );
		update_post_meta( $app_id, kaamase_apps_key( 'app_status_at' ), $now );
		update_post_meta( $app_id, kaamase_apps_key( 'app_sent_at' ), $now );
		update_post_meta( $app_id, kaamase_apps_key( 'app_profile' ), (int) $profile );
		update_post_meta( $app_id, kaamase_apps_key( 'app_note' ), $note );
		update_post_meta( $app_id, kaamase_apps_key( 'app_with_cv' ), ( false !== $with_cv && kaamase_apps_has_cv( $user_id ) ) ? 1 : 0 );
		delete_post_meta( $app_id, kaamase_apps_key( 'app_told' ) );
		delete_post_meta( $app_id, kaamase_apps_key( 'app_told_status' ) );

		kaamase_apps_user_index( $user_id, true );

		kaamase_apps_tell_employer_first( $app_id );

		/**
		 * Fires when somebody applies for a job.
		 *
		 * @since 1.0.0
		 * @param int $app_id  Application.
		 * @param int $job_id  Job.
		 * @param int $user_id Applicant.
		 */
		do_action( 'kaamase_application_sent', $app_id, $job_id, $user_id );

		return $app_id;
	}
}

if ( ! function_exists( 'kaamase_apps_has_cv' ) ) {
	/**
	 * Whether somebody has a CV to send.
	 *
	 * @since 1.1.0
	 * @param int $user_id Applicant.
	 * @return bool
	 */
	function kaamase_apps_has_cv( $user_id ) {
		return function_exists( 'kaamase_cv_of' ) && null !== kaamase_cv_of( (int) $user_id );
	}
}

if ( ! function_exists( 'kaamase_apps_withdraw' ) ) {
	/**
	 * Take an application back.
	 *
	 * Allowed until the employer says no or the job closes. A shortlisted
	 * person who has taken another job is exactly who should say so.
	 *
	 * @since 1.0.0
	 * @param int $app_id  Application.
	 * @param int $user_id Who is asking.
	 * @return true|WP_Error
	 */
	function kaamase_apps_withdraw( $app_id, $user_id ) {

		$app = kaamase_apps_get( $app_id );

		if ( ! $app || (int) $app->post_author !== (int) $user_id ) {
			return new WP_Error( 'kaamase_apply_missing', __( 'That application was not found.', 'kaamase-core' ), array( 'status' => 404 ) );
		}

		if ( ! in_array( kaamase_apps_status( $app->ID ), array( 'sent', 'seen', 'shortlisted' ), true ) ) {
			return new WP_Error( 'kaamase_apply_settled', __( 'This application can no longer be withdrawn.', 'kaamase-core' ), array( 'status' => 409 ) );
		}

		update_post_meta( $app->ID, kaamase_apps_key( 'app_status' ), 'withdrawn' );
		update_post_meta( $app->ID, kaamase_apps_key( 'app_status_at' ), time() );

		// Nothing left to tell the employer about this one.
		update_post_meta( $app->ID, kaamase_apps_key( 'app_told' ), 1 );

		return true;
	}
}

if ( ! function_exists( 'kaamase_apps_decide' ) ) {
	/**
	 * The employer's answer: shortlisted, not selected, or back to seen.
	 *
	 * The applicant is told the first time an answer is given, and again
	 * only if it changes to a different one. Changing a mind twice in a
	 * minute should not send two messages.
	 *
	 * @since 1.0.0
	 * @param int    $app_id  Application.
	 * @param string $status  shortlisted, not_selected or seen.
	 * @param int    $user_id Who is deciding.
	 * @return true|WP_Error
	 */
	function kaamase_apps_decide( $app_id, $status, $user_id ) {

		$app    = kaamase_apps_get( $app_id );
		$status = sanitize_key( (string) $status );

		if ( ! $app || ! kaamase_apps_owns_job( (int) $app->post_parent, (int) $user_id ) || 'withdrawn' === kaamase_apps_status( $app->ID ) ) {
			return new WP_Error( 'kaamase_apply_missing', __( 'That application was not found.', 'kaamase-core' ), array( 'status' => 404 ) );
		}

		if ( ! in_array( $status, array( 'shortlisted', 'not_selected', 'seen' ), true ) ) {
			return new WP_Error( 'kaamase_apply_bad_status', __( 'That is not an answer an application can have.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		if ( kaamase_apps_status( $app->ID ) === $status ) {
			return true;
		}

		update_post_meta( $app->ID, kaamase_apps_key( 'app_status' ), $status );
		update_post_meta( $app->ID, kaamase_apps_key( 'app_status_at' ), time() );
		update_post_meta( $app->ID, kaamase_apps_key( 'app_told' ), 1 );

		if ( 'seen' !== $status && (string) get_post_meta( $app->ID, kaamase_apps_key( 'app_told_status' ), true ) !== $status ) {
			update_post_meta( $app->ID, kaamase_apps_key( 'app_told_status' ), $status );
			kaamase_apps_tell_applicant( $app->ID, $status );
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_apps_mark_seen' ) ) {
	/**
	 * The employer has looked: New becomes Seen.
	 *
	 * @since 1.0.0
	 * @param int[] $app_ids Applications shown.
	 * @return void
	 */
	function kaamase_apps_mark_seen( $app_ids ) {

		foreach ( (array) $app_ids as $app_id ) {
			if ( 'sent' === kaamase_apps_status( $app_id ) ) {
				update_post_meta( (int) $app_id, kaamase_apps_key( 'app_status' ), 'seen' );
				update_post_meta( (int) $app_id, kaamase_apps_key( 'app_status_at' ), time() );
				update_post_meta( (int) $app_id, kaamase_apps_key( 'app_told' ), 1 );
			}
		}
	}
}


/* ==========================================================================
   3. WHAT EACH SIDE SEES
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_job_brief' ) ) {
	/**
	 * A job, in the few words a list of applications needs.
	 *
	 * Built here rather than with the full job shape, because a closed
	 * or deleted job still has to be named in somebody's list.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return array
	 */
	function kaamase_apps_job_brief( $job_id ) {

		$job_id = (int) $job_id;
		$post   = get_post( $job_id );

		if ( ! $post || 'kaamase_job' !== $post->post_type || 'trash' === $post->post_status ) {
			return array(
				'id'       => $job_id,
				'title'    => __( 'A job that has been removed', 'kaamase-core' ),
				'url'      => '',
				'employer' => '',
				'district' => null,
				'salary'   => null,
				'open'     => false,
			);
		}

		$details  = function_exists( 'kaamase_pro_details' ) ? kaamase_pro_details( $job_id ) : null;
		$district = function_exists( 'kaamase_read_field' ) ? (string) kaamase_read_field( $job_id, 'district' ) : '';

		return array(
			'id'       => $job_id,
			'title'    => get_the_title( $job_id ),
			'url'      => (string) get_permalink( $job_id ),
			'employer' => function_exists( 'kaamase_field' ) ? (string) kaamase_field( $job_id, 'employer_name' ) : '',
			'district' => function_exists( 'kaamase_shape_district' ) ? kaamase_shape_district( $district ) : null,
			'salary'   => $details ? $details['salary'] : null,
			'open'     => kaamase_job_is_open( $job_id ),
		);
	}
}

if ( ! function_exists( 'kaamase_apps_for_applicant' ) ) {
	/**
	 * One application, as the person who sent it sees it.
	 *
	 * @since 1.0.0
	 * @param int $app_id Application.
	 * @return array|null
	 */
	function kaamase_apps_for_applicant( $app_id ) {

		$app = kaamase_apps_get( $app_id );

		if ( ! $app ) {
			return null;
		}

		$status = kaamase_apps_status( $app->ID );
		$labels = kaamase_apps_labels( 'applicant' );

		return array(
			'id'           => (int) $app->ID,
			'status'       => $status,
			'status_label' => $labels[ $status ],
			'sent_at'      => (int) get_post_meta( $app->ID, kaamase_apps_key( 'app_sent_at' ), true ),
			'status_at'    => (int) get_post_meta( $app->ID, kaamase_apps_key( 'app_status_at' ), true ),
			'note'         => (string) get_post_meta( $app->ID, kaamase_apps_key( 'app_note' ), true ),
			'with_cv'      => (bool) get_post_meta( $app->ID, kaamase_apps_key( 'app_with_cv' ), true ),
			'can_withdraw' => in_array( $status, array( 'sent', 'seen', 'shortlisted' ), true ),
			'job'          => kaamase_apps_job_brief( (int) $app->post_parent ),
		);
	}
}

if ( ! function_exists( 'kaamase_apps_phone_of' ) ) {
	/**
	 * The applicant's number, ten digits, or ''.
	 *
	 * Their professional profile's, else the one on their account's
	 * other profile.
	 *
	 * @since 1.0.0
	 * @param int $user_id Applicant.
	 * @param int $profile Their professional profile.
	 * @return string
	 */
	function kaamase_apps_phone_of( $user_id, $profile ) {

		$numbers = array();

		if ( $profile && function_exists( 'kaamase_read_field' ) ) {
			$numbers[] = (string) kaamase_read_field( (int) $profile, 'phone' );
		}

		$main = (int) get_user_meta( (int) $user_id, 'kaamase_profile_id', true );

		if ( $main && function_exists( 'kaamase_read_field' ) ) {
			$numbers[] = (string) kaamase_read_field( $main, 'phone' );
		}

		foreach ( $numbers as $number ) {

			$digits = preg_replace( '/\D+/', '', $number );

			if ( strlen( $digits ) >= 10 ) {
				return substr( $digits, -10 );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_apps_for_employer' ) ) {
	/**
	 * One application, as the employer sees it.
	 *
	 * The whole professional profile, whatever its listing: applying
	 * shows it to this employer. With the phone and email, which the
	 * applicant agreed to share by sending.
	 *
	 * @since 1.0.0
	 * @param int $app_id Application.
	 * @return array|null
	 */
	function kaamase_apps_for_employer( $app_id ) {

		$app = kaamase_apps_get( $app_id );

		if ( ! $app ) {
			return null;
		}

		$user_id = (int) $app->post_author;
		$user    = get_userdata( $user_id );
		$profile = (int) get_post_meta( $app->ID, kaamase_apps_key( 'app_profile' ), true );
		$profile = ( $profile && get_post( $profile ) ) ? $profile : kaamase_prof_id( $user_id );
		$shaped  = ( $profile && function_exists( 'kaamase_prof_shape' ) ) ? kaamase_prof_shape( $profile, true ) : null;
		$status  = kaamase_apps_status( $app->ID );
		$labels  = kaamase_apps_labels( 'employer' );

		if ( is_array( $shaped ) ) {
			unset( $shaped['mine'], $shaped['is_mine'] );
		}

		$phone = kaamase_apps_phone_of( $user_id, $profile );

		return array(
			'id'           => (int) $app->ID,
			'job_id'       => (int) $app->post_parent,
			'status'       => $status,
			'status_label' => $labels[ $status ],
			'is_new'       => 'sent' === $status,
			'sent_at'      => (int) get_post_meta( $app->ID, kaamase_apps_key( 'app_sent_at' ), true ),
			'status_at'    => (int) get_post_meta( $app->ID, kaamase_apps_key( 'app_status_at' ), true ),
			'note'         => (string) get_post_meta( $app->ID, kaamase_apps_key( 'app_note' ), true ),
			'name'         => $shaped ? (string) $shaped['name'] : ( $user ? $user->display_name : '' ),
			'profile'      => $shaped,
			'contact'      => array(
				'phone'    => $phone,
				'whatsapp' => '' !== $phone ? 'https://wa.me/91' . $phone : '',
				'email'    => $user ? (string) $user->user_email : '',
			),
			// A link for whoever is looking now, and only if they may open it.
			'cv'           => function_exists( 'kaamase_cv_for_app' ) ? kaamase_cv_for_app( $app->ID, get_current_user_id() ) : null,
		);
	}
}


/* ==========================================================================
   4. TELLING PEOPLE
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_tell' ) ) {
	/**
	 * Tell somebody, in their own language, by whatever reaches them.
	 *
	 * @since 1.0.0
	 * @param int      $user_id Who.
	 * @param callable $write   Returns array( title, body, data, url ), called in their language.
	 * @return void
	 */
	function kaamase_apps_tell( $user_id, $write ) {

		if ( ! function_exists( 'kaamase_notify_user' ) || ! is_callable( $write ) ) {
			return;
		}

		$send = static function () use ( $user_id, $write ) {

			list( $title, $body, $data, $url ) = call_user_func( $write );

			kaamase_notify_user( (int) $user_id, $title, $body, $data, $url );
		};

		if ( function_exists( 'kaamase_locale_write_to' ) ) {
			kaamase_locale_write_to( (int) $user_id, $send );
		} else {
			$send();
		}
	}
}

if ( ! function_exists( 'kaamase_apps_tell_employer_first' ) ) {
	/**
	 * Tell the employer about the first person to apply for a job.
	 *
	 * Only the first. Everybody after waits for the day's one message,
	 * because fifty applications must not be fifty notifications.
	 *
	 * @since 1.0.0
	 * @param int $app_id Application.
	 * @return void
	 */
	function kaamase_apps_tell_employer_first( $app_id ) {

		$app = kaamase_apps_get( $app_id );

		if ( ! $app ) {
			return;
		}

		$job_id = (int) $app->post_parent;

		if ( get_post_meta( $job_id, kaamase_apps_key( 'apps_first_told' ), true ) ) {
			return;
		}

		update_post_meta( $job_id, kaamase_apps_key( 'apps_first_told' ), time() );
		update_post_meta( $app->ID, kaamase_apps_key( 'app_told' ), 1 );

		$employer = (int) get_post_field( 'post_author', $job_id );
		$name     = get_the_author_meta( 'display_name', (int) $app->post_author );

		kaamase_apps_tell(
			$employer,
			static function () use ( $job_id, $name ) {
				return array(
					__( 'Your first applicant', 'kaamase-core' ),
					sprintf(
						/* translators: 1: applicant's name, 2: job title */
						__( '%1$s has applied for %2$s. Open Kaam Ase to see their profile.', 'kaamase-core' ),
						$name,
						get_the_title( $job_id )
					),
					array(
						'type' => 'application_new',
						'job'  => $job_id,
					),
					kaamase_apps_list_url( $job_id ),
				);
			}
		);
	}
}

if ( ! function_exists( 'kaamase_apps_tell_applicant' ) ) {
	/**
	 * Tell somebody where their application now stands.
	 *
	 * @since 1.0.0
	 * @param int    $app_id Application.
	 * @param string $status shortlisted, not_selected or closed.
	 * @return void
	 */
	function kaamase_apps_tell_applicant( $app_id, $status ) {

		$app = kaamase_apps_get( $app_id );

		if ( ! $app ) {
			return;
		}

		$job_id = (int) $app->post_parent;
		$app_id = (int) $app->ID;

		kaamase_apps_tell(
			(int) $app->post_author,
			static function () use ( $job_id, $app_id, $status ) {

				$title    = get_the_title( $job_id );
				$employer = function_exists( 'kaamase_field' ) ? (string) kaamase_field( $job_id, 'employer_name' ) : '';
				$employer = '' !== $employer ? $employer : __( 'The employer', 'kaamase-core' );

				if ( 'shortlisted' === $status ) {
					$head = __( 'You are on the shortlist', 'kaamase-core' );
					/* translators: 1: employer, 2: job title */
					$body = sprintf( __( '%1$s has shortlisted you for %2$s. They may call or email you.', 'kaamase-core' ), $employer, $title );
				} elseif ( 'not_selected' === $status ) {
					$head = __( 'About your application', 'kaamase-core' );
					/* translators: 1: employer, 2: job title */
					$body = sprintf( __( '%1$s has chosen other people for %2$s this time. Keep applying: new jobs come in every day.', 'kaamase-core' ), $employer, $title );
				} else {
					$head = __( 'A job you applied for has closed', 'kaamase-core' );
					/* translators: %s: job title */
					$body = sprintf( __( '%s is no longer taking applications.', 'kaamase-core' ), $title );
				}

				return array(
					$head,
					$body,
					array(
						'type'        => 'application_status',
						'application' => $app_id,
						'job'         => $job_id,
						'status'      => $status,
					),
					kaamase_apps_list_url(),
				);
			}
		);
	}
}

if ( ! function_exists( 'kaamase_apps_daily_digest' ) ) {
	/**
	 * Once a day: how many more people applied, one message per employer.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_apps_daily_digest() {

		if ( ! kaamase_apps_ready() ) {
			return;
		}

		$waiting = kaamase_apps_query(
			array(
				'posts_per_page' => 1000,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => kaamase_apps_key( 'app_told' ),
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$by_employer = array();

		foreach ( $waiting as $app_id ) {

			// Whatever happens next, this one has had its turn.
			update_post_meta( $app_id, kaamase_apps_key( 'app_told' ), 1 );

			if ( 'sent' !== kaamase_apps_status( $app_id ) ) {
				continue;
			}

			$job_id = (int) wp_get_post_parent_id( $app_id );

			if ( ! $job_id || ! kaamase_job_is_open( $job_id ) ) {
				continue;
			}

			$employer = (int) get_post_field( 'post_author', $job_id );

			$by_employer[ $employer ][ $job_id ] = ( $by_employer[ $employer ][ $job_id ] ?? 0 ) + 1;
		}

		foreach ( $by_employer as $employer => $jobs ) {

			$count = (int) array_sum( $jobs );
			$only  = 1 === count( $jobs ) ? (int) array_key_first( $jobs ) : 0;

			kaamase_apps_tell(
				$employer,
				static function () use ( $count, $only ) {
					return array(
						__( 'New applicants', 'kaamase-core' ),
						$only
							? sprintf(
								/* translators: 1: how many, 2: job title */
								_n( '%1$s more person has applied for %2$s.', '%1$s more people have applied for %2$s.', $count, 'kaamase-core' ),
								number_format_i18n( $count ),
								get_the_title( $only )
							)
							: sprintf(
								/* translators: %s: how many */
								_n( '%s more person has applied for your jobs.', '%s more people have applied for your jobs.', $count, 'kaamase-core' ),
								number_format_i18n( $count )
							),
						array(
							'type' => 'application_new',
							'job'  => $only,
						),
						kaamase_apps_list_url( $only ),
					);
				}
			);
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_apps_daily_digest' );


/* ==========================================================================
   5. WHEN A JOB ENDS, AND WHAT IS KEPT
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_job_closed' ) ) {
	/**
	 * A job has stopped taking people: close what was still waiting.
	 *
	 * Shortlisted and not selected keep their answer; only the ones
	 * nobody answered become Job closed, and those people are told.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return void
	 */
	function kaamase_apps_job_closed( $job_id ) {

		$job_id = (int) $job_id;

		if ( ! $job_id || ! kaamase_apps_ready() || ! kaamase_apps_job_is_over( $job_id ) ) {
			return;
		}

		$waiting = kaamase_apps_of_job( $job_id, array( 'sent', 'seen' ) );
		$any     = $waiting || kaamase_apps_of_job( $job_id );

		if ( $any && ! get_post_meta( $job_id, kaamase_apps_key( 'apps_closed_at' ), true ) ) {
			update_post_meta( $job_id, kaamase_apps_key( 'apps_closed_at' ), time() );
		}

		foreach ( $waiting as $app_id ) {

			update_post_meta( $app_id, kaamase_apps_key( 'app_status' ), 'closed' );
			update_post_meta( $app_id, kaamase_apps_key( 'app_status_at' ), time() );
			update_post_meta( $app_id, kaamase_apps_key( 'app_told' ), 1 );

			kaamase_apps_tell_applicant( $app_id, 'closed' );
		}
	}
}
add_action( 'kaamase_job_filled', 'kaamase_apps_job_closed' );
add_action( 'kaamase_job_expired', 'kaamase_apps_job_closed' );

if ( ! function_exists( 'kaamase_apps_on_status' ) ) {
	/**
	 * Follow a job's status: closed, trashed or reopened.
	 *
	 * @since 1.0.0
	 * @param string  $new  New status.
	 * @param string  $old  Old status.
	 * @param WP_Post $post The post.
	 * @return void
	 */
	function kaamase_apps_on_status( $new, $old, $post ) {

		if ( ! $post instanceof WP_Post || 'kaamase_job' !== $post->post_type || $new === $old ) {
			return;
		}

		if ( 'publish' === $new ) {
			// Reposted: kept until six months after it closes again.
			delete_post_meta( $post->ID, kaamase_apps_key( 'apps_closed_at' ) );
			return;
		}

		if ( 'publish' === $old ) {
			kaamase_apps_job_closed( $post->ID );
		}
	}
}
add_action( 'transition_post_status', 'kaamase_apps_on_status', 20, 3 );

if ( ! function_exists( 'kaamase_apps_daily_sweep' ) ) {
	/**
	 * Once a day: close what a job ending quietly left waiting, and let
	 * go of what is old enough.
	 *
	 * A job whose date simply passes changes nothing until the daily
	 * expiry runs, and the order of two daily tasks is not something to
	 * rely on. So the waiting ones are checked here too.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_apps_daily_sweep() {

		if ( ! kaamase_apps_ready() ) {
			return;
		}

		$jobs = array();

		foreach ( kaamase_apps_query( array( 'posts_per_page' => 2000 ) ) as $app_id ) {

			$job_id = (int) wp_get_post_parent_id( $app_id );

			// An application whose job is gone goes with it.
			if ( ! $job_id || ! get_post( $job_id ) ) {
				wp_delete_post( $app_id, true );
				continue;
			}

			$jobs[ $job_id ] = true;
		}

		$cutoff = time() - ( (int) KAAMASE_APP_KEEP_DAYS * DAY_IN_SECONDS );

		foreach ( array_keys( $jobs ) as $job_id ) {

			if ( kaamase_apps_job_is_over( $job_id ) ) {
				kaamase_apps_job_closed( $job_id );
			}

			$closed_at = (int) get_post_meta( $job_id, kaamase_apps_key( 'apps_closed_at' ), true );

			if ( $closed_at && $closed_at < $cutoff ) {
				kaamase_apps_delete_of_job( $job_id );
			}
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_apps_daily_sweep', 20 );

if ( ! function_exists( 'kaamase_apps_delete_of_job' ) ) {
	/**
	 * Delete every application to one job.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return void
	 */
	function kaamase_apps_delete_of_job( $job_id ) {

		foreach ( kaamase_apps_query( array( 'post_parent' => (int) $job_id, 'posts_per_page' => -1 ) ) as $app_id ) {
			wp_delete_post( $app_id, true );
		}
	}
}

if ( ! function_exists( 'kaamase_apps_delete_of_user' ) ) {
	/**
	 * Delete every application one person has sent.
	 *
	 * @since 1.0.0
	 * @param int $user_id Applicant.
	 * @return int How many.
	 */
	function kaamase_apps_delete_of_user( $user_id ) {

		$ids = (int) $user_id ? kaamase_apps_query( array( 'author' => (int) $user_id, 'posts_per_page' => -1 ) ) : array();

		foreach ( $ids as $app_id ) {
			wp_delete_post( $app_id, true );
		}

		return count( $ids );
	}
}

if ( ! function_exists( 'kaamase_apps_on_delete_post' ) ) {
	/**
	 * A job deleted takes its applications; a professional profile
	 * deleted takes the applications its owner sent.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post being deleted.
	 * @return void
	 */
	function kaamase_apps_on_delete_post( $post_id ) {

		$type = get_post_type( (int) $post_id );

		if ( 'kaamase_job' === $type ) {
			kaamase_apps_delete_of_job( $post_id );
		} elseif ( defined( 'KAAMASE_PROF_TYPE' ) && KAAMASE_PROF_TYPE === $type ) {
			kaamase_apps_delete_of_user( (int) get_post_field( 'post_author', (int) $post_id ) );
		}
	}
}
add_action( 'before_delete_post', 'kaamase_apps_on_delete_post' );

if ( ! function_exists( 'kaamase_apps_on_delete_user' ) ) {
	/**
	 * An account deleted takes the applications it sent.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return void
	 */
	function kaamase_apps_on_delete_user( $user_id ) {
		kaamase_apps_delete_of_user( $user_id );
	}
}
add_action( 'delete_user', 'kaamase_apps_on_delete_user' );

if ( ! function_exists( 'kaamase_apps_register_exporter' ) ) {
	/**
	 * Applications in Tools → Export Personal Data.
	 *
	 * @since 1.0.0
	 * @param array $exporters Exporters.
	 * @return array
	 */
	function kaamase_apps_register_exporter( $exporters ) {

		$exporters['kaamase-applications'] = array(
			'exporter_friendly_name' => __( 'Kaam Ase job applications', 'kaamase-core' ),
			'callback'               => 'kaamase_apps_export',
		);

		return $exporters;
	}
}
add_filter( 'wp_privacy_personal_data_exporters', 'kaamase_apps_register_exporter' );

if ( ! function_exists( 'kaamase_apps_export' ) ) {
	/**
	 * Everything somebody sent as applications.
	 *
	 * @since 1.0.0
	 * @param string $email Their email.
	 * @param int    $page  Page.
	 * @return array
	 */
	function kaamase_apps_export( $email, $page = 1 ) {

		unset( $page );

		$user  = get_user_by( 'email', $email );
		$items = array();

		foreach ( $user ? kaamase_apps_of_user( $user->ID ) : array() as $app_id ) {

			$mine = kaamase_apps_for_applicant( $app_id );

			if ( ! $mine ) {
				continue;
			}

			$items[] = array(
				'group_id'    => 'kaamase-applications',
				'group_label' => __( 'Job applications', 'kaamase-core' ),
				'item_id'     => 'kaamase-application-' . $app_id,
				'data'        => array(
					array(
						'name'  => __( 'Job', 'kaamase-core' ),
						'value' => $mine['job']['title'],
					),
					array(
						'name'  => __( 'Sent', 'kaamase-core' ),
						'value' => $mine['sent_at'] ? wp_date( 'Y-m-d', $mine['sent_at'] ) : '',
					),
					array(
						'name'  => __( 'Where it stands', 'kaamase-core' ),
						'value' => $mine['status_label'],
					),
					array(
						'name'  => __( 'Note to the employer', 'kaamase-core' ),
						'value' => $mine['note'],
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}
}

if ( ! function_exists( 'kaamase_apps_register_eraser' ) ) {
	/**
	 * Applications in Tools → Erase Personal Data.
	 *
	 * @since 1.0.0
	 * @param array $erasers Erasers.
	 * @return array
	 */
	function kaamase_apps_register_eraser( $erasers ) {

		$erasers['kaamase-applications'] = array(
			'eraser_friendly_name' => __( 'Kaam Ase job applications', 'kaamase-core' ),
			'callback'             => 'kaamase_apps_erase',
		);

		return $erasers;
	}
}
add_filter( 'wp_privacy_personal_data_erasers', 'kaamase_apps_register_eraser' );

if ( ! function_exists( 'kaamase_apps_erase' ) ) {
	/**
	 * Delete everything somebody sent as applications.
	 *
	 * @since 1.0.0
	 * @param string $email Their email.
	 * @param int    $page  Page.
	 * @return array
	 */
	function kaamase_apps_erase( $email, $page = 1 ) {

		unset( $page );

		$user    = get_user_by( 'email', $email );
		$removed = $user ? kaamase_apps_delete_of_user( $user->ID ) : 0;

		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}


/* ==========================================================================
   6. THE REST OF THE PLATFORM
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_contact_veto' ) ) {
	/**
	 * On a job that takes applications, the employer's number stays private.
	 *
	 * Inside the one contact gate, so no route reaches it: the website's
	 * contact screen and the app's contact route alike. Before the daily
	 * count, so a refusal costs nothing. The employer themselves never
	 * gets here, and staff are let through.
	 *
	 * @since 1.0.0
	 * @param null|WP_Error $veto    Refusal so far.
	 * @param int           $post_id Profile or job being asked about.
	 * @param int           $user_id Who is asking.
	 * @return null|WP_Error
	 */
	function kaamase_apps_contact_veto( $veto, $post_id, $user_id ) {

		if ( is_wp_error( $veto ) || ! kaamase_apps_takes( $post_id ) || user_can( (int) $user_id, 'manage_options' ) ) {
			return $veto;
		}

		return new WP_Error(
			'kaamase_apply_only',
			__( 'This employer takes applications on Kaam Ase, not calls. Use Apply on the job to send your profile.', 'kaamase-core' )
		);
	}
}
add_filter( 'kaamase_contact_veto', 'kaamase_apps_contact_veto', 12, 3 );

if ( ! function_exists( 'kaamase_apps_contact_url' ) ) {
	/**
	 * Wherever a job's contact button points, an applications job points
	 * at the form instead.
	 *
	 * @since 1.0.0
	 * @param string $url     Destination so far.
	 * @param int    $post_id Job or profile.
	 * @return string
	 */
	function kaamase_apps_contact_url( $url, $post_id ) {
		return kaamase_apps_takes( $post_id ) ? kaamase_apps_apply_url( $post_id ) : $url;
	}
}
add_filter( 'kaamase_contact_url', 'kaamase_apps_contact_url', 20, 2 );

if ( ! function_exists( 'kaamase_apps_shape_job' ) ) {
	/**
	 * Tell the app how a job takes applications, and where the reader stands.
	 *
	 * Only on jobs that take them. Every other job is sent exactly as
	 * before.
	 *
	 * @since 1.0.0
	 * @param array   $out  Shaped job.
	 * @param WP_Post $post The job.
	 * @return array
	 */
	function kaamase_apps_shape_job( $out, $post ) {

		if ( ! $post instanceof WP_Post || ! kaamase_apps_takes( $post->ID ) ) {
			return $out;
		}

		$user_id = get_current_user_id();
		$mine    = $user_id ? kaamase_apps_find( $post->ID, $user_id ) : 0;
		$labels  = kaamase_apps_labels( 'applicant' );

		$out['contact_method'] = 'apply';

		$out['apply'] = array(
			'on_kaamase' => true,
			'open'       => kaamase_job_is_open( $post->ID ),
			'applied'    => $mine ? array(
				'id'           => $mine,
				'status'       => kaamase_apps_status( $mine ),
				'status_label' => $labels[ kaamase_apps_status( $mine ) ],
			) : null,
		);

		if ( $user_id && kaamase_apps_owns_job( $post->ID, $user_id ) ) {
			$out['apply']['applicants'] = kaamase_apps_counts( $post->ID );
		}

		return $out;
	}
}
add_filter( 'kaamase_shape_job', 'kaamase_apps_shape_job', 18, 2 );

if ( ! function_exists( 'kaamase_apps_shape_me' ) ) {
	/**
	 * The two numbers the app shows as a dot: applications still waiting,
	 * and new applicants on your own jobs.
	 *
	 * @since 1.0.0
	 * @param array $me      Shaped account.
	 * @param int   $user_id Account.
	 * @return array
	 */
	function kaamase_apps_shape_me( $me, $user_id ) {

		if ( ! is_array( $me ) || ! kaamase_apps_ready() ) {
			return $me;
		}

		$active = 0;

		foreach ( kaamase_apps_of_user( $user_id ) as $app_id ) {
			if ( in_array( kaamase_apps_status( $app_id ), array( 'sent', 'seen', 'shortlisted' ), true ) ) {
				$active++;
			}
		}

		$new  = 0;
		$jobs = kaamase_apps_employer_jobs( $user_id );

		foreach ( $jobs ? array_keys( kaamase_apps_parents( array( 'post_parent__in' => $jobs ) ) ) : array() as $app_id ) {
			if ( 'sent' === kaamase_apps_status( $app_id ) ) {
				$new++;
			}
		}

		$me['applications'] = array(
			'active'         => $active,
			'new_applicants' => $new,
		);

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_apps_shape_me', 20, 2 );


/* ==========================================================================
   7. THE APP'S ROUTES
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_routes' ) ) {
	/**
	 * The app's routes.
	 *
	 *   POST /jobs/{id}/apply                 send an application (note)
	 *   GET  /me/applications                 the ones you sent
	 *   POST /me/applications/{id}/withdraw   take one back
	 *   GET  /me/applicants                   your jobs that take applications, with counts
	 *   GET  /jobs/{id}/applicants            who applied for one of your jobs (?status=)
	 *   GET  /applications/{id}               one application, to either side
	 *   POST /applications/{id}/status        shortlisted, not_selected or seen
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_apps_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = function_exists( 'kaamase_rest_require_login' ) ? 'kaamase_rest_require_login' : 'is_user_logged_in';

		$routes = array(
			'/jobs/(?P<id>\d+)/apply'                 => array( 'POST', 'kaamase_apps_rest_apply' ),
			'/me/applications'                        => array( 'GET', 'kaamase_apps_rest_mine' ),
			'/me/applications/(?P<id>\d+)/withdraw'   => array( 'POST', 'kaamase_apps_rest_withdraw' ),
			'/me/applicants'                          => array( 'GET', 'kaamase_apps_rest_my_jobs' ),
			'/jobs/(?P<id>\d+)/applicants'            => array( 'GET', 'kaamase_apps_rest_applicants' ),
			'/applications/(?P<id>\d+)'               => array( 'GET', 'kaamase_apps_rest_one' ),
			'/applications/(?P<id>\d+)/status'        => array( 'POST', 'kaamase_apps_rest_status' ),
		);

		foreach ( $routes as $path => $route ) {
			register_rest_route(
				KAAMASE_REST_NS,
				$path,
				array(
					'methods'             => $route[0],
					'callback'            => $route[1],
					'permission_callback' => $auth,
				)
			);
		}
	}
}
add_action( 'rest_api_init', 'kaamase_apps_routes' );

if ( ! function_exists( 'kaamase_apps_rest_fail' ) ) {
	/**
	 * An error as the app expects it.
	 *
	 * @since 1.0.0
	 * @param WP_Error $error Error.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_apps_rest_fail( $error ) {

		if ( function_exists( 'kaamase_rest_error' ) ) {

			$response = kaamase_rest_error( $error );
			$data     = $error->get_error_data();

			if ( $response instanceof WP_REST_Response && ! empty( $data['application'] ) ) {
				$body                = $response->get_data();
				$body['application'] = kaamase_apps_for_applicant( (int) $data['application'] );
				$response->set_data( $body );
			}

			return $response;
		}

		return $error;
	}
}

if ( ! function_exists( 'kaamase_apps_rest_apply' ) ) {
	/**
	 * POST /jobs/{id}/apply
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_apps_rest_apply( $request ) {

		$with_cv = $request->has_param( 'with_cv' ) ? rest_sanitize_boolean( $request->get_param( 'with_cv' ) ) : null;
		$result  = kaamase_apps_apply( absint( $request['id'] ), get_current_user_id(), $request->get_param( 'note' ), $with_cv );

		if ( is_wp_error( $result ) ) {
			return kaamase_apps_rest_fail( $result );
		}

		return new WP_REST_Response( kaamase_apps_for_applicant( $result ), 201 );
	}
}

if ( ! function_exists( 'kaamase_apps_rest_mine' ) ) {
	/**
	 * GET /me/applications
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	function kaamase_apps_rest_mine() {

		$items = array();

		foreach ( kaamase_apps_of_user( get_current_user_id() ) as $app_id ) {

			$item = kaamase_apps_for_applicant( $app_id );

			if ( $item ) {
				$items[] = $item;
			}
		}

		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}
}

if ( ! function_exists( 'kaamase_apps_rest_withdraw' ) ) {
	/**
	 * POST /me/applications/{id}/withdraw
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_apps_rest_withdraw( $request ) {

		$id     = absint( $request['id'] );
		$result = kaamase_apps_withdraw( $id, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			return kaamase_apps_rest_fail( $result );
		}

		kaamase_apps_user_index( get_current_user_id(), true );

		return new WP_REST_Response( kaamase_apps_for_applicant( $id ), 200 );
	}
}

if ( ! function_exists( 'kaamase_apps_rest_my_jobs' ) ) {
	/**
	 * GET /me/applicants
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	function kaamase_apps_rest_my_jobs() {

		$items = array();

		foreach ( kaamase_apps_employer_jobs( get_current_user_id() ) as $job_id ) {
			$items[] = array(
				'job'        => kaamase_apps_job_brief( $job_id ),
				'takes'      => kaamase_apps_takes( $job_id ),
				'applicants' => kaamase_apps_counts( $job_id ),
			);
		}

		return new WP_REST_Response( array( 'items' => $items ), 200 );
	}
}

if ( ! function_exists( 'kaamase_apps_rest_applicants' ) ) {
	/**
	 * GET /jobs/{id}/applicants?status=all|new|shortlisted|not_selected
	 *
	 * Everyone returned as New is Seen from then on; each carries is_new
	 * as it was, so the screen can still mark them this once.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_apps_rest_applicants( $request ) {

		$job_id = absint( $request['id'] );

		if ( ! kaamase_apps_owns_job( $job_id, get_current_user_id() ) ) {
			return kaamase_apps_rest_fail( new WP_Error( 'kaamase_apply_not_yours', __( 'This list is for the employer who posted the job.', 'kaamase-core' ), array( 'status' => 403 ) ) );
		}

		$ids    = kaamase_apps_filtered( $job_id, sanitize_key( (string) $request->get_param( 'status' ) ) );
		$items  = array_values( array_filter( array_map( 'kaamase_apps_for_employer', $ids ) ) );
		$counts = kaamase_apps_counts( $job_id );

		// Counted before, like the items: the New tab says how many were new on this look.
		kaamase_apps_mark_seen( $ids );

		return new WP_REST_Response(
			array(
				'job'        => kaamase_apps_job_brief( $job_id ),
				'applicants' => $counts,
				'items'      => $items,
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_apps_filtered' ) ) {
	/**
	 * Applications to a job, narrowed to one answer.
	 *
	 * @since 1.0.0
	 * @param int    $job_id Job.
	 * @param string $show   all, new, shortlisted or not_selected.
	 * @return int[]
	 */
	function kaamase_apps_filtered( $job_id, $show ) {

		$map = array(
			'new'          => array( 'sent' ),
			'shortlisted'  => array( 'shortlisted' ),
			'not_selected' => array( 'not_selected' ),
		);

		return kaamase_apps_of_job( $job_id, $map[ $show ] ?? array() );
	}
}

if ( ! function_exists( 'kaamase_apps_rest_one' ) ) {
	/**
	 * GET /applications/{id}
	 *
	 * The applicant gets their view, the employer theirs; opening a new
	 * one marks it Seen.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_apps_rest_one( $request ) {

		$app     = kaamase_apps_get( absint( $request['id'] ) );
		$user_id = get_current_user_id();

		if ( $app && (int) $app->post_author === $user_id ) {
			return new WP_REST_Response( kaamase_apps_for_applicant( $app->ID ), 200 );
		}

		if ( $app && 'withdrawn' !== kaamase_apps_status( $app->ID ) && kaamase_apps_owns_job( (int) $app->post_parent, $user_id ) ) {

			$item = kaamase_apps_for_employer( $app->ID );

			kaamase_apps_mark_seen( array( $app->ID ) );

			return new WP_REST_Response( $item, 200 );
		}

		return kaamase_apps_rest_fail( new WP_Error( 'kaamase_apply_missing', __( 'That application was not found.', 'kaamase-core' ), array( 'status' => 404 ) ) );
	}
}

if ( ! function_exists( 'kaamase_apps_rest_status' ) ) {
	/**
	 * POST /applications/{id}/status  status=shortlisted|not_selected|seen
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_apps_rest_status( $request ) {

		$id     = absint( $request['id'] );
		$result = kaamase_apps_decide( $id, (string) $request->get_param( 'status' ), get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			return kaamase_apps_rest_fail( $result );
		}

		return new WP_REST_Response( kaamase_apps_for_employer( $id ), 200 );
	}
}


/* ==========================================================================
   8. THE TWO PAGES
   ========================================================================== */

if ( ! function_exists( 'kaamase_apps_pages' ) ) {
	/**
	 * Add the two pages to the platform's pages.
	 *
	 * @since 1.0.0
	 * @param array[] $pages Page definitions.
	 * @return array[]
	 */
	function kaamase_apps_pages( $pages ) {

		$pages['apply_job'] = array(
			'title'   => __( 'Apply for a job', 'kaamase-core' ),
			'slug'    => 'apply-for-job',
			'content' => '[kaamase_apply]',
		);

		$pages['applications'] = array(
			'title'   => __( 'Applications', 'kaamase-core' ),
			'slug'    => 'job-applications',
			'content' => '[kaamase_applications]',
		);

		return $pages;
	}
}
add_filter( 'kaamase_page_definitions', 'kaamase_apps_pages' );

if ( ! function_exists( 'kaamase_apps_make_pages' ) ) {
	/**
	 * Create the two pages, once.
	 *
	 * A page already at the address is taken over only if it already
	 * holds the form. Otherwise it is somebody else's page, and WordPress
	 * gives ours the next free address instead.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_apps_make_pages() {

		if ( get_option( 'kaamase_apps_pages_made' ) || ! kaamase_apps_ready() ) {
			return;
		}

		update_option( 'kaamase_apps_pages_made', time(), true );

		$stored = (array) get_option( 'kaamase_pages', array() );

		foreach ( kaamase_apps_pages( array() ) as $name => $page ) {

			if ( ! empty( $stored[ $name ] ) && 'page' === get_post_type( (int) $stored[ $name ] ) ) {
				continue;
			}

			$existing = get_page_by_path( $page['slug'] );

			if ( $existing instanceof WP_Post && false !== strpos( (string) $existing->post_content, $page['content'] ) ) {
				$stored[ $name ] = (int) $existing->ID;
				continue;
			}

			$id = wp_insert_post(
				array(
					'post_title'     => $page['title'],
					'post_name'      => $page['slug'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			if ( ! $id || is_wp_error( $id ) ) {
				delete_option( 'kaamase_apps_pages_made' );
				return;
			}

			$stored[ $name ] = (int) $id;
		}

		update_option( 'kaamase_pages', $stored, true );

		if ( function_exists( 'kaamase_page_url_flush' ) ) {
			kaamase_page_url_flush();
		}
	}
}
add_action( 'init', 'kaamase_apps_make_pages', 32 );

if ( ! function_exists( 'kaamase_apps_page_id' ) ) {
	/**
	 * One of the two pages' IDs, or 0.
	 *
	 * @since 1.0.0
	 * @param string $name apply_job or applications.
	 * @return int
	 */
	function kaamase_apps_page_id( $name ) {

		$stored = (array) get_option( 'kaamase_pages', array() );

		return isset( $stored[ $name ] ) ? (int) $stored[ $name ] : 0;
	}
}

if ( ! function_exists( 'kaamase_apps_apply_url' ) ) {
	/**
	 * The form for one job.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return string
	 */
	function kaamase_apps_apply_url( $job_id ) {

		$base = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'apply_job' ) : home_url( '/apply-for-job/' );

		return add_query_arg( 'job', (int) $job_id, $base );
	}
}

if ( ! function_exists( 'kaamase_apps_list_url' ) ) {
	/**
	 * Your applications, or with a job, the applicants for it.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job, or 0.
	 * @return string
	 */
	function kaamase_apps_list_url( $job_id = 0 ) {

		$base = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'applications' ) : home_url( '/job-applications/' );

		return $job_id ? add_query_arg( 'job', (int) $job_id, $base ) : $base;
	}
}

if ( ! function_exists( 'kaamase_apps_private_pages' ) ) {
	/**
	 * Keep both pages out of search engines.
	 *
	 * @since 1.0.0
	 * @param int[] $ids Pages kept out of search.
	 * @return int[]
	 */
	function kaamase_apps_private_pages( $ids ) {

		foreach ( array( 'apply_job', 'applications' ) as $name ) {

			$id = kaamase_apps_page_id( $name );

			if ( $id ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}
}
add_filter( 'kaamase_seo_private_pages', 'kaamase_apps_private_pages' );

if ( ! function_exists( 'kaamase_apps_no_store' ) ) {
	/**
	 * Keep both pages out of every cache: what they show is one person's.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_apps_no_store() {

		$pages = array_filter( array( kaamase_apps_page_id( 'apply_job' ), kaamase_apps_page_id( 'applications' ) ) );

		if ( ! $pages || ! is_page( $pages ) ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'kaamase applications' );

		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}
}
add_action( 'template_redirect', 'kaamase_apps_no_store', 2 );

if ( ! function_exists( 'kaamase_apps_rank_math_robots' ) ) {
	/**
	 * Noindex for Rank Math too, which writes its own robots tag. The
	 * private pages filter above covers WordPress's.
	 *
	 * @since 1.0.0
	 * @param array $robots Directives keyed by name.
	 * @return array
	 */
	function kaamase_apps_rank_math_robots( $robots ) {

		$pages = array_filter( array( kaamase_apps_page_id( 'apply_job' ), kaamase_apps_page_id( 'applications' ) ) );

		if ( $pages && is_page( $pages ) ) {

			$robots = is_array( $robots ) ? $robots : array();

			unset( $robots['index'], $robots['follow'] );

			$robots['index']  = 'noindex';
			$robots['follow'] = 'nofollow';
		}

		return $robots;
	}
}
add_filter( 'rank_math/frontend/robots', 'kaamase_apps_rank_math_robots', 20 );


/* ==========================================================================
   9. ON THE WEBSITE
   ========================================================================== */

if ( ! function_exists( 'kaamase_apply_button' ) ) {
	/**
	 * The job page's button, for a job that takes applications.
	 *
	 * Empty for every other job, so the theme draws its usual button.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return string Markup.
	 */
	function kaamase_apply_button( $job_id ) {

		$job_id = (int) $job_id;

		if ( ! kaamase_apps_takes( $job_id ) ) {
			return '';
		}

		if ( ! kaamase_job_is_open( $job_id ) ) {
			return '<span class="ka-small ka-soft">' . esc_html__( 'No longer taking applications', 'kaamase-core' ) . '</span>';
		}

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<a class="ka-btn ka-btn--outline ka-btn--sm" href="%1$s" rel="nofollow">%2$s</a>',
				esc_url( wp_login_url( kaamase_apps_apply_url( $job_id ) ) ),
				esc_html__( 'Sign in to apply', 'kaamase-core' )
			);
		}

		$mine = kaamase_apps_find( $job_id, get_current_user_id() );

		if ( $mine && ! in_array( kaamase_apps_status( $mine ), array( 'withdrawn', 'closed' ), true ) ) {
			return sprintf(
				'<a class="ka-btn ka-btn--outline ka-btn--sm" href="%1$s" rel="nofollow">%2$s</a>',
				esc_url( kaamase_apps_list_url() ),
				esc_html(
					sprintf(
						/* translators: %s: where the application stands */
						__( 'Applied: %s', 'kaamase-core' ),
						kaamase_apps_labels( 'applicant' )[ kaamase_apps_status( $mine ) ]
					)
				)
			);
		}

		return sprintf(
			'<a class="ka-btn ka-btn--action ka-btn--sm" href="%1$s" rel="nofollow">%2$s</a>',
			esc_url( kaamase_apps_apply_url( $job_id ) ),
			esc_html__( 'Apply on Kaam Ase', 'kaamase-core' )
		);
	}
}

if ( ! function_exists( 'kaamase_apps_notice' ) ) {
	/**
	 * A notice in the platform's style.
	 *
	 * @since 1.0.0
	 * @param string $kind   info, warn, ok or error.
	 * @param string $title  Heading.
	 * @param string $body   Sentence, or ''.
	 * @param string $url    Button address, or ''.
	 * @param string $button Button label, or ''.
	 * @return string
	 */
	function kaamase_apps_notice( $kind, $title, $body = '', $url = '', $button = '' ) {

		if ( function_exists( 'kaamase_prof_notice' ) ) {
			return kaamase_prof_notice( $kind, $title, $body, $url, $button );
		}

		return '<div class="ka-notice ka-notice--' . esc_attr( $kind ) . ' ka-mb-4"><div><span class="ka-notice__title">' . esc_html( $title ) . '</span>' . ( '' !== $body ? '<p>' . esc_html( $body ) . '</p>' : '' ) . '</div></div>';
	}
}

if ( ! function_exists( 'kaamase_apps_flash_key' ) ) {
	/**
	 * Where a message waits for the page the person lands on next.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_apps_flash_key() {
		return 'kaamase_apps_flash_' . get_current_user_id();
	}
}

if ( ! function_exists( 'kaamase_apps_flash' ) ) {
	/**
	 * Leave a message for the next page.
	 *
	 * @since 1.0.0
	 * @param string $kind  ok or error.
	 * @param string $title Heading.
	 * @param string $body  Sentence.
	 * @return void
	 */
	function kaamase_apps_flash( $kind, $title, $body = '' ) {
		set_transient( kaamase_apps_flash_key(), array( $kind, $title, $body ), 10 * MINUTE_IN_SECONDS );
	}
}

if ( ! function_exists( 'kaamase_apps_flash_show' ) ) {
	/**
	 * The waiting message, once.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_apps_flash_show() {

		$flash = get_transient( kaamase_apps_flash_key() );

		if ( ! is_array( $flash ) || 3 !== count( $flash ) ) {
			return '';
		}

		delete_transient( kaamase_apps_flash_key() );

		return kaamase_apps_notice( (string) $flash[0], (string) $flash[1], (string) $flash[2] );
	}
}

if ( ! function_exists( 'kaamase_apps_handle_forms' ) ) {
	/**
	 * The website's three buttons: apply, withdraw, and the employer's answer.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_apps_handle_forms() {

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked below, per action.
		$action = isset( $_POST['kaamase_action'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_action'] ) ) : '';

		if ( ! in_array( $action, array( 'apply_job', 'withdraw_application', 'decide_application' ), true ) || ! is_user_logged_in() ) {
			return;
		}

		$id    = isset( $_POST['kaamase_id'] ) ? absint( $_POST['kaamase_id'] ) : 0;
		$nonce = isset( $_POST['kaamase_apps_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_apps_nonce'] ) ) : '';

		if ( ! $id || ! wp_verify_nonce( $nonce, 'kaamase_' . $action . '_' . $id ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( 'apply_job' === $action ) {

			$note    = isset( $_POST['kaamase_note'] ) ? wp_unslash( $_POST['kaamase_note'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Cleaned in kaamase_apps_clean_note().
			$with_cv = ! empty( $_POST['kaamase_with_cv'] );
			$result  = null;

			// A CV chosen on the form is saved first, and goes with this application.
			if ( ! empty( $_FILES['kaamase_cv']['name'] ) && function_exists( 'kaamase_cv_save' ) ) {

				$saved = kaamase_cv_save( $user_id, $_FILES['kaamase_cv'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checked in kaamase_cv_save().

				if ( is_wp_error( $saved ) ) {
					$result = $saved;
				} else {
					$with_cv = true;
				}
			}

			$result = $result ? $result : kaamase_apps_apply( $id, $user_id, $note, $with_cv );

			if ( is_wp_error( $result ) ) {
				set_transient( kaamase_apps_flash_key() . '_note', kaamase_apps_clean_note( $note ), 10 * MINUTE_IN_SECONDS );
				kaamase_apps_flash( 'error', __( 'Not sent', 'kaamase-core' ), $result->get_error_message() );
				wp_safe_redirect( kaamase_apps_apply_url( $id ) );
				exit;
			}

			kaamase_apps_flash(
				'ok',
				__( 'Application sent', 'kaamase-core' ),
				sprintf(
					/* translators: %s: job title */
					__( 'Your application for %s has gone to the employer. We will tell you when they answer.', 'kaamase-core' ),
					get_the_title( $id )
				)
			);

			wp_safe_redirect( kaamase_apps_list_url() );
			exit;
		}

		if ( 'withdraw_application' === $action ) {

			$result = kaamase_apps_withdraw( $id, $user_id );

			if ( is_wp_error( $result ) ) {
				kaamase_apps_flash( 'error', __( 'Not withdrawn', 'kaamase-core' ), $result->get_error_message() );
			} else {
				kaamase_apps_flash( 'ok', __( 'Application withdrawn', 'kaamase-core' ), __( 'The employer will not see it any more.', 'kaamase-core' ) );
			}

			wp_safe_redirect( kaamase_apps_list_url() );
			exit;
		}

		$status = isset( $_POST['kaamase_status'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_status'] ) ) : '';
		$show   = isset( $_POST['kaamase_show'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_show'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$result = kaamase_apps_decide( $id, $status, $user_id );
		$job_id = (int) wp_get_post_parent_id( $id );

		if ( is_wp_error( $result ) ) {
			kaamase_apps_flash( 'error', __( 'Not saved', 'kaamase-core' ), $result->get_error_message() );
		}

		$back = kaamase_apps_list_url( $job_id );
		$back = in_array( $show, array( 'new', 'shortlisted', 'not_selected' ), true ) ? add_query_arg( 'show', $show, $back ) : $back;

		wp_safe_redirect( $back . '#application-' . $id );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_apps_handle_forms' );

if ( ! function_exists( 'kaamase_apps_hidden' ) ) {
	/**
	 * The hidden fields every one of the three forms carries.
	 *
	 * @since 1.0.0
	 * @param string $action apply_job, withdraw_application or decide_application.
	 * @param int    $id     Job or application.
	 * @return string
	 */
	function kaamase_apps_hidden( $action, $id ) {

		return '<input type="hidden" name="kaamase_action" value="' . esc_attr( $action ) . '">'
			. '<input type="hidden" name="kaamase_id" value="' . esc_attr( (string) (int) $id ) . '">'
			. wp_nonce_field( 'kaamase_' . $action . '_' . (int) $id, 'kaamase_apps_nonce', false, false );
	}
}

if ( ! function_exists( 'kaamase_apps_when' ) ) {
	/**
	 * "3 days ago", or a date once it is more than a week.
	 *
	 * @since 1.0.0
	 * @param int $time Timestamp.
	 * @return string
	 */
	function kaamase_apps_when( $time ) {

		$time = (int) $time;

		if ( ! $time ) {
			return '';
		}

		if ( time() - $time < WEEK_IN_SECONDS ) {
			/* translators: %s: how long ago, e.g. 3 days */
			return sprintf( __( '%s ago', 'kaamase-core' ), human_time_diff( $time ) );
		}

		return wp_date( 'j M Y', $time );
	}
}

if ( ! function_exists( 'kaamase_apps_badge' ) ) {
	/**
	 * A state as a small badge.
	 *
	 * @since 1.0.0
	 * @param string $status State.
	 * @param string $side   applicant or employer.
	 * @return string
	 */
	function kaamase_apps_badge( $status, $side ) {

		$class = 'ka-badge--neutral';

		if ( 'shortlisted' === $status ) {
			$class = 'ka-badge--verified';
		} elseif ( 'sent' === $status && 'employer' === $side ) {
			$class = 'ka-badge--new';
		}

		return '<span class="ka-badge ' . esc_attr( $class ) . '">' . esc_html( kaamase_apps_labels( $side )[ $status ] ) . '</span>';
	}
}

if ( ! function_exists( 'kaamase_apps_apply_shortcode' ) ) {
	/**
	 * The apply page: [kaamase_apply], for ?job=ID.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_apps_apply_shortcode() {

		if ( ! kaamase_apps_ready() ) {
			return '';
		}

		$job_id  = isset( $_GET['job'] ) ? absint( $_GET['job'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which job, nothing changes.
		$user_id = get_current_user_id();

		ob_start();

		echo kaamase_apps_flash_show(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped where built.

		if ( ! kaamase_apps_takes( $job_id ) ) {
			echo kaamase_apps_notice( 'info', __( 'This job does not take applications here', 'kaamase-core' ), __( 'Open the job to see how to apply.', 'kaamase-core' ), home_url( '/jobs/' ), __( 'Find work', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return (string) ob_get_clean();
		}

		if ( ! $user_id ) {
			echo kaamase_apps_notice( 'info', __( 'Sign in to apply', 'kaamase-core' ), __( 'Use the account you already have. If you do not have one yet, register free first.', 'kaamase-core' ), wp_login_url( kaamase_apps_apply_url( $job_id ) ), __( 'Sign in', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return (string) ob_get_clean();
		}

		if ( (int) get_post_field( 'post_author', $job_id ) === $user_id ) {
			echo kaamase_apps_notice( 'info', __( 'This is your own job', 'kaamase-core' ), __( 'Everybody who applies is on your applicants list.', 'kaamase-core' ), kaamase_apps_list_url( $job_id ), __( 'See applicants', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return (string) ob_get_clean();
		}

		$refusal = kaamase_apps_refusal( $job_id, $user_id );
		$job     = kaamase_apps_job_brief( $job_id );
		?>
		<div class="ka-stack">

			<section class="ka-card ka-card--flat">
				<p class="ka-label"><?php esc_html_e( 'Applying for', 'kaamase-core' ); ?></p>
				<h2><a href="<?php echo esc_url( $job['url'] ); ?>"><?php echo esc_html( $job['title'] ); ?></a></h2>
				<?php if ( '' !== $job['employer'] ) : ?>
					<p class="ka-soft"><?php echo esc_html( $job['employer'] ); ?></p>
				<?php endif; ?>
				<?php if ( function_exists( 'kaamase_job_wage' ) ) : ?>
					<p class="ka-mt-4"><?php echo kaamase_job_wage( $job_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped where built. ?></p>
				<?php endif; ?>
			</section>

			<?php
			if ( is_wp_error( $refusal ) ) {

				$code = $refusal->get_error_code();
				$url  = '';
				$btn  = '';

				if ( in_array( $code, array( 'kaamase_apply_no_profile', 'kaamase_apply_unfinished' ), true ) && function_exists( 'kaamase_prof_url' ) ) {
					$url = kaamase_prof_url( 'my_professional' );
					$btn = 'kaamase_apply_no_profile' === $code ? __( 'Make my professional profile', 'kaamase-core' ) : __( 'Finish my profile', 'kaamase-core' );
				} elseif ( 'kaamase_apply_already' === $code ) {
					$url = kaamase_apps_list_url();
					$btn = __( 'My applications', 'kaamase-core' );
				}

				echo kaamase_apps_notice( 'kaamase_apply_already' === $code ? 'ok' : 'warn', $refusal->get_error_message(), '', $url, $btn ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			} else {

				$profile = kaamase_prof_id( $user_id );
				$shaped  = function_exists( 'kaamase_prof_shape' ) ? kaamase_prof_shape( $profile ) : null;
				$cv      = function_exists( 'kaamase_cv_of' ) ? kaamase_cv_of( $user_id ) : null;
				$typed   = (string) get_transient( kaamase_apps_flash_key() . '_note' );

				delete_transient( kaamase_apps_flash_key() . '_note' );
				?>
				<section class="ka-card ka-card--flat">
					<p class="ka-label"><?php esc_html_e( 'The employer will see your professional profile', 'kaamase-core' ); ?></p>
					<div class="ka-cluster ka-mt-4">
						<?php echo function_exists( 'kaamase_avatar' ) ? kaamase_avatar( $profile ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div>
							<strong><?php echo esc_html( $shaped ? $shaped['name'] : '' ); ?></strong>
							<?php if ( $shaped && '' !== $shaped['headline'] ) : ?>
								<p class="ka-small ka-soft"><?php echo esc_html( $shaped['headline'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<?php if ( function_exists( 'kaamase_prof_url' ) ) : ?>
						<p class="ka-small ka-mt-4"><a href="<?php echo esc_url( kaamase_prof_url( 'my_professional' ) ); ?>"><?php esc_html_e( 'Check or change it first', 'kaamase-core' ); ?></a></p>
					<?php endif; ?>
				</section>

				<form method="post" class="ka-card ka-card--flat" enctype="multipart/form-data">
					<?php echo kaamase_apps_hidden( 'apply_job', $job_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped where built. ?>

					<div class="ka-field">
						<label class="ka-label" for="ka-apply-note"><?php esc_html_e( 'A short note to the employer (optional)', 'kaamase-core' ); ?></label>
						<textarea class="ka-textarea" id="ka-apply-note" name="kaamase_note" rows="5" maxlength="<?php echo esc_attr( (string) (int) KAAMASE_APP_NOTE_MAX ); ?>"
							placeholder="<?php esc_attr_e( 'Why you suit this job, and when you can start.', 'kaamase-core' ); ?>"><?php echo esc_textarea( $typed ); ?></textarea>
					</div>

					<?php if ( function_exists( 'kaamase_cv_save' ) ) : ?>
						<div class="ka-field">
							<?php if ( $cv ) : ?>
								<label class="ka-check">
									<input type="checkbox" name="kaamase_with_cv" value="1" checked>
									<span>
										<?php
										/* translators: %s: the CV's file name */
										echo esc_html( sprintf( __( 'Send my CV (%s)', 'kaamase-core' ), $cv['name'] ) );
										?>
									</span>
								</label>
								<label class="ka-label ka-mt-4" for="ka-apply-cv"><?php esc_html_e( 'Or send a different one', 'kaamase-core' ); ?></label>
							<?php else : ?>
								<label class="ka-label" for="ka-apply-cv"><?php esc_html_e( 'Your CV (optional)', 'kaamase-core' ); ?></label>
							<?php endif; ?>
							<input class="ka-input" type="file" id="ka-apply-cv" name="kaamase_cv" accept="application/pdf,image/jpeg,image/png,image/webp">
							<p class="ka-hint"><?php esc_html_e( 'A PDF, or a photo of your paper CV, up to 5 MB. It is kept with your professional profile for your next application, and you can delete it any time.', 'kaamase-core' ); ?></p>
						</div>
					<?php endif; ?>

					<p class="ka-hint">
						<?php esc_html_e( 'The employer will see your professional profile, this note, your CV if you send it, your phone number and your email address. Nobody else will. Never pay anybody to apply for a job.', 'kaamase-core' ); ?>
					</p>

					<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block ka-mt-4" type="submit"><?php esc_html_e( 'Send application', 'kaamase-core' ); ?></button>
				</form>
				<?php
			}
			?>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_apply', 'kaamase_apps_apply_shortcode' );

if ( ! function_exists( 'kaamase_apps_list_shortcode' ) ) {
	/**
	 * The applications page: [kaamase_applications].
	 *
	 * With ?job=ID and to the employer who posted it, the applicants for
	 * that job. Otherwise the applications you sent, and, when you hire,
	 * your jobs that take applications.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_apps_list_shortcode() {

		if ( ! kaamase_apps_ready() ) {
			return '';
		}

		$user_id = get_current_user_id();
		$job_id  = isset( $_GET['job'] ) ? absint( $_GET['job'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which job, nothing changes.

		if ( ! $user_id ) {
			return kaamase_apps_notice( 'info', __( 'Sign in to see your applications', 'kaamase-core' ), '', wp_login_url( kaamase_apps_list_url( $job_id ) ), __( 'Sign in', 'kaamase-core' ) );
		}

		if ( $job_id ) {

			if ( ! kaamase_apps_owns_job( $job_id, $user_id ) ) {
				return kaamase_apps_notice( 'warn', __( 'This list is for the employer who posted the job.', 'kaamase-core' ) );
			}

			return kaamase_apps_flash_show() . kaamase_apps_applicants_view( $job_id );
		}

		$out  = kaamase_apps_flash_show();
		$out .= kaamase_apps_mine_view( $user_id );
		$out .= kaamase_apps_employer_view( $user_id );

		return $out;
	}
}
add_shortcode( 'kaamase_applications', 'kaamase_apps_list_shortcode' );

if ( ! function_exists( 'kaamase_apps_mine_view' ) ) {
	/**
	 * The applications somebody sent.
	 *
	 * @since 1.0.0
	 * @param int $user_id Applicant.
	 * @return string
	 */
	function kaamase_apps_mine_view( $user_id ) {

		$ids   = kaamase_apps_of_user( $user_id );
		$hires = function_exists( 'user_can' ) && user_can( $user_id, 'create_kaamase_jobs' ) && kaamase_apps_employer_jobs( $user_id );

		// An employer who never applied for anything does not need to be told so.
		if ( ! $ids && $hires ) {
			return '';
		}

		ob_start();
		?>
		<section class="ka-stack">
			<h2><?php esc_html_e( 'My applications', 'kaamase-core' ); ?></h2>

			<?php if ( ! $ids ) : ?>
				<p class="ka-soft">
					<?php esc_html_e( 'You have not applied for anything yet. On a professional job that says Apply on Kaam Ase, you can send your professional profile in one tap.', 'kaamase-core' ); ?>
				</p>
				<?php if ( function_exists( 'kaamase_menu_professional_url' ) && '' !== kaamase_menu_professional_url() ) : ?>
					<p><a class="ka-btn ka-btn--outline" href="<?php echo esc_url( kaamase_menu_professional_url() ); ?>"><?php esc_html_e( 'See professional jobs', 'kaamase-core' ); ?></a></p>
				<?php endif; ?>
			<?php else : ?>
				<ul class="ka-stack">
					<?php foreach ( $ids as $app_id ) : ?>
						<?php
						$app = kaamase_apps_for_applicant( $app_id );

						if ( ! $app ) {
							continue;
						}
						?>
						<li class="ka-card ka-card--flat" id="application-<?php echo esc_attr( (string) $app_id ); ?>">
							<div class="ka-cluster ka-cluster--between">
								<strong>
									<?php if ( '' !== $app['job']['url'] ) : ?>
										<a href="<?php echo esc_url( $app['job']['url'] ); ?>"><?php echo esc_html( $app['job']['title'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $app['job']['title'] ); ?>
									<?php endif; ?>
								</strong>
								<?php echo kaamase_apps_badge( $app['status'], 'applicant' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<p class="ka-small ka-soft ka-mt-4">
								<?php
								echo esc_html(
									trim(
										( '' !== $app['job']['employer'] ? $app['job']['employer'] . ' · ' : '' )
										/* translators: %s: when, e.g. 3 days ago */
										. sprintf( __( 'Sent %s', 'kaamase-core' ), kaamase_apps_when( $app['sent_at'] ) )
										. ( $app['with_cv'] ? ' · ' . __( 'with your CV', 'kaamase-core' ) : '' )
									)
								);
								?>
							</p>
							<?php if ( $app['can_withdraw'] ) : ?>
								<form method="post" class="ka-mt-4">
									<?php echo kaamase_apps_hidden( 'withdraw_application', $app_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<button class="ka-btn ka-btn--outline ka-btn--sm" type="submit"><?php esc_html_e( 'Withdraw', 'kaamase-core' ); ?></button>
								</form>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_apps_employer_view' ) ) {
	/**
	 * An employer's jobs that take applications, each with its count.
	 *
	 * @since 1.0.0
	 * @param int $user_id Employer.
	 * @return string
	 */
	function kaamase_apps_employer_view( $user_id ) {

		$jobs = kaamase_apps_employer_jobs( $user_id );

		if ( ! $jobs ) {
			return '';
		}

		ob_start();
		?>
		<section class="ka-stack ka-mt-6">
			<h2><?php esc_html_e( 'Applicants for your jobs', 'kaamase-core' ); ?></h2>
			<ul class="ka-stack">
				<?php foreach ( $jobs as $job_id ) : ?>
					<?php
					$counts = kaamase_apps_counts( $job_id );
					$brief  = kaamase_apps_job_brief( $job_id );
					?>
					<li class="ka-card ka-card--flat">
						<div class="ka-cluster ka-cluster--between">
							<strong><?php echo esc_html( $brief['title'] ); ?></strong>
							<?php if ( $counts['new'] ) : ?>
								<span class="ka-badge ka-badge--new">
									<?php
									/* translators: %s: how many new applicants */
									echo esc_html( sprintf( __( '%s new', 'kaamase-core' ), number_format_i18n( $counts['new'] ) ) );
									?>
								</span>
							<?php endif; ?>
						</div>
						<p class="ka-small ka-soft ka-mt-4">
							<?php
							/* translators: %s: how many applicants */
							echo esc_html( sprintf( _n( '%s applicant', '%s applicants', $counts['total'], 'kaamase-core' ), number_format_i18n( $counts['total'] ) ) );

							if ( ! $brief['open'] ) {
								echo ' · ' . esc_html__( 'Job closed', 'kaamase-core' );
							}
							?>
						</p>
						<?php if ( $counts['total'] ) : ?>
							<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="<?php echo esc_url( kaamase_apps_list_url( $job_id ) ); ?>"><?php esc_html_e( 'See applicants', 'kaamase-core' ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_apps_applicants_view' ) ) {
	/**
	 * The applicants for one job, to its employer.
	 *
	 * Drawn first, marked Seen after: the ones that were new are shown as
	 * New this once.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return string
	 */
	function kaamase_apps_applicants_view( $job_id ) {

		$show = isset( $_GET['show'] ) ? sanitize_key( wp_unslash( $_GET['show'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A filter, nothing changes.
		$show = in_array( $show, array( 'new', 'shortlisted', 'not_selected' ), true ) ? $show : 'all';

		$counts = kaamase_apps_counts( $job_id );
		$brief  = kaamase_apps_job_brief( $job_id );
		$ids    = kaamase_apps_filtered( $job_id, $show );

		$tabs = array(
			'all'          => array( __( 'All', 'kaamase-core' ), $counts['total'] ),
			'new'          => array( __( 'New', 'kaamase-core' ), $counts['new'] ),
			'shortlisted'  => array( __( 'Shortlisted', 'kaamase-core' ), $counts['shortlisted'] ),
			'not_selected' => array( __( 'Not selected', 'kaamase-core' ), $counts['not_selected'] ),
		);

		ob_start();
		?>
		<div class="ka-stack">

			<div>
				<p class="ka-label"><?php esc_html_e( 'Applicants for', 'kaamase-core' ); ?></p>
				<h2>
					<?php if ( '' !== $brief['url'] ) : ?>
						<a href="<?php echo esc_url( $brief['url'] ); ?>"><?php echo esc_html( $brief['title'] ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $brief['title'] ); ?>
					<?php endif; ?>
				</h2>
				<?php if ( ! $brief['open'] ) : ?>
					<p class="ka-small ka-soft"><?php esc_html_e( 'This job has closed. The list stays for six months.', 'kaamase-core' ); ?></p>
				<?php endif; ?>
			</div>

			<ul class="ka-cluster">
				<?php foreach ( $tabs as $key => $tab ) : ?>
					<li>
						<a class="ka-chip<?php echo $key === $show ? ' ka-chip--on' : ''; ?>" href="<?php echo esc_url( 'all' === $key ? kaamase_apps_list_url( $job_id ) : add_query_arg( 'show', $key, kaamase_apps_list_url( $job_id ) ) ); ?>"<?php echo $key === $show ? ' aria-current="page"' : ''; ?>>
							<?php echo esc_html( $tab[0] ); ?> <span<?php echo $key === $show ? '' : ' class="ka-mute"'; ?>><?php echo esc_html( number_format_i18n( $tab[1] ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( ! $ids ) : ?>
				<p class="ka-soft">
					<?php
					echo esc_html(
						'all' === $show
							? __( 'Nobody has applied yet. We will tell you when somebody does.', 'kaamase-core' )
							: __( 'Nobody here.', 'kaamase-core' )
					);
					?>
				</p>
			<?php else : ?>
				<ul class="ka-stack">
					<?php foreach ( $ids as $app_id ) : ?>
						<?php
						$app = kaamase_apps_for_employer( $app_id );

						if ( $app ) {
							echo kaamase_apps_applicant_card( $app, $show ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped where built.
						}
						?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

		</div>
		<?php

		kaamase_apps_mark_seen( $ids );

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_apps_applicant_card' ) ) {
	/**
	 * One applicant, with everything an employer decides on.
	 *
	 * @since 1.0.0
	 * @param array  $app  As kaamase_apps_for_employer() gives it.
	 * @param string $show The list being looked at, to come back to.
	 * @return string
	 */
	function kaamase_apps_applicant_card( $app, $show ) {

		$p     = is_array( $app['profile'] ) ? $app['profile'] : array();
		$facts = array();

		if ( ! empty( $p['qualification_label'] ) ) {
			$facts[] = trim( $p['qualification_label'] . ( ! empty( $p['course'] ) ? ', ' . $p['course'] : '' ) );
		}

		if ( isset( $p['experience_years'] ) ) {
			$facts[] = $p['experience_years']
				/* translators: %s: years */
				? sprintf( _n( '%s year of experience', '%s years of experience', (int) $p['experience_years'], 'kaamase-core' ), number_format_i18n( (int) $p['experience_years'] ) )
				: __( 'Fresher', 'kaamase-core' );
		}

		if ( ! empty( $p['district']['name'] ) ) {
			$facts[] = (string) $p['district']['name'];
		}

		if ( ! empty( $p['salary_expected'] ) ) {
			/* translators: %s: monthly salary */
			$facts[] = sprintf( __( 'Expects ₹%s a month', 'kaamase-core' ), number_format_i18n( (int) $p['salary_expected'] ) );
		}

		if ( ! empty( $p['notice_label'] ) ) {
			$facts[] = (string) $p['notice_label'];
		}

		ob_start();
		?>
		<li class="ka-card ka-card--flat" id="application-<?php echo esc_attr( (string) $app['id'] ); ?>">

			<div class="ka-cluster ka-cluster--between">
				<div class="ka-cluster">
					<?php echo ( ! empty( $p['id'] ) && function_exists( 'kaamase_avatar' ) ) ? kaamase_avatar( (int) $p['id'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div>
						<strong><?php echo esc_html( $app['name'] ); ?></strong>
						<?php if ( ! empty( $p['headline'] ) ) : ?>
							<p class="ka-small ka-soft"><?php echo esc_html( $p['headline'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
				<?php echo kaamase_apps_badge( $app['status'], 'employer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<?php if ( $facts ) : ?>
				<p class="ka-small ka-mt-4"><?php echo esc_html( implode( ' · ', $facts ) ); ?></p>
			<?php endif; ?>

			<p class="ka-small ka-soft ka-mt-4">
				<?php
				/* translators: %s: when, e.g. 3 days ago */
				echo esc_html( sprintf( __( 'Applied %s', 'kaamase-core' ), kaamase_apps_when( $app['sent_at'] ) ) );
				?>
			</p>

			<?php if ( '' !== $app['note'] ) : ?>
				<blockquote class="ka-mt-4"><p><?php echo nl2br( esc_html( $app['note'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></p></blockquote>
			<?php endif; ?>

			<div class="ka-cluster ka-mt-4">
				<?php if ( '' !== $app['contact']['phone'] ) : ?>
					<a class="ka-btn ka-btn--outline ka-btn--sm" rel="nofollow" href="tel:+91<?php echo esc_attr( $app['contact']['phone'] ); ?>">
						<?php
						/* translators: %s: phone number */
						echo esc_html( sprintf( __( 'Call %s', 'kaamase-core' ), function_exists( 'kaamase_format_phone' ) ? kaamase_format_phone( $app['contact']['phone'] ) : $app['contact']['phone'] ) );
						?>
					</a>
					<a class="ka-btn ka-btn--outline ka-btn--sm" rel="nofollow noopener" target="_blank" href="<?php echo esc_url( $app['contact']['whatsapp'] ); ?>"><?php esc_html_e( 'WhatsApp', 'kaamase-core' ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $app['contact']['email'] ) : ?>
					<a class="ka-btn ka-btn--outline ka-btn--sm" rel="nofollow" href="mailto:<?php echo esc_attr( $app['contact']['email'] ); ?>"><?php esc_html_e( 'Email', 'kaamase-core' ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $app['cv']['url'] ) ) : ?>
					<a class="ka-btn ka-btn--primary ka-btn--sm" rel="nofollow noopener" target="_blank" href="<?php echo esc_url( $app['cv']['url'] ); ?>">
						<?php echo esc_html( 'pdf' === $app['cv']['type'] ? __( 'CV (PDF)', 'kaamase-core' ) : __( 'CV (photo)', 'kaamase-core' ) ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $p ) : ?>
				<details class="ka-mt-4">
					<summary><?php esc_html_e( 'Full profile', 'kaamase-core' ); ?></summary>
					<div class="ka-stack ka-mt-4">
						<?php if ( ! empty( $p['about'] ) ) : ?>
							<p><?php echo nl2br( esc_html( $p['about'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside. ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $p['institute'] ) ) : ?>
							<p class="ka-small">
								<?php
								echo esc_html( $p['institute'] . ( ! empty( $p['passed_year'] ) ? ', ' . (int) $p['passed_year'] : '' ) );
								?>
							</p>
						<?php endif; ?>
						<?php if ( ! empty( $p['jobs'] ) && is_array( $p['jobs'] ) ) : ?>
							<div>
								<p class="ka-label"><?php esc_html_e( 'Past jobs', 'kaamase-core' ); ?></p>
								<ul class="ka-stack ka-stack--sm">
									<?php foreach ( $p['jobs'] as $past ) : ?>
										<li class="ka-small">
											<?php
											echo esc_html(
												implode(
													', ',
													array_filter(
														array(
															(string) ( $past['title'] ?? '' ),
															(string) ( $past['employer'] ?? '' ),
															! empty( $past['from'] ) ? $past['from'] . ' – ' . ( ! empty( $past['to'] ) ? $past['to'] : __( 'now', 'kaamase-core' ) ) : '',
														)
													)
												)
											);
											?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $p['skills'] ) ) : ?>
							<p class="ka-small"><strong><?php esc_html_e( 'Skills', 'kaamase-core' ); ?>:</strong> <?php echo esc_html( implode( ', ', (array) $p['skills'] ) ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $p['languages'] ) ) : ?>
							<p class="ka-small"><strong><?php esc_html_e( 'Languages', 'kaamase-core' ); ?>:</strong> <?php echo esc_html( implode( ', ', wp_list_pluck( (array) $p['languages'], 'name' ) ) ); ?></p>
						<?php endif; ?>
					</div>
				</details>
			<?php endif; ?>

			<div class="ka-cluster ka-mt-4">
				<?php
				$choices = array(
					'shortlisted'  => __( 'Shortlist', 'kaamase-core' ),
					'not_selected' => __( 'Not selected', 'kaamase-core' ),
				);

				foreach ( $choices as $status => $label ) :
					if ( $status === $app['status'] ) {
						continue;
					}
					?>
					<form method="post">
						<?php echo kaamase_apps_hidden( 'decide_application', $app['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<input type="hidden" name="kaamase_status" value="<?php echo esc_attr( $status ); ?>">
						<input type="hidden" name="kaamase_show" value="<?php echo esc_attr( $show ); ?>">
						<button class="ka-btn <?php echo 'shortlisted' === $status ? 'ka-btn--primary' : 'ka-btn--outline'; ?> ka-btn--sm" type="submit"><?php echo esc_html( $label ); ?></button>
					</form>
				<?php endforeach; ?>
			</div>

		</li>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_apps_dashboard_prompt' ) ) {
	/**
	 * Top of the dashboard: new applicants waiting.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return void
	 */
	function kaamase_apps_dashboard_prompt( $user_id ) {

		$lines = array();

		foreach ( kaamase_apps_employer_jobs( (int) $user_id ) as $job_id ) {

			$new = kaamase_apps_counts( $job_id )['new'];

			if ( $new ) {
				$lines[ $job_id ] = $new;
			}
		}

		if ( ! $lines ) {
			return;
		}

		$first = (int) array_key_first( $lines );
		?>
		<div class="ka-notice ka-notice--info ka-mb-4">
			<div>
				<span class="ka-notice__title"><?php esc_html_e( 'New applicants', 'kaamase-core' ); ?></span>
				<ul class="ka-stack ka-stack--sm ka-mt-4">
					<?php foreach ( array_slice( $lines, 0, 3, true ) as $job_id => $new ) : ?>
						<li>
							<a href="<?php echo esc_url( kaamase_apps_list_url( $job_id ) ); ?>">
								<?php
								/* translators: 1: job title, 2: how many new */
								echo esc_html( sprintf( __( '%1$s: %2$s new', 'kaamase-core' ), get_the_title( $job_id ), number_format_i18n( $new ) ) );
								?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
				<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="<?php echo esc_url( 1 === count( $lines ) ? kaamase_apps_list_url( $first ) : kaamase_apps_list_url() ); ?>"><?php esc_html_e( 'See applicants', 'kaamase-core' ); ?></a>
			</div>
		</div>
		<?php
	}
}
add_action( 'kaamase_dashboard_prompts', 'kaamase_apps_dashboard_prompt', 12 );

if ( ! function_exists( 'kaamase_apps_dashboard_section' ) ) {
	/**
	 * Lower on the dashboard: the applications you sent, and your jobs'
	 * applicants. Only for somebody who has either.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return void
	 */
	function kaamase_apps_dashboard_section( $user_id ) {

		$user_id = (int) $user_id;
		$mine    = kaamase_apps_of_user( $user_id );
		$jobs    = kaamase_apps_employer_jobs( $user_id );

		if ( ! $mine && ! $jobs ) {
			return;
		}

		$labels = kaamase_apps_labels( 'applicant' );
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">
			<h2><?php esc_html_e( 'Applications', 'kaamase-core' ); ?></h2>

			<?php if ( $mine ) : ?>
				<ul class="ka-stack ka-stack--sm ka-mt-4">
					<?php foreach ( array_slice( $mine, 0, 3 ) as $app_id ) : ?>
						<li class="ka-small">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: job title, 2: where the application stands */
									__( '%1$s: %2$s', 'kaamase-core' ),
									get_the_title( (int) wp_get_post_parent_id( $app_id ) ),
									$labels[ kaamase_apps_status( $app_id ) ]
								)
							);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $jobs ) : ?>
				<p class="ka-small ka-soft ka-mt-4">
					<?php
					$total = 0;

					foreach ( $jobs as $job_id ) {
						$total += kaamase_apps_counts( $job_id )['total'];
					}

					/* translators: 1: applicants, 2: jobs */
					echo esc_html( sprintf( __( '%1$s applicants across %2$s of your jobs.', 'kaamase-core' ), number_format_i18n( $total ), number_format_i18n( count( $jobs ) ) ) );
					?>
				</p>
			<?php endif; ?>

			<a class="ka-btn ka-btn--outline ka-mt-4" href="<?php echo esc_url( kaamase_apps_list_url() ); ?>"><?php echo esc_html( $mine ? __( 'My applications', 'kaamase-core' ) : __( 'See applicants', 'kaamase-core' ) ); ?></a>
		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_apps_dashboard_section', 15 );
