<?php
/**
 * The webhook.
 *
 * Razorpay telling us what happened, rather than the browser doing it.
 *
 * Why this file is not optional
 * -----------------------------
 * A subscription only involves the browser once, on the day somebody
 * signs up. Every charge after that happens between Razorpay and the
 * customer's bank with nobody watching. Without this endpoint a monthly
 * plan takes the first payment, extends access by thirty days, and then
 * silently stops extending. Nothing errors. It looks fine for a month.
 * Then a paying customer loses their allowance and has to be the one to
 * tell you.
 *
 * On being told twice
 * -------------------
 * Razorpay retries a webhook it did not get a clean answer to, so the
 * same event genuinely does arrive more than once. Handled the crude and
 * reliable way: every event ID is written into a table with a unique key
 * first, and a duplicate insert fails, and a failed insert means stop.
 * Checking whether it exists and then inserting would leave a gap
 * between the two where a retry can slip through and grant a second
 * month for one payment.
 *
 * @package KaamasePay
 * @version 1.0.1
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Register the endpoint.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_pay_webhook_route() {

	register_rest_route(
		'kaamase-pay/v1',
		'/webhook',
		array(
			'methods'             => 'POST',
			'callback'            => 'kaamase_pay_handle_webhook',
			// Razorpay cannot log in. The signature is the authentication.
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'kaamase_pay_webhook_route' );

/**
 * Handle an event.
 *
 * Always answers 200 once the signature is good, including for events it
 * does nothing with. A non 200 makes Razorpay retry, and retrying
 * something we deliberately ignored just fills their queue and ours.
 *
 * @since 1.0.0
 * @param WP_REST_Request $request The request.
 * @return WP_REST_Response
 */
function kaamase_pay_handle_webhook( $request ) {

	$body      = (string) $request->get_body();
	$signature = (string) $request->get_header( 'x-razorpay-signature' );

	if ( ! kaamase_pay_verify_webhook( $body, $signature ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 401 );
	}

	$payload = json_decode( $body, true );

	if ( ! is_array( $payload ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	$event = (string) ( $payload['event'] ?? '' );

	/*
	 * Their event ID header is the reliable one. The payload does not
	 * always carry something unique per delivery, and using a payment ID
	 * would wrongly reject a second, genuine event about the same
	 * payment.
	 */
	$event_id = (string) $request->get_header( 'x-razorpay-event-id' );

	if ( '' !== $event_id && ! kaamase_pay_claim_event( $event_id ) ) {
		return new WP_REST_Response(
			array(
				'ok'   => true,
				'seen' => true,
			),
			200
		);
	}

	switch ( $event ) {

		case 'subscription.charged':
			kaamase_pay_handle_renewal( $payload );
			break;

		case 'subscription.cancelled':
		case 'subscription.halted':
		case 'subscription.completed':
			kaamase_pay_handle_subscription_end( $payload, $event );
			break;

		case 'payment.captured':
			kaamase_pay_handle_capture( $payload );
			break;
	}

	/**
	 * Fires for every verified webhook.
	 *
	 * @since 1.0.0
	 * @param string $event   Event name.
	 * @param array  $payload Decoded payload.
	 */
	do_action( 'kaamase_pay_webhook', $event, $payload );

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Take an event ID, once.
 *
 * @since 1.0.0
 * @param string $event_id Razorpay event ID.
 * @return bool True if this is the first time we have seen it.
 */
function kaamase_pay_claim_event( $event_id ) {

	global $wpdb;

	$wpdb->suppress_errors( true );

	// A duplicate breaks the unique key and returns false, which is the check.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$inserted = $wpdb->insert(
		kaamase_pay_events_table(),
		array(
			'event_id'   => substr( (string) $event_id, 0, 80 ),
			'created_at' => current_time( 'mysql' ),
		)
	);

	$wpdb->suppress_errors( false );

	if ( $inserted ) {
		return true;
	}

	/*
	 * Why the row is looked for rather than the error read.
	 *
	 * This used to decide by searching the MySQL error text for the
	 * word "duplicate". That is the server's language and phrasing, not
	 * an interface: a server running in another locale, or a MariaDB
	 * release that words it differently, turns every duplicate into an
	 * unrecognised failure. The failure then falls through to the branch
	 * below, which lets the event through, and Razorpay's retries get
	 * processed as though they were new.
	 *
	 * Asking whether the row is there answers the same question without
	 * depending on how the answer is spelled.
	 */
	$exists = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			'SELECT COUNT(*) FROM ' . kaamase_pay_events_table() . ' WHERE event_id = %s',
			substr( (string) $event_id, 0, 80 )
		)
	);

	if ( $exists > 0 ) {
		return false;
	}

	/*
	 * The insert failed for a reason that has nothing to do with
	 * duplication, a missing table most of all. Treating that as already
	 * seen would silently drop every webhook on the site, so let it
	 * through: the payment ID check downstream is what actually prevents
	 * double counting.
	 */
	return true;
}

/**
 * A subscription charged again. Push the date forward.
 *
 * @since 1.0.0
 * @param array $payload Decoded payload.
 * @return void
 */
