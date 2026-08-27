<?php
/**
 * Fields.
 *
 * One schema drives everything: registration, sanitising, permissions,
 * REST exposure and later the front end forms. A field is defined once,
 * here, and nowhere else. The alternative, where a field is declared in
 * a form, sanitised in a handler and exposed in a template, is how a
 * phone number ends up public because three files disagreed about
 * whether it was private.
 *
 * The rule this file exists to enforce
 * ------------------------------------
 * Every field is marked private or not. A private field cannot be read
 * by a template, cannot appear in the REST API, and cannot be seen by
 * anybody except the person it belongs to and a site administrator.
 *
 * Enforcement happens at the READ, not at the render. The theme calls
 * kaamase_field() to get any value, and the filter at the bottom of this
 * file returns nothing for a private field unless the viewer is entitled
 * to it. So a template author cannot leak a phone number by forgetting a
 * check, because there is no check to forget. They simply get an empty
 * string.
 *
 * That matters because the leak that actually happens is never the one
 * somebody planned. It is a card template written in a hurry six months
 * from now that echoes a field without thinking.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. SCHEMA
   ========================================================================== */

if ( ! function_exists( 'kaamase_field_schema' ) ) {
	/**
	 * Every field on every post type.
	 *
	 * Keys used in each definition:
	 *
	 *   type      string, int, float, bool. Drives sanitising.
	 *   private   true means the value is never shown to anyone but the
	 *             owner and an administrator. Phone numbers, always.
	 *   locked    true means only an administrator or the plugin itself
	 *             may write it. Ratings and counters, so a user cannot
	 *             edit their own score.
	 *   choices   restricts the value to a fixed set.
	 *   default   returned when nothing is stored.
	 *
	 * @since 1.0.0
	 * @return array[] Field definitions keyed by post type then field.
	 */
	function kaamase_field_schema() {

		static $schema = null;

		if ( null !== $schema ) {
			return $schema;
		}

		/*
		 * Fields shared by workers and teams. Both are somebody offering
		 * labour, so they carry the same commercial information.
		 */
		$offer = array(

			'phone' => array(
				'type'    => 'phone',
				'private' => true,
				'label'   => __( 'Phone number', 'kaamase-core' ),
			),

			'whatsapp' => array(
				'type'    => 'phone',
				'private' => true,
				'label'   => __( 'WhatsApp number', 'kaamase-core' ),
			),

			'district' => array(
				'type'  => 'district',
				'label' => __( 'District', 'kaamase-core' ),
			),

			'town' => array(
				'type'  => 'string',
				'label' => __( 'Town or village', 'kaamase-core' ),
			),

			'years_experience' => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Years of experience', 'kaamase-core' ),
			),

			'day_rate' => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Expected day rate', 'kaamase-core' ),
			),

			'month_rate' => array(
				'type'    => 'int',
				'default' => 0,
				'label'   => __( 'Expected monthly pay', 'kaamase-core' ),
			),

			'availability' => array(
				'type'    => 'choice',
				'choices' => array( 'live', 'busy', 'leave' ),
				'default' => 'live',
				'label'   => __( 'Availability', 'kaamase-core' ),
			),

			'travel_radius' => array(
				'type'    => 'choice',
				'choices' => array( 'town', 'district', 'state' ),
				'default' => 'district',
				'label'   => __( 'Willing to travel', 'kaamase-core' ),
			),

			/*
			 * Vouching.
			 *
			 * The strongest trust signal available on this platform, and
			 * the one no outside competitor can replicate. The voucher's
			 * name and role are shown. Their phone number is private, and
			 * reachable only through the same masked contact route as
			 * everything else, so that a list of every GB and colony
			 * council member in the state cannot be scraped off the site.
			 */
			'vouched_by' => array(
				'type'  => 'string',
				'label' => __( 'Vouched by', 'kaamase-core' ),
			),

			'vouched_role' => array(
				'type'    => 'choice',
				'choices' => array( 'gb', 'colony', 'village', 'church', 'union', 'employer', 'other' ),
				'label'   => __( 'Their position', 'kaamase-core' ),
			),

			'vouched_phone' => array(
				'type'    => 'phone',
				'private' => true,
				'label'   => __( 'Voucher phone number', 'kaamase-core' ),
			),

			// Written by the plugin only.
			'verified' => array(
				'type'    => 'bool',
				'locked'  => true,
				'default' => false,
				'label'   => __( 'Verified', 'kaamase-core' ),
			),

			'rating_average' => array(
				'type'    => 'float',
				'locked'  => true,
				'default' => 0,
				'label'   => __( 'Average rating', 'kaamase-core' ),
			),

			'rating_count' => array(
				'type'    => 'int',
				'locked'  => true,
				'default' => 0,
				'label'   => __( 'Number of ratings', 'kaamase-core' ),
			),

			'jobs_completed' => array(
				'type'    => 'int',
				'locked'  => true,
				'default' => 0,
				'label'   => __( 'Jobs completed', 'kaamase-core' ),
			),
		);

		$schema = array(

			'kaamase_worker' => $offer,

			'kaamase_gang' => array_merge(
				$offer,
				array(
					'leader_name' => array(
						'type'  => 'string',
						'label' => __( 'Team leader', 'kaamase-core' ),
					),
					'headcount'   => array(
						'type'    => 'int',
						'default' => 0,
						'label'   => __( 'Number of workers', 'kaamase-core' ),
					),
				)
			),

			'kaamase_employer' => array(

				'contact_name' => array(
					'type'  => 'string',
					'label' => __( 'Contact person', 'kaamase-core' ),
				),

				'phone' => array(
					'type'    => 'phone',
					'private' => true,
					'label'   => __( 'Phone number', 'kaamase-core' ),
				),

				'employer_type' => array(
					'type'    => 'choice',
					'choices' => array( 'individual', 'contractor', 'company' ),
					'default' => 'individual',
					'label'   => __( 'Type', 'kaamase-core' ),
				),

				'gst' => array(
					'type'  => 'string',
					'label' => __( 'GST number', 'kaamase-core' ),
				),

				'district' => array(
					'type'  => 'district',
					'label' => __( 'District', 'kaamase-core' ),
				),

				'town' => array(
					'type'  => 'string',
					'label' => __( 'Town', 'kaamase-core' ),
				),

				'verified' => array(
					'type'    => 'bool',
					'locked'  => true,
					'default' => false,
					'label'   => __( 'Verified', 'kaamase-core' ),
				),

				'rating_average' => array(
					'type'    => 'float',
					'locked'  => true,
					'default' => 0,
					'label'   => __( 'Average rating', 'kaamase-core' ),
				),

				'rating_count' => array(
					'type'    => 'int',
					'locked'  => true,
					'default' => 0,
					'label'   => __( 'Number of ratings', 'kaamase-core' ),
				),

				'hires_made' => array(
					'type'    => 'int',
					'locked'  => true,
					'default' => 0,
					'label'   => __( 'Workers hired', 'kaamase-core' ),
				),
			),

			'kaamase_job' => array(

				'employer_name' => array(
					'type'  => 'string',
					'label' => __( 'Hiring as', 'kaamase-core' ),
				),

				'employer_id' => array(
					'type'    => 'int',
					'locked'  => true,
					'default' => 0,
					'label'   => __( 'Employer profile', 'kaamase-core' ),
				),

				'contact_phone' => array(
					'type'    => 'phone',
					'private' => true,
					'label'   => __( 'Contact number', 'kaamase-core' ),
				),

				'pay_amount' => array(
					'type'    => 'int',
					'default' => 0,
					'label'   => __( 'Pay', 'kaamase-core' ),
				),

				'pay_unit' => array(
					'type'    => 'choice',
					'choices' => array( 'day', 'month', 'job', 'hour' ),
					'default' => 'day',
					'label'   => __( 'Pay period', 'kaamase-core' ),
				),

				'workers_needed' => array(
					'type'    => 'int',
					'default' => 1,
					'label'   => __( 'Workers needed', 'kaamase-core' ),
				),

				'start_date' => array(
					'type'  => 'date',
					'label' => __( 'Start date', 'kaamase-core' ),
				),

				'duration' => array(
					'type'  => 'string',
					'label' => __( 'How long the work lasts', 'kaamase-core' ),
				),

				'district' => array(
					'type'  => 'district',
					'label' => __( 'District', 'kaamase-core' ),
				),

				'town' => array(
					'type'  => 'string',
					'label' => __( 'Town or village', 'kaamase-core' ),
				),

				'urgent' => array(
					'type'    => 'bool',
					'default' => false,
					'label'   => __( 'Urgent hiring', 'kaamase-core' ),
				),

				'food_provided' => array(
					'type'    => 'bool',
					'default' => false,
					'label'   => __( 'Food provided', 'kaamase-core' ),
				),

				'stay_provided' => array(
					'type'    => 'bool',
					'default' => false,
					'label'   => __( 'Accommodation provided', 'kaamase-core' ),
				),

				'transport_provided' => array(
					'type'    => 'bool',
					'default' => false,
					'label'   => __( 'Transport provided', 'kaamase-core' ),
				),

				'job_status' => array(
					'type'    => 'choice',
					'choices' => array( 'open', 'filled', 'closed', 'expired' ),
					'default' => 'open',
					'label'   => __( 'Status', 'kaamase-core' ),
				),

				'expires' => array(
					'type'    => 'int',
					'locked'  => true,
					'default' => 0,
					'label'   => __( 'Closes on', 'kaamase-core' ),
				),

				'filled_count' => array(
					'type'    => 'int',
					'locked'  => true,
					'default' => 0,
					'label'   => __( 'Workers hired so far', 'kaamase-core' ),
				),
			),
		);

		/**
		 * Filter the field schema.
		 *
		 * @since 1.0.0
		 * @param array[] $schema Field definitions keyed by post type.
		 */
		$schema = (array) apply_filters( 'kaamase_field_schema', $schema );

		return $schema;
	}
}

