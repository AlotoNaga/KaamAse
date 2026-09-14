<?php
/**
 * The owner's screen: who signed up, who confirmed, and how to reach them.
 *
 * Why this exists
 * ---------------
 * Six hundred people had registered and never confirmed, and the only way
 * to see them was a list with no search, no filter and no way to get the
 * numbers out. Ringing six hundred people from a paginated HTML table is
 * not work anybody can do. Neither is answering "are we growing?" by
 * counting rows.
 *
 * So this is two things on one screen. The top half is the shape of the
 * platform — how many people, how many confirmed, workers against
 * employers, and which way they signed in. The bottom half is every
 * account, filtered however you need it, with the phone numbers ready to
 * paste into WhatsApp and a spreadsheet export for anything else.
 *
 * The phone numbers, and why this screen is locked harder than the rest
 * ---------------------------------------------------------------------
 * not-confirmed.php makes the argument in full and it holds here: a list
 * of everybody's number sitting in wp-admin is a liability. Two rules
 * follow from it and both are load bearing.
 *
 * Every number is read through kaamase_field(), never the raw meta, so
 * the privacy rule in fields.php stays the only gate there is. If those
 * rights are ever narrowed this screen empties out on its own rather
 * than carrying on regardless.
 *
 * And the capability is manage_options, not the edit_others_kaamase_workers
 * the rest of the Kaam Ase menu uses. Somebody trusted to edit a worker's
 * trade is not automatically somebody trusted to export every phone
 * number on the platform. The export checks it again on its own, because
 * a menu that hides a link is not a permission check.
 *
 * Why the day is worked out in the site's timezone
 * -------------------------------------------------
 * WordPress stores user_registered in UTC. Grouped by UTC day, everybody
 * who signed up after half past five in the evening lands on tomorrow,
 * which makes "how many today" wrong by exactly the amount that matters.
 * The offset comes from wp_timezone() rather than a hardcoded +05:30, so
 * this follows the site setting instead of assuming it.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The capability this whole screen sits behind.
 */
const KAAMASE_INSIGHTS_CAP = 'manage_options';


/* ==========================================================================
   1. THE MENU
   ========================================================================== */

if ( ! function_exists( 'kaamase_insights_menu' ) ) {
	/**
	 * Add the screen under the Kaam Ase menu.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function kaamase_insights_menu() {

		add_submenu_page(
			'kaamase',
			__( 'Insights', 'kaamase-core' ),
			__( 'Insights', 'kaamase-core' ),
			KAAMASE_INSIGHTS_CAP,
			'kaamase-insights',
			'kaamase_insights_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_insights_menu', 20 );


/* ==========================================================================
   2. THE PIECES EVERY QUERY NEEDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_insights_offset' ) ) {
	/**
	 * The site's UTC offset, as MySQL wants it written.
	 *
	 * @since 1.8.0
	 * @return string Such as +05:30.
	 */
	function kaamase_insights_offset() {

		$seconds = (int) wp_timezone()->getOffset( new DateTime( 'now', new DateTimeZone( 'UTC' ) ) );
		$sign    = $seconds < 0 ? '-' : '+';
		$seconds = abs( $seconds );

		return sprintf( '%s%02d:%02d', $sign, intdiv( $seconds, 3600 ), intdiv( $seconds % 3600, 60 ) );
	}
}

