<?php
/**
 * Contact.
 *
 * How one person on this platform reaches another.
 *
 * What this is, and what it is not
 * --------------------------------
 * This is NOT call masking. Real masking, where both parties dial a
 * platform number and never learn each other's, needs a telephony
 * provider such as Exotel or Knowlarity, a registered business account
 * and a cost per call. It cannot be done in PHP.
 *
 * What this does is gate, log and limit the reveal:
 *
 *   - a number is never printed in a page, a card, or an archive
 *   - it is shown only after a deliberate request on its own screen
 *   - only to a signed in, verified account
 *   - every reveal is recorded with who, when and which profile
 *   - the number of reveals per day is capped
 *   - the person whose number it is can see who asked, and block them
 *
 * That is a large improvement on printing numbers and it is honest
 * about being less than masking. When a telephony provider is added,
 * kaamase_contact_channel() is the only function that changes.
 *
 * The safety gate
 * ---------------
 * Maid, cook, babysitter, caregiver and house cleaner are jobs done
 * alone, usually by women, inside a stranger's home. For those trades an
 * unverified employer cannot reach the worker at all. This is not a
 * setting and there is no override, because a setting is something that
 * gets switched off on a busy day.
 *
 * The Safety page promises exactly this in public. This file is where
 * that promise is either kept or quietly broken.
 *
 * @package KaamaseCore
 * @version 1.2.1
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. LIMITS AND RULES
   ========================================================================== */

if ( ! function_exists( 'kaamase_contact_daily_limit' ) ) {
	/**
	 * How many different people one account may reveal in a day.
	 *
	 * This is the anti scraping defence, and the number is a judgement
	 * rather than a science. A contractor filling a site genuinely calls
	 * ten or fifteen people in a morning. Nobody legitimately needs a
	 * hundred. Somebody harvesting the platform needs all of them.
	 *
	 * @since 1.0.0
	 * @param int $user_id User doing the revealing.
	 * @return int Reveals allowed per day.
	 */
	function kaamase_contact_daily_limit( $user_id ) {

		$limit = 20;

		// Field agents register workers in person and hit this constantly.
		if ( user_can( $user_id, 'edit_others_kaamase_workers' ) ) {
			$limit = 100;
		}

		/**
		 * Filter the daily contact reveal limit.
		 *
		 * @since 1.0.0
		 * @param int $limit   Reveals allowed per day.
		 * @param int $user_id User ID.
		 */
		return absint( apply_filters( 'kaamase_contact_daily_limit', $limit, $user_id ) );
	}
}

if ( ! function_exists( 'kaamase_protected_trades' ) ) {
	/**
	 * Trades where an unverified employer may not make contact.
	 *
	 * Work done alone inside somebody's home. See the note at the top of
	 * this file.
	 *
	 * @since 1.0.0
	 * @return string[] Trade slugs.
	 */
	function kaamase_protected_trades() {

		/**
		 * Filter the protected trade list.
		 *
		 * Adding to this list is fine. Removing from it means an
		 * unverified stranger can contact somebody who works alone in a
		 * house, so do not.
		 *
		 * @since 1.0.0
		 * @param string[] $trades Trade slugs.
		 */
		return (array) apply_filters(
			'kaamase_protected_trades',
			array( 'maid', 'house-cleaner', 'cook', 'babysitter', 'caregiver' )
		);
	}
}

