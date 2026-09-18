<?php
/**
 * Connecting and disconnecting Apple and Google from inside an account,
 * and mending an address that was typed wrong.
 *
 * The two gaps this closes
 * -----------------------
 * Both come from the same place: a sign in provider could only ever be
 * joined to an account at the moment the account was made.
 *
 * One. Somebody registers with a password using raj@gmail.com, and later
 * taps Sign in with Google on a new phone. If Google hands over the same
 * address the two meet and nothing is wrong. If it hands over a different
 * one — a work address, a second account, or nothing at all, which is what
 * Apple does on every authorisation after the first — they get a second
 * account instead, with an empty profile and none of their saved work. The
 * platform never told them, because from the platform's side nothing went
 * wrong.
 *
 * Two. Somebody finishing an Apple sign in types an address that already
 * belongs to their own older account. They are refused, correctly and
 * vaguely, and there is nowhere to go from there.
 *
 * Connecting a provider to an account that already exists answers both. It
 * is the same act in each case: prove you hold the Apple or Google account,
 * while already signed in here, and the two are joined.
 *
 * Why the token is verified in full
 * ---------------------------------
 * Being signed in proves who the caller is. It proves nothing about which
 * Apple account they are claiming. So connect runs the identical
 * verification the sign in route runs — signature, issuer, audience,
 * expiry — with no shortcut for a caller who happens to hold a session.
 * A route that took a provider id on trust from a signed in caller would
 * let anybody attach anybody else's identity to their own account, and
 * then sign in as them.
 *
 * Why nothing here touches the email address
 * ------------------------------------------
 * Connecting writes one thing: the provider's account id. It never reads
 * the address out of the token, never writes it to the account, and never
 * marks the account confirmed. The account's address was settled when the
 * account was made and is not this route's business. Marking an account
 * confirmed because a provider token arrived is exactly the hole that had
 * to be closed in apple-signin.php, and it is not reopened here.
 *
 * Why you cannot remove your last way in
 * --------------------------------------
 * Disconnecting the only thing you can sign in with locks you out of your
 * own account, and the app cannot know that has happened until the person
 * is already outside. So the count is kept on this side: a password the
 * person chose, plus every provider currently connected. The last one will
 * not come off. It is counted across all of them rather than against the
 * password alone, so the guard still holds when a third provider is added.
 *
 * Why changing an address does not sign anybody out
 * -------------------------------------------------
 * wp_update_user() fires profile_update, and rest-auth.php listens for it.
 * It answers by revoking tokens only when the password moved, which it has
 * not here, so the session survives. WordPress's own notice to the previous
 * address is suppressed: on an unconfirmed account that address was very
 * probably a typing mistake, and may well belong to somebody else who
 * should not be told about a stranger's account.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.8.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE PROVIDERS, IN ONE PLACE

   Named once so the state route, the four connect and disconnect routes
   and the last way in guard all read the same definition. A third
   provider is a row here and nothing else.
   ========================================================================== */

if ( ! function_exists( 'kaamase_providers' ) ) {
	/**
	 * Every sign in provider the platform knows about.
	 *
	 * @since 1.8.0
	 * @return array<string,array> Keyed by the name the app uses.
	 */
	function kaamase_providers() {

		return array(
			'apple'  => array(
				'sub_meta'    => 'kaamase_apple_sub',
				'linked_meta' => 'kaamase_apple_linked_at',
				'extra_meta'  => array( 'kaamase_apple_private_email' ),
				'verify'      => 'kaamase_apple_verify',
				'attach'      => 'kaamase_apple_attach',
				'is_on'       => 'kaamase_apple_is_on',
			),
			'google' => array(
				'sub_meta'    => 'kaamase_google_sub',
				'linked_meta' => 'kaamase_google_linked_at',
				'extra_meta'  => array(),
				'verify'      => 'kaamase_google_verify',
				'attach'      => 'kaamase_google_attach',
				'is_on'       => 'kaamase_google_is_on',
			),
		);
	}
}

if ( ! function_exists( 'kaamase_provider' ) ) {
	/**
	 * One provider's definition, or nothing.
	 *
	 * @since 1.8.0
	 * @param string $name Provider name.
	 * @return array|null
	 */
	function kaamase_provider( $name ) {

		$all = kaamase_providers();

		return isset( $all[ $name ] ) ? $all[ $name ] : null;
	}
}

