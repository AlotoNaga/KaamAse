<?php
/**
 * Push notifications.
 *
 * Sent through Expo's push service, which is what the app registers
 * with. No Firebase key lives on this server; Expo holds the
 * credentials and this only ever posts a token and a message to them.
 *
 * What gets sent, and what does not
 * ---------------------------------
 * A short list, and the restraint is the point. A hiring app that
 * pushes every new listing gets muted inside a week, and a muted app
 * cannot tell somebody the one thing that mattered.
 *
 * Sent from this file:
 *
 *   1. Somebody looked up your number. This is the person's own
 *      information and they are entitled to it. It is also the single
 *      most convincing evidence a worker gets that the platform is
 *      doing something for them.
 *
 *   2. Your profile is live. Sent once, on verification, because the
 *      gap between registering and being findable is where people give
 *      up.
 *
 *   3. Did you hire them. The question the whole rating system hangs
 *      on, delivered where it will actually be seen.
 *
 *   4. Your profile, team or job passed a round number of opens. The
 *      only cheerful item on this list, and the only time anybody hears
 *      from the platform when nothing has gone wrong. At most one per
 *      figure per profile, ever, and never at night.
 *
 * Sent from elsewhere, through this layer:
 *
 *   5. Somebody has asked for your number, and the answer to a request
 *      you made. Both live in number-requests.php, because the rules
 *      about who may ask belong with the feature rather than here.
 *      They go out through kaamase_notify_user() below.
 *
 * Anything added later belongs on this list. A notification nobody
 * wrote down is a notification nobody can weigh against the others,
 * and the whole argument above is about the total.
 *
 * Deliberately not sent: new jobs in your trade. That one is genuinely
 * useful and it is also the one that becomes noise fastest, so it needs
 * a frequency cap and a quiet hours rule before it ships. Better absent
 * than resented.
 *
 * Never in a notification
 * -----------------------
 * A phone number. A push payload passes through Expo, sits in the
 * Android notification shade and is readable on a locked screen. The
 * whole platform is built on numbers not travelling casually, and a
 * notification is the most casual channel there is.
 *
 * @package KaamaseCore
 * @version 1.6.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. SENDING
   ========================================================================== */

if ( ! function_exists( 'kaamase_push_endpoint' ) ) {
	/**
	 * Where push messages go.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	function kaamase_push_endpoint() {
		return (string) apply_filters( 'kaamase_push_endpoint', 'https://exp.host/--/api/v2/push/send' );
	}
}

if ( ! function_exists( 'kaamase_push_enabled' ) ) {
	/**
	 * Whether push is switched on.
	 *
	 * Off by default. A site that has not registered a single device
	 * should not be making an outbound HTTP request on every reveal.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	function kaamase_push_enabled() {
		return (bool) apply_filters( 'kaamase_push_enabled', (bool) get_option( 'kaamase_push_enabled', false ) );
	}
}

if ( ! function_exists( 'kaamase_push_user_tokens' ) ) {
	/**
	 * The devices an account has registered, junk removed.
	 *
	 * Checked against the shape /push/register accepts. A malformed
	 * value stored by an older build costs a slot in every batch
	 * afterwards and can never be delivered to.
	 *
	 * @since 1.4.1
	 * @param int $user_id User ID.
	 * @return string[] Tokens, empty when this person has no app.
	 */
	function kaamase_push_user_tokens( $user_id ) {

		$tokens = kaamase_meta_array( get_user_meta( absint( $user_id ), 'kaamase_push_tokens', true ) );

		$tokens = array_filter(
			array_map( 'strval', $tokens ),
			static function ( $token ) {
				return (bool) preg_match( '/^Expo(nent)?PushToken\[[^\]]+\]$/', $token );
			}
		);

		return array_values( array_unique( $tokens ) );
	}
}

