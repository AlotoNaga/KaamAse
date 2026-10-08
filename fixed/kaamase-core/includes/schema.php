<?php
/**
 * Structured data.
 *
 * The machine readable version of a job, so Google can put it in its job
 * results rather than only in ordinary search.
 *
 * Why this is worth more than it looks
 * ------------------------------------
 * For a job board, JobPosting markup is usually the largest single
 * source of free traffic there is. A person in Kohima looking for mason
 * work does not search for Kaam Ase, because they have never heard of
 * it. They search for mason work in Kohima, and Google answers that with
 * a dedicated jobs panel populated entirely from this markup. A job
 * board without it is competing for ordinary blue links against sites
 * that are in the panel above them.
 *
 * It also costs nothing per job. The information is already on the page.
 *
 * The part that is easy to get wrong
 * ----------------------------------
 * Google is strict about expired postings, and rightly: a jobs panel
 * full of work that no longer exists is worse than no panel. Their rule
 * is that an expired posting must stop being marked up, while the page
 * itself should keep returning a normal response rather than a 404.
 *
 * That is exactly the shape this site is already in, since closed jobs
 * were made viewable so their links kept working. So the markup here is
 * emitted only while a job is genuinely open, and the closed page stays
 * alive without it. Marking up a closed job would risk the whole site
 * being dropped from the jobs panel, which is a large penalty for a
 * small oversight.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * Print the structured data in the head.
 *
 * @since 1.2.0
 * @return void
 */
