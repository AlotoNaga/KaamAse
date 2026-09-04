<?php
/**
 * Confirming a hire that the platform never saw.
 *
 * The gap this closes
 * -------------------
 * Every rating on Kaam Ase depends on a recorded hire, and until now
 * exactly one thing in the whole plugin could record one: an employer
 * revealing a worker's number, then answering "yes I hired them" two
 * days later on their dashboard.
 *
 * That is a narrow door. Hiring here happens on the phone, on WhatsApp,
 * through a cousin, and at the work site. None of that goes through a
 * contact reveal, so none of it produces a hire, so none of those two
 * people can ever rate each other -- and nothing on any screen tells
 * them why. It also means one mis-tap on "No" ended it permanently:
 * the question could never be asked again.
 *
 * This file adds a second door. It does not touch the first one.
 * hires.php is unchanged and keeps working exactly as it did.
 *
 * Why the other side has to confirm
 * ---------------------------------
 * The existing answer is one-sided on purpose: the employer paid a
 * contact lookup to get that number, and the reveal is the evidence
 * that a real approach happened.
 *
 * A claim made here has no such evidence behind it. Letting it stand
 * alone would mean anybody could manufacture a hire with a stranger and
 * with it the right to rate them, which on a platform where a bad score
 * costs somebody work is not a small thing. So the other person
 * confirms, in both directions. Nobody is rated by somebody they have
 * never heard of.
 *
 * Nothing here is ever permanent. A declined claim can be made again
 * after a week, and one nobody answers is dropped after a month so it
 * can be made afresh.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. LIMITS
   ========================================================================== */

/** Most claims one person may start in a day. */
if ( ! defined( 'KAAMASE_CLAIMS_PER_DAY' ) ) {
	define( 'KAAMASE_CLAIMS_PER_DAY', 10 );
}

/** How long before a claim nobody answered is dropped. */
if ( ! defined( 'KAAMASE_CLAIM_WINDOW' ) ) {
	define( 'KAAMASE_CLAIM_WINDOW', 30 * DAY_IN_SECONDS );
}

/**
 * How long after a refusal the same claim may be made again.
 *
 * Long enough that a refusal is not something to be worn down by asking
 * again on the same afternoon, short enough that an honest mistake is
 * not permanent.
 */
if ( ! defined( 'KAAMASE_CLAIM_AGAIN_AFTER' ) ) {
	define( 'KAAMASE_CLAIM_AGAIN_AFTER', 7 * DAY_IN_SECONDS );
}


/* ==========================================================================
   2. THE STORE

   Held in user meta on the person who has to answer, so finding what
   somebody must confirm is one read with no query. Small, bounded, and
   it goes with the account.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hire_claims_for' ) ) {
	/**
	 * Every claim waiting on this person.
	 *
	 * @since 1.5.0
	 * @param int $user_id The person who must confirm.
	 * @return array[] Claims keyed by reference.
	 */
	function kaamase_hire_claims_for( $user_id ) {

		$stored = get_user_meta( absint( $user_id ), 'kaamase_hire_claims', true );

		return is_array( $stored ) ? $stored : array();
	}
}

if ( ! function_exists( 'kaamase_hire_claims_save' ) ) {
	/**
	 * Store the list, newest first and capped.
	 *
	 * @since 1.5.0
	 * @param int   $user_id The person who must confirm.
	 * @param array $claims  Claims keyed by reference.
	 * @return void
	 */
	function kaamase_hire_claims_save( $user_id, $claims ) {

		if ( count( $claims ) > 60 ) {

			uasort(
				$claims,
				static function ( $a, $b ) {
					return absint( $b['time'] ) <=> absint( $a['time'] );
				}
			);

			$claims = array_slice( $claims, 0, 60, true );
		}

		update_user_meta( absint( $user_id ), 'kaamase_hire_claims', $claims );
	}
}

if ( ! function_exists( 'kaamase_hire_claim_ref' ) ) {
	/**
	 * The reference for one pairing.
	 *
	 * A worker profile and an employer account name a hire exactly, so
	 * the same pairing always lands on the same entry however it was
	 * claimed and whoever claimed it.
	 *
	 * @since 1.5.0
	 * @param int $worker_id   Worker or team profile ID.
	 * @param int $employer_id Employer user ID.
	 * @return string
	 */
	function kaamase_hire_claim_ref( $worker_id, $employer_id ) {
		return md5( absint( $worker_id ) . '_' . absint( $employer_id ) );
	}
}