if ( ! function_exists( 'kaamase_get_field_definition' ) ) {
	/**
	 * One field definition.
	 *
	 * @since 1.0.0
	 * @param string $post_type Post type.
	 * @param string $key       Field key without the prefix.
	 * @return array|null Definition, or null when the field is unknown.
	 */
	function kaamase_get_field_definition( $post_type, $key ) {

		$schema = kaamase_field_schema();

		if ( ! isset( $schema[ $post_type ][ $key ] ) ) {
			return null;
		}

		return wp_parse_args(
			$schema[ $post_type ][ $key ],
			array(
				'type'    => 'string',
				'private' => false,
				'locked'  => false,
				'choices' => array(),
				'default' => '',
				'label'   => '',
			)
		);
	}
}


/* ==========================================================================
   2. REGISTRATION
   ========================================================================== */

if ( ! function_exists( 'kaamase_register_meta' ) ) {
	/**
	 * Register every field with WordPress.
	 *
	 * show_in_rest is false for private fields with no exception. The
	 * REST API is public by default and a phone number in a REST response
	 * is a phone number available to anybody who knows the URL, signed in
	 * or not.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_register_meta() {

		foreach ( kaamase_field_schema() as $post_type => $fields ) {

			foreach ( array_keys( $fields ) as $key ) {

				$field = kaamase_get_field_definition( $post_type, $key );

				if ( ! $field ) {
					continue;
				}

				register_post_meta(
					$post_type,
					KAAMASE_META_PREFIX . $key,
					array(
						'type'              => kaamase_meta_primitive( $field['type'] ),
						'description'       => $field['label'],
						'single'            => true,
						'default'           => $field['default'],
						'show_in_rest'      => ! $field['private'],
						'sanitize_callback' => static function ( $value ) use ( $post_type, $key ) {
							return kaamase_sanitize_field( $value, $post_type, $key );
						},
						'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) use ( $field ) {
							unset( $allowed, $meta_key );

							// Locked fields are written by the plugin, never by a user.
							if ( $field['locked'] ) {
								return current_user_can( 'manage_options' );
							}

							return current_user_can( 'edit_post', $post_id );
						},
					)
				);
			}
		}
	}
}
add_action( 'init', 'kaamase_register_meta', 11 );

if ( ! function_exists( 'kaamase_meta_primitive' ) ) {
	/**
	 * Map a schema type to a WordPress meta type.
	 *
	 * @since 1.0.0
	 * @param string $type Schema type.
	 * @return string One of string, integer, number or boolean.
	 */
	function kaamase_meta_primitive( $type ) {

		switch ( $type ) {
			case 'int':
				return 'integer';
			case 'float':
				return 'number';
			case 'bool':
				return 'boolean';
			default:
				return 'string';
		}
	}
}


