<?php
/**
 * Rich Manu.
 *
 * A tick on an account, given after somebody at Kaam Ase has spoken to
 * the person on the phone.
 *
 * The one rule this file exists to hold
 * -------------------------------------
 * Paying does not give the tick. Paying puts somebody in a queue to be
 * rung. The tick is set by hand, afterwards, by whoever made the call.
 *
 * That separation is the whole product. A tick handed out by a payment
 * webhook means the person paid, while everybody reading it believes
 * somebody checked, and the gap is carried by whoever trusts it and
 * turns up to a job. A tick set after a call means what it says, and
 * the money is covering the call rather than buying the mark.
 *
 * Anybody may buy it, worker or employer, because here those are the
 * same people in different weeks. What the money buys is unlimited
 * urgent posts, which is a hiring capability, plus a place in the queue.
 * It does not buy a position above anybody in search, and nothing in
 * this file touches ranking.
 *
 * On the wording
 * --------------
 * The label is "We have spoken to them", not "Verified".
 *
 * The difference matters more than it looks. Verified sorts everybody
 * into verified and unverified, and unverified reads as doubtful. Most
 * workers here will never pay for a phone call, and the ones who cannot
 * spare it are the ones who most need the work. A platform whose trust
 * mark quietly sorts labourers by who had a spare few hundred rupees
 * has done something it cannot undo.
 *
 * "We have spoken to them" says what happened and implies nothing about
 * anybody it is missing from. It sits beside the vouch, the ratings and
 * the hire count, which are free, earned, and cannot be bought at all.
 *
 * @package KaamaseCore
 * @version 1.4.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;


/** When the call happened. Absent means it has not. */
define( 'KAAMASE_CALLED_AT_KEY', '_kaamase_called_at' );

/** Who made the call, so a decision has a name against it. */
define( 'KAAMASE_CALLED_BY_KEY', '_kaamase_called_by' );

/** When they paid and joined the queue. */
define( 'KAAMASE_CALL_WAITING_KEY', '_kaamase_call_waiting' );

/** A note from the call. */
define( 'KAAMASE_CALL_NOTE_KEY', '_kaamase_call_note' );


/* ==========================================================================
   1. STATE
   ========================================================================== */

if ( ! function_exists( 'kaamase_plan_name' ) ) {
	/**
	 * What the paid plan is called.
	 *
	 * One place, because it appears on the account screen, in the limit
	 * message, on the plans page and in two emails, and a product with
	 * four spellings looks like four products.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	function kaamase_plan_name() {

		/**
		 * Filter the plan name.
		 *
		 * @since 1.3.0
		 * @param string $name The name.
		 */
		return (string) apply_filters( 'kaamase_plan_name', __( 'Rich Manu', 'kaamase-core' ) );
	}
}

if ( ! function_exists( 'kaamase_has_been_called' ) ) {
	/**
	 * Whether somebody at Kaam Ase has spoken to this person.
	 *
	 * @since 1.3.0
	 * @param int $user_id Optional. Defaults to the current user.
	 * @return bool
	 */
	function kaamase_has_been_called( $user_id = 0 ) {

		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $user_id || (int) get_user_meta( $user_id, KAAMASE_CALLED_AT_KEY, true ) < 1 ) {
			return false;
		}

		/*
		 * The call happened, and the plan has to still be running.
		 *
		 * The call is a fact that never stops being true, so it was
		 * recorded once and never looked at again. That made the mark
		 * permanent: somebody who paid for one month kept a verified
		 * tick for good, and a mark that outlives the thing it was
		 * attached to stops meaning anything.
		 *
		 * Nothing is deleted when a plan lapses. The record of the call
		 * stays, so renewing brings the mark straight back with no
		 * second phone call, which is both correct and a good reason to
		 * renew.
		 *
		 * If the payment plugin is switched off entirely this returns
		 * true rather than false. Turning off payments is the owner's
		 * own act and should not quietly strip the mark from everybody
		 * who earned one.
		 */
		if ( ! function_exists( 'kaamase_pay_is_active' ) ) {
			return true;
		}

		return (bool) kaamase_pay_is_active( $user_id );
	}
}

