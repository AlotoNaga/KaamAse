<?php
/**
 * Registration.
 *
 * Front end signup for workers and employers, with email verification.
 *
 * How verification works here
 * ---------------------------
 * The account is created and the person is signed in immediately. Their
 * profile is created as a draft and is invisible to everybody until they
 * click the link in the email.
 *
 * That order matters. The usual pattern, where you cannot sign in until
 * you verify, loses people permanently. A worker registering on a
 * borrowed phone at a labour point cannot open their email there, walks
 * away, and never comes back. Signing them in at once means they see the
 * profile they just made and understand what it is. The unpublished
 * draft means an unverified account still cannot put anything on the
 * public site.
 *
 * The verification layer is isolated behind kaamase_send_verification()
 * and kaamase_user_is_verified(). Moving to phone verification later
 * means rewriting the inside of those two functions and nothing else in
 * this plugin changes.
 *
 * Bot protection
 * --------------
 * A public registration form on a WordPress site gets found by automated
 * signup scripts within days. There are three defences here and none is
 * a CAPTCHA, because a CAPTCHA on a cheap phone over a slow connection
 * fails real users at a far higher rate than it stops bots.
 *
 * @package KaamaseCore
 * @version 1.5.0
 * @since   1.0.0
 *
 * Changelog
 *   1.0.1  Phone number is mandatory. Email remains the only verified
 *          channel; the number is collected because an employer needs
 *          something to call and contact.php needs something to mask.
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE FORM
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_shortcode' ) ) {
	/**
	 * Render the registration form.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_register_shortcode() {

		if ( is_user_logged_in() ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
				esc_html__( 'You are already signed in.', 'kaamase-core' ),
				esc_url( kaamase_page_url( 'dashboard' ) ),
				esc_html__( 'Go to my account', 'kaamase-core' )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
		$type = in_array( $type, array( 'worker', 'employer' ), true ) ? $type : '';

		ob_start();

		$errors = kaamase_registration_errors();

		if ( ! empty( $errors ) ) {
			echo '<div class="ka-notice ka-notice--error"><div><span class="ka-notice__title">'
				. esc_html__( 'Please fix this', 'kaamase-core' )
				. '</span><ul>';

			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}

			echo '</ul></div></div>';
		}

		// No type chosen yet. Ask the one question that decides the rest.
		if ( '' === $type ) {
			echo kaamase_register_chooser(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return (string) ob_get_clean();
		}

		$old = kaamase_registration_input();
		?>
		<form class="ka-form ka-stack--lg" method="post" action="">

			<?php wp_nonce_field( 'kaamase_register', 'kaamase_register_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="register">
			<input type="hidden" name="kaamase_type" value="<?php echo esc_attr( $type ); ?>">

			<?php
			/*
			 * Defence one: a honeypot.
			 *
			 * Hidden from people, visible to a script that fills every
			 * field it finds. Named website because that is the sort of
			 * field name a bot expects and fills.
			 *
			 * Hidden with an inline style rather than a class, so it
			 * still works when the stylesheet has not arrived yet.
			 */
			?>
			<div style="position:absolute;left:-9999px;" aria-hidden="true">
				<label for="ka-website"><?php esc_html_e( 'Leave this empty', 'kaamase-core' ); ?></label>
				<input type="text" id="ka-website" name="website" tabindex="-1" autocomplete="off" value="">
			</div>

			<?php
			/*
			 * Defence two: a time trap. A person cannot read and fill
			 * this form in under four seconds. A script does it in
			 * milliseconds.
			 */
			?>
			<input type="hidden" name="kaamase_started" value="<?php echo esc_attr( time() ); ?>">

			<h2>
				<?php
				echo 'worker' === $type
					? esc_html__( 'Make your free worker profile', 'kaamase-core' )
					: esc_html__( 'Register as an employer', 'kaamase-core' );
				?>
			</h2>

			<div class="ka-field">
				<label class="ka-label" for="ka-name">
					<?php esc_html_e( 'Your full name', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="text" id="ka-name" name="kaamase_name" required
					autocomplete="name" value="<?php echo esc_attr( isset( $old['name'] ) ? $old['name'] : '' ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-email">
					<?php esc_html_e( 'Email address', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="email" id="ka-email" name="kaamase_email" required
					autocomplete="email" value="<?php echo esc_attr( isset( $old['email'] ) ? $old['email'] : '' ); ?>">
				<p class="ka-hint">
					<?php esc_html_e( 'We send one link to this address to confirm it is yours.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-phone">
					<?php esc_html_e( 'Phone number', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="tel" id="ka-phone" name="kaamase_phone" required
					inputmode="numeric" autocomplete="tel" maxlength="15"
					pattern="[0-9 +\-]{10,15}"
					value="<?php echo esc_attr( isset( $old['phone'] ) ? $old['phone'] : '' ); ?>">
				<p class="ka-hint">
					<?php
					echo 'worker' === $type
						? esc_html__( '10 digits. Your number is never shown on the site. Employers reach you through Kaam Ase.', 'kaamase-core' )
						: esc_html__( '10 digits. Your number is never shown on the site. Workers reach you through Kaam Ase.', 'kaamase-core' );
					?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-district">
					<?php esc_html_e( 'District', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-district" name="kaamase_district" required>
					<option value=""><?php esc_html_e( 'Choose your district', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_district_choices() as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"
							<?php selected( isset( $old['district'] ) ? $old['district'] : '', $slug ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( 'worker' === $type ) : ?>
				<div class="ka-field">
					<label class="ka-label" for="ka-trade">
						<?php esc_html_e( 'What work do you do', 'kaamase-core' ); ?>
						<span class="ka-label__req">*</span>
					</label>
					<select class="ka-select" id="ka-trade" name="kaamase_trade" required>
						<option value=""><?php esc_html_e( 'Choose your trade', 'kaamase-core' ); ?></option>
						<?php foreach ( kaamase_trade_choices() as $group => $trades ) : ?>
							<optgroup label="<?php echo esc_attr( $group ); ?>">
								<?php foreach ( $trades as $slug => $name ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>"
										<?php selected( isset( $old['trade'] ) ? $old['trade'] : '', $slug ); ?>>
										<?php echo esc_html( $name ); ?>
									</option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
					<p class="ka-hint">
						<?php esc_html_e( 'You can add more trades to your profile afterwards.', 'kaamase-core' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="ka-field">
				<label class="ka-label" for="ka-password">
					<?php esc_html_e( 'Choose a password', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="password" id="ka-password" name="kaamase_password"
					required minlength="8" autocomplete="new-password">
				<p class="ka-hint"><?php esc_html_e( 'At least 8 characters.', 'kaamase-core' ); ?></p>
			</div>

			<div class="ka-field">
				<label class="ka-check">
					<input type="checkbox" name="kaamase_agree" value="1" required>
					<span>
						<?php
						printf(
							/* translators: 1: terms page link, 2: privacy page link */
							esc_html__( 'I agree to the %1$s and the %2$s.', 'kaamase-core' ),
							'<a href="' . esc_url( kaamase_page_url( 'terms' ) ) . '">' . esc_html__( 'terms', 'kaamase-core' ) . '</a>',
							'<a href="' . esc_url( kaamase_page_url( 'privacy' ) ) . '">' . esc_html__( 'privacy notice', 'kaamase-core' ) . '</a>'
						);
						?>
					</span>
				</label>
			</div>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
				<?php esc_html_e( 'Create my account', 'kaamase-core' ); ?>
			</button>

			<p class="ka-small ka-mute">
				<?php esc_html_e( 'Kaam Ase is free. We will never ask a worker for money.', 'kaamase-core' ); ?>
			</p>

			<p class="ka-small">
				<?php esc_html_e( 'Already registered?', 'kaamase-core' ); ?>
				<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Sign in', 'kaamase-core' ); ?></a>
			</p>

		</form>
		<?php

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_register', 'kaamase_register_shortcode' );

if ( ! function_exists( 'kaamase_register_chooser' ) ) {
	/**
	 * Ask which kind of account before showing any fields.
	 *
	 * One question on its own screen rather than a radio button buried
	 * in a long form. The two paths differ enough that choosing wrong
	 * means starting again, and somebody who has to start again usually
	 * does not.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_register_chooser() {

		/*
		 * The same screen as the app, matched to it: a group of workers on
		 * each door, a two word title, one line under it and a button. Both
		 * doors sit side by side at every width. Then the Google button and
		 * the account link, in the app's order (see below the loop).
		 *
		 * The pictures live in the theme, beside the homepage's. A theme
		 * without them still gets both doors, just without the picture.
		 */
		$doors = array(
			'worker'   => array(
				'class'  => 'ka-choice--worker',
				'photo'  => 'worker',
				'title'  => __( 'Find work', 'kaamase-core' ),
				'body'   => __( 'Daily work, monthly jobs and government work', 'kaamase-core' ),
				'button' => __( 'Worker', 'kaamase-core' ),
				'btn'    => 'ka-btn--primary',
			),
			'employer' => array(
				'class'  => 'ka-choice--employer',
				'photo'  => 'employer',
				'title'  => __( 'Hire workers', 'kaamase-core' ),
				'body'   => __( 'Post a job and find skilled workers', 'kaamase-core' ),
				'button' => __( 'Employer', 'kaamase-core' ),
				'btn'    => 'ka-btn--action',
			),
		);

		$arrow = '<svg class="ka-choice__arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';

		ob_start();
		?>
		<div class="ka-join">

			<div class="ka-join__head">
				<h2 class="ka-join__title"><?php esc_html_e( 'What brings you here?', 'kaamase-core' ); ?></h2>
				<p class="ka-join__lead">
					<?php esc_html_e( 'Make a free profile so employers can find you, or register as an employer and post a job. Kaam Ase is free for workers and always will be.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-join__doors">
				<?php foreach ( $doors as $type => $door ) : ?>
					<?php
					$small = 'assets/images/register/' . $door['photo'] . '-s.webp';
					$large = 'assets/images/register/' . $door['photo'] . '-l.webp';
					$has   = file_exists( get_theme_file_path( $small ) ) && file_exists( get_theme_file_path( $large ) );
					?>
					<a class="ka-choice <?php echo esc_attr( $door['class'] ); ?>"
						href="<?php echo esc_url( add_query_arg( 'type', $type, kaamase_page_url( 'register' ) ) ); ?>">

						<?php if ( $has ) : ?>
							<span class="ka-choice__photo">
								<img src="<?php echo esc_url( get_theme_file_uri( $small ) ); ?>"
									srcset="<?php echo esc_url( get_theme_file_uri( $small ) ); ?> 400w, <?php echo esc_url( get_theme_file_uri( $large ) ); ?> 720w"
									sizes="(min-width: 700px) 480px, 50vw"
									width="720" height="526" alt="" decoding="async">
							</span>
						<?php endif; ?>

						<span class="ka-choice__text">
							<span class="ka-choice__title"><?php echo esc_html( $door['title'] ); ?></span>
							<span class="ka-choice__body"><?php echo esc_html( $door['body'] ); ?></span>
						</span>

						<span class="ka-btn <?php echo esc_attr( $door['btn'] ); ?> ka-btn--lg ka-btn--block">
							<?php echo esc_html( $door['button'] ); ?>
							<?php echo $arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup above. ?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>

			<?php
			/*
			 * Then the sign in options, in the app's order: the Google
			 * button, then the way out for somebody who already has an
			 * account. The website has no Apple button -- Sign in with
			 * Apple is built for the app only.
			 *
			 * The Google button is drawn here, between the doors and the
			 * account link, rather than left to google-signin.php to append
			 * at the very end, so the order matches the app. It is guarded
			 * by function_exists: if that file is ever removed the page
			 * simply shows the doors and the account link, exactly as it
			 * did before Google sign in existed.
			 */
			if ( function_exists( 'kaamase_google_button' ) ) {
				// Null label: no caption, so the button stands on its own like the app's.
				echo kaamase_google_button( null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_* inside.
			}
			?>
			<a class="ka-btn ka-btn--outline ka-btn--lg ka-btn--block ka-join__signin" href="<?php echo esc_url( wp_login_url() ); ?>">
				<?php esc_html_e( 'I already have an account', 'kaamase-core' ); ?>
			</a>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   2. HANDLING THE SUBMISSION
   ========================================================================== */

if ( ! function_exists( 'kaamase_handle_registration' ) ) {
	/**
	 * Validate and process a registration.
	 *
	 * Runs on template_redirect, so redirecting is still possible and
	 * nothing has been sent to the browser yet.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_registration() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'register' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			empty( $_POST['kaamase_register_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_register_nonce'] ) ), 'kaamase_register' )
		) {
			kaamase_registration_fail( array( __( 'That form expired. Please try again.', 'kaamase-core' ) ) );
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
			exit;
		}

		/* ---- Bot checks ---- */

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['website'] ) ) {
			kaamase_registration_fail( array( __( 'Something went wrong. Please try again.', 'kaamase-core' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$started = isset( $_POST['kaamase_started'] ) ? absint( $_POST['kaamase_started'] ) : 0;

		if ( ! $started || ( time() - $started ) < 4 ) {
			kaamase_registration_fail( array( __( 'That was too quick. Please try again.', 'kaamase-core' ) ) );
		}

		/*
		 * Defence three: rate limiting per connection.
		 *
		 * Five attempts an hour. Generous for a person, useless for a
		 * script working through a list. Five rather than one because at
		 * an internet point or on a shared village connection, several
		 * genuine people register from the same address.
		 */
		if ( ! kaamase_registration_rate_ok() ) {
			kaamase_registration_fail( array( __( 'Too many attempts from this connection. Please try again later.', 'kaamase-core' ) ) );
		}

		/* ---- Collect ---- */

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$type     = isset( $_POST['kaamase_type'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_type'] ) ) : '';
		$name     = isset( $_POST['kaamase_name'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_name'] ) ) : '';
		$email    = isset( $_POST['kaamase_email'] ) ? sanitize_email( wp_unslash( $_POST['kaamase_email'] ) ) : '';
		$phone_in = isset( $_POST['kaamase_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_phone'] ) ) : '';
		$district = isset( $_POST['kaamase_district'] ) ? kaamase_match_district( sanitize_text_field( wp_unslash( $_POST['kaamase_district'] ) ) ) : '';
		$trade    = isset( $_POST['kaamase_trade'] ) ? kaamase_match_trade( sanitize_text_field( wp_unslash( $_POST['kaamase_trade'] ) ) ) : '';
		$password = isset( $_POST['kaamase_password'] ) ? (string) wp_unslash( $_POST['kaamase_password'] ) : '';
		$agreed   = ! empty( $_POST['kaamase_agree'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$phone = kaamase_sanitize_phone( $phone_in );

		/*
		 * The address exactly as typed, for the typo check. sanitize_email()
		 * throws away the very mistakes it looks for: gmail..com comes back
		 * empty, and there would be nothing left to suggest a fix for.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email_raw = isset( $_POST['kaamase_email'] ) ? (string) wp_unslash( $_POST['kaamase_email'] ) : '';

		/*
		 * What the last attempt was warned about. Read before this
		 * attempt's values are stored over it, so the same address typed
		 * again on purpose can be recognised and accepted.
		 */
		$before = kaamase_registration_input();

		kaamase_registration_input(
			array(
				'name'     => $name,
				'email'    => $email,
				'phone'    => $phone_in,
				'district' => $district,
				'trade'    => $trade,
			)
		);

		/* ---- Validate ---- */

		$errors = array();

		if ( ! in_array( $type, array( 'worker', 'employer' ), true ) ) {
			$errors[] = __( 'Choose whether you are looking for work or looking for workers.', 'kaamase-core' );
		}

		if ( '' === $name ) {
			$errors[] = __( 'Please enter your name.', 'kaamase-core' );
		}

		/*
		 * A mistyped address. See email-typos.php.
		 *
		 * The address they meant is put in the box for them, and the one
		 * they typed is remembered, so typing it again exactly is taken as
		 * "yes, this really is my address" and it goes through.
		 */
		$email_problem = null;

		if ( function_exists( 'kaamase_email_problem' ) ) {
			$insisted      = ! empty( $before['email_warned'] ) && kaamase_email_normal( $email_raw ) === $before['email_warned'];
			$email_problem = kaamase_email_problem( $email_raw, $insisted );
		}

		if ( $email_problem ) {

			$errors[] = $email_problem['message'];

			$kept                 = kaamase_registration_input();
			$kept['email_warned'] = kaamase_email_normal( $email_raw );

			if ( '' !== $email_problem['suggestion'] ) {
				$kept['email'] = $email_problem['suggestion'];
				$errors[]      = __( 'We have put that address in the box for you. If what you typed was right, type it again exactly and we will use it.', 'kaamase-core' );
			} else {
				$errors[] = __( 'If what you typed is right, type it again exactly and we will use it.', 'kaamase-core' );
			}

			kaamase_registration_input( $kept );

		} elseif ( ! is_email( $email ) ) {
			$errors[] = __( 'Please enter a working email address.', 'kaamase-core' );
		}

		if ( '' === $district ) {
			$errors[] = __( 'Please choose your district.', 'kaamase-core' );
		}

		if ( 'worker' === $type && '' === $trade ) {
			$errors[] = __( 'Please choose the work you do.', 'kaamase-core' );
		}

		if ( strlen( $password ) < 8 ) {
			$errors[] = __( 'Your password needs at least 8 characters.', 'kaamase-core' );
		}

		if ( ! $agreed ) {
			$errors[] = __( 'Please agree to the terms and the privacy notice.', 'kaamase-core' );
		}

		/*
		 * Phone is required.
		 *
		 * Email is the channel that gets verified, but the number is
		 * what makes the account useful: an employer needs something to
		 * call, and the masked contact system needs something to mask.
		 * An account with no number is a worker nobody can hire.
		 *
		 * A number that failed to parse gets its own message rather than
		 * being silently discarded, because otherwise neither side ever
		 * learns why the calls never came.
		 */
		if ( '' === $phone_in ) {
			$errors[] = __( 'Please enter your phone number.', 'kaamase-core' );
		} elseif ( '' === $phone ) {
			$errors[] = __( 'That phone number does not look right. It should be 10 digits starting with 6, 7, 8 or 9.', 'kaamase-core' );
		}

		if ( is_email( $email ) && email_exists( $email ) ) {
			/*
			 * Deliberately vague, matching the sign in screen. Saying
			 * that an address is already registered confirms to a
			 * stranger that a particular person is on this platform.
			 */
			$errors[] = __( 'We could not create an account with those details. If you already have one, try signing in.', 'kaamase-core' );
		}

		if ( ! empty( $errors ) ) {
			kaamase_registration_fail( $errors );
		}

		/* ---- Create ---- */

		$user_id = kaamase_create_account(
			array(
				'type'     => $type,
				'name'     => $name,
				'email'    => $email,
				'phone'    => $phone,
				'district' => $district,
				'trade'    => $trade,
				'password' => $password,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			kaamase_registration_fail( array( $user_id->get_error_message() ) );
		}

		kaamase_registration_clear();

		// Sign them in now. See the note at the top of this file.
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		wp_safe_redirect( add_query_arg( 'welcome', '1', kaamase_page_url( 'dashboard' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_registration' );


/* ==========================================================================
   3. CREATING THE ACCOUNT AND ITS PROFILE
   ========================================================================== */

if ( ! function_exists( 'kaamase_create_account' ) ) {
	/**
	 * Create the user and the matching profile post together.
	 *
	 * Always both. A user without a profile is an account that signs in
	 * and finds nothing. A profile without a user is a listing nobody
	 * can edit. If the profile fails, the account is removed rather than
	 * left behind in that broken half state.
	 *
	 * @since 1.0.0
	 * @param array $data Validated registration data.
	 * @return int|WP_Error User ID, or an error.
	 */
	function kaamase_create_account( $data ) {

		$login = kaamase_unique_login( $data['email'] );

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_email'   => $data['email'],
				'user_pass'    => $data['password'],
				'display_name' => $data['name'],
				'first_name'   => $data['name'],
				'role'         => 'worker' === $data['type'] ? 'kaamase_worker' : 'kaamase_employer',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$post_type = 'worker' === $data['type'] ? 'kaamase_worker' : 'kaamase_employer';

		/*
		 * Draft until verified. The account is signed in and can look
		 * around, but nothing it owns reaches the public site yet.
		 */
		$post_id = wp_insert_post(
			array(
				'post_type'    => $post_type,
				'post_title'   => $data['name'],
				'post_status'  => 'draft',
				'post_author'  => $user_id,
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {

			require_once ABSPATH . 'wp-admin/includes/user.php';
			wp_delete_user( $user_id );

			return $post_id;
		}

		kaamase_save_field( $post_id, 'phone', $data['phone'] );
		kaamase_save_field( $post_id, 'district', $data['district'] );

		if ( $data['district'] ) {
			wp_set_object_terms( $post_id, $data['district'], 'kaamase_district' );
		}

		if ( 'worker' === $data['type'] && $data['trade'] ) {
			wp_set_object_terms( $post_id, $data['trade'], 'kaamase_trade' );
		}

		update_user_meta( $user_id, 'kaamase_profile_id', $post_id );
		update_user_meta( $user_id, 'kaamase_registered_at', time() );

		kaamase_send_verification( $user_id );

		/**
		 * Fires after an account and its profile are created.
		 *
		 * @since 1.0.0
		 * @param int    $user_id User ID.
		 * @param int    $post_id Profile post ID.
		 * @param string $type    worker or employer.
		 */
		do_action( 'kaamase_account_created', $user_id, $post_id, $data['type'] );

		return $user_id;
	}
}

if ( ! function_exists( 'kaamase_unique_login' ) ) {
	/**
	 * Build a username from an email address.
	 *
	 * Nobody is asked to invent a username. It is one more thing to
	 * remember, one more thing to get wrong, and it means nothing to a
	 * worker. They sign in with their email address.
	 *
	 * @since 1.0.0
	 * @param string $email Email address.
	 * @return string Unique login.
	 */
	function kaamase_unique_login( $email ) {

		$parts = explode( '@', $email );
		$base  = sanitize_user( $parts[0], true );
		$base  = $base ? substr( $base, 0, 40 ) : 'user';

		$login = $base;
		$n     = 1;

		while ( username_exists( $login ) ) {

			$login = $base . $n;
			$n++;

			if ( $n > 500 ) {
				$login = $base . wp_generate_password( 6, false, false );
				break;
			}
		}

		return $login;
	}
}


/* ==========================================================================
   4. VERIFICATION

   Replace the inside of these functions to move to phone verification.
   Nothing outside this section knows how somebody was verified, only
   whether they were.
   ========================================================================== */

if ( ! function_exists( 'kaamase_send_verification' ) ) {
	/**
	 * Email a verification link.
	 *
	 * The token is stored hashed. If the database is ever read by
	 * somebody who should not have it, the stored value cannot be used
	 * to verify anybody's account.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return bool Whether the message was handed to WordPress.
	 */
	function kaamase_send_verification( $user_id ) {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$token = wp_generate_password( 32, false, false );

		update_user_meta( $user_id, 'kaamase_verify_hash', wp_hash_password( $token ) );
		update_user_meta( $user_id, 'kaamase_verify_expires', time() + ( 7 * DAY_IN_SECONDS ) );

		$link = add_query_arg(
			array(
				'kaamase_verify' => rawurlencode( $token ),
				'uid'            => absint( $user_id ),
			),
			home_url( '/' )
		);

		/*
		 * 'request', and this is the only send on the platform that
		 * wants it.
		 *
		 * Usually this IS their own request: somebody registering, in
		 * the language they have been reading the site in. They have no
		 * account language yet because the account is seconds old, so
		 * falling back to the site's would send the very first thing we
		 * ever write to them in a language they did not pick.
		 *
		 * The other two callers are not their request — insights.php
		 * sends it again to accounts that never confirmed, and
		 * rest-api.php sends it for the app — and both are covered,
		 * because a person who HAS chosen a language is switched to it
		 * either way.
		 */
		$switched = function_exists( 'kaamase_locale_switch_to_user' )
			&& kaamase_locale_switch_to_user( $user_id, 'request' );

		$site = get_bloginfo( 'name', 'display' );

		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Confirm your email for %s', 'kaamase-core' ),
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
				__( 'Tap the link below to confirm your email address. Your profile goes live as soon as you do.', 'kaamase-core' ),
				$link,
				__( 'If you did not create this account, ignore this message and nothing will happen.', 'kaamase-core' ),
				sprintf(
					/* translators: %s: site name */
					__( '%s never asks a worker for money.', 'kaamase-core' ),
					$site
				),
			)
		);

		$sent = wp_mail( $user->user_email, $subject, $body );

		if ( $switched ) {
			kaamase_locale_restore();
		}

		return $sent;
	}
}

if ( ! function_exists( 'kaamase_user_is_verified' ) ) {
	/**
	 * Whether an account has been verified.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return bool
	 */
	function kaamase_user_is_verified( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		return (bool) get_user_meta( $user_id, 'kaamase_verified_at', true );
	}
}

if ( ! function_exists( 'kaamase_handle_verification' ) ) {
	/**
	 * Confirm a verification link and publish the waiting profile.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_verification() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['kaamase_verify'] ) || empty( $_GET['uid'] ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$token   = sanitize_text_field( wp_unslash( $_GET['kaamase_verify'] ) );
		$user_id = absint( $_GET['uid'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$hash    = get_user_meta( $user_id, 'kaamase_verify_hash', true );
		$expires = (int) get_user_meta( $user_id, 'kaamase_verify_expires', true );

		$ok = $hash && $expires > time() && wp_check_password( $token, $hash );

		if ( ! $ok ) {
			wp_safe_redirect( add_query_arg( 'verify', 'failed', kaamase_page_url( 'dashboard' ) ) );
			exit;
		}

		update_user_meta( $user_id, 'kaamase_verified_at', time() );
		delete_user_meta( $user_id, 'kaamase_verify_hash' );
		delete_user_meta( $user_id, 'kaamase_verify_expires' );

		$profile_id = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( $profile_id && 'draft' === get_post_status( $profile_id ) ) {
			wp_update_post(
				array(
					'ID'          => $profile_id,
					'post_status' => 'publish',
				)
			);
		}

		/**
		 * Fires when an account is verified.
		 *
		 * @since 1.0.0
		 * @param int $user_id User ID.
		 */
		do_action( 'kaamase_user_verified', $user_id );

		wp_safe_redirect( add_query_arg( 'verify', 'done', kaamase_page_url( 'dashboard' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_verification', 5 );

if ( ! function_exists( 'kaamase_resend_verification' ) ) {
	/**
	 * Send the verification email again.
	 *
	 * One every ten minutes, so the button cannot be used to send
	 * somebody a hundred messages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_resend_verification() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'resend_verification' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_resend_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_resend_nonce'] ) ), 'kaamase_resend' )
		) {
			return;
		}

		$user_id = get_current_user_id();
		$key     = 'kaamase_resend_' . $user_id;

		// Durable, so the cooldown cannot be cleared into an email flood.
		if ( kaamase_rate_value( $key, 0 ) ) {
			wp_safe_redirect( add_query_arg( 'verify', 'wait', kaamase_page_url( 'dashboard' ) ) );
			exit;
		}

		kaamase_rate_write( $key, 1, 10 * MINUTE_IN_SECONDS );
		kaamase_send_verification( $user_id );

		wp_safe_redirect( add_query_arg( 'verify', 'sent', kaamase_page_url( 'dashboard' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_resend_verification' );


/* ==========================================================================
   5. STATE BETWEEN REQUESTS

   Errors and entered values are held in a short lived transient keyed to
   the visitor, so a failed submission comes back with their answers
   still in the fields. Retyping a whole form on a phone because one
   field was wrong is how a registration gets abandoned.
   ========================================================================== */

if ( ! function_exists( 'kaamase_registration_key' ) ) {
	/**
	 * Transient key for this visitor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_registration_key() {

		$address = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: 'unknown';

		$agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		return 'kaamase_reg_' . md5( $address . $agent );
	}
}

if ( ! function_exists( 'kaamase_registration_errors' ) ) {
	/**
	 * Read and clear stored errors.
	 *
	 * @since 1.0.0
	 * @return string[] Error messages.
	 */
	function kaamase_registration_errors() {

		$key    = kaamase_registration_key() . '_err';
		$errors = get_transient( $key );

		if ( $errors ) {
			delete_transient( $key );
		}

		return is_array( $errors ) ? $errors : array();
	}
}

if ( ! function_exists( 'kaamase_registration_input' ) ) {
	/**
	 * Store or read the values somebody entered.
	 *
	 * The password is never stored, for the obvious reason.
	 *
	 * @since 1.0.0
	 * @param array|null $values Values to store, or null to read.
	 * @return array Stored values.
	 */
	function kaamase_registration_input( $values = null ) {

		$key = kaamase_registration_key() . '_val';

		if ( null !== $values ) {
			set_transient( $key, (array) $values, 15 * MINUTE_IN_SECONDS );
			return (array) $values;
		}

		$stored = get_transient( $key );

		return is_array( $stored ) ? $stored : array();
	}
}

if ( ! function_exists( 'kaamase_registration_fail' ) ) {
	/**
	 * Store errors and send the person back to the form.
	 *
	 * @since 1.0.0
	 * @param string[] $errors Messages to show.
	 * @return void
	 */
	function kaamase_registration_fail( $errors ) {

		set_transient( kaamase_registration_key() . '_err', (array) $errors, 15 * MINUTE_IN_SECONDS );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$type = isset( $_POST['kaamase_type'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_type'] ) ) : '';

		$url = kaamase_page_url( 'register' );

		if ( $type ) {
			$url = add_query_arg( 'type', $type, $url );
		}

		wp_safe_redirect( $url );
		exit;
	}
}

if ( ! function_exists( 'kaamase_registration_clear' ) ) {
	/**
	 * Clear stored form state after success.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_registration_clear() {
		delete_transient( kaamase_registration_key() . '_err' );
		delete_transient( kaamase_registration_key() . '_val' );
	}
}

if ( ! function_exists( 'kaamase_registration_rate_ok' ) ) {
	/**
	 * Whether this connection may attempt another registration.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_registration_rate_ok() {

		/*
		 * Durable, not a transient. This is what stops one connection
		 * making accounts in bulk, and a transient is cleared by any
		 * caching plugin's Clear Transients button.
		 */
		return kaamase_rate_bump( kaamase_registration_key() . '_rate', HOUR_IN_SECONDS ) <= 5;
	}
}
