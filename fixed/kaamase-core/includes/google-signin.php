<?php
/**
 * Signing in with Google.
 *
 * An additional door, not a replacement one. Email and password still
 * work exactly as they did, on the website and in the app, and nothing
 * in this file changes that path.
 *
 * Why bother
 * ----------
 * A worker registering on a phone in a village has to invent a
 * password, remember it, and type it again the next time. Most of them
 * do not, and the ones who cannot get back in do not send a support
 * email: they stop using the platform. A Google account is the one
 * credential nearly every Android phone here already has, and the
 * phones that reach this app are overwhelmingly Android.
 *
 * What Google actually tells us
 * -----------------------------
 * Google hands over a signed ID token. It carries a stable account id
 * (sub), an email address, and a flag saying whether Google has
 * confirmed that mailbox belongs to them. That last flag is the whole
 * basis of this file: it is the only reason an account can be matched
 * by email without a password.
 *
 * The signature is checked here, against Google's published keys, and
 * not by asking Google over the network on every sign in. Sending each
 * worker's sign in to a third party endpoint and waiting on the reply
 * is a round trip this connection cannot afford, and a dependency this
 * platform does not need. The same reasoning that kept a JWT plugin out
 * of rest-auth.php applies here.
 *
 * What this file refuses to do
 * ----------------------------
 * It never signs anybody in on an unverified email. If Google says
 * email_verified is false, the token is rejected outright, because the
 * matching rule below would otherwise hand over an existing account to
 * anybody who could put that address on a Google profile.
 *
 * It never creates an account without asking the questions the platform
 * needs. Google supplies a name and an email and nothing else. Whether
 * somebody is looking for work or looking for workers, which district
 * they are in, what trade they do, and a phone number are all still
 * asked, because a profile without them is a listing nobody can use.
 *
 * Two doors, one account
 * ----------------------
 * Somebody who registered with a password and later taps the Google
 * button on that same address is the same person, and Google has just
 * proved they hold the mailbox. They are signed into the account they
 * already have and both doors work from then on. Refusing would wall
 * somebody out of their own account on the strength of a password they
 * have forgotten, which is the exact problem this feature exists to
 * solve.
 *
 * Nothing here is on until a client ID is set. With the settings empty
 * the buttons do not render, the endpoints answer that the door is
 * closed, and the site behaves precisely as it did before this file
 * existed.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. SETTINGS

   Three client IDs because Google issues one per platform. All three
   are public values; they identify the application, they do not
   authorise anything. No client secret is needed anywhere in this file
   because every flow here verifies a signed token rather than
   exchanging an authorisation code.
   ========================================================================== */

if ( ! function_exists( 'kaamase_google_client_ids' ) ) {
	/**
	 * Every client ID that may appear in a token's audience.
	 *
	 * A token is accepted when its audience matches any one of these.
	 * The web ID signs the website in, the iOS and Android IDs sign the
	 * app in, and all three are the same account on this end.
	 *
	 * @since 1.6.0
	 * @return string[] Client IDs, empty values removed.
	 */
	function kaamase_google_client_ids() {

		$ids = array(
			trim( (string) get_option( 'kaamase_google_client_web', '' ) ),
			trim( (string) get_option( 'kaamase_google_client_ios', '' ) ),
			trim( (string) get_option( 'kaamase_google_client_android', '' ) ),
		);

		/**
		 * Filter the accepted Google client IDs.
		 *
		 * @since 1.6.0
		 * @param string[] $ids Client IDs.
		 */
		$ids = (array) apply_filters( 'kaamase_google_client_ids', $ids );

		return array_values( array_filter( array_map( 'strval', $ids ) ) );
	}
}

if ( ! function_exists( 'kaamase_google_web_client_id' ) ) {
	/**
	 * The client ID the website's button uses.
	 *
	 * @since 1.6.0
	 * @return string Empty when the website button is off.
	 */
	function kaamase_google_web_client_id() {
		return trim( (string) get_option( 'kaamase_google_client_web', '' ) );
	}
}

if ( ! function_exists( 'kaamase_google_is_on' ) ) {
	/**
	 * Whether Google sign in is configured at all.
	 *
	 * @since 1.6.0
	 * @return bool
	 */
	function kaamase_google_is_on() {
		return ! empty( kaamase_google_client_ids() ) && function_exists( 'openssl_verify' );
	}
}

if ( ! function_exists( 'kaamase_google_menu' ) ) {
	/**
	 * Add the settings screen.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_google_menu() {

		add_options_page(
			__( 'Google sign in', 'kaamase-core' ),
			__( 'Google sign in', 'kaamase-core' ),
			'manage_options',
			'kaamase-google',
			'kaamase_google_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_google_menu' );

if ( ! function_exists( 'kaamase_google_save_settings' ) ) {
	/**
	 * Save the client IDs.
	 *
	 * @since 1.6.0
	 * @return string A message to show, or an empty string.
	 */
	function kaamase_google_save_settings() {

		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['kaamase_google_settings'] ) ) {
			return '';
		}

		check_admin_referer( 'kaamase_google_settings' );

		foreach ( array( 'web', 'ios', 'android' ) as $platform ) {

			$raw = trim( (string) wp_unslash( $_POST[ 'kaamase_google_client_' . $platform ] ?? '' ) );

			/*
			 * Kept as typed apart from whitespace. A Google client ID is
			 * an opaque string ending in .apps.googleusercontent.com and
			 * inventing a stricter rule here would only reject a format
			 * Google changes later.
			 */
			update_option( 'kaamase_google_client_' . $platform, sanitize_text_field( $raw ) );
		}

		return __( 'Saved.', 'kaamase-core' );
	}
}

