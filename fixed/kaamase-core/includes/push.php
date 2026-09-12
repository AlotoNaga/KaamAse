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
 * Sent from elsewhere, through this layer:
 *
 *   4. Somebody has asked for your number, and the answer to a request
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
 * @version 1.4.0
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

	kaamase_push_to_user(
		$user_id,
		__( 'Your profile is live', 'kaamase-core' ),
		__( 'Employers across Nagaland can find you now. Set yourself available when you are free for work.', 'kaamase-core' ),
		array( 'type' => 'verified' )
	);
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
   3. SETTING
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