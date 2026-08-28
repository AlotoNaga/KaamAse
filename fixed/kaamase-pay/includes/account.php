<?php
/**
 * The person's own view of what they pay.
 *
 * Attached to the dashboard that already exists rather than living on a
 * page of its own. Somebody looking for their subscription looks where
 * their account is, and a second page called Billing is a page nobody
 * finds until they are already annoyed enough to search for it.
 *
 * Why cancelling has to be here
 * -----------------------------
 * A subscription somebody cannot stop without emailing you is not a
 * subscription, it is a trap, and it is the single fastest way to make
 * a person distrust a platform that holds their money. It also creates
 * work for you that a button would have done for free.
 *
 * The button cancels at the end of the period they have paid for, never
 * immediately. Somebody who cancels on the second day of a month keeps
 * the rest of that month. Cutting them off at the moment they press it
 * would mean charging for a month and delivering two days of it, which
 * turns every cancellation into a refund request.
 *
 * @package KaamasePay
 * @version 1.0.1
 * @since   1.0.1
 */

defined( 'ABSPATH' ) || exit;


/**
 * Add the section to the dashboard.
 *
 * @since 1.0.1
 * @param int $user_id Who is looking.
 * @return void
 */
function kaamase_pay_dashboard_section( $user_id ) {

	$user_id = (int) $user_id;

	if ( ! $user_id ) {
		return;
	}

	$plan    = kaamase_pay_user_plan( $user_id );
	$history = kaamase_pay_user_payments( $user_id );

	/*
	 * Nothing to show and nothing to sell means show nothing. A person
	 * who has never paid for anything, on a site that is not charging
	 * for anything, should not be given a heading about payments.
	 */
	if ( ! $plan && empty( $history ) && ! kaamase_pay_is_live() ) {
		return;
	}

	/*
	 * A worker with nothing to show gets no plan section at all. Not
	 * even an empty one saying they are on the free amount, because on
	 * an account that is free forever that sentence invites the
	 * question of what the paid amount is.
	 */
	if ( ! $plan && empty( $history ) && ! kaamase_pay_can_buy( $user_id ) ) {
		return;
	}

	$expires = kaamase_pay_expires( $user_id );
	$sub_id  = (string) get_user_meta( $user_id, KAAMASE_PAY_SUB_KEY, true );
	$meters  = kaamase_meters();
	?>

	<section class="ka-mt-6">

		<h2><?php esc_html_e( 'My plan', 'kaamase-pay' ); ?></h2>

		<?php if ( $plan ) : ?>

			<div class="ka-card ka-stack">

				<h3><?php echo esc_html( (string) $plan['name'] ); ?></h3>

				<?php
				$gives = array();

				foreach ( (array) ( $plan['grants'] ?? array() ) as $key => $amount ) {

					if ( (int) $amount > 0 && isset( $meters[ $key ] ) ) {
						$gives[] = sprintf( '%d %s', (int) $amount, strtolower( (string) $meters[ $key ]['label'] ) );
					}
				}
				?>

				<?php if ( $gives ) : ?>
					<p><?php echo esc_html( sprintf( /* translators: %s: what the plan adds */ __( 'Adds %s on top of the free amount.', 'kaamase-pay' ), implode( ', ', $gives ) ) ); ?></p>
				<?php endif; ?>

				<?php
				/*
				 * The sentence comes from kaamase_pay_plan_state() rather
				 * than being worked out here.
				 *
				 * It used to be decided in this template and decided
				 * again, separately, by the app from the raw expiry date.
				 * The two disagreed: a plan bought once for life stores
				 * an expiry a century out, so this page said it does not
				 * end while the app said it runs until 2126 and then
				 * stops. One purchase described two ways.
				 */
				$kaamase_state = kaamase_pay_plan_state( $user_id );
				?>

				<p class="ka-small ka-soft">
					<?php echo esc_html( (string) $kaamase_state['sentence'] ); ?>
				</p>

				<?php if ( '' !== (string) $kaamase_state['note'] ) : ?>
					<?php
					/*
					 * Why there is no Stop button under this plan.
					 *
					 * Nothing renews, so there is nothing to cancel, and
					 * the screen simply ended there. Somebody hunting for
					 * a way to stop being charged and finding no control
					 * at all concludes it is broken or hidden rather than
					 * absent, which is the state this account was in.
					 */
					?>
					<p class="ka-small ka-soft"><?php echo esc_html( (string) $kaamase_state['note'] ); ?></p>
				<?php endif; ?>

				<?php if ( $sub_id ) : ?>
					<?php
					/*
					 * Posts to the page it is on, not to wp-admin.
					 *
					 * Same fault as the buy button had: something on
					 * this host sits in front of admin-post.php and
					 * swallows the request, so pressing Stop renewing
					 * did nothing at all and said nothing either. A
					 * subscription somebody cannot stop is the worst
					 * thing on a payment screen, so this one goes
					 * nowhere near wp-admin.
					 */
					$kaamase_cancel_return = function_exists( 'kaamase_page_url' )
						? kaamase_page_url( 'dashboard' )
						: home_url( '/' );
					?>
					<?php
					/*
					 * Asked twice, on purpose.
					 *
					 * This ends a paying subscription and cannot be
					 * undone from here: getting it back means paying
					 * again. One stray tap while scrolling on a phone
					 * and somebody has cancelled something they meant to
					 * keep, and only finds out from the email.
					 *
					 * Confirmed on a second view rather than with a
					 * browser dialog, because a dialog needs JavaScript
					 * to have loaded and this has to work on a bad
					 * connection. The first press only changes the page.
					 */
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, decides which view to show.
					$kaamase_confirming = isset( $_GET['stop'] )
						&& 'confirm' === sanitize_key( wp_unslash( $_GET['stop'] ) );
					?>

					<?php if ( ! $kaamase_confirming ) : ?>

						<p>
							<a class="ka-btn ka-btn--outline"
								href="<?php echo esc_url( add_query_arg( 'stop', 'confirm', $kaamase_cancel_return ) ); ?>">
								<?php esc_html_e( 'Stop renewing', 'kaamase-pay' ); ?>
							</a>
						</p>

						<p class="ka-small ka-soft">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: date */
									__( 'You keep everything until %s. Nothing is taken away today and there is nothing more to pay.', 'kaamase-pay' ),
									date_i18n( get_option( 'date_format' ), $expires )
								)
							);
							?>
						</p>

					<?php else : ?>

						<div class="ka-notice ka-notice--warn ka-mt-4">
							<div>
								<span class="ka-notice__title">
									<?php esc_html_e( 'Stop it renewing?', 'kaamase-pay' ); ?>
								</span>

								<p>
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: date */
											__( 'You keep everything until %s. After that the extra urgent posts stop and the verified mark comes off your profile. Starting again means paying again.', 'kaamase-pay' ),
											date_i18n( get_option( 'date_format' ), $expires )
										)
									);
									?>
								</p>
							</div>
						</div>

						<form method="post" action="<?php echo esc_url( $kaamase_cancel_return ); ?>" class="ka-stack">
							<input type="hidden" name="action" value="kaamase_pay_cancel">
							<?php wp_nonce_field( 'kaamase_pay_cancel', 'kaamase_pay_cancel_nonce' ); ?>

							<a class="ka-btn ka-btn--action" href="<?php echo esc_url( $kaamase_cancel_return ); ?>">
								<?php esc_html_e( 'Keep it', 'kaamase-pay' ); ?>
							</a>

							<button type="submit" class="ka-btn ka-btn--outline">
								<?php esc_html_e( 'Yes, stop renewing', 'kaamase-pay' ); ?>
							</button>
						</form>

					<?php endif; ?>
				<?php endif; ?>

			</div>

		<?php elseif ( kaamase_pay_is_live() && kaamase_pay_can_buy( $user_id ) ) : ?>

			<p><?php esc_html_e( 'You are on the free amount, which is enough for most people.', 'kaamase-pay' ); ?></p>

			<?php if ( kaamase_pay_plans_url() ) : ?>
				<p>
					<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( kaamase_pay_plans_url() ); ?>">
						<?php esc_html_e( 'See what else is available', 'kaamase-pay' ); ?>
					</a>
				</p>
			<?php endif; ?>

		<?php endif; ?>

		<?php if ( ! empty( $history ) ) : ?>

			<h3 class="ka-mt-6"><?php esc_html_e( 'What I have paid', 'kaamase-pay' ); ?></h3>

			<ul class="kp-history">
				<?php foreach ( $history as $row ) : ?>
					<li data-status="<?php echo esc_attr( (string) $row->status ); ?>">
						<span class="kp-history__when">
							<?php echo esc_html( mysql2date( get_option( 'date_format' ), (string) $row->created_at ) ); ?>
						</span>

						<span class="kp-history__what">
							<span class="kp-history__amount">
								<?php echo esc_html( kaamase_pay_money( (float) $row->amount ) ); ?>
							</span>
							<span class="kp-history__status">
								<?php echo esc_html( kaamase_pay_status_word( (string) $row->status ) ); ?>
							</span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="ka-small ka-soft">
				<?php esc_html_e( 'Receipts come from Razorpay by email. If one is missing, tell us the date and we will find it.', 'kaamase-pay' ); ?>
			</p>

		<?php endif; ?>

	</section>

	<?php
}
add_action( 'kaamase_dashboard_sections', 'kaamase_pay_dashboard_section', 30 );

