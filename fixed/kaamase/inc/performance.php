<?php
/**
 * Performance.
 *
 * WordPress ships a lot of markup, scripts and styles that exist for
 * blogs, for feed readers, for editors and for backwards compatibility
 * with software nobody in Nagaland is running. On a fast office
 * connection none of it is noticeable. On a 2G connection in Longleng
 * it is the difference between a page that loads and a page that gets
 * abandoned halfway.
 *
 * Everything removed here is removed for a stated reason. Nothing is
 * removed because a blog post said to. If a feature is ever needed, the
 * relevant block below can be deleted and it returns.
 *
 * Rule for this file: never break the admin. Every removal is either
 * front end only or explicitly guarded, because an editor losing the
 * block editor to save 12KB on the public site is a bad trade.
 *
 * @package Kaamase
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. HEAD CLEANUP

   Each of these prints a tag into every single page. Individually small,
   collectively a few hundred bytes before a single useful byte is sent,
   and several of them advertise information that is nobody's business.
   ========================================================================== */

/**
 * Remove head output this site has no use for.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_clean_head() {

	// Really Simple Discovery. For remote publishing clients from 2006.
	remove_action( 'wp_head', 'rsd_link' );

	// Windows Live Writer manifest. That software was discontinued.
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// Announces the exact WordPress version to anyone scanning for
	// vulnerable installs. No reason to hand that over.
	remove_action( 'wp_head', 'wp_generator' );
	add_filter( 'the_generator', '__return_empty_string' );

	// Previous and next post links. Meaningless on a hiring platform.
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	remove_action( 'wp_head', 'start_post_rel_link' );
	remove_action( 'wp_head', 'parent_post_rel_link' );

	// Shortlink. Nobody is sharing a ?p=41 link.
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

	// Feed links. This is not a blog and nobody subscribes by RSS.
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );

	// oEmbed discovery. Two tags per page so other sites can embed us.
	// The REST endpoints stay alive, only the advertisement goes.
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );

	// REST API link tag. The API itself is untouched and still works.
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
}
add_action( 'init', 'kaamase_clean_head' );

/**
 * Drop the DNS prefetch hint for s.w.org.
 *
 * It resolves a domain used for emoji assets we are about to remove
 * anyway, so it is a round trip spent on nothing.
 *
 * @since 1.0.0
 * @param array  $hints         Resource hint URLs.
 * @param string $relation_type The hint type.
 * @return array Filtered hints.
 */
function kaamase_resource_hints( $hints, $relation_type ) {
	if ( 'dns-prefetch' !== $relation_type ) {
		return $hints;
	}

	return array_filter(
		$hints,
		static function ( $hint ) {
			$url = is_array( $hint ) && isset( $hint['href'] ) ? $hint['href'] : $hint;

			return ! is_string( $url ) || false === strpos( $url, 's.w.org' );
		}
	);
}
add_filter( 'wp_resource_hints', 'kaamase_resource_hints', 10, 2 );


/* ==========================================================================
   2. EMOJI

   Roughly 15KB of JavaScript, an inline style block and an inline script
   on every page, to convert emoji characters into images on browsers that
   have supported emoji natively for a decade.
   ========================================================================== */

/**
 * Disable the emoji detection script and its styles.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	add_filter( 'emoji_svg_url', '__return_false' );

	// Stop TinyMCE loading the emoji plugin as well.
	add_filter(
		'tiny_mce_plugins',
		static function ( $plugins ) {
			return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
		}
	);
}
add_action( 'init', 'kaamase_disable_emoji' );


/* ==========================================================================
   3. SCRIPTS AND STYLES ON THE FRONT END
   ========================================================================== */

/**
 * Remove core assets this theme does not use.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_trim_core_assets() {

	if ( is_admin() ) {
		return;
	}

	/*
	 * jQuery Migrate. A compatibility shim for jQuery code written before
	 * 2016. Nothing in this theme uses jQuery at all. If a plugin later
	 * needs it, this line is the first thing to remove while debugging.
	 */
	add_filter(
		'wp_default_scripts',
		static function ( $scripts ) {
			if ( ! empty( $scripts->registered['jquery'] ) ) {
				$scripts->registered['jquery']->deps = array_diff(
					$scripts->registered['jquery']->deps,
					array( 'jquery-migrate' )
				);
			}
		}
	);

	// The wp-embed script only exists so other sites can embed ours.
	wp_deregister_script( 'wp-embed' );

	/*
	 * Global styles. WordPress prints an inline block of CSS custom
	 * properties generated from theme.json, plus a hidden SVG filter
	 * element, on every page. This theme uses its own design tokens in
	 * style.css and needs neither.
	 */
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
	remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );

	// Legacy classic theme styles. Superseded by the design system.
	wp_dequeue_style( 'classic-theme-styles' );

	/*
	 * Block library CSS is around 30KB and is only needed on pages that
	 * actually contain blocks. Marketing pages will. A worker search
	 * result page will not.
	 */
	if ( is_singular() && ! has_blocks( get_queried_object_id() ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
	} elseif ( ! is_singular() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
	}

	// Dashicons is an admin icon font. Visitors never need it.
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}

	// Comment reply script only where comments are actually open.
	if ( ! is_singular() || ! comments_open() ) {
		wp_dequeue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'kaamase_trim_core_assets', 100 );