/* ==========================================================================
   3. WORKING OUT THE PAIRING

   Both directions end in the same place, because kaamase_record_hire
   always wants the same two things: the worker's profile and the
   employer's account.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hire_claim_pairing' ) ) {
	/**
	 * Who this claim would be between, seen from one profile page.
	 *
	 * @since 1.5.0
	 * @param int $post_id Profile being looked at.
	 * @param int $user_id The person doing the claiming.
	 * @return array|WP_Error worker, employer and confirm keys, or why not.
	 */
	function kaamase_hire_claim_pairing( $post_id, $user_id ) {

		$post    = get_post( absint( $post_id ) );
		$user_id = absint( $user_id );

		if ( ! $post || ! $user_id ) {
			return new WP_Error( 'kaamase_claim_no_post', __( 'That profile no longer exists.', 'kaamase-core' ) );
		}

		if ( kaamase_user_owns( $post->ID, $user_id ) ) {
			return new WP_Error( 'kaamase_claim_self', __( 'That is your own profile.', 'kaamase-core' ) );
		}

		$owner = (int) $post->post_author;

		if ( ! $owner || $owner === $user_id ) {
			return new WP_Error( 'kaamase_claim_self', __( 'That is your own profile.', 'kaamase-core' ) );
		}

		// An employer saying they hired this worker or this team.
		if ( in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true ) ) {

			if ( ! user_can( $user_id, 'create_kaamase_jobs' ) ) {
				return new WP_Error( 'kaamase_claim_side', __( 'Only somebody hiring can confirm a hire from this side.', 'kaamase-core' ) );
			}

			return array(
				'worker'   => (int) $post->ID,
				'employer' => $user_id,
				'confirm'  => $owner,
			);
		}

		// A worker saying they worked for this employer.
		if ( 'kaamase_employer' === $post->post_type ) {

			$mine = kaamase_get_user_profile( $user_id, 'kaamase_worker' );

			if ( ! $mine ) {
				return new WP_Error( 'kaamase_claim_side', __( 'You need a worker profile before you can say you worked somewhere.', 'kaamase-core' ) );
			}

			return array(
				'worker'   => (int) $mine,
				'employer' => $owner,
				'confirm'  => $owner,
			);
		}

		return new WP_Error( 'kaamase_claim_side', __( 'A hire cannot be confirmed against this page.', 'kaamase-core' ) );
	}
}


/* ==========================================================================
   4. MAKING A CLAIM
   ========================================================================== */

if ( ! function_exists( 'kaamase_hire_claim_make' ) ) {
	/**
	 * Say a hire happened, and ask the other person to agree.
	 *
	 * @since 1.5.0
	 * @param int $post_id Profile being looked at.
	 * @param int $user_id The person claiming.
	 * @return true|WP_Error
	 */
	function kaamase_hire_claim_make( $post_id, $user_id ) {

		$user_id = absint( $user_id );
		$pair    = kaamase_hire_claim_pairing( $post_id, $user_id );

		if ( is_wp_error( $pair ) ) {
			return $pair;
		}

		// Already connected. There is nothing to confirm.
		if ( function_exists( 'kaamase_hire_exists_between' )
			&& kaamase_hire_exists_between( $pair['worker'], $pair['employer'] ) ) {
			return new WP_Error( 'kaamase_claim_done', __( 'You are already able to rate each other.', 'kaamase-core' ) );
		}

		$ref    = kaamase_hire_claim_ref( $pair['worker'], $pair['employer'] );
		$claims = kaamase_hire_claims_for( $pair['confirm'] );

		if ( isset( $claims[ $ref ] ) ) {

			$state = isset( $claims[ $ref ]['state'] ) ? (string) $claims[ $ref ]['state'] : '';

			if ( 'pending' === $state ) {
				return new WP_Error( 'kaamase_claim_waiting', __( 'They have already been asked. It is with them now.', 'kaamase-core' ) );
			}

			/*
			 * Refused before. Askable again, but not straight away.
			 * A refusal that can be repeated on the same afternoon is a
			 * way of wearing somebody down rather than a question.
			 */
			if ( 'no' === $state ) {

				$answered = absint( isset( $claims[ $ref ]['answered'] ) ? $claims[ $ref ]['answered'] : 0 );

				if ( $answered && ( time() - $answered ) < KAAMASE_CLAIM_AGAIN_AFTER ) {
					return new WP_Error( 'kaamase_claim_soon', __( 'They said no to this recently. You can ask again in a week.', 'kaamase-core' ) );
				}
			}
		}

		/*
		 * A ceiling per person per day. Confirming real hires is a
		 * handful a week for a busy contractor; anything near this is
		 * somebody working through a list of strangers.
		 */
		if ( function_exists( 'kaamase_rate_bump' ) ) {

			$asked = kaamase_rate_bump( 'hire_claims_' . $user_id . '_' . gmdate( 'Ymd' ), DAY_IN_SECONDS );

			if ( $asked > KAAMASE_CLAIMS_PER_DAY ) {
				return new WP_Error( 'kaamase_claim_many', __( 'That is enough hires to confirm for one day. Try again tomorrow.', 'kaamase-core' ) );
			}
		}

		$claims[ $ref ] = array(
			'worker'   => (int) $pair['worker'],
			'employer' => (int) $pair['employer'],
			'by'       => $user_id,
			'time'     => time(),
			'state'    => 'pending',
		);

		kaamase_hire_claims_save( $pair['confirm'], $claims );

		/**
		 * Fires when somebody says a hire happened.
		 *
		 * @since 1.5.0
		 * @param string $ref     Claim reference.
		 * @param int    $confirm The person being asked to agree.
		 * @param array  $claim   The claim.
		 */
		do_action( 'kaamase_hire_claimed', $ref, (int) $pair['confirm'], $claims[ $ref ] );

		return true;
	}
}

