<?php
/**
 * Jobs for you.
 *
 * Open professional jobs matched to a professional profile: on a page of
 * their own, on the dashboard, in the app, and in one message a day when
 * new ones are posted.
 *
 * How a job is matched
 * --------------------
 * By plain rules anybody can check, never a percentage. "85% match"
 * looks precise and means nothing; "Graduate ✓ · Dimapur ✓ · Asks for
 * 3+ years" tells somebody exactly why, and whether to bother.
 *
 *   - The job's category must be one of the person's categories.
 *   - The place must suit them. Somebody who said "only in my own
 *     district" is not shown work elsewhere, unless it is work from home.
 *   - Then three things are compared where both sides said something:
 *     the qualification asked for, the years of experience asked for,
 *     and the salary against what they expect.
 *
 * Nothing missed is a Strong match. One thing missed is a Good match,
 * shown with the miss spelled out, because a person a year short of what
 * an advert asks for often applies anyway and is often right to. Two or
 * more missed is not shown at all.
 *
 * Anything the person has not told us is not counted against them. A
 * profile made at sign-up has a category and a district and nothing
 * else yet, and it is matched on those two alone.
 *
 * The daily message
 * -----------------
 * The same rules, run once a day over the professional jobs posted in
 * the last day, one message per person however many jobs there were.
 * The reasoning in job-alerts.php holds here too: a board that pings on
 * every post gets muted, and muting is forever. It has its own switch,
 * for the person and for the site, separate from the everyday alerts, so
 * somebody can keep one and stop the other.
 *
 * What it leaves alone
 * --------------------
 * Everyday jobs, everyday job alerts, worker profiles and every existing
 * route. Nothing is stored about who matched what: the matches are
 * worked out when they are asked for, so a job that closes or a profile
 * that changes is reflected at once.
 *
 * @package KaamaseCore
 * @version 1.0.1
 * @since   1.0.0
 *
 * Changelog
 *   1.0.1  On Jobs for you, a job's match and reasons are the top of its
 *          own card, rather than a line between two cards that could be
 *          read as belonging to either.
 */

defined( 'ABSPATH' ) || exit;


/** Whether the daily professional message runs at all. */
define( 'KAAMASE_MATCH_ALERTS_ON_OPTION', 'kaamase_prof_alerts_on' );

/** Which hour it goes out, in site time. */
define( 'KAAMASE_MATCH_ALERTS_HOUR_OPTION', 'kaamase_prof_alerts_hour' );

/** The cron hook. */
define( 'KAAMASE_MATCH_ALERTS_HOOK', 'kaamase_prof_alerts_run' );

/** Set to no by somebody who does not want the daily message. */
define( 'KAAMASE_MATCH_ALERTS_USER_KEY', 'kaamase_prof_alerts' );

/** The day a person last had one, so nobody gets two. */
define( 'KAAMASE_MATCH_ALERTS_SENT_KEY', '_kaamase_prof_alert_sent' );

/** The most open jobs looked at for one person. */
define( 'KAAMASE_MATCH_LIMIT', 300 );


/* ==========================================================================
   1. THE RULES
   ========================================================================== */

if ( ! function_exists( 'kaamase_match_ready' ) ) {
	/**
	 * Whether the professional jobs and profiles this file builds on are here.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_match_ready() {

		return defined( 'KAAMASE_PROF_TYPE' )
			&& function_exists( 'kaamase_prof_id' )
			&& function_exists( 'kaamase_prof_categories_of' )
			&& function_exists( 'kaamase_pro_details' );
	}
}

if ( ! function_exists( 'kaamase_match_qualification_label' ) ) {
	/**
	 * A qualification in a few words, short enough for a reason line.
	 *
	 * @since 1.0.0
	 * @param string $key Qualification key.
	 * @return string
	 */
	function kaamase_match_qualification_label( $key ) {

		$labels = array(
			'10th'         => __( 'Class 10', 'kaamase-core' ),
			'12th'         => __( 'Class 12', 'kaamase-core' ),
			'diploma'      => __( 'Diploma or ITI', 'kaamase-core' ),
			'graduate'     => __( 'Graduate', 'kaamase-core' ),
			'postgraduate' => __( 'Postgraduate', 'kaamase-core' ),
			'professional' => __( 'Professional degree', 'kaamase-core' ),
		);

		return isset( $labels[ $key ] ) ? $labels[ $key ] : '';
	}
}

if ( ! function_exists( 'kaamase_match_qualification_fits' ) ) {
	/**
	 * Whether what somebody has meets what a job asks for.
	 *
	 * Higher meets lower: a graduate meets a job asking for Class 12. A
	 * diploma meets Class 12 too, as most employers here count it. The
	 * two at the top are their own thing: a postgraduate degree is not a
	 * CA or an MBBS, and a professional degree is not counted as a
	 * master's.
	 *
	 * @since 1.0.0
	 * @param string $have The person's highest qualification.
	 * @param string $want What the job asks for.
	 * @return bool|null Null when there is nothing to compare.
	 */
	function kaamase_match_qualification_fits( $have, $want ) {

		$have = (string) $have;
		$want = (string) $want;

		$fits = array(
			'10th'         => array( '10th', '12th', 'diploma', 'graduate', 'postgraduate', 'professional' ),
			'12th'         => array( '12th', 'diploma', 'graduate', 'postgraduate', 'professional' ),
			'diploma'      => array( 'diploma', 'graduate', 'postgraduate', 'professional' ),
			'graduate'     => array( 'graduate', 'postgraduate', 'professional' ),
			'postgraduate' => array( 'postgraduate' ),
			'professional' => array( 'professional' ),
		);

		if ( '' === $have || ! isset( $fits[ $want ] ) ) {
			return null;
		}

		return in_array( $have, $fits[ $want ], true );
	}
}

if ( ! function_exists( 'kaamase_match_money' ) ) {
	/**
	 * An amount of rupees, written the way people here write it.
	 *
	 * @since 1.0.0
	 * @param int $amount Rupees.
	 * @return string
	 */
	function kaamase_match_money( $amount ) {
		return '₹' . number_format_i18n( absint( $amount ) );
	}
}

