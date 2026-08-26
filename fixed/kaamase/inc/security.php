<?php
/**
 * Security and privacy.
 *
 * This site will hold the names, photographs, villages and phone numbers
 * of people who have no lawyer, no insurance and no way to recover if
 * that data leaks. A breach here is not an embarrassment, it is a real
 * problem for real people, some of whom are women working alone in other
 * people's homes.
 *
 * Two things this file is not:
 *
 *   1. Complete. A theme cannot secure a server. Several of the most
 *      important protections belong in wp-config.php and in the host
 *      configuration, and are listed at the bottom of this file.
 *   2. A substitute for a security plugin. It closes the holes WordPress
 *      leaves open by default, which is a different job.
 *
 * @package Kaamase
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. ADMIN HARDENING
   ========================================================================== */

/**
 * Block the plugin and theme file editors.
 *
 * If an administrator account is ever compromised, the built in editors
 * turn that into arbitrary code execution on the server in two clicks.
 * There is no reason to edit PHP from a browser on a live site.
 *
 * This belongs in wp-config.php. It is defined here as a fallback for
 * when it has not been set there yet.
 */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * Turn off application passwords.
 *
 * They exist so external applications can authenticate against the REST
 * API. Nothing here needs that, and every unused authentication path is
 * an attack surface that has to be watched.
 *
 * @since 1.0.0
 * @return bool
 */
add_filter( 'wp_is_application_passwords_available', '__return_false' );


/* ==========================================================================
   2. XML-RPC AND PINGBACKS

   XML-RPC allows unlimited login attempts in a single request, which is
   why it is the preferred target for credential stuffing against
   WordPress. The pingback method is also routinely abused to bounce
   denial of service traffic off innocent sites.

   Nothing on this platform uses either.
   ========================================================================== */

add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Remove the X-Pingback response header.
 *
 * Leaving it advertises the endpoint to automated scanners even after
 * the functionality is disabled.
 *
 * @since 1.0.0
 * @param array $headers Response headers.
 * @return array Filtered headers.
 */
function kaamase_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );

	return $headers;
}
add_filter( 'wp_headers', 'kaamase_remove_pingback_header' );

/**
 * Strip XML-RPC methods that remain callable.
 *
 * @since 1.0.0
 * @param array $methods Registered XML-RPC methods.
 * @return array Empty array.
 */
function kaamase_kill_xmlrpc_methods( $methods ) {
	unset( $methods );

	return array();
}
add_filter( 'xmlrpc_methods', 'kaamase_kill_xmlrpc_methods' );


/* ==========================================================================
   3. USER ENUMERATION

   By default WordPress will happily tell an anonymous visitor every
   username on the site through three separate routes. On a normal blog
   that is careless. Here it means somebody can harvest a list of every
   worker registered on the platform.
   ========================================================================== */

/**
 * Whether this account is staff, for the purposes of listing users.
 *
 * Why this is a capability and not is_user_logged_in()
 * ---------------------------------------------------
 * All three defences below used to be switched off for anybody who was
 * signed in. Registration on this platform is free and open, so that
 * protected the list from a stranger and from nobody else: ninety
 * seconds of making an account bought you every username on the site.
 *
 * That is the exact attack the core plugin argues against when it
 * refuses to show private fields to signed in users, in its own words
 * how a scraper gets the whole list with one free account. The field
 * level rule held. This one did not.
 *
 * Three capabilities rather than one, because the people who genuinely
 * need to look users up hold different ones. Administrators have
 * list_users. Editors moderating content have edit_others_posts. Field
 * agents registering workers in person have
 * edit_others_kaamase_workers. Workers and employers hold none of them,
 * which is the whole point.
 *
 * list_users alone would have been tidier and would also have broken
 * the author dropdown in the block editor for editors, who do not have
 * it.
 *
 * @since 1.2.1
 * @return bool
 */
