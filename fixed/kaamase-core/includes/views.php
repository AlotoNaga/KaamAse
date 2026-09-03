<?php
/**
 * Who looked, and how often.
 *
 * Counting only. Nothing on this platform displays any of it yet; that
 * is deliberate, so the numbers have something in them before the first
 * person sees one. A profile that says 0 views on the day the feature
 * arrives is worse than no number at all.
 *
 * What a view is
 * --------------
 * One person opening one profile or one job. Counted again when they
 * come back later, which is what makes the number feel like a view
 * count rather than a headcount, and at most once every thirty minutes
 * so that holding down refresh does nothing. The first person to notice
 * that refreshing pumps the number is the person who makes every number
 * on the site meaningless.
 *
 * Never your own profile, and never staff. An owner checking their own
 * page all day would otherwise be most of their own audience.
 *
 * One row per viewer per day
 * --------------------------
 * Rather than a row per view. A busy profile is then a handful of rows a
 * day instead of hundreds, and both numbers fall out of one query:
 * SUM(hits) is how many views, COUNT(*) is how many people. Showing only
 * the first tells a worker she is in demand when four people looked and
 * none called.
 *
 * People who are not signed in
 * ----------------------------
 * They count, and there is nobody to name. Telling them apart needs
 * something per visitor, and an IP address is not something worth
 * keeping: visitor_key is a one way hash of the address, the browser and
 * TODAY, salted with the site's own key. It cannot be turned back into
 * an address, it is a different value for the same person tomorrow, and
 * the daily prune takes it away entirely.
 *
 * The table looks after itself
 * ----------------------------
 * Created on demand rather than by a version number. Tying setup to a
 * version is what lost the employers page: the number was written while
 * the file that needed it was still being copied, and the work never ran
 * again. Anything missing here is simply made.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;


/** How long a row is kept. The paid history window is a year. */
if ( ! defined( 'KAAMASE_VIEWS_KEEP_DAYS' ) ) {
	define( 'KAAMASE_VIEWS_KEEP_DAYS', 365 );
}

/** How long before the same person looking again counts again. */
if ( ! defined( 'KAAMASE_VIEWS_COOLDOWN' ) ) {
	define( 'KAAMASE_VIEWS_COOLDOWN', 30 * MINUTE_IN_SECONDS );
}

/** Bumped when the table's shape changes. */
/*
 * No cooldown on a showing. Every time a card comes past on somebody's
 * screen is a separate showing, which is what a showing means: scroll
 * down, scroll back up, and the second look counts as well as the
 * first. This is the ordinary meaning of an impression and it is the
 * behaviour the platform is meant to have.
 *
 * Zero still leaves one guard, and it is the one worth keeping. The
 * counting statement only adds a hit when last_hit is older than this,
 * so at zero two reports arriving in the same second collapse into
 * one. That stops a retry or a double-fired beacon counting twice, and
 * it costs nothing real: a card has to be half visible for a full
 * second before it is reported at all, so no honest second showing can
 * land inside the same second as the first.
 *
 * An opening keeps its thirty minutes. Opening a profile twice in an
 * afternoon is one person deciding about one worker, not two views.
 */
if ( ! defined( 'KAAMASE_SHOWN_COOLDOWN' ) ) {
	define( 'KAAMASE_SHOWN_COOLDOWN', 0 );
}

if ( ! defined( 'KAAMASE_VIEWS_SCHEMA' ) ) {
	define( 'KAAMASE_VIEWS_SCHEMA', 2 );
}


/* ==========================================================================
   1. THE TABLE
   ========================================================================== */

if ( ! function_exists( 'kaamase_views_table' ) ) {
	/**
	 * The table name, with this installation's prefix.
	 *
	 * @since 1.4.3
	 * @return string
	 */
	function kaamase_views_table() {

		global $wpdb;

		return $wpdb->prefix . 'kaamase_views';
	}
}