function kaamase_pay_handle_renewal( $payload ) {

	$subscription = $payload['payload']['subscription']['entity'] ?? array();
	$payment      = $payload['payload']['payment']['entity'] ?? array();

	$subscription_id = (string) ( $subscription['id'] ?? '' );
	$payment_id      = (string) ( $payment['id'] ?? '' );

	if ( '' === $subscription_id ) {
		return;
	}

	/*
	 * Razorpay sends subscription.charged for the signup charge as well
	 * as for renewals, and the browser has usually already dealt with
	 * that first one by the time this arrives. Without this line a new
	 * subscriber gets sixty days for one month's money, which is the
	 * kind of bug that is invisible until somebody works out they can
	 * make it happen on purpose.
	 */
	if ( kaamase_pay_payment_seen( $payment_id ) ) {
		return;
	}

	/*
	 * The user is found from the notes we set when the subscription was
	 * created, falling back to a lookup on the stored subscription ID.
	 * Notes survive a user meta key being renamed; the lookup survives
	 * notes being dropped. Between them something always works.
	 */
	$user_id = (int) ( $subscription['notes']['user_id'] ?? 0 );
	$plan_id = (string) ( $subscription['notes']['plan'] ?? '' );

	if ( ! $user_id ) {
		$user_id = kaamase_pay_user_by_subscription( $subscription_id );
	}

	if ( ! $user_id ) {
		return;
	}

	if ( '' === $plan_id ) {
		$plan_id = (string) get_user_meta( $user_id, KAAMASE_PAY_PLAN_KEY, true );
	}

	$plan = kaamase_pay_plan( $plan_id );

	if ( ! $plan ) {
		return;
	}

	/*
	 * Which period renewed is decided by matching the Razorpay plan ID
	 * on the subscription against the two we stored, not by guessing
	 * from the amount. A price change makes a new Razorpay plan and
	 * leaves the old one running for existing subscribers, so the
	 * amount on a renewal is often not the current price and matching
	 * on it would extend the wrong number of days.
	 */
	$rzp_plan = (string) ( $subscription['plan_id'] ?? '' );

	$period = ( '' !== $rzp_plan && $rzp_plan === (string) $plan['rzp_plan_yearly'] )
		? 'yearly'
		: 'monthly';

	kaamase_pay_record(
		array(
			'user_id'         => $user_id,
			'plan'            => $plan_id,
			'period'          => $period,
			'amount'          => ( (int) ( $payment['amount'] ?? 0 ) ) / 100,
			'status'          => 'paid',
			'payment_id'      => $payment_id,
			'subscription_id' => $subscription_id,
			'note'            => 'renewal',
		)
	);

	kaamase_pay_grant( $user_id, $plan_id, $period, $subscription_id );
}

/**
 * A subscription stopped.
 *
 * Nothing is taken away. They keep what the last payment bought until
 * the date it runs to, and then it lapses on its own because the date
 * stops being in the future. Cutting somebody off the moment they cancel
 * means charging for a month and delivering part of one.
 *
 * @since 1.0.0
 * @param array  $payload Decoded payload.
 * @param string $event   Which event.
 * @return void
 */
function kaamase_pay_handle_subscription_end( $payload, $event ) {

	$subscription    = $payload['payload']['subscription']['entity'] ?? array();
	$subscription_id = (string) ( $subscription['id'] ?? '' );

	if ( '' === $subscription_id ) {
		return;
	}

	$user_id = (int) ( $subscription['notes']['user_id'] ?? 0 );

	if ( ! $user_id ) {
		$user_id = kaamase_pay_user_by_subscription( $subscription_id );
	}

	if ( ! $user_id ) {
		return;
	}

	delete_user_meta( $user_id, KAAMASE_PAY_SUB_KEY );

	/*
	 * Only the most recent row, not every row carrying this
	 * subscription ID. After a year of renewals that would be twelve
	 * rows, and rewriting a paid renewal from last March as cancelled
	 * destroys the record of money that did arrive.
	 */
	$latest = kaamase_pay_find_row( '', $subscription_id );

	if ( $latest ) {
		kaamase_pay_update_by_id(
			(int) $latest->id,
			array(
				'status' => 'halted' === $event ? 'halted' : 'cancelled',
				'note'   => $event,
			)
		);
	}

	/**
	 * Fires when a subscription stops renewing.
	 *
	 * @since 1.0.0
	 * @param int    $user_id         User ID.
	 * @param string $subscription_id Razorpay subscription ID.
	 * @param string $event           Which event ended it.
	 */
	do_action( 'kaamase_pay_subscription_ended', $user_id, $subscription_id, $event );
}

/**
 * A one time payment was captured.
 *
 * The browser normally gets here first and the access is already given.
 * This is the safety net for the person whose connection died between
 * paying and being sent back, which is the single most common way
 * somebody pays and gets nothing.
 *
 * @since 1.0.0
 * @param array $payload Decoded payload.
 * @return void
 */
function kaamase_pay_handle_capture( $payload ) {

	global $wpdb;

	$payment  = $payload['payload']['payment']['entity'] ?? array();
	$order_id = (string) ( $payment['order_id'] ?? '' );

	if ( '' === $order_id ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$row = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM ' . kaamase_pay_table() . ' WHERE order_id = %s LIMIT 1',
			$order_id
		)
	);

	if ( ! $row || 'paid' === $row->status ) {
		return;
	}

	kaamase_pay_update_by_id(
		(int) $row->id,
		array(
			'status'     => 'paid',
			'payment_id' => (string) ( $payment['id'] ?? '' ),
			'note'       => 'confirmed by webhook',
		)
	);

	kaamase_pay_grant( (int) $row->user_id, (string) $row->plan, (string) $row->period );
}
