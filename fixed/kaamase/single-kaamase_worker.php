<?php
/**
 * Single worker profile.
 *
 * The page an employer reads before deciding whether to spend a phone
 * call. Everything on it is ordered by what that decision actually turns
 * on, which is not the same order the data happens to be stored in.
 *
 * The order, and why
 * ------------------
 * 1. Who and where, with availability. An employer scanning six tabs
 *    needs to know in one glance whether this person is free.
 * 2. The vouch. In Nagaland a named colony or village authority standing
 *    behind somebody outweighs anything this platform can compute, so it
 *    sits above the rate rather than in a details block at the bottom.
 * 3. Rate and experience. What makes the call worth making.
 * 4. Their own words.
 * 5. Ratings.
 *
 * The action bar
 * --------------
 * Fixed to the bottom of the screen on a phone, above the tab bar. An
 * employer who has read to the bottom of a profile should not have to
 * scroll back up to find the call button, and on a cracked screen with
 * one thumb free, scrolling back up is where the hire gets lost.
 *
 * No phone number is printed into this page. Contact goes through the
 * plugin's contact screen, which gates, logs and rate limits the reveal.
 *
 * @package Kaamase
 * @version 1.1.0
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$kaamase_id         = get_the_ID();
	$kaamase_experience = absint( kaamase_field( $kaamase_id, 'years_experience' ) );
	$kaamase_day        = absint( kaamase_field( $kaamase_id, 'day_rate' ) );
	$kaamase_month      = absint( kaamase_field( $kaamase_id, 'month_rate' ) );
	$kaamase_radius     = (string) kaamase_field( $kaamase_id, 'travel_radius' );
	$kaamase_vouch      = (string) kaamase_field( $kaamase_id, 'vouched_by' );
	$kaamase_vouch_role = (string) kaamase_field( $kaamase_id, 'vouched_role' );
	$kaamase_done       = absint( kaamase_field( $kaamase_id, 'jobs_completed' ) );
	$kaamase_owner      = function_exists( 'kaamase_user_owns' ) && kaamase_user_owns( $kaamase_id );

	$kaamase_travel = array(
		'town'     => __( 'Works around their own town', 'kaamase' ),
		'district' => __( 'Will travel anywhere in their district', 'kaamase' ),
		'state'    => __( 'Will travel anywhere in Nagaland', 'kaamase' ),
	);

	$kaamase_vouch_roles = array(
		'gb'       => __( 'Gaon Bura', 'kaamase' ),
		'colony'   => __( 'Colony council member', 'kaamase' ),
		'village'  => __( 'Village council member', 'kaamase' ),
		'church'   => __( 'Church elder or pastor', 'kaamase' ),
		'union'    => __( 'Student or workers union officer', 'kaamase' ),
		'employer' => __( 'Someone they worked for', 'kaamase' ),
		'other'    => __( 'Someone who knows them', 'kaamase' ),
	);
	?>

	<div class="ka-container ka-section ka-profile">

		<?php if ( $kaamase_owner ) : ?>
			<div class="ka-notice ka-notice--info ka-mb-4">
				<div>
					<span class="ka-notice__title"><?php esc_html_e( 'This is your profile', 'kaamase' ); ?></span>
					<p><?php esc_html_e( 'This is exactly what an employer sees when they open it.', 'kaamase' ); ?></p>
					<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4"
						href="<?php echo esc_url( add_query_arg( 'edit', $kaamase_id, home_url( '/dashboard/' ) ) ); ?>">
						<?php esc_html_e( 'Edit my profile', 'kaamase' ); ?>
					</a>
				</div>
			</div>
		<?php endif; ?>

		<article <?php post_class( 'ka-profile__article' ); ?>>

			<?php
			/* ----------------------------------------------------------
			 * Identity
			 * -------------------------------------------------------- */
			?>
			<header class="ka-card ka-card--pad-lg ka-profile__head">

				<div class="ka-profile__identity">
					<?php echo kaamase_avatar( $kaamase_id, 'kaamase-avatar-lg', 'ka-avatar--xl' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<div class="ka-profile__titles">
						<h1 class="ka-profile__name"><?php the_title(); ?></h1>

						<?php echo kaamase_place( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

						<div class="ka-cluster ka-mt-4">
							<?php
							echo kaamase_status( kaamase_field( $kaamase_id, 'availability' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo kaamase_rating( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								kaamase_field( $kaamase_id, 'rating_average', 0 ),
								kaamase_field( $kaamase_id, 'rating_count', 0 )
							);
							echo kaamase_badges( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
					</div>
				</div>

				<div class="ka-profile__trades ka-mt-6">
					<?php echo kaamase_trades( $kaamase_id, 8, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php
				/*
				 * Last, and behind a rule.
				 *
				 * The order of this card is who they are, where they
				 * are, whether they can work, how good they are, and
				 * what they do. How many people have looked is weaker
				 * than every one of those and belongs after them. The
				 * rule is what stops it reading as an afterthought:
				 * above it are facts about the person, below it is a
				 * fact about the page.
				 */
				$kaamase_seen = function_exists( 'kaamase_views' ) ? kaamase_views( $kaamase_id, 'full' ) : '';
				?>
				<?php if ( $kaamase_seen ) : ?>
					<div class="ka-profile__seen">
						<?php echo $kaamase_seen; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>

			</header>

			<?php
			/* ----------------------------------------------------------
			 * The vouch.
			 *
			 * Given a block of its own rather than a badge, because the
			 * name of the person standing behind somebody is the whole
			 * point. A badge saying Vouched tells an employer nothing
			 * they can act on. A name and a role does.
			 * -------------------------------------------------------- */
			?>
			<?php if ( $kaamase_vouch ) : ?>
				<section class="ka-vouch ka-mt-6">
					<h2 class="ka-vouch__title"><?php esc_html_e( 'Vouched for by', 'kaamase' ); ?></h2>

					<p class="ka-vouch__name"><?php echo esc_html( $kaamase_vouch ); ?></p>

					<?php if ( isset( $kaamase_vouch_roles[ $kaamase_vouch_role ] ) ) : ?>
						<p class="ka-vouch__role"><?php echo esc_html( $kaamase_vouch_roles[ $kaamase_vouch_role ] ); ?></p>
					<?php endif; ?>

					<p class="ka-small ka-mute ka-mt-4">
						<?php esc_html_e( 'Somebody local who knows this person and will say so. We hold their number and check it if there is ever a problem.', 'kaamase' ); ?>
					</p>
				</section>
			<?php endif; ?>

			<?php
			/* ----------------------------------------------------------
			 * Facts
			 * -------------------------------------------------------- */
			?>
			<section class="ka-facts ka-mt-6">

				<div class="ka-fact">
					<p class="ka-fact__label"><?php esc_html_e( 'Day rate', 'kaamase' ); ?></p>
					<p class="ka-fact__value">
						<?php echo kaamase_wage( $kaamase_day, 'day' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</p>
				</div>

				<?php if ( $kaamase_month ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Monthly', 'kaamase' ); ?></p>
						<p class="ka-fact__value">
							<?php echo kaamase_wage( $kaamase_month, 'month' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</p>
					</div>
				<?php endif; ?>

				<div class="ka-fact">
					<p class="ka-fact__label"><?php esc_html_e( 'Experience', 'kaamase' ); ?></p>
					<p class="ka-fact__value">
						<?php
						if ( $kaamase_experience ) {
							printf(
								esc_html(
									/* translators: %s: number of years */
									_n( '%s year', '%s years', $kaamase_experience, 'kaamase' )
								),
								esc_html( number_format_i18n( $kaamase_experience ) )
							);
						} else {
							echo '<span class="ka-mute ka-small">' . esc_html__( 'Not given', 'kaamase' ) . '</span>';
						}
						?>
					</p>
				</div>

				<?php if ( $kaamase_done ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Jobs done here', 'kaamase' ); ?></p>
						<p class="ka-fact__value"><?php echo esc_html( number_format_i18n( $kaamase_done ) ); ?></p>
					</div>
				<?php endif; ?>

			</section>

			<?php if ( isset( $kaamase_travel[ $kaamase_radius ] ) ) : ?>
				<p class="ka-soft ka-small ka-mt-4">
					<?php echo esc_html( $kaamase_travel[ $kaamase_radius ] ); ?>
				</p>
			<?php endif; ?>

			<?php
			/* ----------------------------------------------------------
			 * Languages.
			 *
			 * A practical matching problem here, not a cultural note. A
			 * Konyak speaking helper and a Sumi speaking contractor both
			 * need to know whether they share Nagamese before the first
			 * day, not on it.
			 * -------------------------------------------------------- */
			$kaamase_languages = get_the_terms( $kaamase_id, 'kaamase_language' );

			if ( $kaamase_languages && ! is_wp_error( $kaamase_languages ) ) :
				?>
				<section class="ka-mt-6">
					<h2 class="ka-text-lg"><?php esc_html_e( 'Speaks', 'kaamase' ); ?></h2>

					<ul class="ka-cluster ka-mt-4">
						<?php foreach ( $kaamase_languages as $kaamase_language ) : ?>
							<li class="ka-chip"><?php echo esc_html( $kaamase_language->name ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php
			/* ----------------------------------------------------------
			 * Their own words, plus everything the core plugin appends:
			 * the contact screen, the save button, and any notice.
			 * -------------------------------------------------------- */
			?>
			<div class="ka-prose ka-mt-6">
				<?php the_content(); ?>
			</div>

		</article>

		<?php
		/* --------------------------------------------------------------
		 * Ratings
		 * ------------------------------------------------------------ */
		if ( function_exists( 'kaamase_rating_list' ) ) {
			echo kaamase_rating_list( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( function_exists( 'kaamase_rating_form' ) ) {
			echo kaamase_rating_form( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/* --------------------------------------------------------------
		 * The owner's own information: who has looked them up, with a
		 * block button next to each.
		 * ------------------------------------------------------------ */
		if ( $kaamase_owner && function_exists( 'kaamase_who_looked_me_up' ) ) {
			echo kaamase_who_looked_me_up( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>

	</div>

	<?php
	/* ------------------------------------------------------------------
	 * Sticky action bar.
	 *
	 * Not shown to the person whose profile it is, because offering
	 * somebody a button to call themselves is the kind of detail that
	 * makes a platform feel unfinished.
	 * ---------------------------------------------------------------- */
	?>
	<?php if ( ! $kaamase_owner ) : ?>
		<div class="ka-actionbar">
			<div class="ka-container ka-actionbar__inner">

				<div class="ka-actionbar__meta ka-hide-sm">
					<strong><?php the_title(); ?></strong>
					<?php echo kaamase_wage( $kaamase_day, 'day' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<?php
				echo kaamase_contact_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$kaamase_id,
					__( 'Get contact details', 'kaamase' )
				);
				?>

			</div>
		</div>
	<?php endif; ?>

	<?php
endwhile;

get_footer();
