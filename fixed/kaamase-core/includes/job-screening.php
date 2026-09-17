<?php
/**
 * Job screening.
 *
 * Everything posted as a job is read before it reaches a worker, and
 * anything that looks like it is not a job is held back.
 *
 * What this is actually for
 * -------------------------
 * A job board reaching poor workers in the Northeast is a recruitment
 * channel, and recruitment channels get used for recruitment of the kind
 * nobody wants. Trafficking, bonded labour, and sex work advertised as
 * housekeeping are not hypothetical risks in this region and never have
 * been. Neither is the advance fee scam, which on a platform aimed at
 * people who cannot afford to lose five hundred rupees does more damage
 * per incident than it does anywhere else.
 *
 * So this is not a profanity filter. Profanity is a manners problem.
 * This is aimed at the small number of posts capable of ruining somebody
 * who trusted the platform enough to answer them.
 *
 * Why not simply approve every job by hand
 * ----------------------------------------
 * Because a labour market runs on today. Somebody needing four masons
 * tomorrow morning who posts at nine at night and sees it go live on
 * Thursday has been given nothing. Hold every job and the honest ones
 * leave, which leaves the platform quieter, and a quiet platform is one
 * the bad posts have all to themselves.
 *
 * The compromise here is that most jobs publish instantly and a small
 * number do not:
 *
 *   1. Anything matching the serious term lists is held and flagged, no
 *      matter who posted it or how long they have been here.
 *   2. An employer's first job is held, because a new account is where
 *      an abusive post almost always comes from.
 *   3. Everything else from an employer with a clean history goes live
 *      immediately.
 *
 * On the word lists
 * -----------------
 * A word list is a blunt instrument and this one will be wrong in both
 * directions. It will hold a legitimate post about a night shift at a
 * hotel, and it will miss anything phrased carefully. That is expected.
 * Its job is not to be a judge, it is to make sure a human sees the
 * post before a worker does, and being wrong in that direction costs an
 * employer a few hours.
 *
 * The lists below are a starting point written in English, and the
 * important gap is that this platform's users do not post only in
 * English. Nagamese and Hindi terms need adding by somebody who knows
 * how these advertisements are actually worded here. Use the
 * kaamase_screening_terms filter rather than editing this file, so the
 * additions survive an update.
 *
 * @package KaamaseCore
 * @version 1.2.0
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;


/** Why a job was held. */
define( 'KAAMASE_SCREEN_REASON_KEY', '_kaamase_screen_reasons' );

/** Set on a job a human has looked at and cleared. */
define( 'KAAMASE_SCREEN_CLEARED_KEY', '_kaamase_screen_cleared' );

/**
 * How many published jobs an employer needs before their posts stop
 * being held.
 *
 * One, not two. Email verification already stops an unverified account
 * from publishing anything, so this sits on top of a gate that exists,
 * and a second held post buys very little for the work it costs.
 *
 * It is not zero, and that is a judgement worth being explicit about.
 * A verification email is a weak filter: a throwaway address takes a
 * minute to make. The word lists catch the obvious posts and will miss
 * anything written carefully. Holding the first post from each new
 * employer is the one place a person reads something before a worker
 * does, and on a platform reaching people who can be badly hurt by a
 * fake job, that reading is worth the delay. It happens once per
 * employer, ever.
 *
 * Definable in wp-config.php to change it without touching this file.
 * Setting it to 0 turns the new employer hold off entirely and leaves
 * only the word lists.
 */
defined( 'KAAMASE_SCREEN_TRUST_AFTER' ) || define( 'KAAMASE_SCREEN_TRUST_AFTER', 1 );


/* ==========================================================================
   1. THE TERMS

   Two severities. Serious means hold the post and tell somebody now.
   Review means hold the post, no alarm.
   ========================================================================== */

