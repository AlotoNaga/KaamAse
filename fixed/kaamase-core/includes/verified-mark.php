<?php
/**
 * Showing the mark.
 *
 * The tick, where people can see it.
 *
 * What was wrong
 * --------------
 * rich-manu.php has built the mark since it was written: an SVG tick in
 * a filled circle, a label, and a panel explaining what it stands for.
 * Nothing has ever called it. Not the theme, not the plugin. The website
 * has been rendering a different badge with a similar name, an admin set
 * field on the profile, which is why somebody granted the mark saw
 * nothing on the website while the app showed it correctly.
 *
 * There was no styling for it either, so even where it had been used it
 * would have drawn as an unstyled tick and a run of text.
 *
 * Where it goes
 * -------------
 * Beside the name, not underneath it. A trust mark under a name reads as
 * one more tag in a row of tags, and a row of tags is the thing people
 * have learned to skip. Beside the name it is read as part of the name,
 * which is the whole point and is why every platform that has one puts
 * it there.
 *
 * Two shapes for two jobs. Next to a name it is the tick alone, because
 * a name is phrasing and anything bigger is a paragraph interrupting a
 * heading. In the badge row underneath it stays the full mark with its
 * label and its panel, so the explanation is one tap away on the same
 * screen.
 *
 * Why a panel and not a tooltip
 * -----------------------------
 * A mark nobody can question is worth less than one they can. Somebody
 * who has never seen it before should be able to find out what it claims
 * without leaving the profile, and the claim should be narrow enough to
 * survive being read carefully: we telephoned this person. Not that
 * their work is good, not that they are recommended.
 *
 * It opens without any script, so it works on a cheap phone on a bad
 * connection before anything else on the page has loaded.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. BESIDE THE NAME
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_author_of' ) ) {
	/**
	 * Whose account a profile belongs to.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return int User ID, or 0.
	 */
	function kaamase_mark_author_of( $post_id ) {

		$post_id = (int) $post_id;

		if ( ! $post_id ) {
			return 0;
		}

		return (int) get_post_field( 'post_author', $post_id );
	}
}

if ( ! function_exists( 'kaamase_mark_in_title' ) ) {
	/**
	 * Put the tick after the name on a profile page.
	 *
	 * Narrow on purpose. Only the profile being looked at, only on the
	 * front end, only in the main loop. The title filter runs in a
	 * dozen places nobody thinks about, including menus and link text,
	 * and a trust mark appearing in a navigation menu would be both
	 * wrong and hard to trace back to here.
	 *
	 * The compact mark, which is a single span. A name is a heading and
	 * a heading holds phrasing, so the version with the panel in it
	 * cannot go here without producing markup that browsers will break
	 * apart in their own ways. The panel lives in the row underneath.
	 *
	 * @since 1.0.0
	 * @param string $title   The name.
	 * @param int    $post_id Which post.
	 * @return string
	 */
	function kaamase_mark_in_title( $title, $post_id = 0 ) {

		if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		$post_id = (int) $post_id;

		if ( ! $post_id || get_queried_object_id() !== $post_id ) {
			return $title;
		}

		if ( ! in_array(
			get_post_type( $post_id ),
			array( 'kaamase_worker', 'kaamase_employer', 'kaamase_gang' ),
			true
		) ) {
			return $title;
		}

		if ( ! function_exists( 'kaamase_called_badge' ) ) {
			return $title;
		}

		$badge = kaamase_called_badge( kaamase_mark_author_of( $post_id ), true );

		return $badge ? $title . ' ' . $badge : $title;
	}
}
add_filter( 'the_title', 'kaamase_mark_in_title', 10, 2 );


/* ==========================================================================
   2. IN THE BADGE ROW

   kaamase_badges() is the theme's, and every profile template plus the
   hiring block on a job page already calls it. Defining it here first
   puts the mark in all of them at once: plugins load before themes, and
   the theme's copy is wrapped in a check for whether the name is already
   taken, which is what makes replacing it from here legitimate rather
   than a collision.

   The two badges it already drew are reproduced exactly. This adds a
   third and changes nothing else.
   ========================================================================== */

if ( ! function_exists( 'kaamase_badges' ) ) {
	/**
	 * Trust badges for a profile.
	 *
	 * Vouched sits above verified on purpose. In Nagaland a named colony
	 * or village authority standing behind somebody carries more weight
	 * than any automated check this platform could run.
	 *
	 * The call mark goes last and looks different from the other two,
	 * because it is a different kind of claim. The first two are things
	 * recorded about somebody. This one is a thing we did.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string Markup.
	 */
	function kaamase_badges( $post_id ) {

		$out = '';

		/*
		 * The Ad mark goes first, and it is the only one here that was
		 * bought rather than earned.
		 *
		 * It belongs in this copy of the function rather than the
		 * theme's. The note at the top of this section is the reason:
		 * this file takes the name before the theme can, so the theme's
		 * copy never runs on a site where this plugin is active. The
		 * mark was added there first and was dead code -- a promoted
		 * worker showed the Ad label in the app and nothing at all on
		 * the website, which is the worst of both, because the person
		 * who paid could see it working everywhere except the place they
		 * were most likely to check.
		 *
		 * First in the row because a disclosure printed after two awards
		 * has already been read as a third award. Quieter than both,
		 * because an advertisement dressed as an achievement is what an
		 * advertising code exists to prevent -- and because an employer
		 * who works out that the bright badge is for sale stops
		 * believing Vouched and Verified as well.
		 */
		if ( function_exists( 'kaamase_promo_badge' ) ) {
			$out .= kaamase_promo_badge( $post_id );
		}

		if ( function_exists( 'kaamase_field' ) && kaamase_field( $post_id, 'vouched_by' ) ) {
			$out .= sprintf(
				'<span class="ka-badge ka-badge--vouched">%s</span>',
				esc_html__( 'Vouched', 'kaamase' )
			);
		}

		if ( function_exists( 'kaamase_field' ) && kaamase_field( $post_id, 'verified' ) ) {
			$out .= sprintf(
				'<span class="ka-badge ka-badge--verified">%s</span>',
				esc_html__( 'Verified', 'kaamase' )
			);
		}

		if ( function_exists( 'kaamase_called_badge' ) ) {
			$out .= kaamase_called_badge( kaamase_mark_author_of( $post_id ) );
		}

		return $out;
	}
}


