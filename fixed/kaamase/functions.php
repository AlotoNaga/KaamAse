<?php
/**
 * Kaam Ase theme bootstrap.
 *
 * This file is deliberately thin. It does four things and nothing else:
 *
 *   1. Refuses to run on an environment that cannot support the theme.
 *   2. Defines the constants every other file relies on.
 *   3. Loads the modules in inc/ in a fixed, predictable order.
 *   4. Tells you clearly, in the admin, if a module is missing.
 *
 * Real work belongs in inc/. If you find yourself adding a feature here,
 * it belongs in a module instead. Data structures belong in the
 * kaamase-core plugin, never in the theme, so that switching themes
 * later does not destroy every worker and job record on the site.
 *
 * @package Kaamase
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. COMPATIBILITY GUARD

   A white screen on a live site is worse than a disabled theme. If the
   server cannot run this theme, say so in plain language and stop, rather
   than fatal on some random function three files later.
   ========================================================================== */

define( 'KAAMASE_MIN_PHP', '8.1' );
define( 'KAAMASE_MIN_WP', '6.4' );

/**
 * Check the environment before loading anything else.
 *
 * @since 1.0.0
 * @return bool True when the environment is supported.
 */
function kaamase_environment_ok() {
	static $ok = null;

	if ( null !== $ok ) {
		return $ok;
	}

	$ok = version_compare( PHP_VERSION, KAAMASE_MIN_PHP, '>=' )
		&& version_compare( get_bloginfo( 'version' ), KAAMASE_MIN_WP, '>=' );

	return $ok;
}

if ( ! kaamase_environment_ok() ) {

	/**
	 * Show a clear admin notice and load nothing further.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_environment_notice() {
		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong><br>%2$s</p></div>',
			esc_html__( 'Kaam Ase theme is not running.', 'kaamase' ),
			sprintf(
				/* translators: 1: required PHP version, 2: current PHP version, 3: required WordPress version, 4: current WordPress version */
				esc_html__( 'This theme needs PHP %1$s or newer (running %2$s) and WordPress %3$s or newer (running %4$s). Ask your host to upgrade PHP.', 'kaamase' ),
				esc_html( KAAMASE_MIN_PHP ),
				esc_html( PHP_VERSION ),
				esc_html( KAAMASE_MIN_WP ),
				esc_html( get_bloginfo( 'version' ) )
			)
		);
	}
	add_action( 'admin_notices', 'kaamase_environment_notice' );

	return;
}


/* ==========================================================================
   2. CONSTANTS
   ========================================================================== */

if ( ! function_exists( 'kaamase_theme_version' ) ) {
	/**
	 * The theme version, read from style.css.
	 *
	 * Why this is read rather than typed
	 * ----------------------------------
	 * This number busts the browser cache for the stylesheet and the
	 * script. It used to be a hand written constant, and it drifted:
	 * style.css said 1.5.0 while the constant still said 1.2.0, so every
	 * release after 1.2.0 went out behind an unchanged ?ver= string.
	 *
	 * A returning visitor kept being served the stylesheet they already
	 * had, which on this platform means the people least able to force a
	 * refresh were the ones left looking at a broken page. WordPress
	 * treats style.css as the version of record, so this reads the same
	 * number WordPress does and the two can no longer disagree.
	 *
	 * @since 1.5.1
	 * @return string Version string.
	 */
	function kaamase_theme_version() {

		static $version = null;

		if ( null !== $version ) {
			return $version;
		}

		$version = '';

		/*
		 * get_template() rather than the active stylesheet, so a child
		 * theme reports the Kaam Ase version rather than its own.
		 * WP_Theme caches the parsed header, so this costs one read.
		 */
		if ( function_exists( 'wp_get_theme' ) ) {
			$version = (string) wp_get_theme( get_template() )->get( 'Version' );
		}

		/*
		 * Only reached if the header is unreadable, which means the
		 * theme is already broken. A fixed string is still better than
		 * an empty ?ver=, which caches forever on some proxies.
		 */
		if ( '' === $version ) {
			$version = '1.5.0';
		}

		return $version;
	}
}

/**
 * Theme version.
 *
 * Comes from the Version header in style.css. Change it there and
 * nowhere else.
 */
define( 'KAAMASE_VERSION', kaamase_theme_version() );

define( 'KAAMASE_DIR', trailingslashit( get_template_directory() ) );
define( 'KAAMASE_URI', trailingslashit( get_template_directory_uri() ) );

