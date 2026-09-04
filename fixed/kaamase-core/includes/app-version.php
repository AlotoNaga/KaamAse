<?php
/**
 * The oldest app version allowed in.
 *
 * The app asks /reference on every launch and already caches it. Two
 * optional fields there let this site turn away a version that has a
 * fault in it, without waiting on a store review.
 *
 * Why this is a screen and not a constant
 * ---------------------------------------
 * It is reached for when something has gone wrong, which is exactly
 * when nobody should be editing PHP over a hotel connection. The owner
 * sets it, and can lift it, without help.
 *
 * Why it is never cached
 * ----------------------
 * /reference is held in a transient for six hours. A floor written into
 * that would take six hours to arrive, which is bad, and six hours to
 * REMOVE, which is far worse: one mistyped number and every phone is
 * locked out until the afternoon. So these two fields are merged into
 * the answer after the cache is read, and are always live.
 *
 * Off by default, and off means the fields are absent from the answer
 * rather than empty. The app lets everybody in when they are missing.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHAT IS SET
   ========================================================================== */

if ( ! function_exists( 'kaamase_app_version_clean' ) ) {
	/**
	 * A version string, or nothing.
	 *
	 * Digits and dots only. Anything else is somebody's typing, and a
	 * gate that acts on a typo shuts out people it should not.
	 *
	 * @since 1.5.0
	 * @param string $value Raw value.
	 * @return string Clean version, or an empty string.
	 */
	function kaamase_app_version_clean( $value ) {

		$value = trim( (string) $value );

		if ( '' === $value || ! preg_match( '/^\d+(\.\d+){0,3}$/', $value ) ) {
			return '';
		}

		return $value;
	}
}

if ( ! function_exists( 'kaamase_app_minimum_versions' ) ) {
	/**
	 * The oldest allowed version per platform.
	 *
	 * @since 1.5.0
	 * @return array<string,string> Keyed ios and android. Empty means no floor.
	 */
	function kaamase_app_minimum_versions() {

		$versions = array(
			'ios'     => kaamase_app_version_clean( get_option( 'kaamase_app_min_ios', '' ) ),
			'android' => kaamase_app_version_clean( get_option( 'kaamase_app_min_android', '' ) ),
		);

		/**
		 * Filter the oldest allowed app version per platform.
		 *
		 * The way to lift a gate from code if the screen cannot be
		 * reached. Return empty strings to let everybody in.
		 *
		 * @since 1.5.0
		 * @param array<string,string> $versions Keyed ios and android.
		 */
		return (array) apply_filters( 'kaamase_app_minimum_versions', $versions );
	}
}

if ( ! function_exists( 'kaamase_app_update_message' ) ) {
	/**
	 * What to say on the screen that stops them.
	 *
	 * @since 1.5.0
	 * @return string
	 */
	function kaamase_app_update_message() {

		$message = trim( (string) get_option( 'kaamase_app_update_message', '' ) );

		/**
		 * Filter the message shown when an app is too old.
		 *
		 * @since 1.5.0
		 * @param string $message Message.
		 */
		return (string) apply_filters( 'kaamase_app_update_message', $message );
	}
}


/* ==========================================================================
   2. TELLING THE APP

   After the cache, never inside it. See the note at the top.
   ========================================================================== */

