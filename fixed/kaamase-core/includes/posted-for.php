<?php
/**
 * Posted for somebody else.
 *
 * A job that Kaam Ase puts up on behalf of an employer who is not on the
 * platform, carrying that employer's name and that employer's number.
 *
 * Why this exists
 * ---------------
 * A hiring platform with workers and no jobs is not a hiring platform.
 * Early on the workers arrive first, because they have more reason to
 * look, and they arrive to an empty jobs page and do not come back. The
 * jobs exist: they are sitting in local groups and on message boards,
 * written by people who have never heard of this site.
 *
 * So the owner copies one across. The rule that makes it honest is the
 * number: it is the employer's, never ours. A worker who taps call
 * reaches the person actually hiring, and Kaam Ase is out of the way. We
 * are not a middleman on these, we are a noticeboard, and the wording on
 * the job says so.
 *
 * What this file will not do
 * --------------------------
 * It will not invent jobs. Nothing here generates or scrapes anything.
 * It is a form somebody fills in by hand, having read the original and
 * spoken to the employer. A job that turns out to be nonsense costs a
 * worker a day's wages and a bus fare, which is the whole reason the
 * screening queue exists for everybody else.
 *
 * It also does not link the job to any employer profile. The name shows,
 * the ratings do not, because there is nobody here to rate.
 *
 * Asking to promote one
 * ---------------------
 * The same request anybody can send from their dashboard, for a job put
 * up from this screen. It goes into the Promotions queue as waiting,
 * exactly as if the employer had asked, with the number on the job as
 * the number to ring. See section 5. Nothing about how promotions work
 * is changed here; promote.php still decides everything after the ask.
 *
 * An email address instead of a number
 * ------------------------------------
 * Government offices and larger firms often take applications by email
 * only and give no phone at all. So a job put up from this screen can
 * carry the employer's email address in place of the number, and then a
 * worker who asks for contact details gets that address and a button to
 * write, with no call and no WhatsApp. It goes through the same gate as a
 * number: signed in, email confirmed, counted, logged. Only this screen
 * can set it, so it is not something a member can choose. See section 6
 * for changing it on a job already up.
 *
 * @package KaamaseCore
 * @version 1.3.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE EXTRA FIELDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_posted_for_fields' ) ) {
	/**
	 * Register the fields that mark a job as posted for somebody.
	 *
	 * Done through the schema filter so fields.php stays the one place
	 * that knows what a field is, and this file only says what it needs.
	 *
	 * @since 1.0.0
	 * @param array[] $schema Field definitions keyed by post type.
	 * @return array[]
	 */
	function kaamase_posted_for_fields( $schema ) {

		if ( ! isset( $schema['kaamase_job'] ) ) {
			return $schema;
		}

		$schema['kaamase_job']['posted_for'] = array(
			'type'    => 'bool',
			'default' => false,
			'label'   => __( 'Posted for an outside employer', 'kaamase-core' ),
		);

		$schema['kaamase_job']['posted_source'] = array(
			'type'    => 'string',
			'private' => true,
			'label'   => __( 'Where this job came from', 'kaamase-core' ),
		);

		/*
		 * Private for the same reason the number is: it reaches a worker
		 * only through the contact screen, never printed in a page or
		 * handed out in the job the app downloads.
		 */
		$schema['kaamase_job']['contact_email'] = array(
			'type'    => 'string',
			'private' => true,
			'label'   => __( 'Contact email', 'kaamase-core' ),
		);

		return $schema;
	}
}
add_filter( 'kaamase_field_schema', 'kaamase_posted_for_fields' );

if ( ! function_exists( 'kaamase_job_is_posted_for' ) ) {
	/**
	 * Whether this job was put up on somebody else's behalf.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return bool
	 */
	function kaamase_job_is_posted_for( $job_id ) {

		return (bool) kaamase_read_field( (int) $job_id, 'posted_for' );
	}
}

if ( ! function_exists( 'kaamase_posted_for_email' ) ) {
	/**
	 * The email address workers use instead of a number, if there is one.
	 *
	 * Only ever on a job put up from this screen. Anything else answers
	 * with an empty string, whatever is stored, so the email route can
	 * never turn up on a member's own job.
	 *
	 * @since 1.3.0
	 * @param int $job_id Job ID.
	 * @return string The address, or an empty string for a job reached by phone.
	 */
	function kaamase_posted_for_email( $job_id ) {

		$job_id = (int) $job_id;

		if ( 'kaamase_job' !== get_post_type( $job_id ) || ! kaamase_job_is_posted_for( $job_id ) ) {
			return '';
		}

		$email = sanitize_email( (string) kaamase_read_field( $job_id, 'contact_email' ) );

		return is_email( $email ) ? $email : '';
	}
}


