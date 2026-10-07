<?php
/**
 * Closed jobs stay on the internet.
 *
 * A job that has been filled or has run out is locked rather than
 * hidden, the way an old forum thread stays readable after it is closed.
 *
 * What this changes
 * -----------------
 * Until now a closed job returned 404 to everybody except its owner. The
 * page vanished from the internet the day the work went, taking with it
 * every link anybody had ever shared and every search result it had
 * earned.
 *
 * For a job board that is the wrong trade. The value of a listing does
 * not end when the work is filled: somebody searching next year for
 * plastering work in Dimapur should land on the page, see that this one
 * went, and find the open ones from there. A site with three years of
 * readable job pages behind it is the site that comes up first. A site
 * that deletes its own history has to win that fight again every month.
 *
 * What it does not change
 * -----------------------
 * A closed job stays out of every listing, every search on this site and
 * the sitemap. It is reachable by its own address and by nothing else,
 * so nobody browsing for work is shown work that has gone.
 *
 * The number is refused. Not hidden behind a message, refused before the
 * lookup is counted, because the one unforgivable outcome here is a
 * worker spending a lookup and a phone call on a job that was filled
 * three weeks ago and an employer being rung about it.
 *
 * The structured data was already handled: schema.php returns nothing
 * for a job that is not open, so a closed page carries no job markup for
 * a search engine to treat as live work. That was correct before this
 * file existed and it stays correct.
 *
 * @package KaamaseCore
 * @version 1.2.0
 * @since   1.0.0
 *
 * Changelog
 *   1.2.0  Kinder words for somebody who followed an old link: why the
 *          job closed and what that means for them ("Somebody was hired
 *          for it before you."), on the page and in the apps. The apps
 *          used to be told "That link does not lead anywhere any more."
 *          for every closed job they opened from a shared link. The
 *          theme can now show the notice at the top of the page, above
 *          the pay.
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE STATUS BECOMES PUBLIC

   Two lines that do all the work.

   WordPress decides whether to show a single post by one test: is the
   post's status public. Everything else about a status, protected,
   excluded from search, hidden from the admin list, applies to listings
   rather than to the page itself.

   Which is why this is safe. Every listing on this site pins its own
   post status to publish, in queries.php for the main query and by hand
   in each of the four secondary queries, so none of them can pick up a
   closed job however this status is registered. Site search goes through
   the same pinning. The sitemap asks for published posts only, which is
   also what Google asks for: an expired posting should not be advertised
   as new work.
   ========================================================================== */