if ( ! function_exists( 'kaamase_insights_kinds' ) ) {
	/**
	 * The profile types an account can hold, and what to call them.
	 *
	 * @since 1.8.0
	 * @return array<string,string>
	 */
	function kaamase_insights_kinds() {

		return array(
			'kaamase_worker'   => __( 'Worker', 'kaamase-core' ),
			'kaamase_gang'     => __( 'Team', 'kaamase-core' ),
			'kaamase_employer' => __( 'Employer', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_insights_methods' ) ) {
	/**
	 * How somebody signed up, and what to call it.
	 *
	 * An account made through the ordinary form has no password_source
	 * meta at all, so absent means the form. Same rule as
	 * kaamase_password_is_chosen(), and for the same reason.
	 *
	 * @since 1.8.0
	 * @return array<string,string>
	 */
	function kaamase_insights_methods() {

		return array(
			'form'   => __( 'Email and password', 'kaamase-core' ),
			'google' => __( 'Google', 'kaamase-core' ),
			'apple'  => __( 'Apple', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_insights_from_sql' ) ) {
	/**
	 * The FROM and JOIN every query on this screen shares.
	 *
	 * One profile per account, chosen as the lowest post id, so somebody
	 * holding both a worker and an employer profile is one row rather
	 * than two. Written as a derived table rather than a GROUP BY on the
	 * outer query so it is still correct under ONLY_FULL_GROUP_BY.
	 *
	 * An account with no profile is left out entirely. That is every
	 * staff account, without this file having to name a role.
	 *
	 * @since 1.8.0
	 * @return string
	 */
	function kaamase_insights_from_sql() {

		global $wpdb;

		return "
			FROM {$wpdb->users} u
			INNER JOIN (
				SELECT post_author, MIN(ID) AS pid
				FROM {$wpdb->posts}
				WHERE post_type IN ( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' )
				  AND post_status IN ( 'publish', 'draft', 'pending' )
				GROUP BY post_author
			) one ON one.post_author = u.ID
			INNER JOIN {$wpdb->posts} p ON p.ID = one.pid
			LEFT JOIN {$wpdb->usermeta} vm
				ON vm.user_id = u.ID AND vm.meta_key = 'kaamase_verified_at'
			LEFT JOIN {$wpdb->usermeta} sm
				ON sm.user_id = u.ID AND sm.meta_key = 'kaamase_password_source'
			LEFT JOIN {$wpdb->postmeta} dm
				ON dm.post_id = p.ID AND dm.meta_key = '_kaamase_district'
		";
	}
}

if ( ! function_exists( 'kaamase_insights_where' ) ) {
	/**
	 * Turn the filter bar into a WHERE clause.
	 *
	 * Everything variable goes through $wpdb->prepare. The search term is
	 * escaped for LIKE before it is prepared, so a percent sign typed
	 * into the box is a percent sign rather than a wildcard.
	 *
	 * @since 1.8.0
	 * @param array $f Filters.
	 * @return string
	 */
	function kaamase_insights_where( $f ) {

		global $wpdb;

		$sql = ' WHERE 1=1';

		if ( '' !== $f['search'] ) {
			$like = '%' . $wpdb->esc_like( $f['search'] ) . '%';
			$sql .= $wpdb->prepare(
				' AND ( p.post_title LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s OR EXISTS (
					SELECT 1 FROM ' . $wpdb->postmeta . ' pm
					WHERE pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value LIKE %s
				) )',
				$like,
				$like,
				$like,
				'_kaamase_phone',
				'%' . $wpdb->esc_like( preg_replace( '/[^0-9]/', '', $f['search'] ) ) . '%'
			);
		}

		if ( isset( kaamase_insights_kinds()[ $f['kind'] ] ) ) {
			$sql .= $wpdb->prepare( ' AND p.post_type = %s', $f['kind'] );
		}

		if ( '' !== $f['district'] ) {
			$sql .= $wpdb->prepare( ' AND dm.meta_value = %s', $f['district'] );
		}

		if ( 'confirmed' === $f['state'] ) {
			$sql .= ' AND vm.meta_value IS NOT NULL';
		} elseif ( 'unconfirmed' === $f['state'] ) {
			$sql .= ' AND vm.meta_value IS NULL';
		}

		if ( 'form' === $f['method'] ) {
			$sql .= " AND ( sm.meta_value IS NULL OR sm.meta_value = 'chosen' )";
		} elseif ( isset( kaamase_insights_methods()[ $f['method'] ] ) ) {
			$sql .= $wpdb->prepare( ' AND sm.meta_value = %s', $f['method'] );
		}

		$offset = kaamase_insights_offset();

		if ( '' !== $f['from'] ) {
			$sql .= $wpdb->prepare(
				" AND DATE( CONVERT_TZ( u.user_registered, '+00:00', %s ) ) >= %s",
				$offset,
				$f['from']
			);
		}

		if ( '' !== $f['to'] ) {
			$sql .= $wpdb->prepare(
				" AND DATE( CONVERT_TZ( u.user_registered, '+00:00', %s ) ) <= %s",
				$offset,
				$f['to']
			);
		}

		return $sql;
	}
}

if ( ! function_exists( 'kaamase_insights_filters' ) ) {
	/**
	 * Read the filter bar off the request.
	 *
	 * @since 1.8.0
	 * @return array
	 */
	function kaamase_insights_filters() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$get = function ( $key ) {
			return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
		};

		$date = function ( $value ) {
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
		};

		return array(
			'search'   => $get( 's' ),
			'kind'     => sanitize_key( $get( 'kind' ) ),
			'district' => function_exists( 'kaamase_match_district' ) ? kaamase_match_district( $get( 'district' ) ) : '',
			'state'    => sanitize_key( $get( 'state' ) ),
			'method'   => sanitize_key( $get( 'method' ) ),
			'from'     => $date( $get( 'from' ) ),
			'to'       => $date( $get( 'to' ) ),
			'paged'    => max( 1, (int) $get( 'paged' ) ),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}


/* ==========================================================================
   3. THE NUMBERS
   ========================================================================== */

if ( ! function_exists( 'kaamase_insights_totals' ) ) {
	/**
	 * The headline counts, for the whole platform.
	 *
	 * @since 1.8.0
	 * @return array
	 */
	function kaamase_insights_totals() {

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			'SELECT
				COUNT(*) AS total,
				SUM( CASE WHEN vm.meta_value IS NOT NULL THEN 1 ELSE 0 END ) AS confirmed,
				SUM( CASE WHEN p.post_type = "kaamase_worker" THEN 1 ELSE 0 END ) AS workers,
				SUM( CASE WHEN p.post_type = "kaamase_gang" THEN 1 ELSE 0 END ) AS teams,
				SUM( CASE WHEN p.post_type = "kaamase_employer" THEN 1 ELSE 0 END ) AS employers,
				SUM( CASE WHEN sm.meta_value = "google" THEN 1 ELSE 0 END ) AS google,
				SUM( CASE WHEN sm.meta_value = "apple" THEN 1 ELSE 0 END ) AS apple
			' . kaamase_insights_from_sql(),
			ARRAY_A
		);
		// phpcs:enable

		$row = is_array( $row ) ? array_map( 'intval', $row ) : array();

		$total     = isset( $row['total'] ) ? $row['total'] : 0;
		$confirmed = isset( $row['confirmed'] ) ? $row['confirmed'] : 0;

		return array(
			'total'       => $total,
			'confirmed'   => $confirmed,
			'unconfirmed' => $total - $confirmed,
			'rate'        => $total ? (int) round( ( $confirmed / $total ) * 100 ) : 0,
			'workers'     => isset( $row['workers'] ) ? $row['workers'] : 0,
			'teams'       => isset( $row['teams'] ) ? $row['teams'] : 0,
			'employers'   => isset( $row['employers'] ) ? $row['employers'] : 0,
			'google'      => isset( $row['google'] ) ? $row['google'] : 0,
			'apple'       => isset( $row['apple'] ) ? $row['apple'] : 0,
			'form'        => $total - ( isset( $row['google'] ) ? $row['google'] : 0 ) - ( isset( $row['apple'] ) ? $row['apple'] : 0 ),
		);
	}
}

if ( ! function_exists( 'kaamase_insights_by_day' ) ) {
	/**
	 * Registrations per day, split by whether they confirmed.
	 *
	 * Days with nobody are filled in as zero rather than left out, so the
	 * chart has a real time axis instead of a row of bars that silently
	 * skips the quiet days.
	 *
	 * @since 1.8.0
	 * @param int $days How many days back.
	 * @return array<string,array{confirmed:int,unconfirmed:int}> Keyed by Y-m-d.
	 */
	function kaamase_insights_by_day( $days = 30 ) {

		global $wpdb;

		$days   = max( 7, min( 120, (int) $days ) );
		$offset = kaamase_insights_offset();

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT
					DATE( CONVERT_TZ( u.user_registered, "+00:00", %s ) ) AS day,
					SUM( CASE WHEN vm.meta_value IS NOT NULL THEN 1 ELSE 0 END ) AS confirmed,
					SUM( CASE WHEN vm.meta_value IS NULL THEN 1 ELSE 0 END ) AS unconfirmed
				' . kaamase_insights_from_sql() . '
				WHERE u.user_registered >= DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d DAY )
				GROUP BY day
				ORDER BY day ASC',
				$offset,
				$days
			),
			ARRAY_A
		);
		// phpcs:enable

		$found = array();

		foreach ( (array) $rows as $row ) {
			$found[ $row['day'] ] = array(
				'confirmed'   => (int) $row['confirmed'],
				'unconfirmed' => (int) $row['unconfirmed'],
			);
		}

		$out   = array();
		$start = new DateTimeImmutable( 'now', wp_timezone() );

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$key         = $start->modify( "-$i day" )->format( 'Y-m-d' );
			$out[ $key ] = isset( $found[ $key ] )
				? $found[ $key ]
				: array( 'confirmed' => 0, 'unconfirmed' => 0 );
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_insights_by_district' ) ) {
	/**
	 * Where everybody is, busiest first.
	 *
	 * @since 1.8.0
	 * @return array<string,array{total:int,confirmed:int}>
	 */
	function kaamase_insights_by_district() {

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			'SELECT
				COALESCE( NULLIF( dm.meta_value, "" ), "" ) AS district,
				COUNT(*) AS total,
				SUM( CASE WHEN vm.meta_value IS NOT NULL THEN 1 ELSE 0 END ) AS confirmed
			' . kaamase_insights_from_sql() . '
			GROUP BY district
			ORDER BY total DESC',
			ARRAY_A
		);
		// phpcs:enable

		$out = array();

		foreach ( (array) $rows as $row ) {

			/*
			 * The meta holds a slug. Everything on screen wants the name,
			 * and a district whose slug no longer resolves is counted as
			 * not given rather than printed raw.
			 */
			$name = '' === $row['district'] ? '' : kaamase_district_name( $row['district'] );
			$name = '' === $name ? __( 'Not given', 'kaamase-core' ) : $name;

			if ( ! isset( $out[ $name ] ) ) {
				$out[ $name ] = array(
					'total'     => 0,
					'confirmed' => 0,
				);
			}

			// Added to rather than replaced: an empty district and a slug
			// that no longer resolves both land on "Not given".
			$out[ $name ]['total']     += (int) $row['total'];
			$out[ $name ]['confirmed'] += (int) $row['confirmed'];
		}

		uasort(
			$out,
			static function ( $a, $b ) {
				return $b['total'] <=> $a['total'];
			}
		);

		return $out;
	}
}


/* ==========================================================================
   4. THE PEOPLE
   ========================================================================== */

if ( ! function_exists( 'kaamase_insights_people' ) ) {
	/**
	 * Accounts matching the filter bar.
	 *
	 * @since 1.8.0
	 * @param array $f     Filters.
	 * @param int   $per   Rows per page. 0 means every match, for the export.
	 * @return array{rows:array[],total:int}
	 */
	function kaamase_insights_people( $f, $per = 50 ) {

		global $wpdb;

		$where = kaamase_insights_where( $f );

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var( 'SELECT COUNT(*) ' . kaamase_insights_from_sql() . $where );

		$sql = 'SELECT u.ID, u.user_email, u.user_registered, u.display_name,
					p.ID AS profile, p.post_type, p.post_status, p.post_title,
					vm.meta_value AS verified_at,
					sm.meta_value AS source,
					dm.meta_value AS district
				' . kaamase_insights_from_sql() . $where . ' ORDER BY u.user_registered DESC';

		if ( $per > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per, ( $f['paged'] - 1 ) * $per );
		}

		$found = $wpdb->get_results( $sql, ARRAY_A );
		// phpcs:enable

		$rows = array();

		foreach ( (array) $found as $row ) {

			$source = (string) $row['source'];
			$method = in_array( $source, array( 'google', 'apple' ), true ) ? $source : 'form';

			$rows[] = array(
				'user_id'    => (int) $row['ID'],
				'profile'    => (int) $row['profile'],
				'name'       => '' !== $row['post_title'] ? $row['post_title'] : $row['display_name'],
				'kind'       => (string) $row['post_type'],
				'email'      => (string) $row['user_email'],
				/*
				 * Through kaamase_field(), never the raw meta. The privacy
				 * rule in fields.php stays the only gate there is.
				 */
				'phone'      => (string) kaamase_field( (int) $row['profile'], 'phone' ),
				// Stored as a slug, read everywhere here as the name.
				'district'   => '' !== (string) $row['district'] ? kaamase_district_name( (string) $row['district'] ) : '',
				'confirmed'  => ( null !== $row['verified_at'] ),
				'method'     => $method,
				'status'     => (string) $row['post_status'],
				'registered' => (string) $row['user_registered'],
			);
		}

		return array(
			'rows'  => $rows,
			'total' => $total,
		);
	}
}


/* ==========================================================================
   5. GETTING IT OUT

   A spreadsheet for anything, and a box of numbers for WhatsApp. Both
   check the capability again on their own, because a menu that hides a
   link is not a permission check.
   ========================================================================== */

if ( ! function_exists( 'kaamase_insights_export' ) ) {
	/**
	 * Send the filtered list as a CSV.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function kaamase_insights_export() {

		if ( ! current_user_can( KAAMASE_INSIGHTS_CAP ) ) {
			wp_die( esc_html__( 'You cannot export this.', 'kaamase-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'kaamase_insights_export' );

		$f    = kaamase_insights_filters();
		$list = kaamase_insights_people( $f, 0 );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=kaam-ase-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );

		// So Excel opens the file as UTF-8 rather than mangling the names.
		fwrite( $out, "\xEF\xBB\xBF" );

		fputcsv(
			$out,
			array(
				__( 'Name', 'kaamase-core' ),
				__( 'Kind', 'kaamase-core' ),
				__( 'Phone', 'kaamase-core' ),
				__( 'WhatsApp', 'kaamase-core' ),
				__( 'Email', 'kaamase-core' ),
				__( 'District', 'kaamase-core' ),
				__( 'Confirmed', 'kaamase-core' ),
				__( 'Signed up with', 'kaamase-core' ),
				__( 'Registered', 'kaamase-core' ),
			)
		);

		$kinds   = kaamase_insights_kinds();
		$methods = kaamase_insights_methods();

		foreach ( $list['rows'] as $row ) {
			fputcsv(
				$out,
				array(
					$row['name'],
					isset( $kinds[ $row['kind'] ] ) ? $kinds[ $row['kind'] ] : $row['kind'],
					$row['phone'],
					kaamase_insights_wa( $row['phone'] ),
					$row['email'],
					$row['district'],
					$row['confirmed'] ? __( 'Yes', 'kaamase-core' ) : __( 'No', 'kaamase-core' ),
					isset( $methods[ $row['method'] ] ) ? $methods[ $row['method'] ] : $row['method'],
					$row['registered'],
				)
			);
		}

		fclose( $out );
		exit;
	}
}
add_action( 'admin_post_kaamase_insights_export', 'kaamase_insights_export' );

if ( ! function_exists( 'kaamase_insights_wa' ) ) {
	/**
	 * A number in the shape a broadcast list wants.
	 *
	 * Numbers are stored as ten digits with no country code, which is
	 * right for showing to a person here and wrong for every tool that
	 * sends a message. Anything that is not ten digits is left alone
	 * rather than guessed at.
	 *
	 * @since 1.8.0
	 * @param string $phone Stored number.
	 * @return string
	 */
	function kaamase_insights_wa( $phone ) {

		$digits = preg_replace( '/[^0-9]/', '', (string) $phone );

		return 10 === strlen( $digits ) ? '+91' . $digits : (string) $phone;
	}
}


/* ==========================================================================
   6. SENDING THE CONFIRMATION EMAIL AGAIN

   The link somebody never opened is also, by now, very likely expired:
   kaamase_send_verification() gives each one seven days, and most of
   these accounts are older than that. So this is not a reminder about
   the old email. It writes a fresh token, kills the old one, and sends
   a new link — the same call the person would make themselves by
   pressing "Send it again" on their own dashboard.

   That is the whole reason this exists rather than a nudge by hand.
   Pressing that button needs them to be signed in, and somebody who
   registered on a borrowed phone a fortnight ago is not signed in
   anywhere. There is no signed-out route to a new link, so without this
   the only people who can rescue those accounts are the people who
   already cannot reach them.

   In batches, deliberately
   ------------------------
   Six hundred emails in one request would time out, and would hand a
   shared host six hundred messages in one breath, which is how a
   domain's sending reputation gets ruined. A batch a click is slower
   and is also the rate limit.
   ========================================================================== */

if ( ! function_exists( 'kaamase_insights_nudge_cooldown' ) ) {
	/**
	 * How long before the same account may be sent another one.
	 *
	 * Long enough that clicking the button twice, or working through the
	 * districts one at a time and losing your place, cannot mail the same
	 * person twice in a day.
	 *
	 * @since 1.8.0
	 * @return int Seconds.
	 */
	function kaamase_insights_nudge_cooldown() {
		return 7 * DAY_IN_SECONDS;
	}
}

if ( ! function_exists( 'kaamase_insights_nudge_where' ) ) {
	/**
	 * The filter bar, narrowed to who may be sent one right now.
	 *
	 * Unconfirmed is forced rather than read, whatever the screen's own
	 * state filter says. There is no reading of this where somebody who
	 * has already confirmed should be asked to confirm again.
	 *
	 * @since 1.8.0
	 * @param array $f Filters.
	 * @return string
	 */
	function kaamase_insights_nudge_where( $f ) {

		global $wpdb;

		$f['state'] = 'unconfirmed';

		return kaamase_insights_where( $f ) . $wpdb->prepare(
			' AND NOT EXISTS (
				SELECT 1 FROM ' . $wpdb->usermeta . ' nm
				WHERE nm.user_id = u.ID
				  AND nm.meta_key = %s
				  AND nm.meta_value > %d
			)',
			'kaamase_confirm_nudge_at',
			time() - kaamase_insights_nudge_cooldown()
		);
	}
}

