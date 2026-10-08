<?php
/**
 * How people find you.
 *
 * The explainer that says what decides who gets seen, and a short card
 * on the dashboard pointing at it.
 *
 * Why write this down at all
 * --------------------------
 * Every marketplace gets asked the same question in the end: why is that
 * person above me. If there is no answer in public, people invent one,
 * and the invented answer is always that somebody paid. Once workers
 * believe that, a free platform gets treated like a rigged one and the
 * ones who cannot pay stop bothering.
 *
 * The answer here is unusually good, so it should be said out loud. The
 * order is availability first, then whoever has waited longest. There is
 * nothing to buy. A worker with no money reaches the front of the list
 * by waiting, which is a promise most job boards cannot make and none of
 * them advertise.
 *
 * What this file will not say
 * ---------------------------
 * That opening the app helps. That spending time on it helps. That
 * paying helps. None of it is true: exposure.php sorts on availability
 * and on days waited, and a profile whose owner never opens the app
 * still arrives at the front in its turn.
 *
 * It would be easy copy to write and it would work for about a month,
 * until somebody who followed the advice compared notes with somebody
 * who ignored it. A ranking explainer that turns out to be false does
 * more damage than no explainer, because it is read by exactly the
 * people who were trying hardest.
 *
 * Every claim below is a claim about code in this plugin. If the
 * ordering changes, this file changes with it.
 *
 * @package KaamaseCore
 * @version 1.0.1
 * @since   1.0.0
 *
 * Changelog
 *   1.0.1  The dashboard card shows again. It checked for kaamase_worker,
 *          and the dashboard says worker.
 */

defined( 'ABSPATH' ) || exit;


/** Where the page holding the article is remembered. */
define( 'KAAMASE_HOW_SEEN_CACHE', 'kaamase_how_seen_page' );


/* ==========================================================================
   1. FINDING THE PAGE

   Found by looking for the shortcode rather than by asking the owner to
   choose it on a settings screen. One less step to get wrong, and it
   corrects itself if the page is ever moved or rebuilt.
   ========================================================================== */

if ( ! function_exists( 'kaamase_how_seen_page_id' ) ) {
	/**
	 * The page carrying the article, if there is one.
	 *
	 * @since 1.0.0
	 * @return int Page ID, or 0.
	 */
	function kaamase_how_seen_page_id() {

		$cached = get_transient( KAAMASE_HOW_SEEN_CACHE );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$found = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				's'                => 'kaamase_how_seen',
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		$page_id = ! empty( $found[0] ) ? (int) $found[0] : 0;

		set_transient( KAAMASE_HOW_SEEN_CACHE, $page_id, DAY_IN_SECONDS );

		return $page_id;
	}
}

if ( ! function_exists( 'kaamase_how_seen_forget_page' ) ) {
	/**
	 * Look again next time a page is saved.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_how_seen_forget_page() {
		delete_transient( KAAMASE_HOW_SEEN_CACHE );
	}
}
add_action( 'save_post_page', 'kaamase_how_seen_forget_page' );

if ( ! function_exists( 'kaamase_how_seen_url' ) ) {
	/**
	 * The article address.
	 *
	 * @since 1.0.0
	 * @return string URL, or an empty string.
	 */
	function kaamase_how_seen_url() {

		$page_id = kaamase_how_seen_page_id();

		return $page_id ? (string) get_permalink( $page_id ) : '';
	}
}


/* ==========================================================================
   2. THE ARTICLE
   ========================================================================== */