/* ==========================================================================
   3. SANITISING
   ========================================================================== */

if ( ! function_exists( 'kaamase_sanitize_field' ) ) {
	/**
	 * Clean a value according to its schema type.
	 *
	 * @since 1.0.0
	 * @param mixed  $value     Raw value.
	 * @param string $post_type Post type.
	 * @param string $key       Field key.
	 * @return mixed Clean value.
	 */
	function kaamase_sanitize_field( $value, $post_type, $key ) {

		$field = kaamase_get_field_definition( $post_type, $key );

		if ( ! $field ) {
			return '';
		}

		switch ( $field['type'] ) {

			case 'int':
				return absint( $value );

			case 'float':
				return round( (float) $value, 2 );

			case 'bool':
				return (bool) $value;

			case 'choice':
				$value = sanitize_key( $value );
				return in_array( $value, (array) $field['choices'], true ) ? $value : $field['default'];

			case 'district':
				return function_exists( 'kaamase_match_district' ) ? kaamase_match_district( $value ) : '';

			case 'date':
				$value = sanitize_text_field( $value );
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';

			case 'phone':
				return kaamase_sanitize_phone( $value );

			default:
				return sanitize_text_field( $value );
		}
	}
}

if ( ! function_exists( 'kaamase_sanitize_phone' ) ) {
	/**
	 * Normalise an Indian mobile number.
	 *
	 * Stored as ten digits with no country code, no spaces and no
	 * punctuation, whatever the person typed. People write the same
	 * number as 9856012345, +91 98560 12345, 098560-12345 and 0091
	 * 9856012345. Stored as typed, those are four different numbers, and
	 * duplicate detection, masking and blocking an abusive account all
	 * quietly fail.
	 *
	 * @since 1.0.0
	 * @param string $value Raw input.
	 * @return string Ten digits, or an empty string when it cannot be one.
	 */
	function kaamase_sanitize_phone( $value ) {

		$digits = preg_replace( '/\D+/', '', (string) $value );

		if ( '' === $digits ) {
			return '';
		}

		/*
		 * Strip a country code or a trunk zero.
		 *
		 * The 0091 branch used to test for a length of 13. 0091 is four
		 * digits and an Indian mobile is ten, so the string it was
		 * meant to catch is fourteen long and the branch never once
		 * matched. Anybody who wrote their number the way it is printed
		 * on a visiting card, 0091 98560 12345, fell through every
		 * branch, failed the ten digit check, and was told at
		 * registration that their phone number did not look right.
		 *
		 * Peeled in order now rather than matched against fixed
		 * lengths, so 0091, 0091 with spaces, +91, 91 and a plain
		 * trunk zero all arrive at the same ten digits.
		 */

		// International access code, however it was written.
		if ( str_starts_with( $digits, '00' ) ) {
			$digits = substr( $digits, 2 );
		}

		// Country code. Length checked, so a real number starting 91 is safe.
		if ( 12 === strlen( $digits ) && str_starts_with( $digits, '91' ) ) {
			$digits = substr( $digits, 2 );
		}

		// Trunk zero.
		if ( 11 === strlen( $digits ) && str_starts_with( $digits, '0' ) ) {
			$digits = substr( $digits, 1 );
		}

		// Indian mobile numbers are ten digits starting 6 to 9.
		if ( 10 !== strlen( $digits ) || ! preg_match( '/^[6-9]/', $digits ) ) {
			return '';
		}

		return $digits;
	}
}