function kaamase_may_list_users() {

	$allowed = current_user_can( 'list_users' )
		|| current_user_can( 'edit_others_posts' )
		|| current_user_can( 'edit_others_kaamase_workers' );

	/**
	 * Filter who may enumerate users.
	 *
	 * @since 1.2.1
	 * @param bool $allowed Whether the current user may list users.
	 */
	return (bool) apply_filters( 'kaamase_may_list_users', $allowed );
}

/**
 * Block the ?author=1 redirect trick.
 *
 * Requesting an author ID redirects to that user's archive, which
 * exposes their login slug. Walking the IDs from 1 upward produces the
 * full user list in a few seconds.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_block_author_scan() {
	if ( is_admin() || kaamase_may_list_users() ) {
		return;
	}

	if ( isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( home_url(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_block_author_scan' );

/**
 * Disable author archives entirely.
 *
 * Worker profiles are their own post type handled by the core plugin.
 * The WordPress author archive serves no purpose here and only leaks
 * account information.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_disable_author_archive() {
	if ( is_author() && ! kaamase_may_list_users() ) {
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'kaamase_disable_author_archive', 5 );

/**
 * Close the REST users endpoint to anyone who is not staff.
 *
 * /wp-json/wp/v2/users returns the full user list without authentication
 * on a default install, and WordPress allows the collection in view
 * context to anybody. The endpoint stays available to administrators,
 * editors and field agents, so the admin screens and the core plugin
 * are unaffected.
 *
 * @since 1.0.0
 * @param WP_Error|null|true $result Existing authentication result.
 * @return WP_Error|null|true
 */
function kaamase_restrict_rest_users( $result ) {

	if ( ! empty( $result ) ) {
		return $result;
	}

	if ( kaamase_may_list_users() ) {
		return $result;
	}

	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] )
		? (string) $GLOBALS['wp']->query_vars['rest_route']
		: '';

	if ( '' === $route ) {
		$route = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
	}

	if ( false !== strpos( $route, '/wp/v2/users' ) ) {
		return new WP_Error(
			'kaamase_rest_forbidden',
			__( 'You are not allowed to view this.', 'kaamase' ),
			/*
			 * 401 means we do not know who you are, 403 means we do and
			 * the answer is still no. Signed in accounts reach this now,
			 * so the two cases have to be told apart or every refusal
			 * looks like a broken token.
			 */
			array( 'status' => is_user_logged_in() ? 403 : 401 )
		);
	}

	return $result;
}
add_filter( 'rest_authentication_errors', 'kaamase_restrict_rest_users' );

/**
 * Stop the oEmbed response revealing the author name.
 *
 * @since 1.0.0
 * @param array $data Prepared oEmbed data.
 * @return array Filtered data.
 */
function kaamase_scrub_oembed_author( $data ) {
	unset( $data['author_name'], $data['author_url'] );

	return $data;
}
add_filter( 'oembed_response_data', 'kaamase_scrub_oembed_author' );


/* ==========================================================================
   4. LOGIN
   ========================================================================== */

/**
 * Return one generic message for every login failure.
 *
 * WordPress by default says whether the username exists and whether the
 * password was wrong. That confirms valid accounts to anyone guessing,
 * which turns a hard attack into an easy one.
 *
 * @since 1.0.0
 * @return string
 */
function kaamase_generic_login_error() {
	return __( 'Those details are not correct. Please check and try again.', 'kaamase' );
}
add_filter( 'login_errors', 'kaamase_generic_login_error' );

/**
 * Do not tell a stranger whether an email address is registered.
 *
 * The password reset form otherwise confirms which addresses have
 * accounts. The user still receives their email if the address exists.
 *
 * @since 1.0.0
 * @param WP_Error $errors Errors from the reset request.
 * @return WP_Error
 */
function kaamase_quiet_reset_errors( $errors ) {

	if ( ! is_wp_error( $errors ) ) {
		return $errors;
	}

	foreach ( array( 'invalid_email', 'invalidcombo', 'invalid_username' ) as $code ) {
		if ( $errors->get_error_message( $code ) ) {
			$errors->remove( $code );
		}
	}

	return $errors;
}
add_filter( 'lostpassword_errors', 'kaamase_quiet_reset_errors' );