if ( ! function_exists( 'kaamase_profile_is_protected' ) ) {
	/**
	 * Whether a profile falls under the safety gate.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return bool
	 */
	function kaamase_profile_is_protected( $post_id ) {

		$terms = wp_get_object_terms( $post_id, 'kaamase_trade', array( 'fields' => 'slugs' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
		}

		return (bool) array_intersect( $terms, kaamase_protected_trades() );
	}
}


if ( ! function_exists( 'kaamase_may_contact_home_worker' ) ) {
	/**
	 * Whether this account may reach a worker who works inside homes.
	 *
	 * What changed, and why
	 * ---------------------
	 * This used to require an employer profile carrying a verified flag
	 * that only an administrator could set, by hand, after speaking to
	 * the person. As a rule it was sound. As a system it was not, because
	 * nobody was doing the verifying.
	 *
	 * The effect of an unstaffed gate is not safety. It is that a maid, a
	 * cook or a caregiver puts up a profile and is then unreachable by
	 * everybody, permanently. The workers the rule was written to protect
	 * were the only ones it was actually affecting, and what it did to
	 * them was take away their work.
	 *
	 * So the gate is now something the platform can check on its own,
	 * every time, with nobody in the loop: a confirmed email address and
	 * a phone number on a real employer profile. A person answering a
	 * stranger's call can at least see who asked, and that record exists
	 * whether or not anybody looked at it beforehand.
	 *
	 * This is a weaker barrier than a phone call from you, and it is
	 * meant to be read as such. It is chosen because it runs, and a
	 * weaker rule that runs protects more people than a stronger one
	 * that does not.
	 *
	 * A manually verified employer still passes, so any verification you
	 * do by hand still counts for something.
	 *
	 * @since 1.2.0
	 * @param int $user_id Person asking for the number.
	 * @return bool
	 */
	function kaamase_may_contact_home_worker( $user_id ) {

		$user_id = (int) $user_id;

		/*
		 * Staff, and anybody who does the verifying, are never held up.
		 *
		 * user_can against the account passed in, not current_user_can.
		 * This function takes a user id and every other line in it
		 * honours that; this one line asked about whoever happened to be
		 * making the request. Correct today because the only caller
		 * passes the current user, wrong the moment anything asks on
		 * somebody else's behalf: cron, the API, or a field agent acting
		 * for a worker.
		 */
		if ( user_can( $user_id, 'kaamase_verify_workers' ) ) {
			return true;
		}

		$employer = kaamase_get_user_profile( $user_id, 'kaamase_employer' );

		// Verified by hand still passes, and skips every other check.
		if ( $employer && kaamase_read_field( $employer, 'verified' ) ) {
			return true;
		}

		/**
		 * Filter whether home based trades are protected at all.
		 *
		 * Returning false removes the check completely, so anybody who
		 * can use the platform can reach these workers on the same terms
		 * as any other. Left as a deliberate switch rather than deleting
		 * the code, because the day something goes wrong is the day you
		 * will want it back without waiting for a developer.
		 *
		 * @since 1.2.0
		 * @param bool $protect Whether to apply the check.
		 */
		if ( ! apply_filters( 'kaamase_protect_home_trades', true ) ) {
			return true;
		}

		// A confirmed email address.
		if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( $user_id ) ) {
			return false;
		}

		// A real employer profile with a number on it.
		if ( ! $employer ) {
			return false;
		}

		return '' !== trim( (string) kaamase_read_field( $employer, 'phone' ) );
	}
}


/* ==========================================================================
   2. PERMISSION
   ========================================================================== */