if ( ! function_exists( 'kaamase_google_page' ) ) {
	/**
	 * Draw the settings screen.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_google_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		$notice = kaamase_google_save_settings();
		$web    = kaamase_google_web_client_id();
		$ios    = trim( (string) get_option( 'kaamase_google_client_ios', '' ) );
		$droid  = trim( (string) get_option( 'kaamase_google_client_android', '' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Google sign in', 'kaamase-core' ); ?></h1>

			<?php if ( '' !== $notice ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<p>
				<?php
				esc_html_e(
					'Lets people sign in with a Google account instead of a password. Email and password keep working either way. Leave these empty and the Google buttons do not appear at all.',
					'kaamase-core'
				);
				?>
			</p>

			<?php if ( ! function_exists( 'openssl_verify' ) ) : ?>
				<div class="notice notice-error inline">
					<p>
						<?php
						esc_html_e(
							'This server has no OpenSSL support in PHP, so a Google token cannot be checked here. Google sign in stays off until your host enables it.',
							'kaamase-core'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="notice notice-info inline">
				<p>
					<strong><?php esc_html_e( 'Where these come from', 'kaamase-core' ); ?></strong><br>
					<?php
					esc_html_e(
						'Google Cloud Console, under Credentials. Make one OAuth client ID per platform. For the website client, add this exact address to Authorised redirect URIs:',
						'kaamase-core'
					);
					?>
					<br>
					<code><?php echo esc_html( admin_url( 'admin-post.php?action=kaamase_google' ) ); ?></code>
					<br>
					<?php
					esc_html_e(
						'and add your site address to Authorised JavaScript origins.',
						'kaamase-core'
					);
					?>
				</p>
			</div>

			<form method="post">
				<?php wp_nonce_field( 'kaamase_google_settings' ); ?>
				<input type="hidden" name="kaamase_google_settings" value="1">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="kaamase_google_client_web"><?php esc_html_e( 'Website client ID', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_google_client_web" id="kaamase_google_client_web" type="text"
								class="large-text code" value="<?php echo esc_attr( $web ); ?>"
								placeholder="000000000000-xxxxxxxx.apps.googleusercontent.com">
							<p class="description">
								<?php esc_html_e( 'Type: Web application. Empty means no button on the website.', 'kaamase-core' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="kaamase_google_client_ios"><?php esc_html_e( 'iPhone client ID', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_google_client_ios" id="kaamase_google_client_ios" type="text"
								class="large-text code" value="<?php echo esc_attr( $ios ); ?>"
								placeholder="000000000000-xxxxxxxx.apps.googleusercontent.com">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="kaamase_google_client_android"><?php esc_html_e( 'Android client ID', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_google_client_android" id="kaamase_google_client_android" type="text"
								class="large-text code" value="<?php echo esc_attr( $droid ); ?>"
								placeholder="000000000000-xxxxxxxx.apps.googleusercontent.com">
							<p class="description">
								<?php
								esc_html_e(
									'The app signs in with its own Android client, but the token it sends names the Web client ID as its audience. Fill both in.',
									'kaamase-core'
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}


/* ==========================================================================
   2. READING WHAT GOOGLE SENT

   A Google ID token is three base64url parts separated by dots: a
   header naming which key signed it, the claims, and an RSA signature
   over the first two.

   Checking it means fetching Google's public keys, rebuilding the one
   named in the header into something OpenSSL will accept, and verifying
   the signature before a single claim inside is believed. Everything in
   this section exists to make that possible without a JWT library.
   ========================================================================== */

if ( ! function_exists( 'kaamase_google_b64' ) ) {
	/**
	 * Decode base64url.
	 *
	 * The URL safe alphabet with the padding left off, which is what
	 * every part of a JWT uses.
	 *
	 * @since 1.6.0
	 * @param string $data Encoded value.
	 * @return string Raw bytes, or an empty string when it will not decode.
	 */
	function kaamase_google_b64( $data ) {

		$data = strtr( (string) $data, '-_', '+/' );
		$pad  = strlen( $data ) % 4;

		if ( $pad ) {
			$data .= str_repeat( '=', 4 - $pad );
		}

		$out = base64_decode( $data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		return false === $out ? '' : $out;
	}
}

if ( ! function_exists( 'kaamase_google_der' ) ) {
	/**
	 * Wrap a value in one DER element.
	 *
	 * Used to rebuild an RSA public key by hand. Google publishes keys
	 * as two raw numbers, and OpenSSL wants a PEM, so the structure
	 * around those numbers has to be assembled here.
	 *
	 * @since 1.6.0
	 * @param int    $tag   DER tag byte.
	 * @param string $value Contents.
	 * @return string Encoded element.
	 */
	function kaamase_google_der( $tag, $value ) {

		$length = strlen( $value );

		if ( $length < 0x80 ) {
			$header = chr( $length );
		} else {
			$bytes  = ltrim( pack( 'N', $length ), "\x00" );
			$header = chr( 0x80 | strlen( $bytes ) ) . $bytes;
		}

		return chr( $tag ) . $header . $value;
	}
}

if ( ! function_exists( 'kaamase_google_jwk_to_pem' ) ) {
	/**
	 * Turn one published key into a PEM.
	 *
	 * @since 1.6.0
	 * @param array $jwk One entry from Google's key list.
	 * @return string PEM, or an empty string when the entry is unusable.
	 */
	function kaamase_google_jwk_to_pem( $jwk ) {

		if ( empty( $jwk['n'] ) || empty( $jwk['e'] ) ) {
			return '';
		}

		$modulus  = kaamase_google_b64( $jwk['n'] );
		$exponent = kaamase_google_b64( $jwk['e'] );

		if ( '' === $modulus || '' === $exponent ) {
			return '';
		}

		/*
		 * A DER integer is signed, so a leading byte above 0x7f would
		 * read as a negative number. Both values here are always
		 * positive, and a zero byte in front says so.
		 */
		if ( ord( $modulus[0] ) > 0x7f ) {
			$modulus = "\x00" . $modulus;
		}

		if ( ord( $exponent[0] ) > 0x7f ) {
			$exponent = "\x00" . $exponent;
		}

		$key = kaamase_google_der(
			0x30,
			kaamase_google_der( 0x02, $modulus ) . kaamase_google_der( 0x02, $exponent )
		);

		// AlgorithmIdentifier for rsaEncryption, which never varies.
		$algorithm = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

		$info = kaamase_google_der(
			0x30,
			$algorithm . kaamase_google_der( 0x03, "\x00" . $key )
		);

		return "-----BEGIN PUBLIC KEY-----\n"
			. chunk_split( base64_encode( $info ), 64, "\n" ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			. "-----END PUBLIC KEY-----\n";
	}
}

if ( ! function_exists( 'kaamase_google_keys' ) ) {
	/**
	 * Google's current signing keys, keyed by kid.
	 *
	 * Held for six hours. Google rotates these on their own schedule and
	 * publishes the replacement before retiring the old one, so a cache
	 * this length never leaves a valid token unverifiable. When a token
	 * does name a kid that is not in the cache, the caller asks again
	 * with $force, which covers a rotation landing mid window.
	 *
	 * @since 1.6.0
	 * @param bool $force Skip the cache.
	 * @return array<string,array> Keys by kid.
	 */
	function kaamase_google_keys( $force = false ) {

		$cached = get_transient( 'kaamase_google_keys' );

		if ( ! $force && is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			'https://www.googleapis.com/oauth2/v3/certs',
			array( 'timeout' => 10 )
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return is_array( $cached ) ? $cached : array();
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['keys'] ) || ! is_array( $body['keys'] ) ) {
			return is_array( $cached ) ? $cached : array();
		}

		$keys = array();

		foreach ( $body['keys'] as $key ) {

			if ( ! is_array( $key ) || empty( $key['kid'] ) ) {
				continue;
			}

			$keys[ (string) $key['kid'] ] = $key;
		}

		if ( ! empty( $keys ) ) {
			set_transient( 'kaamase_google_keys', $keys, 6 * HOUR_IN_SECONDS );
		}

		return $keys;
	}
}

if ( ! function_exists( 'kaamase_google_verify' ) ) {
	/**
	 * Check a Google ID token and return what it says.
	 *
	 * Nothing inside the token is believed until the signature over it
	 * has been verified against a key Google published, and the audience
	 * has been matched to one of ours. A token signed by Google for
	 * somebody else's application is a valid token and still not ours to
	 * accept.
	 *
	 * @since 1.6.0
	 * @param string $jwt The raw token.
	 * @return array|WP_Error Claims, or an error.
	 */
	function kaamase_google_verify( $jwt ) {

		$fail = new WP_Error(
			'kaamase_google_rejected',
			__( 'That Google sign in could not be confirmed. Please try again.', 'kaamase-core' ),
			array( 'status' => 401 )
		);

		if ( ! function_exists( 'openssl_verify' ) ) {
			return new WP_Error(
				'kaamase_google_unavailable',
				__( 'Google sign in is not available on this site.', 'kaamase-core' ),
				array( 'status' => 503 )
			);
		}

		$audiences = kaamase_google_client_ids();

		if ( empty( $audiences ) ) {
			return new WP_Error(
				'kaamase_google_off',
				__( 'Google sign in is not switched on for this site.', 'kaamase-core' ),
				array( 'status' => 503 )
			);
		}

		$jwt = trim( (string) $jwt );

		/*
		 * A real Google token is well under two kilobytes. Anything
		 * past this is not a token that will ever verify, and decoding
		 * it first would mean doing the work to find that out.
		 */
		if ( '' === $jwt || strlen( $jwt ) > 8192 ) {
			return $fail;
		}

		$parts = explode( '.', $jwt );

		if ( 3 !== count( $parts ) ) {
			return $fail;
		}

		$header    = json_decode( kaamase_google_b64( $parts[0] ), true );
		$claims    = json_decode( kaamase_google_b64( $parts[1] ), true );
		$signature = kaamase_google_b64( $parts[2] );

		if ( ! is_array( $header ) || ! is_array( $claims ) || '' === $signature ) {
			return $fail;
		}

		/*
		 * RS256 and nothing else.
		 *
		 * Accepting whatever the header asks for is how a token signed
		 * with "none", or symmetrically signed with a public key, gets
		 * through. The algorithm is our decision, not the token's.
		 */
		if ( empty( $header['alg'] ) || 'RS256' !== $header['alg'] || empty( $header['kid'] ) ) {
			return $fail;
		}

		$kid  = (string) $header['kid'];
		$keys = kaamase_google_keys();

		if ( ! isset( $keys[ $kid ] ) ) {
			$keys = kaamase_google_keys( true );
		}

		if ( ! isset( $keys[ $kid ] ) ) {
			return $fail;
		}

		$pem = kaamase_google_jwk_to_pem( $keys[ $kid ] );

		if ( '' === $pem ) {
			return $fail;
		}

		$verified = openssl_verify(
			$parts[0] . '.' . $parts[1],
			$signature,
			$pem,
			OPENSSL_ALGO_SHA256
		);

		if ( 1 !== $verified ) {
			return $fail;
		}

		// Who issued it.
		$issuer = isset( $claims['iss'] ) ? (string) $claims['iss'] : '';

		if ( ! in_array( $issuer, array( 'https://accounts.google.com', 'accounts.google.com' ), true ) ) {
			return $fail;
		}

		// Who it was issued for.
		$audience = isset( $claims['aud'] ) ? (string) $claims['aud'] : '';

		if ( ! in_array( $audience, $audiences, true ) ) {
			return $fail;
		}

		/*
		 * Expiry, with a minute of slack for clocks that disagree.
		 * Shared hosting drifts, and a worker rejected because the
		 * server is forty seconds fast has no way to understand why.
		 */
		$expires = isset( $claims['exp'] ) ? (int) $claims['exp'] : 0;

		if ( $expires < ( time() - MINUTE_IN_SECONDS ) ) {
			return new WP_Error(
				'kaamase_google_expired',
				__( 'That sign in took too long. Please tap the Google button again.', 'kaamase-core' ),
				array( 'status' => 401 )
			);
		}

		$email = isset( $claims['email'] ) ? sanitize_email( (string) $claims['email'] ) : '';

		if ( ! is_email( $email ) ) {
			return $fail;
		}

		/*
		 * The flag everything else rests on.
		 *
		 * An account here is matched by email address, so an
		 * unconfirmed address would let somebody claim an account by
		 * typing its address into a Google profile. Google sends this
		 * as a real boolean or the string "true" depending on the
		 * flow, so both are accepted and nothing else is.
		 */
		$confirmed = isset( $claims['email_verified'] ) ? $claims['email_verified'] : false;

		if ( true !== $confirmed && 'true' !== $confirmed ) {
			return new WP_Error(
				'kaamase_google_unconfirmed',
				__( 'Google has not confirmed that email address, so it cannot be used to sign in here.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		if ( empty( $claims['sub'] ) ) {
			return $fail;
		}

		return array(
			'sub'   => (string) $claims['sub'],
			'email' => $email,
			'name'  => isset( $claims['name'] ) ? sanitize_text_field( (string) $claims['name'] ) : '',
		);
	}
}


/* ==========================================================================
   3. THE ACCOUNT

   Finding whose account a verified Google identity belongs to, joining
   it to one that already exists, or building a new one once the
   remaining questions have been answered.
   ========================================================================== */

if ( ! function_exists( 'kaamase_google_find_user' ) ) {
	/**
	 * Whose account this is, if it is anybody's.
	 *
	 * The Google account id is asked first because it never changes.
	 * Email is the fallback, and it is what joins a Google sign in to an
	 * account made with a password. It is only safe as a match because
	 * the caller has already established that Google confirmed the
	 * address.
	 *
	 * @since 1.6.0
	 * @param array $claims Verified claims.
	 * @return int User ID, or 0 when nobody holds this identity yet.
	 */
	function kaamase_google_find_user( $claims ) {

		$found = get_users(
			array(
				'meta_key'   => 'kaamase_google_sub', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $claims['sub'],       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			)
		);

		if ( ! empty( $found ) ) {
			return (int) $found[0];
		}

		$user = get_user_by( 'email', $claims['email'] );

		return $user ? (int) $user->ID : 0;
	}
}

if ( ! function_exists( 'kaamase_google_mark_verified' ) ) {
	/**
	 * Treat the account as confirmed, and publish what was waiting.
	 *
	 * Google has already proved the mailbox, so asking the same person
	 * to go and click a link in it would be theatre. This does what the
	 * emailed link does, and kills the link on its way past so an old
	 * confirmation email cannot be replayed later.
	 *
	 * @since 1.6.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_google_mark_verified( $user_id ) {

		$user_id = (int) $user_id;

		if ( kaamase_user_is_verified( $user_id ) ) {
			return;
		}

		update_user_meta( $user_id, 'kaamase_verified_at', time() );
		delete_user_meta( $user_id, 'kaamase_verify_hash' );
		delete_user_meta( $user_id, 'kaamase_verify_expires' );

		$profile_id = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( $profile_id && 'draft' === get_post_status( $profile_id ) ) {
			wp_update_post(
				array(
					'ID'          => $profile_id,
					'post_status' => 'publish',
				)
			);
		}

		/** This action is documented in includes/registration.php */
		do_action( 'kaamase_user_verified', $user_id );
	}
}

if ( ! function_exists( 'kaamase_google_attach' ) ) {
	/**
	 * Remember the Google account against the user.
	 *
	 * Stored so the next sign in matches on the account id rather than
	 * the email address, which means somebody who later changes their
	 * email on either side still gets into the same account.
	 *
	 * @since 1.6.0
	 * @param int   $user_id User ID.
	 * @param array $claims  Verified claims.
	 * @return void
	 */
	function kaamase_google_attach( $user_id, $claims ) {

		$user_id = (int) $user_id;

		if ( get_user_meta( $user_id, 'kaamase_google_sub', true ) === $claims['sub'] ) {
			return;
		}

		update_user_meta( $user_id, 'kaamase_google_sub', $claims['sub'] );
		update_user_meta( $user_id, 'kaamase_google_linked_at', time() );

		/**
		 * Fires when a Google account is joined to a Kaam Ase account.
		 *
		 * @since 1.6.0
		 * @param int   $user_id User ID.
		 * @param array $claims  Verified claims.
		 */
		do_action( 'kaamase_google_linked', $user_id, $claims );
	}
}

if ( ! function_exists( 'kaamase_google_signup_errors' ) ) {
	/**
	 * Check the answers to the questions Google cannot answer.
	 *
	 * Deliberately the same rules and the same wording as the ordinary
	 * registration form, minus the password and the email, because those
	 * two are what Google has just supplied.
	 *
	 * @since 1.6.0
	 * @param array $data Submitted values, already sanitised.
	 * @return string[] Messages, empty when everything is in order.
	 */
	function kaamase_google_signup_errors( $data ) {

		$errors = array();

		if ( ! in_array( $data['type'], array( 'worker', 'employer' ), true ) ) {
			$errors[] = __( 'Choose whether you are looking for work or looking for workers.', 'kaamase-core' );
		}

		if ( '' === trim( $data['name'] ) ) {
			$errors[] = __( 'Please enter your name.', 'kaamase-core' );
		}

		if ( '' === $data['district'] ) {
			$errors[] = __( 'Please choose your district.', 'kaamase-core' );
		}

		if ( 'worker' === $data['type'] && '' === $data['trade'] ) {
			$errors[] = __( 'Please choose the work you do.', 'kaamase-core' );
		}

		if ( '' === $data['phone_in'] ) {
			$errors[] = __( 'Please enter your phone number.', 'kaamase-core' );
		} elseif ( '' === $data['phone'] ) {
			$errors[] = __( 'That phone number does not look right. It should be 10 digits starting with 6, 7, 8 or 9.', 'kaamase-core' );
		}

		if ( empty( $data['agreed'] ) ) {
			$errors[] = __( 'Please agree to the terms and the privacy notice.', 'kaamase-core' );
		}

		return $errors;
	}
}

if ( ! function_exists( 'kaamase_google_create' ) ) {
	/**
	 * Build the account, then let them straight in.
	 *
	 * The ordinary account builder does the work, so a Google account
	 * and a password account are the same shape and the same hooks fire
	 * for both. Two things differ afterwards: the password is random
	 * because nobody will ever type it, and the account is confirmed on
	 * the spot.
	 *
	 * @since 1.6.0
	 * @param array $claims Verified claims.
	 * @param array $data   The answers to the remaining questions.
	 * @return int|WP_Error User ID, or an error.
	 */
	function kaamase_google_create( $claims, $data ) {

		/*
		 * The confirmation email is held back for this one call.
		 *
		 * kaamase_create_account() sends it, and rightly so for every
		 * other route in. Here it would arrive asking somebody to
		 * confirm an address Google confirmed a second earlier, and the
		 * link inside it is deleted moments later anyway. Blocking the
		 * send is done from out here so registration.php keeps working
		 * exactly as it does today.
		 */
		add_filter( 'pre_wp_mail', '__return_false', 99 );

		$user_id = kaamase_create_account(
			array(
				'type'     => $data['type'],
				'name'     => $data['name'],
				'email'    => $claims['email'],
				'phone'    => $data['phone'],
				'district' => $data['district'],
				'trade'    => $data['trade'],
				'password' => wp_generate_password( 32, true, true ),
			)
		);

		remove_filter( 'pre_wp_mail', '__return_false', 99 );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		kaamase_google_attach( $user_id, $claims );
		kaamase_google_mark_verified( $user_id );

		return (int) $user_id;
	}
}


/* ==========================================================================
   4. THE APP

   Two endpoints. The first says who this is, and whether we already
   know them. The second finishes a new account once the app has asked
   the few things Google does not carry.

   The same ID token is sent to both. It is valid for about an hour and
   is checked again on the second call, which means no half made account
   and no pending state is held on this end between the two steps.
   ========================================================================== */

if ( ! function_exists( 'kaamase_google_session' ) ) {
	/**
	 * The answer a signed in app expects.
	 *
	 * Deliberately the same shape as /auth/login and /auth/register, so
	 * the app stores a Google session exactly as it stores any other.
	 *
	 * @since 1.6.0
	 * @param int    $user_id User ID.
	 * @param string $device  Device label from the request.
	 * @param int    $status  HTTP status.
	 * @return WP_REST_Response
	 */
	function kaamase_google_session( $user_id, $device, $status = 200 ) {

		$token = kaamase_issue_token( $user_id, $device );

		wp_set_current_user( $user_id );

		return new WP_REST_Response(
			array(
				'token'      => $token['token'],
				'expires_at' => $token['expires_at'],
				'me'         => kaamase_shape_me( $user_id ),
			),
			$status
		);
	}
}

if ( ! function_exists( 'kaamase_rest_google' ) ) {
	/**
	 * Sign in with a Google ID token.
	 *
	 * Answers one of two ways. Either the account exists and a session
	 * comes back, or it does not and the app is told what is still
	 * needed. A new account is never created here, because the answers
	 * this platform needs have not been asked yet.
	 *
	 * @since 1.6.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_google( $request ) {

		if ( kaamase_login_blocked() ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_rate_limited', __( 'Too many sign in attempts. Please wait a few minutes and try again.', 'kaamase-core' ), array( 'status' => 429 ) )
			);
		}

		$claims = kaamase_google_verify( (string) $request->get_param( 'id_token' ) );

		if ( is_wp_error( $claims ) ) {
			kaamase_login_failed();
			return kaamase_rest_error( $claims );
		}

		$user_id = kaamase_google_find_user( $claims );

		if ( ! $user_id ) {

			/*
			 * Not a failure, so the login counter is left alone. This
			 * is the ordinary first visit of somebody who has never
			 * registered, and counting it against them would lock out a
			 * household setting up two accounts on one connection.
			 */
			return new WP_REST_Response(
				array(
					'needs_profile' => true,
					'email'         => $claims['email'],
					'name'          => $claims['name'],
				),
				200
			);
		}

		kaamase_login_succeeded();
		kaamase_google_attach( $user_id, $claims );
		kaamase_google_mark_verified( $user_id );

		return kaamase_google_session( $user_id, (string) $request->get_param( 'device' ) );
	}
}

if ( ! function_exists( 'kaamase_rest_google_complete' ) ) {
	/**
	 * Finish a new account started with Google.
	 *
	 * @since 1.6.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_google_complete( $request ) {

		if ( ! kaamase_throttle_ok( kaamase_client_key( 'register' ), 5, HOUR_IN_SECONDS ) ) {
			return kaamase_rest_error( kaamase_throttle_error() );
		}

		$claims = kaamase_google_verify( (string) $request->get_param( 'id_token' ) );

		if ( is_wp_error( $claims ) ) {
			return kaamase_rest_error( $claims );
		}

		/*
		 * Somebody who got here twice, or whose account was made on the
		 * website between the two taps, is signed in rather than told
		 * off. Both are the same person and the second account would be
		 * the bug.
		 */
		$existing = kaamase_google_find_user( $claims );

		if ( $existing ) {
			kaamase_google_attach( $existing, $claims );
			kaamase_google_mark_verified( $existing );
			return kaamase_google_session( $existing, (string) $request->get_param( 'device' ) );
		}

		$phone_in = sanitize_text_field( (string) $request->get_param( 'phone' ) );

		$data = array(
			'type'     => sanitize_key( (string) $request->get_param( 'type' ) ),
			'name'     => sanitize_text_field( (string) $request->get_param( 'name' ) ),
			'district' => kaamase_match_district( (string) $request->get_param( 'district' ) ),
			'trade'    => kaamase_match_trade( (string) $request->get_param( 'trade' ) ),
			'phone_in' => $phone_in,
			'phone'    => kaamase_sanitize_phone( $phone_in ),
			'agreed'   => (bool) $request->get_param( 'agreed' ),
		);

		// Google's name is the sensible default when the app sends none.
		if ( '' === trim( $data['name'] ) ) {
			$data['name'] = $claims['name'];
		}

		$errors = kaamase_google_signup_errors( $data );

		if ( ! empty( $errors ) ) {
			return kaamase_rest_error(
				new WP_Error(
					'kaamase_invalid_registration',
					$errors[0],
					array(
						'messages' => $errors,
						'status'   => 400,
					)
				)
			);
		}

		$user_id = kaamase_google_create( $claims, $data );

		if ( is_wp_error( $user_id ) ) {
			return kaamase_rest_error( $user_id );
		}

		kaamase_login_succeeded();

		return kaamase_google_session( $user_id, (string) $request->get_param( 'device' ), 201 );
	}
}

