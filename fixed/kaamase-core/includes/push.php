<?php
/**
 * Sending a notification to somebody's phone.
 *
 * The app has been handing this site a push token since it first shipped
 * and the site has never once used it. /push/register stored the token
 * and nothing else in the plugin knew what to do with it. This is the
 * other half.
 *
 * Why Expo rather than Firebase directly
 * --------------------------------------
 * The app is built with Expo, so the tokens already stored are Expo
 * tokens. Expo's service takes one HTTP request and works out whether
 * the phone on the other end is Android or iPhone, which is the whole
 * job. Going straight to Firebase would mean a service account key on
 * this server, a second path for iPhones, and re-registering every
 * device that is already registered.
 *
 * What this deliberately does not do
 * ----------------------------------
 * It does not retry, and it does not queue. A notification that fails
 * is gone. That sounds careless until you consider the alternative on a
 * shared host: a retry queue that nobody watches, filling up during an
 * outage and then delivering four hundred stale notifications at once
 * when the connection returns. Everything sent from here is also
 * available on the dashboard, so a lost notification costs somebody a
 * later look at their account rather than the information itself.
 *
 * A dead token is cleaned up
 * --------------------------
 * When Expo says a device is no longer registered, the token is
 * removed. Without that, somebody who uninstalls the app leaves a
 * token behind that is retried on every notification for ever.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHO CAN BE REACHED
   ========================================================================== */

if ( ! function_exists( 'kaamase_push_tokens' ) ) {
	/**
	 * The push tokens held for an account.
	 *
	 * @since 1.6.0
	 * @param int $user_id User ID.
	 * @return string[] Tokens, empty when the person has no app.
	 */
	function kaamase_push_tokens( $user_id ) {

		$tokens = kaamase_meta_array( get_user_meta( (int) $user_id, 'kaamase_push_tokens', true ) );

		$tokens = array_filter(
			array_map( 'strval', $tokens ),
			static function ( $token ) {
				return (bool) preg_match( '/^Expo(nent)?PushToken\[[^\]]+\]$/', $token );
			}
		);

		return array_values( array_unique( $tokens ) );
	}
}

if ( ! function_exists( 'kaamase_push_has_app' ) ) {
	/**
	 * Whether this person can be reached on a phone at all.
	 *
	 * The question that decides between a notification and an email.
	 *
	 * @since 1.6.0
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_push_has_app( $user_id ) {
		return ! empty( kaamase_push_tokens( $user_id ) );
	}
}

if ( ! function_exists( 'kaamase_push_forget' ) ) {
	/**
	 * Drop a token Expo has told us is dead.
	 *
	 * @since 1.6.0
	 * @param int    $user_id User ID.
	 * @param string $token   The token to remove.
	 * @return void
	 */
	function kaamase_push_forget( $user_id, $token ) {

		$user_id = (int) $user_id;
		$tokens  = kaamase_push_tokens( $user_id );
		$left    = array_values( array_diff( $tokens, array( (string) $token ) ) );

		if ( count( $left ) === count( $tokens ) ) {
			return;
		}

		if ( empty( $left ) ) {
			delete_user_meta( $user_id, 'kaamase_push_tokens' );
			return;
		}

		update_user_meta( $user_id, 'kaamase_push_tokens', $left );
	}
}


/* ==========================================================================
   2. SENDING
   ========================================================================== */

if ( ! function_exists( 'kaamase_push_send' ) ) {
	/**
	 * Send one notification to every phone an account has.
	 *
	 * @since 1.6.0
	 * @param int    $user_id User to notify.
	 * @param string $title   Notification title. Short.
	 * @param string $body    The sentence underneath it.
	 * @param array  $data    Anything the app needs to open the right screen.
	 * @return bool Whether at least one phone accepted it.
	 */
	function kaamase_push_send( $user_id, $title, $body, $data = array() ) {

		$user_id = (int) $user_id;
		$tokens  = kaamase_push_tokens( $user_id );

		if ( empty( $tokens ) ) {
			return false;
		}

		/**
		 * Filter a notification before it leaves.
		 *
		 * Returning an empty title stops the send, which is the hook a
		 * per person notification setting would use later.
		 *
		 * @since 1.6.0
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
			$user_id
		);

		if ( empty( $message['title'] ) ) {
			return false;
		}

		$payload = array();

		foreach ( $tokens as $token ) {

			$payload[] = array(
				'to'    => $token,
				'title' => mb_substr( wp_strip_all_tags( (string) $message['title'] ), 0, 100 ),
				'body'  => mb_substr( wp_strip_all_tags( (string) $message['body'] ), 0, 240 ),
				'data'  => (array) $message['data'],
				'sound' => 'default',
			);
		}

		$response = wp_remote_post(
			'https://exp.host/--/api/v2/push/send',
			array(
				'timeout' => 12,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body_json = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body_json ) || ! isset( $body_json['data'] ) || ! is_array( $body_json['data'] ) ) {
			return false;
		}

		$delivered = false;

		/*
		 * Expo answers in the order it was asked, so the nth result
		 * belongs to the nth token. A device that has been wiped or had
		 * the app removed comes back as DeviceNotRegistered, and that
		 * token is dropped rather than retried for ever.
		 */
		foreach ( array_values( $body_json['data'] ) as $index => $result ) {

			if ( ! is_array( $result ) ) {
				continue;
			}

			if ( isset( $result['status'] ) && 'ok' === $result['status'] ) {
				$delivered = true;
				continue;
			}

			$error = isset( $result['details']['error'] ) ? (string) $result['details']['error'] : '';

			if ( 'DeviceNotRegistered' === $error && isset( $tokens[ $index ] ) ) {
				kaamase_push_forget( $user_id, $tokens[ $index ] );
			}
		}

		return $delivered;
	}
}

if ( ! function_exists( 'kaamase_notify' ) ) {
	/**
	 * Tell somebody something, by whatever means reaches them.
	 *
	 * The phone first, because it arrives while they are holding it.
	 * Email when there is no app, because a worker who signed up on the
	 * website and never installed anything still needs to know.
	 *
	 * Never both. Two copies of the same sentence teaches people that
	 * this platform is noisy, and the ones who learn that stop reading
	 * either.
	 *
	 * @since 1.6.0
	 * @param int    $user_id  Who to tell.
	 * @param string $title    Short line. Used as the email subject too.
	 * @param string $body     The sentence.
	 * @param array  $data     Routing information for the app.
	 * @param string $mail_url A link to put at the end of the email.
	 * @return bool Whether anything went out.
	 */
	function kaamase_notify( $user_id, $title, $body, $data = array(), $mail_url = '' ) {

		$user_id = (int) $user_id;

		if ( kaamase_push_send( $user_id, $title, $body, $data ) ) {
			return true;
		}

		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			return false;
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

		return (bool) wp_mail(
			$user->user_email,
			wp_specialchars_decode( (string) $title, ENT_QUOTES ),
			implode( "\n", $lines )
		);
	}
}
