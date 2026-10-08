<?php
/**
 * Giving back ticks taken off by mistake.
 *
 * Before mark-changes.php 1.2.1, a person lost the tick for adding a
 * side to their own account -- an employer tapping "I also look for
 * work", a worker adding hiring, the phone repair on an employer
 * profile, a team listed with their own number. Each of those copies
 * the person's own number onto another profile of theirs, and the rule
 * counted that as a changed number. 1.2.1 stopped it happening again.
 * This screen finds the people it already happened to.
 *
 * Who is listed
 * -------------
 * Only an exact match, so nobody gets a tick back for a real change:
 *
 *   - the tick came off because of a change, and there is no call on
 *     record now (nobody has rung them again since);
 *   - everything that changed in that one save was a phone number going
 *     from empty to a number;
 *   - and that number is, today, on at least two of their own profiles:
 *     their own number, copied, not a new one.
 *
 * A name or a photograph changed in the same save, a number that went
 * from one number to another, or a number found on only one profile,
 * and the person is left off the list. Ringing them is the right thing
 * then, which is what the call list already says.
 *
 * What giving it back does
 * ------------------------
 * Puts the call back on record and takes them off the call list. The
 * call's own date was deleted when the tick came off, so the date the
 * tick came off is used in its place. The plan is not touched, as
 * nothing about the tick ever touches it: the tick shows again for
 * whoever's plan is still running, and comes back for the rest when
 * they renew, exactly as for any call.
 *
 * Nothing is given back without somebody pressing the button.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/** When a tick was given back from this screen. */
define( 'KAAMASE_MARK_RESTORED_KEY', '_kaamase_mark_restored' );


/* ==========================================================================
   1. WHO
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_restore_ready' ) ) {
	/**
	 * Whether the tick and its record are here to work with.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_mark_restore_ready() {

		return defined( 'KAAMASE_MARK_DROPPED_KEY' )
			&& defined( 'KAAMASE_CALLED_AT_KEY' )
			&& defined( 'KAAMASE_CALLED_BY_KEY' )
			&& function_exists( 'kaamase_mark_log' )
			&& function_exists( 'kaamase_mark_profile_types' );
	}
}

if ( ! function_exists( 'kaamase_mark_restore_profiles' ) ) {
	/**
	 * Every profile of an account the tick rules watch.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return int[]
	 */
	function kaamase_mark_restore_profiles( $user_id ) {

		$found = get_posts(
			array(
				'post_type'        => kaamase_mark_profile_types(),
				'author'           => (int) $user_id,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'   => 20,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		return is_array( $found ) ? array_map( 'intval', $found ) : array();
	}
}

if ( ! function_exists( 'kaamase_mark_restore_number' ) ) {
	/**
	 * A phone number in one form, for comparing.
	 *
	 * @since 1.0.0
	 * @param string $raw Number.
	 * @return string
	 */
	function kaamase_mark_restore_number( $raw ) {

		return function_exists( 'kaamase_sanitize_phone' )
			? (string) kaamase_sanitize_phone( (string) $raw )
			: preg_replace( '/\D+/', '', (string) $raw );
	}
}

if ( ! function_exists( 'kaamase_mark_restore_candidate' ) ) {
	/**
	 * Whether an account lost its tick to its own number being copied.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return array|null dropped (time) and number, or null.
	 */
	function kaamase_mark_restore_candidate( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || ! kaamase_mark_restore_ready() ) {
			return null;
		}

		$dropped = (int) get_user_meta( $user_id, KAAMASE_MARK_DROPPED_KEY, true );

		// Never lost one, or rung again since.
		if ( ! $dropped || (int) get_user_meta( $user_id, KAAMASE_CALLED_AT_KEY, true ) > 0 ) {
			return null;
		}

		// Everything recorded in the save that took it off: one request, a few seconds either side.
		$changes = array();

		foreach ( kaamase_mark_log( $user_id ) as $entry ) {
			if ( abs( (int) $entry['at'] - $dropped ) <= 10 ) {
				$changes[] = $entry;
			}
		}

		if ( empty( $changes ) ) {
			return null;
		}

		$numbers = array();

		foreach ( $changes as $entry ) {

			if ( 'phone' !== $entry['what'] || '' !== trim( (string) $entry['from'] ) ) {
				return null;
			}

			$number = kaamase_mark_restore_number( $entry['to'] );

			if ( '' === $number ) {
				return null;
			}

			$numbers[ $number ] = true;
		}

		// Their own number, copied: on two or more of their profiles today.
		$profiles = kaamase_mark_restore_profiles( $user_id );

		foreach ( array_keys( $numbers ) as $number ) {

			// A number used as an array key comes back as an integer.
			$number = (string) $number;
			$on     = 0;

			foreach ( $profiles as $profile ) {
				if ( kaamase_mark_restore_number( (string) kaamase_read_field( $profile, 'phone' ) ) === $number ) {
					++$on;
				}
			}

			if ( $on < 2 ) {
				return null;
			}
		}

		return array(
			'dropped' => $dropped,
			'number'  => (string) key( $numbers ),
		);
	}
}

