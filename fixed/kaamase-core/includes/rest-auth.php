<?php
/**
 * API authentication.
 *
 * Bearer tokens for the phone app.
 *
 * Why not one of the existing options
 * -----------------------------------
 * WordPress offers cookies and application passwords. Cookies need a
 * browser and a nonce, so they are no use to a native app. Application
 * passwords are built for one developer wiring up a script, not for
 * several thousand workers signing in on their own phones: they cannot
 * be issued during registration, they cannot expire, and the user is
 * expected to copy a generated string by hand.
 *
 * A JWT plugin would work, and it would also mean the security of every
 * worker's account on this platform depends on a third party plugin
 * being maintained. That is a decision worth avoiding when the
 * alternative is two hundred lines.
 *
 * How a token is built
 * --------------------
 *   <user id>.<48 random characters>
 *
 * The user id is in the token so a lookup is one get_user_meta rather
 * than a scan of every user on the site. It is not a secret; knowing
 * somebody's user id gets you nowhere without the second half.
 *
 * Only a hash of the second half is stored, and it is an HMAC keyed on
 * the site's auth salt rather than a plain hash. So a stolen database
 * does not hand over working tokens unless wp-config.php went with it.
 *
 * What a token is not
 * -------------------
 * It is not a password reset, and it is not a way around verification.
 * A token proves which account is calling. Everything about what that
 * account may then do is still decided by the same capability and gate
 * checks the website uses.
 *
 * @package KaamaseCore
 * @version 1.3.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. SETTINGS
   ========================================================================== */

if ( ! function_exists( 'kaamase_token_lifetime' ) ) {
	/**
	 * How long a token stays valid without being used.
	 *
	 * Sixty days, refreshed on every call. A worker who opens the app
	 * once a month is never signed out; one who loses their phone and
	 * never signs in again stops being a live credential after two
	 * months.
	 *
	 * @since 1.3.0
	 * @return int Seconds.
	 */
	function kaamase_token_lifetime() {

		/**
		 * Filter the API token lifetime.
		 *
		 * @since 1.3.0
		 * @param int $seconds Lifetime in seconds.
		 */
		return absint( apply_filters( 'kaamase_token_lifetime', 60 * DAY_IN_SECONDS ) );
	}
}

