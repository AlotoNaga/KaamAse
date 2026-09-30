<?php
/**
 * One free verification a year.
 *
 * What this is for
 * ----------------
 * The owner gives verification away for free: to people who ask for it
 * on the Asked to be verified screen, and to people they know, on the Give
 * the plan screen. Both are gifts, and a gift runs out on its own.
 *
 * Nothing stopped the same person asking again the day it ran out, and
 * again a month after that. The owner cannot remember who had one last
 * month, so the only fair rule is one the platform keeps for them: once a
 * free one has ended, that person cannot ask for another for a year.
 *
 * Ended means either way it ends. It ran out on its date, or the owner
 * took it back early. Taking back a year's gift after a week does not
 * reset anything: the year starts from the day it was taken back.
 *
 * What this does not touch
 * ------------------------
 * The tick coming off when somebody changes their name, number or photo
 * (mark-changes.php). That is a different thing: the gift is still
 * running, the person goes back on the Calls to make list, and nothing
 * here is involved. This file listens for exactly two events, a gift
 * given and a gift taken back, and nothing else.
 *
 * It never stops the owner either. The lock is on the person asking.
 * The owner can still give anybody anything from Give the plan at any
 * time, and that is the way to make an exception.
 *
 * A slip is not a gift
 * --------------------
 * A gift taken back within the hour it was given was given to the wrong
 * person or with the wrong length. That is undone as if it never
 * happened: no lock, no message.
 *
 * Telling them
 * ------------
 * One email and one app notification when a free one ends, in the
 * person's own language, saying when they can ask again and that Rich
 * Manu is there if they would like the tick back sooner. Said once per
 * ending. Somebody who is paying by then is not told anything: nothing
 * has changed for them.
 *
 * Gifts given before this file existed
 * ------------------------------------
 * A gift that is still on the account, or that ran out on its own, left
 * its end date behind, so those are counted from that date. A gift that
 * was taken back before this file existed left nothing behind at all, so
 * those cannot be counted.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/** When the latest free one ends, or ended. Set on every gift. */
define( 'KAAMASE_FREE_UNTIL_KEY', '_kaamase_free_until' );

/** Set once they have been told that it ended. Cleared by the next gift. */
define( 'KAAMASE_FREE_TOLD_KEY', '_kaamase_free_told' );

/** When the latest gift was given, to tell a slip from a decision. */
define( 'KAAMASE_FREE_GIVEN_KEY', '_kaamase_free_given' );

/** What was there before the latest gift, so a slip can be put back. */
define( 'KAAMASE_FREE_PREV_KEY', '_kaamase_free_prev' );

/** How long after a free one ends before they may ask for another. */
define( 'KAAMASE_FREE_LOCK_DAYS', 365 );

/** How recently it must have ended to be worth an email about it. */
define( 'KAAMASE_FREE_TELL_DAYS', 7 );

/** A gift taken back this quickly was a slip, not a decision. */
define( 'KAAMASE_FREE_SLIP_SECONDS', HOUR_IN_SECONDS );

/** Set once the gifts from before this file have been dated. */
define( 'KAAMASE_FREE_DATED_OPTION', 'kaamase_free_lock_dated' );


/* ==========================================================================
   1. WHERE A PERSON STANDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_free_until' ) ) {
	/**
	 * When this person's latest free one ends, or ended.
	 *
	 * Read from what a gift writes. For a gift from before this file,
	 * read from the gift it left behind, until the dating below has
	 * written it down properly.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return int Timestamp, or 0 when they have never had one.
	 */
	function kaamase_free_until( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return 0;
		}

		$stored = get_user_meta( $user_id, KAAMASE_FREE_UNTIL_KEY, true );

		if ( '' !== $stored ) {
			return (int) $stored;
		}

		return kaamase_free_until_from_old_gift( $user_id );
	}
}

