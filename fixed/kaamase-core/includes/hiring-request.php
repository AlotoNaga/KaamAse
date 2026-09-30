<?php
/**
 * Hiring access requests.
 *
 * A worker who also hires people asks for it, an administrator approves,
 * and the account gains the ability to post jobs without losing anything
 * it already had.
 *
 * Why not two accounts
 * --------------------
 * The obvious answer to somebody who is both is to tell them to register
 * again as an employer. It is also the wrong one.
 *
 * A mason with four years of ratings who registers a second time starts
 * at zero. Two logins, two profiles, two phone numbers to keep straight,
 * and every rating they earned sitting on the account they are not
 * currently signed in to. On a platform whose entire value is that the
 * next person can see who has vouched for you and how the last job went,
 * splitting that history in half is not a small inconvenience.
 *
 * It is also not an edge case here. A mason who subcontracts, a carpenter
 * who takes on two helpers for a big job, a shop owner who works with his
 * hands and hires for the rest. That is ordinary in Nagaland, not unusual.
 *
 * How it works instead
 * --------------------
 * WordPress lets a user hold more than one role, and capabilities are the
 * union of all of them. So approval calls add_role rather than set_role:
 * the worker role stays exactly as it was, the employer role is added
 * beside it, and create_kaamase_jobs comes with it. Nothing else in the
 * plugin needs changing, because everything already asks about the
 * capability rather than the role. The app included, since can_post_jobs
 * in the REST layer is that same capability check.
 *
 * Instant, not approved
 * ---------------------
 * Adding hiring happens the moment they ask. Nobody waits.
 *
 * A queue would have been a filter against somebody who wanted to reach
 * workers for the wrong reason, but it is the wrong place to put that
 * filter. What such a person actually does is post the advertisement,
 * and the advertisement is screened whoever posts it. Holding an honest
 * mason for two days to slow down somebody who is stopped at the next
 * gate anyway costs the platform far more than it protects it.
 *
 * It is reversible. Withdrawing access removes the employer role and
 * leaves the worker profile, the ratings and the login untouched.
 *
 * @package KaamaseCore
 * @version 1.2.0
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;


/** Where the request state lives on the user. */
define( 'KAAMASE_HIRING_STATUS_KEY', '_kaamase_hiring_status' );

/** When it was asked for. */
define( 'KAAMASE_HIRING_TIME_KEY', '_kaamase_hiring_time' );

/** Set when the person added hiring themselves rather than an admin doing it. */
define( 'KAAMASE_HIRING_SELF_KEY', '_kaamase_hiring_self' );


/* ==========================================================================
   1. STATE
   ========================================================================== */

if ( ! function_exists( 'kaamase_hiring_status' ) ) {
	/**
	 * Where a user's request stands.
	 *
	 * @since 1.1.0
	 * @param int $user_id Optional. Defaults to the current user.
	 * @return string One of none, approved or declined.
	 */
	function kaamase_hiring_status( $user_id = 0 ) {

		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $user_id ) {
			return 'none';
		}

		// The capability is the truth. Anything else is a stale note.
		if ( user_can( $user_id, 'create_kaamase_jobs' ) ) {
			return 'approved';
		}

		$status = (string) get_user_meta( $user_id, KAAMASE_HIRING_STATUS_KEY, true );

		return 'declined' === $status ? 'declined' : 'none';
	}
}

if ( ! function_exists( 'kaamase_dual_role_users' ) ) {
	/**
	 * Accounts that are a worker and an employer at once.
	 *
	 * There is no approval queue any more, so this screen exists for the
	 * opposite job: seeing who has hiring and being able to take it away
	 * from somebody who has misused it.
	 *
	 * @since 1.1.0
	 * @return WP_User[]
	 */
	function kaamase_dual_role_users() {

		$users = get_users(
			array(
				'role__in' => array( 'kaamase_worker' ),
				'number'   => 200,
				'orderby'  => 'ID',
				'order'    => 'DESC',
			)
		);

		if ( ! is_array( $users ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$users,
				static function ( $user ) {
					return in_array( 'kaamase_employer', (array) $user->roles, true );
				}
			)
		);
	}
}


