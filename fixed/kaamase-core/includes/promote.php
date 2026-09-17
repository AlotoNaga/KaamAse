<?php
/**
 * Promoted listings.
 *
 * An employer paying to have one job marked, or a worker paying to have
 * one profile marked, for a set number of days.
 *
 * What is being sold, and what is not
 * -----------------------------------
 * A slot. Not a position.
 *
 * exposure.php is unambiguous about this and it is worth quoting, because
 * everything in this file is built to keep it true:
 *
 *   "It is not ranking. Nobody is ranked above anybody on merit here, and
 *    nothing about paying moves a profile up. A hiring platform that
 *    sells position tells an employer that the worker who paid is the
 *    better worker, and the employer finds out on the site that this is
 *    false."
 *
 * So a promotion never reorders the list. It puts one clearly marked card
 * above it, and the queue underneath is exactly the queue exposure.php
 * decided. Nobody drops a place because somebody else paid. An employer
 * reading the list is reading the same honest list as before, plus one
 * thing that says Ad on it.
 *
 * That is also what makes it sellable to more than one agency at a time,
 * which is the point. Nobody can buy the top of Kaam Ase, because the top
 * of Kaam Ase is not for sale. What is for sale is a marked slot, shared
 * out among whoever bought it.
 *
 * The money does not come through this site
 * -----------------------------------------
 * Deliberately. Somebody asks, the owner telephones them, and payment
 * happens between two people on Google Pay like every other transaction
 * in this state. Then the owner starts it here by hand.
 *
 * This is the same shape as verify-requests.php and for the same reason:
 * the call is the product. It is what stops a placement racket buying a
 * promoted slot with a card at two in the morning, and on a platform
 * whose own job-screening file opens by naming trafficking as a live
 * risk in this region, that is not a small thing. Taking money to
 * promote a job is a louder act than merely hosting it, and the person
 * doing it should have spoken to somebody first.
 *
 * So there is no checkout here, no gateway, and no price stored in code.
 * There is a request, a queue, a telephone call, and a button.
 *
 * What runs out on its own
 * ------------------------
 * Everything. A promotion carries the day it ends and stops being live on
 * that day whether or not any scheduled task ran, because a paid thing
 * that quietly keeps running is how you give away three months, and a
 * paid thing that stops early is how you lose the customer.
 *
 * Phase one of three
 * ------------------
 * This file takes requests, holds the queue, starts and stops a run, and
 * keeps the ledger. It displays nothing anywhere yet. The Ad badge and
 * the slot come next, on the website and then in the app, in that order,
 * and views.php was built the same way for the same reason: the machinery
 * should be right and running before the first person sees a mark.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.10.0
 */

defined( 'ABSPATH' ) || exit;


/** Where the request stands: waiting, live, ended or no. */
define( 'KAAMASE_PROMO_STATE_KEY', KAAMASE_META_PREFIX . 'promo_state' );

/** The day it stops, as a timestamp. Queryable on its own for the sweep. */
define( 'KAAMASE_PROMO_ENDS_KEY', KAAMASE_META_PREFIX . 'promo_ends' );

/** What they asked for, and how to reach them. */
define( 'KAAMASE_PROMO_ASK_KEY', KAAMASE_META_PREFIX . 'promo_ask' );

/** The run that is going on now. */
define( 'KAAMASE_PROMO_RUN_KEY', KAAMASE_META_PREFIX . 'promo_run' );

/** Runs that have finished, newest last. */
define( 'KAAMASE_PROMO_LOG_KEY', KAAMASE_META_PREFIX . 'promo_log' );

/** Every run ever started, for the owner's own records. */
define( 'KAAMASE_PROMO_LEDGER', 'kaamase_promo_ledger' );

/** How long after a refusal before somebody may ask again. */
if ( ! defined( 'KAAMASE_PROMO_AGAIN_DAYS' ) ) {
	define( 'KAAMASE_PROMO_AGAIN_DAYS', 30 );
}

/** How many finished runs are kept against one listing. */
if ( ! defined( 'KAAMASE_PROMO_LOG_KEEP' ) ) {
	define( 'KAAMASE_PROMO_LOG_KEEP', 20 );
}


/* ==========================================================================
   1. WHAT A PROMOTION IS
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_lengths' ) ) {
	/**
	 * How long one can run for.
	 *
	 * Two, on purpose. Every extra option is one more thing to explain on
	 * a telephone call, and the owner is making that call himself.
	 *
	 * @since 1.10.0
	 * @return array Keyed by slug, each with days and a label.
	 */
	function kaamase_promo_lengths() {

		return (array) apply_filters(
			'kaamase_promo_lengths',
			array(
				'week'  => array(
					'days'  => 7,
					'label' => __( 'One week', 'kaamase-core' ),
				),
				'month' => array(
					'days'  => 30,
					'label' => __( 'One month', 'kaamase-core' ),
				),
			)
		);
	}
}

if ( ! function_exists( 'kaamase_promo_days' ) ) {
	/**
	 * How many days a length slug means.
	 *
	 * @since 1.10.0
	 * @param string $want Length slug.
	 * @return int Days, or 0 when the slug is not one of ours.
	 */
	function kaamase_promo_days( $want ) {

		$lengths = kaamase_promo_lengths();
		$want    = sanitize_key( $want );

		return isset( $lengths[ $want ] ) ? (int) $lengths[ $want ]['days'] : 0;
	}
}

if ( ! function_exists( 'kaamase_promo_types' ) ) {
	/**
	 * What can be promoted.
	 *
	 * A worker, a team or a job. Not an employer's own page: nobody is
	 * looking for employers, and a promoted slot pointing at a company
	 * profile is an advertisement for a company rather than a job, which
	 * is the one thing Google's job posting rules say a listing must
	 * never be.
	 *
	 * @since 1.10.0
	 * @return string[]
	 */
	function kaamase_promo_types() {

		return (array) apply_filters(
			'kaamase_promo_types',
			array( 'kaamase_worker', 'kaamase_gang', 'kaamase_job' )
		);
	}
}

if ( ! function_exists( 'kaamase_promo_state' ) ) {
	/**
	 * Where a listing's promotion stands.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return string waiting, live, ended, no, or an empty string.
	 */
	function kaamase_promo_state( $post_id ) {

		return (string) get_post_meta( absint( $post_id ), KAAMASE_PROMO_STATE_KEY, true );
	}
}

if ( ! function_exists( 'kaamase_promo_ends' ) ) {
	/**
	 * When it stops, as a timestamp.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return int 0 when nothing is running.
	 */
	function kaamase_promo_ends( $post_id ) {

		return (int) get_post_meta( absint( $post_id ), KAAMASE_PROMO_ENDS_KEY, true );
	}
}

if ( ! function_exists( 'kaamase_promo_end_of_day' ) ) {
	/**
	 * The last second of the day a timestamp falls in, here.
	 *
	 * A week bought at nine at night should not end at nine at night on
	 * the seventh day, six hours before the person selling it would say
	 * it ends. Days are whole days, counted in Nagaland's clock, which is
	 * how anybody would explain it out loud.
	 *
	 * @since 1.10.0
	 * @param int $when Timestamp.
	 * @return int Timestamp of 23:59:59 that day, site time.
	 */
	function kaamase_promo_end_of_day( $when ) {

		$zone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$day  = new DateTimeImmutable( '@' . (int) $when );

		return (int) $day->setTimezone( $zone )->setTime( 23, 59, 59 )->getTimestamp();
	}
}

