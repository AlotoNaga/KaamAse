<?php
/**
 * Ratings.
 *
 * Two way, double blind, and built on specific questions rather than
 * stars.
 *
 * Three problems this file is designed around
 * -------------------------------------------
 *
 * 1. Retaliation. If a worker can see an employer's rating before
 *    writing their own, a bad review comes back as a bad review, and
 *    both sides quickly learn to be dishonest. So neither rating appears
 *    until both are in, or until the window closes. Whoever writes first
 *    cannot be punished for it.
 *
 * 2. Star inflation. Where everybody knows somebody, nobody gives three
 *    stars to a man from the next colony. Every rating comes back five
 *    and the whole system means nothing within a month. So a rating here
 *    is a handful of concrete questions with a yes or no answer. Did he
 *    turn up. Was he paid what was agreed. Those get answered honestly
 *    in a way stars never do.
 *
 * 3. One bad day ending somebody's livelihood. In a market this size a
 *    single angry employer could finish a worker on a bad afternoon.
 *    Nothing is shown publicly until three people have rated.
 *
 * Storage
 * -------
 * Ratings are WordPress comments of type kaamase_rating on the profile
 * being rated. That gets moderation, spam handling, an admin screen and
 * removal on user erasure for free, rather than a custom table needing
 * all four built by hand.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE QUESTIONS

   The questions ARE the product here. Get these right and the ratings
   are worth something. Replace them with five stars and they are not.
   ========================================================================== */

if ( ! function_exists( 'kaamase_rating_questions' ) ) {
	/**
	 * What each side is asked.
	 *
	 * Each is a plain yes or no. The weight decides what a no costs.
	 * turned_up carries the most weight of anything on this platform,
	 * because a worker who does not arrive is worse than no worker at
	 * all, and paid_agreed matters the same way in reverse.
	 *
	 * @since 1.0.0
	 * @param string $about worker or employer.
	 * @return array[] Question definitions keyed by field name.
	 */
	function kaamase_rating_questions( $about = 'worker' ) {

		if ( 'employer' === $about ) {

			$questions = array(
				'paid_agreed'       => array(
					'label'  => __( 'Were you paid the amount that was agreed?', 'kaamase-core' ),
					'weight' => 3,
					'flag'   => true,
				),
				'paid_on_time'      => array(
					'label'  => __( 'Were you paid when they said you would be?', 'kaamase-core' ),
					'weight' => 2,
					'flag'   => true,
				),
				'work_as_described' => array(
					'label'  => __( 'Was the work what they described?', 'kaamase-core' ),
					'weight' => 1,
					'flag'   => false,
				),
				'treated_well'      => array(
					'label'  => __( 'Were you treated properly?', 'kaamase-core' ),
					'weight' => 2,
					'flag'   => true,
				),
				'would_work_again'  => array(
					'label'  => __( 'Would you work for them again?', 'kaamase-core' ),
					'weight' => 2,
					'flag'   => false,
				),
			);

			/**
			 * Filter the questions asked about an employer.
			 *
			 * @since 1.0.0
			 * @param array[] $questions Question definitions.
			 */
			return (array) apply_filters( 'kaamase_employer_rating_questions', $questions );
		}

		$questions = array(
			'turned_up'        => array(
				'label'  => __( 'Did they turn up on the day?', 'kaamase-core' ),
				'weight' => 3,
				'flag'   => true,
			),
			'on_time'          => array(
				'label'  => __( 'Were they on time?', 'kaamase-core' ),
				'weight' => 1,
				'flag'   => false,
			),
			'work_quality'     => array(
				'label'  => __( 'Was the work done properly?', 'kaamase-core' ),
				'weight' => 3,
				'flag'   => false,
			),
			'stuck_to_rate'    => array(
				'label'  => __( 'Did they stick to the rate that was agreed?', 'kaamase-core' ),
				'weight' => 2,
				'flag'   => true,
			),
			'would_hire_again' => array(
				'label'  => __( 'Would you hire them again?', 'kaamase-core' ),
				'weight' => 2,
				'flag'   => false,
			),
		);

		/**
		 * Filter the questions asked about a worker.
		 *
		 * @since 1.0.0
		 * @param array[] $questions Question definitions.
		 */
		return (array) apply_filters( 'kaamase_worker_rating_questions', $questions );
	}
}

