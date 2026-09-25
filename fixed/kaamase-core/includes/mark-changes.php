<?php
/**
 * Keeping the mark true.
 *
 * The tick on a profile means one narrow thing, and verified-mark.php is
 * careful to say exactly what: somebody at Kaam Ase telephoned this
 * person. Not that their work is good. Not that they are recommended.
 *
 * What that call actually established was three facts, and only three:
 * this NAME, this NUMBER, this FACE. Everything else on the profile --
 * the day rate, the trades, the years of experience, the description --
 * was never checked by anybody and the tick has never claimed otherwise.
 *
 * The hole that left
 * ------------------
 * Nothing stopped somebody getting the tick and then changing those
 * three things afterwards. A profile could carry a mark saying "we have
 * spoken to them" above a name nobody had ever spoken to, beside a
 * photograph taken off the internet. Reported from the live site: paid
 * accounts changing to a false name and a stranger's picture while
 * keeping the tick.
 *
 * That is worse than having no mark at all. An unmarked platform asks
 * people to use their own judgement. A platform with a mark that can be
 * quietly falsified spends its own credibility vouching for whoever is
 * willing to abuse it, and the person who finds out is a worker who
 * travelled to a job that was not real.
 *
 * What this does
 * --------------
 * When somebody changes one of those three things on their own profile,
 * the tick comes off and they go back in the queue to be rung.
 *
 * It is not a punishment and should not be built or worded as one. The
 * mark stated a fact that has stopped being true, so it stops being
 * shown. Somebody who genuinely changed their telephone number has done
 * nothing wrong at all -- and their profile still must not tell people
 * we rang a number we have never rung.
 *
 * Why not simply lock the fields
 * ------------------------------
 * It was the first idea and it is the wrong one.
 *
 * Locking makes the owner of the platform the bottleneck for every
 * honest correction: a misspelled name, a marriage, a better photograph.
 * Worse, somebody whose number really has changed could not fix it, so
 * employers would ring a dead number and conclude the platform is full
 * of ghosts. That is a bigger reputation problem than the one being
 * solved, and it grows with every person who gets the tick.
 *
 * Letting them edit and taking the mark off puts the cost exactly where
 * it belongs. Nobody who paid for a tick swaps to a false name on a whim
 * if it costs them the tick, and nobody honest is stopped from fixing
 * their own details.
 *
 * What it does NOT touch
 * ----------------------
 * The subscription. Not once, not in any branch. rich-manu.php keeps the
 * paid plan and the tick as two separate things on purpose -- paying
 * buys a place in the queue and never the mark -- and that separation is
 * what makes this safe to do automatically. Somebody who changes their
 * name loses the tick and keeps every day of the plan they paid for.
 *
 * @package KaamaseCore
 * @version 1.2.0
 * @since   1.12.0
 */

defined( 'ABSPATH' ) || exit;

/** The last few identity changes on an account, newest first. */
define( 'KAAMASE_MARK_LOG_KEY', '_kaamase_mark_log' );

/** When the mark was last taken off by a change. */
define( 'KAAMASE_MARK_DROPPED_KEY', '_kaamase_mark_dropped' );

/** How many changes to keep. Enough to see a pattern, not a history. */
if ( ! defined( 'KAAMASE_MARK_LOG_KEEP' ) ) {
	define( 'KAAMASE_MARK_LOG_KEEP', 10 );
}


/* ==========================================================================
   1. WHAT THE CALL VOUCHED FOR
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_profile_types' ) ) {
	/**
	 * The profiles a tick can appear on.
	 *
	 * All three, and the list is taken from where the mark is actually
	 * drawn rather than from what seems obvious.
	 *
	 * Employers for the same reason as workers, and more urgently: a
	 * worker reads an employer's mark before deciding whether to travel
	 * to a job.
	 *
	 * Teams because they carry it too, which is easy to miss.
	 * kaamase_mark_in_title() puts the tick beside a kaamase_gang title
	 * on the website, and the app shapes teams through
	 * kaamase_shape_worker(), so the mark reaches a team profile by both
	 * routes. A team has its own name, its own number and its own
	 * photograph, so leaving it out would have left the whole hole open
	 * one post type along: rename the team rather than yourself and the
	 * tick stays.
	 *
	 * @since 1.12.0
	 * @return string[]
	 */
	function kaamase_mark_profile_types() {
		return (array) apply_filters(
			'kaamase_mark_profile_types',
			array( 'kaamase_worker', 'kaamase_employer', 'kaamase_gang' )
		);
	}
}