if ( ! function_exists( 'kaamase_insights_nudge_waiting' ) ) {
	/**
	 * How many match the filters and have not been sent one lately.
	 *
	 * @since 1.8.0
	 * @param array $f Filters.
	 * @return int
	 */
	function kaamase_insights_nudge_waiting( $f ) {

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( 'SELECT COUNT(*) ' . kaamase_insights_from_sql() . kaamase_insights_nudge_where( $f ) );
	}
}

if ( ! function_exists( 'kaamase_insights_nudge_run' ) ) {
	/**
	 * Send the next batch.
	 *
	 * Stops dead on the first refusal from the mail server rather than
	 * carrying on. If the host has stopped accepting mail, the useful
	 * outcome is finding that out after one failure with everybody else
	 * still queued, not after six hundred silent ones with every account
	 * marked as done.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function kaamase_insights_nudge_run() {

		if ( ! current_user_can( KAAMASE_INSIGHTS_CAP ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'kaamase_insights_nudge' );

		$f     = kaamase_insights_filters();
		$size  = isset( $_POST['batch'] ) ? absint( wp_unslash( $_POST['batch'] ) ) : 50;
		$size  = max( 1, min( 100, $size ) );
		$sent  = 0;
		$stuck = 0;

		if ( ! function_exists( 'kaamase_send_verification' ) ) {
			wp_die( esc_html__( 'The registration module is not loaded, so nothing was sent.', 'kaamase-core' ) );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col(
			'SELECT u.ID ' . kaamase_insights_from_sql() . kaamase_insights_nudge_where( $f )
			. $wpdb->prepare( ' ORDER BY u.user_registered DESC LIMIT %d', $size )
		);

		foreach ( (array) $ids as $id ) {

			$id = (int) $id;

			if ( ! kaamase_send_verification( $id ) ) {
				$stuck = 1;
				break;
			}

			/*
			 * Only once it actually went. Marked before sending, a mail
			 * server having a bad minute would quietly burn through the
			 * whole list leaving nobody to retry.
			 */
			update_user_meta( $id, 'kaamase_confirm_nudge_at', time() );

			/*
			 * The same ten minute lock the self service button sets, so
			 * somebody who presses "Send it again" right after getting
			 * this one does not set off a second.
			 */
			set_transient( 'kaamase_resend_' . $id, 1, 10 * MINUTE_IN_SECONDS );

			$sent++;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'ka_sent'  => $sent,
					'ka_stuck' => $stuck,
				),
				wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=kaamase-insights' )
			)
		);
		exit;
	}
}
add_action( 'admin_post_kaamase_insights_nudge', 'kaamase_insights_nudge_run' );

