<?php
/**
 * Who has paid for what.
 *
 * This file is the entire connection between money and what somebody is
 * allowed to do. It answers one question for the core plugin, using
 * three pieces of user meta, and it does so without any scheduled task.
 *
 * Access has an end date rather than a state. There is no active flag to
 * go stale, nothing to sweep up nightly, and no window where somebody
 * whose subscription lapsed at midnight keeps their allowance until a
 * cron happens to run. A date in the past simply stops counting, on the
 * next request, everywhere, at once.
 *
 * @package KaamasePay
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Whether this account may be sold anything at all.
 *
 * Only accounts that can post jobs, which means employers.
 *
 * This is a promise, not a product decision. The homepage, the footer
 * and the app all state that Kaam Ase never asks a worker for money,
 * and the registration screen says free for workers and always will be.
 * A worker who opens a page listing things to buy has been told the
 * opposite of that, and nothing about how carefully it is worded
 * afterwards repairs it.
 *
 * It is also the safety story. The single most common fraud against
 * labourers is being charged for the promise of work. A platform that
 * takes a worker's money for anything, however legitimate, has given up
 * the sentence it uses to warn them about people who do.
 *
 * @since 1.0.2
 * @param int $user_id Optional. Defaults to the current user.
 * @return bool
 */
function kaamase_pay_can_buy( $user_id = 0 ) {

	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	if ( ! $user_id ) {
		return false;
	}

	/**
	 * Filter whether an account may buy.
	 *
	 * @since 1.0.2
	 * @param bool $can     Whether they may.
	 * @param int  $user_id User ID.
	 */
	/*
	 * Anybody signed in may buy, worker or employer.
	 *
	 * This was employers only, on the reasoning that charging a worker
	 * breaks the promise the platform makes them. The restriction turned
	 * out to be both meaningless and wrong for this market: adding the
	 * hiring side is free and takes one tap, so it stopped nobody, and
	 * here the same person hires one week and takes work the next.
	 *
	 * The promise is kept by what the money buys instead. It buys
	 * unlimited urgent job posts, which is a hiring capability, and a
	 * call from Kaam Ase. It does not buy a place above anybody in
	 * search, and no worker is ever charged to be found, to be called,
	 * or to be hired.
	 */
	return (bool) apply_filters( 'kaamase_pay_can_buy', true, $user_id );
}

/**
 * When this person's paid access runs out.
 *
 * @since 1.0.0
 * @param int $user_id User ID.
 * @return int Timestamp, or 0 if they have never paid.
 */
function kaamase_pay_expires( $user_id ) {
	return (int) get_user_meta( (int) $user_id, KAAMASE_PAY_EXPIRES_KEY, true );
}

/**
 * Whether this person's paid access is currently good.
 *
 * @since 1.0.0
 * @param int $user_id User ID.
 * @return bool
 */
function kaamase_pay_is_active( $user_id ) {
	return kaamase_pay_expires( $user_id ) > time();
}

/**
 * The plan they are on, if it is still running.
 *
 * @since 1.0.0
 * @param int $user_id User ID.
 * @return array|null
 */
function kaamase_pay_user_plan( $user_id ) {

	if ( ! kaamase_pay_is_active( $user_id ) ) {
		return null;
	}

	return kaamase_pay_plan( (string) get_user_meta( (int) $user_id, KAAMASE_PAY_PLAN_KEY, true ) );
}


/* ==========================================================================
   THE ANSWER THE CORE PLUGIN ASKS FOR
   ========================================================================== */

/**
 * How much extra allowance this person has bought.
 *
 * @since 1.0.0
 * @param int    $extra   Running total from other listeners.
 * @param string $key     Meter key.
 * @param int    $user_id User being checked.
 * @return int
 */
function kaamase_pay_allowance( $extra, $key, $user_id ) {

	$plan = kaamase_pay_user_plan( $user_id );

	if ( ! $plan ) {
		return $extra;
	}

	return $extra + max( 0, (int) ( $plan['grants'][ $key ] ?? 0 ) );
}
add_filter( 'kaamase_paid_allowance', 'kaamase_pay_allowance', 10, 3 );


/* ==========================================================================
   GIVING ACCESS
   ========================================================================== */

