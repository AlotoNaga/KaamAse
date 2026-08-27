<?php
/**
 * Taxonomies.
 *
 * Three: trades, districts and languages. All three are closed lists.
 *
 * The capability settings below are the important part and they are easy
 * to miss. Workers and employers can ASSIGN terms. They cannot CREATE
 * them. Without that, the first week of real use produces Mason, mason,
 * Masson, Mason work, Masonry and Mistri as six separate trades, none of
 * which find each other, and no amount of search tuning afterwards
 * repairs it. The fix has to be in place before the first registration,
 * not after somebody notices.
 *
 * Trade archives are also the entire organic search strategy. A page at
 * /trade/mason/ filtered to Dimapur is what somebody typing "mason in
 * Dimapur" into Google needs to land on. That page exists because these
 * taxonomies are public with archives switched on, so leave them that
 * way even though nothing else on the site links to them yet.
 *
 * @package KaamaseCore
 * @version 1.0.1
 * @since   1.0.0
 *
 * Changelog
 *   1.0.1  assign_terms was edit_posts, which would have granted every
 *          worker access to wp-admin, since that is the capability the
 *          theme tests to decide who may reach the dashboard. Replaced
 *          with the dedicated kaamase_assign_terms capability.
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHAT THEY ATTACH TO
   ========================================================================== */

if ( ! function_exists( 'kaamase_taxonomy_objects' ) ) {
	/**
	 * Post types that carry Kaam Ase taxonomies.
	 *
	 * @since 1.0.0
	 * @param string $taxonomy Which taxonomy is being registered.
	 * @return string[] Post type names.
	 */
	function kaamase_taxonomy_objects( $taxonomy ) {

		$objects = array( 'kaamase_worker', 'kaamase_gang', 'kaamase_job' );

		// Employers have a district but no trade and no spoken language.
		if ( 'kaamase_district' === $taxonomy ) {
			$objects[] = 'kaamase_employer';
		}

		if ( 'kaamase_language' === $taxonomy ) {
			$objects = array( 'kaamase_worker', 'kaamase_gang' );
		}

		/**
		 * Filter which post types a taxonomy attaches to.
		 *
		 * @since 1.0.0
		 * @param string[] $objects  Post type names.
		 * @param string   $taxonomy Taxonomy name.
		 */
		return (array) apply_filters( 'kaamase_taxonomy_objects', $objects, $taxonomy );
	}
}

if ( ! function_exists( 'kaamase_term_capabilities' ) ) {
	/**
	 * Capability map for every Kaam Ase taxonomy.
	 *
	 * Managing, editing and deleting terms is restricted to site admins.
	 *
	 * Assigning uses a dedicated capability rather than edit_posts. That
	 * distinction matters: the theme uses edit_posts to decide who may
	 * reach wp-admin, so granting it to workers just so they can pick a
	 * trade would hand every worker on the platform the WordPress
	 * dashboard. roles.php grants kaamase_assign_terms instead.
	 *
	 * @since 1.0.0
	 * @return string[] Capability map.
	 */
	function kaamase_term_capabilities() {
		return array(
			'manage_terms' => 'manage_options',
			'edit_terms'   => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'kaamase_assign_terms',
		);
	}
}