if ( ! function_exists( 'kaamase_insights_nudge_box' ) ) {
	/**
	 * The button, and what it is about to do.
	 *
	 * @since 1.8.0
	 * @param array $f Filters.
	 * @return void
	 */
	function kaamase_insights_nudge_box( $f ) {

		$waiting = kaamase_insights_nudge_waiting( $f );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$sent  = isset( $_GET['ka_sent'] ) ? absint( wp_unslash( $_GET['ka_sent'] ) ) : -1;
		$stuck = ! empty( $_GET['ka_stuck'] );
		// phpcs:enable

		echo '<h2 id="ka-send">' . esc_html__( 'Send the confirmation email again', 'kaamase-core' ) . '</h2>';

		if ( $sent > -1 ) {
			printf(
				'<div class="notice notice-%1$s inline"><p>%2$s</p></div>',
				$stuck ? 'error' : 'success',
				esc_html(
					$stuck
						? sprintf(
							/* translators: %s: how many were sent before it stopped */
							__( 'Stopped after %s. The mail server refused the next one, so nobody else was marked as done. Try again in a few minutes, and if it keeps stopping the sending limit has been reached for now.', 'kaamase-core' ),
							number_format_i18n( $sent )
						)
						: sprintf(
							/* translators: %s: how many were sent */
							__( 'Sent %s.', 'kaamase-core' ),
							number_format_i18n( $sent )
						)
				)
			);
		}

		?>
		<div class="ka-ins__send">

			<p>
				<?php
				esc_html_e(
					'This writes a new link for each person and emails it, exactly as pressing "Send it again" on their own dashboard would. It is not a reminder about the old email: those links last seven days, so for most of this list the one they were sent has already expired and a reminder would send them to a dead page.',
					'kaamase-core'
				);
				?>
			</p>

			<p>
				<?php
				printf(
					/* translators: %s: how many people are waiting */
					esc_html__( 'Ready to send to: %s', 'kaamase-core' ),
					'<strong>' . esc_html( number_format_i18n( $waiting ) ) . '</strong>'
				);
				?>
				<br>
				<span class="description">
					<?php
					esc_html_e(
						'Unconfirmed accounts matching the filters above, minus anybody already sent one in the last seven days. Confirmed accounts are never included, whatever the filters say.',
						'kaamase-core'
					);
					?>
				</span>
			</p>

			<?php if ( $waiting < 1 ) : ?>
				<p><em><?php esc_html_e( 'Nobody is waiting. Either everybody matching has been sent one recently, or the filters match nobody unconfirmed.', 'kaamase-core' ); ?></em></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
					onsubmit="return confirm( this.dataset.warn );"
					data-warn="<?php esc_attr_e( 'This sends a real email to real people and cannot be taken back. Continue?', 'kaamase-core' ); ?>">

					<?php wp_nonce_field( 'kaamase_insights_nudge' ); ?>
					<input type="hidden" name="action" value="kaamase_insights_nudge">

					<?php
					// The filters travel with it, so the batch is the list on screen.
					foreach ( array(
						's'        => $f['search'],
						'kind'     => $f['kind'],
						'district' => $f['district'],
						'method'   => $f['method'],
						'from'     => $f['from'],
						'to'       => $f['to'],
					) as $key => $value ) {
						if ( '' !== $value ) {
							printf(
								'<input type="hidden" name="%s" value="%s">',
								esc_attr( $key ),
								esc_attr( $value )
							);
						}
					}
					?>

					<label for="ka-batch"><?php esc_html_e( 'How many this time', 'kaamase-core' ); ?></label>
					<select name="batch" id="ka-batch">
						<option value="10">10</option>
						<option value="25">25</option>
						<option value="50" selected>50</option>
						<option value="100">100</option>
					</select>

					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Send this batch now', 'kaamase-core' ); ?>
					</button>
				</form>

				<p class="description">
					<?php
					esc_html_e(
						'A batch at a time on purpose. Six hundred messages handed to a shared mail server in one breath is how a domain stops being trusted, and one request could not finish them anyway. Press it again for the next batch.',
						'kaamase-core'
					);
					?>
				</p>
			<?php endif; ?>

		</div>
		<?php
	}
}