if ( ! function_exists( 'kaamase_screening_terms' ) ) {
	/**
	 * Terms that stop a job from publishing.
	 *
	 * @since 1.1.0
	 * @return array[] Groups keyed by name, each with severity and terms.
	 */
	function kaamase_screening_terms() {

		$terms = array(

			/*
			 * Trafficking and bonded labour.
			 *
			 * The tell is almost never the work. It is the arrangement:
			 * the passport held, the advance against wages that can
			 * never be worked off, the job that is somewhere else and
			 * the transport arranged for you.
			 */
			'trafficking' => array(
				'severity' => 'serious',
				'terms'    => array(
					'passport kept',
					'keep passport',
					'documents kept',
					'hold your documents',
					'bonded',
					'bandhua',
					'advance against salary',
					'salary advance deducted',
					'debt will be adjusted',
					'cannot leave until',
					'work until debt',
					'agent will arrange travel',
					'travel arranged by agent',
					'placement outside state',
					'sending abroad',
					'gulf placement',
					'visa arranged',
				),
			),

			/*
			 * Anybody under eighteen.
			 *
			 * India prohibits employing children in hazardous work and
			 * this platform is largely hazardous work. There is no
			 * version of this that is acceptable, including the ones
			 * phrased kindly.
			 */
			'minors' => array(
				'severity' => 'serious',
				'terms'    => array(
					'under 18',
					'under18',
					'below 18',
					'under age',
					'underage',
					'minor girl',
					'minor boy',
					'school girl',
					'school boy',
					'child worker',
					'small boy',
					'small girl',
					'young girl needed',
					'young boy needed',
					'age 12',
					'age 13',
					'age 14',
					'age 15',
					'age 16',
					'age 17',
					'14 years old',
					'15 years old',
					'16 years old',
					'17 years old',
					'beti',
					'ladki chahiye',
				),
			),

			/*
			 * Sexual services dressed as work.
			 *
			 * The words are ordinary. What matters is the combination,
			 * so these are deliberately broad and will hold honest posts
			 * about hotels and spas. A held hotel job is a small cost.
			 */
			'sexual' => array(
				'severity' => 'serious',
				'terms'    => array(
					'escort',
					'call girl',
					'massage girl',
					'body massage',
					'full body service',
					'friendly girls',
					'girls needed for',
					'ladies bar',
					'dance bar',
					'private service',
					'night service',
					'stay with client',
					'good looking girls',
					'beautiful girls needed',
					'unmarried girls only',
					'physical relationship',
				),
			),

			/*
			 * Money taken from the worker.
			 *
			 * Kaam Ase promises that a worker never pays to get a job.
			 * A post asking for a fee is either a scam or a breach of
			 * that promise, and both are held.
			 */
			'advance_fee' => array(
				'severity' => 'review',
				'terms'    => array(
					'registration fee',
					'registration charge',
					'security deposit',
					'refundable deposit',
					'pay to apply',
					'joining fee',
					'processing fee',
					'training fee',
					'pay first',
					'deposit required',
					'send money',
					'google pay first',
					'phonepe first',
					'upi first',
				),
			),

			/*
			 * Excluding people by who they are.
			 *
			 * Tribe, religion and community. Nagaland is not a place
			 * where a platform can be casual about this, and a listing
			 * that shuts out a community is the kind of thing that
			 * follows the company rather than the person who wrote it.
			 */
			'discrimination' => array(
				'severity' => 'review',
				'terms'    => array(
					'no muslim',
					'muslim not allowed',
					'hindu only',
					'christian only',
					'no christian',
					'only naga',
					'naga only',
					'non naga not',
					'no bihari',
					'no bengali',
					'no outsider',
					'no tribal',
					'same tribe only',
					'own community only',
					'no illegal immigrant',
				),
			),

			/*
			 * Not a job at all.
			 */
			'not_a_job' => array(
				'severity' => 'review',
				'terms'    => array(
					'for sale',
					'selling my',
					'investment opportunity',
					'earn from home daily',
					'part time online typing',
					'data entry from home no experience',
					'crypto',
					'trading account',
					'lottery',
					'loan available',
					'personal loan',
					'network marketing',
					'mlm',
					'buy this course',
				),
			),
		);

		/**
		 * Filter the screening terms.
		 *
		 * The intended use is adding Nagamese and Hindi wording without
		 * editing this file. Groups are merged by key, so returning a
		 * group that already exists replaces its terms entirely.
		 *
		 * @since 1.1.0
		 * @param array[] $terms Groups keyed by name.
		 */
		return (array) apply_filters( 'kaamase_screening_terms', $terms );
	}
}


/* ==========================================================================
   2. READING A POST
   ========================================================================== */