if ( ! function_exists( 'kaamase_waiting_for_call' ) ) {
	/**
	 * Whether this person has paid and is still waiting to be rung.
	 *
	 * @since 1.3.0
	 * @param int $user_id Optional. Defaults to the current user.
	 * @return bool
	 */
	function kaamase_waiting_for_call( $user_id = 0 ) {

		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $user_id || kaamase_has_been_called( $user_id ) ) {
			return false;
		}

		return (int) get_user_meta( $user_id, KAAMASE_CALL_WAITING_KEY, true ) > 0;
	}
}

if ( ! function_exists( 'kaamase_called_label' ) ) {
	/**
	 * The words on the mark.
	 *
	 * See the note at the top of this file on why it does not say
	 * verified.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	function kaamase_called_label() {

		/**
		 * Filter the label on the mark.
		 *
		 * @since 1.3.0
		 * @param string $label The label.
		 */
		return (string) apply_filters(
			'kaamase_called_label',
			__( 'Verified', 'kaamase-core' )
		);
	}
}


/* ==========================================================================
   2. JOINING THE QUEUE, AND LEAVING IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_join_call_queue' ) ) {
	/**
	 * Put somebody in the queue to be rung.
	 *
	 * Called when a payment lands. Notably it does not set the tick, and
	 * that is the point of the file.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_join_call_queue( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || kaamase_has_been_called( $user_id ) ) {
			return;
		}

		if ( kaamase_waiting_for_call( $user_id ) ) {
			return;
		}

		update_user_meta( $user_id, KAAMASE_CALL_WAITING_KEY, time() );

		/**
		 * Fires when somebody joins the queue to be called.
		 *
		 * @since 1.3.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_call_queued', $user_id );

		kaamase_notify_call_queued( $user_id );
	}
}

if ( ! function_exists( 'kaamase_record_call' ) ) {
	/**
	 * Record that the call happened, and give the tick.
	 *
	 * @since 1.3.0
	 * @param int    $user_id User who was called.
	 * @param string $note    Anything worth remembering from the call.
	 * @return bool
	 */
	function kaamase_record_call( $user_id, $note = '' ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return false;
		}

		update_user_meta( $user_id, KAAMASE_CALLED_AT_KEY, time() );
		update_user_meta( $user_id, KAAMASE_CALLED_BY_KEY, get_current_user_id() );
		update_user_meta( $user_id, KAAMASE_CALL_NOTE_KEY, sanitize_textarea_field( (string) $note ) );

		delete_user_meta( $user_id, KAAMASE_CALL_WAITING_KEY );

		/**
		 * Fires after a verification call is recorded.
		 *
		 * @since 1.3.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_call_recorded', $user_id );

		kaamase_notify_called( $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_undo_call' ) ) {
	/**
	 * Take the tick away.
	 *
	 * Needed because a mark given by hand can be given in error, and a
	 * trust mark nobody can remove is a trust mark that eventually
	 * cannot be trusted.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_undo_call( $user_id ) {

		$user_id = (int) $user_id;

		delete_user_meta( $user_id, KAAMASE_CALLED_AT_KEY );
		delete_user_meta( $user_id, KAAMASE_CALLED_BY_KEY );

		/**
		 * Fires when a tick is removed.
		 *
		 * @since 1.3.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_call_undone', $user_id );
	}
}


/* ==========================================================================
   3. TELLING THEM
   ========================================================================== */

if ( ! function_exists( 'kaamase_notify_call_queued' ) ) {
	/**
	 * Tell somebody their payment arrived and a call is coming.
	 *
	 * Sent because the thing they bought does not appear immediately,
	 * and a payment that produces nothing visible is a payment somebody
	 * assumes has failed.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_notify_call_queued( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user || ! $user->user_email ) {
			return;
		}

		/*
		 * Marked paid by hand from the admin. kaamase_plan_name() is
		 * inside the switch with everything else, because the name of
		 * the thing they bought is a translated string too.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $user->ID );

		$subject = sprintf(
			/* translators: %s: plan name */
			__( 'We will call you about %s', 'kaamase-core' ),
			kaamase_plan_name()
		);

		$body = sprintf(
			/* translators: 1: display name, 2: plan name */
			__(
				"Hello %1\$s,\n\nThank you. %2\$s is on your account and you can post as many urgent jobs as you need, starting now.\n\nThe tick is the other half and it comes after we speak to you. Somebody from Kaam Ase will ring the number on your profile in the next day or two. It is a short call: we confirm you are who you say you are, and then the tick goes on your account.\n\nIf your number has changed, update it on your profile so we can reach you.\n",
				'kaamase-core'
			),
			$user->display_name,
			kaamase_plan_name()
		);

		wp_mail( $user->user_email, $subject, $body );

		if ( $switched ) {
			kaamase_locale_restore();
		}
	}
}