function kaamase_print_schema() {

	$data = array();

	if ( is_singular( 'kaamase_job' ) ) {
		$data = kaamase_job_schema( get_the_ID() );
	} elseif ( is_front_page() ) {
		$data = kaamase_organisation_schema();
	}

	if ( empty( $data ) ) {
		return;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'kaamase_print_schema', 20 );


/* ==========================================================================
   THE JOB
   ========================================================================== */

/**
 * Build the JobPosting for one job.
 *
 * @since 1.2.0
 * @param int $job_id Job ID.
 * @return array Empty when this job should not be marked up.
 */
function kaamase_job_schema( $job_id ) {

	$job_id = (int) $job_id;

	/*
	 * Open jobs only. A filled, closed or expired posting must not carry
	 * this markup, and a held one has not been read by anybody yet.
	 */
	if ( ! $job_id || ! function_exists( 'kaamase_job_is_open' ) || ! function_exists( 'kaamase_field' ) ) {
		return array();
	}

	if ( ! kaamase_job_is_open( $job_id ) ) {
		return array();
	}

	$post = get_post( $job_id );

	if ( ! $post ) {
		return array();
	}

	$get = static function ( $field, $fallback = '' ) use ( $job_id ) {
		return function_exists( 'kaamase_field' )
			? kaamase_field( $job_id, $field, $fallback )
			: $fallback;
	};

	$schema = array(
		'@context'    => 'https://schema.org/',
		'@type'       => 'JobPosting',
		'title'       => wp_strip_all_tags( get_the_title( $job_id ) ),
		'description' => kaamase_job_schema_description( $post ),
		'identifier'  => array(
			'@type' => 'PropertyValue',
			'name'  => get_bloginfo( 'name' ),
			'value' => (string) $job_id,
		),
		'datePosted'  => get_post_time( 'c', true, $job_id ),
		'url'         => (string) get_permalink( $job_id ),
	);

	$expires = (int) get_post_meta( $job_id, KAAMASE_META_PREFIX . 'expires', true );

	if ( $expires ) {
		$schema['validThrough'] = gmdate( 'c', $expires );
	}

	$schema['hiringOrganization'] = kaamase_job_hiring_organisation( $job_id, $get );
	$schema['jobLocation']        = kaamase_job_location( $get );

	$pay = kaamase_job_salary( $get );

	if ( $pay ) {
		$schema['baseSalary'] = $pay;
	}

	$type = kaamase_job_employment_type( $get );

	if ( $type ) {
		$schema['employmentType'] = $type;
	}

	$needed = (int) $get( 'workers_needed' );

	if ( $needed > 0 ) {
		$schema['totalJobOpenings'] = $needed;
	}

	$start = (string) $get( 'start_date' );

	if ( $start ) {
		$schema['jobStartDate'] = $start;
	}

	/*
	 * Said out loud, because it is the thing this platform exists to
	 * promise and the thing a fraudulent listing elsewhere breaks.
	 */
	$schema['directApply'] = true;

	/**
	 * Filter the job structured data.
	 *
	 * @since 1.2.0
	 * @param array $schema The data.
	 * @param int   $job_id Job ID.
	 */
	return (array) apply_filters( 'kaamase_job_schema', $schema, $job_id );
}

/**
 * The description, as Google wants it.
 *
 * It expects a full description including HTML, not a one line summary,
 * and a posting with a thin one competes badly. The page content is used
 * as written, with the useful details appended so a short post is still
 * a complete one.
 *
 * @since 1.2.0
 * @param WP_Post $post The job.
 * @return string
 */
function kaamase_job_schema_description( $post ) {

	$body = trim( wp_kses_post( (string) $post->post_content ) );

	$extra = array();
	$get   = static function ( $field ) use ( $post ) {
		return function_exists( 'kaamase_field' ) ? kaamase_field( $post->ID, $field, '' ) : '';
	};

	$duration = (string) $get( 'duration' );

	if ( $duration ) {
		/* translators: %s: how long the work lasts */
		$extra[] = sprintf( __( 'How long the work lasts: %s', 'kaamase-core' ), $duration );
	}

	if ( $get( 'food_provided' ) ) {
		$extra[] = __( 'Food is provided.', 'kaamase-core' );
	}

	if ( $get( 'stay_provided' ) ) {
		$extra[] = __( 'A place to stay is provided.', 'kaamase-core' );
	}

	if ( $extra ) {
		$body .= '<p>' . implode( ' ', array_map( 'esc_html', $extra ) ) . '</p>';
	}

	return $body ? $body : wp_strip_all_tags( get_the_title( $post->ID ) );
}

/**
 * Who is hiring.
 *
 * Falls back to the site itself when a job has no employer profile
 * attached. Google requires the property, and leaving it out loses the
 * whole posting over a field the reader would not have looked at.
 *
 * @since 1.2.0
 * @param int      $job_id Job ID.
 * @param callable $get    Field reader.
 * @return array
 */
function kaamase_job_hiring_organisation( $job_id, $get ) {

	$employer_id = (int) $get( 'employer_id' );

	$name = $employer_id && 'publish' === get_post_status( $employer_id )
		? get_the_title( $employer_id )
		: (string) $get( 'employer_name' );

	if ( '' === trim( (string) $name ) ) {
		$name = get_bloginfo( 'name' );
	}

	$organisation = array(
		'@type' => 'Organization',
		'name'  => wp_strip_all_tags( (string) $name ),
	);

	if ( $employer_id && 'publish' === get_post_status( $employer_id ) ) {
		$organisation['sameAs'] = (string) get_permalink( $employer_id );
	}

	unset( $job_id );

	return $organisation;
}

/**
 * Where the work is.
 *
 * District and state always, town when it was given. Nagaland and IN are
 * fixed because this platform does not operate anywhere else, and a
 * guessed country is worse than a stated one.
 *
 * @since 1.2.0
 * @param callable $get Field reader.
 * @return array
 */
function kaamase_job_location( $get ) {

	$district = (string) $get( 'district' );
	$town     = (string) $get( 'town' );

	/*
	 * The district is stored as a slug. Resolve it to the canonical name,
	 * so the locality reads the way the page shows it rather than as a
	 * lowercase slug, and to the headquarters PIN, so postalCode is filled.
	 * Google flags a missing postalCode, and the structured data has to
	 * match what the reader sees on the page.
	 */
	$slug = $district;

	if ( $district && function_exists( 'kaamase_match_district' ) ) {
		$resolved = (string) kaamase_match_district( $district );

		if ( '' !== $resolved ) {
			$slug = $resolved;
		}
	}

	$name = ( '' !== $slug && function_exists( 'kaamase_district_name' ) ) ? kaamase_district_name( $slug ) : '';
	$pin  = ( '' !== $slug && function_exists( 'kaamase_district_pin' ) ) ? kaamase_district_pin( $slug ) : '';

	$locality = '' !== $name ? $name : $district;

	$address = array(
		'@type'          => 'PostalAddress',
		'addressRegion'  => 'Nagaland',
		'addressCountry' => 'IN',
	);

	if ( $town ) {
		$address['streetAddress'] = wp_strip_all_tags( $town );
	}

	if ( $locality ) {
		$address['addressLocality'] = wp_strip_all_tags( $locality );
	} elseif ( $town ) {
		$address['addressLocality'] = wp_strip_all_tags( $town );
	}

	if ( '' !== $pin ) {
		$address['postalCode'] = $pin;
	}

	return array(
		'@type'   => 'Place',
		'address' => $address,
	);
}

/**
 * What the job pays.
 *
 * Given as a unit price rather than converted to a yearly figure.
 * Multiplying a day rate by an invented number of working days would
 * produce a salary nobody agreed to, on a platform where the day rate is
 * the thing being negotiated.
 *
 * @since 1.2.0
 * @param callable $get Field reader.
 * @return array Empty when no pay was stated.
 */
function kaamase_job_salary( $get ) {

	$amount = (int) $get( 'pay_amount' );

	if ( $amount <= 0 ) {
		return array();
	}

	$units = array(
		'hour'  => 'HOUR',
		'day'   => 'DAY',
		'month' => 'MONTH',
		'job'   => 'DAY',
	);

	$unit = (string) $get( 'pay_unit', 'day' );

	/*
	 * A price for the whole job has no schema.org equivalent, so it is
	 * left off rather than described as something it is not.
	 */
	if ( 'job' === $unit ) {
		return array();
	}

	return array(
		'@type'    => 'MonetaryAmount',
		'currency' => 'INR',
		'value'    => array(
			'@type'    => 'QuantitativeValue',
			'value'    => $amount,
			'unitText' => $units[ $unit ] ?? 'DAY',
		),
	);
}

/**
 * Full time, part time or contract.
 *
 * Read from how the pay is counted, which is the only signal this form
 * collects. A month rate is somebody taken on properly; a day or hour
 * rate on a building site is daily hire, which is closest to TEMPORARY.
 *
 * @since 1.2.0
 * @param callable $get Field reader.
 * @return string
 */
function kaamase_job_employment_type( $get ) {

	$unit = (string) $get( 'pay_unit', 'day' );

	if ( 'month' === $unit ) {
		return 'FULL_TIME';
	}

	if ( 'job' === $unit ) {
		return 'CONTRACTOR';
	}

	return 'TEMPORARY';
}


/* ==========================================================================
   THE COMPANY
   ========================================================================== */

/**
 * Organisation data for the front page.
 *
 * Small, and worth having. It is what lets a search for the company name
 * return something that looks like a real business rather than a page,
 * which matters when part of the pitch is that this is registered and
 * run from Dimapur rather than an app that has never been here.
 *
 * @since 1.2.0
 * @return array
 */
function kaamase_organisation_schema() {

	$schema = array(
		'@context' => 'https://schema.org/',
		'@type'    => 'Organization',
		'name'     => get_bloginfo( 'name' ),
		'url'      => home_url( '/' ),
		'address'  => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Dimapur',
			'addressRegion'   => 'Nagaland',
			'postalCode'      => '797112',
			'addressCountry'  => 'IN',
		),
	);

	$description = get_bloginfo( 'description' );

	if ( $description ) {
		$schema['description'] = wp_strip_all_tags( $description );
	}

	$logo = get_theme_mod( 'custom_logo' );

	if ( $logo ) {

		$src = wp_get_attachment_image_src( (int) $logo, 'full' );

		if ( ! empty( $src[0] ) ) {
			$schema['logo'] = (string) $src[0];
		}
	}

	/**
	 * Filter the organisation structured data.
	 *
	 * @since 1.2.0
	 * @param array $schema The data.
	 */
	return (array) apply_filters( 'kaamase_organisation_schema', $schema );
}