/* ==========================================================================
   5. RESPONSE HEADERS

   Cheap, effective, and applied to every request.
   ========================================================================== */

/**
 * Send security and privacy headers.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_security_headers() {

	if ( headers_sent() ) {
		return;
	}

	// Stop browsers guessing a file type and running an upload as script.
	header( 'X-Content-Type-Options: nosniff' );

	/*
	 * Do not leak the full URL to third parties. Without this, opening an
	 * outbound link from a worker profile page sends that profile URL to
	 * the destination site in the referrer.
	 */
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );

	// No framing. Blocks clickjacking of the login and dashboard screens.
	header( 'X-Frame-Options: SAMEORIGIN' );

	/*
	 * Switch off browser features this site never uses. Location is the
	 * one that matters. Nothing here should ever be able to ask a worker
	 * for their live position.
	 */
	header(
		'Permissions-Policy: geolocation=(), microphone=(), camera=(self), payment=(), usb=(), magnetometer=(), gyroscope=(), interest-cohort=()'
	);

	// Only meaningful over HTTPS, and only safe once HTTPS is confirmed working.
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=15552000; includeSubDomains' );
	}
}
add_action( 'send_headers', 'kaamase_security_headers' );

/**
 * Never let a signed in page be cached by a proxy or a CDN.
 *
 * A dashboard contains one person's data. If a shared cache stores it,
 * the next visitor through that cache can be served somebody else's
 * profile. This is the single most damaging caching mistake a site like
 * this can make, and it is easy to make by accident when a caching
 * plugin gets installed later.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_no_cache_when_signed_in() {

	if ( ! is_user_logged_in() || headers_sent() ) {
		return;
	}

	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
}
add_action( 'send_headers', 'kaamase_no_cache_when_signed_in', 20 );


/* ==========================================================================
   6. UPLOADS

   Workers will upload photographs of themselves and of past work. That
   is the only thing they should be able to upload.
   ========================================================================== */

/**
 * Restrict what non administrators may upload.
 *
 * SVG is excluded deliberately. It is a text format that can carry
 * JavaScript, so an SVG upload is a stored cross site scripting payload
 * waiting for an administrator to open it.
 *
 * @since 1.0.0
 * @param array $mimes Allowed mime types.
 * @return array Filtered mime types.
 */
function kaamase_limit_upload_types( $mimes ) {

	if ( current_user_can( 'manage_options' ) ) {
		unset( $mimes['svg'], $mimes['svgz'] );

		return $mimes;
	}

	return array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
	);
}
add_filter( 'upload_mimes', 'kaamase_limit_upload_types' );

/**
 * Verify the real file type rather than trusting the extension.
 *
 * A file named photo.jpg containing PHP is still PHP. This checks the
 * actual contents and rejects anything that does not match.
 *
 * @since 1.0.0
 * @param array  $data     File data with ext, type and proper_filename.
 * @param string $file     Full path to the file.
 * @param string $filename The name of the file.
 * @param array  $mimes    Allowed mime types.
 * @return array Filtered data.
 */