/* ==========================================================================
   2. SAYING SO, ON THE JOB PAGE AND IN THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_posted_for_notice' ) ) {
	/**
	 * Put a line above the job saying who it belongs to.
	 *
	 * A worker deciding whether to ring is entitled to know that the
	 * person answering did not write this and is not us. Leaving it
	 * unsaid would be the platform quietly taking credit for somebody
	 * else's hiring, which is the one thing that would make copying
	 * these across dishonest.
	 *
	 * @since 1.0.0
	 * @param string $content The job description.
	 * @return string
	 */
	function kaamase_posted_for_notice( $content ) {

		if ( ! is_singular( 'kaamase_job' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$job_id = get_the_ID();

		if ( ! kaamase_job_is_posted_for( $job_id ) ) {
			return $content;
		}

		$name = (string) kaamase_read_field( $job_id, 'employer_name' );

		if ( '' !== kaamase_posted_for_email( $job_id ) ) {

			$line = $name
				? sprintf(
					/* translators: %s: the employer's name */
					__( '%s is hiring, not us. They take applications by email, and the address on this job is theirs, so you write to them directly.', 'kaamase-core' ),
					$name
				)
				: __( 'The email address on this job belongs to the employer, not to us. You write to them directly.', 'kaamase-core' );

		} else {

			$line = $name
				? sprintf(
					/* translators: %s: the employer's name */
					__( '%s is hiring, not us. The number on this job is theirs, so you speak to them directly.', 'kaamase-core' ),
					$name
				)
				: __( 'The number on this job belongs to the employer, not to us. You speak to them directly.', 'kaamase-core' );
		}

		$notice = sprintf(
			'<p class="ka-hint"><strong>%1$s</strong> %2$s</p>',
			esc_html__( 'Shared by Kaam Ase.', 'kaamase-core' ),
			esc_html( $line )
		);

		return $notice . $content;
	}
}
add_filter( 'the_content', 'kaamase_posted_for_notice' );

if ( ! function_exists( 'kaamase_posted_for_shape' ) ) {
	/**
	 * Tell the app about it too.
	 *
	 * @since 1.0.0
	 * @param array   $out  Shaped job.
	 * @param WP_Post $post The job.
	 * @return array
	 */
	function kaamase_posted_for_shape( $out, $post ) {

		$out['posted_for'] = kaamase_job_is_posted_for( $post->ID );

		/*
		 * How a worker reaches this job, so the app can say "email" on
		 * the button before anybody presses it. The address itself is not
		 * here: like a number, it comes only from the contact endpoint,
		 * behind the same gate.
		 */
		$out['contact_method'] = '' !== kaamase_posted_for_email( $post->ID ) ? 'email' : 'phone';

		return $out;
	}
}
add_filter( 'kaamase_shape_job', 'kaamase_posted_for_shape', 16, 2 );

if ( ! function_exists( 'kaamase_posted_for_channel' ) ) {
	/**
	 * Hand out the email address instead of a number.
	 *
	 * Through the channel filter contact.php already offers, so the gate,
	 * the daily count and the log are exactly the ones a number goes
	 * through. The number is emptied on purpose: an email job has no
	 * phone and no WhatsApp, not both.
	 *
	 * @since 1.3.0
	 * @param array $channel Channel details.
	 * @param int   $post_id Profile or job ID.
	 * @return array
	 */
	function kaamase_posted_for_channel( $channel, $post_id ) {

		$email = kaamase_posted_for_email( $post_id );

		if ( '' === $email ) {
			return $channel;
		}

		$channel['number'] = '';
		$channel['email']  = $email;
		$channel['label']  = __( 'Email address', 'kaamase-core' );

		return $channel;
	}
}
add_filter( 'kaamase_contact_channel', 'kaamase_posted_for_channel', 10, 2 );


/* ==========================================================================
   3. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_posted_for_menu' ) ) {
	/**
	 * Add the screen under Kaam Ase.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_posted_for_menu() {

		add_submenu_page(
			'kaamase',
			__( 'Post a job for somebody', 'kaamase-core' ),
			__( 'Post for somebody', 'kaamase-core' ),
			'edit_others_kaamase_jobs',
			'kaamase-post-for',
			'kaamase_posted_for_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_posted_for_menu', 22 );

if ( ! function_exists( 'kaamase_posted_for_page' ) ) {
	/**
	 * Render the form.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_posted_for_page() {

		if ( ! current_user_can( 'edit_others_kaamase_jobs' ) ) {
			return;
		}

		$errors = get_transient( 'kaamase_post_for_' . get_current_user_id() );

		delete_transient( 'kaamase_post_for_' . get_current_user_id() );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$posted = isset( $_GET['posted'] ) ? absint( $_GET['posted'] ) : 0;
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Post a job for somebody', 'kaamase-core' ); ?></h1>

			<?php if ( $posted ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php esc_html_e( 'The job is up.', 'kaamase-core' ); ?>
						<a href="<?php echo esc_url( (string) get_permalink( $posted ) ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Open it', 'kaamase-core' ); ?>
						</a>
						<?php if ( kaamase_posted_for_promo_ready() ) : ?>
							&middot;
							<a href="#kaamase-pf-promote"><?php esc_html_e( 'Ask to promote it', 'kaamase-core' ); ?></a>
						<?php endif; ?>
					</p>
					<?php kaamase_posted_for_email_posted( $posted ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $errors ) && is_array( $errors ) ) : ?>
				<div class="notice notice-error">
					<?php foreach ( $errors as $error ) : ?>
						<p><?php echo esc_html( $error ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<p style="max-width:45em">
				<?php esc_html_e( 'For jobs you have seen elsewhere and checked yourself. The job goes up under Kaam Ase with the employer\'s name on it, and workers get the employer\'s number, never yours. Ring the employer first and make sure the job is real and that they are happy to be listed.', 'kaamase-core' ); ?>
			</p>

			<form class="kaamase-pf-contact" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">

				<input type="hidden" name="action" value="kaamase_post_for">
				<?php wp_nonce_field( 'kaamase_post_for' ); ?>

				<h2><?php esc_html_e( 'Who is hiring', 'kaamase-core' ); ?></h2>

				<table class="form-table" role="presentation">

					<tr>
						<th scope="row">
							<label for="kaamase-pf-name"><?php esc_html_e( 'Their name', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input class="regular-text" type="text" id="kaamase-pf-name" name="kaamase_employer_name" required
								placeholder="<?php esc_attr_e( 'The person or firm hiring', 'kaamase-core' ); ?>">
							<p class="description"><?php esc_html_e( 'This shows on the job as who is hiring.', 'kaamase-core' ); ?></p>
						</td>
					</tr>

					<?php kaamase_posted_for_contact_rows( 'kaamase-pf' ); ?>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-source"><?php esc_html_e( 'Where you saw it', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input class="large-text" type="text" id="kaamase-pf-source" name="kaamase_source"
								placeholder="<?php esc_attr_e( 'Group name, link, or who told you. Only you see this.', 'kaamase-core' ); ?>">
						</td>
					</tr>

				</table>

				<h2><?php esc_html_e( 'The job', 'kaamase-core' ); ?></h2>

				<table class="form-table" role="presentation">

					<tr>
						<th scope="row">
							<label for="kaamase-pf-trade"><?php esc_html_e( 'Trade', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<select id="kaamase-pf-trade" name="kaamase_trade" required>
								<option value=""><?php esc_html_e( 'Choose the trade', 'kaamase-core' ); ?></option>
								<?php foreach ( kaamase_trade_choices() as $group => $trades ) : ?>
									<optgroup label="<?php echo esc_attr( $group ); ?>">
										<?php foreach ( $trades as $slug => $name ) : ?>
											<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-district"><?php esc_html_e( 'District', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<select id="kaamase-pf-district" name="kaamase_district" required>
								<option value=""><?php esc_html_e( 'Choose the district', 'kaamase-core' ); ?></option>
								<?php foreach ( kaamase_district_choices() as $slug => $name ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-town"><?php esc_html_e( 'Town or village', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input class="regular-text" type="text" id="kaamase-pf-town" name="kaamase_town">
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-pay"><?php esc_html_e( 'Pay', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input type="number" id="kaamase-pf-pay" name="kaamase_pay_amount" min="1" step="1" required
								placeholder="<?php esc_attr_e( 'Rupees', 'kaamase-core' ); ?>">
							<select name="kaamase_pay_unit">
								<option value="day"><?php esc_html_e( 'per day', 'kaamase-core' ); ?></option>
								<option value="month"><?php esc_html_e( 'per month', 'kaamase-core' ); ?></option>
								<option value="job"><?php esc_html_e( 'for the whole job', 'kaamase-core' ); ?></option>
								<option value="hour"><?php esc_html_e( 'per hour', 'kaamase-core' ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'If the original says negotiable, ask the employer for a figure when you ring. A job with no rate gets almost no answers.', 'kaamase-core' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-count"><?php esc_html_e( 'How many workers', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input type="number" id="kaamase-pf-count" name="kaamase_workers_needed" min="1" max="200" value="1" required>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-title"><?php esc_html_e( 'Title', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input class="large-text" type="text" id="kaamase-pf-title" name="kaamase_title"
								placeholder="<?php esc_attr_e( 'Leave blank and one is written for you', 'kaamase-core' ); ?>">
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-description"><?php esc_html_e( 'Details', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<textarea class="large-text" id="kaamase-pf-description" name="kaamase_description" rows="6"
								placeholder="<?php esc_attr_e( 'What the work is, in your own words.', 'kaamase-core' ); ?>"></textarea>
						</td>
					</tr>

					<?php if ( function_exists( 'kaamase_job_photo_limit' ) ) : ?>
						<tr>
							<th scope="row">
								<label for="kaamase-pf-photos"><?php esc_html_e( 'Pictures', 'kaamase-core' ); ?></label>
							</th>
							<td>
								<input type="file" id="kaamase-pf-photos" name="kaamase_photos[]" multiple
									accept="<?php echo esc_attr( kaamase_job_photo_accept() ); ?>">
								<p class="description">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: how many pictures may be added */
											__( 'Up to %s. A screenshot of the original post is not a picture of the work, so use a photo of the site or the shop where you can get one.', 'kaamase-core' ),
											number_format_i18n( kaamase_job_photo_limit() )
										)
									);
									?>
								</p>
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-start"><?php esc_html_e( 'Start date', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input type="date" id="kaamase-pf-start" name="kaamase_start_date">
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-duration"><?php esc_html_e( 'How long the work lasts', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input class="regular-text" type="text" id="kaamase-pf-duration" name="kaamase_duration"
								placeholder="<?php esc_attr_e( 'Two weeks, one month, ongoing', 'kaamase-core' ); ?>">
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Extras', 'kaamase-core' ); ?></th>
						<td>
							<label><input type="checkbox" name="kaamase_urgent" value="1"> <?php esc_html_e( 'Urgent, needed today or tomorrow', 'kaamase-core' ); ?></label><br>
							<label><input type="checkbox" name="kaamase_food" value="1"> <?php esc_html_e( 'Food provided', 'kaamase-core' ); ?></label><br>
							<label><input type="checkbox" name="kaamase_stay" value="1"> <?php esc_html_e( 'Stay provided', 'kaamase-core' ); ?></label><br>
							<label><input type="checkbox" name="kaamase_transport" value="1"> <?php esc_html_e( 'Transport provided', 'kaamase-core' ); ?></label>
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Put this job up', 'kaamase-core' ) ); ?>

			</form>

			<?php kaamase_posted_for_promo_section( $posted ); ?>

			<?php kaamase_posted_for_contact_section(); ?>

			<?php kaamase_posted_for_contact_script(); ?>

		</div>
		<?php
	}
}


