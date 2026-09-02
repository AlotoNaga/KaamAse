<?php
/**
 * The API.
 *
 * Everything the Kaam Ase phone app talks to, under kaamase/v1.
 *
 * The rule this file follows
 * --------------------------
 * No endpoint here decides anything. Every one of them reads the
 * request, hands it to the function that already owns that decision,
 * and shapes whatever comes back.
 *
 * Contact is the clearest example. The endpoint does not re-check
 * whether the caller is verified, whether the trade is protected,
 * whether they are blocked or whether they have quota left. It calls
 * kaamase_can_contact(), which is the same function the website calls,
 * and which is where that judgement is written down. If somebody later
 * decides an unverified employer may contact a cook after all, they
 * change one function and both front doors change together.
 *
 * The alternative, where the app has its own copy of the safety gate,
 * is how a platform ends up protecting women on the website and not in
 * the app, and not finding out for a year.
 *
 * Reading is public, writing is not
 * ---------------------------------
 * Browsing works signed out, because a worker deciding whether this app
 * is worth installing should be able to see that there is work on it
 * first. Every write, and every contact reveal, needs a token.
 *
 * @package KaamaseCore
 * @version 1.4.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;

define( 'KAAMASE_REST_NS', 'kaamase/v1' );


/* ==========================================================================
   1. HELPERS
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_require_login' ) ) {
	/**
	 * Permission callback for anything that writes.
	 *
	 * @since 1.3.0
	 * @return true|WP_Error
	 */
	function kaamase_rest_require_login() {

		if ( is_user_logged_in() ) {
			return true;
		}

		return new WP_Error(
			'kaamase_signed_out',
			__( 'Sign in to do that.', 'kaamase-core' ),
			array( 'status' => 401 )
		);
	}
}

