<?php
/**
 * Asking to be verified.
 *
 * A second door into the call queue, for people who have not paid.
 *
 * Why a second door and not a second tick
 * ---------------------------------------
 * Plenty of people want the mark and nothing else. That is not vanity to
 * be sniffed at, it is the whole reason the mark works: a worker looking
 * at an unknown number wants to know somebody is real, and a person
 * wanting to be seen as real is wanting the same thing from the other
 * side.
 *
 * The trap is giving it to them cheaply. The moment the mark can be got
 * without a call, it stops meaning a call happened, and then it stops
 * being worth wanting, and the demand that made it valuable disappears.
 * Every ticked profile on this platform has been telephoned by a person,
 * and that stays true.
 *
 * So this file does not hand out a mark. It hands out a place in the
 * queue. Somebody asks, they appear on a list, the owner rings them,
 * speaks to them, and gives them the plan and the mark the same way he
 * would to anybody he had just spoken to.
 *
 * A queue of its own
 * ------------------
 * Deliberately not the same list as the payers. That queue is sorted by
 * how long somebody has waited, and pouring free requests into it would
 * put a person who paid this morning behind fifty people who did not.
 * They paid for the call. They get it first, and their list is left
 * exactly as it was.
 *
 * The bar to ask
 * --------------
 * A published profile and a phone number that works. Not to be difficult
 * but because the owner's evenings are the thing in short supply, and a
 * call to somebody with an empty profile and no number helps nobody. The
 * missing pieces are named on the screen so it reads as a step rather
 * than a refusal.
 *
 * What happens after
 * ------------------
 * Whatever the owner grants runs out on its own. The mark only shows
 * while a plan is running, so a month given is a month shown, and after
 * that the profile goes quietly back to how it was.
 *
 * Once a free one has ended, whether it ran out or was taken back, that
 * person cannot ask again for a year (free-lock.php). They are told the
 * date they can, and that Rich Manu is there if they would rather not
 * wait. The owner can still give anybody anything from Give the plan.
 *
 * @package KaamaseCore
 * @version 1.2.1
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/** When they asked. */
define( 'KAAMASE_ASK_AT_KEY', '_kaamase_verify_asked' );

/** Where the request stands: waiting, done or no. */
define( 'KAAMASE_ASK_STATE_KEY', '_kaamase_verify_state' );

/** How long after a no before somebody may ask again. */
define( 'KAAMASE_ASK_AGAIN_DAYS', 30 );


/* ==========================================================================
   1. WHERE A PERSON STANDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_ask_state' ) ) {
	/**
	 * Whether this person is waiting, was turned down, or neither.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string One of waiting, done, no, or an empty string.
	 */
	function kaamase_ask_state( $user_id ) {

		return (string) get_user_meta( (int) $user_id, KAAMASE_ASK_STATE_KEY, true );
	}
}

if ( ! function_exists( 'kaamase_ask_missing' ) ) {
	/**
	 * What is not ready yet on this account.
	 *
	 * Returned as a list of sentences rather than a yes or no, because
	 * being told no without being told why is the fastest way to lose
	 * somebody who was trying to take part.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string[] Empty when they may ask.
	 */
	function kaamase_ask_missing( $user_id ) {

		$user_id = (int) $user_id;
		$missing = array();

		$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( ! $profile || 'publish' !== get_post_status( $profile ) ) {

			$missing[] = __( 'Your profile needs to be finished and live first.', 'kaamase-core' );

			return $missing;
		}

		$phone = function_exists( 'kaamase_read_field' ) ? (string) kaamase_read_field( $profile, 'phone' ) : '';

		if ( '' === trim( $phone ) ) {
			$missing[] = __( 'Add your phone number to your profile. We cannot ring you without one.', 'kaamase-core' );
		}

		return $missing;
	}
}