/**
 * Load the stylesheet on the dashboard as well.
 *
 * The plan section borrows the same styles as the plans page, and the
 * enqueue there is scoped to the shortcode, which the dashboard does
 * not contain.
 *
 * @since 1.0.1
 * @return void
 */
function kaamase_pay_dashboard_styles() {

	if ( ! is_singular() ) {
		return;
	}

	$content = (string) get_post_field( 'post_content', get_the_ID() );

	if ( ! has_shortcode( $content, 'kaamase_dashboard' ) ) {
		return;
	}

	wp_enqueue_style(
		'kaamase-pay',
		plugins_url( 'assets/plans.css', KAAMASE_PAY_FILE ),
		array(),
		KAAMASE_PAY_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'kaamase_pay_dashboard_styles', 20 );

/**
 * A payment status in words a person would use.
 *
 * @since 1.0.1
 * @param string $status Stored status.
 * @return string
 */
function kaamase_pay_status_word( $status ) {

	$words = array(
		'paid'      => __( 'paid', 'kaamase-pay' ),
		'created'   => __( 'not finished', 'kaamase-pay' ),
		'failed'    => __( 'did not go through', 'kaamase-pay' ),
		'cancelled' => __( 'stopped renewing', 'kaamase-pay' ),
		'halted'    => __( 'stopped, payment failed', 'kaamase-pay' ),
	);

	return $words[ $status ] ?? $status;
}

/**
 * This person's payments, most recent first.
 *
 * @since 1.0.1
 * @param int $user_id User ID.
 * @return array
 */
function kaamase_pay_user_payments( $user_id ) {

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM ' . kaamase_pay_table() . " WHERE user_id = %d AND status <> 'created' ORDER BY id DESC LIMIT 12",
			(int) $user_id
		)
	);

	return is_array( $rows ) ? $rows : array();
}