/* ==========================================================================
   4. ACCESS
   ========================================================================== */

if ( ! function_exists( 'kaamase_can_see_private' ) ) {
	/**
	 * Whether the current user may see private fields on a post.
	 *
	 * Owner or administrator. Nobody else, ever, including other signed
	 * in users. Being registered on a hiring platform does not entitle
	 * somebody to every worker's phone number, and treating it as if it
	 * does is how a scraper gets the whole list with one free account.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	function kaamase_can_see_private( $post_id ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( function_exists( 'kaamase_user_owns' ) && kaamase_user_owns( $post_id ) ) {
			return true;
		}

		/*
		 * Field agents, on the profiles they are responsible for.
		 *
		 * The launch plan is somebody walking to a labour point in
		 * Dimapur and registering workers by hand, because most of them
		 * will not do it themselves. That person could create and edit a
		 * worker profile but could not see the phone number on it, not
		 * even the one they had just typed in, because this test was
		 * owner or administrator and an agent is neither. They could not
		 * check their own typing, and they could not ring the worker
		 * back to correct it.
		 *
		 * Scoped to the post type they already have moderator rights
		 * over, so it grants nothing new: an agent who can edit a
		 * profile can already change that number. It does not extend to
		 * employers or to jobs.
		 */
		$post = get_post( $post_id );

		if ( $post && in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true ) ) {
			return current_user_can( 'edit_others_kaamase_workers' );
		}

		return false;
	}
}

