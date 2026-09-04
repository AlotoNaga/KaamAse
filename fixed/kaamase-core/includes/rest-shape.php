<?php
/**
 * API response shapes.
 *
 * Every object the app receives is built here and nowhere else.
 *
 * The rule this file exists to enforce
 * ------------------------------------
 * No phone number, ever, in any response, for any reason.
 *
 * fields.php makes the same argument about templates: the leak that
 * actually happens is never the one somebody planned, it is a card
 * written in a hurry six months from now that echoes a field without
 * thinking. An API is worse than a template in one specific way. A
 * template leak is visible on the page, so somebody eventually sees it.
 * A JSON leak sits in a response body that nobody looks at, and it is
 * readable by anybody who opens the developer tools once.
 *
 * So the shapers below read through kaamase_field(), which is the
 * filtered reader with the privacy rule attached, never through
 * kaamase_read_field(), which is the raw one. A number reaches the app
 * through exactly one route: the contact endpoint, after the same gate,
 * log and quota the website applies.
 *
 * On what is included that a public API would not include
 * ------------------------------------------------------
 * is_mine and saved are per viewer, so these objects are not cacheable
 * across users. That is a deliberate trade. Sending them means the app
 * can draw a correct save button on first paint instead of firing a
 * second request per card, which on a village connection is the
 * difference between a list that appears and a list that assembles
 * itself over four seconds.
 *
 * @package KaamaseCore
 * @version 1.4.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. SMALL PIECES
   ========================================================================== */

if ( ! function_exists( 'kaamase_shape_term' ) ) {
	/**
	 * A taxonomy term as the app wants it.
	 *
	 * @since 1.3.0
	 * @param WP_Term|null $term Term.
	 * @return array|null
	 */
	function kaamase_shape_term( $term ) {

		if ( ! $term instanceof WP_Term ) {
			return null;
		}

		return array(
			'slug' => $term->slug,
			'name' => $term->name,
		);
	}
}

if ( ! function_exists( 'kaamase_shape_terms' ) ) {
	/**
	 * Every term of one taxonomy on a post.
	 *
	 * @since 1.3.0
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return array[]
	 */
	function kaamase_shape_terms( $post_id, $taxonomy ) {

		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}

		$out = array();

		foreach ( $terms as $term ) {

			// Trade categories are containers, not trades. Skip the parents.
			if ( 'kaamase_trade' === $taxonomy && 0 === (int) $term->parent ) {
				continue;
			}

			$shaped = kaamase_shape_term( $term );

			if ( $shaped ) {
				$out[] = $shaped;
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_shape_district' ) ) {
	/**
	 * A district slug as a slug and a readable name.
	 *
	 * The app must never have to title case a slug itself. That is how
	 * one screen ends up saying Dimapur and another dimapur.
	 *
	 * @since 1.3.0
	 * @param string $slug District slug.
	 * @return array|null
	 */
	function kaamase_shape_district( $slug ) {

		$slug = (string) $slug;

		if ( '' === $slug ) {
			return null;
		}

		$name = function_exists( 'kaamase_district_name' ) ? kaamase_district_name( $slug ) : '';

		return array(
			'slug' => $slug,
			'name' => $name ? $name : ucwords( str_replace( '-', ' ', $slug ) ),
		);
	}
}

if ( ! function_exists( 'kaamase_shape_image' ) ) {
	/**
	 * A post's photograph at the sizes the app uses.
	 *
	 * Two sizes rather than a full srcset. A card needs a small one and a
	 * profile needs a larger one, and shipping eight URLs per object to
	 * a phone on a slow connection costs more than it saves.
	 *
	 * @since 1.3.0
	 * @param int $post_id Post ID.
	 * @return array|null
	 */
	function kaamase_shape_image( $post_id ) {

		if ( ! has_post_thumbnail( $post_id ) ) {
			return null;
		}

		$small = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'kaamase-avatar' );
		$large = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'kaamase-avatar-lg' );

		if ( ! $small && ! $large ) {
			return null;
		}

		return array(
			'small' => $small ? $small[0] : ( $large ? $large[0] : '' ),
			'large' => $large ? $large[0] : ( $small ? $small[0] : '' ),
		);
	}
}