/* ==========================================================================
   STOPPING A SUBSCRIPTION
   ========================================================================== */

/**
 * Cancel at the end of the paid period.
 *
 * The user meta holding the subscription is only cleared once Razorpay
 * confirms, and the expiry date is left exactly as it was. So a failed
 * cancellation leaves everything consistent rather than leaving somebody
 * believing they have stopped paying while the charges continue, which
 * is the worst of the ways this can go wrong.
 *
 * @since 1.0.1
 * @return void
 */
function kaamase_pay_cancel() {

	$back = kaamase_page_url( 'dashboard' );

	if ( ! is_user_logged_in()
		|| ! isset( $_POST['kaamase_pay_cancel_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_pay_cancel_nonce'] ) ), 'kaamase_pay_cancel' ) ) {
		wp_safe_redirect( $back );
		exit;
	}

	$user_id = get_current_user_id();
	$sub_id  = (string) get_user_meta( $user_id, KAAMASE_PAY_SUB_KEY, true );

	if ( '' === $sub_id ) {
		wp_safe_redirect( $back );
		exit;
	}

	$result = kaamase_pay_cancel_subscription( $sub_id );

	if ( is_wp_error( $result ) ) {

		/*
		 * A refusal is not the same as a failure, so ask before saying
		 * so.
		 *
		 * Razorpay declines to cancel a subscription that has already
		 * stopped, and that decline arrives looking exactly like a real
		 * error. Trusting it meant the stored subscription was never
		 * cleared: Razorpay had genuinely cancelled, the person had the
		 * emails to prove it, and this site still showed a Stop
		 * renewing button that did nothing, for good.
		 *
		 * Asking what the status actually is settles it. If it has
		 * stopped, that is a success however the cancel call was
		 * phrased.
		 */
		$state = function_exists( 'kaamase_pay_fetch_subscription' )
			? kaamase_pay_fetch_subscription( $sub_id )
			: $result;

		$really_stopped = ! is_wp_error( $state )
			&& function_exists( 'kaamase_pay_subscription_stopped' )
			&& kaamase_pay_subscription_stopped( $state );

		if ( ! $really_stopped ) {

			set_transient( 'kaamase_pay_cancel_' . $user_id, 'failed', 5 * MINUTE_IN_SECONDS );

			wp_safe_redirect( add_query_arg( 'sub', 'failed', $back ) );
			exit;
		}
	}

	/*
	 * The date is deliberately untouched. They paid for this period and
	 * they keep it. The webhook clears the subscription when Razorpay
	 * confirms it has stopped, and the access lapses on its own when the
	 * date passes.
	 */
	delete_user_meta( $user_id, KAAMASE_PAY_SUB_KEY );

	wp_safe_redirect( add_query_arg( 'sub', 'stopped', $back ) );
	exit;
}
add_action( 'admin_post_kaamase_pay_cancel', 'kaamase_pay_cancel' );