/* ==========================================================================
   2. REGISTRATION
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_taxonomies' ) ) {
	/**
	 * Register trades, districts and languages.
	 *
	 * Runs before post types register, which is the order WordPress
	 * prefers. Registering a post type that names a taxonomy which does
	 * not exist yet produces a working site with a broken admin screen,
	 * and the cause is not obvious when you go looking.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_register_taxonomies() {

		/* --------------------------------------------------------------
		 * Trade
		 *
		 * Hierarchical, so Construction can hold Mason, Carpenter and
		 * the rest. Somebody browsing wants the category. Somebody
		 * searching wants the trade. Both need to work.
		 * ------------------------------------------------------------ */
		register_taxonomy(
			'kaamase_trade',
			kaamase_taxonomy_objects( 'kaamase_trade' ),
			array(
				'labels'             => array(
					'name'              => _x( 'Trades', 'taxonomy general name', 'kaamase-core' ),
					'singular_name'     => _x( 'Trade', 'taxonomy singular name', 'kaamase-core' ),
					'search_items'      => __( 'Search trades', 'kaamase-core' ),
					'all_items'         => __( 'All trades', 'kaamase-core' ),
					'parent_item'       => __( 'Trade category', 'kaamase-core' ),
					'parent_item_colon' => __( 'Trade category:', 'kaamase-core' ),
					'edit_item'         => __( 'Edit trade', 'kaamase-core' ),
					'update_item'       => __( 'Update trade', 'kaamase-core' ),
					'add_new_item'      => __( 'Add trade', 'kaamase-core' ),
					'new_item_name'     => __( 'New trade name', 'kaamase-core' ),
					'menu_name'         => __( 'Trades', 'kaamase-core' ),
					'not_found'         => __( 'No trades found.', 'kaamase-core' ),
					'back_to_items'     => __( 'Back to trades', 'kaamase-core' ),
				),
				'hierarchical'       => true,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'show_tagcloud'      => false,
				'query_var'          => 'kaamase_trade',
				'capabilities'       => kaamase_term_capabilities(),
				'rewrite'            => array(
					'slug'         => 'trade',
					'with_front'   => false,
					'hierarchical' => false,
				),
			)
		);

		/* --------------------------------------------------------------
		 * District
		 *
		 * Flat. The 17 districts are peers, there is no hierarchy
		 * between them. Towns are stored as a field rather than as
		 * child terms, because a town archive would be seventeen mostly
		 * empty pages and a maintenance job forever.
		 * ------------------------------------------------------------ */
		register_taxonomy(
			'kaamase_district',
			kaamase_taxonomy_objects( 'kaamase_district' ),
			array(
				'labels'             => array(
					'name'          => _x( 'Districts', 'taxonomy general name', 'kaamase-core' ),
					'singular_name' => _x( 'District', 'taxonomy singular name', 'kaamase-core' ),
					'search_items'  => __( 'Search districts', 'kaamase-core' ),
					'all_items'     => __( 'All districts', 'kaamase-core' ),
					'edit_item'     => __( 'Edit district', 'kaamase-core' ),
					'update_item'   => __( 'Update district', 'kaamase-core' ),
					'add_new_item'  => __( 'Add district', 'kaamase-core' ),
					'new_item_name' => __( 'New district name', 'kaamase-core' ),
					'menu_name'     => __( 'Districts', 'kaamase-core' ),
					'not_found'     => __( 'No districts found.', 'kaamase-core' ),
				),
				'hierarchical'       => false,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'show_admin_column'  => true,
				'show_tagcloud'      => false,
				'query_var'          => 'kaamase_district',
				'capabilities'       => kaamase_term_capabilities(),
				'rewrite'            => array(
					'slug'       => 'district',
					'with_front' => false,
				),
			)
		);

		/* --------------------------------------------------------------
		 * Language
		 *
		 * Practical, not cultural. The question this answers is whether
		 * an employer and a worker can actually talk to each other on a
		 * site. A Konyak speaking helper and a Sumi speaking contractor
		 * both needing Nagamese is a real matching problem here, and no
		 * national hiring app models it at all.
		 *
		 * Not shown in admin columns, and never used for filtering by
		 * anyone other than the worker themselves. It describes what
		 * somebody speaks. It must not become a way to screen people.
		 * ------------------------------------------------------------ */
		register_taxonomy(
			'kaamase_language',
			kaamase_taxonomy_objects( 'kaamase_language' ),
			array(
				'labels'             => array(
					'name'          => _x( 'Languages', 'taxonomy general name', 'kaamase-core' ),
					'singular_name' => _x( 'Language', 'taxonomy singular name', 'kaamase-core' ),
					'search_items'  => __( 'Search languages', 'kaamase-core' ),
					'all_items'     => __( 'All languages', 'kaamase-core' ),
					'edit_item'     => __( 'Edit language', 'kaamase-core' ),
					'update_item'   => __( 'Update language', 'kaamase-core' ),
					'add_new_item'  => __( 'Add language', 'kaamase-core' ),
					'new_item_name' => __( 'New language name', 'kaamase-core' ),
					'menu_name'     => __( 'Languages', 'kaamase-core' ),
					'not_found'     => __( 'No languages found.', 'kaamase-core' ),
				),
				'hierarchical'       => false,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => true,
				'show_admin_column'  => false,
				'show_tagcloud'      => false,
				'query_var'          => false,
				'capabilities'       => kaamase_term_capabilities(),
				'rewrite'            => false,
			)
		);
	}
}
add_action( 'init', 'kaamase_register_taxonomies', 5 );