if ( ! function_exists( 'kaamase_mark_watches' ) ) {
	/**
	 * The three things, and nothing else.
	 *
	 * Keeping this list short is the most important decision in the
	 * file. Taking somebody's tick away because they corrected their day
	 * rate would be wrong -- the call never vouched for their day rate --
	 * and it would teach everybody that the mark is arbitrary, which
	 * costs more trust than the abuse it was meant to stop.
	 *
	 * @since 1.12.0
	 * @return array Key => the word shown to a person.
	 */
	function kaamase_mark_watches() {

		$watched = array(
			'name'  => __( 'name', 'kaamase-core' ),
			'phone' => __( 'phone number', 'kaamase-core' ),
			'photo' => __( 'photograph', 'kaamase-core' ),
		);

		/**
		 * Filter what taking the mark off depends on.
		 *
		 * Add to this only for something a telephone call actually
		 * established. Everything else belongs outside it.
		 *
		 * @since 1.12.0
		 * @param array $watched Key => label.
		 */
		return (array) apply_filters( 'kaamase_mark_watches', $watched );
	}
}

if ( ! function_exists( 'kaamase_mark_phone_key' ) ) {
	/**
	 * The meta key the telephone number is kept under.
	 *
	 * @since 1.12.0
	 * @return string
	 */
	function kaamase_mark_phone_key() {
		return KAAMASE_META_PREFIX . 'phone';
	}
}


/* ==========================================================================
   2. WHOSE CHANGE IT IS
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_has_record' ) ) {
	/**
	 * Whether a call is on record for this account.
	 *
	 * Deliberately NOT kaamase_has_been_called(), and the difference is
	 * a hole rather than a detail.
	 *
	 * That function answers "is the tick showing", which also requires
	 * the plan to be running. rich-manu.php says why, and says plainly
	 * what happens when it is not: "Nothing is deleted when a plan
	 * lapses. The record of the call stays, so renewing brings the mark
	 * straight back with no second phone call."
	 *
	 * Which means asking whether the tick is showing would leave the
	 * whole rule avoidable, by the easiest route there is:
	 *
	 *   1. let the plan lapse, and the tick goes quiet on its own
	 *   2. change to a false name while nothing is watching, because
	 *      there is no tick to lose
	 *   3. renew -- and the mark comes straight back, onto the new name,
	 *      with nobody ever having rung it
	 *
	 * So this asks the question that actually matters: is there a call
	 * on record that a change would make untrue. A lapsed plan does not
	 * make the call less true, and it must not make the change less
	 * consequential.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account.
	 * @return bool
	 */
	function kaamase_mark_has_record( $user_id ) {

		if ( ! defined( 'KAAMASE_CALLED_AT_KEY' ) ) {
			return false;
		}

		return (int) get_user_meta( (int) $user_id, KAAMASE_CALLED_AT_KEY, true ) > 0;
	}
}

if ( ! function_exists( 'kaamase_mark_dropped_here' ) ) {
	/**
	 * Whose mark this request has already taken off.
	 *
	 * Read by two places that would otherwise disagree with each other.
	 * kaamase_mark_drop() uses it so one save takes the mark off once,
	 * and kaamase_mark_change_counts() uses it so the REST of that same
	 * save is still recorded after the mark has gone.
	 *
	 * Without the second use, somebody who changed their name, their
	 * number and their photograph in one go would have the name recorded
	 * and the other two silently dropped, because by the time the number
	 * was written there was no longer a mark to lose. The call list
	 * would then say one thing changed when three did, which is the
	 * opposite of what it is for.
	 *
	 * @since 1.12.0
	 * @param int  $user_id Account.
	 * @param bool $set     Mark it as dropped.
	 * @return bool
	 */
	function kaamase_mark_dropped_here( $user_id, $set = false ) {

		static $done = array();

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return false;
		}

		if ( $set ) {
			$done[ $user_id ] = true;
		}

		return isset( $done[ $user_id ] );
	}
}