if ( ! function_exists( 'kaamase_match_facts' ) ) {
	/**
	 * What a professional profile says, as the rules read it.
	 *
	 * @since 1.0.0
	 * @param int $prof_id Professional profile.
	 * @return array|null Null when it is not a professional profile.
	 */
	function kaamase_match_facts( $prof_id ) {

		if ( ! kaamase_match_ready() ) {
			return null;
		}

		$post = get_post( (int) $prof_id );

		if ( ! $post || KAAMASE_PROF_TYPE !== $post->post_type ) {
			return null;
		}

		$id = (int) $post->ID;

		return array(
			'id'            => $id,
			'owner'         => (int) $post->post_author,
			'categories'    => kaamase_prof_categories_of( $id ),
			'district'      => (string) kaamase_read_field( $id, 'district' ),
			'where'         => (string) kaamase_read_field( $id, 'prof_where' ),
			'status'        => (string) kaamase_read_field( $id, 'prof_status' ),
			'experience'    => absint( kaamase_read_field( $id, 'prof_experience' ) ),
			'qualification' => (string) kaamase_read_field( $id, 'prof_qualification' ),
			'salary'        => absint( kaamase_read_field( $id, 'prof_salary' ) ),
			'complete'      => function_exists( 'kaamase_prof_is_complete' ) ? kaamase_prof_is_complete( $id ) : true,
		);
	}
}

if ( ! function_exists( 'kaamase_match_job' ) ) {
	/**
	 * How well one job suits one person, with the reasons.
	 *
	 * @since 1.0.0
	 * @param int   $job_id Job.
	 * @param array $facts  From kaamase_match_facts().
	 * @return array|null level (strong or good), label and reasons, or
	 *                    null when the job is not for them.
	 */
	function kaamase_match_job( $job_id, $facts ) {

		$job_id = (int) $job_id;

		if ( ! $job_id || ! is_array( $facts ) || empty( $facts['categories'] ) ) {
			return null;
		}

		// Their own advert is not a job for them.
		if ( (int) get_post_field( 'post_author', $job_id ) === (int) $facts['owner'] ) {
			return null;
		}

		$details = kaamase_pro_details( $job_id );

		if ( ! $details ) {
			return null;
		}

		$names    = function_exists( 'kaamase_prof_category_names' ) ? kaamase_prof_category_names() : array();
		$category = '';

		foreach ( (array) get_the_terms( $job_id, 'kaamase_trade' ) as $term ) {
			if ( $term instanceof WP_Term && in_array( $term->slug, $facts['categories'], true ) ) {
				$category = (string) $term->slug;
				break;
			}
		}

		if ( '' === $category ) {
			return null;
		}

		$reasons = array();
		$misses  = 0;

		$reasons[] = array(
			'key'  => 'category',
			'ok'   => true,
			'text' => isset( $names[ $category ] ) ? (string) $names[ $category ] : $category,
		);

		/* ---- Place ---- */

		$remote   = 'remote' === $details['work_mode'];
		$district = (string) kaamase_read_field( $job_id, 'district' );

		if ( 'district' === $facts['where'] && ! $remote && '' !== $district && '' !== $facts['district'] && $district !== $facts['district'] ) {
			return null;
		}

		if ( $remote ) {
			$reasons[] = array(
				'key'  => 'place',
				'ok'   => true,
				'text' => __( 'Work from home', 'kaamase-core' ),
			);
		} elseif ( '' !== $district ) {

			$place = function_exists( 'kaamase_district_name' ) ? (string) kaamase_district_name( $district ) : '';

			$reasons[] = array(
				'key'  => 'place',
				'ok'   => true,
				/* translators: %s: district */
				'text' => sprintf( __( 'In %s', 'kaamase-core' ), '' !== $place ? $place : $district ),
			);
		}

		/* ---- Qualification ---- */

		$fits = kaamase_match_qualification_fits( $facts['qualification'], $details['qualification'] );

		if ( null !== $fits ) {

			$asked = kaamase_match_qualification_label( $details['qualification'] );

			if ( ! $fits ) {
				++$misses;
			}

			$reasons[] = array(
				'key'  => 'qualification',
				'ok'   => $fits,
				/* translators: %s: a qualification, for example Graduate */
				'text' => $fits ? $asked : sprintf( __( 'Asks for %s', 'kaamase-core' ), $asked ),
			);
		}

		/*
		 * Experience only once the profile is finished. Until then the
		 * nought on it is not an answer, and counting it would mark down
		 * somebody who simply has not got that far yet.
		 */
		if ( ! empty( $details['complete'] ) && ! empty( $facts['complete'] ) ) {

			$min = absint( $details['experience_min'] );

			if ( $min > 0 ) {

				$enough = $facts['experience'] >= $min;

				if ( ! $enough ) {
					++$misses;
				}

				$reasons[] = array(
					'key'  => 'experience',
					'ok'   => $enough,
					'text' => $enough
						/* translators: %s: number of years */
						? sprintf( _n( '%s+ year', '%s+ years', $min, 'kaamase-core' ), number_format_i18n( $min ) )
						/* translators: %s: number of years */
						: sprintf( _n( 'Asks for %s+ year', 'Asks for %s+ years', $min, 'kaamase-core' ), number_format_i18n( $min ) ),
				);

			} elseif ( 'fresher' === $facts['status'] || 0 === $facts['experience'] ) {

				$reasons[] = array(
					'key'  => 'experience',
					'ok'   => true,
					'text' => __( 'Freshers welcome', 'kaamase-core' ),
				);
			}
		}

		/* ---- Salary, only when both sides gave a monthly figure ---- */

		$expected = absint( $facts['salary'] );
		$top      = isset( $details['salary']['max'] ) ? absint( $details['salary']['max'] ) : 0;
		$unit     = isset( $details['salary']['unit'] ) ? (string) $details['salary']['unit'] : '';

		if ( $expected > 0 && $top > 0 && 'month' === $unit ) {

			$pays = $top >= $expected;

			if ( ! $pays ) {
				++$misses;
			}

			$reasons[] = array(
				'key'  => 'salary',
				'ok'   => $pays,
				'text' => $pays
					/* translators: %s: monthly salary */
					? sprintf( __( 'Pays up to %s', 'kaamase-core' ), kaamase_match_money( $top ) )
					/* translators: %s: monthly salary */
					: sprintf( __( 'Pays up to %s, less than you asked', 'kaamase-core' ), kaamase_match_money( $top ) ),
			);
		}

		if ( $misses > 1 ) {
			return null;
		}

		return array(
			'level'   => $misses ? 'good' : 'strong',
			'label'   => $misses ? __( 'Good match', 'kaamase-core' ) : __( 'Strong match', 'kaamase-core' ),
			'reasons' => $reasons,
		);
	}
}