/* ==========================================================================
   4. HANDLING THE FORM
   ========================================================================== */

if ( ! function_exists( 'kaamase_posted_for_handle' ) ) {
	/**
	 * Save the job, then put the employer's name and number on it.
	 *
	 * The save goes through kaamase_save_job() like every other job, so
	 * the wage floor, the phone check and the screening queue all still
	 * apply. Only afterwards is the ownership rewritten, because the
	 * fields that say who is hiring are filled from the author's own
	 * profile and the author here is staff.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_posted_for_handle() {

		if ( ! current_user_can( 'edit_others_kaamase_jobs' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_post_for' );

		$user_id = get_current_user_id();
		$back    = admin_url( 'admin.php?page=kaamase-post-for' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked above.
		$name    = isset( $_POST['kaamase_employer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_employer_name'] ) ) : '';
		$contact = kaamase_posted_for_contact_input();
		$source  = isset( $_POST['kaamase_source'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_source'] ) ) : '';

		$values = array(
			'title'              => isset( $_POST['kaamase_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_title'] ) ) : '',
			'description'        => isset( $_POST['kaamase_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kaamase_description'] ) ) : '',
			'trade'              => isset( $_POST['kaamase_trade'] ) ? kaamase_match_trade( sanitize_text_field( wp_unslash( $_POST['kaamase_trade'] ) ) ) : '',
			'district'           => isset( $_POST['kaamase_district'] ) ? kaamase_match_district( sanitize_text_field( wp_unslash( $_POST['kaamase_district'] ) ) ) : '',
			'town'               => isset( $_POST['kaamase_town'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_town'] ) ) : '',
			'pay_amount'         => isset( $_POST['kaamase_pay_amount'] ) ? absint( $_POST['kaamase_pay_amount'] ) : 0,
			'pay_unit'           => isset( $_POST['kaamase_pay_unit'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_pay_unit'] ) ) : 'day',
			'workers_needed'     => isset( $_POST['kaamase_workers_needed'] ) ? absint( $_POST['kaamase_workers_needed'] ) : 1,
			'start_date'         => isset( $_POST['kaamase_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_start_date'] ) ) : '',
			'duration'           => isset( $_POST['kaamase_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_duration'] ) ) : '',
			'urgent'             => ! empty( $_POST['kaamase_urgent'] ),
			'food_provided'      => ! empty( $_POST['kaamase_food'] ),
			'stay_provided'      => ! empty( $_POST['kaamase_stay'] ),
			'transport_provided' => ! empty( $_POST['kaamase_transport'] ),
			'contact_phone'      => $contact['phone'],
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$errors = array();

		if ( '' === trim( $name ) ) {
			$errors[] = __( 'Put the name of the person or firm hiring.', 'kaamase-core' );
		}

		$errors = array_merge( $errors, $contact['errors'] );

		if ( $errors ) {
			set_transient( 'kaamase_post_for_' . $user_id, $errors, 15 * MINUTE_IN_SECONDS );
			wp_safe_redirect( $back );
			exit;
		}

		/*
		 * Staff accounts made in wp-admin never went through the email
		 * confirmation, so they have no verified stamp and their jobs
		 * would save as drafts and never appear. Stamping it here is
		 * the same statement the confirmation link makes, about an
		 * account that can already edit everybody else's jobs.
		 */
		if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( $user_id ) ) {
			update_user_meta( $user_id, 'kaamase_verified_at', time() );
		}

		$result = kaamase_save_job( $values, $user_id, 0 );

		if ( is_wp_error( $result ) ) {

			$data = $result->get_error_data();

			set_transient(
				'kaamase_post_for_' . $user_id,
				isset( $data['messages'] ) ? (array) $data['messages'] : array( $result->get_error_message() ),
				15 * MINUTE_IN_SECONDS
			);

			wp_safe_redirect( $back );
			exit;
		}

		$job_id = (int) $result;

		/*
		 * Rewrite who is hiring.
		 *
		 * kaamase_save_job() filled these from the author, which on this
		 * screen is staff. The name becomes the employer's and the link
		 * to any profile is removed, so the job page shows their name
		 * with no ratings attached to it, because there is no account
		 * here to have earned any.
		 */
		kaamase_save_field( $job_id, 'employer_name', $name );
		kaamase_save_field( $job_id, 'posted_for', true );
		kaamase_save_field( $job_id, 'posted_source', $source );

		/*
		 * The number or the address, whichever was chosen, and never both.
		 * An email job is saved with no number, and kaamase_save_job()
		 * fills an empty one from the author's own profile, so it is
		 * emptied again here rather than left as a staff phone.
		 */
		kaamase_posted_for_contact_save( $job_id, $contact );

		delete_post_meta( $job_id, KAAMASE_META_PREFIX . 'employer_id' );

		/*
		 * Staff posts do not sit in the screening queue. Whoever filled
		 * this form has already read the original and rung the employer,
		 * which is more checking than the queue does.
		 */
		if ( in_array( get_post_status( $job_id ), array( 'draft', 'pending' ), true ) ) {
			wp_update_post(
				array(
					'ID'          => $job_id,
					'post_status' => 'publish',
				)
			);
		}

		wp_safe_redirect( add_query_arg( 'posted', $job_id, $back ) );
		exit;
	}
}
add_action( 'admin_post_kaamase_post_for', 'kaamase_posted_for_handle' );


