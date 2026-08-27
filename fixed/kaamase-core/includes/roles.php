<?php
/**
 * Roles and capabilities.
 *
 * Three roles: Worker, Employer and Field Agent. None of them is
 * Subscriber, Contributor or anything else WordPress ships with.
 *
 * Why not reuse Subscriber
 * ------------------------
 * Because Subscriber means nothing here. A worker needs to create and
 * edit exactly one worker profile and one team, and nothing else. An
 * employer needs to post jobs and edit their own company profile, and
 * nothing else. Bending a built in role to fit either of those means
 * granting capabilities that do more than intended, and the extra always
 * turns out to matter later.
 *
 * The Field Agent role
 * --------------------
 * This one is not in the original plan and it is the most important role
 * on the platform in the first months.
 *
 * The launch plan involves somebody walking to a labour point in Dimapur
 * and registering workers by hand, because most of them will not do it
 * themselves. That person needs to create and edit worker profiles on
 * behalf of other people, and they must be able to do it without being
 * an administrator, because an administrator can install plugins, read
 * every phone number on the site and delete the database.
 *
 * A field agent can register and edit workers, and can mark a profile
 * verified after meeting the person. They cannot touch settings, cannot
 * see employer contact details, and cannot delete anything.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. CAPABILITY SETS
   ========================================================================== */

if ( ! function_exists( 'kaamase_own_caps' ) ) {
	/**
	 * Capabilities for managing one's own posts of a type.
	 *
	 * Deliberately excludes every capability containing others or
	 * private. Those are what separate a user from a moderator.
	 *
	 * @since 1.0.0
	 * @param string $plural Plural capability name, for example kaamase_workers.
	 * @return array Capabilities mapped to true.
	 */
	function kaamase_own_caps( $plural ) {
		return array(
			'create_' . $plural           => true,
			'edit_' . $plural             => true,
			'edit_published_' . $plural   => true,
			'publish_' . $plural          => true,
			'delete_' . $plural           => true,
			'delete_published_' . $plural => true,
		);
	}
}

if ( ! function_exists( 'kaamase_moderator_caps' ) ) {
	/**
	 * Capabilities for managing everybody's posts of a type.
	 *
	 * @since 1.0.0
	 * @param string $plural Plural capability name.
	 * @return array Capabilities mapped to true.
	 */
	function kaamase_moderator_caps( $plural ) {
		return array_merge(
			kaamase_own_caps( $plural ),
			array(
				'edit_others_' . $plural   => true,
				'read_private_' . $plural  => true,
				'edit_private_' . $plural  => true,
			)
		);
	}
}

if ( ! function_exists( 'kaamase_admin_caps' ) ) {
	/**
	 * Every capability for a post type.
	 *
	 * @since 1.0.0
	 * @param string $plural Plural capability name.
	 * @return array Capabilities mapped to true.
	 */
	function kaamase_admin_caps( $plural ) {
		return array_merge(
			kaamase_moderator_caps( $plural ),
			array(
				'delete_others_' . $plural  => true,
				'delete_private_' . $plural => true,
			)
		);
	}
}


/* ==========================================================================
   2. THE ROLES
   ========================================================================== */

if ( ! function_exists( 'kaamase_role_definitions' ) ) {
	/**
	 * All three roles and what each may do.
	 *
	 * @since 1.0.0
	 * @return array[] Role definitions keyed by role name.
	 */
	function kaamase_role_definitions() {

		$roles = array(

			/*
			 * Worker.
			 *
			 * Owns one profile and optionally one team they lead. Can
			 * upload photographs of themselves and their past work.
			 * Cannot see anybody else's contact details, cannot reach
			 * wp-admin, cannot create trades or districts.
			 */
			'kaamase_worker' => array(
				'label' => __( 'Worker', 'kaamase-core' ),
				'caps'  => array_merge(
					array(
						'read'                  => true,
						'upload_files'          => true,
						'kaamase_assign_terms'  => true,
					),
					kaamase_own_caps( 'kaamase_workers' ),
					kaamase_own_caps( 'kaamase_gangs' )
				),
			),

			/*
			 * Employer.
			 *
			 * Covers the individual building a house, the contractor
			 * with three sites running and the registered company. One
			 * role rather than three, because the difference between
			 * them is a field on their profile, not a difference in what
			 * they are allowed to do.
			 */
			'kaamase_employer' => array(
				'label' => __( 'Employer', 'kaamase-core' ),
				'caps'  => array_merge(
					array(
						'read'                 => true,
						'upload_files'         => true,
						'kaamase_assign_terms' => true,
					),
					kaamase_own_caps( 'kaamase_employers' ),
					kaamase_own_caps( 'kaamase_jobs' )
				),
			),

			/*
			 * Field agent.
			 *
			 * Registers workers in person. Can edit any worker or team
			 * and mark them verified, because verification here means an
			 * agent physically met the person.
			 *
			 * Cannot delete anything, cannot touch employers or jobs,
			 * cannot reach settings. If an agent's account is
			 * compromised, the damage is bad data, not lost data and not
			 * a harvested contact list.
			 */
			'kaamase_agent' => array(
				'label' => __( 'Field agent', 'kaamase-core' ),
				'caps'  => array_merge(
					array(
						'read'                   => true,
						'upload_files'           => true,
						'kaamase_assign_terms'   => true,
						'kaamase_verify_workers' => true,
					),
					kaamase_moderator_caps( 'kaamase_workers' ),
					kaamase_moderator_caps( 'kaamase_gangs' )
				),
			),
		);

		/**
		 * Filter the role definitions.
		 *
		 * @since 1.0.0
		 * @param array[] $roles Role definitions keyed by role name.
		 */
		return (array) apply_filters( 'kaamase_role_definitions', $roles );
	}
}

