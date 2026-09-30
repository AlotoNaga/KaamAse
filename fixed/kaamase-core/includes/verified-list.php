<?php
/**
 * The verified list.
 *
 * Everybody who carries the tick right now, in a room of its own.
 *
 * What it is
 * ----------
 * A list for the app, beside Workers and Employers, of the workers, teams
 * and employers whose account has the mark. It does two jobs: the people
 * who have the tick get seen, and the people who do not can see what
 * having it looks like.
 *
 * What it is not
 * --------------
 * It does not touch the Workers or Employers lists. exposure.php keeps the
 * promise that paying never moves anybody up those, and this keeps it:
 * nothing here changes their order or who is in them. This room is extra.
 *
 * Who is in it
 * ------------
 * Exactly the people the tick is drawn on, decided by the function that
 * draws it, kaamase_has_been_called(): the call is on record and the plan
 * is still running. So nobody is listed without the tick, and nobody with
 * it is missing. When a plan runs out, or mark-changes.php takes the tick
 * off after a name, number or photo change, they leave on the next
 * request with nothing to clear.
 *
 * Employers only for an account that may see the employer directory, the
 * same rule as /employers: signed in, email confirmed. Somebody signed
 * out still gets the workers and teams, and the answer says employers
 * were left out so the app can say why.
 *
 * The order
 * ---------
 * A shuffle that changes once a day, so every verified person has days at
 * the top instead of the same few always first. It holds still for the
 * whole day, so paging never reshuffles under somebody. sort=newest lists
 * the most recently verified first instead.
 *
 * Deliberately not the exposure queue. That records who reached a first
 * page and sends them back for tomorrow, so being shown here would cost
 * somebody their turn on the main Workers list. The order here is worked
 * out from the date and records nothing.
 *
 * For the app only. The website has no page for it.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.13.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHO HAS THE TICK
   ========================================================================== */

if ( ! function_exists( 'kaamase_verified_list_kinds' ) ) {
	/**
	 * The word the app uses for each kind of profile, and its post type.
	 *
	 * The words are the ones the shapers already put in "type", so a
	 * filter the app sends and the cards it gets back say the same thing.
	 *
	 * @since 1.13.0
	 * @return array<string,string>
	 */
	function kaamase_verified_list_kinds() {

		return array(
			'worker'   => 'kaamase_worker',
			'team'     => 'kaamase_gang',
			'employer' => 'kaamase_employer',
		);
	}
}