if ( ! function_exists( 'kaamase_google_routes' ) ) {
	/**
	 * Register the two endpoints.
	 *
	 * Registered from this file rather than added to the list in
	 * rest-api.php, so the whole feature arrives and leaves as one file.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_google_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/google',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_google',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/google/complete',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_google_complete',
				'permission_callback' => '__return_true',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_google_routes' );

if ( ! function_exists( 'kaamase_google_in_reference' ) ) {
	/**
	 * Tell the app whether the button is worth drawing.
	 *
	 * Without this the app has to guess, and a Google button that fails
	 * every time because no client ID was ever set is worse than no
	 * button. Merged after the cache for the same reason the version
	 * floor is: switching it off has to take effect now, not in six
	 * hours.
	 *
	 * @since 1.6.0
	 * @param WP_REST_Response $response The response.
	 * @param WP_REST_Server   $server   The server.
	 * @param WP_REST_Request  $request  The request.
	 * @return WP_REST_Response
	 */
	function kaamase_google_in_reference( $response, $server, $request ) {

		unset( $server );

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return $response;
		}

		if ( '/' . KAAMASE_REST_NS . '/reference' !== $request->get_route() ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) ) {
			return $response;
		}

		$data['google_sign_in'] = kaamase_google_is_on();

		$response->set_data( $data );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_google_in_reference', 10, 3 );