if ( ! function_exists( 'kaamase_add_roles' ) ) {
	/**
	 * Create or refresh the roles.
	 *
	 * Safe to call repeatedly. Existing roles are removed and rebuilt so
	 * that a capability added in a later version actually reaches users
	 * who already have the role. WordPress stores roles in the database,
	 * so simply editing the definitions above changes nothing on a site
	 * that is already running.
	 *
	 * install.php calls this on activation and after a schema bump.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_add_roles() {

		foreach ( kaamase_role_definitions() as $role => $definition ) {

			remove_role( $role );
			add_role( $role, $definition['label'], $definition['caps'] );
		}

		kaamase_grant_admin_caps();
	}
}

if ( ! function_exists( 'kaamase_grant_admin_caps' ) ) {
	/**
	 * Give administrators every Kaam Ase capability.
	 *
	 * Custom post types with custom capability types are invisible to an
	 * administrator until this runs. Forgetting it produces the most
	 * confusing possible symptom: the plugin activates, the menus appear,
	 * and every screen says you are not allowed to edit this.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_grant_admin_caps() {

		$admin = get_role( 'administrator' );

		if ( ! $admin ) {
			return;
		}

		$caps = array_merge(
			array(
				'kaamase_assign_terms'   => true,
				'kaamase_verify_workers' => true,
				'kaamase_manage'         => true,
			),
			kaamase_admin_caps( 'kaamase_workers' ),
			kaamase_admin_caps( 'kaamase_gangs' ),
			kaamase_admin_caps( 'kaamase_employers' ),
			kaamase_admin_caps( 'kaamase_jobs' )
		);

		foreach ( array_keys( $caps ) as $cap ) {
			$admin->add_cap( $cap );
		}

		// Editors moderate content but never reach plugin settings.
		$editor = get_role( 'editor' );

		if ( $editor ) {

			$editor_caps = array_merge(
				array(
					'kaamase_assign_terms'   => true,
					'kaamase_verify_workers' => true,
				),
				kaamase_moderator_caps( 'kaamase_workers' ),
				kaamase_moderator_caps( 'kaamase_gangs' ),
				kaamase_moderator_caps( 'kaamase_employers' ),
				kaamase_moderator_caps( 'kaamase_jobs' )
			);

			foreach ( array_keys( $editor_caps ) as $cap ) {
				$editor->add_cap( $cap );
			}
		}
	}
}

if ( ! function_exists( 'kaamase_remove_roles' ) ) {
	/**
	 * Delete the roles.
	 *
	 * Called from uninstall.php only. Never on deactivation. Removing a
	 * role strands every user who holds it with no capabilities at all,
	 * which locks them out of their own profile.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_remove_roles() {

		foreach ( array_keys( kaamase_role_definitions() ) as $role ) {
			remove_role( $role );
		}
	}
}


/* ==========================================================================
   3. WHO IS THIS
   ========================================================================== */

if ( ! function_exists( 'kaamase_get_user_type' ) ) {
	/**
	 * Which kind of user an account is.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return string worker, employer, agent, staff or guest.
	 */
	function kaamase_get_user_type( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return 'guest';
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return 'guest';
		}

		$roles = (array) $user->roles;

		/*
		 * Which side is the main one, decided by the profile they
		 * registered with rather than by the order the roles happen to
		 * be listed in below.
		 *
		 * This matters now that an account can hold both. The checks
		 * that follow test worker first, so an employer who added the
		 * working side silently became a worker: the type said worker
		 * while kaamase_profile_id still pointed at their employer
		 * profile, and every screen that trusted the pair to agree got
		 * one of each. On the phone that is a worker form reading fields
		 * off an employer profile, which is a crash rather than a wrong
		 * label, and on the website it is a form that will not save.
		 *
		 * Registration writes kaamase_profile_id once and nothing moves
		 * it, so it is the stable answer. Adding a side adds a side; it
		 * does not turn somebody into a different kind of member.
		 */
		$primary = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( $primary ) {

			$primary_type = get_post_type( $primary );

			if ( 'kaamase_employer' === $primary_type ) {
				return 'employer';
			}

			if ( 'kaamase_worker' === $primary_type ) {
				return 'worker';
			}
		}

		if ( in_array( 'kaamase_worker', $roles, true ) ) {
			return 'worker';
		}

		if ( in_array( 'kaamase_employer', $roles, true ) ) {
			return 'employer';
		}

		if ( in_array( 'kaamase_agent', $roles, true ) ) {
			return 'agent';
		}

		if ( user_can( $user, 'edit_posts' ) ) {
			return 'staff';
		}

		return 'guest';
	}
}

