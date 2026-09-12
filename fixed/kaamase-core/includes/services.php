<?php
/**
 * Services.
 *
 * The rules, with no front end attached to them.
 *
 * Why this file exists
 * --------------------
 * Every write on this platform used to live inside a form handler:
 * kaamase_handle_post_job(), kaamase_handle_profile_save() and the rest.
 * Those functions read $_POST, set a transient, redirect and exit. They
 * are the web form, not the rule.
 *
 * The moment a phone app needs to post a job, there are two ways to go.
 * Reimplement the validation in the REST endpoint, or share it. The
 * first is faster to write and it is how two front ends end up
 * disagreeing about the minimum wage, about whether a phone number is
 * required, about how many urgent jobs one employer may run. The
 * website says no and the app says yes, and nobody notices for months
 * because each one is correct on its own terms.
 *
 * fields.php already makes this argument about a field being declared
 * once. This is the same argument about a rule being enforced once.
 *
 * So the validation and the save moved here, into functions that take an
 * array and return an ID or a WP_Error. The form handlers call them. The
 * REST endpoints call them. Neither owns the rule.
 *
 * What did NOT move
 * -----------------
 * Redirects, transients, nonces and $_POST reading. Those are transport,
 * and transport differs between a browser posting a form and a phone
 * sending JSON. Each front end keeps its own.
 *
 * @package KaamaseCore
 * @version 1.2.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. JOBS
   ========================================================================== */