/* ==========================================================================
   5. THE WEBSITE

   Google's own button script posts the token straight back to this
   site. Same verification, same account rules; only the way somebody
   ends up signed in differs, because a browser gets a cookie where the
   app gets a bearer token.
   ========================================================================== */

if ( ! function_exists( 'kaamase_google_return_url' ) ) {
	/**
	 * Where Google posts the token back to.
	 *
	 * @since 1.6.0
	 * @return string
	 */
	function kaamase_google_return_url() {
		return admin_url( 'admin-post.php?action=kaamase_google' );
	}
}

if ( ! function_exists( 'kaamase_google_button' ) ) {
	/**
	 * The Google button, with its script.
	 *
	 * @since 1.6.0
	 * @param string $label What the button should say.
	 * @return string Markup, empty when the feature is off.
	 */
	function kaamase_google_button( $label = '' ) {

		$client = kaamase_google_web_client_id();

		if ( '' === $client || ! kaamase_google_is_on() || is_user_logged_in() ) {
			return '';
		}

		$label = '' !== $label ? $label : __( 'or sign in with a password below', 'kaamase-core' );

		ob_start();
		?>
		<div class="ka-google" style="margin:0 0 18px;">

			<div id="g_id_onload"
				data-client_id="<?php echo esc_attr( $client ); ?>"
				data-login_uri="<?php echo esc_url( kaamase_google_return_url() ); ?>"
				data-ux_mode="redirect"
				data-context="signin"
				data-auto_prompt="false"></div>

			<div class="g_id_signin"
				data-type="standard"
				data-theme="outline"
				data-size="large"
				data-text="continue_with"
				data-shape="rectangular"
				data-logo_alignment="left"
				style="display:flex;justify-content:center;"></div>

			<p class="ka-small ka-mute" style="text-align:center;margin:12px 0 0;">
				<?php echo esc_html( $label ); ?>
			</p>
		</div>
		<script src="https://accounts.google.com/gsi/client" async defer></script>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_google_login_screen' ) ) {
	/**
	 * Put the button on the WordPress sign in screen.
	 *
	 * Above the form rather than inside it. login_form would drop the
	 * button between the password box and the submit button, where it
	 * reads as part of the password form and sits inside a form element
	 * it has nothing to do with.
	 *
	 * @since 1.6.0
	 * @param string $message Existing message markup.
	 * @return string
	 */
	function kaamase_google_login_screen( $message ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['kaamase_google_error'] ) ) {

			$message .= '<div id="login_error">'
				. esc_html__( 'That Google sign in did not go through. Please try again, or sign in with your password.', 'kaamase-core' )
				. '</div>';
		}

		return $message . kaamase_google_button();
	}
}
add_filter( 'login_message', 'kaamase_google_login_screen' );

