<?php
/**
 * Asset loading.
 *
 * The whole theme ships one stylesheet and one script. That is a
 * deliberate constraint, not a limitation.
 *
 * On a high latency connection the cost of an asset is dominated by the
 * round trip, not the file size. Ten small files are slower than one
 * larger file even when the total bytes are lower. So everything lives
 * in style.css, and JavaScript is one deferred file that the page does
 * not wait for.
 *
 * Every enqueue here checks the file exists before registering it. The
 * theme is being built one file at a time, and a 404 on a stylesheet is
 * a broken page while a missing enqueue is just a plain one.
 *
 * @package Kaamase
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. FRONT END
   ========================================================================== */

if ( ! function_exists( 'kaamase_enqueue_frontend' ) ) {
	/**
	 * Load the public stylesheet and script.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_enqueue_frontend() {

		/*
		 * Main stylesheet.
		 *
		 * get_stylesheet_uri resolves to the child theme when one is
		 * active, so a child theme can override the design system without
		 * this file changing.
		 *
		 * When there is a child theme the parent goes first, and the
		 * dependency is decided here, before anything is enqueued, which
		 * is the only point at which it can be. This used to enqueue
		 * 'kaamase' with no dependencies and then try to re-enqueue it
		 * with one, but wp_enqueue_style does not re-register a handle
		 * that already exists, so the second call was discarded and the
		 * ordering it was reaching for never happened. A child theme's
		 * overrides could load before the design system they override.
		 */
		$deps = array();

		if ( is_child_theme() ) {

			wp_enqueue_style(
				'kaamase-parent',
				KAAMASE_URI . 'style.css',
				array(),
				KAAMASE_ASSET_VERSION
			);

			$deps[] = 'kaamase-parent';
		}

		wp_enqueue_style(
			'kaamase',
			get_stylesheet_uri(),
			$deps,
			KAAMASE_ASSET_VERSION
		);

		/*
		 * Theme script.
		 *
		 * Deferred and in the footer. Nothing on a first page load
		 * depends on it: the menu, the filters and the tab bar all work
		 * as plain links and forms without JavaScript, and the script
		 * upgrades them when it arrives. On a connection that drops
		 * halfway through loading, the site still works.
		 */
		$script_path = KAAMASE_DIR . 'assets/js/app.js';

		if ( file_exists( $script_path ) ) {

			wp_enqueue_script(
				'kaamase-app',
				KAAMASE_URI . 'assets/js/app.js',
				array(),
				KAAMASE_ASSET_VERSION,
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);

			wp_add_inline_script(
				'kaamase-app',
				'window.kaamase = ' . wp_json_encode( kaamase_script_config() ) . ';',
				'before'
			);
		}

		/**
		 * Fires after the theme has loaded its own assets.
		 *
		 * The core plugin hooks this so its assets always load after the
		 * design system, and so it never has to guess at handle names.
		 *
		 * @since 1.0.0
		 */
		do_action( 'kaamase_enqueue_assets' );
	}
}
add_action( 'wp_enqueue_scripts', 'kaamase_enqueue_frontend' );


/**
 * Configuration passed to the front end script.
 *
 * Rules for this array:
 *
 *   1. Nothing private. It is printed in the page source and readable by
 *      anyone, signed in or not.
 *   2. Every string goes through a translation function, because the
 *      Nagamese build will need them.
 *   3. The nonce is for our own endpoints only. It is not a permission.
 *
 * @since 1.0.0
 * @return array Configuration for window.kaamase.
 */
function kaamase_script_config() {

	$config = array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'restUrl'  => esc_url_raw( rest_url() ),
		'homeUrl'  => esc_url_raw( home_url( '/' ) ),
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'signedIn' => is_user_logged_in(),
		'strings'  => array(
			'menuOpen'   => __( 'Open menu', 'kaamase' ),
			'menuClose'  => __( 'Close menu', 'kaamase' ),
			'loading'    => __( 'Loading', 'kaamase' ),
			'error'      => __( 'Something went wrong. Please try again.', 'kaamase' ),
			'offline'    => __( 'You are offline. Check your connection.', 'kaamase' ),
			'copied'     => __( 'Copied', 'kaamase' ),
			'confirm'    => __( 'Are you sure?', 'kaamase' ),
			'noResults'  => __( 'Nothing found. Try a different district or trade.', 'kaamase' ),
		),
	);

	/**
	 * Filter the front end script configuration.
	 *
	 * @since 1.0.0
	 * @param array $config Configuration array.
	 */
	return (array) apply_filters( 'kaamase_script_config', $config );
}


/* ==========================================================================
   2. LOGIN SCREEN

   This screen matters more here than on a normal site. For a worker
   registering at a labour point, the login page is the second thing they
   ever see. If it looks like a generic WordPress install, the trust that
   the landing page built disappears immediately.
   ========================================================================== */

if ( ! function_exists( 'kaamase_enqueue_login' ) ) {
	/**
	 * Load login screen styling.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_enqueue_login() {

		$login_css = KAAMASE_DIR . 'assets/css/login.css';

		if ( ! file_exists( $login_css ) ) {
			return;
		}

		wp_enqueue_style(
			'kaamase-login',
			KAAMASE_URI . 'assets/css/login.css',
			array(),
			KAAMASE_ASSET_VERSION
		);
	}
}
add_action( 'login_enqueue_scripts', 'kaamase_enqueue_login' );

/**
 * Point the login logo at the site, not at wordpress.org.
 *
 * @since 1.0.0
 * @return string Home URL.
 */
function kaamase_login_logo_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'kaamase_login_logo_url' );

/**
 * Use the site name as the login logo title.
 *
 * @since 1.0.0
 * @return string
 */
