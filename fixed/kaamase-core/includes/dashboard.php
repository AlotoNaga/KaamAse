<?php
/**
 * Dashboard.
 *
 * The front end account screen. Everything a worker or employer does
 * with their own account happens here, and none of it happens in
 * wp-admin.
 *
 * The design problem this file solves
 * -----------------------------------
 * A worker who registers, sees a page saying nothing, and closes it, is
 * a worker who never gets hired and never comes back. So the dashboard
 * is not a menu. It is a short list of the specific things this
 * particular person still needs to do, in the order that matters, with
 * the reason attached to each one.
 *
 * A profile with no photo and no vouch is close to unusable to a
 * contractor deciding who to call. Telling somebody that plainly, on
 * their own screen, with a button next to it, is worth more than any
 * feature on the roadmap.
 *
 * The banner slot
 * ---------------
 * kaamase_before_main is already fired by the theme header. When phone
 * verification ships, the prompt to existing accounts hooks in here and
 * nothing else has to change.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE SHORTCODE
   ========================================================================== */

if ( ! function_exists( 'kaamase_dashboard_shortcode' ) ) {
	/**
	 * Render the dashboard.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_dashboard_shortcode() {

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><p>%1$s <a href="%2$s">%3$s</a> %4$s <a href="%5$s">%6$s</a></p></div>',
				esc_html__( 'Sign in to see your account.', 'kaamase-core' ),
				esc_url( wp_login_url( kaamase_page_url( 'dashboard' ) ) ),
				esc_html__( 'Sign in', 'kaamase-core' ),
				esc_html__( 'or', 'kaamase-core' ),
				esc_url( kaamase_page_url( 'register' ) ),
				esc_html__( 'register free', 'kaamase-core' )
			);
		}

		$user_id = get_current_user_id();
		$type    = kaamase_get_user_type( $user_id );
		$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		ob_start();

		echo kaamase_dashboard_flash(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo kaamase_verification_banner( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// An account with no profile should not exist, but if it does, fix it rather than showing a broken page.
		if ( ! $profile || ! get_post( $profile ) ) {
			echo kaamase_dashboard_no_profile( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return (string) ob_get_clean();
		}

		echo kaamase_dashboard_greeting( $user_id, $profile ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/**
		 * Fires near the top of the dashboard, above the profile checklist.
		 *
		 * The slot for anything that is waiting on this person right now.
		 * hires.php uses it to ask whether a hire happened, which is the
		 * question the whole rating system depends on being asked.
		 *
		 * @since 1.2.0
		 * @param int    $user_id User ID.
		 * @param int    $profile Profile post ID.
		 * @param string $type    worker or employer.
		 */
		do_action( 'kaamase_dashboard_prompts', $user_id, $profile, $type );

		echo kaamase_dashboard_todo( $profile, $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo kaamase_dashboard_actions( $type, $profile ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/**
		 * Fires below the main dashboard actions.
		 *
		 * @since 1.2.0
		 * @param int    $user_id User ID.
		 * @param int    $profile Profile post ID.
		 * @param string $type    worker or employer.
		 */
		do_action( 'kaamase_dashboard_sections', $user_id, $profile, $type );

		if ( 'employer' === $type ) {
			echo kaamase_dashboard_my_jobs( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo kaamase_dashboard_footer_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_dashboard', 'kaamase_dashboard_shortcode' );


/* ==========================================================================
   2. PROFILE COMPLETENESS

   The heart of the screen. Each item names what is missing and why it
   matters, in the words a person would actually use.
   ========================================================================== */

if ( ! function_exists( 'kaamase_profile_todo' ) ) {
	/**
	 * What this profile is still missing, most important first.
	 *
	 * The ordering is not arbitrary. A photograph and a vouch are what a
	 * contractor looks at before deciding whether to call. A day rate is
	 * what stops the call being wasted. Everything else is detail.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Profile post ID.
	 * @param string $type    worker or employer.
	 * @return array[] Items, each with label, why, url and done.
	 */
	function kaamase_profile_todo( $post_id, $type ) {

		$edit = add_query_arg( 'edit', $post_id, kaamase_page_url( 'dashboard' ) );
		$todo = array();

		if ( 'employer' === $type ) {

			$todo[] = array(
				'label' => __( 'Add your phone number', 'kaamase-core' ),
				'why'   => __( 'Workers cannot be introduced to you without it.', 'kaamase-core' ),
				'url'   => $edit,
				'done'  => (bool) kaamase_read_field( $post_id, 'phone' ),
			);

			$todo[] = array(
				'label' => __( 'Say what kind of employer you are', 'kaamase-core' ),
				'why'   => __( 'Workers treat an individual building a house differently from a contractor.', 'kaamase-core' ),
				'url'   => $edit,
				'done'  => (bool) kaamase_read_field( $post_id, 'employer_type' ),
			);

			$todo[] = array(
				'label' => __( 'Add your town', 'kaamase-core' ),
				'why'   => __( 'Workers nearby will see your jobs first.', 'kaamase-core' ),
				'url'   => $edit,
				'done'  => (bool) kaamase_read_field( $post_id, 'town' ),
			);

			return $todo;
		}

		/* ---- Worker ---- */

		$todo[] = array(
			'label' => __( 'Add a photo of yourself', 'kaamase-core' ),
			'why'   => __( 'A profile with a photo gets called far more often. A plain head and shoulders picture is enough.', 'kaamase-core' ),
			'url'   => $edit,
			'done'  => has_post_thumbnail( $post_id ),
		);

		$todo[] = array(
			'label' => __( 'Set your day rate', 'kaamase-core' ),
			'why'   => __( 'Employers filter by rate. With no rate, you are left out of most searches.', 'kaamase-core' ),
			'url'   => $edit,
			'done'  => (bool) kaamase_read_field( $post_id, 'day_rate' ),
		);

		$todo[] = array(
			'label' => __( 'Get someone to vouch for you', 'kaamase-core' ),
			'why'   => __( 'A colony council member, GB, church elder or a past employer. This is the strongest thing on your profile.', 'kaamase-core' ),
			'url'   => $edit,
			'done'  => (bool) kaamase_read_field( $post_id, 'vouched_by' ),
		);

		$todo[] = array(
			'label' => __( 'Add your years of experience', 'kaamase-core' ),
			'why'   => __( 'It is the second thing an employer looks at, after the trade.', 'kaamase-core' ),
			'url'   => $edit,
			'done'  => (bool) kaamase_read_field( $post_id, 'years_experience' ),
		);

		$todo[] = array(
			'label' => __( 'Add your town or village', 'kaamase-core' ),
			'why'   => __( 'Employers look for workers near the site.', 'kaamase-core' ),
			'url'   => $edit,
			'done'  => (bool) kaamase_read_field( $post_id, 'town' ),
		);

		$todo[] = array(
			'label' => __( 'Say which languages you speak', 'kaamase-core' ),
			'why'   => __( 'It helps an employer know you can talk to each other on site.', 'kaamase-core' ),
			'url'   => $edit,
			'done'  => (bool) wp_get_object_terms( $post_id, 'kaamase_language', array( 'fields' => 'ids' ) ),
		);

		/**
		 * Filter the profile checklist.
		 *
		 * @since 1.0.0
		 * @param array[] $todo    Checklist items.
		 * @param int     $post_id Profile ID.
		 * @param string  $type    worker or employer.
		 */
		return (array) apply_filters( 'kaamase_profile_todo', $todo, $post_id, $type );
	}
}

if ( ! function_exists( 'kaamase_profile_strength' ) ) {
	/**
	 * How complete a profile is, as a percentage.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Profile ID.
	 * @param string $type    worker or employer.
	 * @return int 0 to 100.
	 */
	function kaamase_profile_strength( $post_id, $type ) {

		$todo = kaamase_profile_todo( $post_id, $type );

		if ( empty( $todo ) ) {
			return 100;
		}

		$done = count(
			array_filter(
				$todo,
				static function ( $item ) {
					return ! empty( $item['done'] );
				}
			)
		);

		return (int) round( ( $done / count( $todo ) ) * 100 );
	}
}

if ( ! function_exists( 'kaamase_dashboard_todo' ) ) {
	/**
	 * Render the checklist.
	 *
	 * Outstanding items first, and only the first three, because a list
	 * of nine things somebody has not done reads as a telling off. The
	 * rest appear as each one gets ticked.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Profile ID.
	 * @param string $type    worker or employer.
	 * @return string Markup.
	 */
	function kaamase_dashboard_todo( $post_id, $type ) {

		$todo     = kaamase_profile_todo( $post_id, $type );
		$strength = kaamase_profile_strength( $post_id, $type );

		$pending = array_values(
			array_filter(
				$todo,
				static function ( $item ) {
					return empty( $item['done'] );
				}
			)
		);

		ob_start();

		if ( empty( $pending ) ) {
			?>
			<div class="ka-notice ka-notice--ok ka-mt-6">
				<div>
					<span class="ka-notice__title"><?php esc_html_e( 'Your profile is complete', 'kaamase-core' ); ?></span>
					<?php esc_html_e( 'Keep your availability up to date and you will show up in more searches.', 'kaamase-core' ); ?>
				</div>
			</div>
			<?php

			return (string) ob_get_clean();
		}
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">

			<div class="ka-cluster ka-cluster--between">
				<h2><?php esc_html_e( 'Finish your profile', 'kaamase-core' ); ?></h2>
				<span class="ka-badge ka-badge--neutral">
					<?php
					printf(
						/* translators: %d: percentage complete */
						esc_html__( '%d%% done', 'kaamase-core' ),
						absint( $strength )
					);
					?>
				</span>
			</div>

			<p class="ka-small ka-soft ka-mt-4">
				<?php
				echo 'worker' === $type
					? esc_html__( 'Employers skip past profiles that are half empty. These are the ones that matter most.', 'kaamase-core' )
					: esc_html__( 'Workers decide whether to answer based on what your profile shows.', 'kaamase-core' );
				?>
			</p>

			<ul class="ka-stack ka-mt-6">
				<?php foreach ( array_slice( $pending, 0, 3 ) as $item ) : ?>
					<li class="ka-card ka-card--flat">
						<div class="ka-cluster ka-cluster--between">
							<div>
								<strong><?php echo esc_html( $item['label'] ); ?></strong>
								<p class="ka-small ka-mute"><?php echo esc_html( $item['why'] ); ?></p>
							</div>
							<a class="ka-btn ka-btn--outline ka-btn--sm" href="<?php echo esc_url( $item['url'] ); ?>">
								<?php esc_html_e( 'Add', 'kaamase-core' ); ?>
							</a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( count( $pending ) > 3 ) : ?>
				<p class="ka-small ka-mute ka-mt-4">
					<?php
					printf(
						esc_html(
							/* translators: %s: number of remaining items */
							_n( '%s more thing after these.', '%s more things after these.', count( $pending ) - 3, 'kaamase-core' )
						),
						esc_html( number_format_i18n( count( $pending ) - 3 ) )
					);
					?>
				</p>
			<?php endif; ?>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   3. GREETING AND STATUS
   ========================================================================== */

if ( ! function_exists( 'kaamase_dashboard_greeting' ) ) {
	/**
	 * Name, availability control and a link to the public profile.
	 *
	 * The availability toggle is here rather than buried in an edit form
	 * because it is the one thing a worker changes several times a week,
	 * and every tap between them and it is a tap where they give up and
	 * leave themselves marked available while working somewhere else.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_dashboard_greeting( $user_id, $post_id ) {

		$user      = get_userdata( $user_id );
		$type      = kaamase_get_user_type( $user_id );
		$published = 'publish' === get_post_status( $post_id );

		ob_start();
		?>
		<section class="ka-card ka-card--pad-lg">

			<div class="ka-card__head">
				<?php echo kaamase_avatar( $post_id, 'kaamase-avatar', 'ka-avatar--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<div>
					<h1 class="ka-text-xl">
						<?php
						printf(
							/* translators: %s: person's name */
							esc_html__( 'Hello %s', 'kaamase-core' ),
							esc_html( $user ? $user->display_name : '' )
						);
						?>
					</h1>

					<?php if ( $published ) : ?>
						<p class="ka-small">
							<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
								<?php esc_html_e( 'See how others see your profile', 'kaamase-core' ); ?>
							</a>
						</p>
					<?php else : ?>
						<p class="ka-small ka-mute">
							<?php esc_html_e( 'Your profile is not live yet. Confirm your email to publish it.', 'kaamase-core' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( 'worker' === $type ) : ?>
				<?php echo kaamase_availability_control( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_availability_control' ) ) {
	/**
	 * Three buttons for availability.
	 *
	 * A form rather than a JavaScript toggle, so it works on a browser
	 * where the script never arrived.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_availability_control( $post_id ) {

		$current = (string) kaamase_read_field( $post_id, 'availability' );

		$options = array(
			'live'  => __( 'Available now', 'kaamase-core' ),
			'busy'  => __( 'Working', 'kaamase-core' ),
			'leave' => __( 'On leave', 'kaamase-core' ),
		);

		ob_start();
		?>
		<div class="ka-card__foot">
			<p class="ka-label"><?php esc_html_e( 'Are you free for work?', 'kaamase-core' ); ?></p>

			<form method="post" action="" class="ka-cluster">
				<?php wp_nonce_field( 'kaamase_availability', 'kaamase_availability_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="set_availability">
				<input type="hidden" name="kaamase_profile" value="<?php echo esc_attr( $post_id ); ?>">

				<?php foreach ( $options as $value => $label ) : ?>
					<button type="submit" name="kaamase_availability" value="<?php echo esc_attr( $value ); ?>"
						class="ka-chip <?php echo $current === $value ? 'ka-chip--on' : ''; ?>"
						<?php echo $current === $value ? ' aria-pressed="true"' : ' aria-pressed="false"'; ?>>
						<?php echo esc_html( $label ); ?>
					</button>
				<?php endforeach; ?>
			</form>

			<p class="ka-hint">
				<?php esc_html_e( 'Set yourself working when you take a job. It stops employers calling you for nothing, and it keeps your rating clean.', 'kaamase-core' ); ?>
			</p>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_handle_availability' ) ) {
	/**
	 * Save an availability change.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_availability() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'set_availability' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_availability_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_availability_nonce'] ) ), 'kaamase_availability' )
		) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$post_id = isset( $_POST['kaamase_profile'] ) ? absint( $_POST['kaamase_profile'] ) : 0;
		$value   = isset( $_POST['kaamase_availability'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_availability'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Capability, not just ownership. Ownership decides what is drawn, capability decides what is written.
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		kaamase_save_field( $post_id, 'availability', $value );

		wp_safe_redirect( add_query_arg( 'saved', 'availability', kaamase_page_url( 'dashboard' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_availability' );


/* ==========================================================================
   4. ACTIONS
   ========================================================================== */

if ( ! function_exists( 'kaamase_dashboard_actions' ) ) {
	/**
	 * The main things this user does.
	 *
	 * @since 1.0.0
	 * @param string $type    worker or employer.
	 * @param int    $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_dashboard_actions( $type, $post_id ) {

		$edit = add_query_arg( 'edit', $post_id, kaamase_page_url( 'dashboard' ) );

		if ( 'employer' === $type ) {

			$actions = array(
				array( kaamase_page_url( 'post_job' ), __( 'Post a job', 'kaamase-core' ), 'action' ),
				array( home_url( '/workers/' ), __( 'Find workers', 'kaamase-core' ), 'outline' ),
				array( $edit, __( 'Edit my details', 'kaamase-core' ), 'outline' ),
				array( kaamase_page_url( 'saved' ), __( 'Saved workers', 'kaamase-core' ), 'ghost' ),
			);

		} else {

			$actions = array(
				array( home_url( '/jobs/' ), __( 'Find work', 'kaamase-core' ), 'action' ),
				array( $edit, __( 'Edit my profile', 'kaamase-core' ), 'outline' ),
				array( kaamase_page_url( 'saved' ), __( 'Saved jobs', 'kaamase-core' ), 'ghost' ),
			);
		}

		/*
		 * Who looked at you, for both kinds of account.
		 *
		 * Here rather than in the header. The header is for places to
		 * browse to; this is a fact about you, and the moment somebody
		 * wants it is just after signing in, which is the moment they
		 * are looking at this screen.
		 *
		 * The page is checked for, not assumed. kaamase_page_url falls
		 * back to a guessed address when nothing is stored, so testing
		 * it for emptiness proves nothing: before the pages are built
		 * this would be a button on the dashboard leading to a 404.
		 */
		$stored_pages = (array) get_option( 'kaamase_pages', array() );

		if ( ! empty( $stored_pages['who-looked'] )
			&& 'publish' === get_post_status( (int) $stored_pages['who-looked'] ) ) {

			$actions[] = array(
				kaamase_page_url( 'who-looked' ),
				__( 'Who looked at you', 'kaamase-core' ),
				'ghost'
			);
		}

		ob_start();
		?>
		<section class="ka-grid ka-grid--2 ka-mt-6">
			<?php foreach ( $actions as $action ) : ?>
				<a class="ka-btn ka-btn--<?php echo esc_attr( $action[2] ); ?> ka-btn--lg ka-btn--block"
					href="<?php echo esc_url( $action[0] ); ?>">
					<?php echo esc_html( $action[1] ); ?>
				</a>
			<?php endforeach; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_dashboard_held_notice' ) ) {
	/**
	 * The message somebody sees straight after a job is held.
	 *
	 * Shown on arrival rather than left for them to work out from a
	 * status label. The whole risk with holding a post is that it feels
	 * like a fault, and a fault is something people report or walk away
	 * from rather than wait out.
	 *
	 * @since 1.1.0
	 * @return string Markup, or an empty string.
	 */
	function kaamase_dashboard_held_notice() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only banner.
		if ( empty( $_GET['held'] ) ) {
			return '';
		}

		return sprintf(
			'<div class="ka-notice ka-notice--ok ka-mb-4"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
			esc_html__( 'Your job is saved and is being checked', 'kaamase-core' ),
			esc_html__( 'A person reads the first job from every new employer before workers see it. It is usually done within a few hours, and you will get an email when it goes live. Nothing more is needed from you. After this one, your jobs go live straight away.', 'kaamase-core' )
		);
	}
}

if ( ! function_exists( 'kaamase_dashboard_my_jobs' ) ) {
	/**
	 * An employer's own job posts.
	 *
	 * Includes closed ones, because the most valuable thing on this list
	 * is a job that closed last week which the employer wants to run
	 * again. Reposting is one tap.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string Markup.
	 */
	function kaamase_dashboard_my_jobs( $user_id ) {

		$jobs = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'author'         => $user_id,
				'posts_per_page' => 10,
				/*
				 * Pending belongs here.
				 *
				 * A job held for checking used to be missing from this
				 * list entirely. The employer filled in the form, got a
				 * confirmation, came back to the dashboard, and found
				 * nothing. There is no reading of that except that the
				 * site lost their job, and somebody who believes that
				 * does not post a second one.
				 */
				'post_status'    => array( 'publish', 'pending', 'draft', 'kaamase_closed' ),
				'no_found_rows'  => true,
			)
		);

		ob_start();
		?>
		<section class="ka-mt-6">

			<h2><?php esc_html_e( 'My jobs', 'kaamase-core' ); ?></h2>

			<?php echo kaamase_dashboard_held_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( empty( $jobs ) ) : ?>

				<div class="ka-card ka-card--flat ka-mt-4">
					<p class="ka-soft"><?php esc_html_e( 'You have not posted a job yet.', 'kaamase-core' ); ?></p>
					<a class="ka-btn ka-btn--action ka-mt-4" href="<?php echo esc_url( kaamase_page_url( 'post_job' ) ); ?>">
						<?php esc_html_e( 'Post your first job', 'kaamase-core' ); ?>
					</a>
				</div>

			<?php else : ?>

				<ul class="ka-stack ka-mt-4">
					<?php foreach ( $jobs as $job ) : ?>
						<?php
						$open   = kaamase_job_is_open( $job->ID );
						$status = get_post_status( $job->ID );
						?>
						<li class="ka-card ka-card--flat">
							<div class="ka-cluster ka-cluster--between">
								<div>
									<a href="<?php echo esc_url( get_permalink( $job->ID ) ); ?>">
										<strong><?php echo esc_html( get_the_title( $job->ID ) ); ?></strong>
									</a>
									<p class="ka-small ka-mute">
										<?php
										if ( 'pending' === $status ) {
											echo esc_html__( 'Being checked', 'kaamase-core' );
										} elseif ( 'draft' === $status ) {
											echo esc_html__( 'Waiting for you to confirm your email', 'kaamase-core' );
										} else {
											echo $open
												? esc_html__( 'Open', 'kaamase-core' )
												: esc_html__( 'Closed', 'kaamase-core' );
										}
										?>
										<span aria-hidden="true">&middot;</span>
										<?php echo esc_html( get_the_date( '', $job->ID ) ); ?>
									</p>

									<?php if ( 'pending' === $status ) : ?>
										<p class="ka-small ka-soft">
											<?php esc_html_e( 'Your first job is read by a person before workers see it. Usually done within a few hours. Nothing more is needed from you.', 'kaamase-core' ); ?>
										</p>
									<?php endif; ?>
								</div>

								<?php if ( 'pending' === $status || 'draft' === $status ) : ?>
									<?php /* Nothing to repost or fill while it is not live yet. */ ?>
								<?php elseif ( ! $open ) : ?>
									<form method="post" action="">
										<?php wp_nonce_field( 'kaamase_repost', 'kaamase_repost_nonce' ); ?>
										<input type="hidden" name="kaamase_action" value="repost_job">
										<input type="hidden" name="kaamase_job" value="<?php echo esc_attr( $job->ID ); ?>">
										<button class="ka-btn ka-btn--outline ka-btn--sm" type="submit">
											<?php esc_html_e( 'Post again', 'kaamase-core' ); ?>
										</button>
									</form>
								<?php else : ?>
									<?php
									/*
									 * Closing a job that is already filled.
									 *
									 * Without this an employer who found
									 * somebody on day two has no way to say
									 * so, and workers keep ringing about a
									 * job that went three weeks ago. That is
									 * the single fastest way to teach people
									 * the listings are not worth answering.
									 */
									?>
									<form method="post" action="">
										<?php wp_nonce_field( 'kaamase_fill_job', 'kaamase_fill_nonce' ); ?>
										<input type="hidden" name="kaamase_action" value="fill_job">
										<input type="hidden" name="kaamase_job" value="<?php echo esc_attr( $job->ID ); ?>">
										<button class="ka-btn ka-btn--ghost ka-btn--sm" type="submit">
											<?php esc_html_e( 'Mark as filled', 'kaamase-core' ); ?>
										</button>
									</form>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>

			<?php endif; ?>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_handle_repost' ) ) {
	/**
	 * Reopen a closed job.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_repost() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'repost_job' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			empty( $_POST['kaamase_repost_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_repost_nonce'] ) ), 'kaamase_repost' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$job_id = isset( $_POST['kaamase_job'] ) ? absint( $_POST['kaamase_job'] ) : 0;

		if ( ! $job_id || ! current_user_can( 'edit_post', $job_id ) ) {
			return;
		}

		// Clear the old expiry so post-types.php stamps a fresh one.
		delete_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires' );
		kaamase_save_field( $job_id, 'job_status', 'open' );

		wp_update_post(
			array(
				'ID'            => $job_id,
				'post_status'   => 'publish',
				'post_date'     => current_time( 'mysql' ),
				'post_date_gmt' => current_time( 'mysql', 1 ),
			)
		);

		wp_safe_redirect( add_query_arg( 'saved', 'reposted', kaamase_page_url( 'dashboard' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_repost' );


/* ==========================================================================
   5. BANNERS AND MESSAGES
   ========================================================================== */

if ( ! function_exists( 'kaamase_verification_banner' ) ) {
	/**
	 * Prompt an unverified account to confirm their email.
	 *
	 * States the consequence rather than the instruction. Confirm your
	 * email means nothing to somebody. Nobody can find you until you do
	 * means something.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string Markup.
	 */
	function kaamase_verification_banner( $user_id ) {

		if ( kaamase_user_is_verified( $user_id ) ) {
			return '';
		}

		$user = get_userdata( $user_id );

		ob_start();
		?>
		<div class="ka-banner">
			<div>
				<strong><?php esc_html_e( 'Your profile is hidden until you confirm your email', 'kaamase-core' ); ?></strong>
				<p class="ka-small ka-mt-4">
					<?php
					printf(
						/* translators: %s: email address */
						esc_html__( 'We sent a link to %s. Tap it and your profile goes live straight away.', 'kaamase-core' ),
						'<strong>' . esc_html( $user ? $user->user_email : '' ) . '</strong>'
					);
					?>
				</p>
			</div>

			<form method="post" action="">
				<?php wp_nonce_field( 'kaamase_resend', 'kaamase_resend_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="resend_verification">
				<button class="ka-btn ka-btn--outline ka-btn--sm" type="submit">
					<?php esc_html_e( 'Send it again', 'kaamase-core' ); ?>
				</button>
			</form>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_dashboard_flash' ) ) {
	/**
	 * Confirmation messages after an action.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_dashboard_flash() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$verify = isset( $_GET['verify'] ) ? sanitize_key( wp_unslash( $_GET['verify'] ) ) : '';
		$saved  = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : '';
		$new    = ! empty( $_GET['welcome'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$messages = array(
			'done'   => array( 'ok', __( 'Email confirmed. Your profile is live.', 'kaamase-core' ) ),
			'failed' => array( 'error', __( 'That link did not work. It may have expired. Send a new one below.', 'kaamase-core' ) ),
			'sent'   => array( 'ok', __( 'Sent. Check your email.', 'kaamase-core' ) ),
			'wait'   => array( 'warn', __( 'We just sent one. Please wait a few minutes before asking again.', 'kaamase-core' ) ),
		);

		$saved_messages = array(
			'availability' => __( 'Availability updated.', 'kaamase-core' ),
			'reposted'     => __( 'Job posted again. It is open for three weeks.', 'kaamase-core' ),
			'profile'      => __( 'Profile saved.', 'kaamase-core' ),
			'blocked'      => __( 'Done. That person can no longer see your number.', 'kaamase-core' ),
			'filled'       => __( 'Job closed. Workers will stop calling about it.', 'kaamase-core' ),
			'hired'        => __( 'Thank you. You can rate each other now, and it helps the next person.', 'kaamase-core' ),
			'nothired'     => __( 'Noted. We will not ask about that one again.', 'kaamase-core' ),
			'team'         => __( 'Team saved.', 'kaamase-core' ),
			'teamgone'     => __( 'Your team listing is down. Your own profile is untouched.', 'kaamase-core' ),
		);

		$out = '';

		if ( $new ) {
			$out .= sprintf(
				'<div class="ka-notice ka-notice--ok"><div><span class="ka-notice__title">%1$s</span>%2$s</div></div>',
				esc_html__( 'Account created', 'kaamase-core' ),
				esc_html__( 'One more step below, then people can find you.', 'kaamase-core' )
			);
		}

		if ( $verify && isset( $messages[ $verify ] ) ) {
			$out .= sprintf(
				'<div class="ka-notice ka-notice--%1$s"><p>%2$s</p></div>',
				esc_attr( $messages[ $verify ][0] ),
				esc_html( $messages[ $verify ][1] )
			);
		}

		if ( $saved && isset( $saved_messages[ $saved ] ) ) {
			$out .= sprintf(
				'<div class="ka-notice ka-notice--ok"><p>%s</p></div>',
				esc_html( $saved_messages[ $saved ] )
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_dashboard_no_profile' ) ) {
	/**
	 * Recover an account whose profile is missing.
	 *
	 * Should never happen, since registration creates both together. It
	 * exists because should never happen is not the same as does not
	 * happen, and the alternative is a person staring at a blank screen
	 * with no way forward.
	 *
	 * @since 1.0.0
	 * @param string $type User type.
	 * @return string Markup.
	 */
	function kaamase_dashboard_no_profile( $type ) {

		ob_start();
		?>
		<div class="ka-empty">
			<h2 class="ka-empty__title"><?php esc_html_e( 'Your profile is missing', 'kaamase-core' ); ?></h2>
			<p class="ka-empty__text">
				<?php esc_html_e( 'Something went wrong when your account was made. Tap below and we will build it again. Nothing else about your account changes.', 'kaamase-core' ); ?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( 'kaamase_rebuild', 'kaamase_rebuild_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="rebuild_profile">
				<input type="hidden" name="kaamase_type" value="<?php echo esc_attr( $type ); ?>">
				<button class="ka-btn ka-btn--primary" type="submit">
					<?php esc_html_e( 'Rebuild my profile', 'kaamase-core' ); ?>
				</button>
			</form>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_handle_rebuild' ) ) {
	/**
	 * Recreate a missing profile post.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_rebuild() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'rebuild_profile' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_rebuild_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_rebuild_nonce'] ) ), 'kaamase_rebuild' )
		) {
			return;
		}

		$user_id = get_current_user_id();

		// Do not create a second one if the first turns up.
		if ( get_user_meta( $user_id, 'kaamase_profile_id', true ) ) {

			$existing = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

			if ( get_post( $existing ) ) {
				wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
				exit;
			}
		}

		$user = get_userdata( $user_id );
		$type = kaamase_get_user_type( $user_id );

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'employer' === $type ? 'kaamase_employer' : 'kaamase_worker',
				'post_title'  => $user ? $user->display_name : __( 'My profile', 'kaamase-core' ),
				'post_status' => kaamase_user_is_verified( $user_id ) ? 'publish' : 'draft',
				'post_author' => $user_id,
			),
			true
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_user_meta( $user_id, 'kaamase_profile_id', $post_id );
		}

		wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_rebuild' );

if ( ! function_exists( 'kaamase_dashboard_footer_links' ) ) {
	/**
	 * Account links at the bottom of the screen.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_dashboard_footer_links() {

		ob_start();
		?>
		<section class="ka-card ka-card--flat ka-mt-6">
			<ul class="ka-cluster ka-cluster--gap-lg">
				<li>
					<a class="ka-small" href="<?php echo esc_url( kaamase_page_url( 'report' ) ); ?>">
						<?php esc_html_e( 'Report a problem', 'kaamase-core' ); ?>
					</a>
				</li>
				<li>
					<a class="ka-small" href="<?php echo esc_url( kaamase_page_url( 'grievance' ) ); ?>">
						<?php esc_html_e( 'Wage complaint', 'kaamase-core' ); ?>
					</a>
				</li>
				<li>
					<a class="ka-small" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
						<?php esc_html_e( 'Change password', 'kaamase-core' ); ?>
					</a>
				</li>
				<li>
					<a class="ka-small" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
						<?php esc_html_e( 'Sign out', 'kaamase-core' ); ?>
					</a>
				</li>
			</ul>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   6. THEME INTEGRATION
   ========================================================================== */

/**
 * Send people to their dashboard after signing in.
 *
 * @since 1.0.0
 * @param string $url Default destination.
 * @return string Dashboard URL.
 */
function kaamase_login_destination( $url ) {

	unset( $url );

	return kaamase_page_url( 'dashboard' );
}
add_filter( 'kaamase_login_destination', 'kaamase_login_destination' );

/**
 * Give each user type the right bottom tab bar.
 *
 * @since 1.0.0
 * @param array  $tabs Default tabs from the theme.
 * @param string $type Current user type.
 * @return array Filtered tabs.
 */
function kaamase_tabbar_by_type( $tabs, $type ) {

	if ( 'employer' === $type ) {
		return array(
			array(
				'url'   => kaamase_page_url( 'post_job' ),
				'label' => __( 'Post job', 'kaamase-core' ),
				'icon'  => 'plus',
			),
			array(
				'url'   => home_url( '/workers/' ),
				'label' => __( 'Workers', 'kaamase-core' ),
				'icon'  => 'users',
			),
			array(
				'url'   => kaamase_page_url( 'saved' ),
				'label' => __( 'Saved', 'kaamase-core' ),
				'icon'  => 'heart',
			),
			array(
				'url'   => kaamase_page_url( 'dashboard' ),
				'label' => __( 'Me', 'kaamase-core' ),
				'icon'  => 'user',
			),
		);
	}

	if ( 'worker' === $type ) {
		return array(
			array(
				'url'   => home_url( '/jobs/' ),
				'label' => __( 'Find work', 'kaamase-core' ),
				'icon'  => 'briefcase',
			),
			array(
				'url'   => kaamase_page_url( 'saved' ),
				'label' => __( 'Saved', 'kaamase-core' ),
				'icon'  => 'heart',
			),
			array(
				'url'   => kaamase_page_url( 'dashboard' ),
				'label' => __( 'Me', 'kaamase-core' ),
				'icon'  => 'user',
			),
		);
	}

	return $tabs;
}
add_filter( 'kaamase_tabbar_items', 'kaamase_tabbar_by_type', 10, 2 );