if ( ! function_exists( 'kaamase_screen_text' ) ) {
	/**
	 * Check text against the term lists.
	 *
	 * Matching is done on a normalised copy: lower case, punctuation
	 * flattened to spaces, runs of spaces collapsed. That is what stops
	 * u.n.d.e.r 1.8 and UNDER-18 from walking straight past a list
	 * written in plain words.
	 *
	 * @since 1.1.0
	 * @param string $text Text to check.
	 * @return array[] Matches, each with group, severity and term.
	 */
	function kaamase_screen_text( $text ) {

		$haystack = ' ' . preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9]+/', ' ', strtolower( (string) $text ) ) ) . ' ';

		$found = array();

		foreach ( kaamase_screening_terms() as $group => $config ) {

			$severity = isset( $config['severity'] ) ? (string) $config['severity'] : 'review';
			$terms    = isset( $config['terms'] ) ? (array) $config['terms'] : array();

			foreach ( $terms as $term ) {

				$needle = ' ' . preg_replace( '/\s+/', ' ', preg_replace( '/[^a-z0-9]+/', ' ', strtolower( (string) $term ) ) ) . ' ';

				if ( ' ' === $needle || false === strpos( $haystack, $needle ) ) {
					continue;
				}

				$found[] = array(
					'group'    => $group,
					'severity' => $severity,
					'term'     => (string) $term,
				);
			}
		}

		return $found;
	}
}

if ( ! function_exists( 'kaamase_employer_is_trusted' ) ) {
	/**
	 * Whether this employer's jobs can publish without being held.
	 *
	 * Earned by having had jobs published already, which means a human
	 * cleared their earlier ones. Not by how old the account is, because
	 * waiting a week costs an abusive poster nothing.
	 *
	 * @since 1.1.0
	 * @param int $user_id Author.
	 * @return bool
	 */
	function kaamase_employer_is_trusted( $user_id ) {

		if ( user_can( $user_id, 'edit_others_kaamase_jobs' ) ) {
			return true;
		}

		$published = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'author'         => (int) $user_id,
				'post_status'    => array( 'publish', 'kaamase_closed' ),
				'posts_per_page' => KAAMASE_SCREEN_TRUST_AFTER,
				'fields'         => 'ids',
			)
		);

		$trusted = count( (array) $published ) >= KAAMASE_SCREEN_TRUST_AFTER;

		/**
		 * Filter whether an employer skips the new account hold.
		 *
		 * @since 1.1.0
		 * @param bool $trusted Whether they are trusted.
		 * @param int  $user_id Author.
		 */
		return (bool) apply_filters( 'kaamase_employer_is_trusted', $trusted, (int) $user_id );
	}
}


/* ==========================================================================
   3. HOLDING THE POST

   Done on wp_insert_post_data, which runs before the row is written, so
   a job that should not be public is never public for even a moment.
   Nothing in services.php needed changing for this.
   ========================================================================== */