/* ==========================================================================
   3. SEED DATA

   Called from install.php on activation and after an update. Every
   seeding function below adds what is missing and touches nothing that
   already exists. None of them ever delete a term, because a term that
   somebody's profile is attached to is not ours to remove.
   ========================================================================== */

if ( ! function_exists( 'kaamase_trade_seed' ) ) {
	/**
	 * The starting trade list.
	 *
	 * Taken from the original plan. Two additions worth noting.
	 *
	 * Mistri appears as an alias in the description for Mason, because
	 * that is the word actually used on a site in Dimapur and somebody
	 * will search it.
	 *
	 * Helper and Labour are kept as separate trades rather than merged.
	 * They are different jobs at different rates and merging them would
	 * mean a contractor searching for one gets both.
	 *
	 * @since 1.0.0
	 * @return array[] Categories, each with a name and a list of trades.
	 */
	function kaamase_trade_seed() {

		$seed = array(
			'construction' => array(
				'name'   => __( 'Construction', 'kaamase-core' ),
				'trades' => array(
					'mason'        => __( 'Mason', 'kaamase-core' ),
					'carpenter'    => __( 'Carpenter', 'kaamase-core' ),
					'electrician'  => __( 'Electrician', 'kaamase-core' ),
					'plumber'      => __( 'Plumber', 'kaamase-core' ),
					'painter'      => __( 'Painter', 'kaamase-core' ),
					'welder'       => __( 'Welder', 'kaamase-core' ),
					'tile-worker'  => __( 'Tile worker', 'kaamase-core' ),
					'steel-fixer'  => __( 'Steel fixer', 'kaamase-core' ),
					'helper'       => __( 'Helper', 'kaamase-core' ),
					'labour'       => __( 'Labour', 'kaamase-core' ),
				),
			),

			'home-services' => array(
				'name'   => __( 'Home services', 'kaamase-core' ),
				'trades' => array(
					'maid'          => __( 'Maid', 'kaamase-core' ),
					'house-cleaner' => __( 'House cleaner', 'kaamase-core' ),
					'cook'          => __( 'Cook', 'kaamase-core' ),
					'babysitter'    => __( 'Babysitter', 'kaamase-core' ),
					'caregiver'     => __( 'Caregiver', 'kaamase-core' ),
					'driver'        => __( 'Driver', 'kaamase-core' ),
					'gardener'      => __( 'Gardener', 'kaamase-core' ),
				),
			),

			'repair' => array(
				'name'   => __( 'Repair and technical', 'kaamase-core' ),
				'trades' => array(
					'ac-repair'           => __( 'AC repair', 'kaamase-core' ),
					'fridge-repair'       => __( 'Refrigerator repair', 'kaamase-core' ),
					'tv-repair'           => __( 'TV repair', 'kaamase-core' ),
					'mobile-repair'       => __( 'Mobile repair', 'kaamase-core' ),
					'computer-technician' => __( 'Computer technician', 'kaamase-core' ),
				),
			),

			'agriculture' => array(
				'name'   => __( 'Agriculture', 'kaamase-core' ),
				'trades' => array(
					'farm-labour'    => __( 'Farm labour', 'kaamase-core' ),
					'tractor-driver' => __( 'Tractor driver', 'kaamase-core' ),
					'harvest-worker' => __( 'Harvest worker', 'kaamase-core' ),
				),
			),

			'other' => array(
				'name'   => __( 'Other work', 'kaamase-core' ),
				'trades' => array(
					'security-guard' => __( 'Security guard', 'kaamase-core' ),
					'delivery-rider' => __( 'Delivery rider', 'kaamase-core' ),
					'tutor'          => __( 'Tutor', 'kaamase-core' ),
					'photographer'   => __( 'Photographer', 'kaamase-core' ),
					'tailor'         => __( 'Tailor', 'kaamase-core' ),
					'beautician'     => __( 'Beautician', 'kaamase-core' ),
				),
			),
		);

		/**
		 * Filter the trade seed list.
		 *
		 * @since 1.0.0
		 * @param array[] $seed Trade categories and their trades.
		 */
		return (array) apply_filters( 'kaamase_trade_seed', $seed );
	}
}

