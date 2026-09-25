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
 * Said in the words people search for
 * -----------------------------------
 * The heading used to be the slogan, "Kaam ase.", and nothing on the page
 * said jobs in Nagaland or workers in Nagaland. Somebody who had never
 * heard of Kaam Ase could not find it by describing what it is, and an
 * AI assistant asked where to find work in Nagaland had nothing here to
 * quote. So the heading, the section titles and the questions at the
 * bottom now say it plainly. The slogan stays, as the name above the
 * heading and in the header.
 *
 * The questions come from kaamase_home_faq() in the core plugin, which
 * also gives the same answers to search engines as structured data.
 * Written once, so the two can never disagree.
 *
 * Every kind of job
 * -----------------
 * Kaam Ase is for every job, not only daily wage work: teachers, nurses,
 * office staff and government adverts sit beside masons and drivers. The
 * words on this page say so, and avoid "trade" where it would tell a
 * teacher the site is not for them. The trade taxonomy keeps its name
 * underneath; only what a visitor reads changed.
 *
 * @package Kaamase
 * @version 1.4.0
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

/*
 * Real counts, never written in by hand. A number on the front page that
 * stops being true the week a trade is added is worse than no number.
 */
$kaamase_trade_count = 0;

if ( $kaamase_has_trades ) {
	$kaamase_count       = wp_count_terms( array( 'taxonomy' => 'kaamase_trade', 'hide_empty' => false ) );
	$kaamase_trade_count = is_wp_error( $kaamase_count ) ? 0 : (int) $kaamase_count;
}

$kaamase_districts = array();

if ( $kaamase_has_places ) {
	$kaamase_districts = get_terms(
		array(
			'taxonomy'   => 'kaamase_district',
			'hide_empty' => false,
			'orderby'    => 'name',
		)
	);

	$kaamase_districts = is_array( $kaamase_districts ) ? $kaamase_districts : array();
}

$kaamase_tick = '<svg class="ka-hero__tick" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
?>

<?php
/* ==========================================================================
   HERO

   Photos behind the heading
   -------------------------
   The badge, the heading and the line under it sit on photographs that
   fade from one to the next. Only that part: the search card and the
   ticks stay on the green, where a photo would have to be too tall to
   look like anything.

   The words stay real text on top, never part of a picture. They are
   what a search engine reads, and they change language.

   Each photo is in assets/images/hero/ twice, cropped 16:9: "-s", at
   most 800 wide, for phones, and "-l" for everything wider. A phone
   never fetches a large one. Only the first photo comes with the page;
   the rest are fetched once the page has finished loading, so a slow
   connection gets a working page first and the pictures after.

   "w" is the large file's width. "focus" is the part of the photo that
   must stay in view: a phone shows the middle of a wide photo, a
   computer a band across it.

   No script, or reduced motion asked for, and the first photo simply
   stays. No photos at all and the hero is the green it was.
   ========================================================================== */

$kaamase_hero_photos = array_values(
	(array) apply_filters(
		'kaamase_hero_photos',
		array(
			array( 'name' => 'queue', 'w' => 1000, 'focus' => '35% 50%' ),
			array( 'name' => 'electrician', 'w' => 1600, 'focus' => '45% 50%' ),
			array( 'name' => 'dishes', 'w' => 740, 'focus' => '60% 50%' ),
			array( 'name' => 'interview', 'w' => 1024, 'focus' => '50% 60%' ),
			array( 'name' => 'building', 'w' => 1000, 'focus' => '12% 50%' ),
			array( 'name' => 'job-fair', 'w' => 1000, 'focus' => '50% 50%' ),
			array( 'name' => 'kohima', 'w' => 1200, 'focus' => '50% 50%' ),
		)
	)
);