if ( ! function_exists( 'kaamase_mark_change_counts' ) ) {
	/**
	 * Whether this particular change should cost the mark.
	 *
	 * Only a change the account holder makes to their own profile.
	 *
	 * The two exceptions matter as much as the rule. The owner of the
	 * platform fixing a misspelling from the admin has not falsified
	 * anything, and taking the tick off would mean tidying somebody's
	 * record punished them. And a change made with nobody signed in --
	 * an import, a migration, a scheduled task -- has no author to hold
	 * responsible and must not be treated as one.
	 *
	 * @since 1.12.0
	 * @param WP_Post|null $post The profile being changed.
	 * @return bool
	 */
	function kaamase_mark_change_counts( $post ) {

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( ! in_array( $post->post_type, kaamase_mark_profile_types(), true ) ) {
			return false;
		}

		$actor = get_current_user_id();

		// Nobody signed in. Not somebody's doing.
		if ( ! $actor ) {
			return false;
		}

		// Staff tidying a record, rather than the person rewriting it.
		if ( user_can( $actor, 'manage_options' ) ) {
			return false;
		}

		if ( $actor !== (int) $post->post_author ) {
			return false;
		}

		/*
		 * Nothing to lose -- unless this request has already taken it
		 * off them, in which case the rest of the same save still has to
		 * be written down. See kaamase_mark_dropped_here().
		 */
		if ( ! kaamase_mark_dropped_here( $actor ) ) {

			if ( ! kaamase_mark_has_record( $actor ) ) {
				return false;
			}
		}

		return true;
	}
}


/* ==========================================================================
   3. NOTICING

   Core hooks rather than the platform's own save function, deliberately.

   kaamase_save_profile() is the front door and covers the website and
   the app, but it is not the only door: the photograph is written by
   set_post_thumbnail() from two different places, and anything added
   later would have to remember to report itself. Watching the post and
   its meta catches every route into those three fields by construction,
   including routes that do not exist yet.
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_watch_title' ) ) {
	/**
	 * The name.
	 *
	 * post_updated hands over the record as it was and as it now is, so
	 * the old name is read without having to stash anything earlier in
	 * the request.
	 *
	 * @since 1.12.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $after   The post now.
	 * @param WP_Post $before  The post before.
	 * @return void
	 */
	function kaamase_mark_watch_title( $post_id, $after, $before ) {

		if ( ! $after instanceof WP_Post || ! $before instanceof WP_Post ) {
			return;
		}

		if ( $after->post_title === $before->post_title ) {
			return;
		}

		if ( ! kaamase_mark_change_counts( $after ) ) {
			return;
		}

		kaamase_mark_noticed( $after, 'name', $before->post_title, $after->post_title );
	}
}
add_action( 'post_updated', 'kaamase_mark_watch_title', 10, 3 );

if ( ! function_exists( 'kaamase_mark_watch_meta' ) ) {
	/**
	 * The telephone number, and the photograph.
	 *
	 * Hooked to the action that fires BEFORE the write, which is what
	 * makes the old value readable. WordPress only fires it when the
	 * value is genuinely different, so re-saving a form without touching
	 * anything costs nobody their mark.
	 *
	 * This runs on every post meta write on the site, so the key is
	 * checked first and costs one string comparison for everything it
	 * does not care about.
	 *
	 * @since 1.12.0
	 * @param int    $meta_id  Meta row. Unused.
	 * @param int    $post_id  Post the meta belongs to.
	 * @param string $key      Meta key.
	 * @param mixed  $value    The value about to be written.
	 * @return void
	 */
	function kaamase_mark_watch_meta( $meta_id, $post_id, $key, $value ) {

		unset( $meta_id );

		$what = kaamase_mark_meta_is( $key );

		if ( '' === $what ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! kaamase_mark_change_counts( $post ) ) {
			return;
		}

		$old = get_post_meta( $post_id, $key, true );

		if ( (string) $old === (string) $value ) {
			return;
		}

		kaamase_mark_noticed( $post, $what, $old, $value );
	}
}
add_action( 'update_post_meta', 'kaamase_mark_watch_meta', 10, 4 );

