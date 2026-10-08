<?php
/**
 * Telling people about their own money.
 *
 * The gap this closes
 * -------------------
 * There was no email anywhere in this plugin except the one that goes out
 * when a card is declined. Somebody paid and got a screen, and that was
 * the whole of it: no record of what they bought, no price, no date it
 * runs to, and nothing at all before a plan that does not renew simply
 * stopped working one morning.
 *
 * For a monthly subscription that is survivable, because the charge
 * repeats and the bank statement is its own receipt. For a one off pack
 * it is not. Nobody remembers a date they were shown once, weeks ago, on
 * a page they closed.
 *
 * Two messages, and only two
 * --------------------------
 * A receipt when money arrives, and a warning before access runs out for
 * somebody nobody is going to charge again. Anything more is a mailing
 * list, and this platform's whole promise to the people on it rests on
 * not being one.
 *
 * Why the warning needs a scheduled task and access still does not
 * ----------------------------------------------------------------
 * access.php is deliberate about having no cron: paid access is a date,
 * so it lapses correctly on the next request whether or not anything ran.
 * That is untouched. A date cannot email anybody three days before it
 * arrives, though, so the reminder — and only the reminder — is on a
 * daily run. If the cron never fires, nobody is warned and everything
 * else still behaves exactly as it did.
 *
 * @package KaamasePay
 * @version 1.1.0
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KAAMASE_PAY_REMIND_DAYS' ) ) {
	/** How many days before the end to warn somebody. */
	define( 'KAAMASE_PAY_REMIND_DAYS', 3 );
}

if ( ! defined( 'KAAMASE_PAY_REMINDED_KEY' ) ) {
	/**
	 * Which expiry date we have already warned about.
	 *
	 * The date itself rather than a yes or no, so that renewing re-arms
	 * it automatically. A flag would have to be cleared by whatever
	 * grants access, and the day somebody forgets to clear it is the day
	 * a customer stops being warned for good.
	 */
	define( 'KAAMASE_PAY_REMINDED_KEY', '_kaamase_pay_reminded_for' );
}


/* ==========================================================================
   THE RECEIPT
   ========================================================================== */

if ( ! function_exists( 'kaamase_pay_last_payment' ) ) {
	/**
	 * The most recent payment recorded against an account.
	 *
	 * @since 1.5.0
	 * @param int $user_id User ID.
	 * @return object|null
	 */
	function kaamase_pay_last_payment( $user_id ) {

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . kaamase_pay_table() . " WHERE user_id = %d AND status = 'paid' ORDER BY id DESC LIMIT 1",
				(int) $user_id
			)
		);
	}
}

