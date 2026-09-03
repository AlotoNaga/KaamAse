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


/* ==========================================================================
   3. COUNTING WHAT THE APP OPENS

   The counter has never seen the app at all. kaamase_view_catch_singular
   asks is_singular(), which is only ever true for a page of the website,
   and a REST request stops inside parse_request long before that hook
   runs. exposure.php says most of the traffic is the app, so the count
   on every profile has been missing the majority of real openings since
   the day it started.

   Opening a profile in the app is the same act as opening it in a
   browser, so it is counted the same way: the id is put where the
   website puts it, and the shutdown handler that already exists records
   it. One recording path, one set of rules. Nothing here decides who
   counts -- kaamase_record_view still refuses the owner, refuses staff,
   refuses robots and holds the thirty minute cooldown.
   ========================================================================== */

if ( ! function_exists( 'kaamase_views_rest_opened' ) ) {
	/**
	 * Note that this request opened one profile or job.
	 *
	 * Only a successful GET of a single record. A list is not somebody
	 * opening a profile, and a failed request is not somebody seeing
	 * one.
	 *
	 * @since 1.5.0
	 * @param WP_HTTP_Response $response Response.
	 * @param WP_REST_Server   $server   Server.
	 * @param WP_REST_Request  $request  Request.
	 * @return WP_HTTP_Response The response, untouched.
	 */
	function kaamase_views_rest_opened( $response, $server, $request ) {

		unset( $server );

		if ( ! defined( 'KAAMASE_REST_NS' ) || ! $request instanceof WP_REST_Request ) {
			return $response;
		}

		if ( 'GET' !== $request->get_method() ) {
			return $response;
		}

		if ( ! $response instanceof WP_REST_Response || 200 !== $response->get_status() ) {
			return $response;
		}

		$ns    = preg_quote( KAAMASE_REST_NS, '#' );
		$route = (string) $request->get_route();

		/*
		 * By id, and by slug. The slug routes are how a kaamase.com link
		 * opens a profile inside the app, which is the whole point of
		 * the universal links, and they carry no id in the address.
		 */
		$one = '#^/' . $ns . '/(workers|jobs|employers)/(?:slug/[a-z0-9\-_]+|\d+)$#';

		if ( ! preg_match( $one, $route ) ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['id'] ) ) {
			return $response;
		}

		/*
		 * Handed to the website's own pending slot rather than written
		 * here. shutdown fires after a REST request as it does after a
		 * page, so the same handler does the writing and the app can
		 * never drift from the website about what counts.
		 */
		$GLOBALS['kaamase_view_pending'] = (int) $data['id'];

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_views_rest_opened', 10, 3 );


/* ==========================================================================
   4. THE VIEWS THE CACHE SWALLOWS

   A view is recorded on shutdown, and shutdown only happens if PHP ran.
   When LiteSpeed answers from store PHP never starts, so nothing is
   counted. Since fix 29 a signed in visitor always gets an uncached
   page and is always counted; a signed out one usually is not. So the
   count leans towards people with accounts and misses the visitor
   arriving from Google, which is precisely who the trade and district
   pages exist to reach.

   The page therefore asks to be counted after it has loaded, from an
   address the cache cannot answer.

   Why this cannot inflate anything
   --------------------------------
   It goes through kaamase_record_view like everything else, and that
   upsert only adds a hit when last_hit is older than the cooldown. So
   when PHP did run and already counted the view, this second ask adds
   nothing. It is the cooldown, not any cleverness here, that makes
   asking twice safe.

   The owner, staff and robots are refused there too, and a stranger is
   still one visitor_key, so the most any single browser can add is one
   hit per profile per thirty minutes.
   ========================================================================== */

/** Most times one browser may ask to be counted in an hour. */
if ( ! defined( 'KAAMASE_SEEN_PER_HOUR' ) ) {
	define( 'KAAMASE_SEEN_PER_HOUR', 120 );
}

if ( ! function_exists( 'kaamase_rest_seen' ) ) {
	/**
	 * Count a page that was served from the cache.
	 *
	 * @since 1.5.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response Always 204. The page is not waiting on an answer.
	 */
	function kaamase_rest_seen( $request ) {

		$post_id = absint( $request->get_param( 'id' ) );

		if ( ! $post_id || ! function_exists( 'kaamase_record_view' ) ) {
			return new WP_REST_Response( null, 204 );
		}

		/*
		 * A ceiling per browser per hour. The cooldown already stops one
		 * profile being counted twice; this stops one script walking
		 * every profile on the site. A person opening sixty profiles in
		 * an hour is doing a hard day's looking and is still under it.
		 */
		if ( function_exists( 'kaamase_rate_bump' ) && function_exists( 'kaamase_view_visitor_key' ) ) {

			$asked = kaamase_rate_bump( 'seen_' . kaamase_view_visitor_key(), HOUR_IN_SECONDS );

			if ( $asked > KAAMASE_SEEN_PER_HOUR ) {
				return new WP_REST_Response( null, 204 );
			}
		}

		// Every rule about who counts lives there, not here.
		kaamase_record_view( $post_id );

		return new WP_REST_Response( null, 204 );
	}
}

if ( ! function_exists( 'kaamase_views_seen_route' ) ) {
	/**
	 * Register the counting address.
	 *
	 * Public on purpose: the whole point is the visitor who has no
	 * account. A POST, because it changes something, and because a GET
	 * that changes something gets fired by link scanners and prefetch.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_views_seen_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/seen',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_seen',
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_views_seen_route' );

if ( ! function_exists( 'kaamase_views_beacon' ) ) {
	/**
	 * Ask to be counted, once, after the page has loaded.
	 *
	 * sendBeacon where there is one: the browser posts it in its own
	 * time, after the page is done, and it survives the reader tapping
	 * a link immediately. It is the one request in the theme that is
	 * allowed to happen after the page is usable, and it blocks nothing.
	 *
	 * The id is written into the page, and the page it is written into
	 * is that same profile, so a cached copy carries the right number
	 * for everybody who is handed it.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_views_beacon() {

		if ( ! is_singular() || ! function_exists( 'kaamase_view_subject_type' ) ) {
			return;
		}

		$post_id = (int) get_queried_object_id();

		if ( ! $post_id || '' === kaamase_view_subject_type( (string) get_post_type( $post_id ) ) ) {
			return;
		}

		$url = esc_url_raw( rest_url( KAAMASE_REST_NS . '/seen' ) );
		?>
		<script>
		(function () {
			var to = <?php echo wp_json_encode( $url ); ?>;
			var body = new Blob(
				[ JSON.stringify({ id: <?php echo (int) $post_id; ?> }) ],
				{ type: 'application/json' }
			);

			/*
			 * After load, never during it. On a village connection the
			 * page finishing matters and this does not.
			 */
			function ask() {
				try {
					if (navigator.sendBeacon && navigator.sendBeacon(to, body)) { return; }
					fetch(to, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify({ id: <?php echo (int) $post_id; ?> }),
						keepalive: true
					});
				} catch (e) { /* Counting is never worth an error on the page. */ }
			}

			if (document.readyState === 'complete') { ask(); }
			else { window.addEventListener('load', ask, { once: true }); }
		})();
		</script>
		<?php
	}
}
add_action( 'wp_footer', 'kaamase_views_beacon', 30 );
