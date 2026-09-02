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
   5. THE DEVICES SOMEBODY IS SIGNED IN ON

   An account may hold ten tokens and nothing ever showed them.

   The device label, when it was made and when it was last used have all
   been stored since the day tokens were added, and none of it was ever
   put in front of the person it belongs to. So somebody whose phone was
   stolen, which on this platform is a real event rather than a
   hypothetical, had no way to sign that phone out. Their only options
   were to change their password, which revokes every device including
   the one in their hand, or to do nothing.

   A list and a button each.
   ========================================================================== */

if ( ! function_exists( 'kaamase_device_id' ) ) {
	/**
	 * A short, stable handle for one token.
	 *
	 * Derived from the stored hash by hashing it again, so the value
	 * printed into a page and posted back in a form is not the stored
	 * credential and cannot be turned back into it.
	 *
	 * @since 1.3.4
	 * @param array $entry Token entry.
	 * @return string
	 */
	function kaamase_device_id( $entry ) {

		if ( empty( $entry['hash'] ) ) {
			return '';
		}

		return substr( hash( 'sha256', (string) $entry['hash'] ), 0, 12 );
	}
}

if ( ! function_exists( 'kaamase_user_devices' ) ) {
	/**
	 * Every device this account is signed in on.
	 *
	 * Expired entries are left out rather than shown as dead rows.
	 * The hash never leaves this function.
	 *
	 * @since 1.3.4
	 * @param int $user_id User ID.
	 * @return array[] Device rows, most recently used first.
	 */
	function kaamase_user_devices( $user_id ) {

		$tokens = kaamase_meta_array( get_user_meta( absint( $user_id ), 'kaamase_tokens', true ) );
		$now    = time();
		$out    = array();

		foreach ( $tokens as $entry ) {

			if ( empty( $entry['hash'] ) || absint( $entry['expires'] ) < $now ) {
				continue;
			}

			$out[] = array(
				'id'        => kaamase_device_id( $entry ),
				'device'    => (string) ( $entry['device'] ?? '' ),
				'created'   => absint( $entry['created'] ?? 0 ),
				'last_used' => absint( $entry['last_used'] ?? 0 ),
				'expires'   => absint( $entry['expires'] ?? 0 ),
			);
		}

		usort(
			$out,
			static function ( $a, $b ) {
				return $b['last_used'] <=> $a['last_used'];
			}
		);

		return $out;
	}
}

if ( ! function_exists( 'kaamase_revoke_device' ) ) {
	/**
	 * Sign one device out.
	 *
	 * @since 1.3.4
	 * @param int    $user_id   User ID.
	 * @param string $device_id Handle from kaamase_device_id().
	 * @return bool Whether anything was removed.
	 */
	function kaamase_revoke_device( $user_id, $device_id ) {

		$user_id   = absint( $user_id );
		$device_id = sanitize_key( $device_id );

		if ( ! $user_id || '' === $device_id ) {
			return false;
		}

		$tokens  = kaamase_meta_array( get_user_meta( $user_id, 'kaamase_tokens', true ) );
		$kept    = array();
		$removed = false;

		foreach ( $tokens as $entry ) {

			// hash_equals, because this compares one derived value against another.
			if ( ! empty( $entry['hash'] ) && hash_equals( kaamase_device_id( $entry ), $device_id ) ) {
				$removed = true;
				continue;
			}

			$kept[] = $entry;
		}

		if ( $removed ) {
			update_user_meta( $user_id, 'kaamase_tokens', $kept );
		}

		return $removed;
	}
}

if ( ! function_exists( 'kaamase_device_last_seen' ) ) {
	/**
	 * When a device was last used, in words.
	 *
	 * @since 1.3.4
	 * @param int $stamp Timestamp.
	 * @return string
	 */
	function kaamase_device_last_seen( $stamp ) {

		$stamp = absint( $stamp );

		if ( ! $stamp ) {
			return __( 'Not used yet', 'kaamase-core' );
		}

		$ago = time() - $stamp;

		if ( $ago < HOUR_IN_SECONDS ) {
			return __( 'Used in the last hour', 'kaamase-core' );
		}

		return sprintf(
			/* translators: %s: human readable time difference, for example 3 days */
			__( 'Last used %s ago', 'kaamase-core' ),
			human_time_diff( $stamp, time() )
		);
	}
}