/* ==========================================================================
   7. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_insights_page' ) ) {
	/**
	 * Render it.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function kaamase_insights_page() {

		if ( ! current_user_can( KAAMASE_INSIGHTS_CAP ) ) {
			wp_die( esc_html__( 'You cannot see this page.', 'kaamase-core' ), '', array( 'response' => 403 ) );
		}

		$f       = kaamase_insights_filters();
		$totals  = kaamase_insights_totals();
		$days    = kaamase_insights_by_day( 30 );
		$places  = kaamase_insights_by_district();
		$list    = kaamase_insights_people( $f, 50 );
		$kinds   = kaamase_insights_kinds();
		$methods = kaamase_insights_methods();
		$pages   = (int) ceil( $list['total'] / 50 );

		kaamase_insights_styles();

		?>
		<div class="wrap kaamase-insights">

			<h1><?php esc_html_e( 'Insights', 'kaamase-core' ); ?></h1>

			<?php kaamase_insights_tiles( $totals ); ?>
			<?php kaamase_insights_chart( $days ); ?>
			<?php kaamase_insights_places( $places ); ?>

			<h2><?php esc_html_e( 'Everybody', 'kaamase-core' ); ?></h2>

			<?php kaamase_insights_filter_bar( $f, $kinds, $methods ); ?>

			<p class="ka-ins__count">
				<?php
				printf(
					/* translators: %s: how many accounts matched the filters */
					esc_html__( '%s accounts match.', 'kaamase-core' ),
					'<strong>' . esc_html( number_format_i18n( $list['total'] ) ) . '</strong>'
				);
				?>
			</p>

			<?php kaamase_insights_actions( $f, $list ); ?>
			<?php kaamase_insights_table( $list, $kinds, $methods ); ?>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $f['paged'],
								'total'     => $pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>

			<?php kaamase_insights_nudge_box( $f ); ?>

		</div>
		<?php
	}
}