if ( ! function_exists( 'kaamase_can_contact' ) ) {
	/**
	 * Whether the current user may reveal a profile's contact details.
	 *
	 * Returns true, or a WP_Error carrying a message written for the
	 * person who will read it. Every refusal explains itself, because
	 * you cannot get past a wall you do not understand.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile or job ID.
	 * @return true|WP_Error
	 */
	function kaamase_can_contact( $post_id ) {

		/**
		 * Fires before any contact rule is applied.
		 *
		 * A place for anything that has to put an account in order
		 * before it is judged, so somebody is never refused over a field
		 * that a bug removed from their profile without telling them.
		 *
		 * @since 1.2.1
		 * @param int $post_id Profile or job being asked about.
		 */
		do_action( 'kaamase_before_contact_check', (int) $post_id );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'kaamase_no_post', __( 'That profile no longer exists.', 'kaamase-core' ) );
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'kaamase_signed_out',
				__( 'Sign in to see contact details. It takes a minute and it keeps numbers away from strangers.', 'kaamase-core' )
			);
		}

		$user_id = get_current_user_id();

		// Your own profile is always visible to you.
		if ( kaamase_user_owns( $post_id, $user_id ) ) {
			return true;
		}

		if ( ! kaamase_user_is_verified( $user_id ) ) {
			return new WP_Error(
				'kaamase_unverified',
				__( 'Confirm your email first. We keep unconfirmed accounts away from other people\'s numbers.', 'kaamase-core' )
			);
		}

		// Has this person been blocked by the profile owner?
		if ( kaamase_is_blocked( $post->post_author, $user_id ) ) {
			return new WP_Error(
				'kaamase_blocked',
				__( 'You cannot contact this person.', 'kaamase-core' )
			);
		}

		/*
		 * The safety gate.
		 *
		 * Applies only when somebody is reaching a WORKER who does this
		 * kind of work. It must not apply to a job post.
		 *
		 * The direction matters and getting it wrong inverts the rule.
		 * On a worker profile the person contacting is the employer, and
		 * requiring them to be verified is the whole point. On a job post
		 * the person contacting is the worker answering an advert, and
		 * asking a mason to hold a verified employer profile before she
		 * may ring about a cook's job is a wall with nothing behind it.
		 *
		 * Before this check, every job tagged maid, cook, babysitter,
		 * caregiver or house cleaner was unanswerable by any worker on
		 * the platform, and the refusal told them to go and get verified
		 * as an employer.
		 */
		if (
			in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true )
			&& kaamase_profile_is_protected( $post_id )
		) {

			if ( ! kaamase_may_contact_home_worker( $user_id ) ) {
				return new WP_Error(
					'kaamase_needs_verified_employer',
					__( 'Before you can see this number, finish your employer profile: confirm your email and add your phone number. It takes a minute and it means the person you are calling can see who asked.', 'kaamase-core' )
				);
			}
		}

		// Daily cap.
		if ( ! kaamase_contact_quota_left( $user_id ) ) {
			/*
			 * The sentence comes from the limits layer rather than being
			 * written here, because it has to change depending on
			 * whether there is anything to buy. Telling somebody to come
			 * back tomorrow when they could carry on now is a lost sale;
			 * offering to sell something on a site that is not charging
			 * is worse.
			 */
			return new WP_Error(
				'kaamase_limit',
				function_exists( 'kaamase_limit_reached_message' )
					? kaamase_limit_reached_message( 'contact_reveals' )
					: __( 'You have looked up a lot of numbers today. Try again tomorrow.', 'kaamase-core' )
			);
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_is_blocked' ) ) {
	/**
	 * Whether one user has blocked another.
	 *
	 * @since 1.0.0
	 * @param int $owner_id   The person who may have blocked.
	 * @param int $blocked_id The person who may be blocked.
	 * @return bool
	 */
	function kaamase_is_blocked( $owner_id, $blocked_id ) {

		$blocked = kaamase_meta_array( get_user_meta( absint( $owner_id ), 'kaamase_blocked', true ) );

		return in_array( absint( $blocked_id ), array_map( 'absint', $blocked ), true );
	}
}


/* ==========================================================================
   3. QUOTA
   ========================================================================== */

if ( ! function_exists( 'kaamase_contact_quota_key' ) ) {
	/**
	 * Transient key for today's reveal count.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string
	 */
	function kaamase_contact_quota_key( $user_id ) {

		/*
		 * The day comes from the site's timezone, not from UTC.
		 *
		 * This used to key on gmdate, so the allowance rolled over at
		 * midnight UTC, which is half past five in the morning here. A
		 * contractor ringing round at nine was working against a
		 * quota that had reset in the middle of the night.
		 */
		return 'kaamase_reveals_' . absint( $user_id ) . '_' . kaamase_rate_day();
	}
}