if ( ! function_exists( 'kaamase_mark_watch_meta_added' ) ) {
	/**
	 * The same, for the first time a value is set.
	 *
	 * update_post_meta() hands off to add_metadata() when there is no
	 * row yet, and that fires a different action. A profile that had no
	 * photograph when it was rung and has one now is a changed face, so
	 * it belongs here too.
	 *
	 * @since 1.12.0
	 * @param int    $meta_id Meta row. Unused.
	 * @param int    $post_id Post the meta belongs to.
	 * @param string $key     Meta key.
	 * @param mixed  $value   The value written.
	 * @return void
	 */
	function kaamase_mark_watch_meta_added( $meta_id, $post_id, $key, $value ) {

		unset( $meta_id );

		$what = kaamase_mark_meta_is( $key );

		if ( '' === $what ) {
			return;
		}

		if ( '' === (string) $value ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! kaamase_mark_change_counts( $post ) ) {
			return;
		}

		kaamase_mark_noticed( $post, $what, '', $value );
	}
}
add_action( 'added_post_meta', 'kaamase_mark_watch_meta_added', 10, 4 );

if ( ! function_exists( 'kaamase_mark_meta_is' ) ) {
	/**
	 * Which watched thing a meta key is, if any.
	 *
	 * @since 1.12.0
	 * @param string $key Meta key.
	 * @return string name, phone, photo, or empty.
	 */
	function kaamase_mark_meta_is( $key ) {

		if ( '_thumbnail_id' === $key ) {
			return 'photo';
		}

		if ( kaamase_mark_phone_key() === $key ) {
			return 'phone';
		}

		return '';
	}
}


/* ==========================================================================
   4. TAKING IT OFF
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_pending' ) ) {
	/**
	 * The changes seen so far in this request.
	 *
	 * One save can change all three at once. The mark has to come off
	 * straight away so the answer that request returns is already
	 * truthful, but the message afterwards should name everything that
	 * changed rather than whichever happened to be written first, so the
	 * list is gathered here and read once at the end.
	 *
	 * @since 1.12.0
	 * @param array|null $add Change to add, or null to read.
	 * @return array User ID => list of changes.
	 */
	function kaamase_mark_pending( $add = null ) {

		static $pending = array();

		if ( is_array( $add ) ) {
			$pending[ (int) $add['user'] ][] = $add;
		}

		return $pending;
	}
}

if ( ! function_exists( 'kaamase_mark_noticed' ) ) {
	/**
	 * A watched thing changed. Record it, and take the mark off.
	 *
	 * @since 1.12.0
	 * @param WP_Post $post The profile.
	 * @param string  $what name, phone or photo.
	 * @param mixed   $from What it was.
	 * @param mixed   $to   What it is now.
	 * @return void
	 */
	function kaamase_mark_noticed( $post, $what, $from, $to ) {

		$user_id = (int) $post->post_author;

		if ( ! $user_id ) {
			return;
		}

		/*
		 * A photograph is recorded as having changed, without the file
		 * it used to be. Both upload paths delete the old attachment as
		 * soon as the new one is set, so a stored id would point at
		 * nothing within the same request. What is worth knowing is that
		 * the face changed and when; the face itself is on the profile
		 * to look at.
		 */
		$readable = 'photo' !== $what;

		kaamase_mark_log_add(
			$user_id,
			array(
				'at'   => time(),
				'what' => (string) $what,
				'from' => $readable ? (string) $from : '',
				'to'   => $readable ? (string) $to : '',
			)
		);

		kaamase_mark_pending(
			array(
				'user' => $user_id,
				'what' => (string) $what,
			)
		);

		kaamase_mark_drop( $user_id, (int) $post->ID );
	}
}