if ( ! function_exists( 'kaamase_how_seen_points' ) ) {
	/**
	 * The whole explainer, as headings and points.
	 *
	 * Held as data rather than as a page of markup so the same words can
	 * go to the app without a second copy being written and then quietly
	 * drifting from this one.
	 *
	 * @since 1.0.0
	 * @return array[] Sections, each with a heading and a list of points.
	 */
	function kaamase_how_seen_points() {

		return array(

			array(
				'heading' => __( 'How the order is decided', 'kaamase-core' ),
				'points'  => array(
					array(
						'title' => __( 'Available comes first.', 'kaamase-core' ),
						'body'  => __( 'If your profile says you are available for work, you are shown ahead of everybody marked busy. This is the single biggest thing you control and it is one tap. Turn it off when you are working. An employer who rings three busy people stops ringing anybody.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'After that it is turn taking.', 'kaamase-core' ),
						'body'  => __( 'Among available workers, whoever has gone longest without being on a first page goes to the top. Your turn comes around whether or not you do anything. Nobody sits at the bottom forever.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'A new profile goes to the front.', 'kaamase-core' ),
						'body'  => __( 'Somebody who joined this morning has waited longer than everybody, as far as the list is concerned, so they are seen on their first day rather than waiting weeks for a turn.', 'kaamase-core' ),
					),
				),
			),

			array(
				'heading' => __( 'What decides whether you are in the list at all', 'kaamase-core' ),
				'points'  => array(
					array(
						'title' => __( 'Your trade.', 'kaamase-core' ),
						'body'  => __( 'Employers look for a mason, a driver, a carpenter. If your trade is missing or wrong you are not in that list at any position. Pick the one you actually do most.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'Your district.', 'kaamase-core' ),
						'body'  => __( 'Almost nobody hires across the state for a day of work. Your district is what puts you in front of the people close enough to call you.', 'kaamase-core' ),
					),
				),
			),

			array(
				'heading' => __( 'What decides whether they call', 'kaamase-core' ),
				'points'  => array(
					array(
						'title' => __( 'A photograph.', 'kaamase-core' ),
						'body'  => __( 'A profile with a face gets tapped far more than one without. It does not have to be a good photograph. It has to be you.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'Say what you actually do.', 'kaamase-core' ),
						'body'  => __( 'Plastering, shuttering, tile work, night shifts, your own tools. Somebody deciding between two masons decides on this.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'Ratings, once three people have worked with you.', 'kaamase-core' ),
						'body'  => __( 'We wait for three before showing a score, so one bad day does not decide everything. When somebody hires you, answer the question we send afterwards. That is what builds it.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'A correct phone number.', 'kaamase-core' ),
						'body'  => __( 'Everything above is wasted on a number that does not ring. Check it today.', 'kaamase-core' ),
					),
				),
			),

			array(
				'heading' => __( 'What does not work', 'kaamase-core' ),
				'points'  => array(
					array(
						'title' => __( 'Paying. There is nothing to buy.', 'kaamase-core' ),
						'body'  => __( 'Rich Manu gives an employer more phone lookups in a day and lets them mark more jobs urgent. It does not move anybody up a list, and it never will. If it did, the person at the top would be the person with money rather than the person free for work on Thursday, and every employer here would learn to ignore the top of the list.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'Opening the app every day.', 'kaamase-core' ),
						'body'  => __( 'Although we encourage you to actively use the Kaam Ase app, it does not change where you appear in the list. Open it when you need work, make sure you are available, and get on with your day otherwise.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'A second profile.', 'kaamase-core' ),
						'body'  => __( 'Two profiles split your ratings and your history in half, so both do worse than one would have. Employers also notice, and it is the kind of thing that gets both taken down.', 'kaamase-core' ),
					),
					array(
						'title' => __( 'The tick is not a rank.', 'kaamase-core' ),
						'body'  => __( 'It means somebody at Kaam Ase telephoned that person and spoke to them. It tells a stranger they are real. It does not move them above you and it is not given out for using the app.', 'kaamase-core' ),
					),
				),
			),
		);
	}
}

if ( ! function_exists( 'kaamase_how_seen_short' ) ) {
	/**
	 * The version that fits on a dashboard.
	 *
	 * @since 1.0.0
	 * @return string[] Four lines.
	 */
	function kaamase_how_seen_short() {

		return array(
			__( 'Say you are available. Available workers are shown ahead of busy ones, and it is one tap.', 'kaamase-core' ),
			__( 'Get your trade and district right. That is what decides which lists you turn up in.', 'kaamase-core' ),
			__( 'Add a photograph and say what you actually do. That is what decides whether somebody calls.', 'kaamase-core' ),
			__( 'Then wait. The order is turn taking, so everybody reaches the top. Nobody can pay to get there sooner.', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_how_seen_shortcode' ) ) {
	/**
	 * The article.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_how_seen_shortcode() {

		ob_start();
		?>
		<div class="ka-stack--lg ka-how">

			<p class="ka-lead">
				<?php esc_html_e( 'Nobody can pay to be higher up this site. There is no advertisement, no boosted profile and no hidden score. Here is exactly what decides who gets seen, so you can judge for yourself whether that is true.', 'kaamase-core' ); ?>
			</p>

			<?php foreach ( kaamase_how_seen_points() as $section ) : ?>

				<section class="ka-card ka-card--pad-lg">

					<h2><?php echo esc_html( $section['heading'] ); ?></h2>

					<ul class="ka-how__list ka-mt-4">
						<?php foreach ( $section['points'] as $point ) : ?>
							<li>
								<strong><?php echo esc_html( $point['title'] ); ?></strong>
								<span><?php echo esc_html( $point['body'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>

				</section>

			<?php endforeach; ?>

			<p class="ka-soft">
				<?php esc_html_e( 'If you ever see something on Kaam Ase that looks like it contradicts this page, tell us. This page describes how the site is built, and if the two disagree then one of them is wrong and we want to know which.', 'kaamase-core' ); ?>
			</p>

		</div>

		<style id="kaamase-how-seen">
			.ka-how__list{list-style:none;margin:0;padding:0}
			.ka-how__list li{padding:.6rem 0;line-height:1.55}
			.ka-how__list strong{display:block}
		</style>
		<?php

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_how_seen', 'kaamase_how_seen_shortcode' );


/* ==========================================================================
   3. THE CARD ON THE DASHBOARD
   ========================================================================== */

if ( ! function_exists( 'kaamase_how_seen_card' ) ) {
	/**
	 * The short version, with a way through to the long one.
	 *
	 * Shown to workers. An employer's question is how to get answers, not
	 * how to be found, and a card that answers somebody else's question
	 * is a card they learn to scroll past.
	 *
	 * @since 1.0.0
	 * @param int    $user_id Who is looking.
	 * @param int    $profile Their profile.
	 * @param string $type    Which kind of profile.
	 * @return void
	 */
	function kaamase_how_seen_card( $user_id, $profile, $type ) {

		unset( $profile );

		/*
		 * The dashboard names the side, worker or employer, not the post
		 * type, so this waited for a name it was never given and the card
		 * never showed. Shown to anybody with a worker profile, whichever
		 * side they registered as.
		 */
		$works = in_array( $type, array( 'worker', 'kaamase_worker', 'kaamase_gang' ), true )
			|| ( function_exists( 'kaamase_has_worker_side' ) && kaamase_has_worker_side( (int) $user_id ) );

		if ( ! $works ) {
			return;
		}

		$url = kaamase_how_seen_url();
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">

			<h2><?php esc_html_e( 'How people find you', 'kaamase-core' ); ?></h2>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'Nobody can pay to be higher up this site. Four things decide whether you get called.', 'kaamase-core' ); ?>
			</p>

			<ul class="ka-how__list ka-mt-4">
				<?php foreach ( kaamase_how_seen_short() as $line ) : ?>
					<li><span><?php echo esc_html( $line ); ?></span></li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $url ) : ?>
				<p class="ka-mt-4">
					<a class="ka-btn ka-btn--outline ka-btn--sm" href="<?php echo esc_url( $url ); ?>">
						<?php esc_html_e( 'Read the whole thing', 'kaamase-core' ); ?>
					</a>
				</p>
			<?php endif; ?>

			<style id="kaamase-how-seen-card">
				.ka-how__list{list-style:none;margin:0;padding:0}
				.ka-how__list li{padding:.5rem 0;line-height:1.5}
			</style>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_how_seen_card', 60, 3 );


/* ==========================================================================
   4. THE SAME WORDS FOR THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_how_seen_shape_me' ) ) {
	/**
	 * Send the explainer with the account.
	 *
	 * Both halves go: the short lines for a card, and the full set of
	 * sections so the app can show the article without opening a browser.
	 * A worker on a slow connection should not have to load a web page to
	 * find out how the platform treats them.
	 *
	 * @since 1.0.0
	 * @param array $me      The account object.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_how_seen_shape_me( $me, $user_id ) {

		unset( $user_id );

		$me['how_seen'] = array(
			'title'    => __( 'How people find you', 'kaamase-core' ),
			'summary'  => __( 'Nobody can pay to be higher up this site. Four things decide whether you get called.', 'kaamase-core' ),
			'short'    => array_values( kaamase_how_seen_short() ),
			'sections' => array_values( kaamase_how_seen_points() ),
			'url'      => kaamase_how_seen_url(),
		);

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_how_seen_shape_me', 24, 2 );