/* ==========================================================================
   2. ASKING

   Rendered inside the notice on the post a job page, so the answer sits
   where the person hit the wall rather than on a help page they would
   have to go looking for.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hiring_request_notice' ) ) {
	/**
	 * The whole block shown to somebody who cannot post jobs.
	 *
	 * @since 1.1.0
	 * @return string Markup.
	 */
	function kaamase_hiring_request_notice() {

		$status = kaamase_hiring_status();

		if ( 'declined' === $status ) {
			return sprintf(
				'<div class="ka-notice ka-notice--warn"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--ghost ka-mt-4" href="%3$s">%4$s</a></div></div>',
				esc_html__( 'Hiring was not added to this account', 'kaamase-core' ),
				esc_html__( 'If you think this is a mistake, get in touch and tell us about the work you hire for.', 'kaamase-core' ),
				esc_url( kaamase_page_url( 'contact' ) ),
				esc_html__( 'Contact us', 'kaamase-core' )
			);
		}

		ob_start();
		?>

		<div class="ka-notice ka-notice--warn">
			<div>
				<span class="ka-notice__title">
					<?php esc_html_e( 'This account cannot post jobs yet', 'kaamase-core' ); ?>
				</span>

				<p>
					<?php esc_html_e( 'You registered as a worker. If you also hire people, add hiring to this same account. It happens straight away. You keep your profile, your ratings and your login exactly as they are.', 'kaamase-core' ); ?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ka-mt-6">

					<input type="hidden" name="action" value="kaamase_request_hiring">
					<?php wp_nonce_field( 'kaamase_request_hiring', 'kaamase_hiring_nonce' ); ?>

					<button type="submit" class="ka-btn ka-btn--action ka-btn--lg">
						<?php esc_html_e( 'Add hiring to my account', 'kaamase-core' ); ?>
					</button>

				</form>

				<p class="ka-small ka-soft ka-mt-4">
					<?php esc_html_e( 'Every job posted on Kaam Ase is checked. Anything that is not real work is removed and the account is closed.', 'kaamase-core' ); ?>
				</p>
			</div>
		</div>

		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_handle_hiring_request' ) ) {
	/**
	 * Store a request.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	function kaamase_handle_hiring_request() {

		$back = kaamase_page_url( 'post-job' );

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( $back );
			exit;
		}

		if ( ! isset( $_POST['kaamase_hiring_nonce'] ) ) {
			wp_safe_redirect( $back );
			exit;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['kaamase_hiring_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'kaamase_request_hiring' ) ) {
			wp_safe_redirect( $back );
			exit;
		}

		$user_id = get_current_user_id();

		/*
		 * Already settled one way or the other. Asking again from a stale
		 * tab should not reopen a decision somebody has already made.
		 */
		if ( 'none' !== kaamase_hiring_status( $user_id ) ) {
			wp_safe_redirect( $back );
			exit;
		}

		/*
		 * Granted here and now rather than queued. See the note at the
		 * top of this file on why the filter belongs on the job rather
		 * than on the person.
		 */
		kaamase_grant_hiring( $user_id );

		update_user_meta( $user_id, KAAMASE_HIRING_TIME_KEY, time() );
		update_user_meta( $user_id, KAAMASE_HIRING_SELF_KEY, 1 );

		/**
		 * Fires when somebody adds hiring to their own account.
		 *
		 * @since 1.1.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_hiring_requested', $user_id );

		wp_safe_redirect( $back );
		exit;
	}
}
add_action( 'admin_post_kaamase_request_hiring', 'kaamase_handle_hiring_request' );
add_action( 'admin_post_nopriv_kaamase_request_hiring', 'kaamase_handle_hiring_request' );


/* ==========================================================================
   3. GRANTING

   add_role, never set_role. The distinction is the whole feature: one
   adds hiring to a worker, the other quietly turns a worker into an
   employer and takes their profile capabilities away with it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_grant_hiring' ) ) {
	/**
	 * Add hiring to an account.
	 *
	 * @since 1.1.0
	 * @param int $user_id User to approve.
	 * @return bool True when granted.
	 */
	function kaamase_grant_hiring( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return false;
		}

		if ( ! in_array( 'kaamase_employer', (array) $user->roles, true ) ) {
			$user->add_role( 'kaamase_employer' );
		}

		update_user_meta( $user_id, KAAMASE_HIRING_STATUS_KEY, 'approved' );

		kaamase_ensure_employer_profile( $user_id );
		kaamase_notify_hiring_approved( $user_id );

		/**
		 * Fires after hiring is added to an account.
		 *
		 * @since 1.1.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_hiring_granted', (int) $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_revoke_hiring' ) ) {
	/**
	 * Take hiring away again.
	 *
	 * Removes the employer role only. Whatever else the account is stays
	 * exactly as it was, which is the reason this is safe to use.
	 *
	 * @since 1.1.0
	 * @param int    $user_id User to withdraw from.
	 * @param string $status  Status to record. Defaults to declined.
	 * @return bool True when done.
	 */
	function kaamase_revoke_hiring( $user_id, $status = 'declined' ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return false;
		}

		/*
		 * Refused if it would leave them with nothing. An account with no
		 * roles cannot sign in to anything, and somebody registered as an
		 * employer in the first place has no worker role to fall back on.
		 */
		if ( count( (array) $user->roles ) < 2 ) {
			return false;
		}

		$user->remove_role( 'kaamase_employer' );

		update_user_meta( $user_id, KAAMASE_HIRING_STATUS_KEY, $status );

		/**
		 * Fires after hiring is withdrawn.
		 *
		 * @since 1.1.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_hiring_revoked', (int) $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_ensure_employer_profile' ) ) {
	/**
	 * Make sure there is an employer profile to attach jobs to.
	 *
	 * Draft, matching what registration does, so nothing appears on the
	 * public site until it has been filled in.
	 *
	 * @since 1.1.0
	 * @param int $user_id User ID.
	 * @return int Profile post ID, or 0 on failure.
	 */
	function kaamase_ensure_employer_profile( $user_id ) {

		$existing = get_posts(
			array(
				'post_type'      => 'kaamase_employer',
				'author'         => (int) $user_id,
				'post_status'    => array( 'draft', 'pending', 'publish' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			return (int) $existing[0];
		}

		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return 0;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'kaamase_employer',
				'post_title'  => $user->display_name,
				'post_status' => 'draft',
				'post_author' => (int) $user_id,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		/*
		 * The district and phone are copied from the worker profile they
		 * already have, so nobody is handed an empty form asking for
		 * details they gave when they registered.
		 *
		 * This block used to call kaamase_get_field, which does not
		 * exist: the reader is kaamase_field. Guarded by function_exists,
		 * it therefore did nothing at all, silently, and every account
		 * that added hiring got an employer profile with no phone number
		 * on it. That was invisible until the contact rules started
		 * asking for one, at which point those accounts could not reach
		 * anybody in a home based trade and the reason was a typo two
		 * files away.
		 */
		$worker_id = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( $worker_id && function_exists( 'kaamase_field' ) ) {

			$phone    = (string) kaamase_field( $worker_id, 'phone', '' );
			$district = (string) kaamase_field( $worker_id, 'district', '' );

			if ( '' !== $phone ) {
				kaamase_save_field( $post_id, 'phone', $phone );
			}

			if ( '' !== $district ) {
				kaamase_save_field( $post_id, 'district', $district );
				wp_set_object_terms( $post_id, $district, 'kaamase_district' );
			}
		}

		return (int) $post_id;
	}
}

if ( ! function_exists( 'kaamase_notify_hiring_approved' ) ) {
	/**
	 * Tell them it is done.
	 *
	 * @since 1.1.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_notify_hiring_approved( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user || ! $user->user_email ) {
			return;
		}

		/*
		 * Approved from the admin, by somebody else, in English. The
		 * person this is about is not in the request.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $user->ID );

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'You can now post jobs on %s', 'kaamase-core' ),
			get_bloginfo( 'name' )
		);

		$body = sprintf(
			/* translators: 1: display name, 2: post a job URL */
			__(
				"Hello %1\$s,\n\nHiring has been added to your account. You can post jobs now, using the same login you already have. Your worker profile and your ratings are unchanged.\n\nPost a job here: %2\$s\n",
				'kaamase-core'
			),
			$user->display_name,
			kaamase_page_url( 'post-job' )
		);

		wp_mail( $user->user_email, $subject, $body );

		if ( $switched ) {
			kaamase_locale_restore();
		}
	}
}