if ( ! function_exists( 'kaamase_views_install' ) ) {
	/**
	 * Create or update the table.
	 *
	 * dbDelta is safe to run against a table that already exists: it
	 * compares and alters rather than replacing, so nothing stored is
	 * lost by calling this again.
	 *
	 * @since 1.4.3
	 * @return bool Whether the table is now in the shape this code needs.
	 */
	function kaamase_views_install() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = kaamase_views_table();
		$collate = $wpdb->get_charset_collate();

		/*
		 * The unique key is what makes one row per viewer per day, and
		 * it is what the counting statement relies on: the insert either
		 * makes the row or bumps the one that is already there, in a
		 * single statement with no read first and no race.
		 *
		 * viewer_id and visitor_key are both in it because one is empty
		 * whenever the other is set. A signed in person is their user
		 * id; a stranger is their hash for the day.
		 */
		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				subject_id bigint(20) unsigned NOT NULL DEFAULT 0,
				subject_type varchar(20) NOT NULL DEFAULT '',
				viewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
				visitor_key char(12) NOT NULL DEFAULT '',
				seen_on date NOT NULL DEFAULT '1970-01-01',
				kind varchar(6) NOT NULL DEFAULT 'open',
				hits int(10) unsigned NOT NULL DEFAULT 0,
				last_hit int(10) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY seen (subject_id,viewer_id,visitor_key,seen_on,kind),
				KEY subject (subject_id,seen_on),
				KEY viewer (viewer_id,seen_on),
				KEY tidy (seen_on)
			) {$collate};"
		);

		/*
		 * After dbDelta, so the column the key names is already there,
		 * and only when it is actually needed. See the helper below for
		 * why dbDelta cannot be left to do this itself.
		 */
		if ( ! kaamase_views_key_has_kind() ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query(
				"ALTER TABLE {$table}
				 DROP INDEX seen,
				 ADD UNIQUE KEY seen (subject_id,viewer_id,visitor_key,seen_on,kind)"
			);
		}

		/*
		 * Checked again rather than assumed. If that ALTER did not take
		 * -- no permission on a locked-down host, a lock it could not
		 * get -- then the old key is still in place, and every showing
		 * would collide with that day's opening and quietly bump the
		 * opened count instead. Numbers that are wrong and look right
		 * are worse than numbers that have stopped, so the schema is
		 * not marked done, nothing is counted, and the next admin page
		 * load tries again.
		 */
		if ( ! kaamase_views_key_has_kind() ) {
			return false;
		}

		update_option( 'kaamase_views_schema', KAAMASE_VIEWS_SCHEMA, false );

		return true;
	}
}

if ( ! function_exists( 'kaamase_views_key_has_kind' ) ) {
	/**
	 * Whether the unique key tells an opening from a showing.
	 *
	 * dbDelta adds a column that is missing but will not rebuild a key
	 * that already exists under the same name with different columns.
	 * So on a site upgrading from schema 1 the kind column appears and
	 * the key does not change, which is the one state that has to be
	 * found and repaired rather than trusted.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	function kaamase_views_key_has_kind() {

		global $wpdb;

		$table = kaamase_views_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$index = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Key_name = 'seen'", ARRAY_A );

		if ( empty( $index ) ) {
			return false;
		}

		return in_array( 'kind', (array) wp_list_pluck( $index, 'Column_name' ), true );
	}
}

if ( ! function_exists( 'kaamase_views_ready' ) ) {
	/**
	 * Whether the table is there, making it if it is not.
	 *
	 * Answered from an option rather than by asking the database on
	 * every request. The option is the fast path; SHOW TABLES only runs
	 * when the option says the table has never been made, or says it was
	 * made to an older shape.
	 *
	 * @since 1.4.3
	 * @return bool
	 */
	function kaamase_views_ready() {

		static $ready = null;

		if ( null !== $ready ) {
			return $ready;
		}

		if ( (int) get_option( 'kaamase_views_schema', 0 ) === KAAMASE_VIEWS_SCHEMA ) {
			$ready = true;
			return $ready;
		}

		/*
		 * Making a table is not something to attempt from whatever
		 * request happens to arrive first. It is left to an admin page
		 * load, which is rare, unhurried, and where a failure is visible
		 * to somebody who can act on it. Until then nothing is counted,
		 * which loses a few views and breaks nothing.
		 */
		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			$ready = false;
			return $ready;
		}

		$ready = kaamase_views_install();

		return $ready;
	}
}
add_action( 'admin_init', 'kaamase_views_ready' );


