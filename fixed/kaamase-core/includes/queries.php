<?php
/**
 * Queries.
 *
 * Search, filtering, and the order results come back in.
 *
 * Two decisions in this file shape the platform more than any feature
 * elsewhere in the plugin, so they are stated here rather than buried in
 * a switch statement.
 *
 * 1. Workers are NOT ranked by rating
 * -----------------------------------
 * Sorting by rating looks obviously correct and quietly kills a small
 * marketplace. In the first months almost nobody has a rating, so the
 * few people who get an early job float to the top of every search, take
 * all the calls, collect more ratings, and float higher. Two hundred
 * registered workers end up sharing whatever falls past the top five,
 * conclude the platform does nothing for them, and leave. Supply
 * collapses while the registration numbers still look healthy.
 *
 * So the default order is availability first, then a position that
 * rotates daily. Every worker who is free gets a turn near the top over
 * the course of a week. Rating is on the card and can be sorted on
 * deliberately. It is not what the platform pushes.
 *
 * 2. Cheapest first is never the default
 * --------------------------------------
 * A hiring platform that opens on the lowest day rate teaches every
 * worker that the way to get hired is to undercut the last one. That is
 * a wage floor collapsing, built by a sort order. Employers can choose
 * it. They are not handed it.
 *
 * @package KaamaseCore
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. QUERY VARS
   ========================================================================== */

if ( ! function_exists( 'kaamase_query_vars' ) ) {
	/**
	 * Filters the platform understands from a URL.
	 *
	 * Trade and district are taxonomy query vars, already registered in
	 * taxonomies.php, so WordPress handles those itself. These are the
	 * extras.
	 *
	 * @since 1.0.0
	 * @param string[] $vars Existing public query vars.
	 * @return string[] Filtered list.
	 */
	function kaamase_query_vars( $vars ) {

		$vars[] = 'kaamase_available';  // Only workers who are free.
		$vars[] = 'kaamase_max_rate';   // Upper limit on day rate.
		$vars[] = 'kaamase_min_rate';   // Lower limit on day rate.
		$vars[] = 'kaamase_sort';       // Ordering.
		$vars[] = 'kaamase_town';       // Narrower than district.
		$vars[] = 'kaamase_urgent';     // Urgent jobs only.
		$vars[] = 'kaamase_vouched';    // Vouched profiles only.

		return $vars;
	}
}
add_filter( 'query_vars', 'kaamase_query_vars' );


/* ==========================================================================
   2. THE MAIN QUERY
   ========================================================================== */

if ( ! function_exists( 'kaamase_queried_types' ) ) {
	/**
	 * Which Kaam Ase post types a query is asking for.
	 *
	 * Covers a post type archive, a taxonomy archive, and a search
	 * scoped by post_type in the URL.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query.
	 * @return string[] Matching post type names.
	 */
	function kaamase_queried_types( $query ) {

		$ours = kaamase_post_types();

		$requested = $query->get( 'post_type' );
		$requested = $requested ? (array) $requested : array();

		// A taxonomy archive carries no post_type, so infer it.
		if ( empty( $requested ) && $query->is_tax( array( 'kaamase_trade', 'kaamase_district' ) ) ) {
			$requested = array( 'kaamase_worker', 'kaamase_gang', 'kaamase_job' );
			$query->set( 'post_type', $requested );
		}

		return array_values( array_intersect( $requested, $ours ) );
	}
}

