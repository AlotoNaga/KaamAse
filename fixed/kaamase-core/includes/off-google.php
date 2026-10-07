<?php
/**
 * Keeping a profile off Google, for the people who ask.
 *
 * Profiles are on Google by default, and that stays the default. A mason
 * whose name comes up when somebody searches "mason Dimapur" gets calls a
 * hidden profile never would, and every profile Google shows brings people
 * to the platform.
 *
 * But it is the person's name, and some people have a reason not to want
 * it searchable: somebody leaving a bad situation, somebody whose family
 * does not know they are looking for work, a woman who does not want her
 * photograph found by a stranger. For them this is one switch on the
 * dashboard, and one in the app.
 *
 * What the switch does
 * --------------------
 * Every profile page of that account -- worker, team, employer and a
 * public professional profile -- tells search engines not to list it,
 * and leaves the sitemaps. Nothing else changes. They are still in every
 * list on Kaam Ase, employers still find them and reach them the same
 * way, and a link they share still opens with its preview.
 *
 * What it cannot do, and says so
 * ------------------------------
 * Google drops a page it already shows only when it next visits, which
 * can take weeks. And a list of many people, such as the masons in
 * Dimapur, can still appear on Google with their name in it, because the
 * list is not theirs to switch off. The words on the switch say both,
 * because a privacy setting that promises more than it does is worse
 * than none.
 *
 * Account pages -- the dashboard, saved, my team -- are not touched here.
 * Google only ever sees a sign-in screen on those.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/** Set on an account that asked to be kept off Google. Holds when. */
define( 'KAAMASE_OFF_GOOGLE_KEY', 'kaamase_off_google' );


/* ==========================================================================
   1. THE SETTING
   ========================================================================== */

if ( ! function_exists( 'kaamase_off_google_types' ) ) {
	/**
	 * The kinds of profile the switch covers.
	 *
	 * @since 1.0.0
	 * @return string[]
	 */
	function kaamase_off_google_types() {

		$types = array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' );

		if ( defined( 'KAAMASE_PROF_TYPE' ) ) {
			$types[] = KAAMASE_PROF_TYPE;
		}

		return $types;
	}
}

if ( ! function_exists( 'kaamase_is_off_google' ) ) {
	/**
	 * Whether an account asked to be kept off Google.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return bool
	 */
	function kaamase_is_off_google( $user_id ) {

		$user_id = absint( $user_id );

		return $user_id && (bool) get_user_meta( $user_id, KAAMASE_OFF_GOOGLE_KEY, true );
	}
}

if ( ! function_exists( 'kaamase_off_google_profiles' ) ) {
	/**
	 * Every profile of an account, whatever its state.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return int[]
	 */
	function kaamase_off_google_profiles( $user_id ) {

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array();
		}

		return array_map(
			'intval',
			(array) get_posts(
				array(
					'post_type'      => kaamase_off_google_types(),
					'author'         => $user_id,
					'post_status'    => 'any',
					'posts_per_page' => 20,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);
	}
}

if ( ! function_exists( 'kaamase_off_google_set' ) ) {
	/**
	 * Turn the switch on or off for an account.
	 *
	 * The stored copies of their pages and of the sitemaps are cleared at
	 * once, so the next visit from a search engine reads the new answer
	 * rather than one kept from before.
	 *
	 * @since 1.0.0
	 * @param int  $user_id Account.
	 * @param bool $off     True to keep them off Google.
	 * @return void
	 */
	function kaamase_off_google_set( $user_id, $off ) {

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return;
		}

		if ( $off ) {
			if ( ! kaamase_is_off_google( $user_id ) ) {
				update_user_meta( $user_id, KAAMASE_OFF_GOOGLE_KEY, time() );
			}
		} else {
			delete_user_meta( $user_id, KAAMASE_OFF_GOOGLE_KEY );
		}

		foreach ( kaamase_off_google_profiles( $user_id ) as $post_id ) {
			do_action( 'litespeed_purge_post', $post_id );
		}

		if ( class_exists( '\RankMath\Sitemap\Cache_Watcher' ) && method_exists( '\RankMath\Sitemap\Cache_Watcher', 'invalidate' ) ) {
			foreach ( kaamase_off_google_types() as $type ) {
				\RankMath\Sitemap\Cache_Watcher::invalidate( $type );
			}
		}

		/**
		 * Fires when somebody turns the Google switch on or off.
		 *
		 * @since 1.0.0
		 * @param int  $user_id Account.
		 * @param bool $off     True when they are now kept off Google.
		 */
		do_action( 'kaamase_off_google_changed', $user_id, (bool) $off );
	}
}


/* ==========================================================================
   2. TELLING SEARCH ENGINES
   ========================================================================== */

