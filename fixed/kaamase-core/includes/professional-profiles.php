<?php
/**
 * Professional profiles.
 *
 * A second kind of profile for people looking for salaried, qualified
 * work: accountants, teachers, nurses, engineers, bank staff. It sits on
 * the same account as everything else, next to the worker and employer
 * profiles, with the same login, the same phone number and the same tick.
 *
 * Why not the worker profile
 * --------------------------
 * A mason is found today, by trade, district and day rate. An accounts
 * executive is hired over weeks, on qualifications, past jobs and a
 * monthly salary. The worker profile asks the first set of questions and
 * has no room for the second, and squeezing both into one form would
 * make it worse for the thousand people already using it.
 *
 * Who sees them
 * -------------
 * Only employers, by default. Many professionals already have a job and
 * are looking quietly, and a public "looking for work" page that their
 * current employer can find on Google can cost them that job. So a
 * profile is shown to signed in accounts that hire, with their email
 * confirmed, and to nobody else -- unless its owner chooses to make it
 * public. Kept out of search engines and sitemaps unless public, and
 * never in a sitemap in this version.
 *
 * Reaching them
 * -------------
 * Through the same contact gate as everybody else on the platform:
 * signed in, email confirmed, not blocked, counted against the daily
 * lookup limit, logged, and the owner told who looked. On top of that,
 * only an account that hires may ask. No star ratings and no "Did you
 * hire them?" question for professionals yet: hiring a salaried person
 * is not a one-day job, and those questions do not fit it.
 *
 * What it leaves alone
 * --------------------
 * Worker, team and employer profiles, their lists, their routes and
 * their pages. Old versions of the app see nothing new. The only change
 * to existing screens on the website is that the worker trade pickers
 * stop offering the professional categories, which belong here instead.
 *
 * @package KaamaseCore
 * @version 1.1.2
 * @since   1.0.0
 *
 * Changelog
 *   1.1.0  A profile begun at sign-up (professional-signup.php) is "not
 *          finished" until its first full save, which shows it. The app
 *          is told with "complete". Where the account is kept off Google,
 *          a public profile says so, and the form explains it under the
 *          "Everyone" choice.
 *   1.1.1  Only a profile that has never been saved is shown by its first
 *          save. A profile its owner hid stays hidden even if a category
 *          or language it uses is later removed and it has to be saved
 *          again.
 *   1.1.2  The search on the Professionals list folds away until it is
 *          used, as every other list's filters do, so the first screen
 *          on a phone shows professionals rather than a form.
 */

defined( 'ABSPATH' ) || exit;

/** The post type. Twenty characters, the most WordPress allows. */
define( 'KAAMASE_PROF_TYPE', 'kaamase_professional' );

/** Bumped when the one-off setup below needs to run again. */
define( 'KAAMASE_PROF_VERSION', 1 );

/** How many of each list a profile may hold. */
define( 'KAAMASE_PROF_MAX_CATEGORIES', 3 );
define( 'KAAMASE_PROF_MAX_JOBS', 5 );
define( 'KAAMASE_PROF_MAX_SKILLS', 15 );
define( 'KAAMASE_PROF_MAX_LANGUAGES', 8 );

/** One row per category, so a list can be filtered by any of them. */
define( 'KAAMASE_PROF_CATEGORY_KEY', '_kaamase_prof_category' );

/** Past jobs, skills and languages, each kept as one list. */
define( 'KAAMASE_PROF_JOBS_KEY', '_kaamase_prof_jobs' );
define( 'KAAMASE_PROF_SKILLS_KEY', '_kaamase_prof_skills' );
define( 'KAAMASE_PROF_LANGUAGES_KEY', '_kaamase_prof_languages' );

/** Whether the owner wants the profile shown. It shows once their email is confirmed. */
define( 'KAAMASE_PROF_LISTED_KEY', '_kaamase_prof_listed' );

/** Set when the profile was hidden because the account went quiet. */
define( 'KAAMASE_PROF_DORMANT_KEY', '_kaamase_prof_dormant' );


/* ==========================================================================
   1. THE TYPE
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_register_type' ) ) {
	/**
	 * Register the professional profile.
	 *
	 * Not public, but with pages of its own. That keeps it out of the
	 * site search, the menus, WordPress's own sitemap and any SEO plugin
	 * that works from the list of public types, while each profile still
	 * has an address an employer can open and share. Who may actually
	 * read that address is decided in section 7.
	 *
	 * Not in the WordPress REST API either. The app reads profiles only
	 * through the routes in section 5, which apply the same rules as the
	 * website.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_register_type() {

		register_post_type(
			KAAMASE_PROF_TYPE,
			array(
				'labels'              => array(
					'name'                  => _x( 'Professionals', 'post type general name', 'kaamase-core' ),
					'singular_name'         => _x( 'Professional', 'post type singular name', 'kaamase-core' ),
					'add_new'               => __( 'Add professional', 'kaamase-core' ),
					'add_new_item'          => __( 'Add professional', 'kaamase-core' ),
					'edit_item'             => __( 'Edit professional profile', 'kaamase-core' ),
					'new_item'              => __( 'New professional profile', 'kaamase-core' ),
					'view_item'             => __( 'View professional profile', 'kaamase-core' ),
					'search_items'          => __( 'Search professionals', 'kaamase-core' ),
					'not_found'             => __( 'No professionals found.', 'kaamase-core' ),
					'not_found_in_trash'    => __( 'No professionals in trash.', 'kaamase-core' ),
					'all_items'             => __( 'All professionals', 'kaamase-core' ),
					'menu_name'             => __( 'Professionals', 'kaamase-core' ),
					'featured_image'        => __( 'Profile photo', 'kaamase-core' ),
					'set_featured_image'    => __( 'Set profile photo', 'kaamase-core' ),
					'remove_featured_image' => __( 'Remove profile photo', 'kaamase-core' ),
				),
				'public'              => false,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-id-alt',
				'menu_position'       => 29,
				'hierarchical'        => false,
				'has_archive'         => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'author' ),
				'capability_type'     => array( 'kaamase_professional', 'kaamase_professionals' ),
				'map_meta_cap'        => true,
				'delete_with_user'    => true,
				'rewrite'             => array(
					'slug'       => 'professional',
					'with_front' => false,
				),
			)
		);
	}
}
add_action( 'init', 'kaamase_prof_register_type', 10 );

if ( ! function_exists( 'kaamase_prof_install' ) ) {
	/**
	 * One-off setup: who may manage profiles in wp-admin, the two pages,
	 * and the new addresses.
	 *
	 * Members never edit a profile through wp-admin, so no member role
	 * gains anything here. Their own profile is checked by owner in the
	 * code below.
	 *
	 * The address rules are rebuilt once everything has registered for
	 * the request, not here in the middle of init, so a rule another
	 * plugin adds later cannot be left out of them.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_install() {

		if ( (int) get_option( 'kaamase_prof_version', 0 ) >= KAAMASE_PROF_VERSION ) {
			return;
		}

		// Set first, so a failure below cannot repeat this on every request.
		update_option( 'kaamase_prof_version', KAAMASE_PROF_VERSION, true );

		if ( function_exists( 'kaamase_admin_caps' ) ) {

			$admin = get_role( 'administrator' );

			if ( $admin ) {
				foreach ( array_keys( kaamase_admin_caps( 'kaamase_professionals' ) ) as $cap ) {
					$admin->add_cap( $cap );
				}
			}
		}

		if ( function_exists( 'kaamase_moderator_caps' ) ) {

			$editor = get_role( 'editor' );

			if ( $editor ) {
				foreach ( array_keys( kaamase_moderator_caps( 'kaamase_professionals' ) ) as $cap ) {
					$editor->add_cap( $cap );
				}
			}
		}

		kaamase_prof_make_pages();

		add_action( 'wp_loaded', 'kaamase_prof_flush_rules' );
	}
}
add_action( 'init', 'kaamase_prof_install', 32 );

if ( ! function_exists( 'kaamase_prof_flush_rules' ) ) {
	/**
	 * Rebuild the address rules, so /professional/name/ resolves.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_flush_rules() {
		flush_rewrite_rules( false );
	}
}


/* ==========================================================================
   2. WHAT A PROFILE HOLDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_choices' ) ) {
	/**
	 * The answers each question takes, with the words shown for them.
	 *
	 * The qualification keys are the ones professional jobs use, so a
	 * profile and a job can be compared later without translating one
	 * into the other.
	 *
	 * @since 1.0.0
	 * @return array[]
	 */
	function kaamase_prof_choices() {

		return array(
			'status'        => array(
				'looking' => __( 'Looking for a job now', 'kaamase-core' ),
				'working' => __( 'Working, open to better offers', 'kaamase-core' ),
				'fresher' => __( 'Fresher, looking for a first job', 'kaamase-core' ),
			),
			'qualification' => array(
				'10th'         => __( 'Class 10', 'kaamase-core' ),
				'12th'         => __( 'Class 12', 'kaamase-core' ),
				'diploma'      => __( 'Diploma or ITI', 'kaamase-core' ),
				'graduate'     => __( 'Graduate (BA, B.Com, B.Sc, BCA, B.Tech and similar)', 'kaamase-core' ),
				'postgraduate' => __( 'Postgraduate (MA, M.Com, M.Sc, MBA, PhD and similar)', 'kaamase-core' ),
				'professional' => __( 'Professional degree (CA, MBBS, LLB, B.Ed and similar)', 'kaamase-core' ),
			),
			'notice'        => array(
				'now'      => __( 'Straight away', 'kaamase-core' ),
				'15_days'  => __( 'Within 15 days', 'kaamase-core' ),
				'1_month'  => __( 'Within a month', 'kaamase-core' ),
				'2_months' => __( 'Within two months', 'kaamase-core' ),
				'3_months' => __( 'In three months or more', 'kaamase-core' ),
			),
			'where'         => array(
				'district' => __( 'Only in my own district', 'kaamase-core' ),
				'nagaland' => __( 'Anywhere in Nagaland', 'kaamase-core' ),
				'anywhere' => __( 'Anywhere, in Nagaland or outside', 'kaamase-core' ),
			),
			'visibility'    => array(
				'employers' => __( 'Only employers signed in to Kaam Ase', 'kaamase-core' ),
				'public'    => __( 'Everyone, including Google search', 'kaamase-core' ),
			),
		);
	}
}

if ( ! function_exists( 'kaamase_prof_label' ) ) {
	/**
	 * The words for one answer, or nothing when it was not given.
	 *
	 * @since 1.0.0
	 * @param string $list  Which list.
	 * @param string $value The stored answer.
	 * @return string
	 */
	function kaamase_prof_label( $list, $value ) {

		$choices = kaamase_prof_choices();

		return isset( $choices[ $list ][ $value ] ) ? (string) $choices[ $list ][ $value ] : '';
	}
}

if ( ! function_exists( 'kaamase_prof_fields' ) ) {
	/**
	 * Register the single-value fields through the platform's schema.
	 *
	 * The same schema as every other profile, so the phone number is
	 * private by the same rule and is never handed to a template or the
	 * app by accident. Lists live outside it, in their own keys.
	 *
	 * @since 1.0.0
	 * @param array[] $schema Field definitions keyed by post type.
	 * @return array[]
	 */
	function kaamase_prof_fields( $schema ) {

		$choices = kaamase_prof_choices();

		$schema[ KAAMASE_PROF_TYPE ] = array(
			'phone'              => array(
				'type'    => 'phone',
				'private' => true,
				'label'   => __( 'Phone number', 'kaamase-core' ),
			),
			'district'           => array(
				'type'  => 'district',
				'label' => __( 'District', 'kaamase-core' ),
			),
			'town'               => array(
				'type'  => 'string',
				'label' => __( 'Town or village', 'kaamase-core' ),
			),
			'prof_headline'      => array(
				'type'  => 'string',
				'label' => __( 'Headline', 'kaamase-core' ),
			),
			'prof_status'        => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['status'] ),
				'default' => 'looking',
				'label'   => __( 'Status', 'kaamase-core' ),
			),
			'prof_where'         => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['where'] ),
				'default' => 'nagaland',
				'label'   => __( 'Willing to work', 'kaamase-core' ),
			),
			'prof_experience'    => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Years of experience', 'kaamase-core' ),
			),
			'prof_qualification' => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['qualification'] ),
				'default' => '',
				'label'   => __( 'Highest qualification', 'kaamase-core' ),
			),
			'prof_course'        => array(
				'type'  => 'string',
				'label' => __( 'Course or subject', 'kaamase-core' ),
			),
			'prof_institute'     => array(
				'type'  => 'string',
				'label' => __( 'College or institute', 'kaamase-core' ),
			),
			'prof_passed'        => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Year passed', 'kaamase-core' ),
			),
			'prof_salary'        => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Expected monthly salary', 'kaamase-core' ),
			),
			'prof_notice'        => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['notice'] ),
				'default' => '',
				'label'   => __( 'Can join', 'kaamase-core' ),
			),
			'prof_visibility'    => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['visibility'] ),
				'default' => 'employers',
				'label'   => __( 'Who can see the profile', 'kaamase-core' ),
			),
		);

		return $schema;
	}
}
add_filter( 'kaamase_field_schema', 'kaamase_prof_fields' );

if ( ! function_exists( 'kaamase_prof_category_names' ) ) {
	/**
	 * Every professional category, slug => name, in alphabetical order.
	 *
	 * The professional job categories, read from the terms so a category
	 * the owner adds under the heading in wp-admin is offered here too.
	 *
	 * @since 1.0.0
	 * @return array<string,string>
	 */
	function kaamase_prof_category_names() {

		static $names = null;

		if ( null !== $names ) {
			return $names;
		}

		$names = array();

		if ( ! function_exists( 'kaamase_pro_trade_slugs' ) ) {
			return $names;
		}

		foreach ( kaamase_pro_trade_slugs() as $slug ) {

			$term = get_term_by( 'slug', $slug, 'kaamase_trade' );

			if ( $term instanceof WP_Term ) {
				$names[ $slug ] = $term->name;
			}
		}

		asort( $names );

		return $names;
	}
}

if ( ! function_exists( 'kaamase_prof_language_names' ) ) {
	/**
	 * The spoken languages the platform already lists, slug => name.
	 *
	 * @since 1.0.0
	 * @return array<string,string>
	 */
	function kaamase_prof_language_names() {

		static $names = null;

		if ( null !== $names ) {
			return $names;
		}

		$names = array();

		if ( ! taxonomy_exists( 'kaamase_language' ) ) {
			return $names;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'kaamase_language',
				'hide_empty' => false,
			)
		);

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$names[ $term->slug ] = $term->name;
			}
		}

		return $names;
	}
}

if ( ! function_exists( 'kaamase_prof_list' ) ) {
	/**
	 * One stored list, as a clean array.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Profile.
	 * @param string $key     Meta key.
	 * @return array
	 */
	function kaamase_prof_list( $post_id, $key ) {

		$value = get_post_meta( (int) $post_id, $key, true );

		return is_array( $value ) ? array_values( $value ) : array();
	}
}

if ( ! function_exists( 'kaamase_prof_categories_of' ) ) {
	/**
	 * The categories on a profile, leaving out any that no longer exist.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return string[] Slugs.
	 */
	function kaamase_prof_categories_of( $post_id ) {

		$known  = kaamase_prof_category_names();
		$stored = get_post_meta( (int) $post_id, KAAMASE_PROF_CATEGORY_KEY, false );
		$out    = array();

		foreach ( (array) $stored as $slug ) {

			$slug = (string) $slug;

			if ( isset( $known[ $slug ] ) && ! in_array( $slug, $out, true ) ) {
				$out[] = $slug;
			}
		}

		return array_slice( $out, 0, KAAMASE_PROF_MAX_CATEGORIES );
	}
}

if ( ! function_exists( 'kaamase_prof_jobs_of' ) ) {
	/**
	 * Past jobs on a profile, each with title, employer, from and to.
	 *
	 * A to of 0 means they still work there.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return array[]
	 */
	function kaamase_prof_jobs_of( $post_id ) {

		$out = array();

		foreach ( kaamase_prof_list( $post_id, KAAMASE_PROF_JOBS_KEY ) as $job ) {

			if ( ! is_array( $job ) ) {
				continue;
			}

			$out[] = array(
				'title'    => isset( $job['title'] ) ? (string) $job['title'] : '',
				'employer' => isset( $job['employer'] ) ? (string) $job['employer'] : '',
				'from'     => isset( $job['from'] ) ? absint( $job['from'] ) : 0,
				'to'       => isset( $job['to'] ) ? absint( $job['to'] ) : 0,
			);
		}

		return array_slice( $out, 0, KAAMASE_PROF_MAX_JOBS );
	}
}

if ( ! function_exists( 'kaamase_prof_values' ) ) {
	/**
	 * A profile read back as the answers the form and the app send.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return array
	 */
	function kaamase_prof_values( $post_id ) {

		$post = get_post( (int) $post_id );

		if ( ! $post ) {
			return array();
		}

		$id = (int) $post->ID;

		return array(
			'name'          => (string) $post->post_title,
			'about'         => (string) $post->post_content,
			'headline'      => (string) kaamase_read_field( $id, 'prof_headline' ),
			'categories'    => kaamase_prof_categories_of( $id ),
			'district'      => (string) kaamase_read_field( $id, 'district' ),
			'town'          => (string) kaamase_read_field( $id, 'town' ),
			'where'         => (string) kaamase_read_field( $id, 'prof_where' ),
			'status'        => (string) kaamase_read_field( $id, 'prof_status' ),
			'experience'    => absint( kaamase_read_field( $id, 'prof_experience' ) ),
			'qualification' => (string) kaamase_read_field( $id, 'prof_qualification' ),
			'course'        => (string) kaamase_read_field( $id, 'prof_course' ),
			'institute'     => (string) kaamase_read_field( $id, 'prof_institute' ),
			'passed'        => absint( kaamase_read_field( $id, 'prof_passed' ) ),
			'salary'        => absint( kaamase_read_field( $id, 'prof_salary' ) ),
			'notice'        => (string) kaamase_read_field( $id, 'prof_notice' ),
			'jobs'          => kaamase_prof_jobs_of( $id ),
			'skills'        => array_map( 'strval', kaamase_prof_list( $id, KAAMASE_PROF_SKILLS_KEY ) ),
			'languages'     => array_map( 'strval', kaamase_prof_list( $id, KAAMASE_PROF_LANGUAGES_KEY ) ),
			'phone'         => (string) kaamase_read_field( $id, 'phone' ),
			'visibility'    => (string) kaamase_read_field( $id, 'prof_visibility' ),
			'listed'        => (bool) get_post_meta( $id, KAAMASE_PROF_LISTED_KEY, true ),
		);
	}
}