/* ==========================================================================
   2. WHAT COUNTS AS A VIEW
   ========================================================================== */

if ( ! function_exists( 'kaamase_view_subject_type' ) ) {
	/**
	 * The short name a post type is stored under, or nothing.
	 *
	 * Anything not named here is not counted at all, so adding a post
	 * type later is a deliberate act rather than something that starts
	 * happening on its own.
	 *
	 * @since 1.4.3
	 * @param string $post_type Post type.
	 * @return string worker, gang, employer, job, or an empty string.
	 */
	function kaamase_view_subject_type( $post_type ) {

		$map = array(
			'kaamase_worker'   => 'worker',
			'kaamase_gang'     => 'gang',
			'kaamase_employer' => 'employer',
			'kaamase_job'      => 'job',
		);

		return isset( $map[ $post_type ] ) ? $map[ $post_type ] : '';
	}
}

if ( ! function_exists( 'kaamase_view_visitor_key' ) ) {
	/**
	 * Something to tell one stranger from another, for today only.
	 *
	 * Not an IP address and not stored as one. The address and the
	 * browser go in, a twelve character hash comes out, and the day and
	 * the site's own salt are mixed in so the same person is a different
	 * value tomorrow and the value is worthless anywhere else.
	 *
	 * The point is only to stop one stranger's refresh counting as
	 * another stranger's visit. It is not identity and cannot become it.
	 *
	 * @since 1.4.3
	 * @return string
	 */
	function kaamase_view_visitor_key() {

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

		if ( '' === $ip && '' === $ua ) {
			return '';
		}

		return substr(
			hash_hmac( 'sha256', $ip . '|' . $ua . '|' . gmdate( 'Y-m-d' ), wp_salt( 'auth' ) ),
			0,
			12
		);
	}
}

