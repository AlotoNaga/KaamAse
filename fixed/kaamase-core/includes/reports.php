<?php
/**
 * Reports and grievances.
 *
 * The two forms the footer and the Safety page already promise.
 *
 * Four rules this file is built on
 * --------------------------------
 *
 * 1. A report form must never be a black hole. The worst thing this
 *    platform could do is take somebody's complaint that they were
 *    cheated out of a week's wages and then say nothing. Every
 *    submission gets a reference number on screen, a stated timeframe,
 *    and a real reply. If you cannot keep that promise, change the
 *    wording in this file before launch rather than leaving it and
 *    breaking it.
 *
 * 2. You do not have to be signed in. A worker who has been harassed may
 *    be on somebody else's phone, may have deleted their account, or may
 *    not want their name on it. Requiring a login suppresses exactly the
 *    reports you most need to see. Anonymous reports are rate limited
 *    instead.
 *
 * 3. This is not an emergency service. A form on a hiring website is the
 *    wrong channel for somebody in danger right now, and pretending
 *    otherwise could cost somebody real time. The emergency numbers sit
 *    at the top of the form, before any field.
 *
 * 4. Reports are private, always. A complaint about a named person is
 *    never published, never shown on their profile, and never visible to
 *    them. It is an allegation until somebody looks into it, and a
 *    platform that publishes allegations becomes a weapon.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. STORAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_report_type' ) ) {
	/**
	 * Register the reports post type.
	 *
	 * Completely private. Not public, not queryable, excluded from
	 * search, no archive. It exists so reports land in the admin where
	 * they can be worked through, and nowhere else.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_register_report_type() {

		register_post_type(
			'kaamase_report',
			array(
				'labels'              => array(
					'name'               => _x( 'Reports', 'post type general name', 'kaamase-core' ),
					'singular_name'      => _x( 'Report', 'post type singular name', 'kaamase-core' ),
					'edit_item'          => __( 'Report', 'kaamase-core' ),
					'search_items'       => __( 'Search reports', 'kaamase-core' ),
					'not_found'          => __( 'No reports. Good.', 'kaamase-core' ),
					'not_found_in_trash' => __( 'No reports in trash.', 'kaamase-core' ),
					'all_items'          => __( 'Reports', 'kaamase-core' ),
					'menu_name'          => __( 'Reports', 'kaamase-core' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => 'kaamase',
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
			)
		);

		register_post_status(
			'kaamase_open_case',
			array(
				'label'                     => _x( 'Open', 'report status', 'kaamase-core' ),
				'public'                    => false,
				'internal'                  => false,
				'protected'                 => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of open reports */
				'label_count'               => _n_noop(
					'Open <span class="count">(%s)</span>',
					'Open <span class="count">(%s)</span>',
					'kaamase-core'
				),
			)
		);
	}
}
add_action( 'init', 'kaamase_register_report_type', 12 );


/* ==========================================================================
   2. EMERGENCY NUMBERS
   ========================================================================== */

if ( ! function_exists( 'kaamase_emergency_numbers' ) ) {
	/**
	 * Numbers shown at the top of the report forms.
	 *
	 * These are the national ones. Add your local police station and
	 * anything else that is actually answered in Nagaland through the
	 * filter below, and check every number by dialling it before launch.
	 * A helpline printed here that rings out is worse than no helpline.
	 *
	 * @since 1.0.0
	 * @return array[] Numbers with label and number.
	 */
	function kaamase_emergency_numbers() {

		/**
		 * Filter the emergency numbers.
		 *
		 * @since 1.0.0
		 * @param array[] $numbers Emergency contacts.
		 */
		return (array) apply_filters(
			'kaamase_emergency_numbers',
			array(
				array(
					'label'  => __( 'Police and emergency', 'kaamase-core' ),
					'number' => '112',
				),
				array(
					'label'  => __( 'Women helpline', 'kaamase-core' ),
					'number' => '181',
				),
			)
		);
	}
}