if ( ! function_exists( 'kaamase_match_candidates' ) ) {
	/**
	 * Open jobs in any of these categories, newest first.
	 *
	 * Whole posts rather than IDs, so their fields and categories arrive
	 * in two queries instead of two for every job.
	 *
	 * @since 1.0.0
	 * @param string[] $categories Category slugs.
	 * @param int      $since      Only jobs published after this time. 0 for any.
	 * @return WP_Post[]
	 */
	function kaamase_match_candidates( $categories, $since = 0 ) {

		$categories = array_values( array_filter( array_map( 'strval', (array) $categories ) ) );

		if ( empty( $categories ) ) {
			return array();
		}

		$args = array(
			'post_type'              => 'kaamase_job',
			'post_status'            => 'publish',
			'posts_per_page'         => KAAMASE_MATCH_LIMIT,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'              => array(
				array(
					'taxonomy'         => 'kaamase_trade',
					'field'            => 'slug',
					'terms'            => $categories,
					'include_children' => false,
				),
			),
			// Never a job that has closed. The same test the app's job list uses.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'             => array(
				'relation' => 'OR',
				array(
					'key'     => KAAMASE_META_PREFIX . 'expires',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => KAAMASE_META_PREFIX . 'expires',
					'value'   => time(),
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		);

		if ( $since > 0 ) {
			$args['date_query'] = array(
				array(
					'after'  => gmdate( 'Y-m-d H:i:s', (int) $since ),
					'column' => 'post_date_gmt',
				),
			);
		}

		$found = get_posts( $args );

		return is_array( $found ) ? $found : array();
	}
}

if ( ! function_exists( 'kaamase_match_for' ) ) {
	/**
	 * Every open job for a professional profile, strong matches first.
	 *
	 * @since 1.0.0
	 * @param int $prof_id Professional profile.
	 * @param int $since   Only jobs published after this time. 0 for any.
	 * @return array[] Each: id, level, label, reasons.
	 */
	function kaamase_match_for( $prof_id, $since = 0 ) {

		$facts = kaamase_match_facts( $prof_id );

		if ( ! $facts || empty( $facts['categories'] ) ) {
			return array();
		}

		$strong = array();
		$good   = array();

		foreach ( kaamase_match_candidates( $facts['categories'], $since ) as $job ) {

			$match = kaamase_match_job( $job->ID, $facts );

			if ( ! $match ) {
				continue;
			}

			$match = array( 'id' => (int) $job->ID ) + $match;

			if ( 'strong' === $match['level'] ) {
				$strong[] = $match;
			} else {
				$good[] = $match;
			}
		}

		return array_merge( $strong, $good );
	}
}


/* ==========================================================================
   2. THE DAILY MESSAGE: SETTINGS AND WHO WANTS IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_match_alerts_on' ) ) {
	/**
	 * Whether the site sends the daily professional message.
	 *
	 * On unless switched off. It only ever goes to somebody when a job
	 * that suits them was posted that day, so there is no empty message
	 * to put anybody off.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_match_alerts_on() {
		return (bool) get_option( KAAMASE_MATCH_ALERTS_ON_OPTION, true );
	}
}

if ( ! function_exists( 'kaamase_match_alerts_hour' ) ) {
	/**
	 * Which hour it goes out.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	function kaamase_match_alerts_hour() {
		return max( 0, min( 23, (int) get_option( KAAMASE_MATCH_ALERTS_HOUR_OPTION, 8 ) ) );
	}
}

if ( ! function_exists( 'kaamase_match_clean_hour' ) ) {
	/**
	 * Keep the hour sensible and rebook the run when it moves.
	 *
	 * @since 1.0.0
	 * @param mixed $raw What was chosen.
	 * @return int
	 */
	function kaamase_match_clean_hour( $raw ) {

		wp_clear_scheduled_hook( KAAMASE_MATCH_ALERTS_HOOK );

		return max( 0, min( 23, absint( $raw ) ) );
	}
}

if ( ! function_exists( 'kaamase_match_clean_switch' ) ) {
	/**
	 * A tick box, as stored. Rebooks the run when the switch moves.
	 *
	 * @since 1.0.0
	 * @param mixed $raw What was sent.
	 * @return int 1 or 0.
	 */
	function kaamase_match_clean_switch( $raw ) {

		wp_clear_scheduled_hook( KAAMASE_MATCH_ALERTS_HOOK );

		return rest_sanitize_boolean( $raw ) ? 1 : 0;
	}
}

if ( ! function_exists( 'kaamase_match_schedule' ) ) {
	/**
	 * Make sure the daily run is booked, in the site's own time.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_schedule() {

		if ( ! kaamase_match_alerts_on() ) {

			if ( wp_next_scheduled( KAAMASE_MATCH_ALERTS_HOOK ) ) {
				wp_clear_scheduled_hook( KAAMASE_MATCH_ALERTS_HOOK );
			}

			return;
		}

		if ( wp_next_scheduled( KAAMASE_MATCH_ALERTS_HOOK ) ) {
			return;
		}

		$zone = wp_timezone();
		$now  = new DateTime( 'now', $zone );
		$next = new DateTime( 'now', $zone );

		$next->setTime( kaamase_match_alerts_hour(), 0, 0 );

		if ( $next <= $now ) {
			$next->modify( '+1 day' );
		}

		wp_schedule_event( $next->getTimestamp(), 'daily', KAAMASE_MATCH_ALERTS_HOOK );
	}
}
add_action( 'init', 'kaamase_match_schedule', 30 );

if ( ! function_exists( 'kaamase_match_alerts_wanted_by' ) ) {
	/**
	 * Whether this person wants the daily message. Missing counts as yes.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return bool
	 */
	function kaamase_match_alerts_wanted_by( $user_id ) {
		return 'no' !== (string) get_user_meta( (int) $user_id, KAAMASE_MATCH_ALERTS_USER_KEY, true );
	}
}

if ( ! function_exists( 'kaamase_match_alerts_set_wanted' ) ) {
	/**
	 * Record what somebody chose.
	 *
	 * @since 1.0.0
	 * @param int  $user_id Account.
	 * @param bool $wanted  Whether they want it.
	 * @return void
	 */
	function kaamase_match_alerts_set_wanted( $user_id, $wanted ) {

		if ( $wanted ) {
			delete_user_meta( (int) $user_id, KAAMASE_MATCH_ALERTS_USER_KEY );
			return;
		}

		update_user_meta( (int) $user_id, KAAMASE_MATCH_ALERTS_USER_KEY, 'no' );
	}
}