if ( ! function_exists( 'kaamase_validate_job' ) ) {
	/**
	 * Check a job against every rule, without saving anything.
	 *
	 * @since 1.3.0
	 * @param array $data    Raw job data, already sanitised by the caller.
	 * @param int   $user_id Employer user ID.
	 * @param int   $job_id  Job being edited, or 0 for a new one.
	 * @return true|WP_Error True, or an error whose data carries every message.
	 */
	function kaamase_validate_job( $data, $user_id, $job_id = 0 ) {

		$errors = array();

		if ( empty( $data['trade'] ) ) {
			$errors[] = __( 'Choose what work you need done.', 'kaamase-core' );
		}

		if ( empty( $data['district'] ) ) {
			$errors[] = __( 'Choose the district.', 'kaamase-core' );
		}

		if ( absint( $data['workers_needed'] ) < 1 ) {
			$errors[] = __( 'Say how many workers you need.', 'kaamase-core' );
		}

		$pay = absint( $data['pay_amount'] );

		if ( $pay < 1 ) {
			$errors[] = __( 'Put the rate you are paying. Jobs with no rate get almost no answers.', 'kaamase-core' );
		}

		$floor = kaamase_wage_floor( $data['pay_unit'] );

		if ( $floor && $pay > 0 && $pay < $floor ) {
			$errors[] = sprintf(
				/* translators: 1: amount offered, 2: statutory minimum */
				__( 'Rupees %1$s is below the legal minimum wage of rupees %2$s. Check the amount, or change the pay period if you picked the wrong one.', 'kaamase-core' ),
				number_format_i18n( $pay ),
				number_format_i18n( $floor )
			);
		}

		if ( ! empty( $data['urgent'] ) && kaamase_has_open_urgent( $user_id, $job_id ) ) {
			$errors[] = function_exists( 'kaamase_limit_reached_message' )
				? kaamase_limit_reached_message( 'urgent_jobs' )
				: __( 'You already have an urgent job running. One at a time.', 'kaamase-core' );
		}

		$phone_in = isset( $data['contact_phone'] ) ? (string) $data['contact_phone'] : '';

		if ( '' !== $phone_in && '' === kaamase_sanitize_phone( $phone_in ) ) {
			$errors[] = __( 'That phone number does not look right. It should be 10 digits starting with 6, 7, 8 or 9.', 'kaamase-core' );
		}

		if ( ! $job_id && ! kaamase_job_rate_ok( $user_id ) ) {
			$errors[] = function_exists( 'kaamase_limit_reached_message' )
				? kaamase_limit_reached_message( 'jobs_per_day' )
				: __( 'You have posted a lot of jobs today. Try again tomorrow.', 'kaamase-core' );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'kaamase_invalid_job', $errors[0], array( 'messages' => $errors, 'status' => 400 ) );
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_save_job' ) ) {
	/**
	 * Create or update a job.
	 *
	 * @since 1.3.0
	 * @param array $data    Job data.
	 * @param int   $user_id Employer user ID.
	 * @param int   $job_id  Job being edited, or 0.
	 * @return int|WP_Error Job ID, or an error.
	 */
	function kaamase_save_job( $data, $user_id, $job_id = 0 ) {

		$defaults = array(
			'title'              => '',
			'description'        => '',
			'trade'              => '',
			'district'           => '',
			'town'               => '',
			'pay_amount'         => 0,
			'pay_unit'           => 'day',
			'workers_needed'     => 1,
			'start_date'         => '',
			'duration'           => '',
			'urgent'             => false,
			'food_provided'      => false,
			'stay_provided'      => false,
			'transport_provided' => false,
			'contact_phone'      => '',
		);

		$data = wp_parse_args( $data, $defaults );

		if ( ! user_can( $user_id, 'create_kaamase_jobs' ) ) {
			return new WP_Error(
				'kaamase_forbidden',
				__( 'This account cannot post jobs.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		if ( $job_id && ! user_can( $user_id, 'edit_post', $job_id ) ) {
			return new WP_Error(
				'kaamase_forbidden',
				__( 'That job is not yours to edit.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		$valid = kaamase_validate_job( $data, $user_id, $job_id );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$title = (string) $data['title'];

		if ( '' === trim( $title ) ) {
			$title = kaamase_generate_job_title(
				$data['trade'],
				absint( $data['workers_needed'] ),
				$data['district']
			);
		}

		$is_new = ! $job_id;
		$status = kaamase_user_is_verified( $user_id ) ? 'publish' : 'draft';

		$args = array(
			'post_title'   => $title,
			'post_content' => (string) $data['description'],
			'post_type'    => 'kaamase_job',
			'post_author'  => $user_id,
		);

		if ( $job_id ) {

			$args['ID'] = $job_id;

			// Never silently reopen a job the employer closed.
			if ( 'kaamase_closed' !== get_post_status( $job_id ) ) {
				$args['post_status'] = $status;
			}

			$saved = wp_update_post( $args, true );
		} else {
			$args['post_status'] = $status;
			$saved               = wp_insert_post( $args, true );
		}

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$job_id = (int) ( $is_new ? $saved : $job_id );

		wp_set_object_terms( $job_id, $data['trade'], 'kaamase_trade' );
		wp_set_object_terms( $job_id, $data['district'], 'kaamase_district' );

		$employer_profile = kaamase_get_user_profile( $user_id, 'kaamase_employer' );
		$phone            = kaamase_sanitize_phone( (string) $data['contact_phone'] );

		// Fall back to the account number when none was given for this job.
		if ( '' === $phone && $employer_profile ) {
			$phone = (string) kaamase_read_field( $employer_profile, 'phone' );
		}

		$user = get_userdata( $user_id );

		$fields = array(
			'district'           => $data['district'],
			'town'               => $data['town'],
			'pay_amount'         => absint( $data['pay_amount'] ),
			'pay_unit'           => $data['pay_unit'],
			'workers_needed'     => absint( $data['workers_needed'] ),
			'start_date'         => $data['start_date'],
			'duration'           => $data['duration'],
			'urgent'             => ! empty( $data['urgent'] ),
			'food_provided'      => ! empty( $data['food_provided'] ),
			'stay_provided'      => ! empty( $data['stay_provided'] ),
			'transport_provided' => ! empty( $data['transport_provided'] ),
			'contact_phone'      => $phone,
			'employer_name'      => $employer_profile ? get_the_title( $employer_profile ) : ( $user ? $user->display_name : '' ),
		);

		if ( $is_new ) {
			$fields['job_status'] = 'open';
		}

		foreach ( $fields as $key => $value ) {
			kaamase_save_field( $job_id, $key, $value );
		}

		if ( $employer_profile ) {
			update_post_meta( $job_id, KAAMASE_META_PREFIX . 'employer_id', $employer_profile );
		}

		if ( $is_new ) {

			kaamase_job_rate_use( $user_id );

			/*
			 * Stamp the expiry here, not during the insert above.
			 *
			 * kaamase_set_job_expiry runs on wp_insert_post and asks
			 * kaamase_job_lifespan whether this job gets three days or
			 * twenty-one. That answer comes from the urgent flag -- and
			 * the urgent flag is written a few lines above this, well
			 * after the insert has already happened. So at the moment
			 * it was asked the flag was always absent, every urgent job
			 * was stamped twenty-one days like an ordinary one, and the
			 * tag meant nothing at all.
			 *
			 * By here the fields are stored, so lifespan reads the
			 * truth. It is written directly because kaamase_set_job_expiry
			 * refuses to move an expiry that already exists -- right for
			 * an employer who extended one, wrong for the placeholder it
			 * wrote itself moments ago on a guess.
			 *
			 * New jobs only. Ticking urgent on a job that is already
			 * running does not move its expiry, exactly as before: on a
			 * listing with six days left, three days from now would
			 * be a shorter listing, and on one with two days left it
			 * would be a longer one. Neither is what the employer
			 * asked for.
			 */
			if ( function_exists( 'kaamase_job_lifespan' ) ) {
				update_post_meta(
					$job_id,
					KAAMASE_META_PREFIX . 'expires',
					time() + ( kaamase_job_lifespan( $job_id ) * DAY_IN_SECONDS )
				);
			}
		}

		/** This action is documented in includes/post-job.php */
		do_action( 'kaamase_job_saved', $job_id, $is_new, 'publish' === $status );

		return $job_id;
	}
}


/* ==========================================================================
   2. PROFILES
   ========================================================================== */

if ( ! function_exists( 'kaamase_save_profile' ) ) {
	/**
	 * Update a worker or employer profile.
	 *
	 * @since 1.3.0
	 * @param int   $post_id Profile ID.
	 * @param array $data    Profile data.
	 * @param int   $user_id User making the change.
	 * @return int|WP_Error Profile ID, or an error.
	 */
	function kaamase_save_profile( $post_id, $data, $user_id ) {

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_employer' ), true ) ) {
			return new WP_Error( 'kaamase_no_post', __( 'That profile no longer exists.', 'kaamase-core' ), array( 'status' => 404 ) );
		}

		if ( ! user_can( $user_id, 'edit_post', $post_id ) ) {
			return new WP_Error( 'kaamase_forbidden', __( 'That profile is not yours to edit.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		$employer = 'kaamase_employer' === $post->post_type;

		$name     = isset( $data['name'] ) ? (string) $data['name'] : '';
		$district = isset( $data['district'] ) ? kaamase_match_district( (string) $data['district'] ) : '';
		$phone    = isset( $data['phone'] ) ? kaamase_sanitize_phone( (string) $data['phone'] ) : '';
		$trades   = isset( $data['trades'] ) ? array_map( 'sanitize_key', (array) $data['trades'] ) : array();

		$errors = array();

		if ( '' === trim( $name ) ) {
			$errors[] = __( 'Please put your name.', 'kaamase-core' );
		}

		if ( '' === $district ) {
			$errors[] = __( 'Please choose your district.', 'kaamase-core' );
		}

		if ( '' === $phone ) {
			$errors[] = __( 'Please put a phone number. It should be 10 digits starting with 6, 7, 8 or 9.', 'kaamase-core' );
		}

		if ( ! $employer && empty( $trades ) ) {
			$errors[] = __( 'Tick at least one kind of work you do.', 'kaamase-core' );
		}

		/**
		 * Filter the errors found on a profile before it is saved.
		 *
		 * Here so a check can be added without this function needing to
		 * know about it. The GST number is checked this way: it is
		 * optional, so it does not belong in the required field list
		 * above, but a number that fails its check character is a typo
		 * the person can fix and should be told about rather than left
		 * wondering why nothing appeared.
		 *
		 * @since 1.2.0
		 * @param string[] $errors Errors so far.
		 * @param array    $data   The submitted profile.
		 * @param int      $user_id Whose profile it is.
		 */
		$errors = (array) apply_filters( 'kaamase_profile_errors', $errors, $data, (int) $user_id );

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'kaamase_invalid_profile', $errors[0], array( 'messages' => $errors, 'status' => 400 ) );
		}

		$updated = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => $name,
				'post_content' => isset( $data['about'] ) ? (string) $data['about'] : '',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		kaamase_save_field( $post_id, 'district', $district );
		kaamase_save_field( $post_id, 'town', isset( $data['town'] ) ? (string) $data['town'] : '' );
		kaamase_save_field( $post_id, 'phone', $phone );

		wp_set_object_terms( $post_id, $district, 'kaamase_district' );

		if ( $employer ) {

			kaamase_save_field( $post_id, 'employer_type', isset( $data['employer_type'] ) ? sanitize_key( $data['employer_type'] ) : 'individual' );
			kaamase_save_field( $post_id, 'contact_name', isset( $data['contact_name'] ) ? (string) $data['contact_name'] : '' );
			kaamase_save_field( $post_id, 'gst', isset( $data['gst'] ) ? strtoupper( (string) $data['gst'] ) : '' );

		} else {

			wp_set_object_terms( $post_id, $trades, 'kaamase_trade' );

			if ( isset( $data['languages'] ) ) {
				wp_set_object_terms( $post_id, array_map( 'sanitize_key', (array) $data['languages'] ), 'kaamase_language' );
			}

			$worker_fields = array(
				'years_experience' => 'years_experience',
				'day_rate'         => 'day_rate',
				'month_rate'       => 'month_rate',
				'travel_radius'    => 'travel_radius',
				'vouched_by'       => 'vouched_by',
				'vouched_role'     => 'vouched_role',
				'vouched_phone'    => 'vouched_phone',
			);

			foreach ( $worker_fields as $key ) {
				if ( isset( $data[ $key ] ) ) {
					kaamase_save_field( $post_id, $key, $data[ $key ] );
				}
			}
		}

		/**
		 * Fires after a profile is saved through any front end.
		 *
		 * @since 1.3.0
		 * @param int $post_id Profile ID.
		 * @param int $user_id User who saved it.
		 */
		do_action( 'kaamase_profile_saved', $post_id, $user_id );

		return $post_id;
	}
}


/* ==========================================================================
   3. TEAMS
   ========================================================================== */

if ( ! function_exists( 'kaamase_save_team' ) ) {
	/**
	 * Create or update a team.
	 *
	 * @since 1.3.0
	 * @param array $data    Team data.
	 * @param int   $user_id Owner.
	 * @param int   $team_id Team being edited, or 0.
	 * @return int|WP_Error Team ID, or an error.
	 */
	function kaamase_save_team( $data, $user_id, $team_id = 0 ) {

		if ( ! user_can( $user_id, 'create_kaamase_gangs' ) ) {
			return new WP_Error( 'kaamase_forbidden', __( 'This account cannot list a team.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		// One team per person, whatever the request said.
		$existing = kaamase_get_user_profile( $user_id, 'kaamase_gang' );

		if ( ! $team_id && $existing ) {
			$team_id = $existing;
		}

		if ( $team_id && ! user_can( $user_id, 'edit_post', $team_id ) ) {
			return new WP_Error( 'kaamase_forbidden', __( 'That team is not yours to edit.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		$name      = isset( $data['name'] ) ? (string) $data['name'] : '';
		$headcount = isset( $data['headcount'] ) ? absint( $data['headcount'] ) : 0;
		$trades    = isset( $data['trades'] ) ? array_map( 'sanitize_key', (array) $data['trades'] ) : array();
		$district  = isset( $data['district'] ) ? kaamase_match_district( (string) $data['district'] ) : '';
		$phone     = isset( $data['phone'] ) ? kaamase_sanitize_phone( (string) $data['phone'] ) : '';

		$errors = array();

		if ( '' === trim( $name ) ) {
			$errors[] = __( 'Give the team a name. Your own name is fine.', 'kaamase-core' );
		}

		if ( $headcount < 2 ) {
			$errors[] = __( 'A team is two workers or more. If you work alone, your worker profile is the right listing.', 'kaamase-core' );
		}

		if ( empty( $trades ) ) {
			$errors[] = __( 'Tick at least one kind of work the team does.', 'kaamase-core' );
		}

		if ( '' === $district ) {
			$errors[] = __( 'Choose your district.', 'kaamase-core' );
		}

		if ( '' === $phone ) {
			$errors[] = __( 'Put a phone number for the team. It should be 10 digits starting with 6, 7, 8 or 9.', 'kaamase-core' );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'kaamase_invalid_team', $errors[0], array( 'messages' => $errors, 'status' => 400 ) );
		}

		$args = array(
			'post_title'   => $name,
			'post_content' => isset( $data['about'] ) ? (string) $data['about'] : '',
			'post_type'    => 'kaamase_gang',
			'post_author'  => $user_id,
		);

		if ( $team_id ) {
			$args['ID'] = $team_id;
			$saved      = wp_update_post( $args, true );
		} else {
			$args['post_status'] = kaamase_user_is_verified( $user_id ) ? 'publish' : 'draft';
			$saved               = wp_insert_post( $args, true );
		}

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$team_id = (int) ( $team_id ? $team_id : $saved );

		wp_set_object_terms( $team_id, $trades, 'kaamase_trade' );
		wp_set_object_terms( $team_id, $district, 'kaamase_district' );

		$fields = array(
			'headcount'     => $headcount,
			'leader_name'   => isset( $data['leader_name'] ) ? (string) $data['leader_name'] : '',
			'district'      => $district,
			'town'          => isset( $data['town'] ) ? (string) $data['town'] : '',
			'day_rate'      => isset( $data['day_rate'] ) ? absint( $data['day_rate'] ) : 0,
			'travel_radius' => isset( $data['travel_radius'] ) ? sanitize_key( $data['travel_radius'] ) : 'district',
			'phone'         => $phone,
		);

		foreach ( $fields as $key => $value ) {
			kaamase_save_field( $team_id, $key, $value );
		}

		/** This action is documented in includes/teams.php */
		do_action( 'kaamase_team_saved', $team_id, $user_id );

		return $team_id;
	}
}


/* ==========================================================================
   4. RATINGS
   ========================================================================== */

if ( ! function_exists( 'kaamase_submit_rating' ) ) {
	/**
	 * Record a rating.
	 *
	 * The double blind, the flags and the score all behave exactly as they
	 * do on the website, because this is the same code path.
	 *
	 * @since 1.3.0
	 * @param int    $post_id Profile being rated.
	 * @param array  $answers Answers keyed by question name.
	 * @param string $note    Optional public note.
	 * @param int    $user_id Rater.
	 * @return int|WP_Error Comment ID, or an error.
	 */
	function kaamase_submit_rating( $post_id, $answers, $note, $user_id ) {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error( 'kaamase_signed_out', __( 'Sign in to leave a rating.', 'kaamase-core' ), array( 'status' => 401 ) );
		}

		/*
		 * kaamase_can_rate() reads the current user, so make sure it is
		 * the one we were handed before asking it anything.
		 */
		$previous = get_current_user_id();

		if ( $previous !== (int) $user_id ) {
			wp_set_current_user( $user_id );
		}

		$allowed = kaamase_can_rate( $post_id );

		if ( is_wp_error( $allowed ) ) {
			$allowed->add_data( array( 'status' => 403 ), $allowed->get_error_code() );
			return $allowed;
		}

		$post  = get_post( $post_id );
		$about = 'kaamase_employer' === $post->post_type ? 'employer' : 'worker';

		$clean = array();

		foreach ( array_keys( kaamase_rating_questions( $about ) ) as $key ) {
			$clean[ $key ] = ! empty( $answers[ $key ] );
		}

		$note  = mb_substr( sanitize_textarea_field( (string) $note ), 0, 300 );
		$score = kaamase_score_answers( $clean, $about );

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_content'      => $note,
				'comment_type'         => 'kaamase_rating',
				'user_id'              => $user->ID,
				'comment_approved'     => 0,
			)
		);

		if ( ! $comment_id ) {
			return new WP_Error( 'kaamase_rating_failed', __( 'Something went wrong saving that rating.', 'kaamase-core' ), array( 'status' => 500 ) );
		}

		add_comment_meta( $comment_id, 'kaamase_score', $score );
		add_comment_meta( $comment_id, 'kaamase_answers', $clean );
		add_comment_meta( $comment_id, 'kaamase_about', $about );
		add_comment_meta( $comment_id, 'kaamase_window', time() + ( 14 * DAY_IN_SECONDS ) );

		kaamase_maybe_release_pair( $post_id, (int) $user->ID );
		kaamase_raise_flags( $post_id, $clean, $about );

		return (int) $comment_id;
	}
}


/* ==========================================================================
   5. REPORTS
   ========================================================================== */

if ( ! function_exists( 'kaamase_submit_report' ) ) {
	/**
	 * File a report or a wage complaint.
	 *
	 * @since 1.3.0
	 * @param array $data    Report data.
	 * @param int   $user_id Reporter, or 0 when anonymous.
	 * @return array|WP_Error Reference and ID, or an error.
	 */
	function kaamase_submit_report( $data, $user_id = 0 ) {

		$mode = isset( $data['mode'] ) && 'grievance' === $data['mode'] ? 'grievance' : 'report';
		$wage = ( 'grievance' === $mode );

		$kind    = isset( $data['kind'] ) ? sanitize_key( $data['kind'] ) : '';
		$details = mb_substr( sanitize_textarea_field( (string) ( $data['details'] ?? '' ) ), 0, 2000 );

		$errors = array();

		if ( ! $wage && '' === $kind ) {
			$errors[] = __( 'Choose what the report is about.', 'kaamase-core' );
		}

		if ( mb_strlen( trim( $details ) ) < 20 ) {
			$errors[] = __( 'Please tell us a bit more about what happened, so we know where to start.', 'kaamase-core' );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'kaamase_invalid_report', $errors[0], array( 'messages' => $errors, 'status' => 400 ) );
		}

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
			return $report_id;
		}

		$reporter_name  = isset( $data['reporter_name'] ) ? (string) $data['reporter_name'] : '';
		$reporter_phone = kaamase_sanitize_phone( (string) ( $data['reporter_phone'] ?? '' ) );
		$reporter_email = '';

		if ( $user_id ) {

			$user = get_userdata( $user_id );

			if ( $user ) {
				$reporter_name  = $user->display_name;
				$reporter_email = $user->user_email;

				$profile        = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );
				$reporter_phone = $profile ? (string) kaamase_read_field( $profile, 'phone' ) : $reporter_phone;
			}
		}

		$meta = array(
			'reference'      => $reference,
			'mode'           => $mode,
			'kind'           => $wage ? 'not_paid' : $kind,
			'urgent'         => $urgent ? 1 : 0,
			'about_who'      => isset( $data['about_who'] ) ? (string) $data['about_who'] : '',
			'amount'         => isset( $data['amount'] ) ? absint( $data['amount'] ) : 0,
			'when'           => isset( $data['when'] ) ? (string) $data['when'] : '',
			'days'           => isset( $data['days'] ) ? absint( $data['days'] ) : 0,
			'reporter_id'    => absint( $user_id ),
			'reporter_name'  => $reporter_name,
			'reporter_phone' => $reporter_phone,
			'reporter_email' => $reporter_email,
			'anonymous'      => ( ! $user_id && '' === $reporter_name && '' === $reporter_phone ) ? 1 : 0,
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $report_id, KAAMASE_META_PREFIX . 'r_' . $key, $value );
		}

		kaamase_notify_report( $report_id, $urgent );

		/** This action is documented in includes/reports.php */
		do_action( 'kaamase_report_submitted', $report_id, $urgent );

		return array(
			'id'        => (int) $report_id,
			'reference' => $reference,
		);
	}
}