if ( ! function_exists( 'kaamase_ask_can' ) ) {
	/**
	 * Whether this person may ask right now.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_ask_can( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return false;
		}

		// Already spoken to, and it is still showing. Nothing to ask for.
		if ( function_exists( 'kaamase_has_been_called' ) && kaamase_has_been_called( $user_id ) ) {
			return false;
		}

		// Already in the paid queue. They are getting a call anyway.
		if ( function_exists( 'kaamase_waiting_for_call' ) && kaamase_waiting_for_call( $user_id ) ) {
			return false;
		}

		if ( kaamase_ask_missing( $user_id ) ) {
			return false;
		}

		$state = kaamase_ask_state( $user_id );

		if ( 'waiting' === $state ) {
			return false;
		}

		// Had a free one in the last year. See free-lock.php.
		if ( function_exists( 'kaamase_free_is_locked' ) && kaamase_free_is_locked( $user_id ) ) {
			return false;
		}

		if ( 'no' === $state ) {

			$asked = (int) get_user_meta( $user_id, KAAMASE_ASK_AT_KEY, true );

			return ( time() - $asked ) > ( KAAMASE_ASK_AGAIN_DAYS * DAY_IN_SECONDS );
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_ask_locked_until' ) ) {
	/**
	 * Until when the one year rule is what stops this person asking.
	 *
	 * Nothing for somebody who has the tick, is waiting for a call, or is
	 * already on this list. The rule may still apply to them, but it is
	 * not the thing standing in their way, and telling somebody with a
	 * tick to wait a year for one is nonsense.
	 *
	 * @since 1.2.0
	 * @param int $user_id User ID.
	 * @return int Timestamp, or 0.
	 */
	function kaamase_ask_locked_until( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || ! function_exists( 'kaamase_free_locked_until' ) ) {
			return 0;
		}

		if ( function_exists( 'kaamase_has_been_called' ) && kaamase_has_been_called( $user_id ) ) {
			return 0;
		}

		if ( function_exists( 'kaamase_waiting_for_call' ) && kaamase_waiting_for_call( $user_id ) ) {
			return 0;
		}

		if ( 'waiting' === kaamase_ask_state( $user_id ) ) {
			return 0;
		}

		return kaamase_free_locked_until( $user_id );
	}
}