if ( ! function_exists( 'kaamase_promo_showable' ) ) {
	/**
	 * Whether this listing is in a fit state to carry an advertisement.
	 *
	 * A job that closed halfway through its week must stop being promoted
	 * the moment it closes. The employer has paid for days that are no
	 * longer being delivered, which is a conversation the owner needs to
	 * have -- so the run is left standing and flagged on his screen
	 * rather than silently cancelled. What must not happen is workers
	 * being sent to a job nobody can take.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return bool
	 */
	function kaamase_promo_showable( $post_id ) {

		$post = get_post( absint( $post_id ) );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! in_array( $post->post_type, kaamase_promo_types(), true ) ) {
			return false;
		}

		if ( 'kaamase_job' === $post->post_type && function_exists( 'kaamase_job_is_open' ) ) {
			return (bool) kaamase_job_is_open( $post->ID );
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_promo_is_live' ) ) {
	/**
	 * Whether this listing should be carrying the mark right now.
	 *
	 * The clock decides, not the stored word. The daily sweep tidies
	 * state up and sends the message, but a promotion stops on its day
	 * even if the sweep never runs, because a task that failed on Tuesday
	 * must not hand anybody a free Wednesday.
	 *
	 * This is the one function everything else should ask. Phase two
	 * draws the badge from it; phase three sends it to the app.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return bool
	 */
	function kaamase_promo_is_live( $post_id ) {

		$post_id = absint( $post_id );

		if ( 'live' !== kaamase_promo_state( $post_id ) ) {
			return false;
		}

		$ends = kaamase_promo_ends( $post_id );

		if ( ! $ends || $ends < time() ) {
			return false;
		}

		return kaamase_promo_showable( $post_id );
	}
}

if ( ! function_exists( 'kaamase_promo_ask_data' ) ) {
	/**
	 * What they asked for, when they asked.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return array
	 */
	function kaamase_promo_ask_data( $post_id ) {

		$ask = get_post_meta( absint( $post_id ), KAAMASE_PROMO_ASK_KEY, true );

		return is_array( $ask ) ? $ask : array();
	}
}

if ( ! function_exists( 'kaamase_promo_run_data' ) ) {
	/**
	 * The run going on now, or the one that just finished.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return array
	 */
	function kaamase_promo_run_data( $post_id ) {

		$run = get_post_meta( absint( $post_id ), KAAMASE_PROMO_RUN_KEY, true );

		return is_array( $run ) ? $run : array();
	}
}

/* ==========================================================================
   2. WHAT A PERSON MAY PROMOTE
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_owns' ) ) {
	/**
	 * Whether this listing is this person's to sell space on.
	 *
	 * Asked on the server every time, never taken from the form. A posted
	 * listing id is a number somebody typed.
	 *
	 * @since 1.10.0
	 * @param int $user_id Who is asking.
	 * @param int $post_id Listing.
	 * @return bool
	 */
	function kaamase_promo_owns( $user_id, $post_id ) {

		$user_id = absint( $user_id );
		$post    = get_post( absint( $post_id ) );

		if ( ! $user_id || ! $post ) {
			return false;
		}

		if ( ! in_array( $post->post_type, kaamase_promo_types(), true ) ) {
			return false;
		}

		return (int) $post->post_author === $user_id;
	}
}

if ( ! function_exists( 'kaamase_promo_mine' ) ) {
	/**
	 * Everything this person could promote today.
	 *
	 * Their profile, and any job of theirs still open. A closed job is
	 * left out rather than shown greyed: the point of the list is what
	 * can be done now.
	 *
	 * @since 1.10.0
	 * @param int $user_id Who is asking.
	 * @return int[] Listing IDs.
	 */
	function kaamase_promo_mine( $user_id ) {

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array();
		}

		$mine = array();

		$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( $profile && kaamase_promo_showable( $profile ) && kaamase_promo_owns( $user_id, $profile ) ) {
			$mine[] = $profile;
		}

		$jobs = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'post_status'    => 'publish',
				'author'         => $user_id,
				'posts_per_page' => 30,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( (array) $jobs as $job_id ) {

			if ( kaamase_promo_showable( $job_id ) ) {
				$mine[] = (int) $job_id;
			}
		}

		return array_values( array_unique( array_map( 'absint', $mine ) ) );
	}
}

if ( ! function_exists( 'kaamase_promo_missing' ) ) {
	/**
	 * What is not ready yet, in sentences.
	 *
	 * Told rather than refused, for the same reason verify-requests.php
	 * does it: somebody who was trying to spend money and got only the
	 * word no does not try twice.
	 *
	 * @since 1.10.0
	 * @param int $user_id Who is asking.
	 * @param int $post_id Listing, or 0 to ask about the account only.
	 * @return string[] Empty when they may ask.
	 */
	function kaamase_promo_missing( $user_id, $post_id = 0 ) {

		$user_id = absint( $user_id );
		$post_id = absint( $post_id );
		$missing = array();

		if ( ! kaamase_promo_mine( $user_id ) ) {
			$missing[] = __( 'You need a live profile or an open job before anything can be promoted.', 'kaamase-core' );
			return $missing;
		}

		if ( $post_id && ! kaamase_promo_owns( $user_id, $post_id ) ) {
			$missing[] = __( 'That is not yours to promote.', 'kaamase-core' );
			return $missing;
		}

		if ( $post_id && ! kaamase_promo_showable( $post_id ) ) {
			$missing[] = __( 'That listing is not live at the moment, so there would be nothing to show.', 'kaamase-core' );
			return $missing;
		}

		/*
		 * A number to ring. The whole arrangement is a telephone call, so
		 * an account without one cannot be served at all -- and finding
		 * that out after somebody has filled in a form is worse than
		 * being told at the start.
		 */
		$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );
		$phone   = ( $profile && function_exists( 'kaamase_read_field' ) ) ? (string) kaamase_read_field( $profile, 'phone' ) : '';

		if ( '' === trim( $phone ) ) {
			$missing[] = __( 'Add your phone number to your profile. We arrange this over the telephone, so we cannot start without one.', 'kaamase-core' );
		}

		return $missing;
	}
}

if ( ! function_exists( 'kaamase_promo_can' ) ) {
	/**
	 * Whether this listing may be asked about right now.
	 *
	 * @since 1.10.0
	 * @param int $user_id Who is asking.
	 * @param int $post_id Listing.
	 * @return bool
	 */
	function kaamase_promo_can( $user_id, $post_id ) {

		$user_id = absint( $user_id );
		$post_id = absint( $post_id );

		if ( ! $user_id || ! $post_id ) {
			return false;
		}

		if ( kaamase_promo_missing( $user_id, $post_id ) ) {
			return false;
		}

		$state = kaamase_promo_state( $post_id );

		// Already in the queue, or already running. Nothing to ask for.
		if ( 'waiting' === $state || kaamase_promo_is_live( $post_id ) ) {
			return false;
		}

		/*
		 * A refusal holds for a month. A run that finished does not hold
		 * at all -- somebody asking again the day after their week ended
		 * is a renewal, and a renewal is the best telephone call the
		 * owner is going to get that day.
		 */
		if ( 'no' === $state ) {

			$ask   = kaamase_promo_ask_data( $post_id );
			$asked = isset( $ask['at'] ) ? (int) $ask['at'] : 0;

			return ( time() - $asked ) > ( KAAMASE_PROMO_AGAIN_DAYS * DAY_IN_SECONDS );
		}

		return true;
	}
}