if ( ! function_exists( 'kaamase_score_answers' ) ) {
	/**
	 * Turn yes and no answers into a score out of five.
	 *
	 * Out of five so it reads like the rating people expect, but
	 * calculated from answers rather than asked as a number.
	 *
	 * @since 1.0.0
	 * @param array  $answers Answers keyed by question name.
	 * @param string $about   worker or employer.
	 * @return float Score between 1 and 5.
	 */
	function kaamase_score_answers( $answers, $about = 'worker' ) {

		$questions = kaamase_rating_questions( $about );

		$earned = 0;
		$total  = 0;

		foreach ( $questions as $key => $question ) {

			$weight = absint( $question['weight'] );
			$total += $weight;

			if ( ! empty( $answers[ $key ] ) ) {
				$earned += $weight;
			}
		}

		if ( ! $total ) {
			return 0;
		}

		/*
		 * Scaled between 1 and 5 rather than 0 and 5. A person who
		 * answered no to everything gets a 1, not a 0, because a zero
		 * reads as the platform having judged them rather than having
		 * recorded what happened.
		 */
		return round( 1 + ( ( $earned / $total ) * 4 ), 1 );
	}
}


/* ==========================================================================
   2. WHO MAY RATE WHOM

   A rating needs a hire behind it. Without that, anybody can rate
   anybody and the whole thing is worthless inside a week.
   ========================================================================== */

if ( ! function_exists( 'kaamase_record_hire' ) ) {
	/**
	 * Record that somebody was hired.
	 *
	 * This is what unlocks rating for both sides. It gets created when an
	 * employer confirms a hire, which the platform asks about a few days
	 * after they looked up a worker's number.
	 *
	 * Asking afterwards rather than requiring an application flow is
	 * deliberate. Hiring here happens on the phone and at the site. A
	 * platform that only knew about hires it processed itself would know
	 * about almost none of them.
	 *
	 * @since 1.0.0
	 * @param int $worker_id   Worker or team profile ID.
	 * @param int $employer_id Employer user ID.
	 * @param int $job_id      Job ID, or 0 when hired directly.
	 * @return string Hire reference.
	 */
	function kaamase_record_hire( $worker_id, $employer_id, $job_id = 0 ) {

		$worker_id   = absint( $worker_id );
		$employer_id = absint( $employer_id );
		$job_id      = absint( $job_id );

		$ref   = md5( $worker_id . '_' . $employer_id . '_' . $job_id );
		$hires = kaamase_meta_array( get_post_meta( $worker_id, KAAMASE_META_PREFIX . 'hires', true ) );

		if ( isset( $hires[ $ref ] ) ) {
			return $ref;
		}

		$hires[ $ref ] = array(
			'employer' => $employer_id,
			'job'      => $job_id,
			'time'     => time(),
		);

		update_post_meta( $worker_id, KAAMASE_META_PREFIX . 'hires', $hires );

		$done = absint( kaamase_read_field( $worker_id, 'jobs_completed' ) );
		update_post_meta( $worker_id, KAAMASE_META_PREFIX . 'jobs_completed', $done + 1 );

		$employer_profile = kaamase_get_user_profile( $employer_id, 'kaamase_employer' );

		if ( $employer_profile ) {
			$made = absint( kaamase_read_field( $employer_profile, 'hires_made' ) );
			update_post_meta( $employer_profile, KAAMASE_META_PREFIX . 'hires_made', $made + 1 );
		}

		/**
		 * Fires when a hire is recorded.
		 *
		 * @since 1.0.0
		 * @param int    $worker_id   Worker profile ID.
		 * @param int    $employer_id Employer user ID.
		 * @param string $ref         Hire reference.
		 */
		do_action( 'kaamase_hire_recorded', $worker_id, $employer_id, $ref );

		return $ref;
	}
}