/* ==========================================================================
   5. ASKING TO PROMOTE ONE

   The dashboard card in promote.php only offers somebody their own
   listings, and it will not take a request from an account with no phone
   on its own profile, because the whole arrangement is a telephone call
   to whoever asked. A job put up from this screen belongs to an outside
   employer: staff wrote it, and staff accounts have no profile. So that
   card never offers these jobs, and asking for one from there is turned
   down.

   This is the same request, sent from here. It writes what the card
   writes, in the same place, so the Promotions queue shows it as waiting
   exactly like any other, and everything after the ask -- the call, the
   price, the Google Pay reference, the start, the rotation, the end date
   -- is promote.php's, unchanged. The number to ring is the one on the
   job, because that is the employer's.
   ========================================================================== */

if ( ! function_exists( 'kaamase_posted_for_promo_ready' ) ) {
	/**
	 * Whether the promotions feature is there to ask.
	 *
	 * Asked at run time rather than when this file loads: promote.php
	 * loads after this one, so at load time the answer would always be no.
	 * Without it the section simply does not appear.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	function kaamase_posted_for_promo_ready() {

		return defined( 'KAAMASE_PROMO_ASK_KEY' )
			&& defined( 'KAAMASE_PROMO_STATE_KEY' )
			&& defined( 'KAAMASE_PROMO_AGAIN_DAYS' )
			&& function_exists( 'kaamase_promo_lengths' )
			&& function_exists( 'kaamase_promo_days' )
			&& function_exists( 'kaamase_promo_state' )
			&& function_exists( 'kaamase_promo_ends' )
			&& function_exists( 'kaamase_promo_is_live' )
			&& function_exists( 'kaamase_promo_showable' )
			&& function_exists( 'kaamase_promo_ask_data' );
	}
}

if ( ! function_exists( 'kaamase_posted_for_promo_jobs' ) ) {
	/**
	 * The jobs put up from this screen that are still open.
	 *
	 * Newest first, so the one just posted is at the top of the list.
	 *
	 * @since 1.2.0
	 * @return int[] Job IDs.
	 */
	function kaamase_posted_for_promo_jobs() {

		return array_values( array_filter( kaamase_posted_for_jobs(), 'kaamase_promo_showable' ) );
	}
}