if ( ! function_exists( 'kaamase_push_reaches' ) ) {
	/**
	 * Whether a notification would actually arrive.
	 *
	 * Asked before sending rather than judged afterwards, because the
	 * send below does not block and returns nothing: there is no
	 * success to inspect. Anything that needs to fall back to email has
	 * to ask first.
	 *
	 * @since 1.4.1
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_push_reaches( $user_id ) {
		return kaamase_push_enabled() && ! empty( kaamase_push_user_tokens( $user_id ) );
	}
}

if ( ! function_exists( 'kaamase_push_to_user' ) ) {
	/**
	 * Send a notification to every device an account has registered.
	 *
	 * Non blocking. A slow push service must never hold up the request
	 * that triggered it: an employer tapping to reveal a number should
	 * not wait on somebody else's notification being delivered.
	 *
	 * @since 1.3.0
	 * @param int    $user_id User to notify.
	 * @param string $title   Notification title.
	 * @param string $body    Notification body.
	 * @param array  $data    Payload the app reads to decide where to open.
	 * @return void
	 */
	function kaamase_push_to_user( $user_id, $title, $body, $data = array() ) {

		if ( ! kaamase_push_enabled() ) {
			return;
		}

		$tokens = kaamase_push_user_tokens( $user_id );

		if ( empty( $tokens ) ) {
			return;
		}

		/**
		 * Filter a notification before it is sent.
		 *
		 * The hook a per person setting would use. Return an empty
		 * title to stop the send. Here rather than as a stored option
		 * because muting is not one decision: somebody may well want to
		 * be told that a stranger has their number and not want to be
		 * asked about hires, and data.type says which is which.
		 *
		 * @since 1.4.1
		 * @param array $message Title, body and data.
		 * @param int   $user_id Who is being notified.
		 */
		$message = (array) apply_filters(
			'kaamase_push_message',
			array(
				'title' => (string) $title,
				'body'  => (string) $body,
				'data'  => (array) $data,
			),
			absint( $user_id )
		);

		if ( '' === trim( (string) $message['title'] ) ) {
			return;
		}

		$messages = array();

		foreach ( $tokens as $token ) {

			$messages[] = array(
				'to'       => (string) $token,

				/*
				 * Cut to what a phone will actually show. Android gives
				 * a notification title roughly one line and the body
				 * two before it collapses, and a sentence chopped by
				 * the shade mid word reads like a broken app rather
				 * than a long message.
				 */
				'title'    => mb_substr( wp_strip_all_tags( (string) $message['title'] ), 0, 60 ),
				'body'     => mb_substr( wp_strip_all_tags( (string) $message['body'] ), 0, 180 ),
				'data'     => (array) $message['data'],
				'sound'    => 'default',
				'priority' => 'high',
				'channelId' => 'default',
			);
		}

		wp_remote_post(
			kaamase_push_endpoint(),
			array(
				'headers'  => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'     => wp_json_encode( $messages ),
				'timeout'  => 5,
				'blocking' => false,
			)
		);
	}
}


