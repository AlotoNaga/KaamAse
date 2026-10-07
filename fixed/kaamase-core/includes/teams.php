<?php
/**
 * Teams.
 *
 * The post type existed. The archive existed. The card renderer existed.
 * The footer and the menu both linked to /teams/. Nothing anywhere on the
 * platform could create one, so that page was permanently empty and every
 * link to it led somewhere that looked broken.
 *
 * This is the missing form.
 *
 * Why teams are worth the file
 * ---------------------------
 * A contractor does not hire one mason. He hires a mason who brings four
 * helpers, and he wants one phone call, not five. Every national hiring
 * app models individuals only, which is why contractors here still do
 * their hiring through a man they know rather than through an app.
 *
 * A team is a leader, a headcount and a trade. That is the whole object,
 * and modelling it is most of the reason this platform has a chance
 * against a better funded competitor.
 *
 * One team per worker
 * -------------------
 * Same rule as profiles, for the same reason. Two teams belonging to one
 * person split their ratings and their hire history across both, and
 * neither ends up worth reading.
 *
 * @package KaamaseCore
 * @version 1.2.1
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. LOOKUP
   ========================================================================== */

if ( ! function_exists( 'kaamase_get_user_team' ) ) {
	/**
	 * The team a user leads, if any.
	 *
	 * @since 1.2.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return int Post ID, or 0.
	 */
	function kaamase_get_user_team( $user_id = 0 ) {
		return kaamase_get_user_profile( $user_id, 'kaamase_gang' );
	}
}


/* ==========================================================================
   2. THE FORM
   ========================================================================== */