if ( ! function_exists( 'kaamase_free_until_from_old_gift' ) ) {
	/**
	 * The end date of a gift given before this file existed.
	 *
	 * The gift leaves its date on the account, and the plan it gave has
	 * an end date. Somebody paying on a subscription is left out: their
	 * end date is the one they pay for, not the gift's.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return int Timestamp, or 0 when there is nothing to go on.
	 */
	function kaamase_free_until_from_old_gift( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! defined( 'KAAMASE_GIFT_AT_KEY' ) || ! defined( 'KAAMASE_PAY_EXPIRES_KEY' ) ) {
			return 0;
		}

		if ( ! (int) get_user_meta( $user_id, KAAMASE_GIFT_AT_KEY, true ) ) {
			return 0;
		}

		if ( function_exists( 'kaamase_pay_renews' ) && kaamase_pay_renews( $user_id ) ) {
			return 0;
		}

		return max( 0, (int) get_user_meta( $user_id, KAAMASE_PAY_EXPIRES_KEY, true ) );
	}
}

if ( ! function_exists( 'kaamase_free_locked_until' ) ) {
	/**
	 * Until when this person cannot ask for another free one.
	 *
	 * Nothing while a free one is still running: they have it.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return int Timestamp, or 0 when they are free to ask.
	 */
	function kaamase_free_locked_until( $user_id ) {

		$user_id = (int) $user_id;
		$until   = kaamase_free_until( $user_id );
		$now     = time();

		$locked = ( $until > 0 && $until <= $now )
			? $until + ( KAAMASE_FREE_LOCK_DAYS * DAY_IN_SECONDS )
			: 0;

		/**
		 * Filter until when somebody cannot ask for a free one.
		 *
		 * Return 0 to let them ask now.
		 *
		 * @since 1.0.0
		 * @param int $locked  Timestamp, or 0.
		 * @param int $user_id User ID.
		 * @param int $until   When their latest free one ended.
		 */
		$locked = (int) apply_filters( 'kaamase_free_locked_until', $locked, $user_id, $until );

		return $locked > $now ? $locked : 0;
	}
}

if ( ! function_exists( 'kaamase_free_is_locked' ) ) {
	/**
	 * Whether this person cannot ask for a free one right now.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_free_is_locked( $user_id ) {
		return kaamase_free_locked_until( $user_id ) > 0;
	}
}

if ( ! function_exists( 'kaamase_free_date' ) ) {
	/**
	 * A date, written the way this site writes dates.
	 *
	 * wp_date rather than date_i18n: these are real timestamps, and
	 * date_i18n reads them as already shifted to local time, which moves
	 * a date written late in the evening onto the next day.
	 *
	 * @since 1.0.0
	 * @param int $timestamp Timestamp.
	 * @return string
	 */
	function kaamase_free_date( $timestamp ) {
		return (string) wp_date( (string) get_option( 'date_format' ), (int) $timestamp );
	}
}

if ( ! function_exists( 'kaamase_free_plans_url' ) ) {
	/**
	 * Where to get the paid plan.
	 *
	 * @since 1.0.0
	 * @return string URL.
	 */
	function kaamase_free_plans_url() {

		$url = function_exists( 'kaamase_pay_plans_url' ) ? (string) kaamase_pay_plans_url() : '';

		if ( '' === $url && function_exists( 'kaamase_page_url' ) ) {
			$url = (string) kaamase_page_url( 'dashboard' );
		}

		return '' !== $url ? $url : home_url( '/' );
	}
}

if ( ! function_exists( 'kaamase_free_lock_note' ) ) {
	/**
	 * What to say to somebody who cannot ask yet.
	 *
	 * Said politely and with a date, because "not now" with no date
	 * reads as "never", and with the paid plan named, because some of
	 * them will want the tick badly enough not to wait.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string Empty when they are free to ask.
	 */
	function kaamase_free_lock_note( $user_id ) {

		$locked = kaamase_free_locked_until( $user_id );

		if ( ! $locked ) {
			return '';
		}

		return sprintf(
			/* translators: 1: date, 2: paid plan name */
			__( 'You have had free verification in the last year. You can ask for it again from %1$s. If you would like the tick sooner, you can get %2$s.', 'kaamase-core' ),
			kaamase_free_date( $locked ),
			function_exists( 'kaamase_plan_name' ) ? kaamase_plan_name() : 'Rich Manu'
		);
	}
}