if ( ! function_exists( 'kaamase_notify_called' ) ) {
	/**
	 * Tell somebody the tick is on their account.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_notify_called( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user || ! $user->user_email ) {
			return;
		}

		/*
		 * Same again: the tick goes on after the call, from the admin.
		 * The label on the mark is translated, so it is read inside.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $user->ID );

		$subject = __( 'Your tick is on your account', 'kaamase-core' );

		$body = sprintf(
			/* translators: 1: display name, 2: the label shown on the mark */
			__(
				"Hello %1\$s,\n\nThank you for taking our call. Your profile now carries the mark that says \"%2\$s\".\n\nIt shows wherever you appear, whether you are looking for work or hiring somebody.\n",
				'kaamase-core'
			),
			$user->display_name,
			kaamase_called_label()
		);

		wp_mail( $user->user_email, $subject, $body );

		if ( $switched ) {
			kaamase_locale_restore();
		}
	}
}


/* ==========================================================================
   4. THE CALL LIST

   The screen that makes this workable. Everything needed to make the
   call is on one row: who, which number, when they paid, how long they
   have been waiting.
   ========================================================================== */

if ( ! function_exists( 'kaamase_call_queue' ) ) {
	/**
	 * Everybody waiting to be rung, longest wait first.
	 *
	 * @since 1.3.0
	 * @return WP_User[]
	 */
	function kaamase_call_queue() {

		$users = get_users(
			array(
				'meta_key' => KAAMASE_CALL_WAITING_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'  => 'meta_value_num',
				'order'    => 'ASC',
				'number'   => 100,
			)
		);

		return is_array( $users ) ? $users : array();
	}
}