if ( ! function_exists( 'kaamase_pay_send_receipt' ) ) {
	/**
	 * Tell somebody what they just paid for.
	 *
	 * Sent on every grant, renewals included. A renewal is the charge
	 * somebody is most likely to have forgotten was coming, so it is the
	 * one most worth stating plainly.
	 *
	 * @since 1.5.0
	 * @param int    $user_id User ID.
	 * @param string $plan_id Plan ID.
	 * @param string $period  Period bought.
	 * @return void
	 */
	function kaamase_pay_send_receipt( $user_id, $plan_id, $period ) {

		$user = get_userdata( (int) $user_id );
		$plan = kaamase_pay_plan( $plan_id );

		if ( ! $user || ! $plan || '' === (string) $user->user_email ) {
			return;
		}

		/*
		 * A receipt is written by the payment gateway's own call to this
		 * server, minutes later, with nobody signed in. The person who
		 * paid has no request here for their language to be read from.
		 *
		 * This is also the message most worth getting right. Somebody
		 * has just handed over money and this is the platform's one
		 * written acknowledgement of it.
		 */
		/*
		 * Guarded rather than called plainly, because this is a separate
		 * plugin from the one that holds the language layer and a
		 * receipt must still go out when that plugin is off. False when
		 * it is missing, so nothing is restored that was never switched.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $user_id );

		$site    = get_bloginfo( 'name' );
		$expires = kaamase_pay_expires( $user_id );
		$endless = function_exists( 'kaamase_pay_is_endless' ) && kaamase_pay_is_endless( $expires );

		$lines = array(
			sprintf(
				/* translators: %s: person's name */
				__( 'Hello %s,', 'kaamase-pay' ),
				$user->display_name
			),
			'',
			sprintf(
				/* translators: 1: site name, 2: plan name */
				__( 'Your payment to %1$s for %2$s has gone through. Thank you.', 'kaamase-pay' ),
				$site,
				(string) $plan['name']
			),
		);

		/*
		 * The amount is read from the row that was just written rather
		 * than from the plan's current price. A price that went up last
		 * month is not what this person was charged, and a receipt that
		 * states the wrong figure is worse than one that states none.
		 */
		$row = kaamase_pay_last_payment( $user_id );

		if ( $row && (float) $row->amount > 0 ) {
			$lines[] = '';
			$lines[] = sprintf(
				/* translators: 1: currency, 2: amount */
				__( 'Amount: %1$s %2$s', 'kaamase-pay' ),
				(string) $row->currency,
				number_format_i18n( (float) $row->amount, 2 )
			);
		}

		$lines[] = '';

		if ( $endless ) {
			$lines[] = __( 'This does not end. Nothing more will be charged.', 'kaamase-pay' );
		} else {
			$lines[] = sprintf(
				/* translators: %s: date */
				__( 'It runs until %s.', 'kaamase-pay' ),
				date_i18n( get_option( 'date_format' ), $expires )
			);

			$lines[] = kaamase_pay_renews( $user_id )
				? __( 'It renews on its own, so you do not need to do anything.', 'kaamase-pay' )
				: __( 'It does not renew on its own. Nothing further will be charged.', 'kaamase-pay' );
		}

		$lines[] = '';
		$lines[] = __( 'You can see this on your account here:', 'kaamase-pay' );
		$lines[] = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : home_url( '/' );

		wp_mail(
			$user->user_email,
			sprintf(
				/* translators: %s: site name */
				__( 'Your payment to %s', 'kaamase-pay' ),
				$site
			),
			implode( "\n", $lines )
		);

		if ( $switched ) {
			kaamase_locale_restore();
		}
	}
}
add_action( 'kaamase_pay_granted', 'kaamase_pay_send_receipt', 10, 3 );


/* ==========================================================================
   THE WARNING
   ========================================================================== */

if ( ! function_exists( 'kaamase_pay_schedule_reminders' ) ) {
	/**
	 * Book the daily run.
	 *
	 * Booked from init rather than on activation, because this file
	 * arrived after the plugin was already live and nobody is going to
	 * deactivate a working payment plugin to switch a reminder on.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_pay_schedule_reminders() {

		if ( wp_next_scheduled( 'kaamase_pay_daily' ) ) {
			return;
		}

		/*
		 * Nine in the morning, in the site's own timezone. A message
		 * about money arriving at three in the morning reads as a
		 * problem before it has been opened.
		 */
		$when = ( new DateTimeImmutable( 'now', wp_timezone() ) )->setTime( 9, 0 );

		if ( $when->getTimestamp() <= time() ) {
			$when = $when->modify( '+1 day' );
		}

		wp_schedule_event( $when->getTimestamp(), 'daily', 'kaamase_pay_daily' );
	}
}
add_action( 'init', 'kaamase_pay_schedule_reminders', 30 );