/* ==========================================================================
   4. SPECULATIVE LOADING

   WordPress 6.8 added speculation rules, which prefetch or prerender
   pages the browser guesses a visitor will open next.

   On a fast connection that feels instant. On a metered 2G connection it
   spends a user's data on pages they never open, and the user is paying
   for that data out of a daily wage. Off.
   ========================================================================== */

/**
 * Disable speculative prefetch and prerender.
 *
 * @since 1.0.0
 * @return null Null disables the feature entirely.
 */
function kaamase_disable_speculative_loading() {
	return null;
}
add_filter( 'wp_speculation_rules_configuration', 'kaamase_disable_speculative_loading' );


/* ==========================================================================
   5. IMAGE LOADING PRIORITY
   ========================================================================== */

/**
 * Load the first two images eagerly instead of lazily.
 *
 * WordPress lazy loads everything after the first image. On a slow
 * connection that leaves the top of a worker card list looking like a
 * row of empty grey boxes for several seconds. Two eager images means
 * the screen has something real on it almost immediately, which is what
 * stops a user deciding the site is broken and closing it.
 *
 * @since 1.0.0
 * @return int Number of images to load eagerly.
 */
function kaamase_omit_lazy_threshold() {
	return 2;
}
add_filter( 'wp_omit_loading_attr_threshold', 'kaamase_omit_lazy_threshold' );


/* ==========================================================================
   6. HEARTBEAT

   The Heartbeat API polls the server every 15 to 60 seconds while a
   screen is open. On the front end it does nothing useful for us at all,
   and in the admin it is a steady drip of requests and battery on a
   phone. Slowed, not killed, because the block editor uses it for
   autosave and lock detection.
   ========================================================================== */

/**
 * Stop Heartbeat on the front end entirely.
 *
 * Hooked to wp_enqueue_scripts rather than to init.
 *
 * wp_deregister_script() instantiates wp_scripts(), and instantiating
 * it fires wp_default_scripts, which registers every script WordPress
 * ships. On init that happened on every single request to the site,
 * including REST calls, cron and admin-ajax, none of which were ever
 * going to print a script tag. A small cost, but paid constantly, and
 * an odd one to be paying from inside the performance file.
 *
 * wp_enqueue_scripts only fires when a front end page is actually being
 * rendered, which is the only time this matters.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_heartbeat_frontend() {
	wp_deregister_script( 'heartbeat' );
}
add_action( 'wp_enqueue_scripts', 'kaamase_heartbeat_frontend', 1 );

/**
 * Slow Heartbeat down in the admin.
 *
 * @since 1.0.0
 * @param array $settings Heartbeat settings.
 * @return array Filtered settings.
 */
function kaamase_heartbeat_interval( $settings ) {
	$settings['interval'] = 120;

	return $settings;
}
add_filter( 'heartbeat_settings', 'kaamase_heartbeat_interval' );


/* ==========================================================================
   7. DATABASE WEIGHT

   Cheap hosting means a small database and slow queries. These keep it
   from filling with content nobody will ever read.
   ========================================================================== */

/**
 * Stop the site pinging itself when one page links to another.
 *
 * Every internal link would otherwise create a pingback comment against
 * our own content, which is noise in the comments table and an HTTP
 * request on every save.
 *
 * @since 1.0.0
 * @param array $links Links found in the post.
 * @return void
 */
function kaamase_no_self_pingback( &$links ) {
	$home = home_url();

	foreach ( $links as $key => $link ) {
		if ( 0 === strpos( $link, $home ) ) {
			unset( $links[ $key ] );
		}
	}
}
add_action( 'pre_ping', 'kaamase_no_self_pingback' );

/**
 * Cap stored revisions.
 *
 * A job post edited fifteen times stores fifteen full copies. Five is
 * enough to recover from a mistake and keeps the posts table small.
 * Set to false in wp-config to override.
 *
 * @since 1.0.0
 * @param int     $num  Default revision count.
 * @param WP_Post $post The post being saved.
 * @return int Revision count.
 */
function kaamase_revision_limit( $num, $post ) {
	unset( $post );

	return 5;
}
add_filter( 'wp_revisions_to_keep', 'kaamase_revision_limit', 10, 2 );


/* ==========================================================================
   8. DEFERRED SCRIPT LOADING

   Theme scripts declare their own loading strategy in inc/enqueue.php.
   This block only handles core and plugin handles that block rendering
   for no good reason.

   Anything listed here must be safe to run after the document parses.
   Do not add a handle to this list without checking. A deferred script
   that another script depends on inline will break.
   ========================================================================== */

/**
 * Add defer to known safe script handles.
 *
 * @since 1.0.0
 * @param string $tag    The script tag.
 * @param string $handle Script handle.
 * @return string Filtered tag.
 */
function kaamase_defer_scripts( $tag, $handle ) {

	if ( is_admin() ) {
		return $tag;
	}

	/**
	 * Filter which script handles are deferred.
	 *
	 * @since 1.0.0
	 * @param string[] $handles Script handles to defer.
	 */
	$deferred = (array) apply_filters(
		'kaamase_deferred_scripts',
		array(
			'wp-polyfill',
		)
	);

	if ( ! in_array( $handle, $deferred, true ) ) {
		return $tag;
	}

	if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
		return $tag;
	}

	return str_replace( ' src=', ' defer src=', $tag );
}
add_filter( 'script_loader_tag', 'kaamase_defer_scripts', 10, 2 );
