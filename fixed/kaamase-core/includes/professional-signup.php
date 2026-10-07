<?php
/**
 * Joining as a professional.
 *
 * A third choice when somebody registers, beside Worker and Employer:
 * "I'm a professional", for people looking for salaried office, bank,
 * school, hospital and company jobs.
 *
 * What it makes
 * -------------
 * An account and a professional profile, and nothing else. No worker
 * profile, because a worker profile is public and can come up on
 * Google, and the professional side is kept to signed in employers by
 * default for a reason: many professionals already have a job and are
 * looking quietly. Making a public worker profile for them at the door
 * would undo that on their first day.
 *
 * The account is a job seeker's account, the same role as a worker,
 * so it can later add a worker profile or hiring like anybody else. The
 * professional profile starts as a draft with the name, number,
 * district and kind of work given at the door, and is shown to
 * employers once it is finished and the email address is confirmed --
 * the same two conditions as every other profile, in the same order.
 *
 * Every way in
 * ------------
 * The registration form, the app's /auth/register, Google on the
 * website and in the app, and Apple in the app. Each of those files
 * asks this one for the list of types it accepts and hands a
 * professional account here to be made, so the rules live in one place.
 *
 * What it leaves alone
 * --------------------
 * Worker and employer registration, word for word. An account that
 * joined as a worker or an employer is never treated as anything else.
 * With this file removed, the third door disappears and everything is
 * as it was.
 *
 * @package KaamaseCore
 * @version 1.0.1
 * @since   1.0.0
 *
 * Changelog
 *   1.0.1  A professional-only account is told job_alerts.available is
 *          false, so the installed apps do not offer a switch that could
 *          never send anything.
 */

defined( 'ABSPATH' ) || exit;


/** Whether the third door is offered. Switched on the Professional settings screen. */
define( 'KAAMASE_JOIN_ON_OPTION', 'kaamase_prof_join_on' );

/** How an account joined, when it was as a professional. */
define( 'KAAMASE_JOIN_KEY', 'kaamase_joined_as' );


/* ==========================================================================
   1. THE RULES
   ========================================================================== */

if ( ! function_exists( 'kaamase_join_ready' ) ) {
	/**
	 * Whether professional profiles are here to be made.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_join_ready() {

		return defined( 'KAAMASE_PROF_TYPE' )
			&& defined( 'KAAMASE_PROF_CATEGORY_KEY' )
			&& defined( 'KAAMASE_PROF_LISTED_KEY' )
			&& function_exists( 'kaamase_prof_category_names' )
			&& function_exists( 'kaamase_prof_creating' )
			&& function_exists( 'kaamase_prof_id' );
	}
}

if ( ! function_exists( 'kaamase_join_on' ) ) {
	/**
	 * Whether people may join as a professional right now.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_join_on() {

		if ( ! (bool) get_option( KAAMASE_JOIN_ON_OPTION, 1 ) || ! kaamase_join_ready() ) {
			return false;
		}

		return ! empty( kaamase_prof_category_names() );
	}
}

if ( ! function_exists( 'kaamase_join_types' ) ) {
	/**
	 * The kinds of account somebody may register as.
	 *
	 * @since 1.0.0
	 * @return string[]
	 */
	function kaamase_join_types() {

		$types = array( 'worker', 'employer' );

		if ( kaamase_join_on() ) {
			$types[] = 'professional';
		}

		return $types;
	}
}

if ( ! function_exists( 'kaamase_join_category' ) ) {
	/**
	 * A professional category from what was sent, or nothing.
	 *
	 * @since 1.0.0
	 * @param mixed $raw What was sent.
	 * @return string Category slug, or an empty string.
	 */
	function kaamase_join_category( $raw ) {

		if ( ! is_scalar( $raw ) || ! kaamase_join_ready() ) {
			return '';
		}

		$slug = sanitize_key( (string) $raw );

		return isset( kaamase_prof_category_names()[ $slug ] ) ? $slug : '';
	}
}

if ( ! function_exists( 'kaamase_join_errors' ) ) {
	/**
	 * What is wrong with a professional registration, beyond the usual.
	 *
	 * @since 1.0.0
	 * @param string $type     The kind of account.
	 * @param string $category The category given.
	 * @return string[]
	 */
	function kaamase_join_errors( $type, $category ) {

		if ( 'professional' !== $type || '' !== (string) $category ) {
			return array();
		}

		return array( __( 'Please choose the kind of work you are looking for.', 'kaamase-core' ) );
	}
}