if ( ! function_exists( 'kaamase_google_on_register_page' ) ) {
	/**
	 * Put the button above the registration form.
	 *
	 * Done through the shortcode's own output filter rather than by
	 * editing the form, so registration.php is left exactly as it is
	 * and this whole feature can be removed by deleting one file.
	 *
	 * @since 1.6.0
	 * @param string $output The shortcode's markup.
	 * @param string $tag    Which shortcode.
	 * @return string
	 */
	function kaamase_google_on_register_page( $output, $tag ) {

		if ( 'kaamase_register' !== $tag || is_user_logged_in() ) {
			return $output;
		}

		// Finishing a Google signup replaces the form rather than joining it.
		$pending = kaamase_google_pending();

		if ( $pending ) {
			return kaamase_google_finish_form( $pending );
		}

		$button = kaamase_google_button( __( 'or fill in the form below', 'kaamase-core' ) );

		return '' === $button ? $output : $button . $output;
	}
}
add_filter( 'do_shortcode_tag', 'kaamase_google_on_register_page', 10, 2 );

if ( ! function_exists( 'kaamase_google_stash' ) ) {
	/**
	 * Hold a verified token for the few minutes a signup takes.
	 *
	 * The token itself never travels in the address bar. It is long,
	 * and a URL is written into server logs and browser history, which
	 * is no place for a credential. A short random handle goes there
	 * instead and is spent on first use.
	 *
	 * @since 1.6.0
	 * @param string $jwt The verified token.
	 * @return string The handle.
	 */
	function kaamase_google_stash( $jwt ) {

		$handle = wp_generate_password( 32, false, false );

		set_transient( 'kaamase_google_new_' . $handle, $jwt, 15 * MINUTE_IN_SECONDS );

		return $handle;
	}
}

