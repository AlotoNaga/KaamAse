<?php
/**
 * Privacy.
 *
 * Export, erasure, retention and anonymisation.
 *
 * Why this file is not optional
 * -----------------------------
 * This platform holds names, photographs, villages, phone numbers and
 * work histories of people who have no lawyer and no way to recover if
 * that data is misused. Under the Digital Personal Data Protection Act
 * the obligations here are legal ones, not good practice. But the real
 * reason is simpler: somebody registered on a promise, and this is where
 * the promise is kept.
 *
 * The rule that shapes everything below
 * -------------------------------------
 * Nothing is ever deleted silently or automatically without warning.
 *
 * An automated cleanup that quietly removes a worker's profile after a
 * quiet season is a person losing their listing without ever being told
 * why they stopped getting calls. Retention here always goes: warn,
 * wait, unpublish, wait again, then anonymise. At every step there is a
 * message and a way back.
 *
 * The one thing that is not deleted
 * ---------------------------------
 * Jobs. A worker's rating history hangs off the jobs they did. Deleting
 * an employer's jobs when they close their account would silently erase
 * part of every worker who ever worked for them. So jobs are anonymised
 * instead: the employer's identity goes, the record that work happened
 * stays.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. EXPORT

   Hooked into the WordPress personal data tools, so a request made
   through Tools, Export Personal Data returns everything this plugin
   holds. Building a separate export route would mean two things to keep
   correct, and one of them would drift.
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_exporter' ) ) {
	/**
	 * Register the data exporter.
	 *
	 * @since 1.0.0
	 * @param array $exporters Registered exporters.
	 * @return array Filtered exporters.
	 */
	function kaamase_register_exporter( $exporters ) {

		$exporters['kaamase-core'] = array(
			'exporter_friendly_name' => __( 'Kaam Ase profile and activity', 'kaamase-core' ),
			'callback'               => 'kaamase_export_personal_data',
		);

		return $exporters;
	}
}
add_filter( 'wp_privacy_personal_data_exporters', 'kaamase_register_exporter' );

if ( ! function_exists( 'kaamase_export_personal_data' ) ) {
	/**
	 * Collect everything this plugin holds about one person.
	 *
	 * @since 1.0.0
	 * @param string $email Email address of the person.
	 * @param int    $page  Page number, unused because the data is small.
	 * @return array Export payload.
	 */
	function kaamase_export_personal_data( $email, $page = 1 ) {

		unset( $page );

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$export = array();

		foreach ( kaamase_post_types() as $type ) {

			$posts = get_posts(
				array(
					'post_type'      => $type,
					'author'         => $user->ID,
					'posts_per_page' => -1,
					'post_status'    => 'any',
				)
			);

			foreach ( $posts as $post ) {

				$items  = array(
					array(
						'name'  => __( 'Title', 'kaamase-core' ),
						'value' => $post->post_title,
					),
					array(
						'name'  => __( 'Created', 'kaamase-core' ),
						'value' => $post->post_date,
					),
				);

				$schema = kaamase_field_schema();

				if ( isset( $schema[ $type ] ) ) {

					foreach ( array_keys( $schema[ $type ] ) as $key ) {

						$field = kaamase_get_field_definition( $type, $key );
						$value = kaamase_read_field( $post->ID, $key );

						if ( '' === $value || null === $value || array() === $value ) {
							continue;
						}

						$items[] = array(
							'name'  => $field['label'] ? $field['label'] : $key,
							'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ),
						);
					}
				}

				$export[] = array(
					'group_id'    => 'kaamase-' . $type,
					'group_label' => get_post_type_object( $type )->labels->singular_name,
					'item_id'     => $type . '-' . $post->ID,
					'data'        => $items,
				);
			}
		}

		/* ---- Ratings this person wrote ---- */

		$written = get_comments(
			array(
				'user_id' => $user->ID,
				'type'    => 'kaamase_rating',
				'status'  => 'any',
			)
		);

		foreach ( $written as $comment ) {

			$export[] = array(
				'group_id'    => 'kaamase-ratings-given',
				'group_label' => __( 'Ratings you gave', 'kaamase-core' ),
				'item_id'     => 'rating-' . $comment->comment_ID,
				'data'        => array(
					array(
						'name'  => __( 'About', 'kaamase-core' ),
						'value' => get_the_title( $comment->comment_post_ID ),
					),
					array(
						'name'  => __( 'Date', 'kaamase-core' ),
						'value' => $comment->comment_date,
					),
					array(
						'name'  => __( 'Your comment', 'kaamase-core' ),
						'value' => $comment->comment_content,
					),
					array(
						'name'  => __( 'Score', 'kaamase-core' ),
						'value' => get_comment_meta( $comment->comment_ID, 'kaamase_score', true ),
					),
				),
			);
		}

		/* ---- Who looked up their contact details ---- */

		$profile = (int) get_user_meta( $user->ID, 'kaamase_profile_id', true );

		if ( $profile ) {

			foreach ( kaamase_contact_log( $profile, 200 ) as $index => $entry ) {

				$who = get_userdata( $entry['user'] );

				$export[] = array(
					'group_id'    => 'kaamase-lookups',
					'group_label' => __( 'People who looked up your contact details', 'kaamase-core' ),
					'item_id'     => 'lookup-' . $index,
					'data'        => array(
						array(
							'name'  => __( 'Who', 'kaamase-core' ),
							'value' => $who ? $who->display_name : __( 'Removed account', 'kaamase-core' ),
						),
						array(
							'name'  => __( 'When', 'kaamase-core' ),
							'value' => gmdate( 'Y-m-d H:i', (int) $entry['time'] ),
						),
					),
				);
			}
		}

		return array(
			'data' => $export,
			'done' => true,
		);
	}
}


