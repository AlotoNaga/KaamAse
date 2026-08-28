<?php
/**
 * Install and upgrade.
 *
 * Everything that has to happen once: roles, seed terms, pages, rewrite
 * rules, and the migration framework for later versions.
 *
 * Runs on init at priority 20, which is after taxonomies at 5, post
 * types at 10 and meta at 11. That ordering is the whole reason
 * activation only sets a flag rather than doing the work itself.
 *
 * One thing this file does NOT create
 * ----------------------------------
 * Pages at /workers/, /jobs/ and /teams/. Those three are post type
 * archives already, registered in post-types.php. Creating pages with
 * the same slugs would put a page and an archive in competition for one
 * URL, and the result is a job board where the jobs listing shows an
 * empty page. It is a common mistake and an annoying one to diagnose,
 * because everything looks correctly configured.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. ENTRY POINT
   ========================================================================== */

if ( ! function_exists( 'kaamase_maybe_install' ) ) {
	/**
	 * Run setup or migration when needed.
	 *
	 * Checked on every load. Cheap, because in the normal case it is two
	 * option reads and an integer comparison.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_maybe_install() {

		$needs_setup = (bool) get_option( 'kaamase_core_needs_setup' );
		$installed   = (int) get_option( 'kaamase_core_schema', 0 );

		if ( $needs_setup ) {
			kaamase_install();
			delete_option( 'kaamase_core_needs_setup' );
		} elseif ( $installed && $installed < KAAMASE_CORE_SCHEMA ) {
			kaamase_upgrade( $installed );
		}

		if ( get_option( 'kaamase_core_flush_rewrite' ) ) {
			flush_rewrite_rules();
			delete_option( 'kaamase_core_flush_rewrite' );
		}
	}
}
add_action( 'init', 'kaamase_maybe_install', 20 );

if ( ! function_exists( 'kaamase_heal_pages' ) ) {
	/**
	 * Create any page that is defined but missing.
	 *
	 * Why this exists
	 * ---------------
	 * Page creation used to happen only inside kaamase_upgrade(), which
	 * runs once, when the stored schema number is lower than the one in
	 * the plugin file. On a site whose files are replaced one at a time
	 * by hand, that is a race with a live audience:
	 *
	 *   1. kaamase-core.php lands, carrying the new schema number.
	 *   2. A visitor loads any page. The upgrade runs, using the OLD
	 *      install.php, whose page list does not have the new page in
	 *      it. It creates nothing, and writes the new schema number.
	 *   3. The new install.php lands a minute later. Its page list is
	 *      never read, because the schema now matches and the upgrade
	 *      never runs again.
	 *
	 * The page is then missing permanently, re-copying the files does
	 * not fix it, and the only visible symptom is a 404 on a URL that
	 * the menu is already linking to. That is exactly what happened.
	 *
	 * So the definitions are the authority now, not a version number.
	 * Anything defined and missing is created.
	 *
	 * Admin only, and not during AJAX or cron, because building the
	 * definitions runs the starter content builders. Admin screen loads
	 * are rare and already heavy; the front end must not pay for this.
	 *
	 * @since 1.4.2
	 * @return void
	 */
	function kaamase_heal_pages() {

		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$stored = (array) get_option( 'kaamase_pages', array() );

		foreach ( kaamase_page_definitions() as $name => $page ) {

			if ( ! empty( $stored[ $name ] ) && 'page' === get_post_type( $stored[ $name ] ) ) {
				continue;
			}

			/*
			 * One is missing. kaamase_create_pages() handles the whole
			 * list, including finding a page somebody made by hand with
			 * the same slug, so it is called once and then we stop.
			 */
			kaamase_create_pages();

			// A new page needs its permalink to resolve.
			update_option( 'kaamase_core_flush_rewrite', 1, false );

			return;
		}
	}
}
add_action( 'admin_init', 'kaamase_heal_pages' );



/* ==========================================================================
   2. FIRST INSTALL
   ========================================================================== */

