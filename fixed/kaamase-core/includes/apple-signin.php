<?php
/**
 * Signing in with Apple.
 *
 * A third door, beside email and password and beside Google. Nothing in
 * this file changes either of those.
 *
 * Why it exists
 * -------------
 * Apple's rule 4.8 says an app offering a third party sign in must also
 * offer one that limits what is collected to a name and an email, lets
 * somebody keep that email private, and does not follow them around for
 * advertising. Sign in with Apple is the one Apple names. Without it the
 * iPhone build does not ship, and the app was refused on exactly this
 * ground.
 *
 * So this is not a preference. It is the price of the App Store, and it
 * is a fair one: it is the only sign in on the platform where somebody
 * can join without handing over a real address.
 *
 * Why Google stays everywhere
 * ---------------------------
 * The obvious shortcut was Google on Android, Apple on iPhone, and it is
 * the wrong shape for these users. People here share and borrow phones,
 * and employers look at profiles on somebody's desktop. An account has
 * to open from any device, not only from the family of device that made
 * it. Splitting the providers would have made an account belong to the
 * phone it was born on, and would have locked anybody out of the website
 * who signed up on an iPhone.
 *
 * Three things about Apple that are not true of Google
 * ----------------------------------------------------
 * The name arrives once and never again. Apple sends it in the sign in
 * response the first time somebody authorises, not in the token, and
 * never afterwards. If the app does not pass it on that first call it is
 * gone for good, so the account is created from what the app sends and
 * this file treats a missing name as ordinary rather than as an error.
 *
 * The email may be a forwarding address. Somebody may choose to hide
 * theirs, and what arrives is a privaterelay.appleid.com address that
 * forwards to their real inbox. That is a real, working address and it is
 * accepted as one: mail sent to it arrives.
 *
 * The email may not arrive at all. Apple includes it when somebody first
 * authorises and is not obliged to keep sending it. So a returning person
 * is matched on the Apple account id, which never changes, and the
 * address is only needed when there is nobody to match yet.
 *
 * On the copied code
 * ------------------
 * The base64url, DER and key rebuilding below are the same shape as the
 * ones in google-signin.php. They were not shared into a common file on
 * purpose. That code is live, verified against OpenSSL, and holds up
 * every Google sign in on the platform; rewriting it to serve two callers
 * would put a working door at risk to save a hundred lines. If a third
 * provider is ever added, that is the moment to lift it out.
 *
 * Off until a client ID is set. With the setting empty no endpoint
 * answers, the app is told the door is closed, and the site behaves
 * exactly as it did before this file existed.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.7.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. SETTINGS
   ========================================================================== */

if ( ! function_exists( 'kaamase_apple_audiences' ) ) {
	/**
	 * Every client ID that may appear in a token's audience.
	 *
	 * For the app this is the bundle identifier. A Services ID is there
	 * for the day Sign in with Apple is added to the website, which it
	 * is not today.
	 *
	 * @since 1.7.0
	 * @return string[] Client IDs, empty values removed.
	 */
	function kaamase_apple_audiences() {

		$ids = array(
			trim( (string) get_option( 'kaamase_apple_bundle_id', '' ) ),
			trim( (string) get_option( 'kaamase_apple_service_id', '' ) ),
		);

		/**
		 * Filter the accepted Sign in with Apple audiences.
		 *
		 * @since 1.7.0
		 * @param string[] $ids Client IDs.
		 */
		$ids = (array) apply_filters( 'kaamase_apple_audiences', $ids );

		return array_values( array_filter( array_map( 'strval', $ids ) ) );
	}
}

if ( ! function_exists( 'kaamase_apple_is_on' ) ) {
	/**
	 * Whether Sign in with Apple is configured at all.
	 *
	 * @since 1.7.0
	 * @return bool
	 */
	function kaamase_apple_is_on() {
		return ! empty( kaamase_apple_audiences() ) && function_exists( 'openssl_verify' );
	}
}

