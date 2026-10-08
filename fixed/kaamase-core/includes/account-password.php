<?php
/**
 * Setting a password on an account that never had one.
 *
 * The gap this closes
 * -------------------
 * An account made with Google or with Apple is given a random password
 * thirty two characters long that its owner is never shown and could not
 * type if they wanted to. That is correct: nobody should be handed a
 * password they did not choose.
 *
 * The consequence was not correct. Signing in on a device where that
 * provider is not offered, or on the website, meant using a Forgot
 * password link for a password that never existed. For somebody who
 * checks an inbox once a month, on a borrowed phone, that is not a
 * recovery path. It is a wall.
 *
 * So an account can now be given a password from inside, while signed
 * in, and that password works everywhere: the app, the website, and
 * anybody's phone. The social buttons stay exactly as they are; this is
 * a second key, not a replacement for the first.
 *
 * Why it does not sign you out
 * ----------------------------
 * wp_set_password() is used rather than wp_update_user(). The latter
 * fires profile_update, which rest-auth.php listens for and answers by
 * revoking every token on the account. That is right for a password
 * RESET, where the point is to throw out whoever might be holding a
 * stolen session. It is wrong here, where somebody is adding a key while
 * holding a legitimate one, and being signed out of the app the moment
 * they succeed would read as a failure.
 *
 * Why the old password is still asked for
 * ---------------------------------------
 * Where there is one. Somebody who chose a password knows it, and asking
 * is what every service does, because a bearer token that has been stolen
 * should not be able to become a permanent credential. Where there has
 * never been one, there is nothing to ask for and demanding it would
 * lock out the exact people this file exists for.
 *
 * Either way the account is told by email that it happened, so a person
 * whose session was taken finds out from somewhere other than the
 * attacker.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.7.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHAT IS KNOWN ABOUT THE PASSWORD
   ========================================================================== */

if ( ! function_exists( 'kaamase_password_is_chosen' ) ) {
	/**
	 * Whether this account has a password its owner picked.
	 *
	 * Absent means yes, deliberately.
	 *
	 * Every account made through the registration form picked one, and
	 * those are the overwhelming majority and carry no marking. Only the
	 * accounts built by a sign in provider are marked, and only those
	 * are allowed to set a password without producing the old one. An
	 * unmarked account is therefore treated as having a password, which
	 * is the safe way round: the cost of being wrong is one extra step
	 * through Forgot password, not a way past a check.
	 *
	 * @since 1.7.0
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_password_is_chosen( $user_id ) {

		$source = (string) get_user_meta( (int) $user_id, 'kaamase_password_source', true );

		return ! in_array( $source, array( 'google', 'apple' ), true );
	}
}

if ( ! function_exists( 'kaamase_password_min' ) ) {
	/**
	 * The shortest password allowed.
	 *
	 * Eight, the same as the registration form. One rule, one number,
	 * one sentence to translate.
	 *
	 * @since 1.7.0
	 * @return int
	 */
	function kaamase_password_min() {
		return 8;
	}
}


