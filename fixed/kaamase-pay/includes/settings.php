<?php
/**
 * Gateway settings.
 *
 * Razorpay credentials, and the switch that decides whether anybody is
 * being charged at all.
 *
 * The switch is separate from the plugin being active on purpose. There
 * is a period where you want the plans configured, the keys in, the
 * webhook registered and a test payment made, while no ordinary person
 * sees a price anywhere. Deactivating the plugin to achieve that would
 * also tear down the webhook, which Razorpay would then retry against a
 * dead endpoint. So: active means installed and ready, on means selling.
 *
 * @package KaamasePay
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Settings that may be set in wp-config.php instead of the database.
 *
 * Why these can live in wp-config
 * ------------------------------
 * These four are live payment credentials. Held in an option they sit
 * in the database, in every backup of it, and in the output of any
 * tool that dumps options, which is a routine debugging step. A
 * constant in wp-config.php keeps them out of all three.
 *
 * A constant always wins over the stored value, and the settings screen
 * says so, so nobody types a new secret into a field that cannot take
 * effect.
 *
 * @since 1.4.3
 * @return string[] Constant name keyed by setting name.
 */
function kaamase_pay_setting_constants() {
	return array(
		'key_id'         => 'KAAMASE_PAY_KEY_ID',
		'key_secret'     => 'KAAMASE_PAY_KEY_SECRET',
		'webhook_secret' => 'KAAMASE_PAY_WEBHOOK_SECRET',
		'store_secret'   => 'KAAMASE_PAY_STORE_SECRET',
	);
}

/**
 * The wp-config value for a setting, when there is one.
 *
 * @since 1.4.3
 * @param string $key Setting name.
 * @return string Value, or an empty string when no constant is set.
 */
function kaamase_pay_setting_from_constant( $key ) {

	$constants = kaamase_pay_setting_constants();

	if ( ! isset( $constants[ $key ] ) || ! defined( $constants[ $key ] ) ) {
		return '';
	}

	return (string) constant( $constants[ $key ] );
}

/**
 * Whether a setting is fixed in wp-config.php.
 *
 * @since 1.4.3
 * @param string $key Setting name.
 * @return bool
 */
function kaamase_pay_setting_is_locked( $key ) {
	return '' !== kaamase_pay_setting_from_constant( $key );
}

/**
 * Read a setting.
 *
 * wp-config.php first, then the stored option.
 *
 * @since 1.0.0
 * @param string $key     Setting name.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function kaamase_pay_setting( $key, $default = '' ) {

	$constant = kaamase_pay_setting_from_constant( $key );

	if ( '' !== $constant ) {
		return $constant;
	}

	$settings = (array) get_option( KAAMASE_PAY_SETTINGS, array() );

	return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
}

/**
 * Keep the settings out of the autoloaded option set.
 *
 * The Options API autoloads by default, so this option, holding the
 * live key secret and both webhook secrets, was read into memory on
 * every request on the site including every front end page view. That
 * is not an exposure on its own, but it puts payment credentials into
 * the output of every "what is in my autoload" audit, which is a normal
 * thing to run on a slow site.
 *
 * update_option() rewrites the autoload flag on an existing option, and
 * skips the write entirely once it is already set, so this is cheap to
 * call and safe to leave in place.
 *
 * @since 1.4.3
 * @return void
 */
function kaamase_pay_unautoload_settings() {

	if ( get_option( 'kaamase_pay_autoload_fixed' ) ) {
		return;
	}

	$settings = get_option( KAAMASE_PAY_SETTINGS, null );

	if ( null !== $settings ) {
		update_option( KAAMASE_PAY_SETTINGS, $settings, false );
	}

	update_option( 'kaamase_pay_autoload_fixed', 1, false );
}
add_action( 'admin_init', 'kaamase_pay_unautoload_settings', 5 );

/**
 * Whether the gateway has everything it needs to take a payment.
 *
 * @since 1.0.0
 * @return bool
 */
