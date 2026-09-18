<?php
/**
 * Front page.
 *
 * Leads with search, not with registration.
 *
 * The reasoning: a person arriving here with a need right now is worth
 * more than a person who might register. A contractor whose plumber did
 * not turn up this morning will use a search box. He will not fill in a
 * signup form. And his search, even when it returns nothing, is the
 * demand signal that makes registering worthwhile for a worker. Demand
 * pulls supply. Supply does not pull demand.
 *
 * The other rule this file follows: every section must survive having no
 * content at all. On launch day there are no workers, no jobs and no
 * trades. Nothing here may render as an empty box or a stray heading
 * with nothing under it. Sections that have nothing to show remove
 * themselves entirely and the page still reads as finished.
 *
 * @package Kaamase
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * Check what actually exists before trying to query it. The core plugin
 * registers these, so on a theme only install every one of these is
 * false and the page falls back to hero plus explanation plus signup,
 * which is still a complete landing page.
 */
$kaamase_has_jobs    = post_type_exists( 'kaamase_job' );
$kaamase_has_workers = post_type_exists( 'kaamase_worker' );
$kaamase_has_trades  = taxonomy_exists( 'kaamase_trade' );
$kaamase_has_places  = taxonomy_exists( 'kaamase_district' );
?>

<?php
/* ==========================================================================
   HERO
   ========================================================================== */
?>
<section class="ka-hero">
	<div class="ka-container">

		<div class="ka-hero__inner ka-stack--lg">

			<h1 class="ka-hero__title">
				<?php esc_html_e( 'Kaam ase.', 'kaamase' ); ?>
			</h1>

			<p class="ka-hero__lead">
				<?php
				esc_html_e(
					'Find a mason, electrician, plumber, driver or helper near you. Anywhere in Nagaland.',
					'kaamase'
				);
				?>
			</p>

			<?php
			/* --------------------------------------------------------------
			 * Search
			 *
			 * Two fields and a button. Trade and district, because that is
			 * how a person actually asks the question out loud. Adding a
			 * wage filter or an availability toggle here would be more
			 * complete and would get used less.
			 *
			 * This is a plain GET form. It works with JavaScript switched
			 * off, it works on a browser from 2015, and the result is a
			 * shareable URL somebody can paste into WhatsApp.
			 * ------------------------------------------------------------ */
			?>
			<form class="ka-hero__search ka-card ka-card--pad-lg" role="search" method="get"
				action="<?php echo esc_url( home_url( '/workers/' ) ); ?>">

				<div class="ka-hero__fields">

					<div class="ka-field">
						<label class="ka-label" for="ka-trade">
							<?php esc_html_e( 'What work', 'kaamase' ); ?>
						</label>

						<?php if ( $kaamase_has_trades ) : ?>
							<?php
							wp_dropdown_categories(
								array(
									'taxonomy'        => 'kaamase_trade',
									'name'            => 'kaamase_trade',
									'id'              => 'ka-trade',
									'class'           => 'ka-select',
									'show_option_all' => __( 'Any trade', 'kaamase' ),
									'hide_empty'      => false,
									'value_field'     => 'slug',
									'orderby'         => 'name',
								)
							);
							?>
						<?php else : ?>
							<input class="ka-input" type="search" id="ka-trade" name="s"
								placeholder="<?php esc_attr_e( 'Mason, electrician, driver', 'kaamase' ); ?>">
						<?php endif; ?>
					</div>

					<div class="ka-field">
						<label class="ka-label" for="ka-district">
							<?php esc_html_e( 'Which district', 'kaamase' ); ?>
						</label>

						<?php if ( $kaamase_has_places ) : ?>
							<?php
							wp_dropdown_categories(
								array(
									'taxonomy'        => 'kaamase_district',
									'name'            => 'kaamase_district',
									'id'              => 'ka-district',
									'class'           => 'ka-select',
									'show_option_all' => __( 'All Nagaland', 'kaamase' ),
									'hide_empty'      => false,
									'value_field'     => 'slug',
									'orderby'         => 'name',
								)
							);
							?>
						<?php else : ?>
							<input class="ka-input" type="text" id="ka-district" name="kaamase_district"
								placeholder="<?php esc_attr_e( 'Dimapur, Kohima, Mokokchung', 'kaamase' ); ?>">
						<?php endif; ?>
					</div>

					<div class="ka-field ka-hero__submit">
						<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
							<?php esc_html_e( 'Search', 'kaamase' ); ?>
						</button>
					</div>

				</div>

				<p class="ka-hint ka-mt-4">
					<?php esc_html_e( 'Looking for work instead?', 'kaamase' ); ?>
					<a href="<?php echo esc_url( home_url( '/jobs/' ) ); ?>">
						<?php esc_html_e( 'See jobs near you', 'kaamase' ); ?>
					</a>
				</p>

			</form>

		</div>

	</div>