if ( ! function_exists( 'kaamase_google_pending' ) ) {
	/**
	 * The verified identity of somebody part way through signing up.
	 *
	 * @since 1.6.0
	 * @param string $handle Handle, or empty to read it from the address.
	 * @return array Claims plus the handle, or an empty array.
	 */
	function kaamase_google_pending( $handle = '' ) {

		if ( '' === $handle ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$handle = isset( $_GET['kaamase_google'] ) ? sanitize_text_field( wp_unslash( $_GET['kaamase_google'] ) ) : '';
		}

		if ( '' === $handle ) {
			return array();
		}

		$jwt = get_transient( 'kaamase_google_new_' . $handle );

		if ( ! $jwt ) {
			return array();
		}

		$claims = kaamase_google_verify( (string) $jwt );

		if ( is_wp_error( $claims ) ) {
			delete_transient( 'kaamase_google_new_' . $handle );
			return array();
		}

		$claims['handle'] = $handle;

		return $claims;
	}
}

if ( ! function_exists( 'kaamase_google_receive' ) ) {
	/**
	 * Take the token Google's button posted back.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_google_receive() {

		$failed = add_query_arg( 'kaamase_google_error', '1', wp_login_url() );

		if ( is_user_logged_in() ) {
			wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
			exit;
		}

		if ( kaamase_login_blocked() ) {
			wp_safe_redirect( $failed );
			exit;
		}

		/*
		 * Google's own cross site check.
		 *
		 * The button sets a cookie and posts the same value in the body.
		 * A form posted from another site can carry the body but cannot
		 * read or set the cookie, so the two agreeing is what says this
		 * submission came from the button on this site.
		 */
		$posted = isset( $_POST['g_csrf_token'] ) ? sanitize_text_field( wp_unslash( $_POST['g_csrf_token'] ) ) : '';
		$cookie = isset( $_COOKIE['g_csrf_token'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['g_csrf_token'] ) ) : '';

		if ( '' === $posted || '' === $cookie || ! hash_equals( $cookie, $posted ) ) {
			wp_safe_redirect( $failed );
			exit;
		}

		$jwt = isset( $_POST['credential'] ) ? trim( (string) wp_unslash( $_POST['credential'] ) ) : '';

		$claims = kaamase_google_verify( $jwt );

		if ( is_wp_error( $claims ) ) {
			kaamase_login_failed();
			wp_safe_redirect( $failed );
			exit;
		}

		$user_id = kaamase_google_find_user( $claims );

		// Nobody yet. Ask the rest of the questions on the register page.
		if ( ! $user_id ) {

			wp_safe_redirect(
				add_query_arg(
					'kaamase_google',
					kaamase_google_stash( $jwt ),
					kaamase_page_url( 'register' )
				)
			);
			exit;
		}

		kaamase_login_succeeded();
		kaamase_google_attach( $user_id, $claims );
		kaamase_google_mark_verified( $user_id );

		wp_set_auth_cookie( $user_id, true );
		wp_set_current_user( $user_id );

		wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
		exit;
	}
}
add_action( 'admin_post_nopriv_kaamase_google', 'kaamase_google_receive' );
add_action( 'admin_post_kaamase_google', 'kaamase_google_receive' );

