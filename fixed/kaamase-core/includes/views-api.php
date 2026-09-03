<?php
/**
 * View counts and who looked at you, for the app.
 *
 * Phase three. The website already shows both; this is the same two
 * things over the API so the app can show them as well, from exactly
 * the same numbers rather than a second idea of them.
 *
 * Why a filter and not an edit to rest-shape.php
 * ---------------------------------------------
 * Every shape in that file already ends with apply_filters. Adding a
 * field through the filter means that file is not touched at all, which
 * matters here: a duplicate of a shape function was written once before
 * in this programme and quietly replaced the real one, renaming a field
 * and dropping another, and five callers went on calling it.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE COUNT ON EVERY PROFILE AND JOB
   ========================================================================== */

if ( ! function_exists( 'kaamase_views_shape' ) ) {
	/**
	 * The two numbers, shaped for the app.
	 *
	 * Both, because one without the other misleads: four hundred views
	 * from three people is a different thing from four hundred views
	 * from four hundred people, and only the second is what somebody
	 * reading "400 views" assumes.
	 *
	 * @since 1.5.0
	 * @param int $post_id Profile or job.
	 * @return array total and people.
	 */
	function kaamase_views_shape( $post_id ) {

		if ( ! function_exists( 'kaamase_views_count' ) ) {
			return array( 'total' => 0, 'people' => 0 );
		}

		$views = kaamase_views_count( absint( $post_id ) );

		return array(
			'total'  => isset( $views['total'] ) ? (int) $views['total'] : 0,
			'people' => isset( $views['people'] ) ? (int) $views['people'] : 0,
		);
	}
}

if ( ! function_exists( 'kaamase_views_in_shape' ) ) {
	/**
	 * Add the count to a shaped profile or job.
	 *
	 * On the short shape as well as the full one, because the app draws
	 * cards from the short one and the website shows the count on its
	 * cards. A list costs no extra queries: the_posts primes every count
	 * on the page in one go before any of this runs.
	 *
	 * @since 1.5.0
	 * @param array        $out  The shaped record.
	 * @param WP_Post|int  $post The post it came from.
	 * @return array
	 */
	function kaamase_views_in_shape( $out, $post = null ) {

		if ( ! is_array( $out ) ) {
			return $out;
		}

		// The filters pass a WP_Post; some callers pass an ID.
		$id = 0;

		if ( $post instanceof WP_Post ) {
			$id = (int) $post->ID;
		} elseif ( is_numeric( $post ) ) {
			$id = (int) $post;
		} elseif ( isset( $out['id'] ) ) {
			$id = (int) $out['id'];
		}

		if ( ! $id ) {
			return $out;
		}

		$out['views'] = kaamase_views_shape( $id );

		return $out;
	}
}
add_filter( 'kaamase_shape_worker', 'kaamase_views_in_shape', 10, 2 );
add_filter( 'kaamase_shape_job', 'kaamase_views_in_shape', 10, 2 );
add_filter( 'kaamase_shape_employer', 'kaamase_views_in_shape', 10, 2 );


/* ==========================================================================
   2. WHO LOOKED AT YOU
   ========================================================================== */

if ( ! function_exists( 'kaamase_rest_looked' ) ) {
	/**
	 * The same list the website draws, in the same window.
	 *
	 * The decision about what to show is made here rather than in the
	 * app. Two clients working out separately whether somebody has paid
	 * is two chances to disagree about it, and the one that disagrees
	 * quietly is the one nobody finds.
	 *
	 * @since 1.5.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_looked( $request ) {

		unset( $request );

		$user_id = get_current_user_id();

		if ( ! $user_id || ! function_exists( 'kaamase_views_of_mine' ) ) {
			return new WP_REST_Response(
				array(
					'window_days' => 0,
					'is_paid'     => false,
					'people'      => 0,
					'viewers'     => array(),
					'upgrade'     => array( 'show' => false, 'older_people' => 0 ),
				),
				200
			);
		}

		$days = function_exists( 'kaamase_who_looked_window' )
			? kaamase_who_looked_window( $user_id )
			: 7;

		$paid = function_exists( 'kaamase_pay_is_active' ) && kaamase_pay_is_active( $user_id );
		$rows = kaamase_views_of_mine( $user_id, $days, 200 );

		$ids = array();

		foreach ( $rows as $row ) {
			$ids[] = (int) $row['viewer_id'];
		}

		$people = function_exists( 'kaamase_who_looked_people' )
			? kaamase_who_looked_people( $ids )
			: array();

		$viewers = array();

		foreach ( $rows as $row ) {

			$viewer = (int) $row['viewer_id'];

			// The account went between the view and this request.
			if ( ! isset( $people[ $viewer ] ) ) {
				continue;
			}

			$subject = get_post( (int) $row['subject_id'] );

			if ( ! $subject ) {
				continue;
			}

			$viewers[] = array(
				'id'        => $viewer,
				'name'      => (string) $people[ $viewer ]['name'],
				'url'       => (string) $people[ $viewer ]['url'],
				'initials'  => function_exists( 'kaamase_shape_initials' )
					? kaamase_shape_initials( $people[ $viewer ]['name'] )
					: '',
				'subject'   => array(
					'id'    => (int) $subject->ID,
					'type'  => (string) $row['subject_type'],
					'title' => get_the_title( $subject ),
					'url'   => (string) get_permalink( $subject ),
				),
				'hits'      => max( 1, (int) $row['hits'] ),
				'last_seen' => (int) $row['last_seen'],
			);
		}

		/*
		 * Asked only when the answer can change what is shown: a free
		 * account with an empty week. Offering a year of nothing is
		 * selling a window on an empty wall, so the app is told whether
		 * there is anything behind it rather than left to assume.
		 */
		$older = 0;

		if ( empty( $rows ) && ! $paid ) {

			$deep = kaamase_views_of_mine( $user_id, KAAMASE_WHO_LOOKED_PAID_DAYS, 200 );
			$seen = array();

			foreach ( $deep as $row ) {
				$seen[] = (int) $row['viewer_id'];
			}

			$older = count( array_unique( $seen ) );
		}

		return new WP_REST_Response(
			array(
				'window_days' => (int) $days,
				'is_paid'     => (bool) $paid,
				'people'      => count( array_unique( $ids ) ),
				'viewers'     => $viewers,
				'upgrade'     => array(
					'show'         => ( ! $paid && ( ! empty( $viewers ) || $older > 0 ) ),
					'older_people' => (int) $older,
				),
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_views_routes' ) ) {
	/**
	 * Register the one new route.
	 *
	 * Under /me/, because it is a fact about the account asking and
	 * about nobody else. Everything under that prefix already needs a
	 * sign in, and this needs it for the same reason.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_views_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/looked',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_looked',
				'permission_callback' => 'kaamase_rest_require_login',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_views_routes' );