if ( ! function_exists( 'kaamase_pay_due_to_warn' ) ) {
	/**
	 * Accounts whose access runs out soon and will not be renewed.
	 *
	 * Anything that renews itself is left out. Warning somebody that a
	 * subscription is about to end, when it is about to be charged
	 * instead, invites them to cancel something they meant to keep.
	 *
	 * @since 1.5.0
	 * @param int $limit How many at once.
	 * @return int[] User IDs.
	 */
	function kaamase_pay_due_to_warn( $limit = 100 ) {

		$now  = time();
		$soon = $now + ( KAAMASE_PAY_REMIND_DAYS * DAY_IN_SECONDS );

		$found = get_users(
			array(
				'number'     => (int) $limit,
				'fields'     => 'ID',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => KAAMASE_PAY_EXPIRES_KEY,
						'value'   => array( $now, $soon ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$due = array();

		foreach ( (array) $found as $user_id ) {

			$user_id = (int) $user_id;

			if ( kaamase_pay_renews( $user_id ) ) {
				continue;
			}

			$expires = kaamase_pay_expires( $user_id );

			// Already warned about this exact date.
			if ( (int) get_user_meta( $user_id, KAAMASE_PAY_REMINDED_KEY, true ) === $expires ) {
				continue;
			}

			$due[] = $user_id;
		}

		return $due;
	}
}

if ( ! function_exists( 'kaamase_pay_send_warning' ) ) {
	/**
	 * Tell one person their access is about to end.
	 *
	 * @since 1.5.0
	 * @param int $user_id User ID.
	 * @return bool Whether it went.
	 */
	function kaamase_pay_send_warning( $user_id ) {

		$user_id = (int) $user_id;
		$user    = get_userdata( $user_id );

		if ( ! $user || '' === (string) $user->user_email ) {
			return false;
		}

		/*
		 * And this one runs on a daily task, so there is no request at
		 * all. date_i18n sits inside the switch with the rest: the date
		 * a plan ends is the fact this whole message exists to carry.
		 */
		/*
		 * Guarded rather than called plainly, because this is a separate
		 * plugin from the one that holds the language layer and a
		 * receipt must still go out when that plugin is off. False when
		 * it is missing, so nothing is restored that was never switched.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $user_id );

		$expires = kaamase_pay_expires( $user_id );
		$plan    = kaamase_pay_plan( (string) get_user_meta( $user_id, KAAMASE_PAY_PLAN_KEY, true ) );
		$site    = get_bloginfo( 'name' );

		$lines = array(
			sprintf(
				/* translators: %s: person's name */
				__( 'Hello %s,', 'kaamase-pay' ),
				$user->display_name
			),
			'',
			$plan
				? sprintf(
					/* translators: 1: plan name, 2: site name, 3: date */
					__( 'Your %1$s plan on %2$s ends on %3$s.', 'kaamase-pay' ),
					(string) $plan['name'],
					$site,
					date_i18n( get_option( 'date_format' ), $expires )
				)
				: sprintf(
					/* translators: 1: site name, 2: date */
					__( 'Your paid plan on %1$s ends on %2$s.', 'kaamase-pay' ),
					$site,
					date_i18n( get_option( 'date_format' ), $expires )
				),
			'',
			/*
			 * Said plainly, because the point of warning somebody is that
			 * they can decide. A message that only says "it is ending"
			 * makes them go and find out what that costs them.
			 */
			__( 'Nothing will be charged. When it ends your account stays exactly as it is, and the extra allowance the plan gave you stops.', 'kaamase-pay' ),
			'',
			__( 'If you want to carry on, you can renew here:', 'kaamase-pay' ),
			function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : home_url( '/' ),
		);

		$sent = wp_mail(
			$user->user_email,
			sprintf(
				/* translators: %s: site name */
				__( 'Your plan on %s is ending', 'kaamase-pay' ),
				$site
			),
			implode( "\n", $lines )
		);

		/*
		 * Marked only when it actually went, and marked with the date it
		 * was about. A failed send stays unmarked so tomorrow's run picks
		 * it up again, and a renewal moves the date so the next ending
		 * gets its own warning.
		 */
		if ( $switched ) {
			kaamase_locale_restore();
		}

		if ( $sent ) {
			update_user_meta( $user_id, KAAMASE_PAY_REMINDED_KEY, $expires );
		}

		return (bool) $sent;
	}
}

if ( ! function_exists( 'kaamase_pay_run_reminders' ) ) {
	/**
	 * The daily run.
	 *
	 * Stops on the first refusal from the mail server rather than working
	 * through the rest. Everybody it did not reach stays unmarked and is
	 * tried again tomorrow, which is the behaviour that gets somebody
	 * warned late rather than never.
	 *
	 * @since 1.5.0
	 * @return int How many were told.
	 */
	function kaamase_pay_run_reminders() {

		$sent = 0;

		foreach ( kaamase_pay_due_to_warn( 100 ) as $user_id ) {

			if ( ! kaamase_pay_send_warning( $user_id ) ) {
				break;
			}

			$sent++;
		}

		return $sent;
	}
}
add_action( 'kaamase_pay_daily', 'kaamase_pay_run_reminders' );