/* ==========================================================================
   3. THE DAILY MESSAGE: THE RUN
   ========================================================================== */

if ( ! function_exists( 'kaamase_match_new_jobs' ) ) {
	/**
	 * Professional jobs published in the last day and still open.
	 *
	 * @since 1.0.0
	 * @return WP_Post[]
	 */
	function kaamase_match_new_jobs() {

		if ( ! kaamase_match_ready() || ! function_exists( 'kaamase_pro_trade_slugs' ) ) {
			return array();
		}

		return kaamase_match_candidates( kaamase_pro_trade_slugs(), time() - DAY_IN_SECONDS );
	}
}

if ( ! function_exists( 'kaamase_match_alert_words' ) ) {
	/**
	 * What the message says.
	 *
	 * @since 1.0.0
	 * @param array[] $matches This person's matches, newest strong one first.
	 * @return array Title and body.
	 */
	function kaamase_match_alert_words( $matches ) {

		$count = count( $matches );
		$first = wp_strip_all_tags( get_the_title( $matches[0]['id'] ) );

		if ( 1 === $count ) {

			$district = (string) kaamase_read_field( $matches[0]['id'], 'district' );
			$place    = ( '' !== $district && function_exists( 'kaamase_district_name' ) ) ? (string) kaamase_district_name( $district ) : '';

			return array(
				'title' => __( 'A new job for you', 'kaamase-core' ),
				'body'  => '' !== $place
					/* translators: 1: job title, 2: district */
					? sprintf( __( '%1$s, %2$s', 'kaamase-core' ), $first, $place )
					: $first,
			);
		}

		return array(
			/* translators: %s: how many jobs */
			'title' => sprintf( _n( '%s new job for you', '%s new jobs for you', $count, 'kaamase-core' ), number_format_i18n( $count ) ),
			/* translators: %s: a job title */
			'body'  => sprintf( __( 'Including %s. Open Kaam Ase to see them all.', 'kaamase-core' ), $first ),
		);
	}
}

if ( ! function_exists( 'kaamase_match_alerts_run' ) ) {
	/**
	 * The daily message to professionals.
	 *
	 * Jobs first, people second: there are a handful of new professional
	 * jobs a day, so only the profiles in those categories are looked at.
	 * The day is written against each person before anything is sent, so
	 * a run that dies halfway cannot send anybody the same message twice.
	 *
	 * @since 1.0.0
	 * @return int How many people were sent something.
	 */
	function kaamase_match_alerts_run() {

		if ( ! kaamase_match_alerts_on() || ! kaamase_match_ready() ) {
			return 0;
		}

		if ( function_exists( 'kaamase_push_enabled' ) && ! kaamase_push_enabled() ) {
			return 0;
		}

		$new  = kaamase_match_new_jobs();
		$cats = array();

		foreach ( $new as $job ) {
			foreach ( (array) get_the_terms( $job->ID, 'kaamase_trade' ) as $term ) {
				if ( $term instanceof WP_Term && function_exists( 'kaamase_pro_is_trade' ) && kaamase_pro_is_trade( $term->slug ) ) {
					$cats[ $term->slug ] = true;
				}
			}
		}

		if ( empty( $cats ) ) {
			update_option( 'kaamase_prof_alerts_last', array( 'when' => time(), 'sent' => 0, 'jobs' => count( $new ) ), false );
			return 0;
		}

		$profiles = get_posts(
			array(
				'post_type'      => KAAMASE_PROF_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 3000,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'     => KAAMASE_PROF_CATEGORY_KEY,
						'value'   => array_keys( $cats ),
						'compare' => 'IN',
					),
				),
			)
		);

		$today    = gmdate( 'Ymd' );
		$messages = array();
		$reached  = 0;
		$seen     = array();

		foreach ( array_unique( array_map( 'intval', (array) $profiles ) ) as $prof_id ) {

			$facts = kaamase_match_facts( $prof_id );

			if ( ! $facts ) {
				continue;
			}

			$user_id = (int) $facts['owner'];

			// One profile per account, but be sure: one message per person.
			if ( ! $user_id || isset( $seen[ $user_id ] ) ) {
				continue;
			}

			$seen[ $user_id ] = true;

			// A profile asleep for being unused is not woken by a message.
			if ( defined( 'KAAMASE_PROF_DORMANT_KEY' ) && get_post_meta( $prof_id, KAAMASE_PROF_DORMANT_KEY, true ) ) {
				continue;
			}

			if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( $user_id ) ) {
				continue;
			}

			if ( ! kaamase_match_alerts_wanted_by( $user_id ) ) {
				continue;
			}

			if ( (string) get_user_meta( $user_id, KAAMASE_MATCH_ALERTS_SENT_KEY, true ) === $today ) {
				continue;
			}

			$tokens = function_exists( 'kaamase_meta_array' )
				? kaamase_meta_array( get_user_meta( $user_id, 'kaamase_push_tokens', true ) )
				: array();

			if ( empty( $tokens ) ) {
				continue;
			}

			$strong = array();
			$good   = array();

			foreach ( $new as $job ) {

				$match = kaamase_match_job( $job->ID, $facts );

				if ( ! $match ) {
					continue;
				}

				$match = array( 'id' => (int) $job->ID ) + $match;

				if ( 'strong' === $match['level'] ) {
					$strong[] = $match;
				} else {
					$good[] = $match;
				}
			}

			$matches = array_merge( $strong, $good );

			if ( empty( $matches ) ) {
				continue;
			}

			update_user_meta( $user_id, KAAMASE_MATCH_ALERTS_SENT_KEY, $today );

			++$reached;

			$words   = kaamase_match_alert_words( $matches );
			$job_ids = array_slice( wp_list_pluck( $matches, 'id' ), 0, 20 );

			foreach ( $tokens as $token ) {

				$messages[] = array(
					'to'        => (string) $token,
					'title'     => $words['title'],
					'body'      => $words['body'],
					'sound'     => 'default',
					'priority'  => 'high',
					'channelId' => 'default',
					'data'      => array(
						'type'  => 'pro_job_alerts',
						'id'    => (int) $job_ids[0],
						'ids'   => array_map( 'intval', $job_ids ),
						'count' => count( $matches ),
					),
				);
			}
		}

		if ( $messages && function_exists( 'kaamase_alerts_send' ) ) {
			kaamase_alerts_send( $messages );
		}

		update_option( 'kaamase_prof_alerts_last', array( 'when' => time(), 'sent' => $reached, 'jobs' => count( $new ) ), false );

		return $reached;
	}
}
add_action( KAAMASE_MATCH_ALERTS_HOOK, 'kaamase_match_alerts_run' );


