<?php
/**
 * Professional jobs.
 *
 * Office, bank, school, hospital, engineering and other salaried career
 * jobs, posted beside everyday work in the same jobs list.
 *
 * Why a separate kind of job
 * --------------------------
 * A mason is hired today, by trade and district, with a phone call. An
 * accounts executive is hired over weeks, on qualifications and a
 * monthly salary. Bigger employers were not posting here because the
 * form only asked the first set of questions. This file adds the second
 * set without changing the first.
 *
 * What makes a job professional
 * -----------------------------
 * Its category. Every professional category sits under one heading,
 * Professional jobs, in the same trade list everything else uses. A job
 * filed under one of them is professional however it was posted: this
 * form, the everyday form or an app that has never heard of any of
 * this. A mason job cannot become professional by accident and a bank
 * job cannot quietly be treated as daily work.
 *
 * Using the trade list rather than a list of its own is deliberate. The
 * apps already in people's phones read that list, show its headings and
 * filter by it, so professional jobs appear in them properly on the day
 * this goes live, with no app update. Filtering by the heading,
 * trade=professional-jobs, returns every professional job, because a
 * heading includes everything under it.
 *
 * The rules that differ
 * ---------------------
 *   - Open until a last date to apply the employer picks, up to 90 days,
 *     rather than 21 days. Every 30 days the employer is asked whether
 *     they are still hiring.
 *   - No urgent. Professional hiring is never today-or-tomorrow, and the
 *     tag means something only while it is rare.
 *   - A monthly salary range, a job type, a work mode, the qualification
 *     and the experience wanted, all of which also go to Google.
 *   - Applications by phone or by email.
 *   - The first professional job from every employer is checked by a
 *     person, even an employer whose everyday jobs go straight up. Fake
 *     company offers that end in a registration fee are the commonest
 *     job fraud in India, and they are dressed as exactly this kind of
 *     job.
 *
 * What it leaves alone
 * --------------------
 * Everyday jobs, their 21 days, their form and their app routes. A
 * closed professional job stays readable at its address like any other,
 * which keeps what it earned in search.
 *
 * @package KaamaseCore
 * @version 1.1.1
 * @since   1.0.0
 *
 * Changelog
 *   1.1.0  The professional pages (/trade/professional-jobs/ and each
 *          category) get job filters: district, job type, the reader's
 *          qualification, a lowest monthly salary and the order. They
 *          used to show the worker filters every trade page has (free
 *          for work now, vouched only), which mean nothing on a list of
 *          office jobs. The heading page also lists the categories that
 *          have open jobs.
 *   1.1.1  The form no longer repeats "Post a professional job" under the
 *          page's own title of the same words. Editing a job still says
 *          "Edit this professional job".
 */

defined( 'ABSPATH' ) || exit;


/** The heading every professional category sits under. */
define( 'KAAMASE_PRO_GROUP', 'professional-jobs' );

/** Bumped when the category list changes, so new ones are created once. */
define( 'KAAMASE_PRO_CATEGORIES_VERSION', 1 );

/** The latest a last date to apply can be, in days from today. */
define( 'KAAMASE_PRO_MAX_DAYS', 90 );

/** How long a professional job runs when nobody picked a date. */
define( 'KAAMASE_PRO_DEFAULT_DAYS', 90 );

/** The last date the form suggests, in days from today. */
define( 'KAAMASE_PRO_SUGGEST_DAYS', 30 );

/** How often an employer is asked whether they are still hiring. */
define( 'KAAMASE_PRO_ASK_EVERY_DAYS', 30 );

/** Which of those questions has been asked: the posting's time, a colon, and the count. */
define( 'KAAMASE_PRO_ASKED_KEY', '_kaamase_pro_asked' );


/* ==========================================================================
   1. THE CATEGORIES
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_categories' ) ) {
	/**
	 * Every professional category, with the job titles it takes in.
	 *
	 * Wide on purpose, as the everyday trades are, so an employer can
	 * see their own job title listed before they pick. The words are
	 * written onto each category and reach the app beside its name.
	 *
	 * @since 1.0.0
	 * @return array[] Keyed by slug: name, covers.
	 */
	function kaamase_pro_categories() {

		return array(
			'banking'                 => array( __( 'Banking', 'kaamase-core' ), __( 'Bank officer, clerk, probationary officer, relationship manager, loan officer, branch staff.', 'kaamase-core' ) ),
			'insurance'               => array( __( 'Insurance', 'kaamase-core' ), __( 'Insurance advisor, development officer, claims, underwriting, policy servicing.', 'kaamase-core' ) ),
			'microfinance'            => array( __( 'Microfinance and cooperatives', 'kaamase-core' ), __( 'Field officer, credit officer, loan recovery, self help groups, cooperative society staff.', 'kaamase-core' ) ),
			'accounts-finance'        => array( __( 'Accounts and finance', 'kaamase-core' ), __( 'Accountant, accounts executive, finance officer, finance manager, Tally, GST, billing.', 'kaamase-core' ) ),
			'audit-tax'               => array( __( 'Audit, tax and CA firms', 'kaamase-core' ), __( 'Chartered accountant, CA articleship, audit assistant, tax consultant, GST practitioner, company secretary.', 'kaamase-core' ) ),
			'sales-business'          => array( __( 'Sales and business development', 'kaamase-core' ), __( 'Sales officer, sales manager, business development, area sales, field sales, dealer sales.', 'kaamase-core' ) ),
			'marketing'               => array( __( 'Marketing and advertising', 'kaamase-core' ), __( 'Marketing executive, brand manager, advertising, digital marketing manager, market research.', 'kaamase-core' ) ),
			'customer-support'        => array( __( 'Customer support and call centre', 'kaamase-core' ), __( 'Customer care, call centre, BPO, telecaller, helpdesk, client servicing.', 'kaamase-core' ) ),
			'administration'          => array( __( 'Administration and office management', 'kaamase-core' ), __( 'Admin officer, office manager, executive assistant, secretary, front office executive.', 'kaamase-core' ) ),
			'human-resources'         => array( __( 'Human resources (HR)', 'kaamase-core' ), __( 'HR executive, HR manager, recruiter, payroll, training and development.', 'kaamase-core' ) ),
			'management'              => array( __( 'Management and leadership', 'kaamase-core' ), __( 'Manager, general manager, branch head, centre head, director, CEO.', 'kaamase-core' ) ),
			'operations'              => array( __( 'Operations', 'kaamase-core' ), __( 'Operations executive, operations manager, branch operations, back office, process coordinator.', 'kaamase-core' ) ),
			'supply-chain'            => array( __( 'Procurement, logistics and supply chain', 'kaamase-core' ), __( 'Purchase officer, procurement, inventory manager, logistics coordinator, warehouse manager.', 'kaamase-core' ) ),
			'retail-management'       => array( __( 'Retail and store management', 'kaamase-core' ), __( 'Store manager, showroom manager, retail supervisor, merchandiser.', 'kaamase-core' ) ),
			'real-estate'             => array( __( 'Real estate', 'kaamase-core' ), __( 'Property consultant, real estate sales, leasing, property manager.', 'kaamase-core' ) ),
			'software-development'    => array( __( 'Software and IT development', 'kaamase-core' ), __( 'Software engineer, web developer, app developer, programmer, software tester.', 'kaamase-core' ) ),
			'it-support'              => array( __( 'IT support and networks', 'kaamase-core' ), __( 'System administrator, network engineer, IT support, IT officer, cyber security.', 'kaamase-core' ) ),
			'data-analytics'          => array( __( 'Data and analytics', 'kaamase-core' ), __( 'Data analyst, MIS executive, business analyst, statistics, reporting.', 'kaamase-core' ) ),
			'design-creative'         => array( __( 'Design and creative', 'kaamase-core' ), __( 'Graphic designer, UI and UX designer, illustrator, animator, video producer.', 'kaamase-core' ) ),
			'telecom'                 => array( __( 'Telecom', 'kaamase-core' ), __( 'Telecom engineer, network operations, telecom supervisor, telecom sales.', 'kaamase-core' ) ),
			'journalism-writing'      => array( __( 'Journalism and writing', 'kaamase-core' ), __( 'Journalist, reporter, editor, content writer, copywriter, translator.', 'kaamase-core' ) ),
			'public-relations'        => array( __( 'Public relations and communications', 'kaamase-core' ), __( 'PR officer, communications officer, media relations, social media for organisations.', 'kaamase-core' ) ),
			'arts-entertainment'      => array( __( 'Arts, music and entertainment', 'kaamase-core' ), __( 'Musician, music director, artist, event manager, performer, producer.', 'kaamase-core' ) ),
			'school-teaching'         => array( __( 'School teaching', 'kaamase-core' ), __( 'School teacher, TGT, PGT, primary teacher, B.Ed, subject teacher, special educator.', 'kaamase-core' ) ),
			'higher-education'        => array( __( 'College and university', 'kaamase-core' ), __( 'Lecturer, assistant professor, professor, research scholar, college staff.', 'kaamase-core' ) ),
			'education-management'    => array( __( 'Education management and training', 'kaamase-core' ), __( 'Principal, academic coordinator, trainer, career counsellor, education officer, coaching centre manager.', 'kaamase-core' ) ),
			'library-information'     => array( __( 'Library and information', 'kaamase-core' ), __( 'Librarian, library assistant, archivist, documentation officer.', 'kaamase-core' ) ),
			'doctors'                 => array( __( 'Doctors and medical officers', 'kaamase-core' ), __( 'MBBS doctor, medical officer, specialist, surgeon, dentist, AYUSH doctor.', 'kaamase-core' ) ),
			'nursing'                 => array( __( 'Nursing', 'kaamase-core' ), __( 'Staff nurse, GNM, B.Sc nursing, ANM, nursing tutor, nursing superintendent.', 'kaamase-core' ) ),
			'pharmacy'                => array( __( 'Pharmacy', 'kaamase-core' ), __( 'Pharmacist, D.Pharm, B.Pharm, hospital pharmacy, drug store manager.', 'kaamase-core' ) ),
			'medical-representative'  => array( __( 'Medical representative', 'kaamase-core' ), __( 'Medical representative, MR, pharma sales, area business manager.', 'kaamase-core' ) ),
			'diagnostics'             => array( __( 'Lab, radiology and diagnostics', 'kaamase-core' ), __( 'Medical lab technologist, MLT, radiographer, X-ray, sonography, pathology lab.', 'kaamase-core' ) ),
			'allied-health'           => array( __( 'Therapy and allied health', 'kaamase-core' ), __( 'Physiotherapist, psychologist, counsellor, dietician, optometrist, speech therapist.', 'kaamase-core' ) ),
			'health-administration'   => array( __( 'Hospital and health administration', 'kaamase-core' ), __( 'Hospital administrator, public health officer, health programme manager, medical records.', 'kaamase-core' ) ),
			'civil-engineering'       => array( __( 'Civil engineering', 'kaamase-core' ), __( 'Civil engineer, site engineer, quantity surveyor, structural engineer, roads and buildings.', 'kaamase-core' ) ),
			'mechanical-engineering'  => array( __( 'Mechanical and automobile engineering', 'kaamase-core' ), __( 'Mechanical engineer, automobile engineer, maintenance engineer, workshop manager.', 'kaamase-core' ) ),
			'electrical-engineering'  => array( __( 'Electrical and electronics engineering', 'kaamase-core' ), __( 'Electrical engineer, electronics engineer, power and energy, instrumentation.', 'kaamase-core' ) ),
			'architecture'            => array( __( 'Architecture and planning', 'kaamase-core' ), __( 'Architect, town planner, interior designer, CAD designer.', 'kaamase-core' ) ),
			'project-management'      => array( __( 'Project management', 'kaamase-core' ), __( 'Project manager, project coordinator, project engineer, planning engineer.', 'kaamase-core' ) ),
			'quality-safety'          => array( __( 'Quality, safety and environment', 'kaamase-core' ), __( 'Quality control, quality assurance, safety officer, environment officer, compliance inspector.', 'kaamase-core' ) ),
			'hospitality-management'  => array( __( 'Hotel and hospitality management', 'kaamase-core' ), __( 'Hotel manager, front office manager, restaurant manager, food and beverage, guest relations.', 'kaamase-core' ) ),
			'tourism-travel'          => array( __( 'Tourism and travel', 'kaamase-core' ), __( 'Travel consultant, tour manager, ticketing, tourism officer, homestay manager.', 'kaamase-core' ) ),
			'aviation'                => array( __( 'Aviation and airport', 'kaamase-core' ), __( 'Cabin crew, airline ground staff, airport operations, airport ticketing.', 'kaamase-core' ) ),
			'facility-management'     => array( __( 'Security and facility management', 'kaamase-core' ), __( 'Security officer, security supervisor, facility manager, estate manager.', 'kaamase-core' ) ),
			'fitness-wellness'        => array( __( 'Fitness, sports and wellness', 'kaamase-core' ), __( 'Gym trainer, fitness coach, yoga instructor, sports officer, spa manager.', 'kaamase-core' ) ),
			'government'              => array( __( 'Government and public sector', 'kaamase-core' ), __( 'Contract posts with government departments, PSU jobs, government project staff, municipal posts.', 'kaamase-core' ) ),
			'ngo-development'         => array( __( 'NGO, development and social work', 'kaamase-core' ), __( 'Programme officer, project coordinator, social worker, MSW, field coordinator, community mobiliser.', 'kaamase-core' ) ),
			'legal'                   => array( __( 'Legal', 'kaamase-core' ), __( 'Advocate, legal officer, law associate, paralegal, legal advisor.', 'kaamase-core' ) ),
			'agriculture-veterinary'  => array( __( 'Agriculture, forestry and veterinary', 'kaamase-core' ), __( 'Agriculture officer, horticulturist, veterinary doctor, forest officer, agronomist, extension worker.', 'kaamase-core' ) ),
			'research-science'        => array( __( 'Research and science', 'kaamase-core' ), __( 'Research associate, scientist, research lab staff, field researcher.', 'kaamase-core' ) ),
			'church-mission'          => array( __( 'Church and mission organisations', 'kaamase-core' ), __( 'Pastor, youth director, church administrator, mission worker, church accountant.', 'kaamase-core' ) ),
			'other-professional'      => array( __( 'Other professional jobs', 'kaamase-core' ), __( 'Any professional or office job that does not fit another category.', 'kaamase-core' ) ),
		);
	}
}