if ( ! function_exists( 'kaamase_mark_drop' ) ) {
	/**
	 * Take the tick off and put them back in the queue.
	 *
	 * Once per account per request, however many of the three changed.
	 *
	 * The subscription is not touched here and must never be. The queue
	 * is joined without charging anybody again: they have already paid
	 * for a call, and this is the same call being made a second time
	 * because the facts moved.
	 *
	 * @since 1.12.0
	 * @param int $user_id Whose mark.
	 * @param int $post_id Which profile.
	 * @return void
	 */
	function kaamase_mark_drop( $user_id, $post_id ) {

		if ( kaamase_mark_dropped_here( $user_id ) ) {
			return;
		}

		kaamase_mark_dropped_here( $user_id, true );

		if ( function_exists( 'kaamase_undo_call' ) ) {
			kaamase_undo_call( $user_id );
		}

		update_user_meta( $user_id, KAAMASE_MARK_DROPPED_KEY, time() );

		/*
		 * Straight back on the call list, but NOT through
		 * kaamase_join_call_queue(). That function sends the message
		 * somebody gets when they have just paid -- "Thank you, the plan
		 * is on your account" -- which would be a strange thing to read
		 * a second later for having corrected your own telephone number.
		 * The queue is joined here and the right words are sent below.
		 */
		if ( function_exists( 'kaamase_waiting_for_call' ) && ! kaamase_waiting_for_call( $user_id ) ) {

			update_user_meta( $user_id, KAAMASE_CALL_WAITING_KEY, time() );

			/** This hook is documented in includes/rich-manu.php */
			do_action( 'kaamase_call_queued', $user_id );
		}

		/**
		 * Fires when a tick comes off because the profile changed.
		 *
		 * @since 1.12.0
		 * @param int $user_id Whose mark.
		 * @param int $post_id The profile that changed.
		 */
		do_action( 'kaamase_mark_dropped', $user_id, $post_id );
	}
}


/* ==========================================================================
   5. TELLING BOTH SIDES
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_send_word' ) ) {
	/**
	 * Say what happened, once, at the end of the request.
	 *
	 * On shutdown so that a save which changed a name and a number and a
	 * photograph produces one message naming all three, rather than
	 * three messages racing each other.
	 *
	 * @since 1.12.0
	 * @return void
	 */
	function kaamase_mark_send_word() {

		$pending = kaamase_mark_pending();

		if ( empty( $pending ) ) {
			return;
		}

		/*
		 * Anything printed from here is caught and thrown away.
		 *
		 * shutdown runs after the answer has gone out. On the app that
		 * answer is JSON, and a single PHP warning from the mail layer
		 * appended to it is not a warning any more, it is a response the
		 * phone cannot parse -- a screen that fails for a reason nowhere
		 * near the thing that actually broke. Real failures still reach
		 * the error log, which is where they belong.
		 */
		ob_start();

		try {

			foreach ( $pending as $user_id => $changes ) {

				$what = array_values( array_unique( wp_list_pluck( $changes, 'what' ) ) );

				kaamase_mark_tell_person( (int) $user_id, $what );
				kaamase_mark_tell_owner( (int) $user_id, $what );
			}
		} catch ( Throwable $e ) {

			/*
			 * Caught rather than allowed to rise, and only here.
			 *
			 * The buffer above stops anything PRINTED from landing on
			 * the end of an answer that has already gone out. A thrown
			 * error would get past it: the buffer is discarded on the
			 * way out and the fatal then prints unbuffered, straight
			 * into the JSON this was protecting.
			 *
			 * Nothing is hidden by doing so. The response is sent, the
			 * mark is already off, and the only thing left to lose is
			 * the message -- so it goes to the error log, where a
			 * failure at this point could be read anyway.
			 */
			error_log( 'kaamase: could not send word about a dropped mark: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		} finally {
			ob_end_clean();
		}
	}
}
add_action( 'shutdown', 'kaamase_mark_send_word' );

if ( ! function_exists( 'kaamase_mark_words_for' ) ) {
	/**
	 * The changed things as a readable list.
	 *
	 * @since 1.12.0
	 * @param string[] $what Keys.
	 * @return string
	 */
	function kaamase_mark_words_for( $what ) {

		$labels  = kaamase_mark_watches();
		$written = array();

		foreach ( (array) $what as $key ) {
			if ( isset( $labels[ $key ] ) ) {
				$written[] = $labels[ $key ];
			}
		}

		if ( empty( $written ) ) {
			return '';
		}

		if ( 1 === count( $written ) ) {
			return $written[0];
		}

		/*
		 * Joined with a word rather than commas all the way.
		 *
		 * "your name, phone number, photograph" is a list a machine
		 * wrote. This sentence is being read by somebody who has just
		 * lost their tick and is working out why, and it should sound
		 * like a person telling them.
		 */
		$last = array_pop( $written );

		return sprintf(
			/* translators: 1: a comma separated list, 2: the last item. Example: "name, phone number and photograph" */
			__( '%1$s and %2$s', 'kaamase-core' ),
			implode( __( ', ', 'kaamase-core' ), $written ),
			$last
		);
	}
}