if ( ! function_exists( 'kaamase_prof_source_profile' ) ) {
	/**
	 * The profile a new professional profile copies its name, number and
	 * district from: the worker profile, else the one they registered with.
	 *
	 * The worker profile first because its name is a person's name. An
	 * employer profile is often called after a business.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return int Profile ID, or 0.
	 */
	function kaamase_prof_source_profile( $user_id ) {

		$worker = function_exists( 'kaamase_get_user_profile' ) ? (int) kaamase_get_user_profile( (int) $user_id, 'kaamase_worker' ) : 0;

		return $worker ? $worker : (int) get_user_meta( (int) $user_id, 'kaamase_profile_id', true );
	}
}

if ( ! function_exists( 'kaamase_prof_defaults' ) ) {
	/**
	 * Starting answers for somebody who has no professional profile yet.
	 *
	 * Their name, number and district come across from the profile they
	 * already have, so nobody retypes what they gave when they registered.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return array
	 */
	function kaamase_prof_defaults( $user_id ) {

		$user   = get_userdata( (int) $user_id );
		$source = kaamase_prof_source_profile( $user_id );
		$worker = $source && 'kaamase_worker' === get_post_type( $source );

		return array(
			'name'          => $worker ? (string) get_post_field( 'post_title', $source, 'raw' ) : ( $user ? (string) $user->display_name : '' ),
			'about'         => '',
			'headline'      => '',
			'categories'    => array(),
			'district'      => $source ? (string) kaamase_read_field( $source, 'district' ) : '',
			'town'          => $source ? (string) kaamase_read_field( $source, 'town' ) : '',
			'where'         => 'nagaland',
			'status'        => 'looking',
			'experience'    => 0,
			'qualification' => '',
			'course'        => '',
			'institute'     => '',
			'passed'        => 0,
			'salary'        => 0,
			'notice'        => 'now',
			'jobs'          => array(),
			'skills'        => array(),
			'languages'     => array(),
			'phone'         => $source ? (string) kaamase_read_field( $source, 'phone' ) : '',
			'visibility'    => 'employers',
			'listed'        => true,
		);
	}
}


/* ==========================================================================
   3. WHOSE IT IS, AND WHO MAY SEE IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_id' ) ) {
	/**
	 * The professional profile belonging to an account.
	 *
	 * One per account, like every other kind of profile.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account. Defaults to whoever is signed in.
	 * @return int Profile ID, or 0 when there is none.
	 */
	function kaamase_prof_id( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return 0;
		}

		$key    = 'kaamase_prof_' . $user_id;
		$cached = wp_cache_get( $key, 'kaamase' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		$found = get_posts(
			array(
				'post_type'      => KAAMASE_PROF_TYPE,
				'author'         => $user_id,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$id = ! empty( $found ) ? (int) $found[0] : 0;

		wp_cache_set( $key, $id, 'kaamase', HOUR_IN_SECONDS );

		return $id;
	}
}

if ( ! function_exists( 'kaamase_prof_forget' ) ) {
	/**
	 * Drop the remembered lookup for an account.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return void
	 */
	function kaamase_prof_forget( $user_id ) {
		wp_cache_delete( 'kaamase_prof_' . absint( $user_id ), 'kaamase' );
	}
}

if ( ! function_exists( 'kaamase_prof_forget_post' ) ) {
	/**
	 * Drop the lookup when a profile is saved, trashed or deleted.
	 *
	 * @since 1.0.0
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    The post, when the hook supplies it.
	 * @return void
	 */
	function kaamase_prof_forget_post( $post_id, $post = null ) {

		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post_id );
		}

		if ( $post && KAAMASE_PROF_TYPE === $post->post_type ) {
			kaamase_prof_forget( (int) $post->post_author );
		}
	}
}
add_action( 'save_post', 'kaamase_prof_forget_post', 10, 2 );
add_action( 'deleted_post', 'kaamase_prof_forget_post', 10, 2 );
add_action( 'trashed_post', 'kaamase_prof_forget_post' );