/* ==========================================================================
   2. SETTING IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_password_set' ) ) {
	/**
	 * Give an account a password.
	 *
	 * @since 1.7.0
	 * @param int    $user_id  Whose account.
	 * @param string $password The new password.
	 * @param string $current  The old one, where there is one.
	 * @return true|WP_Error
	 */
	function kaamase_password_set( $user_id, $password, $current = '' ) {

		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'kaamase_no_user',
				__( 'That account no longer exists.', 'kaamase-core' ),
				array( 'status' => 404 )
			);
		}

		if ( strlen( $password ) < kaamase_password_min() ) {
			return new WP_Error(
				'kaamase_password_short',
				sprintf(
					/* translators: %d: the smallest number of characters allowed */
					__( 'Your password needs at least %d characters.', 'kaamase-core' ),
					kaamase_password_min()
				),
				array( 'status' => 400 )
			);
		}

		if ( kaamase_password_is_chosen( $user_id ) ) {

			if ( '' === $current ) {
				return new WP_Error(
					'kaamase_password_current_needed',
					__( 'Enter your current password to change it. If you cannot remember it, use the forgotten password link instead.', 'kaamase-core' ),
					array( 'status' => 400 )
				);
			}

			if ( ! wp_check_password( $current, $user->user_pass, $user_id ) ) {
				return new WP_Error(
					'kaamase_password_current_wrong',
					__( 'That is not your current password.', 'kaamase-core' ),
					array( 'status' => 403 )
				);
			}
		}

		/*
		 * wp_set_password, not wp_update_user. See the note at the top:
		 * the other one would revoke this person's own session as they
		 * used it.
		 */
		wp_set_password( $password, $user_id );

		update_user_meta( $user_id, 'kaamase_password_source', 'chosen' );
		update_user_meta( $user_id, 'kaamase_password_set_at', time() );

		kaamase_password_tell( $user_id );

		/**
		 * Fires after somebody sets a password from inside their account.
		 *
		 * @since 1.7.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_password_chosen', $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_password_tell' ) ) {
	/**
	 * Tell the account a password was set on it.
	 *
	 * By email rather than through the notification layer, and on
	 * purpose. This is the one message whose whole value is that it
	 * reaches somewhere the person holding the phone does not control.
	 * A notification on a stolen handset is read by whoever stole it.
	 *
	 * @since 1.7.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_password_tell( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user || empty( $user->user_email ) ) {
			return;
		}

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		$lines = array(
			__( 'A password was just set on your Kaam Ase account.', 'kaamase-core' ),
			'',
			__( 'If that was you, there is nothing to do. You can now sign in with your email and this password on the website and in the app, as well as any other way you already use.', 'kaamase-core' ),
			'',
			__( 'If it was not you, change it straight away using the forgotten password link on the sign in screen. That also signs out every phone currently signed in to your account.', 'kaamase-core' ),
			'',
			(string) wp_login_url(),
			'',
			sprintf(
				/* translators: %s: site name */
				__( '%s never asks a worker for money.', 'kaamase-core' ),
				$site
			),
		);

		wp_mail(
			$user->user_email,
			__( 'A password was set on your Kaam Ase account', 'kaamase-core' ),
			implode( "\n", $lines )
		);
	}
}