if ( ! function_exists( 'kaamase_mark_restore_list' ) ) {
	/**
	 * Everybody it happened to who has not had the tick back.
	 *
	 * Only accounts that ever lost the tick to a change are looked at,
	 * which on any day is a handful.
	 *
	 * @since 1.0.0
	 * @return array[] Each: user, dropped, number.
	 */
	function kaamase_mark_restore_list() {

		if ( ! kaamase_mark_restore_ready() ) {
			return array();
		}

		$ids = get_users(
			array(
				'meta_key' => KAAMASE_MARK_DROPPED_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'fields'   => 'ID',
				'number'   => 2000,
			)
		);

		$out = array();

		foreach ( (array) $ids as $user_id ) {

			$found = kaamase_mark_restore_candidate( (int) $user_id );

			if ( $found ) {
				$out[] = array( 'user' => (int) $user_id ) + $found;
			}
		}

		return $out;
	}
}


/* ==========================================================================
   2. GIVING IT BACK
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_restore' ) ) {
	/**
	 * Give one account its tick back.
	 *
	 * Checks the account again first, so a stale page cannot give a
	 * tick to somebody whose record has moved on since it was drawn.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return bool Whether it was given back.
	 */
	function kaamase_mark_restore( $user_id ) {

		$user_id = (int) $user_id;
		$found   = kaamase_mark_restore_candidate( $user_id );

		if ( ! $found ) {
			return false;
		}

		update_user_meta( $user_id, KAAMASE_CALLED_AT_KEY, (int) $found['dropped'] );
		update_user_meta( $user_id, KAAMASE_CALLED_BY_KEY, get_current_user_id() );

		/*
		 * Off the call list, where taking the tick off put them. Only when
		 * they joined it then: somebody on it for another reason stays.
		 */
		if ( defined( 'KAAMASE_CALL_WAITING_KEY' ) ) {

			$waiting = (int) get_user_meta( $user_id, KAAMASE_CALL_WAITING_KEY, true );

			if ( $waiting && $waiting >= (int) $found['dropped'] - 10 ) {
				delete_user_meta( $user_id, KAAMASE_CALL_WAITING_KEY );
			}
		}

		delete_user_meta( $user_id, KAAMASE_MARK_DROPPED_KEY );
		update_user_meta( $user_id, KAAMASE_MARK_RESTORED_KEY, time() );

		// Their pages carry the tick, so no stored copy may keep the old one.
		foreach ( kaamase_mark_restore_profiles( $user_id ) as $profile ) {
			do_action( 'litespeed_purge_post', $profile );
		}

		/**
		 * Fires when a tick taken off by mistake is given back.
		 *
		 * @since 1.0.0
		 * @param int $user_id Account.
		 */
		do_action( 'kaamase_mark_restored', $user_id );

		return true;
	}
}