if ( ! function_exists( 'kaamase_rest_require_employer_browse' ) ) {
	/**
	 * Whether this caller may read the employer directory.
	 *
	 * The decision itself is kaamase_may_browse_employers(), which is
	 * the same function the website page calls. This only turns its
	 * answer into the shape a permission callback has to return, and
	 * separates the two refusals so the app can tell them apart: 401
	 * means sign in, 403 means signed in but not allowed yet.
	 *
	 * @since 1.4.1
	 * @return true|WP_Error
	 */
	function kaamase_rest_require_employer_browse() {

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'kaamase_signed_out',
				__( 'Sign in to see who is hiring.', 'kaamase-core' ),
				array( 'status' => 401 )
			);
		}

		if ( ! function_exists( 'kaamase_may_browse_employers' ) || ! kaamase_may_browse_employers() ) {
			return new WP_Error(
				'kaamase_needs_verified_email',
				__( 'Confirm your email to see who is hiring. We sent you a link when you registered.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_rest_error' ) ) {
	/**
	 * Turn a WP_Error into a response the app can act on.
	 *
	 * The app shows error.message directly to a worker, so every message
	 * that reaches here is already written for a person rather than for a
	 * log file. messages carries the full list when a form failed several
	 * checks at once.
	 *
	 * @since 1.3.0
	 * @param WP_Error $error Error.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_error( $error ) {

		$data   = $error->get_error_data();
		$status = isset( $data['status'] ) ? absint( $data['status'] ) : 400;

		return new WP_REST_Response(
			array(
				'code'     => $error->get_error_code(),
				'message'  => $error->get_error_message(),
				'messages' => isset( $data['messages'] ) ? array_values( (array) $data['messages'] ) : array( $error->get_error_message() ),
			),
			$status
		);
	}
}

if ( ! function_exists( 'kaamase_rest_list_response' ) ) {
	/**
	 * A paginated list.
	 *
	 * has_more rather than a page count, because the app scrolls rather
	 * than paginates and a total is an extra COUNT query on every request.
	 *
	 * @since 1.3.0
	 * @param array $items Shaped items.
	 * @param int   $total Total found.
	 * @param int   $page  Current page.
	 * @param int   $per   Per page.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_list_response( $items, $total, $page, $per ) {

		return new WP_REST_Response(
			array(
				'items'    => array_values( $items ),
				'total'    => (int) $total,
				'page'     => (int) $page,
				'has_more' => ( $page * $per ) < $total,
			),
			200
		);
	}
}


/* ==========================================================================
   2. ROUTES
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_rest_routes' ) ) {
	/**
	 * Register every route.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_register_rest_routes() {

		$open = '__return_true';
		$auth = 'kaamase_rest_require_login';

		/* ---- Account ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/register',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_register',
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/login',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_login',
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/logout',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_logout',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/forgot',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_forgot',
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/resend',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_resend_verification',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_me',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/profile',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_save_profile',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/availability',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_set_availability',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/photo',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_upload_photo',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/delete',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_delete_account',
				'permission_callback' => $auth,
			)
		);

		/* ---- Reference data ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/reference',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_reference',
				'permission_callback' => $open,
			)
		);

		/* ---- Listings ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/jobs',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'kaamase_rest_jobs',
					'permission_callback' => $open,
				),
				array(
					'methods'             => 'POST',
					'callback'            => 'kaamase_rest_save_job',
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/jobs/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'kaamase_rest_job',
					'permission_callback' => $open,
				),
				array(
					'methods'             => 'POST',
					'callback'            => 'kaamase_rest_save_job',
					'permission_callback' => $auth,
				),
			)
		);

		/*
		 * Lookup by slug.
		 *
		 * A job link shared into a WhatsApp group is a WordPress
		 * permalink, so it carries a slug and not an ID. Without this the
		 * app can open every job except the ones people actually pass to
		 * each other, which is most of how work spreads here.
		 */
		register_rest_route(
			KAAMASE_REST_NS,
			'/jobs/slug/(?P<slug>[a-z0-9\-_]+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_by_slug',
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/workers/slug/(?P<slug>[a-z0-9\-_]+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_by_slug',
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/jobs/(?P<id>\d+)/(?P<action>fill|repost)',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_job_action',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/workers',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_workers',
				'permission_callback' => $open,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/workers/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_worker',
				'permission_callback' => $open,
			)
		);

		/*
		 * The phones this account is signed in on.
		 *
		 * The website has had this since the day tokens were added and
		 * the app never has, which is backwards: the person whose phone
		 * was stolen is holding a different phone, and the thing in
		 * their hand is far more likely to be the app than a browser.
		 */
		register_rest_route(
			KAAMASE_REST_NS,
			'/devices',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_devices',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/devices/revoke',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_revoke_device',
				'permission_callback' => $auth,
			)
		);

		/*
		 * The employer directory.
		 *
		 * The only listing on this API that is not open. Browsing
		 * workers and jobs signed out is deliberate; a directory of
		 * every business on the platform is not the same thing, and no
		 * employer agreed to that when they registered.
		 */
		register_rest_route(
			KAAMASE_REST_NS,
			'/employers',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_employers',
				'permission_callback' => 'kaamase_rest_require_employer_browse',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/employers/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_employer',
				'permission_callback' => 'kaamase_rest_require_employer_browse',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/teams',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'kaamase_rest_teams',
					'permission_callback' => $open,
				),
				array(
					'methods'             => 'POST',
					'callback'            => 'kaamase_rest_save_team',
					'permission_callback' => $auth,
				),
			)
		);

		/* ---- Contact ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/contact/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_contact',
				'permission_callback' => $auth,
			)
		);

		/* ---- Saved ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/saved',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_saved',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/saved/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_toggle_saved',
				'permission_callback' => $auth,
			)
		);

		/* ---- Hires and ratings ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/hires/questions',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_hire_questions',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/hires/answer',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_hire_answer',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/ratings/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'kaamase_rest_ratings',
					'permission_callback' => $open,
				),
				array(
					'methods'             => 'POST',
					'callback'            => 'kaamase_rest_submit_rating',
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/ratings/pending',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_pending_ratings',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/ratings/questions/(?P<about>worker|employer)',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_rating_questions',
				'permission_callback' => $open,
			)
		);

		/* ---- Reports ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/reports',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_report',
				'permission_callback' => $open,
			)
		);

		/* ---- Push ---- */

		register_rest_route(
			KAAMASE_REST_NS,
			'/push/register',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_push_register',
				'permission_callback' => $auth,
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_register_rest_routes' );


/* ==========================================================================
   3. ACCOUNT
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_register' ) ) {
	/**
	 * Create an account and return a token.
	 *
	 * Reuses kaamase_create_account(), so the app produces exactly the
	 * same thing the website does: a user, a matching draft profile, the
	 * verification email and the consent record. An account created
	 * through the app is not a second class account.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_register( $request ) {

		/*
		 * Registration has its own counter now.
		 *
		 * It used to share the login failure counter, and then call
		 * kaamase_login_succeeded() on success, which clears that
		 * counter. So every successful registration reset the limit
		 * meant to cap registrations, and the cap was only ever felt by
		 * somebody failing. Nobody wrote that on purpose; it is what
		 * happens when two different actions share one tally.
		 *
		 * Counted per caller, per hour, whether it succeeds or not,
		 * which is the only version that limits anything here.
		 */
		if ( ! kaamase_throttle_ok( kaamase_client_key( 'register' ), 5, HOUR_IN_SECONDS ) ) {
			return kaamase_rest_error( kaamase_throttle_error() );
		}

		if ( kaamase_login_blocked() ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_rate_limited', __( 'Too many attempts from this connection. Please try again later.', 'kaamase-core' ), array( 'status' => 429 ) )
			);
		}

		$type     = sanitize_key( (string) $request->get_param( 'type' ) );
		$name     = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$email    = sanitize_email( (string) $request->get_param( 'email' ) );
		$phone_in = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$district = kaamase_match_district( (string) $request->get_param( 'district' ) );
		$trade    = kaamase_match_trade( (string) $request->get_param( 'trade' ) );
		$password = (string) $request->get_param( 'password' );
		$agreed   = (bool) $request->get_param( 'agreed' );

		$phone  = kaamase_sanitize_phone( $phone_in );
		$errors = array();

		if ( ! in_array( $type, array( 'worker', 'employer' ), true ) ) {
			$errors[] = __( 'Choose whether you are looking for work or looking for workers.', 'kaamase-core' );
		}

		if ( '' === trim( $name ) ) {
			$errors[] = __( 'Please enter your name.', 'kaamase-core' );
		}

		if ( ! is_email( $email ) ) {
			$errors[] = __( 'Please enter a working email address.', 'kaamase-core' );
		}

		if ( '' === $district ) {
			$errors[] = __( 'Please choose your district.', 'kaamase-core' );
		}

		if ( 'worker' === $type && '' === $trade ) {
			$errors[] = __( 'Please choose the work you do.', 'kaamase-core' );
		}

		if ( strlen( $password ) < 8 ) {
			$errors[] = __( 'Your password needs at least 8 characters.', 'kaamase-core' );
		}

		if ( ! $agreed ) {
			$errors[] = __( 'Please agree to the terms and the privacy notice.', 'kaamase-core' );
		}

		if ( '' === $phone_in ) {
			$errors[] = __( 'Please enter your phone number.', 'kaamase-core' );
		} elseif ( '' === $phone ) {
			$errors[] = __( 'That phone number does not look right. It should be 10 digits starting with 6, 7, 8 or 9.', 'kaamase-core' );
		}

		if ( is_email( $email ) && email_exists( $email ) ) {
			// Same deliberately vague wording as the website. See registration.php.
			$errors[] = __( 'We could not create an account with those details. If you already have one, try signing in.', 'kaamase-core' );
		}

		if ( ! empty( $errors ) ) {
			kaamase_login_failed();
			return kaamase_rest_error(
				new WP_Error( 'kaamase_invalid_registration', $errors[0], array( 'messages' => $errors, 'status' => 400 ) )
			);
		}

		$user_id = kaamase_create_account(
			array(
				'type'     => $type,
				'name'     => $name,
				'email'    => $email,
				'phone'    => $phone,
				'district' => $district,
				'trade'    => $trade,
				'password' => $password,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return kaamase_rest_error( $user_id );
		}

		/*
		 * The login counter is cleared, the registration one is not.
		 *
		 * Somebody who registers has plainly not been guessing at a
		 * password, so clearing the login failure count is right.
		 * Clearing the registration count would undo the limit on the
		 * action that just happened.
		 */
		kaamase_login_succeeded();

		$token = kaamase_issue_token( $user_id, (string) $request->get_param( 'device' ) );

		wp_set_current_user( $user_id );

		return new WP_REST_Response(
			array(
				'token'      => $token['token'],
				'expires_at' => $token['expires_at'],
				'me'         => kaamase_shape_me( $user_id ),
			),
			201
		);
	}
}

