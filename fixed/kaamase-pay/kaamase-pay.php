<?php
/**
 * Plugin Name: Kaam Ase Payments
 * Description: Razorpay payments for Kaam Ase. One time packs, monthly and yearly subscriptions, priced from the admin screen. Deactivating this plugin returns every account to the free allowances in Kaam Ase Core, without locking anybody out.
 * Version:     1.4.2
 * Requires PHP: 8.0
 * Author:      Nagaland Me
 * Text Domain: kaamase-pay
 * Domain Path: /languages
 *
 * What this plugin is responsible for
 * ----------------------------------
 * Selling more of the allowances that Kaam Ase Core already meters, and
 * nothing else. It owns prices, plans, Razorpay, and the record of who
 * paid for what. It does not own limits, and it deliberately cannot
 * enforce one.
 *
 * The whole contract with the core plugin is two filters:
 *
 *   kaamase_paid_allowance  how much extra this person has bought
 *   kaamase_charging_is_on  whether there is any way to pay at all
 *
 * That is why deactivating this plugin is safe. The filters lose their
 * listeners, the extra allowance becomes zero, and every account falls
 * back to the free numbers on the Limits screen. Nobody is signed out,
 * no page breaks, and nothing has to be undone in code. A payment system
 * you cannot switch off is one you cannot experiment with, and at this
 * stage of a marketplace you will want to experiment.
 *
 * One storage model for everything
 * --------------------------------
 * A one time pack and a subscription do the same thing here: they raise
 * somebody's allowance until a date. A pack sets that date once. A
 * subscription pushes it forward each time Razorpay charges. So there is
 * one thing to read when deciding what somebody is allowed, one thing to
 * write when money arrives, and expiry needs no scheduled task, because
 * a date in the past simply stops counting.
 *
 * @package KaamasePay
 */

defined( 'ABSPATH' ) || exit;

define( 'KAAMASE_PAY_VERSION', '1.4.2' );
define( 'KAAMASE_PAY_FILE', __FILE__ );
define( 'KAAMASE_PAY_DIR', plugin_dir_path( __FILE__ ) );
define( 'KAAMASE_PAY_DB_VERSION', '1.1' );

/** Option holding the gateway settings. */
define( 'KAAMASE_PAY_SETTINGS', 'kaamase_pay_settings' );

/** Option holding the plans. */
define( 'KAAMASE_PAY_PLANS', 'kaamase_pay_plans' );

/** User meta: which plan they are on. */
define( 'KAAMASE_PAY_PLAN_KEY', '_kaamase_pay_plan' );

/** User meta: when their access runs out, as a timestamp. */
define( 'KAAMASE_PAY_EXPIRES_KEY', '_kaamase_pay_expires' );

/** User meta: the Razorpay subscription behind it, when there is one. */
define( 'KAAMASE_PAY_SUB_KEY', '_kaamase_pay_subscription' );


/**
 * Load the plugin, but only when the platform it extends is there.
 *
 * Checked on plugins_loaded rather than at file scope, because plugin
 * load order is alphabetical and this file would otherwise run before
 * kaamase-core had defined anything.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_boot() {

	if ( ! function_exists( 'kaamase_allowance' ) ) {

		add_action( 'admin_notices', 'kaamase_pay_missing_core_notice' );

		return;
	}

	$files = array(
		'includes/settings.php',
		'includes/razorpay.php',
		'includes/plans.php',
		'includes/access.php',
		'includes/checkout.php',
		'includes/account.php',
		'includes/webhook.php',
		'includes/store-webhook.php',
		'includes/subscribers.php',
	);

	foreach ( $files as $file ) {

		$path = KAAMASE_PAY_DIR . $file;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
add_action( 'plugins_loaded', 'kaamase_pay_boot', 20 );

/**
 * Load translations.
 *
 * The Text Domain header was declared and nothing ever loaded it, so
 * every string in this plugin was untranslatable no matter what
 * anybody put in a .po file. The other two packages both did this and
 * this one was missed.
 *
 * @since 1.4.4
 * @return void
 */
function kaamase_pay_textdomain() {
	load_plugin_textdomain(
		'kaamase-pay',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'kaamase_pay_textdomain', 0 );

/**
 * Say plainly why nothing is happening.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_missing_core_notice() {

	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Kaam Ase Payments needs Kaam Ase Core, version 1.2 or newer, and is doing nothing until that is active.', 'kaamase-pay' )
		. '</p></div>';
}


/* ==========================================================================
   TABLES

   Two, and both are small. Payments is the record of money, which has to
   survive anything. Events exists only so a webhook Razorpay sends twice
   is not counted twice, which is a thing that happens routinely rather
   than rarely.
   ========================================================================== */

/**
 * Create or update the tables.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_install() {

	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset  = $wpdb->get_charset_collate();
	$payments = $wpdb->prefix . 'kaamase_payments';
	$events   = $wpdb->prefix . 'kaamase_pay_events';

	dbDelta(
		"CREATE TABLE {$payments} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			plan VARCHAR(40) NOT NULL,
			period VARCHAR(12) NOT NULL DEFAULT 'once',
			amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			currency VARCHAR(6) NOT NULL DEFAULT 'INR',
			status VARCHAR(20) NOT NULL DEFAULT 'created',
			order_id VARCHAR(64) NULL,
			payment_id VARCHAR(64) NULL,
			subscription_id VARCHAR(64) NULL,
			note VARCHAR(191) NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY status (status),
			KEY order_id (order_id),
			KEY payment_id (payment_id),
			KEY subscription_id (subscription_id)
		) {$charset};"
	);

	dbDelta(
		"CREATE TABLE {$events} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id VARCHAR(80) NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY event_id (event_id)
		) {$charset};"
	);

	update_option( 'kaamase_pay_db_version', KAAMASE_PAY_DB_VERSION );
}
register_activation_hook( __FILE__, 'kaamase_pay_install' );

/**
 * Run the installer after an update that changed the tables.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_maybe_upgrade() {

	if ( get_option( 'kaamase_pay_db_version' ) === KAAMASE_PAY_DB_VERSION ) {
		return;
	}

	kaamase_pay_install();
}
add_action( 'admin_init', 'kaamase_pay_maybe_upgrade' );

/**
 * The payments table name.
 *
 * @since 1.0.0
 * @return string
 */
function kaamase_pay_table() {

	global $wpdb;

	return $wpdb->prefix . 'kaamase_payments';
}

/**
 * The webhook events table name.
 *
 * @since 1.0.0
 * @return string
 */
function kaamase_pay_events_table() {

	global $wpdb;

	return $wpdb->prefix . 'kaamase_pay_events';
}