if ( ! function_exists( 'kaamase_prof_is_staff' ) ) {
	/**
	 * Whether an account looks after professional profiles.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return bool
	 */
	function kaamase_prof_is_staff( $user_id ) {

		$user_id = (int) $user_id;

		return $user_id && ( user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_others_kaamase_professionals' ) );
	}
}

if ( ! function_exists( 'kaamase_prof_may_browse' ) ) {
	/**
	 * Whether an account may see every listed professional and contact them.
	 *
	 * Signed in, email confirmed, and able to hire. Adding hiring to an
	 * account is free and instant, so this keeps profiles from strangers
	 * and scrapers rather than from anybody with a real reason.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account. Defaults to whoever is signed in.
	 * @return bool
	 */
	function kaamase_prof_may_browse( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		if ( kaamase_prof_is_staff( $user_id ) ) {
			$may = true;
		} else {
			$verified = ! function_exists( 'kaamase_user_is_verified' ) || kaamase_user_is_verified( $user_id );
			$may      = $verified && user_can( $user_id, 'create_kaamase_jobs' );
		}

		/**
		 * Filter who may see every professional profile.
		 *
		 * @since 1.0.0
		 * @param bool $may     Whether this account may.
		 * @param int  $user_id The account.
		 */
		return (bool) apply_filters( 'kaamase_prof_may_browse', $may, $user_id );
	}
}

if ( ! function_exists( 'kaamase_prof_browse_refusal' ) ) {
	/**
	 * Why an account may not see every profile, in words for that person.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account, or 0 for somebody signed out.
	 * @return array code and message. Both empty when the account may.
	 */
	function kaamase_prof_browse_refusal( $user_id ) {

		$user_id = absint( $user_id );

		if ( $user_id && kaamase_prof_may_browse( $user_id ) ) {
			return array(
				'code'    => '',
				'message' => '',
			);
		}

		if ( ! $user_id ) {
			return array(
				'code'    => 'signed_out',
				'message' => __( 'Professional profiles are shown to employers who are signed in. Sign in with an account that hires to see everybody. Only the people who chose to be public are shown here.', 'kaamase-core' ),
			);
		}

		if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( $user_id ) ) {
			return array(
				'code'    => 'unverified',
				'message' => __( 'Confirm your email to see every professional. We sent you a link when you registered.', 'kaamase-core' ),
			);
		}

		return array(
			'code'    => 'not_hiring',
			'message' => __( 'Professional profiles are shown to accounts that hire. Add hiring to your account to see everybody and contact them. It is free and happens straight away.', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_prof_is_public' ) ) {
	/**
	 * Whether the owner chose to show the profile to everyone.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return bool
	 */
	function kaamase_prof_is_public( $post_id ) {

		// The stored row, not the registered default.
		return 'public' === (string) get_post_meta( (int) $post_id, KAAMASE_META_PREFIX . 'prof_visibility', true );
	}
}

if ( ! function_exists( 'kaamase_prof_can_view' ) ) {
	/**
	 * Whether an account may read a professional profile.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @param int $user_id Account, or 0 for somebody signed out.
	 * @return bool
	 */
	function kaamase_prof_can_view( $post_id, $user_id = 0 ) {

		$post    = get_post( (int) $post_id );
		$user_id = absint( $user_id );

		if ( ! $post || KAAMASE_PROF_TYPE !== $post->post_type ) {
			return false;
		}

		if ( $user_id && (int) $post->post_author === $user_id ) {
			return true;
		}

		if ( kaamase_prof_is_staff( $user_id ) ) {
			return true;
		}

		if ( 'publish' !== $post->post_status ) {
			return false;
		}

		// Somebody they blocked does not get to read them either.
		if ( $user_id && function_exists( 'kaamase_is_blocked' ) && kaamase_is_blocked( (int) $post->post_author, $user_id ) ) {
			return false;
		}

		if ( kaamase_prof_is_public( $post->ID ) ) {
			return true;
		}

		return kaamase_prof_may_browse( $user_id );
	}
}


/* ==========================================================================
   4. SAVING
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_creating' ) ) {
	/**
	 * Whether a profile is being made right now.
	 *
	 * While it is, the tick rules in mark-changes.php do not watch this
	 * kind of profile. Copying somebody's own name and number onto their
	 * new profile is not changing them. Everything they type after that
	 * is watched like any other profile, so a new profile cannot be used
	 * to carry a tick to a different name.
	 *
	 * @since 1.0.0
	 * @param bool|null $set True or false to set, null to read.
	 * @return bool
	 */
	function kaamase_prof_creating( $set = null ) {

		static $now = false;

		if ( null !== $set ) {
			$now = (bool) $set;
		}

		return $now;
	}
}

if ( ! function_exists( 'kaamase_prof_input' ) ) {
	/**
	 * Clean whatever was sent, keeping only the answers that were sent.
	 *
	 * Only what arrived, so the app can send just the fields it changed.
	 *
	 * @since 1.0.0
	 * @param array $raw Request body or form fields, unslashed.
	 * @return array
	 */
	function kaamase_prof_input( $raw ) {

		$raw = is_array( $raw ) ? $raw : array();
		$out = array();

		$text = static function ( $value, $max ) {
			return is_scalar( $value ) ? mb_substr( trim( sanitize_text_field( (string) $value ) ), 0, $max ) : '';
		};

		$list = static function ( $value ) {
			if ( is_string( $value ) ) {
				$value = preg_split( '/[,\n]+/', $value );
			}
			return is_array( $value ) ? array_values( array_filter( $value, 'is_scalar' ) ) : array();
		};

		foreach ( array( 'name' => 90, 'headline' => 100, 'town' => 60, 'course' => 120, 'institute' => 120 ) as $key => $max ) {
			if ( array_key_exists( $key, $raw ) ) {
				$out[ $key ] = $text( $raw[ $key ], $max );
			}
		}

		foreach ( array( 'where', 'status', 'qualification', 'notice', 'visibility' ) as $key ) {
			if ( array_key_exists( $key, $raw ) ) {
				$out[ $key ] = is_scalar( $raw[ $key ] ) ? sanitize_key( (string) $raw[ $key ] ) : '';
			}
		}

		foreach ( array( 'experience', 'passed', 'salary' ) as $key ) {
			if ( array_key_exists( $key, $raw ) ) {
				$out[ $key ] = is_scalar( $raw[ $key ] ) ? absint( $raw[ $key ] ) : 0;
			}
		}

		if ( array_key_exists( 'district', $raw ) ) {
			$district        = is_scalar( $raw['district'] ) ? (string) $raw['district'] : '';
			$out['district'] = function_exists( 'kaamase_match_district' ) ? (string) kaamase_match_district( $district ) : sanitize_key( $district );
		}

		if ( array_key_exists( 'phone', $raw ) ) {
			$out['phone'] = is_scalar( $raw['phone'] ) ? sanitize_text_field( (string) $raw['phone'] ) : '';
		}

		if ( array_key_exists( 'about', $raw ) ) {
			$out['about'] = is_scalar( $raw['about'] ) ? mb_substr( trim( sanitize_textarea_field( (string) $raw['about'] ) ), 0, 2000 ) : '';
		}

		if ( array_key_exists( 'listed', $raw ) ) {
			$out['listed'] = rest_sanitize_boolean( $raw['listed'] );
		}

		if ( array_key_exists( 'categories', $raw ) ) {
			$out['categories'] = array_values( array_unique( array_filter( array_map( 'sanitize_key', array_map( 'strval', $list( $raw['categories'] ) ) ) ) ) );
		}

		if ( array_key_exists( 'languages', $raw ) ) {
			$out['languages'] = array_values( array_unique( array_filter( array_map( 'sanitize_key', array_map( 'strval', $list( $raw['languages'] ) ) ) ) ) );
		}

		if ( array_key_exists( 'skills', $raw ) ) {

			$skills = array();
			$seen   = array();

			foreach ( $list( $raw['skills'] ) as $skill ) {

				$skill = $text( $skill, 40 );
				$fold  = mb_strtolower( $skill );

				if ( '' === $skill || isset( $seen[ $fold ] ) ) {
					continue;
				}

				$seen[ $fold ] = true;
				$skills[]      = $skill;
			}

			$out['skills'] = $skills;
		}

		if ( array_key_exists( 'jobs', $raw ) ) {

			$jobs = array();

			foreach ( is_array( $raw['jobs'] ) ? $raw['jobs'] : array() as $job ) {

				if ( ! is_array( $job ) ) {
					continue;
				}

				$row = array(
					'title'    => $text( $job['title'] ?? '', 90 ),
					'employer' => $text( $job['employer'] ?? '', 90 ),
					'from'     => is_scalar( $job['from'] ?? 0 ) ? absint( $job['from'] ?? 0 ) : 0,
					'to'       => is_scalar( $job['to'] ?? 0 ) ? absint( $job['to'] ?? 0 ) : 0,
				);

				// A row left blank on the form is not a job.
				if ( '' === $row['title'] && '' === $row['employer'] ) {
					continue;
				}

				$jobs[] = $row;
			}

			$out['jobs'] = $jobs;
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_prof_validate' ) ) {
	/**
	 * Every problem with a complete set of answers, in words for a person.
	 *
	 * @since 1.0.0
	 * @param array $v Answers.
	 * @return string[] Empty when there is nothing wrong.
	 */
	function kaamase_prof_validate( $v ) {

		$errors  = array();
		$choices = kaamase_prof_choices();
		$year    = (int) wp_date( 'Y' );

		if ( mb_strlen( (string) $v['name'] ) < 2 ) {
			$errors[] = __( 'Please enter your name.', 'kaamase-core' );
		}

		if ( mb_strlen( (string) $v['headline'] ) < 3 ) {
			$errors[] = __( 'Write one line about yourself, for example: Accountant with five years in banking.', 'kaamase-core' );
		}

		$known = kaamase_prof_category_names();

		if ( empty( $v['categories'] ) ) {
			$errors[] = __( 'Choose at least one kind of work you are looking for.', 'kaamase-core' );
		} elseif ( count( $v['categories'] ) > KAAMASE_PROF_MAX_CATEGORIES ) {
			$errors[] = __( 'Choose up to three kinds of work.', 'kaamase-core' );
		} elseif ( array_diff( $v['categories'], array_keys( $known ) ) ) {
			$errors[] = __( 'One of the kinds of work chosen is not on the list. Please choose again.', 'kaamase-core' );
		}

		if ( '' === (string) $v['district'] ) {
			$errors[] = __( 'Please choose your district.', 'kaamase-core' );
		}

		if ( ! isset( $choices['status'][ $v['status'] ] ) ) {
			$errors[] = __( 'Say whether you are looking, working or a fresher.', 'kaamase-core' );
		}

		if ( ! isset( $choices['qualification'][ $v['qualification'] ] ) ) {
			$errors[] = __( 'Choose your highest qualification.', 'kaamase-core' );
		}

		if ( ! isset( $choices['where'][ $v['where'] ] ) ) {
			$errors[] = __( 'Say where you are willing to work.', 'kaamase-core' );
		}

		if ( '' !== (string) $v['notice'] && ! isset( $choices['notice'][ $v['notice'] ] ) ) {
			$errors[] = __( 'Say when you can join.', 'kaamase-core' );
		}

		if ( ! isset( $choices['visibility'][ $v['visibility'] ] ) ) {
			$errors[] = __( 'Say who can see your profile.', 'kaamase-core' );
		}

		if ( (int) $v['experience'] > 50 ) {
			$errors[] = __( 'Years of experience looks too high. Please check it.', 'kaamase-core' );
		}

		if ( (int) $v['passed'] && ( (int) $v['passed'] < 1950 || (int) $v['passed'] > $year + 5 ) ) {
			$errors[] = __( 'The year you passed does not look right. Please check it.', 'kaamase-core' );
		}

		if ( (int) $v['salary'] > 1000000 ) {
			$errors[] = __( 'Expected salary should be per month. Please check the amount.', 'kaamase-core' );
		}

		if ( count( $v['jobs'] ) > KAAMASE_PROF_MAX_JOBS ) {
			$errors[] = __( 'Add up to five past jobs. Start with the most recent.', 'kaamase-core' );
		}

		foreach ( $v['jobs'] as $job ) {

			if ( mb_strlen( $job['title'] ) < 2 ) {
				$errors[] = __( 'Every past job needs a job title.', 'kaamase-core' );
				break;
			}

			$bad_from = $job['from'] && ( $job['from'] < 1950 || $job['from'] > $year );
			$bad_to   = $job['to'] && ( $job['to'] < 1950 || $job['to'] > $year || ( $job['from'] && $job['to'] < $job['from'] ) );

			if ( $bad_from || $bad_to ) {
				$errors[] = __( 'The years on a past job do not look right. Leave the end year empty if you still work there.', 'kaamase-core' );
				break;
			}
		}

		if ( count( $v['skills'] ) > KAAMASE_PROF_MAX_SKILLS ) {
			$errors[] = __( 'Add up to fifteen skills.', 'kaamase-core' );
		}

		$languages = kaamase_prof_language_names();

		if ( count( $v['languages'] ) > KAAMASE_PROF_MAX_LANGUAGES ) {
			$errors[] = __( 'Choose up to eight languages.', 'kaamase-core' );
		} elseif ( array_diff( $v['languages'], array_keys( $languages ) ) ) {
			$errors[] = __( 'One of the languages chosen is not on the list. Please choose again.', 'kaamase-core' );
		}

		if ( '' === trim( (string) $v['phone'] ) ) {
			$errors[] = __( 'Please enter your phone number.', 'kaamase-core' );
		} elseif ( '' === kaamase_sanitize_phone( $v['phone'] ) ) {
			$errors[] = __( 'That phone number does not look right. It should be 10 digits starting with 6, 7, 8 or 9.', 'kaamase-core' );
		}

		return $errors;
	}
}

if ( ! function_exists( 'kaamase_prof_create' ) ) {
	/**
	 * Make an empty profile for an account, carrying over who they are.
	 *
	 * A draft, so nothing half finished is ever listed. See
	 * kaamase_prof_creating() for why the copying is not watched.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return int Profile ID, or 0 on failure.
	 */
	function kaamase_prof_create( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return 0;
		}

		$seed = kaamase_prof_defaults( (int) $user_id );

		kaamase_prof_creating( true );

		try {

			// Slashed, because wp_insert_post() takes slashes off whatever it is given.
			$id = wp_insert_post(
				wp_slash(
					array(
						'post_type'      => KAAMASE_PROF_TYPE,
						'post_status'    => 'draft',
						'post_author'    => (int) $user_id,
						'post_title'     => '' !== $seed['name'] ? $seed['name'] : $user->display_name,
						'post_content'   => '',
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
					)
				),
				true
			);

			if ( is_wp_error( $id ) || ! $id ) {
				return 0;
			}

			$phone = kaamase_sanitize_phone( $seed['phone'] );

			if ( '' !== $phone ) {
				kaamase_save_field( $id, 'phone', $phone );
			}

			if ( '' !== $seed['district'] ) {
				kaamase_save_field( $id, 'district', $seed['district'] );
			}

			update_post_meta( $id, KAAMASE_PROF_LISTED_KEY, 0 );

		} finally {
			kaamase_prof_creating( false );
		}

		kaamase_prof_forget( (int) $user_id );

		return (int) $id;
	}
}

if ( ! function_exists( 'kaamase_prof_save' ) ) {
	/**
	 * Create or update the professional profile of an account.
	 *
	 * The one function the website form and the app both call, so the two
	 * cannot drift apart.
	 *
	 * @since 1.0.0
	 * @param array $raw     Answers, unslashed. Missing ones keep their current value.
	 * @param int   $user_id Account.
	 * @return int|WP_Error Profile ID.
	 */
	function kaamase_prof_save( $raw, $user_id ) {

		$user_id = absint( $user_id );

		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return new WP_Error( 'kaamase_signed_out', __( 'Sign in to do that.', 'kaamase-core' ), array( 'status' => 401 ) );
		}

		$id     = kaamase_prof_id( $user_id );
		$is_new = ! $id;
		$input  = kaamase_prof_input( $raw );

		$values = array_merge(
			$is_new ? kaamase_prof_defaults( $user_id ) : kaamase_prof_values( $id ),
			$input
		);

		/*
		 * A profile begun at sign-up is not yet shown, because it is not
		 * finished. The save that finishes it shows it, as a new profile
		 * would be, unless the person said otherwise. Only that first save:
		 * a profile saved before keeps the choice its owner made.
		 */
		if ( ! $is_new && ! array_key_exists( 'listed', $input ) && kaamase_prof_never_saved( $id ) ) {
			$values['listed'] = true;
		}

		$errors = kaamase_prof_validate( $values );

		if ( $errors ) {
			return new WP_Error(
				'kaamase_invalid_professional',
				$errors[0],
				array(
					'messages' => $errors,
					'status'   => 400,
				)
			);
		}

		if ( $is_new ) {

			$id = kaamase_prof_create( $user_id );

			if ( ! $id ) {
				return new WP_Error( 'kaamase_professional_failed', __( 'That did not work. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
			}
		}

		$post   = get_post( $id );
		$update = array();

		// Only what changed, so an untouched name is not recorded as a change.
		if ( (string) $post->post_title !== $values['name'] ) {
			$update['post_title'] = $values['name'];
		}

		if ( (string) $post->post_content !== $values['about'] ) {
			$update['post_content'] = $values['about'];
		}

		if ( $update ) {

			$update['ID'] = $id;
			$done         = wp_update_post( wp_slash( $update ), true );

			if ( is_wp_error( $done ) ) {
				return new WP_Error( 'kaamase_professional_failed', __( 'That did not work. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
			}
		}

		$fields = array(
			'phone'              => kaamase_sanitize_phone( $values['phone'] ),
			'district'           => $values['district'],
			'town'               => $values['town'],
			'prof_headline'      => $values['headline'],
			'prof_status'        => $values['status'],
			'prof_where'         => $values['where'],
			'prof_experience'    => $values['experience'],
			'prof_qualification' => $values['qualification'],
			'prof_course'        => $values['course'],
			'prof_institute'     => $values['institute'],
			'prof_passed'        => $values['passed'],
			'prof_salary'        => $values['salary'],
			'prof_notice'        => $values['notice'],
			'prof_visibility'    => $values['visibility'],
		);

		foreach ( $fields as $key => $value ) {
			kaamase_save_field( $id, $key, $value );
		}

		$stored = array_map( 'strval', get_post_meta( $id, KAAMASE_PROF_CATEGORY_KEY, false ) );

		if ( $stored !== $values['categories'] ) {

			delete_post_meta( $id, KAAMASE_PROF_CATEGORY_KEY );

			foreach ( $values['categories'] as $slug ) {
				add_post_meta( $id, KAAMASE_PROF_CATEGORY_KEY, $slug );
			}
		}

		update_post_meta( $id, KAAMASE_PROF_JOBS_KEY, array_values( $values['jobs'] ) );
		update_post_meta( $id, KAAMASE_PROF_SKILLS_KEY, array_values( $values['skills'] ) );
		update_post_meta( $id, KAAMASE_PROF_LANGUAGES_KEY, array_values( $values['languages'] ) );

		kaamase_prof_apply_listing( $id, $user_id, (bool) $values['listed'] );

		kaamase_prof_forget( $user_id );
		kaamase_prof_purge( $id );

		/**
		 * Fires after a professional profile is saved.
		 *
		 * @since 1.0.0
		 * @param int  $id      Profile.
		 * @param bool $is_new  Whether it was just made.
		 * @param int  $user_id Account.
		 */
		do_action( 'kaamase_prof_saved', $id, $is_new, $user_id );

		return (int) $id;
	}
}

if ( ! function_exists( 'kaamase_prof_apply_listing' ) ) {
	/**
	 * Show or hide the profile, as its owner asked.
	 *
	 * Shown only once the email address is confirmed, the same rule every
	 * other profile follows. A profile staff have put on hold, or made
	 * private in wp-admin, is left exactly as staff left it.
	 *
	 * @since 1.0.0
	 * @param int  $post_id Profile.
	 * @param int  $user_id Account.
	 * @param bool $want    Whether the owner wants it shown.
	 * @return void
	 */
	function kaamase_prof_apply_listing( $post_id, $user_id, $want ) {

		update_post_meta( $post_id, KAAMASE_PROF_LISTED_KEY, $want ? 1 : 0 );
		delete_post_meta( $post_id, KAAMASE_PROF_DORMANT_KEY );

		$status = get_post_status( $post_id );

		if ( ! in_array( $status, array( 'publish', 'draft' ), true ) ) {
			return;
		}

		$verified = ! function_exists( 'kaamase_user_is_verified' ) || kaamase_user_is_verified( (int) $user_id );
		$target   = ( $want && $verified ) ? 'publish' : 'draft';

		if ( $target !== $status ) {
			wp_update_post(
				array(
					'ID'          => (int) $post_id,
					'post_status' => $target,
				)
			);
		}
	}
}

if ( ! function_exists( 'kaamase_prof_is_complete' ) ) {
	/**
	 * Whether a profile has been finished: saved in full at least once.
	 *
	 * Every profile saved through the form or the app is, because saving
	 * checks everything. One made at sign-up is not yet: it has a name, a
	 * number, a district and a kind of work, and the rest is asked on the
	 * next screen. See professional-signup.php.
	 *
	 * Deliberately not "passes the checks today". A finished profile can
	 * stop passing them if a category or language it uses is removed, and
	 * calling that profile unfinished would tell its owner, and the app,
	 * to treat it as new: "employers can see it once finished", the
	 * "Show my profile" box ticked for them. That owner has already
	 * chosen. The next save asks them to fix what was removed instead.
	 *
	 * @since 1.1.0
	 * @param int $post_id Profile.
	 * @return bool
	 */
	function kaamase_prof_is_complete( $post_id ) {

		$post = get_post( (int) $post_id );

		return $post && KAAMASE_PROF_TYPE === $post->post_type && ! kaamase_prof_never_saved( $post->ID );
	}
}

if ( ! function_exists( 'kaamase_prof_never_saved' ) ) {
	/**
	 * Whether a profile has never been through a full save: one begun at
	 * sign-up and not yet finished.
	 *
	 * Not the same as "not complete". A finished profile can stop being
	 * complete later, if a category or language it uses is removed, and
	 * that profile's owner has already chosen whether it is shown. Only a
	 * profile nobody has saved yet may have that choice made for it.
	 *
	 * Every save writes the headline, which must be filled in, and a
	 * field is never deleted by saving, so a stored headline is proof of
	 * a save.
	 *
	 * @since 1.1.1
	 * @param int $post_id Profile.
	 * @return bool
	 */
	function kaamase_prof_never_saved( $post_id ) {
		return ! metadata_exists( 'post', (int) $post_id, KAAMASE_META_PREFIX . 'prof_headline' );
	}
}

if ( ! function_exists( 'kaamase_prof_state' ) ) {
	/**
	 * Where a profile stands, as the owner would put it.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return string listed, hidden, waiting_for_email, on_hold or missing.
	 */
	function kaamase_prof_state( $post_id ) {

		$post = get_post( (int) $post_id );

		if ( ! $post || KAAMASE_PROF_TYPE !== $post->post_type ) {
			return 'missing';
		}

		if ( 'publish' === $post->post_status ) {
			return 'listed';
		}

		if ( 'draft' !== $post->post_status ) {
			return 'on_hold';
		}

		$wants    = (bool) get_post_meta( $post->ID, KAAMASE_PROF_LISTED_KEY, true );
		$verified = ! function_exists( 'kaamase_user_is_verified' ) || kaamase_user_is_verified( (int) $post->post_author );

		return ( $wants && ! $verified ) ? 'waiting_for_email' : 'hidden';
	}
}

if ( ! function_exists( 'kaamase_prof_on_verified' ) ) {
	/**
	 * Show a waiting profile once its owner confirms their email.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return void
	 */
	function kaamase_prof_on_verified( $user_id ) {

		$id = kaamase_prof_id( (int) $user_id );

		if ( ! $id || 'draft' !== get_post_status( $id ) ) {
			return;
		}

		if ( ! get_post_meta( $id, KAAMASE_PROF_LISTED_KEY, true ) || get_post_meta( $id, KAAMASE_PROF_DORMANT_KEY, true ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);

		kaamase_prof_purge( $id );
	}
}
add_action( 'kaamase_user_verified', 'kaamase_prof_on_verified' );

if ( ! function_exists( 'kaamase_prof_purge' ) ) {
	/**
	 * Clear the stored copy of a profile's page after a change.
	 *
	 * Only public profiles are ever stored for people signed out, but a
	 * profile that has just stopped being public is exactly the one whose
	 * old copy must go.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return void
	 */
	function kaamase_prof_purge( $post_id ) {
		do_action( 'litespeed_purge_post', (int) $post_id );
	}
}

if ( ! function_exists( 'kaamase_prof_photo_from' ) ) {
	/**
	 * Put an uploaded photograph on a profile, replacing any old one.
	 *
	 * Through media_handle_upload, the path the theme hooks to strip the
	 * location phones hide inside photos.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Profile.
	 * @param string $field   Key of the file in $_FILES.
	 * @return true|WP_Error
	 */
	function kaamase_prof_photo_from( $post_id, $field ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked by every caller.
		$file = isset( $_FILES[ $field ] ) && is_array( $_FILES[ $field ] ) ? $_FILES[ $field ] : array();

		if ( empty( $file['name'] ) ) {
			return new WP_Error( 'kaamase_no_file', __( 'No photo arrived. Please try again.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		$type = isset( $file['type'] ) ? (string) $file['type'] : '';

		if ( ! in_array( $type, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
			return new WP_Error( 'kaamase_bad_file', __( 'Photos need to be a JPG, PNG or WEBP.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		if ( isset( $file['size'] ) && (int) $file['size'] > 10 * MB_IN_BYTES ) {
			return new WP_Error( 'kaamase_bad_file', __( 'That photo is too large. Please choose one under 10 MB.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( $field, (int) $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$previous = (int) get_post_thumbnail_id( $post_id );

		set_post_thumbnail( $post_id, $attachment_id );

		if ( $previous && $previous !== (int) $attachment_id ) {
			wp_delete_attachment( $previous, true );
		}

		kaamase_prof_purge( $post_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_prof_photo_remove' ) ) {
	/**
	 * Take the photograph off a profile, and delete the file.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return void
	 */
	function kaamase_prof_photo_remove( $post_id ) {

		$existing = (int) get_post_thumbnail_id( $post_id );

		if ( $existing ) {
			delete_post_thumbnail( $post_id );
			wp_delete_attachment( $existing, true );
		}

		kaamase_prof_purge( $post_id );
	}
}

if ( ! function_exists( 'kaamase_prof_photo_allowed' ) ) {
	/**
	 * Whether this account may upload another photograph this hour.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return bool
	 */
	function kaamase_prof_photo_allowed( $user_id ) {

		if ( ! function_exists( 'kaamase_rate_bump' ) ) {
			return true;
		}

		return kaamase_rate_bump( 'prof_photo_' . absint( $user_id ), HOUR_IN_SECONDS ) <= 20;
	}
}

if ( ! function_exists( 'kaamase_prof_delete_post' ) ) {
	/**
	 * Delete one profile completely, photographs included.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return void
	 */
	function kaamase_prof_delete_post( $post_id ) {

		$post = get_post( (int) $post_id );

		if ( ! $post || KAAMASE_PROF_TYPE !== $post->post_type ) {
			return;
		}

		if ( function_exists( 'kaamase_delete_attached_media' ) ) {
			kaamase_delete_attached_media( $post->ID );
		} else {
			$thumb = (int) get_post_thumbnail_id( $post->ID );
			if ( $thumb ) {
				wp_delete_attachment( $thumb, true );
			}
		}

		kaamase_prof_purge( $post->ID );

		wp_delete_post( $post->ID, true );

		kaamase_prof_forget( (int) $post->post_author );
	}
}

if ( ! function_exists( 'kaamase_prof_delete_all' ) ) {
	/**
	 * Delete every professional profile an account has.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return int How many were deleted.
	 */
	function kaamase_prof_delete_all( $user_id ) {

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return 0;
		}

		$ids = get_posts(
			array(
				'post_type'      => KAAMASE_PROF_TYPE,
				'author'         => $user_id,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		// 'any' leaves out the bin, which still holds the photograph.
		$binned = get_posts(
			array(
				'post_type'      => KAAMASE_PROF_TYPE,
				'author'         => $user_id,
				'post_status'    => 'trash',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$all = array_unique( array_merge( (array) $ids, (array) $binned ) );

		foreach ( $all as $id ) {
			kaamase_prof_delete_post( (int) $id );
		}

		kaamase_prof_forget( $user_id );

		return count( $all );
	}
}


/* ==========================================================================
   5. THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_shape' ) ) {
	/**
	 * A professional profile as the app receives it.
	 *
	 * Never the phone number, except to its owner, under mine. A number
	 * reaches anybody else only through the contact route.
	 *
	 * @since 1.0.0
	 * @param int|WP_Post $post Profile.
	 * @param bool        $full Whether to include the long fields.
	 * @return array|null
	 */
	function kaamase_prof_shape( $post, $full = false ) {

		$post = get_post( $post );

		if ( ! $post || KAAMASE_PROF_TYPE !== $post->post_type ) {
			return null;
		}

		$id    = (int) $post->ID;
		$names = kaamase_prof_category_names();
		$name  = get_the_title( $id );

		$categories = array();

		foreach ( kaamase_prof_categories_of( $id ) as $slug ) {
			$categories[] = array(
				'slug' => $slug,
				'name' => $names[ $slug ],
			);
		}

		$status        = (string) kaamase_read_field( $id, 'prof_status' );
		$qualification = (string) kaamase_read_field( $id, 'prof_qualification' );
		$notice        = (string) kaamase_read_field( $id, 'prof_notice' );
		$where         = (string) kaamase_read_field( $id, 'prof_where' );
		$visibility    = kaamase_prof_is_public( $id ) ? 'public' : 'employers';

		$out = array(
			'id'                  => $id,
			'type'                => 'professional',
			'name'                => $name,
			'initials'            => function_exists( 'kaamase_shape_initials' ) ? kaamase_shape_initials( $name ) : '',
			'url'                 => (string) get_permalink( $id ),
			'image'               => function_exists( 'kaamase_shape_image' ) ? kaamase_shape_image( $id ) : null,
			'headline'            => (string) kaamase_read_field( $id, 'prof_headline' ),
			'categories'          => $categories,
			'district'            => function_exists( 'kaamase_shape_district' ) ? kaamase_shape_district( (string) kaamase_read_field( $id, 'district' ) ) : null,
			'town'                => (string) kaamase_read_field( $id, 'town' ),
			'status'              => $status,
			'status_label'        => kaamase_prof_label( 'status', $status ),
			'experience_years'    => absint( kaamase_read_field( $id, 'prof_experience' ) ),
			'qualification'       => $qualification,
			'qualification_label' => kaamase_prof_label( 'qualification', $qualification ),
			'course'              => (string) kaamase_read_field( $id, 'prof_course' ),
			'salary_expected'     => absint( kaamase_read_field( $id, 'prof_salary' ) ),
			'notice'              => $notice,
			'notice_label'        => kaamase_prof_label( 'notice', $notice ),
			'work_where'          => $where,
			'work_where_label'    => kaamase_prof_label( 'where', $where ),
			'visibility'          => $visibility,
			'called'              => function_exists( 'kaamase_called_mark' ) ? kaamase_called_mark( (int) $post->post_author ) : array(
				'called' => false,
				'label'  => '',
				'since'  => 0,
			),
			'is_mine'             => (int) $post->post_author === get_current_user_id(),
			'updated_at'          => (int) get_post_modified_time( 'U', true, $id ),
		);

		if ( ! empty( $out['called']['called'] ) && function_exists( 'kaamase_verified_explainer' ) ) {
			$out['called']['explainer'] = array_values( kaamase_verified_explainer() );
		}

		if ( $full ) {

			$languages = array();
			$known     = kaamase_prof_language_names();

			foreach ( kaamase_prof_list( $id, KAAMASE_PROF_LANGUAGES_KEY ) as $slug ) {
				if ( isset( $known[ (string) $slug ] ) ) {
					$languages[] = array(
						'slug' => (string) $slug,
						'name' => $known[ (string) $slug ],
					);
				}
			}

			$out['about']       = wp_strip_all_tags( (string) $post->post_content );
			$out['institute']   = (string) kaamase_read_field( $id, 'prof_institute' );
			$out['passed_year'] = absint( kaamase_read_field( $id, 'prof_passed' ) );
			$out['jobs']        = kaamase_prof_jobs_of( $id );
			$out['skills']      = array_map( 'strval', kaamase_prof_list( $id, KAAMASE_PROF_SKILLS_KEY ) );
			$out['languages']   = $languages;
		}

		if ( $out['is_mine'] ) {
			$out['mine'] = array(
				'phone'    => (string) kaamase_read_field( $id, 'phone' ),
				'listed'   => (bool) get_post_meta( $id, KAAMASE_PROF_LISTED_KEY, true ),
				'state'    => kaamase_prof_state( $id ),
				'lookups'  => kaamase_prof_lookups( $id ),
				'complete' => kaamase_prof_is_complete( $id ),
			);
		}

		/**
		 * Filter a professional profile as the app receives it.
		 *
		 * @since 1.0.0
		 * @param array   $out  Shaped profile.
		 * @param WP_Post $post The profile.
		 * @param bool    $full Whether the long fields were included.
		 */
		return (array) apply_filters( 'kaamase_prof_shape', $out, $post, $full );
	}
}

if ( ! function_exists( 'kaamase_prof_filters' ) ) {
	/**
	 * Clean list filters, from the app or the website's address bar.
	 *
	 * @since 1.0.0
	 * @param array $raw category, district, status, search, sort.
	 * @return array
	 */
	function kaamase_prof_filters( $raw ) {

		$raw     = is_array( $raw ) ? $raw : array();
		$value   = static function ( $key ) use ( $raw ) {
			return isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? (string) $raw[ $key ] : '';
		};
		$choices = kaamase_prof_choices();

		$category = sanitize_key( $value( 'category' ) );
		$status   = sanitize_key( $value( 'status' ) );
		$sort     = sanitize_key( $value( 'sort' ) );
		$district = $value( 'district' );

		return array(
			'category' => isset( kaamase_prof_category_names()[ $category ] ) ? $category : '',
			'district' => '' !== $district && function_exists( 'kaamase_match_district' ) ? (string) kaamase_match_district( $district ) : '',
			'status'   => isset( $choices['status'][ $status ] ) ? $status : '',
			'search'   => mb_substr( trim( sanitize_text_field( $value( 'search' ) ) ), 0, 80 ),
			'sort'     => in_array( $sort, array( 'newest', 'experience' ), true ) ? $sort : 'shuffle',
		);
	}
}

if ( ! function_exists( 'kaamase_prof_find' ) ) {
	/**
	 * The listed profiles one account may see, filtered and in order.
	 *
	 * IDs only. Ordered here rather than in the database, the same way the
	 * verified list is: a shuffle that holds still all day and changes
	 * tomorrow, so everybody has days near the top.
	 *
	 * @since 1.0.0
	 * @param array $filters From kaamase_prof_filters().
	 * @param int   $viewer  Account, or 0.
	 * @return int[]
	 */
	function kaamase_prof_find( $filters, $viewer ) {

		$viewer = absint( $viewer );
		$meta   = array();

		if ( ! kaamase_prof_may_browse( $viewer ) ) {
			$meta[] = array(
				'key'   => KAAMASE_META_PREFIX . 'prof_visibility',
				'value' => 'public',
			);
		}

		if ( '' !== $filters['category'] ) {
			$meta[] = array(
				'key'   => KAAMASE_PROF_CATEGORY_KEY,
				'value' => $filters['category'],
			);
		}

		if ( '' !== $filters['district'] ) {
			$meta[] = array(
				'key'   => KAAMASE_META_PREFIX . 'district',
				'value' => $filters['district'],
			);
		}

		if ( '' !== $filters['status'] ) {
			$meta[] = array(
				'key'   => KAAMASE_META_PREFIX . 'prof_status',
				'value' => $filters['status'],
			);
		}

		$args = array(
			'post_type'           => KAAMASE_PROF_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => 3000,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
		);

		if ( $meta ) {
			if ( count( $meta ) > 1 ) {
				$meta['relation'] = 'AND';
			}
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$ids = array_values( array_filter( array_map( 'intval', (array) get_posts( $args ) ) ) );

		if ( ! $ids ) {
			return array();
		}

		if ( function_exists( '_prime_post_caches' ) ) {
			_prime_post_caches( $ids, false, false );
		}

		// Nobody is shown to an account they blocked.
		if ( $viewer && function_exists( 'kaamase_is_blocked' ) ) {
			$ids = array_values(
				array_filter(
					$ids,
					static function ( $id ) use ( $viewer ) {
						return ! kaamase_is_blocked( (int) get_post_field( 'post_author', $id ), $viewer );
					}
				)
			);
		}

		if ( '' !== $filters['search'] && $ids ) {

			update_meta_cache( 'post', $ids );

			$words = array_filter( preg_split( '/\s+/', mb_strtolower( $filters['search'] ) ) );
			$names = kaamase_prof_category_names();

			$ids = array_values(
				array_filter(
					$ids,
					static function ( $id ) use ( $words, $names ) {

						$hay = array(
							get_post_field( 'post_title', $id, 'raw' ),
							get_post_field( 'post_content', $id, 'raw' ),
							kaamase_read_field( $id, 'prof_headline' ),
							kaamase_read_field( $id, 'prof_course' ),
							kaamase_read_field( $id, 'prof_institute' ),
							kaamase_read_field( $id, 'town' ),
							implode( ' ', array_map( 'strval', kaamase_prof_list( $id, KAAMASE_PROF_SKILLS_KEY ) ) ),
						);

						foreach ( kaamase_prof_categories_of( $id ) as $slug ) {
							$hay[] = $names[ $slug ];
						}

						foreach ( kaamase_prof_jobs_of( $id ) as $job ) {
							$hay[] = $job['title'] . ' ' . $job['employer'];
						}

						$hay = mb_strtolower( implode( ' ', array_map( 'strval', $hay ) ) );

						foreach ( $words as $word ) {
							if ( false === mb_strpos( $hay, $word ) ) {
								return false;
							}
						}

						return true;
					}
				)
			);
		}

		if ( 'newest' === $filters['sort'] ) {
			return $ids;
		}

		if ( 'experience' === $filters['sort'] ) {

			update_meta_cache( 'post', $ids );

			$years = array();

			foreach ( $ids as $id ) {
				$years[ $id ] = absint( kaamase_read_field( $id, 'prof_experience' ) );
			}

			usort(
				$ids,
				static function ( $a, $b ) use ( $years ) {
					return array( $years[ $b ], $b ) <=> array( $years[ $a ], $a );
				}
			);

			return $ids;
		}

		$day = wp_date( 'Ymd' );

		usort(
			$ids,
			static function ( $a, $b ) use ( $day ) {
				return array( crc32( $day . ':' . $a ), $a ) <=> array( crc32( $day . ':' . $b ), $b );
			}
		);

		return $ids;
	}
}

if ( ! function_exists( 'kaamase_prof_no_store' ) ) {
	/**
	 * Keep an answer out of every cache.
	 *
	 * Who is listed depends on who is asking, and a profile that stops
	 * being public must stop being shown at once.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_no_store() {

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'kaamase professionals' );

		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}
}

if ( ! function_exists( 'kaamase_prof_routes' ) ) {
	/**
	 * The app's routes.
	 *
	 *   GET  /professionals                 the list
	 *   GET  /professionals/{id}            one profile
	 *   GET  /professionals/slug/{slug}     one profile, from a shared link
	 *   GET  /me/professional               your own profile, or null
	 *   POST /me/professional               create it, or change it
	 *   POST /me/professional/photo         its photograph
	 *   POST /me/professional/delete        delete it
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = function_exists( 'kaamase_rest_require_login' ) ? 'kaamase_rest_require_login' : 'is_user_logged_in';

		register_rest_route(
			KAAMASE_REST_NS,
			'/professionals',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_prof_rest_list',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/professionals/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_prof_rest_one',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/professionals/slug/(?P<slug>[^/]+)',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_prof_rest_by_slug',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/professional',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'kaamase_prof_rest_mine',
					'permission_callback' => $auth,
				),
				array(
					'methods'             => array( 'POST', 'PUT', 'PATCH' ),
					'callback'            => 'kaamase_prof_rest_save',
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/professional/photo',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_prof_rest_photo',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/professional/delete',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_prof_rest_delete',
				'permission_callback' => $auth,
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_prof_routes' );

if ( ! function_exists( 'kaamase_prof_rest_fail' ) ) {
	/**
	 * An error as the app expects it.
	 *
	 * @since 1.0.0
	 * @param WP_Error $error Error.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_prof_rest_fail( $error ) {
		return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $error ) : $error;
	}
}

if ( ! function_exists( 'kaamase_prof_rest_list' ) ) {
	/**
	 * The list.
	 *
	 * Open to everybody, but somebody who may not see every profile is
	 * given only the public ones, and told why in notice.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_prof_rest_list( $request ) {

		kaamase_prof_no_store();

		$viewer  = get_current_user_id();
		$filters = kaamase_prof_filters(
			array(
				'category' => $request->get_param( 'category' ),
				'district' => $request->get_param( 'district' ),
				'status'   => $request->get_param( 'status' ),
				'search'   => $request->get_param( 'search' ),
				'sort'     => $request->get_param( 'sort' ),
			)
		);

		$page = $request->get_param( 'page' );
		$page = is_scalar( $page ) ? min( max( 1, (int) $page ), 100000 ) : 1;
		$per  = $request->get_param( 'per_page' );
		$per  = is_scalar( $per ) && (int) $per > 0 ? min( (int) $per, 50 ) : 20;

		$ids   = kaamase_prof_find( $filters, $viewer );
		$total = count( $ids );
		$shown = array_slice( $ids, ( $page - 1 ) * $per, $per );

		if ( $shown ) {
			update_meta_cache( 'post', $shown );
		}

		$items = array();

		foreach ( $shown as $id ) {

			$shaped = kaamase_prof_shape( $id, false );

			if ( $shaped ) {
				$items[] = $shaped;
			}
		}

		$all     = kaamase_prof_may_browse( $viewer );
		$refusal = kaamase_prof_browse_refusal( $viewer );

		return new WP_REST_Response(
			array(
				'items'       => $items,
				'total'       => $total,
				'page'        => $page,
				'has_more'    => ( $page * $per ) < $total,
				'scope'       => $all ? 'all' : 'public',
				'can_contact' => $all,
				'notice_code' => $refusal['code'],
				'notice'      => $refusal['message'],
				'sort'        => $filters['sort'],
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_prof_rest_answer' ) ) {
	/**
	 * One profile, or the reason it cannot be shown.
	 *
	 * 401 means sign in, 403 means signed in but not an account that
	 * hires, the same split the employer directory uses. Anything else
	 * that cannot be shown is simply not found.
	 *
	 * @since 1.0.0
	 * @param WP_Post|null $post Profile.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_prof_rest_answer( $post ) {

		kaamase_prof_no_store();

		if ( ! $post instanceof WP_Post || KAAMASE_PROF_TYPE !== $post->post_type ) {
			return kaamase_prof_rest_fail( new WP_Error( 'kaamase_not_found', __( 'That profile no longer exists.', 'kaamase-core' ), array( 'status' => 404 ) ) );
		}

		$viewer = get_current_user_id();

		if ( kaamase_prof_can_view( $post->ID, $viewer ) ) {
			return new WP_REST_Response( kaamase_prof_shape( $post, true ), 200 );
		}

		$blocked = $viewer && function_exists( 'kaamase_is_blocked' ) && kaamase_is_blocked( (int) $post->post_author, $viewer );

		if ( 'publish' !== $post->post_status || $blocked ) {
			return kaamase_prof_rest_fail( new WP_Error( 'kaamase_not_found', __( 'That profile is not available.', 'kaamase-core' ), array( 'status' => 404 ) ) );
		}

		$refusal = kaamase_prof_browse_refusal( $viewer );

		return kaamase_prof_rest_fail(
			new WP_Error(
				$viewer ? 'kaamase_prof_employers_only' : 'kaamase_signed_out',
				$refusal['message'],
				array(
					'status'      => $viewer ? 403 : 401,
					'notice_code' => $refusal['code'],
				)
			)
		);
	}
}

if ( ! function_exists( 'kaamase_prof_rest_one' ) ) {
	/**
	 * GET /professionals/{id}
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_prof_rest_one( $request ) {
		return kaamase_prof_rest_answer( get_post( absint( $request['id'] ) ) );
	}
}

if ( ! function_exists( 'kaamase_prof_rest_by_slug' ) ) {
	/**
	 * GET /professionals/slug/{slug}
	 *
	 * For a profile link somebody shared, which carries the name in the
	 * address rather than the number.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_prof_rest_by_slug( $request ) {

		$slug = sanitize_title( rawurldecode( (string) $request['slug'] ) );

		$found = '' === $slug ? array() : get_posts(
			array(
				'name'           => $slug,
				'post_type'      => KAAMASE_PROF_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		return kaamase_prof_rest_answer( $found ? $found[0] : null );
	}
}

if ( ! function_exists( 'kaamase_prof_rest_mine_answer' ) ) {
	/**
	 * The caller's own profile, wrapped so "none yet" is a plain null.
	 *
	 * @since 1.0.0
	 * @param int $status HTTP status.
	 * @return WP_REST_Response
	 */
	function kaamase_prof_rest_mine_answer( $status = 200 ) {

		$id       = kaamase_prof_id( get_current_user_id() );
		$state    = $id ? kaamase_prof_state( $id ) : 'missing';
		$complete = $id && kaamase_prof_is_complete( $id );

		$messages = array(
			'waiting_for_email' => __( 'Saved. Your profile is shown to employers as soon as you confirm your email.', 'kaamase-core' ),
			'hidden'            => __( 'Saved. Your profile is hidden, so employers cannot see it.', 'kaamase-core' ),
			'on_hold'           => __( 'Your profile is being checked by Kaam Ase.', 'kaamase-core' ),
		);

		// Begun at sign-up and not finished: nothing has been saved yet, so not "Saved".
		if ( $id && ! $complete && 'on_hold' !== $state ) {
			$message = __( 'Your profile is not finished yet. Add the rest and save it, and employers can see it.', 'kaamase-core' );
		} else {
			$message = isset( $messages[ $state ] ) ? $messages[ $state ] : '';
		}

		return new WP_REST_Response(
			array(
				'profile'  => $id ? kaamase_prof_shape( $id, true ) : null,
				'state'    => $state,
				'complete' => $complete,
				'message'  => $message,
			),
			$status
		);
	}
}

if ( ! function_exists( 'kaamase_prof_rest_mine' ) ) {
	/**
	 * GET /me/professional
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	function kaamase_prof_rest_mine() {
		return kaamase_prof_rest_mine_answer( 200 );
	}
}

if ( ! function_exists( 'kaamase_prof_rest_save' ) ) {
	/**
	 * POST /me/professional
	 *
	 * Creates the profile the first time, changes it after. Send only
	 * what changed; everything else keeps its value.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_prof_rest_save( $request ) {

		$user_id = get_current_user_id();
		$had     = (bool) kaamase_prof_id( $user_id );
		$body    = $request->get_json_params();
		$body    = is_array( $body ) ? $body : $request->get_body_params();

		$result = kaamase_prof_save( (array) $body, $user_id );

		if ( is_wp_error( $result ) ) {
			return kaamase_prof_rest_fail( $result );
		}

		return kaamase_prof_rest_mine_answer( $had ? 200 : 201 );
	}
}

if ( ! function_exists( 'kaamase_prof_rest_photo' ) ) {
	/**
	 * POST /me/professional/photo
	 *
	 * A file under photo, as /me/photo takes it. remove=1 takes the
	 * photograph off instead.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_prof_rest_photo( $request ) {

		$user_id = get_current_user_id();
		$id      = kaamase_prof_id( $user_id );

		if ( ! $id ) {
			return kaamase_prof_rest_fail( new WP_Error( 'kaamase_no_professional', __( 'Make your professional profile first, then add a photo.', 'kaamase-core' ), array( 'status' => 404 ) ) );
		}

		if ( rest_sanitize_boolean( $request->get_param( 'remove' ) ) ) {

			kaamase_prof_photo_remove( $id );

			return new WP_REST_Response( array( 'image' => null ), 200 );
		}

		if ( ! kaamase_prof_photo_allowed( $user_id ) ) {
			return kaamase_prof_rest_fail( new WP_Error( 'kaamase_limit', __( 'That is a lot of photos in one hour. Please try again later.', 'kaamase-core' ), array( 'status' => 429 ) ) );
		}

		$files = $request->get_file_params();

		if ( empty( $files['photo'] ) ) {
			return kaamase_prof_rest_fail( new WP_Error( 'kaamase_no_file', __( 'No photo arrived. Please try again.', 'kaamase-core' ), array( 'status' => 400 ) ) );
		}

		$_FILES['kaamase_prof_photo'] = $files['photo'];

		$done = kaamase_prof_photo_from( $id, 'kaamase_prof_photo' );

		if ( is_wp_error( $done ) ) {
			return kaamase_prof_rest_fail( $done );
		}

		return new WP_REST_Response( array( 'image' => function_exists( 'kaamase_shape_image' ) ? kaamase_shape_image( $id ) : null ), 200 );
	}
}

if ( ! function_exists( 'kaamase_prof_rest_delete' ) ) {
	/**
	 * POST /me/professional/delete
	 *
	 * Deletes the professional profile only. The account, and any worker
	 * or employer profile on it, stay exactly as they are.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_prof_rest_delete( $request ) {

		if ( ! rest_sanitize_boolean( $request->get_param( 'confirm' ) ) ) {
			return kaamase_prof_rest_fail( new WP_Error( 'kaamase_confirm', __( 'Confirm that you want to delete your professional profile.', 'kaamase-core' ), array( 'status' => 400 ) ) );
		}

		kaamase_prof_delete_all( get_current_user_id() );

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'profile' => null,
				'state'   => 'missing',
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_prof_shape_me' ) ) {
	/**
	 * Tell the app whether this account has a professional profile, and
	 * whether it may see everybody else's.
	 *
	 * @since 1.0.0
	 * @param array $me      The account.
	 * @param int   $user_id Account.
	 * @return array
	 */
	function kaamase_prof_shape_me( $me, $user_id ) {

		if ( ! is_array( $me ) ) {
			return $me;
		}

		$id = kaamase_prof_id( (int) $user_id );

		$me['professional_profile']     = $id
			? array(
				'id'         => $id,
				'state'      => kaamase_prof_state( $id ),
				'listed'     => (bool) get_post_meta( $id, KAAMASE_PROF_LISTED_KEY, true ),
				'visibility' => kaamase_prof_is_public( $id ) ? 'public' : 'employers',
				'url'        => (string) get_permalink( $id ),
				'complete'   => kaamase_prof_is_complete( $id ),
			)
			: null;
		$me['can_browse_professionals'] = kaamase_prof_may_browse( (int) $user_id );

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_prof_shape_me', 26, 2 );

if ( ! function_exists( 'kaamase_prof_in_reference' ) ) {
	/**
	 * Give the app the lists the professional profile form uses.
	 *
	 * Added onto the finished reference answer, beside what professional
	 * jobs already add, so the route itself is not touched.
	 *
	 * @since 1.0.0
	 * @param WP_HTTP_Response $response Response.
	 * @param WP_REST_Server   $server   Server.
	 * @param WP_REST_Request  $request  Request.
	 * @return WP_HTTP_Response
	 */
	function kaamase_prof_in_reference( $response, $server, $request ) {

		unset( $server );

		if ( ! defined( 'KAAMASE_REST_NS' ) || '/' . KAAMASE_REST_NS . '/reference' !== $request->get_route() ) {
			return $response;
		}

		if ( ! $response instanceof WP_HTTP_Response ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['trades'] ) ) {
			return $response;
		}

		$lists = array();

		foreach ( kaamase_prof_choices() as $name => $list ) {
			foreach ( $list as $key => $label ) {
				$lists[ $name ][] = array(
					'key'   => (string) $key,
					'label' => $label,
				);
			}
		}

		$data['professional_profile'] = array(
			'statuses'       => $lists['status'],
			'qualifications' => $lists['qualification'],
			'notice_periods' => $lists['notice'],
			'work_where'     => $lists['where'],
			'visibilities'   => $lists['visibility'],
			'max_categories' => KAAMASE_PROF_MAX_CATEGORIES,
			'max_jobs'       => KAAMASE_PROF_MAX_JOBS,
			'max_skills'     => KAAMASE_PROF_MAX_SKILLS,
			'max_languages'  => KAAMASE_PROF_MAX_LANGUAGES,
		);

		$response->set_data( $data );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_prof_in_reference', 12, 3 );


/* ==========================================================================
   6. CONTACT, NOTIFICATIONS AND THE TICK
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_lookups' ) ) {
	/**
	 * How many different people have been given this profile's number.
	 *
	 * People, not lookups. The log records every time the number is shown,
	 * and an employer who opens it again to ring a second time is still
	 * one employer. Covers what the log keeps: the last 200 lookups, and
	 * nothing older than a year.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return int
	 */
	function kaamase_prof_lookups( $post_id ) {

		if ( ! function_exists( 'kaamase_contact_log' ) ) {
			return 0;
		}

		$people = array();

		foreach ( kaamase_contact_log( (int) $post_id, 200 ) as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['user'] ) ) {
				$people[ absint( $entry['user'] ) ] = true;
			}
		}

		return count( $people );
	}
}

if ( ! function_exists( 'kaamase_prof_contact_veto' ) ) {
	/**
	 * Only an account that hires may ask for a professional's number.
	 *
	 * Runs inside the platform's one contact gate, after the checks every
	 * profile gets (signed in, email confirmed, not blocked) and before the
	 * daily count, so a refusal here never costs a lookup. The owner of
	 * the profile never reaches this: the gate lets them through first.
	 *
	 * @since 1.0.0
	 * @param null|WP_Error $veto    Refusal so far.
	 * @param int           $post_id Profile or job being asked about.
	 * @param int           $user_id Who is asking.
	 * @return null|WP_Error
	 */
	function kaamase_prof_contact_veto( $veto, $post_id, $user_id ) {

		if ( is_wp_error( $veto ) || KAAMASE_PROF_TYPE !== get_post_type( (int) $post_id ) ) {
			return $veto;
		}

		if ( kaamase_prof_is_staff( (int) $user_id ) ) {
			return $veto;
		}

		if ( 'publish' !== get_post_status( (int) $post_id ) ) {
			return new WP_Error( 'kaamase_prof_unavailable', __( 'This profile is not available at the moment.', 'kaamase-core' ) );
		}

		if ( ! kaamase_prof_may_browse( (int) $user_id ) ) {
			return new WP_Error(
				'kaamase_prof_employers_only',
				__( 'Only accounts that hire can contact professionals. Add hiring to your account first. It is free and happens straight away.', 'kaamase-core' )
			);
		}

		return $veto;
	}
}
add_filter( 'kaamase_contact_veto', 'kaamase_prof_contact_veto', 10, 3 );

if ( ! function_exists( 'kaamase_prof_push_type' ) ) {
	/**
	 * Give a lookup of a professional profile its own notification type.
	 *
	 * The usual "Somebody has your number" goes out unchanged. Only its
	 * type changes, because the apps already installed open contact_revealed
	 * as a worker profile, and a professional profile there would be a
	 * screen saying it no longer exists.
	 *
	 * @since 1.0.0
	 * @param array $message Title, body and data.
	 * @return array
	 */
	function kaamase_prof_push_type( $message ) {

		if ( ! is_array( $message ) || empty( $message['data'] ) || ! is_array( $message['data'] ) ) {
			return $message;
		}

		$data = $message['data'];

		if ( isset( $data['type'], $data['id'] ) && 'contact_revealed' === $data['type'] && KAAMASE_PROF_TYPE === get_post_type( (int) $data['id'] ) ) {
			$message['data']['type'] = 'pro_contact_revealed';
		}

		return $message;
	}
}
add_filter( 'kaamase_push_message', 'kaamase_prof_push_type' );

if ( ! function_exists( 'kaamase_prof_mark_types' ) ) {
	/**
	 * Watch this profile's name, number and photograph like every other.
	 *
	 * The tick is shown on it, so changing those three here takes the tick
	 * off, as on a worker or employer profile. Not while the profile is
	 * first being made: see kaamase_prof_creating().
	 *
	 * @since 1.0.0
	 * @param string[] $types Watched profile types.
	 * @return string[]
	 */
	function kaamase_prof_mark_types( $types ) {

		$types = (array) $types;

		if ( ! kaamase_prof_creating() && ! in_array( KAAMASE_PROF_TYPE, $types, true ) ) {
			$types[] = KAAMASE_PROF_TYPE;
		}

		return $types;
	}
}
add_filter( 'kaamase_mark_profile_types', 'kaamase_prof_mark_types' );


/* ==========================================================================
   7. THE WEBSITE
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_pages' ) ) {
	/**
	 * The two pages: the list, and your own profile form.
	 *
	 * @since 1.0.0
	 * @param array[] $pages Page definitions.
	 * @return array[]
	 */
	function kaamase_prof_pages( $pages ) {

		$pages['professionals'] = array(
			'title'   => __( 'Professionals', 'kaamase-core' ),
			'slug'    => 'professionals',
			'content' => '[kaamase_professionals]',
		);

		$pages['my_professional'] = array(
			'title'   => __( 'My professional profile', 'kaamase-core' ),
			'slug'    => 'my-professional-profile',
			'content' => '[kaamase_my_professional]',
		);

		return $pages;
	}
}
add_filter( 'kaamase_page_definitions', 'kaamase_prof_pages' );

if ( ! function_exists( 'kaamase_prof_make_pages' ) ) {
	/**
	 * Create the two pages, once.
	 *
	 * A page already at the same address is taken over only when it holds
	 * the right shortcode. Anything else there was made by somebody for
	 * another reason, so a new page is made beside it instead.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_make_pages() {

		$stored  = (array) get_option( 'kaamase_pages', array() );
		$changed = false;

		foreach ( kaamase_prof_pages( array() ) as $name => $page ) {

			if ( ! empty( $stored[ $name ] ) && 'page' === get_post_type( (int) $stored[ $name ] ) ) {
				continue;
			}

			$existing = get_page_by_path( $page['slug'] );

			if ( $existing instanceof WP_Post && false !== strpos( (string) $existing->post_content, $page['content'] ) ) {
				$stored[ $name ] = (int) $existing->ID;
				$changed         = true;
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
				$changed         = true;
			}
		}

		if ( $changed ) {

			update_option( 'kaamase_pages', $stored, true );

			if ( function_exists( 'kaamase_page_url_flush' ) ) {
				kaamase_page_url_flush();
			}
		}
	}
}

if ( ! function_exists( 'kaamase_prof_page_id' ) ) {
	/**
	 * The ID of one of the two pages.
	 *
	 * @since 1.0.0
	 * @param string $name professionals or my_professional.
	 * @return int
	 */
	function kaamase_prof_page_id( $name ) {

		$stored = (array) get_option( 'kaamase_pages', array() );

		return isset( $stored[ $name ] ) ? (int) $stored[ $name ] : 0;
	}
}

if ( ! function_exists( 'kaamase_prof_url' ) ) {
	/**
	 * The address of one of the two pages.
	 *
	 * @since 1.0.0
	 * @param string $name professionals or my_professional.
	 * @return string
	 */
	function kaamase_prof_url( $name ) {

		if ( function_exists( 'kaamase_page_url' ) ) {
			return kaamase_page_url( $name );
		}

		return home_url( 'professionals' === $name ? '/professionals/' : '/my-professional-profile/' );
	}
}

if ( ! function_exists( 'kaamase_prof_private_page' ) ) {
	/**
	 * Keep the profile form out of search engines, like the dashboard.
	 *
	 * @since 1.0.0
	 * @param int[] $ids Pages kept out of search.
	 * @return int[]
	 */
	function kaamase_prof_private_page( $ids ) {

		$page = kaamase_prof_page_id( 'my_professional' );

		if ( $page ) {
			$ids[] = $page;
		}

		return $ids;
	}
}
add_filter( 'kaamase_seo_private_pages', 'kaamase_prof_private_page' );

if ( ! function_exists( 'kaamase_prof_worker_trade_choices' ) ) {
	/**
	 * The trade list for the worker and team forms, without the
	 * professional categories.
	 *
	 * Those categories are for professional jobs and professional
	 * profiles. Anything a worker or team already has ticked is still
	 * offered, so saving an older profile never quietly drops it.
	 *
	 * @since 1.0.0
	 * @param string[]|string $keep Slugs to keep whatever they are.
	 * @return array[] Trade names keyed by slug, grouped by category name.
	 */
	function kaamase_prof_worker_trade_choices( $keep = array() ) {

		$all  = function_exists( 'kaamase_trade_choices' ) ? kaamase_trade_choices() : array();
		$pro  = function_exists( 'kaamase_pro_trade_slugs' ) ? kaamase_pro_trade_slugs() : array();
		$keep = array_map( 'strval', array_filter( (array) $keep ) );

		if ( ! $pro ) {
			return $all;
		}

		$out = array();

		foreach ( $all as $group => $trades ) {

			foreach ( (array) $trades as $slug => $name ) {

				if ( in_array( (string) $slug, $pro, true ) && ! in_array( (string) $slug, $keep, true ) ) {
					continue;
				}

				$out[ $group ][ $slug ] = $name;
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_prof_no_lists' ) ) {
	/**
	 * Never list professional profiles through WordPress's own addresses.
	 *
	 * Any type with pages of its own can also be asked for as a list --
	 * ?post_type=kaamase_professional, the same with a search, or as a
	 * feed -- and the theme would draw every profile it found with its
	 * name, photo and opening words, to anybody, signed in or not. The
	 * only lists of professionals are the ones this file draws, which
	 * apply the rules. So any other front-end listing of them finds
	 * nothing. Single profiles are left to kaamase_prof_guard().
	 *
	 * @since 1.0.0
	 * @param WP_Query $query The query about to run.
	 * @return void
	 */
	function kaamase_prof_no_lists( $query ) {

		if ( is_admin() || ! $query->is_main_query() || $query->is_singular() ) {
			return;
		}

		$types = (array) $query->get( 'post_type' );

		if ( in_array( KAAMASE_PROF_TYPE, $types, true ) ) {
			$query->set( 'post__in', array( 0 ) );
		}
	}
}
add_action( 'pre_get_posts', 'kaamase_prof_no_lists', 99 );

if ( ! function_exists( 'kaamase_prof_no_embed' ) ) {
	/**
	 * No link preview of a profile that is not public.
	 *
	 * WordPress answers anybody who asks for the preview of a page --
	 * name, photograph and the account's display name -- without asking
	 * who they are. Only public profiles get one.
	 *
	 * @since 1.0.0
	 * @param array|false $data Preview data.
	 * @param WP_Post     $post The post.
	 * @return array|false
	 */
	function kaamase_prof_no_embed( $data, $post ) {

		if ( $post instanceof WP_Post && KAAMASE_PROF_TYPE === $post->post_type && ! kaamase_prof_is_public( $post->ID ) ) {
			return false;
		}

		return $data;
	}
}
add_filter( 'oembed_response_data', 'kaamase_prof_no_embed', 99, 2 );

if ( ! function_exists( 'kaamase_prof_refuse' ) ) {
	/**
	 * Take a profile out of the page before anything reads it, when the
	 * person asking may not see it.
	 *
	 * Done on the posts the page's own query found, the moment it found
	 * them, so for everything that runs afterwards -- WordPress, its
	 * feeds, the theme, Rank Math, link previews, the cache -- there
	 * simply was no such page. The head is never given
	 * the name to print in the title, and no photograph reaches a link
	 * preview. Deciding later, once the page is under way, is too late:
	 * Rank Math has already read the profile by then.
	 *
	 * The page WordPress keeps for a photograph follows its profile's rules.
	 *
	 * @since 1.0.0
	 * @param WP_Post[] $posts What the query found.
	 * @param WP_Query  $query The query.
	 * @return WP_Post[]
	 */
	function kaamase_prof_refuse( $posts, $query ) {

		if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || ! $query->is_singular() ) {
			return $posts;
		}

		if ( ! is_array( $posts ) || 1 !== count( $posts ) || ! $posts[0] instanceof WP_Post ) {
			return $posts;
		}

		$found   = $posts[0];
		$profile = null;

		if ( KAAMASE_PROF_TYPE === $found->post_type ) {
			$profile = $found;
		} elseif ( 'attachment' === $found->post_type && $found->post_parent && KAAMASE_PROF_TYPE === get_post_type( (int) $found->post_parent ) ) {
			$profile = get_post( (int) $found->post_parent );
		}

		if ( ! $profile instanceof WP_Post || kaamase_prof_can_view( $profile->ID, get_current_user_id() ) ) {
			return $posts;
		}

		$GLOBALS['kaamase_prof_refused'] = (int) $profile->ID;

		/*
		 * And no guessing. On a page that is not found, WordPress looks
		 * for a public post with a similar address and sends the visitor
		 * there, which here could be the same person's worker profile, or
		 * a stranger's.
		 */
		add_filter( 'redirect_canonical', '__return_false' );
		add_filter( 'do_redirect_guess_404_permalink', '__return_false' );

		return array();
	}
}
add_filter( 'posts_results', 'kaamase_prof_refuse', 5, 2 );

if ( ! function_exists( 'kaamase_prof_guard' ) ) {
	/**
	 * Keep a profile page out of caches and search engines, unless it is
	 * public and live.
	 *
	 * A refused page is already a page that was not found. This makes
	 * sure it is not stored, so a profile made public a minute later is
	 * not hidden behind an old copy. A profile only employers may read is
	 * kept out of search engines and every cache even for them.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_guard() {

		$refused = ! empty( $GLOBALS['kaamase_prof_refused'] );
		$private = is_singular( KAAMASE_PROF_TYPE )
			&& ( ! kaamase_prof_is_public( get_queried_object_id() ) || 'publish' !== get_post_status( get_queried_object_id() ) );

		if ( ! $refused && ! $private ) {
			return;
		}

		if ( $refused && ! is_404() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}

		kaamase_prof_no_store();

		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	}
}
add_action( 'template_redirect', 'kaamase_prof_guard', 1 );

if ( ! function_exists( 'kaamase_prof_list_pages_no_store' ) ) {
	/**
	 * Keep the two pages out of every cache.
	 *
	 * Who is on the list depends on who is looking, and a profile that
	 * stops being public must leave it at once, not when a stored copy
	 * runs out.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_list_pages_no_store() {

		$pages = array_filter( array( kaamase_prof_page_id( 'professionals' ), kaamase_prof_page_id( 'my_professional' ) ) );

		if ( $pages && is_page( $pages ) ) {
			kaamase_prof_no_store();
		}
	}
}
add_action( 'template_redirect', 'kaamase_prof_list_pages_no_store', 2 );

if ( ! function_exists( 'kaamase_prof_template' ) ) {
	/**
	 * Draw profile pages, and the refusal, with the template this plugin
	 * ships, so they do not depend on the theme having one.
	 *
	 * @since 1.0.0
	 * @param string $template The template WordPress chose.
	 * @return string
	 */
	function kaamase_prof_template( $template ) {

		if ( empty( $GLOBALS['kaamase_prof_refused'] ) && ! is_singular( KAAMASE_PROF_TYPE ) ) {
			return $template;
		}

		$ours = KAAMASE_CORE_DIR . 'templates/professional.php';

		return file_exists( $ours ) ? $ours : $template;
	}
}
add_filter( 'template_include', 'kaamase_prof_template', 99 );

if ( ! function_exists( 'kaamase_prof_robots' ) ) {
	/**
	 * Tell search engines to leave every profile alone except public ones.
	 *
	 * @since 1.0.0
	 * @param array $robots Robots directives.
	 * @return array
	 */
	function kaamase_prof_robots( $robots ) {

		if ( ! empty( $GLOBALS['kaamase_prof_refused'] ) || ( is_singular( KAAMASE_PROF_TYPE ) && ! kaamase_prof_is_public( get_queried_object_id() ) ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			unset( $robots['index'], $robots['follow'], $robots['max-image-preview'] );
		}

		return $robots;
	}
}
add_filter( 'wp_robots', 'kaamase_prof_robots', 20 );

if ( ! function_exists( 'kaamase_prof_rank_math_robots' ) ) {
	/**
	 * The same, for Rank Math, which writes its own robots tag. The
	 * profile form page too, which WordPress's own tag already covers.
	 *
	 * @since 1.0.0
	 * @param array $robots Directives keyed by name.
	 * @return array
	 */
	function kaamase_prof_rank_math_robots( $robots ) {

		$form = kaamase_prof_page_id( 'my_professional' );

		if ( ! empty( $GLOBALS['kaamase_prof_refused'] ) || ( $form && is_page( $form ) ) || ( is_singular( KAAMASE_PROF_TYPE ) && ! kaamase_prof_is_public( get_queried_object_id() ) ) ) {
			$robots = is_array( $robots ) ? $robots : array();

			unset( $robots['index'], $robots['follow'] );

			$robots['index']  = 'noindex';
			$robots['follow'] = 'nofollow';
		}

		return $robots;
	}
}
add_filter( 'rank_math/frontend/robots', 'kaamase_prof_rank_math_robots', 20 );

if ( ! function_exists( 'kaamase_prof_no_sitemap' ) ) {
	/**
	 * Keep profiles out of WordPress's own sitemap, whatever else changes.
	 *
	 * @since 1.0.0
	 * @param array $types Post types in the sitemap.
	 * @return array
	 */
	function kaamase_prof_no_sitemap( $types ) {

		unset( $types[ KAAMASE_PROF_TYPE ] );

		return $types;
	}
}
add_filter( 'wp_sitemaps_post_types', 'kaamase_prof_no_sitemap' );

if ( ! function_exists( 'kaamase_prof_no_rank_math_sitemap' ) ) {
	/**
	 * And out of Rank Math's.
	 *
	 * @since 1.0.0
	 * @param bool   $exclude Whether to leave the type out.
	 * @param string $type    Post type.
	 * @return bool
	 */
	function kaamase_prof_no_rank_math_sitemap( $exclude, $type ) {
		return KAAMASE_PROF_TYPE === $type ? true : $exclude;
	}
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'kaamase_prof_no_rank_math_sitemap', 10, 2 );

if ( ! function_exists( 'kaamase_prof_image_sizes' ) ) {
	/**
	 * Make only the avatar sizes of a profile photograph, as for workers.
	 *
	 * @since 1.0.0
	 * @param array $sizes         Sizes about to be made.
	 * @param array $metadata      Unused.
	 * @param int   $attachment_id Attachment.
	 * @return array
	 */
	function kaamase_prof_image_sizes( $sizes, $metadata = array(), $attachment_id = 0 ) {

		unset( $metadata );

		$parent = $attachment_id ? (int) wp_get_post_parent_id( $attachment_id ) : 0;

		if ( $parent && KAAMASE_PROF_TYPE === get_post_type( $parent ) ) {
			unset( $sizes['kaamase-card'], $sizes['kaamase-wide'] );
		}

		return $sizes;
	}
}
add_filter( 'intermediate_image_sizes_advanced', 'kaamase_prof_image_sizes', 21, 3 );

if ( ! function_exists( 'kaamase_prof_form_key' ) ) {
	/**
	 * Where what was typed waits while the form reloads with errors.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_prof_form_key() {
		return 'kaamase_prof_form_' . get_current_user_id();
	}
}

if ( ! function_exists( 'kaamase_prof_from_post' ) ) {
	/**
	 * The website form's fields as the answers kaamase_prof_save() takes.
	 *
	 * Every field is named kp_ something. WordPress reads several plain
	 * words from a posted form as instructions about what page to show,
	 * name and year among them, and a form using those would land
	 * somebody on a page that does not exist.
	 *
	 * @since 1.0.0
	 * @param array $post Posted fields, unslashed.
	 * @return array
	 */
	function kaamase_prof_from_post( $post ) {

		$get = static function ( $key ) use ( $post ) {
			return isset( $post[ $key ] ) && is_scalar( $post[ $key ] ) ? (string) $post[ $key ] : '';
		};

		$jobs = array();

		foreach ( isset( $post['kp_jobs'] ) && is_array( $post['kp_jobs'] ) ? $post['kp_jobs'] : array() as $row ) {

			if ( is_array( $row ) ) {
				$jobs[] = array(
					'title'    => isset( $row['title'] ) && is_scalar( $row['title'] ) ? (string) $row['title'] : '',
					'employer' => isset( $row['employer'] ) && is_scalar( $row['employer'] ) ? (string) $row['employer'] : '',
					'from'     => isset( $row['from'] ) && is_scalar( $row['from'] ) ? (string) $row['from'] : '',
					'to'       => isset( $row['to'] ) && is_scalar( $row['to'] ) ? (string) $row['to'] : '',
				);
			}
		}

		return array(
			'name'          => $get( 'kp_name' ),
			'headline'      => $get( 'kp_headline' ),
			'categories'    => array_values( array_filter( array( $get( 'kp_cat_1' ), $get( 'kp_cat_2' ), $get( 'kp_cat_3' ) ) ) ),
			'district'      => $get( 'kp_district' ),
			'town'          => $get( 'kp_town' ),
			'where'         => $get( 'kp_where' ),
			'status'        => $get( 'kp_status' ),
			'experience'    => $get( 'kp_experience' ),
			'qualification' => $get( 'kp_qualification' ),
			'course'        => $get( 'kp_course' ),
			'institute'     => $get( 'kp_institute' ),
			'passed'        => $get( 'kp_passed' ),
			'salary'        => $get( 'kp_salary' ),
			'notice'        => $get( 'kp_notice' ),
			'about'         => $get( 'kp_about' ),
			'jobs'          => $jobs,
			'skills'        => $get( 'kp_skills' ),
			'languages'     => isset( $post['kp_languages'] ) && is_array( $post['kp_languages'] ) ? array_values( array_filter( $post['kp_languages'], 'is_scalar' ) ) : array(),
			'phone'         => $get( 'kp_phone' ),
			'visibility'    => $get( 'kp_visibility' ),
			'listed'        => ! empty( $post['kp_listed'] ),
		);
	}
}

if ( ! function_exists( 'kaamase_prof_handle_form' ) ) {
	/**
	 * Save the website form, then come back to it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_handle_form() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'save_professional' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_prof_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_prof_nonce'] ) ), 'kaamase_prof_save' )
		) {
			return;
		}

		$user_id = get_current_user_id();
		$back    = kaamase_prof_url( 'my_professional' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		$raw = kaamase_prof_from_post( wp_unslash( $_POST ) );

		set_transient( kaamase_prof_form_key(), array_merge( $raw, kaamase_prof_input( $raw ) ), 15 * MINUTE_IN_SECONDS );

		$result = kaamase_prof_save( $raw, $user_id );

		if ( is_wp_error( $result ) ) {

			$data = $result->get_error_data();

			set_transient(
				kaamase_prof_form_key() . '_err',
				isset( $data['messages'] ) ? (array) $data['messages'] : array( $result->get_error_message() ),
				15 * MINUTE_IN_SECONDS
			);

			wp_safe_redirect( $back );
			exit;
		}

		delete_transient( kaamase_prof_form_key() );
		delete_transient( kaamase_prof_form_key() . '_err' );

		$photo = '';

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked above.
		if ( ! empty( $_POST['kp_remove_photo'] ) ) {

			kaamase_prof_photo_remove( $result );

		} elseif ( ! empty( $_FILES['kp_photo']['name'] ) ) {

			if ( ! kaamase_prof_photo_allowed( $user_id ) ) {
				$photo = __( 'That is a lot of photos in one hour. Please try again later.', 'kaamase-core' );
			} else {

				$done = kaamase_prof_photo_from( $result, 'kp_photo' );

				if ( is_wp_error( $done ) ) {
					$photo = $done->get_error_message();
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' !== $photo ) {
			set_transient( kaamase_prof_form_key() . '_photo', $photo, 15 * MINUTE_IN_SECONDS );
		}

		wp_safe_redirect( add_query_arg( 'saved', '1', $back ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_prof_handle_form' );

if ( ! function_exists( 'kaamase_prof_handle_delete' ) ) {
	/**
	 * Delete the professional profile from the website form.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_handle_delete() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'delete_professional' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_prof_delete_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_prof_delete_nonce'] ) ), 'kaamase_prof_delete' )
		) {
			return;
		}

		$back = kaamase_prof_url( 'my_professional' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Checked above.
		if ( empty( $_POST['kp_confirm_delete'] ) ) {
			set_transient( kaamase_prof_form_key() . '_err', array( __( 'Tick the box to confirm you want to delete your professional profile.', 'kaamase-core' ) ), 15 * MINUTE_IN_SECONDS );
			wp_safe_redirect( $back );
			exit;
		}

		kaamase_prof_delete_all( get_current_user_id() );

		delete_transient( kaamase_prof_form_key() );

		wp_safe_redirect( add_query_arg( 'deleted', '1', $back ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_prof_handle_delete' );

if ( ! function_exists( 'kaamase_prof_select' ) ) {
	/**
	 * One select box.
	 *
	 * @since 1.0.0
	 * @param string $name    Field name.
	 * @param string $id      Element ID.
	 * @param array  $options Value => label.
	 * @param string $current Current value.
	 * @param string $empty   Label for an empty first choice, or '' for none.
	 * @param bool   $needed  Whether it must be answered.
	 * @return string
	 */
	function kaamase_prof_select( $name, $id, $options, $current, $empty = '', $needed = false ) {

		$out = sprintf(
			'<select class="ka-select" id="%1$s" name="%2$s"%3$s>',
			esc_attr( $id ),
			esc_attr( $name ),
			$needed ? ' required' : ''
		);

		if ( '' !== $empty ) {
			$out .= '<option value="">' . esc_html( $empty ) . '</option>';
		}

		foreach ( $options as $value => $label ) {
			$out .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( (string) $value ),
				selected( (string) $current, (string) $value, false ),
				esc_html( $label )
			);
		}

		return $out . '</select>';
	}
}

if ( ! function_exists( 'kaamase_prof_notice' ) ) {
	/**
	 * A notice block in the platform's style.
	 *
	 * @since 1.0.0
	 * @param string $kind   info, warn, ok or error.
	 * @param string $title  Heading.
	 * @param string $body   Sentence, or ''.
	 * @param string $url    Button address, or ''.
	 * @param string $button Button label, or ''.
	 * @return string
	 */
	function kaamase_prof_notice( $kind, $title, $body = '', $url = '', $button = '' ) {

		return sprintf(
			'<div class="ka-notice ka-notice--%1$s ka-mb-4"><div><span class="ka-notice__title">%2$s</span>%3$s%4$s</div></div>',
			esc_attr( $kind ),
			esc_html( $title ),
			'' !== $body ? '<p>' . esc_html( $body ) . '</p>' : '',
			'' !== $url ? '<a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="' . esc_url( $url ) . '">' . esc_html( $button ) . '</a>' : ''
		);
	}
}

if ( ! function_exists( 'kaamase_prof_form' ) ) {
	/**
	 * The professional profile form: [kaamase_my_professional]
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_prof_form() {

		if ( ! is_user_logged_in() ) {
			return kaamase_prof_notice(
				'info',
				__( 'Sign in to make your professional profile', 'kaamase-core' ),
				__( 'Use the account you already have. If you do not have one yet, register free first.', 'kaamase-core' ),
				wp_login_url( kaamase_prof_url( 'my_professional' ) ),
				__( 'Sign in', 'kaamase-core' )
			);
		}

		$user_id = get_current_user_id();
		$id      = kaamase_prof_id( $user_id );
		$typed   = get_transient( kaamase_prof_form_key() );
		$errors  = get_transient( kaamase_prof_form_key() . '_err' );
		$photo   = get_transient( kaamase_prof_form_key() . '_photo' );
		$choices = kaamase_prof_choices();

		// Said once. A visit tomorrow should not open on yesterday's mistakes.
		delete_transient( kaamase_prof_form_key() . '_err' );
		delete_transient( kaamase_prof_form_key() . '_photo' );

		$values = $id ? kaamase_prof_values( $id ) : kaamase_prof_defaults( $user_id );

		if ( is_array( $typed ) && is_array( $errors ) && $errors ) {
			$values = array_merge( $values, kaamase_prof_input( $typed ) );
		}

		delete_transient( kaamase_prof_form_key() );

		$unfinished = $id && ! kaamase_prof_is_complete( $id );

		// Begun at sign-up and never saved: the box starts ticked, as it does for a new profile. See kaamase_prof_save().
		if ( $id && kaamase_prof_never_saved( $id ) && ! ( is_array( $errors ) && $errors ) ) {
			$values['listed'] = true;
		}

		$categories = kaamase_prof_category_names();
		$languages  = kaamase_prof_language_names();
		$picked     = array_values( (array) $values['categories'] );
		$jobs       = array_values( (array) $values['jobs'] );
		$state      = $id ? kaamase_prof_state( $id ) : 'missing';
		$off_google = function_exists( 'kaamase_is_off_google' ) && kaamase_is_off_google( $user_id );

		ob_start();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Only choosing a message.
		if ( ! empty( $_GET['deleted'] ) && ! $id ) {
			echo kaamase_prof_notice( 'ok', __( 'Your professional profile is deleted', 'kaamase-core' ), __( 'Your account and any other profile on it are just as they were.', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! empty( $_GET['saved'] ) && $id && ! $errors ) {

			if ( 'listed' === $state ) {
				echo kaamase_prof_notice( 'ok', __( 'Saved. Employers can see your profile.', 'kaamase-core' ), '', get_permalink( $id ), __( 'See my profile', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( 'waiting_for_email' === $state ) {
				echo kaamase_prof_notice( 'info', __( 'Saved', 'kaamase-core' ), __( 'Your profile is shown to employers as soon as you confirm your email. We sent you a link when you registered.', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo kaamase_prof_notice( 'info', __( 'Saved', 'kaamase-core' ), __( 'Your profile is hidden, so employers cannot see it. Tick "Show my profile" when you want to be found.', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( is_string( $photo ) && '' !== $photo ) {
			echo kaamase_prof_notice( 'warn', __( 'The photo was not added', 'kaamase-core' ), $photo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( is_array( $errors ) && $errors ) {
			echo '<div class="ka-notice ka-notice--error ka-mb-4"><div><span class="ka-notice__title">'
				. esc_html__( 'Please fix this', 'kaamase-core' )
				. '</span><ul>';

			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}

			echo '</ul></div></div>';
		}

		if ( 'on_hold' === $state ) {
			echo kaamase_prof_notice( 'warn', __( 'Your profile is being checked', 'kaamase-core' ), __( 'Kaam Ase is looking at your profile. You can still change it here.', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only choosing a message.
		if ( ! empty( $_GET['welcome'] ) && $unfinished && function_exists( 'kaamase_join_welcome' ) ) {
			echo kaamase_join_welcome( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		} elseif ( $unfinished && 'on_hold' !== $state && ! ( is_array( $errors ) && $errors ) ) {
			echo kaamase_prof_notice( 'info', __( 'Your profile is not finished yet', 'kaamase-core' ), __( 'Fill in the rest below and save, and employers can see it.', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( function_exists( 'kaamase_mark_warning_notice' ) ) {
			echo kaamase_mark_warning_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$hint = function_exists( 'kaamase_mark_field_hint' ) ? kaamase_mark_field_hint() : '';
		?>

		<p class="ka-small">
			<a href="<?php echo esc_url( function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Back to my account', 'kaamase-core' ); ?>
			</a>
			<?php if ( 'listed' === $state ) : ?>
				&middot; <a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php esc_html_e( 'See my profile', 'kaamase-core' ); ?></a>
			<?php endif; ?>
		</p>

		<form class="ka-form ka-stack--lg" method="post" action="" enctype="multipart/form-data">

			<?php wp_nonce_field( 'kaamase_prof_save', 'kaamase_prof_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="save_professional">

			<h2>
				<?php
				echo $id
					? esc_html__( 'My professional profile', 'kaamase-core' )
					: esc_html__( 'Make a professional profile', 'kaamase-core' );
				?>
			</h2>

			<p class="ka-hint">
				<?php esc_html_e( 'For salaried jobs in offices, banks, schools, hospitals, engineering and similar work. It is separate from a worker profile: employers who hire professionals look here. Your login stays the same.', 'kaamase-core' ); ?>
			</p>

			<div class="ka-field">
				<label class="ka-label" for="kp-photo"><?php esc_html_e( 'Photo of yourself', 'kaamase-core' ); ?></label>

				<?php if ( $id && has_post_thumbnail( $id ) ) : ?>
					<div class="ka-cluster ka-mb-4">
						<?php echo function_exists( 'kaamase_avatar' ) ? kaamase_avatar( $id, 'kaamase-avatar', 'ka-avatar--lg' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<label class="ka-check">
							<input type="checkbox" name="kp_remove_photo" value="1">
							<span><?php esc_html_e( 'Remove this photo', 'kaamase-core' ); ?></span>
						</label>
					</div>
				<?php endif; ?>

				<input class="ka-input" type="file" id="kp-photo" name="kp_photo" accept="image/jpeg,image/png,image/webp">
				<p class="ka-hint"><?php esc_html_e( 'A clear photo of your face, as for a job application. We remove the hidden location information phones save inside photos.', 'kaamase-core' ); ?></p>
				<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-name"><?php esc_html_e( 'Your name', 'kaamase-core' ); ?> <span class="ka-label__req">*</span></label>
				<input class="ka-input" type="text" id="kp-name" name="kp_name" maxlength="90" required value="<?php echo esc_attr( $values['name'] ); ?>">
				<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-headline"><?php esc_html_e( 'One line about you', 'kaamase-core' ); ?> <span class="ka-label__req">*</span></label>
				<input class="ka-input" type="text" id="kp-headline" name="kp_headline" maxlength="100" required
					value="<?php echo esc_attr( $values['headline'] ); ?>"
					placeholder="<?php esc_attr_e( 'For example: Accountant with five years in banking', 'kaamase-core' ); ?>">
			</div>

			<fieldset class="ka-field">
				<legend class="ka-label"><?php esc_html_e( 'Kind of work you want', 'kaamase-core' ); ?> <span class="ka-label__req">*</span></legend>
				<p class="ka-hint ka-mb-4"><?php esc_html_e( 'Up to three. Employers look for professionals by these.', 'kaamase-core' ); ?></p>
				<?php
				for ( $n = 1; $n <= KAAMASE_PROF_MAX_CATEGORIES; $n++ ) {
					echo '<div class="ka-mb-4">';
					echo kaamase_prof_select( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'kp_cat_' . $n,
						'kp-cat-' . $n,
						$categories,
						isset( $picked[ $n - 1 ] ) ? $picked[ $n - 1 ] : '',
						1 === $n ? __( 'Choose the main kind of work', 'kaamase-core' ) : __( 'Another kind of work (optional)', 'kaamase-core' ),
						1 === $n
					);
					echo '</div>';
				}
				?>
			</fieldset>

			<div class="ka-field">
				<label class="ka-label" for="kp-status"><?php esc_html_e( 'Right now you are', 'kaamase-core' ); ?></label>
				<?php echo kaamase_prof_select( 'kp_status', 'kp-status', $choices['status'], $values['status'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-experience"><?php esc_html_e( 'Years of work experience', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="number" id="kp-experience" name="kp_experience" min="0" max="50" inputmode="numeric" value="<?php echo esc_attr( (int) $values['experience'] ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-qualification"><?php esc_html_e( 'Highest qualification', 'kaamase-core' ); ?> <span class="ka-label__req">*</span></label>
				<?php echo kaamase_prof_select( 'kp_qualification', 'kp-qualification', $choices['qualification'], $values['qualification'], __( 'Choose one', 'kaamase-core' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-course"><?php esc_html_e( 'Course or subject', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="text" id="kp-course" name="kp_course" maxlength="120" value="<?php echo esc_attr( $values['course'] ); ?>"
					placeholder="<?php esc_attr_e( 'For example: B.Com, or MBA in Finance', 'kaamase-core' ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-institute"><?php esc_html_e( 'College or institute', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="text" id="kp-institute" name="kp_institute" maxlength="120" value="<?php echo esc_attr( $values['institute'] ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-passed"><?php esc_html_e( 'Year passed', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="number" id="kp-passed" name="kp_passed" min="1950" max="<?php echo esc_attr( (int) wp_date( 'Y' ) + 5 ); ?>" inputmode="numeric"
					value="<?php echo $values['passed'] ? esc_attr( (int) $values['passed'] ) : ''; ?>">
			</div>

			<fieldset class="ka-field">
				<legend class="ka-label"><?php esc_html_e( 'Past jobs', 'kaamase-core' ); ?></legend>
				<p class="ka-hint ka-mb-4"><?php esc_html_e( 'Up to five, most recent first. Leave the end year empty if you still work there.', 'kaamase-core' ); ?></p>

				<?php
				$rows = max( 1, min( KAAMASE_PROF_MAX_JOBS, count( $jobs ) + 1 ) );

				for ( $n = 0; $n < KAAMASE_PROF_MAX_JOBS; $n++ ) :

					$job  = isset( $jobs[ $n ] ) ? $jobs[ $n ] : array( 'title' => '', 'employer' => '', 'from' => 0, 'to' => 0 );
					$open = $n < $rows;

					if ( ! $open && $n === $rows ) {
						echo '<details class="ka-mt-4"><summary class="ka-small" style="cursor:pointer;">' . esc_html__( 'Add more past jobs', 'kaamase-core' ) . '</summary>';
					}
					?>
					<div class="ka-card ka-card--flat ka-mt-4 ka-stack--sm">
						<label class="ka-label" for="kp-job-<?php echo (int) $n; ?>-title">
							<?php
							/* translators: %d: number of the past job, from 1 */
							echo esc_html( sprintf( __( 'Job %d: title', 'kaamase-core' ), $n + 1 ) );
							?>
						</label>
						<input class="ka-input" type="text" id="kp-job-<?php echo (int) $n; ?>-title" name="kp_jobs[<?php echo (int) $n; ?>][title]" maxlength="90" value="<?php echo esc_attr( $job['title'] ); ?>">

						<label class="ka-label" for="kp-job-<?php echo (int) $n; ?>-employer"><?php esc_html_e( 'Employer', 'kaamase-core' ); ?></label>
						<input class="ka-input" type="text" id="kp-job-<?php echo (int) $n; ?>-employer" name="kp_jobs[<?php echo (int) $n; ?>][employer]" maxlength="90" value="<?php echo esc_attr( $job['employer'] ); ?>">

						<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
							<div>
								<label class="ka-label" for="kp-job-<?php echo (int) $n; ?>-from"><?php esc_html_e( 'From year', 'kaamase-core' ); ?></label>
								<input class="ka-input" type="number" id="kp-job-<?php echo (int) $n; ?>-from" name="kp_jobs[<?php echo (int) $n; ?>][from]" min="1950" max="<?php echo esc_attr( (int) wp_date( 'Y' ) ); ?>" inputmode="numeric" value="<?php echo $job['from'] ? esc_attr( (int) $job['from'] ) : ''; ?>">
							</div>
							<div>
								<label class="ka-label" for="kp-job-<?php echo (int) $n; ?>-to"><?php esc_html_e( 'To year', 'kaamase-core' ); ?></label>
								<input class="ka-input" type="number" id="kp-job-<?php echo (int) $n; ?>-to" name="kp_jobs[<?php echo (int) $n; ?>][to]" min="1950" max="<?php echo esc_attr( (int) wp_date( 'Y' ) ); ?>" inputmode="numeric" value="<?php echo $job['to'] ? esc_attr( (int) $job['to'] ) : ''; ?>">
							</div>
						</div>
					</div>
					<?php
					if ( ! $open && KAAMASE_PROF_MAX_JOBS - 1 === $n ) {
						echo '</details>';
					}
				endfor;
				?>
			</fieldset>

			<div class="ka-field">
				<label class="ka-label" for="kp-skills"><?php esc_html_e( 'Skills', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="text" id="kp-skills" name="kp_skills" maxlength="700"
					value="<?php echo esc_attr( implode( ', ', array_map( 'strval', (array) $values['skills'] ) ) ); ?>"
					placeholder="<?php esc_attr_e( 'For example: Tally, GST, MS Excel, customer service', 'kaamase-core' ); ?>">
				<p class="ka-hint"><?php esc_html_e( 'Up to fifteen, with a comma between each.', 'kaamase-core' ); ?></p>
			</div>

			<?php if ( $languages ) : ?>
				<fieldset class="ka-field">
					<legend class="ka-label"><?php esc_html_e( 'Languages you speak', 'kaamase-core' ); ?></legend>
					<?php foreach ( $languages as $slug => $name ) : ?>
						<label class="ka-check">
							<input type="checkbox" name="kp_languages[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) $values['languages'], true ) ); ?>>
							<span><?php echo esc_html( $name ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
			<?php endif; ?>

			<div class="ka-field">
				<label class="ka-label" for="kp-salary"><?php esc_html_e( 'Expected salary per month (₹)', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="number" id="kp-salary" name="kp_salary" min="0" max="1000000" step="500" inputmode="numeric"
					value="<?php echo $values['salary'] ? esc_attr( (int) $values['salary'] ) : ''; ?>">
				<p class="ka-hint"><?php esc_html_e( 'Leave empty if you would rather discuss it.', 'kaamase-core' ); ?></p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-notice"><?php esc_html_e( 'You can join', 'kaamase-core' ); ?></label>
				<?php echo kaamase_prof_select( 'kp_notice', 'kp-notice', $choices['notice'], $values['notice'], __( 'Choose one', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-district"><?php esc_html_e( 'District', 'kaamase-core' ); ?> <span class="ka-label__req">*</span></label>
				<?php echo kaamase_prof_select( 'kp_district', 'kp-district', function_exists( 'kaamase_district_choices' ) ? kaamase_district_choices() : array(), $values['district'], __( 'Choose your district', 'kaamase-core' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-town"><?php esc_html_e( 'Town or village', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="text" id="kp-town" name="kp_town" maxlength="60" value="<?php echo esc_attr( $values['town'] ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-where"><?php esc_html_e( 'Where you are willing to work', 'kaamase-core' ); ?></label>
				<?php echo kaamase_prof_select( 'kp_where', 'kp-where', $choices['where'], $values['where'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-about"><?php esc_html_e( 'About you', 'kaamase-core' ); ?></label>
				<textarea class="ka-textarea" id="kp-about" name="kp_about" rows="5" maxlength="2000"><?php echo esc_textarea( $values['about'] ); ?></textarea>
				<p class="ka-hint"><?php esc_html_e( 'What you are good at and the kind of job you want. Please do not write your phone number here: employers reach you through Kaam Ase.', 'kaamase-core' ); ?></p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-phone"><?php esc_html_e( 'Phone number', 'kaamase-core' ); ?> <span class="ka-label__req">*</span></label>
				<input class="ka-input" type="tel" id="kp-phone" name="kp_phone" required inputmode="numeric" maxlength="15" value="<?php echo esc_attr( $values['phone'] ); ?>">
				<p class="ka-hint"><?php esc_html_e( 'Never shown on the site. Employers who hire can ask for it, and you can see who asked.', 'kaamase-core' ); ?></p>
				<?php echo $hint; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<fieldset class="ka-field">
				<legend class="ka-label"><?php esc_html_e( 'Who can see your profile', 'kaamase-core' ); ?></legend>
				<?php foreach ( $choices['visibility'] as $key => $label ) : ?>
					<label class="ka-check">
						<input type="radio" name="kp_visibility" value="<?php echo esc_attr( $key ); ?>" <?php checked( $values['visibility'], $key ); ?>>
						<span><?php echo esc_html( $label ); ?></span>
					</label>
					<?php if ( 'public' === $key && $off_google ) : ?>
						<?php
						/*
						 * The account-wide Google switch wins (off-google.php).
						 * Said under the choice it overrides, and only while that
						 * choice is picked; shown either way without scripts.
						 */
						?>
						<p class="ka-hint" id="kp-google-note">
							<?php
							printf(
								/* translators: %s: link to the account page */
								esc_html__( 'Your account is set to stay off Google, so this profile won\'t appear in Google search. You can change this in %s.', 'kaamase-core' ),
								'<a href="' . esc_url( ( function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : home_url( '/dashboard/' ) ) . '#ka-google' ) . '">' . esc_html__( 'My account', 'kaamase-core' ) . '</a>'
							);
							?>
						</p>
						<script>
						( function () {
							var note = document.getElementById( 'kp-google-note' );
							var form = note.closest( 'form' );
							function show() {
								var picked = document.querySelector( 'input[name="kp_visibility"]:checked' );
								note.hidden = ! picked || 'public' !== picked.value;
							}
							if ( form ) {
								form.addEventListener( 'change', function ( event ) {
									if ( event.target && 'kp_visibility' === event.target.name ) {
										show();
									}
								} );
							}
							show();
						}() );
						</script>
					<?php endif; ?>
				<?php endforeach; ?>
				<p class="ka-hint"><?php esc_html_e( 'If you already have a job and are looking quietly, keep it to employers. Public profiles can be found on Google.', 'kaamase-core' ); ?></p>
			</fieldset>

			<label class="ka-check">
				<input type="checkbox" name="kp_listed" value="1" <?php checked( ! empty( $values['listed'] ) ); ?>>
				<span><?php esc_html_e( 'Show my profile. Untick to hide it without deleting it.', 'kaamase-core' ); ?></span>
			</label>

			<?php if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( $user_id ) ) : ?>
				<p class="ka-hint"><?php esc_html_e( 'Your email is not confirmed yet. Your profile is shown once you tap the link we emailed you.', 'kaamase-core' ); ?></p>
			<?php endif; ?>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit"><?php esc_html_e( 'Save', 'kaamase-core' ); ?></button>

		</form>

		<?php if ( $id ) : ?>
			<details class="ka-card ka-card--flat ka-mt-6">
				<summary class="ka-small ka-mute" style="cursor:pointer;padding:8px 0;"><?php esc_html_e( 'Delete my professional profile', 'kaamase-core' ); ?></summary>
				<form method="post" action="" class="ka-stack ka-mt-4">
					<?php wp_nonce_field( 'kaamase_prof_delete', 'kaamase_prof_delete_nonce' ); ?>
					<input type="hidden" name="kaamase_action" value="delete_professional">
					<p class="ka-small ka-soft"><?php esc_html_e( 'This deletes your professional profile and its photo. Your account, your login and any worker or employer profile stay as they are. It cannot be undone.', 'kaamase-core' ); ?></p>
					<label class="ka-check">
						<input type="checkbox" name="kp_confirm_delete" value="1">
						<span><?php esc_html_e( 'Yes, delete my professional profile', 'kaamase-core' ); ?></span>
					</label>
					<button class="ka-btn ka-btn--ghost" type="submit"><?php esc_html_e( 'Delete it', 'kaamase-core' ); ?></button>
				</form>
			</details>
		<?php endif; ?>

		<?php
		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_my_professional', 'kaamase_prof_form' );

if ( ! function_exists( 'kaamase_prof_status_pill' ) ) {
	/**
	 * Looking, working or fresher, as a pill.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return string
	 */
	function kaamase_prof_status_pill( $post_id ) {

		/*
		 * Short words, because a pill never wraps and sits beside a name
		 * on a phone. Amber rather than red for somebody in work: red on
		 * this site means not available, and they are.
		 */
		$pills = array(
			'looking' => array( 'ka-status--live', __( 'Looking for a job', 'kaamase-core' ) ),
			'working' => array( 'ka-status--leave', __( 'Open to offers', 'kaamase-core' ) ),
			'fresher' => array( 'ka-status--live', __( 'Fresher', 'kaamase-core' ) ),
		);

		$status = (string) kaamase_read_field( $post_id, 'prof_status' );

		if ( ! isset( $pills[ $status ] ) ) {
			return '';
		}

		return sprintf(
			'<span class="ka-status %1$s">%2$s</span>',
			esc_attr( $pills[ $status ][0] ),
			esc_html( $pills[ $status ][1] )
		);
	}
}

if ( ! function_exists( 'kaamase_prof_card' ) ) {
	/**
	 * One profile on the list.
	 *
	 * @since 1.0.0
	 * @param int $post_id Profile.
	 * @return string
	 */
	function kaamase_prof_card( $post_id ) {

		$post_id    = (int) $post_id;
		$url        = get_permalink( $post_id );
		$names      = kaamase_prof_category_names();
		$experience = absint( kaamase_read_field( $post_id, 'prof_experience' ) );
		$salary     = absint( kaamase_read_field( $post_id, 'prof_salary' ) );
		$facts      = array();

		if ( $experience ) {
			/* translators: %s: number of years */
			$facts[] = sprintf( _n( '%s year of experience', '%s years of experience', $experience, 'kaamase-core' ), number_format_i18n( $experience ) );
		} elseif ( 'fresher' !== (string) kaamase_read_field( $post_id, 'prof_status' ) ) {
			$facts[] = __( 'Experience not given', 'kaamase-core' );
		}

		$qualification = kaamase_prof_label( 'qualification', (string) kaamase_read_field( $post_id, 'prof_qualification' ) );

		if ( '' !== $qualification ) {
			$facts[] = preg_replace( '/\s*\(.*\)$/', '', $qualification );
		}

		ob_start();
		?>
		<article class="ka-card ka-card--link ka-worker-card">

			<div class="ka-card__head">
				<a href="<?php echo esc_url( $url ); ?>" class="ka-worker-card__photo" tabindex="-1" aria-hidden="true">
					<?php echo function_exists( 'kaamase_avatar' ) ? kaamase_avatar( $post_id ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>

				<div class="ka-worker-card__id">
					<h3 class="ka-worker-card__name">
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a>
						<?php echo function_exists( 'kaamase_called_badge' ) ? kaamase_called_badge( (int) get_post_field( 'post_author', $post_id ), true ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</h3>

					<?php echo function_exists( 'kaamase_place' ) ? kaamase_place( $post_id ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<p class="ka-small ka-soft ka-mt-4"><?php echo esc_html( (string) kaamase_read_field( $post_id, 'prof_headline' ) ); ?></p>
				</div>
			</div>

			<div class="ka-card__body">
				<span class="ka-cluster">
					<?php foreach ( kaamase_prof_categories_of( $post_id ) as $slug ) : ?>
						<span class="ka-chip"><?php echo esc_html( $names[ $slug ] ); ?></span>
					<?php endforeach; ?>
				</span>

				<div class="ka-cluster ka-mt-4">
					<?php echo kaamase_prof_status_pill( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( $facts ) : ?>
						<span class="ka-small ka-soft"><?php echo esc_html( implode( ' · ', $facts ) ); ?></span>
					<?php endif; ?>
				</div>
			</div>

			<div class="ka-card__foot ka-cluster ka-cluster--between">
				<?php
				if ( $salary && function_exists( 'kaamase_wage' ) ) {
					// One piece, so the amount and "per month" stay together.
					echo '<span>' . kaamase_wage( $salary, 'month' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '<span class="ka-mute ka-small">' . esc_html__( 'Salary to discuss', 'kaamase-core' ) . '</span>';
				}
				?>
				<a class="ka-btn ka-btn--outline ka-btn--sm" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'View profile', 'kaamase-core' ); ?></a>
			</div>

		</article>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_prof_list_shortcode' ) ) {
	/**
	 * The Professionals page: [kaamase_professionals]
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_prof_list_shortcode() {

		$viewer = get_current_user_id();
		$base   = kaamase_prof_url( 'professionals' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- A plain filter form.
		$filters = kaamase_prof_filters(
			array(
				'category' => isset( $_GET['kp_cat'] ) ? wp_unslash( $_GET['kp_cat'] ) : '',
				'district' => isset( $_GET['kp_district'] ) ? wp_unslash( $_GET['kp_district'] ) : '',
				'status'   => isset( $_GET['kp_status'] ) ? wp_unslash( $_GET['kp_status'] ) : '',
				'search'   => isset( $_GET['kp_q'] ) ? wp_unslash( $_GET['kp_q'] ) : '',
				'sort'     => isset( $_GET['kp_sort'] ) ? wp_unslash( $_GET['kp_sort'] ) : '',
			)
		);
		$page    = isset( $_GET['kp_pg'] ) && is_scalar( $_GET['kp_pg'] ) ? min( max( 1, (int) $_GET['kp_pg'] ), 100000 ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$per     = 18;
		$ids     = kaamase_prof_find( $filters, $viewer );
		$total   = count( $ids );
		$pages   = max( 1, (int) ceil( $total / $per ) );
		$page    = min( $page, $pages );
		$shown   = array_slice( $ids, ( $page - 1 ) * $per, $per );
		$refusal = kaamase_prof_browse_refusal( $viewer );
		$choices = kaamase_prof_choices();

		if ( $shown ) {
			update_meta_cache( 'post', $shown );
		}

		ob_start();

		if ( 'signed_out' === $refusal['code'] ) {
			echo kaamase_prof_notice( 'info', __( 'Hiring? Sign in to see everybody', 'kaamase-core' ), $refusal['message'], wp_login_url( $base ), __( 'Sign in', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'unverified' === $refusal['code'] ) {
			echo kaamase_prof_notice( 'warn', __( 'Confirm your email', 'kaamase-core' ), $refusal['message'], function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : '', __( 'Go to my account', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'not_hiring' === $refusal['code'] ) {
			echo kaamase_prof_notice( 'info', __( 'Only the public profiles are shown', 'kaamase-core' ), $refusal['message'], function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'post_job' ) : '', __( 'Add hiring to my account', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $viewer && ! kaamase_prof_id( $viewer ) ) {
			echo '<p class="ka-small ka-soft ka-mb-4">'
				. esc_html__( 'Looking for an office or professional job yourself?', 'kaamase-core' )
				. ' <a href="' . esc_url( kaamase_prof_url( 'my_professional' ) ) . '">' . esc_html__( 'Make your professional profile', 'kaamase-core' ) . '</a></p>';
		}
		?>

		<?php
		/*
		 * Folded away until it is used, like the filters on every other
		 * list. Open it filled the first screen of a phone, so an employer
		 * arriving to see who is there was shown a form instead. Open
		 * whenever something is already searched or narrowed, because then
		 * hiding it makes the results look wrong for no visible reason.
		 *
		 * @since 1.1.2
		 */
		$kp_open = '' !== (string) $filters['search'] || '' !== (string) $filters['category'] || '' !== (string) $filters['district'] || '' !== (string) $filters['status']
			|| ( '' !== (string) $filters['sort'] && 'shuffle' !== (string) $filters['sort'] );
		?>
		<details class="ka-filters-wrap"<?php echo $kp_open ? ' open' : ''; ?>>

			<summary class="ka-filters-toggle">
				<?php echo esc_html( $kp_open ? __( 'Change filters', 'kaamase-core' ) : __( 'Filter these results', 'kaamase-core' ) ); ?>
			</summary>

		<form class="ka-filters ka-card" method="get" action="<?php echo esc_url( $base ); ?>">

			<div class="ka-field">
				<label class="ka-label" for="kp-filter-q"><?php esc_html_e( 'Search', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="search" id="kp-filter-q" name="kp_q" maxlength="80" value="<?php echo esc_attr( $filters['search'] ); ?>"
					placeholder="<?php esc_attr_e( 'Job title, skill or course', 'kaamase-core' ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-filter-cat"><?php esc_html_e( 'Kind of work', 'kaamase-core' ); ?></label>
				<?php echo kaamase_prof_select( 'kp_cat', 'kp-filter-cat', kaamase_prof_category_names(), $filters['category'], __( 'Any kind of work', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-filter-district"><?php esc_html_e( 'District', 'kaamase-core' ); ?></label>
				<?php echo kaamase_prof_select( 'kp_district', 'kp-filter-district', function_exists( 'kaamase_district_choices' ) ? kaamase_district_choices() : array(), $filters['district'], __( 'Anywhere in Nagaland', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-filter-status"><?php esc_html_e( 'Status', 'kaamase-core' ); ?></label>
				<?php echo kaamase_prof_select( 'kp_status', 'kp-filter-status', $choices['status'], $filters['status'], __( 'Anybody', 'kaamase-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="kp-filter-sort"><?php esc_html_e( 'Order', 'kaamase-core' ); ?></label>
				<?php
				echo kaamase_prof_select( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'kp_sort',
					'kp-filter-sort',
					array(
						'shuffle'    => __( 'Mixed, changes daily', 'kaamase-core' ),
						'newest'     => __( 'Newest first', 'kaamase-core' ),
						'experience' => __( 'Most experience first', 'kaamase-core' ),
					),
					$filters['sort']
				);
				?>
			</div>

			<button class="ka-btn ka-btn--primary" type="submit"><?php esc_html_e( 'Show these', 'kaamase-core' ); ?></button>

		</form>

		</details>

		<div class="ka-mt-6">

			<?php if ( ! $shown ) : ?>

				<div class="ka-card ka-card--pad-lg ka-center">
					<h2><?php esc_html_e( 'No professionals here yet', 'kaamase-core' ); ?></h2>
					<p class="ka-soft ka-mt-4">
						<?php esc_html_e( 'Try another kind of work or district, or post the job so professionals can find you.', 'kaamase-core' ); ?>
					</p>
					<?php if ( function_exists( 'kaamase_page_url' ) ) : ?>
						<a class="ka-btn ka-btn--primary ka-mt-6" href="<?php echo esc_url( kaamase_page_url( 'post_pro_job' ) ); ?>"><?php esc_html_e( 'Post a professional job', 'kaamase-core' ); ?></a>
					<?php endif; ?>
				</div>

			<?php else : ?>

				<p class="ka-small ka-mute ka-mb-4">
					<?php
					/* translators: %s: number of professionals */
					echo esc_html( sprintf( _n( '%s professional', '%s professionals', $total, 'kaamase-core' ), number_format_i18n( $total ) ) );
					?>
				</p>

				<div class="ka-grid ka-grid--2 ka-grid--3">
					<?php
					foreach ( $shown as $id ) {
						echo kaamase_prof_card( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>

				<?php if ( $pages > 1 ) : ?>
					<?php
					$keep = array_filter(
						array(
							'kp_cat'      => $filters['category'],
							'kp_district' => $filters['district'],
							'kp_status'   => $filters['status'],
							'kp_q'        => $filters['search'],
							'kp_sort'     => 'shuffle' === $filters['sort'] ? '' : $filters['sort'],
						)
					);
					?>
					<nav class="ka-cluster ka-cluster--between ka-mt-6" aria-label="<?php esc_attr_e( 'Pages', 'kaamase-core' ); ?>">
						<?php if ( $page > 1 ) : ?>
							<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( add_query_arg( array_merge( $keep, array( 'kp_pg' => $page - 1 ) ), $base ) ); ?>"><?php esc_html_e( 'Previous', 'kaamase-core' ); ?></a>
						<?php else : ?>
							<span></span>
						<?php endif; ?>

						<span class="ka-small ka-mute">
							<?php
							/* translators: 1: current page, 2: total pages */
							echo esc_html( sprintf( __( 'Page %1$s of %2$s', 'kaamase-core' ), number_format_i18n( $page ), number_format_i18n( $pages ) ) );
							?>
						</span>

						<?php if ( $page < $pages ) : ?>
							<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( add_query_arg( array_merge( $keep, array( 'kp_pg' => $page + 1 ) ), $base ) ); ?>"><?php esc_html_e( 'Next', 'kaamase-core' ); ?></a>
						<?php else : ?>
							<span></span>
						<?php endif; ?>
					</nav>
				<?php endif; ?>

			<?php endif; ?>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_professionals', 'kaamase_prof_list_shortcode' );

if ( ! function_exists( 'kaamase_prof_dashboard_card' ) ) {
	/**
	 * Professional profiles on the dashboard.
	 *
	 * Everybody is offered their own professional profile. Accounts that
	 * hire are also pointed at the list.
	 *
	 * @since 1.0.0
	 * @param int    $user_id Who is looking.
	 * @param int    $profile Their main profile.
	 * @param string $type    Which kind of profile.
	 * @return void
	 */
	function kaamase_prof_dashboard_card( $user_id, $profile = 0, $type = '' ) {

		unset( $profile, $type );

		$user_id    = (int) $user_id;
		$id         = kaamase_prof_id( $user_id );
		$state      = $id ? kaamase_prof_state( $id ) : 'missing';
		$unfinished = $id && 'on_hold' !== $state && ! kaamase_prof_is_complete( $id );
		$off_google = function_exists( 'kaamase_is_off_google' ) && kaamase_is_off_google( $user_id );

		if ( kaamase_prof_is_public( $id ) ) {
			$listed = $off_google
				? __( 'Anyone with the link can open it. It is kept off Google search, as your account is set.', 'kaamase-core' )
				: __( 'Shown to everyone, including Google search.', 'kaamase-core' );
		} else {
			$listed = __( 'Shown to employers signed in to Kaam Ase.', 'kaamase-core' );
		}

		$states = array(
			'listed'            => $listed,
			'waiting_for_email' => __( 'Shown to employers as soon as you confirm your email.', 'kaamase-core' ),
			'hidden'            => __( 'Hidden. Employers cannot see it.', 'kaamase-core' ),
			'on_hold'           => __( 'Being checked by Kaam Ase.', 'kaamase-core' ),
		);
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">

			<?php if ( 'missing' === $state ) : ?>

				<h2><?php esc_html_e( 'Looking for an office or professional job?', 'kaamase-core' ); ?></h2>
				<p class="ka-small ka-soft ka-mt-4">
					<?php esc_html_e( 'Banks, schools, hospitals, offices and companies look for professionals here. Make a professional profile with your qualification, past jobs and the salary you expect. It is free and separate from your other profile.', 'kaamase-core' ); ?>
				</p>
				<a class="ka-btn ka-btn--outline ka-mt-4" href="<?php echo esc_url( kaamase_prof_url( 'my_professional' ) ); ?>"><?php esc_html_e( 'Make a professional profile', 'kaamase-core' ); ?></a>

			<?php elseif ( $unfinished ) : ?>

				<h2><?php esc_html_e( 'Your professional profile', 'kaamase-core' ); ?></h2>
				<p class="ka-small ka-soft ka-mt-4">
					<?php esc_html_e( 'Not finished yet. Add your headline, qualification and experience, and employers can see it.', 'kaamase-core' ); ?>
				</p>
				<a class="ka-btn ka-btn--primary ka-mt-4" href="<?php echo esc_url( kaamase_prof_url( 'my_professional' ) ); ?>"><?php esc_html_e( 'Finish my profile', 'kaamase-core' ); ?></a>

			<?php else : ?>

				<h2><?php esc_html_e( 'Your professional profile', 'kaamase-core' ); ?></h2>
				<p class="ka-small ka-soft ka-mt-4"><?php echo esc_html( $states[ $state ] ); ?></p>
				<?php
				$looked = kaamase_prof_lookups( $id );

				if ( $looked ) :
					?>
					<p class="ka-small ka-mt-4">
						<?php
						/* translators: %s: number of employers */
						echo esc_html( sprintf( _n( '%s employer has asked for your number.', '%s employers have asked for your number.', $looked, 'kaamase-core' ), number_format_i18n( $looked ) ) );
						?>
					</p>
				<?php endif; ?>
				<div class="ka-cluster ka-mt-4">
					<a class="ka-btn ka-btn--outline" href="<?php echo esc_url( kaamase_prof_url( 'my_professional' ) ); ?>"><?php esc_html_e( 'Edit', 'kaamase-core' ); ?></a>
					<?php if ( 'listed' === $state ) : ?>
						<a class="ka-btn ka-btn--ghost" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php esc_html_e( 'See it', 'kaamase-core' ); ?></a>
					<?php endif; ?>
				</div>

			<?php endif; ?>

			<?php if ( user_can( $user_id, 'create_kaamase_jobs' ) ) : ?>
				<p class="ka-small ka-mt-6">
					<?php esc_html_e( 'Hiring for an office or professional job?', 'kaamase-core' ); ?>
					<a href="<?php echo esc_url( kaamase_prof_url( 'professionals' ) ); ?>"><?php esc_html_e( 'Find professionals', 'kaamase-core' ); ?></a>
				</p>
			<?php endif; ?>

		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_prof_dashboard_card', 16, 3 );


/* ==========================================================================
   8. PRIVACY
   ========================================================================== */

if ( ! function_exists( 'kaamase_prof_register_exporter' ) ) {
	/**
	 * Add professional profiles to WordPress's personal data export.
	 *
	 * @since 1.0.0
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	function kaamase_prof_register_exporter( $exporters ) {

		$exporters['kaamase-professional'] = array(
			'exporter_friendly_name' => __( 'Kaam Ase professional profile', 'kaamase-core' ),
			'callback'               => 'kaamase_prof_export',
		);

		return $exporters;
	}
}
add_filter( 'wp_privacy_personal_data_exporters', 'kaamase_prof_register_exporter' );

if ( ! function_exists( 'kaamase_prof_export' ) ) {
	/**
	 * Everything a professional profile holds about one person.
	 *
	 * @since 1.0.0
	 * @param string $email Email address.
	 * @param int    $page  Unused, the data is small.
	 * @return array
	 */
	function kaamase_prof_export( $email, $page = 1 ) {

		unset( $page );

		$user = get_user_by( 'email', $email );
		$out  = array();

		if ( ! $user ) {
			return array(
				'data' => $out,
				'done' => true,
			);
		}

		$id = kaamase_prof_id( (int) $user->ID );

		if ( $id ) {

			$v     = kaamase_prof_values( $id );
			$names = kaamase_prof_category_names();
			$langs = kaamase_prof_language_names();

			$jobs = array();

			foreach ( $v['jobs'] as $job ) {
				$jobs[] = trim( $job['title'] . ', ' . $job['employer'] . ' (' . ( $job['from'] ? $job['from'] : '?' ) . ' - ' . ( $job['to'] ? $job['to'] : __( 'now', 'kaamase-core' ) ) . ')', ', ' );
			}

			$rows = array(
				__( 'Name', 'kaamase-core' )                     => $v['name'],
				__( 'Headline', 'kaamase-core' )                 => $v['headline'],
				__( 'Kind of work', 'kaamase-core' )             => implode( ', ', array_map( static function ( $slug ) use ( $names ) { return isset( $names[ $slug ] ) ? $names[ $slug ] : $slug; }, $v['categories'] ) ),
				__( 'Status', 'kaamase-core' )                   => kaamase_prof_label( 'status', $v['status'] ),
				__( 'Years of experience', 'kaamase-core' )      => (string) $v['experience'],
				__( 'Highest qualification', 'kaamase-core' )    => kaamase_prof_label( 'qualification', $v['qualification'] ),
				__( 'Course or subject', 'kaamase-core' )        => $v['course'],
				__( 'College or institute', 'kaamase-core' )     => $v['institute'],
				__( 'Year passed', 'kaamase-core' )              => $v['passed'] ? (string) $v['passed'] : '',
				__( 'Past jobs', 'kaamase-core' )                => implode( '; ', $jobs ),
				__( 'Skills', 'kaamase-core' )                   => implode( ', ', $v['skills'] ),
				__( 'Languages', 'kaamase-core' )                => implode( ', ', array_map( static function ( $slug ) use ( $langs ) { return isset( $langs[ $slug ] ) ? $langs[ $slug ] : $slug; }, $v['languages'] ) ),
				__( 'Expected monthly salary', 'kaamase-core' )  => $v['salary'] ? (string) $v['salary'] : '',
				__( 'Can join', 'kaamase-core' )                 => kaamase_prof_label( 'notice', $v['notice'] ),
				__( 'District', 'kaamase-core' )                 => function_exists( 'kaamase_district_name' ) ? (string) kaamase_district_name( $v['district'] ) : $v['district'],
				__( 'Town or village', 'kaamase-core' )          => $v['town'],
				__( 'Willing to work', 'kaamase-core' )          => kaamase_prof_label( 'where', $v['where'] ),
				__( 'About you', 'kaamase-core' )                => $v['about'],
				__( 'Phone number', 'kaamase-core' )             => $v['phone'],
				__( 'Who can see the profile', 'kaamase-core' )  => kaamase_prof_label( 'visibility', $v['visibility'] ),
				__( 'Created', 'kaamase-core' )                  => (string) get_post_field( 'post_date', $id ),
			);

			$data = array();

			foreach ( $rows as $name => $value ) {
				if ( '' !== (string) $value ) {
					$data[] = array(
						'name'  => $name,
						'value' => (string) $value,
					);
				}
			}

			$out[] = array(
				'group_id'    => 'kaamase-professional',
				'group_label' => __( 'Professional profile', 'kaamase-core' ),
				'item_id'     => 'professional-' . $id,
				'data'        => $data,
			);

			if ( function_exists( 'kaamase_contact_log' ) ) {

				foreach ( kaamase_contact_log( $id, 200 ) as $index => $entry ) {

					$who = get_userdata( (int) $entry['user'] );

					$out[] = array(
						'group_id'    => 'kaamase-professional-lookups',
						'group_label' => __( 'Employers who asked for the number on your professional profile', 'kaamase-core' ),
						'item_id'     => 'professional-lookup-' . $index,
						'data'        => array(
							array(
								'name'  => __( 'Who', 'kaamase-core' ),
								'value' => $who ? $who->display_name : __( 'A removed account', 'kaamase-core' ),
							),
							array(
								'name'  => __( 'When', 'kaamase-core' ),
								'value' => wp_date( 'Y-m-d H:i', (int) $entry['time'] ),
							),
						),
					);
				}
			}
		}

		return array(
			'data' => $out,
			'done' => true,
		);
	}
}

if ( ! function_exists( 'kaamase_prof_register_eraser' ) ) {
	/**
	 * Add professional profiles to WordPress's personal data erasure.
	 *
	 * @since 1.0.0
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	function kaamase_prof_register_eraser( $erasers ) {

		$erasers['kaamase-professional'] = array(
			'eraser_friendly_name' => __( 'Kaam Ase professional profile', 'kaamase-core' ),
			'callback'             => 'kaamase_prof_erase',
		);

		return $erasers;
	}
}
add_filter( 'wp_privacy_personal_data_erasers', 'kaamase_prof_register_eraser' );

if ( ! function_exists( 'kaamase_prof_erase' ) ) {
	/**
	 * Delete the professional profile of the person asking to be erased.
	 *
	 * @since 1.0.0
	 * @param string $email Email address.
	 * @param int    $page  Unused.
	 * @return array
	 */
	function kaamase_prof_erase( $email, $page = 1 ) {

		unset( $page );

		$user    = get_user_by( 'email', $email );
		$removed = $user ? kaamase_prof_delete_all( (int) $user->ID ) > 0 : false;

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}

if ( ! function_exists( 'kaamase_prof_on_user_delete' ) ) {
	/**
	 * Delete the profile and its photograph when an account is deleted.
	 *
	 * Every way an account closes ends here -- the website's delete
	 * button, the app's, and wp-admin -- so this is where the photograph
	 * is caught. WordPress would delete the profile on its own and leave
	 * the photograph in the uploads folder.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account being deleted.
	 * @return void
	 */
	function kaamase_prof_on_user_delete( $user_id ) {
		kaamase_prof_delete_all( (int) $user_id );
	}
}
add_action( 'delete_user', 'kaamase_prof_on_user_delete', 5 );

if ( ! function_exists( 'kaamase_prof_on_retention_done' ) ) {
	/**
	 * Delete the profile the moment an account's details are removed for
	 * being quiet too long.
	 *
	 * privacy.php removes the other profiles at that point and marks the
	 * account as done. The mark is what this listens for.
	 *
	 * @since 1.0.0
	 * @param int    $meta_id Unused.
	 * @param int    $user_id Account.
	 * @param string $key     Meta key.
	 * @return void
	 */
	function kaamase_prof_on_retention_done( $meta_id, $user_id, $key ) {

		unset( $meta_id );

		if ( 'kaamase_retention_done' === $key ) {
			kaamase_prof_delete_all( (int) $user_id );
		}
	}
}
add_action( 'added_user_meta', 'kaamase_prof_on_retention_done', 10, 3 );
add_action( 'updated_user_meta', 'kaamase_prof_on_retention_done', 10, 3 );

if ( ! function_exists( 'kaamase_prof_retention' ) ) {
	/**
	 * Hide the profiles of accounts that went quiet, on the platform's
	 * own timetable.
	 *
	 * The same stage every other profile is hidden at, read from the same
	 * place. Signing in again brings the profile back. Any profile still
	 * held by an account whose details were removed is deleted here too.
	 *
	 * Every listed profile is looked at, in one query for the profiles
	 * and one for their owners, so none is skipped however many there are.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_prof_retention() {

		if ( ! function_exists( 'kaamase_retention_stages' ) ) {
			return;
		}

		$stages = kaamase_retention_stages();
		$hide   = isset( $stages['unpublish'] ) ? (int) $stages['unpublish'] : 0;

		if ( $hide < 1 ) {
			return;
		}

		$ids = get_posts(
			array(
				'post_type'      => KAAMASE_PROF_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $ids ) ) {
			return;
		}

		_prime_post_caches( $ids, false, false );

		$owners = array();

		foreach ( $ids as $id ) {
			$owners[ (int) $id ] = (int) get_post_field( 'post_author', $id );
		}

		update_meta_cache( 'user', array_values( array_unique( array_filter( $owners ) ) ) );

		foreach ( $owners as $id => $owner ) {

			if ( ! $owner ) {
				continue;
			}

			if ( get_user_meta( $owner, 'kaamase_retention_done', true ) ) {
				kaamase_prof_delete_post( $id );
				continue;
			}

			$last = (int) get_user_meta( $owner, 'kaamase_last_active', true );

			if ( ! $last || 'publish' !== get_post_status( $id ) || ( time() - $last ) < $hide * DAY_IN_SECONDS ) {
				continue;
			}

			update_post_meta( $id, KAAMASE_PROF_DORMANT_KEY, time() );

			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => 'draft',
				)
			);

			kaamase_prof_purge( $id );
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_prof_retention' );

if ( ! function_exists( 'kaamase_prof_on_login' ) ) {
	/**
	 * Bring back a profile hidden for being quiet, when its owner returns.
	 *
	 * @since 1.0.0
	 * @param string  $login Username.
	 * @param WP_User $user  The account.
	 * @return void
	 */
	function kaamase_prof_on_login( $login, $user ) {

		unset( $login );

		if ( ! $user instanceof WP_User ) {
			return;
		}

		$id = kaamase_prof_id( (int) $user->ID );

		if ( ! $id || ! get_post_meta( $id, KAAMASE_PROF_DORMANT_KEY, true ) ) {
			return;
		}

		delete_post_meta( $id, KAAMASE_PROF_DORMANT_KEY );

		if ( 'draft' !== get_post_status( $id ) || ! get_post_meta( $id, KAAMASE_PROF_LISTED_KEY, true ) ) {
			return;
		}

		if ( function_exists( 'kaamase_user_is_verified' ) && ! kaamase_user_is_verified( (int) $user->ID ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
	}
}
add_action( 'wp_login', 'kaamase_prof_on_login', 10, 2 );