/* ==========================================================================
   2. WRITING IT DOWN

   Two events and nothing else: a gift given, a gift taken back. Both
   fire from gift.php, and the Asked to be verified screen gives through
   the same gift, so every free one passes through here.
   ========================================================================== */

if ( ! function_exists( 'kaamase_free_on_given' ) ) {
	/**
	 * A gift was given: remember when it ends.
	 *
	 * The end date is read back from the account rather than worked out
	 * again here, because a gift is added on top of anything already
	 * running, and the account is what knows what was running.
	 *
	 * @since 1.0.0
	 * @param int $user_id Who got it.
	 * @param int $days    How long it runs.
	 * @return void
	 */
	function kaamase_free_on_given( $user_id, $days ) {

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return;
		}

		$ready = function_exists( 'kaamase_gift_pay_ready' ) && kaamase_gift_pay_ready();

		$until = $ready ? (int) get_user_meta( $user_id, KAAMASE_PAY_EXPIRES_KEY, true ) : 0;

		if ( $until <= time() ) {
			$until = time() + ( max( 1, (int) $days ) * DAY_IN_SECONDS );
		}

		// Kept so a slip can be undone without losing an earlier year.
		update_user_meta(
			$user_id,
			KAAMASE_FREE_PREV_KEY,
			array(
				'until' => get_user_meta( $user_id, KAAMASE_FREE_UNTIL_KEY, true ),
				'told'  => get_user_meta( $user_id, KAAMASE_FREE_TOLD_KEY, true ),
			)
		);

		update_user_meta( $user_id, KAAMASE_FREE_GIVEN_KEY, time() );
		update_user_meta( $user_id, KAAMASE_FREE_UNTIL_KEY, $until );
		delete_user_meta( $user_id, KAAMASE_FREE_TOLD_KEY );
	}
}
add_action( 'kaamase_gift_given', 'kaamase_free_on_given', 10, 2 );

if ( ! function_exists( 'kaamase_free_on_taken_back' ) ) {
	/**
	 * A gift was taken back: it ended today.
	 *
	 * Unless it was only just given, in which case it was a slip and
	 * everything goes back to how it was before it.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_free_on_taken_back( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return;
		}

		$given = (int) get_user_meta( $user_id, KAAMASE_FREE_GIVEN_KEY, true );

		if ( $given && ( time() - $given ) < KAAMASE_FREE_SLIP_SECONDS ) {

			$prev = get_user_meta( $user_id, KAAMASE_FREE_PREV_KEY, true );
			$prev = is_array( $prev ) ? $prev : array();

			foreach ( array( 'until' => KAAMASE_FREE_UNTIL_KEY, 'told' => KAAMASE_FREE_TOLD_KEY ) as $part => $key ) {

				if ( isset( $prev[ $part ] ) && '' !== (string) $prev[ $part ] ) {
					update_user_meta( $user_id, $key, $prev[ $part ] );
				} else {
					delete_user_meta( $user_id, $key );
				}
			}

			delete_user_meta( $user_id, KAAMASE_FREE_GIVEN_KEY );
			delete_user_meta( $user_id, KAAMASE_FREE_PREV_KEY );

			return;
		}

		$until = (int) get_user_meta( $user_id, KAAMASE_FREE_UNTIL_KEY, true );

		/*
		 * Still running, or never dated: it ends now. One that had
		 * already run out and is only being tidied off the list keeps
		 * the day it really ended.
		 */
		if ( $until <= 0 || $until > time() ) {
			update_user_meta( $user_id, KAAMASE_FREE_UNTIL_KEY, time() );
		}

		delete_user_meta( $user_id, KAAMASE_FREE_GIVEN_KEY );
		delete_user_meta( $user_id, KAAMASE_FREE_PREV_KEY );

		kaamase_free_tell( $user_id );
	}
}
add_action( 'kaamase_gift_taken_back', 'kaamase_free_on_taken_back' );