if ( ! function_exists( 'kaamase_shape_rating' ) ) {
	/**
	 * A profile's rating, honouring the three rating threshold.
	 *
	 * kaamase_field() already withholds the average below the threshold,
	 * so this reports what the viewer is entitled to see rather than what
	 * is stored.
	 *
	 * @since 1.3.0
	 * @param int $post_id Profile ID.
	 * @return array
	 */
	function kaamase_shape_rating( $post_id ) {

		$average = (float) kaamase_field( $post_id, 'rating_average', 0 );
		$count   = absint( kaamase_field( $post_id, 'rating_count', 0 ) );

		return array(
			'average' => round( $average, 1 ),
			'count'   => $count,
			// False means the app should draw a New badge instead of a score.
			'public'  => ( $count > 0 && $average > 0 ),
		);
	}
}

if ( ! function_exists( 'kaamase_shape_initials' ) ) {
	/**
	 * Initials for the no photograph case.
	 *
	 * @since 1.3.0
	 * @param string $name Full name.
	 * @return string
	 */
	function kaamase_shape_initials( $name ) {

		$name = trim( wp_strip_all_tags( (string) $name ) );

		if ( '' === $name ) {
			return '?';
		}

		$parts    = preg_split( '/\s+/', $name );
		$initials = '';

		foreach ( array_slice( (array) $parts, 0, 2 ) as $part ) {
			$initials .= mb_substr( $part, 0, 1 );
		}

		return mb_strtoupper( $initials );
	}
}


/* ==========================================================================
   2. WORKERS AND TEAMS
   ========================================================================== */

if ( ! function_exists( 'kaamase_shape_worker' ) ) {
	/**
	 * A worker or team profile.
	 *
	 * @since 1.3.0
	 * @param int|WP_Post $post Profile.
	 * @param bool        $full Whether to include the long fields.
	 * @return array|null
	 */
	function kaamase_shape_worker( $post, $full = false ) {

		$post = get_post( $post );

		if ( ! $post ) {
			return null;
		}

		$id   = (int) $post->ID;
		$team = ( 'kaamase_gang' === $post->post_type );

		$vouched_by = (string) kaamase_field( $id, 'vouched_by' );

		$out = array(
			'id'               => $id,
			'type'             => $team ? 'team' : 'worker',
			'name'             => get_the_title( $id ),
			'initials'         => kaamase_shape_initials( get_the_title( $id ) ),
			'url'              => (string) get_permalink( $id ),
			'image'            => kaamase_shape_image( $id ),
			'district'         => kaamase_shape_district( (string) kaamase_field( $id, 'district' ) ),
			'town'             => (string) kaamase_field( $id, 'town' ),
			'trades'           => kaamase_shape_terms( $id, 'kaamase_trade' ),
			'availability'     => (string) kaamase_field( $id, 'availability', 'live' ),
			'day_rate'         => absint( kaamase_field( $id, 'day_rate', 0 ) ),
			'month_rate'       => absint( kaamase_field( $id, 'month_rate', 0 ) ),
			'years_experience' => absint( kaamase_field( $id, 'years_experience', 0 ) ),
			'travel_radius'    => (string) kaamase_field( $id, 'travel_radius', 'district' ),
			'jobs_completed'   => absint( kaamase_field( $id, 'jobs_completed', 0 ) ),
			'rating'           => kaamase_shape_rating( $id ),
			'badges'           => array(
				'vouched'  => (bool) $vouched_by,
				'verified' => (bool) kaamase_field( $id, 'verified', false ),
			),
			'is_mine'          => kaamase_user_owns( $id ),
			'saved'            => is_user_logged_in() && kaamase_is_saved( $id ),
		);

		if ( $team ) {
			$out['headcount']   = absint( kaamase_field( $id, 'headcount', 0 ) );
			$out['leader_name'] = (string) kaamase_field( $id, 'leader_name' );
		}

		if ( $full ) {

			$out['about']     = wp_strip_all_tags( (string) $post->post_content );
			$out['languages'] = kaamase_shape_terms( $id, 'kaamase_language' );

			/*
			 * The voucher's name and role are public, because a vouch
			 * nobody can see the source of persuades nobody. Their phone
			 * number is not, and is not in this array at all.
			 */
			$out['vouch'] = $vouched_by
				? array(
					'name'       => $vouched_by,
					'role'       => (string) kaamase_field( $id, 'vouched_role' ),
					'role_label' => kaamase_vouch_role_label( (string) kaamase_field( $id, 'vouched_role' ) ),
				)
				: null;
		}

		/**
		 * Filter a worker or team as the API returns it.
		 *
		 * @since 1.3.0
		 * @param array   $out  Shaped profile.
		 * @param WP_Post $post The profile.
		 * @param bool    $full Whether long fields were included.
		 */
		return (array) apply_filters( 'kaamase_shape_worker', $out, $post, $full );
	}
}