/**
 * Extend somebody's paid access.
 *
 * Counted from whichever is later, now or the date they already have.
 * Somebody who renews early, or who buys a pack while a month is still
 * running, gets the days added rather than losing what was left. The
 * alternative quietly takes money and shortens the access it paid for,
 * and it is the sort of thing that only ever gets noticed by the
 * customer.
 *
 * @since 1.0.0
 * @param int    $user_id         User ID.
 * @param string $plan_id         Plan ID.
 * @param string $period          once, monthly or yearly.
 * @param string $subscription_id Razorpay subscription, when there is one.
 * @return bool
 */
function kaamase_pay_grant( $user_id, $plan_id, $period, $subscription_id = '' ) {

	$user_id = (int) $user_id;
	$plan    = kaamase_pay_plan( $plan_id );

	if ( ! $user_id || ! $plan ) {
		return false;
	}

	$days = kaamase_pay_days( $plan, $period );
	$from = max( time(), kaamase_pay_expires( $user_id ) );

	update_user_meta( $user_id, KAAMASE_PAY_PLAN_KEY, $plan_id );
	update_user_meta( $user_id, KAAMASE_PAY_EXPIRES_KEY, $from + ( $days * DAY_IN_SECONDS ) );

	if ( $subscription_id ) {
		update_user_meta( $user_id, KAAMASE_PAY_SUB_KEY, $subscription_id );
	}

	/*
	 * Paying joins the queue to be rung. It does not give the tick.
	 *
	 * The tick is set by hand after somebody at Kaam Ase has spoken to
	 * the person, and doing it here instead would make the mark mean
	 * that they paid while everybody reading it believes somebody
	 * checked. The capability they bought, the urgent posts, is already
	 * live by this point: only the mark waits for the call.
	 */
	if ( function_exists( 'kaamase_join_call_queue' ) ) {
		kaamase_join_call_queue( $user_id );
	}

	/**
	 * Fires after paid access is given or extended.
	 *
	 * @since 1.0.0
	 * @param int    $user_id User ID.
	 * @param string $plan_id Plan ID.
	 * @param string $period  Period bought.
	 */
	do_action( 'kaamase_pay_granted', $user_id, $plan_id, $period );

	return true;
}

/**
 * Find the user behind a Razorpay subscription.
 *
 * @since 1.0.0
 * @param string $subscription_id Razorpay subscription ID.
 * @return int User ID, or 0.
 */
function kaamase_pay_user_by_subscription( $subscription_id ) {

	$users = get_users(
		array(
			'meta_key'   => KAAMASE_PAY_SUB_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => (string) $subscription_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
			'fields'     => 'ID',
		)
	);

	return empty( $users ) ? 0 : (int) $users[0];
}


/* ==========================================================================
   RECORDING MONEY
   ========================================================================== */

/**
 * Write a payment row.
 *
 * @since 1.0.0
 * @param array $data Row values.
 * @return int Insert ID.
 */