function kaamase_pay_is_configured() {

	return '' !== kaamase_pay_setting( 'key_id' ) && '' !== kaamase_pay_setting( 'key_secret' );
}

/**
 * Whether people are being charged right now.
 *
 * Both conditions, because a switch turned on without keys behind it
 * produces a checkout that fails after somebody has decided to buy,
 * which is worse than never offering.
 *
 * @since 1.0.0
 * @return bool
 */
function kaamase_pay_is_live() {

	return kaamase_pay_is_configured() && (bool) kaamase_pay_setting( 'live', 0 );
}

/**
 * Answer the core plugin's question.
 *
 * @since 1.0.0
 * @return bool
 */
function kaamase_pay_charging_is_on() {
	return kaamase_pay_is_live();
}
add_filter( 'kaamase_charging_is_on', 'kaamase_pay_charging_is_on' );


/* ==========================================================================
   THE SCREEN
   ========================================================================== */

/**
 * Add the payments screens under Kaam Ase.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_menu() {

	add_submenu_page(
		'kaamase',
		__( 'Payments', 'kaamase-pay' ),
		__( 'Payments', 'kaamase-pay' ),
		'manage_options',
		'kaamase-pay',
		'kaamase_pay_settings_page'
	);

	add_submenu_page(
		'kaamase',
		__( 'Plans and prices', 'kaamase-pay' ),
		__( 'Plans and prices', 'kaamase-pay' ),
		'manage_options',
		'kaamase-pay-plans',
		'kaamase_pay_plans_page'
	);
}
add_action( 'admin_menu', 'kaamase_pay_menu', 21 );

/**
 * Register the settings.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_register_settings() {

	register_setting(
		'kaamase_pay',
		KAAMASE_PAY_SETTINGS,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kaamase_pay_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'kaamase_pay_register_settings' );

/**
 * The note shown under a field whose value is fixed in wp-config.php.
 *
 * Without this an administrator types a new secret into a field that
 * cannot take effect, saves, sees no error, and believes the key was
 * changed. On a payment screen that is the worst kind of quiet failure.
 *
 * @since 1.4.3
 * @param string $key Setting name.
 * @return string Markup, or an empty string when the setting is not locked.
 */
function kaamase_pay_locked_note( $key ) {

	if ( ! kaamase_pay_setting_is_locked( $key ) ) {
		return '';
	}

	$constants = kaamase_pay_setting_constants();

	return sprintf(
		'<p class="description"><strong>%1$s</strong> %2$s <code>%3$s</code></p>',
		esc_html__( 'Set in wp-config.php.', 'kaamase-pay' ),
		esc_html__( 'This field is ignored. Change it in', 'kaamase-pay' ),
		esc_html( $constants[ $key ] )
	);
}

/**
 * Clean the settings before saving.
 *
 * A blank secret leaves the stored one alone. Password fields are
 * rendered empty so a saved secret is never printed into a page, and
 * without this rule every save would wipe them.
 *
 * @since 1.0.0
 * @param array $input Submitted values.
 * @return array
 */
function kaamase_pay_sanitize_settings( $input ) {

	$current = (array) get_option( KAAMASE_PAY_SETTINGS, array() );
	$input   = (array) $input;

	$out = array(
		'key_id'   => sanitize_text_field( $input['key_id'] ?? '' ),
		'currency' => strtoupper( sanitize_text_field( $input['currency'] ?? 'INR' ) ),
		'live'     => empty( $input['live'] ) ? 0 : 1,
	);

	$out['in_app_ios']     = empty( $input['in_app_ios'] ) ? 0 : 1;
	$out['in_app_android'] = empty( $input['in_app_android'] ) ? 0 : 1;

	foreach ( array( 'key_secret', 'webhook_secret', 'store_secret' ) as $secret ) {

		$given = sanitize_text_field( $input[ $secret ] ?? '' );

		$out[ $secret ] = '' === $given ? (string) ( $current[ $secret ] ?? '' ) : $given;
	}

	/*
	 * A value fixed in wp-config.php is never written to the database.
	 *
	 * Storing it as well would put the secret back in the option this
	 * change exists to keep it out of, and would leave a stale copy
	 * behind on the day the constant is removed.
	 */
	foreach ( array_keys( kaamase_pay_setting_constants() ) as $key ) {

		if ( kaamase_pay_setting_is_locked( $key ) ) {
			$out[ $key ] = '';
		}
	}

	return $out;
}

