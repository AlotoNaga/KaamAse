<?php
/**
 * The employer directory.
 *
 * A browsable list of the people and businesses doing the hiring.
 *
 * Why this page exists
 * --------------------
 * Workers could see jobs and could see other workers. They could not see
 * employers. So the only thing a worker knew about whoever posted a job
 * was the one line in the advert, and the question every worker actually
 * asks first -- has this person hired anybody before, did they pay -- had
 * nowhere to be answered.
 *
 * Every employer profile already stores how many workers they have hired
 * and what those workers rated them. None of it was ever shown. This is
 * the page that shows it.
 *
 * Who can see it
 * --------------
 * Signed in with a confirmed email, and nothing more. Deliberately not
 * restricted to paying accounts.
 *
 * An employer is listed here so that workers will approach them, which
 * is the thing they are paying the platform for. Hiding the directory
 * behind a paywall would hide the employers from the workers they want
 * to reach, which is backwards. The value sold to an employer is being
 * IN this list; the value to a worker is reading it.
 *
 * It is kept off the open internet because a public page listing every
 * business on the platform, with district and hiring history, is a
 * scrapeable directory of local businesses and no employer agreed to
 * that when they registered.
 *
 * What is not on it
 * -----------------
 * No phone numbers. Contact goes through kaamase_can_contact() exactly as
 * everywhere else, one profile at a time, counted against the daily cap
 * that exists to stop numbers being harvested. A list that showed numbers
 * would be a way around that cap rather than a feature.
 *
 * @package KaamaseCore
 * @version 1.4.1
 * @since   1.4.1
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHO MAY READ IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_may_browse_employers' ) ) {
	/**
	 * Whether this account may see the employer directory.
	 *
	 * Split out rather than written inline because the website and the
	 * app both have to answer it, and two copies of a rule is how the
	 * two front doors end up disagreeing.
	 *
	 * @since 1.4.1
	 * @param int $user_id Optional. Defaults to the current user.
	 * @return bool
	 */
	function kaamase_may_browse_employers( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( $user_id ) ) {
			return false;
		}

		/**
		 * Filter who may browse the employer directory.
		 *
		 * The place to make this a paid feature later, without editing
		 * the page or the endpoint:
		 *
		 *     add_filter( 'kaamase_may_browse_employers', function ( $may, $user ) {
		 *         return $may && kaamase_has_plan( $user );
		 *     }, 10, 2 );
		 *
		 * @since 1.4.1
		 * @param bool $may     Whether this account may browse.
		 * @param int  $user_id The account being asked about.
		 */
		return (bool) apply_filters( 'kaamase_may_browse_employers', true, $user_id );
	}
}


/* ==========================================================================
   2. THE QUERY
   ========================================================================== */

if ( ! function_exists( 'kaamase_employer_query_args' ) ) {
	/**
	 * Build the directory query from whatever is in the URL.
	 *
	 * @since 1.4.1
	 * @param array $request Usually $_GET, already unslashed.
	 * @return array WP_Query arguments.
	 */
	function kaamase_employer_query_args( $request = array() ) {

		$district = isset( $request['district'] ) ? sanitize_title( (string) $request['district'] ) : '';
		$type     = isset( $request['type'] ) ? sanitize_key( (string) $request['type'] ) : '';
		$sort     = isset( $request['sort'] ) ? sanitize_key( (string) $request['sort'] ) : '';
		/*
		 * Cast, not absint. absint( '-5' ) is 5, so a negative page
		 * number quietly became page five instead of page one.
		 */
		$paged    = isset( $request['paged'] ) ? max( 1, (int) $request['paged'] ) : 1;

		$args = array(
			'post_type'      => 'kaamase_employer',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'paged'          => $paged,
		);

		if ( $district && function_exists( 'kaamase_districts' ) && isset( kaamase_districts()[ $district ] ) ) {
			$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_kaamase_district',
				'value' => $district,
			);
		}

		if ( in_array( $type, array( 'individual', 'contractor', 'company' ), true ) ) {
			$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_kaamase_employer_type',
				'value' => $type,
			);
		}

		if ( 'verified' === $sort ) {
			$args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_kaamase_verified',
				'value' => '1',
			);
		}

		/*
		 * Sorted with the LEFT JOIN helper, never with meta_key on the
		 * query itself.
		 *
		 * meta_key plus meta_value_num is an INNER JOIN, so an employer
		 * who has not hired anybody yet has no row to join against and
		 * drops out of the list entirely. On a directory whose whole
		 * point is showing who is here, silently hiding every new
		 * employer is the worst possible failure.
		 */
		if ( 'hires' === $sort && function_exists( 'kaamase_number_sort_args' ) ) {
			$args = array_merge( $args, kaamase_number_sort_args( 'hires_made', 'DESC' ) );
		} elseif ( 'rating' === $sort && function_exists( 'kaamase_number_sort_args' ) ) {
			$args = array_merge( $args, kaamase_number_sort_args( 'rating_average', 'DESC' ) );
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
		}

		return $args;
	}
}


