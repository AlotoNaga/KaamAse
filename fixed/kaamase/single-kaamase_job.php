<?php
/**
 * Single job.
 *
 * The page a worker reads before deciding whether to travel.
 *
 * The pay is the headline and it is the largest thing on the screen.
 * Every hiring platform in India buries the rate, and every worker on
 * every one of them asks the same first question. Putting it at the top
 * in the biggest type on the page answers it before it is asked.
 *
 * Food, stay and transport sit directly under the pay rather than in a
 * details list. For work away from home a worker decides on those as much
 * as on the rate, and an employer who provides them is throwing away
 * their best argument if the worker has to hunt for it.
 *
 * The closing date is stated plainly. A job board where nothing visibly
 * closes is a job board full of work that was filled three weeks ago.
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
	$kaamase_pay       = absint( kaamase_field( $kaamase_id, 'pay_amount' ) );
	$kaamase_unit      = (string) kaamase_field( $kaamase_id, 'pay_unit', 'day' );
	$kaamase_needed    = absint( kaamase_field( $kaamase_id, 'workers_needed' ) );
	$kaamase_urgent    = (bool) kaamase_field( $kaamase_id, 'urgent' );
	$kaamase_start     = (string) kaamase_field( $kaamase_id, 'start_date' );
	$kaamase_duration  = (string) kaamase_field( $kaamase_id, 'duration' );
	$kaamase_food      = (bool) kaamase_field( $kaamase_id, 'food_provided' );
	$kaamase_stay      = (bool) kaamase_field( $kaamase_id, 'stay_provided' );
	$kaamase_transport = (bool) kaamase_field( $kaamase_id, 'transport_provided' );
	$kaamase_company   = (string) kaamase_field( $kaamase_id, 'employer_name' );
	$kaamase_employer  = absint( kaamase_field( $kaamase_id, 'employer_id' ) );
	$kaamase_expires   = absint( kaamase_field( $kaamase_id, 'expires' ) );
	$kaamase_owner     = function_exists( 'kaamase_user_owns' ) && kaamase_user_owns( $kaamase_id );

	$kaamase_days_left = $kaamase_expires
		? (int) ceil( ( $kaamase_expires - time() ) / DAY_IN_SECONDS )
		: 0;
	?>

	<div class="ka-container ka-container--narrow ka-section ka-job">

		<?php if ( $kaamase_owner ) : ?>
			<div class="ka-notice ka-notice--info ka-mb-4">
				<div>
					<span class="ka-notice__title"><?php esc_html_e( 'This is your job post', 'kaamase' ); ?></span>
					<p><?php esc_html_e( 'This is what a worker sees. Share the link on WhatsApp if you want it seen faster.', 'kaamase' ); ?></p>

					<div class="ka-cluster ka-mt-4">
						<a class="ka-btn ka-btn--outline ka-btn--sm"
							href="<?php echo esc_url( add_query_arg( 'edit', $kaamase_id, home_url( '/post-job/' ) ) ); ?>">
							<?php esc_html_e( 'Edit this job', 'kaamase' ); ?>
						</a>

						<a class="ka-btn ka-btn--ghost ka-btn--sm" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">
							<?php esc_html_e( 'My jobs', 'kaamase' ); ?>
						</a>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<article <?php post_class( 'ka-job__article' ); ?>>

			<header class="ka-job__head">

				<div class="ka-cluster">
					<?php echo kaamase_trades( $kaamase_id, 3, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php if ( $kaamase_urgent ) : ?>
						<span class="ka-badge ka-badge--urgent"><?php esc_html_e( 'Urgent', 'kaamase' ); ?></span>
					<?php endif; ?>
				</div>

				<h1 class="ka-mt-4"><?php the_title(); ?></h1>

				<div class="ka-cluster ka-mt-4">
					<?php
					echo kaamase_place( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo kaamase_posted_ago( $kaamase_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>

				<?php
				/*
				 * On a line of its own, and the full wording.
				 *
				 * It used to sit in the cluster above at card density,
				 * which is why a job page said "19" while a worker page
				 * said "7 seen, 3 opened". Two numbers and the words
				 * that tell them apart do not fit on the end of a line
				 * that already carries a place and a date, so it gets
				 * its own line -- the same place the app puts it.
				 */
				$kaamase_seen = function_exists( 'kaamase_views' ) ? kaamase_views( $kaamase_id, 'full' ) : '';

				if ( '' !== $kaamase_seen ) :
					?>
					<div class="ka-mt-4">
						<?php echo $kaamase_seen; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<?php
				endif;
				?>

			</header>

			<?php
			/* ----------------------------------------------------------
			 * Pay. The headline.
			 * -------------------------------------------------------- */
			?>
			<section class="ka-payblock ka-mt-6">

				<p class="ka-payblock__label"><?php esc_html_e( 'Pay', 'kaamase' ); ?></p>

				<p class="ka-payblock__figure">
					<?php echo kaamase_wage( $kaamase_pay, $kaamase_unit ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>

				<?php if ( $kaamase_food || $kaamase_stay || $kaamase_transport ) : ?>
					<ul class="ka-cluster ka-mt-4">
						<?php if ( $kaamase_food ) : ?>
							<li class="ka-badge ka-badge--verified"><?php esc_html_e( 'Food provided', 'kaamase' ); ?></li>
						<?php endif; ?>

						<?php if ( $kaamase_stay ) : ?>
							<li class="ka-badge ka-badge--verified"><?php esc_html_e( 'Somewhere to stay', 'kaamase' ); ?></li>
						<?php endif; ?>

						<?php if ( $kaamase_transport ) : ?>
							<li class="ka-badge ka-badge--verified"><?php esc_html_e( 'Transport to site', 'kaamase' ); ?></li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>

			</section>

			<?php
			/* ----------------------------------------------------------
			 * Detail
			 * -------------------------------------------------------- */
			?>
			<section class="ka-facts ka-mt-6">

				<?php if ( $kaamase_needed ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Workers wanted', 'kaamase' ); ?></p>
						<p class="ka-fact__value"><?php echo esc_html( number_format_i18n( $kaamase_needed ) ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( $kaamase_start ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Starts', 'kaamase' ); ?></p>
						<p class="ka-fact__value">
							<?php
							echo esc_html(
								date_i18n(
									(string) get_option( 'date_format' ),
									(int) strtotime( $kaamase_start )
								)
							);
							?>
						</p>
					</div>
				<?php endif; ?>

				<?php if ( $kaamase_duration ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'How long', 'kaamase' ); ?></p>
						<p class="ka-fact__value"><?php echo esc_html( $kaamase_duration ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( $kaamase_days_left > 0 ) : ?>
					<div class="ka-fact">
						<p class="ka-fact__label"><?php esc_html_e( 'Closes in', 'kaamase' ); ?></p>
						<p class="ka-fact__value">
							<?php
							printf(
								esc_html(
									/* translators: %s: number of days */
									_n( '%s day', '%s days', $kaamase_days_left, 'kaamase' )
								),
								esc_html( number_format_i18n( $kaamase_days_left ) )
							);
							?>
						</p>
					</div>
				<?php endif; ?>

			</section>

			<?php
			/* ----------------------------------------------------------
			 * Who is hiring
			 * -------------------------------------------------------- */
			?>
			<?php if ( $kaamase_company ) : ?>
				<section class="ka-card ka-card--flat ka-mt-6">
					<p class="ka-label"><?php esc_html_e( 'Hiring', 'kaamase' ); ?></p>

					<p class="ka-text-lg ka-bold">
						<?php if ( $kaamase_employer && 'publish' === get_post_status( $kaamase_employer ) ) : ?>
							<a href="<?php echo esc_url( get_permalink( $kaamase_employer ) ); ?>">
								<?php echo esc_html( $kaamase_company ); ?>
							</a>
						<?php else : ?>
							<?php echo esc_html( $kaamase_company ); ?>
						<?php endif; ?>
					</p>

					<?php if ( $kaamase_employer ) : ?>
						<div class="ka-cluster ka-mt-4">
							<?php
							echo kaamase_rating( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								kaamase_field( $kaamase_employer, 'rating_average', 0 ),
								kaamase_field( $kaamase_employer, 'rating_count', 0 )
							);
							echo kaamase_badges( $kaamase_employer ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>

						<p class="ka-small ka-mute ka-mt-4">
							<?php esc_html_e( 'Workers who have done a job for them rate them too. Open their page to see what they said.', 'kaamase' ); ?>
						</p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php
			/*
			 * The description, plus everything the core plugin appends:
			 * the contact screen, the posted notice and the save button.
			 */
			?>
			<div class="ka-prose ka-mt-6">
				<?php the_content(); ?>
			</div>

			<?php
			/* ----------------------------------------------------------
			 * Safety.
			 *
			 * On the job page rather than only on the Safety page,
			 * because this is the moment somebody is about to agree to
			 * work for a stranger.
			 * -------------------------------------------------------- */
			?>
			<aside class="ka-notice ka-notice--warn ka-mt-6">
				<div>
					<span class="ka-notice__title"><?php esc_html_e( 'Before you agree', 'kaamase' ); ?></span>
					<p><?php esc_html_e( 'Agree the rate and the payment day out loud before the first day, not after. Nobody on Kaam Ase should ever ask you for money to get a job. If they do, report it.', 'kaamase' ); ?></p>

					<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="<?php echo esc_url( home_url( '/report/' ) ); ?>">
						<?php esc_html_e( 'Report a problem', 'kaamase' ); ?>
					</a>
				</div>
			</aside>

		</article>

	</div>

	<?php if ( ! $kaamase_owner ) : ?>
		<div class="ka-actionbar">
			<div class="ka-container ka-actionbar__inner">

				<div class="ka-actionbar__meta ka-hide-sm">
					<strong><?php esc_html_e( 'This job', 'kaamase' ); ?></strong>
					<?php echo kaamase_wage( $kaamase_pay, $kaamase_unit ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