if ( ! function_exists( 'kaamase_provider_is_on' ) ) {
	/**
	 * Whether this provider is switched on for the site at all.
	 *
	 * Connecting needs it. Disconnecting deliberately does not: switching
	 * Apple off in wp-admin must not trap the accounts already holding it.
	 *
	 * @since 1.8.0
	 * @param string $name Provider name.
	 * @return bool
	 */
	function kaamase_provider_is_on( $name ) {

		$provider = kaamase_provider( $name );

		return $provider && function_exists( $provider['is_on'] ) && (bool) call_user_func( $provider['is_on'] );
	}
}

if ( ! function_exists( 'kaamase_provider_sub' ) ) {
	/**
	 * The provider account id stored against a user, if any.
	 *
	 * @since 1.8.0
	 * @param int    $user_id User ID.
	 * @param string $name    Provider name.
	 * @return string Empty when nothing is connected.
	 */
	function kaamase_provider_sub( $user_id, $name ) {

		$provider = kaamase_provider( $name );

		if ( ! $provider ) {
			return '';
		}

		return (string) get_user_meta( (int) $user_id, $provider['sub_meta'], true );
	}
}

if ( ! function_exists( 'kaamase_provider_holder' ) ) {
	/**
	 * Which account holds this provider id, if anybody does.
	 *
	 * Matched on the provider id alone. The sign in routes fall back to
	 * the email address when the id is unknown, and that fallback must
	 * not happen here: this answer decides whether to refuse a connect as
	 * belonging to somebody else, and an address is not a claim to an
	 * identity.
	 *
	 * @since 1.8.0
	 * @param string $name Provider name.
	 * @param string $sub  The provider's account id.
	 * @return int User ID, or 0.
	 */
	function kaamase_provider_holder( $name, $sub ) {

		$provider = kaamase_provider( $name );

		if ( ! $provider || '' === $sub ) {
			return 0;
		}

		$found = get_users(
			array(
				'meta_key'   => $provider['sub_meta'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $sub,                  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			)
		);

		return empty( $found ) ? 0 : (int) $found[0];
	}
}

if ( ! function_exists( 'kaamase_ways_in' ) ) {
	/**
	 * How many separate ways this account can be signed in to.
	 *
	 * A password the person chose counts as one. A password generated for
	 * them by a provider does not, because they have never seen it.
	 *
	 * @since 1.8.0
	 * @param int $user_id User ID.
	 * @return int
	 */
	function kaamase_ways_in( $user_id ) {

		$user_id = (int) $user_id;
		$count   = kaamase_password_is_chosen( $user_id ) ? 1 : 0;

		foreach ( kaamase_providers() as $name => $provider ) {
			if ( '' !== kaamase_provider_sub( $user_id, $name ) ) {
				$count++;
			}
		}

		return $count;
	}
}

if ( ! function_exists( 'kaamase_provider_can_disconnect' ) ) {
	/**
	 * Whether this one can come off without locking the person out.
	 *
	 * The single guard the four routes and the state route share.
	 *
	 * @since 1.8.0
	 * @param int    $user_id User ID.
	 * @param string $name    Provider name.
	 * @return bool
	 */
	function kaamase_provider_can_disconnect( $user_id, $name ) {

		if ( '' === kaamase_provider_sub( $user_id, $name ) ) {
			return false;
		}

		return kaamase_ways_in( $user_id ) > 1;
	}
}


/* ==========================================================================
   2. WHAT THE ACCOUNT SCREEN DRAWS
   ========================================================================== */

if ( ! function_exists( 'kaamase_sign_in_state' ) ) {
	/**
	 * Everything the account screen needs, in one answer.
	 *
	 * Counts and flags only. No sentences: the app writes those, and the
	 * server has no business deciding how they are worded.
	 *
	 * No provider address is stored or returned. Knowing an Apple account
	 * is connected is the whole of what the screen needs, and keeping the
	 * address would mean holding a second copy of something Apple already
	 * refuses to send twice.
	 *
	 * @since 1.8.0
	 * @param int $user_id User ID.
	 * @return array
	 */
	function kaamase_sign_in_state( $user_id ) {

		$user_id   = (int) $user_id;
		$providers = array();

		foreach ( kaamase_providers() as $name => $provider ) {

			$sub    = kaamase_provider_sub( $user_id, $name );
			$linked = (int) get_user_meta( $user_id, $provider['linked_meta'], true );

			$row = array(
				'connected'      => ( '' !== $sub ),
				'available'      => kaamase_provider_is_on( $name ),
				'since'          => ( '' !== $sub && $linked ) ? $linked : null,
				'can_disconnect' => kaamase_provider_can_disconnect( $user_id, $name ),
			);

			/*
			 * Apple only, and only while connected. Google has no
			 * equivalent, so the key is absent there rather than sent as
			 * a false that would read as "Google told us it is not a
			 * forwarding address".
			 */
			if ( 'apple' === $name && '' !== $sub ) {
				$row['private_email'] = (bool) get_user_meta( $user_id, 'kaamase_apple_private_email', true );
			}

			$providers[ $name ] = $row;
		}

		return array(
			'has_password' => kaamase_password_is_chosen( $user_id ),
			'minimum'      => kaamase_password_min(),
			'ways_in'      => kaamase_ways_in( $user_id ),
			'providers'    => $providers,
		);
	}
}

if ( ! function_exists( 'kaamase_rest_sign_in_state' ) ) {
	/**
	 * GET /me/sign-in
	 *
	 * @since 1.8.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_sign_in_state() {

		return new WP_REST_Response( kaamase_sign_in_state( get_current_user_id() ), 200 );
	}
}


/* ==========================================================================
   3. CONNECTING ONE
   ========================================================================== */

if ( ! function_exists( 'kaamase_provider_connect' ) ) {
	/**
	 * Join a provider account to the account already signed in.
	 *
	 * @since 1.8.0
	 * @param int    $user_id User ID.
	 * @param string $name    Provider name.
	 * @param string $jwt     The provider's identity token.
	 * @return true|WP_Error
	 */
	function kaamase_provider_connect( $user_id, $name, $jwt ) {

		$user_id  = (int) $user_id;
		$provider = kaamase_provider( $name );

		if ( ! $provider ) {
			return new WP_Error(
				'kaamase_unknown_provider',
				__( 'That sign in method is not one this site offers.', 'kaamase-core' ),
				array( 'status' => 404 )
			);
		}

		if ( ! kaamase_provider_is_on( $name ) ) {
			return new WP_Error(
				'kaamase_provider_off',
				__( 'That sign in method is not switched on for this site.', 'kaamase-core' ),
				array( 'status' => 503 )
			);
		}

		/*
		 * The same verification the sign in route runs, on purpose. A
		 * session says who is asking; only the token says which provider
		 * account they hold.
		 */
		$claims = call_user_func( $provider['verify'], (string) $jwt );

		if ( is_wp_error( $claims ) ) {
			return $claims;
		}

		$holder = kaamase_provider_holder( $name, $claims['sub'] );

		/*
		 * Already on this account. A second tap, a retry after a dropped
		 * reply, or two phones at once — none of those is a mistake, and
		 * answering an error would have the app show a failure for a state
		 * that is exactly what was asked for.
		 */
		if ( $holder === $user_id ) {
			return true;
		}

		if ( $holder ) {
			return new WP_Error(
				'kaamase_provider_taken',
				__( 'That account is already connected to a different Kaam Ase account. Sign in with it instead, or disconnect it there first.', 'kaamase-core' ),
				array( 'status' => 409 )
			);
		}

		/*
		 * attach() writes the provider id and nothing else that matters
		 * here. The address in the token is not read, the account's own
		 * address is not touched, and the account is not marked confirmed
		 * — a provider token proves an identity, never an address on some
		 * other record.
		 */
		call_user_func( $provider['attach'], $user_id, $claims );

		return true;
	}
}


/* ==========================================================================
   4. TAKING ONE OFF
   ========================================================================== */

if ( ! function_exists( 'kaamase_provider_disconnect' ) ) {
	/**
	 * Remove a provider from the account signed in.
	 *
	 * @since 1.8.0
	 * @param int    $user_id User ID.
	 * @param string $name    Provider name.
	 * @return true|WP_Error
	 */
	function kaamase_provider_disconnect( $user_id, $name ) {

		$user_id  = (int) $user_id;
		$provider = kaamase_provider( $name );

		if ( ! $provider ) {
			return new WP_Error(
				'kaamase_unknown_provider',
				__( 'That sign in method is not one this site offers.', 'kaamase-core' ),
				array( 'status' => 404 )
			);
		}

		/*
		 * Nothing connected is the state being asked for, so it is not an
		 * error, for the same reason connecting twice is not.
		 */
		if ( '' === kaamase_provider_sub( $user_id, $name ) ) {
			return true;
		}

		if ( ! kaamase_provider_can_disconnect( $user_id, $name ) ) {
			return new WP_Error(
				'kaamase_last_way_in',
				__( 'This is the only way you can sign in. Set a password first, then you can disconnect it.', 'kaamase-core' ),
				array( 'status' => 409 )
			);
		}

		delete_user_meta( $user_id, $provider['sub_meta'] );
		delete_user_meta( $user_id, $provider['linked_meta'] );

		foreach ( $provider['extra_meta'] as $meta_key ) {
			delete_user_meta( $user_id, $meta_key );
		}

		/**
		 * Fires when a provider is taken off an account.
		 *
		 * @since 1.8.0
		 * @param int    $user_id User ID.
		 * @param string $name    Provider name.
		 */
		do_action( 'kaamase_provider_disconnected', $user_id, $name );

		return true;
	}
}


/* ==========================================================================
   5. THE ROUTE HANDLERS FOR BOTH
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_provider_connect' ) ) {
	/**
	 * POST /me/{provider}/connect
	 *
	 * @since 1.8.0
	 * @param string          $name    Provider name.
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_provider_connect( $name, $request ) {

		/*
		 * Throttled because verifying a token can send this server out to
		 * the provider for a signing key it has not cached.
		 */
		if ( ! kaamase_throttle_ok( kaamase_client_key( 'provider' ), 20, HOUR_IN_SECONDS ) ) {
			return kaamase_rest_error( kaamase_throttle_error() );
		}

		$user_id = get_current_user_id();
		$done    = kaamase_provider_connect( $user_id, $name, (string) $request->get_param( 'id_token' ) );

		if ( is_wp_error( $done ) ) {
			return kaamase_rest_error( $done );
		}

		return new WP_REST_Response( kaamase_sign_in_state( $user_id ), 200 );
	}
}