if ( ! function_exists( 'kaamase_hire_exists_between' ) ) {
	/**
	 * Whether a hire connects this profile and this user.
	 *
	 * Works in both directions, since either party may be the one rating.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile being rated.
	 * @param int $user_id User doing the rating.
	 * @return bool
	 */
	function kaamase_hire_exists_between( $post_id, $user_id ) {

		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		// An employer rating a worker. The hire sits on the worker's profile.
		if ( in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true ) ) {

			$hires = kaamase_meta_array( get_post_meta( $post_id, KAAMASE_META_PREFIX . 'hires', true ) );

			foreach ( $hires as $hire ) {
				if ( absint( $hire['employer'] ) === absint( $user_id ) ) {
					return true;
				}
			}

			return false;
		}

		// A worker rating an employer. Look on the worker's own profile.
		if ( 'kaamase_employer' === $post->post_type ) {

			$worker = kaamase_get_user_profile( $user_id, 'kaamase_worker' );

			if ( ! $worker ) {
				return false;
			}

			$hires = kaamase_meta_array( get_post_meta( $worker, KAAMASE_META_PREFIX . 'hires', true ) );

			foreach ( $hires as $hire ) {
				if ( absint( $hire['employer'] ) === absint( $post->post_author ) ) {
					return true;
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'kaamase_has_rated' ) ) {
	/**
	 * Whether a user has already rated a profile.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_has_rated( $post_id, $user_id ) {

		$found = get_comments(
			array(
				'post_id' => absint( $post_id ),
				'user_id' => absint( $user_id ),
				'type'    => 'kaamase_rating',
				'status'  => 'any',
				'count'   => true,
			)
		);

		return $found > 0;
	}
}

if ( ! function_exists( 'kaamase_can_rate' ) ) {
	/**
	 * Whether the current user may rate a profile.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile being rated.
	 * @return true|WP_Error
	 */
	function kaamase_can_rate( $post_id ) {

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'kaamase_signed_out', __( 'Sign in to leave a rating.', 'kaamase-core' ) );
		}

		$user_id = get_current_user_id();

		if ( ! get_post( $post_id ) ) {
			return new WP_Error( 'kaamase_no_post', __( 'That profile no longer exists.', 'kaamase-core' ) );
		}

		if ( kaamase_user_owns( $post_id, $user_id ) ) {
			return new WP_Error( 'kaamase_self', __( 'You cannot rate yourself.', 'kaamase-core' ) );
		}

		if ( kaamase_has_rated( $post_id, $user_id ) ) {
			return new WP_Error( 'kaamase_done', __( 'You have already rated this person.', 'kaamase-core' ) );
		}

		if ( ! kaamase_hire_exists_between( $post_id, $user_id ) ) {
			return new WP_Error(
				'kaamase_no_hire',
				__( 'Ratings can only be left after real work. If you worked together, confirm the hire first.', 'kaamase-core' )
			);
		}

		return true;
	}
}


/* ==========================================================================
   3. SUBMITTING
   ========================================================================== */