/* ==========================================================================
   2. ON THE REGISTRATION PAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_join_door' ) ) {
	/**
	 * The third door, under the two big ones.
	 *
	 * Smaller than they are on purpose. Most people who reach this page
	 * are workers and employers, and the two pictures match the app; a
	 * third picture would squeeze both on a phone. Somebody looking for
	 * an office job is looking for exactly these words.
	 *
	 * @since 1.0.0
	 * @return string Markup, or nothing when the door is closed.
	 */
	function kaamase_join_door() {

		if ( ! kaamase_join_on() || ! function_exists( 'kaamase_page_url' ) ) {
			return '';
		}

		return sprintf(
			'<a class="ka-card ka-card--link ka-join__pro" href="%1$s" style="display:flex;flex-direction:column;gap:var(--ka-2);text-align:center;">'
			. '<span class="ka-text-lg">%2$s</span>'
			. '<span class="ka-small ka-soft">%3$s</span>'
			. '<span class="ka-btn ka-btn--outline ka-btn--block ka-mt-4">%4$s</span>'
			. '</a>',
			esc_url( add_query_arg( 'type', 'professional', kaamase_page_url( 'register' ) ) ),
			esc_html__( 'Looking for an office or professional job?', 'kaamase-core' ),
			esc_html__( 'Banks, schools, hospitals, offices and companies. Make a professional profile with your qualification and experience.', 'kaamase-core' ),
			esc_html__( 'I\'m a professional', 'kaamase-core' )
		);
	}
}