function kaamase_pay_record( $data ) {

	global $wpdb;

	$now = current_time( 'mysql' );

	$row = wp_parse_args(
		$data,
		array(
			'user_id'         => 0,
			'plan'            => '',
			'period'          => 'once',
			'amount'          => 0,
			'currency'        => (string) kaamase_pay_setting( 'currency', 'INR' ),
			'status'          => 'created',
			'order_id'        => null,
			'payment_id'      => null,
			'subscription_id' => null,
			'note'            => null,
			'created_at'      => $now,
			'updated_at'      => $now,
		)
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->insert( kaamase_pay_table(), $row );

	return (int) $wpdb->insert_id;
}

/**
 * Update a payment row.
 *
 * @since 1.0.0
 * @param array $where  Which row.
 * @param array $fields What to change.
 * @return void
 */
function kaamase_pay_update( $where, $fields ) {

	global $wpdb;

	$fields['updated_at'] = current_time( 'mysql' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update( kaamase_pay_table(), $fields, $where );
}

/**
 * Update one payment row by its own ID.
 *
 * Preferred over matching on a subscription ID, which after a year of
 * renewals matches twelve rows and would rewrite the history of every
 * one of them.
 *
 * @since 1.0.1
 * @param int   $id     Row ID.
 * @param array $fields What to change.
 * @return void
 */
function kaamase_pay_update_by_id( $id, $fields ) {
	kaamase_pay_update( array( 'id' => (int) $id ), $fields );
}

/**
 * Find the payment we started, by whichever reference came back.
 *
 * @since 1.0.1
 * @param string $order_id        Razorpay order ID.
 * @param string $subscription_id Razorpay subscription ID.
 * @return object|null
 */
function kaamase_pay_find_row( $order_id, $subscription_id ) {

	global $wpdb;

	if ( '' !== (string) $subscription_id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . kaamase_pay_table() . ' WHERE subscription_id = %s ORDER BY id ASC LIMIT 1',
				(string) $subscription_id
			)
		);
	}

	if ( '' === (string) $order_id ) {
		return null;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $wpdb->get_row(
		$wpdb->prepare(
			'SELECT * FROM ' . kaamase_pay_table() . ' WHERE order_id = %s LIMIT 1',
			(string) $order_id
		)
	);
}

/**
 * Whether this exact payment has already been counted.
 *
 * The one guard against giving somebody two months for one charge, and
 * it has to cover three separate ways that can happen:
 *
 *   The browser comes back and the webhook arrives, both for the same
 *   first subscription payment. Razorpay does send subscription.charged
 *   for the signup charge, not only for renewals.
 *
 *   The browser comes back twice, because the person pressed back and
 *   the form resubmitted, or because they refreshed the result.
 *
 *   The webhook is retried, which Razorpay does routinely whenever it
 *   does not get a clean answer quickly enough.
 *
 * Keyed on the Razorpay payment ID, because that is the thing that is
 * one to one with money actually moving.
 *
 * @since 1.0.1
 * @param string $payment_id Razorpay payment ID.
 * @return bool
 */
function kaamase_pay_payment_seen( $payment_id ) {

	global $wpdb;

	if ( '' === (string) $payment_id ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$found = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT id FROM ' . kaamase_pay_table() . " WHERE payment_id = %s AND status = 'paid' LIMIT 1",
			(string) $payment_id
		)
	);

	return ! empty( $found );
}


/* ==========================================================================
   THE MESSAGE WHEN SOMEBODY RUNS OUT
   ========================================================================== */

/**
 * Add a way through to the limit message.
 *
 * Not on iOS, and that is a rule rather than a preference.
 *
 * Outside the United States, Apple does not allow an app to point
 * somebody at a website to pay for something the app could sell. It is
 * called anti steering, and a link in an error message counts. Breaking
 * it risks the app being pulled, which would cost far more than the
 * handful of upgrades the link might have earned.
 *
 * The web and Android are unaffected. India allows alternative billing
 * on Google Play, so an Android user can be sent to Razorpay as normal.
 *
 * @since 1.0.0
 * @param string $message The message from the core plugin.
 * @param string $key     Meter key.
 * @return string
 */
function kaamase_pay_limit_message( $message, $key ) {

	unset( $key );

	if ( ! kaamase_pay_platform_may_offer( kaamase_pay_requesting_platform() ) || ! kaamase_pay_can_buy() ) {
		return $message;
	}

	$page = kaamase_pay_plans_url();

	if ( ! $page ) {
		return $message;
	}

	return $message . ' ' . sprintf(
		/* translators: %s: link to the plans page */
		__( 'See what is available: %s', 'kaamase-pay' ),
		$page
	);
}

/**
 * Whether a way to pay may be shown on this platform at all.
 *
 * The web, and nothing else. Both app stores are excluded, and for
 * different reasons that happen to lead to the same place.
 *
 * On iOS, outside the United States, Apple does not allow an app to
 * point somebody at a website to pay. That one is settled.
 *
 * On Android it is less settled, and that is exactly why the answer is
 * no. Google's payments policy requires Play's billing system for "app
 * functionality or content", and raising a contact lookup limit reads
 * like app functionality however it is described. There is a real
 * argument the other way, since what an employer is ultimately buying
 * is a lead for building work in the physical world, and physical
 * services are exempt. India also has user choice billing, which does
 * permit an alternative to Play's system.
 *
 * But user choice billing means integrating Google's alternative
 * billing APIs, enrolling, certifying PCI compliance and reporting
 * transactions. It does not mean opening a browser at a Razorpay page,
 * which is the thing Play rejects apps for by name, and rejection there
 * can take the whole app down rather than just the payment.
 *
 * So the honest position is that we do not know which side of the line
 * this falls on, the cost of being wrong is the app, and the cost of
 * being cautious is that an employer pays on the website instead. That
 * is not a close decision. When there is enough revenue to justify
 * doing Play Billing properly, this function is where it changes.
 *
 * @since 1.0.3
 * @param string $platform One of ios, android or web.
 * @return bool
 */