/* ==========================================================================
   3. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_restore_menu' ) ) {
	/**
	 * Add the screen under Kaam Ase, beside the call list.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_mark_restore_menu() {

		add_submenu_page(
			'kaamase',
			__( 'Ticks to give back', 'kaamase-core' ),
			__( 'Ticks to give back', 'kaamase-core' ),
			'promote_users',
			'kaamase-ticks-back',
			'kaamase_mark_restore_screen'
		);
	}
}
add_action( 'admin_menu', 'kaamase_mark_restore_menu', 21 );

if ( ! function_exists( 'kaamase_mark_restore_screen' ) ) {
	/**
	 * Render the screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_mark_restore_screen() {

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$given = isset( $_GET['given'] ) ? absint( $_GET['given'] ) : -1;
		$list  = kaamase_mark_restore_list();
		$url   = admin_url( 'admin-post.php' );
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Ticks to give back', 'kaamase-core' ); ?></h1>

			<?php if ( $given > -1 ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: how many people */
								_n( 'Given back to %s person.', 'Given back to %s people.', $given, 'kaamase-core' ),
								number_format_i18n( $given )
							)
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<p style="max-width:48em">
				<?php esc_html_e( 'These people lost the tick for adding a side to their own account, such as a worker adding hiring, because their own number was copied onto the new profile. That has been fixed. Each person here changed nothing but that: their number went from empty to the number already on another of their profiles.', 'kaamase-core' ); ?>
			</p>
			<p style="max-width:48em">
				<?php esc_html_e( 'Giving it back puts the call back on record, dated the day the tick came off, and takes them off the call list. It shows again at once if their plan is running, and when they renew if not. Anybody who changed a name, a photograph or a number to a different one is not listed: ring them as usual.', 'kaamase-core' ); ?>
			</p>

			<?php if ( empty( $list ) ) : ?>

				<p><strong><?php esc_html_e( 'Nobody to give back. Everybody this happened to has the tick again or has been rung since.', 'kaamase-core' ); ?></strong></p>

			<?php else : ?>

				<table class="widefat striped" style="max-width:60em">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Who', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Number', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Tick came off', 'kaamase-core' ); ?></th>
							<th><?php esc_html_e( 'Plan running', 'kaamase-core' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $list as $row ) : ?>
							<?php $person = get_userdata( $row['user'] ); ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_edit_user_link( $row['user'] ) ); ?>"><strong><?php echo esc_html( $person ? $person->display_name : '#' . $row['user'] ); ?></strong></a><br>
									<span class="description"><?php echo esc_html( $person ? $person->user_email : '' ); ?></span>
								</td>
								<td><?php echo esc_html( function_exists( 'kaamase_format_phone' ) ? kaamase_format_phone( $row['number'] ) : $row['number'] ); ?></td>
								<td><?php echo esc_html( wp_date( get_option( 'date_format' ), (int) $row['dropped'] ) ); ?></td>
								<td>
									<?php
									echo ( ! function_exists( 'kaamase_pay_is_active' ) || kaamase_pay_is_active( $row['user'] ) )
										? esc_html__( 'Yes', 'kaamase-core' )
										: esc_html__( 'No: shows when they renew', 'kaamase-core' );
									?>
								</td>
								<td>
									<form method="post" action="<?php echo esc_url( $url ); ?>">
										<input type="hidden" name="action" value="kaamase_mark_restore">
										<input type="hidden" name="user" value="<?php echo esc_attr( $row['user'] ); ?>">
										<?php wp_nonce_field( 'kaamase_mark_restore' ); ?>
										<button class="button" type="submit"><?php esc_html_e( 'Give the tick back', 'kaamase-core' ); ?></button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<form method="post" action="<?php echo esc_url( $url ); ?>" style="margin-top:1em">
					<input type="hidden" name="action" value="kaamase_mark_restore">
					<input type="hidden" name="user" value="all">
					<?php wp_nonce_field( 'kaamase_mark_restore' ); ?>
					<button class="button button-primary" type="submit">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: how many people */
								_n( 'Give the tick back to this %s person', 'Give the tick back to all %s people', count( $list ), 'kaamase-core' ),
								number_format_i18n( count( $list ) )
							)
						);
						?>
					</button>
				</form>

			<?php endif; ?>

		</div>
		<?php
	}
}

if ( ! function_exists( 'kaamase_mark_restore_handle' ) ) {
	/**
	 * Take the button.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_mark_restore_handle() {

		if ( ! current_user_can( 'promote_users' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_mark_restore' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		$who = isset( $_POST['user'] ) ? sanitize_key( wp_unslash( $_POST['user'] ) ) : '';

		$ids = 'all' === $who
			? wp_list_pluck( kaamase_mark_restore_list(), 'user' )
			: array( absint( $who ) );

		$given = 0;

		foreach ( $ids as $user_id ) {
			if ( $user_id && kaamase_mark_restore( (int) $user_id ) ) {
				++$given;
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=kaamase-ticks-back&given=' . $given ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_mark_restore', 'kaamase_mark_restore_handle' );