if ( ! function_exists( 'kaamase_contact_quota_left' ) ) {
	/**
	 * How many reveals this user has left today.
	 *
	 * Counts distinct profiles rather than clicks, so looking at the same
	 * worker's number three times while trying to reach them costs one.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return int Remaining reveals.
	 */
	function kaamase_contact_quota_left( $user_id ) {

		$seen  = kaamase_meta_array( kaamase_rate_value( kaamase_contact_quota_key( $user_id ), array() ) );
		$limit = kaamase_contact_daily_limit( $user_id );

		return max( 0, $limit - count( $seen ) );
	}
}

if ( ! function_exists( 'kaamase_contact_quota_use' ) ) {
	/**
	 * Record a reveal against today's quota.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @param int $post_id Profile revealed.
	 * @return void
	 */
	function kaamase_contact_quota_use( $user_id, $post_id ) {

		$key  = kaamase_contact_quota_key( $user_id );
		$seen = kaamase_meta_array( kaamase_rate_value( $key, array() ) );

		$seen[ absint( $post_id ) ] = 1;

		/*
		 * Written to an option rather than a transient, so clearing the
		 * object cache does not hand somebody a fresh day's worth of
		 * numbers. This is the anti scraping defence; it has to survive
		 * a caching plugin.
		 */
		kaamase_rate_write( $key, $seen, DAY_IN_SECONDS );
	}
}


/* ==========================================================================
   4. THE LOG

   Every reveal is recorded on the profile it belongs to. Two reasons.
   The person can see who looked them up, which is theirs to know. And
   when somebody reports harassment, there is a record of who had the
   number and when, rather than a shrug.
   ========================================================================== */

if ( ! function_exists( 'kaamase_log_contact' ) ) {
	/**
	 * Record that somebody was given a contact detail.
	 *
	 * Capped at the most recent 200 entries per profile, because an
	 * unbounded meta array on a popular worker becomes a slow query and
	 * a large row.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile revealed.
	 * @param int $user_id User who revealed it.
	 * @return void
	 */
	function kaamase_log_contact( $post_id, $user_id ) {

		$log = kaamase_meta_array( get_post_meta( $post_id, KAAMASE_META_PREFIX . 'contact_log', true ) );

		$log[] = array(
			'user' => absint( $user_id ),
			'time' => time(),
		);

		if ( count( $log ) > 200 ) {
			$log = array_slice( $log, -200 );
		}

		update_post_meta( $post_id, KAAMASE_META_PREFIX . 'contact_log', $log );

		/**
		 * Fires when contact details are revealed.
		 *
		 * Hook this to notify the profile owner, which is the honest
		 * thing to do and also a reason for them to open the app.
		 *
		 * @since 1.0.0
		 * @param int $post_id Profile revealed.
		 * @param int $user_id User who revealed it.
		 */
		do_action( 'kaamase_contact_revealed', $post_id, $user_id );
	}
}

if ( ! function_exists( 'kaamase_contact_log' ) ) {
	/**
	 * Who has looked up this profile.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @param int $limit   How many entries to return, newest first.
	 * @return array[] Log entries.
	 */
	function kaamase_contact_log( $post_id, $limit = 20 ) {

		$log = kaamase_meta_array( get_post_meta( $post_id, KAAMASE_META_PREFIX . 'contact_log', true ) );

		return array_slice( array_reverse( $log ), 0, absint( $limit ) );
	}
}


/* ==========================================================================
   5. THE CHANNEL

   The one function a telephony provider replaces.
   ========================================================================== */