/* ==========================================================================
   4. THE ADMIN SCREEN

   Not an approval queue, because there is nothing to approve. This is
   the list of accounts holding both roles, so somebody who turned out to
   be using hiring badly can have it taken away.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hiring_admin_menu' ) ) {
	/**
	 * Add the screen under Kaam Ase.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	function kaamase_hiring_admin_menu() {

		add_submenu_page(
			'kaamase',
			__( 'Workers who hire', 'kaamase-core' ),
			__( 'Workers who hire', 'kaamase-core' ),
			'promote_users',
			'kaamase-hiring',
			'kaamase_hiring_admin_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_hiring_admin_menu', 20 );

if ( ! function_exists( 'kaamase_hiring_admin_page' ) ) {
	/**
	 * Render the screen.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	function kaamase_hiring_admin_page() {

		if ( ! current_user_can( 'promote_users' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		kaamase_handle_hiring_admin_action();

		$users = kaamase_dual_role_users();
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Workers who hire', 'kaamase-core' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Accounts that are a worker and an employer at the same time. Anybody can add hiring to their own account, so nothing here is waiting on you. Removing hiring takes away job posting and leaves the worker profile, the ratings and the login untouched.', 'kaamase-core' ); ?>
			</p>

			<?php if ( empty( $users ) ) : ?>

				<p><?php esc_html_e( 'Nobody yet.', 'kaamase-core' ); ?></p>

			<?php else : ?>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Person', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Jobs posted', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Added', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Action', 'kaamase-core' ); ?></th>
						</tr>
					</thead>
					<tbody>

					<?php foreach ( $users as $user ) : ?>
						<?php
						$added   = (int) get_user_meta( $user->ID, KAAMASE_HIRING_TIME_KEY, true );
						$profile = (int) get_user_meta( $user->ID, 'kaamase_profile_id', true );

						$jobs = count_user_posts( $user->ID, 'kaamase_job', true );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $user->display_name ); ?></strong><br>
								<span class="description"><?php echo esc_html( $user->user_email ); ?></span>

								<?php if ( $profile ) : ?>
									<br>
									<a href="<?php echo esc_url( (string) get_edit_post_link( $profile ) ); ?>">
										<?php esc_html_e( 'See their worker profile', 'kaamase-core' ); ?>
									</a>
								<?php endif; ?>
							</td>

							<td><?php echo esc_html( number_format_i18n( (int) $jobs ) ); ?></td>

							<td>
								<?php
								echo esc_html(
									$added
										? sprintf(
											/* translators: %s: human readable time difference */
											__( '%s ago', 'kaamase-core' ),
											human_time_diff( $added, time() )
										)
										: '&mdash;'
								);
								?>
							</td>

							<td>
								<form method="post" style="display:inline">
									<?php wp_nonce_field( 'kaamase_hiring_action', 'kaamase_hiring_action_nonce' ); ?>
									<input type="hidden" name="user" value="<?php echo esc_attr( (string) $user->ID ); ?>">

									<button type="submit" name="decision" value="revoke" class="button">
										<?php esc_html_e( 'Remove hiring', 'kaamase-core' ); ?>
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

