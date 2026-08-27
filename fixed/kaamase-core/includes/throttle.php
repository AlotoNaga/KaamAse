<?php
/**
 * Throttling.
 *
 * One counter, used by both the website and the app, so a limit cannot
 * be tighter on one than the other by accident.
 *
 * Why one file
 * ------------
 * The website and the REST API had grown their own limits, and they had
 * already drifted: app registration was counted against the login
 * failure counter, which a successful registration then cleared, so
 * registering repeatedly reset the very limit meant to stop it. Nobody
 * wrote that on purpose. It is what happens when two routes to the same
 * action each keep their own tally.
 *
 * So the counters live here and both sides call the same function. A
 * limit changed once is changed everywhere.
 *
 * What is counted
 * ---------------
 * Attempts, not failures, for anything that creates something or sends
 * an email. A limiter that only counts failures does not limit anything
 * when the attempts succeed, which is exactly the case worth limiting:
 * a thousand successful password reset emails to somebody else's
 * address is the attack, and every one of them is a success.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.3.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. DURABLE STORAGE

   Every limit on this platform used to be kept in a transient.

   On a site with no persistent object cache that is fine, because a
   transient with an expiry is written to the options table. On a site
   with Redis or Memcached in front of it, which is most managed Indian
   hosting, a transient lives only in that cache. Flush the cache,
   evict it under memory pressure, or press the Clear Transients button
   that nearly every caching plugin puts in its toolbar, and every
   counter on the site silently resets to zero.

   The counters that reset are the anti scraping cap on contact
   reveals, the registration limit, the report limit, the daily posting
   cap and the sign in lockout. All of them are security controls, and
   a security control that a caching plugin can clear is not one.

   Options instead. get_option falls back to the database when the cache
   does not have the value, so the count survives a flush. Rows are
   small, carry their own expiry, and are collected on the daily task
   that already exists, so the table does not grow without bound.

   No new database table, deliberately: a table needs an activation
   hook to create it, and this plugin is updated by replacing files.
   ========================================================================== */

if ( ! function_exists( 'kaamase_rate_option_name' ) ) {
	/**
	 * Option name for a counter.
	 *
	 * Hashed so that an address or an email address never appears in the
	 * options table in the clear.
	 *
	 * @since 1.3.3
	 * @param string $key Counter key.
	 * @return string
	 */
	function kaamase_rate_option_name( $key ) {
		return 'kaamase_rate_' . md5( (string) $key );
	}
}

if ( ! function_exists( 'kaamase_rate_read' ) ) {
	/**
	 * Read a counter, treating anything past its window as absent.
	 *
	 * @since 1.3.3
	 * @param string $key Counter key.
	 * @return array|null Array with value and expires, or null.
	 */
	function kaamase_rate_read( $key ) {

		$stored = get_option( kaamase_rate_option_name( $key ), null );

		if ( ! is_array( $stored ) || ! isset( $stored['expires'] ) ) {
			return null;
		}

		if ( (int) $stored['expires'] <= time() ) {
			return null;
		}

		return $stored;
	}
}

if ( ! function_exists( 'kaamase_rate_write' ) ) {
	/**
	 * Store a counter value for a window.
	 *
	 * autoload is off. These are read on the request that needs them and
	 * never on any other, so autoloading them would put every limit on
	 * the site into memory on every page view.
	 *
	 * @since 1.3.3
	 * @param string $key    Counter key.
	 * @param mixed  $value  Value to keep.
	 * @param int    $window Seconds the value stays valid.
	 * @return void
	 */
	function kaamase_rate_write( $key, $value, $window ) {

		update_option(
			kaamase_rate_option_name( $key ),
			array(
				'value'   => $value,
				'expires' => time() + max( 1, (int) $window ),
			),
			false
		);
	}
}

if ( ! function_exists( 'kaamase_rate_clear' ) ) {
	/**
	 * Forget a counter.
	 *
	 * @since 1.3.3
	 * @param string $key Counter key.
	 * @return void
	 */
	function kaamase_rate_clear( $key ) {
		delete_option( kaamase_rate_option_name( $key ) );
	}
}

if ( ! function_exists( 'kaamase_rate_value' ) ) {
	/**
	 * The stored value, or a default when the window has passed.
	 *
	 * @since 1.3.3
	 * @param string $key     Counter key.
	 * @param mixed  $default Returned when nothing is live.
	 * @return mixed
	 */
	function kaamase_rate_value( $key, $default = 0 ) {

		$stored = kaamase_rate_read( $key );

		return null === $stored ? $default : $stored['value'];
	}
}

if ( ! function_exists( 'kaamase_rate_bump' ) ) {
	/**
	 * Add one to a counter and return the new total.
	 *
	 * The window is measured from the first attempt in it, not from the
	 * last, so somebody who keeps trying cannot hold the window open for
	 * ever and then get a fresh allowance the moment it lapses.
	 *
	 * @since 1.3.3
	 * @param string $key    Counter key.
	 * @param int    $window Seconds the window lasts.
	 * @return int New count.
	 */
	function kaamase_rate_bump( $key, $window ) {

		$stored = kaamase_rate_read( $key );
		$count  = null === $stored ? 0 : (int) $stored['value'];
		$count++;

		if ( null === $stored ) {

			kaamase_rate_write( $key, $count, $window );

			return $count;
		}

		// Keep the original expiry so the window does not slide.
		update_option(
			kaamase_rate_option_name( $key ),
			array(
				'value'   => $count,
				'expires' => (int) $stored['expires'],
			),
			false
		);

		return $count;
	}
}