if ( ! function_exists( 'kaamase_screen_job_on_save' ) ) {
	/**
	 * Hold a job that needs a human to look at it.
	 *
	 * @since 1.1.0
	 * @param array $data    Sanitised post data about to be written.
	 * @param array $postarr Raw post data.
	 * @return array Post data, possibly with the status changed.
	 */
	function kaamase_screen_job_on_save( $data, $postarr ) {

		if ( 'kaamase_job' !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		// Only ever intercepts something on its way to being public.
		if ( 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}

		$post_id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;

		/*
		 * An administrator editing a held job in wp-admin is the person
		 * who decides it is fine. Getting in their way would make the
		 * review queue impossible to clear.
		 */
		if ( current_user_can( 'edit_others_kaamase_jobs' ) ) {

			if ( $post_id ) {
				update_post_meta( $post_id, KAAMASE_SCREEN_CLEARED_KEY, 1 );
				delete_post_meta( $post_id, KAAMASE_SCREEN_REASON_KEY );
			}

			return $data;
		}

		// Already cleared once. Editing it again does not restart this.
		if ( $post_id && get_post_meta( $post_id, KAAMASE_SCREEN_CLEARED_KEY, true ) ) {
			return $data;
		}

		$author = absint( $data['post_author'] ?? 0 );

		$matches = kaamase_screen_text(
			( $data['post_title'] ?? '' ) . ' ' . ( $data['post_content'] ?? '' )
		);

		$reasons  = array();
		$serious  = false;

		foreach ( $matches as $match ) {

			$reasons[] = $match;

			if ( 'serious' === $match['severity'] ) {
				$serious = true;
			}
		}

		$hold = ! empty( $reasons );

		if ( ! $hold && ! kaamase_employer_is_trusted( $author ) ) {

			$hold      = true;
			$reasons[] = array(
				'group'    => 'new_employer',
				'severity' => 'review',
				'term'     => '',
			);
		}

		if ( ! $hold ) {
			return $data;
		}

		$data['post_status'] = 'pending';

		/*
		 * Stashed rather than written, because a new post has no ID yet.
		 * Picked up by the save_post handler below.
		 */
		kaamase_screen_stash( $reasons, $serious );

		return $data;
	}
}
add_filter( 'wp_insert_post_data', 'kaamase_screen_job_on_save', 20, 2 );

if ( ! function_exists( 'kaamase_screen_stash' ) ) {
	/**
	 * Carry the reasons from the data filter to the save action.
	 *
	 * @since 1.1.0
	 * @param array|null $reasons Reasons to stash, or null to read and clear.
	 * @param bool       $serious Whether any reason was serious.
	 * @return array{reasons: array, serious: bool}
	 */
	function kaamase_screen_stash( $reasons = null, $serious = false ) {

		static $held = array(
			'reasons' => array(),
			'serious' => false,
		);

		if ( null !== $reasons ) {

			$held = array(
				'reasons' => (array) $reasons,
				'serious' => (bool) $serious,
			);

			return $held;
		}

		$out  = $held;
		$held = array(
			'reasons' => array(),
			'serious' => false,
		);

		return $out;
	}
}

if ( ! function_exists( 'kaamase_screen_record' ) ) {
	/**
	 * Write the reasons onto the job, and raise the alarm if needed.
	 *
	 * @since 1.1.0
	 * @param int $post_id Job ID.
	 * @return void
	 */
	function kaamase_screen_record( $post_id ) {

		$held = kaamase_screen_stash();

		if ( empty( $held['reasons'] ) ) {
			return;
		}

		update_post_meta( $post_id, KAAMASE_SCREEN_REASON_KEY, $held['reasons'] );

		/**
		 * Fires when a job is held for review.
		 *
		 * @since 1.1.0
		 * @param int   $post_id Job ID.
		 * @param array $reasons Reasons it was held.
		 * @param bool  $serious Whether any reason was serious.
		 */
		do_action( 'kaamase_job_held', (int) $post_id, $held['reasons'], (bool) $held['serious'] );

		kaamase_tell_employer_job_held( (int) $post_id );

		if ( $held['serious'] ) {
			kaamase_alert_serious_job( (int) $post_id, $held['reasons'] );
		}
	}
}
add_action( 'save_post_kaamase_job', 'kaamase_screen_record', 20 );

if ( ! function_exists( 'kaamase_tell_employer_job_held' ) ) {
	/**
	 * Tell the employer their job is being checked.
	 *
	 * The banner and the dashboard label both rely on somebody still
	 * having the site open. This does not. A person who posts at ten at
	 * night, closes the browser, and hears nothing has been given every
	 * reason to think the job was lost.
	 *
	 * Deliberately says nothing about why. The categories are for the
	 * person reviewing, and telling somebody their post matched a
	 * trafficking word list is either an insult or a hint, depending on
	 * who wrote it.
	 *
	 * @since 1.1.0
	 * @param int $post_id Job ID.
	 * @return void
	 */
	function kaamase_tell_employer_job_held( $post_id ) {

		$author = (int) get_post_field( 'post_author', $post_id );
		$user   = get_userdata( $author );

		if ( ! $user || ! $user->user_email ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Your job on %s is being checked', 'kaamase-core' ),
			get_bloginfo( 'name' )
		);

		$body = sprintf(
			/* translators: 1: display name, 2: job title, 3: dashboard URL */
			__(
				"Hello %1\$s,\n\nYour job \"%2\$s\" has been saved and is being checked before workers see it. A person reads the first job from every new employer. It is usually done within a few hours and you will get another email when it goes live.\n\nNothing more is needed from you. After this one, your jobs go live straight away.\n\nYou can see it here: %3\$s\n",
				'kaamase-core'
			),
			$user->display_name,
			get_the_title( $post_id ),
			kaamase_page_url( 'dashboard' )
		);

		wp_mail( $user->user_email, $subject, $body );
	}
}

if ( ! function_exists( 'kaamase_job_went_live' ) ) {
	/**
	 * Tell the employer when a held job is approved.
	 *
	 * Hooked on the status change rather than on the review screen, so
	 * it fires however the job was published: the review queue, a quick
	 * edit, or bulk actions.
	 *
	 * @since 1.1.0
	 * @param string  $new_status New status.
	 * @param string  $old_status Previous status.
	 * @param WP_Post $post       The job.
	 * @return void
	 */
	function kaamase_job_went_live( $new_status, $old_status, $post ) {

		if ( 'kaamase_job' !== $post->post_type ) {
			return;
		}

		if ( 'publish' !== $new_status || 'pending' !== $old_status ) {
			return;
		}

		$user = get_userdata( (int) $post->post_author );

		if ( ! $user || ! $user->user_email ) {
			return;
		}

		/*
		 * A held job is released by hand, from the admin, by somebody
		 * else. The employer waiting to hear is not in that request, so
		 * their language has to be read off their account.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $user->ID );

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Your job is live on %s', 'kaamase-core' ),
			get_bloginfo( 'name' )
		);

		$body = sprintf(
			/* translators: 1: display name, 2: job title, 3: job URL */
			__(
				"Hello %1\$s,\n\n\"%2\$s\" is live. Workers in your district can see it now, and it stays open for three weeks.\n\n%3\$s\n\nShare that link on WhatsApp if you want it seen faster. Your next jobs go live straight away.\n",
				'kaamase-core'
			),
			$user->display_name,
			get_the_title( $post->ID ),
			(string) get_permalink( $post->ID )
		);

		wp_mail( $user->user_email, $subject, $body );

		if ( $switched ) {
			kaamase_locale_restore();
		}
	}
}
add_action( 'transition_post_status', 'kaamase_job_went_live', 10, 3 );