if ( ! function_exists( 'kaamase_posted_for_promo_can' ) ) {
	/**
	 * Whether this job may be asked about right now.
	 *
	 * The same rules kaamase_promo_can() applies to everybody else, less
	 * the two that are about the person asking rather than the job: whose
	 * listing it is, and a phone number on their own profile. Here the job
	 * is the business's own, and the number is on the job.
	 *
	 * @since 1.2.0
	 * @param int $job_id Job ID.
	 * @return bool
	 */
	function kaamase_posted_for_promo_can( $job_id ) {

		$job_id = absint( $job_id );

		if ( ! $job_id || ! kaamase_posted_for_promo_ready() ) {
			return false;
		}

		if ( 'kaamase_job' !== get_post_type( $job_id ) || ! kaamase_job_is_posted_for( $job_id ) ) {
			return false;
		}

		if ( ! kaamase_promo_showable( $job_id ) ) {
			return false;
		}

		$state = kaamase_promo_state( $job_id );

		// Already in the queue, or already running. Nothing to ask for.
		if ( 'waiting' === $state || kaamase_promo_is_live( $job_id ) ) {
			return false;
		}

		// A refusal holds for the same month it holds for anybody else.
		if ( 'no' === $state ) {

			$ask   = kaamase_promo_ask_data( $job_id );
			$asked = isset( $ask['at'] ) ? (int) $ask['at'] : 0;

			return ( time() - $asked ) > ( KAAMASE_PROMO_AGAIN_DAYS * DAY_IN_SECONDS );
		}

		return true;
	}
}