/**
 * The version string actually used for asset cache busting.
 *
 * During development, file modification time means every save is visible
 * on refresh without editing a version number by hand. In production it
 * falls back to the theme version so the cache stays stable.
 */
if ( ! defined( 'KAAMASE_ASSET_VERSION' ) ) {
	$kaamase_style_path = KAAMASE_DIR . 'style.css';

	define(
		'KAAMASE_ASSET_VERSION',
		( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $kaamase_style_path ) )
			? (string) filemtime( $kaamase_style_path )
			: KAAMASE_VERSION
	);

	unset( $kaamase_style_path );
}

/**
 * Whether the kaamase-core plugin is active.
 *
 * Templates must degrade gracefully without it. The theme should never
 * fatal because the plugin is switched off, and a visitor should never
 * see a broken page. They should see an ordinary WordPress site.
 */
define( 'KAAMASE_HAS_CORE', class_exists( 'Kaamase_Core' ) );


/* ==========================================================================
   3. MODULE LOADER

   Order matters. setup runs first because later modules read the
   supports it registers. Keep this list in dependency order, not
   alphabetical order.
   ========================================================================== */

/**
 * Modules loaded from inc/, in order.
 *
 * @since 1.0.0
 * @var string[]
 */
$kaamase_modules = array(
	'inc/setup.php',          // Theme supports, menus, image sizes, text domain.
	'inc/performance.php',    // Strips WordPress bloat. Built for 2G, not for looks.
	'inc/security.php',       // Hardening. This site will hold personal data of workers.
	'inc/enqueue.php',        // Stylesheet and script loading.
	'inc/nav-walker.php',     // Accessible navigation markup.
	'inc/template-tags.php',  // Card renderers and display helpers.
	'inc/page-display.php',   // Per page control over the title and featured image.
);

/**
 * Modules that were expected but not found on disk.
 *
 * @since 1.0.0
 * @var string[]
 */
$kaamase_missing = array();

foreach ( $kaamase_modules as $kaamase_module ) {
	$kaamase_path = KAAMASE_DIR . $kaamase_module;

	if ( file_exists( $kaamase_path ) ) {
		require_once $kaamase_path;
		continue;
	}

	$kaamase_missing[] = $kaamase_module;
}

unset( $kaamase_module, $kaamase_path );

/**
 * Keep the missing list available to the admin notice below.
 *
 * The theme is being built one file at a time. Until every module is in
 * place, this tells you exactly what is still outstanding instead of
 * letting the site half work with no explanation.
 */
$GLOBALS['kaamase_missing_modules'] = $kaamase_missing;

unset( $kaamase_modules, $kaamase_missing );