if ( ! function_exists( 'kaamase_rate_day' ) ) {
	/**
	 * Today, in the site's own timezone.
	 *
	 * Every daily limit used to key on gmdate, which meant the day
	 * rolled over at midnight UTC. In India that is half past five in
	 * the morning, so a contractor's posting allowance and contact
	 * allowance reset in the middle of the night before rather than at
	 * the start of the working day.
	 *
	 * @since 1.3.3
	 * @return string Date as Ymd.
	 */
	function kaamase_rate_day() {
		return function_exists( 'wp_date' ) ? (string) wp_date( 'Ymd' ) : gmdate( 'Ymd' );
	}
}

if ( ! function_exists( 'kaamase_rate_gc' ) ) {
	/**
	 * Delete counters whose window has passed.
	 *
	 * Batched, because this runs on the same cheap hosting everything
	 * else has to survive on. Anything missed is picked up tomorrow, and
	 * an expired row is harmless in the meantime because every read
	 * checks the expiry.
	 *
	 * @since 1.3.3
	 * @return int How many rows were removed.
	 */
	function kaamase_rate_gc() {

		global $wpdb;

		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 1000",
				$wpdb->esc_like( 'kaamase_rate_' ) . '%'
			)
		);

		if ( empty( $rows ) ) {
			return 0;
		}

		$removed = 0;

		foreach ( $rows as $name ) {

			$stored = get_option( $name, null );

			if ( is_array( $stored ) && isset( $stored['expires'] ) && (int) $stored['expires'] > time() ) {
				continue;
			}

			delete_option( $name );
			$removed++;
		}

		return $removed;
	}
}
add_action( 'kaamase_daily', 'kaamase_rate_gc' );


/* ==========================================================================
   2. WHO IS ASKING
   ========================================================================== */

if ( ! function_exists( 'kaamase_client_key' ) ) {
	/**
	 * Something stable to count against for one caller.
	 *
	 * Address and user agent together, hashed. The address alone puts
	 * everybody behind one office connection or one mobile carrier NAT
	 * in the same bucket, which in a state this size is a real risk of
	 * locking out a village rather than an attacker.
	 *
	 * This is a speed bump, not an identity. Anybody willing to change
	 * address gets a fresh bucket, and that is accepted: the job is to
	 * stop the cheap abuse, and the expensive kind needs a different
	 * answer than a transient.
	 *
	 * @since 1.3.0
	 * @param string $scope What is being counted, so buckets never mix.
	 * @return string
	 */
	function kaamase_client_key( $scope ) {

		$address = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: 'unknown';

		$agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		return 'ka_thr_' . sanitize_key( $scope ) . '_' . md5( $address . $agent );
	}
}

if ( ! function_exists( 'kaamase_throttle_ok' ) ) {
	/**
	 * Count one attempt and say whether it is allowed.
	 *
	 * Counts first, then decides, so an attempt that is refused still
	 * counts. Otherwise somebody who keeps going once blocked never
	 * pushes the window forward and can retry the moment it expires,
	 * every time, forever.
	 *
	 * @since 1.3.0
	 * @param string $key    A key from kaamase_client_key or similar.
	 * @param int    $limit  How many are allowed in the window.
	 * @param int    $window How long the window lasts, in seconds.
	 * @return bool True when this attempt is within the limit.
	 */
	function kaamase_throttle_ok( $key, $limit, $window ) {

		$count = kaamase_rate_bump( $key, $window );

		return $count <= (int) $limit;
	}
}

if ( ! function_exists( 'kaamase_throttle_scoped' ) ) {
	/**
	 * The usual case: count against this caller and this subject.
	 *
	 * Both, because either alone leaves a hole. Counting only the caller
	 * lets one connection walk through a list of addresses a few at a
	 * time. Counting only the subject lets one address be hammered from
	 * anywhere, and also lets somebody lock a person out of their own
	 * password reset by burning their allowance for them.
	 *
	 * @since 1.3.0
	 * @param string $scope   What is being counted.
	 * @param string $subject The thing acted on, such as an email address.
	 * @param int    $limit   Per caller allowance in the window.
	 * @param int    $window  Window in seconds.
	 * @param int    $subject_limit Per subject allowance. Defaults to the same.
	 * @return bool
	 */
	function kaamase_throttle_scoped( $scope, $subject, $limit, $window, $subject_limit = 0 ) {

		$caller_ok = kaamase_throttle_ok( kaamase_client_key( $scope ), $limit, $window );

		$subject = trim( strtolower( (string) $subject ) );

		if ( '' === $subject ) {
			return $caller_ok;
		}

		$subject_ok = kaamase_throttle_ok(
			'ka_thr_' . sanitize_key( $scope ) . '_s_' . md5( $subject ),
			$subject_limit > 0 ? $subject_limit : $limit,
			$window
		);

		return $caller_ok && $subject_ok;
	}
}

if ( ! function_exists( 'kaamase_throttle_error' ) ) {
	/**
	 * The same answer every time somebody is over a limit.
	 *
	 * Deliberately says nothing about which limit or how long is left.
	 * A message that names the window is a message that tells somebody
	 * exactly when to come back and try again.
	 *
	 * @since 1.3.0
	 * @return WP_Error
	 */
	function kaamase_throttle_error() {

		return new WP_Error(
			'kaamase_rate_limited',
			__( 'Too many attempts from this connection. Please wait a while and try again.', 'kaamase-core' ),
			array( 'status' => 429 )
		);
	}
}