if ( ! function_exists( 'kaamase_ask_record' ) ) {
	/**
	 * Put somebody in the queue.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return bool Whether they were added.
	 */
	function kaamase_ask_record( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! kaamase_ask_can( $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, KAAMASE_ASK_AT_KEY, time() );
		update_user_meta( $user_id, KAAMASE_ASK_STATE_KEY, 'waiting' );

		/**
		 * Fires when somebody asks to be verified.
		 *
		 * @since 1.0.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_verify_asked', $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_ask_queue' ) ) {
	/**
	 * Everybody waiting, longest first.
	 *
	 * @since 1.0.0
	 * @return WP_User[]
	 */
	function kaamase_ask_queue() {

		$users = get_users(
			array(
				'meta_key'   => KAAMASE_ASK_STATE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 'waiting', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 200,
			)
		);

		$users = is_array( $users ) ? $users : array();

		usort(
			$users,
			static function ( $a, $b ) {
				return (int) get_user_meta( $a->ID, KAAMASE_ASK_AT_KEY, true )
					<=> (int) get_user_meta( $b->ID, KAAMASE_ASK_AT_KEY, true );
			}
		);

		return $users;
	}
}


/* ==========================================================================
   2. THE WORDS

   Held in one place because they are said in two, and the version that
   drifts is always the one nobody remembers exists. The website reads
   them from here and the app is sent them, so a change to what the mark
   means changes both at once.
   ========================================================================== */

if ( ! function_exists( 'kaamase_ask_copy' ) ) {
	/**
	 * Everything said about the tick, in one place.
	 *
	 * The body promises a telephone call and never promises the mark.
	 * The owner may ring somebody and decide not to give it, and copy
	 * that promised a tick would turn each of those into a broken
	 * promise made by the platform rather than a judgement made by a
	 * person.
	 *
	 * The last line is not padding. A platform that telephones people
	 * out of the blue has just taught several thousand of them to expect
	 * a call from it, and somebody else will notice that before we do.
	 * Saying now what we will never ask for is what makes the imitation
	 * detectable later.
	 *
	 * @since 1.1.0
	 * @return string[]
	 */
	function kaamase_ask_copy() {

		return array(
			'title'   => __( 'The tick on a profile', 'kaamase-core' ),
			'body'    => __( 'It means somebody at Kaam Ase telephoned that person and spoke to them. It is not sold and it does not move anybody up a list. It only tells a stranger they are real, which is the one thing a stranger cannot tell on their own.', 'kaamase-core' ),
			'button'  => __( 'Ask us to ring you', 'kaamase-core' ),
			'waiting' => __( 'You are on the list. We will ring the number on your profile. Keep it switched on and answer numbers you do not know for the next few days.', 'kaamase-core' ),
			'before'  => __( 'Before we can ring you:', 'kaamase-core' ),
			'safety'  => __( 'A real person rings the number on your profile. It takes a few minutes. We do not ask for money and we will never ask you for a code or a password on that call.', 'kaamase-core' ),
			'closed'  => __( 'We cannot take a new request on this account at the moment. Try again in a few weeks.', 'kaamase-core' ),
		);
	}
}


/* ==========================================================================
   3. THE BUTTON ON THE DASHBOARD
   ========================================================================== */

if ( ! function_exists( 'kaamase_ask_card' ) ) {
	/**
	 * Offer the call, or say what is missing.
	 *
	 * The wording promises a telephone call and never promises a mark,
	 * because the owner may ring somebody and decide not to give it. A
	 * card that promised the tick would turn every one of those into a
	 * broken promise.
	 *
	 * @since 1.0.0
	 * @param int    $user_id Who is looking.
	 * @param int    $profile Their profile.
	 * @param string $type    Which kind of profile.
	 * @return void
	 */
	function kaamase_ask_card( $user_id, $profile, $type ) {

		unset( $profile, $type );

		$user_id = (int) $user_id;

		if ( function_exists( 'kaamase_has_been_called' ) && kaamase_has_been_called( $user_id ) ) {
			return;
		}

		if ( function_exists( 'kaamase_waiting_for_call' ) && kaamase_waiting_for_call( $user_id ) ) {
			return;
		}

		$state   = kaamase_ask_state( $user_id );
		$missing = kaamase_ask_missing( $user_id );
		$copy    = kaamase_ask_copy();
		$locked  = function_exists( 'kaamase_free_lock_note' ) ? kaamase_free_lock_note( $user_id ) : '';
		$plans   = function_exists( 'kaamase_pay_plans_url' ) ? (string) kaamase_pay_plans_url() : '';
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">

			<h2><?php echo esc_html( $copy['title'] ); ?></h2>

			<p class="ka-small ka-soft ka-mt-4">
				<?php echo esc_html( $copy['body'] ); ?>
			</p>

			<?php if ( 'waiting' === $state ) : ?>

				<p class="ka-mt-4"><?php echo esc_html( $copy['waiting'] ); ?></p>

			<?php elseif ( '' !== $locked ) : ?>

				<p class="ka-mt-4"><?php echo esc_html( $locked ); ?></p>

				<?php if ( '' !== $plans && function_exists( 'kaamase_plan_name' ) ) : ?>
					<a class="ka-btn ka-btn--action ka-btn--sm ka-mt-4" href="<?php echo esc_url( $plans ); ?>">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: the plan name */
								__( 'See what %s gives you', 'kaamase-core' ),
								kaamase_plan_name()
							)
						);
						?>
					</a>
				<?php endif; ?>

			<?php elseif ( $missing ) : ?>

				<p class="ka-mt-4"><?php echo esc_html( $copy['before'] ); ?></p>

				<ul class="ka-mt-4">
					<?php foreach ( $missing as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>

			<?php elseif ( kaamase_ask_can( $user_id ) ) : ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ka-mt-4">

					<input type="hidden" name="action" value="kaamase_ask_verify">
					<?php wp_nonce_field( 'kaamase_ask_verify' ); ?>

					<button class="ka-btn ka-btn--outline" type="submit">
						<?php echo esc_html( $copy['button'] ); ?>
					</button>

					<p class="ka-small ka-soft ka-mt-4">
						<?php echo esc_html( $copy['safety'] ); ?>
					</p>

				</form>

			<?php else : ?>

				<p class="ka-mt-4"><?php echo esc_html( $copy['closed'] ); ?></p>

			<?php endif; ?>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_ask_card', 55, 3 );

if ( ! function_exists( 'kaamase_ask_handle' ) ) {
	/**
	 * Take the request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_ask_handle() {

		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Sign in first.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_ask_verify' );

		kaamase_ask_record( get_current_user_id() );

		$back = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : '';

		wp_safe_redirect( $back ? $back : home_url( '/' ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_ask_verify', 'kaamase_ask_handle' );


/* ==========================================================================
   4. THE LIST THE OWNER WORKS FROM
   ========================================================================== */

if ( ! function_exists( 'kaamase_ask_menu' ) ) {
	/**
	 * Add the queue under Kaam Ase, with a count on it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_ask_menu() {

		$waiting = count( kaamase_ask_queue() );

		$label = __( 'Asked to be verified', 'kaamase-core' );

		if ( $waiting ) {
			$label .= sprintf(
				' <span class="awaiting-mod"><span class="pending-count">%d</span></span>',
				(int) $waiting
			);
		}

		add_submenu_page(
			'kaamase',
			__( 'Asked to be verified', 'kaamase-core' ),
			$label,
			'promote_users',
			'kaamase-asked',
			'kaamase_ask_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_ask_menu', 25 );

if ( ! function_exists( 'kaamase_ask_page' ) ) {
	/**
	 * Render the queue.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_ask_page() {

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$done = isset( $_GET['done'] ) ? sanitize_key( wp_unslash( $_GET['done'] ) ) : '';

		$queue   = kaamase_ask_queue();
		$lengths = function_exists( 'kaamase_gift_lengths' ) ? kaamase_gift_lengths() : array();
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Asked to be verified', 'kaamase-core' ); ?></h1>

			<?php if ( 'given' === $done ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Done. Their profile has the mark now.', 'kaamase-core' ); ?></p>
				</div>
			<?php elseif ( 'no' === $done ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Taken off the list. They can ask again in a month.', 'kaamase-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p style="max-width:45em">
				<?php esc_html_e( 'People who have asked to be telephoned. Payers are not in this list, they are on the Calls to make screen and they go first. Ring the number, speak to them, then give it here. Whatever length you choose runs out on its own, and the mark goes with it.', 'kaamase-core' ); ?>
			</p>

			<?php if ( empty( $queue ) ) : ?>

				<p><?php esc_html_e( 'Nobody waiting.', 'kaamase-core' ); ?></p>

			<?php else : ?>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Who', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Number', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Asked', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'After the call', 'kaamase-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $queue as $person ) : ?>
							<?php
							$profile = (int) get_user_meta( $person->ID, 'kaamase_profile_id', true );
							$phone   = ( $profile && function_exists( 'kaamase_read_field' ) )
								? (string) kaamase_read_field( $profile, 'phone' )
								: '';
							$asked   = (int) get_user_meta( $person->ID, KAAMASE_ASK_AT_KEY, true );

							/*
							 * A free one they had before, shown so nobody
							 * has to remember it. Red while the one year
							 * rule still applies, which mostly means they
							 * asked before the rule existed.
							 */
							$had_until = function_exists( 'kaamase_free_until' ) ? kaamase_free_until( $person->ID ) : 0;
							$had_until = ( $had_until > 0 && $had_until <= time() ) ? $had_until : 0;
							$no_until  = function_exists( 'kaamase_free_locked_until' ) ? kaamase_free_locked_until( $person->ID ) : 0;
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $person->display_name ); ?></strong><br>
									<?php if ( $profile ) : ?>
										<a href="<?php echo esc_url( (string) get_permalink( $profile ) ); ?>" target="_blank" rel="noopener">
											<?php esc_html_e( 'See their profile', 'kaamase-core' ); ?>
										</a>
									<?php endif; ?>
									<?php if ( $had_until ) : ?>
										<br>
										<span class="description"<?php echo $no_until ? ' style="color:#b32d2e"' : ''; ?>>
											<?php
											echo esc_html(
												$no_until
													? sprintf(
														/* translators: 1: date a free one ended, 2: date they can ask again */
														__( 'Had free verification until %1$s. Cannot ask again before %2$s.', 'kaamase-core' ),
														kaamase_free_date( $had_until ),
														kaamase_free_date( $no_until )
													)
													: sprintf(
														/* translators: %s: date a free one ended */
														__( 'Had free verification until %s.', 'kaamase-core' ),
														kaamase_free_date( $had_until )
													)
											);
											?>
										</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $phone ) : ?>
										<a href="tel:<?php echo esc_attr( $phone ); ?>"><?php echo esc_html( $phone ); ?></a>
									<?php else : ?>
										<span class="description"><?php esc_html_e( 'None on the profile', 'kaamase-core' ); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $asked ? date_i18n( get_option( 'date_format' ), $asked ) : '' ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">

										<input type="hidden" name="action" value="kaamase_ask_decide">
										<input type="hidden" name="user" value="<?php echo esc_attr( $person->ID ); ?>">
										<?php wp_nonce_field( 'kaamase_ask_decide_' . $person->ID ); ?>

										<select name="length">
											<?php foreach ( $lengths as $key => $length ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>" <?php selected( 'month', $key ); ?>>
													<?php echo esc_html( $length['label'] ); ?>
												</option>
											<?php endforeach; ?>
										</select>

										<button class="button button-primary" type="submit" name="decision" value="give">
											<?php esc_html_e( 'Rang them, give it', 'kaamase-core' ); ?>
										</button>

										<button class="button" type="submit" name="decision" value="no">
											<?php esc_html_e( 'Not now', 'kaamase-core' ); ?>
										</button>

									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			<?php endif; ?>

		</div>
		<?php
	}
}

if ( ! function_exists( 'kaamase_ask_decide' ) ) {
	/**
	 * Give it, or take them off the list.
	 *
	 * Giving goes through the same gift as everything else, so the plan,
	 * the dates and the mark are set exactly as they are on the Give the
	 * plan screen. There is only one way to put a mark on a profile and
	 * this is not a second one.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_ask_decide() {

		if ( ! current_user_can( 'promote_users' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked immediately below.
		$user_id  = isset( $_POST['user'] ) ? absint( $_POST['user'] ) : 0;
		$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
		$length   = isset( $_POST['length'] ) ? sanitize_key( wp_unslash( $_POST['length'] ) ) : 'month';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		check_admin_referer( 'kaamase_ask_decide_' . $user_id );

		$back = admin_url( 'admin.php?page=kaamase-asked' );

		if ( ! $user_id ) {
			wp_safe_redirect( $back );
			exit;
		}

		if ( 'give' === $decision && function_exists( 'kaamase_gift_give' ) ) {

			$lengths = function_exists( 'kaamase_gift_lengths' ) ? kaamase_gift_lengths() : array();
			$days    = isset( $lengths[ $length ] ) ? (int) $lengths[ $length ]['days'] : 30;

			$plans   = function_exists( 'kaamase_pay_active_plans' ) ? kaamase_pay_active_plans() : array();
			$plan_id = $plans ? (string) key( $plans ) : '';

			kaamase_gift_give(
				$user_id,
				$plan_id,
				$days,
				true,
				false,
				__( 'Asked to be verified. Rung and spoken to.', 'kaamase-core' )
			);

			update_user_meta( $user_id, KAAMASE_ASK_STATE_KEY, 'done' );

			wp_safe_redirect( add_query_arg( 'done', 'given', $back ) );
			exit;
		}

		update_user_meta( $user_id, KAAMASE_ASK_STATE_KEY, 'no' );
		update_user_meta( $user_id, KAAMASE_ASK_AT_KEY, time() );

		wp_safe_redirect( add_query_arg( 'done', 'no', $back ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_ask_decide', 'kaamase_ask_decide' );


/* ==========================================================================
   5. THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_ask_shape_me' ) ) {
	/**
	 * Tell the app where this person stands.
	 *
	 * @since 1.0.0
	 * @param array $me      The account object.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_ask_shape_me( $me, $user_id ) {

		$copy   = kaamase_ask_copy();
		$locked = kaamase_ask_locked_until( $user_id );
		$note   = $locked ? kaamase_free_lock_note( $user_id ) : '';

		/*
		 * An app built before the one year rule shows copy.closed when it
		 * cannot ask, and "try again in a few weeks" would be untrue.
		 * The real date goes in its place, so that app says the right
		 * thing without an update.
		 */
		if ( '' !== $note ) {
			$copy['closed'] = $note;
		}

		/*
		 * The app reads "done" as "has the tick" and hides the whole card.
		 * The stored state stays "done" long after that stops being true:
		 * once a free one has ended, every app hid the card, so nobody saw
		 * the date they could ask again, nor the button once that date came.
		 * Sent as "done" only while it is true: the tick is on, or a call
		 * is owed after a name, number or photo change. Only what the app
		 * is sent changes; what is stored is left alone.
		 */
		$state = kaamase_ask_state( $user_id );

		if ( 'done' === $state ) {

			$ticked  = function_exists( 'kaamase_has_been_called' ) && kaamase_has_been_called( $user_id );
			$waiting = function_exists( 'kaamase_waiting_for_call' ) && kaamase_waiting_for_call( $user_id );

			if ( ! $ticked && ! $waiting ) {
				$state = '';
			}
		}

		$me['verify'] = array(
			'state'        => $state,
			'can_ask'      => kaamase_ask_can( $user_id ),
			// Empty while locked, as on the website: finishing a profile would not help.
			'missing'      => $locked ? array() : array_values( kaamase_ask_missing( $user_id ) ),
			'copy'         => $copy,
			'locked_until' => (int) $locked,
			'locked_note'  => $note,
		);

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_ask_shape_me', 26, 2 );

if ( ! function_exists( 'kaamase_ask_route' ) ) {
	/**
	 * Let the app ask.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_ask_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/verify-request',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_ask_rest',
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_ask_route' );

if ( ! function_exists( 'kaamase_ask_rest' ) ) {
	/**
	 * Take a request from the app.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_ask_rest( $request ) {

		unset( $request );

		$user_id = get_current_user_id();

		/*
		 * Said before anything else: a missing phone number is not worth
		 * mentioning to somebody who cannot ask for another reason.
		 */
		$locked = kaamase_ask_locked_until( $user_id );

		if ( $locked ) {

			$error = new WP_Error( 'kaamase_ask_locked', kaamase_free_lock_note( $user_id ), array( 'status' => 403 ) );

			if ( ! function_exists( 'kaamase_rest_error' ) ) {
				return $error;
			}

			$response = kaamase_rest_error( $error );
			$body     = (array) $response->get_data();

			$body['locked_until'] = (int) $locked;

			$response->set_data( $body );

			return $response;
		}

		$missing = kaamase_ask_missing( $user_id );

		if ( $missing ) {

			$error = new WP_Error( 'kaamase_not_ready', implode( ' ', $missing ), array( 'status' => 400 ) );

			return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $error ) : $error;
		}

		if ( ! kaamase_ask_record( $user_id ) ) {

			return rest_ensure_response(
				array(
					'ok'    => true,
					'state' => kaamase_ask_state( $user_id ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'ok'    => true,
				'state' => 'waiting',
			)
		);
	}
}