if ( ! function_exists( 'kaamase_vouch_role_label' ) ) {
	/**
	 * Readable label for a voucher's position.
	 *
	 * @since 1.3.0
	 * @param string $role Role key.
	 * @return string
	 */
	function kaamase_vouch_role_label( $role ) {

		$roles = array(
			'gb'       => __( 'Gaon Bura', 'kaamase-core' ),
			'colony'   => __( 'Colony council member', 'kaamase-core' ),
			'village'  => __( 'Village council member', 'kaamase-core' ),
			'church'   => __( 'Church elder or pastor', 'kaamase-core' ),
			'union'    => __( 'Student or workers union officer', 'kaamase-core' ),
			'employer' => __( 'Someone they worked for', 'kaamase-core' ),
			'other'    => __( 'Someone who knows them', 'kaamase-core' ),
		);

		return isset( $roles[ $role ] ) ? $roles[ $role ] : '';
	}
}


/* ==========================================================================
   3. JOBS
   ========================================================================== */

if ( ! function_exists( 'kaamase_shape_job' ) ) {
	/**
	 * A job.
	 *
	 * @since 1.3.0
	 * @param int|WP_Post $post Job.
	 * @param bool        $full Whether to include the description.
	 * @return array|null
	 */
	function kaamase_shape_job( $post, $full = false ) {

		$post = get_post( $post );

		if ( ! $post ) {
			return null;
		}

		$id      = (int) $post->ID;
		$expires = absint( kaamase_field( $id, 'expires', 0 ) );
		$posted  = (int) get_post_time( 'U', true, $id );

		$out = array(
			'id'             => $id,
			'type'           => 'job',
			'title'          => get_the_title( $id ),
			'url'            => (string) get_permalink( $id ),
			'image'          => kaamase_shape_image( $id ),
			'trades'         => kaamase_shape_terms( $id, 'kaamase_trade' ),
			'district'       => kaamase_shape_district( (string) kaamase_field( $id, 'district' ) ),
			'town'           => (string) kaamase_field( $id, 'town' ),
			'pay'            => array(
				'amount' => absint( kaamase_field( $id, 'pay_amount', 0 ) ),
				'unit'   => (string) kaamase_field( $id, 'pay_unit', 'day' ),
			),
			'workers_needed' => absint( kaamase_field( $id, 'workers_needed', 1 ) ),
			'urgent'         => (bool) kaamase_field( $id, 'urgent', false ),
			'provides'       => array(
				'food'      => (bool) kaamase_field( $id, 'food_provided', false ),
				'stay'      => (bool) kaamase_field( $id, 'stay_provided', false ),
				'transport' => (bool) kaamase_field( $id, 'transport_provided', false ),
			),
			'start_date'     => (string) kaamase_field( $id, 'start_date' ),
			'duration'       => (string) kaamase_field( $id, 'duration' ),
			'employer_name'  => (string) kaamase_field( $id, 'employer_name' ),
			'employer_id'    => absint( kaamase_field( $id, 'employer_id', 0 ) ),
			'posted_at'      => $posted,
			'expires_at'     => $expires,
			'days_left'      => $expires ? max( 0, (int) ceil( ( $expires - time() ) / DAY_IN_SECONDS ) ) : 0,
			'is_open'        => kaamase_job_is_open( $id ),
			'status'         => (string) kaamase_field( $id, 'job_status', 'open' ),
			'is_mine'        => kaamase_user_owns( $id ),
			'saved'          => is_user_logged_in() && kaamase_is_saved( $id ),
		);

		if ( $full ) {

			$out['description'] = wp_strip_all_tags( (string) $post->post_content );

			$employer = $out['employer_id'];

			$out['employer'] = ( $employer && 'publish' === get_post_status( $employer ) )
				? array(
					'id'       => $employer,
					'name'     => get_the_title( $employer ),
					'url'      => (string) get_permalink( $employer ),
					'rating'   => kaamase_shape_rating( $employer ),
					'verified' => (bool) kaamase_field( $employer, 'verified', false ),
					'type'     => (string) kaamase_field( $employer, 'employer_type' ),
				)
				: null;
		}

		/**
		 * Filter a job as the API returns it.
		 *
		 * @since 1.3.0
		 * @param array   $out  Shaped job.
		 * @param WP_Post $post The job.
		 * @param bool    $full Whether long fields were included.
		 */
		return (array) apply_filters( 'kaamase_shape_job', $out, $post, $full );
	}
}


