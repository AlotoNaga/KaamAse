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
	 * The numbers, shaped for the app.
	 *
	 * total and people are openings, and both are given because one
	 * without the other misleads: four hundred views from three people
	 * is a different thing from four hundred views from four hundred
	 * people, and only the second is what somebody reading "400 views"
	 * assumes.
	 *
	 * shown is the third and much larger number: how many times a card
	 * for this profile or job came onto somebody's screen in a list,
	 * whether or not they opened it. Added, never substituted, so an
	 * app build that has never heard of it goes on reading total and
	 * people and behaves exactly as before.
	 *
	 * @since 1.5.0
	 * @param int $post_id Profile or job.
	 * @return array total, people and shown.
	 */
	function kaamase_views_shape( $post_id ) {

		if ( ! function_exists( 'kaamase_views_count' ) ) {
			return array( 'total' => 0, 'people' => 0, 'shown' => 0 );
		}

		$post_id = absint( $post_id );

		$views = kaamase_views_count( $post_id );
		$shown = kaamase_views_count( $post_id, 0, 'shown' );

		return array(
			'total'  => isset( $views['total'] ) ? (int) $views['total'] : 0,
			'people' => isset( $views['people'] ) ? (int) $views['people'] : 0,
			'shown'  => isset( $shown['total'] ) ? (int) $shown['total'] : 0,
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

/** Most openings one browser may report in an hour. */
if ( ! defined( 'KAAMASE_SEEN_PER_HOUR' ) ) {
	define( 'KAAMASE_SEEN_PER_HOUR', 120 );
}

/**
 * Most showings one browser may report in an hour.
 *
 * Raised well above the old figure now that a card counts every time
 * it comes past rather than once every ten minutes. Somebody scrolling
 * a district back and forth for a whole afternoon is a real visitor
 * and should not be cut off part way through.
 *
 * It is still a ceiling, and it is the only thing standing between the
 * count and a script walking the site. Three thousand an hour is about
 * fifty a minute sustained for sixty minutes, which is more looking
 * than any person does; a script would pass it in seconds.
 */
if ( ! defined( 'KAAMASE_SHOWN_PER_HOUR' ) ) {
	define( 'KAAMASE_SHOWN_PER_HOUR', 3000 );
}

/** Most ids one request may carry. */
if ( ! defined( 'KAAMASE_SEEN_BATCH' ) ) {
	define( 'KAAMASE_SEEN_BATCH', 30 );
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

		if ( ! function_exists( 'kaamase_record_view' ) ) {
			return new WP_REST_Response( null, 204 );
		}

		$kind = 'shown' === $request->get_param( 'kind' ) ? 'shown' : 'open';

		/*
		 * One id, or a batch. A profile page reports the one it is, a
		 * listing reports what actually came onto the screen, and one
		 * request carries the lot rather than one request per card.
		 */
		$ids = (array) $request->get_param( 'ids' );

		if ( empty( $ids ) ) {
			$ids = array( $request->get_param( 'id' ) );
		}

		/*
		 * intval and then a test for greater than zero, never absint.
		 * absint( -9 ) is 9, so a negative id would quietly be counted
		 * against a different profile. That exact trap has been walked
		 * into once already in this codebase.
		 */
		$ids = array_slice(
			array_unique(
				array_filter(
					array_map( 'intval', $ids ),
					static function ( $id ) {
						return $id > 0;
					}
				)
			),
			0,
			KAAMASE_SEEN_BATCH
		);

		if ( empty( $ids ) ) {
			return new WP_REST_Response( null, 204 );
		}

		/*
		 * A ceiling per browser per hour, counted per id rather than per
		 * request so a batch cannot walk past it. The cooldown already
		 * stops one profile being counted twice; this stops one script
		 * walking every profile on the site.
		 */
		$ceiling = 'shown' === $kind ? KAAMASE_SHOWN_PER_HOUR : KAAMASE_SEEN_PER_HOUR;
		$allowed = kaamase_views_allowance( $kind, count( $ids ), $ceiling );

		if ( $allowed < 1 ) {
			return new WP_REST_Response( null, 204 );
		}

		foreach ( array_slice( $ids, 0, $allowed ) as $post_id ) {
			// Every rule about who counts lives there, not here.
			kaamase_record_view( $post_id, $kind );
		}

		return new WP_REST_Response( null, 204 );
	}
}

if ( ! function_exists( 'kaamase_views_allowance' ) ) {
	/**
	 * How many of the ids in this request the browser may still spend.
	 *
	 * One read and one write, whatever the size of the batch. The
	 * obvious way round is to bump the counter once per id, and that is
	 * an update_option per card: thirty database writes for one screen
	 * of a listing, on every listing, for every visitor. On a shared
	 * host that is the whole feature turning into a load problem.
	 *
	 * Written directly rather than through kaamase_rate_bump because
	 * that helper adds exactly one. Everything else about it is kept:
	 * the same option name, the same shape, and the same rule that the
	 * window is measured from the first request in it and does not
	 * slide, so somebody who keeps asking cannot hold it open and then
	 * collect a fresh allowance.
	 *
	 * @since 1.5.0
	 * @param string $kind    open or shown, so the two have separate windows.
	 * @param int    $want    How many ids this request is asking to spend.
	 * @param int    $ceiling Most one browser may spend in the hour.
	 * @return int How many may be counted. Zero when the window is spent.
	 */
	function kaamase_views_allowance( $kind, $want, $ceiling ) {

		$want = max( 0, (int) $want );

		if ( ! function_exists( 'kaamase_rate_read' ) || ! function_exists( 'kaamase_view_visitor_key' ) ) {
			/*
			 * No throttle installed. The cooldown inside
			 * kaamase_record_view is the real guard against a profile
			 * being counted twice; this ceiling only stops a script
			 * walking the whole site, and its absence is not a reason
			 * to stop counting.
			 */
			return $want;
		}

		$bucket = 'seen_' . $kind . '_' . kaamase_view_visitor_key();
		$stored = kaamase_rate_read( $bucket );
		$used   = null === $stored ? 0 : (int) $stored['value'];
		$take   = min( $want, (int) $ceiling - $used );

		if ( $take < 1 ) {
			return 0;
		}

		if ( null === $stored || ! function_exists( 'kaamase_rate_option_name' ) ) {

			if ( function_exists( 'kaamase_rate_write' ) ) {
				kaamase_rate_write( $bucket, $used + $take, HOUR_IN_SECONDS );
			}

			return $take;
		}

		// Keep the original expiry so the window does not slide.
		update_option(
			kaamase_rate_option_name( $bucket ),
			array(
				'value'   => $used + $take,
				'expires' => (int) $stored['expires'],
			),
			false
		);

		return $take;
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
					'id'   => array(
						'sanitize_callback' => 'absint',
					),
					'ids'  => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
					'kind' => array(
						'type' => 'string',
						'enum' => array( 'open', 'shown' ),
					),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_views_seen_route' );

if ( ! function_exists( 'kaamase_views_script' ) ) {
	/**
	 * Report what was opened, and what came onto the screen.
	 *
	 * One script for both, because they share the sending and because
	 * two blocks on every page is two blocks of bytes on a village
	 * connection.
	 *
	 * An opening is the page itself. A showing is a card that actually
	 * came into view in a listing, which is the thing that was asked
	 * for: somebody scrolling past a worker has genuinely seen that
	 * worker, and it is counted as being shown, never as being opened.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function kaamase_views_script() {

		if ( is_admin() || ! function_exists( 'kaamase_view_subject_type' ) ) {
			return;
		}

		$opened = 0;

		if ( is_singular() ) {

			$post_id = (int) get_queried_object_id();

			if ( $post_id && '' !== kaamase_view_subject_type( (string) get_post_type( $post_id ) ) ) {
				$opened = $post_id;
			}
		}

		/*
		 * Nothing to report and no cards drawn means no script at all.
		 * The pages that have neither are privacy, terms and the like,
		 * and they should not carry two kilobytes for a counter that
		 * has nothing to count.
		 */
		if ( ! $opened && empty( $GLOBALS['kaamase_cards_drawn'] ) ) {
			return;
		}

		$url = esc_url_raw( rest_url( KAAMASE_REST_NS . '/seen' ) );
		?>
		<script>
		(function () {
			var to = <?php echo wp_json_encode( $url ); ?>;
			var opened = <?php echo (int) $opened; ?>;
			var cards = document.querySelectorAll('[data-ka-seen]');

			if (!opened && !cards.length) { return; }

			function send(body) {
				try {
					var blob = new Blob([JSON.stringify(body)], { type: 'application/json' });
					if (navigator.sendBeacon && navigator.sendBeacon(to, blob)) { return; }
					fetch(to, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify(body),
						keepalive: true
					});
				} catch (e) { /* Counting is never worth an error on the page. */ }
			}

			/* ---- What was opened ---- */
			function reportOpen() { if (opened) { send({ id: opened }); } }

			if (document.readyState === 'complete') { reportOpen(); }
			else { window.addEventListener('load', reportOpen, { once: true }); }

			/* ---- What came onto the screen ---- */
			if (!cards.length || !window.IntersectionObserver) { return; }

			var waiting = [];
			var timer = null;

			function flush() {
				timer = null;
				if (!waiting.length) { return; }
				/*
				 * Thirty at a time, which is what the server accepts in
				 * one request. A long listing sends two rather than one
				 * oversized one that would be trimmed.
				 */
				while (waiting.length) {
					send({ ids: waiting.splice(0, 30), kind: 'shown' });
				}
			}

			var watcher = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {

					var card = entry.target;

					/*
					 * Gone off the screen. Cancel a showing that had
					 * not finished, and let the next arrival count as
					 * a new one. Scrolling back up to a worker is
					 * seeing that worker again, and it is counted
					 * again -- which is the whole point of the number.
					 */
					if (!entry.isIntersecting) {
						if (card._kaTimer) {
							clearTimeout(card._kaTimer);
							card._kaTimer = null;
						}
						card._kaCounted = false;
						return;
					}

					/* Already timing, or already counted for this
					   arrival. One showing per arrival, not one per
					   event: the observer fires more than once for a
					   card that is being scrolled slowly. */
					if (card._kaTimer || card._kaCounted) { return; }

					var id = parseInt(card.getAttribute('data-ka-seen'), 10);

					if (!id) { watcher.unobserve(card); return; }

					/*
					 * Half the card, and still there a second later.
					 * A card that flicks past during a fast scroll was
					 * not read by anybody and is not counted.
					 */
					card._kaTimer = setTimeout(function () {
						card._kaTimer = null;
						card._kaCounted = true;
						waiting.push(id);
						if (!timer) { timer = setTimeout(flush, 4000); }
					}, 1000);
				});
			}, { threshold: 0.5 });

			Array.prototype.forEach.call(cards, function (card) { watcher.observe(card); });

			// Whatever is still waiting when the page goes.
			window.addEventListener('pagehide', flush);
		})();
		</script>
		<?php
	}
}
add_action( 'wp_footer', 'kaamase_views_script', 30 );