if ( ! function_exists( 'kaamase_verified_list_users' ) ) {
	/**
	 * Every account that carries the tick right now.
	 *
	 * Asked in two steps: who has a call on record, then which of those
	 * kaamase_has_been_called() still says yes to. The second step is the
	 * same test the mark itself uses, so the two can never disagree.
	 *
	 * @since 1.13.0
	 * @return int[] User IDs.
	 */
	function kaamase_verified_list_users() {

		if ( ! defined( 'KAAMASE_CALLED_AT_KEY' ) || ! function_exists( 'kaamase_has_been_called' ) ) {
			return array();
		}

		$ids = get_users(
			array(
				'fields'      => 'ID',
				'count_total' => false,
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => KAAMASE_CALLED_AT_KEY,
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$ids = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );

		if ( empty( $ids ) ) {
			return array();
		}

		// Everybody's meta in one query, rather than one per person below.
		update_meta_cache( 'user', $ids );

		return array_values( array_filter( $ids, 'kaamase_has_been_called' ) );
	}
}


if ( ! function_exists( 'kaamase_verified_list_param' ) ) {
	/**
	 * One parameter from the request, as plain text.
	 *
	 * Anything that is not a single value -- type[]=x in the address,
	 * say -- counts as not given, rather than being turned into the word
	 * Array and a warning in the log.
	 *
	 * @since 1.13.0
	 * @param WP_REST_Request $request Request.
	 * @param string          $key     Parameter name.
	 * @return string
	 */
	function kaamase_verified_list_param( $request, $key ) {

		$value = $request->get_param( $key );

		return is_scalar( $value ) ? (string) $value : '';
	}
}


/* ==========================================================================
   2. THEIR PROFILES
   ========================================================================== */

if ( ! function_exists( 'kaamase_verified_list_find' ) ) {
	/**
	 * The published profiles of those accounts, with the filters applied.
	 *
	 * IDs only. The order is set explicitly so exposure.php leaves this
	 * query alone: it gives any unordered worker query from the app the
	 * exposure queue, and that queue records who was shown.
	 *
	 * @since 1.13.0
	 * @param int[]    $users   Accounts with the tick.
	 * @param string[] $types   Post types to include.
	 * @param array    $filters Keys trade, district, search, already cleaned.
	 * @return int[] Profile IDs.
	 */
	function kaamase_verified_list_find( $users, $types, $filters ) {

		/*
		 * Both checks matter. An empty author__in is not "nobody", it is
		 * no filter at all, and it would list every profile on the site
		 * under the heading Verified.
		 */
		if ( empty( $users ) || empty( $types ) ) {
			return array();
		}

		$args = array(
			'post_type'           => $types,
			'post_status'         => 'publish',
			'author__in'          => $users,
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'ID',
			'order'               => 'ASC',
		);

		if ( '' !== $filters['trade'] ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'kaamase_trade',
					'field'    => 'slug',
					'terms'    => $filters['trade'],
				),
			);
		}

		/*
		 * The district field rather than the district taxonomy, because
		 * the field is what every card shows and every kind of profile
		 * has it. The employer directory filters on it for the same
		 * reason.
		 */
		if ( '' !== $filters['district'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => KAAMASE_META_PREFIX . 'district',
					'value' => $filters['district'],
				),
			);
		}

		if ( '' !== $filters['search'] ) {
			$args['s'] = $filters['search'];
		}

		$query = new WP_Query( $args );

		return array_values( array_filter( array_map( 'intval', (array) $query->posts ) ) );
	}
}

if ( ! function_exists( 'kaamase_verified_list_order' ) ) {
	/**
	 * Put the profiles in order.
	 *
	 * @since 1.13.0
	 * @param int[]  $ids  Profile IDs.
	 * @param string $sort shuffle or newest.
	 * @return int[]
	 */
	function kaamase_verified_list_order( $ids, $sort ) {

		if ( 'newest' === $sort ) {

			// When each owner was verified. The profile's ID breaks a tie.
			$since = array();

			foreach ( $ids as $id ) {
				$since[ $id ] = (int) get_user_meta( (int) get_post_field( 'post_author', $id ), KAAMASE_CALLED_AT_KEY, true );
			}

			usort(
				$ids,
				static function ( $a, $b ) use ( $since ) {
					return array( $since[ $b ], $b ) <=> array( $since[ $a ], $a );
				}
			);

			return $ids;
		}

		/*
		 * The daily shuffle. Each profile gets a number from its ID and
		 * today's date in the site's own timezone, so the order is the
		 * same all day and different tomorrow.
		 */
		$day = wp_date( 'Ymd' );

		usort(
			$ids,
			static function ( $a, $b ) use ( $day ) {
				return array( crc32( $day . ':' . $a ), $a ) <=> array( crc32( $day . ':' . $b ), $b );
			}
		);

		return $ids;
	}
}


/* ==========================================================================
   3. THE ENDPOINT

   GET /kaamase/v1/verified

   type      all (default), worker, team or employer
   trade     a trade slug, as on /workers
   district  a district slug
   search    words from the name or description
   sort      shuffle (default) or newest
   page      from 1
   per_page  up to 50, default 20

   Answers in the same envelope as /workers -- items, total, page,
   has_more -- plus:

   counts              how many of each kind match, before type is applied
   employers_included  false when the caller may not see employers
   sort                the order used
   ========================================================================== */