/* ==========================================================================
   3. THE PAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_employer_index_shortcode' ) ) {
	/**
	 * Render the employer directory.
	 *
	 * @since 1.4.1
	 * @return string
	 */
	function kaamase_employer_index_shortcode() {

		if ( ! is_user_logged_in() ) {
			return kaamase_employer_index_notice(
				__( 'Sign in to see who is hiring', 'kaamase-core' ),
				__( 'This list shows every employer on Kaam Ase, which district they are in, how many workers they have taken on and how those workers rated them. It is for people with an account, so that employers are not listed on the open internet.', 'kaamase-core' ),
				wp_login_url( kaamase_page_url( 'employers' ) ),
				__( 'Sign in', 'kaamase-core' )
			);
		}

		if ( ! kaamase_may_browse_employers() ) {
			return kaamase_employer_index_notice(
				__( 'Confirm your email first', 'kaamase-core' ),
				__( 'We sent you a link when you registered. Open it and this list is yours. It is held back from unconfirmed accounts because an employer directory is worth harvesting.', 'kaamase-core' ),
				kaamase_page_url( 'dashboard' ),
				__( 'Go to my account', 'kaamase-core' )
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- reading filters from a GET, changes nothing.
		$request = array_map( 'sanitize_text_field', wp_unslash( (array) $_GET ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$query = new WP_Query( kaamase_employer_query_args( $request ) );

		ob_start();
		?>
		<div class="ka-stack">

			<?php echo kaamase_employer_filters( $request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( ! $query->have_posts() ) : ?>

				<div class="ka-card ka-card--pad-lg ka-center">
					<h2><?php esc_html_e( 'Nobody matches that', 'kaamase-core' ); ?></h2>
					<p class="ka-soft ka-mt-4">
						<?php esc_html_e( 'Try a different district, or clear the filters to see everybody.', 'kaamase-core' ); ?>
					</p>
					<a class="ka-btn ka-btn--outline ka-mt-6" href="<?php echo esc_url( kaamase_page_url( 'employers' ) ); ?>">
						<?php esc_html_e( 'Show all employers', 'kaamase-core' ); ?>
					</a>
				</div>

			<?php else : ?>

				<p class="ka-small ka-mute">
					<?php
					printf(
						esc_html(
							/* translators: %s: number of employers */
							_n( '%s employer', '%s employers', (int) $query->found_posts, 'kaamase-core' )
						),
						esc_html( number_format_i18n( (int) $query->found_posts ) )
					);
					?>
				</p>

				<div class="ka-grid ka-grid--2 ka-grid--3">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();

						if ( function_exists( 'kaamase_employer_card' ) ) {
							kaamase_employer_card( get_the_ID() );
						}
					endwhile;
					?>
				</div>

				<?php
				echo kaamase_employer_pagination( $query, $request ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>

			<?php endif; ?>

		</div>
		<?php

		wp_reset_postdata();

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_employer_index', 'kaamase_employer_index_shortcode' );


/* ==========================================================================
   4. THE PIECES THE PAGE IS BUILT FROM
   ========================================================================== */

if ( ! function_exists( 'kaamase_employer_index_notice' ) ) {
	/**
	 * The card shown instead of the list when somebody may not read it.
	 *
	 * A refusal that explains itself and offers the way through, rather
	 * than an empty page. Same reasoning as every other refusal on the
	 * platform: you cannot get past a wall you do not understand.
	 *
	 * @since 1.4.1
	 * @param string $title  Heading.
	 * @param string $body   Explanation.
	 * @param string $url    Where the button goes.
	 * @param string $button Button label.
	 * @return string
	 */
	function kaamase_employer_index_notice( $title, $body, $url, $button ) {

		ob_start();
		?>
		<div class="ka-card ka-card--pad-lg ka-center">
			<h2><?php echo esc_html( $title ); ?></h2>
			<p class="ka-soft ka-mt-4"><?php echo esc_html( $body ); ?></p>
			<a class="ka-btn ka-btn--primary ka-mt-6" href="<?php echo esc_url( $url ); ?>">
				<?php echo esc_html( $button ); ?>
			</a>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_employer_filters' ) ) {
	/**
	 * The filter bar.
	 *
	 * A plain GET form. It works with JavaScript switched off, which on
	 * the connections this runs over is not a hypothetical.
	 *
	 * @since 1.4.1
	 * @param array $request Current filter values.
	 * @return string
	 */
	function kaamase_employer_filters( $request ) {

		$district = isset( $request['district'] ) ? sanitize_title( (string) $request['district'] ) : '';
		$type     = isset( $request['type'] ) ? sanitize_key( (string) $request['type'] ) : '';
		$sort     = isset( $request['sort'] ) ? sanitize_key( (string) $request['sort'] ) : '';

		$types = array(
			''            => __( 'Any kind', 'kaamase-core' ),
			'individual'  => __( 'Individual', 'kaamase-core' ),
			'contractor'  => __( 'Contractor', 'kaamase-core' ),
			'company'     => __( 'Company', 'kaamase-core' ),
		);

		$sorts = array(
			''         => __( 'Newest first', 'kaamase-core' ),
			'hires'    => __( 'Most workers hired', 'kaamase-core' ),
			'rating'   => __( 'Best rated', 'kaamase-core' ),
			'verified' => __( 'Verified only', 'kaamase-core' ),
		);

		ob_start();
		?>
		<form class="ka-filters ka-card" method="get" action="<?php echo esc_url( kaamase_page_url( 'employers' ) ); ?>">

			<div class="ka-field">
				<label class="ka-label" for="ka-employer-district"><?php esc_html_e( 'District', 'kaamase-core' ); ?></label>
				<select class="ka-select" id="ka-employer-district" name="district">
					<option value=""><?php esc_html_e( 'Anywhere in Nagaland', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_districts() as $slug => $place ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $district, $slug ); ?>>
							<?php echo esc_html( $place['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-employer-type"><?php esc_html_e( 'Kind', 'kaamase-core' ); ?></label>
				<select class="ka-select" id="ka-employer-type" name="type">
					<?php foreach ( $types as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-employer-sort"><?php esc_html_e( 'Show', 'kaamase-core' ); ?></label>
				<select class="ka-select" id="ka-employer-sort" name="sort">
					<?php foreach ( $sorts as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $sort, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<button class="ka-btn ka-btn--primary" type="submit">
				<?php esc_html_e( 'Show these', 'kaamase-core' ); ?>
			</button>

		</form>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_employer_pagination' ) ) {
	/**
	 * Previous and next, keeping whatever filters are set.
	 *
	 * @since 1.4.1
	 * @param WP_Query $query   The finished query.
	 * @param array    $request Current filter values.
	 * @return string
	 */
	function kaamase_employer_pagination( $query, $request ) {

		$pages = (int) $query->max_num_pages;

		if ( $pages < 2 ) {
			return '';
		}

		$now  = isset( $request['paged'] ) ? max( 1, (int) $request['paged'] ) : 1;
		$base = kaamase_page_url( 'employers' );

		$keep = array_filter(
			array(
				'district' => isset( $request['district'] ) ? sanitize_title( (string) $request['district'] ) : '',
				'type'     => isset( $request['type'] ) ? sanitize_key( (string) $request['type'] ) : '',
				'sort'     => isset( $request['sort'] ) ? sanitize_key( (string) $request['sort'] ) : '',
			)
		);

		$link = static function ( $page ) use ( $base, $keep ) {
			return add_query_arg( array_merge( $keep, array( 'paged' => $page ) ), $base );
		};

		ob_start();
		?>
		<nav class="ka-cluster ka-cluster--between ka-mt-6" aria-label="<?php esc_attr_e( 'Pages', 'kaamase-core' ); ?>">

			<?php if ( $now > 1 ) : ?>
				<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( $link( $now - 1 ) ); ?>">
					<?php esc_html_e( 'Previous', 'kaamase-core' ); ?>
				</a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>

			<span class="ka-small ka-mute">
				<?php
				printf(
					/* translators: 1: current page, 2: total pages */
					esc_html__( 'Page %1$s of %2$s', 'kaamase-core' ),
					esc_html( number_format_i18n( $now ) ),
					esc_html( number_format_i18n( $pages ) )
				);
				?>
			</span>

			<?php if ( $now < $pages ) : ?>
				<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( $link( $now + 1 ) ); ?>">
					<?php esc_html_e( 'Next', 'kaamase-core' ); ?>
				</a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>

		</nav>
		<?php

		return (string) ob_get_clean();
	}
}