if ( ! function_exists( 'kaamase_calls_menu' ) ) {
	/**
	 * Add the call list under Kaam Ase.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_calls_menu() {

		$waiting = count( kaamase_call_queue() );

		$label = __( 'Calls to make', 'kaamase-core' );

		if ( $waiting ) {
			$label .= sprintf(
				' <span class="awaiting-mod"><span class="pending-count">%d</span></span>',
				(int) $waiting
			);
		}

		add_submenu_page(
			'kaamase',
			__( 'Calls to make', 'kaamase-core' ),
			$label,
			'promote_users',
			'kaamase-calls',
			'kaamase_calls_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_calls_menu', 20 );

if ( ! function_exists( 'kaamase_calls_page' ) ) {
	/**
	 * Render the call list.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_calls_page() {

		if ( ! current_user_can( 'promote_users' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		kaamase_handle_call_action();

		$queue = kaamase_call_queue();
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Calls to make', 'kaamase-core' ); ?></h1>

			<p class="description" style="max-width:44em">
				<?php
				printf(
					/* translators: %s: plan name */
					esc_html__( 'People who have paid for %s and are waiting to be rung. They already have unlimited urgent posts. The tick goes on only after you have spoken to them, which is what makes it worth anything.', 'kaamase-core' ),
					esc_html( kaamase_plan_name() )
				);
				?>
			</p>

			<?php if ( empty( $queue ) ) : ?>

				<p><?php esc_html_e( 'Nobody waiting.', 'kaamase-core' ); ?></p>

			<?php else : ?>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Person', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Ring', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Waiting', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'After the call', 'kaamase-core' ); ?></th>
						</tr>
					</thead>
					<tbody>

					<?php foreach ( $queue as $user ) : ?>
						<?php
						$since = (int) get_user_meta( $user->ID, KAAMASE_CALL_WAITING_KEY, true );
						$phone = kaamase_user_phone( $user->ID );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $user->display_name ); ?></strong><br>
								<span class="description"><?php echo esc_html( $user->user_email ); ?></span>

								<?php
								/*
								 * What they changed, if anything, since
								 * that decides what kind of call this is.
								 * Somebody who went from Imliakum to
								 * Imliakum Jamir needs thirty seconds;
								 * somebody who went to the name of a
								 * company that exists needs a different
								 * conversation entirely.
								 */
								$changes = function_exists( 'kaamase_mark_log_summary' )
									? kaamase_mark_log_summary( $user->ID )
									: array();
								?>

								<?php if ( ! empty( $changes ) ) : ?>
									<p style="margin:6px 0 0;padding:6px 8px;background:#fcf3d9;border-left:3px solid #b7791f;">
										<strong><?php esc_html_e( 'Changed since the call', 'kaamase-core' ); ?></strong><br>
										<?php foreach ( $changes as $line ) : ?>
											<span class="description"><?php echo esc_html( $line ); ?></span><br>
										<?php endforeach; ?>
									</p>
								<?php endif; ?>
							</td>

							<td>
								<?php if ( $phone ) : ?>
									<a href="<?php echo esc_url( 'tel:' . $phone ); ?>">
										<strong><?php echo esc_html( $phone ); ?></strong>
									</a>
								<?php else : ?>
									<span style="color:#b32d2e">
										<?php esc_html_e( 'No number on file', 'kaamase-core' ); ?>
									</span>
								<?php endif; ?>
							</td>

							<td>
								<?php
								echo esc_html(
									$since
										? sprintf(
											/* translators: %s: human readable time difference */
											__( '%s', 'kaamase-core' ),
											human_time_diff( $since, time() )
										)
										: '&mdash;'
								);
								?>
							</td>

							<td>
								<form method="post">
									<?php wp_nonce_field( 'kaamase_call_action', 'kaamase_call_nonce' ); ?>
									<input type="hidden" name="user" value="<?php echo esc_attr( (string) $user->ID ); ?>">

									<input type="text"
										name="note"
										class="regular-text"
										placeholder="<?php esc_attr_e( 'Anything worth remembering', 'kaamase-core' ); ?>"
										style="margin-bottom:6px">
									<br>

									<button type="submit" name="decision" value="done" class="button button-primary">
										<?php esc_html_e( 'Spoken to them', 'kaamase-core' ); ?>
									</button>

									<button type="submit" name="decision" value="unreachable" class="button">
										<?php esc_html_e( 'Could not reach', 'kaamase-core' ); ?>
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

