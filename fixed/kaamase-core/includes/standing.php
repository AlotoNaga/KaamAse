<?php
/**
 * What is known about somebody who is hiring.
 *
 * A short, honest list shown on a job post, so a worker deciding
 * whether to answer an advertisement from a stranger has something to
 * decide on.
 *
 * Why not a badge
 * ---------------
 * The obvious version of this is a verified tick, and it was the plan
 * until two things ruled it out.
 *
 * A tick that can be bought means the person paid, while a worker
 * reading it believes somebody checked. That gap is carried by the
 * worker who takes the job. And a tick that depends on GST registration
 * is nearly useless here anyway: the people hiring on this platform are
 * households and small builders taking on a mason for a house, and they
 * have no GST and never will.
 *
 * So there is no badge. There is a list of things that are true, each
 * shown only when it is true, each of them something the account earned
 * rather than bought:
 *
 *   what workers said about them, and how many said it
 *   how many people they have actually hired through the platform
 *   how long they have been here
 *   a GST number, on the rare occasion there is one
 *
 * And when none of that is true yet, it says so. New is not a failure
 * and hiding it would be the dishonest part: everybody starts there, a
 * worker is entitled to know, and a platform that quietly makes a brand
 * new account look established is running the risk on the worker's
 * behalf without telling them.
 *
 * Why this cannot be sold
 * -----------------------
 * Every line comes from something that happened. Ratings come from jobs
 * that were done, hires come from both sides confirming, the date comes
 * from registration. Money cannot move any of them, which is what makes
 * them worth reading. The paid plan sells speed and reach on the hiring
 * side and touches nothing in this file.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE FACTS
   ========================================================================== */

if ( ! function_exists( 'kaamase_employer_standing' ) ) {
	/**
	 * What is known about an account as somebody who hires.
	 *
	 * @since 1.3.0
	 * @param int $user_id The person hiring.
	 * @return array{lines: string[], is_new: bool, rating: float, ratings: int, hires: int}
	 */
	function kaamase_employer_standing( $user_id ) {

		$user_id = (int) $user_id;

		$out = array(
			'lines'   => array(),
			'is_new'  => true,
			'rating'  => 0.0,
			'ratings' => 0,
			'hires'   => 0,
		);

		if ( ! $user_id ) {
			return $out;
		}

		$profile = kaamase_get_user_profile( $user_id, 'kaamase_employer' );

		if ( $profile && function_exists( 'kaamase_field' ) ) {

			$out['rating']  = (float) kaamase_field( $profile, 'rating_average', 0 );
			$out['ratings'] = (int) kaamase_field( $profile, 'rating_count', 0 );
			$out['hires']   = (int) kaamase_field( $profile, 'hires_made', 0 );
		}

		/*
		 * Ratings first, because it is the only line here that carries
		 * an opinion rather than a count, and it is the one a worker
		 * would ask about if they could.
		 */
		if ( $out['ratings'] > 0 ) {

			$out['lines'][] = sprintf(
				/* translators: 1: rating out of five, 2: how many workers rated them */
				_n(
					'Rated %1$s by %2$d worker',
					'Rated %1$s by %2$d workers',
					$out['ratings'],
					'kaamase-core'
				),
				number_format_i18n( $out['rating'], 1 ),
				$out['ratings']
			);
		}

		if ( $out['hires'] > 0 ) {

			$out['lines'][] = sprintf(
				/* translators: %d: how many people they have hired */
				_n(
					'Has hired %d person through Kaam Ase',
					'Has hired %d people through Kaam Ase',
					$out['hires'],
					'kaamase-core'
				),
				$out['hires']
			);
		}

		$since = kaamase_employer_since( $user_id );

		if ( '' !== $since ) {
			$out['lines'][] = sprintf(
				/* translators: %s: month and year */
				__( 'On Kaam Ase since %s', 'kaamase-core' ),
				$since
			);
		}

		if ( function_exists( 'kaamase_business_on_file' ) && kaamase_business_on_file( $user_id ) ) {
			$out['lines'][] = kaamase_business_mark_label();
		}

		/*
		 * New means no rating and no hire. Being registered a while
		 * without either is not a track record, and counting it as one
		 * would let somebody age into looking established without ever
		 * having employed anybody.
		 */
		$out['is_new'] = 0 === $out['ratings'] && 0 === $out['hires'];

		return $out;
	}
}

if ( ! function_exists( 'kaamase_employer_since' ) ) {
	/**
	 * The month an account registered.
	 *
	 * Month and year, never a date. A precise date invites somebody to
	 * work out that an account is four days old and treat that as the
	 * point, when the thing that matters is whether they have hired
	 * anybody.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return string Empty when unknown.
	 */
	function kaamase_employer_since( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user || empty( $user->user_registered ) ) {
			return '';
		}

		$time = strtotime( $user->user_registered );

		return $time ? date_i18n( 'F Y', $time ) : '';
	}
}

if ( ! function_exists( 'kaamase_new_employer_note' ) ) {
	/**
	 * What to say about somebody who has not hired anybody yet.
	 *
	 * Said without suspicion. Most new accounts are exactly what they
	 * appear to be, and a platform that greets every newcomer as a
	 * probable fraud has no newcomers. The advice under it is the same
	 * advice that belongs on every job, which is the honest position:
	 * the risk is lower with a track record, never zero without one.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	function kaamase_new_employer_note() {

		return __( 'New here, and has not hired anybody through Kaam Ase yet. That is normal for somebody who has just joined. Agree the rate and the payment day out loud before the first day, as you would with anyone.', 'kaamase-core' );
	}
}


/* ==========================================================================
   2. FOR THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_shape_job_standing' ) ) {
	/**
	 * Put the standing on a job.
	 *
	 * On the job rather than on the employer's page, because the job is
	 * where the decision happens. Very few workers open a profile before
	 * calling a number.
	 *
	 * @since 1.3.0
	 * @param array   $shaped The job as sent to the app.
	 * @param WP_Post $post   The job.
	 * @return array
	 */
	function kaamase_shape_job_standing( $shaped, $post ) {

		if ( ! is_array( $shaped ) || ! $post instanceof WP_Post ) {
			return $shaped;
		}

		$standing = kaamase_employer_standing( (int) $post->post_author );

		$shaped['employer_standing'] = array(
			'lines'    => $standing['lines'],
			'is_new'   => $standing['is_new'],
			'new_note' => $standing['is_new'] ? kaamase_new_employer_note() : '',
		);

		return $shaped;
	}
}
add_filter( 'kaamase_shape_job', 'kaamase_shape_job_standing', 12, 2 );


/* ==========================================================================
   3. FOR THE WEBSITE
   ========================================================================== */

if ( ! function_exists( 'kaamase_employer_standing_html' ) ) {
	/**
	 * The standing block, for a job page.
	 *
	 * @since 1.3.0
	 * @param int $user_id The person hiring.
	 * @return string Markup.
	 */
	function kaamase_employer_standing_html( $user_id ) {

		$standing = kaamase_employer_standing( (int) $user_id );

		ob_start();
		?>
		<div class="ka-card ka-stack ka-mt-6">

			<h3><?php esc_html_e( 'About who is hiring', 'kaamase-core' ); ?></h3>

			<?php if ( ! empty( $standing['lines'] ) ) : ?>
				<ul class="ka-stack">
					<?php foreach ( $standing['lines'] as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $standing['is_new'] ) : ?>
				<p class="ka-small ka-soft">
					<?php echo esc_html( kaamase_new_employer_note() ); ?>
				</p>
			<?php endif; ?>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}