function kaamase_pay_platform_may_offer( $platform ) {

	/**
	 * Filter whether a platform may be shown a way to pay.
	 *
	 * The single place to change once Play Billing or StoreKit is
	 * properly integrated, rather than a condition repeated in four
	 * files that will not all be found later.
	 *
	 * @since 1.0.3
	 * @param bool   $may      Whether it may.
	 * @param string $platform The platform asking.
	 */
	return (bool) apply_filters( 'kaamase_pay_platform_may_offer', 'web' === $platform, $platform );
}

/**
 * Which platform is asking.
 *
 * Sent by the app as a header. Anything without the header is treated
 * as web, which is correct: the website is the only thing that does not
 * send it.
 *
 * @since 1.0.2
 * @return string One of ios, android or web.
 */
function kaamase_pay_requesting_platform() {

	$header = isset( $_SERVER['HTTP_X_KAAMASE_PLATFORM'] )
		? sanitize_key( wp_unslash( $_SERVER['HTTP_X_KAAMASE_PLATFORM'] ) )
		: '';

	return in_array( $header, array( 'ios', 'android' ), true ) ? $header : 'web';
}

/**
 * What state this account's plan is in, and the sentence that says so.
 *
 * Why one function rather than a condition in each place
 * ------------------------------------------------------
 * The website worked this out inline on the dashboard and the app worked
 * it out again from the raw expiry date, and the two disagreed. A plan
 * bought once for life stores an expiry a hundred years out, so the
 * website said "This does not end" and the app, holding the same number
 * and no way to know what it meant, said "Runs until 1 August 2126, then
 * stops on its own". Same purchase, two front doors, opposite answers.
 *
 * So the judgement is made here, once, and both are handed the result.
 * Anything that has to describe somebody's plan asks this.
 *
 * The four states
 * ---------------
 * none      Nothing bought, or it has run out.
 * endless   Paid once, never expires. Nothing to cancel.
 * renewing  A live Razorpay subscription. This is the only cancellable
 *           state, and the only one where money is still moving.
 * ending    Paid for a period that has not finished. Nothing renews, so
 *           there is nothing to cancel; it simply stops.
 *
 * @since 1.4.0
 * @param int $user_id User ID.
 * @return array
 */
function kaamase_pay_plan_state( $user_id ) {

	$user_id = (int) $user_id;

	$state = array(
		'status'     => 'none',
		'name'       => '',
		'expires'    => 0,
		'endless'    => false,
		'renews'     => false,
		'can_cancel' => false,
		'sentence'   => '',
		'note'       => '',
	);

	if ( ! $user_id ) {
		return $state;
	}

	$plan = kaamase_pay_user_plan( $user_id );

	/*
	 * kaamase_pay_user_plan() already returns null once the expiry has
	 * passed, so "no plan" and "expired" arrive here as the same thing,
	 * which is what a person means by it too.
	 */
	if ( ! $plan ) {
		return $state;
	}

	$expires = (int) kaamase_pay_expires( $user_id );
	$sub_id  = (string) get_user_meta( $user_id, KAAMASE_PAY_SUB_KEY, true );
	$endless = kaamase_pay_is_endless( $expires );
	$format  = get_option( 'date_format' );

	$state['name']    = (string) $plan['name'];
	$state['expires'] = $expires;
	$state['endless'] = $endless;
	$state['renews']  = ( '' !== $sub_id );

	if ( $endless ) {

		$state['status']   = 'endless';
		$state['sentence'] = __( 'This does not end. You paid once and it stays on your account.', 'kaamase-pay' );

		/*
		 * Said out loud, because the absence of a Stop button is not an
		 * answer. Somebody looking for a way to cancel and finding no
		 * control at all assumes the control is broken or hidden, not
		 * that there is nothing to cancel.
		 */
		$state['note'] = __( 'There is nothing to cancel and nothing more will be charged.', 'kaamase-pay' );

	} elseif ( '' !== $sub_id ) {

		$state['status']     = 'renewing';
		$state['can_cancel'] = true;
		$state['sentence']   = sprintf(
			/* translators: %s: date */
			__( 'Renews on %s.', 'kaamase-pay' ),
			date_i18n( $format, $expires )
		);

	} else {

		$state['status']   = 'ending';
		$state['sentence'] = sprintf(
			/* translators: %s: date */
			__( 'Runs until %s, then stops on its own. Nothing renews.', 'kaamase-pay' ),
			date_i18n( $format, $expires )
		);
		$state['note'] = __( 'There is nothing to cancel and nothing more will be charged.', 'kaamase-pay' );
	}

	return $state;
}