if ( ! function_exists( 'kaamase_pro_group_id' ) ) {
	/**
	 * The term ID of the Professional jobs heading.
	 *
	 * @since 1.0.0
	 * @return int 0 when it does not exist yet.
	 */
	function kaamase_pro_group_id() {

		// Remembered once found. Not remembered while missing: it may be made later in this request.
		static $found = 0;

		if ( $found ) {
			return $found;
		}

		if ( ! taxonomy_exists( 'kaamase_trade' ) ) {
			return 0;
		}

		$term = get_term_by( 'slug', KAAMASE_PRO_GROUP, 'kaamase_trade' );

		if ( $term instanceof WP_Term && 0 === (int) $term->parent ) {
			$found = (int) $term->term_id;
		}

		return $found;
	}
}

if ( ! function_exists( 'kaamase_pro_trade_slugs' ) ) {
	/**
	 * The slugs that are professional categories.
	 *
	 * Read from the terms under the heading rather than from the list
	 * above, so a category the owner adds under it in wp-admin is
	 * professional too.
	 *
	 * @since 1.0.0
	 * @return string[]
	 */
	function kaamase_pro_trade_slugs() {

		static $slugs = array();

		if ( $slugs ) {
			return $slugs;
		}

		$group = kaamase_pro_group_id();

		if ( ! $group ) {
			return array();
		}

		$found = get_terms(
			array(
				'taxonomy'   => 'kaamase_trade',
				'parent'     => $group,
				'hide_empty' => false,
				'fields'     => 'id=>slug',
			)
		);

		$slugs = is_array( $found ) ? array_values( $found ) : array();

		return $slugs;
	}
}

if ( ! function_exists( 'kaamase_pro_is_trade' ) ) {
	/**
	 * Whether a trade is a professional category.
	 *
	 * @since 1.0.0
	 * @param string $slug Trade slug.
	 * @return bool
	 */
	function kaamase_pro_is_trade( $slug ) {

		$slug = (string) $slug;

		return '' !== $slug && in_array( $slug, kaamase_pro_trade_slugs(), true );
	}
}

if ( ! function_exists( 'kaamase_pro_is_job' ) ) {
	/**
	 * Whether a job is a professional job.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return bool
	 */
	function kaamase_pro_is_job( $job_id ) {

		$job_id = (int) $job_id;

		if ( ! $job_id || 'kaamase_job' !== get_post_type( $job_id ) ) {
			return false;
		}

		$group = kaamase_pro_group_id();

		if ( ! $group ) {
			return false;
		}

		$terms = get_the_terms( $job_id, 'kaamase_trade' );

		if ( ! is_array( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( (int) $term->parent === $group || (int) $term->term_id === $group ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'kaamase_pro_add_to_seed' ) ) {
	/**
	 * Put the categories in the trade seed, so a reinstall makes them.
	 *
	 * A new heading only, never added to one that exists. The seeding
	 * moves an existing trade under whatever heading the seed names, so
	 * any slug already in use elsewhere is left out here rather than
	 * dragged across.
	 *
	 * @since 1.0.0
	 * @param array[] $seed Trade headings and their trades.
	 * @return array[]
	 */
	function kaamase_pro_add_to_seed( $seed ) {

		if ( isset( $seed[ KAAMASE_PRO_GROUP ] ) ) {
			return $seed;
		}

		$group  = kaamase_pro_group_id();
		$trades = array();

		foreach ( kaamase_pro_categories() as $slug => $category ) {

			$term = taxonomy_exists( 'kaamase_trade' ) ? get_term_by( 'slug', $slug, 'kaamase_trade' ) : false;

			if ( $term instanceof WP_Term && ( ! $group || (int) $term->parent !== $group ) ) {
				continue;
			}

			$trades[ $slug ] = $category[0];
		}

		$seed[ KAAMASE_PRO_GROUP ] = array(
			'name'   => __( 'Professional jobs', 'kaamase-core' ),
			'trades' => $trades,
		);

		return $seed;
	}
}
add_filter( 'kaamase_trade_seed', 'kaamase_pro_add_to_seed', 20 );

if ( ! function_exists( 'kaamase_pro_install' ) ) {
	/**
	 * Create the heading and its categories, once.
	 *
	 * Done here directly rather than by running the whole trade seeding
	 * again, so that nothing outside the new heading is looked at. A
	 * slug already used by an existing trade is skipped and written
	 * down, never moved.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pro_install() {

		if ( (int) get_option( 'kaamase_pro_categories_version', 0 ) >= KAAMASE_PRO_CATEGORIES_VERSION ) {
			return;
		}

		if ( ! taxonomy_exists( 'kaamase_trade' ) ) {
			return;
		}

		// Marked first, so a slow run is not started again by every request behind it.
		update_option( 'kaamase_pro_categories_version', KAAMASE_PRO_CATEGORIES_VERSION, true );

		/*
		 * Names are stored, so they are stored in English like every other
		 * trade, whatever language the request that happens to arrive
		 * first is in. The app and the site translate them on the way out.
		 */
		$switched = switch_to_locale( 'en_US' );

		try {
			kaamase_pro_make_categories();
		} finally {
			if ( $switched ) {
				restore_previous_locale();
			}
		}

		// Public app answers are kept by LiteSpeed; the category list just changed.
		do_action( 'litespeed_purge', 'REST' );
	}
}
add_action( 'init', 'kaamase_pro_install', 30 );

if ( ! function_exists( 'kaamase_pro_make_categories' ) ) {
	/**
	 * The creating itself, for kaamase_pro_install().
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pro_make_categories() {

		$group = kaamase_pro_group_id();

		if ( ! $group ) {

			if ( term_exists( KAAMASE_PRO_GROUP, 'kaamase_trade' ) ) {
				// The slug is taken by something that is not a heading. Leave it, and say so.
				update_option( 'kaamase_pro_categories_skipped', array( KAAMASE_PRO_GROUP ), false );
				return;
			}

			$made = wp_insert_term(
				__( 'Professional jobs', 'kaamase-core' ),
				'kaamase_trade',
				array(
					'slug'        => KAAMASE_PRO_GROUP,
					'description' => __( 'Salaried career jobs: offices, banks, schools, hospitals, engineering and more.', 'kaamase-core' ),
				)
			);

			if ( is_wp_error( $made ) ) {
				delete_option( 'kaamase_pro_categories_version' );
				return;
			}

			$group = (int) $made['term_id'];
		}

		$skipped = array();

		foreach ( kaamase_pro_categories() as $slug => $category ) {

			$term = get_term_by( 'slug', $slug, 'kaamase_trade' );

			if ( $term instanceof WP_Term ) {

				if ( (int) $term->parent !== $group ) {
					$skipped[] = $slug;
				}

				continue;
			}

			$made = wp_insert_term(
				$category[0],
				'kaamase_trade',
				array(
					'slug'        => $slug,
					'parent'      => $group,
					'description' => $category[1],
				)
			);

			if ( is_wp_error( $made ) ) {
				$skipped[] = $slug;
			}
		}

		update_option( 'kaamase_pro_categories_skipped', $skipped, false );
	}
}


/* ==========================================================================
   2. WHAT A PROFESSIONAL JOB HOLDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_choices' ) ) {
	/**
	 * The answers each question takes, with the words shown for them.
	 *
	 * @since 1.0.0
	 * @return array[]
	 */
	function kaamase_pro_choices() {

		return array(
			'job_type'      => array(
				'full_time'  => __( 'Full time', 'kaamase-core' ),
				'part_time'  => __( 'Part time', 'kaamase-core' ),
				'contract'   => __( 'Contract', 'kaamase-core' ),
				'internship' => __( 'Internship', 'kaamase-core' ),
				'temporary'  => __( 'Temporary', 'kaamase-core' ),
			),
			'work_mode'     => array(
				'on_site' => __( 'At the workplace', 'kaamase-core' ),
				'hybrid'  => __( 'Partly from home', 'kaamase-core' ),
				'remote'  => __( 'Work from home', 'kaamase-core' ),
			),
			'qualification' => array(
				'any'          => __( 'No minimum', 'kaamase-core' ),
				'10th'         => __( 'Class 10', 'kaamase-core' ),
				'12th'         => __( 'Class 12', 'kaamase-core' ),
				'diploma'      => __( 'Diploma or ITI', 'kaamase-core' ),
				'graduate'     => __( 'Graduate', 'kaamase-core' ),
				'postgraduate' => __( 'Postgraduate', 'kaamase-core' ),
				'professional' => __( 'Professional degree (CA, MBBS, LLB, B.Ed and similar)', 'kaamase-core' ),
			),
			'apply_method'  => array(
				'phone' => __( 'Phone or WhatsApp', 'kaamase-core' ),
				'email' => __( 'Email', 'kaamase-core' ),
			),
		);
	}
}

if ( ! function_exists( 'kaamase_pro_fields' ) ) {
	/**
	 * Register the professional fields beside the job's own.
	 *
	 * Through the same schema as everything else, so they are typed,
	 * sanitised and protected the same way. The email applications go
	 * to is private: like a phone number it reaches somebody only
	 * through the contact screen.
	 *
	 * @since 1.0.0
	 * @param array[] $schema Field definitions keyed by post type.
	 * @return array[]
	 */
	function kaamase_pro_fields( $schema ) {

		if ( ! isset( $schema['kaamase_job'] ) || ! is_array( $schema['kaamase_job'] ) ) {
			return $schema;
		}

		$choices = kaamase_pro_choices();

		$schema['kaamase_job'] += array(
			'pro_job_type'      => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['job_type'] ),
				'default' => 'full_time',
				'label'   => __( 'Job type', 'kaamase-core' ),
			),
			'pro_work_mode'     => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['work_mode'] ),
				'default' => 'on_site',
				'label'   => __( 'Work mode', 'kaamase-core' ),
			),
			'pro_salary_max'    => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Highest monthly salary', 'kaamase-core' ),
			),
			'pro_qualification' => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['qualification'] ),
				'default' => 'any',
				'label'   => __( 'Qualification wanted', 'kaamase-core' ),
			),
			'pro_course'        => array(
				'type'    => 'string',
				'default' => '',
				'label'   => __( 'Course or subject', 'kaamase-core' ),
			),
			'pro_experience'    => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Years of experience wanted', 'kaamase-core' ),
			),
			'pro_apply_method'  => array(
				'type'    => 'choice',
				'choices' => array_keys( $choices['apply_method'] ),
				'default' => 'phone',
				'label'   => __( 'How to apply', 'kaamase-core' ),
			),
			'pro_apply_email'   => array(
				'type'    => 'string',
				'private' => true,
				'default' => '',
				'label'   => __( 'Email for applications', 'kaamase-core' ),
			),
		);

		return $schema;
	}
}
add_filter( 'kaamase_field_schema', 'kaamase_pro_fields' );