if ( ! function_exists( 'kaamase_team_shortcode' ) ) {
	/**
	 * Render the team form.
	 *
	 * @since 1.2.0
	 * @return string Markup.
	 */
	function kaamase_team_shortcode() {

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--action ka-mt-4" href="%3$s">%4$s</a></div></div>',
				esc_html__( 'Register first, it is free', 'kaamase-core' ),
				esc_html__( 'Listing a team takes a couple of minutes once you have a worker account.', 'kaamase-core' ),
				esc_url( add_query_arg( 'type', 'worker', kaamase_page_url( 'register' ) ) ),
				esc_html__( 'Make a free worker profile', 'kaamase-core' )
			);
		}

		if ( ! current_user_can( 'create_kaamase_gangs' ) ) {
			return sprintf(
				'<div class="ka-notice ka-notice--warn"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
				esc_html__( 'This account cannot list a team', 'kaamase-core' ),
				esc_html__( 'Teams are listed by the worker who leads them. If you hire teams rather than lead one, post a job instead and say how many workers you need.', 'kaamase-core' )
			);
		}

		$user_id = get_current_user_id();
		$team_id = kaamase_get_user_team( $user_id );
		$errors  = kaamase_team_errors();

		$values = kaamase_team_form_values( $team_id );

		ob_start();

		// Same confirmation messages as the dashboard, read from the URL.
		if ( function_exists( 'kaamase_dashboard_flash' ) ) {
			echo kaamase_dashboard_flash(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! empty( $errors ) ) {
			echo '<div class="ka-notice ka-notice--error"><div><span class="ka-notice__title">'
				. esc_html__( 'Please fix this', 'kaamase-core' )
				. '</span><ul>';

			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}

			echo '</ul></div></div>';
		}

		if ( $team_id && 'publish' === get_post_status( $team_id ) ) {
			printf(
				'<div class="ka-notice ka-notice--ok"><div><span class="ka-notice__title">%1$s</span><p><a href="%2$s">%3$s</a></p></div></div>',
				esc_html__( 'Your team is listed', 'kaamase-core' ),
				esc_url( (string) get_permalink( $team_id ) ),
				esc_html__( 'See how a contractor sees it', 'kaamase-core' )
			);
		}
		?>

		<form class="ka-form ka-stack--lg" method="post" action="" enctype="multipart/form-data">

			<?php wp_nonce_field( 'kaamase_team', 'kaamase_team_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="save_team">
			<input type="hidden" name="kaamase_team_id" value="<?php echo esc_attr( $team_id ); ?>">

			<h2>
				<?php
				echo $team_id
					? esc_html__( 'My team', 'kaamase-core' )
					: esc_html__( 'List your team', 'kaamase-core' );
				?>
			</h2>

			<?php if ( ! $team_id ) : ?>
				<p class="ka-soft">
					<?php esc_html_e( 'If you bring other workers with you, list the team as well as yourself. Contractors search for teams when they need a whole job covered, and one call to you is easier for them than five calls to five people.', 'kaamase-core' ); ?>
				</p>
			<?php endif; ?>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-name">
					<?php esc_html_e( 'What is the team called', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="text" id="ka-team-name" name="kaamase_name" required maxlength="90"
					value="<?php echo esc_attr( $values['name'] ); ?>"
					placeholder="<?php esc_attr_e( 'For example: Imliakum mason team', 'kaamase-core' ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Your own name works perfectly well. Contractors remember people, not brands.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-size">
					<?php esc_html_e( 'How many workers, including you', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="number" id="ka-team-size" name="kaamase_headcount"
					min="2" max="200" inputmode="numeric" required
					value="<?php echo esc_attr( $values['headcount'] ? $values['headcount'] : '' ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'This is the first thing a contractor looks at. Put the number that actually turns up.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-leader">
					<?php esc_html_e( 'Who leads it', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="text" id="ka-team-leader" name="kaamase_leader_name" maxlength="90"
					value="<?php echo esc_attr( $values['leader_name'] ); ?>"
					placeholder="<?php esc_attr_e( 'Leave blank if it is you', 'kaamase-core' ); ?>">
			</div>

			<fieldset class="ka-field">
				<legend class="ka-label">
					<?php esc_html_e( 'What work does the team do', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</legend>

				<p class="ka-hint ka-mb-4">
					<?php esc_html_e( 'Tick everything the team can cover between them.', 'kaamase-core' ); ?>
				</p>

				<?php
				/*
				 * Without the professional categories, which belong to
				 * professional profiles. Any already ticked stay on offer,
				 * so saving this form never drops one. See
				 * professional-profiles.php.
				 */
				$kaamase_team_trades = function_exists( 'kaamase_prof_worker_trade_choices' )
					? kaamase_prof_worker_trade_choices( $values['trades'] )
					: kaamase_trade_choices();
				?>
				<?php foreach ( $kaamase_team_trades as $group => $trades ) : ?>
					<p class="ka-small ka-bold ka-mt-4"><?php echo esc_html( $group ); ?></p>
					<?php foreach ( $trades as $slug => $name ) : ?>
						<label class="ka-check">
							<input type="checkbox" name="kaamase_trades[]" value="<?php echo esc_attr( $slug ); ?>"
								<?php checked( in_array( $slug, $values['trades'], true ) ); ?>>
							<span><?php echo esc_html( $name ); ?></span>
						</label>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</fieldset>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-district">
					<?php esc_html_e( 'District', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-team-district" name="kaamase_district" required>
					<option value=""><?php esc_html_e( 'Choose your district', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_district_choices() as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $values['district'], $slug ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-town"><?php esc_html_e( 'Town or village', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="text" id="ka-team-town" name="kaamase_town" maxlength="60"
					value="<?php echo esc_attr( $values['town'] ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-rate">
					<?php esc_html_e( 'Day rate for the team', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="number" id="ka-team-rate" name="kaamase_day_rate" min="0" step="50"
					inputmode="numeric"
					value="<?php echo esc_attr( $values['day_rate'] ? $values['day_rate'] : '' ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Per worker per day is the usual way to quote it. Say so in the description if you mean something else.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-radius"><?php esc_html_e( 'How far will the team travel', 'kaamase-core' ); ?></label>
				<select class="ka-select" id="ka-team-radius" name="kaamase_travel_radius">
					<?php
					$kaamase_radius_options = array(
						'town'     => __( 'Only around our town', 'kaamase-core' ),
						'district' => __( 'Anywhere in our district', 'kaamase-core' ),
						'state'    => __( 'Anywhere in Nagaland', 'kaamase-core' ),
					);

					foreach ( $kaamase_radius_options as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $values['travel_radius'], $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-phone">
					<?php esc_html_e( 'Number for the team', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="tel" id="ka-team-phone" name="kaamase_phone" required
					inputmode="numeric" maxlength="15"
					value="<?php echo esc_attr( $values['phone'] ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Never shown on the site. Contractors reach you through Kaam Ase and you can see who asked.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-photo"><?php esc_html_e( 'A photo of the team', 'kaamase-core' ); ?></label>

				<?php if ( $team_id && has_post_thumbnail( $team_id ) ) : ?>
					<div class="ka-cluster ka-mb-4">
						<?php echo kaamase_avatar( $team_id, 'kaamase-avatar', 'ka-avatar--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<label class="ka-check">
							<input type="checkbox" name="kaamase_remove_photo" value="1">
							<span><?php esc_html_e( 'Remove this photo', 'kaamase-core' ); ?></span>
						</label>
					</div>
				<?php endif; ?>

				<input class="ka-input" type="file" id="ka-team-photo" name="kaamase_photo"
					accept="image/jpeg,image/png,image/webp">

				<p class="ka-hint">
					<?php esc_html_e( 'A picture of the team on a site is worth more than anything you can write. We remove the hidden location information phones save inside photos.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-team-about"><?php esc_html_e( 'What the team has built', 'kaamase-core' ); ?></label>
				<textarea class="ka-textarea" id="ka-team-about" name="kaamase_about" maxlength="1000"
					placeholder="<?php esc_attr_e( 'Buildings you have worked on, how long you have worked together, tools you bring.', 'kaamase-core' ); ?>"><?php echo esc_textarea( $values['about'] ); ?></textarea>
			</div>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
				<?php
				echo $team_id
					? esc_html__( 'Save my team', 'kaamase-core' )
					: esc_html__( 'List my team', 'kaamase-core' );
				?>
			</button>

		</form>

		<?php if ( $team_id ) : ?>
			<form method="post" action="" class="ka-mt-6">
				<?php wp_nonce_field( 'kaamase_delete_team', 'kaamase_delete_team_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="delete_team">
				<button class="ka-btn ka-btn--ghost ka-btn--sm" type="submit"
					data-ka-confirm="<?php esc_attr_e( 'Take the team listing down? Your own worker profile stays.', 'kaamase-core' ); ?>">
					<?php esc_html_e( 'Take my team listing down', 'kaamase-core' ); ?>
				</button>
			</form>
		<?php endif; ?>

		<?php
		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_team', 'kaamase_team_shortcode' );

if ( ! function_exists( 'kaamase_team_form_values' ) ) {
	/**
	 * Values to put in the team form.
	 *
	 * A rejected submission first, then the stored team, then defaults.
	 *
	 * @since 1.2.0
	 * @param int $team_id Team ID, or 0.
	 * @return array Form values.
	 */
	function kaamase_team_form_values( $team_id = 0 ) {

		$defaults = array(
			'name'          => '',
			'headcount'     => 0,
			'leader_name'   => '',
			'trades'        => array(),
			'district'      => '',
			'town'          => '',
			'day_rate'      => 0,
			'travel_radius' => 'district',
			'phone'         => '',
			'about'         => '',
		);

		$stored = get_transient( kaamase_team_form_key() );

		if ( is_array( $stored ) ) {
			delete_transient( kaamase_team_form_key() );
			return wp_parse_args( $stored, $defaults );
		}

		if ( ! $team_id ) {

			/*
			 * A first time team almost always shares the leader's own
			 * district, town and number. Prefilling from the worker
			 * profile removes three fields from the job of listing one.
			 */
			$profile = kaamase_get_user_profile( get_current_user_id(), 'kaamase_worker' );

			if ( $profile ) {
				$defaults['district'] = (string) kaamase_read_field( $profile, 'district' );
				$defaults['town']     = (string) kaamase_read_field( $profile, 'town' );
				$defaults['phone']    = (string) kaamase_read_field( $profile, 'phone' );
			}

			return $defaults;
		}

		$trades = wp_get_object_terms( $team_id, 'kaamase_trade', array( 'fields' => 'slugs' ) );

		return array(
			'name'          => get_the_title( $team_id ),
			'headcount'     => absint( kaamase_read_field( $team_id, 'headcount' ) ),
			'leader_name'   => (string) kaamase_read_field( $team_id, 'leader_name' ),
			'trades'        => is_wp_error( $trades ) ? array() : $trades,
			'district'      => (string) kaamase_read_field( $team_id, 'district' ),
			'town'          => (string) kaamase_read_field( $team_id, 'town' ),
			'day_rate'      => absint( kaamase_read_field( $team_id, 'day_rate' ) ),
			'travel_radius' => (string) kaamase_read_field( $team_id, 'travel_radius' ),
			'phone'         => (string) kaamase_read_field( $team_id, 'phone' ),
			'about'         => (string) get_post_field( 'post_content', $team_id ),
		);
	}
}


/* ==========================================================================
   3. SAVING
   ========================================================================== */

if ( ! function_exists( 'kaamase_handle_team_save' ) ) {
	/**
	 * Validate and save a team.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	function kaamase_handle_team_save() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'save_team' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_team_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_team_nonce'] ) ), 'kaamase_team' )
		) {
			return;
		}

		if ( ! current_user_can( 'create_kaamase_gangs' ) ) {
			return;
		}

		$user_id = get_current_user_id();

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$team_id   = isset( $_POST['kaamase_team_id'] ) ? absint( $_POST['kaamase_team_id'] ) : 0;
		$name      = isset( $_POST['kaamase_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_name'] ) ) : '';
		$headcount = isset( $_POST['kaamase_headcount'] ) ? absint( $_POST['kaamase_headcount'] ) : 0;
		$leader    = isset( $_POST['kaamase_leader_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_leader_name'] ) ) : '';
		$trades    = isset( $_POST['kaamase_trades'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['kaamase_trades'] ) ) : array();
		$district  = isset( $_POST['kaamase_district'] ) ? kaamase_match_district( sanitize_text_field( wp_unslash( $_POST['kaamase_district'] ) ) ) : '';
		$town      = isset( $_POST['kaamase_town'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_town'] ) ) : '';
		$rate      = isset( $_POST['kaamase_day_rate'] ) ? absint( $_POST['kaamase_day_rate'] ) : 0;
		$radius    = isset( $_POST['kaamase_travel_radius'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_travel_radius'] ) ) : 'district';
		$phone_in  = isset( $_POST['kaamase_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_phone'] ) ) : '';
		$about     = isset( $_POST['kaamase_about'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kaamase_about'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Editing something that is not theirs.
		if ( $team_id && ! current_user_can( 'edit_post', $team_id ) ) {
			return;
		}

		/*
		 * One team per person. If they already have one, edit that rather
		 * than creating a second, whatever the form said.
		 */
		$existing = kaamase_get_user_team( $user_id );

		if ( ! $team_id && $existing ) {
			$team_id = $existing;
		}

		$phone = kaamase_sanitize_phone( $phone_in );

		set_transient(
			kaamase_team_form_key(),
			array(
				'name'          => $name,
				'headcount'     => $headcount,
				'leader_name'   => $leader,
				'trades'        => $trades,
				'district'      => $district,
				'town'          => $town,
				'day_rate'      => $rate,
				'travel_radius' => $radius,
				'phone'         => $phone_in,
				'about'         => $about,
			),
			15 * MINUTE_IN_SECONDS
		);

		/* ---- Validate and save ---- */

		// The rules live in services.php, shared with the phone app.
		$result = kaamase_save_team(
			array(
				'name'          => $name,
				'headcount'     => $headcount,
				'leader_name'   => $leader,
				'trades'        => $trades,
				'district'      => $district,
				'town'          => $town,
				'day_rate'      => $rate,
				'travel_radius' => $radius,
				'phone'         => $phone_in,
				'about'         => $about,
			),
			$user_id,
			$team_id
		);

		if ( is_wp_error( $result ) ) {

			$data     = $result->get_error_data();
			$messages = isset( $data['messages'] ) ? (array) $data['messages'] : array( $result->get_error_message() );

			set_transient( kaamase_team_form_key() . '_err', $messages, 15 * MINUTE_IN_SECONDS );
			wp_safe_redirect( kaamase_page_url( 'team' ) );
			exit;
		}

		$team_id = (int) $result;

		if ( function_exists( 'kaamase_handle_photo' ) ) {
			kaamase_handle_photo( $team_id );
		}

		delete_transient( kaamase_team_form_key() );
		delete_transient( kaamase_team_form_key() . '_err' );

		/**
		 * Fires after a team is saved.
		 *
		 * @since 1.2.0
		 * @param int $team_id Team ID.
		 * @param int $user_id Owner.
		 */
		do_action( 'kaamase_team_saved', $team_id, $user_id );

		wp_safe_redirect( add_query_arg( 'saved', 'team', kaamase_page_url( 'team' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_team_save' );

if ( ! function_exists( 'kaamase_handle_team_delete' ) ) {
	/**
	 * Take a team listing down.
	 *
	 * Trashed rather than deleted outright, because a team carries
	 * ratings and a hire history that belong partly to the contractors
	 * who left them.
	 *
	 * @since 1.2.0
	 * @return void
	 */
	function kaamase_handle_team_delete() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'delete_team' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_delete_team_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_delete_team_nonce'] ) ), 'kaamase_delete_team' )
		) {
			return;
		}

		$team_id = kaamase_get_user_team( get_current_user_id() );

		if ( $team_id && current_user_can( 'edit_post', $team_id ) ) {
			wp_trash_post( $team_id );
		}

		wp_safe_redirect( add_query_arg( 'saved', 'teamgone', kaamase_page_url( 'team' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_team_delete' );


/* ==========================================================================
   4. FORM STATE
   ========================================================================== */

if ( ! function_exists( 'kaamase_team_form_key' ) ) {
	/**
	 * Transient key for this user's in progress team form.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	function kaamase_team_form_key() {
		return 'kaamase_teamform_' . get_current_user_id();
	}
}

if ( ! function_exists( 'kaamase_team_errors' ) ) {
	/**
	 * Read and clear team form errors.
	 *
	 * @since 1.2.0
	 * @return string[] Messages.
	 */
	function kaamase_team_errors() {

		$key    = kaamase_team_form_key() . '_err';
		$errors = get_transient( $key );

		if ( $errors ) {
			delete_transient( $key );
		}

		return is_array( $errors ) ? $errors : array();
	}
}


/* ==========================================================================
   5. DASHBOARD ENTRY POINT

   A form nobody can find is a form nobody fills in.
   ========================================================================== */

if ( ! function_exists( 'kaamase_team_dashboard_card' ) ) {
	/**
	 * Offer the team listing to a worker who has not made one.
	 *
	 * @since 1.2.0
	 * @param int    $user_id User ID.
	 * @param int    $profile Profile post ID.
	 * @param string $type    worker or employer.
	 * @return void
	 */
	function kaamase_team_dashboard_card( $user_id, $profile, $type ) {

		unset( $profile );

		if ( 'worker' !== $type || ! current_user_can( 'create_kaamase_gangs' ) ) {
			return;
		}

		$team_id = kaamase_get_user_team( $user_id );
		?>
		<section class="ka-card ka-card--flat ka-mt-6">

			<h2 class="ka-text-lg">
				<?php
				echo $team_id
					? esc_html__( 'My team', 'kaamase-core' )
					: esc_html__( 'Do you bring other workers?', 'kaamase-core' );
				?>
			</h2>

			<p class="ka-soft ka-small ka-mt-4">
				<?php
				echo $team_id
					? esc_html__( 'Keep the headcount right. It is the first thing a contractor looks at.', 'kaamase-core' )
					: esc_html__( 'If you work with a group, list the team as well as yourself. Contractors search for teams when they need a whole job covered, and that is work you will not see otherwise.', 'kaamase-core' );
				?>
			</p>

			<a class="ka-btn ka-btn--outline ka-mt-4" href="<?php echo esc_url( kaamase_page_url( 'team' ) ); ?>">
				<?php
				echo $team_id
					? esc_html__( 'Edit my team', 'kaamase-core' )
					: esc_html__( 'List my team', 'kaamase-core' );
				?>
			</a>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_team_dashboard_card', 10, 3 );