/**
 * Tell the theme what kind of user is looking.
 *
 * The theme cannot know this on its own. It hooks this filter to choose
 * the tab bar and the dashboard destination.
 *
 * @since 1.0.0
 * @param string $type Type detected by the theme.
 * @return string Type from the actual role.
 */
function kaamase_filter_user_type( $type ) {

	unset( $type );

	return kaamase_get_user_type();
}
add_filter( 'kaamase_user_type', 'kaamase_filter_user_type' );


/* ==========================================================================
   4. HARDENING

   Two holes that open the moment upload_files is granted to ordinary
   users, and neither is obvious.
   ========================================================================== */

/**
 * Show each user only their own uploads.
 *
 * upload_files is needed so a worker can add a profile photograph. It
 * also, by default, lets that worker list every file ever uploaded to
 * the site through the REST media endpoint. Photographs of other
 * workers, other people's children in a portfolio shot, anything an
 * admin uploaded.
 *
 * This restricts the media library to the user's own attachments for
 * anyone who is not a moderator.
 *
 * @since 1.0.0
 * @param WP_Query $query The query being run.
 * @return void
 */
function kaamase_restrict_media_library( $query ) {

	if ( ! is_user_logged_in() ) {
		return;
	}

	/*
	 * The media library and the API only.
	 *
	 * This ran on every query on the site, front end included, and
	 * forced author = you onto anything asking for attachments. A
	 * signed in worker opening somebody else's job photos was quietly
	 * shown only the ones they had uploaded themselves, which reads as
	 * a gallery that has lost its pictures.
	 *
	 * The hole being closed here is the media library and the REST
	 * media endpoint, so those are what it now covers. A query that
	 * names its parent is fetching one post's attachments and is not
	 * an enumeration of the library.
	 */
	if ( ! is_admin() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( $query->get( 'post_parent' ) ) {
		return;
	}

	if ( 'attachment' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( current_user_can( 'edit_others_kaamase_workers' ) ) {
		return;
	}

	$query->set( 'author', get_current_user_id() );
}
add_action( 'pre_get_posts', 'kaamase_restrict_media_library' );

/**
 * Stop a user granting themselves a role.
 *
 * Registration decides the role. Nothing a user submits afterwards may
 * change it. Without this, a crafted profile update that includes a role
 * field is worth trying, and eventually somebody will.
 *
 * @since 1.0.0
 * @param int $user_id User being updated.
 * @return void
 */
function kaamase_lock_roles( $user_id ) {

	if ( current_user_can( 'promote_users' ) ) {
		return;
	}

	if ( get_current_user_id() !== absint( $user_id ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['role'] ) || isset( $_POST['kaamase_role'] ) ) {

		wp_die(
			esc_html__( 'You cannot change your own account type. Contact Kaam Ase if you need to.', 'kaamase-core' ),
			esc_html__( 'Not allowed', 'kaamase-core' ),
			array( 'response' => 403 )
		);
	}
}
add_action( 'personal_options_update', 'kaamase_lock_roles' );

/**
 * Keep Kaam Ase roles out of the WordPress registration role dropdown.
 *
 * These roles are assigned by our own registration flow, which also
 * creates the matching profile post. A user given the role through the
 * admin without a profile is an account in a broken half state.
 *
 * @since 1.0.0
 * @param array $roles Roles offered in the admin.
 * @return array Filtered roles.
 */
function kaamase_hide_roles_from_dropdown( $roles ) {

	if ( current_user_can( 'manage_options' ) ) {
		return $roles;
	}

	foreach ( array_keys( kaamase_role_definitions() ) as $role ) {
		unset( $roles[ $role ] );
	}

	return $roles;
}
add_filter( 'editable_roles', 'kaamase_hide_roles_from_dropdown' );