if ( ! function_exists( 'kaamase_off_google_here' ) ) {
	/**
	 * Whether the page being shown is a profile kept off Google.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_off_google_here() {

		if ( ! is_singular( kaamase_off_google_types() ) ) {
			return false;
		}

		$post = get_queried_object();

		return $post instanceof WP_Post && kaamase_is_off_google( (int) $post->post_author );
	}
}

if ( ! function_exists( 'kaamase_off_google_robots' ) ) {
	/**
	 * Ask search engines not to list the page, nor the photograph on it.
	 *
	 * Links on it are still followed: the trades and places it links to are
	 * not the person.
	 *
	 * @since 1.0.0
	 * @param array $robots Robots directives.
	 * @return array
	 */
	function kaamase_off_google_robots( $robots ) {

		if ( kaamase_off_google_here() ) {
			unset( $robots['index'], $robots['max-image-preview'] );
			$robots['noindex']      = true;
			$robots['noimageindex'] = true;
		}

		return $robots;
	}
}
add_filter( 'wp_robots', 'kaamase_off_google_robots', 30 );

if ( ! function_exists( 'kaamase_off_google_rank_math_robots' ) ) {
	/**
	 * The same, for Rank Math, which writes its own robots tag.
	 *
	 * @since 1.0.0
	 * @param array $robots Directives keyed by name.
	 * @return array
	 */
	function kaamase_off_google_rank_math_robots( $robots ) {

		if ( ! kaamase_off_google_here() ) {
			return $robots;
		}

		$robots = is_array( $robots ) ? $robots : array();

		unset( $robots['max-image-preview'] );

		$robots['index']        = 'noindex';
		$robots['noimageindex'] = 'noimageindex';

		return $robots;
	}
}
add_filter( 'rank_math/frontend/robots', 'kaamase_off_google_rank_math_robots', 30 );

if ( ! function_exists( 'kaamase_off_google_header' ) ) {
	/**
	 * And as a header, which search engines read even where a theme or a
	 * plugin has changed what the page head says.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_off_google_header() {

		if ( kaamase_off_google_here() && ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, noimageindex', true );
		}
	}
}
add_action( 'template_redirect', 'kaamase_off_google_header', 3 );

if ( ! function_exists( 'kaamase_off_google_rank_math_sitemap' ) ) {
	/**
	 * Leave their profiles out of Rank Math's sitemaps.
	 *
	 * @since 1.0.0
	 * @param array|false $url  The sitemap entry.
	 * @param string      $type post, term or user.
	 * @param object      $post The post, with post_type and post_author.
	 * @return array|false
	 */
	function kaamase_off_google_rank_math_sitemap( $url, $type, $post ) {

		if ( 'post' !== $type || ! is_object( $post ) || empty( $post->post_author ) || empty( $post->post_type ) ) {
			return $url;
		}

		if ( in_array( (string) $post->post_type, kaamase_off_google_types(), true ) && kaamase_is_off_google( (int) $post->post_author ) ) {
			return false;
		}

		return $url;
	}
}
add_filter( 'rank_math/sitemap/entry', 'kaamase_off_google_rank_math_sitemap', 10, 3 );

if ( ! function_exists( 'kaamase_off_google_users' ) ) {
	/**
	 * Every account kept off Google.
	 *
	 * @since 1.0.0
	 * @return int[]
	 */
	function kaamase_off_google_users() {

		static $ids = null;

		if ( null === $ids ) {
			$ids = array_map(
				'intval',
				(array) get_users(
					array(
						'fields'       => 'ID',
						'meta_key'     => KAAMASE_OFF_GOOGLE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_compare' => 'EXISTS',
						'count_total'  => false,
					)
				)
			);
		}

		return $ids;
	}
}

if ( ! function_exists( 'kaamase_off_google_core_sitemap' ) ) {
	/**
	 * And out of WordPress's own sitemap, for a site without Rank Math.
	 *
	 * @since 1.0.0
	 * @param array  $args      Query arguments.
	 * @param string $post_type Post type.
	 * @return array
	 */
	function kaamase_off_google_core_sitemap( $args, $post_type ) {

		if ( ! in_array( $post_type, kaamase_off_google_types(), true ) ) {
			return $args;
		}

		$off = kaamase_off_google_users();

		if ( $off ) {
			$args['author__not_in'] = array_merge( isset( $args['author__not_in'] ) ? (array) $args['author__not_in'] : array(), $off );
		}

		return $args;
	}
}
add_filter( 'wp_sitemaps_posts_query_args', 'kaamase_off_google_core_sitemap', 10, 2 );


/* ==========================================================================
   3. THE SWITCH ON THE WEBSITE
   ========================================================================== */