if ( ! function_exists( 'kaamase_google_send_back' ) ) {
	/**
	 * Return somebody to the form with what they typed and what is wrong.
	 *
	 * The ordinary kaamase_registration_fail() is not used here because
	 * it redirects to the plain register page, which would drop the
	 * handle and lose a half finished Google signup. Same two
	 * transients, same wording on the other end, different destination.
	 *
	 * @since 1.6.0
	 * @param string[] $errors Messages to show.
	 * @param array    $data   What they entered, to fill the form back in.
	 * @param string   $back   Where to send them.
	 * @return void
	 */
	function kaamase_google_send_back( $errors, $data, $back ) {

		/*
		 * Kept under its own keys rather than the registration ones.
		 *
		 * This form is drawn from do_shortcode_tag, which runs after
		 * the registration shortcode has already produced its output,
		 * and that shortcode reads the ordinary error transient and
		 * deletes it on the way past. Sharing the key would mean every
		 * error on this form was swallowed before it could be shown.
		 */
		set_transient(
			kaamase_registration_key() . '_gerr',
			(array) $errors,
			15 * MINUTE_IN_SECONDS
		);

		/*
		 * The number as typed goes back into the box, not the cleaned
		 * one. Somebody who mistyped a digit needs to see what they
		 * actually wrote in order to spot it; handing back an empty
		 * field tells them nothing.
		 */
		set_transient(
			kaamase_registration_key() . '_gval',
			array(
				'type'     => isset( $data['type'] ) ? $data['type'] : '',
				'name'     => isset( $data['name'] ) ? $data['name'] : '',
				'phone'    => isset( $data['phone_in'] ) ? $data['phone_in'] : '',
				'district' => isset( $data['district'] ) ? $data['district'] : '',
				'trade'    => isset( $data['trade'] ) ? $data['trade'] : '',
			),
			15 * MINUTE_IN_SECONDS
		);

		wp_safe_redirect( $back );
		exit;
	}
}

if ( ! function_exists( 'kaamase_google_form_state' ) ) {
	/**
	 * Read back what was typed and what was wrong, then forget it.
	 *
	 * @since 1.6.0
	 * @return array{errors: string[], values: array}
	 */
	function kaamase_google_form_state() {

		$error_key = kaamase_registration_key() . '_gerr';
		$value_key = kaamase_registration_key() . '_gval';

		$errors = get_transient( $error_key );
		$values = get_transient( $value_key );

		if ( $errors ) {
			delete_transient( $error_key );
		}

		if ( $values ) {
			delete_transient( $value_key );
		}

		return array(
			'errors' => is_array( $errors ) ? $errors : array(),
			'values' => is_array( $values ) ? $values : array(),
		);
	}
}