if ( ! function_exists( 'kaamase_insights_tiles' ) ) {
	/**
	 * The headline numbers.
	 *
	 * Plain numbers rather than charts. A total is one value and a chart
	 * of one value is decoration.
	 *
	 * @since 1.8.0
	 * @param array $t Totals.
	 * @return void
	 */
	function kaamase_insights_tiles( $t ) {

		$tiles = array(
			array( __( 'Accounts', 'kaamase-core' ), $t['total'], '' ),
			array( __( 'Confirmed', 'kaamase-core' ), $t['confirmed'], $t['rate'] . '%' ),
			array( __( 'Not confirmed', 'kaamase-core' ), $t['unconfirmed'], '' ),
			array( __( 'Workers', 'kaamase-core' ), $t['workers'], '' ),
			array( __( 'Teams', 'kaamase-core' ), $t['teams'], '' ),
			array( __( 'Employers', 'kaamase-core' ), $t['employers'], '' ),
		);

		echo '<div class="ka-ins__tiles">';

		foreach ( $tiles as $tile ) {
			printf(
				'<div class="ka-ins__tile"><span class="ka-ins__tile-label">%s</span><span class="ka-ins__tile-value">%s</span><span class="ka-ins__tile-note">%s</span></div>',
				esc_html( $tile[0] ),
				esc_html( number_format_i18n( $tile[1] ) ),
				esc_html( $tile[2] )
			);
		}

		echo '</div>';

		printf(
			'<p class="ka-ins__methods">%s <strong>%s</strong> &middot; %s <strong>%s</strong> &middot; %s <strong>%s</strong></p>',
			esc_html__( 'Signed up with email and password:', 'kaamase-core' ),
			esc_html( number_format_i18n( $t['form'] ) ),
			esc_html__( 'Google:', 'kaamase-core' ),
			esc_html( number_format_i18n( $t['google'] ) ),
			esc_html__( 'Apple:', 'kaamase-core' ),
			esc_html( number_format_i18n( $t['apple'] ) )
		);
	}
}