if ( ! function_exists( 'kaamase_devices_section' ) ) {
	/**
	 * The devices panel on the dashboard.
	 *
	 * Shown only when there is something to show. An account that has
	 * never opened the app gets no heading about phones.
	 *
	 * @since 1.3.4
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_devices_section( $user_id ) {

		$devices = kaamase_user_devices( $user_id );

		if ( empty( $devices ) ) {
			return;
		}

		?>
		<section class="ka-card ka-stack ka-mt-6">

			<h2><?php esc_html_e( 'Phones signed in', 'kaamase-core' ); ?></h2>

			<p class="ka-small ka-soft">
				<?php esc_html_e( 'These are the phones the Kaam Ase app is signed in on with your account. If you do not recognise one, or a phone was lost or sold, sign it out here.', 'kaamase-core' ); ?>
			</p>

			<?php foreach ( $devices as $device ) : ?>
				<div class="ka-stack">

					<p>
						<strong><?php echo esc_html( '' !== $device['device'] ? $device['device'] : __( 'A phone', 'kaamase-core' ) ); ?></strong><br>
						<span class="ka-small ka-soft"><?php echo esc_html( kaamase_device_last_seen( $device['last_used'] ) ); ?></span>
					</p>

					<form method="post" action="">
						<?php wp_nonce_field( 'kaamase_revoke_device', 'kaamase_device_nonce' ); ?>
						<input type="hidden" name="action" value="kaamase_revoke_device">
						<input type="hidden" name="device" value="<?php echo esc_attr( $device['id'] ); ?>">
						<button type="submit" class="ka-btn ka-btn--outline">
							<?php esc_html_e( 'Sign this phone out', 'kaamase-core' ); ?>
						</button>
					</form>
				</div>
			<?php endforeach; ?>

			<?php if ( count( $devices ) > 1 ) : ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'kaamase_revoke_device', 'kaamase_device_nonce' ); ?>
					<input type="hidden" name="action" value="kaamase_revoke_device">
					<input type="hidden" name="device" value="all">
					<button type="submit" class="ka-btn ka-btn--outline"
						data-ka-confirm="<?php esc_attr_e( 'This signs you out of the app on every phone, including this one. Are you sure?', 'kaamase-core' ); ?>">
						<?php esc_html_e( 'Sign out of every phone', 'kaamase-core' ); ?>
					</button>
				</form>
			<?php endif; ?>
		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_devices_section', 40 );

if ( ! function_exists( 'kaamase_handle_revoke_device' ) ) {
	/**
	 * Sign a device out when the form comes back.
	 *
	 * @since 1.3.4
	 * @return void
	 */
	function kaamase_handle_revoke_device() {

		if ( ! is_user_logged_in() || empty( $_POST['action'] ) ) {
			return;
		}

		if ( 'kaamase_revoke_device' !== sanitize_key( wp_unslash( $_POST['action'] ) ) ) {
			return;
		}

		if ( ! isset( $_POST['kaamase_device_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_device_nonce'] ) ), 'kaamase_revoke_device' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$device  = isset( $_POST['device'] ) ? sanitize_key( wp_unslash( $_POST['device'] ) ) : '';

		if ( 'all' === $device ) {
			kaamase_revoke_token( $user_id );
		} else {
			kaamase_revoke_device( $user_id, $device );
		}

		wp_safe_redirect(
			add_query_arg( 'devices', 'signedout', kaamase_page_url( 'dashboard' ) )
		);
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_revoke_device' );

if ( ! function_exists( 'kaamase_devices_notice' ) ) {
	/**
	 * Say that it worked.
	 *
	 * Signing a phone out produces no visible change on the dashboard
	 * unless the row happened to be on screen, so without this the
	 * person presses the button, the page reloads, and they cannot tell
	 * whether anything happened. On the screen you go to because your
	 * phone was stolen, that is not good enough.
	 *
	 * @since 1.3.4
	 * @return void
	 */
	function kaamase_devices_notice() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, decides whether to print a confirmation.
		$state = isset( $_GET['devices'] ) ? sanitize_key( wp_unslash( $_GET['devices'] ) ) : '';

		if ( 'signedout' !== $state ) {
			return;
		}

		printf(
			'<div class="ka-notice ka-notice--ok"><p>%s</p></div>',
			esc_html__( 'Signed out. That phone will have to sign in again to use the app.', 'kaamase-core' )
		);
	}
}
add_action( 'kaamase_dashboard_prompts', 'kaamase_devices_notice', 40 );


/* ==========================================================================
   6. HOUSEKEEPING
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


/* ==========================================================================
   6. KEEPING PERSONAL ANSWERS OUT OF THE CACHE

   A page cache decides what is "the same request" by the address and the
   cookies. The app sends no cookies. It signs in with an Authorization
   header, which the cache does not read and does not know exists.

   So every phone asking GET /wp-json/kaamase/v1/me looks identical to
   the cache: same address, no cookies. It stores the first answer and
   hands that same person's name, telephone number and profile to every
   phone that asks afterwards.

   This is why the website was never affected. A browser carries the
   WordPress sign in cookie, the cache sees it and steps aside. The app
   carries nothing the cache understands.

   The theme has the same guard for pages, but it cannot help here:
   WordPress serves a REST route and stops inside parse_request, which
   is before send_headers ever runs. An answer to the app therefore
   passes none of the page protections, and needs its own.
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_is_personal' ) ) {
	/**
	 * Whether this REST request carries credentials.
	 *
	 * Deliberately reads the header and the cookie names only. Working
	 * out which account it is would resolve the current user earlier
	 * than the route expects, and the answer to "is this personal" does
	 * not need a name attached to it.
	 *
	 * A stale or invalid credential counts as personal too. The cost of
	 * being wrong that way is one answer not cached. The cost of being
	 * wrong the other way is one person's details handed to a stranger.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	function kaamase_rest_is_personal() {

		if ( function_exists( 'kaamase_bearer_token' ) && '' !== kaamase_bearer_token() ) {
			return true;
		}

		foreach ( array_keys( (array) $_COOKIE ) as $name ) {
			if ( 0 === strpos( (string) $name, 'wordpress_logged_in_' ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'kaamase_rest_no_cache' ) ) {
	/**
	 * Forbid storing an answer that was written for one account.
	 *
	 * Public answers are left alone on purpose. A trade listing is the
	 * same for everybody and is worth caching on a village connection.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_rest_no_cache() {

		if ( ! kaamase_rest_is_personal() ) {
			return;
		}

		/*
		 * The constant and the switch before the headers_sent check.
		 * Neither needs headers, and these two are what actually decide
		 * whether the answer is kept. Response headers alone were not
		 * enough on this server: the /app page sent them and was served
		 * from store for hours.
		 */
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'kaamase personal api answer' );

		if ( headers_sent() ) {
			return;
		}

		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );

		/*
		 * And for anything between here and the phone that stores
		 * things anyway: this answer depends on who asked, and who
		 * asked is in this header. A copy made for one must never be
		 * handed to another.
		 */
		header( 'Vary: Authorization, Cookie', false );
	}
}
add_action( 'rest_api_init', 'kaamase_rest_no_cache', 0 );