/**
 * Catch Stop renewing on the front end.
 *
 * Kept alongside the wp-admin hook rather than replacing it, so a page
 * still open in somebody's browser keeps working.
 *
 * @since 1.4.1
 * @return void
 */
function kaamase_pay_catch_cancel() {

	if ( is_admin() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked inside the handler.
	$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';

	if ( 'kaamase_pay_cancel' !== $action ) {
		return;
	}

	kaamase_pay_cancel();
}
add_action( 'template_redirect', 'kaamase_pay_catch_cancel' );

/**
 * Say how the cancellation went.
 *
 * @since 1.0.1
 * @param int $user_id Who is looking.
 * @return void
 */
function kaamase_pay_cancel_notice( $user_id ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only banner.
	$state = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';

	if ( ! $state ) {
		return;
	}

	if ( 'stopped' === $state ) {

		printf(
			'<div class="ka-notice ka-notice--ok"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
			esc_html__( 'It will not renew again', 'kaamase-pay' ),
			esc_html(
				sprintf(
					/* translators: %s: date */
					__( 'You keep everything you have until %s, and nothing more will be charged. You can start again any time.', 'kaamase-pay' ),
					date_i18n( get_option( 'date_format' ), kaamase_pay_expires( (int) $user_id ) )
				)
			)
		);

		return;
	}

	printf(
		'<div class="ka-notice ka-notice--warn"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
		esc_html__( 'That did not work', 'kaamase-pay' ),
		esc_html__( 'Your subscription has not been stopped and you may be charged again. Please tell us and we will stop it by hand.', 'kaamase-pay' )
	);
}
add_action( 'kaamase_dashboard_prompts', 'kaamase_pay_cancel_notice', 30 );