/**
 * Where somebody manages a plan they already have.
 *
 * Deliberately NOT the plans page, and deliberately not gated on whether
 * this platform may be sold to.
 *
 * Those are two different permissions and the code had them as one. An
 * app store forbids sending somebody out to a website to BUY. It does
 * not forbid showing somebody where to look at what they already bought,
 * and a customer who cannot find that has a worse problem than a
 * customer who cannot buy. Because manage_url was gated on the selling
 * rule, it came through empty on both phones, and the app was left
 * telling people to "manage it from your account page" with nothing to
 * tap.
 *
 * This points at the dashboard, which shows the plan and carries the
 * Stop renewing control. It is not a purchase page, and this returns
 * nothing at all for an account with no plan, so it can never become the
 * route to one.
 *
 * @since 1.4.0
 * @param int $user_id User ID.
 * @return string URL, or an empty string.
 */
function kaamase_pay_account_url( $user_id ) {

	if ( ! kaamase_pay_user_plan( (int) $user_id ) ) {
		return '';
	}

	return function_exists( 'kaamase_page_url' ) ? (string) kaamase_page_url( 'dashboard' ) : '';
}

/**
 * Tell the app what this account's plan is.
 *
 * Sent with the account rather than from an endpoint of its own, so the
 * phone learns everything it needs in the one request it already makes.
 *
 * can_buy_here is answered by the server but decided for the app: it is
 * false on iOS whatever the state of anything else, because that is the
 * platform where sending somebody to a website to pay is not allowed.
 * Keeping that decision here means one place to change it on the day
 * either the rule or the app changes.
 *
 * @since 1.0.2
 * @param array $me      The account.
 * @param int   $user_id User ID.
 * @return array
 */
function kaamase_pay_shape_me( $me, $user_id ) {

	$plan     = kaamase_pay_user_plan( $user_id );
	$platform = kaamase_pay_requesting_platform();
	$live     = kaamase_pay_is_live();

	/*
	 * Three separate conditions, all of which must hold before a way to
	 * pay is shown: something is actually for sale, this account is one
	 * we sell to at all, and this platform may be asked. Any one of them
	 * failing means status only, which is always safe to show.
	 *
	 * In practice the third rules out both app stores, so today this is
	 * only ever true on the website. See the note on
	 * kaamase_pay_platform_may_offer for why.
	 */
	$offer = $live && kaamase_pay_can_buy( $user_id ) && kaamase_pay_platform_may_offer( $platform );

	/*
	 * Selling inside an app is a different permission from linking out
	 * to the website, and the two are never both true.
	 *
	 * Linking out is what Apple forbids outside the United States and
	 * what Google's billing policy makes risky. Selling through the
	 * store the app belongs to is the sanctioned way, and it is only
	 * switched on once the products exist in that store and the webhook
	 * that grants them is live. Until then this is false and the app
	 * shows no way to buy at all, which is the safe direction to be
	 * wrong in.
	 */
	$in_app = $live
		&& kaamase_pay_can_buy( $user_id )
		&& in_array( $platform, array( 'ios', 'android' ), true )
		&& (bool) kaamase_pay_setting( 'in_app_' . $platform, 0 );

	$state = kaamase_pay_plan_state( $user_id );

	$me['plan'] = array(
		'name'           => $plan ? (string) $plan['name'] : '',
		'active'         => (bool) $plan,
		'expires'        => $plan ? (int) kaamase_pay_expires( $user_id ) : 0,
		'renews'         => (bool) get_user_meta( $user_id, KAAMASE_PAY_SUB_KEY, true ),
		'charging'       => $live,
		'can_buy_here'   => $offer,
		'can_buy_in_app' => $in_app,

		/*
		 * Unchanged, including staying empty on both phones. This is the
		 * BUYING link and the app store rule about it has not moved.
		 * Managing an existing plan is account_url below.
		 */
		'manage_url'     => $offer ? kaamase_pay_plans_url() : '',

		/*
		 * Everything from here down is additive. The keys above keep the
		 * meaning and the values they have always had, so an app build
		 * that has never heard of these carries on working exactly as it
		 * does today.
		 */

		/*
		 * The four states, so the app stops having to infer one from a
		 * date it cannot interpret: none, endless, renewing, ending.
		 */
		'status'         => (string) $state['status'],

		/*
		 * True for a plan bought once that never expires. Without this
		 * the app had only a timestamp a hundred years out and read it
		 * as an expiry date, so a lifetime purchase was shown as running
		 * until 2126 and then stopping.
		 */
		'endless'        => (bool) $state['endless'],

		/*
		 * Whether there is actually something to cancel. Only ever true
		 * for a live subscription; a one-off purchase has nothing to
		 * stop, which is different from having a stop button that fails.
		 */
		'can_cancel'     => (bool) $state['can_cancel'],

		/*
		 * The same sentence the website prints, written once. Two front
		 * doors describing one purchase in their own words is how they
		 * came to disagree in the first place. The app may show this
		 * verbatim.
		 */
		'sentence'       => (string) $state['sentence'],
		'note'           => (string) $state['note'],

		// Where to manage an existing plan. Not a purchase page.
		'account_url'    => kaamase_pay_account_url( $user_id ),
	);

	return $me;
}
add_filter( 'kaamase_shape_me', 'kaamase_pay_shape_me', 10, 2 );
add_filter( 'kaamase_limit_reached_message', 'kaamase_pay_limit_message', 10, 2 );