/* ==========================================================================
   4. THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_match_routes' ) ) {
	/**
	 * The app's routes.
	 *
	 *   GET  /me/professional/jobs     jobs for you, a page at a time
	 *   POST /me/professional/alerts   the daily message, on or off
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = function_exists( 'kaamase_rest_require_login' ) ? 'kaamase_rest_require_login' : 'is_user_logged_in';

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/professional/jobs',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_match_rest_jobs',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/professional/alerts',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_match_rest_alerts',
				'permission_callback' => $auth,
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
add_action( 'rest_api_init', 'kaamase_match_routes' );

if ( ! function_exists( 'kaamase_match_rest_jobs' ) ) {
	/**
	 * GET /me/professional/jobs
	 *
	 * Each job in the same shape as the jobs list, so the app can draw it
	 * with the card it already has, plus a match with its reasons.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_match_rest_jobs( $request ) {

		$user_id  = get_current_user_id();
		$prof_id  = kaamase_match_ready() ? kaamase_prof_id( $user_id ) : 0;
		$per_page = max( 1, min( 50, absint( $request->get_param( 'per_page' ) ? $request->get_param( 'per_page' ) : 20 ) ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ? $request->get_param( 'page' ) : 1 ) );

		if ( ! $prof_id ) {
			return new WP_REST_Response(
				array(
					'jobs'     => array(),
					'total'    => 0,
					'strong'   => 0,
					'good'     => 0,
					'page'     => 1,
					'pages'    => 0,
					'complete' => false,
					'reason'   => 'no_profile',
					'message'  => __( 'Make your professional profile and we will show you the jobs that suit you.', 'kaamase-core' ),
				),
				200
			);
		}

		$all    = kaamase_match_for( $prof_id );
		$total  = count( $all );
		$strong = count( wp_list_filter( $all, array( 'level' => 'strong' ) ) );
		$slice  = array_slice( $all, ( $page - 1 ) * $per_page, $per_page );
		$jobs   = array();

		foreach ( $slice as $match ) {

			$shaped = function_exists( 'kaamase_shape_job' ) ? kaamase_shape_job( $match['id'] ) : array( 'id' => $match['id'] );

			if ( ! is_array( $shaped ) ) {
				continue;
			}

			$shaped['match'] = array(
				'level'   => $match['level'],
				'label'   => $match['label'],
				'reasons' => $match['reasons'],
			);

			$jobs[] = $shaped;
		}

		$complete = function_exists( 'kaamase_prof_is_complete' ) ? kaamase_prof_is_complete( $prof_id ) : true;

		if ( ! $total ) {
			$message = __( 'No open jobs suit your profile yet. New ones are posted every week, and we will tell you when one is.', 'kaamase-core' );
		} elseif ( ! $complete ) {
			$message = __( 'Finish your profile and these get more exact: add your qualification and your years of experience.', 'kaamase-core' );
		} else {
			$message = '';
		}

		return new WP_REST_Response(
			array(
				'jobs'     => $jobs,
				'total'    => $total,
				'strong'   => $strong,
				'good'     => $total - $strong,
				'page'     => $page,
				'pages'    => (int) ceil( $total / $per_page ),
				'complete' => $complete,
				'reason'   => '',
				'message'  => $message,
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_match_rest_alerts' ) ) {
	/**
	 * POST /me/professional/alerts
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_match_rest_alerts( $request ) {

		$wanted = (bool) $request->get_param( 'wanted' );

		kaamase_match_alerts_set_wanted( get_current_user_id(), $wanted );

		return new WP_REST_Response(
			array(
				'ok'     => true,
				'wanted' => $wanted,
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_match_shape_me' ) ) {
	/**
	 * Tell the app where the daily professional message stands.
	 *
	 * Cheap on purpose: /me is what the app waits on at every start, so
	 * no matching is done here.
	 *
	 * @since 1.0.0
	 * @param array $me      The account object.
	 * @param int   $user_id Account.
	 * @return array
	 */
	function kaamase_match_shape_me( $me, $user_id ) {

		if ( ! is_array( $me ) ) {
			return $me;
		}

		$me['professional_alerts'] = array(
			'available' => kaamase_match_alerts_on(),
			'wanted'    => kaamase_match_alerts_wanted_by( (int) $user_id ),
			'note'      => __( 'Once a day, one message when new professional jobs that suit your profile are posted. Separate from the everyday job alerts.', 'kaamase-core' ),
		);

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_match_shape_me', 29, 2 );


/* ==========================================================================
   5. THE WEBSITE
   ========================================================================== */

if ( ! function_exists( 'kaamase_match_page' ) ) {
	/**
	 * Add the Jobs for you page to the platform's pages.
	 *
	 * @since 1.0.0
	 * @param array[] $pages Page definitions.
	 * @return array[]
	 */
	function kaamase_match_page( $pages ) {

		$pages['jobs_for_you'] = array(
			'title'   => __( 'Jobs for you', 'kaamase-core' ),
			'slug'    => 'jobs-for-you',
			'content' => '[kaamase_jobs_for_you]',
		);

		return $pages;
	}
}
add_filter( 'kaamase_page_definitions', 'kaamase_match_page' );

if ( ! function_exists( 'kaamase_match_make_page' ) ) {
	/**
	 * Create that one page, once.
	 *
	 * A page already at the address is taken over only when it holds the
	 * shortcode. Anything else there was made for another reason.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_make_page() {

		if ( get_option( 'kaamase_match_page_made' ) ) {
			return;
		}

		update_option( 'kaamase_match_page_made', time(), true );

		$stored = (array) get_option( 'kaamase_pages', array() );

		if ( ! empty( $stored['jobs_for_you'] ) && 'page' === get_post_type( (int) $stored['jobs_for_you'] ) ) {
			return;
		}

		$existing = get_page_by_path( 'jobs-for-you' );

		if ( $existing instanceof WP_Post && false !== strpos( (string) $existing->post_content, '[kaamase_jobs_for_you]' ) ) {
			$stored['jobs_for_you'] = (int) $existing->ID;
		} else {

			$id = wp_insert_post(
				array(
					'post_title'     => __( 'Jobs for you', 'kaamase-core' ),
					'post_name'      => 'jobs-for-you',
					'post_content'   => '[kaamase_jobs_for_you]',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			if ( ! $id || is_wp_error( $id ) ) {
				delete_option( 'kaamase_match_page_made' );
				return;
			}

			$stored['jobs_for_you'] = (int) $id;
		}

		update_option( 'kaamase_pages', $stored, true );

		if ( function_exists( 'kaamase_page_url_flush' ) ) {
			kaamase_page_url_flush();
		}
	}
}
add_action( 'init', 'kaamase_match_make_page', 33 );

if ( ! function_exists( 'kaamase_match_page_id' ) ) {
	/**
	 * The ID of the Jobs for you page.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	function kaamase_match_page_id() {

		$stored = (array) get_option( 'kaamase_pages', array() );

		return isset( $stored['jobs_for_you'] ) ? (int) $stored['jobs_for_you'] : 0;
	}
}

if ( ! function_exists( 'kaamase_match_url' ) ) {
	/**
	 * The address of the Jobs for you page.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_match_url() {
		return function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'jobs_for_you' ) : home_url( '/jobs-for-you/' );
	}
}

if ( ! function_exists( 'kaamase_match_private_page' ) ) {
	/**
	 * Keep the page out of search engines, like the dashboard.
	 *
	 * @since 1.0.0
	 * @param int[] $ids Pages kept out of search.
	 * @return int[]
	 */
	function kaamase_match_private_page( $ids ) {

		$page = kaamase_match_page_id();

		if ( $page ) {
			$ids[] = $page;
		}

		return $ids;
	}
}
add_filter( 'kaamase_seo_private_pages', 'kaamase_match_private_page' );

if ( ! function_exists( 'kaamase_match_page_no_store' ) ) {
	/**
	 * Keep the page out of every cache. It is different for every person.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_page_no_store() {

		$page = kaamase_match_page_id();

		if ( ! $page || ! is_page( $page ) ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'kaamase jobs for you' );

		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}
}
add_action( 'template_redirect', 'kaamase_match_page_no_store', 2 );

if ( ! function_exists( 'kaamase_match_robots' ) ) {
	/**
	 * Tell search engines to leave the page alone. There is nothing on
	 * it for anybody signed out but a request to sign in.
	 *
	 * @since 1.0.0
	 * @param array $robots Robots directives.
	 * @return array
	 */
	function kaamase_match_robots( $robots ) {

		$page = kaamase_match_page_id();

		if ( $page && is_page( $page ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['index'], $robots['follow'], $robots['max-image-preview'] );
		}

		return $robots;
	}
}
add_filter( 'wp_robots', 'kaamase_match_robots', 20 );

if ( ! function_exists( 'kaamase_match_rank_math_robots' ) ) {
	/**
	 * The same, for Rank Math, which writes its own robots tag.
	 *
	 * @since 1.0.0
	 * @param array $robots Directives keyed by name.
	 * @return array
	 */
	function kaamase_match_rank_math_robots( $robots ) {

		$page = kaamase_match_page_id();

		if ( $page && is_page( $page ) ) {

			$robots = is_array( $robots ) ? $robots : array();

			unset( $robots['index'], $robots['follow'] );

			$robots['index']  = 'noindex';
			$robots['follow'] = 'nofollow';
		}

		return $robots;
	}
}
add_filter( 'rank_math/frontend/robots', 'kaamase_match_rank_math_robots', 20 );

if ( ! function_exists( 'kaamase_match_reasons_html' ) ) {
	/**
	 * The match and its reasons, as one line over a job card.
	 *
	 * @since 1.0.0
	 * @param array $match From kaamase_match_job().
	 * @return string
	 */
	function kaamase_match_reasons_html( $match ) {

		$out = sprintf(
			'<span class="ka-badge %1$s">%2$s</span>',
			'strong' === $match['level'] ? 'ka-badge--verified' : 'ka-badge--new',
			esc_html( $match['label'] )
		);

		foreach ( $match['reasons'] as $reason ) {
			$out .= $reason['ok']
				? '<span class="ka-small">&#10003; ' . esc_html( $reason['text'] ) . '</span>'
				: '<span class="ka-small ka-soft">&#10007; ' . esc_html( $reason['text'] ) . '</span>';
		}

		return '<p class="ka-cluster ka-match__why">' . $out . '</p>';
	}
}

if ( ! function_exists( 'kaamase_match_all_jobs_url' ) ) {
	/**
	 * Where every professional job is listed.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_match_all_jobs_url() {

		$link = defined( 'KAAMASE_PRO_GROUP' ) ? get_term_link( KAAMASE_PRO_GROUP, 'kaamase_trade' ) : '';

		return is_string( $link ) && '' !== $link ? $link : home_url( '/jobs/' );
	}
}

if ( ! function_exists( 'kaamase_match_shortcode' ) ) {
	/**
	 * The Jobs for you page: [kaamase_jobs_for_you]
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_match_shortcode() {

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="%3$s">%4$s</a></div></div>',
				esc_html__( 'Sign in to see jobs for you', 'kaamase-core' ),
				esc_html__( 'We match professional jobs to your professional profile: your kind of work, where you will work, your qualification and your experience.', 'kaamase-core' ),
				esc_url( wp_login_url( kaamase_match_url() ) ),
				esc_html__( 'Sign in', 'kaamase-core' )
			);
		}

		$prof_id = kaamase_match_ready() ? kaamase_prof_id( get_current_user_id() ) : 0;
		$form    = function_exists( 'kaamase_prof_url' ) ? kaamase_prof_url( 'my_professional' ) : home_url( '/my-professional-profile/' );

		if ( ! $prof_id ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="%3$s">%4$s</a></div></div>',
				esc_html__( 'Make your professional profile first', 'kaamase-core' ),
				esc_html__( 'Tell us the kind of office or professional work you want, and we will show you the open jobs that suit you.', 'kaamase-core' ),
				esc_url( $form ),
				esc_html__( 'Make a professional profile', 'kaamase-core' )
			);
		}

		$matches  = kaamase_match_for( $prof_id );
		$complete = function_exists( 'kaamase_prof_is_complete' ) ? kaamase_prof_is_complete( $prof_id ) : true;

		ob_start();

		if ( ! $complete ) {
			printf(
				'<div class="ka-notice ka-notice--info ka-mb-4"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="%3$s">%4$s</a></div></div>',
				esc_html__( 'Finish your profile for better matches', 'kaamase-core' ),
				esc_html__( 'These are matched on your kind of work and your district only. Add your qualification and your years of experience and they get more exact. Employers cannot see your profile until it is finished.', 'kaamase-core' ),
				esc_url( $form ),
				esc_html__( 'Finish my profile', 'kaamase-core' )
			);
		}

		if ( empty( $matches ) ) {

			printf(
				'<div class="ka-empty"><h2 class="ka-empty__title">%1$s</h2><p class="ka-empty__text">%2$s</p><a class="ka-btn ka-btn--outline" href="%3$s">%4$s</a></div>',
				esc_html__( 'No jobs for you yet', 'kaamase-core' ),
				esc_html__( 'No open professional job suits your profile right now. New ones are posted every week, and we look again every day.', 'kaamase-core' ),
				esc_url( kaamase_match_all_jobs_url() ),
				esc_html__( 'See every professional job', 'kaamase-core' )
			);

		} else {

			echo '<p class="ka-small ka-soft ka-mb-4">' . esc_html(
				sprintf(
					/* translators: %s: number of jobs */
					_n( '%s open professional job suits your profile.', '%s open professional jobs suit your profile. Strong matches first, then good ones, newest first.', count( $matches ), 'kaamase-core' ),
					number_format_i18n( count( $matches ) )
				)
			) . '</p>';

			/*
			 * One card per job: the match and its reasons across the top,
			 * the job beneath, inside one border. As separate pieces the
			 * reasons sat in the gap between two cards and read as
			 * belonging to either. Kept here with the markup it styles,
			 * because nothing else draws it.
			 *
			 * @since 1.0.1
			 */
			echo '<style id="kaamase-match-card">'
				. '.ka-match{background:var(--ka-surface);border:1px solid var(--ka-border);border-radius:var(--ka-radius-lg);box-shadow:var(--ka-shadow-sm);overflow:hidden}'
				. '.ka-match .ka-match__why{margin:0;padding:var(--ka-3) var(--ka-4);background:var(--ka-surface-alt);border-bottom:1px solid var(--ka-border)}'
				. '.ka-match .ka-job-card,.ka-match .ka-job-card:hover{border:0;border-radius:0;box-shadow:none}'
				. '</style>';

			echo '<div class="ka-stack--lg">';

			foreach ( $matches as $match ) {

				echo '<div class="ka-match">';
				echo kaamase_match_reasons_html( $match ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.

				if ( function_exists( 'kaamase_job_card' ) ) {
					kaamase_job_card( $match['id'] );
				} else {
					printf( '<p><a href="%1$s">%2$s</a></p>', esc_url( get_permalink( $match['id'] ) ), esc_html( get_the_title( $match['id'] ) ) );
				}

				echo '</div>';
			}

			echo '</div>';

			printf(
				'<p class="ka-small ka-mt-6"><a href="%1$s">%2$s</a></p>',
				esc_url( kaamase_match_all_jobs_url() ),
				esc_html__( 'See every professional job', 'kaamase-core' )
			);
		}

		printf(
			'<p class="ka-small ka-soft ka-mt-6">%1$s <a href="%2$s">%3$s</a></p>',
			esc_html__( 'Matched to what your professional profile says.', 'kaamase-core' ),
			esc_url( $form ),
			esc_html__( 'Change my profile', 'kaamase-core' )
		);

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_jobs_for_you', 'kaamase_match_shortcode' );