if ( ! function_exists( 'kaamase_install' ) ) {
	/**
	 * Set the platform up from nothing.
	 *
	 * Every step is idempotent. Running this twice does no harm, which
	 * matters because somebody will deactivate and reactivate the plugin
	 * while debugging something unrelated.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_install() {

		if ( function_exists( 'kaamase_add_roles' ) ) {
			kaamase_add_roles();
		}

		if ( function_exists( 'kaamase_seed_all_terms' ) ) {
			kaamase_seed_all_terms();
		}

		kaamase_create_pages();

		update_option( 'kaamase_core_schema', KAAMASE_CORE_SCHEMA );
		update_option( 'kaamase_core_installed_at', time(), false );
		update_option( 'kaamase_core_flush_rewrite', 1, false );

		/**
		 * Fires after a first install completes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'kaamase_installed' );
	}
}


/* ==========================================================================
   3. UPGRADES

   Empty today because there is only one schema version. The framework
   exists now rather than later because the first time you need a
   migration is always at the worst possible moment, with live data,
   and inventing the mechanism then is how data gets lost.

   To add one: bump KAAMASE_CORE_SCHEMA in the main plugin file, then
   add a case below. Migrations run in order and each runs once.
   ========================================================================== */

if ( ! function_exists( 'kaamase_upgrade' ) ) {
	/**
	 * Migrate stored data from an older schema.
	 *
	 * @since 1.0.0
	 * @param int $from Schema version currently installed.
	 * @return void
	 */
	function kaamase_upgrade( $from ) {

		$from = absint( $from );

		for ( $version = $from + 1; $version <= KAAMASE_CORE_SCHEMA; $version++ ) {

			switch ( $version ) {

				case 2:
					/*
					 * Nothing stored needs rewriting. The bump exists so
					 * that sites already running pick up the My team page
					 * and the refreshed roles, both of which happen below
					 * on every upgrade.
					 */
					break;

				case 4:
					/*
					 * Same again for the Who is hiring page. Nothing is
					 * migrated; the page is created by the
					 * kaamase_create_pages() call below, and the rewrite
					 * flush it schedules is what makes /employers/ resolve
					 * rather than 404 on a site that was already running.
					 */
					break;

				default:
					break;
			}

			/**
			 * Fires after each migration step.
			 *
			 * @since 1.0.0
			 * @param int $version Schema version just applied.
			 */
			do_action( 'kaamase_migrated', $version );
		}

		/*
		 * Roles and terms are refreshed on every upgrade. Roles because
		 * WordPress stores them in the database and a new capability
		 * would otherwise never reach existing users. Terms because a
		 * new trade or a new district added in a later version needs to
		 * appear on sites that are already running.
		 */
		if ( function_exists( 'kaamase_add_roles' ) ) {
			kaamase_add_roles();
		}

		if ( function_exists( 'kaamase_seed_all_terms' ) ) {
			kaamase_seed_all_terms();
		}

		kaamase_create_pages();

		update_option( 'kaamase_core_schema', KAAMASE_CORE_SCHEMA );
		update_option( 'kaamase_core_flush_rewrite', 1, false );
	}
}


/* ==========================================================================
   4. PAGES
   ========================================================================== */

if ( ! function_exists( 'kaamase_page_definitions' ) ) {
	/**
	 * Pages the platform needs.
	 *
	 * Functional pages carry a shortcode and are rendered by later
	 * modules. Content pages carry starter text.
	 *
	 * Privacy and Terms are deliberately NOT filled with legal text.
	 * Generating a policy that reads like a real one is worse than
	 * leaving it blank, because it looks finished and never gets
	 * reviewed. Under the DPDP Act a wrong privacy notice is a liability
	 * rather than a formality, so those two pages get a checklist of
	 * what a lawyer needs to cover and nothing that could be mistaken
	 * for the real thing.
	 *
	 * @since 1.0.0
	 * @return array[] Page definitions keyed by internal name.
	 */
	function kaamase_page_definitions() {

		/*
		 * Built once per request.
		 *
		 * This is not a cheap array. Four of the entries call the
		 * starter content builders below, which assemble several
		 * kilobytes of block markup through dozens of translation
		 * calls. kaamase_page_url() used to run the whole thing every
		 * time it wanted one slug, and it is called seventy two times
		 * across the plugin, twenty of them on the dashboard alone.
		 *
		 * The filter still runs, and still runs before anything is
		 * cached, so a plugin adding a page is unaffected.
		 */
		static $cached = null;

		if ( null !== $cached ) {
			return $cached;
		}

		$pages = array(

			/* ---- Functional ---- */

			'register' => array(
				'title'   => __( 'Register', 'kaamase-core' ),
				'slug'    => 'register',
				'content' => '[kaamase_register]',
			),

			'post_job' => array(
				'title'   => __( 'Post a job', 'kaamase-core' ),
				'slug'    => 'post-job',
				'content' => '[kaamase_post_job]',
			),

			'dashboard' => array(
				'title'   => __( 'My account', 'kaamase-core' ),
				'slug'    => 'dashboard',
				'content' => '[kaamase_dashboard]',
			),

			'saved' => array(
				'title'   => __( 'Saved', 'kaamase-core' ),
				'slug'    => 'saved',
				'content' => '[kaamase_saved]',
			),

			/*
			 * Not /teams/. That slug belongs to the team post type
			 * archive, and a page competing with it for the same URL is
			 * the mistake called out at the top of this file.
			 */
			'team' => array(
				'title'   => __( 'My team', 'kaamase-core' ),
				'slug'    => 'my-team',
				'content' => '[kaamase_team]',
			),

			'trades' => array(
				'title'   => __( 'All trades', 'kaamase-core' ),
				'slug'    => 'trades',
				'content' => '[kaamase_trade_index]',
			),

			'districts' => array(
				'title'   => __( 'All districts', 'kaamase-core' ),
				'slug'    => 'districts',
				'content' => '[kaamase_district_index]',
			),

			/*
			 * Not /employer/. That slug belongs to the employer post
			 * type, and a page competing with it for the same URL is the
			 * mistake called out at the top of this file.
			 */
			'employers' => array(
				'title'   => __( 'Who is hiring', 'kaamase-core' ),
				'slug'    => 'employers',
				'content' => '[kaamase_employer_index]',
			),

			'report' => array(
				'title'   => __( 'Report a problem', 'kaamase-core' ),
				'slug'    => 'report',
				'content' => '[kaamase_report_form]',
			),

			'grievance' => array(
				'title'   => __( 'Wage or job complaint', 'kaamase-core' ),
				'slug'    => 'grievance',
				'content' => '[kaamase_grievance_form]',
			),

			/* ---- Content ---- */

			'how_it_works' => array(
				'title'   => __( 'How it works', 'kaamase-core' ),
				'slug'    => 'how-it-works',
				'content' => kaamase_starter_how_it_works(),
			),

			'safety' => array(
				'title'   => __( 'Safety', 'kaamase-core' ),
				'slug'    => 'safety',
				'content' => kaamase_starter_safety(),
			),

			'about' => array(
				'title'   => __( 'About Kaam Ase', 'kaamase-core' ),
				'slug'    => 'about',
				'content' => kaamase_starter_about(),
			),

			'contact' => array(
				'title'   => __( 'Contact', 'kaamase-core' ),
				'slug'    => 'contact',
				'content' => "<!-- wp:paragraph -->\n<p>" . esc_html__( 'Add your contact details here. A phone number and an email address that a real person actually reads.', 'kaamase-core' ) . "</p>\n<!-- /wp:paragraph -->",
			),

			'help' => array(
				'title'   => __( 'Help centre', 'kaamase-core' ),
				'slug'    => 'help',
				'content' => "<!-- wp:paragraph -->\n<p>" . esc_html__( 'Add common questions here as they come up from real users. Do not write them in advance, write them after somebody asks.', 'kaamase-core' ) . "</p>\n<!-- /wp:paragraph -->",
			),

			/* ---- Legal, needs a lawyer ---- */

			'privacy' => array(
				'title'   => __( 'Privacy', 'kaamase-core' ),
				'slug'    => 'privacy',
				'content' => kaamase_starter_privacy_checklist(),
			),

			'terms' => array(
				'title'   => __( 'Terms', 'kaamase-core' ),
				'slug'    => 'terms',
				'content' => kaamase_starter_terms_checklist(),
			),
		);

		/**
		 * Filter the pages created on install.
		 *
		 * @since 1.0.0
		 * @param array[] $pages Page definitions.
		 */
		$cached = (array) apply_filters( 'kaamase_page_definitions', $pages );

		return $cached;
	}
}

if ( ! function_exists( 'kaamase_create_pages' ) ) {
	/**
	 * Create any page that does not already exist.
	 *
	 * Matched by slug first, then by stored ID, so running this again
	 * never produces a second copy of a page somebody has already
	 * edited. Page IDs are stored so that renaming a page in the admin
	 * does not break the platform.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_create_pages() {

		$stored = (array) get_option( 'kaamase_pages', array() );

		foreach ( kaamase_page_definitions() as $name => $page ) {

			// Already tracked and still present.
			if ( ! empty( $stored[ $name ] ) && 'page' === get_post_type( $stored[ $name ] ) ) {
				continue;
			}

			// Somebody created it manually with the same slug.
			$existing = get_page_by_path( $page['slug'] );

			if ( $existing instanceof WP_Post ) {
				$stored[ $name ] = $existing->ID;
				continue;
			}

			$id = wp_insert_post(
				array(
					'post_title'     => $page['title'],
					'post_name'      => $page['slug'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			if ( $id && ! is_wp_error( $id ) ) {
				$stored[ $name ] = (int) $id;
			}
		}

		/*
		 * Autoloaded, unlike most of what this plugin stores.
		 *
		 * kaamase_page_url() reads this map, and it is called on almost
		 * every request the platform serves. Left off the autoload set
		 * it was a separate query each time.
		 */
		update_option( 'kaamase_pages', $stored, true );

		kaamase_page_url_flush();

		// Point WordPress at our privacy page so its own tools use it.
		if ( ! empty( $stored['privacy'] ) && ! get_option( 'wp_page_for_privacy_policy' ) ) {
			update_option( 'wp_page_for_privacy_policy', $stored['privacy'] );
		}
	}
}