if ( ! function_exists( 'kaamase_notify_user' ) ) {
	/**
	 * Tell somebody something, by whatever reaches them.
	 *
	 * The phone when there is one, email when there is not. Half the
	 * people on this platform registered on the website and never
	 * installed anything, and a notification layer that only speaks to
	 * app users quietly decides those people do not need to be told.
	 *
	 * Never both. Two copies of one sentence teaches people this
	 * platform is noisy, and the ones who learn that read neither.
	 *
	 * Push and email are not interchangeable, so the caller says what
	 * the email should link to. A notification can carry a screen to
	 * open; an email has to carry an address.
	 *
	 * The same rule as everywhere else applies to both: no phone
	 * number, ever. An email is forwarded, printed, and left open on
	 * shared machines.
	 *
	 * @since 1.4.1
	 * @param int    $user_id  Who to tell.
	 * @param string $title    Short line, and the email subject.
	 * @param string $body     The sentence.
	 * @param array  $data     Where the app should open.
	 * @param string $mail_url A link for the end of the email.
	 * @return void
	 */
	function kaamase_notify_user( $user_id, $title, $body, $data = array(), $mail_url = '' ) {

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return;
		}

		if ( kaamase_push_reaches( $user_id ) ) {
			kaamase_push_to_user( $user_id, $title, $body, $data );
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			return;
		}

		$lines = array( $body );

		if ( '' !== $mail_url ) {
			$lines[] = '';
			$lines[] = $mail_url;
		}

		$lines[] = '';
		$lines[] = sprintf(
			/* translators: %s: site name */
			__( '%s never asks a worker for money.', 'kaamase-core' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		wp_mail(
			$user->user_email,
			wp_specialchars_decode( (string) $title, ENT_QUOTES ),
			implode( "\n", $lines )
		);
	}
}


/* ==========================================================================
   2. WHAT TRIGGERS ONE
   ========================================================================== */

/**
 * Tell somebody their number was looked up.
 *
 * The website already promises this in writing on the contact screen:
 * "We have told them that you looked up their number." Before this, that
 * sentence was not true. Now it is.
 *
 * The employer is named. Anonymously telling somebody that a stranger
 * has their number is alarming and useless.
 *
 * @since 1.3.0
 * @param int $post_id Profile revealed.
 * @param int $user_id User who revealed it.
 * @return void
 */
function kaamase_push_contact_revealed( $post_id, $user_id ) {

	$post = get_post( $post_id );

	if ( ! $post ) {
		return;
	}

	$owner = (int) $post->post_author;

	if ( ! $owner || $owner === (int) $user_id ) {
		return;
	}

	$who = get_userdata( $user_id );

	/*
	 * Written in the owner's language, not the looker's.
	 *
	 * The request running here belongs to whoever looked the number up,
	 * so without this the sentence is built in that person's language
	 * and then sent to somebody else. The one person it is for is the
	 * only one whose language was not consulted.
	 */
	$send = function () use ( $owner, $who, $post_id ) {

		kaamase_push_to_user(
				$owner,
				__( 'Somebody has your number', 'kaamase-core' ),
				sprintf(
					/* translators: %s: name of the person who looked them up */
					__( '%s looked you up on Kaam Ase. They may call you.', 'kaamase-core' ),
					$who ? $who->display_name : __( 'An employer', 'kaamase-core' )
				),
			array(
				'type' => 'contact_revealed',
				'id'   => (int) $post_id,
			)
		);
	};

	/*
	 * Guarded, because a notification going out in the wrong language
	 * is a disappointment and a fatal on a live page is not. Every
	 * cross file call on this platform is written this way.
	 */
	if ( function_exists( 'kaamase_locale_write_to' ) ) {
		kaamase_locale_write_to( $owner, $send );
	} else {
		$send();
	}
}
add_action( 'kaamase_contact_revealed', 'kaamase_push_contact_revealed', 10, 2 );

/**
 * Tell somebody their profile went live.
 *
 * @since 1.3.0
 * @param int $user_id User ID.
 * @return void
 */
function kaamase_push_verified( $user_id ) {

	/*
	 * Somebody on the staff approved this, from the admin, in English.
	 * The person being congratulated is not in that request at all.
	 */
	$send = function () use ( $user_id ) {

		kaamase_push_to_user(
			$user_id,
			__( 'Your profile is live', 'kaamase-core' ),
			__( 'Employers across Nagaland can find you now. Set yourself available when you are free for work.', 'kaamase-core' ),
			array( 'type' => 'verified' )
		);
	};

	/*
	 * Guarded, because a notification going out in the wrong language
	 * is a disappointment and a fatal on a live page is not. Every
	 * cross file call on this platform is written this way.
	 */
	if ( function_exists( 'kaamase_locale_write_to' ) ) {
		kaamase_locale_write_to( $user_id, $send );
	} else {
		$send();
	}
}
add_action( 'kaamase_user_verified', 'kaamase_push_verified' );

/**
 * Ask about a hire once the question comes due.
 *
 * Runs on the daily task rather than on a timer, and only asks once per
 * person, because the point is to get an answer rather than to nag.
 *
 * @since 1.3.0
 * @return void
 */
function kaamase_push_hire_questions() {

	if ( ! kaamase_push_enabled() ) {
		return;
	}

	/*
	 * A hundred a day, but a DIFFERENT hundred each day.
	 *
	 * This asked for the first hundred accounts holding the meta and
	 * nothing else. get_users orders by login when nothing says
	 * otherwise, so it was the same hundred logins every single night.
	 * Anybody sorting below them was never asked, not late but never:
	 * their hire question sat on the dashboard waiting to be stumbled
	 * on, and the ratings that depend on the answer never came.
	 *
	 * Nobody would have noticed until the platform passed a hundred
	 * accounts with questions outstanding, and then only as ratings
	 * quietly drying up for everybody with a late alphabet.
	 *
	 * A cursor walks the list instead, a batch a night, back to the
	 * start when it runs off the end. Ordered by ID because a login can
	 * be changed and an ID cannot, so the walk cannot double back.
	 */
	$batch  = 100;
	$offset = max( 0, (int) get_option( 'kaamase_hire_question_cursor', 0 ) );

	$users = get_users(
		array(
			'meta_key' => 'kaamase_hire_questions', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'number'   => $batch,
			'offset'   => $offset,
			'orderby'  => 'ID',
			'order'    => 'ASC',
			'fields'   => array( 'ID' ),
		)
	);

	/*
	 * Off the end, so back to the top. Tomorrow starts again rather
	 * than this run trying to catch the tail as well: a night that does
	 * nothing costs one day, and a run that wraps mid batch would ask
	 * the earliest accounts twice as often as the rest.
	 */
	if ( empty( $users ) ) {

		if ( $offset > 0 ) {
			update_option( 'kaamase_hire_question_cursor', 0, false );
		}

		return;
	}

	update_option( 'kaamase_hire_question_cursor', $offset + $batch, false );

	foreach ( $users as $row ) {

		$due = kaamase_due_hire_questions( $row->ID );

		if ( empty( $due ) ) {
			continue;
		}

		// One ask per person per person, ever.
		$asked = kaamase_meta_array( get_user_meta( $row->ID, 'kaamase_hire_asked', true ) );
		$fresh = array();

		foreach ( $due as $entry ) {
			if ( ! in_array( (int) $entry['post_id'], array_map( 'absint', $asked ), true ) ) {
				$fresh[] = $entry;
			}
		}

		if ( empty( $fresh ) ) {
			continue;
		}

		$first = $fresh[0];
		$name  = get_the_title( $first['post_id'] );

		/*
		 * The profile travels with the notification.
		 *
		 * Without it the app knows only that somebody tapped a hire
		 * question, not which one, so the best it can do is open the
		 * account tab and hope the right card is near the top. The
		 * contact notification above has carried its id since the
		 * beginning and this one should have too. A name is sent
		 * alongside it so the app can say who before it has fetched
		 * anything, which matters on a slow connection.
		 *
		 * Still no phone number, here or in any other notification.
		 */
		/*
		 * This one runs on the nightly task, where there is no person
		 * and therefore no language anywhere in the request. Everybody
		 * asked would get English unless their account is read.
		 */
		$send = function () use ( $row, $name, $first ) {

			kaamase_push_to_user(
				$row->ID,
				__( 'Did you hire them?', 'kaamase-core' ),
				sprintf(
					/* translators: %s: worker name */
					__( 'You looked up %s recently. Telling us what happened lets you both rate each other.', 'kaamase-core' ),
					$name
				),
				array(
					'type' => 'hire_question',
					'id'   => (int) $first['post_id'],
					'name' => (string) $name,
				)
			);
		};

		if ( function_exists( 'kaamase_locale_write_to' ) ) {
			kaamase_locale_write_to( $row->ID, $send );
		} else {
			$send();
		}

		/*
		 * Only the one that was asked is marked as asked.
		 *
		 * Every fresh question used to be marked here while a single
		 * one was sent, so a person owed three answers was pushed once
		 * and the other two were recorded as having been put to them.
		 * They were not. Because the mark is permanent, those two never
		 * got a notification at all: they sat on the dashboard waiting
		 * for somebody to go looking.
		 *
		 * Marking one a day is also the right pace on its own. Three
		 * questions in one notification is a chore, and a chore gets
		 * dismissed.
		 */
		$asked[] = (int) $first['post_id'];

		update_user_meta( $row->ID, 'kaamase_hire_asked', array_slice( array_values( array_unique( $asked ) ), -100 ) );
	}
}
add_action( 'kaamase_daily', 'kaamase_push_hire_questions' );

/* ==========================================================================
   3. MILESTONES

   Number four on the list at the top of this file, and the only one on
   it that nobody is waiting for. It is here anyway, because a worker who
   never hears anything assumes nothing is happening, and on this
   platform something usually is: views are counted quietly in views.php
   and almost nobody goes looking at the number.

   So the number comes to them, once, when it passes a round figure. It
   is the same thing a photo app does at a hundred likes and it works for
   the same reason -- it is news about them rather than about the
   platform. It is also the only notification here that is purely good
   news, which is worth something on its own.

   Read from the table, never hooked onto the counter
   --------------------------------------------------
   Counting a view sits on the critical path of somebody loading a page.
   Turning that into a SUM over a year of rows, on shared hosting, to
   find out whether this particular view was the hundredth, would make
   every profile page slower for a notification that fires once in a
   thousand views.

   A schedule reads the table instead. It asks which profiles and jobs
   were opened in the last couple of days, totals only those, and
   compares each against what its owner was last told. views.php is not
   touched at all, which is the point: the counting works, and nothing
   here can break it.

   Once per figure, ever
   ---------------------
   The figure somebody was last told is kept on the profile itself. That
   is what stops a second notification for the same hundred, and it is
   also what survives the yearly prune in views.php taking old rows away
   and the total dipping back under a figure it has already passed.

   The highest figure crossed, not every one of them. A job that goes
   from nothing to two hundred overnight gets one notification saying
   two hundred, not four saying ten, fifty, a hundred and a hundred and
   sixty.

   No phone number here either, and nothing about who looked. A count is
   not a list, and this notification is deliberately not one.
   ========================================================================== */

/** How far back a run looks for profiles and jobs that were opened. */
if ( ! defined( 'KAAMASE_MILESTONE_DAYS' ) ) {
	define( 'KAAMASE_MILESTONE_DAYS', 2 );
}

/** Most notifications one run may send, whatever the table says. */
if ( ! defined( 'KAAMASE_MILESTONE_MAX_SENDS' ) ) {
	define( 'KAAMASE_MILESTONE_MAX_SENDS', 200 );
}

if ( ! function_exists( 'kaamase_milestone_meta' ) ) {
	/**
	 * Where the last figure somebody was told is kept.
	 *
	 * On the profile or job rather than on the account, because one
	 * employer can have twenty jobs and each of them passes a hundred on
	 * its own day.
	 *
	 * @since 1.10.0
	 * @return string
	 */
	function kaamase_milestone_meta() {
		return KAAMASE_META_PREFIX . 'views_milestone';
	}
}

if ( ! function_exists( 'kaamase_milestone_types' ) ) {
	/**
	 * Which kinds of page this is sent about.
	 *
	 * Workers, teams and jobs. Not employer profiles: an employer's own
	 * page being opened is not the same good news, and telling somebody
	 * their company page is popular is flattery rather than information.
	 *
	 * @since 1.10.0
	 * @return string[]
	 */
	function kaamase_milestone_types() {

		return (array) apply_filters(
			'kaamase_milestone_types',
			array( 'kaamase_worker', 'kaamase_gang', 'kaamase_job' )
		);
	}
}

if ( ! function_exists( 'kaamase_milestones' ) ) {
	/**
	 * The figures worth telling somebody about.
	 *
	 * Close together at the start and further apart later, which is the
	 * only shape that works. The first ten opens are the ones that prove
	 * the platform is doing something, so they get their own
	 * notification; by the time somebody is at five thousand, another
	 * hundred is not news and a notification about it is noise.
	 *
	 * Ten, fifty, a hundred, a hundred and sixty, two hundred, then
	 * every hundred to a thousand. After that every five hundred to five
	 * thousand, then every thousand. The top of the ladder is a stop
	 * rather than a cliff: past it nothing more is sent, which is the
	 * right behaviour for a number that large.
	 *
	 * @since 1.10.0
	 * @return int[] Ascending.
	 */
	function kaamase_milestones() {

		static $steps = null;

		if ( null !== $steps ) {
			return $steps;
		}

		$ladder = array( 10, 50, 100, 160, 200, 300, 400, 500, 600, 700, 800, 900, 1000 );

		for ( $figure = 1500; $figure <= 5000; $figure += 500 ) {
			$ladder[] = $figure;
		}

		for ( $figure = 6000; $figure <= 50000; $figure += 1000 ) {
			$ladder[] = $figure;
		}

		/**
		 * Filter the figures a milestone notification is sent at.
		 *
		 * @since 1.10.0
		 * @param int[] $ladder Figures, ascending.
		 */
		$ladder = array_map( 'absint', (array) apply_filters( 'kaamase_milestones', $ladder ) );
		$ladder = array_values( array_unique( array_filter( $ladder ) ) );

		/*
		 * Sorted here rather than trusted. Reading below stops at the
		 * first figure the total has not reached, which is only correct
		 * on an ascending list, and a filter has no reason to know that.
		 */
		sort( $ladder, SORT_NUMERIC );

		$steps = $ladder;

		return $steps;
	}
}

if ( ! function_exists( 'kaamase_milestone_reached' ) ) {
	/**
	 * The highest figure a total has passed, or nothing.
	 *
	 * @since 1.10.0
	 * @param int $total How many times it has been opened.
	 * @return int A figure from the ladder, or 0 below the first one.
	 */
	function kaamase_milestone_reached( $total ) {

		$total   = (int) $total;
		$reached = 0;

		foreach ( kaamase_milestones() as $figure ) {

			if ( $total < $figure ) {
				break;
			}

			$reached = $figure;
		}

		return $reached;
	}
}

if ( ! function_exists( 'kaamase_milestone_seed' ) ) {
	/**
	 * Write down where everybody already is, and send nothing.
	 *
	 * Run once, the first time this feature is able to send anything at
	 * all. Without it the first run would look at a table holding a year
	 * of views and tell several hundred people at once that their
	 * profile has passed a figure it passed months ago. That is the
	 * worst possible first impression of a notification meant to feel
	 * like good news, and it would teach people to turn them off.
	 *
	 * Afterwards a profile with nothing written down is genuinely new,
	 * so its first ten opens are a real milestone and are sent.
	 *
	 * @since 1.10.0
	 * @return bool Whether this run did the seeding.
	 */
	function kaamase_milestone_seed() {

		global $wpdb;

		if ( get_option( 'kaamase_milestones_seeded' ) ) {
			return false;
		}

		$table  = kaamase_views_table();
		$meta   = kaamase_milestone_meta();
		$ladder = kaamase_milestones();
		$first  = empty( $ladder ) ? 0 : (int) $ladder[0];

		if ( ! $first ) {
			update_option( 'kaamase_milestones_seeded', 1, false );
			return true;
		}

		/*
		 * The whole table, once. HAVING keeps it to the profiles that
		 * are already past the first figure, which on any real site is a
		 * small fraction of the rows and the only ones worth a meta
		 * write.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT subject_id, SUM(hits) AS total
				 FROM {$table}
				 WHERE kind = 'open'
				 GROUP BY subject_id
				 HAVING total >= %d",
				$first
			),
			ARRAY_A
		);
		// phpcs:enable

		foreach ( (array) $rows as $row ) {

			$reached = kaamase_milestone_reached( (int) $row['total'] );

			if ( $reached ) {
				update_post_meta( (int) $row['subject_id'], $meta, $reached );
			}
		}

		/*
		 * Marked done only after the loop. A run that dies halfway
		 * leaves the option unset and does the whole thing again next
		 * hour, which is safe: writing the same figure twice changes
		 * nothing.
		 */
		update_option( 'kaamase_milestones_seeded', 1, false );

		return true;
	}
}

if ( ! function_exists( 'kaamase_milestone_send' ) ) {
	/**
	 * Tell the owner their profile or job passed a figure.
	 *
	 * Push only, never email. A view count is the kind of thing that is
	 * a pleasure to see on a phone and an annoyance to find in an inbox,
	 * and kaamase_notify_user's fallback would put it there.
	 *
	 * @since 1.10.0
	 * @param int $post_id Profile or job.
	 * @param int $figure  The figure it passed.
	 * @return bool Whether anything was sent.
	 */
	function kaamase_milestone_send( $post_id, $figure ) {

		$post   = get_post( $post_id );
		$figure = absint( $figure );

		if ( ! $post || ! $figure ) {
			return false;
		}

		/*
		 * Live pages only. A closed job or a profile still waiting on a
		 * confirmed email has nothing useful to say to its owner about
		 * how many people opened it, and a draft is not in front of
		 * anybody to open.
		 */
		if ( 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! in_array( $post->post_type, kaamase_milestone_types(), true ) ) {
			return false;
		}

		$owner = (int) $post->post_author;

		if ( ! $owner ) {
			return false;
		}

		/*
		 * The pair rather than a closure here, because what follows is
		 * three branches and a send and wrapping it would bury the
		 * wording. There is no return between here and the restore
		 * below, which is the condition for using the pair at all.
		 *
		 * Hourly task, so again there is no person in the request. And
		 * number_format_i18n is inside the switch on purpose: which
		 * digits a figure is written in is part of the language too.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $owner );

		$count = number_format_i18n( $figure );

		if ( 'kaamase_job' === $post->post_type ) {

			$title = sprintf(
				/* translators: %s: number of times the job has been opened */
				__( '%s opens on your job', 'kaamase-core' ),
				$count
			);

			$body = sprintf(
				/* translators: 1: job title, 2: number of times it has been opened */
				__( '%1$s has been opened %2$s times on Kaam Ase. Workers are looking.', 'kaamase-core' ),
				get_the_title( $post ),
				$count
			);

		} elseif ( 'kaamase_gang' === $post->post_type ) {

			$title = sprintf(
				/* translators: %s: number of times the team profile has been opened */
				__( '%s opens on your team', 'kaamase-core' ),
				$count
			);

			$body = sprintf(
				/* translators: %s: number of times the team profile has been opened */
				__( 'Employers have opened your team %s times on Kaam Ase. Keep it up to date so they can reach you.', 'kaamase-core' ),
				$count
			);

		} else {

			$title = sprintf(
				/* translators: %s: number of times the profile has been opened */
				__( '%s opens on your profile', 'kaamase-core' ),
				$count
			);

			$body = sprintf(
				/* translators: %s: number of times the profile has been opened */
				__( 'Employers have opened your profile %s times on Kaam Ase. Set yourself available when you are free for work.', 'kaamase-core' ),
				$count
			);
		}

		/*
		 * The count travels with it so the app can show the figure
		 * straight away rather than fetching the profile to find out
		 * what the notification was about.
		 */
		kaamase_push_to_user(
			$owner,
			$title,
			$body,
			array(
				'type'  => 'view_milestone',
				'id'    => (int) $post->ID,
				'count' => $figure,
			)
		);

		if ( $switched ) {
			kaamase_locale_restore();
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_milestone_check' ) ) {
	/**
	 * Find what crossed a figure, and say so.
	 *
	 * Hourly, because a milestone notification arriving the following
	 * afternoon is not the same thing at all; the whole appeal is that
	 * it lands while it is still true. Two indexed queries per run, and
	 * on most runs nothing crossed anything and nothing is written.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_milestone_check() {

		global $wpdb;

		/*
		 * Nothing at all when push is off, including the seeding. A site
		 * that has never sent a notification should not be carrying
		 * around a record of figures nobody was told, and when push is
		 * switched on later the seeding runs then and starts everybody
		 * from where they actually are.
		 */
		if ( ! kaamase_push_enabled() ) {
			return;
		}

		if ( ! function_exists( 'kaamase_views_ready' ) || ! kaamase_views_ready() ) {
			return;
		}

		/*
		 * Not in the middle of the night. This is the one notification
		 * on the list that could have waited until morning, so it does.
		 * Nothing is lost by skipping a run: the query below looks back
		 * two days, so whatever crossed at one in the morning is still
		 * found at eight.
		 */
		$hour = (int) current_time( 'G' );

		if ( $hour < 8 || $hour >= 21 ) {
			return;
		}

		if ( kaamase_milestone_seed() ) {
			return;
		}

		$table = kaamase_views_table();
		$since = gmdate( 'Y-m-d', time() - ( KAAMASE_MILESTONE_DAYS * DAY_IN_SECONDS ) );

		/*
		 * Which pages were opened recently, not which pages exist. A row
		 * is only ever written on the day it was made, so anything that
		 * moved since the last run is inside this window, and the
		 * seen_on index makes it a short read however big the table has
		 * grown.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$subjects = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT subject_id
				 FROM {$table}
				 WHERE kind = 'open' AND seen_on >= %s
				 ORDER BY subject_id ASC
				 LIMIT 5000",
				$since
			)
		);
		// phpcs:enable

		$subjects = array_filter( array_map( 'absint', (array) $subjects ) );

		if ( empty( $subjects ) ) {
			return;
		}

		$meta = kaamase_milestone_meta();
		$sent = 0;

		/*
		 * In chunks, so the totalling query stays a reasonable size
		 * whatever kind of day the site has had. Every chunk is asked
		 * for whole totals, not the window above: the figure in the
		 * notification has to be the figure on the profile.
		 */
		foreach ( array_chunk( $subjects, 200 ) as $chunk ) {

			$ids = implode( ',', array_map( 'absint', $chunk ) );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				"SELECT subject_id, SUM(hits) AS total
				 FROM {$table}
				 WHERE kind = 'open' AND subject_id IN ({$ids})
				 GROUP BY subject_id",
				ARRAY_A
			);
			// phpcs:enable

			foreach ( (array) $rows as $row ) {

				$post_id = (int) $row['subject_id'];
				$reached = kaamase_milestone_reached( (int) $row['total'] );

				if ( ! $reached ) {
					continue;
				}

				$told = (int) get_post_meta( $post_id, $meta, true );

				if ( $reached <= $told ) {
					continue;
				}

				/*
				 * Out of budget: left unwritten, deliberately. The cap
				 * exists to stop a blast, not to throw milestones away,
				 * and anything skipped here is simply found again next
				 * hour. It cannot starve either, because everything
				 * that did get sent is written down and drops out of
				 * this set, so the queue is two hundred shorter every
				 * time round and the ones behind move up.
				 */
				if ( $sent >= KAAMASE_MILESTONE_MAX_SENDS ) {
					continue;
				}

				/*
				 * Written before the send, and written even when the
				 * send does nothing -- a closed job, a draft, an owner
				 * with no app. A figure that was reached is reached;
				 * holding it back would mean somebody installing the
				 * app in March being told about a hundred opens from
				 * January.
				 */
				update_post_meta( $post_id, $meta, $reached );

				$sent += kaamase_milestone_send( $post_id, $reached ) ? 1 : 0;
			}
		}
	}
}
add_action( 'kaamase_milestones_check', 'kaamase_milestone_check' );

if ( ! function_exists( 'kaamase_milestone_schedule' ) ) {
	/**
	 * Book the hourly run.
	 *
	 * Checked on every load rather than only on activation, for the same
	 * reason the daily task is: a cron event that quietly stopped being
	 * scheduled is invisible until somebody notices that nothing has
	 * been sent in a month.
	 *
	 * @since 1.10.0
	 * @return void
	 */
	function kaamase_milestone_schedule() {

		if ( ! wp_next_scheduled( 'kaamase_milestones_check' ) ) {
			wp_schedule_event( time() + ( 10 * MINUTE_IN_SECONDS ), 'hourly', 'kaamase_milestones_check' );
		}
	}
}
add_action( 'init', 'kaamase_milestone_schedule', 30 );



/* ==========================================================================
   4. SETTING
   ========================================================================== */

/**
 * Add the push switch to the settings screen.
 *
 * @since 1.3.0
 * @return void
 */
function kaamase_register_push_setting() {

	register_setting(
		'kaamase_settings',
		'kaamase_push_enabled',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		)
	);
}
add_action( 'admin_init', 'kaamase_register_push_setting' );