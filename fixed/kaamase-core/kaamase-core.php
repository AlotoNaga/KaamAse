<?php
/**
 * Plugin Name:       Kaam Ase Core
 * Plugin URI:        https://kaamase.com
 * Description:       The data layer for Kaam Ase. Workers, teams, employers, jobs, trades, districts, roles and registration. This plugin owns everything that must survive a theme change.
 * Version:           1.4.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Nagaland Me
 * Author URI:        https://nagaland.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kaamase-core
 * Domain Path:       /languages
 *
 * @package KaamaseCore
 */

/**
 * Why this is a plugin and not part of the theme.
 *
 * Every worker profile, every job, every rating and every district lives
 * in here. If that lived in the theme, then the day you switch themes,
 * or the day a theme update goes wrong, every record on the platform
 * becomes unreachable. Themes are clothes. This is the body.
 *
 * The theme checks for the Kaamase_Core class to decide whether to
 * render worker cards or fall back to plain pages, so the class name
 * below is load bearing. Do not rename it without changing the theme.
 *
 * Nothing here deletes user data. Deactivation stops the plugin
 * running, it does not remove a single worker profile. Removal is a
 * separate, deliberate act handled in uninstall.php, because a mistaken
 * deactivation must never cost somebody their livelihood listing.
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. ENVIRONMENT GUARD
   ========================================================================== */

define( 'KAAMASE_CORE_MIN_PHP', '8.1' );
define( 'KAAMASE_CORE_MIN_WP', '6.4' );

if (
	version_compare( PHP_VERSION, KAAMASE_CORE_MIN_PHP, '<' )
	|| version_compare( get_bloginfo( 'version' ), KAAMASE_CORE_MIN_WP, '<' )
) {

	/**
	 * Explain why the plugin is not running, then do nothing else.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_core_environment_notice() {
		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong><br>%2$s</p></div>',
			esc_html__( 'Kaam Ase Core is not running.', 'kaamase-core' ),
			sprintf(
				/* translators: 1: required PHP version, 2: current PHP version, 3: required WordPress version, 4: current WordPress version */
				esc_html__( 'This plugin needs PHP %1$s or newer (running %2$s) and WordPress %3$s or newer (running %4$s).', 'kaamase-core' ),
				esc_html( KAAMASE_CORE_MIN_PHP ),
				esc_html( PHP_VERSION ),
				esc_html( KAAMASE_CORE_MIN_WP ),
				esc_html( get_bloginfo( 'version' ) )
			)
		);
	}
	add_action( 'admin_notices', 'kaamase_core_environment_notice' );

	return;
}


/* ==========================================================================
   2. CONSTANTS
   ========================================================================== */

define( 'KAAMASE_CORE_VERSION', '1.4.0' );

/**
 * Schema version.
 *
 * Bumped only when stored data needs migrating, which is a different
 * thing from a code release. Keeping the two separate means a routine
 * update does not trigger a migration, and a migration cannot be missed
 * because somebody forgot to bump the plugin version.
 */
define( 'KAAMASE_CORE_SCHEMA', 3 );