if ( ! function_exists( 'kaamase_user_phone' ) ) {
	/**
	 * The number to ring, from whichever profile has one.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return string
	 */
	function kaamase_user_phone( $user_id ) {

		if ( ! function_exists( 'kaamase_field' ) ) {
			return '';
		}

		foreach ( array( 'kaamase_worker', 'kaamase_employer' ) as $type ) {

			$profile = kaamase_get_user_profile( (int) $user_id, $type );

			if ( ! $profile ) {
				continue;
			}

			$phone = trim( (string) kaamase_field( $profile, 'phone', '' ) );

			if ( '' !== $phone ) {
				return $phone;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_handle_call_action' ) ) {
	/**
	 * Act on a press from the call list.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_handle_call_action() {

		if ( ! isset( $_POST['kaamase_call_nonce'], $_POST['user'], $_POST['decision'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_call_nonce'] ) ), 'kaamase_call_action' ) ) {
			return;
		}

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		$user_id  = absint( $_POST['user'] );
		$decision = sanitize_key( wp_unslash( $_POST['decision'] ) );
		$note     = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( ! $user_id ) {
			return;
		}

		if ( 'done' === $decision ) {

			kaamase_record_call( $user_id, $note );

			echo '<div class="notice notice-success"><p>'
				. esc_html__( 'Tick added, and they have been emailed.', 'kaamase-core' )
				. '</p></div>';

			return;
		}

		/*
		 * Could not reach them. They stay in the queue, because they
		 * paid and a missed call is not a refusal. The note is where
		 * "tried twice, no answer" lives.
		 */
		update_user_meta( $user_id, KAAMASE_CALL_NOTE_KEY, $note );

		echo '<div class="notice notice-info"><p>'
			. esc_html__( 'Noted. They stay on the list, because they have paid and not yet had their call.', 'kaamase-core' )
			. '</p></div>';
	}
}


/* ==========================================================================
   4b. DOING IT BY HAND

   The call list only knows about people whose payment reached the
   server. When one does not, through a failed webhook or a browser
   closed at the wrong moment, that person is invisible to it: they have
   paid, they are owed the mark, and there is no button anywhere that
   gives it to them.

   This is the way round that. It is on the user's own record, where
   somebody looking into a complaint is already standing.
   ========================================================================== */

if ( ! function_exists( 'kaamase_called_user_field' ) ) {
	/**
	 * Show and set the mark on a user record.
	 *
	 * @since 1.4.1
	 * @param WP_User $user The user being edited.
	 * @return void
	 */
	function kaamase_called_user_field( $user ) {

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		$called  = (int) get_user_meta( $user->ID, KAAMASE_CALLED_AT_KEY, true );
		$waiting = kaamase_waiting_for_call( $user->ID );
		$showing = kaamase_has_been_called( $user->ID );
		?>
		<h2><?php esc_html_e( 'Kaam Ase verification', 'kaamase-core' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'The call', 'kaamase-core' ); ?></th>
				<td>
					<p>
						<?php
						if ( $called ) {
							printf(
								/* translators: %s: date */
								esc_html__( 'Called on %s.', 'kaamase-core' ),
								esc_html( date_i18n( get_option( 'date_format' ), $called ) )
							);
						} elseif ( $waiting ) {
							esc_html_e( 'Paid and waiting to be rung.', 'kaamase-core' );
						} else {
							esc_html_e( 'Not called.', 'kaamase-core' );
						}
						?>
					</p>

					<?php if ( $called && ! $showing ) : ?>
						<p class="description" style="max-width:40em">
							<?php esc_html_e( 'The call is recorded but the mark is not showing, because their plan is not running. It comes back on its own if they pay again. Nothing needs doing here.', 'kaamase-core' ); ?>
						</p>
					<?php endif; ?>

					<p>
						<label>
							<input type="checkbox" name="kaamase_called_toggle" value="1">
							<?php
							echo esc_html(
								$called
									? __( 'Remove the call record and the mark', 'kaamase-core' )
									: __( 'Record that I have spoken to this person', 'kaamase-core' )
							);
							?>
						</label>
					</p>

					<p class="description" style="max-width:40em">
						<?php esc_html_e( 'Use this when somebody has paid but never appeared on the Calls to make list, which happens if their payment did not reach the server. Only tick it after you have actually telephoned them: the mark tells workers we have, and it is worth nothing the moment that stops being true.', 'kaamase-core' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php

		wp_nonce_field( 'kaamase_called_user', 'kaamase_called_user_nonce' );
	}
}
add_action( 'edit_user_profile', 'kaamase_called_user_field' );
add_action( 'show_user_profile', 'kaamase_called_user_field' );

if ( ! function_exists( 'kaamase_called_user_save' ) ) {
	/**
	 * Save the toggle.
	 *
	 * @since 1.4.1
	 * @param int $user_id User being saved.
	 * @return void
	 */
	function kaamase_called_user_save( $user_id ) {

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		if ( ! isset( $_POST['kaamase_called_user_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['kaamase_called_user_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'kaamase_called_user' ) ) {
			return;
		}

		if ( empty( $_POST['kaamase_called_toggle'] ) ) {
			return;
		}

		if ( (int) get_user_meta( $user_id, KAAMASE_CALLED_AT_KEY, true ) > 0 ) {
			kaamase_undo_call( $user_id );

			return;
		}

		kaamase_record_call( $user_id, __( 'Recorded by hand from the user record.', 'kaamase-core' ) );
	}
}
add_action( 'edit_user_profile_update', 'kaamase_called_user_save' );
add_action( 'personal_options_update', 'kaamase_called_user_save' );


/* ==========================================================================
   5. SHOWING THE MARK

   On both sides of an account, because the call was about the person
   and a person is not two different people on two screens.
   ========================================================================== */

if ( ! function_exists( 'kaamase_called_mark' ) ) {
	/**
	 * The mark for one account, ready to send or render.
	 *
	 * Returns the same shape whether or not the person has one, so a
	 * screen can render it without asking twice.
	 *
	 * @since 1.3.0
	 * @param int $user_id Whose account.
	 * @return array{called: bool, label: string, since: int}
	 */
	function kaamase_called_mark( $user_id ) {

		$user_id = (int) $user_id;
		$called  = kaamase_has_been_called( $user_id );

		return array(
			'called' => $called,
			'label'  => $called ? kaamase_called_label() : '',
			'since'  => $called ? (int) get_user_meta( $user_id, KAAMASE_CALLED_AT_KEY, true ) : 0,
		);
	}
}

if ( ! function_exists( 'kaamase_shape_me_called' ) ) {
	/**
	 * Tell the app whether this account carries the mark.
	 *
	 * @since 1.3.0
	 * @param array $me      The account.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_shape_me_called( $me, $user_id ) {

		$me['called']         = kaamase_called_mark( (int) $user_id );
		$me['awaiting_call']  = kaamase_waiting_for_call( (int) $user_id );
		$me['plan_name']      = kaamase_plan_name();

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_shape_me_called', 10, 2 );

if ( ! function_exists( 'kaamase_shape_profile_called' ) ) {
	/**
	 * Put the mark on a profile, whichever side it is.
	 *
	 * The same call covers both, because it was a call to a person
	 * rather than to one of their profiles.
	 *
	 * @since 1.3.0
	 * @param array   $shaped The profile as sent to the app.
	 * @param WP_Post $post   The profile.
	 * @return array
	 */
	function kaamase_shape_profile_called( $shaped, $post ) {

		if ( ! is_array( $shaped ) || ! $post instanceof WP_Post ) {
			return $shaped;
		}

		$shaped['called'] = kaamase_called_mark( (int) $post->post_author );

		return $shaped;
	}
}
add_filter( 'kaamase_shape_worker', 'kaamase_shape_profile_called', 10, 2 );
add_filter( 'kaamase_shape_employer', 'kaamase_shape_profile_called', 10, 2 );
add_filter( 'kaamase_shape_job', 'kaamase_shape_profile_called', 14, 2 );


if ( ! function_exists( 'kaamase_verified_explainer' ) ) {
	/**
	 * What the tick means, for somebody who taps it.
	 *
	 * The whole reason a tick is worth anything is that people can find
	 * out what it stands for. On the platforms this borrows its shape
	 * from, tapping the mark opens a short, plain statement, and without
	 * that a tick is just decoration somebody might have drawn on.
	 *
	 * Every sentence is one this platform can actually stand behind. It
	 * says a person was telephoned, because one was. It says what was
	 * confirmed, and stops there rather than implying the work itself
	 * was inspected or the person vouched for beyond that. And it says
	 * the mark depends on the plan, because it now does, and finding
	 * that out later would feel like a trick.
	 *
	 * @since 1.4.0
	 * @return string[] Lines, in reading order.
	 */
	function kaamase_verified_explainer() {

		$lines = array(
			__( 'Somebody from Kaam Ase telephoned this person and confirmed they are who they say they are.', 'kaamase-core' ),
			__( 'They also pay for a Kaam Ase plan. The mark stays while that plan is running and goes when it stops.', 'kaamase-core' ),
			__( 'It says we have spoken to them. It is not a promise about the quality of their work, and it does not replace agreeing the rate and the payment day before anybody starts.', 'kaamase-core' ),
		);

		/**
		 * Filter the explanation shown when somebody opens the mark.
		 *
		 * @since 1.4.0
		 * @param string[] $lines The explanation.
		 */
		return (array) apply_filters( 'kaamase_verified_explainer', $lines );
	}
}

if ( ! function_exists( 'kaamase_called_badge' ) ) {
	/**
	 * The mark itself, for the website.
	 *
	 * A drawn tick in a filled circle rather than a character from a
	 * font. Three reasons, and the last is the one that matters.
	 *
	 * A tick character renders differently on every phone, and on some
	 * of the cheaper Android builds common here it renders as an empty
	 * box, which on a trust mark is worse than showing nothing at all.
	 *
	 * It also has to read as deliberate. This is the one thing on the
	 * platform somebody has paid for and been telephoned about, and a
	 * grey pill with a text tick in it looks like a tag rather than a
	 * mark.
	 *
	 * And it must not be mistakable for the amber used on buttons.
	 * Nobody should ever tap a trust mark expecting it to do something.
	 *
	 * @since 1.3.0
	 * @param int  $user_id Whose account.
	 * @param bool $compact True on a card, where there is no room for words.
	 * @return string Markup, or an empty string when there is no mark.
	 */
	function kaamase_called_badge( $user_id, $compact = false ) {

		if ( ! kaamase_has_been_called( (int) $user_id ) ) {
			return '';
		}

		$label = kaamase_called_label();

		$tick = '<span class="ka-tick" aria-hidden="true">'
			. '<svg viewBox="0 0 24 24" focusable="false"><path d="M9.6 16.2 5.4 12l1.4-1.4 2.8 2.8 7.6-7.6L18.6 7z"/></svg>'
			. '</span>';

		if ( $compact ) {
			return sprintf(
				'<span class="ka-called ka-called--compact" title="%1$s">%2$s<span class="ka-sr">%1$s</span></span>',
				esc_attr( $label ),
				$tick
			);
		}

		/*
		 * Opens on a tap, using details and summary rather than a
		 * script.
		 *
		 * A tick nobody can question is worth less than one they can,
		 * and this has to work on a cheap phone with a bad connection
		 * where a script may not have loaded yet. The browser handles
		 * the opening on its own, so it works the moment the page does.
		 */
		$explainer = '';

		foreach ( kaamase_verified_explainer() as $line ) {
			$explainer .= '<p>' . esc_html( $line ) . '</p>';
		}

		return sprintf(
			'<details class="ka-called-wrap">'
			. '<summary class="ka-called">%1$s<span class="ka-called__text">%2$s</span></summary>'
			. '<div class="ka-called__panel">%3$s</div>'
			. '</details>',
			$tick,
			esc_html( $label ),
			$explainer
		);
	}
}


/* ==========================================================================
   6. THE UPSELL

   Shown at the moment somebody is stopped, which is the only moment it
   is useful rather than an advertisement.
   ========================================================================== */

if ( ! function_exists( 'kaamase_urgent_upsell_message' ) ) {
	/**
	 * What to say when somebody runs out of urgent posts.
	 *
	 * Replaces the general limit sentence for this one meter, because
	 * this is the limit the plan exists to lift and telling somebody to
	 * come back tomorrow when they could carry on now is a lost sale
	 * and a worse answer.
	 *
	 * Only when there is actually something to buy. Offering a plan on
	 * a site that is not charging is worse than saying nothing.
	 *
	 * @since 1.3.0
	 * @param string $message The message so far.
	 * @param string $key     Which meter ran out.
	 * @return string
	 */
	function kaamase_urgent_upsell_message( $message, $key ) {

		if ( 'urgent_jobs' !== $key ) {
			return $message;
		}

		if ( ! function_exists( 'kaamase_charging_is_on' ) || ! kaamase_charging_is_on() ) {
			return $message;
		}

		/*
		 * Not inside either app, and this is a store rule rather than a
		 * preference.
		 *
		 * Outside the United States Apple does not allow an app to point
		 * somebody at a purchase it does not take a share of, and naming
		 * a plan that can only be bought on the website is a call to
		 * action whether or not a link is attached. Google's billing
		 * policy is no friendlier.
		 *
		 * So the app gets the plain sentence about the limit and nothing
		 * about the plan. It is the same check the payment plugin uses
		 * for the link, asked here as well because this message is set
		 * before that one runs and would otherwise carry the product
		 * name past it.
		 */
		if ( function_exists( 'kaamase_pay_platform_may_offer' )
			&& function_exists( 'kaamase_pay_requesting_platform' )
			&& ! kaamase_pay_platform_may_offer( kaamase_pay_requesting_platform() ) ) {

			return $message;
		}

		return sprintf(
			/* translators: %s: plan name */
			__( 'You already have an urgent job running. Urgent only means anything while it is rare, so everybody gets one at a time. With %s you can mark as many as you need, and we ring you and put a tick on your profile.', 'kaamase-core' ),
			kaamase_plan_name()
		);
	}
}
add_filter( 'kaamase_limit_reached_message', 'kaamase_urgent_upsell_message', 5, 2 );