<?php
/**
 * Purchases made inside the apps.
 *
 * RevenueCat tells us when Apple or Google has taken money, and this
 * calls the same grant function Razorpay already calls.
 *
 * Why the phone is not trusted
 * ----------------------------
 * The app knows when a purchase succeeded and says so, and that is
 * useful for a screen that updates immediately. It is not what grants
 * anything.
 *
 * A device can be lied to, and on a platform where somebody can pay
 * once and expect a year of access, a granted entitlement that came
 * from an unverified client is not a security worry in the abstract: it
 * is free access for anybody willing to install one tool. The receipt
 * is checked between Apple, RevenueCat and this endpoint, with the
 * phone as a bystander.
 *
 * One grant function, three ways in
 * ---------------------------------
 * Razorpay on the website, Apple in the iOS app, Google in the Android
 * app, all ending at kaamase_pay_grant(). Nothing downstream knows or
 * cares which one paid, which is why adding these two took a file
 * rather than a rewrite.
 *
 * @package KaamasePay
 * @version 1.0.0
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Register the endpoint.
 *
 * @since 1.2.0
 * @return void
 */
function kaamase_pay_store_webhook_route() {

	register_rest_route(
		'kaamase-pay/v1',
		'/store',
		array(
			'methods'             => 'POST',
			// RevenueCat cannot log in. The shared secret is the authentication.
			'permission_callback' => '__return_true',
			'callback'            => 'kaamase_pay_handle_store_webhook',
		)
	);
}
add_action( 'rest_api_init', 'kaamase_pay_store_webhook_route' );

/**
 * Handle a RevenueCat event.
 *
 * @since 1.2.0
 * @param WP_REST_Request $request The request.
 * @return WP_REST_Response
 */