if ( ! function_exists( 'kaamase_pro_apply_email' ) ) {
	/**
	 * Where applications for a professional job go, when it is by email.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return string Empty when this job takes applications by phone.
	 */
	function kaamase_pro_apply_email( $job_id ) {

		if ( ! kaamase_pro_is_job( $job_id ) ) {
			return '';
		}

		if ( 'email' !== (string) kaamase_read_field( $job_id, 'pro_apply_method' ) ) {
			return '';
		}

		$email = sanitize_email( (string) kaamase_read_field( $job_id, 'pro_apply_email' ) );

		return is_email( $email ) ? $email : '';
	}
}

if ( ! function_exists( 'kaamase_pro_details' ) ) {
	/**
	 * Everything professional about a job, ready to show.
	 *
	 * The last date to apply is read from when the job closes rather
	 * than stored twice, so a repost or an extension can never leave the
	 * two disagreeing.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return array|null Null for an everyday job.
	 */
	function kaamase_pro_details( $job_id ) {

		$job_id = (int) $job_id;

		if ( ! kaamase_pro_is_job( $job_id ) ) {
			return null;
		}

		$choices = kaamase_pro_choices();
		$get     = static function ( $key ) use ( $job_id ) {
			return kaamase_read_field( $job_id, $key );
		};

		/*
		 * Whether the employer answered the professional questions. A job
		 * filed under a professional category from an app that has never
		 * asked them has no answers, and the defaults are not answers:
		 * saying Full time and Freshers welcome about a job whose employer
		 * said neither would be inventing the advert.
		 */
		// metadata_exists, not get_post_meta: the field is registered with a default, which WordPress hands back for a job that never stored one.
		$complete = metadata_exists( 'post', $job_id, KAAMASE_META_PREFIX . 'pro_job_type' );

		$type   = $complete ? (string) $get( 'pro_job_type' ) : '';
		$mode   = $complete ? (string) $get( 'pro_work_mode' ) : '';
		$qual   = $complete ? (string) $get( 'pro_qualification' ) : '';
		$min    = absint( $get( 'pay_amount' ) );
		$closes = absint( get_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires', true ) );

		/*
		 * The pay period the job really has. Anything posted through the
		 * professional form is monthly, but an app that does not know
		 * about professional jobs can file one under a professional
		 * category with a daily rate, and nine hundred rupees a day must
		 * never be shown, or sent to Google, as nine hundred a month.
		 */
		$unit = (string) $get( 'pay_unit' );
		$unit = in_array( $unit, array( 'day', 'month', 'job', 'hour' ), true ) ? $unit : 'month';
		$max  = 'month' === $unit ? max( $min, absint( $get( 'pro_salary_max' ) ) ) : $min;

		$category = '';
		$group    = kaamase_pro_group_id();

		foreach ( (array) get_the_terms( $job_id, 'kaamase_trade' ) as $term ) {
			if ( $term instanceof WP_Term && ( '' === $category || (int) $term->parent === $group ) ) {
				$category = (string) $term->slug;
			}
		}

		return array(
			'category'            => $category,
			'job_type'            => $type,
			'job_type_label'      => $choices['job_type'][ $type ] ?? '',
			'work_mode'           => $mode,
			'work_mode_label'     => $choices['work_mode'][ $mode ] ?? '',
			'salary'              => array(
				'min'  => $min,
				'max'  => $max,
				'unit' => $unit,
			),
			'qualification'       => $qual,
			'qualification_label' => $choices['qualification'][ $qual ] ?? '',
			'course'              => $complete ? (string) $get( 'pro_course' ) : '',
			'experience_min'      => $complete ? absint( $get( 'pro_experience' ) ) : 0,
			'complete'            => $complete,
			'apply_by'            => $closes ? wp_date( 'Y-m-d', $closes ) : '',
			'apply_by_ts'         => $closes,
			'apply_method'        => '' !== kaamase_pro_apply_email( $job_id ) ? 'email' : 'phone',
		);
	}
}


/* ==========================================================================
   3. THE RULES, HOWEVER THE JOB WAS POSTED
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_lifespan' ) ) {
	/**
	 * Ninety days instead of twenty-one, and never three.
	 *
	 * This is what a professional job gets when nobody picked a date:
	 * posted from an older app, or from the everyday form. The
	 * professional form sets the exact date right after.
	 *
	 * @since 1.0.0
	 * @param int $days    Days the job stays open.
	 * @param int $post_id Job ID.
	 * @return int
	 */
	function kaamase_pro_lifespan( $days, $post_id ) {
		return kaamase_pro_is_job( $post_id ) ? KAAMASE_PRO_DEFAULT_DAYS : $days;
	}
}
add_filter( 'kaamase_job_lifespan', 'kaamase_pro_lifespan', 10, 2 );

if ( ! function_exists( 'kaamase_pro_pending' ) ) {
	/**
	 * What the professional form sent, held for the save that follows.
	 *
	 * The shared save in services.php knows nothing about these fields
	 * and must not have to. They are put here, the save runs, and the
	 * action it fires at the end writes them.
	 *
	 * @since 1.0.0
	 * @param array|false|null $set An array to hold, false to clear, null to read.
	 * @return array|null
	 */
	function kaamase_pro_pending( $set = null ) {

		static $held = null;

		if ( false === $set ) {
			$held = null;
		} elseif ( is_array( $set ) ) {
			$held = $set;
		}

		return $held;
	}
}

if ( ! function_exists( 'kaamase_pro_close_at' ) ) {
	/**
	 * The moment a date ends, in the site's own time.
	 *
	 * @since 1.0.0
	 * @param string $date Y-m-d.
	 * @return int Timestamp, or 0 for a date that does not parse.
	 */
	function kaamase_pro_close_at( $date ) {

		$date = (string) $date;

		// A real calendar date only: 30 February is refused, not rolled into March.
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
			return 0;
		}

		$at = date_create_immutable( $date . ' 23:59:59', wp_timezone() );

		return $at ? (int) $at->getTimestamp() : 0;
	}
}

if ( ! function_exists( 'kaamase_pro_after_save' ) ) {
	/**
	 * Apply the professional rules after any job save.
	 *
	 * Urgent comes off whatever path the job came by. The form's own
	 * answers and its last date are written only when it was the
	 * professional form that sent them.
	 *
	 * @since 1.0.0
	 * @param int  $job_id    Job ID.
	 * @param bool $is_new    Whether it was just created.
	 * @param bool $published Whether it is live.
	 * @return void
	 */
	function kaamase_pro_after_save( $job_id, $is_new = false, $published = false ) {

		unset( $is_new, $published );

		$job_id = (int) $job_id;

		if ( ! kaamase_pro_is_job( $job_id ) ) {
			return;
		}

		if ( kaamase_read_field( $job_id, 'urgent' ) ) {
			kaamase_save_field( $job_id, 'urgent', false );
		}

		$pending = kaamase_pro_pending();

		if ( ! is_array( $pending ) ) {
			return;
		}

		foreach ( array( 'job_type', 'work_mode', 'salary_max', 'qualification', 'course', 'experience', 'apply_method' ) as $key ) {
			kaamase_save_field( $job_id, 'pro_' . $key, $pending[ $key ] );
		}

		if ( 'email' === $pending['apply_method'] ) {
			kaamase_save_field( $job_id, 'pro_apply_email', $pending['apply_email'] );
		} else {
			delete_post_meta( $job_id, KAAMASE_META_PREFIX . 'pro_apply_email' );
		}

		// A closed job keeps the date it closed on. Reopening is a repost.
		if ( 'kaamase_closed' !== get_post_status( $job_id ) ) {

			$close = kaamase_pro_close_at( $pending['apply_by'] );

			if ( $close ) {
				update_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires', $close );
			}
		}
	}
}
add_action( 'kaamase_job_saved', 'kaamase_pro_after_save', 5, 3 );

if ( ! function_exists( 'kaamase_pro_employer_cleared' ) ) {
	/**
	 * Whether a person has already let one of this employer's
	 * professional jobs go live.
	 *
	 * @since 1.0.0
	 * @param int $user_id Employer account.
	 * @return bool
	 */
	function kaamase_pro_employer_cleared( $user_id ) {

		$group = kaamase_pro_group_id();

		if ( ! $user_id || ! $group ) {
			return false;
		}

		$found = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'author'         => (int) $user_id,
				'post_status'    => array( 'publish', 'kaamase_closed' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy'         => 'kaamase_trade',
						'field'            => 'term_id',
						'terms'            => $group,
						'include_children' => true,
					),
				),
			)
		);

		return ! empty( $found );
	}
}

if ( ! function_exists( 'kaamase_pro_hold_first' ) ) {
	/**
	 * Hold an employer's first professional job for a person to read.
	 *
	 * Runs after job-screening.php and only while the professional form
	 * is saving, because that is the only moment the category is known
	 * before the job is written: the shared save sets categories after.
	 * Anything screening already held stays held for screening's own
	 * reasons. Nothing is ever public in between.
	 *
	 * @since 1.0.0
	 * @param array $data    Post data about to be written.
	 * @param array $postarr Raw post data.
	 * @return array
	 */
	function kaamase_pro_hold_first( $data, $postarr ) {

		if ( 'kaamase_job' !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}

		if ( current_user_can( 'edit_others_kaamase_jobs' ) ) {
			return $data;
		}

		$post_id = absint( $postarr['ID'] ?? 0 );

		if ( $post_id ) {

			/*
			 * An edit to a job that is waiting for this check keeps it
			 * waiting, whichever form or app sent the edit. Screening
			 * would let a trusted employer's edit straight through, and
			 * this reason is not one an edit can fix.
			 */
			if (
				'pending' === get_post_status( $post_id )
				&& kaamase_pro_is_job( $post_id )
				&& ! kaamase_pro_employer_cleared( absint( $data['post_author'] ?? 0 ) )
			) {
				$data['post_status'] = 'pending';
			}

			return $data;
		}

		// A new job's category is only known here when the professional form sent it.
		if ( ! is_array( kaamase_pro_pending() ) ) {
			return $data;
		}

		if ( kaamase_pro_employer_cleared( absint( $data['post_author'] ?? 0 ) ) ) {
			return $data;
		}

		$data['post_status'] = 'pending';

		if ( function_exists( 'kaamase_screen_stash' ) ) {
			kaamase_screen_stash(
				array(
					array(
						'group'    => 'First professional job from this employer',
						'severity' => 'review',
						'term'     => '',
					),
				),
				false
			);
		}

		return $data;
	}
}
add_filter( 'wp_insert_post_data', 'kaamase_pro_hold_first', 25, 2 );