define( 'KAAMASE_CORE_FILE', __FILE__ );
define( 'KAAMASE_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'KAAMASE_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'KAAMASE_CORE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Prefix for every meta key this plugin writes.
 *
 * Matches what the theme reads in kaamase_field(). Changing it orphans
 * every stored value on the site.
 */
define( 'KAAMASE_META_PREFIX', '_kaamase_' );


/* ==========================================================================
   3. THE PLUGIN
   ========================================================================== */

if ( ! class_exists( 'Kaamase_Core' ) ) {

	/**
	 * Main plugin class.
	 *
	 * Deliberately thin. It loads modules and holds nothing else. The
	 * theme tests for this class to decide whether the data layer is
	 * present, so it must exist as early as possible and must never
	 * fatal on a half configured site.
	 *
	 * @since 1.0.0
	 */
	final class Kaamase_Core {

		/**
		 * Single instance.
		 *
		 * @since 1.0.0
		 * @var Kaamase_Core|null
		 */
		private static $instance = null;

		/**
		 * Module files that could not be found.
		 *
		 * @since 1.0.0
		 * @var string[]
		 */
		private $missing = array();

		/**
		 * Get the instance.
		 *
		 * @since 1.0.0
		 * @return Kaamase_Core
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			$this->load();
			$this->hooks();
		}

		/**
		 * Block cloning.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function __clone() {}

		/**
		 * Block unserialising.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function __wakeup() {
			throw new Exception( 'Kaamase_Core cannot be unserialised.' );
		}

		/**
		 * Load module files.
		 *
		 * Two stages, and the reason matters.
		 *
		 * The first list is ORDERED and it has to be. districts supplies
		 * the place data that taxonomies seeds from. taxonomies has to
		 * exist before post-types attaches to it. fields registers meta
		 * against those post types. Get that order wrong and the site
		 * boots into a broken admin with no obvious cause.
		 *
		 * Everything else in includes/ is loaded after, automatically,
		 * in alphabetical order. Those files only register shortcodes and
		 * hooks, so the order between them does not matter.
		 *
		 * This means adding a new feature file is a matter of dropping it
		 * into includes/ and nothing else. This bootstrap does not need
		 * touching again.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function load() {

			// Order matters here. Do not sort this list.
			$ordered = array(
				'includes/districts.php',      // The 17 districts and their towns.
				'includes/taxonomies.php',     // Trades, districts, languages.
				'includes/post-types.php',     // Workers, teams, employers, jobs.
				'includes/fields.php',         // Meta registration and access.
				'includes/roles.php',          // Worker and employer roles.
				'includes/install.php',        // Activation, pages, seeding, migrations.
			);

			$loaded = array();

			foreach ( $ordered as $module ) {

				$path = KAAMASE_CORE_DIR . $module;

				if ( ! file_exists( $path ) ) {
					$this->missing[] = $module;
					continue;
				}

				require_once $path;
				$loaded[ basename( $module ) ] = true;
			}

			/*
			 * Everything else. glob returns nothing on failure rather
			 * than an error, so a missing includes folder degrades to a
			 * plugin that does nothing instead of a fatal.
			 */
			$rest = glob( KAAMASE_CORE_DIR . 'includes/*.php' );

			if ( ! is_array( $rest ) ) {
				return;
			}

			sort( $rest );

			foreach ( $rest as $path ) {

				if ( isset( $loaded[ basename( $path ) ] ) ) {
					continue;
				}

				require_once $path;
			}
		}

		/**
		 * Register hooks owned by the bootstrap itself.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function hooks() {

			add_action( 'init', array( $this, 'load_textdomain' ), 0 );
			add_action( 'admin_notices', array( $this, 'missing_modules_notice' ) );
			add_filter( 'plugin_action_links_' . KAAMASE_CORE_BASENAME, array( $this, 'action_links' ) );
		}

		/**
		 * Load translations.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain(
				'kaamase-core',
				false,
				dirname( KAAMASE_CORE_BASENAME ) . '/languages'
			);
		}

		/**
		 * List module files that have not been uploaded yet.
		 *
		 * The plugin is being built one file at a time, so this is a
		 * build tool as much as a safety net. It tells you exactly what
		 * is outstanding rather than leaving the site half working with
		 * no explanation.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function missing_modules_notice() {

			if ( empty( $this->missing ) || ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-warning"><p><strong>%1$s</strong><br>%2$s</p><ul style="margin:8px 0 0 20px;list-style:disc;">%3$s</ul></div>',
				esc_html__( 'Kaam Ase Core is running with missing files.', 'kaamase-core' ),
				esc_html__( 'These core files have not been uploaded yet. The plugin cannot work properly without them.', 'kaamase-core' ),
				implode(
					'',
					array_map(
						static function ( $file ) {
							return '<li><code>' . esc_html( $file ) . '</code></li>';
						},
						$this->missing
					)
				)
			);
		}

		/**
		 * Add a settings link on the plugins screen.
		 *
		 * @since 1.0.0
		 * @param string[] $links Existing action links.
		 * @return string[] Filtered links.
		 */
		public function action_links( $links ) {

			$settings = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'admin.php?page=kaamase' ) ),
				esc_html__( 'Settings', 'kaamase-core' )
			);

			array_unshift( $links, $settings );

			return $links;
		}

		/**
		 * Whether a module loaded.
		 *
		 * @since 1.0.0
		 * @param string $module Module path relative to the plugin root.
		 * @return bool
		 */
		public function has_module( $module ) {
			return ! in_array( $module, $this->missing, true );
		}
	}
}

/**
 * Access the plugin instance.
 *
 * @since 1.0.0
 * @return Kaamase_Core
 */
function kaamase_core() {
	return Kaamase_Core::instance();
}

/*
 * Start on plugins_loaded rather than immediately.
 *
 * This gives other plugins a chance to register before ours runs, and it
 * means our code is never the reason a site fails to boot.
 */
add_action( 'plugins_loaded', 'kaamase_core', 5 );


/* ==========================================================================
   4. ACTIVATION AND DEACTIVATION

   Activation does almost nothing directly.

   The reason is a trap that catches most WordPress plugins. On the
   request where a plugin is activated, its post types and taxonomies
   have not been registered yet, because init has already passed. Any
   attempt to seed terms or create pages at that moment silently fails or
   creates orphaned records.

   So activation sets a flag, and the real work runs on the next init,
   once everything exists. install.php picks the flag up.
   ========================================================================== */

/**
 * Handle plugin activation.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_core_activate() {

	// Picked up by includes/install.php on the next init.
	update_option( 'kaamase_core_needs_setup', 1, false );

	/*
	 * Permalinks are rebuilt after the post types register on the next
	 * request. Flushing here would write rules that do not yet include
	 * our types, which is the usual cause of a 404 on every worker
	 * profile immediately after activation.
	 */
	update_option( 'kaamase_core_flush_rewrite', 1, false );
}
register_activation_hook( __FILE__, 'kaamase_core_activate' );

/**
 * Handle plugin deactivation.
 *
 * Clears rewrite rules and nothing else. No data is touched. A worker
 * who registered last week still has their profile when the plugin comes
 * back on.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'kaamase_core_deactivate' );