if ( ! function_exists( 'kaamase_apple_menu' ) ) {
	/**
	 * Add the settings screen.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	function kaamase_apple_menu() {

		add_options_page(
			__( 'Apple sign in', 'kaamase-core' ),
			__( 'Apple sign in', 'kaamase-core' ),
			'manage_options',
			'kaamase-apple',
			'kaamase_apple_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_apple_menu' );

if ( ! function_exists( 'kaamase_apple_save_settings' ) ) {
	/**
	 * Save the identifiers.
	 *
	 * @since 1.7.0
	 * @return string A message to show, or an empty string.
	 */
	function kaamase_apple_save_settings() {

		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['kaamase_apple_settings'] ) ) {
			return '';
		}

		check_admin_referer( 'kaamase_apple_settings' );

		foreach ( array( 'bundle_id', 'service_id' ) as $key ) {

			$raw = trim( (string) wp_unslash( $_POST[ 'kaamase_apple_' . $key ] ?? '' ) );

			update_option( 'kaamase_apple_' . $key, sanitize_text_field( $raw ) );
		}

		return __( 'Saved.', 'kaamase-core' );
	}
}

if ( ! function_exists( 'kaamase_apple_page' ) ) {
	/**
	 * Draw the settings screen.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	function kaamase_apple_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		$notice = kaamase_apple_save_settings();
		$bundle = trim( (string) get_option( 'kaamase_apple_bundle_id', '' ) );
		$svc    = trim( (string) get_option( 'kaamase_apple_service_id', '' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apple sign in', 'kaamase-core' ); ?></h1>

			<?php if ( '' !== $notice ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<p>
				<?php
				esc_html_e(
					'Lets people sign in with an Apple account. Apple requires this on the iPhone app because the app also offers Google. Email, password and Google all keep working either way. Leave this empty and Sign in with Apple is switched off.',
					'kaamase-core'
				);
				?>
			</p>

			<?php if ( ! function_exists( 'openssl_verify' ) ) : ?>
				<div class="notice notice-error inline">
					<p>
						<?php
						esc_html_e(
							'This server has no OpenSSL support in PHP, so an Apple token cannot be checked here. Sign in with Apple stays off until your host enables it.',
							'kaamase-core'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'kaamase_apple_settings' ); ?>
				<input type="hidden" name="kaamase_apple_settings" value="1">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="kaamase_apple_bundle_id"><?php esc_html_e( 'App bundle ID', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_apple_bundle_id" id="kaamase_apple_bundle_id" type="text"
								class="large-text code" value="<?php echo esc_attr( $bundle ); ?>"
								placeholder="com.example.kaamase">
							<p class="description">
								<?php esc_html_e( 'The iPhone app’s bundle identifier, exactly as it appears in App Store Connect. This is what the app’s sign in tokens are addressed to.', 'kaamase-core' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="kaamase_apple_service_id"><?php esc_html_e( 'Website Services ID', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_apple_service_id" id="kaamase_apple_service_id" type="text"
								class="large-text code" value="<?php echo esc_attr( $svc ); ?>"
								placeholder="com.example.kaamase.web">
							<p class="description">
								<?php esc_html_e( 'Only needed if Sign in with Apple is ever added to the website. Leave empty otherwise.', 'kaamase-core' ); ?>
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
   2. READING WHAT APPLE SENT

   Same three parts as any other signed token: a header naming the key, the
   claims, and a signature over both. Nothing inside is believed until the
   signature has been checked against a key Apple published.
   ========================================================================== */

if ( ! function_exists( 'kaamase_apple_b64' ) ) {
	/**
	 * Decode base64url.
	 *
	 * @since 1.7.0
	 * @param string $data Encoded value.
	 * @return string Raw bytes, or an empty string when it will not decode.
	 */
	function kaamase_apple_b64( $data ) {

		$data = strtr( (string) $data, '-_', '+/' );
		$pad  = strlen( $data ) % 4;

		if ( $pad ) {
			$data .= str_repeat( '=', 4 - $pad );
		}

		$out = base64_decode( $data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		return false === $out ? '' : $out;
	}
}

if ( ! function_exists( 'kaamase_apple_der' ) ) {
	/**
	 * Wrap a value in one DER element.
	 *
	 * @since 1.7.0
	 * @param int    $tag   DER tag byte.
	 * @param string $value Contents.
	 * @return string Encoded element.
	 */
	function kaamase_apple_der( $tag, $value ) {

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

if ( ! function_exists( 'kaamase_apple_jwk_to_pem' ) ) {
	/**
	 * Turn one published key into a PEM.
	 *
	 * @since 1.7.0
	 * @param array $jwk One entry from Apple's key list.
	 * @return string PEM, or an empty string when the entry is unusable.
	 */
	function kaamase_apple_jwk_to_pem( $jwk ) {

		if ( empty( $jwk['n'] ) || empty( $jwk['e'] ) ) {
			return '';
		}

		$modulus  = kaamase_apple_b64( $jwk['n'] );
		$exponent = kaamase_apple_b64( $jwk['e'] );

		if ( '' === $modulus || '' === $exponent ) {
			return '';
		}

		/*
		 * A DER integer is signed, so a leading byte above 0x7f would
		 * read as negative. Both values are positive; a zero byte in
		 * front says so.
		 */
		if ( ord( $modulus[0] ) > 0x7f ) {
			$modulus = "\x00" . $modulus;
		}

		if ( ord( $exponent[0] ) > 0x7f ) {
			$exponent = "\x00" . $exponent;
		}

		$key = kaamase_apple_der(
			0x30,
			kaamase_apple_der( 0x02, $modulus ) . kaamase_apple_der( 0x02, $exponent )
		);

		// AlgorithmIdentifier for rsaEncryption, which never varies.
		$algorithm = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";

		$info = kaamase_apple_der(
			0x30,
			$algorithm . kaamase_apple_der( 0x03, "\x00" . $key )
		);

		return "-----BEGIN PUBLIC KEY-----\n"
			. chunk_split( base64_encode( $info ), 64, "\n" ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			. "-----END PUBLIC KEY-----\n";
	}
}

if ( ! function_exists( 'kaamase_apple_keys' ) ) {
	/**
	 * Apple's current signing keys, keyed by kid.
	 *
	 * Held for six hours, and asked for again when a token names a key
	 * the cache has not seen, which covers a rotation landing mid window.
	 *
	 * @since 1.7.0
	 * @param bool $force Skip the cache.
	 * @return array<string,array> Keys by kid.
	 */
	function kaamase_apple_keys( $force = false ) {

		$cached = get_transient( 'kaamase_apple_keys' );

		if ( ! $force && is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			'https://appleid.apple.com/auth/keys',
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
			set_transient( 'kaamase_apple_keys', $keys, 6 * HOUR_IN_SECONDS );
		}

		return $keys;
	}
}

if ( ! function_exists( 'kaamase_apple_verify' ) ) {
	/**
	 * Check an Apple identity token and return what it says.
	 *
	 * @since 1.7.0
	 * @param string $jwt The raw token.
	 * @return array|WP_Error Claims, or an error.
	 */
	function kaamase_apple_verify( $jwt ) {

		$fail = new WP_Error(
			'kaamase_apple_rejected',
			__( 'That Apple sign in could not be confirmed. Please try again.', 'kaamase-core' ),
			array( 'status' => 401 )
		);

		if ( ! function_exists( 'openssl_verify' ) ) {
			return new WP_Error(
				'kaamase_apple_unavailable',
				__( 'Apple sign in is not available on this site.', 'kaamase-core' ),
				array( 'status' => 503 )
			);
		}

		$audiences = kaamase_apple_audiences();

		if ( empty( $audiences ) ) {
			return new WP_Error(
				'kaamase_apple_off',
				__( 'Apple sign in is not switched on for this site.', 'kaamase-core' ),
				array( 'status' => 503 )
			);
		}

		$jwt = trim( (string) $jwt );

		/*
		 * A real token is well under two kilobytes. Anything past this
		 * will never verify, and decoding it first would mean doing the
		 * work to find that out.
		 */
		if ( '' === $jwt || strlen( $jwt ) > 8192 ) {
			return $fail;
		}

		$parts = explode( '.', $jwt );

		if ( 3 !== count( $parts ) ) {
			return $fail;
		}

		$header    = json_decode( kaamase_apple_b64( $parts[0] ), true );
		$claims    = json_decode( kaamase_apple_b64( $parts[1] ), true );
		$signature = kaamase_apple_b64( $parts[2] );

		if ( ! is_array( $header ) || ! is_array( $claims ) || '' === $signature ) {
			return $fail;
		}

		/*
		 * RS256 and nothing else. Accepting whatever the header asks for
		 * is how a token signed with "none" gets through. The algorithm
		 * is our decision, not the token's.
		 */
		if ( empty( $header['alg'] ) || 'RS256' !== $header['alg'] || empty( $header['kid'] ) ) {
			return $fail;
		}

		$kid  = (string) $header['kid'];
		$keys = kaamase_apple_keys();

		if ( ! isset( $keys[ $kid ] ) ) {
			$keys = kaamase_apple_keys( true );
		}

		if ( ! isset( $keys[ $kid ] ) ) {
			return $fail;
		}

		$pem = kaamase_apple_jwk_to_pem( $keys[ $kid ] );

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

		if ( 'https://appleid.apple.com' !== $issuer ) {
			return $fail;
		}

		/*
		 * Who it was issued for.
		 *
		 * Apple sends a single audience for a normal sign in, but the
		 * claim is allowed to be a list, so both shapes are handled
		 * rather than assuming the one seen most often.
		 */
		$audience = isset( $claims['aud'] ) ? $claims['aud'] : '';
		$audience = is_array( $audience ) ? array_map( 'strval', $audience ) : array( (string) $audience );

		if ( empty( array_intersect( $audience, $audiences ) ) ) {
			return $fail;
		}

		/*
		 * Expiry, with a minute of slack for clocks that disagree.
		 * Shared hosting drifts, and somebody refused because the server
		 * is forty seconds fast has no way to understand why.
		 */
		$expires = isset( $claims['exp'] ) ? (int) $claims['exp'] : 0;

		if ( $expires < ( time() - MINUTE_IN_SECONDS ) ) {
			return new WP_Error(
				'kaamase_apple_expired',
				__( 'That sign in took too long. Please tap the Apple button again.', 'kaamase-core' ),
				array( 'status' => 401 )
			);
		}

		if ( empty( $claims['sub'] ) ) {
			return $fail;
		}

		/*
		 * The email is optional here, and that is not an oversight.
		 *
		 * Apple sends it when somebody first authorises and is not
		 * obliged to send it again. A returning person is matched on the
		 * account id below, which never changes, so the address is only
		 * required at the point an account has to be found or made by
		 * it. Refusing a token for want of an email would lock out
		 * exactly the people who have used the app longest.
		 *
		 * Absent and unusable are two different answers, and the first
		 * version of this could not tell them apart. It sanitised first
		 * and rejected only what survived, so a claim Apple did send but
		 * sanitize_email() emptied came out identical to no claim at all
		 * — and the caller reported it as "Apple sent no email address",
		 * sending somebody off to remove the app from their Apple
		 * settings to fix a malformed token. A claim that arrived and
		 * will not parse is a bad token. Only a claim that never arrived
		 * is missing.
		 */
		$email = '';

		if ( isset( $claims['email'] ) && '' !== trim( (string) $claims['email'] ) ) {

			$email = sanitize_email( (string) $claims['email'] );

			if ( ! is_email( $email ) ) {
				return $fail;
			}
		}

		/*
		 * Apple sends these as a real boolean or as the string "true"
		 * depending on the flow, so both are accepted and nothing else.
		 */
		$confirmed = isset( $claims['email_verified'] ) ? $claims['email_verified'] : false;
		$private   = isset( $claims['is_private_email'] ) ? $claims['is_private_email'] : false;

		return array(
			'sub'       => (string) $claims['sub'],
			'email'     => $email,
			'confirmed' => ( true === $confirmed || 'true' === $confirmed ),
			'private'   => ( true === $private || 'true' === $private ),
		);
	}
}


/* ==========================================================================
   3. THE ACCOUNT
   ========================================================================== */

if ( ! function_exists( 'kaamase_apple_find_user' ) ) {
	/**
	 * Whose account this is, if it is anybody's.
	 *
	 * The Apple account id first, because it never changes and because a
	 * returning person may arrive with no email at all. Email second, and
	 * only when Apple says it has confirmed it, since that is the whole
	 * basis on which an address is allowed to match an existing account.
	 *
	 * @since 1.7.0
	 * @param array $claims Verified claims.
	 * @return int User ID, or 0 when nobody holds this identity yet.
	 */
	function kaamase_apple_find_user( $claims ) {

		$found = get_users(
			array(
				'meta_key'   => 'kaamase_apple_sub', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $claims['sub'],      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
				'fields'     => 'ID',
			)
		);

		if ( ! empty( $found ) ) {
			return (int) $found[0];
		}

		if ( '' === $claims['email'] || empty( $claims['confirmed'] ) ) {
			return 0;
		}

		$user = get_user_by( 'email', $claims['email'] );

		return $user ? (int) $user->ID : 0;
	}
}

if ( ! function_exists( 'kaamase_apple_attach' ) ) {
	/**
	 * Remember the Apple account against the user.
	 *
	 * @since 1.7.0
	 * @param int   $user_id User ID.
	 * @param array $claims  Verified claims.
	 * @return void
	 */
	function kaamase_apple_attach( $user_id, $claims ) {

		$user_id = (int) $user_id;

		update_user_meta( $user_id, 'kaamase_apple_private_email', ! empty( $claims['private'] ) ? 1 : 0 );

		if ( get_user_meta( $user_id, 'kaamase_apple_sub', true ) === $claims['sub'] ) {
			return;
		}

		update_user_meta( $user_id, 'kaamase_apple_sub', $claims['sub'] );
		update_user_meta( $user_id, 'kaamase_apple_linked_at', time() );

		/**
		 * Fires when an Apple account is joined to a Kaam Ase account.
		 *
		 * @since 1.7.0
		 * @param int   $user_id User ID.
		 * @param array $claims  Verified claims.
		 */
		do_action( 'kaamase_apple_linked', $user_id, $claims );
	}
}

if ( ! function_exists( 'kaamase_apple_mark_verified' ) ) {
	/**
	 * Treat the account as confirmed, and publish what was waiting.
	 *
	 * Apple has already proved the address, including a forwarding one,
	 * so asking the same person to click a link in it would be theatre.
	 * This does what the emailed link does and kills the link on the way
	 * past, so an old confirmation email cannot be replayed later.
	 *
	 * @since 1.7.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_apple_mark_verified( $user_id ) {

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

if ( ! function_exists( 'kaamase_apple_signup_errors' ) ) {
	/**
	 * Check the answers to the questions Apple cannot answer.
	 *
	 * The same rules and the same wording as the ordinary registration
	 * form, minus the password and the email, which Apple has settled.
	 *
	 * @since 1.7.0
	 * @param array $data        Submitted values, already sanitised.
	 * @param bool  $needs_email Whether the form had to ask for an address.
	 * @return string[] Messages, empty when everything is in order.
	 */
	function kaamase_apple_signup_errors( $data, $needs_email = false ) {

		$errors = array();

		if ( ! in_array( $data['type'], array( 'worker', 'employer' ), true ) ) {
			$errors[] = __( 'Choose whether you are looking for work or looking for workers.', 'kaamase-core' );
		}

		if ( '' === trim( $data['name'] ) ) {
			$errors[] = __( 'Please enter your name.', 'kaamase-core' );
		}

		/*
		 * Only asked for when Apple sent nothing, and checked here the
		 * same way the ordinary registration form checks it, including
		 * the deliberately vague answer for an address that is already
		 * registered. That wording is not politeness: a reply that says
		 * plainly whether an address has an account turns this endpoint
		 * into a way to find out who is on the platform.
		 */
		if ( $needs_email ) {

			if ( '' === $data['email_in'] ) {
				$errors[] = __( 'Please enter your email address.', 'kaamase-core' );
			} elseif ( ! is_email( $data['email'] ) ) {
				$errors[] = __( 'Please enter a working email address.', 'kaamase-core' );
			} elseif ( email_exists( $data['email'] ) ) {
				// Same wording as rest-api.php and registration.php, on purpose.
				$errors[] = __( 'We could not create an account with those details. If you already have one, try signing in.', 'kaamase-core' );
			}
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

if ( ! function_exists( 'kaamase_apple_create' ) ) {
	/**
	 * Build the account, then let them straight in.
	 *
	 * The ordinary account builder does the work, so an Apple account and
	 * a password account are the same shape and the same hooks fire for
	 * both. Two things differ afterwards: the password is random because
	 * nobody will ever type it, and the account is confirmed on the spot.
	 *
	 * @since 1.7.0
	 * @param array $claims Verified claims.
	 * @param array $data   The answers to the remaining questions.
	 * @return int|WP_Error User ID, or an error.
	 */
	function kaamase_apple_create( $claims, $data ) {

		/*
		 * Where the address came from decides two things, and it is the
		 * only thing that decides them.
		 *
		 * Out of the token, Apple has proved it. The confirmation email
		 * is held back — it would arrive asking somebody to confirm an
		 * address Apple confirmed a second earlier, and the link inside
		 * it is deleted moments later anyway — and the account is
		 * confirmed on the spot.
		 *
		 * Typed in by hand, nobody has proved anything at all. Arriving
		 * beside a valid Apple token proves the person holds that Apple
		 * account; it says nothing whatever about the address they typed
		 * next to it. So it is treated exactly as an address typed into
		 * the ordinary registration form: the account is made
		 * unconfirmed, its profile stays a draft, and the confirmation
		 * email goes out and does its ordinary job. Anything kinder than
		 * that would let anybody claim any address.
		 */
		$from_apple = ( '' !== $claims['email'] );
		$email      = $from_apple ? $claims['email'] : $data['email'];

		if ( $from_apple ) {
			add_filter( 'pre_wp_mail', '__return_false', 99 );
		}

		$user_id = kaamase_create_account(
			array(
				'type'     => $data['type'],
				'name'     => $data['name'],
				'email'    => $email,
				'phone'    => $data['phone'],
				'district' => $data['district'],
				'trade'    => $data['trade'],
				'password' => wp_generate_password( 32, true, true ),
			)
		);

		if ( $from_apple ) {
			remove_filter( 'pre_wp_mail', '__return_false', 99 );
		}

		if ( is_wp_error( $user_id ) ) {

			/*
			 * wp_insert_user is the real guard on a duplicate address, and
			 * it runs whatever the check before it concluded, so two
			 * requests arriving together still cannot both get through. Its
			 * own wording says outright that the address is taken, so it
			 * is replaced here with the vague answer used everywhere else.
			 */
			if ( 'existing_user_email' === $user_id->get_error_code() ) {
				return new WP_Error(
					'kaamase_invalid_registration',
					__( 'We could not create an account with those details. If you already have one, try signing in.', 'kaamase-core' ),
					array( 'status' => 400 )
				);
			}

			return $user_id;
		}

		/*
		 * Marked as having no password of their own choosing, so the
		 * account screen can offer to set one rather than sending
		 * somebody to a Forgot link for a password they never had.
		 */
		update_user_meta( $user_id, 'kaamase_password_source', 'apple' );

		/*
		 * Safe on either path because the account is new. Linking the
		 * Apple id to an account that already existed is the takeover
		 * this whole shape is built to prevent, and it cannot happen
		 * here: kaamase_create_account() has just made this account, and
		 * refused outright if the address belonged to anybody.
		 */
		kaamase_apple_attach( $user_id, $claims );

		if ( $from_apple ) {
			kaamase_apple_mark_verified( $user_id );
		}

		return (int) $user_id;
	}
}


/* ==========================================================================
   4. FOR THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_apple_session' ) ) {
	/**
	 * The answer a signed in app expects.
	 *
	 * Deliberately the same shape as /auth/login, /auth/register and
	 * /auth/google, so the app stores an Apple session exactly as it
	 * stores any other.
	 *
	 * @since 1.7.0
	 * @param int    $user_id User ID.
	 * @param string $device  Device label from the request.
	 * @param int    $status  HTTP status.
	 * @return WP_REST_Response
	 */
	function kaamase_apple_session( $user_id, $device, $status = 200 ) {

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

if ( ! function_exists( 'kaamase_rest_apple' ) ) {
	/**
	 * Sign in with an Apple identity token.
	 *
	 * Answers one of two ways. Either the account exists and a session
	 * comes back, or it does not and the app is told what is still
	 * needed. A new account is never created here, because the answers
	 * this platform needs have not been asked yet.
	 *
	 * @since 1.7.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_apple( $request ) {

		if ( kaamase_login_blocked() ) {
			return kaamase_rest_error(
				new WP_Error( 'kaamase_rate_limited', __( 'Too many sign in attempts. Please wait a few minutes and try again.', 'kaamase-core' ), array( 'status' => 429 ) )
			);
		}

		$claims = kaamase_apple_verify( (string) $request->get_param( 'id_token' ) );

		if ( is_wp_error( $claims ) ) {
			kaamase_login_failed();
			return kaamase_rest_error( $claims );
		}

		$user_id = kaamase_apple_find_user( $claims );

		if ( ! $user_id ) {

			/*
			 * Nobody yet, so the app is told what is still needed. An
			 * empty address is part of that answer rather than a refusal.
			 *
			 * This used to be a 409 telling somebody to remove Kaam Ase
			 * from their Apple settings and start again. Apple hands the
			 * address over on the first authorisation and never again, so
			 * anybody who reached this screen once and closed it was
			 * finished with Apple for good — and the reply read like an
			 * instruction while being a dead end. Closing a form is not a
			 * mistake somebody should be locked out for. The form asks for
			 * an address instead.
			 *
			 * email_needed says the same thing as an empty email, for
			 * anything that would rather read a flag than test a string.
			 *
			 * Not a failure, so the login counter is left alone. This is
			 * the ordinary first visit of somebody who has never
			 * registered, and counting it against them would lock out a
			 * household setting up two accounts on one connection.
			 */
			return new WP_REST_Response(
				array(
					'needs_profile' => true,
					'email'         => $claims['email'],
					'email_needed'  => ( '' === $claims['email'] ),
					'name'          => '',
					'private_email' => (bool) $claims['private'],
				),
				200
			);
		}

		kaamase_login_succeeded();
		kaamase_apple_attach( $user_id, $claims );
		kaamase_apple_mark_verified( $user_id );

		return kaamase_apple_session( $user_id, (string) $request->get_param( 'device' ) );
	}
}

if ( ! function_exists( 'kaamase_rest_apple_complete' ) ) {
	/**
	 * Finish a new account started with Apple.
	 *
	 * @since 1.7.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_apple_complete( $request ) {

		if ( ! kaamase_throttle_ok( kaamase_client_key( 'register' ), 5, HOUR_IN_SECONDS ) ) {
			return kaamase_rest_error( kaamase_throttle_error() );
		}

		$claims = kaamase_apple_verify( (string) $request->get_param( 'id_token' ) );

		if ( is_wp_error( $claims ) ) {
			return kaamase_rest_error( $claims );
		}

		/*
		 * Somebody who got here twice, or whose account was made on the
		 * website between the two taps, is signed in rather than told
		 * off. Both are the same person and the second account would be
		 * the bug.
		 */
		$existing = kaamase_apple_find_user( $claims );

		if ( $existing ) {
			kaamase_apple_attach( $existing, $claims );
			kaamase_apple_mark_verified( $existing );
			return kaamase_apple_session( $existing, (string) $request->get_param( 'device' ) );
		}

		$phone_in = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$email_in = trim( (string) $request->get_param( 'email' ) );

		/*
		 * The typed address is kept here, in $data, and deliberately
		 * never written into $claims.
		 *
		 * $claims is what the token said, and kaamase_apple_find_user()
		 * will match an account by the address in it. Putting a typed
		 * address there would be the whole takeover: authorise with your
		 * own Apple id, type somebody else's address, and be handed
		 * their account. Keeping the two apart means an address nobody
		 * has proved can never reach the code that finds accounts — not
		 * by policy, but because it is not in the variable that function
		 * reads.
		 */
		$data = array(
			'type'     => sanitize_key( (string) $request->get_param( 'type' ) ),
			'name'     => sanitize_text_field( (string) $request->get_param( 'name' ) ),
			'district' => kaamase_match_district( (string) $request->get_param( 'district' ) ),
			'trade'    => kaamase_match_trade( (string) $request->get_param( 'trade' ) ),
			'phone_in' => $phone_in,
			'phone'    => kaamase_sanitize_phone( $phone_in ),
			'email_in' => $email_in,
			'email'    => sanitize_email( $email_in ),
			'agreed'   => (bool) $request->get_param( 'agreed' ),
		);

		/*
		 * No fallback name from the token, unlike Google.
		 *
		 * Apple never puts a name in the token at all. It is handed to
		 * the app once, at the first authorisation, and if the app did
		 * not pass it on this call there is nothing anywhere to fall
		 * back to. The form asks for it, so an empty one is a plain
		 * validation error rather than a silent blank.
		 */
		$errors = kaamase_apple_signup_errors( $data, '' === $claims['email'] );

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

		$user_id = kaamase_apple_create( $claims, $data );

		if ( is_wp_error( $user_id ) ) {
			return kaamase_rest_error( $user_id );
		}

		kaamase_login_succeeded();

		return kaamase_apple_session( $user_id, (string) $request->get_param( 'device' ), 201 );
	}
}

if ( ! function_exists( 'kaamase_apple_routes' ) ) {
	/**
	 * Register the two endpoints.
	 *
	 * Registered from this file rather than added to the list in
	 * rest-api.php, so the whole feature arrives and leaves as one file.
	 *
	 * @since 1.7.0
	 * @return void
	 */
	function kaamase_apple_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/apple',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_apple',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/auth/apple/complete',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_apple_complete',
				'permission_callback' => '__return_true',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_apple_routes' );

if ( ! function_exists( 'kaamase_apple_in_reference' ) ) {
	/**
	 * Tell the app whether the button is worth drawing.
	 *
	 * Merged after the cache, for the same reason the version floor is:
	 * switching it off has to take effect now, not in six hours.
	 *
	 * @since 1.7.0
	 * @param WP_REST_Response $response The response.
	 * @param WP_REST_Server   $server   The server.
	 * @param WP_REST_Request  $request  The request.
	 * @return WP_REST_Response
	 */
	function kaamase_apple_in_reference( $response, $server, $request ) {

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

		$data['apple_sign_in'] = kaamase_apple_is_on();

		$response->set_data( $data );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_apple_in_reference', 10, 3 );