if ( ! function_exists( 'kaamase_seed_trades' ) ) {
	/**
	 * Create any missing trade terms.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_seed_trades() {

		if ( ! taxonomy_exists( 'kaamase_trade' ) ) {
			return;
		}

		foreach ( kaamase_trade_seed() as $cat_slug => $category ) {

			$parent = term_exists( $cat_slug, 'kaamase_trade' );

			if ( ! $parent ) {
				$parent = wp_insert_term(
					$category['name'],
					'kaamase_trade',
					array( 'slug' => $cat_slug )
				);
			}

			if ( is_wp_error( $parent ) ) {
				continue;
			}

			$parent_id = is_array( $parent ) ? (int) $parent['term_id'] : (int) $parent;

			foreach ( (array) $category['trades'] as $slug => $name ) {

				if ( term_exists( $slug, 'kaamase_trade' ) ) {
					continue;
				}

				wp_insert_term(
					$name,
					'kaamase_trade',
					array(
						'slug'   => $slug,
						'parent' => $parent_id,
					)
				);
			}
		}
	}
}

if ( ! function_exists( 'kaamase_seed_districts' ) ) {
	/**
	 * Create a term for every district.
	 *
	 * Term slugs match the district slugs in districts.php exactly, so
	 * the two never drift apart. Alternate spellings go into the term
	 * description, which makes them searchable in the admin without
	 * creating duplicate terms.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_seed_districts() {

		if ( ! taxonomy_exists( 'kaamase_district' ) || ! function_exists( 'kaamase_districts' ) ) {
			return;
		}

		foreach ( kaamase_districts() as $slug => $district ) {

			if ( term_exists( $slug, 'kaamase_district' ) ) {
				continue;
			}

			$aliases = ! empty( $district['aliases'] )
				? implode( ', ', (array) $district['aliases'] )
				: '';

			wp_insert_term(
				$district['name'],
				'kaamase_district',
				array(
					'slug'        => $slug,
					'description' => $aliases
						? sprintf(
							/* translators: %s: comma separated list of alternate spellings */
							__( 'Also written: %s', 'kaamase-core' ),
							$aliases
						)
						: '',
				)
			);
		}
	}
}

if ( ! function_exists( 'kaamase_language_seed' ) ) {
	/**
	 * The starting language list.
	 *
	 * Nagamese sits first deliberately. It is the language two people
	 * from different tribes actually use to agree a day rate, and it is
	 * the one that matters most for matching.
	 *
	 * The list covers the major Naga languages plus the languages spoken
	 * by workers who came from outside the state. Review it. This is one
	 * part of the platform where getting a name wrong is not a technical
	 * problem, and you are far better placed than I am to check it.
	 *
	 * @since 1.0.0
	 * @return string[] Language names keyed by slug.
	 */
	function kaamase_language_seed() {

		$seed = array(
			'nagamese'    => __( 'Nagamese', 'kaamase-core' ),
			'english'     => __( 'English', 'kaamase-core' ),
			'hindi'       => __( 'Hindi', 'kaamase-core' ),
			'ao'          => __( 'Ao', 'kaamase-core' ),
			'angami'      => __( 'Angami', 'kaamase-core' ),
			'sumi'        => __( 'Sumi', 'kaamase-core' ),
			'lotha'       => __( 'Lotha', 'kaamase-core' ),
			'konyak'      => __( 'Konyak', 'kaamase-core' ),
			'chakhesang'  => __( 'Chakhesang', 'kaamase-core' ),
			'zeliang'     => __( 'Zeliang', 'kaamase-core' ),
			'rengma'      => __( 'Rengma', 'kaamase-core' ),
			'phom'        => __( 'Phom', 'kaamase-core' ),
			'chang'       => __( 'Chang', 'kaamase-core' ),
			'sangtam'     => __( 'Sangtam', 'kaamase-core' ),
			'yimkhiung'   => __( 'Yimkhiung', 'kaamase-core' ),
			'khiamniungan' => __( 'Khiamniungan', 'kaamase-core' ),
			'pochury'     => __( 'Pochury', 'kaamase-core' ),
			'kuki'        => __( 'Kuki', 'kaamase-core' ),
			'assamese'    => __( 'Assamese', 'kaamase-core' ),
			'bengali'     => __( 'Bengali', 'kaamase-core' ),
			'nepali'      => __( 'Nepali', 'kaamase-core' ),
		);

		/**
		 * Filter the language seed list.
		 *
		 * @since 1.0.0
		 * @param string[] $seed Language names keyed by slug.
		 */
		return (array) apply_filters( 'kaamase_language_seed', $seed );
	}
}