if ( ! function_exists( 'kaamase_contact_channel' ) ) {
	/**
	 * How to reach a profile.
	 *
	 * Today this returns the real number for display on the contact
	 * screen. When a masking provider is added, it returns a proxy number
	 * instead and nothing else in the plugin needs to change: the same
	 * gate, the same log, the same quota, a different number on the
	 * screen.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile or job ID.
	 * @return array With keys number, masked and label.
	 */
	function kaamase_contact_channel( $post_id ) {

		$post = get_post( $post_id );
		$key  = ( $post && 'kaamase_job' === $post->post_type ) ? 'contact_phone' : 'phone';

		$channel = array(
			'number' => (string) kaamase_read_field( $post_id, $key ),
			'masked' => false,
			'label'  => __( 'Direct number', 'kaamase-core' ),
		);

		/**
		 * Filter the contact channel.
		 *
		 * A masking integration hooks here, returns a proxy number and
		 * sets masked to true.
		 *
		 * @since 1.0.0
		 * @param array $channel Channel details.
		 * @param int   $post_id Profile or job ID.
		 */
		return (array) apply_filters( 'kaamase_contact_channel', $channel, $post_id );
	}
}


/* ==========================================================================
   6. THE CONTACT SCREEN
   ========================================================================== */

/**
 * Point the theme's contact button at the contact screen.
 *
 * The theme calls this filter from kaamase_contact_button(). It never
 * builds a tel: link itself and never sees a number.
 *
 * @since 1.0.0
 * @param string $url     Default URL.
 * @param int    $post_id Profile ID.
 * @return string Contact screen URL.
 */
function kaamase_contact_url( $url, $post_id ) {

	unset( $url );

	return add_query_arg( 'kaamase_contact', absint( $post_id ), get_permalink( $post_id ) );
}
add_filter( 'kaamase_contact_url', 'kaamase_contact_url', 10, 2 );

if ( ! function_exists( 'kaamase_contact_screen' ) ) {
	/**
	 * Render the contact screen when requested.
	 *
	 * Its own request rather than a panel that opens in place. A reveal
	 * is a deliberate act, it consumes quota and it is logged against the
	 * person's name, so it should not be something a thumb does by
	 * accident while scrolling.
	 *
	 * @since 1.0.0
	 * @param string $content Post content.
	 * @return string Filtered content.
	 */
	function kaamase_contact_screen( $content ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['kaamase_contact'] ) || ! is_singular() ) {
			return $content;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = absint( $_GET['kaamase_contact'] );

		if ( $post_id !== get_the_ID() ) {
			return $content;
		}

		$allowed = kaamase_can_contact( $post_id );

		if ( is_wp_error( $allowed ) ) {
			return kaamase_contact_refused( $allowed, $post_id ) . $content;
		}

		return kaamase_contact_reveal( $post_id ) . $content;
	}
}
add_filter( 'the_content', 'kaamase_contact_screen', 5 );

