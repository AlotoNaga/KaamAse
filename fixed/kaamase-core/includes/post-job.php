<?php
/**
 * Post a job.
 *
 * The form an employer fills in, and the most important form on the
 * platform. Everything else exists to make this one produce something
 * a worker will answer.
 *
 * Four decisions that shape it
 * ----------------------------
 *
 * 1. The pay is required. A job with no rate is the single biggest
 *    complaint on every hiring platform in India. Workers do not apply
 *    to them, employers get no calls, and both sides blame the site. It
 *    is one required field and it fixes all of that.
 *
 * 2. The title is optional. Somebody typing on a cracked phone at a work
 *    site should not have to invent a headline. If they leave it blank
 *    the platform writes one from the trade, the count and the district:
 *    2 masons needed in Dimapur. That reads better than most titles a
 *    person would type anyway.
 *
 * 3. A rate below the statutory minimum is refused. Nagaland's minimum
 *    wage has not been revised since 2019 and sits far below what
 *    anybody actually pays, so the floor almost never triggers. When it
 *    does, it is either a typo or somebody trying it on, and both should
 *    be stopped before a worker travels for it.
 *
 * 4. Urgent is limited to one open job at a time. If every listing is
 *    urgent, urgent means nothing within a fortnight, and the tag stops
 *    working for the person who actually needs somebody today.
 *
 * @package KaamaseCore
 * @version 1.4.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WAGE FLOOR
   ========================================================================== */

if ( ! function_exists( 'kaamase_wage_floor' ) ) {
	/**
	 * The lowest rate a job may offer.
	 *
	 * Based on the Nagaland statutory minimum for unskilled work, last
	 * revised 14 June 2019. It is deliberately the unskilled figure
	 * rather than the skilled one, so the floor is a floor and not a
	 * price control: a genuine employer will never come near it.
	 *
	 * Update these if the state revises the rates.
	 *
	 * @since 1.0.0
	 * @param string $unit day, month, hour or job.
	 * @return int Minimum in rupees, or 0 when there is no floor.
	 */
	function kaamase_wage_floor( $unit = 'day' ) {

		$floors = array(
			'day'   => 176,
			'month' => 4576,   // 26 working days at the daily floor.
			'hour'  => 22,     // Eight hour day.
			'job'   => 0,      // A lump sum for a whole job has no daily equivalent.
		);

		/**
		 * Filter the wage floor.
		 *
		 * @since 1.0.0
		 * @param int[]  $floors Minimums keyed by pay unit.
		 * @param string $unit   Unit being checked.
		 */
		$floors = (array) apply_filters( 'kaamase_wage_floors', $floors, $unit );

		return isset( $floors[ $unit ] ) ? absint( $floors[ $unit ] ) : 0;
	}
}

if ( ! function_exists( 'kaamase_has_open_urgent' ) ) {
	/**
	 * Whether this employer already has an urgent job running.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @param int $exclude Job to ignore, when editing.
	 * @return bool
	 */
	function kaamase_has_open_urgent( $user_id, $exclude = 0 ) {

		/*
		 * How many urgent jobs are allowed at once is an allowance now
		 * rather than a fixed one, so a payment plugin can sell more of
		 * them without this function knowing anything about money. With
		 * no payment plugin installed the allowance is the free number
		 * from the Limits screen, which starts at one, so the behaviour
		 * is what it always was.
		 */
		$allowed = function_exists( 'kaamase_allowance' )
			? (int) kaamase_allowance( 'urgent_jobs', $user_id )
			: 1;

		if ( $allowed < 1 ) {
			return true;
		}

		$jobs = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'author'         => absint( $user_id ),
				'post_status'    => 'publish',
				// Enough headroom to count against a raised allowance.
				'posts_per_page' => max( 5, $allowed + 5 ),
				'fields'         => 'ids',
				'exclude'        => array( absint( $exclude ) ),
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => KAAMASE_META_PREFIX . 'urgent',
						'value' => '1',
					),
				),
			)
		);

		$running = 0;

		foreach ( $jobs as $job_id ) {
			if ( kaamase_job_is_open( $job_id ) ) {
				++$running;
			}
		}

		return $running >= $allowed;
	}
}


