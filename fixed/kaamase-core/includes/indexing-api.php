<?php
/**
 * Telling Google about a job the moment it is posted.
 *
 * What this is
 * ------------
 * Google's Indexing API, which exists for exactly two kinds of page in
 * the world: JobPosting and BroadcastEvent. That is not a detail, it is
 * the reason this file is worth having. Almost every site that wants
 * faster indexing cannot use it. A job board can.
 *
 * Without it a new job waits for a crawler, which on a small new site
 * can be days or weeks. A labour job posted on Monday for work on
 * Wednesday is worthless by the time it is found. With it, the URL is
 * handed to Google within minutes of being published.
 *
 * What it is not
 * --------------
 * It is not a ranking trick and it does not force anything into the
 * jobs box. It says "this page changed, come and look". Whether Google
 * then shows it is Google's decision, made on the structured data in
 * schema.php and everything else it weighs.
 *
 * Using it for pages that are not job postings is against Google's
 * terms and is enforced when quota is requested, so this file will only
 * ever send a URL whose post type is kaamase_job. There is no filter to
 * widen that, deliberately.
 *
 * Why nothing here happens during somebody's request
 * --------------------------------------------------
 * Posting a job must not wait on two HTTPS round trips to Google. If
 * Google is slow, the employer watches a spinner; if Google is down,
 * the post fails for a reason that has nothing to do with posting. So
 * publishing writes a line into a queue and returns, and the queue is
 * emptied on a schedule. A job that fails to send stays in the queue and
 * is tried again.
 *
 * The private key
 * ---------------
 * The service account JSON holds an RSA private key. It is stored with
 * autoload off, for the same reason the Razorpay keys are: an autoloaded
 * option is read into memory on every single request on the site,
 * including the ones that have nothing to do with it. It is never
 * printed back to the screen, and the settings form shows only which
 * account is connected.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.9.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KAAMASE_INDEXING_OPTION' ) ) {
	/** Where the service account and settings live. */
	define( 'KAAMASE_INDEXING_OPTION', 'kaamase_indexing' );
}

if ( ! defined( 'KAAMASE_INDEXING_SCOPE' ) ) {
	/** The one scope this asks for. */
	define( 'KAAMASE_INDEXING_SCOPE', 'https://www.googleapis.com/auth/indexing' );
}

if ( ! defined( 'KAAMASE_INDEXING_PUBLISH_URL' ) ) {
	/**
	 * The publish endpoint.
	 *
	 * The colon before publish is not a typo and is not interchangeable
	 * with a slash. Google's custom-method URLs are written this way, and
	 * the slash spelling returns a 404 that reads like a routing problem
	 * on your own site.
	 */
	define( 'KAAMASE_INDEXING_PUBLISH_URL', 'https://indexing.googleapis.com/v3/urlNotifications:publish' );
}

if ( ! defined( 'KAAMASE_INDEXING_META_URL' ) ) {
	/** Reading back what Google last heard from us about a URL. */
	define( 'KAAMASE_INDEXING_META_URL', 'https://indexing.googleapis.com/v3/urlNotifications/metadata' );
}

if ( ! defined( 'KAAMASE_INDEXING_TOKEN_URL' ) ) {
	/** Where the signed assertion is exchanged for an access token. */
	define( 'KAAMASE_INDEXING_TOKEN_URL', 'https://oauth2.googleapis.com/token' );
}

if ( ! defined( 'KAAMASE_INDEXING_DAILY' ) ) {
	/**
	 * Google's default allowance is two hundred publishes a day.
	 *
	 * Counted here as well as there, because going over returns an error
	 * for every remaining call of the day rather than queueing, and a
	 * queue that empties itself into a wall is worse than one that waits.
	 */
	define( 'KAAMASE_INDEXING_DAILY', 200 );
}


/* ==========================================================================
   1. WHAT IS SET UP
   ========================================================================== */

if ( ! function_exists( 'kaamase_indexing_settings' ) ) {
	/**
	 * Everything stored for this feature.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	function kaamase_indexing_settings() {

		$stored = get_option( KAAMASE_INDEXING_OPTION, array() );

		return wp_parse_args(
			is_array( $stored ) ? $stored : array(),
			array(
				'on'          => 0,
				'credentials' => '',
			)
		);
	}
}

if ( ! function_exists( 'kaamase_indexing_save' ) ) {
	/**
	 * Write the settings back, kept out of the autoloaded set.
	 *
	 * @since 1.9.0
	 * @param array $settings Settings.
	 * @return void
	 */
	function kaamase_indexing_save( $settings ) {

		/*
		 * The third argument is the point of this function existing.
		 * update_option() rewrites the autoload flag on an option that is
		 * already there, so passing false here is what keeps a private key
		 * out of the query that runs on every request on the site.
		 */
		update_option( KAAMASE_INDEXING_OPTION, $settings, false );
	}
}