if ( ! function_exists( 'kaamase_view_should_count' ) ) {
	/**
	 * Whether this request should be counted at all.
	 *
	 * Split out from the recording so the rules can be read in one
	 * place, and tested without a database.
	 *
	 * @since 1.4.3
	 * @param int $post_id  The profile or job.
	 * @param int $viewer_id Signed in account, or 0.
	 * @return bool
	 */
	function kaamase_view_should_count( $post_id, $viewer_id ) {

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

		if ( '' === kaamase_view_subject_type( $post->post_type ) ) {
			return false;
		}

		/*
		 * Your own page is not an audience. Somebody checking how their
		 * profile looks, several times a day, would otherwise be most of
		 * their own number and the number would mean nothing.
		 */
		if ( $viewer_id && function_exists( 'kaamase_user_owns' ) && kaamase_user_owns( $post_id, $viewer_id ) ) {
			return false;
		}

		/*
		 * Nor is staff. Moderating a hundred profiles would put a view
		 * on each of them, and the person it is shown to has no way to
		 * tell that from real interest.
		 */
		if ( $viewer_id && user_can( $viewer_id, 'manage_options' ) ) {
			return false;
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

		if ( '' === $ua ) {
			return false;
		}

		/*
		 * Crawlers are most of the traffic on any public page and none
		 * of the interest. The same list the store redirect uses.
		 */
		$bots = 'bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|'
			. 'twitterbot|linkedinbot|embedly|quora|pinterest|preview|'
			. 'google-inspection|lighthouse|headless';

		if ( preg_match( '/' . $bots . '/i', $ua ) ) {
			return false;
		}

		/**
		 * Filter whether one request counts.
		 *
		 * @since 1.4.3
		 * @param bool $count     Whether to count it.
		 * @param int  $post_id   The profile or job.
		 * @param int  $viewer_id Signed in account, or 0.
		 */
		return (bool) apply_filters( 'kaamase_view_should_count', true, $post_id, $viewer_id );
	}
}

if ( ! function_exists( 'kaamase_record_view' ) ) {
	/**
	 * Count one view.
	 *
	 * One statement. The unique key decides whether this makes a row or
	 * bumps the one already there, and the cooldown is applied inside
	 * the same statement rather than by reading first and writing after,
	 * so two requests arriving together cannot both decide they are the
	 * first.
	 *
	 * The two kinds hold that cooldown to very different lengths. An
	 * opening is half an hour, because opening the same profile twice
	 * in an afternoon is one person deciding about one worker. A
	 * showing is one second, because being scrolled past twice is
	 * genuinely being seen twice.
	 *
	 * @since 1.4.3
	 * @param int    $post_id The profile or job being looked at.
	 * @param string $kind    'open' when it was opened, 'shown' when it
	 *                        only went past in a list. Anything else is
	 *                        read as 'open'.
	 * @return bool Whether anything was written.
	 */
	function kaamase_record_view( $post_id, $kind = 'open' ) {

		global $wpdb;

		$post_id = absint( $post_id );
		$kind    = 'shown' === $kind ? 'shown' : 'open';

		if ( ! $post_id || ! kaamase_views_ready() ) {
			return false;
		}

		$viewer_id = get_current_user_id();

		if ( ! kaamase_view_should_count( $post_id, $viewer_id ) ) {
			return false;
		}

		$post = get_post( $post_id );
		$key  = $viewer_id ? '' : kaamase_view_visitor_key();

		// Nothing to tell one stranger from another. Not counted.
		if ( ! $viewer_id && '' === $key ) {
			return false;
		}

		$table = kaamase_views_table();
		$now   = time();
		$stale = $now - ( 'shown' === $kind ? KAAMASE_SHOWN_COOLDOWN : KAAMASE_VIEWS_COOLDOWN );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table}
					(subject_id, subject_type, viewer_id, visitor_key, seen_on, kind, hits, last_hit)
				 VALUES (%d, %s, %d, %s, %s, %s, 1, %d)
				 ON DUPLICATE KEY UPDATE
					hits     = hits + IF(last_hit < %d, 1, 0),
					last_hit = IF(last_hit < %d, VALUES(last_hit), last_hit)",
				$post_id,
				kaamase_view_subject_type( $post->post_type ),
				$viewer_id,
				$key,
				gmdate( 'Y-m-d', $now ),
				$kind,
				$now,
				$stale,
				$stale
			)
		);
		// phpcs:enable

		return true;
	}
}


/* ==========================================================================
   3. COUNTING THE WEBSITE'S OWN PAGES

   On shutdown, after everything has been sent. Nobody waits on a page
   load for a counter, and a counter that fails takes nothing with it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_view_catch_singular' ) ) {
	/**
	 * Note which profile or job this request is showing.
	 *
	 * @since 1.4.3
	 * @return void
	 */
	function kaamase_view_catch_singular() {

		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post_id = get_queried_object_id();

		if ( $post_id ) {
			$GLOBALS['kaamase_view_pending'] = (int) $post_id;
		}
	}
}
add_action( 'template_redirect', 'kaamase_view_catch_singular', 20 );

if ( ! function_exists( 'kaamase_view_record_pending' ) ) {
	/**
	 * Count it, once the page has gone.
	 *
	 * @since 1.4.3
	 * @return void
	 */
	function kaamase_view_record_pending() {

		if ( empty( $GLOBALS['kaamase_view_pending'] ) ) {
			return;
		}

		$post_id = (int) $GLOBALS['kaamase_view_pending'];

		unset( $GLOBALS['kaamase_view_pending'] );

		kaamase_record_view( $post_id );
	}
}
add_action( 'shutdown', 'kaamase_view_record_pending' );