if ( ! function_exists( 'kaamase_match_dashboard_card' ) ) {
	/**
	 * Jobs for you on the dashboard, under the professional profile card.
	 *
	 * The three best, a way to the rest, and the daily message switch.
	 *
	 * @since 1.0.0
	 * @param int    $user_id Who is looking.
	 * @param int    $profile Their main profile.
	 * @param string $type    Which kind of profile.
	 * @return void
	 */
	function kaamase_match_dashboard_card( $user_id, $profile = 0, $type = '' ) {

		unset( $profile, $type );

		$user_id = (int) $user_id;
		$prof_id = kaamase_match_ready() ? kaamase_prof_id( $user_id ) : 0;

		if ( ! $prof_id ) {
			return;
		}

		$matches = kaamase_match_for( $prof_id );
		$wanted  = kaamase_match_alerts_wanted_by( $user_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only choosing a message.
		$just = isset( $_GET['proalerts'] ) ? sanitize_key( wp_unslash( $_GET['proalerts'] ) ) : '';
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6" id="ka-jobs-for-you">

			<h2><?php esc_html_e( 'Jobs for you', 'kaamase-core' ); ?></h2>

			<?php if ( empty( $matches ) ) : ?>

				<p class="ka-small ka-soft ka-mt-4">
					<?php esc_html_e( 'No open professional job suits your profile right now. We look again every day.', 'kaamase-core' ); ?>
				</p>

			<?php else : ?>

				<p class="ka-small ka-soft ka-mt-4">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of jobs */
							_n( '%s open job suits your professional profile.', '%s open jobs suit your professional profile.', count( $matches ), 'kaamase-core' ),
							number_format_i18n( count( $matches ) )
						)
					);
					?>
				</p>

				<ul class="ka-stack ka-mt-4">
					<?php foreach ( array_slice( $matches, 0, 3 ) as $match ) : ?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $match['id'] ) ); ?>"><?php echo esc_html( get_the_title( $match['id'] ) ); ?></a>
							<span class="ka-small ka-soft">&middot; <?php echo esc_html( $match['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>

				<a class="ka-btn ka-btn--outline ka-mt-4" href="<?php echo esc_url( kaamase_match_url() ); ?>">
					<?php esc_html_e( 'See all jobs for you', 'kaamase-core' ); ?>
				</a>

			<?php endif; ?>

			<?php if ( kaamase_match_alerts_on() ) : ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ka-mt-6">

					<input type="hidden" name="action" value="kaamase_prof_alerts_choose">
					<input type="hidden" name="wanted" value="<?php echo $wanted ? '0' : '1'; ?>">
					<?php wp_nonce_field( 'kaamase_prof_alerts_choose' ); ?>

					<?php if ( 'on' === $just || 'off' === $just ) : ?>
						<p class="ka-small"><strong><?php esc_html_e( 'Done.', 'kaamase-core' ); ?></strong></p>
					<?php endif; ?>

					<p class="ka-small ka-soft">
						<?php
						echo $wanted
							? esc_html__( 'In the app, we send you one message a day when new jobs like these are posted.', 'kaamase-core' )
							: esc_html__( 'The daily message about new jobs like these is off.', 'kaamase-core' );
						?>
					</p>

					<button class="ka-btn ka-btn--ghost ka-btn--sm ka-mt-4" type="submit">
						<?php
						echo $wanted
							? esc_html__( 'Stop the daily message', 'kaamase-core' )
							: esc_html__( 'Send me the daily message', 'kaamase-core' );
						?>
					</button>

				</form>

			<?php endif; ?>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_match_dashboard_card', 17, 3 );

if ( ! function_exists( 'kaamase_match_choose' ) ) {
	/**
	 * Take the daily message choice from the website.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_choose() {

		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Sign in first.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_prof_alerts_choose' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		$wanted = ! empty( $_POST['wanted'] );

		kaamase_match_alerts_set_wanted( get_current_user_id(), $wanted );

		$back = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : home_url( '/' );

		wp_safe_redirect( add_query_arg( 'proalerts', $wanted ? 'on' : 'off', $back ) . '#ka-jobs-for-you' );
		exit;
	}
}
add_action( 'admin_post_kaamase_prof_alerts_choose', 'kaamase_match_choose' );


/* ==========================================================================
   6. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_match_settings' ) ) {
	/**
	 * Register the settings on the Professionals screen.
	 *
	 * The door for joining as a professional lives in
	 * professional-signup.php; its switch is kept here so all the
	 * professional settings are on one screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_settings() {

		register_setting(
			'kaamase_professional_settings',
			KAAMASE_MATCH_ALERTS_ON_OPTION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'kaamase_match_clean_switch',
				'default'           => 1,
			)
		);

		register_setting(
			'kaamase_professional_settings',
			KAAMASE_MATCH_ALERTS_HOUR_OPTION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'kaamase_match_clean_hour',
				'default'           => 8,
			)
		);

		if ( defined( 'KAAMASE_JOIN_ON_OPTION' ) ) {
			register_setting(
				'kaamase_professional_settings',
				KAAMASE_JOIN_ON_OPTION,
				array(
					'type'              => 'integer',
					'sanitize_callback' => 'kaamase_match_tick',
					'default'           => 1,
				)
			);
		}
	}
}
add_action( 'admin_init', 'kaamase_match_settings' );

if ( ! function_exists( 'kaamase_match_tick' ) ) {
	/**
	 * A tick box, as stored.
	 *
	 * @since 1.0.0
	 * @param mixed $raw What was sent.
	 * @return int 1 or 0.
	 */
	function kaamase_match_tick( $raw ) {
		return rest_sanitize_boolean( $raw ) ? 1 : 0;
	}
}

if ( ! function_exists( 'kaamase_match_menu' ) ) {
	/**
	 * Add the screen under Kaam Ase.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_menu() {

		add_submenu_page(
			'kaamase',
			__( 'Professional settings', 'kaamase-core' ),
			__( 'Professional settings', 'kaamase-core' ),
			'manage_options',
			'kaamase-professional-settings',
			'kaamase_match_screen'
		);
	}
}
add_action( 'admin_menu', 'kaamase_match_menu', 28 );

if ( ! function_exists( 'kaamase_match_screen' ) ) {
	/**
	 * Render the screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_screen() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$ran = isset( $_GET['ran'] ) ? absint( $_GET['ran'] ) : -1;

		$last = (array) get_option( 'kaamase_prof_alerts_last', array() );
		$next = wp_next_scheduled( KAAMASE_MATCH_ALERTS_HOOK );
		$jobs = count( kaamase_match_new_jobs() );
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Professional settings', 'kaamase-core' ); ?></h1>

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

			<form method="post" action="options.php">

				<?php settings_fields( 'kaamase_professional_settings' ); ?>

				<table class="form-table" role="presentation">

					<?php if ( defined( 'KAAMASE_JOIN_ON_OPTION' ) ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Joining as a professional', 'kaamase-core' ); ?></th>
							<td>
								<label>
									<input type="checkbox" value="1"
										name="<?php echo esc_attr( KAAMASE_JOIN_ON_OPTION ); ?>"
										<?php checked( (bool) get_option( KAAMASE_JOIN_ON_OPTION, 1 ) ); ?>>
									<?php esc_html_e( 'Offer "I\'m a professional" when people register', 'kaamase-core' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'On the website and in the app. Turning it off hides the choice for new people only. Everybody who already joined that way keeps their account and profile.', 'kaamase-core' ); ?>
								</p>
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<th scope="row"><?php esc_html_e( 'Daily message to professionals', 'kaamase-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" value="1"
									name="<?php echo esc_attr( KAAMASE_MATCH_ALERTS_ON_OPTION ); ?>"
									<?php checked( kaamase_match_alerts_on() ); ?>>
								<?php esc_html_e( 'Yes, send it', 'kaamase-core' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'Once a day, a message in the app to each person with a professional profile, only when professional jobs that suit them were posted that day. Separate from the everyday job alerts, which have their own screen.', 'kaamase-core' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="ka-prof-alert-hour"><?php esc_html_e( 'What time', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<select id="ka-prof-alert-hour" name="<?php echo esc_attr( KAAMASE_MATCH_ALERTS_HOUR_OPTION ); ?>">
								<?php for ( $hour = 0; $hour < 24; $hour++ ) : ?>
									<option value="<?php echo esc_attr( $hour ); ?>" <?php selected( kaamase_match_alerts_hour(), $hour ); ?>>
										<?php echo esc_html( sprintf( '%02d:00', $hour ) ); ?>
									</option>
								<?php endfor; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Site time.', 'kaamase-core' ); ?></p>
						</td>
					</tr>

				</table>

				<?php submit_button(); ?>

			</form>

			<h2><?php esc_html_e( 'How it stands', 'kaamase-core' ); ?></h2>

			<table class="widefat striped" style="max-width:40em">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Professional jobs posted in the last day', 'kaamase-core' ); ?></th>
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
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kaamase_prof_alerts_now' ), 'kaamase_prof_alerts_now' ) ); ?>">
					<?php esc_html_e( 'Send it now', 'kaamase-core' ); ?>
				</a>
			</p>

			<p class="description" style="max-width:45em">
				<?php esc_html_e( 'Sending now counts for today, so anybody reached will not get the scheduled one as well.', 'kaamase-core' ); ?>
			</p>

		</div>
		<?php
	}
}

if ( ! function_exists( 'kaamase_match_now' ) ) {
	/**
	 * Run it by hand.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_match_now() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_prof_alerts_now' );

		$sent = kaamase_match_alerts_run();

		wp_safe_redirect( admin_url( 'admin.php?page=kaamase-professional-settings&ran=' . absint( $sent ) ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_prof_alerts_now', 'kaamase_match_now' );