if ( ! function_exists( 'kaamase_indexing_credentials' ) ) {
	/**
	 * The service account, decoded and checked.
	 *
	 * @since 1.9.0
	 * @return array|WP_Error
	 */
	function kaamase_indexing_credentials() {

		$settings = kaamase_indexing_settings();
		$raw      = (string) $settings['credentials'];

		if ( '' === trim( $raw ) ) {
			return new WP_Error(
				'kaamase_indexing_none',
				__( 'No Google service account has been added yet.', 'kaamase-core' )
			);
		}

		$creds = json_decode( $raw, true );

		if ( ! is_array( $creds ) ) {
			return new WP_Error(
				'kaamase_indexing_unreadable',
				__( 'That does not look like the JSON key file Google gives you.', 'kaamase-core' )
			);
		}

		foreach ( array( 'client_email', 'private_key' ) as $needed ) {
			if ( empty( $creds[ $needed ] ) ) {
				return new WP_Error(
					'kaamase_indexing_incomplete',
					sprintf(
						/* translators: %s: the name of a missing field */
						__( 'The key file has no %s in it. Download it again from Google Cloud.', 'kaamase-core' ),
						$needed
					)
				);
			}
		}

		return $creds;
	}
}

if ( ! function_exists( 'kaamase_indexing_is_on' ) ) {
	/**
	 * Whether to send anything at all.
	 *
	 * @since 1.9.0
	 * @return bool
	 */
	function kaamase_indexing_is_on() {

		$settings = kaamase_indexing_settings();

		if ( empty( $settings['on'] ) ) {
			return false;
		}

		return ! is_wp_error( kaamase_indexing_credentials() );
	}
}


/* ==========================================================================
   2. TALKING TO GOOGLE
   ========================================================================== */

if ( ! function_exists( 'kaamase_indexing_b64' ) ) {
	/**
	 * base64url, which is base64 with three differences and no padding.
	 *
	 * @since 1.9.0
	 * @param string $data Raw bytes.
	 * @return string
	 */
	function kaamase_indexing_b64( $data ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return rtrim( strtr( base64_encode( (string) $data ), '+/', '-_' ), '=' );
	}
}

if ( ! function_exists( 'kaamase_indexing_assertion' ) ) {
	/**
	 * The signed JWT that stands in for a password.
	 *
	 * Google's server-to-server flow: a JWT signed with the service
	 * account's private key is posted to their token endpoint and comes
	 * back as an access token. The claim set is exact — aud must be the
	 * token endpoint itself, not the API being called, and an exp more
	 * than an hour after iat is refused.
	 *
	 * @since 1.9.0
	 * @param array $creds Decoded service account.
	 * @return string|WP_Error
	 */
	function kaamase_indexing_assertion( $creds ) {

		if ( ! function_exists( 'openssl_sign' ) ) {
			return new WP_Error(
				'kaamase_indexing_no_openssl',
				__( 'This server cannot sign the request, because OpenSSL is not available in PHP.', 'kaamase-core' )
			);
		}

		$now = time();

		$header = array(
			'alg' => 'RS256',
			'typ' => 'JWT',
		);

		$claims = array(
			'iss'   => (string) $creds['client_email'],
			'scope' => KAAMASE_INDEXING_SCOPE,
			'aud'   => KAAMASE_INDEXING_TOKEN_URL,
			'exp'   => $now + HOUR_IN_SECONDS,
			'iat'   => $now,
		);

		$input = kaamase_indexing_b64( wp_json_encode( $header ) )
			. '.' . kaamase_indexing_b64( wp_json_encode( $claims ) );

		$signature = '';

		$signed = openssl_sign( $input, $signature, (string) $creds['private_key'], OPENSSL_ALGO_SHA256 );

		if ( ! $signed ) {
			return new WP_Error(
				'kaamase_indexing_sign_failed',
				__( 'The private key in that file could not be used to sign. It may be damaged, or pasted with something missing.', 'kaamase-core' )
			);
		}

		return $input . '.' . kaamase_indexing_b64( $signature );
	}
}