if ( ! function_exists( 'kaamase_free_date_old_gifts' ) ) {
	/**
	 * Write down the end date of gifts given before this file existed.
	 *
	 * Done in one go on the first request after this file arrives, so
	 * those people are counted from the start rather than from the next
	 * morning, and again each morning in case anything was missed.
	 * Somebody with nothing to go on gets a 0, which means no lock, and
	 * which stops them being looked at again.
	 *
	 * @since 1.0.0
	 * @param int $limit How many at once.
	 * @return int How many were dated.
	 */
	function kaamase_free_date_old_gifts( $limit = 500 ) {

		if ( ! defined( 'KAAMASE_GIFT_AT_KEY' ) ) {
			return 0;
		}

		$found = get_users(
			array(
				'number'     => (int) $limit,
				'fields'     => 'ID',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => KAAMASE_GIFT_AT_KEY,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => KAAMASE_FREE_UNTIL_KEY,
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$dated = 0;

		foreach ( (array) $found as $user_id ) {

			$user_id = (int) $user_id;

			update_user_meta( $user_id, KAAMASE_FREE_UNTIL_KEY, kaamase_free_until_from_old_gift( $user_id ) );

			$dated++;
		}

		return $dated;
	}
}

if ( ! function_exists( 'kaamase_free_date_old_gifts_once' ) ) {
	/**
	 * The first run of the above, as soon as this file is live.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_free_date_old_gifts_once() {

		if ( get_option( KAAMASE_FREE_DATED_OPTION ) ) {
			return;
		}

		if ( ! defined( 'KAAMASE_GIFT_AT_KEY' ) ) {
			return;
		}

		// Marked first, so a slow run is not started again by every request behind it.
		update_option( KAAMASE_FREE_DATED_OPTION, time(), true );

		kaamase_free_date_old_gifts( 2000 );
	}
}
add_action( 'init', 'kaamase_free_date_old_gifts_once', 40 );


/* ==========================================================================
   3. TELLING THEM
   ========================================================================== */

if ( ! function_exists( 'kaamase_free_tell' ) ) {
	/**
	 * Tell somebody their free one has ended, once.
	 *
	 * Somebody paying by the time it ends is not told: they still have
	 * the plan, and a message saying it ended would be wrong. Nor is
	 * anybody told about one that ended more than a week ago, which is
	 * what keeps the old gifts dated above from all being emailed at
	 * once on the day this goes live.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string sent, skipped, or failed (try again tomorrow).
	 */
	function kaamase_free_tell( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || get_user_meta( $user_id, KAAMASE_FREE_TOLD_KEY, true ) ) {
			return 'skipped';
		}

		$until = (int) get_user_meta( $user_id, KAAMASE_FREE_UNTIL_KEY, true );
		$now   = time();

		// Not ended yet, or never had one: nothing to say, and nothing marked.
		if ( $until <= 0 || $until > $now ) {
			return 'skipped';
		}

		$locked = kaamase_free_locked_until( $user_id );

		$nothing_to_say = ( $now - $until ) > ( KAAMASE_FREE_TELL_DAYS * DAY_IN_SECONDS )
			|| ! $locked
			|| ! function_exists( 'kaamase_pay_is_active' )
			|| kaamase_pay_is_active( $user_id )
			|| ! get_userdata( $user_id );

		if ( $nothing_to_say ) {
			update_user_meta( $user_id, KAAMASE_FREE_TOLD_KEY, $now );
			return 'skipped';
		}

		$reached = kaamase_free_send_notice( $user_id, $until, $locked );

		if ( ! $reached ) {
			return 'failed';
		}

		update_user_meta( $user_id, KAAMASE_FREE_TOLD_KEY, $now );

		return 'sent';
	}
}

if ( ! function_exists( 'kaamase_free_send_notice' ) ) {
	/**
	 * The email and the app notification.
	 *
	 * Both written in the language the person reads, not the language of
	 * whoever happened to trigger it: the owner in the admin, or nobody
	 * at all on the morning run.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @param int $until   When it ended.
	 * @param int $locked  When they can ask again.
	 * @return bool Whether it reached them one way or the other.
	 */
	function kaamase_free_send_notice( $user_id, $until, $locked ) {

		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$has_app = function_exists( 'kaamase_push_to_user' )
			&& function_exists( 'kaamase_push_enabled' )
			&& function_exists( 'kaamase_push_user_tokens' )
			&& kaamase_push_enabled()
			&& kaamase_push_user_tokens( $user_id );

		$write = function () use ( $user, $user_id, $until, $locked, $has_app ) {

			$plan  = function_exists( 'kaamase_plan_name' ) ? kaamase_plan_name() : 'Rich Manu';
			$again = kaamase_free_date( $locked );

			if ( $has_app ) {
				kaamase_push_to_user(
					$user_id,
					__( 'Your free verification has ended', 'kaamase-core' ),
					sprintf(
						/* translators: 1: date, 2: paid plan name */
						__( 'You can ask for it again from %1$s. Would you like the tick back sooner? Get %2$s.', 'kaamase-core' ),
						$again,
						$plan
					),
					array(
						'type'         => 'free_verify_ended',
						'locked_until' => (int) $locked,
					)
				);
			}

			if ( '' === (string) $user->user_email ) {
				return false;
			}

			$site = get_bloginfo( 'name' );

			$lines = array(
				sprintf(
					/* translators: %s: person's name */
					__( 'Hello %s,', 'kaamase-core' ),
					$user->display_name
				),
				'',
				sprintf(
					/* translators: 1: site name, 2: date */
					__( 'The free verification we gave you on %1$s ended on %2$s, so the tick is no longer on your profile. Your profile itself stays exactly as it is.', 'kaamase-core' ),
					$site,
					kaamase_free_date( $until )
				),
				'',
				sprintf(
					/* translators: %s: date */
					__( 'Thank you for being with us. Free verification is given to each person once a year, so that everybody who asks gets a fair turn. You can ask for it again from %s.', 'kaamase-core' ),
					$again
				),
				'',
				sprintf(
					/* translators: %s: paid plan name */
					__( 'If you would like the tick back sooner, you can get %s here:', 'kaamase-core' ),
					$plan
				),
				kaamase_free_plans_url(),
			);

			return (bool) wp_mail(
				$user->user_email,
				sprintf(
					/* translators: %s: site name */
					__( 'Your free verification on %s has ended', 'kaamase-core' ),
					$site
				),
				implode( "\n", $lines )
			);
		};

		$mailed = function_exists( 'kaamase_locale_write_to' )
			? kaamase_locale_write_to( $user_id, $write )
			: $write();

		return (bool) $mailed || (bool) $has_app;
	}
}

if ( ! function_exists( 'kaamase_free_sweep' ) ) {
	/**
	 * The morning run: tell everybody whose free one ran out.
	 *
	 * Stops at the first email the mail server refuses. Everybody after
	 * it stays untold and is tried again tomorrow, and for a week after
	 * it ended.
	 *
	 * @since 1.0.0
	 * @return int How many were told.
	 */
	function kaamase_free_sweep() {

		kaamase_free_date_old_gifts( 500 );

		$now = time();

		$due = get_users(
			array(
				'number'     => 100,
				'fields'     => 'ID',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => KAAMASE_FREE_UNTIL_KEY,
						'value'   => array( $now - ( KAAMASE_FREE_TELL_DAYS * DAY_IN_SECONDS ), $now ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => KAAMASE_FREE_TOLD_KEY,
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$told = 0;

		foreach ( (array) $due as $user_id ) {

			$result = kaamase_free_tell( (int) $user_id );

			if ( 'failed' === $result ) {
				break;
			}

			if ( 'sent' === $result ) {
				$told++;
			}
		}

		return $told;
	}
}
add_action( 'kaamase_daily', 'kaamase_free_sweep' );