if ( ! function_exists( 'kaamase_filter_private_fields' ) ) {
	/**
	 * Withhold private values from anyone not entitled to them.
	 *
	 * Hooked onto the theme's kaamase_field(). This is the enforcement
	 * point for the whole rule at the top of this file. A template asking
	 * for a phone number gets an empty string, and there is no way for a
	 * template author to bypass it by accident.
	 *
	 * @since 1.0.0
	 * @param mixed  $value   Stored value.
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key.
	 * @param mixed  $default Default value.
	 * @return mixed Value, or an empty string when withheld.
	 */
	function kaamase_filter_private_fields( $value, $post_id, $key, $default ) {

		unset( $default );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return $value;
		}

		$field = kaamase_get_field_definition( $post->post_type, $key );

		if ( ! $field || ! $field['private'] ) {
			return $value;
		}

		return kaamase_can_see_private( $post_id ) ? $value : '';
	}
}
add_filter( 'kaamase_field', 'kaamase_filter_private_fields', 10, 4 );

if ( ! function_exists( 'kaamase_read_field' ) ) {
	/**
	 * Read a field, bypassing the privacy filter.
	 *
	 * For plugin internals only. The masked contact system needs the real
	 * number in order to place a call without revealing it, and the
	 * notification system needs it to send a message.
	 *
	 * Never call this from a template. If you find yourself wanting to,
	 * the answer is that the value should not be on that screen.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key without the prefix.
	 * @return mixed Stored value.
	 */
	function kaamase_read_field( $post_id, $key ) {

		$post = get_post( $post_id );

		if ( ! $post ) {
			return '';
		}

		$value = get_post_meta( $post_id, KAAMASE_META_PREFIX . $key, true );
		$field = kaamase_get_field_definition( $post->post_type, $key );

		if ( ( '' === $value || null === $value ) && $field ) {
			return $field['default'];
		}

		return $value;
	}
}

if ( ! function_exists( 'kaamase_meta_array' ) ) {
	/**
	 * Read a stored value that is meant to be a list.
	 *
	 * The bug this exists to kill
	 * ---------------------------
	 * get_post_meta returns an empty string when nothing is stored, and
	 * get_transient returns false. Casting either to an array does NOT
	 * give an empty array. PHP wraps the scalar, so you get array( 0 =>
	 * '' ), which has a count of one and is not empty.
	 *
	 * Every list in this plugin was read that way, and the consequences
	 * were real rather than theoretical:
	 *
	 *   - the contact log picked up a junk first entry on the very first
	 *     reveal, and every later read of it hit $entry['user'] on a
	 *     string, which is a fatal in PHP 8
	 *   - the daily log trimming task fatalled for the same reason, so it
	 *     silently stopped running
	 *   - the hire record grew the same junk entry, so the rating check
	 *     that walks it fatalled
	 *   - a contact quota that had never been used already counted one
	 *     lookup against the day, because count( array( false ) ) is 1
	 *
	 * One helper, used everywhere a stored list is read.
	 *
	 * @since 1.2.0
	 * @param mixed $value Raw stored value.
	 * @return array Clean list.
	 */
	function kaamase_meta_array( $value ) {

		if ( is_array( $value ) ) {
			return $value;
		}

		if ( '' === $value || null === $value || false === $value ) {
			return array();
		}

		return (array) $value;
	}
}