/* ==========================================================================
   4. EMPLOYERS
   ========================================================================== */

if ( ! function_exists( 'kaamase_shape_employer' ) ) {
	/**
	 * An employer.
	 *
	 * @since 1.3.0
	 * @param int|WP_Post $post Employer.
	 * @param bool        $full Whether to include the long fields.
	 * @return array|null
	 */
	function kaamase_shape_employer( $post, $full = false ) {

		$post = get_post( $post );

		if ( ! $post ) {
			return null;
		}

		$id = (int) $post->ID;

		$out = array(
			'id'         => $id,
			'type'       => 'employer',
			'name'       => get_the_title( $id ),
			'initials'   => kaamase_shape_initials( get_the_title( $id ) ),
			'url'        => (string) get_permalink( $id ),
			'image'      => kaamase_shape_image( $id ),
			'district'   => kaamase_shape_district( (string) kaamase_field( $id, 'district' ) ),
			'town'       => (string) kaamase_field( $id, 'town' ),
			'kind'       => (string) kaamase_field( $id, 'employer_type', 'individual' ),
			'hires_made' => absint( kaamase_field( $id, 'hires_made', 0 ) ),
			'rating'     => kaamase_shape_rating( $id ),
			'badges'     => array(
				'verified' => (bool) kaamase_field( $id, 'verified', false ),
			),
			'is_mine'    => kaamase_user_owns( $id ),
		);

		if ( $full ) {
			$out['about']        = wp_strip_all_tags( (string) $post->post_content );
			$out['contact_name'] = (string) kaamase_field( $id, 'contact_name' );
			$out['gst']          = (string) kaamase_field( $id, 'gst' );
		}

		return (array) apply_filters( 'kaamase_shape_employer', $out, $post, $full );
	}
}


/* ==========================================================================
   5. RATINGS
   ========================================================================== */

if ( ! function_exists( 'kaamase_shape_rating_entry' ) ) {
	/**
	 * One published rating.
	 *
	 * Only the answers that were no are sent. A row of ticks tells the
	 * reader nothing, and shipping all of them invites the app to draw
	 * ten green rows that mean less than one red one.
	 *
	 * @since 1.3.0
	 * @param WP_Comment $comment Rating.
	 * @return array
	 */
	function kaamase_shape_rating_entry( $comment ) {

		$about   = (string) get_comment_meta( $comment->comment_ID, 'kaamase_about', true );
		$answers = kaamase_meta_array( get_comment_meta( $comment->comment_ID, 'kaamase_answers', true ) );
		$asked   = kaamase_rating_questions( $about ? $about : 'worker' );

		$negatives = array();

		foreach ( $asked as $key => $question ) {
			if ( empty( $answers[ $key ] ) ) {
				$negatives[] = rtrim( $question['label'], '?' );
			}
		}

		return array(
			'id'        => (int) $comment->comment_ID,
			'author'    => $comment->comment_author,
			'note'      => $comment->comment_content,
			'score'     => (float) get_comment_meta( $comment->comment_ID, 'kaamase_score', true ),
			'negatives' => $negatives,
			'date'      => mysql2date( 'c', $comment->comment_date_gmt, false ),
		);
	}
}


/* ==========================================================================
   6. THE SIGNED IN ACCOUNT
   ========================================================================== */