if ( ! function_exists( 'kaamase_handle_rating' ) ) {
	/**
	 * Save a submitted rating.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_rating() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'submit_rating' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			empty( $_POST['kaamase_rating_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_rating_nonce'] ) ), 'kaamase_rating' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_id = isset( $_POST['kaamase_rate'] ) ? absint( $_POST['kaamase_rate'] ) : 0;

		$allowed = kaamase_can_rate( $post_id );

		if ( is_wp_error( $allowed ) ) {
			wp_safe_redirect( add_query_arg( 'rating', 'refused', get_permalink( $post_id ) ) );
			exit;
		}

		$post  = get_post( $post_id );
		$about = 'kaamase_employer' === $post->post_type ? 'employer' : 'worker';

		$answers = array();

		foreach ( array_keys( kaamase_rating_questions( $about ) ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$answers[ $key ] = ! empty( $_POST[ 'q_' . $key ] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$note = isset( $_POST['kaamase_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kaamase_note'] ) ) : '';
		$note = mb_substr( $note, 0, 300 );

		$score = kaamase_score_answers( $answers, $about );
		$user  = wp_get_current_user();

		/*
		 * Held unapproved until the other side rates, or until the window
		 * closes. This is the double blind.
		 */
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_content'      => $note,
				'comment_type'         => 'kaamase_rating',
				'user_id'              => $user->ID,
				'comment_approved'     => 0,
			)
		);

		if ( ! $comment_id ) {
			wp_safe_redirect( add_query_arg( 'rating', 'failed', get_permalink( $post_id ) ) );
			exit;
		}

		add_comment_meta( $comment_id, 'kaamase_score', $score );
		add_comment_meta( $comment_id, 'kaamase_answers', $answers );
		add_comment_meta( $comment_id, 'kaamase_about', $about );
		add_comment_meta( $comment_id, 'kaamase_window', time() + ( 14 * DAY_IN_SECONDS ) );

		kaamase_maybe_release_pair( $post_id, (int) $user->ID );
		kaamase_raise_flags( $post_id, $answers, $about );

		wp_safe_redirect( add_query_arg( 'rating', 'thanks', get_permalink( $post_id ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_rating' );

if ( ! function_exists( 'kaamase_maybe_release_pair' ) ) {
	/**
	 * Publish both ratings once both sides have written one.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile just rated.
	 * @param int $user_id User who just rated.
	 * @return void
	 */
	function kaamase_maybe_release_pair( $post_id, $user_id ) {

		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		// The rater's own profile, which is what the other party would rate.
		$mine = ( 'kaamase_employer' === $post->post_type )
			? kaamase_get_user_profile( $user_id, 'kaamase_worker' )
			: kaamase_get_user_profile( $user_id, 'kaamase_employer' );

		if ( ! $mine ) {
			return;
		}

		$theirs = get_comments(
			array(
				'post_id' => $mine,
				'user_id' => (int) $post->post_author,
				'type'    => 'kaamase_rating',
				'status'  => 'any',
				'number'  => 1,
			)
		);

		if ( empty( $theirs ) ) {
			return;
		}

		kaamase_release_ratings_for( $post_id, $user_id );
		kaamase_release_ratings_for( $mine, (int) $post->post_author );
	}
}

if ( ! function_exists( 'kaamase_release_ratings_for' ) ) {
	/**
	 * Approve a held rating and recalculate the average.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile rated.
	 * @param int $user_id Rater.
	 * @return void
	 */
	function kaamase_release_ratings_for( $post_id, $user_id ) {

		$held = get_comments(
			array(
				'post_id' => absint( $post_id ),
				'user_id' => absint( $user_id ),
				'type'    => 'kaamase_rating',
				'status'  => 'hold',
			)
		);

		foreach ( $held as $comment ) {
			wp_set_comment_status( $comment->comment_ID, 'approve' );
		}

		kaamase_recalculate_rating( $post_id );
	}
}

if ( ! function_exists( 'kaamase_release_expired_ratings' ) ) {
	/**
	 * Release ratings whose window has closed.
	 *
	 * Runs daily. Without it, a rating written by somebody whose
	 * counterpart never replies stays invisible forever, which punishes
	 * the person who took the trouble to write it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_release_expired_ratings() {

		$held = get_comments(
			array(
				'type'       => 'kaamase_rating',
				'status'     => 'hold',
				'number'     => 100,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'kaamase_window',
						'value'   => time(),
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$touched = array();

		foreach ( $held as $comment ) {
			wp_set_comment_status( $comment->comment_ID, 'approve' );
			$touched[ $comment->comment_post_ID ] = 1;
		}

		foreach ( array_keys( $touched ) as $post_id ) {
			kaamase_recalculate_rating( $post_id );
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_release_expired_ratings' );


/* ==========================================================================
   4. THE AVERAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_recalculate_rating' ) ) {
	/**
	 * Recalculate a profile's stored average.
	 *
	 * Only approved ratings count. A held rating is invisible in every
	 * sense including the average, or the number itself would leak what
	 * somebody wrote before the window closed.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return void
	 */
	function kaamase_recalculate_rating( $post_id ) {

		$ratings = get_comments(
			array(
				'post_id' => absint( $post_id ),
				'type'    => 'kaamase_rating',
				'status'  => 'approve',
			)
		);

		$total = 0;
		$count = 0;

		foreach ( $ratings as $comment ) {

			$score = (float) get_comment_meta( $comment->comment_ID, 'kaamase_score', true );

			if ( $score > 0 ) {
				$total += $score;
				$count++;
			}
		}

		update_post_meta(
			$post_id,
			KAAMASE_META_PREFIX . 'rating_average',
			$count ? round( $total / $count, 1 ) : 0
		);

		update_post_meta( $post_id, KAAMASE_META_PREFIX . 'rating_count', $count );
	}
}

if ( ! function_exists( 'kaamase_rating_is_public' ) ) {
	/**
	 * Whether a profile has enough ratings to show one.
	 *
	 * Three. Below that the card shows New instead of a number.
	 *
	 * In a market this small a single angry employer could otherwise end
	 * somebody's livelihood with one click on a bad afternoon, and the
	 * worker would have no way to answer it.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return bool
	 */
	function kaamase_rating_is_public( $post_id ) {

		/**
		 * Filter how many ratings are needed before one is shown.
		 *
		 * @since 1.0.0
		 * @param int $minimum Minimum rating count.
		 */
		$minimum = absint( apply_filters( 'kaamase_rating_minimum', 3 ) );

		$count = absint( get_post_meta( $post_id, KAAMASE_META_PREFIX . 'rating_count', true ) );

		return $count >= $minimum;
	}
}

/**
 * Withhold the average until there are enough ratings.
 *
 * Hooked onto the theme's field reader, so a template asking for a
 * rating below the threshold gets zero back and falls through to the New
 * badge. No template has to remember to check.
 *
 * @since 1.0.0
 * @param mixed  $value   Stored value.
 * @param int    $post_id Post ID.
 * @param string $key     Field key.
 * @return mixed Value, or 0 when withheld.
 */
function kaamase_hide_thin_ratings( $value, $post_id, $key ) {

	if ( 'rating_average' !== $key && 'rating_count' !== $key ) {
		return $value;
	}

	if ( current_user_can( 'manage_options' ) || kaamase_user_owns( $post_id ) ) {
		return $value;
	}

	return kaamase_rating_is_public( $post_id ) ? $value : 0;
}
add_filter( 'kaamase_field', 'kaamase_hide_thin_ratings', 20, 3 );


/* ==========================================================================
   5. FLAGS

   The early warning system, and the most operationally useful thing in
   this file. An employer collecting did not pay the agreed amount from
   three different workers is something you want to know on the day it
   happens, not when it reaches Facebook.
   ========================================================================== */

if ( ! function_exists( 'kaamase_raise_flags' ) ) {
	/**
	 * Count serious negative answers against a profile.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Profile rated.
	 * @param array  $answers Submitted answers.
	 * @param string $about   worker or employer.
	 * @return void
	 */
	function kaamase_raise_flags( $post_id, $answers, $about ) {

		$questions = kaamase_rating_questions( $about );
		$flags     = kaamase_meta_array( get_post_meta( $post_id, KAAMASE_META_PREFIX . 'flags', true ) );
		$raised    = array();

		foreach ( $questions as $key => $question ) {

			if ( empty( $question['flag'] ) || ! empty( $answers[ $key ] ) ) {
				continue;
			}

			$current       = absint( isset( $flags[ $key ] ) ? $flags[ $key ] : 0 );
			$flags[ $key ] = $current + 1;
			$raised[]      = $key;
		}

		if ( empty( $raised ) ) {
			return;
		}

		update_post_meta( $post_id, KAAMASE_META_PREFIX . 'flags', $flags );

		foreach ( $raised as $key ) {

			if ( $flags[ $key ] < 3 ) {
				continue;
			}

			/**
			 * Fires when a profile reaches three flags on one question.
			 *
			 * Hook this to email yourself. Three separate workers saying
			 * the same employer did not pay is a pattern, and it is worth
			 * a phone call from you before it becomes a public row.
			 *
			 * @since 1.0.0
			 * @param int    $post_id Profile flagged.
			 * @param string $key     Question that was failed.
			 * @param int    $count   How many times.
			 */
			do_action( 'kaamase_flag_threshold', $post_id, $key, $flags[ $key ] );
		}
	}
}


/* ==========================================================================
   6. DISPLAY
   ========================================================================== */

if ( ! function_exists( 'kaamase_rating_blocked_notice' ) ) {
	/**
	 * Say why the rating form is not here.
	 *
	 * It used to return an empty string and print nothing at all, which
	 * is the worst of the options: somebody who wants to rate the person
	 * they worked for looks at the page, finds no way to do it, and has
	 * no idea whether the feature exists. kaamase_can_rate already knows
	 * the reason and wrote it down. This says it out loud.
	 *
	 * Only for the one reason worth saying. Telling a signed-out reader
	 * to sign in duplicates the button already on the page, and telling
	 * somebody they cannot rate themselves on their own profile is noise
	 * on the one page they look at most.
	 *
	 * And only to somebody who could ever rate this profile. A worker
	 * reading another worker's page is not a missing hire, it is not a
	 * pairing that exists, and a message about confirming a hire would
	 * only confuse them.
	 *
	 * @since 1.5.0
	 * @param int    $post_id Profile being looked at.
	 * @param string $code    Why kaamase_can_rate refused.
	 * @return string Markup, or an empty string when there is nothing worth saying.
	 */
	function kaamase_rating_blocked_notice( $post_id, $code ) {

		if ( 'kaamase_no_hire' !== $code ) {
			return '';
		}

		$post    = get_post( $post_id );
		$user_id = get_current_user_id();

		if ( ! $post || ! $user_id ) {
			return '';
		}

		$about_worker = in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true );

		/*
		 * Opposite sides of the market, or nothing. An employer may rate
		 * a worker or a team; somebody with a worker profile may rate an
		 * employer. Anybody else is not waiting on a hire, they are
		 * simply not part of this pair.
		 */
		if ( $about_worker ) {
			$could_rate = user_can( $user_id, 'create_kaamase_jobs' );
		} elseif ( 'kaamase_employer' === $post->post_type ) {
			$could_rate = (bool) kaamase_get_user_profile( $user_id, 'kaamase_worker' );
		} else {
			return '';
		}

		if ( ! $could_rate ) {
			return '';
		}

		$notice = sprintf(
			'<div class="ka-notice ka-notice--info ka-mt-6" id="ka-rate"><div><p><strong>%1$s</strong></p><p class="ka-small">%2$s</p></div></div>',
			esc_html__( 'Ratings open once a hire is confirmed', 'kaamase-core' ),
			$about_worker
				? esc_html__( 'If you hired this person, say so and you will both be able to rate each other. Nobody sees a rating until you have both written one.', 'kaamase-core' )
				: esc_html__( 'If you have worked for them, say so and you will both be able to rate each other. Nobody sees a rating until you have both written one.', 'kaamase-core' )
		);

		/**
		 * Filter the notice shown in place of a rating form.
		 *
		 * Here so the way of confirming a hire can be added without this
		 * file knowing anything about how that is done.
		 *
		 * @since 1.5.0
		 * @param string $notice  The markup.
		 * @param int    $post_id Profile being looked at.
		 * @param string $code    Why rating was refused.
		 */
		return (string) apply_filters( 'kaamase_rating_blocked', $notice, $post_id, $code );
	}
}

if ( ! function_exists( 'kaamase_rating_form' ) ) {
	/**
	 * Render the rating form.
	 *
	 * Every answer defaults to yes. Most work goes fine, and a form that
	 * starts with everything unticked reads as an accusation waiting to
	 * be filled in.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile to rate.
	 * @return string Markup.
	 */
	function kaamase_rating_form( $post_id ) {

		$allowed = kaamase_can_rate( $post_id );

		if ( is_wp_error( $allowed ) ) {

			if ( 'kaamase_done' === $allowed->get_error_code() ) {
				return sprintf(
					'<div class="ka-notice ka-notice--ok"><p>%s</p></div>',
					esc_html__( 'Thank you, your rating is saved. It appears once the other side has written theirs.', 'kaamase-core' )
				);
			}

			return kaamase_rating_blocked_notice( $post_id, (string) $allowed->get_error_code() );
		}

		$post      = get_post( $post_id );
		$about     = 'kaamase_employer' === $post->post_type ? 'employer' : 'worker';
		$questions = kaamase_rating_questions( $about );

		ob_start();
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6" id="ka-rate">

			<h2><?php esc_html_e( 'How did it go?', 'kaamase-core' ); ?></h2>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'Nobody sees this until both of you have answered. You cannot be punished for being honest.', 'kaamase-core' ); ?>
			</p>

			<form method="post" action="" class="ka-stack ka-mt-6">

				<?php wp_nonce_field( 'kaamase_rating', 'kaamase_rating_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="submit_rating">
				<input type="hidden" name="kaamase_rate" value="<?php echo esc_attr( $post_id ); ?>">

				<?php foreach ( $questions as $key => $question ) : ?>
					<label class="ka-check">
						<input type="checkbox" name="q_<?php echo esc_attr( $key ); ?>" value="1" checked>
						<span><?php echo esc_html( $question['label'] ); ?></span>
					</label>
				<?php endforeach; ?>

				<div class="ka-field">
					<label class="ka-label" for="ka-note"><?php esc_html_e( 'Anything to add?', 'kaamase-core' ); ?></label>
					<textarea class="ka-textarea" id="ka-note" name="kaamase_note" maxlength="300"
						placeholder="<?php esc_attr_e( 'Optional. Keep it about the work.', 'kaamase-core' ); ?>"></textarea>
					<p class="ka-hint"><?php esc_html_e( 'This is shown publicly with your name on it.', 'kaamase-core' ); ?></p>
				</div>

				<button class="ka-btn ka-btn--primary ka-btn--lg" type="submit">
					<?php esc_html_e( 'Send rating', 'kaamase-core' ); ?>
				</button>

			</form>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_rating_list' ) ) {
	/**
	 * Render the ratings on a profile.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_rating_list( $post_id ) {

		if ( ! kaamase_rating_is_public( $post_id ) ) {

			return sprintf(
				'<section class="ka-card ka-card--flat ka-mt-6"><h2>%1$s</h2><p class="ka-soft ka-mt-4">%2$s</p></section>',
				esc_html__( 'Ratings', 'kaamase-core' ),
				esc_html__( 'Not enough ratings yet to show a score. We wait until three people have worked with somebody, so that one bad day does not decide everything.', 'kaamase-core' )
			);
		}

		$ratings = get_comments(
			array(
				'post_id' => absint( $post_id ),
				'type'    => 'kaamase_rating',
				'status'  => 'approve',
				'number'  => 10,
			)
		);

		if ( empty( $ratings ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="ka-mt-6">

			<h2><?php esc_html_e( 'Ratings', 'kaamase-core' ); ?></h2>

			<ul class="ka-stack ka-mt-4">
				<?php
				foreach ( $ratings as $comment ) :

					$score   = (float) get_comment_meta( $comment->comment_ID, 'kaamase_score', true );
					$answers = (array) get_comment_meta( $comment->comment_ID, 'kaamase_answers', true );
					$about   = (string) get_comment_meta( $comment->comment_ID, 'kaamase_about', true );
					$asked   = kaamase_rating_questions( $about ? $about : 'worker' );

					// Only the answers that were no. A row of ticks tells nobody anything.
					$negatives = array();

					foreach ( $asked as $key => $question ) {
						if ( empty( $answers[ $key ] ) ) {
							$negatives[] = $question['label'];
						}
					}
					?>
					<li class="ka-card ka-card--flat">

						<div class="ka-cluster ka-cluster--between">
							<strong><?php echo esc_html( $comment->comment_author ); ?></strong>
							<?php echo kaamase_rating( $score, 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<?php if ( $comment->comment_content ) : ?>
							<p class="ka-soft ka-mt-4"><?php echo esc_html( $comment->comment_content ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $negatives ) ) : ?>
							<ul class="ka-cluster ka-mt-4">
								<?php foreach ( $negatives as $label ) : ?>
									<li class="ka-badge ka-badge--neutral">
										<?php
										printf(
											/* translators: %s: the question that was answered no */
											esc_html__( 'No: %s', 'kaamase-core' ),
											esc_html( rtrim( $label, '?' ) )
										);
										?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<p class="ka-xs ka-mute ka-mt-4">
							<?php echo esc_html( get_comment_date( '', $comment->comment_ID ) ); ?>
						</p>

					</li>
				<?php endforeach; ?>
			</ul>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}

/**
 * Keep ratings out of the normal comment count and comment list.
 *
 * Without this a profile shows four comments where it means four
 * ratings, and any theme comment template tries to render them as blog
 * comments.
 *
 * @since 1.0.0
 * @param array $clauses Comment query clauses.
 * @return array Filtered clauses.
 */
function kaamase_exclude_ratings_from_comments( $clauses ) {

	global $wpdb;

	if ( is_admin() ) {
		return $clauses;
	}

	if ( false !== strpos( $clauses['where'], 'kaamase_rating' ) ) {
		return $clauses;
	}

	$clauses['where'] .= " AND {$wpdb->comments}.comment_type != 'kaamase_rating'";

	return $clauses;
}
add_filter( 'comments_clauses', 'kaamase_exclude_ratings_from_comments' );