/* ==========================================================================
   4. SAVING, FROM THE FORM AND FROM THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_input' ) ) {
	/**
	 * Read what was sent into one clean shape.
	 *
	 * The same keys from the website form and from the app, so the two
	 * are checked by exactly the same rules.
	 *
	 * @since 1.0.0
	 * @param array $raw What was sent.
	 * @return array
	 */
	function kaamase_pro_input( $raw ) {

		$raw     = is_array( $raw ) ? $raw : array();
		$choices = kaamase_pro_choices();

		$text = static function ( $key, $max = 200 ) use ( $raw ) {
			$value = isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? (string) $raw[ $key ] : '';
			return mb_substr( trim( sanitize_text_field( $value ) ), 0, $max );
		};

		$pick = static function ( $key, $list ) use ( $raw ) {
			$value = isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? sanitize_key( (string) $raw[ $key ] ) : '';
			return isset( $list[ $value ] ) ? $value : (string) array_key_first( $list );
		};

		$number = static function ( $key ) use ( $raw ) {
			return isset( $raw[ $key ] ) && is_scalar( $raw[ $key ] ) ? absint( $raw[ $key ] ) : 0;
		};

		$description = isset( $raw['description'] ) && is_scalar( $raw['description'] ) ? (string) $raw['description'] : '';

		$trade = $text( 'category' );
		$trade = '' !== $trade ? $trade : $text( 'trade' );

		return array(
			'title'         => $text( 'title', 90 ),
			'description'   => mb_substr( trim( sanitize_textarea_field( $description ) ), 0, 5000 ),
			'trade'         => function_exists( 'kaamase_match_trade' ) ? kaamase_match_trade( $trade ) : sanitize_title( $trade ),
			'district'      => function_exists( 'kaamase_match_district' ) ? kaamase_match_district( $text( 'district' ) ) : '',
			'town'          => $text( 'town', 80 ),
			'salary_min'    => $number( 'salary_min' ),
			'salary_max'    => $number( 'salary_max' ),
			'openings'      => $number( 'openings' ),
			'job_type'      => $pick( 'job_type', $choices['job_type'] ),
			'work_mode'     => $pick( 'work_mode', $choices['work_mode'] ),
			'qualification' => $pick( 'qualification', $choices['qualification'] ),
			'course'        => $text( 'course', 80 ),
			'experience'    => min( 40, $number( 'experience' ) ),
			'apply_by'      => $text( 'apply_by', 10 ),
			'start_date'    => $text( 'start_date', 10 ),
			'apply_method'  => $pick( 'apply_method', $choices['apply_method'] ),
			'apply_email'   => sanitize_email( $text( 'apply_email', 120 ) ),
			'contact_phone' => $text( 'contact_phone', 20 ),
		);
	}
}

if ( ! function_exists( 'kaamase_pro_validate' ) ) {
	/**
	 * What is wrong with a professional job, in words for the employer.
	 *
	 * Only what the shared checks do not already cover. District, the
	 * legal minimum wage, the daily posting limit and the phone number
	 * are still checked by services.php, the same as every job.
	 *
	 * @since 1.0.0
	 * @param array $in        Clean input.
	 * @param bool  $keep_date Whether the last date is not being chosen now: the job has closed,
	 *                         or an edit leaves the date as it was, even on its final day.
	 * @return string[] Empty when it is fine.
	 */
	function kaamase_pro_validate( $in, $keep_date = false ) {

		$errors = array();

		if ( '' === $in['trade'] || ! kaamase_pro_is_trade( $in['trade'] ) ) {
			$errors[] = __( 'Choose the kind of professional job.', 'kaamase-core' );
		}

		if ( mb_strlen( $in['title'] ) < 3 ) {
			$errors[] = __( 'Give the job a title, for example Accounts Executive.', 'kaamase-core' );
		}

		if ( mb_strlen( $in['description'] ) < 50 ) {
			$errors[] = __( 'Describe the job in a few lines: the work, the hours and who you are looking for.', 'kaamase-core' );
		}

		if ( $in['openings'] < 1 ) {
			$errors[] = __( 'Say how many people you are hiring.', 'kaamase-core' );
		}

		if ( $in['salary_min'] < 1 ) {
			$errors[] = __( 'Put the monthly salary. Jobs that show a salary get far more applications.', 'kaamase-core' );
		} elseif ( $in['salary_max'] && $in['salary_max'] < $in['salary_min'] ) {
			$errors[] = __( 'The highest salary cannot be less than the lowest.', 'kaamase-core' );
		} elseif ( max( $in['salary_min'], $in['salary_max'] ) > 1000000 ) {
			$errors[] = __( 'Check the salary. It should be the amount for one month.', 'kaamase-core' );
		}

		$close = kaamase_pro_close_at( $in['apply_by'] );

		if ( $keep_date ) {
			$close = true;
		} elseif ( ! $close ) {
			$errors[] = __( 'Choose the last date to apply.', 'kaamase-core' );
		} else {

			$today = wp_date( 'Y-m-d' );
			$last  = wp_date( 'Y-m-d', time() + ( KAAMASE_PRO_MAX_DAYS * DAY_IN_SECONDS ) );

			if ( $in['apply_by'] <= $today ) {
				$errors[] = __( 'The last date to apply has to be after today.', 'kaamase-core' );
			} elseif ( $in['apply_by'] > $last ) {
				$errors[] = sprintf(
					/* translators: %d: number of days */
					__( 'The last date to apply can be at most %d days from today.', 'kaamase-core' ),
					KAAMASE_PRO_MAX_DAYS
				);
			}
		}

		if ( '' !== $in['start_date'] && ! kaamase_pro_close_at( $in['start_date'] ) ) {
			$errors[] = __( 'The joining date does not look right.', 'kaamase-core' );
		}

		if ( 'email' === $in['apply_method'] && ! is_email( $in['apply_email'] ) ) {
			$errors[] = __( 'Put the email address applications should go to.', 'kaamase-core' );
		}

		return $errors;
	}
}

if ( ! function_exists( 'kaamase_pro_save_job' ) ) {
	/**
	 * Post or edit a professional job.
	 *
	 * Checks the professional answers, then hands the job to the same
	 * save every other job goes through, so the permissions, screening,
	 * limits and notifications are the ones that already exist.
	 *
	 * @since 1.0.0
	 * @param array $raw     What was sent.
	 * @param int   $user_id Who is posting.
	 * @param int   $job_id  Job being edited, or 0 for a new one.
	 * @return int|WP_Error Job ID.
	 */
	function kaamase_pro_save_job( $raw, $user_id, $job_id = 0 ) {

		$user_id = (int) $user_id;
		$job_id  = (int) $job_id;

		$closed  = false;
		$current = array();

		if ( $job_id ) {

			if ( 'kaamase_job' !== get_post_type( $job_id ) || ! user_can( $user_id, 'edit_post', $job_id ) ) {
				return new WP_Error(
					'kaamase_forbidden',
					__( 'That job is not yours to edit.', 'kaamase-core' ),
					array( 'status' => 403 )
				);
			}

			if ( ! kaamase_pro_is_job( $job_id ) ) {
				return new WP_Error(
					'kaamase_not_professional',
					__( 'That is not a professional job. Edit it on the normal job form.', 'kaamase-core' ),
					array( 'status' => 400 )
				);
			}

			/*
			 * An edit starts from the job as it is, so an app that sends
			 * only what changed does not wipe what it left out.
			 */
			$current = kaamase_pro_job_values( $job_id );
			$raw     = array_merge( $current, is_array( $raw ) ? $raw : array() );
			$closed  = 'kaamase_closed' === get_post_status( $job_id );
		}

		$in     = kaamase_pro_input( $raw );
		$keep   = $closed || ( $job_id && '' !== $in['apply_by'] && $in['apply_by'] === ( $current['apply_by'] ?? '' ) );
		$errors = kaamase_pro_validate( $in, $keep );

		if ( $errors ) {
			return new WP_Error(
				'kaamase_invalid_job',
				$errors[0],
				array(
					'messages' => $errors,
					'status'   => 400,
				)
			);
		}

		$data = array(
			'title'              => $in['title'],
			'description'        => $in['description'],
			'trade'              => $in['trade'],
			'district'           => $in['district'],
			'town'               => $in['town'],
			'pay_amount'         => $in['salary_min'],
			'pay_unit'           => 'month',
			'workers_needed'     => $in['openings'],
			'start_date'         => $in['start_date'],
			'duration'           => '',
			'urgent'             => false,
			'food_provided'      => false,
			'stay_provided'      => false,
			'transport_provided' => false,
			'contact_phone'      => $in['contact_phone'],
		);

		$in['salary_max'] = max( $in['salary_min'], $in['salary_max'] );

		kaamase_pro_pending( $in );

		try {
			$result = kaamase_save_job( $data, $user_id, $job_id );
		} finally {
			kaamase_pro_pending( false );
		}

		return $result;
	}
}


/* ==========================================================================
   5. THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_routes' ) ) {
	/**
	 * POST /pro-jobs to post one, POST /pro-jobs/{id} to edit one.
	 *
	 * Their own routes rather than extra fields on /jobs, so the route
	 * every app already uses is not touched at all.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pro_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/pro-jobs',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_pro_rest_save',
				'permission_callback' => 'is_user_logged_in',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/pro-jobs/(?P<id>\d+)',
			array(
				'methods'             => array( 'POST', 'PUT', 'PATCH' ),
				'callback'            => 'kaamase_pro_rest_save',
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_pro_routes' );

if ( ! function_exists( 'kaamase_pro_rest_save' ) ) {
	/**
	 * Post or edit from the app.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_pro_rest_save( $request ) {

		$job_id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$body   = $request->get_json_params();
		$body   = is_array( $body ) ? $body : $request->get_body_params();

		$result = kaamase_pro_save_job( (array) $body, get_current_user_id(), $job_id );

		if ( is_wp_error( $result ) ) {
			return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $result ) : $result;
		}

		return new WP_REST_Response( kaamase_shape_job( $result, true ), $job_id ? 200 : 201 );
	}
}

if ( ! function_exists( 'kaamase_pro_shape_job' ) ) {
	/**
	 * Tell the app what is professional about a job.
	 *
	 * professional is null on everyday jobs and an object on
	 * professional ones. Apps that do not know the key ignore it.
	 *
	 * @since 1.0.0
	 * @param array   $out  Shaped job.
	 * @param WP_Post $post The job.
	 * @return array
	 */
	function kaamase_pro_shape_job( $out, $post ) {

		$details = kaamase_pro_details( $post->ID );

		$out['professional'] = $details;

		if ( $details && 'email' === $details['apply_method'] ) {
			$out['contact_method'] = 'email';
		}

		return $out;
	}
}
add_filter( 'kaamase_shape_job', 'kaamase_pro_shape_job', 17, 2 );

if ( ! function_exists( 'kaamase_pro_channel' ) ) {
	/**
	 * Hand out the email address instead of a number, when that is how
	 * the employer asked to be reached.
	 *
	 * Through the same channel the contact screen and the app's contact
	 * route already use, so the gate, the daily count and the log are
	 * the ones a number goes through.
	 *
	 * @since 1.0.0
	 * @param array $channel Channel details.
	 * @param int   $post_id Profile or job ID.
	 * @return array
	 */
	function kaamase_pro_channel( $channel, $post_id ) {

		$email = kaamase_pro_apply_email( $post_id );

		if ( '' === $email ) {
			return $channel;
		}

		$channel['number'] = '';
		$channel['email']  = $email;
		$channel['label']  = __( 'Email address', 'kaamase-core' );

		return $channel;
	}
}
add_filter( 'kaamase_contact_channel', 'kaamase_pro_channel', 11, 2 );

if ( ! function_exists( 'kaamase_pro_in_reference' ) ) {
	/**
	 * Tell the app which categories are professional, and the answers
	 * the professional questions take.
	 *
	 * Added onto the finished response, the same way the category
	 * descriptions are, so the reference route itself is not touched.
	 *
	 * @since 1.0.0
	 * @param WP_HTTP_Response $response Response.
	 * @param WP_REST_Server   $server   Server.
	 * @param WP_REST_Request  $request  Request.
	 * @return WP_HTTP_Response
	 */
	function kaamase_pro_in_reference( $response, $server, $request ) {

		unset( $server );

		if ( ! defined( 'KAAMASE_REST_NS' ) || '/' . KAAMASE_REST_NS . '/reference' !== $request->get_route() ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['trades'] ) || ! is_array( $data['trades'] ) ) {
			return $response;
		}

		$pro = kaamase_pro_trade_slugs();

		foreach ( $data['trades'] as $at => $trade ) {
			$data['trades'][ $at ]['professional'] = isset( $trade['slug'] ) && in_array( $trade['slug'], $pro, true );
		}

		$group = get_term_by( 'slug', KAAMASE_PRO_GROUP, 'kaamase_trade' );
		$lists = array();

		foreach ( kaamase_pro_choices() as $name => $list ) {
			foreach ( $list as $key => $label ) {
				$lists[ $name ][] = array(
					'key'   => (string) $key,
					'label' => $label,
				);
			}
		}

		$data['professional'] = array(
			'group'          => KAAMASE_PRO_GROUP,
			'group_name'     => $group instanceof WP_Term ? $group->name : '',
			'max_days'       => KAAMASE_PRO_MAX_DAYS,
			'suggested_days' => KAAMASE_PRO_SUGGEST_DAYS,
			'job_types'      => $lists['job_type'],
			'work_modes'     => $lists['work_mode'],
			'qualifications' => $lists['qualification'],
			'apply_methods'  => $lists['apply_method'],
		);

		$response->set_data( $data );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_pro_in_reference', 11, 3 );


/* ==========================================================================
   6. THE WEBSITE
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_page' ) ) {
	/**
	 * Add the professional form to the platform's pages.
	 *
	 * @since 1.0.0
	 * @param array[] $pages Page definitions.
	 * @return array[]
	 */
	function kaamase_pro_page( $pages ) {

		$pages['post_pro_job'] = array(
			'title'   => __( 'Post a professional job', 'kaamase-core' ),
			'slug'    => 'post-professional-job',
			'content' => '[kaamase_post_pro_job]',
		);

		return $pages;
	}
}
add_filter( 'kaamase_page_definitions', 'kaamase_pro_page' );