if ( ! function_exists( 'kaamase_page_url' ) ) {
	/**
	 * URL of a platform page.
	 *
	 * Resolves through the stored ID, so the link survives somebody
	 * renaming the page in the admin. Falls back to the expected slug
	 * when the page is missing entirely.
	 *
	 * @since 1.0.0
	 * @param string $name Internal page name, for example dashboard.
	 * @return string URL.
	 */
	function kaamase_page_url( $name ) {

		/*
		 * Answered once per name per request.
		 *
		 * Seventy two call sites, twenty of them on the dashboard, and
		 * each one used to do an option read, a status lookup and a
		 * permalink build on top of rebuilding every page definition.
		 * The answer cannot change within a request unless the pages
		 * are rebuilt, and kaamase_create_pages() clears this when that
		 * happens.
		 */
		if ( isset( $GLOBALS['kaamase_page_urls'][ $name ] ) ) {
			return $GLOBALS['kaamase_page_urls'][ $name ];
		}

		$stored = (array) get_option( 'kaamase_pages', array() );
		$pages  = kaamase_page_definitions();
		$slug   = isset( $pages[ $name ]['slug'] ) ? $pages[ $name ]['slug'] : $name;

		/*
		 * The stored ID is checked, not trusted.
		 *
		 * get_permalink returns a URL for a page in the trash as
		 * happily as for a live one, so a page deleted and then
		 * recreated by hand left this pointing at the dead ID for good.
		 * Every link to it went to a page that did not exist, and the
		 * only clue was that the address looked plausible.
		 */
		if ( ! empty( $stored[ $name ] ) ) {

			$page_id = (int) $stored[ $name ];

			if ( 'publish' === get_post_status( $page_id ) ) {

				$url = get_permalink( $page_id );

				if ( $url ) {

					$GLOBALS['kaamase_page_urls'][ $name ] = $url;

					return $url;
				}
			}
		}

		/*
		 * Nothing usable stored, so look for the page by its slug and
		 * remember it. This is what repairs a site where the pages were
		 * made by hand, or remade after a delete, without anybody having
		 * to know that a mapping option exists.
		 */
		$found = get_page_by_path( $slug );

		if ( $found instanceof WP_Post && 'publish' === $found->post_status ) {

			$stored[ $name ] = (int) $found->ID;

			update_option( 'kaamase_pages', $stored, true );

			$url = get_permalink( $found );

			if ( $url ) {

				$GLOBALS['kaamase_page_urls'][ $name ] = $url;

				return $url;
			}
		}

		/*
		 * Not memoised. A missing page is a fault somebody is about to
		 * fix, and caching the guess would keep serving it for the rest
		 * of the request after they had.
		 */
		return home_url( '/' . $slug . '/' );
	}
}