if ( ! function_exists( 'kaamase_off_google_card' ) ) {
	/**
	 * The switch, on the dashboard.
	 *
	 * @since 1.0.0
	 * @param int $user_id Who is looking.
	 * @return void
	 */
	function kaamase_off_google_card( $user_id ) {

		$user_id = (int) $user_id;
		$off     = kaamase_is_off_google( $user_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only choosing a message.
		$just = isset( $_GET['google'] ) ? sanitize_key( wp_unslash( $_GET['google'] ) ) : '';
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6" id="ka-google">

			<h2><?php esc_html_e( 'Your profile on Google', 'kaamase-core' ); ?></h2>

			<?php if ( 'off' === $just && $off ) : ?>
				<p class="ka-small ka-mt-4"><strong><?php esc_html_e( 'Done. Google is asked not to show your profile.', 'kaamase-core' ); ?></strong></p>
			<?php elseif ( 'on' === $just && ! $off ) : ?>
				<p class="ka-small ka-mt-4"><strong><?php esc_html_e( 'Done. Your profile can show on Google again.', 'kaamase-core' ); ?></strong></p>
			<?php endif; ?>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'Your profile can come up when somebody searches Google for your name or your kind of work, and that brings more calls. If you would rather it did not, untick this. Employers on Kaam Ase still find you and reach you exactly as now.', 'kaamase-core' ); ?>
			</p>

			<form method="post" action="" class="ka-stack ka-mt-4">
				<?php wp_nonce_field( 'kaamase_off_google', 'kaamase_off_google_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="off_google">

				<label class="ka-check">
					<input type="checkbox" name="kaamase_show_google" value="1" <?php checked( ! $off ); ?>>
					<span><?php esc_html_e( 'Show my profile on Google search', 'kaamase-core' ); ?></span>
				</label>

				<button class="ka-btn ka-btn--outline ka-btn--sm" type="submit"><?php esc_html_e( 'Save', 'kaamase-core' ); ?></button>
			</form>

			<p class="ka-small ka-mute ka-mt-4">
				<?php esc_html_e( 'Google can take a few weeks to drop a page it already shows. Kaam Ase lists of many people, such as all the masons in Dimapur, can still appear on Google with your name in them.', 'kaamase-core' ); ?>
			</p>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_off_google_card', 66 );

if ( ! function_exists( 'kaamase_off_google_handle' ) ) {
	/**
	 * Save the switch from the dashboard.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_off_google_handle() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'off_google' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_off_google_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_off_google_nonce'] ) ), 'kaamase_off_google' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		$show = ! empty( $_POST['kaamase_show_google'] );

		kaamase_off_google_set( get_current_user_id(), ! $show );

		$back = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : home_url( '/' );

		wp_safe_redirect( add_query_arg( 'google', $show ? 'on' : 'off', $back ) . '#ka-google' );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_off_google_handle' );


/* ==========================================================================
   4. THE SWITCH IN THE APP

   GET  /me                  on_google: true unless they switched it off
   POST /me/google           {show: true|false}  ->  {on_google}
   ========================================================================== */

if ( ! function_exists( 'kaamase_off_google_shape_me' ) ) {
	/**
	 * Tell the app where the switch stands.
	 *
	 * @since 1.0.0
	 * @param array $me      The account.
	 * @param int   $user_id Account.
	 * @return array
	 */
	function kaamase_off_google_shape_me( $me, $user_id ) {

		if ( is_array( $me ) ) {
			$me['on_google'] = ! kaamase_is_off_google( (int) $user_id );
		}

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_off_google_shape_me', 27, 2 );

if ( ! function_exists( 'kaamase_off_google_route' ) ) {
	/**
	 * Register POST /me/google.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_off_google_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/google',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_off_google_rest',
				'permission_callback' => function_exists( 'kaamase_rest_require_login' ) ? 'kaamase_rest_require_login' : 'is_user_logged_in',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_off_google_route' );

if ( ! function_exists( 'kaamase_off_google_rest' ) ) {
	/**
	 * Turn the switch from the app.
	 *
	 * show is required, rather than toggling, so a tap that is sent twice
	 * on a slow connection cannot undo itself.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_off_google_rest( $request ) {

		$show = $request->get_param( 'show' );

		if ( null === $show || '' === $show ) {

			$error = new WP_Error( 'kaamase_bad_value', __( 'Say whether your profile should show on Google.', 'kaamase-core' ), array( 'status' => 400 ) );

			return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $error ) : $error;
		}

		$show    = rest_sanitize_boolean( $show );
		$user_id = get_current_user_id();

		kaamase_off_google_set( $user_id, ! $show );

		return new WP_REST_Response( array( 'on_google' => ! kaamase_is_off_google( $user_id ) ), 200 );
	}
}