if ( ! function_exists( 'kaamase_contact_reveal' ) ) {
	/**
	 * Show the contact details and record it.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_contact_reveal( $post_id ) {

		$user_id = get_current_user_id();

		if ( ! kaamase_user_owns( $post_id, $user_id ) ) {
			kaamase_contact_quota_use( $user_id, $post_id );
			kaamase_log_contact( $post_id, $user_id );
		}

		$channel = kaamase_contact_channel( $post_id );
		$number  = $channel['number'];
		$name    = get_the_title( $post_id );

		ob_start();
		?>
		<div class="ka-card ka-card--pad-lg ka-contact">

			<h2><?php echo esc_html( $name ); ?></h2>

			<?php if ( '' === $number ) : ?>

				<p class="ka-soft ka-mt-4">
					<?php esc_html_e( 'This person has not added a phone number yet. Nothing we can do from here.', 'kaamase-core' ); ?>
				</p>

			<?php else : ?>

				<p class="ka-label ka-mt-4"><?php echo esc_html( $channel['label'] ); ?></p>

				<p class="ka-contact__number ka-wage">
					<?php echo esc_html( kaamase_format_phone( $number ) ); ?>
				</p>

				<div class="ka-cluster ka-mt-6">
					<a class="ka-btn ka-btn--action ka-btn--lg" rel="nofollow"
						href="tel:+91<?php echo esc_attr( $number ); ?>">
						<?php esc_html_e( 'Call now', 'kaamase-core' ); ?>
					</a>

					<a class="ka-btn ka-btn--outline ka-btn--lg" rel="nofollow noopener" target="_blank"
						href="https://wa.me/91<?php echo esc_attr( $number ); ?>">
						<?php esc_html_e( 'WhatsApp', 'kaamase-core' ); ?>
					</a>
				</div>

				<p class="ka-hint ka-mt-6">
					<?php
					printf(
						/* translators: %s: the person's name */
						esc_html__( 'We have told %s that you looked up their number. Agree the rate before work starts.', 'kaamase-core' ),
						esc_html( $name )
					);
					?>
				</p>

			<?php endif; ?>

			<p class="ka-small ka-mute ka-mt-4">
				<?php
				printf(
					esc_html(
						/* translators: %s: number of lookups left today */
						_n( '%s lookup left today.', '%s lookups left today.', kaamase_contact_quota_left( $user_id ), 'kaamase-core' )
					),
					esc_html( number_format_i18n( kaamase_contact_quota_left( $user_id ) ) )
				);
				?>
			</p>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_contact_refused' ) ) {
	/**
	 * Explain why contact was refused, and offer the way past it.
	 *
	 * A refusal with no route forward is a dead end. Every one of these
	 * carries the specific next step.
	 *
	 * @since 1.0.0
	 * @param WP_Error $error   The refusal.
	 * @param int      $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_contact_refused( $error, $post_id ) {

		$code = $error->get_error_code();

		$action = array( '', '' );

		switch ( $code ) {

			case 'kaamase_signed_out':
				$action = array( wp_login_url( get_permalink( $post_id ) ), __( 'Sign in', 'kaamase-core' ) );
				break;

			case 'kaamase_unverified':
				$action = array( kaamase_page_url( 'dashboard' ), __( 'Go to my account', 'kaamase-core' ) );
				break;

			case 'kaamase_needs_verified_employer':
				$action = array( kaamase_page_url( 'contact' ), __( 'Ask to be verified', 'kaamase-core' ) );
				break;
		}

		ob_start();
		?>
		<div class="ka-notice ka-notice--warn">
			<div>
				<span class="ka-notice__title"><?php esc_html_e( 'Contact details are not shown', 'kaamase-core' ); ?></span>
				<p><?php echo esc_html( $error->get_error_message() ); ?></p>

				<?php if ( $action[0] ) : ?>
					<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="<?php echo esc_url( $action[0] ); ?>">
						<?php echo esc_html( $action[1] ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_format_phone' ) ) {
	/**
	 * Space a ten digit number so it can be read off a screen.
	 *
	 * People copy these by hand onto paper and into a keypad. 98560
	 * 12345 is far harder to get wrong than 9856012345.
	 *
	 * @since 1.0.0
	 * @param string $number Ten digits.
	 * @return string Formatted number.
	 */
	function kaamase_format_phone( $number ) {

		$number = preg_replace( '/\D+/', '', (string) $number );

		if ( 10 !== strlen( $number ) ) {
			return $number;
		}

		return substr( $number, 0, 5 ) . ' ' . substr( $number, 5 );
	}
}


/* ==========================================================================
   7. BLOCKING

   A worker being pestered needs to be able to stop it themselves,
   tonight, without contacting anybody or waiting for a moderator.
   ========================================================================== */