/* ==========================================================================
   3. ASKING
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_record' ) ) {
	/**
	 * Put a listing in the queue.
	 *
	 * The phone number is copied in rather than looked up later, so the
	 * owner rings the number they gave when they asked. A profile edited
	 * in between should not quietly change who gets the call.
	 *
	 * @since 1.10.0
	 * @param int    $post_id Listing.
	 * @param string $want    Length slug they would like.
	 * @param string $note    Anything they want to say. Optional.
	 * @param string $when    Best time to ring them. Optional.
	 * @return bool Whether it was added.
	 */
	function kaamase_promo_record( $post_id, $want = 'week', $note = '', $when = '' ) {

		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$user_id = (int) $post->post_author;

		if ( ! kaamase_promo_can( $user_id, $post_id ) ) {
			return false;
		}

		$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );
		$phone   = ( $profile && function_exists( 'kaamase_read_field' ) ) ? (string) kaamase_read_field( $profile, 'phone' ) : '';

		$want = kaamase_promo_days( $want ) ? sanitize_key( $want ) : 'week';

		update_post_meta(
			$post_id,
			KAAMASE_PROMO_ASK_KEY,
			array(
				'at'    => time(),
				'want'  => $want,
				'note'  => mb_substr( sanitize_text_field( $note ), 0, 300 ),
				'when'  => mb_substr( sanitize_text_field( $when ), 0, 60 ),
				'phone' => mb_substr( sanitize_text_field( $phone ), 0, 24 ),
				'user'  => $user_id,
			)
		);

		update_post_meta( $post_id, KAAMASE_PROMO_STATE_KEY, 'waiting' );

		/**
		 * Fires when somebody asks to promote a listing.
		 *
		 * @since 1.10.0
		 * @param int $post_id Listing.
		 * @param int $user_id Who asked.
		 */
		do_action( 'kaamase_promo_asked', $post_id, $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_promo_find' ) ) {
	/**
	 * Listings in a given state, newest request first.
	 *
	 * One query for every list the owner's screen shows, because they are
	 * the same query with a different word in it.
	 *
	 * @since 1.10.0
	 * @param string $state waiting, live, ended or no.
	 * @param int    $limit How many at most.
	 * @return int[] Listing IDs.
	 */
	function kaamase_promo_find( $state, $limit = 100 ) {

		$found = get_posts(
			array(
				'post_type'        => kaamase_promo_types(),
				'post_status'      => array( 'publish', 'draft', 'pending', 'kaamase_closed' ),
				'posts_per_page'   => absint( $limit ),
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'suppress_filters' => false,
				'meta_key'         => KAAMASE_PROMO_STATE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => sanitize_key( $state ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return array_map( 'absint', (array) $found );
	}
}

/* ==========================================================================
   4. STARTING, STOPPING, AND THE RECORD OF WHAT WAS SOLD
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_ledger' ) ) {
	/**
	 * Every run ever started.
	 *
	 * Kept as one option rather than worked out by walking the listings,
	 * because this is not a report -- it is the owner's book of what he
	 * took and from whom, and it has to survive a listing being deleted.
	 * Somebody who paid in April and whose job was binned in May still
	 * paid in April.
	 *
	 * Autoload is off. This is read on one admin screen and nowhere else,
	 * and a growing option loaded into memory on every request on the
	 * site is exactly the fault section 6 fixed for the Razorpay keys.
	 *
	 * @since 1.10.0
	 * @return array Rows, oldest first.
	 */
	function kaamase_promo_ledger() {

		$rows = get_option( KAAMASE_PROMO_LEDGER, array() );

		return is_array( $rows ) ? $rows : array();
	}
}

if ( ! function_exists( 'kaamase_promo_ledger_add' ) ) {
	/**
	 * Write one line in the book.
	 *
	 * @since 1.10.0
	 * @param array $row What was sold.
	 * @return void
	 */
	function kaamase_promo_ledger_add( $row ) {

		$rows   = kaamase_promo_ledger();
		$rows[] = (array) $row;

		update_option( KAAMASE_PROMO_LEDGER, array_slice( $rows, -2000 ), false );
	}
}

if ( ! function_exists( 'kaamase_promo_year_start' ) ) {
	/**
	 * When the current Indian financial year began.
	 *
	 * The first of April, here. The takings total on the owner's screen
	 * is counted from it because that is the number an accountant asks
	 * for, and because the GST registration threshold for services in a
	 * special category state -- which Nagaland is -- is reached a good
	 * deal sooner than most Indian guidance written for Delhi suggests.
	 * Better for him to watch that line arrive than to meet it.
	 *
	 * @since 1.10.0
	 * @return int Timestamp.
	 */
	function kaamase_promo_year_start() {

		$zone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$now  = new DateTimeImmutable( 'now', $zone );
		$year = (int) $now->format( 'Y' );

		// January to March still belongs to the year before.
		if ( (int) $now->format( 'n' ) < 4 ) {
			$year--;
		}

		return (int) $now->setDate( $year, 4, 1 )->setTime( 0, 0, 0 )->getTimestamp();
	}
}

if ( ! function_exists( 'kaamase_promo_taken' ) ) {
	/**
	 * How much has been taken since a date.
	 *
	 * @since 1.10.0
	 * @param int $since Timestamp, or 0 for everything.
	 * @return array total in rupees, and how many runs.
	 */
	function kaamase_promo_taken( $since = 0 ) {

		$since = (int) $since;
		$total = 0;
		$runs  = 0;

		foreach ( kaamase_promo_ledger() as $row ) {

			$at = isset( $row['at'] ) ? (int) $row['at'] : 0;

			if ( $since && $at < $since ) {
				continue;
			}

			$total += isset( $row['paid'] ) ? (int) $row['paid'] : 0;
			$runs++;
		}

		return array(
			'total' => $total,
			'runs'  => $runs,
		);
	}
}

if ( ! function_exists( 'kaamase_promo_start' ) ) {
	/**
	 * Start a run, after the call and after the money.
	 *
	 * The reference is the Google Pay transaction number and it is not
	 * decoration. Without it, "but I paid you" is an argument with no
	 * facts in it, and on a platform where every payment happens between
	 * two phones there is nothing else to point at.
	 *
	 * @since 1.10.0
	 * @param int    $post_id Listing.
	 * @param int    $days    How many days it runs.
	 * @param int    $paid    Rupees taken.
	 * @param string $ref     Google Pay reference.
	 * @param string $note    Anything the owner wants to remember.
	 * @return bool Whether it started.
	 */
	function kaamase_promo_start( $post_id, $days, $paid = 0, $ref = '', $note = '' ) {

		$post_id = absint( $post_id );
		$days    = absint( $days );
		$post    = get_post( $post_id );

		if ( ! $post || ! $days ) {
			return false;
		}

		if ( ! in_array( $post->post_type, kaamase_promo_types(), true ) ) {
			return false;
		}

		$now  = time();
		$ends = kaamase_promo_end_of_day( $now + ( ( $days - 1 ) * DAY_IN_SECONDS ) );

		$run = array(
			'started' => $now,
			'ends'    => $ends,
			'days'    => $days,
			'paid'    => absint( $paid ),
			'ref'     => mb_substr( sanitize_text_field( $ref ), 0, 60 ),
			'note'    => mb_substr( sanitize_text_field( $note ), 0, 300 ),
			'by'      => get_current_user_id(),
		);

		update_post_meta( $post_id, KAAMASE_PROMO_RUN_KEY, $run );
		update_post_meta( $post_id, KAAMASE_PROMO_ENDS_KEY, $ends );
		update_post_meta( $post_id, KAAMASE_PROMO_STATE_KEY, 'live' );

		kaamase_promo_ledger_add(
			array(
				'at'    => $now,
				'post'  => $post_id,
				'title' => mb_substr( (string) get_the_title( $post ), 0, 120 ),
				'type'  => (string) $post->post_type,
				'user'  => (int) $post->post_author,
				'days'  => $days,
				'paid'  => absint( $paid ),
				'ref'   => $run['ref'],
				'ends'  => $ends,
			)
		);

		kaamase_promo_tell( $post_id, 'started' );

		/**
		 * Fires when a promotion starts.
		 *
		 * @since 1.10.0
		 * @param int   $post_id Listing.
		 * @param array $run     What was agreed.
		 */
		do_action( 'kaamase_promo_started', $post_id, $run );

		return true;
	}
}

if ( ! function_exists( 'kaamase_promo_finish' ) ) {
	/**
	 * End a run and file it.
	 *
	 * Used by the daily sweep when a run reaches its day, and by the Stop
	 * button when the owner needs one off the site this minute -- which
	 * is the case that matters. Finding out on Tuesday that the agency
	 * you promoted on Monday is a racket is not the moment to be editing
	 * a database by hand.
	 *
	 * @since 1.10.0
	 * @param int    $post_id Listing.
	 * @param string $why     ran_out or stopped.
	 * @param bool   $tell    Whether to say so.
	 * @return bool Whether anything was ended.
	 */
	function kaamase_promo_finish( $post_id, $why = 'ran_out', $tell = true ) {

		$post_id = absint( $post_id );

		if ( 'live' !== kaamase_promo_state( $post_id ) ) {
			return false;
		}

		$run = kaamase_promo_run_data( $post_id );

		$run['finished'] = time();
		$run['why']      = 'stopped' === $why ? 'stopped' : 'ran_out';

		$log = get_post_meta( $post_id, KAAMASE_PROMO_LOG_KEY, true );
		$log = is_array( $log ) ? $log : array();
		$log[] = $run;

		update_post_meta( $post_id, KAAMASE_PROMO_LOG_KEY, array_slice( $log, -KAAMASE_PROMO_LOG_KEEP ) );
		update_post_meta( $post_id, KAAMASE_PROMO_STATE_KEY, 'ended' );
		update_post_meta( $post_id, KAAMASE_PROMO_ENDS_KEY, 0 );
		delete_post_meta( $post_id, KAAMASE_PROMO_RUN_KEY );

		if ( $tell ) {
			kaamase_promo_tell( $post_id, 'stopped' === $run['why'] ? 'stopped' : 'ended' );
		}

		/**
		 * Fires when a promotion ends.
		 *
		 * @since 1.10.0
		 * @param int   $post_id Listing.
		 * @param array $run     The run that ended.
		 */
		do_action( 'kaamase_promo_ended', $post_id, $run );

		return true;
	}
}

if ( ! function_exists( 'kaamase_promo_refuse' ) ) {
	/**
	 * Take a request off the list without starting anything.
	 *
	 * Nothing is sent. A refusal delivered by notification, with no
	 * person attached to explain it, reads as a door closing -- and the
	 * owner has their number and is going to ring them anyway.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return bool
	 */
	function kaamase_promo_refuse( $post_id ) {

		$post_id = absint( $post_id );

		if ( 'waiting' !== kaamase_promo_state( $post_id ) ) {
			return false;
		}

		$ask       = kaamase_promo_ask_data( $post_id );
		$ask['at'] = time();

		update_post_meta( $post_id, KAAMASE_PROMO_ASK_KEY, $ask );
		update_post_meta( $post_id, KAAMASE_PROMO_STATE_KEY, 'no' );

		return true;
	}
}

if ( ! function_exists( 'kaamase_promo_tell' ) ) {
	/**
	 * Say what has happened, by whatever reaches them.
	 *
	 * Through kaamase_notify_user rather than push alone, because half
	 * the people on this platform never installed the app and somebody
	 * who has paid money is owed the message either way. The date is in
	 * it on purpose: an advertisement with an end date nobody was told is
	 * an argument waiting to happen.
	 *
	 * @since 1.10.0
	 * @param int    $post_id Listing.
	 * @param string $what    started, ended or stopped.
	 * @return void
	 */
	function kaamase_promo_tell( $post_id, $what ) {

		if ( ! function_exists( 'kaamase_notify_user' ) ) {
			return;
		}

		$post = get_post( absint( $post_id ) );

		if ( ! $post ) {
			return;
		}

		$owner = (int) $post->post_author;

		if ( ! $owner ) {
			return;
		}

		$name = (string) get_the_title( $post );
		$url  = function_exists( 'kaamase_page_url' ) ? (string) kaamase_page_url( 'dashboard' ) : '';

		if ( 'started' === $what ) {

			$ends = kaamase_promo_ends( $post_id );

			$title = __( 'Your promotion is running', 'kaamase-core' );
			$body  = sprintf(
				/* translators: 1: job or profile name, 2: the date it ends */
				__( '%1$s is being promoted on Kaam Ase until %2$s.', 'kaamase-core' ),
				$name,
				date_i18n( (string) get_option( 'date_format', 'j F Y' ), $ends )
			);

		} elseif ( 'stopped' === $what ) {

			$title = __( 'Your promotion has been stopped', 'kaamase-core' );
			$body  = sprintf(
				/* translators: %s: job or profile name */
				__( 'The promotion on %s has been stopped. Ring us if you were not expecting this.', 'kaamase-core' ),
				$name
			);

		} else {

			$title = __( 'Your promotion has finished', 'kaamase-core' );
			$body  = sprintf(
				/* translators: %s: job or profile name */
				__( 'The promotion on %s has run its time. Tell us if you would like another.', 'kaamase-core' ),
				$name
			);
		}

		kaamase_notify_user(
			$owner,
			$title,
			$body,
			array(
				'type' => 'promotion',
				'id'   => (int) $post->ID,
				'what' => (string) $what,
			),
			$url
		);
	}
}


/* ==========================================================================
   5. RUNNING OUT

   The clock in kaamase_promo_is_live() is what actually stops a
   promotion showing. This tidies the state up behind it and sends the
   message, which is why a day the task did not run costs a notification
   rather than a week of free advertising.
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_sweep' ) ) {
	/**
	 * Finish anything that has reached its day.
	 *
	 * @since 1.10.0
	 * @return int How many ended.
	 */
	function kaamase_promo_sweep() {

		$done = 0;

		foreach ( kaamase_promo_find( 'live', 200 ) as $post_id ) {

			$ends = kaamase_promo_ends( $post_id );

			if ( $ends && $ends >= time() ) {
				continue;
			}

			$done += kaamase_promo_finish( $post_id, 'ran_out' ) ? 1 : 0;
		}

		return $done;
	}
}
add_action( 'kaamase_daily', 'kaamase_promo_sweep' );

/* ==========================================================================
   6. THE CARD ON SOMEBODY'S DASHBOARD
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_copy' ) ) {
	/**
	 * What the card says.
	 *
	 * In one place because the app shows the same words, and two copies
	 * of a sentence drift apart the first time one of them is improved.
	 *
	 * No price anywhere. The price is settled on the telephone, where it
	 * can be different for a worker and for a recruitment agency without
	 * either of them reading the other's number off a page.
	 *
	 * @since 1.10.0
	 * @return array
	 */
	function kaamase_promo_copy() {

		return array(
			'title'   => __( 'Promote a job or your profile', 'kaamase-core' ),
			'body'    => __( 'We can put one of your listings in a marked slot at the top of the list for a week or a month. It is shown with an Ad label, and nothing else on the list moves for it.', 'kaamase-core' ),
			'how'     => __( 'Tell us which one and we will ring you to arrange it. Nothing is charged here.', 'kaamase-core' ),
			'button'  => __( 'Ask about promoting this', 'kaamase-core' ),
			'waiting' => __( 'We have your request and will ring you. There is nothing to pay until we have spoken.', 'kaamase-core' ),
			'live'    => __( 'This is being promoted now.', 'kaamase-core' ),
			'before'  => __( 'Before we can arrange this:', 'kaamase-core' ),
			'closed'  => __( 'You asked about this one recently. Ring us if you have not heard back.', 'kaamase-core' ),
			'which'   => __( 'Which one?', 'kaamase-core' ),
			'length'  => __( 'How long?', 'kaamase-core' ),
			'when'    => __( 'Best time to ring you', 'kaamase-core' ),
			'note'    => __( 'Anything we should know', 'kaamase-core' ),
			'safety'  => __( 'We never ask for money to apply for a job, and we never promote a job that asks a worker to pay.', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_promo_card' ) ) {
	/**
	 * Draw the card.
	 *
	 * @since 1.10.0
	 * @param int    $user_id Who is looking.
	 * @param int    $profile Their profile.
	 * @param string $type    Which kind of profile.
	 * @return void
	 */
	function kaamase_promo_card( $user_id, $profile, $type ) {

		unset( $profile, $type );

		$user_id = absint( $user_id );
		$mine    = kaamase_promo_mine( $user_id );
		$copy    = kaamase_promo_copy();

		if ( empty( $mine ) ) {
			return;
		}

		$can     = array();
		$waiting = array();
		$live    = array();

		foreach ( $mine as $post_id ) {

			if ( kaamase_promo_is_live( $post_id ) ) {
				$live[] = $post_id;
			} elseif ( 'waiting' === kaamase_promo_state( $post_id ) ) {
				$waiting[] = $post_id;
			} elseif ( kaamase_promo_can( $user_id, $post_id ) ) {
				$can[] = $post_id;
			}
		}

		$missing = kaamase_promo_missing( $user_id );
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">

			<h2><?php echo esc_html( $copy['title'] ); ?></h2>

			<p class="ka-small ka-soft ka-mt-4"><?php echo esc_html( $copy['body'] ); ?></p>

			<?php foreach ( $live as $post_id ) : ?>
				<p class="ka-mt-4">
					<strong><?php echo esc_html( get_the_title( $post_id ) ); ?></strong> &mdash;
					<?php
					printf(
						/* translators: %s: the date the promotion ends */
						esc_html__( 'promoted until %s.', 'kaamase-core' ),
						esc_html( date_i18n( (string) get_option( 'date_format', 'j F Y' ), kaamase_promo_ends( $post_id ) ) )
					);
					?>
				</p>
			<?php endforeach; ?>

			<?php foreach ( $waiting as $post_id ) : ?>
				<p class="ka-mt-4">
					<strong><?php echo esc_html( get_the_title( $post_id ) ); ?></strong> &mdash;
					<?php echo esc_html( $copy['waiting'] ); ?>
				</p>
			<?php endforeach; ?>

			<?php if ( $missing ) : ?>

				<p class="ka-mt-4"><?php echo esc_html( $copy['before'] ); ?></p>

				<ul class="ka-mt-4">
					<?php foreach ( $missing as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>

			<?php elseif ( empty( $can ) ) : ?>

				<?php if ( empty( $live ) && empty( $waiting ) ) : ?>
					<p class="ka-mt-4"><?php echo esc_html( $copy['closed'] ); ?></p>
				<?php endif; ?>

			<?php else : ?>

				<p class="ka-small ka-soft ka-mt-4"><?php echo esc_html( $copy['how'] ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ka-mt-4">

					<input type="hidden" name="action" value="kaamase_promo_ask">
					<?php wp_nonce_field( 'kaamase_promo_ask' ); ?>

					<p>
						<label for="ka-promo-post"><?php echo esc_html( $copy['which'] ); ?></label><br>
						<select name="post" id="ka-promo-post" required>
							<?php foreach ( $can as $post_id ) : ?>
								<option value="<?php echo esc_attr( (string) $post_id ); ?>">
									<?php echo esc_html( get_the_title( $post_id ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label for="ka-promo-want"><?php echo esc_html( $copy['length'] ); ?></label><br>
						<select name="want" id="ka-promo-want">
							<?php foreach ( kaamase_promo_lengths() as $slug => $length ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>">
									<?php echo esc_html( $length['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label for="ka-promo-when"><?php echo esc_html( $copy['when'] ); ?></label><br>
						<input type="text" name="when" id="ka-promo-when" maxlength="60" autocomplete="off">
					</p>

					<p>
						<label for="ka-promo-note"><?php echo esc_html( $copy['note'] ); ?></label><br>
						<textarea name="note" id="ka-promo-note" rows="2" maxlength="300"></textarea>
					</p>

					<button class="ka-btn ka-btn--outline" type="submit">
						<?php echo esc_html( $copy['button'] ); ?>
					</button>

					<p class="ka-small ka-soft ka-mt-4"><?php echo esc_html( $copy['safety'] ); ?></p>

				</form>

			<?php endif; ?>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_promo_card', 60, 3 );

if ( ! function_exists( 'kaamase_promo_handle' ) ) {
	/**
	 * Take the request from the website form.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_promo_handle() {

		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Sign in first.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_promo_ask' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked above.
		$post_id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
		$want    = isset( $_POST['want'] ) ? sanitize_key( wp_unslash( $_POST['want'] ) ) : 'week';
		$note    = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
		$when    = isset( $_POST['when'] ) ? sanitize_text_field( wp_unslash( $_POST['when'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		/*
		 * Ownership is decided here, from the session, never from the
		 * form. The select box lists their own listings; the posted
		 * number is still only a number somebody sent.
		 */
		if ( kaamase_promo_owns( get_current_user_id(), $post_id ) ) {
			kaamase_promo_record( $post_id, $want, $note, $when );
		}

		$back = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : '';

		wp_safe_redirect( $back ? $back : home_url( '/' ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_promo_ask', 'kaamase_promo_handle' );


/* ==========================================================================
   7. THE APP

   The route exists now so the app can be built against it whenever that
   release happens. Nothing here waits on it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_shape_me' ) ) {
	/**
	 * Tell the app what this person could promote, and where each stands.
	 *
	 * @since 1.10.0
	 * @param array $me      The account object.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_promo_shape_me( $me, $user_id ) {

		$user_id = absint( $user_id );
		$listing = array();

		foreach ( kaamase_promo_mine( $user_id ) as $post_id ) {

			$listing[] = array(
				'id'    => (int) $post_id,
				'name'  => (string) get_the_title( $post_id ),
				'kind'  => 'kaamase_job' === get_post_type( $post_id ) ? 'job' : 'profile',
				'state' => kaamase_promo_is_live( $post_id ) ? 'live' : kaamase_promo_state( $post_id ),
				'ends'  => (int) kaamase_promo_ends( $post_id ),
				'can'   => kaamase_promo_can( $user_id, $post_id ),
			);
		}

		$me['promote'] = array(
			'mine'    => $listing,
			'missing' => array_values( kaamase_promo_missing( $user_id ) ),
			'lengths' => kaamase_promo_lengths(),
			'copy'    => kaamase_promo_copy(),
		);

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_promo_shape_me', 27, 2 );

if ( ! function_exists( 'kaamase_promo_route' ) ) {
	/**
	 * Let the app ask.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_promo_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/promote',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_promo_rest',
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_promo_route' );

if ( ! function_exists( 'kaamase_promo_rest' ) ) {
	/**
	 * Take a request from the app.
	 *
	 * @since 1.10.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_promo_rest( $request ) {

		$user_id = get_current_user_id();
		$post_id = absint( $request->get_param( 'post' ) );
		$want    = sanitize_key( (string) $request->get_param( 'want' ) );
		$note    = sanitize_text_field( (string) $request->get_param( 'note' ) );
		$when    = sanitize_text_field( (string) $request->get_param( 'when' ) );

		if ( ! kaamase_promo_owns( $user_id, $post_id ) ) {

			$error = new WP_Error(
				'kaamase_not_yours',
				__( 'That is not yours to promote.', 'kaamase-core' ),
				array( 'status' => 403 )
			);

			return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $error ) : $error;
		}

		$missing = kaamase_promo_missing( $user_id, $post_id );

		if ( $missing ) {

			$error = new WP_Error( 'kaamase_not_ready', implode( ' ', $missing ), array( 'status' => 400 ) );

			return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $error ) : $error;
		}

		kaamase_promo_record( $post_id, $want, $note, $when );

		return rest_ensure_response(
			array(
				'ok'    => true,
				'state' => kaamase_promo_is_live( $post_id ) ? 'live' : kaamase_promo_state( $post_id ),
				'ends'  => (int) kaamase_promo_ends( $post_id ),
			)
		);
	}
}

/* ==========================================================================
   8. THE OWNER'S SCREEN

   Three lists and a total: who is waiting to be rung, what is running,
   and what has just finished. The last one is not tidiness -- somebody
   whose week ended yesterday is the easiest telephone call of the day.
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_menu' ) ) {
	/**
	 * Add the screen under Kaam Ase, with a count on it.
	 *
	 * manage_options rather than the capability the verify queue uses.
	 * Money is entered on this screen, and an editor who can approve
	 * profiles has no business writing down what was taken for one.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_promo_menu() {

		$label = __( 'Promotions', 'kaamase-core' );

		if ( current_user_can( 'manage_options' ) ) {

			$waiting = count( kaamase_promo_find( 'waiting', 100 ) );

			if ( $waiting ) {
				$label .= sprintf(
					' <span class="awaiting-mod"><span class="pending-count">%d</span></span>',
					(int) $waiting
				);
			}
		}

		add_submenu_page(
			'kaamase',
			__( 'Promotions', 'kaamase-core' ),
			$label,
			'manage_options',
			'kaamase-promotions',
			'kaamase_promo_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_promo_menu', 26 );

if ( ! function_exists( 'kaamase_promo_who' ) ) {
	/**
	 * A line naming the owner of a listing and how to ring them.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return string
	 */
	function kaamase_promo_who( $post_id ) {

		$post = get_post( absint( $post_id ) );

		if ( ! $post ) {
			return '';
		}

		$user = get_userdata( (int) $post->post_author );
		$ask  = kaamase_promo_ask_data( $post_id );

		$phone = isset( $ask['phone'] ) ? (string) $ask['phone'] : '';

		if ( '' === $phone ) {
			$profile = (int) get_user_meta( (int) $post->post_author, 'kaamase_profile_id', true );
			$phone   = ( $profile && function_exists( 'kaamase_read_field' ) ) ? (string) kaamase_read_field( $profile, 'phone' ) : '';
		}

		$parts = array( $user ? $user->display_name : __( 'Unknown', 'kaamase-core' ) );

		if ( '' !== trim( $phone ) ) {
			$parts[] = $phone;
		}

		return implode( ' — ', $parts );
	}
}

if ( ! function_exists( 'kaamase_promo_page' ) ) {
	/**
	 * Render the screen.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_promo_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$done = isset( $_GET['done'] ) ? sanitize_key( wp_unslash( $_GET['done'] ) ) : '';

		$waiting = kaamase_promo_find( 'waiting', 100 );
		$live    = kaamase_promo_find( 'live', 100 );
		$ended   = kaamase_promo_find( 'ended', 25 );
		$year    = kaamase_promo_taken( kaamase_promo_year_start() );
		$lengths = kaamase_promo_lengths();
		$date    = (string) get_option( 'date_format', 'j F Y' );
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Promotions', 'kaamase-core' ); ?></h1>

			<?php if ( 'started' === $done ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Running. It stops on its own and they have been told the date.', 'kaamase-core' ); ?></p>
				</div>
			<?php elseif ( 'stopped' === $done ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Stopped. It is off the site now.', 'kaamase-core' ); ?></p>
				</div>
			<?php elseif ( 'no' === $done ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Taken off the list. They can ask again in a month. Nothing was sent to them.', 'kaamase-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p style="max-width:48em">
				<?php esc_html_e( 'Somebody asks here, you ring them, they pay you on Google Pay, and then you start it below. Nothing is charged by the website. Whatever you start stops on its own, so there is nothing to remember to switch off.', 'kaamase-core' ); ?>
			</p>

			<p style="max-width:48em">
				<strong><?php esc_html_e( 'Before you take money for a job:', 'kaamase-core' ); ?></strong>
				<?php esc_html_e( 'ask for the company name and GST number, an address, and who pays for travel. If a worker is asked to pay anything at any stage, say no. Promoting a job is a stronger thing than hosting one.', 'kaamase-core' ); ?>
			</p>

			<h2 style="margin-top:1.4em">
				<?php
				printf(
					/* translators: 1: rupee total, 2: number of promotions */
					esc_html__( 'Taken this financial year: ₹%1$s across %2$s', 'kaamase-core' ),
					esc_html( number_format_i18n( $year['total'] ) ),
					esc_html(
						sprintf(
							/* translators: %s: number of promotions */
							_n( '%s promotion', '%s promotions', $year['runs'], 'kaamase-core' ),
							number_format_i18n( $year['runs'] )
						)
					)
				);
				?>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kaamase_promo_csv' ), 'kaamase_promo_csv' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Download the book', 'kaamase-core' ); ?>
				</a>
			</h2>

			<p class="description" style="max-width:48em">
				<?php esc_html_e( 'Counted from 1 April. Worth watching: once a business in Nagaland passes ten lakh of service turnover in a year it has to register for GST, and advertising is a service. Ask an accountant before you get near it, not after.', 'kaamase-core' ); ?>
			</p>

			<h2><?php esc_html_e( 'Waiting to be rung', 'kaamase-core' ); ?></h2>

			<?php if ( empty( $waiting ) ) : ?>

				<p><?php esc_html_e( 'Nobody waiting.', 'kaamase-core' ); ?></p>

			<?php else : ?>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'What', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Who to ring', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Asked', 'kaamase-core' ); ?></th>
							<th style="width:38%"><?php esc_html_e( 'Start it', 'kaamase-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $waiting as $post_id ) : ?>
						<?php
						$ask  = kaamase_promo_ask_data( $post_id );
						$want = isset( $ask['want'] ) ? (string) $ask['want'] : 'week';
						$days = kaamase_promo_days( $want );
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>">
									<?php echo esc_html( get_the_title( $post_id ) ); ?>
								</a>
								<br>
								<span class="description">
									<?php echo esc_html( 'kaamase_job' === get_post_type( $post_id ) ? __( 'Job', 'kaamase-core' ) : __( 'Profile', 'kaamase-core' ) ); ?>
								</span>
								<?php if ( ! kaamase_promo_showable( $post_id ) ) : ?>
									<br><strong><?php esc_html_e( 'Not live at the moment.', 'kaamase-core' ); ?></strong>
								<?php endif; ?>
							</td>
							<td>
								<?php echo esc_html( kaamase_promo_who( $post_id ) ); ?>
								<?php if ( ! empty( $ask['when'] ) ) : ?>
									<br><span class="description"><?php echo esc_html( $ask['when'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $ask['note'] ) ) : ?>
									<br><em><?php echo esc_html( $ask['note'] ); ?></em>
								<?php endif; ?>
							</td>
							<td>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: length of time, e.g. "2 days" */
										__( '%s ago', 'kaamase-core' ),
										human_time_diff( isset( $ask['at'] ) ? (int) $ask['at'] : time() )
									)
								);
								?>
								<br>
								<span class="description">
									<?php
									echo esc_html(
										isset( $lengths[ $want ] ) ? sprintf(
											/* translators: %s: length they asked for */
											__( 'Asked for: %s', 'kaamase-core' ),
											$lengths[ $want ]['label']
										) : ''
									);
									?>
								</span>
							</td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

									<input type="hidden" name="action" value="kaamase_promo_decide">
									<input type="hidden" name="post" value="<?php echo esc_attr( (string) $post_id ); ?>">
									<?php wp_nonce_field( 'kaamase_promo_decide_' . $post_id ); ?>

									<label>
										<?php esc_html_e( 'Days', 'kaamase-core' ); ?>
										<input type="number" name="days" value="<?php echo esc_attr( (string) ( $days ? $days : 7 ) ); ?>" min="1" max="365" style="width:5em">
									</label>
									<label>
										<?php esc_html_e( '₹', 'kaamase-core' ); ?>
										<input type="number" name="paid" value="" min="0" max="1000000" style="width:7em" placeholder="<?php esc_attr_e( 'amount', 'kaamase-core' ); ?>">
									</label>
									<br>
									<label style="display:block;margin-top:.4em">
										<?php esc_html_e( 'Google Pay reference', 'kaamase-core' ); ?>
										<input type="text" name="ref" value="" maxlength="60" style="width:14em">
									</label>

									<p style="margin:.6em 0 0">
										<button class="button button-primary" type="submit" name="decision" value="start">
											<?php esc_html_e( 'Start it', 'kaamase-core' ); ?>
										</button>
										<button class="button" type="submit" name="decision" value="no">
											<?php esc_html_e( 'Not this one', 'kaamase-core' ); ?>
										</button>
									</p>

								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

			<?php endif; ?>

			<h2 style="margin-top:1.6em">
				<?php
				printf(
					/* translators: %s: how many promotions are running */
					esc_html__( 'Running now (%s)', 'kaamase-core' ),
					esc_html( number_format_i18n( count( $live ) ) )
				);
				?>
			</h2>

			<?php if ( empty( $live ) ) : ?>

				<p><?php esc_html_e( 'Nothing running.', 'kaamase-core' ); ?></p>

			<?php else : ?>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'What', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Who', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Until', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Paid', 'kaamase-core' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $live as $post_id ) : ?>
						<?php $run = kaamase_promo_run_data( $post_id ); ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>">
									<?php echo esc_html( get_the_title( $post_id ) ); ?>
								</a>
								<?php if ( ! kaamase_promo_showable( $post_id ) ) : ?>
									<br><strong><?php esc_html_e( 'Closed or unpublished — this is not being shown. They have paid for days they are not getting.', 'kaamase-core' ); ?></strong>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( kaamase_promo_who( $post_id ) ); ?></td>
							<td>
								<?php echo esc_html( date_i18n( $date, kaamase_promo_ends( $post_id ) ) ); ?>
								<br>
								<span class="description">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: length of time, e.g. "3 days" */
											__( '%s left', 'kaamase-core' ),
											human_time_diff( time(), max( time(), kaamase_promo_ends( $post_id ) ) )
										)
									);
									?>
								</span>
							</td>
							<td>
								<?php echo esc_html( '₹' . number_format_i18n( isset( $run['paid'] ) ? (int) $run['paid'] : 0 ) ); ?>
								<?php if ( ! empty( $run['ref'] ) ) : ?>
									<br><span class="description"><?php echo esc_html( $run['ref'] ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="kaamase_promo_decide">
									<input type="hidden" name="post" value="<?php echo esc_attr( (string) $post_id ); ?>">
									<?php wp_nonce_field( 'kaamase_promo_decide_' . $post_id ); ?>
									<button class="button" type="submit" name="decision" value="stop">
										<?php esc_html_e( 'Stop now', 'kaamase-core' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

			<?php endif; ?>

			<?php if ( ! empty( $ended ) ) : ?>

				<h2 style="margin-top:1.6em"><?php esc_html_e( 'Just finished — worth a ring', 'kaamase-core' ); ?></h2>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'What', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Who', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Ended', 'kaamase-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $ended as $post_id ) : ?>
						<?php
						$log  = get_post_meta( $post_id, KAAMASE_PROMO_LOG_KEY, true );
						$log  = is_array( $log ) ? $log : array();
						$last = $log ? end( $log ) : array();
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>">
									<?php echo esc_html( get_the_title( $post_id ) ); ?>
								</a>
							</td>
							<td><?php echo esc_html( kaamase_promo_who( $post_id ) ); ?></td>
							<td>
								<?php
								echo esc_html(
									! empty( $last['finished'] )
										? date_i18n( $date, (int) $last['finished'] )
										: __( 'Recently', 'kaamase-core' )
								);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

			<?php endif; ?>

		</div>
		<?php
	}
}

if ( ! function_exists( 'kaamase_promo_decide' ) ) {
	/**
	 * Start one, stop one, or take one off the list.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_promo_decide() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked immediately below.
		$post_id  = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
		$days     = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 0;
		$paid     = isset( $_POST['paid'] ) ? absint( $_POST['paid'] ) : 0;
		$ref      = isset( $_POST['ref'] ) ? sanitize_text_field( wp_unslash( $_POST['ref'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		check_admin_referer( 'kaamase_promo_decide_' . $post_id );

		$back = admin_url( 'admin.php?page=kaamase-promotions' );

		if ( ! $post_id ) {
			wp_safe_redirect( $back );
			exit;
		}

		if ( 'start' === $decision ) {

			/*
			 * A day count of nothing would make a promotion that has
			 * already ended, which reads on the screen as one that never
			 * started and is the sort of thing somebody blames the site
			 * for. A week is what the form offers by default.
			 */
			kaamase_promo_start( $post_id, $days ? $days : 7, $paid, $ref );

			wp_safe_redirect( add_query_arg( 'done', 'started', $back ) );
			exit;
		}

		if ( 'stop' === $decision ) {

			kaamase_promo_finish( $post_id, 'stopped' );

			wp_safe_redirect( add_query_arg( 'done', 'stopped', $back ) );
			exit;
		}

		kaamase_promo_refuse( $post_id );

		wp_safe_redirect( add_query_arg( 'done', 'no', $back ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_promo_decide', 'kaamase_promo_decide' );

if ( ! function_exists( 'kaamase_promo_csv' ) ) {
	/**
	 * Hand the book over as a spreadsheet.
	 *
	 * For the accountant, and for the day somebody disputes a payment.
	 * Written from the ledger rather than from the listings, so a job
	 * deleted six months ago is still on the line where it was paid for.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_promo_csv() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_promo_csv' );

		$date = 'Y-m-d';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=kaamase-promotions-' . gmdate( $date ) . '.csv' );

		$out = fopen( 'php://output', 'w' );

		/*
		 * Separator, enclosure and escape all given.
		 *
		 * PHP 8.4 deprecates calling this without the last one, and a
		 * deprecation notice on a host with display_errors on is printed
		 * into the download -- so the spreadsheet arrives with a line of
		 * PHP at the top and will not open. An empty escape is also the
		 * value PHP is moving to, and the one the CSV standard describes.
		 */
		fputcsv(
			$out,
			array(
				__( 'Date', 'kaamase-core' ),
				__( 'What', 'kaamase-core' ),
				__( 'Kind', 'kaamase-core' ),
				__( 'Who', 'kaamase-core' ),
				__( 'Days', 'kaamase-core' ),
				__( 'Rupees', 'kaamase-core' ),
				__( 'Google Pay reference', 'kaamase-core' ),
				__( 'Ends', 'kaamase-core' ),
			),
			',',
			'"',
			''
		);

		foreach ( kaamase_promo_ledger() as $row ) {

			$user = isset( $row['user'] ) ? get_userdata( (int) $row['user'] ) : null;

			fputcsv(
				$out,
				array(
					isset( $row['at'] ) ? date_i18n( $date, (int) $row['at'] ) : '',
					isset( $row['title'] ) ? (string) $row['title'] : '',
					isset( $row['type'] ) && 'kaamase_job' === $row['type'] ? __( 'Job', 'kaamase-core' ) : __( 'Profile', 'kaamase-core' ),
					$user ? $user->display_name : '',
					isset( $row['days'] ) ? (int) $row['days'] : 0,
					isset( $row['paid'] ) ? (int) $row['paid'] : 0,
					isset( $row['ref'] ) ? (string) $row['ref'] : '',
					isset( $row['ends'] ) ? date_i18n( $date, (int) $row['ends'] ) : '',
				),
				',',
				'"',
				''
			);
		}

		fclose( $out );
		exit;
	}
}
add_action( 'admin_post_kaamase_promo_csv', 'kaamase_promo_csv' );


/* ==========================================================================
   9. THE MARK, AND THE SLOT

   Phase two. The word Ad on a promoted listing, and one promoted card at
   the top of a list that would not otherwise have shown it.

   Why the word rather than a colour
   ---------------------------------
   Because a disclosure has to be read, not decoded, and because India's
   advertising code names "Ad" as an acceptable label. It is also the
   shortest true word available, which matters on a card where a worker's
   name is already being cut off.

   Why it does not look like New or Vouched
   ----------------------------------------
   Those two are earned. This one is bought. A grey outline rather than a
   filled colour, deliberately quieter than the badges around it, because
   an advertisement dressed as an achievement is the exact thing an
   advertising standard exists to prevent -- and because an employer who
   works out that the shiny badge is for sale stops believing the other
   two as well.

   Why nothing is reordered
   -----------------------
   The list is not touched at all. No filter here sits on posts_orderby,
   nothing is removed from the results, and no page count changes. One
   card is drawn above the grid and the grid is exactly what
   exposure.php decided it should be. Everybody keeps their place.
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_badge' ) ) {
	/**
	 * The mark, for a listing that is being promoted.
	 *
	 * Lives here rather than in the theme so the website, the fallback
	 * cards in compat.php and anything added later all say the same word
	 * and can be changed in one place.
	 *
	 * Returns nothing at all for a listing that is not promoted, so a
	 * caller can print it unconditionally.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return string Markup, or an empty string.
	 */
	function kaamase_promo_badge( $post_id ) {

		if ( ! kaamase_promo_is_live( $post_id ) ) {
			return '';
		}

		return sprintf(
			'<span class="ka-badge ka-badge--ad" title="%1$s">%2$s</span>',
			/* translators: the tooltip on the Ad mark */
			esc_attr__( 'Paid for by the person who posted it. Nothing else on this list has moved for it.', 'kaamase-core' ),
			/* translators: the mark shown on a paid listing. Kept to two or three letters. */
			esc_html_x( 'Ad', 'mark on a paid listing', 'kaamase-core' )
		);
	}
}

if ( ! function_exists( 'kaamase_promo_district' ) ) {
	/**
	 * Which district a listing is in.
	 *
	 * @since 1.10.0
	 * @param int $post_id Listing.
	 * @return string Term slug, or an empty string.
	 */
	function kaamase_promo_district( $post_id ) {

		$terms = get_the_terms( absint( $post_id ), 'kaamase_district' );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$first = reset( $terms );

		return isset( $first->slug ) ? (string) $first->slug : '';
	}
}

if ( ! function_exists( 'kaamase_promo_query_district' ) ) {
	/**
	 * Which district a listing page is showing, if it is showing one.
	 *
	 * Somebody who bought a week in Dimapur bought a week in Dimapur. A
	 * promoted card must not appear on the Mon list because the Mon list
	 * happened to be the one being looked at.
	 *
	 * @since 1.10.0
	 * @param WP_Query $query The listing.
	 * @return string Term slug, or an empty string for an unfiltered list.
	 */
	function kaamase_promo_query_district( $query ) {

		$wanted = $query->get( 'kaamase_district' );

		if ( is_string( $wanted ) && '' !== $wanted ) {
			return sanitize_title( $wanted );
		}

		if ( $query->is_tax( 'kaamase_district' ) ) {

			$term = $query->get_queried_object();

			if ( $term && isset( $term->slug ) ) {
				return (string) $term->slug;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_promo_pick' ) ) {
	/**
	 * Choose one from however many bought the same slot.
	 *
	 * This is the fairness, and it is the reason the slot can be sold to
	 * more than one agency at a time. Five advertisers in one district
	 * get a fifth of it each rather than the first one getting all of it.
	 *
	 * Rotated by the hour rather than at random, for three reasons. It
	 * shares out evenly across a day instead of merely on average. Two
	 * people looking at the same page at the same time see the same
	 * thing, which is what anybody would expect. And it does not write
	 * anything, so a listing page stays a read.
	 *
	 * The website is behind a page cache, so a cached page holds whoever
	 * was chosen when it was built until it is rebuilt. Over a day that
	 * still comes out even, and the app -- which is most of the traffic
	 * and is not cached -- rotates exactly.
	 *
	 * Sorted first so the choice is the same on every server and every
	 * request within the hour.
	 *
	 * @since 1.10.0
	 * @param int[] $ids Listings that could take the slot.
	 * @return int One of them, or 0.
	 */
	function kaamase_promo_pick( $ids ) {

		$ids = array_values( array_unique( array_map( 'absint', (array) $ids ) ) );

		sort( $ids, SORT_NUMERIC );

		if ( empty( $ids ) ) {
			return 0;
		}

		$slice = (int) floor( time() / HOUR_IN_SECONDS );

		return (int) $ids[ $slice % count( $ids ) ];
	}
}

if ( ! function_exists( 'kaamase_promo_slot_for' ) ) {
	/**
	 * Which listing, if any, should take the slot above this page.
	 *
	 * @since 1.10.0
	 * @param WP_Query $query The listing being drawn.
	 * @return int Listing ID, or 0 for no advertisement.
	 */
	function kaamase_promo_slot_for( $query ) {

		if ( empty( $query->posts ) ) {
			return 0;
		}

		/*
		 * The kind of thing this page is showing, taken from what it is
		 * actually showing rather than from the query vars. A district
		 * archive, a trade archive and a search all arrive here with
		 * different vars set and the same answer in their results.
		 */
		$first = reset( $query->posts );
		$kind  = is_object( $first ) ? (string) $first->post_type : '';

		if ( ! in_array( $kind, kaamase_promo_types(), true ) ) {
			return 0;
		}

		$on_page = array();

		foreach ( $query->posts as $post ) {
			if ( is_object( $post ) ) {
				$on_page[] = (int) $post->ID;
			}
		}

		$district = kaamase_promo_query_district( $query );
		$wanted   = array();

		foreach ( kaamase_promo_find( 'live', 100 ) as $post_id ) {

			if ( ! kaamase_promo_is_live( $post_id ) ) {
				continue;
			}

			/*
			 * A worker list advertises workers. A team is a worker
			 * profile and belongs on the same list; a job does not.
			 */
			if ( 'kaamase_job' === $kind ) {
				if ( 'kaamase_job' !== get_post_type( $post_id ) ) {
					continue;
				}
			} elseif ( 'kaamase_job' === get_post_type( $post_id ) ) {
				continue;
			}

			/*
			 * Already on the page, so there is nothing to advertise. It
			 * carries its own mark where it stands, and a second copy of
			 * one card on one screen reads as a fault rather than as an
			 * advertisement.
			 */
			if ( in_array( (int) $post_id, $on_page, true ) ) {
				continue;
			}

			if ( '' !== $district && kaamase_promo_district( $post_id ) !== $district ) {
				continue;
			}

			$wanted[] = (int) $post_id;
		}

		return kaamase_promo_pick( $wanted );
	}
}

if ( ! function_exists( 'kaamase_promo_slot_drawn' ) ) {
	/**
	 * Whether this request has already drawn an advertisement.
	 *
	 * Held in a function rather than in a static inside the drawing one,
	 * the same way kaamase_views_primed() does it, so it can be read and
	 * set from outside. A static that nothing can reach is a static that
	 * nothing can test.
	 *
	 * @since 1.10.0
	 * @param bool|null $put true to mark it drawn, false to forget.
	 * @return bool
	 */
	function kaamase_promo_slot_drawn( $put = null ) {

		static $drawn = false;

		if ( is_bool( $put ) ) {
			$drawn = $put;
		}

		return $drawn;
	}
}

if ( ! function_exists( 'kaamase_promo_slot' ) ) {
	/**
	 * Draw the promoted card at the top of a listing.
	 *
	 * On loop_start, which fires once at the top of the loop the template
	 * is about to run. Nothing about the query is changed by being here:
	 * the results, their order, the number found and the paging are all
	 * exactly what they were.
	 *
	 * First page only. An advertisement on page four is an advertisement
	 * nobody sees, and a second copy of it on every page is the reason
	 * people mute a site.
	 *
	 * @since 1.10.0
	 * @param WP_Query $query The listing.
	 * @return void
	 */
	function kaamase_promo_slot( $query ) {

		if ( is_admin() || ! ( $query instanceof WP_Query ) ) {
			return;
		}

		/**
		 * Filter whether the promoted slot is drawn at all.
		 *
		 * The off switch, without editing a file. Everything else about a
		 * promotion carries on: the mark still shows where the listing
		 * stands, the run still ends on its day, and the takings are
		 * unaffected.
		 *
		 * @since 1.10.0
		 * @param bool     $on    Whether to draw it.
		 * @param WP_Query $query The listing.
		 */
		if ( ! apply_filters( 'kaamase_promo_slot_enabled', true, $query ) ) {
			return;
		}

		if ( ! $query->is_main_query() || $query->is_singular() ) {
			return;
		}

		// Listings only. Not the blog, not a feed, not the front page.
		if ( ! $query->is_post_type_archive() && ! $query->is_tax() && ! $query->is_search() ) {
			return;
		}

		if ( is_feed() || $query->is_paged() ) {
			return;
		}

		/*
		 * Once per request, whatever else runs a loop. A template that
		 * runs the main loop twice on one page -- a count in the header
		 * and then the grid, or a rewind_posts -- must not produce two
		 * advertisements.
		 */
		if ( kaamase_promo_slot_drawn() ) {
			return;
		}

		$post_id = kaamase_promo_slot_for( $query );

		if ( ! $post_id ) {
			return;
		}

		kaamase_promo_slot_drawn( true );

		/*
		 * Drawn with the ordinary card renderer, and with no wrapper
		 * around it. The grid on the listing page styles its own
		 * children, so an extra element between the two would be a
		 * layout change made for the sake of an advertisement, which is
		 * the wrong way round. The card carries the mark, which is the
		 * disclosure.
		 */
		switch ( get_post_type( $post_id ) ) {

			case 'kaamase_job':
				if ( function_exists( 'kaamase_job_card' ) ) {
					kaamase_job_card( $post_id );
				}
				break;

			case 'kaamase_gang':
				if ( function_exists( 'kaamase_gang_card' ) ) {
					kaamase_gang_card( $post_id );
				}
				break;

			default:
				if ( function_exists( 'kaamase_worker_card' ) ) {
					kaamase_worker_card( $post_id );
				}
				break;
		}
	}
}
add_action( 'loop_start', 'kaamase_promo_slot' );


/* ==========================================================================
   10. THE APP'S MARK, AND THE APP'S SLOT

   Phase three's half of the work that belongs on this server. The app
   release is separate; nothing here waits on it and nothing here breaks
   an app that has not had it.

   Both additions are additive. An older build reads a listing it does
   not recognise a key in and carries on, and it simply never calls the
   slot route, so a phone that has not updated sees the platform exactly
   as it saw it yesterday.
   ========================================================================== */

if ( ! function_exists( 'kaamase_promo_shape_worker' ) ) {
	/**
	 * Put the mark on a worker or team, for the app.
	 *
	 * Inside badges, beside vouched and verified, because that is where
	 * this shape already keeps its marks. It is a third key in an object
	 * the app is already reading rather than a new place to look.
	 *
	 * @since 1.10.0
	 * @param array   $out  The shaped listing.
	 * @param WP_Post $post The listing.
	 * @return array
	 */
	function kaamase_promo_shape_worker( $out, $post ) {

		if ( ! isset( $out['badges'] ) || ! is_array( $out['badges'] ) ) {
			$out['badges'] = array();
		}

		$out['badges']['promoted'] = kaamase_promo_is_live( is_object( $post ) ? $post->ID : $post );

		return $out;
	}
}
add_filter( 'kaamase_shape_worker', 'kaamase_promo_shape_worker', 20, 2 );

if ( ! function_exists( 'kaamase_promo_shape_job' ) ) {
	/**
	 * Put the mark on a job, for the app.
	 *
	 * At the top level next to urgent, because that is where this shape
	 * keeps its flags. The two shapes differ here and the difference is
	 * older than this feature; matching each one is less surprising than
	 * inventing a third arrangement for the sake of symmetry.
	 *
	 * @since 1.10.0
	 * @param array   $out  The shaped listing.
	 * @param WP_Post $post The listing.
	 * @return array
	 */
	function kaamase_promo_shape_job( $out, $post ) {

		$out['promoted'] = kaamase_promo_is_live( is_object( $post ) ? $post->ID : $post );

		return $out;
	}
}
add_filter( 'kaamase_shape_job', 'kaamase_promo_shape_job', 20, 2 );

if ( ! function_exists( 'kaamase_promo_slot_pick' ) ) {
	/**
	 * Which listing takes the slot on a list of a given kind.
	 *
	 * The same rules the website's slot follows, with the page's own
	 * results taken out of it: the app says what it is showing and where,
	 * and this says what to put above it.
	 *
	 * @since 1.10.0
	 * @param string $kind     worker or job.
	 * @param string $district District slug, or an empty string for everywhere.
	 * @return int Listing ID, or 0.
	 */
	function kaamase_promo_slot_pick( $kind, $district = '' ) {

		$kind     = 'job' === $kind ? 'job' : 'worker';
		$district = sanitize_title( (string) $district );
		$wanted   = array();

		foreach ( kaamase_promo_find( 'live', 100 ) as $post_id ) {

			if ( ! kaamase_promo_is_live( $post_id ) ) {
				continue;
			}

			$is_job = 'kaamase_job' === get_post_type( $post_id );

			if ( ( 'job' === $kind ) !== $is_job ) {
				continue;
			}

			if ( '' !== $district && kaamase_promo_district( $post_id ) !== $district ) {
				continue;
			}

			$wanted[] = (int) $post_id;
		}

		return kaamase_promo_pick( $wanted );
	}
}

if ( ! function_exists( 'kaamase_promo_slot_route' ) ) {
	/**
	 * A route of its own, rather than a key on the listing responses.
	 *
	 * Three reasons. The listing envelope in rest-api.php is shared by
	 * every list on the platform and adding to it would touch screens
	 * that have nothing to do with advertising. An app that has not been
	 * updated never calls this, so nothing changes for it. And a slot
	 * that fails or is slow must not be able to stop a worker seeing a
	 * list of jobs, which is what it would do if it travelled with them.
	 *
	 * Open to anybody. An advertisement shown only to signed-in people
	 * is an advertisement the buyer is not getting what they paid for,
	 * and there is nothing in the answer that a listing page does not
	 * already show.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_promo_slot_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/promoted',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_promo_slot_rest',
				'permission_callback' => '__return_true',
				'args'                => array(
					'kind'     => array(
						'type'    => 'string',
						'enum'    => array( 'worker', 'job' ),
						'default' => 'worker',
					),
					'district' => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_promo_slot_route' );

if ( ! function_exists( 'kaamase_promo_slot_rest' ) ) {
	/**
	 * Hand the app the card for the slot, or nothing.
	 *
	 * Shaped by the same function the lists use, so the app can draw it
	 * with the component it already has. item is null rather than absent
	 * when there is nothing to show, because a key that comes and goes is
	 * a key somebody forgets to check for.
	 *
	 * @since 1.10.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_promo_slot_rest( $request ) {

		$kind     = (string) $request->get_param( 'kind' );
		$district = (string) $request->get_param( 'district' );

		$post_id = kaamase_promo_slot_pick( $kind, $district );

		if ( ! $post_id ) {
			return rest_ensure_response( array( 'item' => null ) );
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return rest_ensure_response( array( 'item' => null ) );
		}

		$item = 'kaamase_job' === $post->post_type
			? kaamase_shape_job( $post, false )
			: kaamase_shape_worker( $post, false );

		return rest_ensure_response( array( 'item' => $item ) );
	}
}
