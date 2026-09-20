<?php
/**
 * Profile editing.
 *
 * The form behind every Add button on the dashboard checklist, plus the
 * two things a person must be able to do for themselves: change what the
 * platform says about them, and leave.
 *
 * On account deletion
 * -------------------
 * privacy.php registered an eraser with the WordPress privacy tools.
 * That is the right plumbing and it is useless on its own, because a
 * mason cannot reach Tools, Erase Personal Data. Under the DPDP Act the
 * right to erasure has to be exercisable by the person, not by an
 * administrator on their behalf when asked nicely. So there is a Delete
 * my account button at the bottom of this form and it does the real
 * thing.
 *
 * On photographs
 * --------------
 * The upload path here goes through wp_handle_upload, which is what the
 * theme's metadata stripping hooks. That is deliberate and it is why
 * uploads are not handled with a custom file mover. A worker uploading a
 * selfie taken at home would otherwise publish their home coordinates
 * inside the image file, and the form says out loud that this is removed,
 * because a promise nobody knows about is not reassuring anybody.
 *
 * @package KaamaseCore
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. ROUTING

   The dashboard links to ?edit=<id> on its own page. Rather than editing
   dashboard.php, this intercepts the shortcode output when that query
   var is present, which keeps the two files independent.
   ========================================================================== */

if ( ! function_exists( 'kaamase_intercept_dashboard' ) ) {
	/**
	 * Show the edit form in place of the dashboard when asked.
	 *
	 * @since 1.0.0
	 * @param string $output Shortcode output.
	 * @param string $tag    Shortcode name.
	 * @return string Filtered output.
	 */
	function kaamase_intercept_dashboard( $output, $tag ) {

		if ( 'kaamase_dashboard' !== $tag ) {
			return $output;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['edit'] ) ) {
			return $output;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = absint( $_GET['edit'] );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return $output;
		}

		return kaamase_profile_form( $post_id );
	}
}
add_filter( 'do_shortcode_tag', 'kaamase_intercept_dashboard', 10, 2 );

/**
 * Also available as its own shortcode, for a dedicated page.
 *
 * @since 1.0.0
 * @return string Markup.
 */
function kaamase_edit_profile_shortcode() {

	if ( ! is_user_logged_in() ) {
		return sprintf(
			'<div class="ka-notice ka-notice--info"><p><a href="%1$s">%2$s</a></p></div>',
			esc_url( wp_login_url() ),
			esc_html__( 'Sign in to edit your profile', 'kaamase-core' )
		);
	}

	$profile = (int) get_user_meta( get_current_user_id(), 'kaamase_profile_id', true );

	return $profile ? kaamase_profile_form( $profile ) : '';
}
add_shortcode( 'kaamase_edit_profile', 'kaamase_edit_profile_shortcode' );


/* ==========================================================================
   2. THE FORM
   ========================================================================== */