/* ==========================================================================
   2. THE FORM
   ========================================================================== */

if ( ! function_exists( 'kaamase_post_job_shortcode' ) ) {
	/**
	 * Render the job posting form.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_post_job_shortcode() {

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--action ka-mt-4" href="%3$s">%4$s</a></div></div>',
				esc_html__( 'Register first, it is free', 'kaamase-core' ),
				esc_html__( 'Posting a job takes two minutes once you have an account.', 'kaamase-core' ),
				esc_url( add_query_arg( 'type', 'employer', kaamase_page_url( 'register' ) ) ),
				esc_html__( 'Register as an employer', 'kaamase-core' )
			);
		}

		$user_id = get_current_user_id();

		if ( ! current_user_can( 'create_kaamase_jobs' ) ) {

			/*
			 * A dead end with no door in it used to live here. It told a
			 * worker who also hires that they could not post, and that
			 * they should tell somebody, without saying who or offering
			 * any way to do it. The block below asks instead.
			 *
			 * Guarded on the function existing so the page still renders
			 * something sensible if the module is ever removed.
			 */
			if ( function_exists( 'kaamase_hiring_request_notice' ) ) {
				return kaamase_hiring_request_notice();
			}

			return sprintf(
				'<div class="ka-notice ka-notice--warn"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
				esc_html__( 'This account cannot post jobs', 'kaamase-core' ),
				esc_html__( 'You registered as a worker. If you also hire people, get in touch and we will add hiring to your account.', 'kaamase-core' )
			);
		}

		// Editing an existing job.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

		if ( $job_id && ! current_user_can( 'edit_post', $job_id ) ) {
			$job_id = 0;
		}

		$values = kaamase_job_form_values( $job_id );
		$errors = kaamase_job_errors();

		ob_start();

		if ( ! empty( $errors ) ) {
			echo '<div class="ka-notice ka-notice--error"><div><span class="ka-notice__title">'
				. esc_html__( 'Please fix this', 'kaamase-core' )
				. '</span><ul>';

			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}

			echo '</ul></div></div>';
		}

		if ( ! kaamase_user_is_verified( $user_id ) ) {
			printf(
				'<div class="ka-notice ka-notice--warn"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
				esc_html__( 'Confirm your email to make jobs visible', 'kaamase-core' ),
				esc_html__( 'You can write the job now. It goes live the moment you tap the link in your email.', 'kaamase-core' )
			);
		}
		?>

		<form class="ka-form ka-stack--lg" method="post" action="">

			<?php wp_nonce_field( 'kaamase_post_job', 'kaamase_job_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="post_job">
			<input type="hidden" name="kaamase_job_id" value="<?php echo esc_attr( $job_id ); ?>">

			<h2>
				<?php
				echo $job_id
					? esc_html__( 'Edit this job', 'kaamase-core' )
					: esc_html__( 'Post a job', 'kaamase-core' );
				?>
			</h2>

			<?php
			/* ----------------------------------------------------------
			 * What work
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-job-trade">
					<?php esc_html_e( 'What work do you need done', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-job-trade" name="kaamase_trade" required>
					<option value=""><?php esc_html_e( 'Choose the trade', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_trade_choices() as $group => $trades ) : ?>
						<optgroup label="<?php echo esc_attr( $group ); ?>">
							<?php foreach ( $trades as $slug => $name ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $values['trade'], $slug ); ?>>
									<?php echo esc_html( $name ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-job-count">
					<?php esc_html_e( 'How many workers', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="number" id="ka-job-count" name="kaamase_workers_needed"
					min="1" max="200" inputmode="numeric" required
					value="<?php echo esc_attr( $values['workers_needed'] ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Need a whole team? Put the number and we will show your job to team leaders too.', 'kaamase-core' ); ?>
				</p>
			</div>

			<?php
			/* ----------------------------------------------------------
			 * Pay. Required, and the floor is checked on submit.
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-job-pay">
					<?php esc_html_e( 'What are you paying', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>

				<div class="ka-cluster">
					<input class="ka-input" type="number" id="ka-job-pay" name="kaamase_pay_amount"
						min="1" step="1" inputmode="numeric" required style="flex:1;"
						value="<?php echo esc_attr( $values['pay_amount'] ? $values['pay_amount'] : '' ); ?>"
						placeholder="<?php esc_attr_e( 'Amount in rupees', 'kaamase-core' ); ?>">

					<select class="ka-select" name="kaamase_pay_unit" aria-label="<?php esc_attr_e( 'Pay period', 'kaamase-core' ); ?>" style="flex:1;">
						<option value="day" <?php selected( $values['pay_unit'], 'day' ); ?>><?php esc_html_e( 'per day', 'kaamase-core' ); ?></option>
						<option value="month" <?php selected( $values['pay_unit'], 'month' ); ?>><?php esc_html_e( 'per month', 'kaamase-core' ); ?></option>
						<option value="job" <?php selected( $values['pay_unit'], 'job' ); ?>><?php esc_html_e( 'for the whole job', 'kaamase-core' ); ?></option>
						<option value="hour" <?php selected( $values['pay_unit'], 'hour' ); ?>><?php esc_html_e( 'per hour', 'kaamase-core' ); ?></option>
					</select>
				</div>

				<p class="ka-hint">
					<?php esc_html_e( 'Jobs without a rate get almost no answers. Put a real number even if it is negotiable.', 'kaamase-core' ); ?>
				</p>
			</div>

			<?php
			/* ----------------------------------------------------------
			 * Where
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-job-district">
					<?php esc_html_e( 'District', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-job-district" name="kaamase_district" required>
					<option value=""><?php esc_html_e( 'Choose the district', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_district_choices() as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $values['district'], $slug ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-job-town">
					<?php esc_html_e( 'Town or village', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="text" id="ka-job-town" name="kaamase_town"
					value="<?php echo esc_attr( $values['town'] ); ?>"
					placeholder="<?php esc_attr_e( 'Where the work actually is', 'kaamase-core' ); ?>">
			</div>

			<?php
			/* ----------------------------------------------------------
			 * When
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-job-start">
					<?php esc_html_e( 'When does it start', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="date" id="ka-job-start" name="kaamase_start_date"
					value="<?php echo esc_attr( $values['start_date'] ); ?>"
					min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-job-duration">
					<?php esc_html_e( 'How long is the work', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="text" id="ka-job-duration" name="kaamase_duration" maxlength="60"
					value="<?php echo esc_attr( $values['duration'] ); ?>"
					placeholder="<?php esc_attr_e( 'Two weeks, three months, ongoing', 'kaamase-core' ); ?>">
			</div>

			<?php
			/* ----------------------------------------------------------
			 * What is provided.
			 *
			 * High up rather than buried at the bottom. For work away
			 * from home a worker decides on these as much as on the rate,
			 * and an employer who provides food and stay is throwing away
			 * their best argument by not saying so.
			 * -------------------------------------------------------- */
			?>
			<fieldset class="ka-field">
				<legend class="ka-label"><?php esc_html_e( 'What do you provide', 'kaamase-core' ); ?></legend>

				<label class="ka-check">
					<input type="checkbox" name="kaamase_food" value="1" <?php checked( $values['food_provided'] ); ?>>
					<span><?php esc_html_e( 'Food', 'kaamase-core' ); ?></span>
				</label>

				<label class="ka-check">
					<input type="checkbox" name="kaamase_stay" value="1" <?php checked( $values['stay_provided'] ); ?>>
					<span><?php esc_html_e( 'Somewhere to stay', 'kaamase-core' ); ?></span>
				</label>

				<label class="ka-check">
					<input type="checkbox" name="kaamase_transport" value="1" <?php checked( $values['transport_provided'] ); ?>>
					<span><?php esc_html_e( 'Transport to the site', 'kaamase-core' ); ?></span>
				</label>

				<p class="ka-hint">
					<?php esc_html_e( 'For work away from home these matter as much as the rate. Say so if you provide them.', 'kaamase-core' ); ?>
				</p>
			</fieldset>

			<?php
			/* ----------------------------------------------------------
			 * Detail
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-job-description">
					<?php esc_html_e( 'Anything else they should know', 'kaamase-core' ); ?>
				</label>
				<textarea class="ka-textarea" id="ka-job-description" name="kaamase_description" maxlength="1500"
					placeholder="<?php esc_attr_e( 'What the work involves, what tools they need, who to ask for on the day.', 'kaamase-core' ); ?>"><?php echo esc_textarea( $values['description'] ); ?></textarea>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-job-title">
					<?php esc_html_e( 'Give it a title', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="text" id="ka-job-title" name="kaamase_title" maxlength="90"
					value="<?php echo esc_attr( $values['title'] ); ?>"
					placeholder="<?php esc_attr_e( 'Leave blank and we will write one', 'kaamase-core' ); ?>">
			</div>

			<?php
			/* ----------------------------------------------------------
			 * Urgent
			 * -------------------------------------------------------- */
			?>
			<?php $urgent_taken = kaamase_has_open_urgent( $user_id, $job_id ); ?>

			<div class="ka-field">
				<label class="ka-check">
					<input type="checkbox" name="kaamase_urgent" value="1"
						<?php checked( $values['urgent'] ); ?>
						<?php disabled( $urgent_taken && ! $values['urgent'] ); ?>>
					<span><?php esc_html_e( 'I need somebody today or tomorrow', 'kaamase-core' ); ?></span>
				</label>

				<p class="ka-hint">
					<?php
					echo $urgent_taken && ! $values['urgent']
						? esc_html__( 'You already have one urgent job running. Close it first, or leave this one normal. One urgent job at a time keeps the tag worth something.', 'kaamase-core' )
						: esc_html__( 'Urgent jobs go to the top and close after three days. One at a time.', 'kaamase-core' );
					?>
				</p>
			</div>

			<?php
			/* ----------------------------------------------------------
			 * Contact
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-job-phone">
					<?php esc_html_e( 'Number for this job', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="tel" id="ka-job-phone" name="kaamase_contact_phone"
					inputmode="numeric" maxlength="15"
					value="<?php echo esc_attr( $values['contact_phone'] ); ?>"
					placeholder="<?php esc_attr_e( 'Leave blank to use your account number', 'kaamase-core' ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Never shown on the site. Workers reach you through Kaam Ase.', 'kaamase-core' ); ?>
				</p>
			</div>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
				<?php
				echo $job_id
					? esc_html__( 'Save changes', 'kaamase-core' )
					: esc_html__( 'Post this job', 'kaamase-core' );
				?>
			</button>

			<p class="ka-small ka-mute">
				<?php esc_html_e( 'Free to post. Open for three weeks, then it closes on its own so nobody answers a job that is already filled.', 'kaamase-core' ); ?>
			</p>

		</form>
		<?php

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_post_job', 'kaamase_post_job_shortcode' );

if ( ! function_exists( 'kaamase_job_form_values' ) ) {
	/**
	 * Values to put in the form.
	 *
	 * A rejected submission first, then an existing job, then defaults.
	 * Losing a filled in form because one field was wrong is how a job
	 * post gets abandoned halfway.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job being edited, or 0.
	 * @return array Form values.
	 */
	function kaamase_job_form_values( $job_id = 0 ) {

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

		$stored = get_transient( kaamase_job_form_key() );

		if ( is_array( $stored ) ) {
			delete_transient( kaamase_job_form_key() );
			return wp_parse_args( $stored, $defaults );
		}

		if ( ! $job_id ) {
			return $defaults;
		}

		$post = get_post( $job_id );

		if ( ! $post ) {
			return $defaults;
		}

		$trades = wp_get_object_terms( $job_id, 'kaamase_trade', array( 'fields' => 'slugs' ) );

		return array(
			'title'              => $post->post_title,
			'description'        => $post->post_content,
			'trade'              => ! is_wp_error( $trades ) && ! empty( $trades ) ? $trades[0] : '',
			'district'           => (string) kaamase_read_field( $job_id, 'district' ),
			'town'               => (string) kaamase_read_field( $job_id, 'town' ),
			'pay_amount'         => absint( kaamase_read_field( $job_id, 'pay_amount' ) ),
			'pay_unit'           => (string) kaamase_read_field( $job_id, 'pay_unit' ),
			'workers_needed'     => absint( kaamase_read_field( $job_id, 'workers_needed' ) ),
			'start_date'         => (string) kaamase_read_field( $job_id, 'start_date' ),
			'duration'           => (string) kaamase_read_field( $job_id, 'duration' ),
			'urgent'             => (bool) kaamase_read_field( $job_id, 'urgent' ),
			'food_provided'      => (bool) kaamase_read_field( $job_id, 'food_provided' ),
			'stay_provided'      => (bool) kaamase_read_field( $job_id, 'stay_provided' ),
			'transport_provided' => (bool) kaamase_read_field( $job_id, 'transport_provided' ),
			'contact_phone'      => (string) kaamase_read_field( $job_id, 'contact_phone' ),
		);
	}
}