</section>

<?php
/* ==========================================================================
   TRADES

   A row of chips. Faster than the search box for somebody who is
   browsing rather than looking, and it shows at a glance what this site
   is actually for. Hidden entirely when no trades exist.
   ========================================================================== */

if ( $kaamase_has_trades ) :

	$kaamase_trades = get_terms(
		array(
			'taxonomy'   => 'kaamase_trade',
			'hide_empty' => false,
			'number'     => 12,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if ( ! empty( $kaamase_trades ) && ! is_wp_error( $kaamase_trades ) ) :
		?>
		<section class="ka-container ka-section">

			<h2 class="ka-sr"><?php esc_html_e( 'Trades', 'kaamase' ); ?></h2>

			<div class="ka-chips-scroll">
				<?php foreach ( $kaamase_trades as $kaamase_trade ) : ?>
					<?php $kaamase_link = get_term_link( $kaamase_trade ); ?>
					<?php if ( ! is_wp_error( $kaamase_link ) ) : ?>
						<a class="ka-chip" href="<?php echo esc_url( $kaamase_link ); ?>">
							<?php echo esc_html( $kaamase_trade->name ); ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>

				<a class="ka-chip" href="<?php echo esc_url( home_url( '/trades/' ) ); ?>">
					<?php esc_html_e( 'All trades', 'kaamase' ); ?>
				</a>
			</div>

		</section>
		<?php
	endif;

endif;
?>

<?php
/* ==========================================================================
   LATEST JOBS

   Jobs above workers on purpose. A job post is proof that somebody is
   hiring, which is the single most persuasive thing a worker landing
   here can see. A wall of worker profiles proves nothing to anybody.
   ========================================================================== */

if ( $kaamase_has_jobs ) :

	$kaamase_jobs = new WP_Query(
		array(
			'post_type'           => 'kaamase_job',
			'posts_per_page'      => 6,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	if ( $kaamase_jobs->have_posts() ) :
		?>
		<section class="ka-container ka-section">

			<div class="ka-cluster ka-cluster--between">
				<h2><?php esc_html_e( 'Work posted recently', 'kaamase' ); ?></h2>
			</div>

			<div class="ka-grid ka-grid--2 ka-grid--3 ka-mt-6">
				<?php
				while ( $kaamase_jobs->have_posts() ) :
					$kaamase_jobs->the_post();
					kaamase_job_card();
				endwhile;
				?>
			</div>

			<?php
			/*
			 * Under the cards, not beside the heading.
			 *
			 * On a phone the grid is one column, so six cards is a long
			 * scroll and a link level with the heading has been off the
			 * screen since the second card. Somebody who has read the
			 * list and wants more had to scroll back up to find it.
			 * Below the last card is where they already are.
			 *
			 * Named rather than "See all", because out of reach of the
			 * heading the word all no longer says all of what.
			 */
			?>
			<p class="ka-center ka-mt-6">
				<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( home_url( '/jobs/' ) ); ?>">
					<?php esc_html_e( 'See all jobs', 'kaamase' ); ?>
				</a>
			</p>

		</section>
		<?php
	endif;

	wp_reset_postdata();

endif;
?>

<?php
/* ==========================================================================
   AVAILABLE WORKERS

   Only workers marked available now. Showing somebody who is busy on a
   site in Mon is worse than showing nobody, because the contractor calls
   and gets turned down and stops trusting the listings.
   ========================================================================== */

if ( $kaamase_has_workers ) :

	$kaamase_workers = new WP_Query(
		array(
			'post_type'           => 'kaamase_worker',
			'posts_per_page'      => 6,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'   => '_kaamase_availability',
					'value' => 'live',
				),
				array(
					'key'     => '_kaamase_availability',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	if ( $kaamase_workers->have_posts() ) :
		?>
		<section class="ka-container ka-section">

			<div class="ka-cluster ka-cluster--between">
				<h2><?php esc_html_e( 'Available now', 'kaamase' ); ?></h2>
			</div>

			<div class="ka-grid ka-grid--2 ka-grid--3 ka-mt-6">
				<?php
				while ( $kaamase_workers->have_posts() ) :
					$kaamase_workers->the_post();
					kaamase_worker_card();
				endwhile;
				?>
			</div>

			<?php // Below the cards, for the reason given above the jobs one. ?>
			<p class="ka-center ka-mt-6">
				<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( home_url( '/workers/' ) ); ?>">
					<?php esc_html_e( 'See all workers', 'kaamase' ); ?>
				</a>
			</p>

		</section>
		<?php
	endif;

	wp_reset_postdata();

endif;
?>

<?php
/* ==========================================================================
   THE APP

   Above the two doors because that is where it was asked for.

   It is here at all because the other two offers each need a particular
   browser: Apple's banner is drawn by Safari and nothing else, and the
   strip at the foot of the screen is Android only. Hardly anybody in
   Nagaland opens Safari by choice, so on an iPhone in Chrome the app was
   invisible. This is in the page, so it is on every phone and every
   browser, and it can be looked at.
   ========================================================================== */

if ( function_exists( 'kaamase_app_cta' ) ) :
	?>
	<section class="ka-container ka-section ka-section--app">
		<?php kaamase_app_cta(); ?>
	</section>
	<?php
endif;
?>

<?php
/* ==========================================================================
   THE TWO DOORS

   Registration lives here, below search, and it is split by who the
   person is. A single Register button forces a visitor to work out which
   kind of user they are before they have understood the site. Two named
   doors answer that for them.
   ========================================================================== */
?>
<section class="ka-container ka-section">

	<div class="ka-grid ka-grid--2">

		<div class="ka-card ka-card--pad-lg ka-door ka-door--worker">
			<h2><?php esc_html_e( 'Looking for work', 'kaamase' ); ?></h2>

			<p class="ka-soft ka-mt-4">
				<?php
				esc_html_e(
					'Make a free profile. Employers across Nagaland can find you by trade and district. Kaam Ase is free for workers and always will be.',
					'kaamase'
				);
				?>
			</p>

			<a class="ka-btn ka-btn--primary ka-btn--lg ka-mt-6" href="<?php echo esc_url( home_url( '/register/?type=worker' ) ); ?>">
				<?php esc_html_e( 'Make my free profile', 'kaamase' ); ?>
			</a>
		</div>

		<div class="ka-card ka-card--pad-lg ka-door ka-door--employer">
			<h2><?php esc_html_e( 'Looking for workers', 'kaamase' ); ?></h2>

			<p class="ka-soft ka-mt-4">
				<?php
				esc_html_e(
					'Post the job with your rate, how many workers you need and when you need them. Hire one person or a whole team.',
					'kaamase'
				);
				?>
			</p>

			<a class="ka-btn ka-btn--action ka-btn--lg ka-mt-6" href="<?php echo esc_url( home_url( '/post-job/' ) ); ?>">
				<?php esc_html_e( 'Post a job free', 'kaamase' ); ?>
			</a>
		</div>

	</div>

</section>

<?php
/* ==========================================================================
   TRUST

   Four plain statements. Everything here is either already true or a
   commitment worth holding yourself to. Nothing aspirational.
   ========================================================================== */
?>
<section class="ka-section ka-trust">
	<div class="ka-container">

		<ul class="ka-grid ka-grid--2 ka-grid--4">

			<li class="ka-trust__item">
				<h3><?php esc_html_e( 'Free to use', 'kaamase' ); ?></h3>
				<p class="ka-small ka-soft">
					<?php esc_html_e( 'No fee to make a profile, post a job, or get hired. Workers never pay, ever.', 'kaamase' ); ?>
				</p>
			</li>

			<li class="ka-trust__item">
				<h3><?php esc_html_e( 'Vouched locally', 'kaamase' ); ?></h3>
				<p class="ka-small ka-soft">
					<?php esc_html_e( 'Profiles can carry a vouch from a colony or village authority who knows the person.', 'kaamase' ); ?>
				</p>
			</li>

			<li class="ka-trust__item">
				<h3><?php esc_html_e( 'Both sides rate', 'kaamase' ); ?></h3>
				<p class="ka-small ka-soft">
					<?php esc_html_e( 'Workers rate employers too. Ratings come only from jobs that were actually done.', 'kaamase' ); ?>
				</p>
			</li>

			<li class="ka-trust__item">
				<h3><?php esc_html_e( 'A Nagaland company', 'kaamase' ); ?></h3>
				<p class="ka-small ka-soft">
					<?php esc_html_e( 'Registered and run from Dimapur. Not an outside app that has never been here.', 'kaamase' ); ?>
				</p>
			</li>

		</ul>

	</div>
</section>

<?php
get_footer();