if ( ! function_exists( 'kaamase_hire_claim_answer' ) ) {
	/**
	 * Agree, or say it did not happen.
	 *
	 * @since 1.5.0
	 * @param string $ref     Claim reference.
	 * @param bool   $agreed  Whether the hire happened.
	 * @param int    $user_id The person answering.
	 * @return true|WP_Error
	 */
	function kaamase_hire_claim_answer( $ref, $agreed, $user_id ) {

		$user_id = absint( $user_id );
		$ref     = preg_replace( '/[^a-f0-9]/', '', (string) $ref );
		$claims  = kaamase_hire_claims_for( $user_id );

		/*
		 * Only a claim actually made against this person can be
		 * answered by them. Without this anybody could post a reference
		 * and manufacture a hire, which is the same as manufacturing the
		 * right to rate a stranger.
		 */
		$state = isset( $claims[ $ref ]['state'] ) ? (string) $claims[ $ref ]['state'] : '';

		if ( '' === $ref || 'pending' !== $state ) {
			return new WP_Error( 'kaamase_claim_gone', __( 'That question is no longer waiting for an answer.', 'kaamase-core' ) );
		}

		$claims[ $ref ]['state']    = $agreed ? 'yes' : 'no';
		$claims[ $ref ]['answered'] = time();

		kaamase_hire_claims_save( $user_id, $claims );

		if ( $agreed && function_exists( 'kaamase_record_hire' ) ) {
			kaamase_record_hire( (int) $claims[ $ref ]['worker'], (int) $claims[ $ref ]['employer'] );
		}

		return true;
	}
}