if ( ! function_exists( 'kaamase_token_limit' ) ) {
	/**
	 * How many devices one account may be signed in on.
	 *
	 * Phones get sold, broken and shared here more than the average
	 * market assumes, so this is generous. The oldest token is dropped
	 * when the limit is reached, which signs out the device somebody
	 * stopped using rather than the one in their hand.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	function kaamase_token_limit() {
		return absint( apply_filters( 'kaamase_token_limit', 10 ) );
	}
}


/* ==========================================================================
   2. ISSUING
   ========================================================================== */

if ( ! function_exists( 'kaamase_issue_token' ) ) {
	/**
	 * Create a token for a user.
	 *
	 * @since 1.3.0
	 * @param int    $user_id User ID.
	 * @param string $device  Human readable device label.
	 * @return array Token string and its expiry.
	 */
	function kaamase_issue_token( $user_id, $device = '' ) {

		$user_id = absint( $user_id );
		$secret  = wp_generate_password( 48, false, false );
		$expires = time() + kaamase_token_lifetime();

		$tokens = kaamase_meta_array( get_user_meta( $user_id, 'kaamase_tokens', true ) );

		$tokens[] = array(
			'hash'      => kaamase_hash_token( $secret ),
			'expires'   => $expires,
			'device'    => sanitize_text_field( mb_substr( (string) $device, 0, 60 ) ),
			'created'   => time(),
			'last_used' => time(),
		);

		// Oldest out first when the device limit is reached.
		if ( count( $tokens ) > kaamase_token_limit() ) {

			usort(
				$tokens,
				static function ( $a, $b ) {
					return absint( $a['created'] ) <=> absint( $b['created'] );
				}
			);

			$tokens = array_slice( $tokens, -kaamase_token_limit() );
		}

		update_user_meta( $user_id, 'kaamase_tokens', array_values( $tokens ) );

		return array(
			'token'      => $user_id . '.' . $secret,
			'expires_at' => $expires,
		);
	}
}

if ( ! function_exists( 'kaamase_hash_token' ) ) {
	/**
	 * Hash the secret half of a token.
	 *
	 * Keyed on the site auth salt, so the stored value is useless on any
	 * other installation and useless without wp-config.php.
	 *
	 * @since 1.3.0
	 * @param string $secret Raw secret.
	 * @return string Hash.
	 */
	function kaamase_hash_token( $secret ) {
		return hash_hmac( 'sha256', (string) $secret, wp_salt( 'auth' ) );
	}
}

if ( ! function_exists( 'kaamase_revoke_token' ) ) {
	/**
	 * Remove one token, or every token for a user.
	 *
	 * @since 1.3.0
	 * @param int    $user_id User ID.
	 * @param string $token   Full token string, or an empty string for all.
	 * @return void
	 */
	function kaamase_revoke_token( $user_id, $token = '' ) {

		$user_id = absint( $user_id );

		if ( '' === $token ) {
			delete_user_meta( $user_id, 'kaamase_tokens' );
			return;
		}

		$parts = explode( '.', (string) $token, 2 );

		if ( count( $parts ) !== 2 ) {
			return;
		}

		$hash   = kaamase_hash_token( $parts[1] );
		$tokens = kaamase_meta_array( get_user_meta( $user_id, 'kaamase_tokens', true ) );

		$kept = array();

		foreach ( $tokens as $entry ) {

			if ( isset( $entry['hash'] ) && hash_equals( (string) $entry['hash'], $hash ) ) {
				continue;
			}

			$kept[] = $entry;
		}

		update_user_meta( $user_id, 'kaamase_tokens', $kept );
	}
}


/* ==========================================================================
   3. VERIFYING
   ========================================================================== */

if ( ! function_exists( 'kaamase_user_from_token' ) ) {
	/**
	 * Resolve a token to a user.
	 *
	 * Expired entries are dropped as they are encountered, so the meta
	 * tidies itself without a scheduled task.
	 *
	 * @since 1.3.0
	 * @param string $token Full token string.
	 * @return int User ID, or 0.
	 */
	function kaamase_user_from_token( $token ) {

		$parts = explode( '.', (string) $token, 2 );

		if ( count( $parts ) !== 2 ) {
			return 0;
		}

		$user_id = absint( $parts[0] );
		$secret  = $parts[1];

		if ( ! $user_id || '' === $secret ) {
			return 0;
		}

		$tokens = kaamase_meta_array( get_user_meta( $user_id, 'kaamase_tokens', true ) );

		if ( empty( $tokens ) ) {
			return 0;
		}

		$hash    = kaamase_hash_token( $secret );
		$now     = time();
		$matched = false;
		$kept    = array();
		$changed = false;

		foreach ( $tokens as $entry ) {

			if ( empty( $entry['hash'] ) || absint( $entry['expires'] ) < $now ) {
				$changed = true;
				continue;
			}

			// hash_equals, never ===. Timing matters on a credential check.
			if ( hash_equals( (string) $entry['hash'], $hash ) ) {

				$matched = true;

				// Sliding expiry, written at most once a day.
				if ( $now - absint( $entry['last_used'] ) > DAY_IN_SECONDS ) {
					$entry['last_used'] = $now;
					$entry['expires']   = $now + kaamase_token_lifetime();
					$changed            = true;
				}
			}

			$kept[] = $entry;
		}

		if ( $changed ) {
			update_user_meta( $user_id, 'kaamase_tokens', $kept );
		}

		return $matched ? $user_id : 0;
	}
}

if ( ! function_exists( 'kaamase_bearer_token' ) ) {
	/**
	 * The bearer token on this request, if there is one.
	 *
	 * Some shared hosts strip the Authorization header before PHP sees
	 * it, which is the single most common reason a token API works in
	 * testing and fails on the customer's server. The fallbacks cover the
	 * two rewrites that usually carry it instead.
	 *
	 * @since 1.3.0
	 * @return string Token, or an empty string.
	 */
	function kaamase_bearer_token() {

		$header = '';

		foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$header = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				break;
			}
		}

		if ( '' === $header && function_exists( 'apache_request_headers' ) ) {

			$headers = apache_request_headers();

			foreach ( (array) $headers as $name => $value ) {
				if ( 'authorization' === strtolower( (string) $name ) ) {
					$header = sanitize_text_field( $value );
					break;
				}
			}
		}

		if ( '' === $header || ! preg_match( '/^Bearer\s+(\S+)$/i', $header, $match ) ) {
			return '';
		}

		return $match[1];
	}
}