/**
 * Render the settings screen.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$live       = kaamase_pay_is_live();
	$configured = kaamase_pay_is_configured();
	?>
	<div class="wrap">

		<h1><?php esc_html_e( 'Payments', 'kaamase-pay' ); ?></h1>

		<?php if ( $live ) : ?>
			<div class="notice notice-warning inline">
				<p><strong><?php esc_html_e( 'People are being charged.', 'kaamase-pay' ); ?></strong>
				<?php esc_html_e( 'Plans are visible and payments are being taken. Switching this off leaves everybody on the free allowances and takes nothing away that they already paid for: paid access keeps working until the date it was bought to.', 'kaamase-pay' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-info inline">
				<p><?php esc_html_e( 'Nothing is being charged for. Plans are hidden and everybody is on the free allowances. You can set all of this up now and switch it on whenever you are ready.', 'kaamase-pay' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php">

			<?php settings_fields( 'kaamase_pay' ); ?>

			<table class="form-table" role="presentation">

				<tr>
					<th scope="row"><?php esc_html_e( 'Charge people', 'kaamase-pay' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[live]"
								value="1"
								<?php checked( (int) kaamase_pay_setting( 'live', 0 ), 1 ); ?>
								<?php disabled( ! $configured ); ?>>
							<?php esc_html_e( 'Show plans and take payments', 'kaamase-pay' ); ?>
						</label>

						<?php if ( ! $configured ) : ?>
							<p class="description">
								<?php esc_html_e( 'Add your Razorpay keys first. A switch turned on with no keys behind it gives somebody a checkout that fails after they have decided to buy, which is worse than never offering.', 'kaamase-pay' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="kaamase_pay_key_id"><?php esc_html_e( 'Razorpay Key ID', 'kaamase-pay' ); ?></label>
					</th>
					<td>
						<input type="text"
							class="regular-text"
							id="kaamase_pay_key_id"
							name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[key_id]"
							<?php disabled( kaamase_pay_setting_is_locked( 'key_id' ) ); ?>
							value="<?php echo esc_attr( (string) kaamase_pay_setting( 'key_id' ) ); ?>">
						<?php echo wp_kses_post( kaamase_pay_locked_note( 'key_id' ) ); ?>
						<p class="description"><?php esc_html_e( 'Razorpay dashboard, Account and Settings, API Keys. Starts rzp_test for testing and rzp_live when you are ready.', 'kaamase-pay' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="kaamase_pay_key_secret"><?php esc_html_e( 'Razorpay Key Secret', 'kaamase-pay' ); ?></label>
					</th>
					<td>
						<input type="password"
							class="regular-text"
							id="kaamase_pay_key_secret"
							name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[key_secret]"
							<?php disabled( kaamase_pay_setting_is_locked( 'key_secret' ) ); ?>
							autocomplete="new-password"
							value="">
						<?php echo wp_kses_post( kaamase_pay_locked_note( 'key_secret' ) ); ?>
						<p class="description">
							<?php
							echo '' !== kaamase_pay_setting( 'key_secret' )
								? esc_html__( 'A secret is saved. Leave this blank to keep it, or type a new one to replace it.', 'kaamase-pay' )
								: esc_html__( 'Shown once by Razorpay when you create the key. Never leaves the server.', 'kaamase-pay' );
							?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="kaamase_pay_webhook_secret"><?php esc_html_e( 'Webhook secret', 'kaamase-pay' ); ?></label>
					</th>
					<td>
						<input type="password"
							class="regular-text"
							id="kaamase_pay_webhook_secret"
							name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[webhook_secret]"
							<?php disabled( kaamase_pay_setting_is_locked( 'webhook_secret' ) ); ?>
							autocomplete="new-password"
							value="">
						<?php echo wp_kses_post( kaamase_pay_locked_note( 'webhook_secret' ) ); ?>

						<p class="description">
							<?php esc_html_e( 'Any string you choose. Put the same one into Razorpay when you create the webhook. Send it to this address:', 'kaamase-pay' ); ?>
						</p>

						<p><code><?php echo esc_url( rest_url( 'kaamase-pay/v1/webhook' ) ); ?></code></p>

						<p class="description">
							<?php esc_html_e( 'Subscribe it to these events: payment.captured, subscription.charged, subscription.cancelled, subscription.halted. Without the webhook a monthly plan takes the first payment and then quietly stops extending, which nobody notices until somebody complains.', 'kaamase-pay' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="kaamase_pay_currency"><?php esc_html_e( 'Currency', 'kaamase-pay' ); ?></label>
					</th>
					<td>
						<input type="text"
							class="small-text"
							id="kaamase_pay_currency"
							name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[currency]"
							value="<?php echo esc_attr( (string) kaamase_pay_setting( 'currency', 'INR' ) ); ?>">
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Sell inside the apps', 'kaamase-pay' ); ?></th>
					<td>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[in_app_ios]"
								value="1"
								<?php checked( (int) kaamase_pay_setting( 'in_app_ios', 0 ), 1 ); ?>>
							<?php esc_html_e( 'iPhone, through Apple', 'kaamase-pay' ); ?>
						</label>
						<br>
						<label>
							<input type="checkbox"
								name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[in_app_android]"
								value="1"
								<?php checked( (int) kaamase_pay_setting( 'in_app_android', 0 ), 1 ); ?>>
							<?php esc_html_e( 'Android, through Google Play', 'kaamase-pay' ); ?>
						</label>

						<p class="description" style="max-width:40em">
							<?php esc_html_e( 'Leave both off until the products exist in that store and the webhook below is connected. With them off the app shows no way to buy at all, which is the safe direction: an app that takes money it cannot honour is worse than one that sells nothing.', 'kaamase-pay' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="kaamase_pay_store_secret"><?php esc_html_e( 'RevenueCat secret', 'kaamase-pay' ); ?></label>
					</th>
					<td>
						<input type="password"
							class="regular-text"
							id="kaamase_pay_store_secret"
							name="<?php echo esc_attr( KAAMASE_PAY_SETTINGS ); ?>[store_secret]"
							<?php disabled( kaamase_pay_setting_is_locked( 'store_secret' ) ); ?>
							autocomplete="new-password"
							value="">
						<?php echo wp_kses_post( kaamase_pay_locked_note( 'store_secret' ) ); ?>

						<p class="description">
							<?php esc_html_e( 'Any string you choose. Put the same one into RevenueCat as the Authorization header on the webhook, and send it here:', 'kaamase-pay' ); ?>
						</p>

						<p><code><?php echo esc_url( rest_url( 'kaamase-pay/v1/store' ) ); ?></code></p>
					</td>
				</tr>

			</table>

			<?php submit_button(); ?>

		</form>

		<h2><?php esc_html_e( 'What this is pointed at', 'kaamase-pay' ); ?></h2>

		<?php
		/*
		 * Shown because the alternative is guessing.
		 *
		 * The plans page is worked out rather than configured, and when
		 * it worked it out wrongly there was no way to see that: the
		 * only symptom was Choose landing somewhere unexpected. One line
		 * of truth here turns a mystery into a fact.
		 */
		$found = kaamase_pay_plans_url();
		?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Plans page', 'kaamase-pay' ); ?></th>
				<td>
					<?php if ( $found ) : ?>
						<code><?php echo esc_url( $found ); ?></code>
						<p class="description">
							<?php esc_html_e( 'This is where Choose sends people back to. If that is not the page holding the plans, the page it should be on is missing the [kaamase_plans] shortcode.', 'kaamase-pay' ); ?>
						</p>
					<?php else : ?>
						<span style="color:#b32d2e">
							<?php esc_html_e( 'No page found.', 'kaamase-pay' ); ?>
						</span>
						<p class="description">
							<?php esc_html_e( 'Make a page containing the shortcode [kaamase_plans] and publish it. Until then nothing can be bought and Choose has nowhere sensible to return to.', 'kaamase-pay' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Ready to take money', 'kaamase-pay' ); ?></th>
				<td>
					<?php
					$blockers = array();

					if ( '' === kaamase_pay_setting( 'key_id' ) || '' === kaamase_pay_setting( 'key_secret' ) ) {
						$blockers[] = __( 'Razorpay keys are missing.', 'kaamase-pay' );
					}

					if ( ! kaamase_pay_setting( 'live', 0 ) ) {
						$blockers[] = __( 'Charge people is not ticked.', 'kaamase-pay' );
					}

					$active_plans = function_exists( 'kaamase_pay_active_plans' ) ? kaamase_pay_active_plans() : array();

					if ( empty( $active_plans ) ) {
						$blockers[] = __( 'No plan is switched on.', 'kaamase-pay' );
					}

					if ( ! $found ) {
						$blockers[] = __( 'There is no page holding the plans.', 'kaamase-pay' );
					}

					if ( empty( $blockers ) ) {
						echo '<strong style="color:#0f8a3c">' . esc_html__( 'Yes.', 'kaamase-pay' ) . '</strong>';
					} else {
						echo '<strong style="color:#b32d2e">' . esc_html__( 'Not yet:', 'kaamase-pay' ) . '</strong><ul style="list-style:disc;margin-left:20px">';

						foreach ( $blockers as $blocker ) {
							echo '<li>' . esc_html( $blocker ) . '</li>';
						}

						echo '</ul>';
					}
					?>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Recent payments', 'kaamase-pay' ); ?></h2>

		<?php kaamase_pay_render_recent(); ?>

	</div>
	<?php
}