/**
 * Where the plans are shown to people.
 *
 * Found by looking for the shortcode rather than by asking for a page ID
 * in settings, so there is one fewer thing to configure and one fewer
 * way for the link to point at nothing.
 *
 * @since 1.0.0
 * @return string URL, or an empty string.
 */
function kaamase_pay_plans_url() {

	/*
	 * The page ID is written down the first time the shortcode renders,
	 * which is the only completely reliable signal that a given page is
	 * the plans page. Searching for the shortcode text is the fallback,
	 * and only that: WordPress search is not built for finding markup
	 * and will happily miss a page that has the shortcode inside a
	 * block, or match one that merely mentions it.
	 */
	$known = (int) get_option( 'kaamase_pay_plans_page', 0 );

	if ( $known && 'publish' === get_post_status( $known ) ) {

		/*
		 * The remembered page has to still be the plans page.
		 *
		 * Being published was the only test, so once this pointed at the
		 * wrong page it pointed there for good. That is what sent people
		 * to the dashboard after pressing Choose: the handler redirects
		 * to wherever this says, and it was saying dashboard, on a page
		 * nobody had pressed anything on and which explains nothing.
		 *
		 * Checking for the shortcode means a wrong answer corrects
		 * itself on the next request rather than needing somebody to
		 * know that an option exists.
		 */
		if ( has_shortcode( (string) get_post_field( 'post_content', $known ), 'kaamase_plans' ) ) {
			return (string) get_permalink( $known );
		}

		delete_option( 'kaamase_pay_plans_page' );
	}

	if ( get_transient( 'kaamase_pay_no_plans_page' ) ) {
		return '';
	}

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$found = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'page' AND post_status = 'publish'
			 AND post_content LIKE %s LIMIT 1",
			'%' . $wpdb->esc_like( '[kaamase_plans]' ) . '%'
		)
	);

	/*
	 * The miss is remembered as well as the hit, for an hour.
	 *
	 * Without this, a site with payments on and no plans page yet runs
	 * a LIKE across every page's content on every single request that
	 * touches a limit or loads an account. That is a full table scan
	 * hidden behind a function that reads like a lookup.
	 */
	if ( ! $found ) {
		set_transient( 'kaamase_pay_no_plans_page', 1, HOUR_IN_SECONDS );

		return '';
	}

	update_option( 'kaamase_pay_plans_page', $found, false );

	return (string) get_permalink( $found );
}