/* ==========================================================================
   3. WHAT IT LOOKS LIKE

   Shipped from here rather than added to the theme stylesheet, so the
   mark carries its own appearance wherever it is used and cannot end up
   rendered as a bare tick and a run of text again.
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_styles' ) ) {
	/**
	 * The mark's appearance.
	 *
	 * Sized in ems so it scales with whatever text it sits beside: the
	 * same rules work in a heading and in a small grey line under a job
	 * title, without either one needing to know about the other.
	 *
	 * Green rather than the amber used on buttons. Nobody should look at
	 * a trust mark and think it is something to press for an action, and
	 * on this site amber means press me.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_mark_styles() {

		if ( is_admin() ) {
			return;
		}

		echo '<style id="kaamase-mark">'
			/* The tick itself, in a filled circle. */
			. '.ka-tick{display:inline-flex;align-items:center;justify-content:center;'
			. 'width:1.05em;height:1.05em;border-radius:50%;background:#14532d;'
			. 'flex:0 0 auto;vertical-align:-0.14em}'
			. '.ka-tick svg{width:.72em;height:.72em;fill:#fff;display:block}'

			/* Beside a name. */
			. '.ka-called--compact{display:inline-flex;margin-left:.28em;vertical-align:baseline}'

			/* In a badge row, with its label. */
			. '.ka-called-wrap{display:inline-block}'
			. '.ka-called{display:inline-flex;align-items:center;gap:.35em;cursor:pointer;'
			. 'list-style:none;font-size:.875rem;font-weight:600;color:#14532d}'
			. '.ka-called::-webkit-details-marker{display:none}'
			. '.ka-called__text{line-height:1}'

			/* The panel that opens on a tap. */
			. '.ka-called__panel{margin-top:.6rem;padding:.85rem 1rem;border-radius:12px;'
			. 'background:rgba(20,83,45,.06);border:1px solid rgba(20,83,45,.14);'
			. 'font-size:.875rem;line-height:1.5;max-width:34em}'
			. '.ka-called__panel p{margin:0 0 .6rem}'
			. '.ka-called__panel p:last-child{margin:0}'

			/* Read by a screen reader, not drawn. */
			. '.ka-sr{position:absolute;width:1px;height:1px;overflow:hidden;'
			. 'clip:rect(0 0 0 0);white-space:nowrap}'
			. '</style>' . "\n";
	}
}
add_action( 'wp_head', 'kaamase_mark_styles', 9 );


/* ==========================================================================
   4. NOT SOMEBODY ELSE'S MARK

   A job that staff put up for an outside employer is authored by the
   staff account. rich-manu.php reads the mark off the job's author, which
   is right for every job somebody posted themselves and wrong for these:
   the name on the job belongs to a person with no account here, and the
   tick beside it would be the poster's.

   That is the one way this badge could state something untrue, so it is
   cleared here rather than left to each screen to remember. The website
   is already safe by accident, because those jobs have no employer
   profile for the badge row to read from. The app was not.
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_not_posted_for' ) ) {
	/**
	 * Take the mark off a job posted on somebody else's behalf.
	 *
	 * @since 1.1.0
	 * @param array $shaped The shaped job.
	 * @return array
	 */
	function kaamase_mark_not_posted_for( $shaped ) {

		if ( ! is_array( $shaped ) || empty( $shaped['posted_for'] ) ) {
			return $shaped;
		}

		$shaped['called'] = array(
			'called' => false,
			'label'  => '',
			'since'  => 0,
		);

		return $shaped;
	}
}
add_filter( 'kaamase_shape_job', 'kaamase_mark_not_posted_for', 20 );


/* ==========================================================================
   5. THE SAME WORDS FOR THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_mark_explainer_shape' ) ) {
	/**
	 * Send the explanation with the mark.
	 *
	 * The app draws its own tick and needs the same sentences behind it.
	 * Sent on every shape that already carries the mark, so a profile
	 * anywhere in the app can open the panel without another request.
	 *
	 * @since 1.0.0
	 * @param array $shaped The shaped record.
	 * @return array
	 */
	function kaamase_mark_explainer_shape( $shaped ) {

		if ( ! is_array( $shaped ) || empty( $shaped['called']['called'] ) ) {
			return $shaped;
		}

		if ( function_exists( 'kaamase_verified_explainer' ) ) {
			$shaped['called']['explainer'] = array_values( kaamase_verified_explainer() );
		}

		return $shaped;
	}
}
add_filter( 'kaamase_shape_worker', 'kaamase_mark_explainer_shape', 30 );
add_filter( 'kaamase_shape_employer', 'kaamase_mark_explainer_shape', 30 );
add_filter( 'kaamase_shape_job', 'kaamase_mark_explainer_shape', 30 );
add_filter( 'kaamase_shape_me', 'kaamase_mark_explainer_shape', 30 );