if ( ! function_exists( 'kaamase_verified_list_route' ) ) {
	/**
	 * Register the endpoint.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	function kaamase_verified_list_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/verified',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_verified_list_rest',
				'permission_callback' => '__return_true',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_verified_list_route' );

if ( ! function_exists( 'kaamase_verified_list_rest' ) ) {
	/**
	 * Answer the endpoint.
	 *
	 * @since 1.13.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_verified_list_rest( $request ) {

		$kinds     = kaamase_verified_list_kinds();
		$employers = function_exists( 'kaamase_may_browse_employers' ) && kaamase_may_browse_employers();

		$type = sanitize_key( kaamase_verified_list_param( $request, 'type' ) );
		$type = isset( $kinds[ $type ] ) ? $type : 'all';

		/*
		 * Asking for employers alone, without being allowed to see them,
		 * gets the refusal /employers gives, word for word and code for
		 * code, so the app offers the same sign in or confirm step.
		 * Asking for everything just leaves them out.
		 */
		if ( 'employer' === $type && ! $employers ) {

			$refusal = function_exists( 'kaamase_rest_require_employer_browse' )
				? kaamase_rest_require_employer_browse()
				: true;

			if ( ! is_wp_error( $refusal ) ) {
				$refusal = new WP_Error(
					'kaamase_signed_out',
					__( 'Sign in to see who is hiring.', 'kaamase-core' ),
					array( 'status' => 401 )
				);
			}

			return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $refusal ) : $refusal;
		}

		$filters = array(
			'trade'    => function_exists( 'kaamase_match_trade' ) ? (string) kaamase_match_trade( kaamase_verified_list_param( $request, 'trade' ) ) : '',
			'district' => function_exists( 'kaamase_match_district' ) ? (string) kaamase_match_district( kaamase_verified_list_param( $request, 'district' ) ) : '',
			'search'   => sanitize_text_field( kaamase_verified_list_param( $request, 'search' ) ),
		);

		/*
		 * Cast, not absint, for the same reason as the employer
		 * directory: absint( '-3' ) is 3, which would turn a nonsense
		 * page number into page three instead of page one.
		 *
		 * And a ceiling no real list reaches, because page times page
		 * size has to stay a whole number: an absurd page overflowed it
		 * into a float, and array_slice() stopped the request with a
		 * fatal error instead of answering with an empty page.
		 */
		$sort = 'newest' === sanitize_key( kaamase_verified_list_param( $request, 'sort' ) ) ? 'newest' : 'shuffle';
		$page = min( max( 1, (int) kaamase_verified_list_param( $request, 'page' ) ), 100000 );
		$per  = (int) kaamase_verified_list_param( $request, 'per_page' );
		$per  = $per > 0 ? min( $per, 50 ) : 20;

		$types = array( $kinds['worker'], $kinds['team'] );

		if ( $employers ) {
			$types[] = $kinds['employer'];
		}

		$ids = kaamase_verified_list_find( kaamase_verified_list_users(), $types, $filters );

		// The posts themselves, in one query, for the counts and the order.
		if ( $ids && function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $ids, false, false );
		}

		$counts = array_fill_keys( array_keys( $kinds ), 0 );
		$word   = array_flip( $kinds );
		$kept   = array();

		foreach ( $ids as $id ) {

			$kind = $word[ (string) get_post_type( $id ) ] ?? '';

			if ( '' === $kind ) {
				continue;
			}

			++$counts[ $kind ];

			if ( 'all' === $type || $kind === $type ) {
				$kept[] = $id;
			}
		}

		$kept  = kaamase_verified_list_order( $kept, $sort );
		$total = count( $kept );
		$shown = array_slice( $kept, ( $page - 1 ) * $per, $per );

		// Fields and trades for this page only, in two queries.
		if ( $shown ) {
			update_meta_cache( 'post', $shown );
			update_object_term_cache( $shown, $types );
		}

		$items = array();

		foreach ( $shown as $id ) {

			$post = get_post( $id );

			if ( ! $post ) {
				continue;
			}

			$shaped = $kinds['employer'] === $post->post_type
				? kaamase_shape_employer( $post, false )
				: kaamase_shape_worker( $post, false );

			if ( $shaped ) {
				$items[] = $shaped;
			}
		}

		return new WP_REST_Response(
			array(
				'items'              => $items,
				'total'              => $total,
				'page'               => $page,
				'has_more'           => ( $page * $per ) < $total,
				'counts'             => $counts,
				'employers_included' => $employers,
				'sort'               => $sort,
			),
			200
		);
	}
}