/**
 * A short list of what has come in.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_render_recent() {

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows = $wpdb->get_results( 'SELECT * FROM ' . kaamase_pay_table() . ' ORDER BY id DESC LIMIT 25' );

	if ( empty( $rows ) ) {
		echo '<p>' . esc_html__( 'Nothing yet.', 'kaamase-pay' ) . '</p>';

		return;
	}

	echo '<table class="wp-list-table widefat fixed striped"><thead><tr>'
		. '<th>' . esc_html__( 'When', 'kaamase-pay' ) . '</th>'
		. '<th>' . esc_html__( 'Person', 'kaamase-pay' ) . '</th>'
		. '<th>' . esc_html__( 'Plan', 'kaamase-pay' ) . '</th>'
		. '<th>' . esc_html__( 'Amount', 'kaamase-pay' ) . '</th>'
		. '<th>' . esc_html__( 'Status', 'kaamase-pay' ) . '</th>'
		. '</tr></thead><tbody>';

	foreach ( $rows as $row ) {

		$user = get_userdata( (int) $row->user_id );

		printf(
			'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td><td>%5$s</td></tr>',
			esc_html( (string) $row->created_at ),
			esc_html( $user ? $user->display_name : '#' . (int) $row->user_id ),
			esc_html( $row->plan . ' / ' . $row->period ),
			esc_html( $row->currency . ' ' . number_format_i18n( (float) $row->amount, 2 ) ),
			esc_html( (string) $row->status )
		);
	}

	echo '</tbody></table>';
}