if ( ! function_exists( 'kaamase_emergency_notice' ) ) {
	/**
	 * The block that sits above every report form.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_emergency_notice() {

		ob_start();
		?>
		<div class="ka-notice ka-notice--error">
			<div>
				<span class="ka-notice__title">
					<?php esc_html_e( 'If you are in danger right now, do not use this form', 'kaamase-core' ); ?>
				</span>

				<p>
					<?php esc_html_e( 'This is a website. Nobody reads it at night. Call one of these instead.', 'kaamase-core' ); ?>
				</p>

				<ul class="ka-cluster ka-mt-4">
					<?php foreach ( kaamase_emergency_numbers() as $contact ) : ?>
						<li>
							<a class="ka-btn ka-btn--danger ka-btn--sm" href="tel:<?php echo esc_attr( $contact['number'] ); ?>">
								<?php
								printf(
									'%1$s %2$s',
									esc_html( $contact['label'] ),
									esc_html( $contact['number'] )
								);
								?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   3. REPORT A PROBLEM
   ========================================================================== */

if ( ! function_exists( 'kaamase_report_kinds' ) ) {
	/**
	 * What somebody can report.
	 *
	 * The order matters. Money and safety first, because those are what
	 * people are actually reporting, and burying them under Something
	 * else on the site means somebody picks the wrong one and it lands in
	 * the wrong queue.
	 *
	 * @since 1.0.0
	 * @return array[] Kinds with label and urgency.
	 */
	function kaamase_report_kinds() {

		return array(
			'asked_for_money' => array(
				'label'  => __( 'Somebody asked a worker for money to get a job', 'kaamase-core' ),
				'urgent' => true,
			),
			'unsafe' => array(
				'label'  => __( 'Somebody behaved badly, threatened or harassed', 'kaamase-core' ),
				'urgent' => true,
			),
			'fake' => array(
				'label'  => __( 'A fake profile, or somebody pretending to be someone else', 'kaamase-core' ),
				'urgent' => true,
			),
			'not_paid' => array(
				'label'  => __( 'Work was done and not paid for', 'kaamase-core' ),
				'urgent' => false,
			),
			'no_show' => array(
				'label'  => __( 'Somebody agreed to a job and did not turn up', 'kaamase-core' ),
				'urgent' => false,
			),
			'wrong_info' => array(
				'label'  => __( 'A listing has wrong or misleading information', 'kaamase-core' ),
				'urgent' => false,
			),
			'site_problem' => array(
				'label'  => __( 'Something on the website is broken', 'kaamase-core' ),
				'urgent' => false,
			),
			'other' => array(
				'label'  => __( 'Something else', 'kaamase-core' ),
				'urgent' => false,
			),
		);
	}
}

if ( ! function_exists( 'kaamase_report_form_shortcode' ) ) {
	/**
	 * Render the general report form.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_report_form_shortcode() {
		return kaamase_render_report_form( 'report' );
	}
}
add_shortcode( 'kaamase_report_form', 'kaamase_report_form_shortcode' );

if ( ! function_exists( 'kaamase_grievance_form_shortcode' ) ) {
	/**
	 * Render the wage complaint form.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_grievance_form_shortcode() {
		return kaamase_render_report_form( 'grievance' );
	}
}
add_shortcode( 'kaamase_grievance_form', 'kaamase_grievance_form_shortcode' );

if ( ! function_exists( 'kaamase_render_report_form' ) ) {
	/**
	 * Render either report form.
	 *
	 * @since 1.0.0
	 * @param string $mode report or grievance.
	 * @return string Markup.
	 */
	function kaamase_render_report_form( $mode ) {

		$wage   = ( 'grievance' === $mode );
		$errors = kaamase_report_errors();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$done = isset( $_GET['reported'] ) ? sanitize_text_field( wp_unslash( $_GET['reported'] ) ) : '';

		/*
		 * Checked before the buffer opens, not after.
		 *
		 * Returning out of an open output buffer leaves it open for the
		 * rest of the request. Everything rendered afterwards lands in
		 * the wrong buffer, and the next ob_get_clean anywhere on the
		 * page collects somebody else's markup. On the confirmation
		 * screen for a wage complaint, of all places.
		 */
		if ( $done ) {
			return kaamase_report_thanks( $done );
		}

		ob_start();

		echo kaamase_emergency_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $errors ) ) {
			echo '<div class="ka-notice ka-notice--error"><div><span class="ka-notice__title">'
				. esc_html__( 'Please fix this', 'kaamase-core' )
				. '</span><ul>';

			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}

			echo '</ul></div></div>';
		}
		?>

		<form class="ka-form ka-stack--lg" method="post" action="">

			<?php wp_nonce_field( 'kaamase_report', 'kaamase_report_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="submit_report">
			<input type="hidden" name="kaamase_mode" value="<?php echo esc_attr( $mode ); ?>">
			<input type="hidden" name="kaamase_started" value="<?php echo esc_attr( time() ); ?>">

			<div style="position:absolute;left:-9999px;" aria-hidden="true">
				<label for="ka-r-website"><?php esc_html_e( 'Leave this empty', 'kaamase-core' ); ?></label>
				<input type="text" id="ka-r-website" name="website" tabindex="-1" autocomplete="off" value="">
			</div>

			<h2>
				<?php
				echo $wage
					? esc_html__( 'Wage or job complaint', 'kaamase-core' )
					: esc_html__( 'Report a problem', 'kaamase-core' );
				?>
			</h2>

			<p class="ka-soft">
				<?php
				echo $wage
					? esc_html__( 'If you were not paid, or paid less than agreed, tell us here. We will look into it and speak to the other side. What you write is private.', 'kaamase-core' )
					: esc_html__( 'Tell us what happened. What you write here is private. It is never shown on anybody\'s profile and the person you are reporting is not told who reported them.', 'kaamase-core' );
				?>
			</p>

			<?php if ( ! $wage ) : ?>
				<div class="ka-field">
					<label class="ka-label" for="ka-report-kind">
						<?php esc_html_e( 'What is it about', 'kaamase-core' ); ?>
						<span class="ka-label__req">*</span>
					</label>
					<select class="ka-select" id="ka-report-kind" name="kaamase_kind" required>
						<option value=""><?php esc_html_e( 'Choose one', 'kaamase-core' ); ?></option>
						<?php foreach ( kaamase_report_kinds() as $key => $kind ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $kind['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<div class="ka-field">
				<label class="ka-label" for="ka-report-who">
					<?php
					echo $wage
						? esc_html__( 'Who was supposed to pay you', 'kaamase-core' )
						: esc_html__( 'Who is this about', 'kaamase-core' );
					?>
				</label>
				<input class="ka-input" type="text" id="ka-report-who" name="kaamase_about_who" maxlength="120"
					placeholder="<?php esc_attr_e( 'Their name, or the link to their profile', 'kaamase-core' ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Leave empty if it is not about a person.', 'kaamase-core' ); ?>
				</p>
			</div>

			<?php if ( $wage ) : ?>

				<div class="ka-field">
					<label class="ka-label" for="ka-report-amount">
						<?php esc_html_e( 'How much are you owed', 'kaamase-core' ); ?>
					</label>
					<input class="ka-input" type="number" id="ka-report-amount" name="kaamase_amount" min="0" step="1"
						inputmode="numeric"
						placeholder="<?php esc_attr_e( 'Amount in rupees', 'kaamase-core' ); ?>">
				</div>

				<div class="ka-field">
					<label class="ka-label" for="ka-report-when">
						<?php esc_html_e( 'When did you do the work', 'kaamase-core' ); ?>
					</label>
					<input class="ka-input" type="text" id="ka-report-when" name="kaamase_when" maxlength="80"
						placeholder="<?php esc_attr_e( 'Last week, three days in March, and so on', 'kaamase-core' ); ?>">
				</div>

				<div class="ka-field">
					<label class="ka-label" for="ka-report-days">
						<?php esc_html_e( 'How many days did you work', 'kaamase-core' ); ?>
					</label>
					<input class="ka-input" type="number" id="ka-report-days" name="kaamase_days" min="0" max="365"
						inputmode="numeric">
				</div>

			<?php endif; ?>

			<div class="ka-field">
				<label class="ka-label" for="ka-report-what">
					<?php esc_html_e( 'What happened', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<textarea class="ka-textarea" id="ka-report-what" name="kaamase_details" required maxlength="2000"
					style="min-height:180px;"
					placeholder="<?php esc_attr_e( 'Tell it in your own words. Dates and places help.', 'kaamase-core' ); ?>"></textarea>
			</div>

			<?php
			/* ----------------------------------------------------------
			 * Contact.
			 *
			 * Optional, and the hint says plainly what happens if it is
			 * left blank. Somebody who wants to report anonymously should
			 * be able to, and should also understand the cost of that.
			 * -------------------------------------------------------- */
			?>
			<fieldset class="ka-field">
				<legend class="ka-label"><?php esc_html_e( 'How can we reach you', 'kaamase-core' ); ?></legend>

				<?php if ( is_user_logged_in() ) : ?>
					<p class="ka-small ka-soft ka-mb-4">
						<?php
						printf(
							/* translators: %s: signed in person's name */
							esc_html__( 'You are signed in as %s, so we already have your details. They are not shared with the person you are reporting.', 'kaamase-core' ),
							esc_html( wp_get_current_user()->display_name )
						);
						?>
					</p>
				<?php else : ?>

					<div class="ka-field">
						<label class="ka-label" for="ka-report-name"><?php esc_html_e( 'Your name', 'kaamase-core' ); ?></label>
						<input class="ka-input" type="text" id="ka-report-name" name="kaamase_reporter_name" maxlength="90">
					</div>

					<div class="ka-field">
						<label class="ka-label" for="ka-report-phone"><?php esc_html_e( 'Your phone number', 'kaamase-core' ); ?></label>
						<input class="ka-input" type="tel" id="ka-report-phone" name="kaamase_reporter_phone"
							inputmode="numeric" maxlength="15">
					</div>

					<p class="ka-hint">
						<?php esc_html_e( 'You can leave both empty and send this without your name. We will still read it and act on it, but we will not be able to tell you what happened, and we cannot chase a wage complaint without being able to reach you.', 'kaamase-core' ); ?>
					</p>

				<?php endif; ?>
			</fieldset>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
				<?php esc_html_e( 'Send this', 'kaamase-core' ); ?>
			</button>

			<p class="ka-small ka-mute">
				<?php esc_html_e( 'We read everything within two working days. Nothing you write here appears anywhere on the website.', 'kaamase-core' ); ?>
			</p>

		</form>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   4. SAVING
   ========================================================================== */

if ( ! function_exists( 'kaamase_handle_report' ) ) {
	/**
	 * Save a report.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_report() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'submit_report' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			empty( $_POST['kaamase_report_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_report_nonce'] ) ), 'kaamase_report' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$mode = isset( $_POST['kaamase_mode'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_mode'] ) ) : 'report';
		$wage = ( 'grievance' === $mode );
		$page = $wage ? 'grievance' : 'report';

		/* ---- Bot checks ---- */

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['website'] ) ) {
			wp_safe_redirect( kaamase_page_url( $page ) );
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$started = isset( $_POST['kaamase_started'] ) ? absint( $_POST['kaamase_started'] ) : 0;

		if ( ! $started || ( time() - $started ) < 5 ) {
			kaamase_report_fail( array( __( 'That was too quick. Please try again.', 'kaamase-core' ) ), $page );
		}

		/*
		 * Rate limited, but generously. Somebody in a genuine dispute may
		 * well send two or three as they remember more. Blocking that
		 * would be worse than the spam it prevents.
		 */
		if ( ! kaamase_report_rate_ok() ) {
			kaamase_report_fail(
				array( __( 'You have sent several already. If it is urgent, call us instead.', 'kaamase-core' ) ),
				$page
			);
		}

		/* ---- Collect ---- */

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$kind      = isset( $_POST['kaamase_kind'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_kind'] ) ) : '';
		$about_who = isset( $_POST['kaamase_about_who'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_about_who'] ) ) : '';
		$details   = isset( $_POST['kaamase_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kaamase_details'] ) ) : '';
		$amount    = isset( $_POST['kaamase_amount'] ) ? absint( $_POST['kaamase_amount'] ) : 0;
		$when      = isset( $_POST['kaamase_when'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_when'] ) ) : '';
		$days      = isset( $_POST['kaamase_days'] ) ? absint( $_POST['kaamase_days'] ) : 0;
		$r_name    = isset( $_POST['kaamase_reporter_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_reporter_name'] ) ) : '';
		$r_phone   = isset( $_POST['kaamase_reporter_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_reporter_phone'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$details = mb_substr( $details, 0, 2000 );

		/* ---- Validate ---- */

		$errors = array();

		if ( ! $wage && '' === $kind ) {
			$errors[] = __( 'Choose what the report is about.', 'kaamase-core' );
		}

		if ( mb_strlen( trim( $details ) ) < 20 ) {
			$errors[] = __( 'Please tell us a bit more about what happened, so we know where to start.', 'kaamase-core' );
		}

		if ( ! empty( $errors ) ) {
			kaamase_report_fail( $errors, $page );
		}

		/* ---- Who is reporting ---- */

		if ( is_user_logged_in() ) {

			$user    = wp_get_current_user();
			$r_name  = $user->display_name;
			$r_email = $user->user_email;
			$r_id    = $user->ID;

			$profile = (int) get_user_meta( $user->ID, 'kaamase_profile_id', true );
			$r_phone = $profile ? (string) kaamase_read_field( $profile, 'phone' ) : '';

		} else {

			$r_email = '';
			$r_id    = 0;
			$r_phone = kaamase_sanitize_phone( $r_phone );
		}

		/* ---- Save ---- */

		$kinds  = kaamase_report_kinds();
		$urgent = $wage || ( isset( $kinds[ $kind ] ) && $kinds[ $kind ]['urgent'] );

		$reference = strtoupper( wp_generate_password( 6, false, false ) );

		$title = $wage
			/* translators: %s: reference code */
			? sprintf( __( 'Wage complaint %s', 'kaamase-core' ), $reference )
			: sprintf(
				/* translators: 1: kind of report, 2: reference code */
				__( '%1$s %2$s', 'kaamase-core' ),
				isset( $kinds[ $kind ] ) ? $kinds[ $kind ]['label'] : __( 'Report', 'kaamase-core' ),
				$reference
			);

		$report_id = wp_insert_post(
			array(
				'post_type'    => 'kaamase_report',
				'post_title'   => $title,
				'post_content' => $details,
				'post_status'  => 'kaamase_open_case',
				'post_author'  => 0,
			),
			true
		);

		if ( is_wp_error( $report_id ) ) {
			kaamase_report_fail(
				array( __( 'Something went wrong sending that. Please try again, or call us.', 'kaamase-core' ) ),
				$page
			);
		}

		$meta = array(
			'reference'      => $reference,
			'mode'           => $mode,
			'kind'           => $wage ? 'not_paid' : $kind,
			'urgent'         => $urgent ? 1 : 0,
			'about_who'      => $about_who,
			'amount'         => $amount,
			'when'           => $when,
			'days'           => $days,
			'reporter_id'    => $r_id,
			'reporter_name'  => $r_name,
			'reporter_phone' => $r_phone,
			'reporter_email' => $r_email,
			'anonymous'      => ( ! $r_id && '' === $r_name && '' === $r_phone ) ? 1 : 0,
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $report_id, KAAMASE_META_PREFIX . 'r_' . $key, $value );
		}

		kaamase_notify_report( $report_id, $urgent );

		/**
		 * Fires when a report is submitted.
		 *
		 * @since 1.0.0
		 * @param int  $report_id Report ID.
		 * @param bool $urgent    Whether it was marked urgent.
		 */
		do_action( 'kaamase_report_submitted', $report_id, $urgent );

		wp_safe_redirect( add_query_arg( 'reported', $reference, kaamase_page_url( $page ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_report' );

if ( ! function_exists( 'kaamase_notify_report' ) ) {
	/**
	 * Email the report to whoever handles them.
	 *
	 * Urgent ones say so in the subject line, because a report about
	 * somebody asking a worker for money should not sit behind forty
	 * other messages until Thursday.
	 *
	 * @since 1.0.0
	 * @param int  $report_id Report ID.
	 * @param bool $urgent    Whether it is urgent.
	 * @return void
	 */
	function kaamase_notify_report( $report_id, $urgent ) {

		/**
		 * Filter where reports are sent.
		 *
		 * Set this to a real address that a person opens. The site admin
		 * address is often nobody's inbox.
		 *
		 * @since 1.0.0
		 * @param string $address Destination email.
		 */
		$to = apply_filters( 'kaamase_report_email', get_option( 'admin_email' ) );

		$subject = sprintf(
			'%1$s%2$s',
			$urgent ? __( 'URGENT: ', 'kaamase-core' ) : '',
			get_the_title( $report_id )
		);

		$prefix = KAAMASE_META_PREFIX . 'r_';

		$lines = array(
			get_post_field( 'post_content', $report_id ),
			'',
			sprintf(
				/* translators: %s: who the report is about */
				__( 'About: %s', 'kaamase-core' ),
				get_post_meta( $report_id, $prefix . 'about_who', true ) ? get_post_meta( $report_id, $prefix . 'about_who', true ) : __( 'not given', 'kaamase-core' )
			),
			sprintf(
				/* translators: %s: reporter name */
				__( 'From: %s', 'kaamase-core' ),
				get_post_meta( $report_id, $prefix . 'anonymous', true )
					? __( 'anonymous', 'kaamase-core' )
					: get_post_meta( $report_id, $prefix . 'reporter_name', true )
			),
		);

		$phone = get_post_meta( $report_id, $prefix . 'reporter_phone', true );

		if ( $phone ) {
			/* translators: %s: phone number */
			$lines[] = sprintf( __( 'Phone: %s', 'kaamase-core' ), $phone );
		}

		$amount = absint( get_post_meta( $report_id, $prefix . 'amount', true ) );

		if ( $amount ) {
			/* translators: %s: amount in rupees */
			$lines[] = sprintf( __( 'Amount owed: rupees %s', 'kaamase-core' ), number_format_i18n( $amount ) );
		}

		$lines[] = '';
		$lines[] = admin_url( 'post.php?post=' . absint( $report_id ) . '&action=edit' );

		wp_mail( $to, $subject, implode( "\n", $lines ) );
	}
}

if ( ! function_exists( 'kaamase_report_thanks' ) ) {
	/**
	 * The confirmation screen.
	 *
	 * States the reference, the timeframe and what happens next. A
	 * confirmation that only says thank you leaves somebody wondering
	 * whether anything was received at all, and that is the moment they
	 * decide the platform does not care.
	 *
	 * @since 1.0.0
	 * @param string $reference Reference code.
	 * @return string Markup.
	 */
	function kaamase_report_thanks( $reference ) {

		$reference = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $reference ) );

		ob_start();
		?>
		<div class="ka-card ka-card--pad-lg">

			<h2><?php esc_html_e( 'We have it', 'kaamase-core' ); ?></h2>

			<p class="ka-lead ka-soft ka-mt-4">
				<?php esc_html_e( 'Your reference number is', 'kaamase-core' ); ?>
				<strong class="ka-wage"><?php echo esc_html( $reference ); ?></strong>
			</p>

			<p class="ka-soft ka-mt-4">
				<?php esc_html_e( 'Write it down. Quote it if you contact us again about the same thing.', 'kaamase-core' ); ?>
			</p>

			<h3 class="ka-mt-6"><?php esc_html_e( 'What happens now', 'kaamase-core' ); ?></h3>

			<ol class="ka-stack ka-mt-4">
				<li><?php esc_html_e( 'Somebody reads it within two working days.', 'kaamase-core' ); ?></li>
				<li><?php esc_html_e( 'If we can reach you, we call and ask what we need to know.', 'kaamase-core' ); ?></li>
				<li><?php esc_html_e( 'We speak to the other side. We never tell them who reported it.', 'kaamase-core' ); ?></li>
				<li><?php esc_html_e( 'If somebody has done something serious, their account goes.', 'kaamase-core' ); ?></li>
			</ol>

			<p class="ka-small ka-mute ka-mt-6">
				<?php esc_html_e( 'We are a listing platform, not a court and not the police. We cannot make anybody pay you. What we can do is look into it, keep a record, and remove people who cheat.', 'kaamase-core' ); ?>
			</p>

			<a class="ka-btn ka-btn--outline ka-mt-6" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Back to Kaam Ase', 'kaamase-core' ); ?>
			</a>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   5. STATE AND LIMITS
   ========================================================================== */

if ( ! function_exists( 'kaamase_report_key' ) ) {
	/**
	 * Transient key for this visitor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_report_key() {

		if ( is_user_logged_in() ) {
			return 'kaamase_rep_u' . get_current_user_id();
		}

		$address = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: 'unknown';

		return 'kaamase_rep_' . md5( $address );
	}
}

if ( ! function_exists( 'kaamase_report_errors' ) ) {
	/**
	 * Read and clear report form errors.
	 *
	 * @since 1.0.0
	 * @return string[] Messages.
	 */
	function kaamase_report_errors() {

		$key    = kaamase_report_key() . '_err';
		$errors = get_transient( $key );

		if ( $errors ) {
			delete_transient( $key );
		}

		return is_array( $errors ) ? $errors : array();
	}
}

if ( ! function_exists( 'kaamase_report_fail' ) ) {
	/**
	 * Store errors and go back to the form.
	 *
	 * @since 1.0.0
	 * @param string[] $errors Messages.
	 * @param string   $page   Which page to return to.
	 * @return void
	 */
	function kaamase_report_fail( $errors, $page ) {

		set_transient( kaamase_report_key() . '_err', (array) $errors, 15 * MINUTE_IN_SECONDS );

		wp_safe_redirect( kaamase_page_url( $page ) );
		exit;
	}
}

if ( ! function_exists( 'kaamase_report_rate_ok' ) ) {
	/**
	 * Whether this visitor may send another report.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_report_rate_ok() {

		// Durable, not a transient. See the note in throttle.php.
		return kaamase_rate_bump( kaamase_report_key() . '_rate', HOUR_IN_SECONDS ) <= 5;
	}
}


/* ==========================================================================
   6. ADMIN
   ========================================================================== */

/**
 * Show the report details on the edit screen.
 *
 * Everything in one panel, because working through a report while
 * clicking between custom fields is how a detail gets missed.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_report_meta_box() {

	add_meta_box(
		'kaamase-report',
		__( 'Report details', 'kaamase-core' ),
		'kaamase_render_report_meta',
		'kaamase_report',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'kaamase_report_meta_box' );

/**
 * Render the report details panel.
 *
 * @since 1.0.0
 * @param WP_Post $post The report.
 * @return void
 */
function kaamase_render_report_meta( $post ) {

	$prefix = KAAMASE_META_PREFIX . 'r_';

	$rows = array(
		__( 'Reference', 'kaamase-core' )   => get_post_meta( $post->ID, $prefix . 'reference', true ),
		__( 'About', 'kaamase-core' )       => get_post_meta( $post->ID, $prefix . 'about_who', true ),
		__( 'From', 'kaamase-core' )        => get_post_meta( $post->ID, $prefix . 'anonymous', true )
			? __( 'Anonymous', 'kaamase-core' )
			: get_post_meta( $post->ID, $prefix . 'reporter_name', true ),
		__( 'Phone', 'kaamase-core' )       => get_post_meta( $post->ID, $prefix . 'reporter_phone', true ),
		__( 'Email', 'kaamase-core' )       => get_post_meta( $post->ID, $prefix . 'reporter_email', true ),
		__( 'Amount owed', 'kaamase-core' ) => absint( get_post_meta( $post->ID, $prefix . 'amount', true ) )
			? '₹' . number_format_i18n( absint( get_post_meta( $post->ID, $prefix . 'amount', true ) ) )
			: '',
		__( 'When', 'kaamase-core' )        => get_post_meta( $post->ID, $prefix . 'when', true ),
		__( 'Days worked', 'kaamase-core' ) => absint( get_post_meta( $post->ID, $prefix . 'days', true ) ) ? absint( get_post_meta( $post->ID, $prefix . 'days', true ) ) : '',
	);

	if ( get_post_meta( $post->ID, $prefix . 'urgent', true ) ) {
		echo '<p style="background:#fbe9e7;border-left:4px solid #c0392b;padding:8px;margin:0 0 12px;"><strong>'
			. esc_html__( 'Marked urgent. Deal with this first.', 'kaamase-core' )
			. '</strong></p>';
	}

	echo '<table style="width:100%;">';

	foreach ( $rows as $label => $value ) {

		if ( '' === $value ) {
			continue;
		}

		printf(
			'<tr><th style="text-align:left;padding:4px 8px 4px 0;vertical-align:top;">%1$s</th><td style="padding:4px 0;">%2$s</td></tr>',
			esc_html( $label ),
			esc_html( $value )
		);
	}

	echo '</table>';

	echo '<p class="description" style="margin-top:12px;">'
		. esc_html__( 'Set the status to Closed when it is dealt with. Reports are never shown on the website.', 'kaamase-core' )
		. '</p>';
}