if ( ! function_exists( 'kaamase_rest_provider_disconnect' ) ) {
	/**
	 * POST /me/{provider}/disconnect
	 *
	 * @since 1.8.0
	 * @param string $name Provider name.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_provider_disconnect( $name ) {

		$user_id = get_current_user_id();
		$done    = kaamase_provider_disconnect( $user_id, $name );

		if ( is_wp_error( $done ) ) {
			return kaamase_rest_error( $done );
		}

		return new WP_REST_Response( kaamase_sign_in_state( $user_id ), 200 );
	}
}


/* ==========================================================================
   6. MENDING AN ADDRESS THAT WAS TYPED WRONG

   Only while it is unconfirmed. A confirmed address is somebody's proven
   property and moving it is a different job, with its own checks on both
   ends. This one exists for the person who mistyped and is now stuck
   behind a link they will never receive.
   ========================================================================== */

if ( ! function_exists( 'kaamase_email_change' ) ) {
	/**
	 * Replace an unconfirmed address and send the link again.
	 *
	 * @since 1.8.0
	 * @param int    $user_id User ID.
	 * @param string $raw     The address as typed.
	 * @return true|WP_Error
	 */
	function kaamase_email_change( $user_id, $raw ) {

		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'kaamase_no_user',
				__( 'That account no longer exists.', 'kaamase-core' ),
				array( 'status' => 404 )
			);
		}

		if ( kaamase_user_is_verified( $user_id ) ) {
			return new WP_Error(
				'kaamase_email_confirmed',
				__( 'Your email address is already confirmed and cannot be changed here yet.', 'kaamase-core' ),
				array( 'status' => 409 )
			);
		}

		$raw   = trim( (string) $raw );
		$email = sanitize_email( $raw );

		if ( '' === $raw ) {
			return new WP_Error(
				'kaamase_invalid_registration',
				__( 'Please enter your email address.', 'kaamase-core' ),
				array( 'status' => 400 )
			);
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'kaamase_invalid_registration',
				__( 'Please enter a working email address.', 'kaamase-core' ),
				array( 'status' => 400 )
			);
		}

		/*
		 * Vague on purpose, exactly as the registration form is. A reply
		 * that says plainly whether an address has an account turns a
		 * route any signed in person can call into a way to find out who
		 * is on the platform.
		 */
		$owner = email_exists( $email );

		if ( $owner && (int) $owner !== $user_id ) {
			return new WP_Error(
				'kaamase_invalid_registration',
				__( 'We could not change your address to that one. If it is already yours, sign in with it instead.', 'kaamase-core' ),
				array( 'status' => 400 )
			);
		}

		/*
		 * WordPress tells the previous address that the address changed.
		 * Right for an account whose owner proved that address; wrong
		 * here, where it is unconfirmed, was very probably a typing
		 * mistake, and may belong to a stranger who should not be told
		 * anything about an account that is not theirs.
		 */
		add_filter( 'send_email_change_email', '__return_false', 99 );

		$done = wp_update_user(
			array(
				'ID'         => $user_id,
				'user_email' => $email,
			)
		);

		remove_filter( 'send_email_change_email', '__return_false', 99 );

		if ( is_wp_error( $done ) ) {

			/*
			 * wp_insert_user checks uniqueness itself, whatever the check
			 * above concluded, so two requests arriving together still
			 * cannot both get through. Its own wording says outright that
			 * the address is taken, so it is replaced with the vague one.
			 */
			if ( 'existing_user_email' === $done->get_error_code() ) {
				return new WP_Error(
					'kaamase_invalid_registration',
					__( 'We could not change your address to that one. If it is already yours, sign in with it instead.', 'kaamase-core' ),
					array( 'status' => 400 )
				);
			}

			return $done;
		}

		/*
		 * A fresh link to the new address, and only to it. This also
		 * replaces the stored hash, so a link sent to the old address
		 * stops working the moment the address is corrected.
		 */
		kaamase_send_verification( $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_rest_email_change' ) ) {
	/**
	 * POST /me/email
	 *
	 * @since 1.8.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_email_change( $request ) {

		/*
		 * Tighter than the other routes here. Each success sends a mail,
		 * so an unthrottled one is somewhere to send mail from.
		 */
		if ( ! kaamase_throttle_ok( kaamase_client_key( 'email_change' ), 5, HOUR_IN_SECONDS ) ) {
			return kaamase_rest_error( kaamase_throttle_error() );
		}

		$user_id = get_current_user_id();
		$done    = kaamase_email_change( $user_id, (string) $request->get_param( 'email' ) );

		if ( is_wp_error( $done ) ) {
			return kaamase_rest_error( $done );
		}

		$user = get_userdata( $user_id );

		return new WP_REST_Response(
			array(
				'email'    => $user ? $user->user_email : '',
				'verified' => false,
				'message'  => __( 'Address changed. Tap the link in the email we have just sent to finish.', 'kaamase-core' ),
			),
			200
		);
	}
}


/* ==========================================================================
   7. THE ROUTES
   ========================================================================== */

if ( ! function_exists( 'kaamase_provider_routes' ) ) {
	/**
	 * Register everything in this file.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function kaamase_provider_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = 'kaamase_rest_require_login';

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/sign-in',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_sign_in_state',
				'permission_callback' => $auth,
			)
		);

		foreach ( array_keys( kaamase_providers() ) as $name ) {

			register_rest_route(
				KAAMASE_REST_NS,
				'/me/' . $name . '/connect',
				array(
					'methods'             => 'POST',
					'callback'            => function ( $request ) use ( $name ) {
						return kaamase_rest_provider_connect( $name, $request );
					},
					'permission_callback' => $auth,
				)
			);

			register_rest_route(
				KAAMASE_REST_NS,
				'/me/' . $name . '/disconnect',
				array(
					'methods'             => 'POST',
					'callback'            => function () use ( $name ) {
						return kaamase_rest_provider_disconnect( $name );
					},
					'permission_callback' => $auth,
				)
			);
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/email',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_email_change',
				'permission_callback' => $auth,
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_provider_routes' );