if ( ! function_exists( 'kaamase_rest_login' ) ) {
	/**
	 * Exchange an email and password for a token.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_login( $request ) {

		if ( kaamase_login_blocked() ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_rate_limited', __( 'Too many sign in attempts. Please wait a few minutes and try again.', 'kaamase-core' ), array( 'status' => 429 ) )
			);
		}

		$email    = sanitize_text_field( (string) $request->get_param( 'email' ) );
		$password = (string) $request->get_param( 'password' );

		$user = wp_authenticate( $email, $password );

		if ( is_wp_error( $user ) ) {

			kaamase_login_failed();

			/*
			 * One message for every failure. Telling somebody the email
			 * was right but the password was wrong confirms that a given
			 * person holds an account here, which is exactly what a
			 * stranger checking a list of numbers wants to learn.
			 */
			return kaamase_rest_error(
				new WP_Error(
					'kaamase_bad_credentials',
					__( 'That email address and password do not match. Check both and try again.', 'kaamase-core' ),
					array( 'status' => 401 )
				)
			);
		}

		kaamase_login_succeeded();

		$token = kaamase_issue_token( $user->ID, (string) $request->get_param( 'device' ) );

		wp_set_current_user( $user->ID );

		// Brings a dormant profile back, exactly as signing in on the site does.
		do_action( 'wp_login', $user->user_login, $user );

		return new WP_REST_Response(
			array(
				'token'      => $token['token'],
				'expires_at' => $token['expires_at'],
				'me'         => kaamase_shape_me( $user->ID ),
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_logout' ) ) {
	/**
	 * Revoke the token this request arrived with.
	 *
	 * @since 1.3.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_logout() {

		kaamase_revoke_token( get_current_user_id(), kaamase_bearer_token() );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_forgot' ) ) {
	/**
	 * Send a password reset email.
	 *
	 * Always answers the same way. A different answer for a known and an
	 * unknown address turns this into a way to test whether somebody is
	 * registered.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_forgot( $request ) {

		$email = sanitize_email( (string) $request->get_param( 'email' ) );

		/*
		 * Throttled, and the throttle is silent.
		 *
		 * This route was open with nothing counting it, so anybody could
		 * make it send reset emails to a real person over and over. The
		 * account is never compromised by that, which is why it reads as
		 * harmless, but the person on the other end gets a mailbox full
		 * of password resets they did not ask for and every one of them
		 * says their account is being attacked.
		 *
		 * Counted against the caller and against the address, because
		 * either alone leaves a way through, and refused with the same
		 * cheerful sentence as success so this still cannot be used to
		 * find out who has an account.
		 */
		if ( ! kaamase_throttle_scoped( 'forgot', $email, 5, HOUR_IN_SECONDS, 3 ) ) {

			return new WP_REST_Response(
				array(
					'ok'      => true,
					'message' => __( 'If that address has an account, we have sent it a link to set a new password.', 'kaamase-core' ),
				),
				200
			);
		}

		$user = $email ? get_user_by( 'email', $email ) : null;

		if ( $user ) {
			retrieve_password( $user->user_login );
		}

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => __( 'If that address has an account, we have sent it a link to set a new password.', 'kaamase-core' ),
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_resend_verification' ) ) {
	/**
	 * Send the verification email again.
	 *
	 * @since 1.3.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_resend_verification() {

		$user_id = get_current_user_id();
		$key     = 'kaamase_resend_' . $user_id;

		if ( get_transient( $key ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_wait', __( 'We just sent one. Please wait a few minutes before asking again.', 'kaamase-core' ), array( 'status' => 429 ) )
			);
		}

		set_transient( $key, 1, 10 * MINUTE_IN_SECONDS );
		kaamase_send_verification( $user_id );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_me' ) ) {
	/**
	 * The signed in account.
	 *
	 * @since 1.3.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_me() {
		return new WP_REST_Response( kaamase_shape_me( get_current_user_id() ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_save_profile' ) ) {
	/**
	 * Update the caller's profile.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_save_profile( $request ) {

		$user_id = get_current_user_id();

		/*
		 * Which side is being edited, asked for rather than assumed.
		 *
		 * An account can hold a worker profile and an employer profile
		 * at once. This always saved kaamase_profile_id, which is
		 * whichever came first, so the second one could be created and
		 * then never filled in: an employer who added the working side
		 * got a worker profile that stayed a draft forever, because
		 * every save went to their employer profile instead.
		 *
		 * No side named means the primary one, so anything already
		 * written against this endpoint keeps working unchanged.
		 */
		$side = sanitize_key( (string) $request->get_param( 'side' ) );

		if ( in_array( $side, array( 'worker', 'employer' ), true ) ) {
			$profile_id = (int) kaamase_get_user_profile( $user_id, 'kaamase_' . $side );
		} else {
			$profile_id = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );
		}

		if ( ! $profile_id ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_no_profile', __( 'Your profile is missing. Open the app again and we will rebuild it.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		$result = kaamase_save_profile( $profile_id, (array) $request->get_json_params(), $user_id );

		if ( is_wp_error( $result ) ) {
			return kaamase_rest_error( $result );
		}

		return new WP_REST_Response( kaamase_shape_me( $user_id ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_set_availability' ) ) {
	/**
	 * Set the caller's availability.
	 *
	 * The single most used write on the platform, so it is its own tiny
	 * endpoint rather than a full profile save. A worker changing this
	 * on a bus should not be re-uploading their whole profile to do it.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_set_availability( $request ) {

		$user_id    = get_current_user_id();
		$profile_id = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );
		$value      = sanitize_key( (string) $request->get_param( 'availability' ) );

		if ( ! in_array( $value, array( 'live', 'busy', 'leave' ), true ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_bad_value', __( 'That is not an availability we know about.', 'kaamase-core' ), array( 'status' => 400 ) )
			);
		}

		if ( ! $profile_id || ! current_user_can( 'edit_post', $profile_id ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_forbidden', __( 'That profile is not yours.', 'kaamase-core' ), array( 'status' => 403 ) )
			);
		}

		kaamase_save_field( $profile_id, 'availability', $value );

		return new WP_REST_Response( array( 'availability' => $value ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_upload_photo' ) ) {
	/**
	 * Upload a profile photograph.
	 *
	 * Goes through media_handle_upload, which is the path the theme hooks
	 * to strip camera metadata. A worker uploading a selfie taken at home
	 * would otherwise publish their home coordinates inside the file, and
	 * an app upload must not be the one route that skips that.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_upload_photo( $request ) {

		$user_id = get_current_user_id();
		$files   = $request->get_file_params();

		$target = absint( $request->get_param( 'target' ) );
		$target = $target ? $target : (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		/*
		 * The target has to be one of this account's own profiles, said
		 * as two separate rules rather than left to edit_post.
		 *
		 * edit_post answers whether somebody may edit a post, which is
		 * the right question for an editor and the wrong one for an
		 * upload endpoint: it happens to be true for everything a
		 * capable role can touch. So the moment a role gains a broader
		 * capability, or a new post type is added, this route quietly
		 * accepts more than it was written for, and nothing here would
		 * have to change for that to happen.
		 *
		 * Naming the post types means the contract is readable and does
		 * not move when the capabilities do.
		 */
		$target_type = $target ? get_post_type( $target ) : '';

		$allowed_targets = array( 'kaamase_worker', 'kaamase_employer', 'kaamase_gang' );

		if ( ! $target
			|| ! in_array( $target_type, $allowed_targets, true )
			|| (int) get_post_field( 'post_author', $target ) !== $user_id
			|| ! current_user_can( 'edit_post', $target ) ) {

			return kaamase_rest_error(
				new WP_Error( 'kaamase_forbidden', __( 'That profile is not yours.', 'kaamase-core' ), array( 'status' => 403 ) )
			);
		}

		if ( empty( $files['photo'] ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_no_file', __( 'No photo arrived. Please try again.', 'kaamase-core' ), array( 'status' => 400 ) )
			);
		}

		$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
		$type    = isset( $files['photo']['type'] ) ? (string) $files['photo']['type'] : '';

		if ( ! in_array( $type, $allowed, true ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_bad_file', __( 'Photos need to be a JPG, PNG or WEBP.', 'kaamase-core' ), array( 'status' => 400 ) )
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$_FILES['photo'] = $files['photo'];

		$attachment_id = media_handle_upload( 'photo', $target );

		if ( is_wp_error( $attachment_id ) ) {
			return kaamase_rest_error( $attachment_id );
		}

		$previous = get_post_thumbnail_id( $target );

		set_post_thumbnail( $target, $attachment_id );

		// Replace rather than accumulate.
		if ( $previous && (int) $previous !== (int) $attachment_id ) {
			wp_delete_attachment( $previous, true );
		}

		return new WP_REST_Response( array( 'image' => kaamase_shape_image( $target ) ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_delete_account' ) ) {
	/**
	 * Erase the caller's account.
	 *
	 * Same routine as the website's delete button and the privacy tools.
	 * The right to erasure has to be exercisable from the device somebody
	 * actually owns, which for most people here is the phone.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_delete_account( $request ) {

		$typed = strtoupper( trim( (string) $request->get_param( 'confirm' ) ) );

		if ( 'DELETE' !== $typed ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_confirm', __( 'Type DELETE to confirm you want to remove your account.', 'kaamase-core' ), array( 'status' => 400 ) )
			);
		}

		$user = wp_get_current_user();

		kaamase_erase_personal_data( $user->user_email );
		kaamase_revoke_token( $user->ID );

		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user->ID );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
}


/* ==========================================================================
   4. REFERENCE DATA

   Trades, districts and languages in one request. The app caches this
   and stops asking, so the pickers work with no connection.
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_reference' ) ) {
	/**
	 * Every fixed list the app needs.
	 *
	 * @since 1.3.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_reference() {

		$cached = get_transient( 'kaamase_rest_reference' );

		if ( is_array( $cached ) ) {
			return new WP_REST_Response( $cached, 200 );
		}

		$trades = array();

		foreach ( kaamase_trade_choices() as $group => $items ) {
			foreach ( $items as $slug => $name ) {
				$trades[] = array(
					'slug'  => $slug,
					'name'  => $name,
					'group' => $group,
				);
			}
		}

		$districts = array();

		foreach ( kaamase_districts() as $slug => $district ) {
			$districts[] = array(
				'slug' => $slug,
				'name' => $district['name'],
			);
		}

		$languages = array();
		$terms     = get_terms(
			array(
				'taxonomy'   => 'kaamase_language',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$languages[] = array(
					'slug' => $term->slug,
					'name' => $term->name,
				);
			}
		}

		$data = array(
			'trades'      => $trades,
			'districts'   => $districts,
			'languages'   => $languages,
			'wage_floors' => array(
				'day'   => kaamase_wage_floor( 'day' ),
				'month' => kaamase_wage_floor( 'month' ),
				'hour'  => kaamase_wage_floor( 'hour' ),
				'job'   => kaamase_wage_floor( 'job' ),
			),
			'emergency'   => kaamase_emergency_numbers(),
			'pages'       => array(
				'privacy' => kaamase_page_url( 'privacy' ),
				'terms'   => kaamase_page_url( 'terms' ),
				'safety'  => kaamase_page_url( 'safety' ),
				'help'    => kaamase_page_url( 'help' ),
			),
			'help_phone'  => (string) get_option( 'kaamase_help_phone', '' ),
		);

		set_transient( 'kaamase_rest_reference', $data, 6 * HOUR_IN_SECONDS );

		return new WP_REST_Response( $data, 200 );
	}
}

/**
 * Drop the cached reference data when a term changes.
 *
 * @since 1.3.0
 * @return void
 */
function kaamase_clear_reference_cache() {
	delete_transient( 'kaamase_rest_reference' );
}
add_action( 'created_term', 'kaamase_clear_reference_cache' );
add_action( 'edited_term', 'kaamase_clear_reference_cache' );
add_action( 'delete_term', 'kaamase_clear_reference_cache' );


/* ==========================================================================
   5. LISTINGS
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_query_args' ) ) {
	/**
	 * Build query arguments from the request.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request   Request.
	 * @param string          $post_type Post type.
	 * @return array
	 */
	function kaamase_rest_query_args( $request, $post_type ) {

		$page = max( 1, absint( $request->get_param( 'page' ) ) );
		$per  = absint( $request->get_param( 'per_page' ) );
		$per  = $per > 0 ? min( $per, 50 ) : 20;

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per,
			'paged'          => $page,
		);

		$tax = array();

		$trade = kaamase_match_trade( (string) $request->get_param( 'trade' ) );

		if ( $trade ) {
			$tax[] = array(
				'taxonomy' => 'kaamase_trade',
				'field'    => 'slug',
				'terms'    => $trade,
			);
		}

		$district = kaamase_match_district( (string) $request->get_param( 'district' ) );

		if ( $district ) {
			$tax[] = array(
				'taxonomy' => 'kaamase_district',
				'field'    => 'slug',
				'terms'    => $district,
			);
		}

		if ( ! empty( $tax ) ) {
			$tax['relation'] = 'AND';
			$args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$search = sanitize_text_field( (string) $request->get_param( 'search' ) );

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$meta = array();

		if ( 'kaamase_job' === $post_type ) {

			// Never return a job that has closed.
			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'     => KAAMASE_META_PREFIX . 'expires',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => KAAMASE_META_PREFIX . 'expires',
					'value'   => time(),
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			);

			if ( $request->get_param( 'urgent' ) ) {
				$meta[] = array(
					'key'   => KAAMASE_META_PREFIX . 'urgent',
					'value' => '1',
				);
			}
		} elseif ( $request->get_param( 'available' ) ) {

			/*
			 * A worker who has never touched the availability control
			 * counts as available, because that is what the rest of the
			 * platform already says about them.
			 *
			 * The field defaults to live, so kaamase_field returns live
			 * when the row is missing and every screen shows them as
			 * available now. This filter asked for the row itself, so it
			 * excluded exactly those people: the app showed an employer
			 * an empty list of workers while the same workers were
			 * visible, and labelled available, everywhere else.
			 *
			 * NOT EXISTS brings the default into the query so the two
			 * agree.
			 */
			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'   => KAAMASE_META_PREFIX . 'availability',
					'value' => 'live',
				),
				array(
					'key'     => KAAMASE_META_PREFIX . 'availability',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( $request->get_param( 'vouched' ) ) {
			$meta[] = array(
				'key'     => KAAMASE_META_PREFIX . 'vouched_by',
				'compare' => '!=',
				'value'   => '',
			);
		}

		$max = absint( $request->get_param( 'max_rate' ) );

		if ( $max ) {

			$key = 'kaamase_job' === $post_type
				? KAAMASE_META_PREFIX . 'pay_amount'
				: KAAMASE_META_PREFIX . 'day_rate';

			// A worker with no rate set is included, never filtered out.
			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'     => $key,
					'value'   => $max,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( ! empty( $meta ) ) {

			if ( count( $meta ) > 1 ) {
				$meta['relation'] = 'AND';
			}

			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		/*
		 * Ordering. The same argument as queries.php: workers are not
		 * ranked by rating, and cheapest is never the default.
		 *
		 * The ordering itself comes from kaamase_number_sort_args() in
		 * queries.php rather than being written out again here. This
		 * file used to carry its own copy, and the copies drifted: the
		 * website was fixed and the app was left with the original
		 * fault, where setting meta_key made WP_Query INNER JOIN the
		 * meta table and every worker who had not yet filled in a
		 * profile disappeared from the results rather than sorting
		 * last. On a site where nobody had been rated yet, the app's
		 * Best rated list came back empty.
		 *
		 * One rule, two callers. The parameter names and the response
		 * are unchanged, so no app release is involved in this.
		 */
		$sort = sanitize_key( (string) $request->get_param( 'sort' ) );

		if ( 'kaamase_job' === $post_type ) {

			switch ( $sort ) {
				case 'highest':
					$args = array_merge( $args, kaamase_number_sort_args( 'pay_amount', 'DESC' ) );
					break;
				case 'urgent':
					$args = array_merge( $args, kaamase_number_sort_args( 'urgent', 'DESC' ) );
					break;
				default:
					$args['orderby'] = 'date';
					$args['order']   = 'DESC';
			}

			return $args;
		}

		switch ( $sort ) {
			case 'rated':
				$args = array_merge( $args, kaamase_number_sort_args( 'rating_average', 'DESC' ) );
				break;
			case 'experience':
				$args = array_merge( $args, kaamase_number_sort_args( 'years_experience', 'DESC' ) );
				break;
			case 'cheapest':
				// A rate of zero is a blank field, not the cheapest worker.
				$args = array_merge( $args, kaamase_number_sort_args( 'day_rate', 'ASC', true ) );
				break;
			case 'newest':
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
				break;
			default:
				// Available first, then the daily rotation. See queries.php.
				$args['orderby'] = 'kaamase_rotate';
		}

		return $args;
	}
}

if ( ! function_exists( 'kaamase_rest_jobs' ) ) {
	/**
	 * List jobs.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_jobs( $request ) {

		$args  = kaamase_rest_query_args( $request, 'kaamase_job' );
		$query = new WP_Query( $args );

		$items = array_map(
			static function ( $post ) {
				return kaamase_shape_job( $post, false );
			},
			(array) $query->posts
		);

		return kaamase_rest_list_response(
			array_filter( $items ),
			$query->found_posts,
			$args['paged'],
			$args['posts_per_page']
		);
	}
}

if ( ! function_exists( 'kaamase_rest_job' ) ) {
	/**
	 * One job.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_job( $request ) {

		$id   = absint( $request['id'] );
		$post = get_post( $id );

		if ( ! $post || 'kaamase_job' !== $post->post_type ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_not_found', __( 'That job no longer exists.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		/*
		 * A closed job 404s for everybody except its owner, which is what
		 * makes the app's "this job is filled" screen honest rather than
		 * showing live-looking work that went three weeks ago.
		 */
		if ( ! kaamase_job_is_open( $id ) && ! current_user_can( 'edit_post', $id ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_job_closed', __( 'This job has closed. It was probably filled.', 'kaamase-core' ), array( 'status' => 410 ) )
			);
		}

		return new WP_REST_Response( kaamase_shape_job( $post, true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_save_job' ) ) {
	/**
	 * Post or edit a job.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_save_job( $request ) {

		$job_id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$body   = (array) $request->get_json_params();

		$result = kaamase_save_job( $body, get_current_user_id(), $job_id );

		if ( is_wp_error( $result ) ) {
			return kaamase_rest_error( $result );
		}

		return new WP_REST_Response( kaamase_shape_job( $result, true ), $job_id ? 200 : 201 );
	}
}

if ( ! function_exists( 'kaamase_rest_job_action' ) ) {
	/**
	 * Close a filled job, or reopen a closed one.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_job_action( $request ) {

		$id     = absint( $request['id'] );
		$action = sanitize_key( (string) $request['action'] );

		if ( ! current_user_can( 'edit_post', $id ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_forbidden', __( 'That job is not yours.', 'kaamase-core' ), array( 'status' => 403 ) )
			);
		}

		if ( 'fill' === $action ) {

			kaamase_save_field( $id, 'job_status', 'filled' );
			wp_update_post( array( 'ID' => $id, 'post_status' => 'kaamase_closed' ) );

			/** This action is documented in includes/hires.php */
			do_action( 'kaamase_job_filled', $id );

		} else {

			delete_post_meta( $id, KAAMASE_META_PREFIX . 'expires' );
			kaamase_save_field( $id, 'job_status', 'open' );

			wp_update_post(
				array(
					'ID'            => $id,
					'post_status'   => 'publish',
					'post_date'     => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', 1 ),
				)
			);
		}

		return new WP_REST_Response( kaamase_shape_job( $id, true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_workers' ) ) {
	/**
	 * List workers.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_workers( $request ) {

		$args  = kaamase_rest_query_args( $request, 'kaamase_worker' );
		$query = new WP_Query( $args );

		$items = array_map(
			static function ( $post ) {
				return kaamase_shape_worker( $post, false );
			},
			(array) $query->posts
		);

		return kaamase_rest_list_response(
			array_filter( $items ),
			$query->found_posts,
			$args['paged'],
			$args['posts_per_page']
		);
	}
}

if ( ! function_exists( 'kaamase_rest_device_list' ) ) {
	/**
	 * Every phone this account is signed in on, marking the one asking.
	 *
	 * is_current is the whole reason this is not just kaamase_user_devices()
	 * handed straight out. A list of four identical rows called "Kaam Ase
	 * app" is useless for the job it exists to do: somebody has to be able
	 * to tell which one is in their hand before they dare sign the others
	 * out, and only the server can say, because the phone knows its token
	 * but not which stored entry that token became.
	 *
	 * The handle compared here is a hash of the stored hash, so nothing
	 * that could be replayed as a credential is computed or returned.
	 *
	 * @since 1.4.2
	 * @param int $user_id Account.
	 * @return array[]
	 */
	function kaamase_rest_device_list( $user_id ) {

		$devices = kaamase_user_devices( $user_id );
		$bearer  = kaamase_bearer_token();

		$current = '';

		if ( $bearer ) {
			$current = kaamase_device_id( array( 'hash' => kaamase_hash_token( $bearer ) ) );
		}

		foreach ( $devices as $at => $device ) {
			$devices[ $at ]['is_current'] = ( '' !== $current && $device['id'] === $current );
		}

		return $devices;
	}
}

if ( ! function_exists( 'kaamase_rest_devices' ) ) {
	/**
	 * List the phones this account is signed in on.
	 *
	 * @since 1.4.2
	 * @return WP_REST_Response
	 */
	function kaamase_rest_devices() {

		return new WP_REST_Response(
			array( 'items' => kaamase_rest_device_list( get_current_user_id() ) ),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_revoke_device' ) ) {
	/**
	 * Sign a phone out.
	 *
	 * device takes a handle from the list, or one of two words:
	 *
	 *   others  everything except the phone asking. What somebody whose
	 *           phone was stolen actually wants, and the only one of the
	 *           three that leaves them still signed in.
	 *   all     everything, this phone included. Matches the website's
	 *           Sign out everywhere.
	 *
	 * Rate limited, because this is the one endpoint where a stolen
	 * phone can act against the person it was stolen from. It cannot be
	 * prevented outright -- whoever holds the phone holds a valid token,
	 * and they could already read everything the account can see -- but
	 * it can be kept to a handful of attempts an hour, which is more
	 * than any honest person needs and not enough to grind through
	 * anything.
	 *
	 * @since 1.4.2
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_revoke_device( $request ) {

		$user_id = get_current_user_id();
		$device  = sanitize_key( (string) $request->get_param( 'device' ) );

		if ( '' === $device ) {
			return kaamase_rest_error(
				new WP_Error(
					'kaamase_no_device',
					__( 'Say which phone to sign out.', 'kaamase-core' ),
					array( 'status' => 400 )
				)
			);
		}

		$key = 'devices_' . $user_id;

		if ( kaamase_rate_value( $key, 0 ) >= 20 ) {
			return kaamase_rest_error(
				new WP_Error(
					'kaamase_limit',
					__( 'That is a lot of sign outs at once. Try again in an hour, or use the website.', 'kaamase-core' ),
					array( 'status' => 429 )
				)
			);
		}

		kaamase_rate_bump( $key, HOUR_IN_SECONDS );

		if ( 'all' === $device ) {

			kaamase_revoke_token( $user_id );

		} elseif ( 'others' === $device ) {

			/*
			 * Everything except this phone, done by revoking each other
			 * handle rather than by revoking all and re-issuing. Re-issuing
			 * would hand back a new token the app would have to notice and
			 * store, and a phone that missed that response would be signed
			 * out by the very action meant to keep it signed in.
			 */
			foreach ( kaamase_rest_device_list( $user_id ) as $entry ) {

				if ( empty( $entry['is_current'] ) ) {
					kaamase_revoke_device( $user_id, (string) $entry['id'] );
				}
			}
		} else {

			kaamase_revoke_device( $user_id, $device );
		}

		/*
		 * The refreshed list comes back with the answer, so the screen
		 * somebody is staring at during a theft updates from the reply
		 * rather than from a second request that might not arrive.
		 */
		return new WP_REST_Response(
			array(
				'ok'    => true,
				'items' => kaamase_rest_device_list( $user_id ),
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_employers' ) ) {
	/**
	 * The employer directory.
	 *
	 * Filters and sorting are built by the same function the website
	 * page uses, so the two cannot drift apart. In particular the sort
	 * goes through kaamase_number_sort_args(), which LEFT JOINs: an
	 * employer who has hired nobody yet still appears.
	 *
	 * @since 1.4.1
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_employers( $request ) {

		$args = kaamase_employer_query_args(
			array(
				'district' => (string) $request->get_param( 'district' ),
				'type'     => (string) $request->get_param( 'type' ),
				'sort'     => (string) $request->get_param( 'sort' ),
				/*
				 * Passed through raw. kaamase_employer_query_args() does
				 * the clamping, and doing it twice is how the two paths
				 * end up disagreeing: absint() here would turn page -5
				 * into page 5 before the clamp ever saw it.
				 */
				'paged'    => $request->get_param( 'page' ),
			)
		);

		$query = new WP_Query( $args );

		$items = array_map(
			static function ( $post ) {
				return kaamase_shape_employer( $post, false );
			},
			(array) $query->posts
		);

		return kaamase_rest_list_response(
			array_filter( $items ),
			$query->found_posts,
			$args['paged'],
			$args['posts_per_page']
		);
	}
}

if ( ! function_exists( 'kaamase_rest_employer' ) ) {
	/**
	 * One employer.
	 *
	 * @since 1.4.1
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_employer( $request ) {

		$id   = absint( $request['id'] );
		$post = get_post( $id );

		if ( ! $post || 'kaamase_employer' !== $post->post_type ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_not_found', __( 'That employer no longer exists.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $id ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_not_found', __( 'That employer is not available.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		return new WP_REST_Response( kaamase_shape_employer( $post, true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_worker' ) ) {
	/**
	 * One worker or team.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_worker( $request ) {

		$id   = absint( $request['id'] );
		$post = get_post( $id );

		if ( ! $post || ! in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_not_found', __( 'That profile no longer exists.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $id ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_not_found', __( 'That profile is not available.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		return new WP_REST_Response( kaamase_shape_worker( $post, true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_teams' ) ) {
	/**
	 * List teams.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_teams( $request ) {

		$args  = kaamase_rest_query_args( $request, 'kaamase_gang' );
		$query = new WP_Query( $args );

		$items = array_map(
			static function ( $post ) {
				return kaamase_shape_worker( $post, false );
			},
			(array) $query->posts
		);

		return kaamase_rest_list_response(
			array_filter( $items ),
			$query->found_posts,
			$args['paged'],
			$args['posts_per_page']
		);
	}
}

if ( ! function_exists( 'kaamase_rest_save_team' ) ) {
	/**
	 * Create or update the caller's team.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_save_team( $request ) {

		$user_id = get_current_user_id();
		$team_id = kaamase_get_user_team( $user_id );

		$result = kaamase_save_team( (array) $request->get_json_params(), $user_id, $team_id );

		if ( is_wp_error( $result ) ) {
			return kaamase_rest_error( $result );
		}

		return new WP_REST_Response( kaamase_shape_worker( $result, true ), $team_id ? 200 : 201 );
	}
}

if ( ! function_exists( 'kaamase_rest_by_slug' ) ) {
	/**
	 * Resolve a permalink slug to the record behind it.
	 *
	 * Serves both jobs and profiles; which one is decided by the route
	 * the request came in on, so a job slug cannot be used to fish for a
	 * worker and the reverse.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_by_slug( $request ) {

		$slug  = sanitize_title( (string) $request['slug'] );
		$route = (string) $request->get_route();

		$types = ( false !== strpos( $route, '/jobs/' ) )
			? array( 'kaamase_job' )
			: array( 'kaamase_worker', 'kaamase_gang' );

		$found = get_posts(
			array(
				'name'             => $slug,
				'post_type'        => $types,
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		if ( empty( $found ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_not_found', __( 'That link does not lead anywhere any more.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		$post = $found[0];

		if ( 'kaamase_job' === $post->post_type ) {

			if ( ! kaamase_job_is_open( $post->ID ) && ! current_user_can( 'edit_post', $post->ID ) ) {
				return kaamase_rest_error(
					new WP_Error( 'kaamase_job_closed', __( 'This job has closed. It was probably filled.', 'kaamase-core' ), array( 'status' => 410 ) )
				);
			}

			return new WP_REST_Response( kaamase_shape_job( $post, true ), 200 );
		}

		return new WP_REST_Response( kaamase_shape_worker( $post, true ), 200 );
	}
}


/* ==========================================================================
   6. CONTACT

   The endpoint that matters most, and the one that decides nothing for
   itself.
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_contact' ) ) {
	/**
	 * Reveal a contact number, subject to every rule the website applies.
	 *
	 * A refusal is a 403 carrying the same sentence a person would read
	 * on the site, plus a machine readable code so the app can offer the
	 * right button next to it. A wall you do not understand is a wall you
	 * cannot get past.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_contact( $request ) {

		$id      = absint( $request['id'] );
		$user_id = get_current_user_id();

		$allowed = kaamase_can_contact( $id );

		if ( is_wp_error( $allowed ) ) {

			return new WP_REST_Response(
				array(
					'code'       => $allowed->get_error_code(),
					'message'    => $allowed->get_error_message(),
					'messages'   => array( $allowed->get_error_message() ),
					'quota_left' => kaamase_contact_quota_left( $user_id ),
				),
				403
			);
		}

		// Charging quota and writing the log are the same act as on the site.
		if ( ! kaamase_user_owns( $id, $user_id ) ) {
			kaamase_contact_quota_use( $user_id, $id );
			kaamase_log_contact( $id, $user_id );
		}

		$channel = kaamase_contact_channel( $id );
		$number  = (string) $channel['number'];

		return new WP_REST_Response(
			array(
				'name'       => get_the_title( $id ),
				'number'     => $number,
				'display'    => kaamase_format_phone( $number ),
				'masked'     => (bool) $channel['masked'],
				'label'      => (string) $channel['label'],
				'whatsapp'   => $number ? 'https://wa.me/91' . $number : '',
				'tel'        => $number ? 'tel:+91' . $number : '',
				'quota_left' => kaamase_contact_quota_left( $user_id ),
			),
			200
		);
	}
}


/* ==========================================================================
   7. SAVED
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_saved' ) ) {
	/**
	 * The caller's saved list and their rehire list.
	 *
	 * @since 1.3.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_saved() {

		$user_id = get_current_user_id();

		$saved = array();

		foreach ( kaamase_get_saved( $user_id ) as $post_id ) {

			$post = get_post( $post_id );

			if ( ! $post ) {
				continue;
			}

			$shaped = ( 'kaamase_job' === $post->post_type )
				? kaamase_shape_job( $post, false )
				: kaamase_shape_worker( $post, false );

			if ( $shaped ) {
				$saved[] = $shaped;
			}
		}

		$worked = array();

		foreach ( kaamase_worked_with( $user_id ) as $entry ) {

			$post = get_post( $entry['post_id'] );

			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$shaped = ( 'kaamase_employer' === $post->post_type )
				? kaamase_shape_employer( $post, false )
				: kaamase_shape_worker( $post, false );

			if ( $shaped ) {
				$shaped['worked_at'] = (int) $entry['time'];
				$worked[]            = $shaped;
			}
		}

		return new WP_REST_Response(
			array(
				'saved'        => $saved,
				'worked_with'  => $worked,
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_toggle_saved' ) ) {
	/**
	 * Save or unsave something.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_toggle_saved( $request ) {

		$id      = absint( $request['id'] );
		$user_id = get_current_user_id();
		$post    = get_post( $id );

		if ( ! $post || ! in_array( $post->post_type, kaamase_post_types(), true ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_not_found', __( 'That no longer exists.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		$saved = kaamase_get_saved( $user_id );

		if ( in_array( $id, $saved, true ) ) {
			$saved = array_values( array_diff( $saved, array( $id ) ) );
			$now   = false;
		} else {
			array_unshift( $saved, $id );
			$saved = array_slice( array_unique( $saved ), 0, kaamase_saved_limit() );
			$now   = true;
		}

		update_user_meta( $user_id, 'kaamase_saved', $saved );

		return new WP_REST_Response( array( 'saved' => $now ), 200 );
	}
}


/* ==========================================================================
   8. HIRES AND RATINGS
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_hire_questions' ) ) {
	/**
	 * Which hires the caller has not confirmed yet.
	 *
	 * @since 1.3.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_hire_questions() {

		$out = array();

		foreach ( kaamase_due_hire_questions( get_current_user_id() ) as $entry ) {

			$shaped = kaamase_shape_worker( $entry['post_id'], false );

			if ( $shaped ) {
				$shaped['looked_up_at'] = (int) $entry['time'];
				$out[]                  = $shaped;
			}
		}

		return new WP_REST_Response( array( 'items' => $out ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_hire_answer' ) ) {
	/**
	 * Answer a hire question.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_hire_answer( $request ) {

		/*
		 * The id, under whichever name it arrives.
		 *
		 * This read profile_id only. An id sent under any other name
		 * became absint( null ), which is zero, which fails the check
		 * below with "there is no open question about that person" —
		 * a message about the record when the record was never looked
		 * for. Apple rejected the app over these buttons, and the
		 * server was answering a question nobody had asked it.
		 *
		 * Accepting the obvious spellings costs nothing and means one
		 * side renaming a field can no longer break the other.
		 */
		$post_id = 0;

		foreach ( array( 'profile_id', 'worker_id', 'post_id', 'id' ) as $key ) {

			$post_id = absint( $request->get_param( $key ) );

			if ( $post_id ) {
				break;
			}
		}

		$hired   = (bool) $request->get_param( 'hired' );
		$user_id = get_current_user_id();

		$questions = kaamase_get_hire_questions( $user_id );
		$entry     = isset( $questions[ $post_id ] ) ? (array) $questions[ $post_id ] : array();

		/*
		 * Answered against the same list the person was shown.
		 *
		 * This checked a stored record while the screen was built from
		 * kaamase_due_hire_questions, and the two disagreed: the app
		 * offered three people to answer about and every answer came
		 * back saying no such question exists. Nothing the person could
		 * do would clear it, and to anybody tapping the buttons the app
		 * was simply broken.
		 *
		 * Falling back to the list itself means the two can no longer
		 * come apart, whichever of them is missing an entry. If somebody
		 * was asked, they can answer.
		 */
		if ( $post_id && ! $entry && function_exists( 'kaamase_due_hire_questions' ) ) {

			foreach ( kaamase_due_hire_questions( $user_id ) as $due ) {

				if ( (int) ( $due['post_id'] ?? 0 ) !== $post_id ) {
					continue;
				}

				$entry = array(
					'time'  => (int) ( $due['time'] ?? time() ),
					'state' => 'pending',
				);

				break;
			}
		}

		if ( ! $post_id || ! $entry ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_no_question', __( 'There is no open question about that person.', 'kaamase-core' ), array( 'status' => 404 ) )
			);
		}

		/*
		 * Already answered, so say yes rather than no.
		 *
		 * Somebody whose first tap errored taps again, and a reviewer
		 * certainly will. Refusing the second one turns a fixed problem
		 * back into a broken button.
		 */
		$state = isset( $entry['state'] ) ? (string) $entry['state'] : 'pending';

		if ( 'pending' !== $state ) {
			return new WP_REST_Response(
				array(
					'ok'       => true,
					'can_rate' => 'hired' === $state,
				),
				200
			);
		}

		$entry['state']    = $hired ? 'hired' : 'no';
		$entry['answered'] = time();

		$questions[ $post_id ] = $entry;

		kaamase_save_hire_questions( $user_id, $questions );

		if ( $hired ) {
			kaamase_record_hire( $post_id, $user_id );
		}

		return new WP_REST_Response( array( 'ok' => true, 'can_rate' => $hired ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_rating_questions' ) ) {
	/**
	 * The questions asked about a worker or an employer.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_rating_questions( $request ) {

		$about = sanitize_key( (string) $request['about'] );
		$out   = array();

		foreach ( kaamase_rating_questions( $about ) as $key => $question ) {
			$out[] = array(
				'key'   => $key,
				'label' => $question['label'],
			);
		}

		return new WP_REST_Response( array( 'about' => $about, 'questions' => $out ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_ratings' ) ) {
	/**
	 * Published ratings on a profile.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_ratings( $request ) {

		$id = absint( $request['id'] );

		if ( ! kaamase_rating_is_public( $id ) ) {
			return new WP_REST_Response(
				array(
					'items'   => array(),
					'public'  => false,
					'message' => __( 'Not enough ratings yet to show a score. We wait until three people have worked with somebody, so that one bad day does not decide everything.', 'kaamase-core' ),
				),
				200
			);
		}

		$comments = get_comments(
			array(
				'post_id' => $id,
				'type'    => 'kaamase_rating',
				'status'  => 'approve',
				'number'  => 20,
			)
		);

		return new WP_REST_Response(
			array(
				'items'  => array_map( 'kaamase_shape_rating_entry', $comments ),
				'public' => true,
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_submit_rating' ) ) {
	/**
	 * Leave a rating.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_submit_rating( $request ) {

		$result = kaamase_submit_rating(
			absint( $request['id'] ),
			(array) $request->get_param( 'answers' ),
			(string) $request->get_param( 'note' ),
			get_current_user_id()
		);

		if ( is_wp_error( $result ) ) {
			return kaamase_rest_error( $result );
		}

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => __( 'Thank you. It appears once the other side has written theirs.', 'kaamase-core' ),
			),
			201
		);
	}
}

if ( ! function_exists( 'kaamase_rest_pending_ratings' ) ) {
	/**
	 * People the caller has worked with and not yet rated.
	 *
	 * @since 1.3.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_pending_ratings() {

		$out = array();

		foreach ( kaamase_pending_ratings( get_current_user_id() ) as $post_id ) {

			$post = get_post( $post_id );

			if ( ! $post ) {
				continue;
			}

			$shaped = ( 'kaamase_employer' === $post->post_type )
				? kaamase_shape_employer( $post, false )
				: kaamase_shape_worker( $post, false );

			if ( $shaped ) {
				$out[] = $shaped;
			}
		}

		return new WP_REST_Response( array( 'items' => $out ), 200 );
	}
}


/* ==========================================================================
   9. REPORTS
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_report' ) ) {
	/**
	 * File a report or a wage complaint.
	 *
	 * Signing in is not required, for the reason set out in reports.php:
	 * requiring it suppresses exactly the reports you most need to see.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_report( $request ) {

		if ( ! kaamase_report_rate_ok() ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_rate_limited', __( 'You have sent several already. If it is urgent, call us instead.', 'kaamase-core' ), array( 'status' => 429 ) )
			);
		}

		$result = kaamase_submit_report( (array) $request->get_json_params(), get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			return kaamase_rest_error( $result );
		}

		return new WP_REST_Response(
			array(
				'reference' => $result['reference'],
				'message'   => __( 'We have it. Somebody reads it within two working days.', 'kaamase-core' ),
			),
			201
		);
	}
}


/* ==========================================================================
   10. PUSH
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_push_register' ) ) {
	/**
	 * Store an Expo push token against the account.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_push_register( $request ) {

		$token   = sanitize_text_field( (string) $request->get_param( 'push_token' ) );
		$user_id = get_current_user_id();

		if ( '' === $token || ! preg_match( '/^Expo(nent)?PushToken\[[^\]]+\]$/', $token ) ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_bad_token', __( 'That is not a push token we recognise.', 'kaamase-core' ), array( 'status' => 400 ) )
			);
		}

		$tokens = kaamase_meta_array( get_user_meta( $user_id, 'kaamase_push_tokens', true ) );

		if ( ! in_array( $token, $tokens, true ) ) {
			$tokens[] = $token;
		}

		// One person, a handful of devices.
		update_user_meta( $user_id, 'kaamase_push_tokens', array_slice( array_values( array_unique( $tokens ) ), -5 ) );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
}