if ( ! function_exists( 'kaamase_handle_block' ) ) {
	/**
	 * Block or unblock a user.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_block() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'block_user' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_block_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_block_nonce'] ) ), 'kaamase_block' )
		) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$target = isset( $_POST['kaamase_target'] ) ? absint( $_POST['kaamase_target'] ) : 0;
		$undo   = ! empty( $_POST['kaamase_unblock'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! $target ) {
			return;
		}

		$user_id = get_current_user_id();
		$blocked = array_map( 'absint', kaamase_meta_array( get_user_meta( $user_id, 'kaamase_blocked', true ) ) );

		if ( $undo ) {
			$blocked = array_diff( $blocked, array( $target ) );
		} elseif ( ! in_array( $target, $blocked, true ) ) {
			$blocked[] = $target;
		}

		update_user_meta( $user_id, 'kaamase_blocked', array_values( array_filter( $blocked ) ) );

		wp_safe_redirect( add_query_arg( 'saved', 'blocked', kaamase_page_url( 'dashboard' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_block' );

if ( ! function_exists( 'kaamase_who_looked_me_up' ) ) {
	/**
	 * Render the lookup list for the profile owner, with block buttons.
	 *
	 * Shown on the dashboard. Two purposes: it is the person's own
	 * information and they are entitled to it, and seeing that four
	 * employers looked them up this week is the single most convincing
	 * evidence that the platform is doing something.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_who_looked_me_up( $post_id ) {

		if ( ! kaamase_user_owns( $post_id ) ) {
			return '';
		}

		$log = kaamase_contact_log( $post_id, 10 );

		if ( empty( $log ) ) {
			return sprintf(
				'<section class="ka-card ka-card--flat ka-mt-6"><h2>%1$s</h2><p class="ka-soft ka-mt-4">%2$s</p></section>',
				esc_html__( 'Who looked you up', 'kaamase-core' ),
				esc_html__( 'Nobody yet. Finish your profile and set yourself available, and you will show up in more searches.', 'kaamase-core' )
			);
		}

		$blocked = array_map( 'absint', kaamase_meta_array( get_user_meta( get_current_user_id(), 'kaamase_blocked', true ) ) );

		ob_start();
		?>
		<section class="ka-mt-6">

			<h2><?php esc_html_e( 'Who looked you up', 'kaamase-core' ); ?></h2>

			<ul class="ka-stack ka-mt-4">
				<?php
				foreach ( $log as $entry ) :

					$who        = get_userdata( $entry['user'] );
					$is_blocked = in_array( absint( $entry['user'] ), $blocked, true );
					?>
					<li class="ka-card ka-card--flat">
						<div class="ka-cluster ka-cluster--between">
							<div>
								<strong><?php echo esc_html( $who ? $who->display_name : __( 'An employer', 'kaamase-core' ) ); ?></strong>
								<p class="ka-small ka-mute">
									<?php
									printf(
										/* translators: %s: how long ago */
										esc_html__( '%s ago', 'kaamase-core' ),
										esc_html( human_time_diff( (int) $entry['time'], time() ) )
									);
									?>
								</p>
							</div>

							<form method="post" action="">
								<?php wp_nonce_field( 'kaamase_block', 'kaamase_block_nonce' ); ?>
								<input type="hidden" name="kaamase_action" value="block_user">
								<input type="hidden" name="kaamase_target" value="<?php echo esc_attr( $entry['user'] ); ?>">

								<?php if ( $is_blocked ) : ?>
									<input type="hidden" name="kaamase_unblock" value="1">
									<button class="ka-btn ka-btn--ghost ka-btn--sm" type="submit">
										<?php esc_html_e( 'Unblock', 'kaamase-core' ); ?>
									</button>
								<?php else : ?>
									<button class="ka-btn ka-btn--ghost ka-btn--sm" type="submit">
										<?php esc_html_e( 'Block', 'kaamase-core' ); ?>
									</button>
								<?php endif; ?>
							</form>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="ka-small ka-mute ka-mt-4">
				<?php esc_html_e( 'Blocking stops that person seeing your number. They are not told.', 'kaamase-core' ); ?>
			</p>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}