if ( ! function_exists( 'kaamase_pro_make_page' ) ) {
	/**
	 * Create that one page, once.
	 *
	 * Only this page. Running the general page creation instead would
	 * also bring back any page the owner deleted on purpose.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pro_make_page() {

		if ( get_option( 'kaamase_pro_page_made' ) ) {
			return;
		}

		update_option( 'kaamase_pro_page_made', time(), true );

		$stored = (array) get_option( 'kaamase_pages', array() );

		if ( ! empty( $stored['post_pro_job'] ) && 'page' === get_post_type( (int) $stored['post_pro_job'] ) ) {
			return;
		}

		$existing = get_page_by_path( 'post-professional-job' );

		if ( $existing instanceof WP_Post ) {
			$stored['post_pro_job'] = (int) $existing->ID;
		} else {

			$id = wp_insert_post(
				array(
					'post_title'     => __( 'Post a professional job', 'kaamase-core' ),
					'post_name'      => 'post-professional-job',
					'post_content'   => '[kaamase_post_pro_job]',
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				)
			);

			if ( ! $id || is_wp_error( $id ) ) {
				delete_option( 'kaamase_pro_page_made' );
				return;
			}

			$stored['post_pro_job'] = (int) $id;
		}

		update_option( 'kaamase_pages', $stored, true );

		if ( function_exists( 'kaamase_page_url_flush' ) ) {
			kaamase_page_url_flush();
		}
	}
}
add_action( 'init', 'kaamase_pro_make_page', 31 );

if ( ! function_exists( 'kaamase_pro_form_key' ) ) {
	/**
	 * Where a half-filled form waits while the page reloads with errors.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_pro_form_key() {
		return 'kaamase_pro_form_' . get_current_user_id();
	}
}

if ( ! function_exists( 'kaamase_pro_form_values' ) ) {
	/**
	 * What the form shows: what was just typed, the job being edited, or
	 * sensible starting answers.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job being edited, or 0.
	 * @return array
	 */
	function kaamase_pro_form_values( $job_id ) {

		$typed = get_transient( kaamase_pro_form_key() );

		// What was typed is shown again only on the same job it was typed for.
		if ( is_array( $typed ) && (int) ( $typed['job_id'] ?? -1 ) === (int) $job_id ) {
			return $typed;
		}

		$values = kaamase_pro_input( array() );

		$values['openings'] = 1;
		$values['apply_by'] = wp_date( 'Y-m-d', time() + ( KAAMASE_PRO_SUGGEST_DAYS * DAY_IN_SECONDS ) );
		$values['trade']    = '';

		if ( ! $job_id ) {
			return $values;
		}

		return array_merge( $values, kaamase_pro_job_values( $job_id ) );
	}
}

if ( ! function_exists( 'kaamase_pro_job_values' ) ) {
	/**
	 * A professional job read back as the answers the form and the app send.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return array Empty for anything that is not a professional job.
	 */
	function kaamase_pro_job_values( $job_id ) {

		$details = kaamase_pro_details( $job_id );
		$post    = get_post( $job_id );

		if ( ! $details || ! $post ) {
			return array();
		}

		/*
		 * A job filed from an older app with a daily or hourly rate opens
		 * with the salary boxes empty, so the employer types a monthly
		 * salary rather than finding 900 sitting under a monthly label.
		 */
		$monthly = 'month' === $details['salary']['unit'];

		$defaults = kaamase_pro_input( array() );

		return array(
			'title'         => (string) $post->post_title,
			'description'   => (string) $post->post_content,
			'trade'         => $details['category'],
			'district'      => (string) kaamase_read_field( $job_id, 'district' ),
			'town'          => (string) kaamase_read_field( $job_id, 'town' ),
			'salary_min'    => $monthly ? $details['salary']['min'] : 0,
			'salary_max'    => $monthly ? $details['salary']['max'] : 0,
			'openings'      => absint( kaamase_read_field( $job_id, 'workers_needed' ) ),
			'job_type'      => $details['complete'] ? $details['job_type'] : $defaults['job_type'],
			'work_mode'     => $details['complete'] ? $details['work_mode'] : $defaults['work_mode'],
			'qualification' => $details['complete'] ? $details['qualification'] : $defaults['qualification'],
			'course'        => $details['course'],
			'experience'    => $details['experience_min'],
			'apply_by'      => $details['apply_by'],
			'start_date'    => (string) kaamase_read_field( $job_id, 'start_date' ),
			'apply_method'  => $details['apply_method'],
			'apply_email'   => (string) kaamase_read_field( $job_id, 'pro_apply_email' ),
			'contact_phone' => (string) kaamase_read_field( $job_id, 'contact_phone' ),
		);
	}
}