/* ==========================================================================
   4. READING THE NUMBERS

   Nothing calls these yet. They are here so that the screens added
   later have one place to ask, rather than each writing its own SQL.
   ========================================================================== */

if ( ! function_exists( 'kaamase_views_primed' ) ) {
	/**
	 * The counts already fetched for this page.
	 *
	 * @since 1.5.0
	 * @param array|null $put Rows to remember, or null to read.
	 * @return array Keyed by post ID.
	 */
	function kaamase_views_primed( $put = null ) {

		static $primed = array();

		if ( is_array( $put ) ) {
			$primed = $primed + $put;
		}

		return $primed;
	}
}

if ( ! function_exists( 'kaamase_views_prime' ) ) {
	/**
	 * Fetch the counts for a whole page in one go.
	 *
	 * Hooked to the_posts, so a listing pays one query for the numbers
	 * on it rather than one per card. A post with no views at all still
	 * gets an answer remembered, otherwise every empty profile on the
	 * page would fall through and ask again on its own.
	 *
	 * @since 1.5.0
	 * @param int[] $post_ids Posts about to be drawn.
	 * @return void
	 */
	function kaamase_views_prime( $post_ids ) {

		global $wpdb;

		$post_ids = array_filter( array_map( 'absint', (array) $post_ids ) );
		$already  = kaamase_views_primed();
		$wanted   = array();

		/*
		 * Both kinds are fetched together, so a listing showing an
		 * opened count and a shown count still pays for one query
		 * rather than two.
		 */
		foreach ( array_unique( $post_ids ) as $id ) {
			if ( ! isset( $already[ 'open:' . $id ] ) ) {
				$wanted[] = $id;
			}
		}

		if ( empty( $wanted ) || ! kaamase_views_ready() ) {
			return;
		}

		$table = kaamase_views_table();
		$in    = implode( ',', array_map( 'absint', $wanted ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT subject_id, kind,
					COALESCE(SUM(hits),0) AS total,
					COUNT(DISTINCT CONCAT(viewer_id, ':', visitor_key)) AS people
			 FROM {$table}
			 WHERE subject_id IN ({$in})
			 GROUP BY subject_id, kind",
			ARRAY_A
		);
		// phpcs:enable

		$found = array();

		foreach ( (array) $rows as $row ) {

			$kind = 'shown' === $row['kind'] ? 'shown' : 'open';

			$found[ $kind . ':' . (int) $row['subject_id'] ] = array(
				'total'  => (int) $row['total'],
				'people' => (int) $row['people'],
				'days'   => KAAMASE_VIEWS_KEEP_DAYS,
			);
		}

		// Nothing found still counts as an answer, for both kinds.
		foreach ( $wanted as $id ) {
			foreach ( array( 'open', 'shown' ) as $kind ) {
				if ( ! isset( $found[ $kind . ':' . $id ] ) ) {
					$found[ $kind . ':' . $id ] = array( 'total' => 0, 'people' => 0, 'days' => KAAMASE_VIEWS_KEEP_DAYS );
				}
			}
		}

		kaamase_views_primed( $found );
	}
}

if ( ! function_exists( 'kaamase_views_prime_loop' ) ) {
	/**
	 * Prime whatever a query is about to hand the page.
	 *
	 * @since 1.5.0
	 * @param WP_Post[] $posts The posts.
	 * @return WP_Post[] The same posts, untouched.
	 */
	function kaamase_views_prime_loop( $posts ) {

		if ( is_admin() || empty( $posts ) || ! is_array( $posts ) ) {
			return $posts;
		}

		$ids = array();

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post && kaamase_view_subject_type( $post->post_type ) ) {
				$ids[] = $post->ID;
			}
		}

		if ( ! empty( $ids ) ) {
			kaamase_views_prime( $ids );
		}

		return $posts;
	}
}
add_filter( 'the_posts', 'kaamase_views_prime_loop' );