if ( ! function_exists( 'kaamase_mark_tell_person' ) ) {
	/**
	 * Tell them, in their language, and without scolding.
	 *
	 * They may well have done nothing wrong. The message says what
	 * happened, says the plan is safe because that is the first thing
	 * anybody who paid will worry about, and says somebody will ring.
	 *
	 * @since 1.12.0
	 * @param int      $user_id Whose mark.
	 * @param string[] $what    What changed.
	 * @return void
	 */
	function kaamase_mark_tell_person( $user_id, $what ) {

		if ( ! function_exists( 'kaamase_notify_user' ) ) {
			return;
		}

		$send = function () use ( $user_id, $what ) {

			$changed = kaamase_mark_words_for( $what );

			kaamase_notify_user(
				$user_id,
				__( 'Your tick has come off for now', 'kaamase-core' ),
				sprintf(
					/* translators: %s: the details that changed, for example "name, phone number" */
					__( 'You changed your %s, so the tick has come off until somebody from Kaam Ase can ring you again. Your plan is not affected and nothing has been charged. We will call you.', 'kaamase-core' ),
					$changed
				),
				array(
					'type' => 'mark_dropped',
					'id'   => (int) $user_id,
				),
				function_exists( 'kaamase_page_url' ) ? (string) kaamase_page_url( 'dashboard' ) : ''
			);
		};

		if ( function_exists( 'kaamase_locale_write_to' ) ) {
			kaamase_locale_write_to( $user_id, $send );
			return;
		}

		$send();
	}
}