if ( ! function_exists( 'kaamase_filter_archives' ) ) {
	/**
	 * Apply Kaam Ase filters to public listings.
	 *
	 * Only touches the main query on the front end. Admin screens, feeds
	 * and secondary queries are left alone, because a plugin that
	 * rewrites every query on a site is a plugin that breaks something
	 * unrelated six months later.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query about to run.
	 * @return void
	 */
	function kaamase_filter_archives( $query ) {

		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$types = kaamase_queried_types( $query );

		if ( empty( $types ) ) {
			return;
		}

		$meta = array();

		/* ---- Jobs: never show one that has closed ---- */

		if ( in_array( 'kaamase_job', $types, true ) ) {

			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'     => KAAMASE_META_PREFIX . 'expires',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => KAAMASE_META_PREFIX . 'expires',
					'value'   => time(),
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			);

			if ( $query->get( 'kaamase_urgent' ) ) {
				$meta[] = array(
					'key'   => KAAMASE_META_PREFIX . 'urgent',
					'value' => '1',
				);
			}
		}

		/* ---- Workers and teams ---- */

		if ( array_intersect( array( 'kaamase_worker', 'kaamase_gang' ), $types ) ) {

			if ( $query->get( 'kaamase_available' ) ) {

				/*
				 * A worker who has never touched the availability
				 * control counts as available, because that is what the
				 * rest of the platform already says about them.
				 *
				 * The field defaults to live, so kaamase_field returns
				 * live when the row is missing and every card, profile
				 * and listing shows them as available now. Asking for
				 * the row itself excluded exactly those people, so
				 * ticking "Free for work now" hid most of the workers on
				 * the site, and the ones it hid were the ones the tick
				 * box was meant to find.
				 *
				 * NOT EXISTS brings the default into the query so the
				 * two agree. The same fix is in the REST layer, which
				 * had the same bug for the app.
				 */
				$meta[] = array(
					'relation' => 'OR',
					array(
						'key'   => KAAMASE_META_PREFIX . 'availability',
						'value' => 'live',
					),
					array(
						'key'     => KAAMASE_META_PREFIX . 'availability',
						'compare' => 'NOT EXISTS',
					),
				);
			}

			if ( $query->get( 'kaamase_vouched' ) ) {
				$meta[] = array(
					'key'     => KAAMASE_META_PREFIX . 'vouched_by',
					'compare' => '!=',
					'value'   => '',
				);
			}
		}

		/* ---- Rate range ---- */

		$rate_key = in_array( 'kaamase_job', $types, true )
			? KAAMASE_META_PREFIX . 'pay_amount'
			: KAAMASE_META_PREFIX . 'day_rate';

		$max = absint( $query->get( 'kaamase_max_rate' ) );
		$min = absint( $query->get( 'kaamase_min_rate' ) );

		if ( $max ) {
			/*
			 * A worker who has not set a rate is included rather than
			 * filtered out. Excluding them would punish an incomplete
			 * profile by making the person invisible, which they would
			 * never understand and could not diagnose.
			 */
			$meta[] = array(
				'relation' => 'OR',
				array(
					'key'     => $rate_key,
					'value'   => $max,
					'compare' => '<=',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => $rate_key,
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( $min ) {
			$meta[] = array(
				'key'     => $rate_key,
				'value'   => $min,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		/* ---- Town ---- */

		$town = $query->get( 'kaamase_town' );

		if ( $town ) {

			$matched = kaamase_match_town( $town );

			if ( $matched ) {
				$meta[] = array(
					'key'     => KAAMASE_META_PREFIX . 'town',
					'value'   => $matched,
					'compare' => '=',
				);
			}
		}

		if ( ! empty( $meta ) ) {

			if ( count( $meta ) > 1 ) {
				$meta['relation'] = 'AND';
			}

			$query->set( 'meta_query', $meta );
		}

		kaamase_apply_sort( $query, $types );
	}
}
add_action( 'pre_get_posts', 'kaamase_filter_archives' );


/* ==========================================================================
   3. ORDERING
   ========================================================================== */

if ( ! function_exists( 'kaamase_sort_options' ) ) {
	/**
	 * Orderings offered to a visitor.
	 *
	 * Cheapest first is present for an employer who wants it, listed
	 * last, and never the default. See the note at the top of this file.
	 *
	 * @since 1.0.0
	 * @param string $type Post type being listed.
	 * @return string[] Labels keyed by sort key.
	 */
	function kaamase_sort_options( $type = 'kaamase_worker' ) {

		if ( 'kaamase_job' === $type ) {
			return array(
				'newest'  => __( 'Newest first', 'kaamase-core' ),
				'urgent'  => __( 'Urgent first', 'kaamase-core' ),
				'highest' => __( 'Highest pay', 'kaamase-core' ),
			);
		}

		return array(
			'default'    => __( 'Available now', 'kaamase-core' ),
			'rated'      => __( 'Best rated', 'kaamase-core' ),
			'experience' => __( 'Most experienced', 'kaamase-core' ),
			'newest'     => __( 'Newest profiles', 'kaamase-core' ),
			'cheapest'   => __( 'Lowest day rate', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_apply_sort' ) ) {
	/**
	 * Set the ordering on a listing query.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query.
	 * @param string[] $types Post types being listed.
	 * @return void
	 */
	function kaamase_apply_sort( $query, $types ) {

		$sort = sanitize_key( (string) $query->get( 'kaamase_sort' ) );

		/* ---- Jobs ---- */

		if ( in_array( 'kaamase_job', $types, true ) ) {

			switch ( $sort ) {

				case 'highest':
					kaamase_sort_by_number( $query, 'pay_amount', 'DESC' );
					break;

				case 'urgent':
					kaamase_sort_by_number( $query, 'urgent', 'DESC' );
					break;

				default:
					// Freshness. A job board is only as good as its newest listing.
					$query->set( 'orderby', 'date' );
					$query->set( 'order', 'DESC' );
					break;
			}

			return;
		}

		/* ---- Workers and teams ---- */

		switch ( $sort ) {

			case 'rated':
				kaamase_sort_by_number( $query, 'rating_average', 'DESC' );
				break;

			case 'experience':
				kaamase_sort_by_number( $query, 'years_experience', 'DESC' );
				break;

			case 'cheapest':
				/*
				 * Zero counts as no answer here, not as the cheapest
				 * worker on the platform. A day rate of nothing is a
				 * blank field, and putting blanks at the top of Lowest
				 * day rate is worse than leaving them out.
				 */
				kaamase_sort_by_number( $query, 'day_rate', 'ASC', true );
				break;

			case 'newest':
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'DESC' );
				break;

			default:
				/*
				 * Rotation rather than ranking. See the note at the top
				 * of this file. Handled in SQL below, because availability
				 * first plus a rotating position cannot be expressed
				 * through WP_Query arguments alone.
				 */
				$query->set( 'orderby', 'kaamase_rotate' );
				break;
		}
	}
}

if ( ! function_exists( 'kaamase_sort_by_number' ) ) {
	/**
	 * Order a listing by a numeric field without dropping anybody.
	 *
	 * The bug this exists to kill
	 * ---------------------------
	 * Setting meta_key and ordering by meta_value_num makes WP_Query
	 * INNER JOIN the postmeta table. A profile with no row for that key
	 * is not sorted last, it is removed from the results entirely.
	 *
	 * That is not a rare case, it is the normal one. Registration writes
	 * a phone number and a district and nothing else, and the default in
	 * the field schema is a read time fallback that never writes a row.
	 * So a worker who has registered but not yet filled in their profile
	 * has no rating_average, no years_experience and no day_rate row at
	 * all.
	 *
	 * The result was that Best rated returned an empty page on a site
	 * where nobody had been rated yet, which is every site in its first
	 * month, and Lowest day rate and Most experienced hid every new
	 * worker on the platform.
	 *
	 * A LEFT JOIN keeps everybody and sorts the blanks to the bottom,
	 * which is what the sort was always meant to mean.
	 *
	 * @since 1.3.2
	 * @param WP_Query $query         The query being ordered.
	 * @param string   $key           Field key without the meta prefix.
	 * @param string   $direction     ASC or DESC.
	 * @param bool     $zero_is_blank Whether a stored zero counts as no answer.
	 * @return void
	 */
	function kaamase_sort_by_number( $query, $key, $direction = 'DESC', $zero_is_blank = false ) {

		/*
		 * meta_key is deliberately NOT set. Setting it is what creates
		 * the INNER JOIN this function exists to avoid.
		 */
		$query->set( 'orderby', 'kaamase_number' );
		$query->set( 'kaamase_sort_key', KAAMASE_META_PREFIX . $key );
		$query->set( 'kaamase_sort_dir', 'ASC' === strtoupper( (string) $direction ) ? 'ASC' : 'DESC' );
		$query->set( 'kaamase_sort_zero_blank', (bool) $zero_is_blank );
	}
}

if ( ! function_exists( 'kaamase_number_sort_join' ) ) {
	/**
	 * Attach the value being sorted on, without filtering anything out.
	 *
	 * @since 1.3.2
	 * @param string   $join  Existing JOIN clause.
	 * @param WP_Query $query The query.
	 * @return string Filtered clause.
	 */
	function kaamase_number_sort_join( $join, $query ) {

		if ( 'kaamase_number' !== $query->get( 'orderby' ) ) {
			return $join;
		}

		$key = (string) $query->get( 'kaamase_sort_key' );

		if ( '' === $key ) {
			return $join;
		}

		global $wpdb;

		return $join . $wpdb->prepare(
			" LEFT JOIN {$wpdb->postmeta} AS ka_sort
			ON ( ka_sort.post_id = {$wpdb->posts}.ID AND ka_sort.meta_key = %s ) ",
			$key
		);
	}
}
add_filter( 'posts_join', 'kaamase_number_sort_join', 10, 2 );

if ( ! function_exists( 'kaamase_number_orderby' ) ) {
	/**
	 * Real values first, best or cheapest first within them, blanks last.
	 *
	 * The leading CASE is what makes one function serve both directions.
	 * Without it, MySQL sorts NULL lowest, so descending puts blanks last
	 * on its own but ascending puts them first, and Lowest day rate would
	 * open with every worker who never named a rate.
	 *
	 * @since 1.3.2
	 * @param string   $orderby Existing ORDER BY clause.
	 * @param WP_Query $query   The query.
	 * @return string Filtered clause.
	 */
	function kaamase_number_orderby( $orderby, $query ) {

		if ( 'kaamase_number' !== $query->get( 'orderby' ) ) {
			return $orderby;
		}

		if ( '' === (string) $query->get( 'kaamase_sort_key' ) ) {
			return $orderby;
		}

		global $wpdb;

		$direction = 'ASC' === $query->get( 'kaamase_sort_dir' ) ? 'ASC' : 'DESC';

		// A blank is NULL when there is no row, and an empty string when there is one.
		$blank = "ka_sort.meta_value IS NULL OR ka_sort.meta_value = ''";

		if ( $query->get( 'kaamase_sort_zero_blank' ) ) {
			$blank .= ' OR ka_sort.meta_value + 0 <= 0';
		}

		/*
		 * DECIMAL rather than SIGNED, because rating_average is a float
		 * and casting to an integer would sort a 4.9 and a 4.1 as equal.
		 */
		return "CASE WHEN ( {$blank} ) THEN 1 ELSE 0 END ASC,
			CAST( COALESCE( ka_sort.meta_value, '0' ) AS DECIMAL(20,4) ) {$direction},
			{$wpdb->posts}.post_date DESC";
	}
}
add_filter( 'posts_orderby', 'kaamase_number_orderby', 10, 2 );

if ( ! function_exists( 'kaamase_rotation_orderby' ) ) {
	/**
	 * Available workers first, then a position that rotates daily.
	 *
	 * The rotation is the post ID multiplied by today's date, modulo a
	 * prime. It is stable within a day, so paging through results does
	 * not shuffle underneath somebody, and it changes overnight, so a
	 * different set of workers sits near the top tomorrow.
	 *
	 * No extra table and no stored column, which matters on the sort of
	 * shared hosting this will run on.
	 *
	 * @since 1.0.0
	 * @param string   $orderby Existing ORDER BY clause.
	 * @param WP_Query $query   The query.
	 * @return string Filtered clause.
	 */
	function kaamase_rotation_orderby( $orderby, $query ) {

		if ( 'kaamase_rotate' !== $query->get( 'orderby' ) ) {
			return $orderby;
		}

		global $wpdb;

		// Changes once a day.
		$seed = absint( gmdate( 'Ymd' ) );

		/*
		 * Available workers first, and a missing row counts as
		 * available.
		 *
		 * Same default as everywhere else: the field is live unless
		 * somebody changed it. Testing only for a row saying live meant
		 * every worker who never opened that control sorted below every
		 * worker who had, on every listing on the site, while being
		 * labelled available on their own card. NOT EXISTS puts them
		 * back where the label says they belong.
		 */
		return $wpdb->prepare(
			"CASE WHEN NOT EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} ka_av
				WHERE ka_av.post_id = {$wpdb->posts}.ID
				AND ka_av.meta_key = %s
				AND ka_av.meta_value <> 'live'
			) THEN 0 ELSE 1 END ASC,
			MOD({$wpdb->posts}.ID * %d, 97) ASC,
			{$wpdb->posts}.post_date DESC",
			KAAMASE_META_PREFIX . 'availability',
			$seed
		);
	}
}
add_filter( 'posts_orderby', 'kaamase_rotation_orderby', 10, 2 );


/* ==========================================================================
   4. SEARCH
   ========================================================================== */

if ( ! function_exists( 'kaamase_search_post_types' ) ) {
	/**
	 * Include platform content in site search.
	 *
	 * Without this, searching finds pages and posts and none of the
	 * workers or jobs, which is every visitor's first search.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query.
	 * @return void
	 */
	function kaamase_search_post_types( $query ) {

		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}

		// Respect a scoped search from an archive search box.
		if ( $query->get( 'post_type' ) ) {
			return;
		}

		$query->set(
			'post_type',
			array( 'kaamase_worker', 'kaamase_gang', 'kaamase_job', 'page', 'post' )
		);
	}
}
add_action( 'pre_get_posts', 'kaamase_search_post_types', 5 );

if ( ! function_exists( 'kaamase_search_redirect' ) ) {
	/**
	 * Send a search for a trade or a district to its archive.
	 *
	 * Somebody typing mason wants the mason page, not a keyword match
	 * against every profile that happens to contain the word. The archive
	 * is filtered, ordered and paginated. A search result is a pile.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_search_redirect() {

		if ( is_admin() || ! is_search() ) {
			return;
		}

		$term = trim( get_search_query() );

		if ( '' === $term || str_word_count( $term ) > 3 ) {
			return;
		}

		// Only redirect a bare search, never one that already has filters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['kaamase_trade'] ) || ! empty( $_GET['kaamase_district'] ) ) {
			return;
		}

		$trade = kaamase_match_trade( $term );

		if ( $trade ) {

			$link = get_term_link( $trade, 'kaamase_trade' );

			if ( ! is_wp_error( $link ) ) {
				wp_safe_redirect( $link, 302 );
				exit;
			}
		}

		$district = kaamase_match_district( $term );

		if ( $district ) {

			$link = get_term_link( $district, 'kaamase_district' );

			if ( ! is_wp_error( $link ) ) {
				wp_safe_redirect( $link, 302 );
				exit;
			}
		}
	}
}
add_action( 'template_redirect', 'kaamase_search_redirect', 20 );


/* ==========================================================================
   5. VISIBILITY
   ========================================================================== */

if ( ! function_exists( 'kaamase_listing_status' ) ) {
	/**
	 * Only published records appear in a public listing.
	 *
	 * Registration already creates profiles as drafts, so this is a
	 * second layer rather than the only one. It catches a profile
	 * published by hand in the admin, and it keeps closed jobs out of
	 * every archive.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query.
	 * @return void
	 */
	function kaamase_listing_status( $query ) {

		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		/*
		 * Listings only.
		 *
		 * Forcing publish on a singular query hides a record from the one
		 * person entitled to see it. A worker who has registered but not
		 * yet confirmed their email owns a draft profile, and the
		 * dashboard links them straight to it. Without this guard that
		 * link is a 404, on the very first screen after signing up.
		 */
		if ( $query->is_singular() ) {
			return;
		}

		if ( empty( kaamase_queried_types( $query ) ) ) {
			return;
		}

		$query->set( 'post_status', 'publish' );
	}
}
add_action( 'pre_get_posts', 'kaamase_listing_status', 20 );

if ( ! function_exists( 'kaamase_protect_closed_jobs' ) ) {
	/**
	 * Return 404 for a closed job opened directly.
	 *
	 * This is what makes the theme's 404 template honest. That template
	 * tells a visitor the job is probably filled and offers open ones
	 * instead, which is only true if a closed job actually 404s rather
	 * than sitting there looking live to somebody who followed a link
	 * forwarded through three WhatsApp groups.
	 *
	 * The owner and administrators still see it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_protect_closed_jobs() {

		if ( ! is_singular( 'kaamase_job' ) ) {
			return;
		}

		$job_id = get_queried_object_id();

		if ( kaamase_job_is_open( $job_id ) || current_user_can( 'edit_post', $job_id ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'kaamase_protect_closed_jobs', 10 );

if ( ! function_exists( 'kaamase_posts_per_page' ) ) {
	/**
	 * How many results per page.
	 *
	 * Twelve rather than the WordPress default of ten, because twelve
	 * divides evenly into two and three column grids. An orphan card
	 * alone on the last row looks like something failed to load.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query.
	 * @return void
	 */
	function kaamase_posts_per_page( $query ) {

		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( empty( kaamase_queried_types( $query ) ) ) {
			return;
		}

		$query->set( 'posts_per_page', 12 );
	}
}
add_action( 'pre_get_posts', 'kaamase_posts_per_page', 15 );


/* ==========================================================================
   6. FILTER BAR
   ========================================================================== */

if ( ! function_exists( 'kaamase_current_filters' ) ) {
	/**
	 * Filters currently applied, read from the query.
	 *
	 * @since 1.0.0
	 * @return array Filter values.
	 */
	function kaamase_current_filters() {

		return array(
			'trade'     => sanitize_key( (string) get_query_var( 'kaamase_trade' ) ),
			'district'  => sanitize_key( (string) get_query_var( 'kaamase_district' ) ),
			'sort'      => sanitize_key( (string) get_query_var( 'kaamase_sort' ) ),
			'available' => (bool) get_query_var( 'kaamase_available' ),
			'vouched'   => (bool) get_query_var( 'kaamase_vouched' ),
			'urgent'    => (bool) get_query_var( 'kaamase_urgent' ),
			'max_rate'  => absint( get_query_var( 'kaamase_max_rate' ) ),
			'min_rate'  => absint( get_query_var( 'kaamase_min_rate' ) ),
		);
	}
}

if ( ! function_exists( 'kaamase_has_active_filters' ) ) {
	/**
	 * Whether anything is filtered right now.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_has_active_filters() {

		foreach ( kaamase_current_filters() as $value ) {
			if ( $value ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'kaamase_current_path_url' ) ) {
	/**
	 * The current page URL with no query string.
	 *
	 * Rebuilt from the home URL and the parsed request rather than read
	 * from the server, because a URL taken straight out of REQUEST_URI
	 * and printed into a page is a reflected scripting hole.
	 *
	 * @since 1.0.0
	 * @return string URL.
	 */
	function kaamase_current_path_url() {

		global $wp;

		$request = isset( $wp->request ) ? (string) $wp->request : '';

		return home_url( user_trailingslashit( $request ) );
	}
}

if ( ! function_exists( 'kaamase_filter_bar' ) ) {
	/**
	 * Render the filter controls above a listing.
	 *
	 * A plain GET form. Works with no JavaScript, and every filtered view
	 * ends up with a URL somebody can paste into a WhatsApp group, which
	 * is how things actually get shared here.
	 *
	 * @since 1.0.0
	 * @param string $type Post type being listed.
	 * @return string Markup.
	 */
	function kaamase_filter_bar( $type = 'kaamase_worker' ) {

		$jobs    = 'kaamase_job' === $type;
		$current = kaamase_current_filters();

		ob_start();
		/*
		 * Open when a filter is already applied, shut otherwise.
		 *
		 * Closed by default because on a phone this panel filled the
		 * screen and pushed every worker below the fold: somebody
		 * arriving to see who is available was shown a form instead.
		 * Open when something is already narrowed down, because then
		 * hiding it makes the results look wrong for no visible reason.
		 */
		$has_filters = ! empty( $current['trade'] ) || ! empty( $current['district'] )
			|| ! empty( $current['available'] ) || ! empty( $current['vouched'] )
			|| ! empty( $current['urgent'] )
			|| ( ! empty( $current['sort'] ) && 'newest' !== $current['sort'] );
		?>
		<details class="ka-filters-wrap"<?php echo $has_filters ? ' open' : ''; ?>>

			<summary class="ka-filters-toggle">
				<?php
				echo esc_html(
					$has_filters
						? __( 'Change filters', 'kaamase-core' )
						: __( 'Filter these results', 'kaamase-core' )
				);
				?>
			</summary>

		<form class="ka-filters ka-card" method="get" action="">

			<?php
			/*
			 * On a taxonomy archive the term lives in the path, not the
			 * query string, so the trade and district selects are left
			 * out. Including them would let somebody pick a second trade
			 * and get an empty page they cannot explain.
			 */
			if ( ! is_tax() ) :
				?>
				<div class="ka-field">
					<label class="ka-label" for="ka-filter-trade"><?php esc_html_e( 'Trade', 'kaamase-core' ); ?></label>
					<select class="ka-select" id="ka-filter-trade" name="kaamase_trade">
						<option value=""><?php esc_html_e( 'Any trade', 'kaamase-core' ); ?></option>
						<?php foreach ( kaamase_trade_choices() as $group => $trades ) : ?>
							<optgroup label="<?php echo esc_attr( $group ); ?>">
								<?php foreach ( $trades as $slug => $name ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current['trade'], $slug ); ?>>
										<?php echo esc_html( $name ); ?>
									</option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ka-field">
					<label class="ka-label" for="ka-filter-district"><?php esc_html_e( 'District', 'kaamase-core' ); ?></label>
					<select class="ka-select" id="ka-filter-district" name="kaamase_district">
						<option value=""><?php esc_html_e( 'All Nagaland', 'kaamase-core' ); ?></option>
						<?php foreach ( kaamase_district_choices() as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current['district'], $slug ); ?>>
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<div class="ka-field">
				<label class="ka-label" for="ka-filter-sort"><?php esc_html_e( 'Show', 'kaamase-core' ); ?></label>
				<select class="ka-select" id="ka-filter-sort" name="kaamase_sort">
					<?php foreach ( kaamase_sort_options( $type ) as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['sort'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( $jobs ) : ?>

				<div class="ka-field">
					<label class="ka-check">
						<input type="checkbox" name="kaamase_urgent" value="1" <?php checked( $current['urgent'] ); ?>>
						<span><?php esc_html_e( 'Urgent only', 'kaamase-core' ); ?></span>
					</label>
				</div>

			<?php else : ?>

				<div class="ka-field">
					<label class="ka-check">
						<input type="checkbox" name="kaamase_available" value="1" <?php checked( $current['available'] ); ?>>
						<span><?php esc_html_e( 'Free for work now', 'kaamase-core' ); ?></span>
					</label>
				</div>

				<div class="ka-field">
					<label class="ka-check">
						<input type="checkbox" name="kaamase_vouched" value="1" <?php checked( $current['vouched'] ); ?>>
						<span><?php esc_html_e( 'Vouched only', 'kaamase-core' ); ?></span>
					</label>
				</div>

			<?php endif; ?>

			<div class="ka-cluster">
				<button class="ka-btn ka-btn--primary" type="submit">
					<?php esc_html_e( 'Show results', 'kaamase-core' ); ?>
				</button>

				<?php if ( kaamase_has_active_filters() ) : ?>
					<a class="ka-btn ka-btn--ghost ka-btn--sm" href="<?php echo esc_url( kaamase_current_path_url() ); ?>">
						<?php esc_html_e( 'Clear', 'kaamase-core' ); ?>
					</a>
				<?php endif; ?>
			</div>

		</form>

		</details>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_result_summary' ) ) {
	/**
	 * A sentence describing what is being shown.
	 *
	 * Useful to a visitor, and it is also the text that makes a filtered
	 * archive worth indexing. Eleven electricians in Kohima is exactly
	 * what somebody searching that phrase needs to find on the page.
	 *
	 * @since 1.0.0
	 * @return string Plain text.
	 */
	function kaamase_result_summary() {

		global $wp_query;

		$count   = absint( $wp_query->found_posts );
		$filters = kaamase_current_filters();

		$district = $filters['district'] ? kaamase_district_name( $filters['district'] ) : '';
		$trade    = '';

		$queried = get_queried_object();

		if ( is_tax( 'kaamase_district' ) && $queried instanceof WP_Term ) {
			$district = $queried->name;
		}

		if ( is_tax( 'kaamase_trade' ) && $queried instanceof WP_Term ) {
			$trade = strtolower( $queried->name );
		} elseif ( $filters['trade'] ) {

			$term = get_term_by( 'slug', $filters['trade'], 'kaamase_trade' );

			if ( $term instanceof WP_Term ) {
				$trade = strtolower( $term->name );
			}
		}

		if ( $trade ) {
			$what = sprintf(
				/* translators: 1: number of results, 2: trade name */
				__( '%1$s %2$s', 'kaamase-core' ),
				number_format_i18n( $count ),
				$trade
			);
		} else {
			$what = sprintf(
				/* translators: %s: number of results */
				_n( '%s result', '%s results', $count, 'kaamase-core' ),
				number_format_i18n( $count )
			);
		}

		if ( $district ) {
			return sprintf(
				/* translators: 1: what was found, 2: district name */
				__( '%1$s in %2$s', 'kaamase-core' ),
				$what,
				$district
			);
		}

		return sprintf(
			/* translators: %s: what was found */
			__( '%s across Nagaland', 'kaamase-core' ),
			$what
		);
	}
}