if ( ! function_exists( 'kaamase_posted_for_promo_section' ) ) {
	/**
	 * Draw the section under the form.
	 *
	 * @since 1.2.0
	 * @param int $posted A job just put up, to have it chosen already.
	 * @return void
	 */
	function kaamase_posted_for_promo_section( $posted = 0 ) {

		if ( ! kaamase_posted_for_promo_ready() ) {
			return;
		}

		$posted = absint( $posted );
		$jobs   = kaamase_posted_for_promo_jobs();
		$date   = (string) get_option( 'date_format', 'j F Y' );
		$queue  = current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=kaamase-promotions' ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$result = isset( $_GET['promo'] ) ? sanitize_key( wp_unslash( $_GET['promo'] ) ) : '';

		$can     = array();
		$waiting = array();
		$live    = array();

		foreach ( $jobs as $job_id ) {

			if ( kaamase_promo_is_live( $job_id ) ) {
				$live[] = $job_id;
			} elseif ( 'waiting' === kaamase_promo_state( $job_id ) ) {
				$waiting[] = $job_id;
			} elseif ( kaamase_posted_for_promo_can( $job_id ) ) {
				$can[] = $job_id;
			}
		}
		?>
		<hr style="margin:2em 0 1.4em">

		<h2 id="kaamase-pf-promote"><?php esc_html_e( 'Promote a job you posted', 'kaamase-core' ); ?></h2>

		<?php if ( 'asked' === $result ) : ?>
			<div class="notice notice-success inline">
				<p>
					<?php esc_html_e( 'Asked. It is waiting in Promotions exactly like a request from anybody else. Ring the employer, then start it there.', 'kaamase-core' ); ?>
					<?php if ( $queue ) : ?>
						<a href="<?php echo esc_url( $queue ); ?>"><?php esc_html_e( 'Open Promotions', 'kaamase-core' ); ?></a>
					<?php endif; ?>
				</p>
			</div>
		<?php elseif ( 'not' === $result ) : ?>
			<div class="notice notice-error inline">
				<p><?php esc_html_e( 'That job cannot be asked about right now. It may have closed, already be waiting or running, or have been taken off the list in the last month.', 'kaamase-core' ); ?></p>
			</div>
		<?php endif; ?>

		<p style="max-width:45em">
			<?php esc_html_e( 'The same request anybody can send from their dashboard, for a job you put up here. It goes into your Promotions queue as waiting, with the number on the job as the one to ring. Nothing is charged here.', 'kaamase-core' ); ?>
		</p>

		<?php foreach ( $live as $job_id ) : ?>
			<p>
				<strong><?php echo esc_html( get_the_title( $job_id ) ); ?></strong> &mdash;
				<?php
				printf(
					/* translators: %s: the date the promotion ends */
					esc_html__( 'promoted until %s.', 'kaamase-core' ),
					esc_html( date_i18n( $date, kaamase_promo_ends( $job_id ) ) )
				);
				?>
			</p>
		<?php endforeach; ?>

		<?php foreach ( $waiting as $job_id ) : ?>
			<p>
				<strong><?php echo esc_html( get_the_title( $job_id ) ); ?></strong> &mdash;
				<?php esc_html_e( 'waiting in Promotions.', 'kaamase-core' ); ?>
			</p>
		<?php endforeach; ?>

		<?php if ( empty( $can ) ) : ?>

			<p class="description">
				<?php
				echo esc_html(
					empty( $jobs )
						? __( 'No open job put up from this screen yet.', 'kaamase-core' )
						: __( 'Nothing else to ask about right now.', 'kaamase-core' )
				);
				?>
			</p>

		<?php else : ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

				<input type="hidden" name="action" value="kaamase_post_for_promo">
				<?php wp_nonce_field( 'kaamase_post_for_promo' ); ?>

				<table class="form-table" role="presentation">

					<tr>
						<th scope="row">
							<label for="kaamase-pf-promo-job"><?php esc_html_e( 'Which job', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<select id="kaamase-pf-promo-job" name="job" required style="max-width:100%">
								<?php foreach ( $can as $job_id ) : ?>
									<option value="<?php echo esc_attr( (string) $job_id ); ?>" <?php selected( $posted, $job_id ); ?>>
										<?php echo esc_html( get_the_title( $job_id ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'How long', 'kaamase-core' ); ?></th>
						<td>
							<?php $first = true; ?>
							<?php foreach ( kaamase_promo_lengths() as $slug => $length ) : ?>
								<label style="margin-right:1.2em">
									<input type="radio" name="want" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $first ); ?>>
									<?php echo esc_html( $length['label'] ); ?>
								</label>
								<?php $first = false; ?>
							<?php endforeach; ?>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-promo-when"><?php esc_html_e( 'Best time to ring them', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<input class="regular-text" type="text" id="kaamase-pf-promo-when" name="when" maxlength="60" autocomplete="off">
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="kaamase-pf-promo-note"><?php esc_html_e( 'Note', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<textarea class="large-text" id="kaamase-pf-promo-note" name="note" rows="3" maxlength="300"></textarea>
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Ask about promoting this', 'kaamase-core' ), 'secondary', 'kaamase-pf-promo-submit' ); ?>

			</form>

		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'kaamase_posted_for_promo_handle' ) ) {
	/**
	 * Put the job in the Promotions queue.
	 *
	 * Writes what kaamase_promo_record() writes, in the same keys and the
	 * same shape, then fires the same action, so nothing downstream can
	 * tell this request from one sent off a dashboard. It does not call
	 * that function because it asks the account that posted the job for a
	 * phone on its own profile, and staff accounts have none. promote.php
	 * is left exactly as it is.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	function kaamase_posted_for_promo_handle() {

		if ( ! current_user_can( 'edit_others_kaamase_jobs' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_post_for_promo' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked above.
		$job_id = isset( $_POST['job'] ) ? absint( $_POST['job'] ) : 0;
		$want   = isset( $_POST['want'] ) ? sanitize_key( wp_unslash( $_POST['want'] ) ) : 'week';
		$note   = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
		$when   = isset( $_POST['when'] ) ? sanitize_text_field( wp_unslash( $_POST['when'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$back = admin_url( 'admin.php?page=kaamase-post-for' );

		/*
		 * Decided here, from the job, never from the form. The list only
		 * offers jobs that can be asked about, but the posted number is
		 * still only a number somebody sent.
		 */
		if ( ! kaamase_posted_for_promo_can( $job_id ) ) {
			wp_safe_redirect( add_query_arg( 'promo', 'not', $back ) . '#kaamase-pf-promote' );
			exit;
		}

		$user_id = (int) get_post_field( 'post_author', $job_id );
		$phone   = (string) kaamase_read_field( $job_id, 'contact_phone' );
		$want    = kaamase_promo_days( $want ) ? $want : 'week';
		$email   = kaamase_posted_for_email( $job_id );

		/*
		 * A job reached by email has no number to ring, so its address
		 * goes at the front of the note, which the Promotions screen
		 * shows under the request.
		 */
		if ( '' !== $email ) {
			$note = sprintf(
				/* translators: %s: the employer's email address */
				__( 'Email: %s', 'kaamase-core' ),
				$email
			) . ( '' !== $note ? ' — ' . $note : '' );
		}

		update_post_meta(
			$job_id,
			KAAMASE_PROMO_ASK_KEY,
			array(
				'at'    => time(),
				'want'  => $want,
				'note'  => mb_substr( $note, 0, 300 ),
				'when'  => mb_substr( $when, 0, 60 ),
				'phone' => mb_substr( sanitize_text_field( $phone ), 0, 24 ),
				'user'  => $user_id,
			)
		);

		update_post_meta( $job_id, KAAMASE_PROMO_STATE_KEY, 'waiting' );

		/** This action is documented in includes/promote.php */
		do_action( 'kaamase_promo_asked', $job_id, $user_id );

		wp_safe_redirect( add_query_arg( 'promo', 'asked', $back ) . '#kaamase-pf-promote' );
		exit;
	}
}
add_action( 'admin_post_kaamase_post_for_promo', 'kaamase_posted_for_promo_handle' );


/* ==========================================================================
   6. HOW WORKERS REACH THEM

   A number, or an email address instead. Chosen when the job goes up and
   changeable afterwards from the same screen, for a typo, for an employer
   who would rather be written to, or the other way round.

   Never both. An office that gives only an email does not want calls to
   a number that is not theirs, and a job with a number and an address
   would leave the worker guessing which one is answered.
   ========================================================================== */

if ( ! function_exists( 'kaamase_posted_for_jobs' ) ) {
	/**
	 * The jobs put up from this screen that are still up.
	 *
	 * Newest first, so the one just posted is at the top of any list.
	 *
	 * @since 1.3.0
	 * @return int[] Job IDs.
	 */
	function kaamase_posted_for_jobs() {

		$ids = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => KAAMASE_META_PREFIX . 'posted_for',
						'value' => '1',
					),
				),
			)
		);

		return array_values( array_filter( array_map( 'intval', (array) $ids ), 'kaamase_job_is_posted_for' ) );
	}
}