if ( ! function_exists( 'kaamase_mark_tell_owner' ) ) {
	/**
	 * And tell whoever runs the platform, the same day.
	 *
	 * The queue would show it eventually. Eventually is not the same as
	 * today: the profile is live with a changed name the whole time, and
	 * the person who can ring them is the one who should decide how
	 * quickly that matters.
	 *
	 * In English, because it is going to the owner rather than to a user.
	 *
	 * @since 1.12.0
	 * @param int      $user_id Whose mark.
	 * @param string[] $what    What changed.
	 * @return void
	 */
	function kaamase_mark_tell_owner( $user_id, $what ) {

		/**
		 * Filter whether the owner is emailed when a tick comes off.
		 *
		 * @since 1.12.0
		 * @param bool $tell    Whether to send it. Default true.
		 * @param int  $user_id Whose mark came off.
		 */
		if ( ! apply_filters( 'kaamase_mark_tell_owner', true, $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		/** This filter is documented in includes/reports.php */
		$to = apply_filters( 'kaamase_report_email', get_option( 'kaamase_report_email', get_option( 'admin_email' ) ) );

		if ( ! is_email( $to ) ) {
			return;
		}

		$lines = array(
			sprintf(
				/* translators: 1: person's name, 2: what they changed */
				__( '%1$s changed their %2$s, so their tick has come off and they are back on the call list.', 'kaamase-core' ),
				$user->display_name,
				kaamase_mark_words_for( $what )
			),
			'',
			sprintf(
				/* translators: %s: email address */
				__( 'Account: %s', 'kaamase-core' ),
				$user->user_email
			),
		);

		$log = kaamase_mark_log( $user_id );

		foreach ( array_slice( $log, 0, count( (array) $what ) ) as $entry ) {

			if ( '' === (string) $entry['from'] && '' === (string) $entry['to'] ) {
				continue;
			}

			$lines[] = sprintf(
				/* translators: 1: what changed, 2: the old value, 3: the new value */
				__( '%1$s: was "%2$s", now "%3$s"', 'kaamase-core' ),
				(string) $entry['what'],
				(string) $entry['from'],
				(string) $entry['to']
			);
		}

		$lines[] = '';
		$lines[] = __( 'Their plan has not been touched.', 'kaamase-core' );

		wp_mail(
			$to,
			sprintf(
				/* translators: %s: person's name */
				__( 'Tick came off: %s', 'kaamase-core' ),
				$user->display_name
			),
			implode( "\n", $lines )
		);
	}
}


/* ==========================================================================
   6. THE RECORD
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_log' ) ) {
	/**
	 * What has changed on this account, newest first.
	 *
	 * Kept so that a call back starts informed. Ringing somebody to ask
	 * why their name changed is a different conversation depending on
	 * whether it went from Imliakum to Imliakum Jamir or from Imliakum
	 * to the name of a company that exists.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account.
	 * @return array[]
	 */
	function kaamase_mark_log( $user_id ) {

		$log = get_user_meta( (int) $user_id, KAAMASE_MARK_LOG_KEY, true );

		if ( ! is_array( $log ) ) {
			return array();
		}

		$clean = array();

		foreach ( $log as $entry ) {

			if ( ! is_array( $entry ) || ! isset( $entry['what'] ) ) {
				continue;
			}

			$clean[] = array(
				'at'   => isset( $entry['at'] ) ? (int) $entry['at'] : 0,
				'what' => (string) $entry['what'],
				'from' => isset( $entry['from'] ) ? (string) $entry['from'] : '',
				'to'   => isset( $entry['to'] ) ? (string) $entry['to'] : '',
			);
		}

		return $clean;
	}
}

if ( ! function_exists( 'kaamase_mark_log_add' ) ) {
	/**
	 * Add one change to the front of the record.
	 *
	 * @since 1.12.0
	 * @param int   $user_id Account.
	 * @param array $entry   at, what, from, to.
	 * @return void
	 */
	function kaamase_mark_log_add( $user_id, $entry ) {

		$log = kaamase_mark_log( $user_id );

		array_unshift( $log, $entry );

		update_user_meta(
			(int) $user_id,
			KAAMASE_MARK_LOG_KEY,
			array_slice( $log, 0, KAAMASE_MARK_LOG_KEEP )
		);
	}
}

if ( ! function_exists( 'kaamase_mark_log_summary' ) ) {
	/**
	 * The record as lines of plain text, for the call list.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account.
	 * @param int $limit   Most lines to return.
	 * @return string[]
	 */
	function kaamase_mark_log_summary( $user_id, $limit = 4 ) {

		$labels = kaamase_mark_watches();
		$out    = array();

		foreach ( array_slice( kaamase_mark_log( $user_id ), 0, absint( $limit ) ) as $entry ) {

			$label = isset( $labels[ $entry['what'] ] ) ? $labels[ $entry['what'] ] : $entry['what'];

			$when = $entry['at']
				? sprintf(
					/* translators: %s: human readable time difference, for example "2 days" */
					__( '%s ago', 'kaamase-core' ),
					human_time_diff( $entry['at'], time() )
				)
				: '';

			if ( '' === $entry['from'] && '' === $entry['to'] ) {

				$out[] = trim(
					sprintf(
						/* translators: 1: what changed, 2: how long ago */
						__( '%1$s changed, %2$s', 'kaamase-core' ),
						$label,
						$when
					),
					' ,'
				);

				continue;
			}

			$out[] = trim(
				sprintf(
					/* translators: 1: what changed, 2: old value, 3: new value, 4: how long ago */
					__( '%1$s: "%2$s" to "%3$s", %4$s', 'kaamase-core' ),
					$label,
					$entry['from'],
					$entry['to'],
					$when
				),
				' ,'
			);
		}

		return $out;
	}
}


/* ==========================================================================
   7. SAYING SO BEFOREHAND

   Somebody should never lose the mark by surprise. Told in advance it is
   a choice they made; found out afterwards it is a platform that took
   something off them without warning, and that is how a fair rule
   becomes a grievance.
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_warning' ) ) {
	/**
	 * The sentence shown above an edit form, or nothing.
	 *
	 * Empty for everybody without a tick, which is nearly everybody, so
	 * both the website and the app can print it without asking any
	 * further questions.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account.
	 * @return string
	 */
	function kaamase_mark_warning( $user_id = 0 ) {

		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		/*
		 * Warned on the record, not on the tick being visible, so that
		 * somebody whose plan has lapsed is told before they change
		 * something. They have the most to lose: their mark is coming
		 * back on renewal and this is what stops it.
		 */
		if ( ! $user_id || ! kaamase_mark_has_record( $user_id ) ) {
			return '';
		}

		return sprintf(
			/* translators: %s: the details that are checked, for example "name, phone number, photograph" */
			__( 'Changing your %s will take your tick off until somebody can ring you again. Your plan is not affected.', 'kaamase-core' ),
			kaamase_mark_words_for( array_keys( kaamase_mark_watches() ) )
		);
	}
}