if ( ! empty( $GLOBALS['kaamase_missing_modules'] ) ) {

	/**
	 * List any module files that have not been uploaded yet.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_missing_modules_notice() {
		if ( ! current_user_can( 'switch_themes' ) ) {
			return;
		}

		$missing = isset( $GLOBALS['kaamase_missing_modules'] ) ? (array) $GLOBALS['kaamase_missing_modules'] : array();

		if ( empty( $missing ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>%1$s</strong><br>%2$s</p><ul style="margin:8px 0 0 20px;list-style:disc;">%3$s</ul></div>',
			esc_html__( 'Kaam Ase theme is running with missing files.', 'kaamase' ),
			esc_html__( 'These module files have not been uploaded yet. The site will work, but the features in them are not active.', 'kaamase' ),
			implode(
				'',
				array_map(
					static function ( $file ) {
						return '<li><code>' . esc_html( $file ) . '</code></li>';
					},
					$missing
				)
			)
		);
	}
	add_action( 'admin_notices', 'kaamase_missing_modules_notice' );
}


/* ==========================================================================
   4. CORE PLUGIN NOTICE

   The theme renders workers, gangs, jobs and employers. None of those
   exist without the plugin. Say so once, in the admin, and never on the
   front end where a visitor would see it.
   ========================================================================== */

if ( ! KAAMASE_HAS_CORE ) {

	/**
	 * Remind the administrator that the data layer is not installed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_core_plugin_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong><br>%2$s</p></div>',
			esc_html__( 'Kaam Ase Core is not active.', 'kaamase' ),
			esc_html__( 'Workers, gangs, employers and jobs are stored by the Kaam Ase Core plugin. Until it is installed and activated, this theme will display ordinary pages and posts only.', 'kaamase' )
		);
	}
	add_action( 'admin_notices', 'kaamase_core_plugin_notice' );
}


/* ==========================================================================
   5. SMALL HELPERS

   Anything used across more than one template but too small to justify
   its own module. Keep this section short. If it grows past a handful
   of functions, move them into inc/template-tags.php.
   ========================================================================== */

if ( ! function_exists( 'kaamase_asset' ) ) {
	/**
	 * Build a full URL to a file inside the theme.
	 *
	 * @since 1.0.0
	 * @param string $relative_path Path relative to the theme root, with no leading slash.
	 * @return string Absolute URL.
	 */
	function kaamase_asset( $relative_path ) {
		return KAAMASE_URI . ltrim( (string) $relative_path, '/' );
	}
}

if ( ! function_exists( 'kaamase_is_app_screen' ) ) {
	/**
	 * Whether the current screen is part of the logged in app experience.
	 *
	 * App screens get the bottom tab bar and lose the marketing footer.
	 * Public browse and marketing pages keep the full footer.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_is_app_screen() {
		/**
		 * Filter whether the current screen is an app screen.
		 *
		 * The core plugin hooks this to flag dashboards, job posting,
		 * applications and profile editing.
		 *
		 * @since 1.0.0
		 * @param bool $is_app_screen Default is true for any logged in user.
		 */
		return (bool) apply_filters( 'kaamase_is_app_screen', is_user_logged_in() );
	}
}

if ( ! function_exists( 'kaamase_svg' ) ) {
	/**
	 * Print an inline SVG icon from the theme icon set.
	 *
	 * Icons are inlined rather than loaded as files. On a slow connection
	 * twenty icon requests cost more than the bytes they save, and an icon
	 * that has not loaded yet leaves a user staring at an empty button.
	 *
	 * @since 1.0.0
	 * @param string $name  Icon file name without the extension.
	 * @param array  $args  Optional. size (int), class (string), label (string).
	 * @return string Sanitised SVG markup, or an empty string when not found.
	 */
	function kaamase_svg( $name, $args = array() ) {
		$name = preg_replace( '/[^a-z0-9\-]/', '', strtolower( (string) $name ) );

		if ( '' === $name ) {
			return '';
		}

		$file = KAAMASE_DIR . 'assets/icons/' . $name . '.svg';

		if ( ! file_exists( $file ) ) {
			return '';
		}

		$defaults = array(
			'size'  => 24,
			'class' => '',
			'label' => '',
		);

		$args = wp_parse_args( $args, $defaults );
		$svg  = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $svg ) {
			return '';
		}

		$attributes = sprintf(
			' width="%1$d" height="%2$d" class="ka-icon %3$s" %4$s',
			absint( $args['size'] ),
			absint( $args['size'] ),
			esc_attr( $args['class'] ),
			$args['label']
				? 'role="img" aria-label="' . esc_attr( $args['label'] ) . '"'
				: 'aria-hidden="true" focusable="false"'
		);

		$svg = preg_replace( '/^<svg/', '<svg' . $attributes, trim( $svg ), 1 );

		return wp_kses( $svg, kaamase_svg_allowed_tags() );
	}
}

if ( ! function_exists( 'kaamase_svg_allowed_tags' ) ) {
	/**
	 * Allowed SVG tags and attributes for wp_kses.
	 *
	 * Icons come from our own theme folder, but they are still run through
	 * kses. If someone gains write access to the icons folder, they should
	 * not also gain a script injection point.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	function kaamase_svg_allowed_tags() {
		$shared = array(
			'fill'             => true,
			'stroke'           => true,
			'stroke-width'     => true,
			'stroke-linecap'   => true,
			'stroke-linejoin'  => true,
			'opacity'          => true,
			'transform'        => true,
			'class'            => true,
		);

		return array(
			'svg'      => array_merge(
				$shared,
				array(
					'xmlns'        => true,
					'viewbox'      => true,
					'width'        => true,
					'height'       => true,
					'role'         => true,
					'aria-label'   => true,
					'aria-hidden'  => true,
					'focusable'    => true,
				)
			),
			'g'        => $shared,
			'title'    => array(),
			'path'     => array_merge( $shared, array( 'd' => true, 'fill-rule' => true, 'clip-rule' => true ) ),
			'circle'   => array_merge( $shared, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
			'ellipse'  => array_merge( $shared, array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true ) ),
			'rect'     => array_merge( $shared, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true ) ),
			'line'     => array_merge( $shared, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ),
			'polyline' => array_merge( $shared, array( 'points' => true ) ),
			'polygon'  => array_merge( $shared, array( 'points' => true ) ),
		);
	}
}