function kaamase_login_logo_text() {
	return get_bloginfo( 'name', 'display' );
}
add_filter( 'login_headertext', 'kaamase_login_logo_text' );

/**
 * Send users to the front end after logging in, not to the dashboard.
 *
 * A worker landing on wp-admin has no idea what they are looking at and
 * will close the tab. Only users who can actually edit the site get the
 * WordPress dashboard.
 *
 * @since 1.0.0
 * @param string           $redirect Default redirect URL.
 * @param string           $request  Requested redirect URL.
 * @param WP_User|WP_Error $user     Logged in user or error.
 * @return string Redirect URL.
 */
function kaamase_login_redirect( $redirect, $request, $user ) {

	if ( ! ( $user instanceof WP_User ) ) {
		return $redirect;
	}

	if ( user_can( $user, 'edit_posts' ) ) {
		return $redirect;
	}

	/**
	 * Filter where a non editing user lands after login.
	 *
	 * The core plugin hooks this to send workers and employers to their
	 * own dashboards.
	 *
	 * @since 1.0.0
	 * @param string  $url  Destination URL.
	 * @param WP_User $user The user logging in.
	 */
	return apply_filters( 'kaamase_login_destination', home_url( '/dashboard/' ), $user );
}
add_filter( 'login_redirect', 'kaamase_login_redirect', 10, 3 );

/**
 * Keep the admin bar away from users who cannot use it.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_hide_admin_bar() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		show_admin_bar( false );
	}
}
add_action( 'after_setup_theme', 'kaamase_hide_admin_bar' );

/**
 * Endpoints inside wp-admin that the front end is meant to post to.
 *
 * These are not screens. They are handlers, and WordPress puts them
 * inside wp-admin only because that is where it has always put them.
 * A worker submitting a form on the public site reaches admin-post.php
 * on purpose, and must not be treated as somebody wandering into the
 * dashboard.
 *
 * @since 1.2.1
 * @return string[] Script file names.
 */
function kaamase_admin_endpoints() {

	/**
	 * Filter the wp-admin endpoints the front end may post to.
	 *
	 * @since 1.2.1
	 * @param string[] $endpoints Script file names.
	 */
	return (array) apply_filters(
		'kaamase_admin_endpoints',
		array( 'admin-post.php', 'admin-ajax.php' )
	);
}

/**
 * Whether this request is a front end form handler rather than a screen.
 *
 * @since 1.2.1
 * @return bool
 */
function kaamase_is_admin_endpoint() {

	$pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';

	/*
	 * $pagenow is set in wp-includes/vars.php long before admin_init, so
	 * it is the reliable answer. The SCRIPT_NAME fallback covers a host
	 * whose rewrite rules leave $pagenow empty, because getting this
	 * wrong silently discards the submission rather than erroring.
	 */
	if ( '' === $pagenow && isset( $_SERVER['SCRIPT_NAME'] ) ) {
		$pagenow = basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) );
	}

	return in_array( $pagenow, kaamase_admin_endpoints(), true );
}

/**
 * Stop non editing users reaching wp-admin at all.
 *
 * A worker who follows a stale bookmark into the dashboard sees an
 * interface built for somebody else. Send them to the front end instead.
 *
 * What this must not catch
 * ------------------------
 * admin-post.php is not a screen, it is the handler every front end form
 * on this platform posts to. WordPress defines WP_ADMIN there and fires
 * admin_init BEFORE it fires admin_post_{action}, so a redirect here ran
 * first and the handler never ran at all. The person landed on their
 * dashboard having pressed a button that reached none of our code, and
 * nothing was written and no error was shown.
 *
 * It looked exactly like a host swallowing requests in front of wp-admin.
 * It was this function. Every form guarded by is_user_logged_in() rather
 * than by a capability was affected, which is all of the ones workers and
 * employers use: job alerts, verification requests, hiring requests,
 * adding the working side, and posting on behalf of somebody else.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_block_admin_access() {

	if ( wp_doing_ajax() || ! is_admin() ) {
		return;
	}

	// Form handlers, not screens. Let them through and let them do their own checks.
	if ( kaamase_is_admin_endpoint() ) {
		return;
	}

	if ( current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_safe_redirect( home_url( '/dashboard/' ) );
	exit;
}
add_action( 'admin_init', 'kaamase_block_admin_access' );


/* ==========================================================================
   3. ADMIN
   ========================================================================== */

if ( ! function_exists( 'kaamase_enqueue_admin' ) ) {
	/**
	 * Load admin styling for theme related screens.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	function kaamase_enqueue_admin( $hook ) {

		unset( $hook );

		$admin_css = KAAMASE_DIR . 'assets/css/admin.css';

		if ( ! file_exists( $admin_css ) ) {
			return;
		}

		wp_enqueue_style(
			'kaamase-admin',
			KAAMASE_URI . 'assets/css/admin.css',
			array(),
			KAAMASE_ASSET_VERSION
		);
	}
}
add_action( 'admin_enqueue_scripts', 'kaamase_enqueue_admin' );


/* ==========================================================================
   4. PRELOAD

   One hint, for the one thing that always blocks rendering.
   ========================================================================== */

/**
 * Preload the main stylesheet.
 *
 * The browser discovers the stylesheet when it parses the head, which is
 * already early. The gain here is small on a fast connection and worth
 * having on a slow one, because it lets the request start before any
 * other head content is processed.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_preload_stylesheet() {
	printf(
		'<link rel="preload" href="%1$s" as="style">' . "\n",
		esc_url( add_query_arg( 'ver', KAAMASE_ASSET_VERSION, get_stylesheet_uri() ) )
	);
}
add_action( 'wp_head', 'kaamase_preload_stylesheet', 1 );