if ( ! function_exists( 'kaamase_app_version_in_reference' ) ) {
	/**
	 * Merge the floor into the reference answer.
	 *
	 * @since 1.5.0
	 * @param WP_REST_Response $response The response.
	 * @param WP_REST_Server   $server   The server.
	 * @param WP_REST_Request  $request  The request.
	 * @return WP_REST_Response
	 */
	function kaamase_app_version_in_reference( $response, $server, $request ) {

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

		$versions = kaamase_app_minimum_versions();
		$floor    = array();

		/*
		 * Cleaned again after the filter, not just before it. A filter
		 * returning an integer, an array or a beta string would
		 * otherwise put "1", "Array" or "2.0.7-beta" on the wire, and
		 * the far end would have to defend against our output. What
		 * leaves here is a version or nothing at all.
		 */
		foreach ( array( 'ios', 'android' ) as $platform ) {

			$version = isset( $versions[ $platform ] ) && is_string( $versions[ $platform ] )
				? kaamase_app_version_clean( $versions[ $platform ] )
				: '';

			if ( '' !== $version ) {
				$floor[ $platform ] = $version;
			}
		}

		/*
		 * Nothing set means the keys are left out altogether, not sent
		 * empty. The app treats a missing field as "let everybody in",
		 * and an absent key cannot be misread as a floor of zero.
		 */
		if ( empty( $floor ) ) {
			$response->set_data( $data );
			return $response;
		}

		$data['minimum_version'] = $floor;

		$message = kaamase_app_update_message();

		/*
		 * Capped. This is drawn on a screen somebody is stuck behind,
		 * on the smallest phone we build for, and an essay there is a
		 * wall with no way past it.
		 */
		if ( '' !== $message ) {
			$data['update_message'] = mb_substr( wp_strip_all_tags( $message ), 0, 200 );
		}

		$response->set_data( $data );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_app_version_in_reference', 10, 3 );


/* ==========================================================================
   3. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_app_version_menu' ) ) {
	/**
	 * Add the screen under Settings.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_app_version_menu() {

		add_options_page(
			__( 'App version', 'kaamase-core' ),
			__( 'App version', 'kaamase-core' ),
			'manage_options',
			'kaamase-app-version',
			'kaamase_app_version_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_app_version_menu' );

if ( ! function_exists( 'kaamase_app_version_save' ) ) {
	/**
	 * Save, or lift.
	 *
	 * @since 1.5.0
	 * @return string A message to show, or an empty string.
	 */
	function kaamase_app_version_save() {

		if ( ! current_user_can( 'manage_options' ) || empty( $_POST['kaamase_app_version'] ) ) {
			return '';
		}

		check_admin_referer( 'kaamase_app_version' );

		/*
		 * Lifting is its own button rather than "clear both boxes and
		 * save". This gets pressed by somebody who has just shut out
		 * every user by mistake, and at that moment one obvious button
		 * beats two fields and a save.
		 */
		if ( isset( $_POST['kaamase_app_version_off'] ) ) {

			delete_option( 'kaamase_app_min_ios' );
			delete_option( 'kaamase_app_min_android' );

			return __( 'The gate is off. Every version can sign in again.', 'kaamase-core' );
		}

		$ios     = kaamase_app_version_clean( wp_unslash( $_POST['kaamase_app_min_ios'] ?? '' ) );
		$android = kaamase_app_version_clean( wp_unslash( $_POST['kaamase_app_min_android'] ?? '' ) );
		$message = sanitize_text_field( wp_unslash( $_POST['kaamase_app_update_message'] ?? '' ) );

		update_option( 'kaamase_app_min_ios', $ios );
		update_option( 'kaamase_app_min_android', $android );
		update_option( 'kaamase_app_update_message', $message );

		$typed = trim( (string) wp_unslash( $_POST['kaamase_app_min_ios'] ?? '' ) )
			. trim( (string) wp_unslash( $_POST['kaamase_app_min_android'] ?? '' ) );

		if ( '' !== $typed && '' === $ios . $android ) {
			return __( 'That is not a version number, so nothing was set. Use digits and dots only, like 2.0.7.', 'kaamase-core' );
		}

		return __( 'Saved.', 'kaamase-core' );
	}
}

if ( ! function_exists( 'kaamase_app_version_page' ) ) {
	/**
	 * Draw the screen.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_app_version_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		$notice   = kaamase_app_version_save();
		$versions = kaamase_app_minimum_versions();
		$message  = kaamase_app_update_message();
		$on       = ! empty( $versions['ios'] ) || ! empty( $versions['android'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'App version', 'kaamase-core' ); ?></h1>

			<?php if ( '' !== $notice ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
			<?php endif; ?>

			<p>
				<?php
				esc_html_e(
					'Stops an old version of the app being used. Somebody on a version below the one you set is shown an Update button and cannot go further until they update. Leave both boxes empty and nobody is stopped.',
					'kaamase-core'
				);
				?>
			</p>

			<?php if ( $on ) : ?>
				<div class="notice notice-warning">
					<p>
						<strong><?php esc_html_e( 'The gate is on right now.', 'kaamase-core' ); ?></strong>
						<?php
						printf(
							/* translators: 1: iPhone version or a dash, 2: Android version or a dash. */
							esc_html__( 'iPhone: %1$s. Android: %2$s. Anybody below those is being stopped.', 'kaamase-core' ),
							esc_html( $versions['ios'] ? $versions['ios'] : '—' ),
							esc_html( $versions['android'] ? $versions['android'] : '—' )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="notice notice-error inline">
				<p>
					<strong><?php esc_html_e( 'Read this before you set a number.', 'kaamase-core' ); ?></strong><br>
					<?php
					esc_html_e(
						'Type a version that is already live in the App Store and on Google Play. If you type one that has not been released yet, every single user is locked out with no way back in until you come here and turn it off, and the stores have nothing for them to update to.',
						'kaamase-core'
					);
					?>
				</p>
			</div>

			<form method="post">
				<?php wp_nonce_field( 'kaamase_app_version' ); ?>
				<input type="hidden" name="kaamase_app_version" value="1">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="kaamase_app_min_ios"><?php esc_html_e( 'Oldest iPhone version', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_app_min_ios" id="kaamase_app_min_ios" type="text"
								class="regular-text" value="<?php echo esc_attr( $versions['ios'] ); ?>"
								placeholder="<?php esc_attr_e( 'Empty means nobody is stopped', 'kaamase-core' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="kaamase_app_min_android"><?php esc_html_e( 'Oldest Android version', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_app_min_android" id="kaamase_app_min_android" type="text"
								class="regular-text" value="<?php echo esc_attr( $versions['android'] ); ?>"
								placeholder="<?php esc_attr_e( 'Empty means nobody is stopped', 'kaamase-core' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="kaamase_app_update_message"><?php esc_html_e( 'What to tell them', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input name="kaamase_app_update_message" id="kaamase_app_update_message" type="text"
								class="large-text" value="<?php echo esc_attr( $message ); ?>"
								placeholder="<?php esc_attr_e( 'Please update Kaam Ase to carry on.', 'kaamase-core' ); ?>">
							<p class="description">
								<?php esc_html_e( 'Optional. Shown above the Update button.', 'kaamase-core' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save', 'kaamase-core' ), 'primary', 'submit', false ); ?>

				<?php if ( $on ) : ?>
					<button type="submit" name="kaamase_app_version_off" value="1"
						class="button button-secondary" style="margin-left:12px;">
						<?php esc_html_e( 'Turn the gate off', 'kaamase-core' ); ?>
					</button>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}
}
