<?php
/**
 * Single team.
 *
 * A team is a leader plus a headcount, and it is the thing a contractor
 * actually hires. Nobody hires one mason. They hire a mason who brings
 * four helpers, and every hiring platform that only models individuals
 * pushes the contractor back onto the phone to assemble the rest.
 *
 * So the headcount is the headline here, where the day rate is the
 * headline on a worker profile. The question a contractor is asking is
 * how many people arrive on the day, and the answer should not need
 * looking for.
 *
 * @package Kaamase
 * @version 1.1.0
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$kaamase_id        = get_the_ID();
	$kaamase_size      = absint( kaamase_field( $kaamase_id, 'headcount' ) );
	$kaamase_leader    = (string) kaamase_field( $kaamase_id, 'leader_name' );
	$kaamase_day       = absint( kaamase_field( $kaamase_id, 'day_rate' ) );
	$kaamase_years     = absint( kaamase_field( $kaamase_id, 'years_experience' ) );
	$kaamase_vouch     = (string) kaamase_field( $kaamase_id, 'vouched_by' );
	$kaamase_radius    = (string) kaamase_field( $kaamase_id, 'travel_radius' );
	$kaamase_owner     = function_exists( 'kaamase_user_owns' ) && kaamase_user_owns( $kaamase_id );

	$kaamase_travel = array(
		'town'     => __( 'Works around their own town', 'kaamase' ),
		'district' => __( 'Will travel anywhere in their district', 'kaamase' ),
		'state'    => __( 'Will travel anywhere in Nagaland', 'kaamase' ),
	);
	?>

	<div class="ka-container ka-section ka-profile">

		<?php if ( $kaamase_owner ) : ?>
			<div class="ka-notice ka-notice--info ka-mb-4">
				<div>
					<span class="ka-notice__title"><?php esc_html_e( 'This is your team', 'kaamase' ); ?></span>
					<p><?php esc_html_e( 'This is what a contractor sees when they open it.', 'kaamase' ); ?></p>
					<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4"
						href="<?php echo esc_url( add_query_arg( 'edit', $kaamase_id, home_url( '/dashboard/' ) ) ); ?>">
						<?php esc_html_e( 'Edit my team', 'kaamase' ); ?>
					</a>
				</div>
			</div>
		<?php endif; ?>

		<article <?php post_class( 'ka-profile__article' ); ?>>

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
			 * The headcount. The reason a team exists as its own thing.
			 * -------------------------------------------------------- */
			?>
			<?php if ( $kaamase_size ) : ?>
				<section class="ka-payblock ka-mt-6">
					<p class="ka-payblock__label"><?php esc_html_e( 'Team size', 'kaamase' ); ?></p>

					<p class="ka-payblock__figure">
						<span class="ka-wage"><?php echo esc_html( number_format_i18n( $kaamase_size ) ); ?></span>
						<span class="ka-wage__unit">
							<?php
							echo esc_html(
								_n( 'worker', 'workers', $kaamase_size, 'kaamase' )
							);
							?>
						</span>
					</p>

					<p class="ka-small ka-soft ka-mt-4">
						<?php esc_html_e( 'They come as a group. One call and the whole team arrives.', 'kaamase' ); ?>
					</p>
				</section>
			<?php endif; ?>

			<section class="ka-facts ka-mt-6">

				<?php if ( $kaamase_leader ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Led by', 'kaamase' ); ?></p>
						<p class="ka-fact__value"><?php echo esc_html( $kaamase_leader ); ?></p>
					</div>
				<?php endif; ?>

				<div class="ka-fact">
					<p class="ka-fact__label"><?php esc_html_e( 'Day rate', 'kaamase' ); ?></p>
					<p class="ka-fact__value">
						<?php echo kaamase_wage( $kaamase_day, 'day' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</p>
				</div>

				<?php if ( $kaamase_years ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Experience', 'kaamase' ); ?></p>
						<p class="ka-fact__value">
							<?php
							printf(
								esc_html(
									/* translators: %s: number of years */
									_n( '%s year', '%s years', $kaamase_years, 'kaamase' )
								),
								esc_html( number_format_i18n( $kaamase_years ) )
							);
							?>
						</p>
					</div>
				<?php endif; ?>

			</section>

			<?php if ( $kaamase_vouch ) : ?>
				<section class="ka-vouch ka-mt-6">
					<h2 class="ka-vouch__title"><?php esc_html_e( 'Vouched for by', 'kaamase' ); ?></h2>
					<p class="ka-vouch__name"><?php echo esc_html( $kaamase_vouch ); ?></p>
				</section>
			<?php endif; ?>

			<?php if ( isset( $kaamase_travel[ $kaamase_radius ] ) ) : ?>
				<p class="ka-soft ka-small ka-mt-4">
					<?php echo esc_html( $kaamase_travel[ $kaamase_radius ] ); ?>
				</p>
			<?php endif; ?>

			<div class="ka-prose ka-mt-6">
				<?php the_content(); ?>
			</div>

		</article>

		<?php
		if ( function_exists( 'kaamase_rating_list' ) ) {
			echo kaamase_rating_list( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( function_exists( 'kaamase_rating_form' ) ) {
			echo kaamase_rating_form( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $kaamase_owner && function_exists( 'kaamase_who_looked_me_up' ) ) {
			echo kaamase_who_looked_me_up( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>

	</div>

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