if ( ! function_exists( 'kaamase_pro_select' ) ) {
	/**
	 * One select box.
	 *
	 * @since 1.0.0
	 * @param string $name    Field name.
	 * @param string $id      Element ID.
	 * @param array  $options Value => label.
	 * @param string $current Current value.
	 * @return void
	 */
	function kaamase_pro_select( $name, $id, $options, $current ) {

		printf( '<select class="ka-select" id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );

		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( (string) $value ),
				selected( (string) $current, (string) $value, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}
}

if ( ! function_exists( 'kaamase_pro_form' ) ) {
	/**
	 * The professional job form.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_pro_form() {

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--action ka-mt-4" href="%3$s">%4$s</a></div></div>',
				esc_html__( 'Register first, it is free', 'kaamase-core' ),
				esc_html__( 'Posting a job takes two minutes once you have an account.', 'kaamase-core' ),
				esc_url( add_query_arg( 'type', 'employer', kaamase_page_url( 'register' ) ) ),
				esc_html__( 'Register as an employer', 'kaamase-core' )
			);
		}

		if ( ! current_user_can( 'create_kaamase_jobs' ) ) {

			if ( function_exists( 'kaamase_hiring_request_notice' ) ) {
				return kaamase_hiring_request_notice();
			}

			return sprintf(
				'<div class="ka-notice ka-notice--warn"><div><span class="ka-notice__title">%s</span></div></div>',
				esc_html__( 'This account cannot post jobs', 'kaamase-core' )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

		if ( $job_id && ( ! current_user_can( 'edit_post', $job_id ) || ! kaamase_pro_is_job( $job_id ) ) ) {
			$job_id = 0;
		}

		$values  = kaamase_pro_form_values( $job_id );
		$errors  = get_transient( kaamase_pro_form_key() . '_err' );
		$choices = kaamase_pro_choices();

		// Said once. A visit tomorrow should not open on yesterday's mistakes.
		delete_transient( kaamase_pro_form_key() . '_err' );

		$categories = array();

		foreach ( kaamase_pro_trade_slugs() as $slug ) {
			$term = get_term_by( 'slug', $slug, 'kaamase_trade' );
			if ( $term instanceof WP_Term ) {
				$categories[ $slug ] = $term->name;
			}
		}

		asort( $categories );

		$experience = array( 0 => __( 'Freshers welcome', 'kaamase-core' ) );

		foreach ( array( 1, 2, 3, 5, 7, 10, 15 ) as $years ) {
			$experience[ $years ] = sprintf(
				/* translators: %d: number of years */
				_n( 'At least %d year', 'At least %d years', $years, 'kaamase-core' ),
				$years
			);
		}

		if ( ! isset( $experience[ (int) $values['experience'] ] ) ) {
			$experience[ (int) $values['experience'] ] = sprintf(
				/* translators: %d: number of years */
				_n( 'At least %d year', 'At least %d years', (int) $values['experience'], 'kaamase-core' ),
				(int) $values['experience']
			);
			ksort( $experience );
		}

		$today = wp_date( 'Y-m-d', time() + DAY_IN_SECONDS );
		$last  = wp_date( 'Y-m-d', time() + ( KAAMASE_PRO_MAX_DAYS * DAY_IN_SECONDS ) );

		ob_start();

		if ( is_array( $errors ) && $errors ) {
			echo '<div class="ka-notice ka-notice--error"><div><span class="ka-notice__title">'
				. esc_html__( 'Please fix this', 'kaamase-core' )
				. '</span><ul>';

			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}

			echo '</ul></div></div>';
		}

		if ( ! kaamase_user_is_verified( get_current_user_id() ) ) {
			printf(
				'<div class="ka-notice ka-notice--warn"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p></div></div>',
				esc_html__( 'Confirm your email to make jobs visible', 'kaamase-core' ),
				esc_html__( 'You can write the job now. It goes live the moment you tap the link in your email.', 'kaamase-core' )
			);
		}
		?>

		<form class="ka-form ka-stack--lg" method="post" action="">

			<?php wp_nonce_field( 'kaamase_post_pro_job', 'kaamase_pro_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="post_pro_job">
			<input type="hidden" name="kaamase_job_id" value="<?php echo esc_attr( $job_id ); ?>">

			<?php
			/*
			 * Only when editing. A new job sits under the page's own title,
			 * "Post a professional job", and saying it again straight
			 * underneath looked like a mistake.
			 *
			 * @since 1.1.1
			 */
			if ( $job_id ) :
				?>
				<h2><?php esc_html_e( 'Edit this professional job', 'kaamase-core' ); ?></h2>
			<?php endif; ?>

			<p class="ka-hint">
				<?php esc_html_e( 'For salaried jobs in offices, banks, schools, hospitals, engineering and similar work. For daily work like masonry, driving or house help, use the normal job form.', 'kaamase-core' ); ?>
			</p>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-category">
					<?php esc_html_e( 'Kind of job', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-pro-category" name="category" required>
					<option value=""><?php esc_html_e( 'Choose the kind of job', 'kaamase-core' ); ?></option>
					<?php foreach ( $categories as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $values['trade'], $slug ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-title">
					<?php esc_html_e( 'Job title', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="text" id="ka-pro-title" name="title" maxlength="90" required
					value="<?php echo esc_attr( $values['title'] ); ?>"
					placeholder="<?php esc_attr_e( 'For example Accounts Executive', 'kaamase-core' ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-openings">
					<?php esc_html_e( 'Number of openings', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="number" id="ka-pro-openings" name="openings" min="1" max="500" inputmode="numeric" required
					value="<?php echo esc_attr( max( 1, (int) $values['openings'] ) ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-type"><?php esc_html_e( 'Job type', 'kaamase-core' ); ?></label>
				<?php kaamase_pro_select( 'job_type', 'ka-pro-type', $choices['job_type'], $values['job_type'] ); ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-mode"><?php esc_html_e( 'Where the work is done', 'kaamase-core' ); ?></label>
				<?php kaamase_pro_select( 'work_mode', 'ka-pro-mode', $choices['work_mode'], $values['work_mode'] ); ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-salary-min">
					<?php esc_html_e( 'Monthly salary in rupees', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<div class="ka-cluster">
					<input class="ka-input" type="number" id="ka-pro-salary-min" name="salary_min" min="1" step="1" inputmode="numeric" required style="flex:1;"
						value="<?php echo esc_attr( $values['salary_min'] ? $values['salary_min'] : '' ); ?>"
						placeholder="<?php esc_attr_e( 'From', 'kaamase-core' ); ?>"
						aria-label="<?php esc_attr_e( 'Lowest monthly salary', 'kaamase-core' ); ?>">
					<input class="ka-input" type="number" id="ka-pro-salary-max" name="salary_max" min="0" step="1" inputmode="numeric" style="flex:1;"
						value="<?php echo esc_attr( $values['salary_max'] ? $values['salary_max'] : '' ); ?>"
						placeholder="<?php esc_attr_e( 'To', 'kaamase-core' ); ?>"
						aria-label="<?php esc_attr_e( 'Highest monthly salary', 'kaamase-core' ); ?>">
				</div>
				<p class="ka-hint">
					<?php esc_html_e( 'Jobs that show a salary get far more applications. Leave the second box empty for a fixed salary.', 'kaamase-core' ); ?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-district">
					<?php esc_html_e( 'District', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<select class="ka-select" id="ka-pro-district" name="district" required>
					<option value=""><?php esc_html_e( 'Choose the district', 'kaamase-core' ); ?></option>
					<?php foreach ( kaamase_district_choices() as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $values['district'], $slug ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-town"><?php esc_html_e( 'Town', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="text" id="ka-pro-town" name="town" maxlength="80"
					value="<?php echo esc_attr( $values['town'] ); ?>"
					placeholder="<?php esc_attr_e( 'Where the office is', 'kaamase-core' ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-qual"><?php esc_html_e( 'Qualification needed', 'kaamase-core' ); ?></label>
				<?php kaamase_pro_select( 'qualification', 'ka-pro-qual', $choices['qualification'], $values['qualification'] ); ?>
				<input class="ka-input ka-mt-4" type="text" name="course" maxlength="80"
					value="<?php echo esc_attr( $values['course'] ); ?>"
					placeholder="<?php esc_attr_e( 'Course or subject, for example B.Com or B.Ed (optional)', 'kaamase-core' ); ?>"
					aria-label="<?php esc_attr_e( 'Course or subject', 'kaamase-core' ); ?>">
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-exp"><?php esc_html_e( 'Experience needed', 'kaamase-core' ); ?></label>
				<?php kaamase_pro_select( 'experience', 'ka-pro-exp', $experience, (string) (int) $values['experience'] ); ?>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-description">
					<?php esc_html_e( 'About the job', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<textarea class="ka-textarea" id="ka-pro-description" name="description" maxlength="5000" required rows="8"
					placeholder="<?php esc_attr_e( 'The work, the hours, the skills you need and anything about the organisation.', 'kaamase-core' ); ?>"><?php echo esc_textarea( $values['description'] ); ?></textarea>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-apply-by">
					<?php esc_html_e( 'Last date to apply', 'kaamase-core' ); ?>
					<span class="ka-label__req">*</span>
				</label>
				<input class="ka-input" type="date" id="ka-pro-apply-by" name="apply_by" required
					min="<?php echo esc_attr( $today ); ?>" max="<?php echo esc_attr( $last ); ?>"
					value="<?php echo esc_attr( $values['apply_by'] ); ?>">
				<p class="ka-hint">
					<?php
					printf(
						/* translators: %d: number of days */
						esc_html__( 'Up to %d days from today. The job closes on its own after this date and stays readable at its address.', 'kaamase-core' ),
						(int) KAAMASE_PRO_MAX_DAYS
					);
					?>
				</p>
			</div>

			<div class="ka-field">
				<label class="ka-label" for="ka-pro-start"><?php esc_html_e( 'Joining date', 'kaamase-core' ); ?></label>
				<input class="ka-input" type="date" id="ka-pro-start" name="start_date"
					value="<?php echo esc_attr( $values['start_date'] ); ?>">
			</div>

			<fieldset class="ka-field">
				<legend class="ka-label"><?php esc_html_e( 'How people apply', 'kaamase-core' ); ?></legend>

				<label class="ka-check">
					<input type="radio" name="apply_method" value="phone" <?php checked( 'phone', $values['apply_method'] ); ?>>
					<span><?php esc_html_e( 'By phone or WhatsApp', 'kaamase-core' ); ?></span>
				</label>
				<input class="ka-input ka-mt-4" type="tel" name="contact_phone" inputmode="numeric" maxlength="15"
					value="<?php echo esc_attr( $values['contact_phone'] ); ?>"
					placeholder="<?php esc_attr_e( 'Leave blank to use your account number', 'kaamase-core' ); ?>"
					aria-label="<?php esc_attr_e( 'Number for this job', 'kaamase-core' ); ?>">

				<label class="ka-check ka-mt-4">
					<input type="radio" name="apply_method" value="email" <?php checked( 'email', $values['apply_method'] ); ?>>
					<span><?php esc_html_e( 'By email', 'kaamase-core' ); ?></span>
				</label>
				<input class="ka-input ka-mt-4" type="email" name="apply_email" maxlength="120"
					value="<?php echo esc_attr( $values['apply_email'] ); ?>"
					placeholder="<?php esc_attr_e( 'Email address for applications', 'kaamase-core' ); ?>"
					aria-label="<?php esc_attr_e( 'Email for applications', 'kaamase-core' ); ?>">

				<p class="ka-hint">
					<?php esc_html_e( 'Never printed on the page. People reach you through Kaam Ase, with the same limits and records as a phone number.', 'kaamase-core' ); ?>
				</p>
			</fieldset>

			<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">
				<?php
				echo $job_id
					? esc_html__( 'Save changes', 'kaamase-core' )
					: esc_html__( 'Post this job', 'kaamase-core' );
				?>
			</button>

			<p class="ka-small ka-mute">
				<?php esc_html_e( 'Free to post. The first professional job from every employer is read by a person before it goes live, usually within a few hours.', 'kaamase-core' ); ?>
			</p>

		</form>
		<?php

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_post_pro_job', 'kaamase_pro_form' );

if ( ! function_exists( 'kaamase_pro_handle_form' ) ) {
	/**
	 * Take the form, then send the employer somewhere sensible.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pro_handle_form() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'post_pro_job' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_pro_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_pro_nonce'] ) ), 'kaamase_post_pro_job' )
		) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked above.
		$job_id = isset( $_POST['kaamase_job_id'] ) ? absint( $_POST['kaamase_job_id'] ) : 0;
		$raw    = wp_unslash( $_POST );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$back = function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'post_pro_job' ) : home_url( '/' );
		$back = $job_id ? add_query_arg( 'edit', $job_id, $back ) : $back;

		set_transient( kaamase_pro_form_key(), array( 'job_id' => $job_id ) + kaamase_pro_input( $raw ), 15 * MINUTE_IN_SECONDS );

		$result = kaamase_pro_save_job( $raw, get_current_user_id(), $job_id );

		if ( is_wp_error( $result ) ) {

			$data     = $result->get_error_data();
			$messages = isset( $data['messages'] ) ? (array) $data['messages'] : array( $result->get_error_message() );

			set_transient( kaamase_pro_form_key() . '_err', $messages, 15 * MINUTE_IN_SECONDS );

			wp_safe_redirect( $back );
			exit;
		}

		delete_transient( kaamase_pro_form_key() );
		delete_transient( kaamase_pro_form_key() . '_err' );

		$job_id = (int) $result;

		if ( 'pending' === get_post_status( $job_id ) ) {
			wp_safe_redirect( add_query_arg( 'held', '1', kaamase_page_url( 'dashboard' ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'posted', '1', get_permalink( $job_id ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_pro_handle_form' );

if ( ! function_exists( 'kaamase_pro_page_id' ) ) {
	/**
	 * The ID of one of the platform's pages.
	 *
	 * @since 1.0.0
	 * @param string $name Internal page name.
	 * @return int
	 */
	function kaamase_pro_page_id( $name ) {

		$stored = (array) get_option( 'kaamase_pages', array() );

		return isset( $stored[ $name ] ) ? (int) $stored[ $name ] : 0;
	}
}

if ( ! function_exists( 'kaamase_pro_edit_redirect' ) ) {
	/**
	 * Open a professional job in the professional form.
	 *
	 * The Edit link on every job points at the everyday form, which does
	 * not show the salary range, the last date or the rest. Sent here
	 * instead, so editing never quietly drops them.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_pro_edit_redirect() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

		if ( ! $job_id || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		$page = kaamase_pro_page_id( 'post_job' );

		if ( ! $page || ! is_page( $page ) || ! kaamase_pro_is_job( $job_id ) ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'edit', $job_id, kaamase_page_url( 'post_pro_job' ) ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_pro_edit_redirect', 9 );

if ( ! function_exists( 'kaamase_pro_everyday_form_hint' ) ) {
	/**
	 * Point employers on the everyday form at the professional one.
	 *
	 * @since 1.0.0
	 * @param string $content Page content.
	 * @return string
	 */
	function kaamase_pro_everyday_form_hint( $content ) {

		$page = kaamase_pro_page_id( 'post_job' );

		if ( ! $page || ! is_page( $page ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only deciding whether to show a note.
		if ( ! current_user_can( 'create_kaamase_jobs' ) || ! empty( $_GET['edit'] ) ) {
			return $content;
		}

		$hint = sprintf(
			'<div class="ka-notice ka-notice--info ka-mb-4"><div><span class="ka-notice__title">%1$s</span><p>%2$s</p><a class="ka-btn ka-btn--outline ka-btn--sm ka-mt-4" href="%3$s">%4$s</a></div></div>',
			esc_html__( 'Hiring for an office or professional job?', 'kaamase-core' ),
			esc_html__( 'Banks, schools, hospitals, offices and companies: use the professional form. It asks for the salary range and qualification, and stays open up to three months.', 'kaamase-core' ),
			esc_url( kaamase_page_url( 'post_pro_job' ) ),
			esc_html__( 'Post a professional job', 'kaamase-core' )
		);

		return $hint . $content;
	}
}
add_filter( 'the_content', 'kaamase_pro_everyday_form_hint', 9 );

if ( ! function_exists( 'kaamase_pro_job_facts' ) ) {
	/**
	 * Show the professional details at the top of the job's page.
	 *
	 * @since 1.0.0
	 * @param string $content The job description.
	 * @return string
	 */
	function kaamase_pro_job_facts( $content ) {

		if ( ! is_singular( 'kaamase_job' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$details = kaamase_pro_details( get_the_ID() );

		if ( ! $details ) {
			return $content;
		}

		$amount = number_format_i18n( $details['salary']['min'] );

		switch ( $details['salary']['unit'] ) {

			case 'day':
				/* translators: %s: amount */
				$salary = sprintf( __( '₹%s a day', 'kaamase-core' ), $amount );
				break;

			case 'hour':
				/* translators: %s: amount */
				$salary = sprintf( __( '₹%s an hour', 'kaamase-core' ), $amount );
				break;

			case 'job':
				/* translators: %s: amount */
				$salary = sprintf( __( '₹%s for the whole job', 'kaamase-core' ), $amount );
				break;

			default:
				$salary = $details['salary']['max'] > $details['salary']['min']
					? sprintf(
						/* translators: 1: lowest salary, 2: highest salary */
						__( '₹%1$s to ₹%2$s a month', 'kaamase-core' ),
						$amount,
						number_format_i18n( $details['salary']['max'] )
					)
					: sprintf(
						/* translators: %s: salary */
						__( '₹%s a month', 'kaamase-core' ),
						$amount
					);
		}

		$qualification = $details['qualification_label'];

		if ( '' !== $details['course'] ) {
			$qualification .= ' · ' . $details['course'];
		}

		$experience = '';

		if ( $details['complete'] ) {
			$experience = $details['experience_min']
				? sprintf(
					/* translators: %d: number of years */
					_n( 'At least %d year', 'At least %d years', $details['experience_min'], 'kaamase-core' ),
					$details['experience_min']
				)
				: __( 'Freshers welcome', 'kaamase-core' );
		}

		$facts = array(
			__( 'Salary', 'kaamase-core' )        => $salary,
			__( 'Job type', 'kaamase-core' )      => $details['job_type_label'],
			__( 'Where', 'kaamase-core' )         => $details['work_mode_label'],
			__( 'Qualification', 'kaamase-core' ) => $qualification,
			__( 'Experience', 'kaamase-core' )    => $experience,
		);

		if ( $details['apply_by_ts'] ) {
			$facts[ __( 'Apply by', 'kaamase-core' ) ] = wp_date( (string) get_option( 'date_format' ), $details['apply_by_ts'] );
		}

		$html = '<section class="ka-facts ka-mb-4 ka-pro-facts">';

		foreach ( $facts as $label => $value ) {

			if ( '' === (string) $value ) {
				continue;
			}

			$html .= sprintf(
				'<div class="ka-fact"><p class="ka-fact__label">%1$s</p><p class="ka-fact__value">%2$s</p></div>',
				esc_html( $label ),
				esc_html( $value )
			);
		}

		$html .= '</section>';

		return $html . $content;
	}
}
add_filter( 'the_content', 'kaamase_pro_job_facts', 5 );

if ( ! function_exists( 'kaamase_pro_dashboard_card' ) ) {
	/**
	 * Offer the professional form on the dashboard of anybody who hires.
	 *
	 * @since 1.0.0
	 * @param int    $user_id Who is looking.
	 * @param int    $profile Their profile.
	 * @param string $type    Which kind of profile.
	 * @return void
	 */
	function kaamase_pro_dashboard_card( $user_id, $profile = 0, $type = '' ) {

		unset( $profile, $type );

		if ( ! user_can( (int) $user_id, 'create_kaamase_jobs' ) ) {
			return;
		}
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6">
			<h2><?php esc_html_e( 'Hiring for a professional job?', 'kaamase-core' ); ?></h2>
			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'Offices, banks, schools, hospitals and companies: post with a salary range, the qualification you need and a last date to apply of up to three months.', 'kaamase-core' ); ?>
			</p>
			<a class="ka-btn ka-btn--outline ka-mt-4" href="<?php echo esc_url( kaamase_page_url( 'post_pro_job' ) ); ?>">
				<?php esc_html_e( 'Post a professional job', 'kaamase-core' ); ?>
			</a>
		</section>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_pro_dashboard_card', 15, 3 );


/* ==========================================================================
   7. GOOGLE
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_schema' ) ) {
	/**
	 * Fuller job markup for professional jobs.
	 *
	 * The salary as a range, the real job type, the qualification and
	 * the experience wanted, and work from home said in the way Google
	 * reads it. Only ever added to markup that is already being printed,
	 * which happens only while the job is open.
	 *
	 * @since 1.0.0
	 * @param array $schema The JobPosting data.
	 * @param int   $job_id Job ID.
	 * @return array
	 */
	function kaamase_pro_schema( $schema, $job_id ) {

		$details = kaamase_pro_details( $job_id );

		if ( ! $details || empty( $schema ) ) {
			return $schema;
		}

		if ( $details['salary']['min'] > 0 && 'month' === $details['salary']['unit'] ) {

			$value = array(
				'@type'    => 'QuantitativeValue',
				'unitText' => 'MONTH',
			);

			if ( $details['salary']['max'] > $details['salary']['min'] ) {
				$value['minValue'] = $details['salary']['min'];
				$value['maxValue'] = $details['salary']['max'];
			} else {
				$value['value'] = $details['salary']['min'];
			}

			$schema['baseSalary'] = array(
				'@type'    => 'MonetaryAmount',
				'currency' => 'INR',
				'value'    => $value,
			);
		}

		$types = array(
			'full_time'  => 'FULL_TIME',
			'part_time'  => 'PART_TIME',
			'contract'   => 'CONTRACTOR',
			'internship' => 'INTERN',
			'temporary'  => 'TEMPORARY',
		);

		// Unanswered: leave the markup exactly as everyday jobs have it.
		if ( ! $details['complete'] ) {
			return $schema;
		}

		if ( isset( $types[ $details['job_type'] ] ) ) {
			$schema['employmentType'] = $types[ $details['job_type'] ];
		}

		$credentials = array(
			'10th'         => 'high school',
			'12th'         => 'high school',
			'diploma'      => 'professional certificate',
			'graduate'     => 'bachelor degree',
			'postgraduate' => 'postgraduate degree',
		);

		if ( 'any' === $details['qualification'] ) {
			$schema['educationRequirements'] = 'no requirements';
		} elseif ( isset( $credentials[ $details['qualification'] ] ) ) {
			$schema['educationRequirements'] = array(
				'@type'              => 'EducationalOccupationalCredential',
				'credentialCategory' => $credentials[ $details['qualification'] ],
			);
		}

		$schema['experienceRequirements'] = $details['experience_min'] > 0
			? array(
				'@type'              => 'OccupationalExperienceRequirements',
				'monthsOfExperience' => $details['experience_min'] * 12,
			)
			: 'no requirements';

		if ( 'remote' === $details['work_mode'] ) {
			$schema['jobLocationType']               = 'TELECOMMUTE';
			$schema['applicantLocationRequirements'] = array(
				'@type' => 'Country',
				'name'  => 'India',
			);
		}

		return $schema;
	}
}
add_filter( 'kaamase_job_schema', 'kaamase_pro_schema', 10, 2 );


/* ==========================================================================
   8. STILL HIRING?

   A professional job can run for three months, and the commonest way a
   job board loses people's trust is a job that was filled in week two
   and is still taking applications in week ten. So every thirty days
   the employer is asked, once, whether it is still open.
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_ask_still_hiring' ) ) {
	/**
	 * Ask one employer about one job.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @param int $days   How long it has been up.
	 * @return void
	 */
	function kaamase_pro_ask_still_hiring( $job_id, $days ) {

		$author = (int) get_post_field( 'post_author', $job_id );
		$user   = get_userdata( $author );

		if ( ! $user ) {
			return;
		}

		$write = static function () use ( $user, $author, $job_id, $days ) {

			// The title as typed. get_the_title() turns quotes into HTML entities, which a phone or an email shows as code.
			$title = (string) get_post_field( 'post_title', $job_id, 'raw' );

			if ( function_exists( 'kaamase_push_to_user' ) ) {
				kaamase_push_to_user(
					$author,
					__( 'Still hiring?', 'kaamase-core' ),
					sprintf(
						/* translators: 1: job title, 2: number of days */
						__( '"%1$s" has been up for %2$d days. If you have found somebody, close it so people stop applying.', 'kaamase-core' ),
						$title,
						$days
					),
					array(
						'type' => 'pro_job_check',
						'id'   => (int) $job_id,
					)
				);
			}

			if ( '' === (string) $user->user_email ) {
				return;
			}

			$closes = absint( get_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires', true ) );

			$lines = array(
				sprintf(
					/* translators: %s: person's name */
					__( 'Hello %s,', 'kaamase-core' ),
					$user->display_name
				),
				'',
				sprintf(
					/* translators: 1: job title, 2: number of days, 3: date */
					__( 'Your job "%1$s" has been up for %2$d days and stays open until %3$s.', 'kaamase-core' ),
					$title,
					$days,
					$closes ? wp_date( (string) get_option( 'date_format' ), $closes ) : ''
				),
				'',
				__( 'If you have already found somebody, please close it, so nobody spends time applying for a job that is filled. If you are still hiring, you do not need to do anything.', 'kaamase-core' ),
				'',
				function_exists( 'kaamase_page_url' ) ? kaamase_page_url( 'dashboard' ) : home_url( '/' ),
			);

			wp_mail(
				$user->user_email,
				sprintf(
					/* translators: %s: job title */
					__( 'Still hiring for %s?', 'kaamase-core' ),
					$title
				),
				implode( "\n", $lines )
			);
		};

		if ( function_exists( 'kaamase_locale_write_to' ) ) {
			kaamase_locale_write_to( $author, $write );
		} else {
			$write();
		}
	}
}

if ( ! function_exists( 'kaamase_pro_still_hiring_run' ) ) {
	/**
	 * The morning run.
	 *
	 * @since 1.0.0
	 * @return int How many were asked.
	 */
	function kaamase_pro_still_hiring_run() {

		$group = kaamase_pro_group_id();

		if ( ! $group ) {
			return 0;
		}

		$period = KAAMASE_PRO_ASK_EVERY_DAYS * DAY_IN_SECONDS;

		$jobs = get_posts(
			array(
				'post_type'      => 'kaamase_job',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'date_query'     => array(
					array(
						'column' => 'post_date_gmt',
						'before' => gmdate( 'Y-m-d H:i:s', time() - $period ),
					),
				),
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy'         => 'kaamase_trade',
						'field'            => 'term_id',
						'terms'            => $group,
						'include_children' => true,
					),
				),
			)
		);

		$told = 0;

		foreach ( (array) $jobs as $job_id ) {

			$job_id = (int) $job_id;
			$posted = (int) get_post_time( 'U', true, $job_id );
			$age    = time() - $posted;
			$round  = (int) floor( $age / $period );
			$closes = absint( get_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires', true ) );

			/*
			 * The count belongs to one posting. A repost starts a new one,
			 * so a reposted job is asked again thirty days on rather than
			 * never.
			 */
			$mark  = explode( ':', (string) get_post_meta( $job_id, KAAMASE_PRO_ASKED_KEY, true ) );
			$asked = ( 2 === count( $mark ) && (int) $mark[0] === $posted ) ? (int) $mark[1] : 0;

			if ( $round < 1 || $round <= $asked ) {
				continue;
			}

			// Marked first: whatever happens below, nobody is asked twice.
			update_post_meta( $job_id, KAAMASE_PRO_ASKED_KEY, $posted . ':' . $round );

			// Closing within three days anyway: nothing worth asking.
			if ( $closes && $closes - time() < 3 * DAY_IN_SECONDS ) {
				continue;
			}

			kaamase_pro_ask_still_hiring( $job_id, (int) floor( $age / DAY_IN_SECONDS ) );
			$told++;
		}

		return $told;
	}
}
add_action( 'kaamase_daily', 'kaamase_pro_still_hiring_run' );


/* ==========================================================================
   9. FINDING ONE: THE FILTERS ON THE PROFESSIONAL PAGES

   A trade page assumes it lists workers, so /trade/professional-jobs/
   and every professional category offered "Free for work now" and
   "Vouched only", which mean nothing on a list of office and bank jobs,
   and nothing a person choosing one actually asks: where, what kind,
   do I have the qualification, what does it pay.

   These pages get their own panel through kaamase_filter_bar_own in
   queries.php. The filters are plain GET values, so every filtered view
   is a link somebody can paste into a WhatsApp group, and they apply to
   these pages only. Every other list, and the app, is unchanged.
   ========================================================================== */

if ( ! function_exists( 'kaamase_pro_listing_slug' ) ) {
	/**
	 * The professional heading or category this page lists, or empty.
	 *
	 * @since 1.1.0
	 * @param WP_Query|null $query Optional. The query to ask; the main one by default.
	 * @return string Trade slug.
	 */
	function kaamase_pro_listing_slug( $query = null ) {

		$query = $query instanceof WP_Query ? $query : ( $GLOBALS['wp_query'] ?? null );

		if ( ! $query instanceof WP_Query || ! $query->is_tax( 'kaamase_trade' ) ) {
			return '';
		}

		$slug = sanitize_title( (string) $query->get( 'kaamase_trade' ) );

		return ( '' !== $slug && ( KAAMASE_PRO_GROUP === $slug || kaamase_pro_is_trade( $slug ) ) ) ? $slug : '';
	}
}

if ( ! function_exists( 'kaamase_pro_filter_vars' ) ) {
	/**
	 * Let WordPress read the three new filters from the address.
	 *
	 * @since 1.1.0
	 * @param string[] $vars Public query vars.
	 * @return string[]
	 */
	function kaamase_pro_filter_vars( $vars ) {

		$vars[] = 'kaamase_pro_type';   // Full time, part time and so on.
		$vars[] = 'kaamase_pro_qual';   // The reader's own qualification.
		$vars[] = 'kaamase_pro_salary'; // Lowest monthly salary wanted.

		return $vars;
	}
}
add_filter( 'query_vars', 'kaamase_pro_filter_vars' );

if ( ! function_exists( 'kaamase_pro_salary_steps' ) ) {
	/**
	 * The lowest monthly salaries offered in the filter.
	 *
	 * @since 1.1.0
	 * @return int[]
	 */
	function kaamase_pro_salary_steps() {
		return array( 10000, 15000, 20000, 30000, 50000 );
	}
}

if ( ! function_exists( 'kaamase_pro_filters_now' ) ) {
	/**
	 * The professional filters asked for, checked.
	 *
	 * Anything that is not one of the offered choices is ignored rather
	 * than trusted, so a hand edited address can only ever narrow the list
	 * in the ways the form offers.
	 *
	 * @since 1.1.0
	 * @param WP_Query|null $query Optional. The query to read; the main one by default.
	 * @return array{type:string,qual:string,salary:int}
	 */
	function kaamase_pro_filters_now( $query = null ) {

		$query   = $query instanceof WP_Query ? $query : ( $GLOBALS['wp_query'] ?? null );
		$get     = static function ( $key ) use ( $query ) {
			return $query instanceof WP_Query ? $query->get( $key ) : '';
		};
		$choices = kaamase_pro_choices();

		$type   = sanitize_key( (string) $get( 'kaamase_pro_type' ) );
		$qual   = sanitize_key( (string) $get( 'kaamase_pro_qual' ) );
		$salary = absint( $get( 'kaamase_pro_salary' ) );

		return array(
			'type'   => isset( $choices['job_type'][ $type ] ) ? $type : '',
			'qual'   => ( 'any' !== $qual && isset( $choices['qualification'][ $qual ] ) ) ? $qual : '',
			'salary' => in_array( $salary, kaamase_pro_salary_steps(), true ) ? $salary : 0,
		);
	}
}

if ( ! function_exists( 'kaamase_pro_filter_query' ) ) {
	/**
	 * Narrow a professional page to the filters asked for.
	 *
	 * After queries.php has built its own conditions (open jobs only, the
	 * district, the order), which are kept whole and joined to these.
	 *
	 * @since 1.1.0
	 * @param WP_Query $query The query about to run.
	 * @return void
	 */
	function kaamase_pro_filter_query( $query ) {

		if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || '' === kaamase_pro_listing_slug( $query ) ) {
			return;
		}

		$now = kaamase_pro_filters_now( $query );

		if ( ! $now['type'] && ! $now['qual'] && ! $now['salary'] ) {
			return;
		}

		$add = array();

		if ( $now['type'] ) {
			$add[] = array(
				'key'   => KAAMASE_META_PREFIX . 'pro_job_type',
				'value' => $now['type'],
			);
		}

		if ( $now['qual'] ) {

			/*
			 * Jobs this person is qualified for: those asking for no
			 * minimum, for less, or for exactly what they have, by the
			 * same rule Jobs for you uses. A job whose employer never
			 * answered the question is kept, not hidden for it.
			 */
			$allowed = array( 'any' );

			foreach ( array_keys( kaamase_pro_choices()['qualification'] ) as $want ) {

				if ( 'any' === $want ) {
					continue;
				}

				$fits = function_exists( 'kaamase_match_qualification_fits' )
					? kaamase_match_qualification_fits( $now['qual'], $want )
					: ( $now['qual'] === $want );

				if ( true === $fits ) {
					$allowed[] = $want;
				}
			}

			$add[] = array(
				'relation' => 'OR',
				array(
					'key'     => KAAMASE_META_PREFIX . 'pro_qualification',
					'value'   => $allowed,
					'compare' => 'IN',
				),
				array(
					'key'     => KAAMASE_META_PREFIX . 'pro_qualification',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( $now['salary'] ) {

			/*
			 * Monthly pay only, and a range counts when its top reaches
			 * the figure: ₹18,000 to ₹24,000 is a job that can pay
			 * ₹20,000. A job paid by the day never matches a monthly
			 * figure, whatever its number.
			 */
			$add[] = array(
				'relation' => 'AND',
				array(
					'key'   => KAAMASE_META_PREFIX . 'pay_unit',
					'value' => 'month',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => KAAMASE_META_PREFIX . 'pay_amount',
						'value'   => $now['salary'],
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => KAAMASE_META_PREFIX . 'pro_salary_max',
						'value'   => $now['salary'],
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				),
			);
		}

		$meta     = array( 'relation' => 'AND' );
		$existing = $query->get( 'meta_query' );

		if ( ! empty( $existing ) ) {
			$meta[] = $existing;
		}

		foreach ( $add as $clause ) {
			$meta[] = $clause;
		}

		$query->set( 'meta_query', $meta );

		// These are job filters; a person in one of these categories is not a job.
		$query->set( 'post_type', array( 'kaamase_job' ) );
	}
}
add_action( 'pre_get_posts', 'kaamase_pro_filter_query', 20 );

if ( ! function_exists( 'kaamase_pro_category_counts' ) ) {
	/**
	 * Open professional jobs per category, most first.
	 *
	 * Held for ten minutes: the heading page is busy and the answer moves
	 * slowly.
	 *
	 * @since 1.1.0
	 * @return int[] Count keyed by category slug.
	 */
	function kaamase_pro_category_counts() {

		$cached = get_transient( 'kaamase_pro_category_counts' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$slugs  = kaamase_pro_trade_slugs();
		$counts = array();

		if ( $slugs ) {

			$ids = get_posts(
				array(
					'post_type'        => 'kaamase_job',
					'post_status'      => 'publish',
					'posts_per_page'   => 500,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
					'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'kaamase_trade',
							'field'    => 'slug',
							'terms'    => $slugs,
						),
					),
					'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'OR',
						array(
							'key'     => KAAMASE_META_PREFIX . 'expires',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => KAAMASE_META_PREFIX . 'expires',
							'value'   => time(),
							'compare' => '>',
							'type'    => 'NUMERIC',
						),
					),
				)
			);

			if ( $ids ) {

				$terms = wp_get_object_terms( $ids, 'kaamase_trade', array( 'fields' => 'all_with_object_id' ) );

				foreach ( is_array( $terms ) ? $terms : array() as $term ) {
					if ( in_array( $term->slug, $slugs, true ) ) {
						$counts[ $term->slug ] = ( $counts[ $term->slug ] ?? 0 ) + 1;
					}
				}
			}

			arsort( $counts );
		}

		set_transient( 'kaamase_pro_category_counts', $counts, 10 * MINUTE_IN_SECONDS );

		return $counts;
	}
}

if ( ! function_exists( 'kaamase_pro_filter_bar' ) ) {
	/**
	 * The filter panel for the professional pages.
	 *
	 * Same look and behaviour as every other list's panel: shut until a
	 * filter is in use, a plain form, Clear when something is narrowed.
	 *
	 * @since 1.1.0
	 * @param string|null $markup Null, or a panel somebody else supplied.
	 * @param string      $type   Post type being listed.
	 * @return string|null
	 */
	function kaamase_pro_filter_bar( $markup, $type ) {

		unset( $type );

		$slug = kaamase_pro_listing_slug();

		if ( '' === $slug || is_string( $markup ) ) {
			return $markup;
		}

		$choices = kaamase_pro_choices();
		$now     = kaamase_pro_filters_now();
		$current = function_exists( 'kaamase_current_filters' ) ? kaamase_current_filters() : array();
		$sort    = (string) ( $current['sort'] ?? '' );
		$area    = (string) ( $current['district'] ?? '' );
		$open    = $now['type'] || $now['qual'] || $now['salary'] || '' !== $area || ( '' !== $sort && 'newest' !== $sort );

		// Newest and highest pay; urgent means nothing here, since a professional job is never urgent.
		$sorts = function_exists( 'kaamase_sort_options' ) ? kaamase_sort_options( 'kaamase_job' ) : array();
		unset( $sorts['urgent'] );

		ob_start();
		?>
		<details class="ka-filters-wrap"<?php echo $open ? ' open' : ''; ?>>

			<summary class="ka-filters-toggle">
				<?php echo esc_html( $open ? __( 'Change filters', 'kaamase-core' ) : __( 'Filter these results', 'kaamase-core' ) ); ?>
			</summary>

			<form class="ka-filters ka-card" method="get" action="">

				<div class="ka-field">
					<label class="ka-label" for="ka-pro-filter-district"><?php esc_html_e( 'District', 'kaamase-core' ); ?></label>
					<select class="ka-select" id="ka-pro-filter-district" name="kaamase_district">
						<option value=""><?php esc_html_e( 'All Nagaland', 'kaamase-core' ); ?></option>
						<?php foreach ( kaamase_district_choices() as $key => $name ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $area, $key ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ka-field">
					<label class="ka-label" for="ka-pro-filter-type"><?php esc_html_e( 'Job type', 'kaamase-core' ); ?></label>
					<select class="ka-select" id="ka-pro-filter-type" name="kaamase_pro_type">
						<option value=""><?php esc_html_e( 'Any job type', 'kaamase-core' ); ?></option>
						<?php foreach ( $choices['job_type'] as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $now['type'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ka-field">
					<label class="ka-label" for="ka-pro-filter-qual"><?php esc_html_e( 'Your qualification', 'kaamase-core' ); ?></label>
					<select class="ka-select" id="ka-pro-filter-qual" name="kaamase_pro_qual">
						<option value=""><?php esc_html_e( 'Show every job', 'kaamase-core' ); ?></option>
						<?php foreach ( $choices['qualification'] as $key => $label ) : ?>
							<?php
							if ( 'any' === $key ) {
								continue;
							}
							?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $now['qual'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="ka-hint"><?php esc_html_e( 'Shows the jobs you are qualified for.', 'kaamase-core' ); ?></p>
				</div>

				<div class="ka-field">
					<label class="ka-label" for="ka-pro-filter-salary"><?php esc_html_e( 'Salary', 'kaamase-core' ); ?></label>
					<select class="ka-select" id="ka-pro-filter-salary" name="kaamase_pro_salary">
						<option value=""><?php esc_html_e( 'Any salary', 'kaamase-core' ); ?></option>
						<?php foreach ( kaamase_pro_salary_steps() as $step ) : ?>
							<option value="<?php echo esc_attr( (string) $step ); ?>" <?php selected( $now['salary'], $step ); ?>>
								<?php
								/* translators: %s: monthly amount, such as 20,000 */
								echo esc_html( sprintf( __( 'At least ₹%s a month', 'kaamase-core' ), number_format_i18n( $step ) ) );
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<?php if ( $sorts ) : ?>
					<div class="ka-field">
						<label class="ka-label" for="ka-pro-filter-sort"><?php esc_html_e( 'Show', 'kaamase-core' ); ?></label>
						<select class="ka-select" id="ka-pro-filter-sort" name="kaamase_sort">
							<?php foreach ( $sorts as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="ka-cluster">
					<button class="ka-btn ka-btn--primary" type="submit"><?php esc_html_e( 'Show results', 'kaamase-core' ); ?></button>

					<?php if ( $open && function_exists( 'kaamase_current_path_url' ) ) : ?>
						<a class="ka-btn ka-btn--ghost ka-btn--sm" href="<?php echo esc_url( kaamase_current_path_url() ); ?>"><?php esc_html_e( 'Clear', 'kaamase-core' ); ?></a>
					<?php endif; ?>
				</div>

			</form>

		</details>
		<?php
		echo kaamase_pro_category_links( $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return (string) ob_get_clean();
	}
}
add_filter( 'kaamase_filter_bar_own', 'kaamase_pro_filter_bar', 10, 2 );

if ( ! function_exists( 'kaamase_pro_category_links' ) ) {
	/**
	 * Under the panel: the categories with open jobs, or the way back.
	 *
	 * On the heading page, a chip for every category that has an open job
	 * right now, with how many, so a bank officer does not scroll past
	 * nurses to find out there are three bank jobs. On a category page, a
	 * link back to every professional job.
	 *
	 * @since 1.1.0
	 * @param string $slug The page's heading or category.
	 * @return string Markup.
	 */
	function kaamase_pro_category_links( $slug ) {

		if ( KAAMASE_PRO_GROUP !== $slug ) {

			$all = get_term_link( KAAMASE_PRO_GROUP, 'kaamase_trade' );

			return is_wp_error( $all ) ? '' : sprintf(
				'<p class="ka-mt-4"><a href="%1$s">%2$s</a></p>',
				esc_url( $all ),
				esc_html__( 'All professional jobs', 'kaamase-core' )
			);
		}

		$counts = kaamase_pro_category_counts();

		if ( ! $counts ) {
			return '';
		}

		$out = '<ul class="ka-cluster ka-mt-4">';

		foreach ( $counts as $cat => $count ) {

			$term = get_term_by( 'slug', $cat, 'kaamase_trade' );
			$link = $term ? get_term_link( $term ) : '';

			if ( ! $term || is_wp_error( $link ) ) {
				continue;
			}

			$out .= sprintf(
				'<li><a class="ka-chip" href="%1$s">%2$s <span class="ka-mute">%3$s</span></a></li>',
				esc_url( $link ),
				esc_html( $term->name ),
				esc_html( number_format_i18n( $count ) )
			);
		}

		return $out . '</ul>';
	}
}