/* ==========================================================================
   5. THE BUTTON, ON THE PROFILE ITSELF

   Added to the notice ratings.php prints where the form would be, so it
   appears on the worker, team and employer pages without one line
   changing in any template.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hire_claim_button' ) ) {
	/**
	 * Offer to confirm the hire, right where the rating would be.
	 *
	 * @since 1.5.0
	 * @param string $notice  Markup so far.
	 * @param int    $post_id Profile being looked at.
	 * @param string $code    Why rating was refused.
	 * @return string
	 */
	function kaamase_hire_claim_button( $notice, $post_id, $code ) {

		if ( 'kaamase_no_hire' !== $code || '' === $notice ) {
			return $notice;
		}

		$user_id = get_current_user_id();
		$pair    = kaamase_hire_claim_pairing( $post_id, $user_id );

		if ( is_wp_error( $pair ) ) {
			return $notice;
		}

		$post   = get_post( $post_id );
		$worker = in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true );

		$ref    = kaamase_hire_claim_ref( $pair['worker'], $pair['employer'] );
		$claims = kaamase_hire_claims_for( $pair['confirm'] );

		// Already asked. Say so rather than offering a button that repeats it.
		if ( isset( $claims[ $ref ]['state'] ) && 'pending' === $claims[ $ref ]['state'] ) {
			return $notice . sprintf(
				'<p class="ka-small ka-soft ka-mt-4">%s</p>',
				esc_html__( 'You have said you worked together. It opens as soon as they agree.', 'kaamase-core' )
			);
		}

		ob_start();
		?>
		<form method="post" action="" class="ka-mt-4">
			<?php wp_nonce_field( 'kaamase_hire_claim', 'kaamase_claim_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="hire_claim">
			<input type="hidden" name="kaamase_claim_profile" value="<?php echo esc_attr( (string) absint( $post_id ) ); ?>">

			<button class="ka-btn ka-btn--action ka-btn--sm" type="submit">
				<?php
				echo $worker
					? esc_html__( 'I hired this person', 'kaamase-core' )
					: esc_html__( 'I have worked for them', 'kaamase-core' );
				?>
			</button>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'They will be asked to agree before either of you can rate the other.', 'kaamase-core' ); ?>
			</p>
		</form>
		<?php

		return $notice . (string) ob_get_clean();
	}
}
add_filter( 'kaamase_rating_blocked', 'kaamase_hire_claim_button', 10, 3 );

if ( ! function_exists( 'kaamase_handle_hire_claim' ) ) {
	/**
	 * Take a claim made from a profile page.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_handle_hire_claim() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'hire_claim' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_claim_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_claim_nonce'] ) ), 'kaamase_hire_claim' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_id = isset( $_POST['kaamase_claim_profile'] ) ? absint( $_POST['kaamase_claim_profile'] ) : 0;

		$done = kaamase_hire_claim_make( $post_id, get_current_user_id() );

		wp_safe_redirect(
			add_query_arg(
				'claim',
				is_wp_error( $done ) ? $done->get_error_code() : 'asked',
				get_permalink( $post_id )
			) . '#ka-rate'
		);
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_hire_claim' );

if ( ! function_exists( 'kaamase_hire_claim_result' ) ) {
	/**
	 * Say what happened, above the rating area.
	 *
	 * @since 1.5.0
	 * @param string $notice  Markup so far.
	 * @param int    $post_id Profile being looked at.
	 * @param string $code    Why rating was refused.
	 * @return string
	 */
	function kaamase_hire_claim_result( $notice, $post_id, $code ) {

		unset( $post_id, $code );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which message to show.
		$said = isset( $_GET['claim'] ) ? sanitize_key( wp_unslash( $_GET['claim'] ) ) : '';

		if ( '' === $said ) {
			return $notice;
		}

		$words = array(
			'asked'                 => __( 'Asked. They will see it the next time they open Kaam Ase.', 'kaamase-core' ),
			'kaamase_claim_waiting' => __( 'They have already been asked. It is with them now.', 'kaamase-core' ),
			'kaamase_claim_soon'    => __( 'They said no to this recently. You can ask again in a week.', 'kaamase-core' ),
			'kaamase_claim_many'    => __( 'That is enough hires to confirm for one day. Try again tomorrow.', 'kaamase-core' ),
			'kaamase_claim_done'    => __( 'You are already able to rate each other.', 'kaamase-core' ),
		);

		if ( ! isset( $words[ $said ] ) ) {
			return $notice;
		}

		return sprintf(
			'<div class="ka-notice ka-notice--%1$s ka-mt-6"><div><p>%2$s</p></div></div>',
			'asked' === $said ? 'ok' : 'warn',
			esc_html( $words[ $said ] )
		) . $notice;
	}
}
add_filter( 'kaamase_rating_blocked', 'kaamase_hire_claim_result', 20, 3 );


/* ==========================================================================
   6. THE QUESTION, ON THE OTHER PERSON'S DASHBOARD

   Through the prompts hook the dashboard already fires, so nothing in
   dashboard.php changes.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hire_claims_due' ) ) {
	/**
	 * Claims still waiting on this person.
	 *
	 * @since 1.5.0
	 * @param int $user_id The person who must answer.
	 * @return array[] Claims with their reference in a ref key.
	 */
	function kaamase_hire_claims_due( $user_id ) {

		$due = array();

		foreach ( kaamase_hire_claims_for( $user_id ) as $ref => $claim ) {

			if ( ! isset( $claim['state'] ) || 'pending' !== $claim['state'] ) {
				continue;
			}

			if ( ( time() - absint( $claim['time'] ) ) > KAAMASE_CLAIM_WINDOW ) {
				continue;
			}

			$claim['ref'] = (string) $ref;
			$due[]        = $claim;
		}

		return $due;
	}
}