if ( ! function_exists( 'kaamase_authenticate_bearer' ) ) {
	/**
	 * Sign in the account behind the bearer token.
	 *
	 * Hooked onto determine_current_user, so everything downstream,
	 * including every capability check the website already relies on,
	 * behaves exactly as it does for a signed in browser.
	 *
	 * @since 1.3.0
	 * @param int|false $user_id User ID determined so far.
	 * @return int|false User ID.
	 */
	function kaamase_authenticate_bearer( $user_id ) {

		// Something else already identified the caller. Leave it alone.
		if ( $user_id ) {
			return $user_id;
		}

		/*
		 * get_user_meta can trigger determine_current_user again on some
		 * setups. Without this guard that is an infinite loop and a white
		 * screen on every request.
		 */
		static $running = false;

		if ( $running ) {
			return $user_id;
		}

		$token = kaamase_bearer_token();

		if ( '' === $token ) {
			return $user_id;
		}

		$running = true;
		$found   = kaamase_user_from_token( $token );
		$running = false;

		return $found ? $found : $user_id;
	}
}
add_filter( 'determine_current_user', 'kaamase_authenticate_bearer', 20 );


/* ==========================================================================
   4. SIGN IN RATE LIMITING

   A public login endpoint is a password guessing endpoint unless
   somebody stops it being one.
   ========================================================================== */

if ( ! function_exists( 'kaamase_login_attempts_key' ) ) {
	/**
	 * Transient key for failed sign in attempts from this connection.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	function kaamase_login_attempts_key() {

		$address = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: 'unknown';

		return 'kaamase_login_fail_' . md5( $address );
	}
}

if ( ! function_exists( 'kaamase_login_blocked' ) ) {
	/**
	 * Whether this connection has failed too many times.
	 *
	 * Ten in fifteen minutes. A person who has forgotten which of two
	 * passwords they used will not hit it. A script working through a
	 * list will hit it almost immediately.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	function kaamase_login_blocked() {
		return (int) kaamase_rate_value( kaamase_login_attempts_key(), 0 ) >= 10;
	}
}

if ( ! function_exists( 'kaamase_login_failed' ) ) {
	/**
	 * Count a failed sign in.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_login_failed() {

		/*
		 * Durable. A lockout kept in a transient is cleared by any
		 * caching plugin's Clear Transients button, which hands a
		 * password guesser a fresh ten attempts.
		 */
		kaamase_rate_bump( kaamase_login_attempts_key(), 15 * MINUTE_IN_SECONDS );
	}
}

if ( ! function_exists( 'kaamase_login_succeeded' ) ) {
	/**
	 * Clear the failure count after a good sign in.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_login_succeeded() {
		kaamase_rate_clear( kaamase_login_attempts_key() );
	}
}


/* ==========================================================================
   5. HOUSEKEEPING
   ========================================================================== */

/**
 * Drop every token when somebody changes their password.
 *
 * The point of changing a password is usually that somebody else has
 * it. Leaving old app sessions signed in would make that gesture
 * meaningless on the device that matters.
 *
 * @since 1.3.0
 * @param int $user_id User ID.
 * @return void
 */
function kaamase_revoke_tokens_after_reset( $user_id ) {
	kaamase_revoke_token( $user_id );
}
add_action( 'after_password_reset', 'kaamase_revoke_tokens_after_reset' );

/**
 * Drop tokens on a profile update, but only when the password moved.
 *
 * profile_update fires on every save of a user record, including the
 * ordinary ones this platform does itself. Revoking on all of them
 * would sign somebody out of their phone every time they edited their
 * own town, which reads as the app randomly logging you out.
 *
 * @since 1.3.0
 * @param int     $user_id       User ID.
 * @param WP_User $old_user_data The record as it was before the save.
 * @return void
 */
function kaamase_revoke_tokens_on_password_change( $user_id, $old_user_data = null ) {

	if ( ! $old_user_data instanceof WP_User ) {
		return;
	}

	$new = get_userdata( $user_id );

	if ( ! $new || $new->user_pass === $old_user_data->user_pass ) {
		return;
	}

	kaamase_revoke_token( $user_id );
}
add_action( 'profile_update', 'kaamase_revoke_tokens_on_password_change', 10, 2 );