if ( ! function_exists( 'kaamase_save_field' ) ) {
	/**
	 * Write a field after sanitising it.
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key without the prefix.
	 * @param mixed  $value   Raw value.
	 * @return bool Whether the value was written.
	 */
	function kaamase_save_field( $post_id, $key, $value ) {

		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		$field = kaamase_get_field_definition( $post->post_type, $key );

		if ( ! $field ) {
			return false;
		}

		$clean = kaamase_sanitize_field( $value, $post->post_type, $key );

		/*
		 * update_post_meta returns false both when the write fails and
		 * when the value was already what you asked for. Those are not
		 * the same outcome, and a caller checking the return would treat
		 * a successful no-op save as an error. Report on the stored
		 * value instead of on whether a row changed.
		 *
		 * Compared after sanitising both sides, not with ===.
		 *
		 * WordPress stores meta as strings. A saved integer 5 reads back
		 * as '5', and a saved false reads back as ''. A strict
		 * comparison against the value we passed in therefore returned
		 * false for every int, float and bool field on the platform,
		 * which is the exact opposite of what this block was written to
		 * fix. Nothing checks the return today, so it never bit; it was
		 * a trap set for whoever checked it first.
		 */
		update_post_meta( $post_id, KAAMASE_META_PREFIX . $key, $clean );

		$stored = kaamase_sanitize_field(
			get_post_meta( $post_id, KAAMASE_META_PREFIX . $key, true ),
			$post->post_type,
			$key
		);

		return $stored === $clean;
	}
}


/* ==========================================================================
   5. REST HARDENING

   register_post_meta already keeps private fields out of the meta
   object. This closes the two remaining doors: querying by a private
   field, and the search endpoint matching against one.
   ========================================================================== */

if ( ! function_exists( 'kaamase_block_private_meta_query' ) ) {
	/**
	 * Refuse REST requests that try to filter by a private field.
	 *
	 * Without this, an unauthenticated request can confirm whether a
	 * given phone number is registered by filtering on it and seeing
	 * whether anything comes back. That is enough to check a list of
	 * numbers against the platform one at a time.
	 *
	 * @since 1.0.0
	 * @param mixed           $result  Response to replace the default.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed Result, or a WP_Error when the request is refused.
	 */
	function kaamase_block_private_meta_query( $result, $server, $request ) {

		unset( $server );

		if ( current_user_can( 'manage_options' ) ) {
			return $result;
		}

		$params = (array) $request->get_params();
		$flat   = implode( ' ', array_map( 'strval', array_keys( $params ) ) ) . ' ' . wp_json_encode( $params );

		foreach ( kaamase_private_field_keys() as $key ) {

			if ( false !== strpos( $flat, KAAMASE_META_PREFIX . $key ) ) {
				return new WP_Error(
					'kaamase_forbidden_filter',
					__( 'That filter is not available.', 'kaamase-core' ),
					array( 'status' => 400 )
				);
			}
		}

		return $result;
	}
}
add_filter( 'rest_pre_dispatch', 'kaamase_block_private_meta_query', 10, 3 );

if ( ! function_exists( 'kaamase_private_field_keys' ) ) {
	/**
	 * Every field key marked private, across all post types.
	 *
	 * @since 1.0.0
	 * @return string[] Unique field keys without the prefix.
	 */
	function kaamase_private_field_keys() {

		static $keys = null;

		if ( null !== $keys ) {
			return $keys;
		}

		$keys = array();

		foreach ( kaamase_field_schema() as $post_type => $fields ) {
			foreach ( array_keys( $fields ) as $key ) {

				$field = kaamase_get_field_definition( $post_type, $key );

				if ( $field && $field['private'] ) {
					$keys[] = $key;
				}
			}
		}

		$keys = array_values( array_unique( $keys ) );

		return $keys;
	}
}

if ( ! function_exists( 'kaamase_block_meta_search' ) ) {
	/**
	 * Keep private fields out of front end search.
	 *
	 * A search plugin installed later will happily index post meta and
	 * start returning results for a phone number typed into the search
	 * box. This declares the keys that must never be searchable so that
	 * anything respecting the filter behaves.
	 *
	 * @since 1.0.0
	 * @param string[] $excluded Meta keys excluded from search.
	 * @return string[] Filtered list.
	 */
	function kaamase_block_meta_search( $excluded ) {

		foreach ( kaamase_private_field_keys() as $key ) {
			$excluded[] = KAAMASE_META_PREFIX . $key;
		}

		return array_unique( (array) $excluded );
	}
}
add_filter( 'kaamase_search_excluded_meta', 'kaamase_block_meta_search' );