// A one pixel stand-in, so photos two onwards fetch nothing until the script asks.
$kaamase_blank = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
?>
<section class="ka-hero<?php echo $kaamase_hero_photos ? ' ka-hero--photos' : ''; ?>">

	<div class="ka-hero__band">

		<?php if ( $kaamase_hero_photos ) : ?>
			<div class="ka-hero__slides" aria-hidden="true">
				<?php foreach ( $kaamase_hero_photos as $kaamase_i => $kaamase_photo ) : ?>
					<?php
					$kaamase_file  = 'assets/images/hero/' . sanitize_file_name( (string) ( $kaamase_photo['name'] ?? '' ) );
					$kaamase_small = get_theme_file_uri( $kaamase_file . '-s.webp' );
					$kaamase_large = get_theme_file_uri( $kaamase_file . '-l.webp' );
					$kaamase_wide  = max( 1, (int) ( $kaamase_photo['w'] ?? 1600 ) );
					$kaamase_focus = preg_match( '/^\d{1,3}% \d{1,3}%$/', (string) ( $kaamase_photo['focus'] ?? '' ) ) ? $kaamase_photo['focus'] : '50% 50%';
					?>
					<picture class="ka-hero__slide<?php echo 0 === $kaamase_i ? ' is-on' : ''; ?>">
						<?php if ( 0 === $kaamase_i ) : ?>
							<source media="(max-width: 767px)" srcset="<?php echo esc_url( $kaamase_small ); ?>">
							<img src="<?php echo esc_url( $kaamase_large ); ?>" alt=""
								width="<?php echo (int) $kaamase_wide; ?>" height="<?php echo (int) round( $kaamase_wide * 9 / 16 ); ?>"
								fetchpriority="high" data-no-lazy="1" class="skip-lazy"
								style="object-position: <?php echo esc_attr( $kaamase_focus ); ?>">
						<?php else : ?>
							<source media="(max-width: 767px)" srcset="<?php echo esc_attr( $kaamase_blank ); ?>"
								data-srcset="<?php echo esc_url( $kaamase_small ); ?>">
							<img src="<?php echo esc_attr( $kaamase_blank ); ?>" data-src="<?php echo esc_url( $kaamase_large ); ?>" alt=""
								width="<?php echo (int) $kaamase_wide; ?>" height="<?php echo (int) round( $kaamase_wide * 9 / 16 ); ?>"
								decoding="async" data-no-lazy="1" class="skip-lazy"
								style="object-position: <?php echo esc_attr( $kaamase_focus ); ?>">
						<?php endif; ?>
					</picture>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="ka-container">
			<div class="ka-hero__inner">

				<p class="ka-hero__eyebrow">
					<span class="ka-hero__dot" aria-hidden="true"></span>
					<?php
					printf(
						'%1$s &middot; %2$s',
						esc_html( get_bloginfo( 'name', 'display' ) ),
						esc_html__( 'Nagaland’s own job platform', 'kaamase' )
					);
					?>
				</p>

				<h1 class="ka-hero__title">
					<?php esc_html_e( 'Jobs and workers in Nagaland', 'kaamase' ); ?>
				</h1>

				<p class="ka-hero__lead">
					<?php
					esc_html_e(
						'Every kind of job, government and private, from teaching and office work to driving and building, in every district.',
						'kaamase'
					);
					?>
				</p>

			</div>
		</div>

		<?php if ( count( $kaamase_hero_photos ) > 1 ) : ?>
			<?php // Shown by the script, so nobody without one sees a button that does nothing. ?>
			<button class="ka-hero__pause" type="button" hidden
				data-pause="<?php esc_attr_e( 'Pause the photos', 'kaamase' ); ?>"
				data-play="<?php esc_attr_e( 'Play the photos', 'kaamase' ); ?>"
				aria-label="<?php esc_attr_e( 'Pause the photos', 'kaamase' ); ?>">
				<svg class="ka-hero__pause-icon" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><rect x="3" y="2" width="3.5" height="12" rx="1" fill="currentColor"/><rect x="9.5" y="2" width="3.5" height="12" rx="1" fill="currentColor"/></svg>
				<svg class="ka-hero__play-icon" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M4 2.5v11a1 1 0 0 0 1.5.86l9-5.5a1 1 0 0 0 0-1.72l-9-5.5A1 1 0 0 0 4 2.5z" fill="currentColor"/></svg>
			</button>
		<?php endif; ?>

	</div>

	<div class="ka-container">
		<div class="ka-hero__inner">

			<?php
			/* --------------------------------------------------------------
			 * Search
			 *
			 * Two fields and two buttons. Trade and district, because that
			 * is how a person actually asks the question out loud. The same
			 * two answers find either side, so there are two doors out of
			 * one form: Find workers goes to the worker list, Find work goes
			 * to the job list, carrying the same choices.
			 *
			 * This is a plain GET form, and the second button chooses its
			 * address with formaction, which needs no script. It works with
			 * JavaScript switched off, on a browser from 2015, and the
			 * result is a shareable URL somebody can paste into WhatsApp.
			 * ------------------------------------------------------------ */
			?>
			<form class="ka-hero__search" role="search" method="get"
				action="<?php echo esc_url( home_url( '/workers/' ) ); ?>">

				<div class="ka-hero__grid">

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
									'show_option_all' => __( 'All kinds of work', 'kaamase' ),
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

					<div class="ka-hero__actions">
						<button class="ka-btn ka-btn--action ka-btn--lg" type="submit">
							<?php esc_html_e( 'Find workers', 'kaamase' ); ?>
						</button>

						<button class="ka-btn ka-btn--primary ka-btn--lg" type="submit"
							formaction="<?php echo esc_url( home_url( '/jobs/' ) ); ?>">
							<?php esc_html_e( 'Find work', 'kaamase' ); ?>
						</button>
					</div>

				</div>

			</form>

			<ul class="ka-hero__facts">
				<li>
					<?php echo $kaamase_tick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup above. ?>
					<?php esc_html_e( 'Free for job seekers, always', 'kaamase' ); ?>
				</li>

				<?php if ( $kaamase_districts ) : ?>
					<li>
						<?php echo $kaamase_tick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: number of districts */
								_n( 'All %s district', 'All %s districts', count( $kaamase_districts ), 'kaamase' ),
								number_format_i18n( count( $kaamase_districts ) )
							)
						);
						?>
					</li>
				<?php endif; ?>

				<?php if ( $kaamase_trade_count ) : ?>
					<li>
						<?php echo $kaamase_tick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: number of trades, every kind of work from teacher to mason */
								_n( '%s kind of work', '%s kinds of work', $kaamase_trade_count, 'kaamase' ),
								number_format_i18n( $kaamase_trade_count )
							)
						);
						?>
					</li>
				<?php endif; ?>

				<li>
					<?php echo $kaamase_tick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php // The names of the three languages, each in itself, so they are never translated. ?>
					English &middot; हिन्दी &middot; Nagamese
				</li>
			</ul>

		</div>
	</div>

	<?php if ( count( $kaamase_hero_photos ) > 1 ) : ?>
		<script>
		(function () {
			var hero = document.querySelector('.ka-hero--photos');

			if (!hero) { return; }

			var slides = [].slice.call(hero.querySelectorAll('.ka-hero__slide'));
			var button = hero.querySelector('.ka-hero__pause');
			var still  = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

			/*
			 * Somebody who asked for less motion keeps the first photo,
			 * and never downloads the others.
			 */
			if (slides.length < 2 || still) { return; }

			var current = 0;
			var timer   = null;
			var paused  = false;

			/* Photos two onwards, once the page itself has finished. */
			function fetchRest() {
				slides.forEach(function (slide) {
					var source = slide.querySelector('source[data-srcset]');
					var img    = slide.querySelector('img[data-src]');

					if (source) {
						source.srcset = source.getAttribute('data-srcset');
						source.removeAttribute('data-srcset');
					}

					if (img) {
						img.src = img.getAttribute('data-src');
						img.removeAttribute('data-src');
					}
				});
			}

			/* A photo that has not arrived is skipped, never shown half loaded. */
			function arrived(slide) {
				var img = slide.querySelector('img');
				return img && img.complete && img.naturalWidth > 1;
			}

			function next() {
				for (var step = 1; step < slides.length; step++) {
					var n = (current + step) % slides.length;

					if (arrived(slides[n])) {
						slides[current].classList.remove('is-on');
						slides[n].classList.add('is-on');
						current = n;
						return;
					}
				}
			}

			function stop() {
				if (timer) { clearInterval(timer); timer = null; }
			}

			/* Nothing moves in a tab nobody is looking at. */
			function start() {
				stop();

				if (!paused && !document.hidden) { timer = setInterval(next, 5500); }
			}

			if (document.readyState === 'complete') {
				fetchRest();
			} else {
				window.addEventListener('load', fetchRest);
			}

			document.addEventListener('visibilitychange', start);

			if (button) {
				button.hidden = false;

				button.addEventListener('click', function () {
					paused = !paused;
					button.classList.toggle('is-paused', paused);
					button.setAttribute('aria-label', button.getAttribute(paused ? 'data-play' : 'data-pause'));

					if (paused) { stop(); } else { next(); start(); }
				});
			}

			start();
		})();
		</script>
	<?php endif; ?>
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
		<section class="ka-container ka-section ka-home-trades">

			<h2 class="ka-home-trades__title"><?php esc_html_e( 'Popular categories', 'kaamase' ); ?></h2>

			<div class="ka-chips-scroll">
				<?php foreach ( $kaamase_trades as $kaamase_trade ) : ?>
					<?php $kaamase_link = get_term_link( $kaamase_trade ); ?>
					<?php if ( ! is_wp_error( $kaamase_link ) ) : ?>
						<a class="ka-chip" href="<?php echo esc_url( $kaamase_link ); ?>">
							<?php echo esc_html( $kaamase_trade->name ); ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>

				<a class="ka-chip ka-chip--more" href="<?php echo esc_url( home_url( '/trades/' ) ); ?>">
					<?php esc_html_e( 'All categories', 'kaamase' ); ?>
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

			/*
			 * The same rule the job list uses: a job whose date has passed
			 * is not shown, even in the hours before the daily task marks
			 * it closed. Otherwise the front page could advertise work
			 * that the job list has already stopped showing.
			 */
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_kaamase_expires',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_kaamase_expires',
					'value'   => time(),
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	if ( $kaamase_jobs->have_posts() ) :
		?>
		<section class="ka-container ka-section">

			<div class="ka-section-head">
				<h2><?php esc_html_e( 'Latest jobs in Nagaland', 'kaamase' ); ?></h2>
				<p><?php esc_html_e( 'Government and private jobs from across the state. Job seekers never pay to apply.', 'kaamase' ); ?></p>
			</div>

			<div class="ka-grid ka-grid--2 ka-grid--3">
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

			<div class="ka-section-head">
				<h2><?php esc_html_e( 'Workers available now in Nagaland', 'kaamase' ); ?></h2>
				<p><?php esc_html_e( 'See what they do, where they are, their ratings and who vouches for them.', 'kaamase' ); ?></p>
			</div>

			<div class="ka-grid ka-grid--2 ka-grid--3">
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
   BY DISTRICT

   Every district, each one a link to its own page of jobs and workers.
   Those pages are what answers "jobs in Kohima" or "electrician in
   Dimapur" in a search engine, and a link from the front page is the
   strongest signal the site can give that they matter. It is also the
   quickest way in for somebody who thinks in places rather than trades.
   ========================================================================== */