if ( ! function_exists( 'kaamase_alert_serious_job' ) ) {
	/**
	 * Email somebody about a job that matched a serious list.
	 *
	 * Sent immediately rather than batched. The whole point of the
	 * serious lists is that these are the posts where hours matter.
	 *
	 * @since 1.1.0
	 * @param int   $post_id Job ID.
	 * @param array $reasons Reasons.
	 * @return void
	 */
	function kaamase_alert_serious_job( $post_id, $reasons ) {

		$groups = array();

		foreach ( $reasons as $reason ) {
			if ( 'serious' === ( $reason['severity'] ?? '' ) ) {
				$groups[] = (string) ( $reason['group'] ?? '' );
			}
		}

		$groups = array_values( array_unique( array_filter( $groups ) ) );

		if ( empty( $groups ) ) {
			return;
		}

		$to = (string) get_option( 'admin_email' );

		if ( ! $to ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'A job post on %s needs looking at now', 'kaamase-core' ),
			get_bloginfo( 'name' )
		);

		$body = sprintf(
			/* translators: 1: comma separated list of categories, 2: edit URL */
			__(
				"A job post has been held before going live.\n\nIt matched: %1\$s\n\nIt is not visible to anybody. Read it here:\n%2\$s\n\nIf it is what it looks like, do not simply delete it. Keep the account and the post, and report it.\n",
				'kaamase-core'
			),
			implode( ', ', $groups ),
			(string) get_edit_post_link( $post_id, 'raw' )
		);

		wp_mail( $to, $subject, $body );
	}
}


/* ==========================================================================
   4. SEEING IT IN THE ADMIN
   ========================================================================== */

if ( ! function_exists( 'kaamase_screen_job_column' ) ) {
	/**
	 * Add the review column to the jobs list.
	 *
	 * @since 1.1.0
	 * @param array $columns Existing columns.
	 * @return array
	 */
	function kaamase_screen_job_column( $columns ) {

		$columns['kaamase_screen'] = __( 'Held because', 'kaamase-core' );

		return $columns;
	}
}
add_filter( 'manage_kaamase_job_posts_columns', 'kaamase_screen_job_column' );