/* ==========================================================================
   3. SAVING
   ========================================================================== */

if ( ! function_exists( 'kaamase_handle_post_job' ) ) {
	/**
	 * Validate and save a job.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_post_job() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'post_job' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_job_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_job_nonce'] ) ), 'kaamase_post_job' )
		) {
			return;
		}

		if ( ! current_user_can( 'create_kaamase_jobs' ) ) {
			return;
		}

		$user_id = get_current_user_id();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$job_id      = isset( $_POST['kaamase_job_id'] ) ? absint( $_POST['kaamase_job_id'] ) : 0;
		$title       = isset( $_POST['kaamase_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_title'] ) ) : '';
		$description = isset( $_POST['kaamase_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kaamase_description'] ) ) : '';
		$trade       = isset( $_POST['kaamase_trade'] ) ? kaamase_match_trade( sanitize_text_field( wp_unslash( $_POST['kaamase_trade'] ) ) ) : '';
		$district    = isset( $_POST['kaamase_district'] ) ? kaamase_match_district( sanitize_text_field( wp_unslash( $_POST['kaamase_district'] ) ) ) : '';
		$town        = isset( $_POST['kaamase_town'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_town'] ) ) : '';
		$pay         = isset( $_POST['kaamase_pay_amount'] ) ? absint( $_POST['kaamase_pay_amount'] ) : 0;
		$unit        = isset( $_POST['kaamase_pay_unit'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_pay_unit'] ) ) : 'day';
		$needed      = isset( $_POST['kaamase_workers_needed'] ) ? absint( $_POST['kaamase_workers_needed'] ) : 1;
		$start       = isset( $_POST['kaamase_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_start_date'] ) ) : '';
		$duration    = isset( $_POST['kaamase_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_duration'] ) ) : '';
		$phone_in    = isset( $_POST['kaamase_contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_contact_phone'] ) ) : '';
		$urgent      = ! empty( $_POST['kaamase_urgent'] );
		$food        = ! empty( $_POST['kaamase_food'] );
		$stay        = ! empty( $_POST['kaamase_stay'] );
		$transport   = ! empty( $_POST['kaamase_transport'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Editing something that is not theirs.
		if ( $job_id && ! current_user_can( 'edit_post', $job_id ) ) {
			return;
		}

		$values = array(
			'title'              => $title,
			'description'        => $description,
			'trade'              => $trade,
			'district'           => $district,
			'town'               => $town,
			'pay_amount'         => $pay,
			'pay_unit'           => $unit,
			'workers_needed'     => $needed,
			'start_date'         => $start,
			'duration'           => $duration,
			'urgent'             => $urgent,
			'food_provided'      => $food,
			'stay_provided'      => $stay,
			'transport_provided' => $transport,
			'contact_phone'      => $phone_in,
		);

		set_transient( kaamase_job_form_key(), $values, 15 * MINUTE_IN_SECONDS );

		/* ---- Validate and save ---- */

		/*
		 * Both the rules and the write live in services.php.
		 *
		 * This handler used to carry its own copy of the wage floor, the
		 * urgent limit and the daily cap. The phone app needs every one of
		 * those enforced identically, and two copies of a rule is two
		 * copies that drift. What is left here is transport: read the form,
		 * hand it over, then redirect somewhere sensible.
		 */
		$result = kaamase_save_job( $values, $user_id, $job_id );

		if ( is_wp_error( $result ) ) {

			$data     = $result->get_error_data();
			$messages = isset( $data['messages'] ) ? (array) $data['messages'] : array( $result->get_error_message() );

			set_transient( kaamase_job_form_key() . '_err', $messages, 15 * MINUTE_IN_SECONDS );
			wp_safe_redirect( kaamase_page_url( 'post_job' ) . ( $job_id ? '?edit=' . $job_id : '' ) );
			exit;
		}

		$job_id = (int) $result;

		delete_transient( kaamase_job_form_key() );
		delete_transient( kaamase_job_form_key() . '_err' );

		/*
		 * A held job goes to the dashboard, not to its own page.
		 *
		 * Pending is a protected status. Whether the author can open
		 * their own pending post depends on capabilities that are easy
		 * to get subtly wrong, and the failure mode is a 404 landing
		 * immediately after pressing Post a job. That reads as the site
		 * eating the job. The dashboard always works and now lists the
		 * job with its status on it.
		 */
		if ( 'pending' === get_post_status( $job_id ) ) {
			wp_safe_redirect( add_query_arg( 'held', '1', kaamase_page_url( 'dashboard' ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'posted', '1', get_permalink( $job_id ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_post_job' );

if ( ! function_exists( 'kaamase_generate_job_title' ) ) {
	/**
	 * Write a title when the employer left it blank.
	 *
	 * Produces things like 2 masons needed in Dimapur, which is clearer
	 * than most titles somebody would type on a phone at a work site, and
	 * consistent enough to be worth indexing.
	 *
	 * @since 1.0.0
	 * @param string $trade    Trade slug.
	 * @param int    $needed   How many workers.
	 * @param string $district District slug.
	 * @return string Title.
	 */
	function kaamase_generate_job_title( $trade, $needed, $district ) {

		$term = get_term_by( 'slug', $trade, 'kaamase_trade' );
		$name = $term instanceof WP_Term ? $term->name : __( 'Workers', 'kaamase-core' );
		$name = ( $needed > 1 ) ? kaamase_pluralise( $name ) : $name;
		$town = kaamase_district_name( $district );

		if ( $town ) {
			return sprintf(
				/* translators: 1: number of workers, 2: trade name, 3: district */
				__( '%1$s %2$s needed in %3$s', 'kaamase-core' ),
				number_format_i18n( $needed ),
				strtolower( $name ),
				$town
			);
		}

		return sprintf(
			/* translators: 1: number of workers, 2: trade name */
			__( '%1$s %2$s needed', 'kaamase-core' ),
			number_format_i18n( $needed ),
			strtolower( $name )
		);
	}
}

if ( ! function_exists( 'kaamase_pluralise' ) ) {
	/**
	 * Rough English pluralisation for a trade name.
	 *
	 * Rough is fine. This is a generated title, not a document, and the
	 * alternative is a plural form stored against every trade forever.
	 *
	 * @since 1.0.0
	 * @param string $word Singular.
	 * @return string Plural.
	 */
	function kaamase_pluralise( $word ) {

		$word = trim( (string) $word );

		if ( '' === $word ) {
			return $word;
		}

		$last = strtolower( substr( $word, -1 ) );

		if ( 'y' === $last && ! preg_match( '/[aeiou]y$/i', $word ) ) {
			return substr( $word, 0, -1 ) . 'ies';
		}

		if ( in_array( $last, array( 's', 'x', 'z' ), true ) || preg_match( '/(ch|sh)$/i', $word ) ) {
			return $word . 'es';
		}

		return $word . 's';
	}
}


/* ==========================================================================
   4. STATE AND LIMITS
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_form_key' ) ) {
	/**
	 * Transient key for this user's in progress form.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_job_form_key() {
		return 'kaamase_jobform_' . get_current_user_id();
	}
}

if ( ! function_exists( 'kaamase_job_errors' ) ) {
	/**
	 * Read and clear errors from a rejected submission.
	 *
	 * @since 1.0.0
	 * @return string[] Messages.
	 */
	function kaamase_job_errors() {

		$key    = kaamase_job_form_key() . '_err';
		$errors = get_transient( $key );

		if ( $errors ) {
			delete_transient( $key );
		}

		return is_array( $errors ) ? $errors : array();
	}
}

if ( ! function_exists( 'kaamase_job_rate_ok' ) ) {
	/**
	 * Whether this employer may post another job today.
	 *
	 * Ten. A contractor running several sites genuinely posts a few in a
	 * morning. Nobody legitimately posts fifty.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function kaamase_job_rate_ok( $user_id ) {

		/*
		 * Ten was a number in the code. It is an allowance now, so it
		 * can be raised on the Limits screen and sold more of, without
		 * this function knowing anything about either.
		 */
		$allowed = function_exists( 'kaamase_allowance' )
			? (int) kaamase_allowance( 'jobs_per_day', $user_id )
			: 10;

		return (int) kaamase_rate_value( kaamase_job_rate_key( $user_id ), 0 ) < $allowed;
	}
}

if ( ! function_exists( 'kaamase_job_rate_key' ) ) {
	/**
	 * Transient key for today's job count.
	 *
	 * @since 1.2.0
	 * @param int $user_id User ID.
	 * @return string
	 */
	function kaamase_job_rate_key( $user_id ) {

		// Site timezone, not UTC. See kaamase_rate_day().
		return 'kaamase_jobrate_' . absint( $user_id ) . '_' . kaamase_rate_day();
	}
}

if ( ! function_exists( 'kaamase_job_rate_use' ) ) {
	/**
	 * Count a job that actually went up.
	 *
	 * Called after the save succeeds, never during validation.
	 *
	 * @since 1.2.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	function kaamase_job_rate_use( $user_id ) {

		// Durable, so clearing the cache does not reset the day's allowance.
		kaamase_rate_bump( kaamase_job_rate_key( $user_id ), DAY_IN_SECONDS );
	}
}


/* ==========================================================================
   5. AFTER POSTING
   ========================================================================== */

/**
 * Confirm a new job on its own page.
 *
 * Tells the employer what happens next rather than just saying saved.
 * The most common worry after posting is whether anything is actually
 * going to happen.
 *
 * @since 1.0.0
 * @param string $content Post content.
 * @return string Filtered content.
 */
function kaamase_job_posted_notice( $content ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( empty( $_GET['posted'] ) || ! is_singular( 'kaamase_job' ) ) {
		return $content;
	}

	$job_id    = get_the_ID();
	$published = 'publish' === get_post_status( $job_id );

	$notice = sprintf(
		'<div class="ka-notice ka-notice--ok"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
		$published
			? esc_html__( 'Your job is live', 'kaamase-core' )
			: esc_html__( 'Your job is saved but not live yet', 'kaamase-core' ),
		$published
			? esc_html__( 'Workers in this district can see it now. It stays open for three weeks, then closes on its own. Share the link on WhatsApp if you want it seen faster.', 'kaamase-core' )
			: kaamase_job_pending_reason( $job_id )
	);

	return $notice . $content;
}
add_filter( 'the_content', 'kaamase_job_posted_notice', 4 );

/**
 * Why a job is not live yet, in words that fit the actual reason.
 *
 * There are two ways to end up unpublished and they need different
 * sentences. An unverified account is waiting on the person themselves
 * and can be fixed in ten seconds. A held job is waiting on us, and
 * telling somebody to check their email for a link that does not exist
 * is how a support message gets written.
 *
 * @since 1.2.0
 * @param int $job_id Job ID.
 * @return string Escaped sentence.
 */
function kaamase_job_pending_reason( $job_id ) {

	$held = defined( 'KAAMASE_SCREEN_REASON_KEY' )
		? get_post_meta( $job_id, KAAMASE_SCREEN_REASON_KEY, true )
		: '';

	if ( ! empty( $held ) ) {
		return esc_html__( 'Somebody is reading it before it goes out to workers. This is usually done within a few hours, and every job on Kaam Ase gets the same check. Nothing more is needed from you.', 'kaamase-core' );
	}

	return esc_html__( 'Tap the link in your email and it goes live straight away.', 'kaamase-core' );
}