function kaamase_pay_handle_store_webhook( $request ) {

	$secret = (string) kaamase_pay_setting( 'store_secret' );

	if ( '' === $secret ) {
		return new WP_REST_Response( array( 'ok' => false ), 503 );
	}

	/*
	 * RevenueCat sends the secret in the Authorization header exactly as
	 * it was entered in their dashboard. Compared with hash_equals so a
	 * wrong guess takes the same time as a right one.
	 */
	$given = (string) $request->get_header( 'authorization' );

	if ( ! hash_equals( $secret, $given ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 401 );
	}

	$event = (array) $request->get_param( 'event' );

	$type = isset( $event['type'] ) ? sanitize_text_field( (string) $event['type'] ) : '';

	/*
	 * The account id is the one the app told RevenueCat at sign in,
	 * which is our own user id. Without it there is nothing to grant to,
	 * and answering 200 stops them retrying an event that can never
	 * succeed.
	 */
	$user_id = isset( $event['app_user_id'] ) ? absint( $event['app_user_id'] ) : 0;

	if ( ! $user_id || ! get_userdata( $user_id ) ) {
		return new WP_REST_Response(
			array(
				'ok'   => true,
				'note' => 'unknown account',
			),
			200
		);
	}

	/*
	 * Seen already. RevenueCat retries anything it did not get a clean
	 * answer to, so the same event genuinely arrives more than once, and
	 * counting it twice would give two months for one payment.
	 */
	$event_id = isset( $event['id'] ) ? sanitize_text_field( (string) $event['id'] ) : '';

	if ( '' !== $event_id && ! kaamase_pay_claim_event( 'rc_' . $event_id ) ) {
		return new WP_REST_Response(
			array(
				'ok'   => true,
				'seen' => true,
			),
			200
		);
	}

	/*
	 * A lifetime purchase arrives as NON_RENEWING_PURCHASE, which is
	 * already here. It is listed again in the comment rather than the
	 * array because forgetting it would mean money taken and nothing
	 * granted, and that is worth a sentence.
	 */
	$granting = array( 'INITIAL_PURCHASE', 'RENEWAL', 'NON_RENEWING_PURCHASE', 'UNCANCELLATION' );

	if ( in_array( $type, $granting, true ) ) {
		kaamase_pay_grant_from_store( $user_id, $event, $type );
	}

	/*
	 * Nothing is taken away on cancellation or expiry, and that is
	 * deliberate rather than an omission. Access runs to the date it was
	 * paid to and then lapses on its own, because the date is in the
	 * past. Somebody who cancels on day two of a month they paid for
	 * keeps the rest of that month, which is both fair and what stops a
	 * cancellation turning into a refund request.
	 */

	/**
	 * Fires for every verified store event.
	 *
	 * @since 1.2.0
	 * @param string $type    Event type.
	 * @param array  $event   The event.
	 * @param int    $user_id Account it belongs to.
	 */
	do_action( 'kaamase_pay_store_event', $type, $event, $user_id );

	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Give access for a store purchase.
 *
 * @since 1.2.0
 * @param int    $user_id Account.
 * @param array  $event   The RevenueCat event.
 * @param string $type    Event type, so a renewing subscription can be
 *                        told apart from a one off purchase.
 * @return void
 */
function kaamase_pay_grant_from_store( $user_id, $event, $type = '' ) {

	$plan_id = kaamase_pay_plan_for_store( $event );

	if ( '' === $plan_id ) {
		return;
	}

	$period = kaamase_pay_period_for_store( $event );

	$plan = kaamase_pay_plan( $plan_id );

	kaamase_pay_record(
		array(
			'user_id'    => $user_id,
			'plan'       => $plan_id,
			'period'     => $period,
			'amount'     => isset( $event['price'] ) ? (float) $event['price'] : 0,
			'currency'   => isset( $event['currency'] ) ? sanitize_text_field( (string) $event['currency'] ) : 'INR',
			'status'     => 'paid',
			'payment_id' => isset( $event['transaction_id'] ) ? sanitize_text_field( (string) $event['transaction_id'] ) : null,
			'note'       => isset( $event['store'] ) ? sanitize_text_field( (string) $event['store'] ) : 'store',
		)
	);

	unset( $plan );

	/*
	 * Which store took the money, so that anything asking "where do I
	 * stop paying for this" can answer it. RevenueCat sends the store on
	 * every event; an unrecognised value comes back empty and is left
	 * alone rather than guessed at, because a wrong answer sends
	 * somebody to the wrong place to cancel.
	 */
	$origin = function_exists( 'kaamase_pay_normalise_origin' )
		? kaamase_pay_normalise_origin( isset( $event['store'] ) ? (string) $event['store'] : '' )
		: '';

	kaamase_pay_grant( $user_id, $plan_id, $period, '', $origin );

	/*
	 * Whether this one renews, recorded separately from the Razorpay
	 * subscription id.
	 *
	 * NON_RENEWING_PURCHASE is the store's own word for a one off, and
	 * it is the event a lifetime product arrives as. Everything else in
	 * the granting list is a subscription that will be charged again.
	 *
	 * Without this the server had no idea a store subscription renewed
	 * at all, so an iPhone subscriber was told their plan stops on its
	 * own and nothing renews, on the morning Apple charged them again.
	 *
	 * Deleted rather than set to false for a one off, so that somebody
	 * who subscribes, cancels, and later buys a lifetime is not left
	 * carrying a stale flag saying they are still being charged.
	 */
	if ( 'NON_RENEWING_PURCHASE' === $type ) {
		delete_user_meta( $user_id, KAAMASE_PAY_STORE_RENEWS_KEY );
	} elseif ( '' !== $type ) {
		update_user_meta( $user_id, KAAMASE_PAY_STORE_RENEWS_KEY, 1 );
	}
}

/**
 * Which of our plans a store product corresponds to.
 *
 * Matched on the product identifier, which is set in App Store Connect
 * and Play Console and mapped on the payments screen. Guessing from the
 * price would break the day a currency conversion moves.
 *
 * @since 1.2.0
 * @param array $event The event.
 * @return string Plan ID, or an empty string when unmapped.
 */
function kaamase_pay_plan_for_store( $event ) {

	$product = isset( $event['product_id'] ) ? (string) $event['product_id'] : '';

	if ( '' === $product ) {
		return '';
	}

	$map = (array) get_option( 'kaamase_pay_store_map', array() );

	if ( ! empty( $map[ $product ] ) ) {
		return sanitize_key( (string) $map[ $product ] );
	}

	/*
	 * Unmapped, so fall back to the only plan there is. Most sites will
	 * have exactly one, and refusing to grant because a mapping was
	 * never filled in would take money and give nothing, which is the
	 * worst possible failure here.
	 */
	$plans = kaamase_pay_active_plans();

	if ( 1 === count( $plans ) ) {
		return (string) array_key_first( $plans );
	}

	return '';
}

/**
 * Whether a store purchase was monthly, yearly or one off.
 *
 * @since 1.2.0
 * @param array $event The event.
 * @return string once, monthly or yearly.
 */
function kaamase_pay_period_for_store( $event ) {

	$type = isset( $event['period_type'] ) ? strtoupper( (string) $event['period_type'] ) : '';

	if ( 'TRIAL' === $type ) {
		return 'monthly';
	}

	$product = isset( $event['product_id'] ) ? strtolower( (string) $event['product_id'] ) : '';

	/*
	 * Lifetime first, because a product named rich_manu_lifetime would
	 * otherwise fall through to a one time pack and expire after
	 * whatever the plan's days field happens to say. Somebody who paid
	 * once for forever losing it a month later is the worst failure
	 * this file can have.
	 */
	if ( false !== strpos( $product, 'lifetime' ) || false !== strpos( $product, 'forever' ) ) {
		return 'lifetime';
	}

	if ( false !== strpos( $product, 'year' ) || false !== strpos( $product, 'annual' ) ) {
		return 'yearly';
	}

	if ( false !== strpos( $product, 'month' ) ) {
		return 'monthly';
	}

	return 'once';
}