if ( ! function_exists( 'kaamase_screen_job_column_value' ) ) {
	/**
	 * Fill the review column.
	 *
	 * @since 1.1.0
	 * @param string $column  Column key.
	 * @param int    $post_id Job ID.
	 * @return void
	 */
	function kaamase_screen_job_column_value( $column, $post_id ) {

		if ( 'kaamase_screen' !== $column ) {
			return;
		}

		$reasons = get_post_meta( $post_id, KAAMASE_SCREEN_REASON_KEY, true );

		if ( empty( $reasons ) || ! is_array( $reasons ) ) {
			echo '&mdash;';

			return;
		}

		$labels = kaamase_screen_group_labels();

		foreach ( $reasons as $reason ) {

			$group   = (string) ( $reason['group'] ?? '' );
			$serious = 'serious' === ( $reason['severity'] ?? '' );
			$term    = (string) ( $reason['term'] ?? '' );

			printf(
				'<div%1$s>%2$s%3$s</div>',
				$serious ? ' style="color:#b32d2e;font-weight:600"' : '',
				esc_html( $labels[ $group ] ?? $group ),
				$term ? esc_html( sprintf( ' (%s)', $term ) ) : ''
			);
		}
	}
}
add_action( 'manage_kaamase_job_posts_custom_column', 'kaamase_screen_job_column_value', 10, 2 );

if ( ! function_exists( 'kaamase_screen_group_labels' ) ) {
	/**
	 * Readable names for the term groups.
	 *
	 * @since 1.1.0
	 * @return array<string,string>
	 */
	function kaamase_screen_group_labels() {

		return array(
			'trafficking'    => __( 'Possible trafficking or bonded labour', 'kaamase-core' ),
			'minors'         => __( 'Possibly involves someone under 18', 'kaamase-core' ),
			'sexual'         => __( 'Possible sexual services', 'kaamase-core' ),
			'advance_fee'    => __( 'Asks the worker for money', 'kaamase-core' ),
			'discrimination' => __( 'Excludes people by tribe, religion or origin', 'kaamase-core' ),
			'not_a_job'      => __( 'May not be a job', 'kaamase-core' ),
			'new_employer'   => __( 'First post from this employer', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_screen_edit_notice' ) ) {
	/**
	 * Explain on the job edit screen why it is being held.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	function kaamase_screen_edit_notice() {

		$screen = get_current_screen();

		if ( ! $screen instanceof WP_Screen || 'kaamase_job' !== $screen->id ) {
			return;
		}

		$post_id = isset( $GLOBALS['post'] ) ? (int) $GLOBALS['post']->ID : 0;

		if ( ! $post_id ) {
			return;
		}

		$reasons = get_post_meta( $post_id, KAAMASE_SCREEN_REASON_KEY, true );

		if ( empty( $reasons ) || ! is_array( $reasons ) ) {
			return;
		}

		$labels  = kaamase_screen_group_labels();
		$serious = false;

		echo '<div class="notice notice-warning"><p><strong>'
			. esc_html__( 'This job is held and is not visible to anybody.', 'kaamase-core' )
			. '</strong></p><ul style="list-style:disc;margin-left:20px">';

		foreach ( $reasons as $reason ) {

			if ( 'serious' === ( $reason['severity'] ?? '' ) ) {
				$serious = true;
			}

			$group = (string) ( $reason['group'] ?? '' );
			$term  = (string) ( $reason['term'] ?? '' );

			echo '<li>' . esc_html( $labels[ $group ] ?? $group );

			if ( $term ) {
				echo ' ' . esc_html( sprintf( '(matched: %s)', $term ) );
			}

			echo '</li>';
		}

		echo '</ul><p>'
			. esc_html__( 'Publishing it from here clears it, and this employer\'s later jobs will go live on their own.', 'kaamase-core' )
			. '</p>';

		if ( $serious ) {
			echo '<p><strong>'
				. esc_html__( 'One of these is serious. If the post is what it appears to be, do not just delete it. Keep the post and the account, and report it to the police.', 'kaamase-core' )
				. '</strong></p>';
		}

		echo '</div>';
	}
}
add_action( 'admin_notices', 'kaamase_screen_edit_notice' );