if ( ! function_exists( 'kaamase_indexing_token' ) ) {
	/**
	 * An access token, held until shortly before it expires.
	 *
	 * @since 1.9.0
	 * @param bool $fresh Skip the cache.
	 * @return string|WP_Error
	 */
	function kaamase_indexing_token( $fresh = false ) {

		$cached = get_transient( 'kaamase_indexing_token' );

		if ( ! $fresh && is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$creds = kaamase_indexing_credentials();

		if ( is_wp_error( $creds ) ) {
			return $creds;
		}

		$assertion = kaamase_indexing_assertion( $creds );

		if ( is_wp_error( $assertion ) ) {
			return $assertion;
		}

		$response = wp_remote_post(
			KAAMASE_INDEXING_TOKEN_URL,
			array(
				'timeout' => 15,
				'body'    => array(
					'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
					'assertion'  => $assertion,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $body ) || empty( $body['access_token'] ) ) {

			/*
			 * Google's own wording is passed through. "invalid_grant"
			 * almost always means the server clock is wrong or the key was
			 * revoked, and saying so beats a generic failure.
			 */
			$said = is_array( $body ) && ! empty( $body['error_description'] )
				? (string) $body['error_description']
				: (string) wp_remote_retrieve_body( $response );

			return new WP_Error(
				'kaamase_indexing_token_refused',
				sprintf(
					/* translators: 1: HTTP status, 2: what Google said */
					__( 'Google refused the sign in (%1$d). %2$s', 'kaamase-core' ),
					$code,
					$said
				)
			);
		}

		$life = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;

		// Five minutes short, so a token is never used in its last moments.
		set_transient( 'kaamase_indexing_token', (string) $body['access_token'], max( 60, $life - 300 ) );

		return (string) $body['access_token'];
	}
}

if ( ! function_exists( 'kaamase_indexing_publish' ) ) {
	/**
	 * Tell Google one URL changed or went away.
	 *
	 * @since 1.9.0
	 * @param string $url  The job URL.
	 * @param string $type URL_UPDATED or URL_DELETED.
	 * @return true|WP_Error
	 */
	function kaamase_indexing_publish( $url, $type ) {

		$type = in_array( $type, array( 'URL_UPDATED', 'URL_DELETED' ), true ) ? $type : 'URL_UPDATED';

		$token = kaamase_indexing_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_remote_post(
			KAAMASE_INDEXING_PUBLISH_URL,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'url'  => $url,
						'type' => $type,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 === $code ) {
			kaamase_indexing_count();
			return true;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$said = isset( $body['error']['message'] )
			? (string) $body['error']['message']
			: (string) wp_remote_retrieve_body( $response );

		/*
		 * A stale token is the one failure worth retrying immediately,
		 * and it is cheap to rule out: drop the cached token so the next
		 * attempt fetches a new one rather than failing the same way.
		 */
		if ( 401 === $code ) {
			delete_transient( 'kaamase_indexing_token' );
		}

		return new WP_Error(
			'kaamase_indexing_refused',
			sprintf(
				/* translators: 1: HTTP status, 2: what Google said */
				__( 'Google refused it (%1$d). %2$s', 'kaamase-core' ),
				$code,
				$said
			)
		);
	}
}

if ( ! function_exists( 'kaamase_indexing_metadata' ) ) {
	/**
	 * Ask Google what it last heard from us about a URL.
	 *
	 * The proof, rather than the promise. A publish that returns 200 says
	 * the message was accepted; this says Google has it on record, and is
	 * what makes the test on the settings screen worth anything.
	 *
	 * @since 1.9.0
	 * @param string $url The URL.
	 * @return array|WP_Error
	 */
	function kaamase_indexing_metadata( $url ) {

		$token = kaamase_indexing_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_remote_get(
			add_query_arg( 'url', rawurlencode( $url ), KAAMASE_INDEXING_META_URL ),
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $body ) ) {
			$said = isset( $body['error']['message'] )
				? (string) $body['error']['message']
				: (string) wp_remote_retrieve_body( $response );

			return new WP_Error(
				'kaamase_indexing_no_metadata',
				sprintf(
					/* translators: 1: HTTP status, 2: what Google said */
					__( 'Google had nothing to report (%1$d). %2$s', 'kaamase-core' ),
					$code,
					$said
				)
			);
		}

		return $body;
	}
}


/* ==========================================================================
   3. THE ALLOWANCE
   ========================================================================== */

if ( ! function_exists( 'kaamase_indexing_used' ) ) {
	/**
	 * How many have gone today, in the site's own timezone.
	 *
	 * @since 1.9.0
	 * @return int
	 */
	function kaamase_indexing_used() {

		$count = get_option( 'kaamase_indexing_used', array() );
		$today = wp_date( 'Ymd' );

		if ( ! is_array( $count ) || ( $count['day'] ?? '' ) !== $today ) {
			return 0;
		}

		return (int) ( $count['n'] ?? 0 );
	}
}

if ( ! function_exists( 'kaamase_indexing_count' ) ) {
	/**
	 * Record one that went.
	 *
	 * @since 1.9.0
	 * @return void
	 */
	function kaamase_indexing_count() {

		update_option(
			'kaamase_indexing_used',
			array(
				'day' => wp_date( 'Ymd' ),
				'n'   => kaamase_indexing_used() + 1,
			),
			false
		);
	}
}

if ( ! function_exists( 'kaamase_indexing_left' ) ) {
	/**
	 * How many are left today.
	 *
	 * @since 1.9.0
	 * @return int
	 */
	function kaamase_indexing_left() {
		return max( 0, KAAMASE_INDEXING_DAILY - kaamase_indexing_used() );
	}
}


/* ==========================================================================
   4. THE QUEUE

   Nothing is sent while somebody is waiting for a page. Publishing a job
   writes a line here and returns; the schedule empties it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_indexing_queue' ) ) {
	/**
	 * What is waiting.
	 *
	 * @since 1.9.0
	 * @return array[]
	 */
	function kaamase_indexing_queue() {

		$queue = get_option( 'kaamase_indexing_queue', array() );

		return is_array( $queue ) ? $queue : array();
	}
}

if ( ! function_exists( 'kaamase_indexing_add' ) ) {
	/**
	 * Put one URL in the queue.
	 *
	 * The same URL twice is one entry, holding whichever answer is newer.
	 * A job posted and then closed within the hour should tell Google it
	 * is gone, not tell it twice that it exists and then argue.
	 *
	 * @since 1.9.0
	 * @param string $url  The URL.
	 * @param string $type URL_UPDATED or URL_DELETED.
	 * @return void
	 */
	function kaamase_indexing_add( $url, $type ) {

		$url = esc_url_raw( (string) $url );

		if ( '' === $url || ! kaamase_indexing_is_on() ) {
			return;
		}

		$queue = kaamase_indexing_queue();

		$queue[ md5( $url ) ] = array(
			'url'  => $url,
			'type' => in_array( $type, array( 'URL_UPDATED', 'URL_DELETED' ), true ) ? $type : 'URL_UPDATED',
			'at'   => time(),
		);

		/*
		 * Capped. A bulk edit that touches a thousand jobs must not write
		 * a thousand-row option that then has to be read and rewritten on
		 * every run. The oldest go; they are the ones Google is most
		 * likely to have crawled by itself already.
		 */
		if ( count( $queue ) > 500 ) {
			$queue = array_slice( $queue, -500, null, true );
		}

		update_option( 'kaamase_indexing_queue', $queue, false );
	}
}

if ( ! function_exists( 'kaamase_indexing_run' ) ) {
	/**
	 * Empty as much of the queue as the allowance permits.
	 *
	 * Stops on the first refusal rather than working through the rest.
	 * Whatever is wrong — a revoked key, a wrong clock, the daily
	 * allowance — is wrong for every entry, and burning the queue against
	 * it just loses the URLs.
	 *
	 * @since 1.9.0
	 * @param int $batch How many at most.
	 * @return int How many went.
	 */
	function kaamase_indexing_run( $batch = 20 ) {

		if ( ! kaamase_indexing_is_on() ) {
			return 0;
		}

		$queue = kaamase_indexing_queue();

		if ( empty( $queue ) ) {
			return 0;
		}

		$batch = min( (int) $batch, kaamase_indexing_left() );
		$sent  = 0;

		foreach ( $queue as $key => $entry ) {

			if ( $sent >= $batch ) {
				break;
			}

			$done = kaamase_indexing_publish( (string) $entry['url'], (string) $entry['type'] );

			if ( is_wp_error( $done ) ) {
				kaamase_indexing_log( $entry['url'], $entry['type'], $done->get_error_message() );
				break;
			}

			kaamase_indexing_log( $entry['url'], $entry['type'], 'ok' );

			unset( $queue[ $key ] );
			$sent++;
		}

		update_option( 'kaamase_indexing_queue', $queue, false );

		return $sent;
	}
}
add_action( 'kaamase_indexing_cron', 'kaamase_indexing_run' );

if ( ! function_exists( 'kaamase_indexing_log' ) ) {
	/**
	 * Keep the last thirty results, so a failure is visible.
	 *
	 * @since 1.9.0
	 * @param string $url    The URL.
	 * @param string $type   What was said about it.
	 * @param string $result ok, or what went wrong.
	 * @return void
	 */
	function kaamase_indexing_log( $url, $type, $result ) {

		$log = get_option( 'kaamase_indexing_log', array() );
		$log = is_array( $log ) ? $log : array();

		array_unshift(
			$log,
			array(
				'url'    => (string) $url,
				'type'   => (string) $type,
				'result' => (string) $result,
				'at'     => time(),
			)
		);

		update_option( 'kaamase_indexing_log', array_slice( $log, 0, 30 ), false );
	}
}


/* ==========================================================================
   5. WHAT SETS IT OFF

   One hook rather than several. transition_post_status sees every way a
   job can start or stop being public — published, closed, filled,
   expired, unpublished, binned — so nothing has to be chased separately
   and a route added later cannot be missed.
   ========================================================================== */

if ( ! function_exists( 'kaamase_indexing_on_transition' ) ) {
	/**
	 * A job changed state.
	 *
	 * @since 1.9.0
	 * @param string  $new  New status.
	 * @param string  $old  Old status.
	 * @param WP_Post $post The post.
	 * @return void
	 */
	function kaamase_indexing_on_transition( $new, $old, $post ) {

		if ( ! $post instanceof WP_Post || 'kaamase_job' !== $post->post_type ) {
			return;
		}

		if ( $new === $old ) {
			return;
		}

		if ( 'publish' === $new ) {
			kaamase_indexing_add( get_permalink( $post ), 'URL_UPDATED' );
			return;
		}

		/*
		 * Only worth saying when it was public a moment ago. A draft that
		 * moves to pending was never a page Google could have, and telling
		 * it to drop a URL it has never seen is a wasted call out of an
		 * allowance of two hundred.
		 */
		if ( 'publish' === $old ) {
			kaamase_indexing_add( get_permalink( $post ), 'URL_DELETED' );
		}
	}
}
add_action( 'transition_post_status', 'kaamase_indexing_on_transition', 10, 3 );

if ( ! function_exists( 'kaamase_indexing_on_delete' ) ) {
	/**
	 * A job is about to be deleted for good.
	 *
	 * Caught before it goes, because the permalink cannot be built
	 * afterwards.
	 *
	 * @since 1.9.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	function kaamase_indexing_on_delete( $post_id ) {

		$post = get_post( $post_id );

		if ( ! $post || 'kaamase_job' !== $post->post_type ) {
			return;
		}

		kaamase_indexing_add( get_permalink( $post ), 'URL_DELETED' );
	}
}
add_action( 'before_delete_post', 'kaamase_indexing_on_delete' );

if ( ! function_exists( 'kaamase_indexing_schedule' ) ) {
	/**
	 * Book the run.
	 *
	 * @since 1.9.0
	 * @return void
	 */
	function kaamase_indexing_schedule() {

		if ( ! wp_next_scheduled( 'kaamase_indexing_cron' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'kaamase_five_minutes', 'kaamase_indexing_cron' );
		}
	}
}
add_action( 'init', 'kaamase_indexing_schedule', 30 );

if ( ! function_exists( 'kaamase_indexing_interval' ) ) {
	/**
	 * A five minute schedule, which WordPress does not have.
	 *
	 * @since 1.9.0
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	function kaamase_indexing_interval( $schedules ) {

		$schedules['kaamase_five_minutes'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every five minutes', 'kaamase-core' ),
		);

		return $schedules;
	}
}
add_filter( 'cron_schedules', 'kaamase_indexing_interval' ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected


/* ==========================================================================
   6. THE SCREEN

   Setting it up, and proving it works.

   The test is the reason this screen exists. Everything here can be
   correct and still send nothing, because the half that decides is in a
   Google Cloud console and a Search Console property, neither of which
   this code can see. So the test does the real thing — publishes a real
   job URL and then asks Google to read back what it just heard — and
   prints both answers exactly as they came.
   ========================================================================== */

if ( ! function_exists( 'kaamase_indexing_menu' ) ) {
	/**
	 * Add the screen.
	 *
	 * @since 1.9.0
	 * @return void
	 */
	function kaamase_indexing_menu() {

		add_submenu_page(
			'kaamase',
			__( 'Google indexing', 'kaamase-core' ),
			__( 'Google indexing', 'kaamase-core' ),
			'manage_options',
			'kaamase-indexing',
			'kaamase_indexing_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_indexing_menu', 20 );

if ( ! function_exists( 'kaamase_indexing_newest_job' ) ) {
	/**
	 * The most recently published job, for testing against.
	 *
	 * A real job, because Google refuses a URL with no JobPosting on it
	 * and a test that passes on the wrong kind of page proves nothing.
	 *
	 * @since 1.9.0
	 * @return int Post ID, or 0.
	 */
	function kaamase_indexing_newest_job() {

		$found = get_posts(
			array(
				'post_type'        => 'kaamase_job',
				'post_status'      => 'publish',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		return empty( $found ) ? 0 : (int) $found[0];
	}
}

if ( ! function_exists( 'kaamase_indexing_handle' ) ) {
	/**
	 * Save the settings, or run the test.
	 *
	 * @since 1.9.0
	 * @return void
	 */
	function kaamase_indexing_handle() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot do that.', 'kaamase-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'kaamase_indexing' );

		$back = admin_url( 'admin.php?page=kaamase-indexing' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$doing = isset( $_POST['what'] ) ? sanitize_key( wp_unslash( $_POST['what'] ) ) : '';

		if ( 'save' === $doing ) {

			$settings = kaamase_indexing_settings();

			$settings['on'] = empty( $_POST['on'] ) ? 0 : 1;

			/*
			 * An empty box leaves the stored key alone. The form never
			 * prints the key back, so an empty box means "unchanged"
			 * rather than "delete it" — otherwise saving the on switch
			 * would quietly throw the credentials away.
			 */
			$pasted = isset( $_POST['credentials'] )
				? trim( (string) wp_unslash( $_POST['credentials'] ) )
				: '';

			if ( '' !== $pasted ) {
				$settings['credentials'] = $pasted;
				delete_transient( 'kaamase_indexing_token' );
			}

			if ( ! empty( $_POST['forget'] ) ) {
				$settings['credentials'] = '';
				$settings['on']          = 0;
				delete_transient( 'kaamase_indexing_token' );
			}

			kaamase_indexing_save( $settings );

			wp_safe_redirect( add_query_arg( 'saved', '1', $back ) );
			exit;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( 'test' === $doing ) {
			set_transient( 'kaamase_indexing_test', kaamase_indexing_test(), 5 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( 'tested', '1', $back ) );
			exit;
		}

		if ( 'flush' === $doing ) {
			$sent = kaamase_indexing_run( 20 );
			wp_safe_redirect( add_query_arg( 'sent', (int) $sent, $back ) );
			exit;
		}

		wp_safe_redirect( $back );
		exit;
	}
}
add_action( 'admin_post_kaamase_indexing', 'kaamase_indexing_handle' );

if ( ! function_exists( 'kaamase_indexing_test' ) ) {
	/**
	 * Do the real thing once and report exactly what happened.
	 *
	 * Four steps, each able to fail on its own, because "it did not work"
	 * is not a useful answer when there are four different reasons and
	 * three of them are settings in somebody else's console.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	function kaamase_indexing_test() {

		$out = array( 'when' => time() );

		// 1. Is there a key, and does it parse.
		$creds = kaamase_indexing_credentials();

		if ( is_wp_error( $creds ) ) {
			$out['step']   = __( 'Reading the key file', 'kaamase-core' );
			$out['error']  = $creds->get_error_message();
			return $out;
		}

		$out['account'] = (string) $creds['client_email'];

		// 2. Will Google trade it for a token.
		$token = kaamase_indexing_token( true );

		if ( is_wp_error( $token ) ) {
			$out['step']  = __( 'Signing in to Google', 'kaamase-core' );
			$out['error'] = $token->get_error_message();
			return $out;
		}

		$out['signed_in'] = true;

		// 3. Is there a real job to send.
		$job = kaamase_indexing_newest_job();

		if ( ! $job ) {
			$out['step']  = __( 'Finding a job to test with', 'kaamase-core' );
			$out['error'] = __( 'There are no published jobs on the site yet. Post one and run this again — Google refuses any URL that has no job posting on it, so there is nothing valid to send until then.', 'kaamase-core' );
			return $out;
		}

		$url         = (string) get_permalink( $job );
		$out['url']  = $url;
		$out['job']  = get_the_title( $job );

		// 4. Send it, then ask Google to read it back.
		$sent = kaamase_indexing_publish( $url, 'URL_UPDATED' );

		if ( is_wp_error( $sent ) ) {
			$out['step']  = __( 'Sending the URL', 'kaamase-core' );
			$out['error'] = $sent->get_error_message();
			return $out;
		}

		$out['sent'] = true;

		$meta = kaamase_indexing_metadata( $url );

		if ( is_wp_error( $meta ) ) {
			/*
			 * Not a failure of the send. Google accepts a publish before
			 * the record is readable, so a miss here on a first run is
			 * ordinary and is reported as such rather than as an error.
			 */
			$out['note'] = $meta->get_error_message();
			return $out;
		}

		$out['confirmed'] = isset( $meta['latestUpdate']['notifyTime'] )
			? (string) $meta['latestUpdate']['notifyTime']
			: '';

		return $out;
	}
}

if ( ! function_exists( 'kaamase_indexing_page' ) ) {
	/**
	 * Render it.
	 *
	 * @since 1.9.0
	 * @return void
	 */
	function kaamase_indexing_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot see this page.', 'kaamase-core' ), '', array( 'response' => 403 ) );
		}

		$settings = kaamase_indexing_settings();
		$creds    = kaamase_indexing_credentials();
		$ready    = ! is_wp_error( $creds );
		$queue    = kaamase_indexing_queue();
		$log      = get_option( 'kaamase_indexing_log', array() );
		$log      = is_array( $log ) ? $log : array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$tested = ! empty( $_GET['tested'] ) ? get_transient( 'kaamase_indexing_test' ) : null;
		// phpcs:enable

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Google indexing', 'kaamase-core' ); ?></h1>

			<p class="description" style="max-width:720px">
				<?php
				esc_html_e(
					'Google has an API that tells it to come and look at a page now, instead of waiting for it to be crawled. It works for exactly two kinds of page, and a job posting is one of them. This is what lets a job posted this morning be findable this afternoon rather than next week.',
					'kaamase-core'
				);
				?>
			</p>

			<?php if ( is_array( $tested ) ) : ?>
				<?php kaamase_indexing_show_test( $tested ); ?>
			<?php endif; ?>

			<h2><?php esc_html_e( 'The service account', 'kaamase-core' ); ?></h2>

			<?php if ( $ready ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: the service account email address */
						esc_html__( 'Connected as %s', 'kaamase-core' ),
						'<code>' . esc_html( (string) $creds['client_email'] ) . '</code>'
					);
					?>
				</p>
				<p class="description">
					<?php
					printf(
						/* translators: %s: the service account email address */
						esc_html__( 'That address must also be added as an owner of the site in Google Search Console. Without it Google accepts the sign in and then refuses every URL, which reads like a broken key but is not one.', 'kaamase-core' ),
						''
					);
					?>
				</p>
			<?php else : ?>
				<div class="notice notice-warning inline"><p><?php echo esc_html( $creds->get_error_message() ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'kaamase_indexing' ); ?>
				<input type="hidden" name="action" value="kaamase_indexing">
				<input type="hidden" name="what" value="save">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Send jobs to Google', 'kaamase-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="on" value="1" <?php checked( ! empty( $settings['on'] ) ); ?>>
								<?php esc_html_e( 'On', 'kaamase-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="ka-creds"><?php esc_html_e( 'Key file', 'kaamase-core' ); ?></label>
						</th>
						<td>
							<textarea id="ka-creds" name="credentials" rows="6" class="large-text code"
								placeholder="<?php esc_attr_e( 'Paste the whole JSON key file from Google Cloud here', 'kaamase-core' ); ?>"></textarea>
							<p class="description">
								<?php
								esc_html_e(
									'Left empty, whatever is already stored stays. The key is never shown again once saved, and is kept out of the options WordPress loads on every request.',
									'kaamase-core'
								);
								?>
							</p>
							<?php if ( $ready ) : ?>
								<p>
									<label>
										<input type="checkbox" name="forget" value="1">
										<?php esc_html_e( 'Forget the stored key and switch this off', 'kaamase-core' ); ?>
									</label>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save', 'kaamase-core' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Check it really works', 'kaamase-core' ); ?></h2>

			<p class="description" style="max-width:720px">
				<?php
				esc_html_e(
					'This sends your most recent published job to Google for real, then asks Google to read back what it just received. It uses one of the two hundred a day.',
					'kaamase-core'
				);
				?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'kaamase_indexing' ); ?>
				<input type="hidden" name="action" value="kaamase_indexing">
				<input type="hidden" name="what" value="test">
				<?php submit_button( __( 'Run the test', 'kaamase-core' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Today', 'kaamase-core' ); ?></h2>

			<p>
				<?php
				printf(
					/* translators: 1: how many sent today, 2: the daily allowance, 3: how many are waiting */
					esc_html__( 'Sent today: %1$s of %2$s. Waiting in the queue: %3$s.', 'kaamase-core' ),
					'<strong>' . esc_html( number_format_i18n( kaamase_indexing_used() ) ) . '</strong>',
					esc_html( number_format_i18n( KAAMASE_INDEXING_DAILY ) ),
					'<strong>' . esc_html( number_format_i18n( count( $queue ) ) ) . '</strong>'
				);
				?>
			</p>

			<?php if ( ! empty( $queue ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'kaamase_indexing' ); ?>
					<input type="hidden" name="action" value="kaamase_indexing">
					<input type="hidden" name="what" value="flush">
					<?php submit_button( __( 'Send the queue now', 'kaamase-core' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>

			<h2><?php esc_html_e( 'What happened recently', 'kaamase-core' ); ?></h2>

			<?php if ( empty( $log ) ) : ?>
				<p><em><?php esc_html_e( 'Nothing has been sent yet.', 'kaamase-core' ); ?></em></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'When', 'kaamase-core' ); ?></th>
						<th><?php esc_html_e( 'URL', 'kaamase-core' ); ?></th>
						<th><?php esc_html_e( 'Told Google', 'kaamase-core' ); ?></th>
						<th><?php esc_html_e( 'Result', 'kaamase-core' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $log as $row ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( 'j M H:i', (int) $row['at'] ) ); ?></td>
							<td><a href="<?php echo esc_url( $row['url'] ); ?>"><?php echo esc_html( $row['url'] ); ?></a></td>
							<td><?php echo esc_html( 'URL_DELETED' === $row['type'] ? __( 'it is gone', 'kaamase-core' ) : __( 'it changed', 'kaamase-core' ) ); ?></td>
							<td>
								<?php if ( 'ok' === $row['result'] ) : ?>
									<span style="color:#2a78d6;font-weight:600"><?php esc_html_e( 'Accepted', 'kaamase-core' ); ?></span>
								<?php else : ?>
									<span style="color:#eb6834"><?php echo esc_html( $row['result'] ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		</div>
		<?php
	}
}

if ( ! function_exists( 'kaamase_indexing_show_test' ) ) {
	/**
	 * Print what the test found.
	 *
	 * @since 1.9.0
	 * @param array $t The result.
	 * @return void
	 */
	function kaamase_indexing_show_test( $t ) {

		$failed = ! empty( $t['error'] );

		?>
		<div class="notice notice-<?php echo $failed ? 'error' : 'success'; ?>">
			<p><strong>
				<?php
				echo $failed
					? esc_html__( 'The test did not get through.', 'kaamase-core' )
					: esc_html__( 'It works.', 'kaamase-core' );
				?>
			</strong></p>

			<?php if ( $failed ) : ?>
				<p>
					<?php
					printf(
						/* translators: 1: which step, 2: the error */
						esc_html__( 'Stopped at: %1$s — %2$s', 'kaamase-core' ),
						'<strong>' . esc_html( (string) $t['step'] ) . '</strong>',
						esc_html( (string) $t['error'] )
					);
					?>
				</p>
			<?php else : ?>
				<ul style="list-style:disc;margin-left:20px">
					<li><?php printf( esc_html__( 'Signed in as %s', 'kaamase-core' ), '<code>' . esc_html( (string) ( $t['account'] ?? '' ) ) . '</code>' ); ?></li>
					<li><?php printf( esc_html__( 'Sent: %s', 'kaamase-core' ), '<a href="' . esc_url( (string) ( $t['url'] ?? '' ) ) . '">' . esc_html( (string) ( $t['job'] ?? '' ) ) . '</a>' ); ?></li>
					<?php if ( ! empty( $t['confirmed'] ) ) : ?>
						<li><strong><?php printf( esc_html__( 'Google confirmed it has this on record, at %s', 'kaamase-core' ), esc_html( (string) $t['confirmed'] ) ); ?></strong></li>
					<?php elseif ( ! empty( $t['note'] ) ) : ?>
						<li><?php printf( esc_html__( 'Accepted, but the read back said: %s. On a first send this is normal — it can take a moment before Google will read it back.', 'kaamase-core' ), esc_html( (string) $t['note'] ) ); ?></li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
