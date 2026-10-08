<?php
/**
 * A professional profile page, and the page shown instead when somebody
 * may not read it.
 *
 * Shipped with the plugin rather than the theme, so these pages work and
 * keep their rules whatever theme is active. Chosen in
 * includes/professional-profiles.php; never loaded on its own.
 *
 * Built from the same pieces as the worker profile page, in the order an
 * employer decides on: who and where, what they want, what they have
 * done, then their own words. No phone number is printed here. Contact
 * goes through the platform's contact screen, which gates, logs and
 * counts every lookup.
 *
 * @package KaamaseCore
 * @version 1.0.1
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

/* --------------------------------------------------------------------------
 * Not for this visitor.
 * ------------------------------------------------------------------------ */

if ( ! empty( $GLOBALS['kaamase_prof_refused'] ) ) :

	$kaamase_viewer  = get_current_user_id();
	$kaamase_refusal = function_exists( 'kaamase_prof_browse_refusal' ) ? kaamase_prof_browse_refusal( $kaamase_viewer ) : array( 'code' => '' );
	$kaamase_here    = (string) get_permalink( (int) $GLOBALS['kaamase_prof_refused'] );
	$kaamase_page    = function_exists( 'kaamase_page_url' ) ? 'kaamase_page_url' : null;

	switch ( $kaamase_refusal['code'] ) {

		case 'signed_out':
			$kaamase_title   = __( 'This professional profile is for employers', 'kaamase-core' );
			$kaamase_body    = __( 'Professional profiles are shown to employers who are signed in to Kaam Ase. Sign in to see this one.', 'kaamase-core' );
			$kaamase_buttons = array(
				array( wp_login_url( $kaamase_here ), __( 'Sign in', 'kaamase-core' ), 'ka-btn--primary' ),
				array( $kaamase_page ? add_query_arg( 'type', 'employer', $kaamase_page( 'register' ) ) : '', __( 'Register as an employer', 'kaamase-core' ), 'ka-btn--outline' ),
			);
			break;

		case 'unverified':
			$kaamase_title   = __( 'Confirm your email first', 'kaamase-core' );
			$kaamase_body    = __( 'Confirm your email to see professional profiles. We sent you a link when you registered.', 'kaamase-core' );
			$kaamase_buttons = array(
				array( $kaamase_page ? $kaamase_page( 'dashboard' ) : '', __( 'Go to my account', 'kaamase-core' ), 'ka-btn--primary' ),
			);
			break;

		case 'not_hiring':
			$kaamase_title   = __( 'This professional profile is for employers', 'kaamase-core' );
			$kaamase_body    = __( 'Professional profiles are shown to accounts that hire. Add hiring to your account to see this one. It is free and happens straight away.', 'kaamase-core' );
			$kaamase_buttons = array(
				array( $kaamase_page ? $kaamase_page( 'post_job' ) : '', __( 'Add hiring to my account', 'kaamase-core' ), 'ka-btn--primary' ),
			);
			break;

		default:
			$kaamase_title   = __( 'This profile is not available', 'kaamase-core' );
			$kaamase_body    = __( 'Its owner may have hidden it.', 'kaamase-core' );
			$kaamase_buttons = array();
	}
	?>

	<div class="ka-container ka-container--narrow ka-section">
		<div class="ka-card ka-card--pad-lg ka-center">

			<h1><?php echo esc_html( $kaamase_title ); ?></h1>
			<p class="ka-soft ka-mt-4"><?php echo esc_html( $kaamase_body ); ?></p>

			<div class="ka-cluster ka-mt-6" style="justify-content:center;">
				<?php foreach ( $kaamase_buttons as $kaamase_button ) : ?>
					<?php if ( '' !== $kaamase_button[0] ) : ?>
						<a class="ka-btn <?php echo esc_attr( $kaamase_button[2] ); ?>" href="<?php echo esc_url( $kaamase_button[0] ); ?>"><?php echo esc_html( $kaamase_button[1] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php if ( function_exists( 'kaamase_prof_url' ) ) : ?>
				<p class="ka-small ka-mt-6">
					<a href="<?php echo esc_url( kaamase_prof_url( 'professionals' ) ); ?>"><?php esc_html_e( 'See the professionals you can see', 'kaamase-core' ); ?></a>
				</p>
			<?php endif; ?>

		</div>
	</div>

	<?php
	get_footer();
	return;
endif;

/* --------------------------------------------------------------------------
 * The profile.
 * ------------------------------------------------------------------------ */

while ( have_posts() ) :
	the_post();

	$kaamase_id         = get_the_ID();
	$kaamase_owner_id   = (int) get_post_field( 'post_author', $kaamase_id );
	$kaamase_viewer     = get_current_user_id();
	$kaamase_owner      = $kaamase_viewer && $kaamase_viewer === $kaamase_owner_id;
	$kaamase_may        = function_exists( 'kaamase_prof_may_browse' ) && kaamase_prof_may_browse( $kaamase_viewer );
	$kaamase_names      = kaamase_prof_category_names();
	$kaamase_languages  = kaamase_prof_language_names();
	$kaamase_experience = absint( kaamase_read_field( $kaamase_id, 'prof_experience' ) );
	$kaamase_salary     = absint( kaamase_read_field( $kaamase_id, 'prof_salary' ) );
	$kaamase_status     = (string) kaamase_read_field( $kaamase_id, 'prof_status' );
	$kaamase_qualify    = kaamase_prof_label( 'qualification', (string) kaamase_read_field( $kaamase_id, 'prof_qualification' ) );
	$kaamase_course     = (string) kaamase_read_field( $kaamase_id, 'prof_course' );
	$kaamase_institute  = (string) kaamase_read_field( $kaamase_id, 'prof_institute' );
	$kaamase_passed     = absint( kaamase_read_field( $kaamase_id, 'prof_passed' ) );
	$kaamase_notice     = kaamase_prof_label( 'notice', (string) kaamase_read_field( $kaamase_id, 'prof_notice' ) );
	$kaamase_where      = kaamase_prof_label( 'where', (string) kaamase_read_field( $kaamase_id, 'prof_where' ) );
	$kaamase_jobs       = kaamase_prof_jobs_of( $kaamase_id );
	$kaamase_skills     = array_map( 'strval', kaamase_prof_list( $kaamase_id, KAAMASE_PROF_SKILLS_KEY ) );
	$kaamase_spoken     = array_map( 'strval', kaamase_prof_list( $kaamase_id, KAAMASE_PROF_LANGUAGES_KEY ) );
	$kaamase_looked     = function_exists( 'kaamase_contact_log' ) ? count( kaamase_contact_log( $kaamase_id, 200 ) ) : 0;
	?>

	<div class="ka-container ka-section ka-profile">

		<?php if ( $kaamase_owner ) : ?>
			<div class="ka-notice ka-notice--info ka-mb-4">
				<div>
					<span class="ka-notice__title"><?php esc_html_e( 'This is your professional profile', 'kaamase-core' ); ?></span>
					<p>
						<?php
						if ( ! kaamase_prof_is_public( $kaamase_id ) ) {
							esc_html_e( 'Employers signed in to Kaam Ase see exactly this. Nobody else can open it.', 'kaamase-core' );
						} elseif ( function_exists( 'kaamase_is_off_google' ) && kaamase_is_off_google( $kaamase_owner_id ) ) {
							// The account-wide switch wins. See off-google.php.
							esc_html_e( 'Anyone with the link can open it. It is kept off Google search, as your account is set.', 'kaamase-core' );
						} else {
							esc_html_e( 'Everyone can see it, including Google search.', 'kaamase-core' );
						}
						?>
					</p>
					<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="<?php echo esc_url( kaamase_prof_url( 'my_professional' ) ); ?>">
						<?php esc_html_e( 'Edit my professional profile', 'kaamase-core' ); ?>
					</a>
				</div>
			</div>
		<?php endif; ?>

		<article <?php post_class( 'ka-profile__article' ); ?>>

			<header class="ka-card ka-card--pad-lg ka-profile__head">

				<div class="ka-profile__identity">
					<?php echo function_exists( 'kaamase_avatar' ) ? kaamase_avatar( $kaamase_id, 'kaamase-avatar-lg', 'ka-avatar--xl' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<div class="ka-profile__titles">
						<h1 class="ka-profile__name">
							<?php echo esc_html( get_the_title( $kaamase_id ) ); ?>
							<?php echo function_exists( 'kaamase_called_badge' ) ? kaamase_called_badge( $kaamase_owner_id, true ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</h1>

						<?php echo function_exists( 'kaamase_place' ) ? kaamase_place( $kaamase_id ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

						<div class="ka-cluster ka-mt-4">
							<?php
							echo kaamase_prof_status_pill( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo function_exists( 'kaamase_called_badge' ) ? kaamase_called_badge( $kaamase_owner_id ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
					</div>
				</div>

				<p class="ka-lead ka-mt-6"><?php echo esc_html( (string) kaamase_read_field( $kaamase_id, 'prof_headline' ) ); ?></p>

				<div class="ka-profile__trades ka-mt-4">
					<span class="ka-cluster">
						<?php foreach ( kaamase_prof_categories_of( $kaamase_id ) as $kaamase_slug ) : ?>
							<span class="ka-chip"><?php echo esc_html( $kaamase_names[ $kaamase_slug ] ); ?></span>
						<?php endforeach; ?>
					</span>
				</div>

			</header>

			<section class="ka-facts ka-mt-6">

				<div class="ka-fact">
					<p class="ka-fact__label"><?php esc_html_e( 'Experience', 'kaamase-core' ); ?></p>
					<p class="ka-fact__value">
						<?php
						if ( $kaamase_experience ) {
							/* translators: %s: number of years */
							echo esc_html( sprintf( _n( '%s year', '%s years', $kaamase_experience, 'kaamase-core' ), number_format_i18n( $kaamase_experience ) ) );
						} elseif ( 'fresher' === $kaamase_status ) {
							esc_html_e( 'Fresher', 'kaamase-core' );
						} else {
							echo '<span class="ka-mute ka-small">' . esc_html__( 'Not given', 'kaamase-core' ) . '</span>';
						}
						?>
					</p>
				</div>

				<div class="ka-fact">
					<p class="ka-fact__label"><?php esc_html_e( 'Qualification', 'kaamase-core' ); ?></p>
					<p class="ka-fact__value"><?php echo esc_html( '' !== $kaamase_course ? $kaamase_course : preg_replace( '/\s*\(.*\)$/', '', $kaamase_qualify ) ); ?></p>
				</div>

				<div class="ka-fact">
					<p class="ka-fact__label"><?php esc_html_e( 'Expected salary', 'kaamase-core' ); ?></p>
					<p class="ka-fact__value">
						<?php
						if ( $kaamase_salary && function_exists( 'kaamase_wage' ) ) {
							echo kaamase_wage( $kaamase_salary, 'month' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<span class="ka-mute ka-small">' . esc_html__( 'To discuss', 'kaamase-core' ) . '</span>';
						}
						?>
					</p>
				</div>

				<?php if ( '' !== $kaamase_notice ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Can join', 'kaamase-core' ); ?></p>
						<p class="ka-fact__value"><?php echo esc_html( $kaamase_notice ); ?></p>
					</div>
				<?php endif; ?>

			</section>

			<?php
			$kaamase_study = array_filter(
				array(
					$kaamase_qualify,
					$kaamase_institute,
					/* translators: %d: year */
					$kaamase_passed ? sprintf( __( 'passed %d', 'kaamase-core' ), $kaamase_passed ) : '',
				)
			);
			?>
			<?php if ( $kaamase_study ) : ?>
				<p class="ka-soft ka-small ka-mt-4"><?php echo esc_html( implode( ' · ', $kaamase_study ) ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $kaamase_where ) : ?>
				<p class="ka-soft ka-small ka-mt-4">
					<?php
					/* translators: %s: where they will work, for example Anywhere in Nagaland */
					echo esc_html( sprintf( __( 'Will work: %s', 'kaamase-core' ), $kaamase_where ) );
					?>
				</p>
			<?php endif; ?>

			<?php if ( $kaamase_jobs ) : ?>
				<section class="ka-mt-6">
					<h2 class="ka-text-lg"><?php esc_html_e( 'Past jobs', 'kaamase-core' ); ?></h2>
					<ul class="ka-stack ka-mt-4">
						<?php foreach ( $kaamase_jobs as $kaamase_job ) : ?>
							<li class="ka-card ka-card--flat">
								<strong><?php echo esc_html( $kaamase_job['title'] ); ?></strong>
								<?php
								if ( $kaamase_job['from'] && $kaamase_job['to'] ) {
									$kaamase_when = $kaamase_job['from'] . ' – ' . $kaamase_job['to'];
								} elseif ( $kaamase_job['from'] ) {
									/* translators: %d: year they started */
									$kaamase_when = sprintf( __( '%d – now', 'kaamase-core' ), $kaamase_job['from'] );
								} elseif ( $kaamase_job['to'] ) {
									/* translators: %d: year they left */
									$kaamase_when = sprintf( __( 'until %d', 'kaamase-core' ), $kaamase_job['to'] );
								} else {
									$kaamase_when = '';
								}

								$kaamase_line = implode( ' · ', array_filter( array( $kaamase_job['employer'], $kaamase_when ) ) );
								?>
								<?php if ( '' !== $kaamase_line ) : ?>
									<p class="ka-small ka-soft"><?php echo esc_html( $kaamase_line ); ?></p>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( $kaamase_skills ) : ?>
				<section class="ka-mt-6">
					<h2 class="ka-text-lg"><?php esc_html_e( 'Skills', 'kaamase-core' ); ?></h2>
					<ul class="ka-cluster ka-mt-4">
						<?php foreach ( $kaamase_skills as $kaamase_skill ) : ?>
							<li class="ka-chip"><?php echo esc_html( $kaamase_skill ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php
			$kaamase_spoken = array_values( array_intersect_key( $kaamase_languages, array_flip( $kaamase_spoken ) ) );

			if ( $kaamase_spoken ) :
				?>
				<section class="ka-mt-6">
					<h2 class="ka-text-lg"><?php esc_html_e( 'Speaks', 'kaamase-core' ); ?></h2>
					<ul class="ka-cluster ka-mt-4">
						<?php foreach ( $kaamase_spoken as $kaamase_language ) : ?>
							<li class="ka-chip"><?php echo esc_html( $kaamase_language ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<div class="ka-prose ka-mt-6">
				<?php the_content(); ?>
			</div>

		</article>

		<?php
		if ( $kaamase_owner && $kaamase_looked && function_exists( 'kaamase_who_looked_me_up' ) ) {
			echo kaamase_who_looked_me_up( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>

	</div>

	<?php if ( ! $kaamase_owner ) : ?>
		<div class="ka-actionbar">
			<div class="ka-container ka-actionbar__inner">

				<div class="ka-actionbar__meta ka-hide-sm">
					<strong><?php echo esc_html( get_the_title( $kaamase_id ) ); ?></strong>
				</div>

				<?php
				if ( $kaamase_may || ! $kaamase_viewer ) {

					// Signed out, the theme's button says sign in to contact.
					echo function_exists( 'kaamase_contact_button' ) ? kaamase_contact_button( $kaamase_id, __( 'Get contact details', 'kaamase-core' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				} elseif ( function_exists( 'kaamase_page_url' ) ) {

					printf(
						'<a class="ka-btn ka-btn--outline ka-btn--sm" href="%1$s">%2$s</a>',
						esc_url( kaamase_page_url( 'post_job' ) ),
						esc_html__( 'Add hiring to contact', 'kaamase-core' )
					);
				}
				?>

			</div>
		</div>
	<?php endif; ?>

	<?php
endwhile;

get_footer();