if ( ! function_exists( 'kaamase_seed_languages' ) ) {
	/**
	 * Create any missing language terms.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_seed_languages() {

		if ( ! taxonomy_exists( 'kaamase_language' ) ) {
			return;
		}

		foreach ( kaamase_language_seed() as $slug => $name ) {

			if ( term_exists( $slug, 'kaamase_language' ) ) {
				continue;
			}

			wp_insert_term( $name, 'kaamase_language', array( 'slug' => $slug ) );
		}
	}
}

if ( ! function_exists( 'kaamase_seed_all_terms' ) ) {
	/**
	 * Run every seeding routine.
	 *
	 * Safe to call repeatedly. install.php calls it on activation and
	 * after a schema change.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_seed_all_terms() {
		kaamase_seed_trades();
		kaamase_seed_districts();
		kaamase_seed_languages();
	}
}


/* ==========================================================================
   4. HELPERS
   ========================================================================== */

if ( ! function_exists( 'kaamase_trade_choices' ) ) {
	/**
	 * Trades grouped by category, ready for a select field.
	 *
	 * @since 1.0.0
	 * @return array[] Trade names keyed by slug, grouped by category name.
	 */
	function kaamase_trade_choices() {

		if ( ! taxonomy_exists( 'kaamase_trade' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'kaamase_trade',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$parents = array();
		$grouped = array();

		foreach ( $terms as $term ) {
			if ( 0 === (int) $term->parent ) {
				$parents[ $term->term_id ] = $term->name;
			}
		}

		foreach ( $terms as $term ) {

			if ( 0 === (int) $term->parent ) {
				continue;
			}

			$group = isset( $parents[ $term->parent ] )
				? $parents[ $term->parent ]
				: __( 'Other work', 'kaamase-core' );

			$grouped[ $group ][ $term->slug ] = $term->name;
		}

		return $grouped;
	}
}

if ( ! function_exists( 'kaamase_match_trade' ) ) {
	/**
	 * Resolve however somebody wrote a trade into its slug.
	 *
	 * Same problem as districts and the same fix. Somebody typing
	 * "electric" or "mistri" should reach the right trade rather than a
	 * dead end.
	 *
	 * @since 1.0.0
	 * @param string $value Anything a human typed.
	 * @return string Trade slug, or an empty string when no match.
	 */
	function kaamase_match_trade( $value ) {

		$value = trim( (string) $value );

		if ( '' === $value || ! taxonomy_exists( 'kaamase_trade' ) ) {
			return '';
		}

		// Exact slug first.
		$term = get_term_by( 'slug', sanitize_title( $value ), 'kaamase_trade' );

		if ( $term instanceof WP_Term ) {
			return $term->slug;
		}

		// Exact name.
		$term = get_term_by( 'name', $value, 'kaamase_trade' );

		if ( $term instanceof WP_Term ) {
			return $term->slug;
		}

		// Loose match against every trade name.
		$needle = function_exists( 'kaamase_flatten_place' )
			? kaamase_flatten_place( $value )
			: strtolower( preg_replace( '/[^a-z0-9]+/i', '', $value ) );

		if ( '' === $needle ) {
			return '';
		}

		// The words people actually use for a trade, before any guessing.
		foreach ( kaamase_trade_aliases() as $slug => $aliases ) {
			foreach ( (array) $aliases as $alias ) {
				if ( kaamase_flatten_place( $alias ) === $needle ) {
					return $slug;
				}
			}
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'kaamase_trade',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return '';
		}

		$flatten = static function ( $value ) {
			return function_exists( 'kaamase_flatten_place' )
				? kaamase_flatten_place( $value )
				: strtolower( preg_replace( '/[^a-z0-9]+/i', '', $value ) );
		};

		/*
		 * Every exact match is tried before any partial one.
		 *
		 * This used to do both in a single pass and return the first
		 * hit, so which trade you got depended on the order get_terms
		 * happened to return them in. Somebody typing driver landed on
		 * Driver rather than Tractor driver by alphabetical luck, not
		 * by design, and a renamed trade could silently flip it.
		 */
		foreach ( $terms as $candidate ) {
			if ( $flatten( $candidate->name ) === $needle ) {
				return $candidate->slug;
			}
		}

		foreach ( $terms as $candidate ) {

			$name = $flatten( $candidate->name );

			if ( strlen( $needle ) > 3 && false !== strpos( $name, $needle ) ) {
				return $candidate->slug;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_trade_aliases' ) ) {
	/**
	 * What people call a trade when they are not reading a dropdown.
	 *
	 * The seed list has always said that Mistri appears as an alias for
	 * Mason, because that is the word used on a site in Dimapur and
	 * somebody will search it. No description was ever written onto the
	 * terms and nothing ever read one, so typing mistri found nothing at
	 * all. This is the list that was being described.
	 *
	 * Same reasoning as the district aliases: a search that returns
	 * nothing teaches somebody the site does not have what they want,
	 * and they do not try a second spelling.
	 *
	 * @since 1.3.3
	 * @return array[] Alias lists keyed by trade slug.
	 */
	function kaamase_trade_aliases() {

		$aliases = array(
			'mason'               => array( 'Mistri', 'Mistry', 'Rajmistri', 'Raj mistri', 'Bricklayer' ),
			'helper'              => array( 'Beldar', 'Coolie', 'Labour helper' ),
			'labour'              => array( 'Labourer', 'Laborer', 'Daily wage', 'Daily labour' ),
			'carpenter'           => array( 'Mistri carpenter', 'Wood work', 'Furniture maker' ),
			'electrician'         => array( 'Electric', 'Wireman', 'Lineman' ),
			'plumber'             => array( 'Plumbing', 'Pipe fitter' ),
			'painter'             => array( 'Painting', 'Wall painter' ),
			'welder'              => array( 'Welding', 'Gas cutter' ),
			'tile-worker'         => array( 'Tiles', 'Tile fitter', 'Marble worker' ),
			'steel-fixer'         => array( 'Bar bender', 'Barbender', 'Rod binder' ),
			'maid'                => array( 'House maid', 'Housemaid', 'Domestic help', 'Kamwali' ),
			'house-cleaner'       => array( 'Cleaner', 'Cleaning' ),
			'cook'                => array( 'Cooking', 'Kitchen help', 'Bawarchi' ),
			'babysitter'          => array( 'Baby sitter', 'Nanny', 'Ayah', 'Child care' ),
			'caregiver'           => array( 'Care taker', 'Caretaker', 'Nurse aide', 'Attendant' ),
			'driver'              => array( 'Driving', 'Car driver', 'Taxi driver' ),
			'gardener'            => array( 'Mali', 'Garden' ),
			'ac-repair'           => array( 'AC mechanic', 'Air conditioner repair', 'AC technician' ),
			'fridge-repair'       => array( 'Fridge mechanic', 'Refrigerator mechanic' ),
			'mobile-repair'       => array( 'Phone repair', 'Mobile technician' ),
			'computer-technician' => array( 'Computer repair', 'Laptop repair' ),
			'security-guard'      => array( 'Guard', 'Chowkidar', 'Watchman' ),
			'delivery-rider'      => array( 'Delivery boy', 'Delivery', 'Courier' ),
			'tailor'              => array( 'Darzi', 'Stitching', 'Sewing' ),
			'beautician'          => array( 'Parlour', 'Beauty parlour', 'Salon' ),
			'farm-labour'         => array( 'Farm worker', 'Field labour', 'Agriculture labour' ),
		);

		/**
		 * Filter the trade alias list.
		 *
		 * @since 1.3.3
		 * @param array[] $aliases Alias lists keyed by trade slug.
		 */
		return (array) apply_filters( 'kaamase_trade_aliases', $aliases );
	}
}