if ( ! function_exists( 'kaamase_handle_hiring_admin_action' ) ) {
	/**
	 * Act on a remove press.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	function kaamase_handle_hiring_admin_action() {

		if ( ! isset( $_POST['kaamase_hiring_action_nonce'], $_POST['user'], $_POST['decision'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['kaamase_hiring_action_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'kaamase_hiring_action' ) ) {
			return;
		}

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		$user_id = absint( $_POST['user'] );

		if ( ! $user_id || 'revoke' !== sanitize_key( wp_unslash( $_POST['decision'] ) ) ) {
			return;
		}

		if ( kaamase_revoke_hiring( $user_id ) ) {
			echo '<div class="notice notice-success"><p>'
				. esc_html__( 'Hiring removed. Their worker account is untouched.', 'kaamase-core' )
				. '</p></div>';

			return;
		}

		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Not removed. That account has no other role, so it would be left unable to sign in.', 'kaamase-core' )
			. '</p></div>';
	}
}


/* ==========================================================================
   5. ON THE USER PROFILE

   So an administrator opening somebody's user record can see and change
   this without hunting for the requests screen.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hiring_user_field' ) ) {
	/**
	 * Show hiring state on the user edit screen.
	 *
	 * @since 1.1.0
	 * @param WP_User $user The user being edited.
	 * @return void
	 */
	function kaamase_hiring_user_field( $user ) {

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		$status = kaamase_hiring_status( $user->ID );
		?>
		<h2><?php esc_html_e( 'Kaam Ase hiring', 'kaamase-core' ); ?></h2>

		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Can post jobs', 'kaamase-core' ); ?></th>
				<td>
					<p>
						<?php
						echo esc_html(
							'approved' === $status
								? __( 'Yes', 'kaamase-core' )
								: __( 'No', 'kaamase-core' )
						);

						?>
					</p>

					<p>
						<label>
							<input type="checkbox" name="kaamase_hiring_toggle" value="1">
							<?php
							echo esc_html(
								'approved' === $status
									? __( 'Remove job posting from this account', 'kaamase-core' )
									: __( 'Add job posting to this account', 'kaamase-core' )
							);
							?>
						</label>
					</p>

					<p class="description">
						<?php esc_html_e( 'This adds or removes the employer role only. Everything else about the account is left alone.', 'kaamase-core' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php

		wp_nonce_field( 'kaamase_hiring_user', 'kaamase_hiring_user_nonce' );
	}
}
add_action( 'edit_user_profile', 'kaamase_hiring_user_field' );
add_action( 'show_user_profile', 'kaamase_hiring_user_field' );

if ( ! function_exists( 'kaamase_hiring_user_save' ) ) {
	/**
	 * Save the toggle from the user edit screen.
	 *
	 * @since 1.1.0
	 * @param int $user_id User being saved.
	 * @return void
	 */
	function kaamase_hiring_user_save( $user_id ) {

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		if ( ! isset( $_POST['kaamase_hiring_user_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['kaamase_hiring_user_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'kaamase_hiring_user' ) ) {
			return;
		}

		if ( empty( $_POST['kaamase_hiring_toggle'] ) ) {
			return;
		}

		if ( 'approved' === kaamase_hiring_status( $user_id ) ) {
			kaamase_revoke_hiring( $user_id, 'declined' );

			return;
		}

		kaamase_grant_hiring( $user_id );
	}
}
add_action( 'edit_user_profile_update', 'kaamase_hiring_user_save' );
add_action( 'personal_options_update', 'kaamase_hiring_user_save' );


/* ==========================================================================
   5b. REPAIRING THE ACCOUNTS THIS ALREADY BROKE

   Every employer profile created before the typo above was fixed has no
   phone number on it. Those people cannot be told to go and fix it,
   because they never knew it happened, and the symptom they see is a
   contact refusal that says nothing about phone numbers being missing
   from a profile they did not know they had.

   So it repairs itself: the next time such an account asks for a
   number, the phone is copied across from the worker profile and the
   request proceeds. Runs once per account and then never again.
   ========================================================================== */

if ( ! function_exists( 'kaamase_repair_employer_phone' ) ) {
	/**
	 * Put the phone back on an employer profile that never got one.
	 *
	 * @since 1.1.1
	 * @param int $user_id User to repair.
	 * @return void
	 */
	function kaamase_repair_employer_phone( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || ! function_exists( 'kaamase_field' ) ) {
			return;
		}

		$employer = kaamase_get_user_profile( $user_id, 'kaamase_employer' );

		if ( ! $employer ) {
			return;
		}

		if ( '' !== trim( (string) kaamase_field( $employer, 'phone', '' ) ) ) {
			return;
		}

		$worker = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( ! $worker || $worker === (int) $employer ) {
			return;
		}

		$phone = (string) kaamase_field( $worker, 'phone', '' );

		if ( '' === trim( $phone ) ) {
			return;
		}

		kaamase_save_field( $employer, 'phone', $phone );
	}
}

/**
 * Repair before the contact rules are applied, not after.
 *
 * Hooked on the same check that would otherwise refuse them, so the
 * person never sees a refusal caused by the missing field.
 *
 * @since 1.1.1
 * @return void
 */
function kaamase_repair_before_contact() {

	if ( is_user_logged_in() ) {
		kaamase_repair_employer_phone( get_current_user_id() );
	}
}
add_action( 'kaamase_before_contact_check', 'kaamase_repair_before_contact' );


/* ==========================================================================
   6. THE APP

   The same door, for people who never open the website. Registered here
   rather than in rest-api.php so the whole feature stays in one file and
   removing that file removes all of it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_hiring_route' ) ) {
	/**
	 * POST /me/hiring
	 *
	 * @since 1.1.0
	 * @return void
	 */
	function kaamase_register_hiring_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/hiring',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_add_hiring',
				'permission_callback' => 'kaamase_rest_require_login',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_register_hiring_route', 20 );

if ( ! function_exists( 'kaamase_rest_add_hiring' ) ) {
	/**
	 * Add hiring to the calling account.
	 *
	 * Returns the refreshed me object, so the app can swap its Post a job
	 * button in without a second request.
	 *
	 * @since 1.1.0
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_rest_add_hiring() {

		$user_id = get_current_user_id();

		if ( 'declined' === kaamase_hiring_status( $user_id ) ) {
			return new WP_Error(
				'kaamase_hiring_declined',
				__( 'Hiring was removed from this account. Get in touch if that was a mistake.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		if ( user_can( $user_id, 'create_kaamase_jobs' ) ) {
			return rest_ensure_response( kaamase_shape_me( $user_id ) );
		}

		if ( ! kaamase_grant_hiring( $user_id ) ) {
			return new WP_Error(
				'kaamase_hiring_failed',
				__( 'That did not work. Please try again.', 'kaamase-core' ),
				array( 'status' => 500 )
			);
		}

		update_user_meta( $user_id, KAAMASE_HIRING_TIME_KEY, time() );
		update_user_meta( $user_id, KAAMASE_HIRING_SELF_KEY, 1 );

		return rest_ensure_response( kaamase_shape_me( $user_id ) );
	}
}