if ( ! function_exists( 'kaamase_posted_for_contact_rows' ) ) {
	/**
	 * The phone-or-email choice, as rows of a form table.
	 *
	 * Both inputs are always in the page, so the form still works with
	 * scripts off; the script only hides the one not chosen. Which one is
	 * required is decided on the server, from the choice.
	 *
	 * @since 1.3.0
	 * @param string $prefix Start of the input IDs, unique to the form.
	 * @return void
	 */
	function kaamase_posted_for_contact_rows( $prefix ) {
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'How workers reach them', 'kaamase-core' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'How workers reach them', 'kaamase-core' ); ?></legend>
					<label>
						<input type="radio" name="kaamase_contact_method" value="phone" checked>
						<?php esc_html_e( 'Phone: workers can call and WhatsApp', 'kaamase-core' ); ?>
					</label><br>
					<label>
						<input type="radio" name="kaamase_contact_method" value="email">
						<?php esc_html_e( 'Email only: no phone and no WhatsApp', 'kaamase-core' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Email is for offices and firms that take applications by email and give no number, such as government departments and big companies.', 'kaamase-core' ); ?>
					</p>
				</fieldset>
			</td>
		</tr>

		<tr data-kaamase-pf-for="phone">
			<th scope="row">
				<label for="<?php echo esc_attr( $prefix ); ?>-phone"><?php esc_html_e( 'Their number', 'kaamase-core' ); ?></label>
			</th>
			<td>
				<input class="regular-text" type="tel" id="<?php echo esc_attr( $prefix ); ?>-phone" name="kaamase_contact_phone"
					maxlength="15" placeholder="<?php esc_attr_e( '10 digits', 'kaamase-core' ); ?>">
				<p class="description"><?php esc_html_e( 'What a worker gets when they ask for contact details. Never shown openly on the page.', 'kaamase-core' ); ?></p>
			</td>
		</tr>

		<tr data-kaamase-pf-for="email">
			<th scope="row">
				<label for="<?php echo esc_attr( $prefix ); ?>-email"><?php esc_html_e( 'Their email', 'kaamase-core' ); ?></label>
			</th>
			<td>
				<input class="regular-text" type="email" id="<?php echo esc_attr( $prefix ); ?>-email" name="kaamase_contact_email"
					maxlength="100" placeholder="<?php esc_attr_e( 'jobs@example.com', 'kaamase-core' ); ?>">
				<p class="description"><?php esc_html_e( 'What a worker gets instead of a number, with a button to write to it. Never shown openly on the page.', 'kaamase-core' ); ?></p>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'kaamase_posted_for_contact_input' ) ) {
	/**
	 * Read the choice and the one detail that goes with it.
	 *
	 * The caller has already checked the nonce.
	 *
	 * @since 1.3.0
	 * @return array{method: string, phone: string, email: string, errors: string[]}
	 */
	function kaamase_posted_for_contact_input() {

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked by the caller.
		$method = isset( $_POST['kaamase_contact_method'] ) && 'email' === sanitize_key( wp_unslash( $_POST['kaamase_contact_method'] ) ) ? 'email' : 'phone';
		$phone  = isset( $_POST['kaamase_contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_contact_phone'] ) ) : '';
		$email  = isset( $_POST['kaamase_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['kaamase_contact_email'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$errors = array();

		if ( 'email' === $method ) {

			$phone = '';

			if ( ! is_email( $email ) ) {
				$errors[] = __( 'Put their email address, like jobs@example.com. Without it this job is no use to anybody.', 'kaamase-core' );
			}
		} else {

			$email = '';

			if ( '' === kaamase_sanitize_phone( $phone ) ) {
				$errors[] = __( 'Put their number. Ten digits starting with 6, 7, 8 or 9. Without it this job is no use to anybody.', 'kaamase-core' );
			}
		}

		return array(
			'method' => $method,
			'phone'  => $phone,
			'email'  => $email,
			'errors' => $errors,
		);
	}
}

if ( ! function_exists( 'kaamase_posted_for_contact_save' ) ) {
	/**
	 * Store the number or the address, and clear the other.
	 *
	 * @since 1.3.0
	 * @param int   $job_id  Job ID.
	 * @param array $contact What kaamase_posted_for_contact_input() read.
	 * @return void
	 */
	function kaamase_posted_for_contact_save( $job_id, $contact ) {

		if ( 'email' === $contact['method'] ) {
			kaamase_save_field( $job_id, 'contact_email', $contact['email'] );
			kaamase_save_field( $job_id, 'contact_phone', '' );
			return;
		}

		kaamase_save_field( $job_id, 'contact_phone', $contact['phone'] );
		delete_post_meta( $job_id, KAAMASE_META_PREFIX . 'contact_email' );
	}
}

if ( ! function_exists( 'kaamase_posted_for_contact_now' ) ) {
	/**
	 * What a worker gets today, in a few words, for the list of jobs.
	 *
	 * @since 1.3.0
	 * @param int $job_id Job ID.
	 * @return string
	 */
	function kaamase_posted_for_contact_now( $job_id ) {

		$email = kaamase_posted_for_email( $job_id );

		if ( '' !== $email ) {
			return $email;
		}

		$number = (string) kaamase_read_field( $job_id, 'contact_phone' );

		if ( '' === $number ) {
			return __( 'no number', 'kaamase-core' );
		}

		return function_exists( 'kaamase_format_phone' ) ? kaamase_format_phone( $number ) : $number;
	}
}

if ( ! function_exists( 'kaamase_posted_for_email_posted' ) ) {
	/**
	 * Say which address workers will get, and catch an obvious typo.
	 *
	 * Shown in the notice after the job goes up or the contact changes,
	 * so the address is read once more by the person who typed it. The
	 * typo check is the one sign up uses; it only ever suggests, and the
	 * section below is where to put it right.
	 *
	 * @since 1.3.0
	 * @param int $job_id Job ID.
	 * @return void
	 */
	function kaamase_posted_for_email_posted( $job_id ) {

		$email = kaamase_posted_for_email( $job_id );

		if ( '' === $email ) {
			return;
		}

		$better = function_exists( 'kaamase_email_suggest' ) ? kaamase_email_suggest( $email ) : '';
		?>
		<p>
			<?php
			printf(
				/* translators: %s: the employer's email address */
				esc_html__( 'Workers who ask for contact details get %s, with no phone and no WhatsApp.', 'kaamase-core' ),
				'<strong>' . esc_html( $email ) . '</strong>'
			);
			?>
		</p>
		<?php if ( '' !== $better ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: the email address we think was meant */
					esc_html__( 'Check it: did you mean %s? Change it below if so.', 'kaamase-core' ),
					'<strong>' . esc_html( $better ) . '</strong>'
				);
				?>
				<a href="#kaamase-pf-contact"><?php esc_html_e( 'Change how workers reach a job', 'kaamase-core' ); ?></a>
			</p>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'kaamase_posted_for_contact_section' ) ) {
	/**
	 * Draw the section for changing the contact on a job already up.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_posted_for_contact_section() {

		$jobs = kaamase_posted_for_jobs();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only notice.
		$changed = isset( $_GET['contact'] ) ? absint( $_GET['contact'] ) : 0;

		if ( $changed && ! in_array( $changed, $jobs, true ) ) {
			$changed = 0;
		}
		?>
		<hr style="margin:2em 0 1.4em">

		<h2 id="kaamase-pf-contact"><?php esc_html_e( 'Change how workers reach a job', 'kaamase-core' ); ?></h2>

		<?php if ( $changed ) : ?>
			<div class="notice notice-success inline">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: the job's title */
							__( 'Saved. Workers who ask about "%s" from now on get the new contact.', 'kaamase-core' ),
							get_the_title( $changed )
						)
					);
					?>
				</p>
				<?php kaamase_posted_for_email_posted( $changed ); ?>
			</div>
		<?php endif; ?>

		<p style="max-width:45em">
			<?php esc_html_e( 'For a job you already put up here: correct the number or the email address, or switch between the two. Anybody who asked before keeps what they were given.', 'kaamase-core' ); ?>
		</p>

		<?php if ( empty( $jobs ) ) : ?>

			<p class="description"><?php esc_html_e( 'No open job put up from this screen yet.', 'kaamase-core' ); ?></p>

		<?php else : ?>

			<form class="kaamase-pf-contact" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

				<input type="hidden" name="action" value="kaamase_post_for_contact">
				<?php wp_nonce_field( 'kaamase_post_for_contact' ); ?>

				<table class="form-table" role="presentation">

					<tr>
						<th scope="row">
							<label for="kaamase-pf-change-job"><?php esc_html_e( 'Which job', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<select id="kaamase-pf-change-job" name="job" required style="max-width:100%">
								<?php foreach ( $jobs as $job_id ) : ?>
									<option value="<?php echo esc_attr( (string) $job_id ); ?>" <?php selected( $changed, $job_id ); ?>>
										<?php echo esc_html( get_the_title( $job_id ) . ' — ' . kaamase_posted_for_contact_now( $job_id ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<?php kaamase_posted_for_contact_rows( 'kaamase-pf-change' ); ?>

				</table>

				<?php submit_button( __( 'Save the new contact', 'kaamase-core' ), 'secondary', 'kaamase-pf-contact-submit' ); ?>

			</form>

		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'kaamase_posted_for_contact_handle' ) ) {
	/**
	 * Change the number or the address on a job already up.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_posted_for_contact_handle() {

		if ( ! current_user_can( 'edit_others_kaamase_jobs' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ) );
		}

		check_admin_referer( 'kaamase_post_for_contact' );

		$user_id = get_current_user_id();
		$back    = admin_url( 'admin.php?page=kaamase-post-for' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		$job_id = isset( $_POST['job'] ) ? absint( $_POST['job'] ) : 0;

		/*
		 * Only a job put up from this screen. A member's own job carries
		 * the member's own number and is theirs to change, not ours.
		 */
		if ( ! $job_id || 'kaamase_job' !== get_post_type( $job_id ) || ! kaamase_job_is_posted_for( $job_id ) ) {
			set_transient(
				'kaamase_post_for_' . $user_id,
				array( __( 'Only a job put up from this screen can be changed here.', 'kaamase-core' ) ),
				15 * MINUTE_IN_SECONDS
			);
			wp_safe_redirect( $back );
			exit;
		}

		$contact = kaamase_posted_for_contact_input();

		if ( $contact['errors'] ) {
			set_transient( 'kaamase_post_for_' . $user_id, $contact['errors'], 15 * MINUTE_IN_SECONDS );
			wp_safe_redirect( $back );
			exit;
		}

		kaamase_posted_for_contact_save( $job_id, $contact );

		/*
		 * The line above the job says "number" or "email address", and a
		 * page cache would go on showing the old one to anybody signed
		 * out. Does nothing where LiteSpeed is not installed.
		 */
		clean_post_cache( $job_id );
		do_action( 'litespeed_purge_post', $job_id );

		wp_safe_redirect( add_query_arg( 'contact', $job_id, $back ) . '#kaamase-pf-contact' );
		exit;
	}
}
add_action( 'admin_post_kaamase_post_for_contact', 'kaamase_posted_for_contact_handle' );

if ( ! function_exists( 'kaamase_posted_for_contact_script' ) ) {
	/**
	 * Show only the box that goes with the choice.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	function kaamase_posted_for_contact_script() {
		?>
		<script>
		( function () {
			document.querySelectorAll( 'form.kaamase-pf-contact' ).forEach( function ( form ) {
				function sync() {
					var chosen = form.querySelector( 'input[name="kaamase_contact_method"]:checked' );
					var method = chosen ? chosen.value : 'phone';

					form.querySelectorAll( '[data-kaamase-pf-for]' ).forEach( function ( row ) {
						var on = row.getAttribute( 'data-kaamase-pf-for' ) === method;

						row.style.display = on ? '' : 'none';

						row.querySelectorAll( 'input' ).forEach( function ( input ) {
							input.required = on;
						} );
					} );
				}

				form.addEventListener( 'change', sync );
				sync();
			} );
		}() );
		</script>
		<?php
	}
}