if ( ! function_exists( 'kaamase_views_count' ) ) {
	/**
	 * How many views a profile or job has, and from how many people.
	 *
	 * Both, always, because one without the other misleads. Four
	 * hundred views from three people is a different thing from four
	 * hundred views from four hundred people, and only the second is
	 * what somebody reading "400 views" will assume.
	 *
	 * @since 1.4.3
	 * @param int    $post_id The profile or job.
	 * @param int    $days    How far back, or 0 for everything kept.
	 * @param string $kind    'open' for openings, 'shown' for showings in
	 *                        a list. Anything else is read as 'open'.
	 * @return array total, people, days.
	 */
	function kaamase_views_count( $post_id, $days = 0, $kind = 'open' ) {

		global $wpdb;

		$post_id = absint( $post_id );
		$days    = absint( $days );
		$kind    = 'shown' === $kind ? 'shown' : 'open';

		$empty = array(
			'total'  => 0,
			'people' => 0,
			'days'   => $days ? $days : KAAMASE_VIEWS_KEEP_DAYS,
		);

		if ( ! $post_id || ! kaamase_views_ready() ) {
			return $empty;
		}

		/*
		 * Answered from the batch when the page has already asked for
		 * everything on it. A listing draws twenty cards, and twenty
		 * separate counts is twenty queries on shared hosting for a
		 * number nobody came to the page to read.
		 *
		 * Only the whole-window count is batched. A narrowed one is
		 * rare and asks its own question.
		 */
		if ( ! $days ) {

			$primed = kaamase_views_primed();

			if ( isset( $primed[ $kind . ':' . $post_id ] ) ) {
				return $primed[ $kind . ':' . $post_id ];
			}
		}

		$table = kaamase_views_table();

		$where = $wpdb->prepare( 'subject_id = %d AND kind = %s', $post_id, $kind );

		if ( $days ) {
			$where .= $wpdb->prepare(
				' AND seen_on >= %s',
				gmdate( 'Y-m-d', time() - ( $days * DAY_IN_SECONDS ) )
			);
		}

		/*
		 * DISTINCT on the viewer, not COUNT(*).
		 *
		 * A row is one viewer on one day, so somebody who looked on
		 * Monday and again on Friday is two rows. Counting rows would
		 * call that two people, and "people" would end up meaning
		 * roughly the same thing as "views" -- which is the whole
		 * distinction this function exists to keep.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			"SELECT COALESCE(SUM(hits),0) AS total,
					COUNT(DISTINCT CONCAT(viewer_id, ':', visitor_key)) AS people
			 FROM {$table} WHERE {$where}",
			ARRAY_A
		);
		// phpcs:enable

		if ( ! $row ) {
			return $empty;
		}

		return array(
			'total'  => (int) $row['total'],
			'people' => (int) $row['people'],
			'days'   => $days ? $days : KAAMASE_VIEWS_KEEP_DAYS,
		);
	}
}

if ( ! function_exists( 'kaamase_views_of_mine' ) ) {
	/**
	 * The people who looked at anything this account owns.
	 *
	 * Signed in viewers only. A stranger has no name to show, and a row
	 * saying "somebody" tells nobody anything they can act on.
	 *
	 * @since 1.4.3
	 * @param int $user_id Whose profiles and jobs.
	 * @param int $days    How far back.
	 * @param int $limit   Most rows to return.
	 * @return array[] Newest first.
	 */
	function kaamase_views_of_mine( $user_id, $days = 7, $limit = 100 ) {

		global $wpdb;

		$user_id = absint( $user_id );
		$days    = max( 1, absint( $days ) );
		$limit   = min( 500, max( 1, absint( $limit ) ) );

		if ( ! $user_id || ! kaamase_views_ready() ) {
			return array();
		}

		$mine = get_posts(
			array(
				'post_type'      => array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer', 'kaamase_job' ),
				'author'         => $user_id,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $mine ) ) {
			return array();
		}

		$table  = kaamase_views_table();
		$in     = implode( ',', array_map( 'absint', $mine ) );
		$since  = gmdate( 'Y-m-d', time() - ( $days * DAY_IN_SECONDS ) );

		/*
		 * Grouped by viewer and subject, so somebody who came back four
		 * times over a week is one line saying so rather than four lines
		 * saying the same thing.
		 */
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT subject_id, subject_type, viewer_id,
						SUM(hits) AS hits, MAX(last_hit) AS last_seen
				 FROM {$table}
				 WHERE subject_id IN ({$in})
				   AND viewer_id > 0
				   AND kind = 'open'
				   AND seen_on >= %s
				 GROUP BY subject_id, subject_type, viewer_id
				 ORDER BY last_seen DESC
				 LIMIT %d",
				$since,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable

		return is_array( $rows ) ? $rows : array();
	}
}


/* ==========================================================================
   5. FORGETTING

   Three ways a row stops being wanted, and all three are handled. A
   viewing record that outlives the thing it was about, or the person it
   was about, is a log nobody agreed to keep.
   ========================================================================== */

if ( ! function_exists( 'kaamase_views_prune' ) ) {
	/**
	 * Delete anything past the keeping window.
	 *
	 * Deleted in batches. One statement removing a year of rows from a
	 * busy site can hold a lock long enough for everything else to
	 * notice, and this runs on the same shared hosting as the site.
	 *
	 * @since 1.4.3
	 * @return int How many rows went.
	 */
	function kaamase_views_prune() {

		global $wpdb;

		if ( ! kaamase_views_ready() ) {
			return 0;
		}

		$table  = kaamase_views_table();
		$cutoff = gmdate( 'Y-m-d', time() - ( KAAMASE_VIEWS_KEEP_DAYS * DAY_IN_SECONDS ) );
		$gone   = 0;

		for ( $pass = 0; $pass < 20; $pass++ ) {

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$went = (int) $wpdb->query(
				$wpdb->prepare( "DELETE FROM {$table} WHERE seen_on < %s LIMIT 1000", $cutoff )
			);
			// phpcs:enable

			$gone += $went;

			if ( $went < 1000 ) {
				break;
			}
		}

		return $gone;
	}
}
add_action( 'kaamase_daily', 'kaamase_views_prune' );

if ( ! function_exists( 'kaamase_views_forget_subject' ) ) {
	/**
	 * Drop every view of a profile or job that has been deleted.
	 *
	 * @since 1.4.3
	 * @param int $post_id The post that went.
	 * @return void
	 */
	function kaamase_views_forget_subject( $post_id ) {

		global $wpdb;

		$post_id = absint( $post_id );

		if ( ! $post_id || ! kaamase_views_ready() ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( kaamase_views_table(), array( 'subject_id' => $post_id ), array( '%d' ) );
	}
}
add_action( 'deleted_post', 'kaamase_views_forget_subject' );

if ( ! function_exists( 'kaamase_views_forget_viewer' ) ) {
	/**
	 * Drop every record of what one account looked at.
	 *
	 * Called when somebody erases their account. Their browsing is
	 * theirs, and keeping a record of it after they have asked to be
	 * removed is the thing this function exists to make impossible.
	 *
	 * The counts on other people's profiles fall by whatever this
	 * person contributed, which is correct: those views were theirs.
	 *
	 * @since 1.4.3
	 * @param int $user_id The account being erased.
	 * @return void
	 */
	function kaamase_views_forget_viewer( $user_id ) {

		global $wpdb;

		$user_id = absint( $user_id );

		if ( ! $user_id || ! kaamase_views_ready() ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( kaamase_views_table(), array( 'viewer_id' => $user_id ), array( '%d' ) );
	}
}
add_action( 'deleted_user', 'kaamase_views_forget_viewer' );