if ( ! function_exists( 'kaamase_join_intro' ) ) {
	/**
	 * The line under the heading of the professional form.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_join_intro() {

		return '<p class="ka-small ka-soft">'
			. esc_html__( 'For office, bank, school, hospital and company jobs. Your profile is shown to employers signed in to Kaam Ase, not to everybody, unless you choose otherwise.', 'kaamase-core' )
			. '</p>';
	}
}

if ( ! function_exists( 'kaamase_join_category_field' ) ) {
	/**
	 * The question asked of a professional instead of a trade.
	 *
	 * One category at the door, like one trade for a worker. Up to three
	 * can be chosen on the profile afterwards.
	 *
	 * @since 1.0.0
	 * @param string $selected The category already chosen.
	 * @param string $id       The field's id.
	 * @param bool   $required Whether the form must have it.
	 * @param string $hint     The line under it.
	 * @return string Markup.
	 */
	function kaamase_join_category_field( $selected = '', $id = 'ka-category', $required = true, $hint = '' ) {

		if ( ! kaamase_join_ready() ) {
			return '';
		}

		$hint = '' !== $hint ? $hint : __( 'You can choose up to three on your profile afterwards.', 'kaamase-core' );

		ob_start();
		?>
		<div class="ka-field">
			<label class="ka-label" for="<?php echo esc_attr( $id ); ?>">
				<?php esc_html_e( 'What kind of work are you looking for', 'kaamase-core' ); ?>
				<?php if ( $required ) : ?>
					<span class="ka-label__req">*</span>
				<?php endif; ?>
			</label>
			<select class="ka-select" id="<?php echo esc_attr( $id ); ?>" name="kaamase_category" <?php echo $required ? 'required' : ''; ?>>
				<option value=""><?php esc_html_e( 'Choose the kind of work', 'kaamase-core' ); ?></option>
				<?php foreach ( kaamase_prof_category_names() as $slug => $name ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( (string) $selected, $slug ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="ka-hint"><?php echo esc_html( $hint ); ?></p>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_join_landing' ) ) {
	/**
	 * Where somebody goes straight after joining: the rest of their profile.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_join_landing() {

		$form = function_exists( 'kaamase_prof_url' ) ? kaamase_prof_url( 'my_professional' ) : home_url( '/my-professional-profile/' );

		return add_query_arg( 'welcome', '1', $form );
	}
}

if ( ! function_exists( 'kaamase_join_welcome' ) ) {
	/**
	 * The greeting at the top of the profile form on that first visit.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return string Markup.
	 */
	function kaamase_join_welcome( $user_id ) {

		$user     = get_userdata( (int) $user_id );
		$verified = ! function_exists( 'kaamase_user_is_verified' ) || kaamase_user_is_verified( (int) $user_id );

		$text = $verified
			? __( 'Now finish your profile below. Employers can see it as soon as it is finished.', 'kaamase-core' )
			: sprintf(
				/* translators: %s: email address */
				__( 'We sent a link to %s. Tap it to confirm your email, and finish your profile below. Employers can see it once both are done.', 'kaamase-core' ),
				$user ? $user->user_email : ''
			);

		return sprintf(
			'<div class="ka-notice ka-notice--ok ka-mb-4"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
			esc_html__( 'Your account is made', 'kaamase-core' ),
			esc_html( $text )
		);
	}
}


/* ==========================================================================
   3. MAKING THE ACCOUNT
   ========================================================================== */

if ( ! function_exists( 'kaamase_join_make_profile' ) ) {
	/**
	 * The professional profile an account starts with.
	 *
	 * A draft, and not yet wanting to be shown: it has a name, a number,
	 * a district and one kind of work, and the rest is asked on the next
	 * screen. Shown to employers only once it is finished. See
	 * kaamase_prof_creating() for why the copying is not watched.
	 *
	 * @since 1.0.0
	 * @param int    $user_id  Account.
	 * @param array  $data     What was given at the door.
	 * @param string $category The kind of work.
	 * @return int Profile ID, or 0 on failure.
	 */
	function kaamase_join_make_profile( $user_id, $data, $category ) {

		kaamase_prof_creating( true );

		try {

			// Slashed, because wp_insert_post() takes slashes off whatever it is given.
			$id = wp_insert_post(
				wp_slash(
					array(
						'post_type'      => KAAMASE_PROF_TYPE,
						'post_status'    => 'draft',
						'post_author'    => (int) $user_id,
						'post_title'     => (string) $data['name'],
						'post_content'   => '',
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
					)
				),
				true
			);

			if ( is_wp_error( $id ) || ! $id ) {
				return 0;
			}

			if ( '' !== (string) $data['phone'] ) {
				kaamase_save_field( $id, 'phone', (string) $data['phone'] );
			}

			if ( '' !== (string) $data['district'] ) {
				kaamase_save_field( $id, 'district', (string) $data['district'] );
			}

			add_post_meta( $id, KAAMASE_PROF_CATEGORY_KEY, $category );
			update_post_meta( $id, KAAMASE_PROF_LISTED_KEY, 0 );

		} finally {
			kaamase_prof_creating( false );
		}

		if ( function_exists( 'kaamase_prof_forget' ) ) {
			kaamase_prof_forget( (int) $user_id );
		}

		return (int) $id;
	}
}

if ( ! function_exists( 'kaamase_join_create' ) ) {
	/**
	 * Make a professional account and its profile together.
	 *
	 * Called by kaamase_create_account() for a professional, so every way
	 * in -- the form, the app, Google, Apple -- makes the same thing and
	 * the same hooks fire. If the profile cannot be made the account is
	 * removed again, as for every other kind of account.
	 *
	 * @since 1.0.0
	 * @param array $data Validated registration data, with category.
	 * @return int|WP_Error User ID, or an error.
	 */
	function kaamase_join_create( $data ) {

		if ( ! kaamase_join_ready() ) {
			return new WP_Error( 'kaamase_professional_failed', __( 'That did not work. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
		}

		$category = kaamase_join_category( isset( $data['category'] ) ? $data['category'] : '' );

		if ( '' === $category ) {
			return new WP_Error( 'kaamase_invalid_registration', __( 'Please choose the kind of work you are looking for.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => kaamase_unique_login( $data['email'] ),
				'user_email'   => $data['email'],
				'user_pass'    => $data['password'],
				'display_name' => $data['name'],
				'first_name'   => $data['name'],
				'role'         => 'kaamase_worker',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, KAAMASE_JOIN_KEY, 'professional' );

		$post_id = kaamase_join_make_profile( $user_id, $data, $category );

		if ( ! $post_id ) {

			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );

			return new WP_Error( 'kaamase_professional_failed', __( 'That did not work. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
		}

		update_user_meta( $user_id, 'kaamase_registered_at', time() );

		kaamase_send_verification( $user_id );

		/** This action is documented in includes/registration.php */
		do_action( 'kaamase_account_created', $user_id, $post_id, 'professional' );

		return (int) $user_id;
	}
}


/* ==========================================================================
   4. THEIR DASHBOARD
   ========================================================================== */

if ( ! function_exists( 'kaamase_join_is_professional_only' ) ) {
	/**
	 * Whether an account's only profile is a professional one.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return bool
	 */
	function kaamase_join_is_professional_only( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || ! kaamase_join_ready() || ! function_exists( 'kaamase_get_user_profile' ) ) {
			return false;
		}

		return kaamase_prof_id( $user_id )
			&& ! kaamase_get_user_profile( $user_id, 'kaamase_worker' )
			&& ! kaamase_get_user_profile( $user_id, 'kaamase_employer' );
	}
}

if ( ! function_exists( 'kaamase_join_dashboard' ) ) {
	/**
	 * The dashboard of an account with no worker or employer profile, when
	 * that account is a professional one.
	 *
	 * Called by the dashboard only where it would otherwise say the
	 * profile is missing and offer to rebuild it. For a professional that
	 * is wrong twice over: nothing is missing, and the rebuild would make
	 * the public worker profile they joined this way to avoid.
	 *
	 * The cards are the dashboard's own, through the same two hooks, so
	 * everything that applies to every account -- the password, the
	 * phones signed in, the language, Google, the tick -- appears here too.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return string Markup, or nothing when this is not such an account.
	 */
	function kaamase_join_dashboard( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || ! kaamase_join_ready() || ! function_exists( 'kaamase_get_user_profile' ) ) {
			return '';
		}

		if ( kaamase_get_user_profile( $user_id, 'kaamase_worker' ) || kaamase_get_user_profile( $user_id, 'kaamase_employer' ) ) {
			return '';
		}

		$prof_id = kaamase_prof_id( $user_id );

		if ( ! $prof_id && 'professional' !== (string) get_user_meta( $user_id, KAAMASE_JOIN_KEY, true ) ) {
			return '';
		}

		$user = get_userdata( $user_id );

		ob_start();

		echo kaamase_join_flash( $user_id, $prof_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		echo kaamase_join_banner( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		?>
		<section class="ka-card ka-card--pad-lg">
			<div class="ka-card__head">
				<?php
				if ( $prof_id && function_exists( 'kaamase_avatar' ) ) {
					echo kaamase_avatar( $prof_id, 'kaamase-avatar', 'ka-avatar--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<div>
					<h1 class="ka-text-xl">
						<?php
						/* translators: %s: person's name */
						printf( esc_html__( 'Hello %s', 'kaamase-core' ), esc_html( $user ? $user->display_name : '' ) );
						?>
					</h1>
					<p class="ka-small ka-soft"><?php esc_html_e( 'Your account is for office and professional jobs.', 'kaamase-core' ); ?></p>
				</div>
			</div>
		</section>
		<?php

		/** This action is documented in includes/dashboard.php */
		do_action( 'kaamase_dashboard_prompts', $user_id, $prof_id, 'professional' );

		/*
		 * The offer to add a worker profile speaks to employers, about
		 * their job posts. It is left off this one screen rather than
		 * reworded for everybody.
		 */
		$offer = has_action( 'kaamase_dashboard_sections', 'kaamase_working_dashboard_section' );

		if ( false !== $offer ) {
			remove_action( 'kaamase_dashboard_sections', 'kaamase_working_dashboard_section', $offer );
		}

		/** This action is documented in includes/dashboard.php */
		do_action( 'kaamase_dashboard_sections', $user_id, $prof_id, 'professional' );

		if ( false !== $offer ) {
			add_action( 'kaamase_dashboard_sections', 'kaamase_working_dashboard_section', $offer );
		}

		if ( function_exists( 'kaamase_dashboard_footer_links' ) ) {
			echo kaamase_dashboard_footer_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_join_flash' ) ) {
	/**
	 * Confirmation messages, in a professional's words.
	 *
	 * The dashboard's own say "your profile is live" when the email is
	 * confirmed, which for a professional profile is true only once it
	 * is finished as well.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @param int $prof_id Their professional profile, or 0.
	 * @return string Markup.
	 */
	function kaamase_join_flash( $user_id, $prof_id ) {

		unset( $user_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only choosing a message.
		$verify = isset( $_GET['verify'] ) ? sanitize_key( wp_unslash( $_GET['verify'] ) ) : '';

		$listed = $prof_id && function_exists( 'kaamase_prof_state' ) && 'listed' === kaamase_prof_state( $prof_id );

		$messages = array(
			'done'   => array(
				'ok',
				$listed
					? __( 'Email confirmed. Employers can see your professional profile.', 'kaamase-core' )
					: __( 'Email confirmed. Finish your professional profile and employers can see it.', 'kaamase-core' ),
			),
			'failed' => array( 'error', __( 'That link did not work. It may have expired. Send a new one below.', 'kaamase-core' ) ),
			'sent'   => array( 'ok', __( 'Sent. Check your email.', 'kaamase-core' ) ),
			'wait'   => array( 'warn', __( 'We just sent one. Please wait a few minutes before asking again.', 'kaamase-core' ) ),
		);

		if ( ! isset( $messages[ $verify ] ) ) {
			return '';
		}

		return sprintf(
			'<div class="ka-notice ka-notice--%1$s"><p>%2$s</p></div>',
			esc_attr( $messages[ $verify ][0] ),
			esc_html( $messages[ $verify ][1] )
		);
	}
}

if ( ! function_exists( 'kaamase_join_banner' ) ) {
	/**
	 * The reminder to confirm the email address, with the resend button.
	 *
	 * The resend goes through the dashboard's own handler.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return string Markup.
	 */
	function kaamase_join_banner( $user_id ) {

		if ( ! function_exists( 'kaamase_user_is_verified' ) || kaamase_user_is_verified( (int) $user_id ) ) {
			return '';
		}

		$user = get_userdata( (int) $user_id );

		ob_start();
		?>
		<div class="ka-banner">
			<div>
				<strong><?php esc_html_e( 'Confirm your email', 'kaamase-core' ); ?></strong>
				<p class="ka-small ka-mt-4">
					<?php
					printf(
						/* translators: %s: email address */
						esc_html__( 'We sent a link to %s. Employers can see your professional profile once you tap it and the profile is finished.', 'kaamase-core' ),
						'<strong>' . esc_html( $user ? $user->user_email : '' ) . '</strong>'
					);
					?>
				</p>
			</div>

			<form method="post" action="">
				<?php wp_nonce_field( 'kaamase_resend', 'kaamase_resend_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="resend_verification">
				<button class="ka-btn ka-btn--outline ka-btn--sm" type="submit">
					<?php esc_html_e( 'Send it again', 'kaamase-core' ); ?>
				</button>
			</form>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   5. ADDING A SIDE LATER
   ========================================================================== */

if ( ! function_exists( 'kaamase_join_side_added' ) ) {
	/**
	 * When a professional account adds a worker profile or hiring.
	 *
	 * Two things the rest of the platform expects of every account with
	 * a worker or employer profile, which a professional account did not
	 * have to give at the door:
	 *
	 *   - The profile they registered with. Every screen reads it, and
	 *     the dashboard offers to rebuild a missing one. The new profile
	 *     becomes it, exactly as if they had joined that way.
	 *   - A phone number and district on the new profile. The ordinary
	 *     code copies them from the profile they registered with, which a
	 *     professional account does not have, so they are copied from the
	 *     professional profile instead. Only where empty.
	 *
	 * Accounts that joined as a worker or an employer always have the
	 * first and are never touched here.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @param int $profile The profile just added, when known.
	 * @return void
	 */
	function kaamase_join_side_added( $user_id, $profile = 0 ) {

		$user_id = (int) $user_id;

		if ( ! $user_id || get_user_meta( $user_id, 'kaamase_profile_id', true ) ) {
			return;
		}

		$profile = (int) $profile;

		/*
		 * Hiring does not say which profile it made. Asked of the
		 * database rather than kaamase_get_user_profile(), whose answer
		 * may have been remembered as "none" earlier in this same request.
		 */
		if ( ! $profile ) {

			$found = get_posts(
				array(
					'post_type'      => 'kaamase_employer',
					'author'         => $user_id,
					'post_status'    => array( 'draft', 'pending', 'publish' ),
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'orderby'        => 'ID',
					'order'          => 'ASC',
				)
			);

			$profile = ! empty( $found ) ? (int) $found[0] : 0;
		}

		$type = $profile ? get_post_type( $profile ) : '';

		if ( ! in_array( $type, array( 'kaamase_worker', 'kaamase_employer' ), true ) || (int) get_post_field( 'post_author', $profile ) !== $user_id ) {
			return;
		}

		update_user_meta( $user_id, 'kaamase_profile_id', $profile );

		$prof_id = kaamase_join_ready() ? kaamase_prof_id( $user_id ) : 0;

		if ( ! $prof_id ) {
			return;
		}

		/*
		 * Read as stored, not through kaamase_field(), which hands a phone
		 * number only to somebody allowed to see it. This runs when staff
		 * grant a side, or with nobody signed in at all, and copying a
		 * person's number between their own two profiles is not showing
		 * it to anybody.
		 */
		$phone    = (string) kaamase_read_field( $prof_id, 'phone' );
		$district = (string) kaamase_read_field( $prof_id, 'district' );

		if ( '' !== trim( $phone ) && '' === trim( (string) kaamase_read_field( $profile, 'phone' ) ) ) {
			kaamase_save_field( $profile, 'phone', $phone );
		}

		if ( '' !== trim( $district ) && '' === trim( (string) kaamase_read_field( $profile, 'district' ) ) ) {
			kaamase_save_field( $profile, 'district', $district );
			wp_set_object_terms( $profile, $district, 'kaamase_district' );
		}
	}
}
add_action( 'kaamase_working_granted', 'kaamase_join_side_added', 10, 2 );
add_action( 'kaamase_hiring_granted', 'kaamase_join_side_added', 10, 1 );


/* ==========================================================================
   6. THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_join_shape_me' ) ) {
	/**
	 * Tell the app what kind of account this is.
	 *
	 *   joined_as          "professional" when the account joined that way.
	 *   professional_only  true when its only profile is a professional one,
	 *                      so the app opens on that rather than on a worker
	 *                      or employer home with nothing in it.
	 *
	 * @since 1.0.0
	 * @param array $me      The account object.
	 * @param int   $user_id Account.
	 * @return array
	 */
	function kaamase_join_shape_me( $me, $user_id ) {

		if ( ! is_array( $me ) ) {
			return $me;
		}

		$me['joined_as']         = 'professional' === (string) get_user_meta( (int) $user_id, KAAMASE_JOIN_KEY, true ) ? 'professional' : '';
		$me['professional_only'] = kaamase_join_is_professional_only( (int) $user_id );

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_join_shape_me', 27, 2 );

if ( ! function_exists( 'kaamase_join_no_everyday_alerts' ) ) {
	/**
	 * No everyday job alerts switch for a professional-only account.
	 *
	 * The everyday alerts go by a worker or team profile's trade and
	 * district, which this account does not have, so the switch could
	 * never send anything. The apps already installed show it whenever
	 * it is available. Professionals have their own daily message,
	 * professional_alerts. Once the account adds a worker profile the
	 * switch is back as for anybody else.
	 *
	 * After job-alerts.php has written it (priority 28).
	 *
	 * @since 1.0.1
	 * @param array $me      The account object.
	 * @param int   $user_id Account.
	 * @return array
	 */
	function kaamase_join_no_everyday_alerts( $me, $user_id ) {

		if ( is_array( $me ) && isset( $me['job_alerts'] ) && is_array( $me['job_alerts'] ) && kaamase_join_is_professional_only( (int) $user_id ) ) {
			$me['job_alerts']['available'] = false;
		}

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_join_no_everyday_alerts', 31, 2 );

if ( ! function_exists( 'kaamase_join_in_reference' ) ) {
	/**
	 * Tell the app whether to offer the third door.
	 *
	 * Added onto the finished reference answer, after its cache, so
	 * switching the door off reaches the app at once.
	 *
	 * @since 1.0.0
	 * @param WP_HTTP_Response $response Response.
	 * @param WP_REST_Server   $server   Server.
	 * @param WP_REST_Request  $request  Request.
	 * @return WP_HTTP_Response
	 */
	function kaamase_join_in_reference( $response, $server, $request ) {

		unset( $server );

		if ( ! defined( 'KAAMASE_REST_NS' ) || '/' . KAAMASE_REST_NS . '/reference' !== $request->get_route() ) {
			return $response;
		}

		if ( ! $response instanceof WP_HTTP_Response ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['trades'] ) ) {
			return $response;
		}

		$data['professional_join'] = array(
			'on' => kaamase_join_on(),
		);

		$response->set_data( $data );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_join_in_reference', 13, 3 );