if ( ! function_exists( 'kaamase_page_url_flush' ) ) {
	/**
	 * Forget the resolved page URLs.
	 *
	 * Called after the pages are created or repaired, so anything asking
	 * for a URL later in the same request gets the new answer.
	 *
	 * @since 1.3.3
	 * @return void
	 */
	function kaamase_page_url_flush() {

		// Repopulated on the next call to kaamase_page_url().
		$GLOBALS['kaamase_page_urls'] = array();
	}
}


/* ==========================================================================
   5. STARTER CONTENT
   ========================================================================== */

if ( ! function_exists( 'kaamase_starter_how_it_works' ) ) {
	/**
	 * Starter content for the How it works page.
	 *
	 * @since 1.0.0
	 * @return string Block markup.
	 */
	function kaamase_starter_how_it_works() {

		$blocks = array(
			'<!-- wp:heading --><h2>' . esc_html__( 'If you are looking for work', 'kaamase-core' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:list --><ul>'
				. '<li>' . esc_html__( 'Make a free profile. It takes a few minutes.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Put your trade, your district and the day rate you expect.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Ask your colony council member, GB or church elder to vouch for you. This is the strongest thing on your profile.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Set yourself available when you are free and busy when you are not.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Kaam Ase is free for workers. Nobody from Kaam Ase will ever ask you for money.', 'kaamase-core' ) . '</li>'
				. '</ul><!-- /wp:list -->',
			'<!-- wp:heading --><h2>' . esc_html__( 'If you are looking for workers', 'kaamase-core' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:list --><ul>'
				. '<li>' . esc_html__( 'Post the job with the rate, how many workers you need and when you need them.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Say whether food, stay or transport is provided. Workers decide on this as much as on the rate.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Hire one person or a whole team with a leader.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Rate the worker afterwards. They rate you too.', 'kaamase-core' ) . '</li>'
				. '</ul><!-- /wp:list -->',
		);

		return implode( "\n", $blocks );
	}
}

if ( ! function_exists( 'kaamase_starter_safety' ) ) {
	/**
	 * Starter content for the Safety page.
	 *
	 * This is the page that gets pointed at when something goes wrong,
	 * so it says plainly what the platform will and will not do. Edit
	 * the wording, but do not soften the commitments. A safety page that
	 * promises less than you actually do is worthless, and one that
	 * promises more than you can deliver is worse.
	 *
	 * @since 1.0.0
	 * @return string Block markup.
	 */
	function kaamase_starter_safety() {

		$blocks = array(
			'<!-- wp:heading --><h2>' . esc_html__( 'Nobody pays to get a job', 'kaamase-core' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'Kaam Ase is free for workers. We will never ask a worker for money, and neither should an employer. If anyone asks you to pay for a job, for registration, for a badge or for a placement, do not pay. Report it to us.', 'kaamase-core' ) . '</p><!-- /wp:paragraph -->',

			'<!-- wp:heading --><h2>' . esc_html__( 'Work in someone else\'s home', 'kaamase-core' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'For maid, cook, babysitter and caregiver work, only verified employers can make contact. Tell someone you trust where you are going and who you are working for. If a place or a person does not feel right, leave. You do not need a reason and you do not need permission.', 'kaamase-core' ) . '</p><!-- /wp:paragraph -->',

			'<!-- wp:heading --><h2>' . esc_html__( 'Before you start work', 'kaamase-core' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:list --><ul>'
				. '<li>' . esc_html__( 'Agree the rate out loud before the first day, not after.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Agree when you will be paid. Daily, weekly or at the end.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Be careful with a large advance from someone you have never worked with.', 'kaamase-core' ) . '</li>'
				. '</ul><!-- /wp:list -->',

			'<!-- wp:heading --><h2>' . esc_html__( 'What we are and are not', 'kaamase-core' ) . '</h2><!-- /wp:heading -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'Kaam Ase is a listing platform. We introduce workers and employers to each other. We are not the employer, we do not pay wages and we are not present at the work site. What we can do is investigate a report, remove an account and keep a record.', 'kaamase-core' ) . '</p><!-- /wp:paragraph -->',
		);

		return implode( "\n", $blocks );
	}
}

if ( ! function_exists( 'kaamase_starter_about' ) ) {
	/**
	 * Starter content for the About page.
	 *
	 * @since 1.0.0
	 * @return string Block markup.
	 */
	function kaamase_starter_about() {

		return '<!-- wp:paragraph --><p>'
			. esc_html__( 'Kaam Ase means there is work. It is a hiring platform built in Nagaland, for Nagaland, by a company registered in Dimapur. Write the rest of this page yourself. People trust a page that sounds like a person more than one that sounds like a company.', 'kaamase-core' )
			. '</p><!-- /wp:paragraph -->';
	}
}

if ( ! function_exists( 'kaamase_starter_privacy_checklist' ) ) {
	/**
	 * Placeholder for the Privacy page.
	 *
	 * A checklist, not a policy. See the note in kaamase_page_definitions.
	 *
	 * @since 1.0.0
	 * @return string Block markup.
	 */
	function kaamase_starter_privacy_checklist() {

		$blocks = array(
			'<!-- wp:paragraph --><p><strong>' . esc_html__( 'This page is not written yet. Do not launch with it as it stands.', 'kaamase-core' ) . '</strong></p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'Kaam Ase holds names, photographs, villages and phone numbers of real people. Under the Digital Personal Data Protection Act a privacy notice is a legal requirement, not a formality. Have a lawyer write this. Below is what they need to cover.', 'kaamase-core' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:list --><ul>'
				. '<li>' . esc_html__( 'What is collected: name, photograph, phone number, district, town, trade, work history, ratings.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Why each item is collected, in plain language.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'What is shown publicly and what is never shown. Phone numbers are never shown.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'How long data is kept, and what happens to an inactive account.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'How somebody asks for their data, corrects it, or deletes their account.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Who to contact about a data problem, with a name and a real address.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Consent: how it is taken, and how it is withdrawn.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Any third party that touches the data, including the hosting company.', 'kaamase-core' ) . '</li>'
				. '</ul><!-- /wp:list -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'Write it so a worker with limited English can understand it. If they cannot understand it, consent was not informed.', 'kaamase-core' ) . '</p><!-- /wp:paragraph -->',
		);

		return implode( "\n", $blocks );
	}
}

if ( ! function_exists( 'kaamase_starter_terms_checklist' ) ) {
	/**
	 * Placeholder for the Terms page.
	 *
	 * @since 1.0.0
	 * @return string Block markup.
	 */
	function kaamase_starter_terms_checklist() {

		$blocks = array(
			'<!-- wp:paragraph --><p><strong>' . esc_html__( 'This page is not written yet. Do not launch with it as it stands.', 'kaamase-core' ) . '</strong></p><!-- /wp:paragraph -->',
			'<!-- wp:paragraph --><p>' . esc_html__( 'A lawyer needs to write this. The points below are the ones that will actually be argued about.', 'kaamase-core' ) . '</p><!-- /wp:paragraph -->',
			'<!-- wp:list --><ul>'
				. '<li>' . esc_html__( 'Kaam Ase is a listing platform and not the employer. This is the single most important line.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Wages are agreed and paid between the worker and the employer. Kaam Ase does not hold or guarantee them.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'What happens in a wage dispute, and what the platform will and will not do.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Grounds for removing an account: fraud, harassment, fake profiles, asking a worker for money.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Rules on ratings, and what happens to a disputed one.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Who owns the photographs a worker uploads.', 'kaamase-core' ) . '</li>'
				. '<li>' . esc_html__( 'Which courts have jurisdiction.', 'kaamase-core' ) . '</li>'
				. '</ul><!-- /wp:list -->',
		);

		return implode( "\n", $blocks );
	}
}


/* ==========================================================================
   6. AFTER INSTALL NOTICE
   ========================================================================== */

/**
 * Tell the administrator what to do next.
 *
 * Shown once, immediately after install, then dismissed for good.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_install_notice() {

	if ( ! current_user_can( 'manage_options' ) || get_option( 'kaamase_install_notice_seen' ) ) {
		return;
	}

	if ( ! get_option( 'kaamase_core_installed_at' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-success is-dismissible"><p><strong>%1$s</strong></p><ol style="margin:8px 0 0 20px;list-style:decimal;">%2$s</ol></div>',
		esc_html__( 'Kaam Ase is installed. Three things before you show it to anybody.', 'kaamase-core' ),
		implode(
			'',
			array_map(
				static function ( $step ) {
					return '<li>' . wp_kses_post( $step ) . '</li>';
				},
				array(
					esc_html__( 'Set permalinks to Post name under Settings, Permalinks. Nothing on this platform works with plain permalinks.', 'kaamase-core' ),
					esc_html__( 'Write the Privacy and Terms pages. They are placeholders and both say so in public.', 'kaamase-core' ),
					esc_html__( 'Ask your host to block PHP execution inside wp-content/uploads. Until that is done, an upload hole becomes a compromised server.', 'kaamase-core' ),
				)
			)
		)
	);

	update_option( 'kaamase_install_notice_seen', 1, false );
}
add_action( 'admin_notices', 'kaamase_install_notice' );