if ( ! function_exists( 'kaamase_hire_claims_section' ) ) {
	/**
	 * Ask whether the hire somebody named really happened.
	 *
	 * @since 1.5.0
	 * @param int $user_id Dashboard owner.
	 * @return void
	 */
	function kaamase_hire_claims_section( $user_id ) {

		$due = kaamase_hire_claims_due( $user_id );

		if ( empty( $due ) ) {
			return;
		}

		$due = array_slice( $due, 0, 3 );
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">

			<h2><?php esc_html_e( 'Did you work together?', 'kaamase-core' ); ?></h2>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'These people say you worked together. Agreeing is what lets the two of you rate each other, and nobody sees a rating until you have both written one.', 'kaamase-core' ); ?>
			</p>

			<ul class="ka-stack ka-mt-6">
				<?php
				foreach ( $due as $claim ) :

					$asker = absint( $claim['by'] );

					// Name the person, not the record. Their profile, whichever side they are.
					$face = kaamase_get_user_profile( $asker, 'kaamase_employer' );

					if ( ! $face ) {
						$face = kaamase_get_user_profile( $asker, 'kaamase_worker' );
					}

					$who = $face ? get_the_title( $face ) : '';

					if ( '' === $who ) {
						$user = get_userdata( $asker );
						$who  = $user ? $user->display_name : __( 'Somebody on Kaam Ase', 'kaamase-core' );
					}
					?>
					<li class="ka-card ka-card--flat">
						<div class="ka-cluster ka-cluster--between">

							<div class="ka-cluster">
								<?php
								if ( $face ) {
									echo kaamase_avatar( $face, 'kaamase-avatar', 'ka-avatar--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>

								<div>
									<?php if ( $face ) : ?>
										<a href="<?php echo esc_url( get_permalink( $face ) ); ?>">
											<strong><?php echo esc_html( $who ); ?></strong>
										</a>
									<?php else : ?>
										<strong><?php echo esc_html( $who ); ?></strong>
									<?php endif; ?>

									<p class="ka-small ka-mute">
										<?php
										printf(
											/* translators: %s: how long ago */
											esc_html__( 'Said so %s ago', 'kaamase-core' ),
											esc_html( human_time_diff( absint( $claim['time'] ), time() ) )
										);
										?>
									</p>
								</div>
							</div>

							<form method="post" action="" class="ka-cluster">
								<?php wp_nonce_field( 'kaamase_claim_answer', 'kaamase_claim_answer_nonce' ); ?>
								<input type="hidden" name="kaamase_action" value="claim_answer">
								<input type="hidden" name="kaamase_claim_ref" value="<?php echo esc_attr( $claim['ref'] ); ?>">

								<button class="ka-btn ka-btn--action ka-btn--sm" type="submit"
									name="kaamase_claim_agreed" value="1">
									<?php esc_html_e( 'Yes, we did', 'kaamase-core' ); ?>
								</button>

								<button class="ka-btn ka-btn--ghost ka-btn--sm" type="submit"
									name="kaamase_claim_agreed" value="0">
									<?php esc_html_e( 'No', 'kaamase-core' ); ?>
								</button>
							</form>

						</div>
					</li>
				<?php endforeach; ?>
			</ul>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_prompts', 'kaamase_hire_claims_section', 15, 1 );

if ( ! function_exists( 'kaamase_handle_claim_answer' ) ) {
	/**
	 * Take the answer from the dashboard.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_handle_claim_answer() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'claim_answer' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_claim_answer_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_claim_answer_nonce'] ) ), 'kaamase_claim_answer' )
		) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$ref    = isset( $_POST['kaamase_claim_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_claim_ref'] ) ) : '';
		$agreed = isset( $_POST['kaamase_claim_agreed'] ) && '1' === (string) wp_unslash( $_POST['kaamase_claim_agreed'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		kaamase_hire_claim_answer( $ref, $agreed, get_current_user_id() );

		wp_safe_redirect(
			add_query_arg( 'saved', $agreed ? 'confirmed' : 'notconfirmed', kaamase_page_url( 'dashboard' ) )
		);
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_claim_answer' );


/* ==========================================================================
   7. THE SAME THING FOR THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_hire_claims_rest' ) ) {
	/**
	 * List what this account has been asked to agree to.
	 *
	 * @since 1.5.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_hire_claims_rest( $request ) {

		unset( $request );

		$out = array();

		foreach ( kaamase_hire_claims_due( get_current_user_id() ) as $claim ) {

			$asker = absint( $claim['by'] );
			$face  = kaamase_get_user_profile( $asker, 'kaamase_employer' );

			if ( ! $face ) {
				$face = kaamase_get_user_profile( $asker, 'kaamase_worker' );
			}

			$user = get_userdata( $asker );

			$out[] = array(
				'ref'        => (string) $claim['ref'],
				'name'       => $face ? get_the_title( $face ) : ( $user ? $user->display_name : '' ),
				'profile_id' => (int) $face,
				'url'        => $face ? (string) get_permalink( $face ) : '',
				'image'      => ( $face && function_exists( 'kaamase_shape_image' ) ) ? kaamase_shape_image( $face ) : null,
				'asked_at'   => gmdate( 'c', absint( $claim['time'] ) ),
			);
		}

		return new WP_REST_Response( array( 'claims' => $out ), 200 );
	}
}

if ( ! function_exists( 'kaamase_hire_claim_make_rest' ) ) {
	/**
	 * Say a hire happened, from the app.
	 *
	 * @since 1.5.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_hire_claim_make_rest( $request ) {

		$done = kaamase_hire_claim_make( absint( $request->get_param( 'profile' ) ), get_current_user_id() );

		if ( is_wp_error( $done ) ) {
			$done->add_data( array( 'status' => 400 ) );
			return $done;
		}

		return new WP_REST_Response( array( 'asked' => true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_hire_claim_answer_rest' ) ) {
	/**
	 * Agree or refuse, from the app.
	 *
	 * @since 1.5.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_hire_claim_answer_rest( $request ) {

		$done = kaamase_hire_claim_answer(
			(string) $request->get_param( 'ref' ),
			(bool) $request->get_param( 'confirm' ),
			get_current_user_id()
		);

		if ( is_wp_error( $done ) ) {
			$done->add_data( array( 'status' => 400 ) );
			return $done;
		}

		return new WP_REST_Response( array( 'answered' => true ), 200 );
	}
}

if ( ! function_exists( 'kaamase_hire_claims_routes' ) ) {
	/**
	 * Register the three addresses.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_hire_claims_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = function_exists( 'kaamase_rest_require_login' )
			? 'kaamase_rest_require_login'
			: 'is_user_logged_in';

		register_rest_route(
			KAAMASE_REST_NS,
			'/hire-claims',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_hire_claims_rest',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/hire-claims',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_hire_claim_make_rest',
				'permission_callback' => $auth,
				'args'                => array(
					'profile' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/hire-claims/answer',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_hire_claim_answer_rest',
				'permission_callback' => $auth,
				'args'                => array(
					'ref'     => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'confirm' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_hire_claims_routes' );


/* ==========================================================================
   8. TIDYING UP
   ========================================================================== */

if ( ! function_exists( 'kaamase_hire_claims_expire' ) ) {
	/**
	 * Drop questions nobody answered in time.
	 *
	 * Dropped rather than refused, so the same claim can simply be made
	 * again. Somebody who never opened the app is not somebody saying
	 * no.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_hire_claims_expire() {

		$users = get_users(
			array(
				'meta_key' => 'kaamase_hire_claims', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'fields'   => 'ID',
				'number'   => 200,
			)
		);

		foreach ( $users as $user_id ) {

			$claims  = kaamase_hire_claims_for( $user_id );
			$changed = false;

			foreach ( $claims as $ref => $claim ) {

				$state = isset( $claim['state'] ) ? (string) $claim['state'] : '';
				$old   = ( time() - absint( $claim['time'] ) ) > KAAMASE_CLAIM_WINDOW;

				if ( $old && 'pending' === $state ) {
					unset( $claims[ $ref ] );
					$changed = true;
				}
			}

			if ( $changed ) {
				kaamase_hire_claims_save( $user_id, $claims );
			}
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_hire_claims_expire' );