if ( ! function_exists( 'kaamase_mark_warning_notice' ) ) {
	/**
	 * The same, as the website draws notices.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account.
	 * @return string
	 */
	function kaamase_mark_warning_notice( $user_id = 0 ) {

		$warning = kaamase_mark_warning( $user_id );

		if ( '' === $warning ) {
			return '';
		}

		return '<div class="ka-notice ka-notice--info"><div><span class="ka-notice__title">'
			. esc_html__( 'You have a tick', 'kaamase-core' )
			. '</span><p>' . esc_html( $warning ) . '</p></div></div>';
	}
}

if ( ! function_exists( 'kaamase_mark_field_hint' ) ) {
	/**
	 * The short version, to sit under the field itself.
	 *
	 * One notice at the top of the form is not enough on its own, and
	 * the reason is the shape of the page rather than the wording. The
	 * edit form is long: photograph, name, trades, rates, experience,
	 * district, town, telephone. Somebody who opens it to change their
	 * photograph scrolls straight past the top, and somebody changing
	 * their number is a long way below anything they read on the way in.
	 *
	 * So the notice at the top explains the rule once, and this repeats
	 * it at the three places where it is about to apply -- which is the
	 * moment somebody is actually deciding.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account. Defaults to whoever is signed in.
	 * @return string Markup, or nothing.
	 */
	function kaamase_mark_field_hint( $user_id = 0 ) {

		$note = kaamase_mark_field_words( $user_id );

		if ( empty( $note ) ) {
			return '';
		}

		return '<p class="ka-hint ka-mark-hint"><strong>'
			. esc_html( $note['lead'] )
			. '</strong> '
			. esc_html( $note['rest'] )
			. '</p>';
	}
}

if ( ! function_exists( 'kaamase_mark_field_words' ) ) {
	/**
	 * The two halves of that line, or nothing.
	 *
	 * Split because the website bolds the first half and the app cannot
	 * be handed markup. Kept in one place so neither surface can end up
	 * saying something the other does not.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account. Defaults to whoever is signed in.
	 * @return array lead and rest, or empty.
	 */
	function kaamase_mark_field_words( $user_id = 0 ) {

		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $user_id || ! kaamase_mark_has_record( $user_id ) ) {
			return array();
		}

		return array(
			'lead' => __( 'Changing this takes your tick off', 'kaamase-core' ),
			'rest' => __( 'until somebody can ring you again. Your plan is not affected.', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_mark_field_note' ) ) {
	/**
	 * The same line as one plain sentence, for the app.
	 *
	 * @since 1.12.0
	 * @param int $user_id Account.
	 * @return string
	 */
	function kaamase_mark_field_note( $user_id = 0 ) {

		$note = kaamase_mark_field_words( $user_id );

		return empty( $note ) ? '' : $note['lead'] . ' ' . $note['rest'];
	}
}

if ( ! function_exists( 'kaamase_mark_shape_me' ) ) {
	/**
	 * Hand the same sentence to the app.
	 *
	 * Sent as the finished sentence rather than as a flag, so the app
	 * shows exactly what the website shows, already in the language the
	 * person reads, and neither side can drift from the other when the
	 * rule changes.
	 *
	 * @since 1.12.0
	 * @param array $me      The account.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_mark_shape_me( $me, $user_id ) {

		/*
		 * Two sentences, for the two places the website says it.
		 *
		 * mark_warning is the one that explains the rule, and belongs at
		 * the top of an edit screen. mark_field_note is the short one
		 * that belongs under each of the three fields it applies to,
		 * which matters more on a phone than it does on a desktop: the
		 * screen is smaller and the form is just as long, so the notice
		 * at the top is gone before somebody reaches their number.
		 *
		 * Both are empty for anybody without a call on record, so the
		 * app can print them without asking any further questions.
		 */
		$me['mark_warning']    = kaamase_mark_warning( $user_id );
		$me['mark_field_note'] = kaamase_mark_field_note( $user_id );

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_mark_shape_me', 10, 2 );