if ( $kaamase_districts ) :
	?>
	<section class="ka-container ka-section">

		<div class="ka-section-head">
			<h2><?php esc_html_e( 'Find jobs and workers by district', 'kaamase' ); ?></h2>
			<p><?php esc_html_e( 'Every district of Nagaland, from Dimapur and Kohima to Mon and Noklak.', 'kaamase' ); ?></p>
		</div>

		<ul class="ka-districts">
			<?php foreach ( $kaamase_districts as $kaamase_district ) : ?>
				<?php $kaamase_link = get_term_link( $kaamase_district ); ?>
				<?php if ( ! is_wp_error( $kaamase_link ) ) : ?>
					<li>
						<a class="ka-district" href="<?php echo esc_url( $kaamase_link ); ?>">
							<span class="ka-district__name"><?php echo esc_html( $kaamase_district->name ); ?></span>
							<span class="ka-district__sub"><?php esc_html_e( 'Jobs and workers', 'kaamase' ); ?></span>
						</a>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>

	</section>
	<?php
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
/* ==========================================================================
   QUESTIONS

   Plain answers to what people ask a search box or an AI assistant. The
   first one is open, so the page states what Kaam Ase is without anybody
   having to tap. The rest stay closed to keep the page short; the text is
   in the page either way, and a search engine reads it all.

   Only drawn when the core plugin is there to supply the answers.
   ========================================================================== */

if ( function_exists( 'kaamase_home_faq' ) ) :

	$kaamase_faq = kaamase_home_faq();

	if ( $kaamase_faq ) :
		?>
		<section class="ka-section ka-home-faq" id="questions">
			<div class="ka-container ka-container--narrow">

				<div class="ka-section-head ka-section-head--center">
					<h2><?php esc_html_e( 'Finding work and workers in Nagaland', 'kaamase' ); ?></h2>
					<p><?php esc_html_e( 'Plain answers to what people ask most.', 'kaamase' ); ?></p>
				</div>

				<div class="ka-faq">
					<?php foreach ( $kaamase_faq as $kaamase_i => $kaamase_row ) : ?>
						<details class="ka-faq__item" <?php echo 0 === $kaamase_i ? 'open' : ''; ?>>
							<summary>
								<h3 class="ka-faq__q"><?php echo esc_html( $kaamase_row['q'] ); ?></h3>
							</summary>
							<div class="ka-faq__a">
								<p><?php echo esc_html( $kaamase_row['a'] ); ?></p>
							</div>
						</details>
					<?php endforeach; ?>
				</div>

			</div>
		</section>
		<?php
	endif;

endif;

get_footer();