if ( ! function_exists( 'kaamase_profile_form' ) ) {
	/**
	 * Render the profile edit form.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_profile_form( $post_id ) {

		$post = get_post( $post_id );

		if ( ! $post ) {
			return '';
		}

		$employer = 'kaamase_employer' === $post->post_type;
		$errors   = kaamase_profile_errors();

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

		/*
		 * Above the form, not beside the name field.
		 *
		 * Somebody with a tick is about to be able to change three
		 * things that will cost them it, and finding that out afterwards
		 * is how a fair rule turns into a grievance. Guarded because the
		 * file that owns the rule is a separate one.
		 */
		if ( function_exists( 'kaamase_mark_warning_notice' ) ) {
			echo kaamase_mark_warning_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>

		<p class="ka-small">
			<a href="<?php echo esc_url( kaamase_page_url( 'dashboard' ) ); ?>">
				<?php esc_html_e( 'Back to my account', 'kaamase-core' ); ?>
			</a>
		</p>

		<form class="ka-form ka-stack--lg" method="post" action="" enctype="multipart/form-data">

			<?php wp_nonce_field( 'kaamase_profile', 'kaamase_profile_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="save_profile">
			<input type="hidden" name="kaamase_profile_id" value="<?php echo esc_attr( $post_id ); ?>">

			<h2>
				<?php
				echo $employer
					? esc_html__( 'My details', 'kaamase-core' )
					: esc_html__( 'My profile', 'kaamase-core' );
				?>
			</h2>

			<?php
			/* ----------------------------------------------------------
			 * Photo
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-photo">
					<?php
					echo $employer
						? esc_html__( 'Logo or photo', 'kaamase-core' )
						: esc_html__( 'Photo of yourself', 'kaamase-core' );
					?>
				</label>

				<?php if ( has_post_thumbnail( $post_id ) ) : ?>
					<div class="ka-cluster ka-mb-4">
						<?php echo kaamase_avatar( $post_id, 'kaamase-avatar', 'ka-avatar--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<label class="ka-check">
							<input type="checkbox" name="kaamase_remove_photo" value="1">
							<span><?php esc_html_e( 'Remove this photo', 'kaamase-core' ); ?></span>
						</label>
					</div>
				<?php endif; ?>

				<input class="ka-input" type="file" id="ka-photo" name="kaamase_photo"
					accept="image/jpeg,image/png,image/webp">

				<p class="ka-hint">
					<?php
					echo $employer
						? esc_html__( 'Workers trust a job more when they can see who is behind it.', 'kaamase-core' )
						: esc_html__( 'A plain photo of your face is enough. Profiles with a photo get called far more often.', 'kaamase-core' );
					?>
				</p>

				<p class="ka-hint">
					<?php esc_html_e( 'We remove the hidden location information that phones save inside photos, so uploading a picture taken at home does not tell anybody where you live.', 'kaamase-core' ); ?>
				</p>

				<?php
				if ( function_exists( 'kaamase_mark_field_hint' ) ) {
					echo kaamase_mark_field_hint(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>

			<?php
			/* ----------------------------------------------------------
			 * Name
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-profile-name">
					<?php
					echo $employer
						? esc_html__( 'Business or your name', 'kaamase-core' )
						: esc_html__( 'Your name', 'kaamase-core' );
					?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="text" id="ka-profile-name" name="kaamase_name" required maxlength="90"
					value="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">

				<?php
				if ( function_exists( 'kaamase_mark_field_hint' ) ) {
					echo kaamase_mark_field_hint(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>

			<?php if ( $employer ) : ?>
				<?php echo kaamase_employer_fields( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<?php echo kaamase_worker_fields( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php
			/* ----------------------------------------------------------
			 * Shared: where and phone
			 * -------------------------------------------------------- */
			?>
			<div class="ka-field">
				<label class="ka-label" for="ka-profile-district">
					<?php esc_html_e( 'District', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-profile-district" name="kaamase_district" required>
					<?php foreach ( kaamase_district_choices() as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"
							<?php selected( kaamase_read_field( $post_id, 'district' ), $slug ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-profile-town">
					<?php esc_html_e( 'Town or village', 'kaamase-core' ); ?>
				</label>
				<input class="ka-input" type="text" id="ka-profile-town" name="kaamase_town" maxlength="60"
					value="<?php echo esc_attr( kaamase_read_field( $post_id, 'town' ) ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Employers look for people near the work.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-profile-phone">
					<?php esc_html_e( 'Phone number', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="tel" id="ka-profile-phone" name="kaamase_phone" required
					inputmode="numeric" maxlength="15"
					value="<?php echo esc_attr( kaamase_read_field( $post_id, 'phone' ) ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Never shown on the site. People reach you through Kaam Ase, and you can see who asked.', 'kaamase-core' ); ?>
				</p>

				<?php
				if ( function_exists( 'kaamase_mark_field_hint' ) ) {
					echo kaamase_mark_field_hint(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
				<?php esc_html_e( 'Save', 'kaamase-core' ); ?>
			</button>

		</form>

		<?php echo kaamase_delete_account_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php
		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   3. WORKER FIELDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_worker_fields' ) ) {
	/**
	 * The worker specific part of the form.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_worker_fields( $post_id ) {

		$my_trades    = wp_get_object_terms( $post_id, 'kaamase_trade', array( 'fields' => 'slugs' ) );
		$my_trades    = is_wp_error( $my_trades ) ? array() : $my_trades;
		$my_languages = wp_get_object_terms( $post_id, 'kaamase_language', array( 'fields' => 'slugs' ) );
		$my_languages = is_wp_error( $my_languages ) ? array() : $my_languages;

		$district = (string) kaamase_read_field( $post_id, 'district' );
		$guidance = kaamase_rate_guidance( $my_trades, $district );

		ob_start();
		?>

		<?php
		/* --------------------------------------------------------------
		 * Trades. Checkboxes rather than a multiple select, because a
		 * multiple select on a phone is close to unusable and most
		 * people do not know you have to hold something down.
		 * ------------------------------------------------------------ */
		?>
		<fieldset class="ka-field">
			<legend class="ka-label">
				<?php esc_html_e( 'What work do you do', 'kaamase-core' ); ?>
				<span class="ka-label__req">*</span>
			</legend>

			<p class="ka-hint ka-mb-4">
				<?php esc_html_e( 'Tick everything you can do. More trades means more searches you appear in.', 'kaamase-core' ); ?>
			</p>

			<?php foreach ( kaamase_trade_choices() as $group => $trades ) : ?>
				<p class="ka-small ka-bold ka-mt-4"><?php echo esc_html( $group ); ?></p>
				<?php foreach ( $trades as $slug => $name ) : ?>
					<label class="ka-check">
						<input type="checkbox" name="kaamase_trades[]" value="<?php echo esc_attr( $slug ); ?>"
							<?php checked( in_array( $slug, $my_trades, true ) ); ?>>
						<span><?php echo esc_html( $name ); ?></span>
					</label>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</fieldset>

		<div class="ka-field">
			<label class="ka-label" for="ka-experience">
				<?php esc_html_e( 'Years doing this work', 'kaamase-core' ); ?>
			</label>
			<input class="ka-input" type="number" id="ka-experience" name="kaamase_years" min="0" max="70"
				inputmode="numeric"
				value="<?php echo esc_attr( kaamase_read_field( $post_id, 'years_experience' ) ); ?>">
		</div>

		<?php
		/* --------------------------------------------------------------
		 * Rates
		 * ------------------------------------------------------------ */
		?>
		<div class="ka-field">
			<label class="ka-label" for="ka-day-rate">
				<?php esc_html_e( 'Day rate you expect', 'kaamase-core' ); ?>
			</label>
			<input class="ka-input" type="number" id="ka-day-rate" name="kaamase_day_rate" min="0" step="10"
				inputmode="numeric"
				value="<?php echo esc_attr( kaamase_read_field( $post_id, 'day_rate' ) ); ?>">

			<?php if ( $guidance ) : ?>
				<p class="ka-hint"><?php echo esc_html( $guidance ); ?></p>
			<?php else : ?>
				<p class="ka-hint">
					<?php esc_html_e( 'Put what you normally ask for. Employers filter by rate, so leaving it empty keeps you out of most searches.', 'kaamase-core' ); ?>
				</p>
			<?php endif; ?>
		</div>

		<div class="ka-field">
			<label class="ka-label" for="ka-month-rate">
				<?php esc_html_e( 'Monthly pay you expect', 'kaamase-core' ); ?>
			</label>
			<input class="ka-input" type="number" id="ka-month-rate" name="kaamase_month_rate" min="0" step="100"
				inputmode="numeric"
				value="<?php echo esc_attr( kaamase_read_field( $post_id, 'month_rate' ) ); ?>">
			<p class="ka-hint">
				<?php esc_html_e( 'Only if you want steady monthly work. Leave empty otherwise.', 'kaamase-core' ); ?>
			</p>
		</div>

		<div class="ka-field">
			<label class="ka-label" for="ka-radius">
				<?php esc_html_e( 'How far will you travel', 'kaamase-core' ); ?>
			</label>
			<select class="ka-select" id="ka-radius" name="kaamase_travel_radius">
				<?php
				$radius  = (string) kaamase_read_field( $post_id, 'travel_radius' );
				$options = array(
					'town'     => __( 'Only around my town', 'kaamase-core' ),
					'district' => __( 'Anywhere in my district', 'kaamase-core' ),
					'state'    => __( 'Anywhere in Nagaland', 'kaamase-core' ),
				);

				foreach ( $options as $value => $label ) :
					?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $radius, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php
		/* --------------------------------------------------------------
		 * Languages
		 * ------------------------------------------------------------ */
		?>
		<fieldset class="ka-field">
			<legend class="ka-label"><?php esc_html_e( 'Languages you speak', 'kaamase-core' ); ?></legend>

			<p class="ka-hint ka-mb-4">
				<?php esc_html_e( 'So an employer knows you can understand each other on site.', 'kaamase-core' ); ?>
			</p>

			<?php
			$languages = get_terms(
				array(
					'taxonomy'   => 'kaamase_language',
					'hide_empty' => false,
				)
			);

			if ( ! is_wp_error( $languages ) ) :
				foreach ( $languages as $language ) :
					?>
					<label class="ka-check">
						<input type="checkbox" name="kaamase_languages[]" value="<?php echo esc_attr( $language->slug ); ?>"
							<?php checked( in_array( $language->slug, $my_languages, true ) ); ?>>
						<span><?php echo esc_html( $language->name ); ?></span>
					</label>
					<?php
				endforeach;
			endif;
			?>
		</fieldset>

		<?php
		/* --------------------------------------------------------------
		 * Vouching.
		 *
		 * Given its own block with real explanation, because it is the
		 * strongest thing on the profile and the one people will skip if
		 * it looks like another optional field.
		 * ------------------------------------------------------------ */
		?>
		<fieldset class="ka-field ka-card ka-card--pad-lg">
			<legend class="ka-label"><?php esc_html_e( 'Who can vouch for you', 'kaamase-core' ); ?></legend>

			<p class="ka-small ka-soft ka-mb-4">
				<?php esc_html_e( 'Somebody local who knows you and will say so. A colony council member, a GB, a church elder, a student union officer, or somebody you worked for before. This carries more weight with employers than anything else on your profile.', 'kaamase-core' ); ?>
			</p>

			<div class="ka-field">
				<label class="ka-label" for="ka-vouch-name"><?php esc_html_e( 'Their name', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="text" id="ka-vouch-name" name="kaamase_vouched_by" maxlength="90"
					value="<?php echo esc_attr( kaamase_read_field( $post_id, 'vouched_by' ) ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-vouch-role"><?php esc_html_e( 'What are they', 'kaamase-core' ); ?></label>
				<select class="ka-select" id="ka-vouch-role" name="kaamase_vouched_role">
					<option value=""><?php esc_html_e( 'Choose one', 'kaamase-core' ); ?></option>
					<?php
					$role  = (string) kaamase_read_field( $post_id, 'vouched_role' );
					$roles = array(
						'gb'       => __( 'Gaon Bura', 'kaamase-core' ),
						'colony'   => __( 'Colony council member', 'kaamase-core' ),
						'village'  => __( 'Village council member', 'kaamase-core' ),
						'church'   => __( 'Church elder or pastor', 'kaamase-core' ),
						'union'    => __( 'Student or workers union officer', 'kaamase-core' ),
						'employer' => __( 'Someone I worked for', 'kaamase-core' ),
						'other'    => __( 'Someone else who knows me', 'kaamase-core' ),
					);

					foreach ( $roles as $value => $label ) :
						?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $role, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-vouch-phone"><?php esc_html_e( 'Their phone number', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="tel" id="ka-vouch-phone" name="kaamase_vouched_phone"
					inputmode="numeric" maxlength="15"
					value="<?php echo esc_attr( kaamase_read_field( $post_id, 'vouched_phone' ) ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'Never shown publicly. Only we see it, and only to check if there is a problem. Ask them before you put their number here.', 'kaamase-core' ); ?>
				</p>
			</div>
		</fieldset>

		<?php
		/* --------------------------------------------------------------
		 * About
		 * ------------------------------------------------------------ */
		?>
		<div class="ka-field">
			<label class="ka-label" for="ka-about"><?php esc_html_e( 'Anything else about your work', 'kaamase-core' ); ?></label>
			<textarea class="ka-textarea" id="ka-about" name="kaamase_about" maxlength="1000"
				placeholder="<?php esc_attr_e( 'What you have built, what tools you have, anything an employer should know.', 'kaamase-core' ); ?>"><?php echo esc_textarea( get_post_field( 'post_content', $post_id ) ); ?></textarea>
		</div>

		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_rate_guidance' ) ) {
	/**
	 * Tell a worker what others in their trade and district are asking.
	 *
	 * Only shown once at least five other people have set a rate. Below
	 * that the number would be noise, and a wrong figure here is worse
	 * than none: it would anchor somebody's price for a year.
	 *
	 * The median rather than the average, so one person asking a
	 * ridiculous amount does not move it.
	 *
	 * @since 1.0.0
	 * @param string[] $trades   Trade slugs.
	 * @param string   $district District slug.
	 * @return string Guidance sentence, or an empty string.
	 */
	function kaamase_rate_guidance( $trades, $district ) {

		if ( empty( $trades ) || '' === $district ) {
			return '';
		}

		$key    = 'kaamase_rates_' . md5( $trades[0] . $district );
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return (string) $cached;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'kaamase_worker',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'relation' => 'AND',
					array(
						'taxonomy' => 'kaamase_trade',
						'field'    => 'slug',
						'terms'    => $trades[0],
					),
					array(
						'taxonomy' => 'kaamase_district',
						'field'    => 'slug',
						'terms'    => $district,
					),
				),
			)
		);

		$rates = array();

		foreach ( $posts as $id ) {

			$rate = absint( get_post_meta( $id, KAAMASE_META_PREFIX . 'day_rate', true ) );

			if ( $rate > 0 ) {
				$rates[] = $rate;
			}
		}

		if ( count( $rates ) < 5 ) {
			set_transient( $key, '', HOUR_IN_SECONDS );
			return '';
		}

		sort( $rates );
		$middle = (int) floor( count( $rates ) / 2 );
		$median = $rates[ $middle ];

		$term = get_term_by( 'slug', $trades[0], 'kaamase_trade' );
		$name = $term instanceof WP_Term ? strtolower( $term->name ) : __( 'workers', 'kaamase-core' );

		$sentence = sprintf(
			/* translators: 1: trade name, 2: district, 3: median day rate */
			__( 'Most %1$s in %2$s ask around rupees %3$s a day. Yours is yours to decide.', 'kaamase-core' ),
			$name,
			kaamase_district_name( $district ),
			number_format_i18n( $median )
		);

		set_transient( $key, $sentence, 12 * HOUR_IN_SECONDS );

		return $sentence;
	}
}


/* ==========================================================================
   4. EMPLOYER FIELDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_employer_fields' ) ) {
	/**
	 * The employer specific part of the form.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_employer_fields( $post_id ) {

		ob_start();
		?>

		<div class="ka-field">
			<label class="ka-label" for="ka-employer-type">
				<?php esc_html_e( 'What kind of employer are you', 'kaamase-core' ); ?>
			</label>
			<select class="ka-select" id="ka-employer-type" name="kaamase_employer_type">
				<?php
				$type  = (string) kaamase_read_field( $post_id, 'employer_type' );
				$types = array(
					'individual' => __( 'An individual, building or fixing something', 'kaamase-core' ),
					'contractor' => __( 'A contractor', 'kaamase-core' ),
					'company'    => __( 'A registered company', 'kaamase-core' ),
				);

				foreach ( $types as $value => $label ) :
					?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="ka-hint">
				<?php esc_html_e( 'Workers treat an individual building a house differently from a contractor running four sites. Both are fine, they just want to know.', 'kaamase-core' ); ?>
			</p>
		</div>

		<div class="ka-field">
			<label class="ka-label" for="ka-contact-name">
				<?php esc_html_e( 'Who should they ask for', 'kaamase-core' ); ?>
			</label>
			<input class="ka-input" type="text" id="ka-contact-name" name="kaamase_contact_name" maxlength="90"
				value="<?php echo esc_attr( kaamase_read_field( $post_id, 'contact_name' ) ); ?>">
		</div>

		<div class="ka-field">
			<label class="ka-label" for="ka-gst">
				<?php esc_html_e( 'GST number', 'kaamase-core' ); ?>
			</label>
			<input class="ka-input" type="text" id="ka-gst" name="kaamase_gst" maxlength="20"
				value="<?php echo esc_attr( kaamase_read_field( $post_id, 'gst' ) ); ?>">
			<p class="ka-hint">
				<?php esc_html_e( 'Optional. Registered businesses get taken more seriously by workers.', 'kaamase-core' ); ?>
			</p>
		</div>

		<div class="ka-field">
			<label class="ka-label" for="ka-employer-about">
				<?php esc_html_e( 'About your work', 'kaamase-core' ); ?>
			</label>
			<textarea class="ka-textarea" id="ka-employer-about" name="kaamase_about" maxlength="1000"
				placeholder="<?php esc_attr_e( 'What you build, how you pay, anything a worker should know before taking a job with you.', 'kaamase-core' ); ?>"><?php echo esc_textarea( get_post_field( 'post_content', $post_id ) ); ?></textarea>
		</div>

		<?php
		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   5. SAVING
   ========================================================================== */

if ( ! function_exists( 'kaamase_handle_profile_save' ) ) {
	/**
	 * Validate and save a profile.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_profile_save() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'save_profile' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_profile_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_profile_nonce'] ) ), 'kaamase_profile' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_id = isset( $_POST['kaamase_profile_id'] ) ? absint( $_POST['kaamase_profile_id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post     = get_post( $post_id );
		$employer = 'kaamase_employer' === $post->post_type;

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$name     = isset( $_POST['kaamase_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_name'] ) ) : '';
		$about    = isset( $_POST['kaamase_about'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kaamase_about'] ) ) : '';
		$district = isset( $_POST['kaamase_district'] ) ? kaamase_match_district( sanitize_text_field( wp_unslash( $_POST['kaamase_district'] ) ) ) : '';
		$town     = isset( $_POST['kaamase_town'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_town'] ) ) : '';
		$phone_in = isset( $_POST['kaamase_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_phone'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$trades = isset( $_POST['kaamase_trades'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['kaamase_trades'] ) ) : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$languages = isset( $_POST['kaamase_languages'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['kaamase_languages'] ) ) : array();

		/*
		 * The rules live in services.php so the phone app enforces exactly
		 * the same ones. This handler is transport only.
		 */
		$values = array(
			'name'      => $name,
			'about'     => $about,
			'district'  => $district,
			'town'      => $town,
			'phone'     => $phone_in,
			'trades'    => $trades,
			'languages' => $languages,
		);

		if ( $employer ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$values['employer_type'] = isset( $_POST['kaamase_employer_type'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_employer_type'] ) ) : 'individual';
			$values['contact_name']  = isset( $_POST['kaamase_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_contact_name'] ) ) : '';
			$values['gst']           = isset( $_POST['kaamase_gst'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_gst'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		} else {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$values['years_experience'] = isset( $_POST['kaamase_years'] ) ? absint( $_POST['kaamase_years'] ) : 0;
			$values['day_rate']         = isset( $_POST['kaamase_day_rate'] ) ? absint( $_POST['kaamase_day_rate'] ) : 0;
			$values['month_rate']       = isset( $_POST['kaamase_month_rate'] ) ? absint( $_POST['kaamase_month_rate'] ) : 0;
			$values['travel_radius']    = isset( $_POST['kaamase_travel_radius'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_travel_radius'] ) ) : 'district';
			$values['vouched_by']       = isset( $_POST['kaamase_vouched_by'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_vouched_by'] ) ) : '';
			$values['vouched_role']     = isset( $_POST['kaamase_vouched_role'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_vouched_role'] ) ) : '';
			$values['vouched_phone']    = isset( $_POST['kaamase_vouched_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_vouched_phone'] ) ) : '';
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		$result = kaamase_save_profile( $post_id, $values, get_current_user_id() );

		if ( is_wp_error( $result ) ) {

			$data     = $result->get_error_data();
			$messages = isset( $data['messages'] ) ? (array) $data['messages'] : array( $result->get_error_message() );

			set_transient( 'kaamase_profile_err_' . get_current_user_id(), $messages, 15 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( 'edit', $post_id, kaamase_page_url( 'dashboard' ) ) );
			exit;
		}

		kaamase_handle_photo( $post_id );

		wp_safe_redirect( add_query_arg( 'saved', 'profile', kaamase_page_url( 'dashboard' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_profile_save' );

if ( ! function_exists( 'kaamase_handle_photo' ) ) {
	/**
	 * Save or remove the profile photograph.
	 *
	 * Goes through media_handle_upload rather than moving the file
	 * directly, because that is the path the theme hooks to strip camera
	 * metadata. A custom uploader here would silently defeat that and
	 * start publishing people's home coordinates.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return void
	 */
	function kaamase_handle_photo( $post_id ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['kaamase_remove_photo'] ) ) {

			$existing = get_post_thumbnail_id( $post_id );

			if ( $existing ) {
				delete_post_thumbnail( $post_id );
				wp_delete_attachment( $existing, true );
			}

			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_FILES['kaamase_photo']['name'] ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( 'kaamase_photo', $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return;
		}

		// Replace rather than accumulate, so an old photo is never left behind.
		$previous = get_post_thumbnail_id( $post_id );

		set_post_thumbnail( $post_id, $attachment_id );

		if ( $previous && $previous !== $attachment_id ) {
			wp_delete_attachment( $previous, true );
		}
	}
}

if ( ! function_exists( 'kaamase_profile_errors' ) ) {
	/**
	 * Read and clear profile form errors.
	 *
	 * @since 1.0.0
	 * @return string[] Messages.
	 */
	function kaamase_profile_errors() {

		$key    = 'kaamase_profile_err_' . get_current_user_id();
		$errors = get_transient( $key );

		if ( $errors ) {
			delete_transient( $key );
		}

		return is_array( $errors ) ? $errors : array();
	}
}


/* ==========================================================================
   6. LEAVING

   The right to erasure only means something if the person can use it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_delete_account_section' ) ) {
	/**
	 * Render the account deletion block.
	 *
	 * Two steps, and the second one requires typing a word rather than
	 * clicking again, because a thumb on a small screen can hit two
	 * buttons by accident and this one cannot be undone.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_delete_account_section() {

		ob_start();
		?>
		<details class="ka-card ka-card--flat ka-mt-6">

			<summary class="ka-small ka-mute" style="cursor:pointer;padding:8px 0;">
				<?php esc_html_e( 'Delete my account', 'kaamase-core' ); ?>
			</summary>

			<div class="ka-mt-4">

				<p class="ka-soft">
					<?php esc_html_e( 'This removes your profile, your photo and your details from Kaam Ase. It cannot be undone and you would have to start again.', 'kaamase-core' ); ?>
				</p>

				<p class="ka-small ka-mute ka-mt-4">
					<?php esc_html_e( 'Jobs and ratings involving other people are kept without your name, because they are part of somebody else\'s record too.', 'kaamase-core' ); ?>
				</p>

				<form method="post" action="" class="ka-mt-6">
					<?php wp_nonce_field( 'kaamase_delete_account', 'kaamase_delete_nonce' ); ?>
					<input type="hidden" name="kaamase_action" value="delete_account">

					<div class="ka-field">
						<label class="ka-label" for="ka-confirm-delete">
							<?php
							printf(
								/* translators: %s: the word the person must type */
								esc_html__( 'Type %s to confirm', 'kaamase-core' ),
								'<strong>' . esc_html_x( 'DELETE', 'confirmation word, keep it short and in capitals', 'kaamase-core' ) . '</strong>'
							);
							?>
						</label>
						<input class="ka-input" type="text" id="ka-confirm-delete" name="kaamase_confirm"
							autocomplete="off" required>
					</div>

					<button class="ka-btn ka-btn--danger" type="submit">
						<?php esc_html_e( 'Delete my account for good', 'kaamase-core' ); ?>
					</button>
				</form>

			</div>

		</details>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_handle_delete_account' ) ) {
	/**
	 * Erase an account at the person's own request.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_delete_account() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'delete_account' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_delete_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_delete_nonce'] ) ), 'kaamase_delete_account' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$typed = isset( $_POST['kaamase_confirm'] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['kaamase_confirm'] ) ) ) ) : '';

		if ( $typed !== strtoupper( _x( 'DELETE', 'confirmation word, keep it short and in capitals', 'kaamase-core' ) ) ) {
			set_transient(
				'kaamase_profile_err_' . get_current_user_id(),
				array( __( 'Type the word exactly to confirm you want to delete your account.', 'kaamase-core' ) ),
				5 * MINUTE_IN_SECONDS
			);
			wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
			exit;
		}

		$user = wp_get_current_user();

		// Reuse the same routine the privacy tools call. One code path, one behaviour.
		kaamase_erase_personal_data( $user->user_email );

		require_once ABSPATH . 'wp-admin/includes/user.php';

		$email = $user->user_email;
		$name  = $user->display_name;

		wp_logout();
		wp_delete_user( $user->ID );

		// Say goodbye properly. Somebody deleting an account still deserves a reply.
		wp_mail(
			$email,
			sprintf(
				/* translators: %s: site name */
				__( 'Your %s account is closed', 'kaamase-core' ),
				get_bloginfo( 'name', 'display' )
			),
			implode(
				"\n\n",
				array(
					sprintf(
						/* translators: %s: person's name */
						__( 'Hello %s,', 'kaamase-core' ),
						$name
					),
					__( 'Your account and your profile have been removed. Nothing of yours is left on the site.', 'kaamase-core' ),
					__( 'If you ever want to come back, you are welcome to register again.', 'kaamase-core' ),
				)
			)
		);

		wp_safe_redirect( add_query_arg( 'goodbye', '1', home_url( '/' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_delete_account' );