if ( ! function_exists( 'kaamase_insights_chart' ) ) {
	/**
	 * Registrations a day for the last month, confirmed against not.
	 *
	 * Stacked, because the two parts add up to something meaningful: the
	 * day's signups. The same numbers are in a table underneath, so the
	 * chart is never the only way to read them.
	 *
	 * @since 1.8.0
	 * @param array $days By day.
	 * @return void
	 */
	function kaamase_insights_chart( $days ) {

		$max = 1;

		foreach ( $days as $d ) {
			$max = max( $max, $d['confirmed'] + $d['unconfirmed'] );
		}

		?>
		<h2><?php esc_html_e( 'Who signed up, last 30 days', 'kaamase-core' ); ?></h2>

		<div class="ka-ins__legend">
			<span><i class="ka-ins__swatch ka-ins__swatch--yes"></i><?php esc_html_e( 'Confirmed', 'kaamase-core' ); ?></span>
			<span><i class="ka-ins__swatch ka-ins__swatch--no"></i><?php esc_html_e( 'Not confirmed', 'kaamase-core' ); ?></span>
		</div>

		<div class="ka-ins__chart" role="img"
			aria-label="<?php esc_attr_e( 'Daily registrations for the last 30 days, split by whether the account was confirmed. The same figures are in the table below the chart.', 'kaamase-core' ); ?>">
			<?php foreach ( $days as $day => $d ) : ?>
				<?php
				$sum = $d['confirmed'] + $d['unconfirmed'];
				$pc  = static function ( $n ) use ( $max ) {
					return $n > 0 ? max( 2, round( ( $n / $max ) * 100 ) ) : 0;
				};
				?>
				<div class="ka-ins__col">
					<div class="ka-ins__stack">
						<?php if ( $d['unconfirmed'] > 0 ) : ?>
							<div class="ka-ins__bar ka-ins__bar--no" style="height:<?php echo esc_attr( $pc( $d['unconfirmed'] ) ); ?>%"></div>
						<?php endif; ?>
						<?php if ( $d['confirmed'] > 0 ) : ?>
							<div class="ka-ins__bar ka-ins__bar--yes" style="height:<?php echo esc_attr( $pc( $d['confirmed'] ) ); ?>%"></div>
						<?php endif; ?>
					</div>
					<span class="ka-ins__tip">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: date, 2: total that day, 3: how many confirmed */
								__( '%1$s — %2$s signed up, %3$s confirmed', 'kaamase-core' ),
								wp_date( get_option( 'date_format' ), strtotime( $day . ' 12:00:00' ) ),
								number_format_i18n( $sum ),
								number_format_i18n( $d['confirmed'] )
							)
						);
						?>
					</span>
				</div>
			<?php endforeach; ?>
		</div>

		<details class="ka-ins__details">
			<summary><?php esc_html_e( 'Show these numbers as a table', 'kaamase-core' ); ?></summary>
			<table class="widefat striped ka-ins__daytable">
				<thead><tr>
					<th><?php esc_html_e( 'Day', 'kaamase-core' ); ?></th>
					<th><?php esc_html_e( 'Signed up', 'kaamase-core' ); ?></th>
					<th><?php esc_html_e( 'Confirmed', 'kaamase-core' ); ?></th>
					<th><?php esc_html_e( 'Not confirmed', 'kaamase-core' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( array_reverse( $days, true ) as $day => $d ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $day . ' 12:00:00' ) ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $d['confirmed'] + $d['unconfirmed'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $d['confirmed'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $d['unconfirmed'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</details>
		<?php
	}
}

if ( ! function_exists( 'kaamase_insights_places' ) ) {
	/**
	 * Where they are, busiest first.
	 *
	 * @since 1.8.0
	 * @param array $places By district.
	 * @return void
	 */
	function kaamase_insights_places( $places ) {

		if ( empty( $places ) ) {
			return;
		}

		$max = 1;

		foreach ( $places as $p ) {
			$max = max( $max, $p['total'] );
		}

		echo '<h2>' . esc_html__( 'Where they are', 'kaamase-core' ) . '</h2>';
		echo '<table class="widefat striped ka-ins__places"><tbody>';

		foreach ( $places as $name => $p ) {
			printf(
				'<tr><th scope="row">%s</th><td class="ka-ins__meter"><span style="width:%s%%"></span></td><td class="ka-ins__num">%s</td><td class="ka-ins__num ka-ins__muted">%s</td></tr>',
				esc_html( $name ),
				esc_attr( (string) max( 2, round( ( $p['total'] / $max ) * 100 ) ) ),
				esc_html( number_format_i18n( $p['total'] ) ),
				esc_html(
					sprintf(
						/* translators: %s: how many of that district's accounts confirmed */
						__( '%s confirmed', 'kaamase-core' ),
						number_format_i18n( $p['confirmed'] )
					)
				)
			);
		}

		echo '</tbody></table>';
	}
}

if ( ! function_exists( 'kaamase_insights_filter_bar' ) ) {
	/**
	 * The filters.
	 *
	 * @since 1.8.0
	 * @param array $f       Current filters.
	 * @param array $kinds   Profile types.
	 * @param array $methods Sign up methods.
	 * @return void
	 */
	function kaamase_insights_filter_bar( $f, $kinds, $methods ) {

		/*
		 * Keyed by slug, which is what the meta holds, labelled with the
		 * name, which is what a person reads.
		 */
		$districts = function_exists( 'kaamase_districts' )
			? wp_list_pluck( kaamase_districts(), 'name' )
			: array();

		?>
		<form method="get" class="ka-ins__filters">
			<input type="hidden" name="page" value="kaamase-insights">

			<input type="search" name="s" value="<?php echo esc_attr( $f['search'] ); ?>"
				placeholder="<?php esc_attr_e( 'Name, email or phone', 'kaamase-core' ); ?>">

			<select name="state">
				<option value=""><?php esc_html_e( 'Confirmed or not', 'kaamase-core' ); ?></option>
				<option value="unconfirmed" <?php selected( $f['state'], 'unconfirmed' ); ?>><?php esc_html_e( 'Not confirmed', 'kaamase-core' ); ?></option>
				<option value="confirmed" <?php selected( $f['state'], 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'kaamase-core' ); ?></option>
			</select>

			<select name="kind">
				<option value=""><?php esc_html_e( 'Everybody', 'kaamase-core' ); ?></option>
				<?php foreach ( $kinds as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $f['kind'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<select name="district">
				<option value=""><?php esc_html_e( 'All districts', 'kaamase-core' ); ?></option>
				<?php foreach ( $districts as $slug => $name ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $f['district'], $slug ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>

			<select name="method">
				<option value=""><?php esc_html_e( 'Any sign in', 'kaamase-core' ); ?></option>
				<?php foreach ( $methods as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $f['method'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label><?php esc_html_e( 'From', 'kaamase-core' ); ?>
				<input type="date" name="from" value="<?php echo esc_attr( $f['from'] ); ?>"></label>
			<label><?php esc_html_e( 'To', 'kaamase-core' ); ?>
				<input type="date" name="to" value="<?php echo esc_attr( $f['to'] ); ?>"></label>

			<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'kaamase-core' ); ?></button>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=kaamase-insights' ) ); ?>"><?php esc_html_e( 'Clear', 'kaamase-core' ); ?></a>
		</form>
		<?php
	}
}

if ( ! function_exists( 'kaamase_insights_actions' ) ) {
	/**
	 * The export button and the box of numbers.
	 *
	 * The box holds the numbers for this page only. Pasting six hundred
	 * numbers into a broadcast list is not something to do by accident,
	 * and the spreadsheet is the right tool when that really is the job.
	 *
	 * @since 1.8.0
	 * @param array $f    Filters.
	 * @param array $list Matching rows.
	 * @return void
	 */
	function kaamase_insights_actions( $f, $list ) {

		$numbers = array();

		foreach ( $list['rows'] as $row ) {
			if ( '' !== $row['phone'] ) {
				$numbers[] = kaamase_insights_wa( $row['phone'] );
			}
		}

		$export = wp_nonce_url(
			add_query_arg(
				array_merge(
					array( 'action' => 'kaamase_insights_export' ),
					array_filter(
						array(
							's'        => $f['search'],
							'kind'     => $f['kind'],
							'district' => $f['district'],
							'state'    => $f['state'],
							'method'   => $f['method'],
							'from'     => $f['from'],
							'to'       => $f['to'],
						),
						static function ( $v ) {
							return '' !== $v;
						}
					)
				),
				admin_url( 'admin-post.php' )
			),
			'kaamase_insights_export'
		);

		?>
		<p class="ka-ins__actions">
			<a class="button button-primary" href="<?php echo esc_url( $export ); ?>">
				<?php esc_html_e( 'Download all matches as a spreadsheet', 'kaamase-core' ); ?>
			</a>
		</p>

		<?php if ( ! empty( $numbers ) ) : ?>
			<details class="ka-ins__details">
				<summary>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: how many numbers are in the box */
							__( 'Phone numbers on this page, ready to paste (%s)', 'kaamase-core' ),
							number_format_i18n( count( $numbers ) )
						)
					);
					?>
				</summary>
				<p class="description"><?php esc_html_e( 'With the country code, which is what WhatsApp and most message tools want. Select all and copy.', 'kaamase-core' ); ?></p>
				<textarea class="ka-ins__numbers" rows="6" readonly onclick="this.select()"><?php echo esc_textarea( implode( ', ', $numbers ) ); ?></textarea>
			</details>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'kaamase_insights_table' ) ) {
	/**
	 * The list itself.
	 *
	 * @since 1.8.0
	 * @param array $list    Rows and total.
	 * @param array $kinds   Profile types.
	 * @param array $methods Sign up methods.
	 * @return void
	 */
	function kaamase_insights_table( $list, $kinds, $methods ) {

		?>
		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'Name', 'kaamase-core' ); ?></th>
				<th><?php esc_html_e( 'Kind', 'kaamase-core' ); ?></th>
				<th><?php esc_html_e( 'Phone', 'kaamase-core' ); ?></th>
				<th><?php esc_html_e( 'Email', 'kaamase-core' ); ?></th>
				<th><?php esc_html_e( 'Where', 'kaamase-core' ); ?></th>
				<th><?php esc_html_e( 'Confirmed', 'kaamase-core' ); ?></th>
				<th><?php esc_html_e( 'Signed up with', 'kaamase-core' ); ?></th>
				<th><?php esc_html_e( 'Registered', 'kaamase-core' ); ?></th>
			</tr></thead>
			<tbody>
			<?php if ( empty( $list['rows'] ) ) : ?>
				<tr><td colspan="8"><?php esc_html_e( 'Nobody matches those filters.', 'kaamase-core' ); ?></td></tr>
			<?php endif; ?>
			<?php foreach ( $list['rows'] as $row ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $row['profile'] ) ); ?>">
							<?php echo esc_html( $row['name'] ); ?></a>
					</td>
					<td><?php echo esc_html( isset( $kinds[ $row['kind'] ] ) ? $kinds[ $row['kind'] ] : $row['kind'] ); ?></td>
					<td>
						<?php if ( '' !== $row['phone'] ) : ?>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $row['phone'] ) ); ?>"><?php echo esc_html( $row['phone'] ); ?></a>
						<?php else : ?>
							<span class="ka-ins__muted"><?php esc_html_e( 'None', 'kaamase-core' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $row['email'] ); ?></td>
					<td><?php echo esc_html( '' !== $row['district'] ? $row['district'] : '—' ); ?></td>
					<td>
						<?php if ( $row['confirmed'] ) : ?>
							<span class="ka-ins__yes"><?php esc_html_e( 'Yes', 'kaamase-core' ); ?></span>
						<?php else : ?>
							<span class="ka-ins__no"><?php esc_html_e( 'No', 'kaamase-core' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( isset( $methods[ $row['method'] ] ) ? $methods[ $row['method'] ] : $row['method'] ); ?></td>
					<td><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $row['registered'] . ' UTC' ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

if ( ! function_exists( 'kaamase_insights_styles' ) ) {
	/**
	 * The screen's own styles.
	 *
	 * Inline because this is one admin screen and a stylesheet file for
	 * it would be another request for something nobody else reads. The
	 * two series colours are a checked pair: they stay apart for the
	 * commonest colour blindness as well as for everybody else, which
	 * matters here because the whole point of the chart is telling the
	 * two apart.
	 *
	 * @since 1.8.0
	 * @return void
	 */
	function kaamase_insights_styles() {

		?>
		<style>
		.kaamase-insights {
			--ka-yes: #2a78d6;
			--ka-no: #eb6834;
			--ka-ink: #1d2327;
			--ka-muted: #646970;
			--ka-line: #dcdcde;
			--ka-surface: #fff;
		}
		.ka-ins__tiles { display: flex; flex-wrap: wrap; gap: 12px; margin: 16px 0; }
		.ka-ins__tile {
			background: var(--ka-surface); border: 1px solid var(--ka-line);
			border-radius: 6px; padding: 12px 16px; min-width: 120px;
		}
		.ka-ins__tile-label { display: block; font-size: 12px; color: var(--ka-muted); }
		.ka-ins__tile-value { display: block; font-size: 26px; font-weight: 600; color: var(--ka-ink); line-height: 1.2; }
		.ka-ins__tile-note { display: block; font-size: 12px; color: var(--ka-muted); }
		.ka-ins__methods { color: var(--ka-muted); }

		.ka-ins__legend { display: flex; gap: 16px; font-size: 12px; color: var(--ka-muted); margin-bottom: 6px; }
		.ka-ins__swatch { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 5px; vertical-align: baseline; }
		.ka-ins__swatch--yes { background: var(--ka-yes); }
		.ka-ins__swatch--no { background: var(--ka-no); }

		.ka-ins__chart {
			display: flex; align-items: flex-end; gap: 3px; height: 160px;
			background: var(--ka-surface); border: 1px solid var(--ka-line);
			border-radius: 6px; padding: 12px; overflow-x: auto;
		}
		.ka-ins__col { position: relative; flex: 1 1 0; min-width: 10px; height: 100%; display: flex; align-items: flex-end; }
		.ka-ins__stack { width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; gap: 2px; }
		.ka-ins__bar { width: 100%; }
		/* Rounded only at the top of the stack, anchored to the baseline. */
		.ka-ins__bar--no { background: var(--ka-no); border-radius: 3px 3px 0 0; }
		.ka-ins__bar--yes { background: var(--ka-yes); }
		.ka-ins__stack .ka-ins__bar--yes:first-child { border-radius: 3px 3px 0 0; }
		.ka-ins__col:hover .ka-ins__stack { outline: 2px solid var(--ka-line); outline-offset: 1px; }
		.ka-ins__tip {
			display: none; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
			background: var(--ka-ink); color: #fff; font-size: 12px; line-height: 1.4;
			padding: 5px 8px; border-radius: 4px; white-space: nowrap; z-index: 5; margin-bottom: 6px;
		}
		.ka-ins__col:hover .ka-ins__tip { display: block; }

		.ka-ins__details { margin: 10px 0 24px; }
		.ka-ins__details summary { cursor: pointer; color: var(--ka-muted); }
		.ka-ins__daytable, .ka-ins__places { max-width: 720px; margin-top: 8px; }
		.ka-ins__places .ka-ins__meter { width: 50%; }
		.ka-ins__places .ka-ins__meter span { display: block; height: 10px; border-radius: 3px; background: var(--ka-yes); }
		.ka-ins__num { text-align: right; width: 90px; }
		.ka-ins__muted { color: var(--ka-muted); }

		.ka-ins__filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin: 12px 0; }
		.ka-ins__filters label { font-size: 12px; color: var(--ka-muted); }
		.ka-ins__count { margin: 8px 0; }
		.ka-ins__actions { margin: 12px 0; }
		.ka-ins__numbers { width: 100%; max-width: 720px; font-family: monospace; font-size: 12px; }

		.ka-ins__yes { color: var(--ka-yes); font-weight: 600; }
		.ka-ins__no { color: var(--ka-no); font-weight: 600; }

		.ka-ins__send {
			background: var(--ka-surface); border: 1px solid var(--ka-line);
			border-radius: 6px; padding: 12px 16px; max-width: 720px; margin-bottom: 24px;
		}
		.ka-ins__send label { margin-right: 6px; }
		.ka-ins__send select { margin-right: 8px; }

		@media (prefers-color-scheme: dark) {
			.kaamase-insights {
				--ka-yes: #3987e5; --ka-no: #d95926;
				--ka-ink: #e6e6e6; --ka-muted: #a7aaad;
				--ka-line: #3c434a; --ka-surface: #1d2327;
			}
		}
		</style>
		<?php
	}
}