/* ==========================================================================
   3. FOR THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_password_state' ) ) {
	/**
	 * Whether this account already has a password of its own.
	 *
	 * The app asks so it can word the screen: Set a password for
	 * somebody who has never had one, Change your password for somebody
	 * who has, and ask for the old one only in the second case.
	 *
	 * @since 1.7.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_password_state() {

		$user_id = get_current_user_id();

		return new WP_REST_Response(
			array(
				'has_password'  => kaamase_password_is_chosen( $user_id ),
				'minimum'       => kaamase_password_min(),
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_password_set' ) ) {
	/**
	 * Set or change the caller's password.
	 *
	 * @since 1.7.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_password_set( $request ) {

		$user_id = get_current_user_id();

		/*
		 * Throttled per caller. Without it this is somewhere to guess a
		 * current password at speed, which is the one thing on this
		 * endpoint worth guessing.
		 */
		if ( ! kaamase_throttle_ok( kaamase_client_key( 'password' ), 10, HOUR_IN_SECONDS ) ) {
			return kaamase_rest_error( kaamase_throttle_error() );
		}

		$done = kaamase_password_set(
			$user_id,
			(string) $request->get_param( 'password' ),
			(string) $request->get_param( 'current' )
		);

		if ( is_wp_error( $done ) ) {
			return kaamase_rest_error( $done );
		}

		/*
		 * The token the app is holding still works: wp_set_password does
		 * not fire the hook that revokes them. Said plainly so the app
		 * does not sign somebody out to be helpful.
		 */
		return new WP_REST_Response(
			array(
				'has_password'  => true,
				'still_signed_in' => true,
				'message'       => __( 'Password set. You can sign in with your email and this password from now on.', 'kaamase-core' ),
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_password_routes' ) ) {
	/**
	 * Register the endpoints.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	function kaamase_password_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = 'kaamase_rest_require_login';

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/password',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'kaamase_rest_password_state',
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => 'kaamase_rest_password_set',
					'permission_callback' => $auth,
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_password_routes' );


/* ==========================================================================
   4. FOR THE WEBSITE
   ========================================================================== */

if ( ! function_exists( 'kaamase_password_dashboard' ) ) {
	/**
	 * The card that offers it, on the account screen.
	 *
	 * Only shown to somebody who has no password of their own. Somebody
	 * who chose one already has the ordinary WordPress screen for
	 * changing it, and a second way to do the same thing is a second
	 * thing to keep working.
	 *
	 * @since 1.7.0
	 * @param int    $user_id User ID.
	 * @param int    $profile Profile post ID.
	 * @param string $type    worker or employer.
	 * @return void
	 */
	function kaamase_password_dashboard( $user_id, $profile, $type ) {

		unset( $profile, $type );

		if ( kaamase_password_is_chosen( $user_id ) ) {
			return;
		}
		?>
		<div class="ka-card ka-stack ka-mt-6">

			<h3><?php esc_html_e( 'Set a password', 'kaamase-core' ); ?></h3>

			<p>
				<?php
				esc_html_e(
					'You signed up without one, so at the moment you can only get in the way you joined. Set a password and you can also sign in with your email address, on this website and on any phone.',
					'kaamase-core'
				);
				?>
			</p>

			<form class="ka-form ka-stack" method="post" action="">
				<?php wp_nonce_field( 'kaamase_set_password', 'kaamase_password_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="set_password">

				<div class="ka-field">
					<label class="ka-label" for="ka-new-password">
						<?php esc_html_e( 'New password', 'kaamase-core' ); ?>
					</label>
					<input class="ka-input" type="password" id="ka-new-password" name="kaamase_new_password"
						required minlength="<?php echo esc_attr( kaamase_password_min() ); ?>"
						autocomplete="new-password">
					<p class="ka-hint">
						<?php
						printf(
							/* translators: %d: the smallest number of characters allowed */
							esc_html__( 'At least %d characters.', 'kaamase-core' ),
							absint( kaamase_password_min() )
						);
						?>
					</p>
				</div>

				<button class="ka-btn ka-btn--action" type="submit">
					<?php esc_html_e( 'Set my password', 'kaamase-core' ); ?>
				</button>
			</form>

		</div>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_password_dashboard', 20, 3 );

if ( ! function_exists( 'kaamase_password_handle_form' ) ) {
	/**
	 * Handle the form.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	function kaamase_password_handle_form() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'set_password' !== $_POST['kaamase_action'] ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		check_admin_referer( 'kaamase_set_password', 'kaamase_password_nonce' );

		$user_id = get_current_user_id();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$new = isset( $_POST['kaamase_new_password'] ) ? (string) wp_unslash( $_POST['kaamase_new_password'] ) : '';

		$done = kaamase_password_set( $user_id, $new );

		/*
		 * Signed back in on purpose.
		 *
		 * Changing the stored password invalidates the cookie that was
		 * built from the old one, so without this the person is thrown
		 * out to the sign in screen at the exact moment they succeeded,
		 * which reads as the thing having failed.
		 */
		if ( ! is_wp_error( $done ) ) {
			wp_set_auth_cookie( $user_id, true );
			wp_set_current_user( $user_id );
		}

		set_transient(
			'kaamase_password_said_' . $user_id,
			is_wp_error( $done )
				? $done->get_error_message()
				: __( 'Password set. You can now sign in with your email address as well.', 'kaamase-core' ),
			MINUTE_IN_SECONDS
		);

		wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_password_handle_form' );

if ( ! function_exists( 'kaamase_password_flash' ) ) {
	/**
	 * Say how it went, once.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	function kaamase_password_flash() {

		$key     = 'kaamase_password_said_' . get_current_user_id();
		$message = get_transient( $key );

		if ( ! $message ) {
			return;
		}

		delete_transient( $key );

		printf(
			'<div class="ka-notice ka-notice--info"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
add_action( 'kaamase_dashboard_prompts', 'kaamase_password_flash', 6 );