if ( ! function_exists( 'kaamase_shape_me' ) ) {
	/**
	 * Everything the app needs about the account holding the token.
	 *
	 * This one CAN carry the phone number, because it is the caller's
	 * own, and their own number is the one thing they are unambiguously
	 * entitled to. It comes from kaamase_field(), which returns it only
	 * because ownership already passed the privacy check.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return array|null
	 */
	function kaamase_shape_me( $user_id ) {

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return null;
		}

		$type = kaamase_get_user_type( $user_id );

		/*
		 * The profile sent here is the one that matches the type.
		 *
		 * An account can hold both sides now, so kaamase_profile_id on
		 * its own is not enough: it points at whichever profile came
		 * first, and if the type ever disagrees with it then the app
		 * renders one form against the other one's data. Resolving from
		 * the type means the two cannot come apart, whatever order the
		 * sides were added in.
		 */
		/*
		 * Both profiles in one query, and each shaped once.
		 *
		 * This grew badly. It fetched the profile matching the type,
		 * then fetched both sides again for the profiles object, so the
		 * primary profile was queried twice and shaped twice, and three
		 * separate lookups ran where one would do. Shaping is not cheap
		 * either: it reads ratings, trades, district and images.
		 *
		 * /me is the request the app waits on before it can draw
		 * anything at all on a cold start, so a third of the work on
		 * this endpoint is a third of the wait on every screen after
		 * closing and reopening the app.
		 */
		$owned = get_posts(
			array(
				'post_type'        => array( 'kaamase_worker', 'kaamase_employer' ),
				'author'           => $user_id,
				'post_status'      => array( 'draft', 'pending', 'publish' ),
				'posts_per_page'   => 2,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		$sides = array(
			'worker'   => null,
			'employer' => null,
		);

		$posts_by_side = array(
			'worker'   => null,
			'employer' => null,
		);

		foreach ( (array) $owned as $owned_post ) {

			$side = ( 'kaamase_employer' === $owned_post->post_type ) ? 'employer' : 'worker';

			// First of each type wins, matching the old ordering.
			if ( null !== $posts_by_side[ $side ] ) {
				continue;
			}

			$posts_by_side[ $side ] = $owned_post;

			$sides[ $side ] = 'employer' === $side
				? kaamase_shape_employer( $owned_post, true )
				: kaamase_shape_worker( $owned_post, true );
		}

		$wanted  = 'employer' === $type ? 'employer' : 'worker';
		$profile = $posts_by_side[ $wanted ];
		$shaped  = $sides[ $wanted ];

		/*
		 * Nothing of the wanted type. Falls back to the profile they
		 * registered with, which is what this did before, so an account
		 * in an odd state still gets something rather than nothing.
		 */
		if ( ! $shaped ) {

			$fallback_id = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

			if ( $fallback_id ) {

				$other = $posts_by_side[ 'worker' === $wanted ? 'employer' : 'worker' ];

				if ( $other && (int) $other->ID === $fallback_id ) {
					$profile = $other;
					$shaped  = $sides[ 'worker' === $wanted ? 'employer' : 'worker' ];
				}
			}
		}

		$profile_id = $profile ? (int) $profile->ID : 0;

		$team_id = function_exists( 'kaamase_get_user_team' ) ? kaamase_get_user_team( $user_id ) : 0;

		$me = array(
			'id'            => (int) $user_id,
			'name'          => $user->display_name,
			'email'         => $user->user_email,
			'type'          => $type,
			'verified'      => kaamase_user_is_verified( $user_id ),
			'profile_id'    => $profile_id,
			'profile'       => $shaped,
			'profiles'      => $sides,

			/*
			 * Whether this person runs the platform, asked as a
			 * capability rather than read off the type.
			 *
			 * type names which side of the market somebody is on, and
			 * since it now resolves from the profile they registered
			 * with, an administrator who also has a worker profile comes
			 * back as a worker. Anything gated on type being staff was
			 * therefore invisible to the one person it was built for.
			 */
			'is_staff'      => user_can( $user_id, 'manage_options' ),
			'phone'         => $profile_id ? (string) kaamase_field( $profile_id, 'phone' ) : '',
			'team_id'       => $team_id ? (int) $team_id : 0,
			'can_post_jobs' => user_can( $user_id, 'create_kaamase_jobs' ),
			'can_add_team'  => user_can( $user_id, 'create_kaamase_gangs' ),
			'quota_left'    => kaamase_contact_quota_left( $user_id ),
			'profile_state' => $profile ? $profile->post_status : 'missing',
		);

		/**
		 * Filter the account object the app receives.
		 *
		 * Here so a plugin can add to what the phone knows about an
		 * account without costing a second request. On the connections
		 * this app is used over, a second round trip is not a detail:
		 * it is the difference between a screen that fills in and one
		 * that sits half empty while somebody decides the app is
		 * broken.
		 *
		 * @since 1.1.0
		 * @param array $me      The account.
		 * @param int   $user_id User ID.
		 */
		return (array) apply_filters( 'kaamase_shape_me', $me, $user_id );
	}
}