function kaamase_verify_real_filetype( $data, $file, $filename, $mimes ) {
	unset( $mimes );

	if ( current_user_can( 'manage_options' ) ) {
		return $data;
	}

	$check = wp_check_filetype( $filename, kaamase_limit_upload_types( array() ) );

	if ( empty( $check['type'] ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	// Confirm the bytes on disk agree with the claimed type.
	$image = @getimagesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

	if ( false === $image ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'kaamase_verify_real_filetype', 10, 4 );

/**
 * Uploaded types that can carry camera metadata.
 *
 * The old list was JPEG and TIFF only, on the stated grounds that PNG
 * and WebP as written by phones do not carry EXIF. That is not correct.
 * WebP carries a standard EXIF chunk including GPS, PNG has supported
 * eXIf since 1.5, and current Android and iOS both produce them. WebP
 * is explicitly on the allowed upload list in this same file, so a GPS
 * tagged WebP went straight through untouched and the promise on the
 * theme readme was not being kept.
 *
 * @since 1.2.1
 * @return string[] Mime types.
 */
function kaamase_metadata_bearing_types() {

	/**
	 * Filter which uploaded types are re-encoded to remove metadata.
	 *
	 * @since 1.2.1
	 * @param string[] $types Mime types.
	 */
	return (array) apply_filters(
		'kaamase_metadata_bearing_types',
		array( 'image/jpeg', 'image/tiff', 'image/webp', 'image/png' )
	);
}

/**
 * Refuse an upload whose metadata could not be removed.
 *
 * The file is deleted, not merely rejected.
 *
 * wp_handle_upload runs after the file has already been moved into the
 * uploads folder, so returning an error on its own would leave the
 * original sitting on disk at a guessable address with its coordinates
 * intact. That is worse than the fault being fixed, not better.
 *
 * @since 1.2.1
 * @param array $upload Upload data.
 * @return array Upload data, or an error array when refusing.
 */
function kaamase_refuse_upload( $upload ) {

	/**
	 * Filter whether an upload is refused when metadata cannot be stripped.
	 *
	 * Set to false only if a host cannot process a format you have
	 * decided to keep accepting anyway. Doing so means location data
	 * from that format reaches the public uploads folder.
	 *
	 * @since 1.2.1
	 * @param bool  $require Whether to refuse the upload.
	 * @param array $upload  Upload data.
	 */
	if ( ! apply_filters( 'kaamase_require_metadata_strip', true, $upload ) ) {
		return $upload;
	}

	if ( ! empty( $upload['file'] ) && file_exists( $upload['file'] ) ) {
		wp_delete_file( $upload['file'] );
	}

	return array(
		'error' => __( 'This photo could not be processed, so it has not been saved. Please try a different photo, or save it as a JPEG and upload it again.', 'kaamase' ),
	);
}

/**
 * Strip camera metadata, including GPS coordinates, from uploads.
 *
 * This is the most important function in this file.
 *
 * A photograph taken on a phone carries the exact latitude and longitude
 * where it was taken, embedded in the file. A worker uploading a selfie
 * taken at home is publishing their home address to anyone who downloads
 * the image and opens its properties. For a woman registering for
 * domestic work, that is a safety issue, not a privacy preference.
 *
 * WordPress strips metadata from the resized copies it generates, but
 * the original uploaded file keeps everything. This re-encodes the
 * original so the coordinates never reach the server's public folder.
 *
 * Rotation is applied first, because orientation is also stored in the
 * metadata being removed. Without that step, half the photos on the site
 * would appear sideways.
 *
 * @since 1.0.0
 * @param array $upload Upload data with file, url and type.
 * @return array Upload array, or an error when the metadata could not be removed.
 */
function kaamase_strip_image_metadata( $upload ) {

	if ( empty( $upload['type'] ) || empty( $upload['file'] ) ) {
		return $upload;
	}

	if ( ! in_array( $upload['type'], kaamase_metadata_bearing_types(), true ) ) {
		return $upload;
	}

	$editor = wp_get_image_editor( $upload['file'] );

	/*
	 * Refused rather than waved through.
	 *
	 * If the server has no editor for this format we cannot remove the
	 * coordinates, and the old behaviour was to store the file anyway.
	 * That is the quiet failure this function exists to prevent: a
	 * worker is told her location was removed, and it was not. Better a
	 * refused upload she can retry than a home address published
	 * without her knowing.
	 */
	if ( is_wp_error( $editor ) ) {
		return kaamase_refuse_upload( $upload );
	}

	// Apply the orientation flag before discarding it. JPEG only, and harmless elsewhere.
	if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
		$editor->maybe_exif_rotate();
	}

	/*
	 * Imagick preserves metadata unless told otherwise. GD drops it on
	 * save. Setting this filter covers the Imagick path.
	 */
	add_filter( 'image_strip_meta', '__return_true' );

	/*
	 * 90, not 74.
	 *
	 * This is the master copy. WordPress generates every thumbnail from
	 * it afterwards at the jpeg_quality set in setup.php, which is 74,
	 * so saving here at 74 compressed the same photograph twice and the
	 * loss compounded. Now that PNG and WebP go through this path too,
	 * that would have applied to far more uploads.
	 */
	$editor->set_quality( 90 );
	$saved = $editor->save( $upload['file'], $upload['type'] );

	remove_filter( 'image_strip_meta', '__return_true' );

	if ( is_wp_error( $saved ) ) {
		return kaamase_refuse_upload( $upload );
	}

	return $upload;
}
add_filter( 'wp_handle_upload', 'kaamase_strip_image_metadata' );

/**
 * Clean up uploaded file names.
 *
 * Phone cameras produce names like IMG_20260731_143022.jpg, which is
 * harmless, but a user typed name can contain anything. This keeps the
 * filesystem predictable and stops a name being used to smuggle
 * characters into a path.
 *
 * @since 1.0.0
 * @param string $filename Proposed file name.
 * @return string Sanitised name.
 */
function kaamase_clean_filename( $filename ) {

	$info = pathinfo( $filename );
	$ext  = isset( $info['extension'] ) ? '.' . strtolower( $info['extension'] ) : '';
	$name = isset( $info['filename'] ) ? $info['filename'] : $filename;

	$name = remove_accents( $name );
	$name = strtolower( $name );
	$name = preg_replace( '/[^a-z0-9]+/', '-', $name );
	$name = trim( (string) $name, '-' );

	if ( '' === $name ) {
		$name = 'upload';
	}

	// Long names break on some filesystems and in some backup tools.
	$name = substr( $name, 0, 60 );

	return $name . $ext;
}
add_filter( 'sanitize_file_name', 'kaamase_clean_filename', 20 );


/* ==========================================================================
   7. OUTPUT SCRUBBING
   ========================================================================== */

/**
 * Remove the WordPress version from asset URLs.
 *
 * Minor, but it stops an automated scanner reading the exact version
 * from a stylesheet query string and matching it against a list of
 * known vulnerabilities.
 *
 * @since 1.0.0
 * @param string $src Asset URL.
 * @return string Filtered URL.
 */
function kaamase_strip_version_query( $src ) {

	if ( ! is_string( $src ) || false === strpos( $src, 'ver=' ) ) {
		return $src;
	}

	$version = get_bloginfo( 'version' );

	if ( false === strpos( $src, 'ver=' . $version ) ) {
		return $src;
	}

	return remove_query_arg( 'ver', $src );
}
add_filter( 'style_loader_src', 'kaamase_strip_version_query', 9999 );
add_filter( 'script_loader_src', 'kaamase_strip_version_query', 9999 );


/* ==========================================================================
   8. WHAT THIS FILE CANNOT DO

   The following are not achievable from a theme and must be handled in
   wp-config.php or in the host configuration. Treat this list as
   outstanding work, not as advice.

   wp-config.php
     define( 'DISALLOW_FILE_MODS', true );   Blocks plugin and theme
                                             installs from the dashboard.
     define( 'FORCE_SSL_ADMIN', true );      Admin over HTTPS only.
     define( 'WP_DEBUG_DISPLAY', false );    Never print errors to a visitor.
     Fresh salts from the WordPress secret key generator.
     A database prefix that is not wp_.

   Server
     PHP execution blocked inside wp-content/uploads. Without this, any
     upload hole becomes remote code execution. This is the single most
     important item on the list.
     Directory listing switched off.
     Rate limiting or a lockout plugin on wp-login.php. WordPress has no
     built in limit on login attempts.
     Automatic off site backups, tested by actually restoring one.

   Policy
     A published privacy notice covering what is collected, why, and for
     how long. Under the DPDP Act this is not optional once real user
     data is being held.
     A retention rule that deletes inactive worker accounts and their
     photographs after a defined period.
   ========================================================================== */