if ( ! function_exists( 'kaamase_google_finish' ) ) {
	/**
	 * Create the account once the last questions are answered.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_google_finish() {

		check_admin_referer( 'kaamase_google_finish' );

		$handle  = isset( $_POST['kaamase_google_handle'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_google_handle'] ) ) : '';
		$pending = kaamase_google_pending( $handle );

		if ( empty( $pending ) ) {
			wp_safe_redirect( add_query_arg( 'kaamase_google_error', '1', wp_login_url() ) );
			exit;
		}

		$back = add_query_arg( 'kaamase_google', $handle, kaamase_page_url( 'register' ) );

		if ( ! kaamase_throttle_ok( kaamase_client_key( 'register' ), 5, HOUR_IN_SECONDS ) ) {
			kaamase_google_send_back(
				array( kaamase_throttle_error()->get_error_message() ),
				array(),
				$back
			);
		}

		$phone_in = isset( $_POST['kaamase_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_phone'] ) ) : '';

		$data = array(
			'type'     => isset( $_POST['kaamase_type'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_type'] ) ) : '',
			'name'     => isset( $_POST['kaamase_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_name'] ) ) : '',
			'district' => kaamase_match_district( isset( $_POST['kaamase_district'] ) ? wp_unslash( $_POST['kaamase_district'] ) : '' ),
			'trade'    => kaamase_match_trade( isset( $_POST['kaamase_trade'] ) ? wp_unslash( $_POST['kaamase_trade'] ) : '' ),
			'phone_in' => $phone_in,
			'phone'    => kaamase_sanitize_phone( $phone_in ),
			'agreed'   => ! empty( $_POST['kaamase_agree'] ),
		);

		$errors = kaamase_google_signup_errors( $data );

		if ( ! empty( $errors ) ) {
			kaamase_google_send_back( $errors, $data, $back );
		}

		$user_id = kaamase_google_create( $pending, $data );

		if ( is_wp_error( $user_id ) ) {
			kaamase_google_send_back( array( $user_id->get_error_message() ), $data, $back );
		}

		// Spent. The handle cannot make a second account.
		delete_transient( 'kaamase_google_new_' . $handle );

		delete_transient( kaamase_registration_key() . '_gerr' );
		delete_transient( kaamase_registration_key() . '_gval' );

		wp_set_auth_cookie( $user_id, true );
		wp_set_current_user( $user_id );

		wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
		exit;
	}
}
add_action( 'admin_post_nopriv_kaamase_google_finish', 'kaamase_google_finish' );
add_action( 'admin_post_kaamase_google_finish', 'kaamase_google_finish' );

if ( ! function_exists( 'kaamase_google_finish_form' ) ) {
	/**
	 * The short form somebody sees after tapping Google for the first time.
	 *
	 * No email field and no password field. Google settled both, and
	 * showing an email box somebody cannot change only invites them to
	 * try. What is left is what the platform genuinely cannot work
	 * without.
	 *
	 * @since 1.6.0
	 * @param array $pending Verified claims plus the handle.
	 * @return string Markup.
	 */
	function kaamase_google_finish_form( $pending ) {

		$state  = kaamase_google_form_state();
		$old    = $state['values'];
		$errors = $state['errors'];

		$name = isset( $old['name'] ) && '' !== $old['name'] ? $old['name'] : $pending['name'];
		$type = isset( $old['type'] ) ? $old['type'] : '';

		ob_start();

		if ( ! empty( $errors ) ) {
			echo '<div class="ka-notice ka-notice--error"><div><span class="ka-notice__title">'
				. esc_html__( 'Please fix this', 'kaamase-core' )
				. '</span><ul>';

			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}

			echo '</ul></div></div>';
		}
		?>
		<div class="ka-notice ka-notice--info">
			<p>
				<?php
				printf(
					/* translators: %s: the email address Google confirmed */
					esc_html__( 'Signed in with Google as %s. Just a few more things and you are done.', 'kaamase-core' ),
					'<strong>' . esc_html( $pending['email'] ) . '</strong>'
				);
				?>
			</p>
		</div>

		<form class="ka-form ka-stack--lg" method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

			<?php wp_nonce_field( 'kaamase_google_finish' ); ?>
			<input type="hidden" name="action" value="kaamase_google_finish">
			<input type="hidden" name="kaamase_google_handle" value="<?php echo esc_attr( $pending['handle'] ); ?>">

			<div class="ka-field">
				<span class="ka-label">
					<?php esc_html_e( 'What brings you here', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</span>
				<label class="ka-check">
					<input type="radio" name="kaamase_type" value="worker" required
						<?php checked( 'worker', $type ); ?>>
					<span><?php esc_html_e( 'I am looking for work', 'kaamase-core' ); ?></span>
				</label>
				<label class="ka-check">
					<input type="radio" name="kaamase_type" value="employer"
						<?php checked( 'employer', $type ); ?>>
					<span><?php esc_html_e( 'I am looking for workers', 'kaamase-core' ); ?></span>
				</label>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-g-name">
					<?php esc_html_e( 'Your name', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="text" id="ka-g-name" name="kaamase_name" required
					autocomplete="name" value="<?php echo esc_attr( $name ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-g-phone">
					<?php esc_html_e( 'Phone number', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="tel" id="ka-g-phone" name="kaamase_phone" required
					inputmode="numeric" autocomplete="tel" maxlength="15"
					pattern="[0-9 +\-]{10,15}"
					value="<?php echo esc_attr( isset( $old['phone'] ) ? $old['phone'] : '' ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( '10 digits. Your number is never shown on the site. People reach you through Kaam Ase.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-g-district">
					<?php esc_html_e( 'District', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-g-district" name="kaamase_district" required>
					<option value=""><?php esc_html_e( 'Choose your district', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_district_choices() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"
							<?php selected( isset( $old['district'] ) ? $old['district'] : '', $slug ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php
			/*
			 * The trade list is always rendered, because the type is
			 * chosen on this same form and the answer is not known
			 * until it is submitted. It is ignored for an employer,
			 * exactly as it is in the ordinary registration handler.
			 */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-g-trade">
					<?php esc_html_e( 'What work do you do', 'kaamase-core' ); ?>
				</label>
				<select class="ka-select" id="ka-g-trade" name="kaamase_trade">
					<option value=""><?php esc_html_e( 'Choose your trade', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_trade_choices() as $group => $trades ) : ?>
						<optgroup label="<?php echo esc_attr( $group ); ?>">
							<?php foreach ( $trades as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"
									<?php selected( isset( $old['trade'] ) ? $old['trade'] : '', $slug ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
				<p class="ka-hint">
					<?php esc_html_e( 'Only needed if you are looking for work. You can add more trades afterwards.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-check">
					<input type="checkbox" name="kaamase_agree" value="1" required>
					<span>
						<?php
						printf(
							/* translators: 1: terms page link, 2: privacy page link */
							esc_html__( 'I agree to the %1$s and the %2$s.', 'kaamase-core' ),
							'<a href="' . esc_url( kaamase_page_url( 'terms' ) ) . '">' . esc_html__( 'terms', 'kaamase-core' ) . '</a>',
							'<a href="' . esc_url( kaamase_page_url( 'privacy' ) ) . '">' . esc_html__( 'privacy notice', 'kaamase-core' ) . '</a>'
						);
						?>
					</span>
				</label>
			</div>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
				<?php esc_html_e( 'Create my account', 'kaamase-core' ); ?>
			</button>

			<p class="ka-small ka-mute">
				<?php esc_html_e( 'Kaam Ase is free. We will never ask a worker for money.', 'kaamase-core' ); ?>
			</p>

		</form>
		<?php

		return (string) ob_get_clean();
	}
}