if ( ! function_exists( 'kaamase_closed_jobs_readable' ) ) {
	/**
	 * Let a closed job render for anybody who has the address.
	 *
	 * Changed on the registered object rather than by registering the
	 * status again, so the label, the count wording and the admin list
	 * behaviour that post-types.php set are all left exactly as they
	 * are. This file adds one capability and takes nothing away.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_closed_jobs_readable() {

		$status = get_post_status_object( 'kaamase_closed' );

		if ( ! $status ) {
			return;
		}

		$status->public             = true;
		$status->publicly_queryable = true;
	}
}
add_action( 'init', 'kaamase_closed_jobs_readable', 7 );

if ( ! function_exists( 'kaamase_closed_jobs_no_404' ) ) {
	/**
	 * Stop the guard that turned these pages into 404s.
	 *
	 * Needed as well as the status change. Plenty of finished jobs never
	 * reach the closed status at all: a job whose end date has passed is
	 * still published until the daily job runs, and that guard sent
	 * those to a 404 on the expiry date rather than on the status.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_closed_jobs_no_404() {

		remove_action( 'template_redirect', 'kaamase_protect_closed_jobs', 10 );
	}
}
add_action( 'init', 'kaamase_closed_jobs_no_404', 8 );


/* ==========================================================================
   2. THE NUMBER IS STILL REFUSED
   ========================================================================== */

if ( ! function_exists( 'kaamase_closed_jobs_asked_for_contact' ) ) {
	/**
	 * The closed job somebody just asked for the number of.
	 *
	 * @since 1.0.0
	 * @return int Job ID, or 0.
	 */
	function kaamase_closed_jobs_asked_for_contact() {

		return (int) ( $GLOBALS['kaamase_closed_contact'] ?? 0 );
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_hold_contact' ) ) {
	/**
	 * Take the contact screen off a closed job.
	 *
	 * Stopped here, before the screen runs, rather than by emptying the
	 * number it would have shown. That screen spends the visitor's daily
	 * lookup and writes to the employer's contact log as its first act,
	 * before it has looked at the number at all. Blocking it any later
	 * would charge somebody for a number they were never going to be
	 * given.
	 *
	 * The owner is not stopped. Their own closed job is theirs to look
	 * at.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_closed_jobs_hold_contact() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which screen was asked for.
		$asked = isset( $_GET['kaamase_contact'] ) ? absint( $_GET['kaamase_contact'] ) : 0;

		if ( ! $asked || 'kaamase_job' !== get_post_type( $asked ) ) {
			return;
		}

		if ( ! function_exists( 'kaamase_job_is_open' ) || kaamase_job_is_open( $asked ) ) {
			return;
		}

		if ( current_user_can( 'edit_post', $asked ) ) {
			return;
		}

		remove_filter( 'the_content', 'kaamase_contact_screen', 5 );

		$GLOBALS['kaamase_closed_contact'] = $asked;
	}
}
add_action( 'template_redirect', 'kaamase_closed_jobs_hold_contact', 11 );


/* ==========================================================================
   3. SAYING SO ON THE PAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_closed_jobs_reason' ) ) {
	/**
	 * Why this job is closed, in words.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return string
	 */
	function kaamase_closed_jobs_reason( $job_id ) {

		$state = (string) get_post_meta( $job_id, KAAMASE_META_PREFIX . 'job_status', true );

		if ( 'filled' === $state ) {
			return __( 'This job has been filled.', 'kaamase-core' );
		}

		if ( 'closed' === $state ) {
			return __( 'The employer has closed this job.', 'kaamase-core' );
		}

		$expires = (int) get_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires', true );

		if ( $expires && $expires < time() ) {
			return sprintf(
				/* translators: %s: the date the job ran out */
				__( 'This job ran out on %s.', 'kaamase-core' ),
				date_i18n( get_option( 'date_format' ), $expires )
			);
		}

		return __( 'This job is closed.', 'kaamase-core' );
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_said' ) ) {
	/**
	 * New wording, shown only where it can be read in full.
	 *
	 * A sentence added here is English until somebody translates it.
	 * Put next to a sentence that is already in Hindi or Nagamese, it
	 * would make one message in two languages. So the new sentence is
	 * used in English, and in any other language only once it has been
	 * translated; until then that reader gets $instead, which is the
	 * wording they had before.
	 *
	 * @since 1.2.0
	 * @param string $said    The new sentence, through __().
	 * @param string $english The same sentence, untranslated.
	 * @param string $instead What to say until it is translated.
	 * @return string
	 */
	function kaamase_closed_jobs_said( $said, $english, $instead = '' ) {

		if ( $said !== $english || 0 === strpos( (string) determine_locale(), 'en' ) ) {
			return $said;
		}

		return $instead;
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_for_you' ) ) {
	/**
	 * What the closing means for somebody who wanted the job.
	 *
	 * The line after the reason. Only for somebody who followed a link
	 * to it: the employer who closed it does not need telling.
	 *
	 * @since 1.2.0
	 * @param int $job_id Job ID.
	 * @return string Empty when there is nothing kind and true to add.
	 */
	function kaamase_closed_jobs_for_you( $job_id ) {

		$state = (string) get_post_meta( $job_id, KAAMASE_META_PREFIX . 'job_status', true );

		if ( 'filled' === $state ) {
			return kaamase_closed_jobs_said( __( 'Somebody was hired for it before you.', 'kaamase-core' ), 'Somebody was hired for it before you.' );
		}

		if ( 'closed' === $state ) {
			return kaamase_closed_jobs_said( __( 'They are no longer taking anybody for it.', 'kaamase-core' ), 'They are no longer taking anybody for it.' );
		}

		$expires = (int) get_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires', true );

		if ( $expires && $expires < time() ) {
			return kaamase_closed_jobs_said( __( 'It is no longer taking anybody.', 'kaamase-core' ), 'It is no longer taking anybody.' );
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_message' ) ) {
	/**
	 * The whole message for an app that opened a closed job.
	 *
	 * The app puts its own heading ("This job has closed") and an "open
	 * jobs" button around it, so this is the reason and what it means,
	 * nothing more.
	 *
	 * @since 1.2.0
	 * @param int $job_id Job ID.
	 * @return string
	 */
	function kaamase_closed_jobs_message( $job_id ) {

		return trim( kaamase_closed_jobs_reason( $job_id ) . ' ' . kaamase_closed_jobs_for_you( $job_id ) );
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_gone_message' ) ) {
	/**
	 * The message for an app that opened a link to a job that is not
	 * there at all: deleted by its poster, or taken down.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	function kaamase_closed_jobs_gone_message() {

		return kaamase_closed_jobs_said(
			__( 'This job is no longer on Kaam Ase. It has probably been filled, or the employer closed it.', 'kaamase-core' ),
			'This job is no longer on Kaam Ase. It has probably been filled, or the employer closed it.',
			__( 'That job no longer exists.', 'kaamase-core' )
		);
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_ways_on' ) ) {
	/**
	 * Links to work that is still open.
	 *
	 * The reason anybody should be glad they landed here. Somebody who
	 * followed an old link wants the same kind of work in the same
	 * place, and both of those are already on the job.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return string Markup.
	 */
	function kaamase_closed_jobs_ways_on( $job_id ) {

		$links = array();

		foreach ( array( 'kaamase_trade', 'kaamase_district' ) as $taxonomy ) {

			$terms = get_the_terms( $job_id, $taxonomy );

			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}

			$term = $terms[0];
			$url  = get_term_link( $term );

			if ( is_wp_error( $url ) ) {
				continue;
			}

			$links[] = sprintf(
				'<a class="ka-btn ka-btn--outline ka-btn--sm" href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html(
					sprintf(
						/* translators: %s: a trade or a district */
						__( 'Open jobs in %s', 'kaamase-core' ),
						$term->name
					)
				)
			);
		}

		$links[] = sprintf(
			'<a class="ka-btn ka-btn--ghost ka-btn--sm" href="%1$s">%2$s</a>',
			esc_url( get_post_type_archive_link( 'kaamase_job' ) ),
			esc_html__( 'All open jobs', 'kaamase-core' )
		);

		/*
		 * And back to the archive, when there is one. This is the link
		 * that stops the page being orphaned: without a way back to a
		 * list, a closed job is reachable only by its own address.
		 *
		 * @since 1.1.0
		 */
		$archive = function_exists( 'kaamase_past_jobs_url' ) ? kaamase_past_jobs_url() : '';

		if ( $archive ) {
			$links[] = sprintf(
				'<a class="ka-btn ka-btn--ghost ka-btn--sm" href="%1$s">%2$s</a>',
				esc_url( $archive ),
				esc_html__( 'Past jobs', 'kaamase-core' )
			);
		}

		return '<div class="ka-cluster ka-mt-4">' . implode( '', $links ) . '</div>';
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_notice_markup' ) ) {
	/**
	 * The locked notice itself.
	 *
	 * @since 1.2.0 Split out of kaamase_closed_jobs_notice(), so the
	 *              theme can put it at the top of the page.
	 * @param int $job_id Job ID.
	 * @return string Markup.
	 */
	function kaamase_closed_jobs_notice_markup( $job_id ) {

		$heading = ( kaamase_closed_jobs_asked_for_contact() === $job_id )
			? __( 'The number for this job is closed', 'kaamase-core' )
			: __( 'This job is closed', 'kaamase-core' );

		$body = kaamase_closed_jobs_reason( $job_id );

		if ( kaamase_closed_jobs_asked_for_contact() === $job_id ) {
			$body .= ' ' . __( 'Nobody has been rung and nothing has been counted against your lookups for today.', 'kaamase-core' );
		} else {
			/*
			 * Somebody who followed a link is told what the closing means
			 * for them. The employer looking at their own closed job, and
			 * a reader whose language does not have the new line yet, get
			 * the sentence they always had.
			 */
			$for_you = current_user_can( 'edit_post', $job_id ) ? '' : kaamase_closed_jobs_for_you( $job_id );

			$body .= ' ' . ( '' !== $for_you ? $for_you : __( 'It is kept here so the record stays, but the work has gone.', 'kaamase-core' ) );
		}

		return '<div class="ka-notice ka-notice--closed"><div>'
			. '<p><strong>' . esc_html( $heading ) . '</strong></p>'
			. '<p>' . esc_html( $body ) . '</p>'
			. kaamase_closed_jobs_ways_on( $job_id )
			. '</div></div>';
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_box' ) ) {
	/**
	 * The notice, for the theme to print at the top of a closed job.
	 *
	 * Above the pay, so somebody who followed an old link learns the
	 * work has gone before they read a wage they cannot have. Printed
	 * once: the description no longer carries it after this has run.
	 * A theme that never calls this keeps the notice where it was.
	 *
	 * @since 1.2.0
	 * @param int $job_id Job ID.
	 * @return string Markup, or empty for an open job.
	 */
	function kaamase_closed_jobs_box( $job_id ) {

		$job_id = (int) $job_id;

		if ( ! $job_id || ! function_exists( 'kaamase_job_is_open' ) || kaamase_job_is_open( $job_id ) ) {
			return '';
		}

		$GLOBALS['kaamase_closed_box_shown'] = $job_id;

		return kaamase_closed_jobs_notice_markup( $job_id );
	}
}

if ( ! function_exists( 'kaamase_closed_jobs_notice' ) ) {
	/**
	 * Put the locked notice at the top of the job.
	 *
	 * Highest priority of anything that adds to a job page, so it sits
	 * above the pictures and reads first. Somebody who followed an old
	 * link should know within one second that the work has gone, before
	 * they read a wage they cannot have.
	 *
	 * @since 1.0.0
	 * @param string $content The job description.
	 * @return string
	 */
	function kaamase_closed_jobs_notice( $content ) {

		if ( ! is_singular( 'kaamase_job' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$job_id = get_the_ID();

		if ( ! function_exists( 'kaamase_job_is_open' ) || kaamase_job_is_open( $job_id ) ) {
			return $content;
		}

		// The theme has already put it at the top of the page.
		if ( (int) ( $GLOBALS['kaamase_closed_box_shown'] ?? 0 ) === (int) $job_id ) {
			return $content;
		}

		return kaamase_closed_jobs_notice_markup( $job_id ) . $content;
	}
}
add_filter( 'the_content', 'kaamase_closed_jobs_notice', 13 );


/* ==========================================================================
   4. THE BUTTON GOES QUIET

   The action bar is drawn by the theme template, which has no hook to
   remove it from. A body class and one rule take it off a closed job.

   That is presentation only and it is not the guard. The guard is above:
   the number is refused whether or not this rule ever loads, which is
   the order these two things have to be in.
   ========================================================================== */

if ( ! function_exists( 'kaamase_closed_jobs_body_class' ) ) {
	/**
	 * Mark the page as a closed job.
	 *
	 * @since 1.0.0
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	function kaamase_closed_jobs_body_class( $classes ) {

		if ( is_singular( 'kaamase_job' )
			&& function_exists( 'kaamase_job_is_open' )
			&& ! kaamase_job_is_open( get_queried_object_id() ) ) {

			$classes[] = 'kaamase-job-closed';
		}

		return $classes;
	}
}
add_filter( 'body_class', 'kaamase_closed_jobs_body_class' );

if ( ! function_exists( 'kaamase_closed_jobs_style' ) ) {
	/**
	 * Hide the contact bar and mark the notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_closed_jobs_style() {

		if ( ! is_singular( 'kaamase_job' ) ) {
			return;
		}

		if ( ! function_exists( 'kaamase_job_is_open' ) || kaamase_job_is_open( get_queried_object_id() ) ) {
			return;
		}

		echo '<style id="kaamase-closed-job">'
			. '.kaamase-job-closed .ka-actionbar{display:none}'
			. '.ka-notice--closed{border-left:4px solid currentColor;opacity:.95}'
			. '</style>' . "\n";
	}
}
add_action( 'wp_head', 'kaamase_closed_jobs_style', 8 );