/* ==========================================================================
   2. ERASURE
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_eraser' ) ) {
	/**
	 * Register the data eraser.
	 *
	 * @since 1.0.0
	 * @param array $erasers Registered erasers.
	 * @return array Filtered erasers.
	 */
	function kaamase_register_eraser( $erasers ) {

		$erasers['kaamase-core'] = array(
			'eraser_friendly_name' => __( 'Kaam Ase profile and activity', 'kaamase-core' ),
			'callback'             => 'kaamase_erase_personal_data',
		);

		return $erasers;
	}
}
add_filter( 'wp_privacy_personal_data_erasers', 'kaamase_register_eraser' );

if ( ! function_exists( 'kaamase_erase_personal_data' ) ) {
	/**
	 * Remove everything personal, keeping what history requires.
	 *
	 * Profiles and photographs go. Jobs are anonymised rather than
	 * deleted, for the reason at the top of this file. Ratings the person
	 * wrote lose their name but keep their content, because a rating is
	 * partly a record about somebody else and deleting it would rewrite
	 * that person's history without their say.
	 *
	 * @since 1.0.0
	 * @param string $email Email address.
	 * @param int    $page  Page number, unused.
	 * @return array Erasure result.
	 */
	function kaamase_erase_personal_data( $email, $page = 1 ) {

		unset( $page );

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$removed  = false;
		$retained = false;
		$messages = array();

		/* ---- What they looked at: deleted outright ---- */

		/*
		 * Done first, before the profiles go.
		 *
		 * Two different things are being removed and only one of them is
		 * theirs to have kept: the views OF their profile disappear with
		 * the profile a moment from now, but the record of what THEY
		 * looked at sits on other people's profiles and would survive
		 * them entirely. A log of somebody's browsing that outlives their
		 * account is exactly the thing an erasure request is asking to be
		 * rid of.
		 *
		 * Other people's counts fall by whatever this person contributed.
		 * That is correct. Those views were theirs.
		 */
		if ( function_exists( 'kaamase_views_forget_viewer' ) ) {

			kaamase_views_forget_viewer( $user->ID );
			$removed = true;
		}

		/* ---- Profiles and teams: deleted outright ---- */

		foreach ( array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' ) as $type ) {

			$posts = get_posts(
				array(
					'post_type'      => $type,
					'author'         => $user->ID,
					'posts_per_page' => -1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);

			foreach ( $posts as $post_id ) {

				kaamase_delete_attached_media( $post_id );
				wp_delete_post( $post_id, true );
				$removed = true;
			}
		}

		/* ---- Jobs: anonymised ---- */

		$jobs = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'author'         => $user->ID,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		foreach ( $jobs as $job_id ) {

			kaamase_anonymise_job( $job_id );
			$retained = true;
		}

		if ( ! empty( $jobs ) ) {
			$messages[] = __( 'Jobs you posted were kept but your name and number were removed, because workers who did those jobs have ratings attached to them.', 'kaamase-core' );
		}

		/* ---- Ratings written: name removed, content kept ---- */

		$written = get_comments(
			array(
				'user_id' => $user->ID,
				'type'    => 'kaamase_rating',
				'status'  => 'any',
			)
		);

		foreach ( $written as $comment ) {

			wp_update_comment(
				array(
					'comment_ID'           => $comment->comment_ID,
					'comment_author'       => __( 'Removed account', 'kaamase-core' ),
					'comment_author_email' => '',
					'comment_author_IP'    => '',
					'user_id'              => 0,
				)
			);

			$retained = true;
		}

		if ( ! empty( $written ) ) {
			$messages[] = __( 'Ratings you gave were kept without your name, because they are part of somebody else\'s record.', 'kaamase-core' );
		}

		/* ---- Contact logs mentioning this person ---- */

		kaamase_scrub_contact_logs( $user->ID );

		/* ---- Account meta ---- */

		foreach ( array( 'kaamase_profile_id', 'kaamase_verified_at', 'kaamase_verify_hash', 'kaamase_verify_expires', 'kaamase_registered_at', 'kaamase_blocked' ) as $key ) {
			delete_user_meta( $user->ID, $key );
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => $messages,
			'done'           => true,
		);
	}
}

if ( ! function_exists( 'kaamase_anonymise_job' ) ) {
	/**
	 * Strip an employer's identity from a job while keeping the record.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return void
	 */
	function kaamase_anonymise_job( $job_id ) {

		kaamase_save_field( $job_id, 'employer_name', __( 'Removed account', 'kaamase-core' ) );
		kaamase_save_field( $job_id, 'contact_phone', '' );
		kaamase_save_field( $job_id, 'employer_id', 0 );

		wp_update_post(
			array(
				'ID'          => $job_id,
				'post_author' => 0,
				'post_status' => 'kaamase_closed',
			)
		);

		kaamase_delete_attached_media( $job_id );
	}
}

if ( ! function_exists( 'kaamase_delete_attached_media' ) ) {
	/**
	 * Delete the photographs belonging to a post.
	 *
	 * Deleting a profile without this leaves the person's photograph
	 * sitting in the uploads folder at a guessable URL forever, which is
	 * the most common way an erasure quietly fails to erase anything that
	 * matters.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	function kaamase_delete_attached_media( $post_id ) {

		$attachments = get_attached_media( '', $post_id );

		foreach ( $attachments as $attachment ) {
			wp_delete_attachment( $attachment->ID, true );
		}

		$thumbnail = get_post_thumbnail_id( $post_id );

		if ( $thumbnail ) {
			wp_delete_attachment( $thumbnail, true );
		}
	}
}

if ( ! function_exists( 'kaamase_scrub_contact_logs' ) ) {
	/**
	 * Remove a user from every contact log on the platform.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_scrub_contact_logs( $user_id ) {

		global $wpdb;

		$post_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
				KAAMASE_META_PREFIX . 'contact_log'
			)
		);

		foreach ( (array) $post_ids as $post_id ) {

			$log = kaamase_meta_array( get_post_meta( $post_id, KAAMASE_META_PREFIX . 'contact_log', true ) );

			$filtered = array_values(
				array_filter(
					$log,
					static function ( $entry ) use ( $user_id ) {
						return absint( $entry['user'] ) !== absint( $user_id );
					}
				)
			);

			if ( count( $filtered ) !== count( $log ) ) {
				update_post_meta( $post_id, KAAMASE_META_PREFIX . 'contact_log', $filtered );
			}
		}
	}
}


/* ==========================================================================
   3. RETENTION

   Warn, wait, unpublish, wait, anonymise. Never a silent deletion.
   ========================================================================== */

if ( ! function_exists( 'kaamase_retention_stages' ) ) {
	/**
	 * How long each stage lasts, in days since last activity.
	 *
	 * Generous on purpose. Construction work in Nagaland is seasonal,
	 * and a mason with no work through a long monsoon is not an
	 * abandoned account.
	 *
	 * @since 1.0.0
	 * @return int[] Days keyed by stage.
	 */
	function kaamase_retention_stages() {

		/**
		 * Filter the retention timetable.
		 *
		 * @since 1.0.0
		 * @param int[] $stages Days for each stage.
		 */
		return (array) apply_filters(
			'kaamase_retention_stages',
			array(
				'warn'      => 540,  // Around 18 months. An email, nothing else.
				'unpublish' => 600,  // Hidden from listings. Still recoverable by signing in.
				'anonymise' => 730,  // Two years. Personal details removed.
			)
		);
	}
}

if ( ! function_exists( 'kaamase_touch_activity' ) ) {
	/**
	 * Record that an account is in use.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_touch_activity() {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		$last    = (int) get_user_meta( $user_id, 'kaamase_last_active', true );

		// Once a day is enough. No reason to write on every page load.
		if ( $last && ( time() - $last ) < DAY_IN_SECONDS ) {
			return;
		}

		update_user_meta( $user_id, 'kaamase_last_active', time() );
	}
}
add_action( 'wp_loaded', 'kaamase_touch_activity' );

if ( ! function_exists( 'kaamase_run_retention' ) ) {
	/**
	 * Move dormant accounts through the retention stages.
	 *
	 * Batched at fifty a day. Slow on purpose: a bug in a retention
	 * routine that processes everything at once is a bug that takes the
	 * whole platform's data with it before anybody notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_run_retention() {

		$stages = kaamase_retention_stages();

		$users = get_users(
			array(
				'role__in'   => array( 'kaamase_worker', 'kaamase_employer' ),
				'number'     => 50,
				'fields'     => array( 'ID' ),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => 'kaamase_last_active',
						'value'   => time() - ( $stages['warn'] * DAY_IN_SECONDS ),
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'kaamase_retention_done',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		foreach ( $users as $row ) {

			$user_id = (int) $row->ID;
			$last    = (int) get_user_meta( $user_id, 'kaamase_last_active', true );
			$days    = (int) floor( ( time() - $last ) / DAY_IN_SECONDS );
			$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

			if ( $days >= $stages['anonymise'] ) {

				$user = get_userdata( $user_id );

				if ( $user ) {
					kaamase_erase_personal_data( $user->user_email );
				}

				update_user_meta( $user_id, 'kaamase_retention_done', time() );
				continue;
			}

			if ( $days >= $stages['unpublish'] ) {

				if ( $profile && 'publish' === get_post_status( $profile ) ) {

					wp_update_post(
						array(
							'ID'          => $profile,
							'post_status' => 'draft',
						)
					);

					kaamase_send_retention_notice( $user_id, 'unpublished' );
				}

				continue;
			}

			if ( ! get_user_meta( $user_id, 'kaamase_retention_warned', true ) ) {
				kaamase_send_retention_notice( $user_id, 'warning' );
				update_user_meta( $user_id, 'kaamase_retention_warned', time() );
			}
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_run_retention' );

if ( ! function_exists( 'kaamase_send_retention_notice' ) ) {
	/**
	 * Tell somebody their account is going quiet.
	 *
	 * @since 1.0.0
	 * @param int    $user_id User ID.
	 * @param string $stage   warning or unpublished.
	 * @return void
	 */
	function kaamase_send_retention_notice( $user_id, $stage ) {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return;
		}

		$site = get_bloginfo( 'name', 'display' );

		if ( 'unpublished' === $stage ) {

			$subject = sprintf(
				/* translators: %s: site name */
				__( 'Your %s profile is hidden for now', 'kaamase-core' ),
				$site
			);

			$body = implode(
				"\n\n",
				array(
					sprintf(
						/* translators: %s: person's name */
						__( 'Hello %s,', 'kaamase-core' ),
						$user->display_name
					),
					__( 'You have not used your account for a long time, so we have hidden your profile from searches. Nothing is deleted.', 'kaamase-core' ),
					__( 'Sign in and it comes straight back.', 'kaamase-core' ),
					kaamase_page_url( 'dashboard' ),
				)
			);

		} else {

			$subject = sprintf(
				/* translators: %s: site name */
				__( 'Are you still looking for work on %s?', 'kaamase-core' ),
				$site
			);

			$body = implode(
				"\n\n",
				array(
					sprintf(
						/* translators: %s: person's name */
						__( 'Hello %s,', 'kaamase-core' ),
						$user->display_name
					),
					__( 'We have not seen you for a while. If you are still available, sign in and set yourself available so employers can find you.', 'kaamase-core' ),
					__( 'If you do nothing, we will hide your profile in a couple of months and remove your details after two years. You can come back at any time before then.', 'kaamase-core' ),
					kaamase_page_url( 'dashboard' ),
				)
			);
		}

		wp_mail( $user->user_email, $subject, $body );
	}
}

/**
 * Bring a profile back when its owner returns.
 *
 * @since 1.0.0
 * @param string  $login Username.
 * @param WP_User $user  The user.
 * @return void
 */
function kaamase_restore_on_return( $login, $user ) {

	unset( $login );

	delete_user_meta( $user->ID, 'kaamase_retention_warned' );
	update_user_meta( $user->ID, 'kaamase_last_active', time() );

	$profile = (int) get_user_meta( $user->ID, 'kaamase_profile_id', true );

	if ( ! $profile || 'draft' !== get_post_status( $profile ) ) {
		return;
	}

	// Only restore a profile that was hidden for dormancy, not one waiting on verification.
	if ( ! kaamase_user_is_verified( $user->ID ) ) {
		return;
	}

	wp_update_post(
		array(
			'ID'          => $profile,
			'post_status' => 'publish',
		)
	);
}
add_action( 'wp_login', 'kaamase_restore_on_return', 10, 2 );


/* ==========================================================================
   4. LOG TRIMMING
   ========================================================================== */

if ( ! function_exists( 'kaamase_trim_contact_logs' ) ) {
	/**
	 * Drop contact log entries older than a year.
	 *
	 * Data you no longer need is data you can still lose. A record of who
	 * called whom in 2026 is useful for six months and a liability after
	 * that.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_trim_contact_logs() {

		global $wpdb;

		$cutoff = time() - YEAR_IN_SECONDS;

		$post_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 200",
				KAAMASE_META_PREFIX . 'contact_log'
			)
		);

		foreach ( (array) $post_ids as $post_id ) {

			$log = kaamase_meta_array( get_post_meta( $post_id, KAAMASE_META_PREFIX . 'contact_log', true ) );

			$kept = array_values(
				array_filter(
					$log,
					static function ( $entry ) use ( $cutoff ) {
						return absint( $entry['time'] ) > $cutoff;
					}
				)
			);

			if ( count( $kept ) !== count( $log ) ) {
				update_post_meta( $post_id, KAAMASE_META_PREFIX . 'contact_log', $kept );
			}
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_trim_contact_logs' );


/* ==========================================================================
   5. CONSENT RECORD
   ========================================================================== */

/**
 * Record what somebody agreed to, and when.
 *
 * Under the DPDP Act consent has to be demonstrable. A tick box with no
 * record behind it is not. This stores the moment and the version of the
 * terms, so that a change to the wording later does not retroactively
 * rewrite what a person actually agreed to.
 *
 * @since 1.0.0
 * @param int $user_id User ID.
 * @return void
 */
function kaamase_record_consent( $user_id ) {

	update_user_meta(
		$user_id,
		'kaamase_consent',
		array(
			'time'    => time(),
			'terms'   => (int) get_option( 'kaamase_terms_version', 1 ),
			'privacy' => (int) get_option( 'kaamase_privacy_version', 1 ),
		)
	);
}
add_action( 'kaamase_account_created', 'kaamase_record_consent' );

/**
 * Declare what this plugin stores, for the WordPress privacy guide.
 *
 * Appears under Tools, Privacy in the admin. Written for a lawyer to
 * work from rather than to be pasted onto the site as it stands.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_privacy_declaration() {

	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = '<p>' . esc_html__( 'Kaam Ase stores the following about registered users: name, email address, phone number, district, town, trade, years of experience, expected rates, availability, profile photograph, photographs of past work, who vouched for them, ratings given and received, records of who looked up their contact details, and the date they agreed to the terms.', 'kaamase-core' ) . '</p>'
		. '<p>' . esc_html__( 'Phone numbers are never shown publicly. They are released one at a time to signed in, verified accounts, and every release is recorded and shown to the person it belongs to.', 'kaamase-core' ) . '</p>'
		. '<p>' . esc_html__( 'Camera metadata, including the location where a photograph was taken, is stripped from every upload before it is stored.', 'kaamase-core' ) . '</p>'
		. '<p>' . esc_html__( 'Accounts that go unused are warned by email at around eighteen months, hidden from searches at twenty months, and have their personal details removed at two years. Signing in at any point stops this and restores the profile.', 'kaamase-core' ) . '</p>';

	wp_add_privacy_policy_content( __( 'Kaam Ase Core', 'kaamase-core' ), wp_kses_post( $content ) );
}
add_action( 'admin_init', 'kaamase_privacy_declaration' );
