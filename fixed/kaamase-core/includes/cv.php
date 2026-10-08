<?php
/**
 * CVs.
 *
 * One CV per person, kept with their professional profile and sent with
 * the applications they choose to send it with. A PDF or a photo of the
 * paper one, up to five megabytes.
 *
 * Who can open one
 * ----------------
 *   - Its owner, always.
 *   - The employer of a job they applied for with it, while the job is
 *     open and for KAAMASE_CV_AFTER_CLOSE days after it closes. Not after
 *     the application is withdrawn.
 *   - The site's administrators, for a complaint.
 * Nobody else, by any route. A CV holds a date of birth, an address, a
 * family; on a platform this size it is the most private thing kept.
 *
 * Where it is kept
 * ----------------
 * Not in the media library, where every file has a public address. In
 * uploads/kaamase-private/, which carries an .htaccess refusing every
 * direct request (LiteSpeed and Apache both read it), under a random
 * 32 character name with no extension. A guess at an address finds
 * nothing, and the folder lists nothing.
 *
 * A file goes out only through this file: a link signed for one person
 * and one CV, good for KAAMASE_CV_LINK_MINUTES, checked again when it is
 * opened. The app can open such a link in a browser; it needs no header.
 *
 * check.txt in the folder is there for one test after an upload: opened
 * in a browser it must be refused. If it opens, the folder is not
 * protected and the person running the site must be told.
 *
 * What is checked on the way in
 * -----------------------------
 *   - the size, and that it really is a PDF, a JPEG, a PNG or a WebP by
 *     its contents, not by its name;
 *   - a PDF that carries a script or a file inside it is refused;
 *   - a photo is drawn again as a plain JPEG, which drops whatever rode
 *     along with it, the location it was taken at included.
 *
 * What goes
 * ---------
 * The file, when its owner deletes or replaces it, deletes their
 * professional profile or their account, or uses Erase Personal Data.
 * Every way an account closes deletes the professional profile first.
 *
 * With this file removed, applications go on without CVs and nothing
 * else changes. The files stay in the folder, unreachable, until it is
 * put back.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KAAMASE_CV_MAX' ) ) {
	define( 'KAAMASE_CV_MAX', 5 * MB_IN_BYTES );
}

if ( ! defined( 'KAAMASE_CV_LINK_MINUTES' ) ) {
	define( 'KAAMASE_CV_LINK_MINUTES', 30 );
}

if ( ! defined( 'KAAMASE_CV_AFTER_CLOSE' ) ) {
	define( 'KAAMASE_CV_AFTER_CLOSE', 30 );
}

if ( ! defined( 'KAAMASE_CV_META' ) ) {
	define( 'KAAMASE_CV_META', 'kaamase_cv' );
}


/* ==========================================================================
   1. THE FOLDER AND THE FILE
   ========================================================================== */

if ( ! function_exists( 'kaamase_cv_ready' ) ) {
	/**
	 * Whether the professional side this leans on is installed.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_cv_ready() {
		return function_exists( 'kaamase_prof_id' ) && defined( 'KAAMASE_PROF_TYPE' );
	}
}

if ( ! function_exists( 'kaamase_cv_dir' ) ) {
	/**
	 * The private folder, made and locked the first time it is needed.
	 *
	 * The lock is written every time it is asked to be made, not only
	 * once: a folder copied between servers without its dot files must
	 * not stay open.
	 *
	 * @since 1.0.0
	 * @param bool $make Make it, and its lock, if missing.
	 * @return string Path with no trailing slash, or '' when it cannot be made.
	 */
	function kaamase_cv_dir( $make = false ) {

		$uploads = wp_upload_dir( null, false );
		$dir     = untrailingslashit( (string) $uploads['basedir'] ) . '/kaamase-private';

		if ( ! $make ) {
			return $dir;
		}

		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return '';
		}

		$files = array(
			'.htaccess'  => "# Kaam Ase: CVs. Never served from here; only through the site, to the people allowed.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\nOptions -Indexes\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>\n",
			'index.php'  => "<?php\n// Silence.\n",
			'index.html' => '',
			'check.txt'  => "Kaam Ase: if you can read this in a browser, this folder is open to the internet and the CVs in it can be reached. It must not be. Tell whoever looks after the website.\n",
		);

		foreach ( $files as $name => $body ) {
			if ( ! file_exists( $dir . '/' . $name ) ) {
				file_put_contents( $dir . '/' . $name, $body ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}

		return $dir;
	}
}

if ( ! function_exists( 'kaamase_cv_make_dir_once' ) ) {
	/**
	 * Make and lock the folder as soon as this file is installed.
	 *
	 * Before anybody sends a CV, so check.txt can be tried straight after
	 * the upload rather than after the first application.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_cv_make_dir_once() {

		if ( get_option( 'kaamase_cv_dir_made' ) ) {
			return;
		}

		if ( '' !== kaamase_cv_dir( true ) ) {
			update_option( 'kaamase_cv_dir_made', time(), false );
		}
	}
}
add_action( 'init', 'kaamase_cv_make_dir_once', 40 );

if ( ! function_exists( 'kaamase_cv_of' ) ) {
	/**
	 * Somebody's CV, or null.
	 *
	 * @since 1.0.0
	 * @param int $user_id Owner.
	 * @return array|null file, name, type, size, at.
	 */
	function kaamase_cv_of( $user_id ) {

		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return null;
		}

		$cv = get_user_meta( $user_id, KAAMASE_CV_META, true );

		if ( ! is_array( $cv ) || empty( $cv['file'] ) || ! preg_match( '/^[a-f0-9]{32}$/', (string) $cv['file'] ) ) {
			return null;
		}

		if ( ! is_file( kaamase_cv_dir() . '/' . $cv['file'] ) ) {
			return null;
		}

		return array(
			'file' => (string) $cv['file'],
			'name' => (string) ( $cv['name'] ?? 'CV' ),
			'type' => 'application/pdf' === ( $cv['type'] ?? '' ) ? 'application/pdf' : 'image/jpeg',
			'size' => (int) ( $cv['size'] ?? 0 ),
			'at'   => (int) ( $cv['at'] ?? 0 ),
		);
	}
}

if ( ! function_exists( 'kaamase_cv_kind' ) ) {
	/**
	 * What an uploaded file really is, by its contents: pdf, image, or ''.
	 *
	 * @since 1.0.0
	 * @param string $path Temporary file.
	 * @return string
	 */
	function kaamase_cv_kind( $path ) {

		$head = (string) file_get_contents( $path, false, null, 0, 8 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( 0 === strpos( $head, '%PDF-' ) ) {
			return 'pdf';
		}

		$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Not an image is an answer, not an error.

		if ( is_array( $info ) && in_array( (int) $info[2], array( IMAGETYPE_JPEG, IMAGETYPE_PNG, defined( 'IMAGETYPE_WEBP' ) ? IMAGETYPE_WEBP : -1 ), true ) ) {
			return 'image';
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_cv_pdf_is_plain' ) ) {
	/**
	 * Whether a PDF carries nothing but pages: no script, no file inside,
	 * nothing that starts a program.
	 *
	 * A CV has no reason to. Refusing the few that do costs a person one
	 * export from another program; letting one through could cost an
	 * employer their computer.
	 *
	 * @since 1.0.0
	 * @param string $path File.
	 * @return bool
	 */
	function kaamase_cv_pdf_is_plain( $path ) {

		$body = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		return ! preg_match( '#/(JavaScript|JS|Launch|EmbeddedFiles?|RichMedia|XFA)\b#', $body );
	}
}

if ( ! function_exists( 'kaamase_cv_allowed' ) ) {
	/**
	 * At most ten new CVs an hour from one account.
	 *
	 * @since 1.0.0
	 * @param int $user_id Owner.
	 * @return bool
	 */
	function kaamase_cv_allowed( $user_id ) {

		if ( ! function_exists( 'kaamase_rate_bump' ) ) {
			return true;
		}

		return kaamase_rate_bump( 'cv_' . absint( $user_id ), HOUR_IN_SECONDS ) <= 10;
	}
}

if ( ! function_exists( 'kaamase_cv_save' ) ) {
	/**
	 * Keep an uploaded file as somebody's CV, in place of any before it.
	 *
	 * @since 1.0.0
	 * @param int   $user_id Owner.
	 * @param array $file    One entry of $_FILES.
	 * @return array|WP_Error The CV as kaamase_cv_of() gives it.
	 */
	function kaamase_cv_save( $user_id, $file ) {

		$user_id = (int) $user_id;

		if ( ! kaamase_cv_ready() || ! $user_id || ! kaamase_prof_id( $user_id ) ) {
			return new WP_Error( 'kaamase_cv_no_profile', __( 'Make your professional profile first. Your CV goes with it.', 'kaamase-core' ), array( 'status' => 403 ) );
		}

		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {

			$big = is_array( $file ) && in_array( (int) ( $file['error'] ?? 0 ), array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true );

			return $big
				? new WP_Error( 'kaamase_cv_too_big', __( 'That file is bigger than 5 MB. Save it smaller, or take a photo of the page instead.', 'kaamase-core' ), array( 'status' => 413 ) )
				: new WP_Error( 'kaamase_cv_missing', __( 'Choose a file to send.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		$tmp = (string) $file['tmp_name'];

		if ( ! is_uploaded_file( $tmp ) ) {
			return new WP_Error( 'kaamase_cv_missing', __( 'Choose a file to send.', 'kaamase-core' ), array( 'status' => 400 ) );
		}

		if ( filesize( $tmp ) > (int) KAAMASE_CV_MAX ) {
			return new WP_Error( 'kaamase_cv_too_big', __( 'That file is bigger than 5 MB. Save it smaller, or take a photo of the page instead.', 'kaamase-core' ), array( 'status' => 413 ) );
		}

		$kind = kaamase_cv_kind( $tmp );

		if ( '' === $kind ) {
			return new WP_Error( 'kaamase_cv_type', __( 'A CV has to be a PDF or a photo (JPG, PNG or WebP).', 'kaamase-core' ), array( 'status' => 415 ) );
		}

		if ( 'pdf' === $kind && ! kaamase_cv_pdf_is_plain( $tmp ) ) {
			return new WP_Error( 'kaamase_cv_unsafe', __( 'This PDF has a script or a file inside it, so it cannot be sent. Save it again as a plain PDF, or send a photo of it.', 'kaamase-core' ), array( 'status' => 415 ) );
		}

		if ( ! kaamase_cv_allowed( $user_id ) ) {
			return new WP_Error( 'kaamase_cv_too_often', __( 'That is a lot of CVs in one hour. Please try again later.', 'kaamase-core' ), array( 'status' => 429 ) );
		}

		$dir = kaamase_cv_dir( true );

		if ( '' === $dir ) {
			return new WP_Error( 'kaamase_cv_failed', __( 'Your CV could not be saved. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
		}

		$name = bin2hex( random_bytes( 16 ) );
		$dest = $dir . '/' . $name;

		if ( 'pdf' === $kind ) {

			if ( ! move_uploaded_file( $tmp, $dest ) ) {
				return new WP_Error( 'kaamase_cv_failed', __( 'Your CV could not be saved. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
			}

			$type = 'application/pdf';

		} else {

			// Drawn again: only the picture survives.
			$editor = wp_get_image_editor( $tmp );

			if ( is_wp_error( $editor ) ) {
				return new WP_Error( 'kaamase_cv_type', __( 'A CV has to be a PDF or a photo (JPG, PNG or WebP).', 'kaamase-core' ), array( 'status' => 415 ) );
			}

			if ( method_exists( $editor, 'maybe_exif_rotate' ) ) {
				$editor->maybe_exif_rotate();
			}

			$editor->resize( 2200, 2200, false );
			$editor->set_quality( 85 );

			$saved = $editor->save( $dest . '.jpg', 'image/jpeg' );

			if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! rename( $saved['path'], $dest ) ) {
				return new WP_Error( 'kaamase_cv_failed', __( 'Your CV could not be saved. Please try again.', 'kaamase-core' ), array( 'status' => 500 ) );
			}

			$type = 'image/jpeg';
		}

		// Shown as text only, never used as a path: their own spelling, made plain.
		$label = sanitize_text_field( wp_basename( (string) ( $file['name'] ?? '' ) ) );
		$label = '' !== $label ? $label : 'CV';
		$label = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 80 ) : substr( $label, 0, 80 );

		$old = kaamase_cv_of( $user_id );

		update_user_meta(
			$user_id,
			KAAMASE_CV_META,
			array(
				'file' => $name,
				'name' => $label,
				'type' => $type,
				'size' => (int) filesize( $dest ),
				'at'   => time(),
			)
		);

		if ( $old && $old['file'] !== $name ) {
			wp_delete_file( $dir . '/' . $old['file'] );
		}

		return kaamase_cv_of( $user_id );
	}
}

if ( ! function_exists( 'kaamase_cv_delete' ) ) {
	/**
	 * Delete somebody's CV.
	 *
	 * @since 1.0.0
	 * @param int $user_id Owner.
	 * @return bool Whether there was one.
	 */
	function kaamase_cv_delete( $user_id ) {

		$user_id = (int) $user_id;
		$raw     = $user_id ? get_user_meta( $user_id, KAAMASE_CV_META, true ) : null;

		if ( ! is_array( $raw ) ) {
			return false;
		}

		if ( ! empty( $raw['file'] ) && preg_match( '/^[a-f0-9]{32}$/', (string) $raw['file'] ) ) {
			wp_delete_file( kaamase_cv_dir() . '/' . $raw['file'] );
		}

		delete_user_meta( $user_id, KAAMASE_CV_META );

		return true;
	}
}


/* ==========================================================================
   2. WHO MAY OPEN ONE, AND THE LINK
   ========================================================================== */

if ( ! function_exists( 'kaamase_cv_window_open' ) ) {
	/**
	 * Whether the employer of a job may still open the CVs sent to it.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job.
	 * @return bool
	 */
	function kaamase_cv_window_open( $job_id ) {

		if ( ! function_exists( 'kaamase_apps_job_is_over' ) || ! kaamase_apps_job_is_over( $job_id ) ) {
			return true;
		}

		$closed = (int) get_post_meta( (int) $job_id, KAAMASE_META_PREFIX . 'apps_closed_at', true );
		$closed = $closed ? $closed : (int) get_post_modified_time( 'U', true, (int) $job_id );

		return $closed + ( (int) KAAMASE_CV_AFTER_CLOSE * DAY_IN_SECONDS ) > time();
	}
}

if ( ! function_exists( 'kaamase_cv_may_open' ) ) {
	/**
	 * Whether one person may open another's CV, by way of one application.
	 *
	 * @since 1.0.0
	 * @param int $viewer Who is asking.
	 * @param int $owner  Whose CV.
	 * @param int $app_id The application it came with, or 0 for the owner's own.
	 * @return bool
	 */
	function kaamase_cv_may_open( $viewer, $owner, $app_id = 0 ) {

		$viewer = (int) $viewer;
		$owner  = (int) $owner;

		if ( ! $viewer || ! $owner || ! kaamase_cv_of( $owner ) ) {
			return false;
		}

		if ( $viewer === $owner || user_can( $viewer, 'manage_options' ) ) {
			return true;
		}

		if ( ! $app_id || ! function_exists( 'kaamase_apps_get' ) ) {
			return false;
		}

		$app = kaamase_apps_get( $app_id );

		if ( ! $app || (int) $app->post_author !== $owner || ! get_post_meta( $app->ID, KAAMASE_META_PREFIX . 'app_with_cv', true ) ) {
			return false;
		}

		if ( 'withdrawn' === kaamase_apps_status( $app->ID ) || ! kaamase_apps_owns_job( (int) $app->post_parent, $viewer ) ) {
			return false;
		}

		return kaamase_cv_window_open( (int) $app->post_parent );
	}
}

if ( ! function_exists( 'kaamase_cv_sign' ) ) {
	/**
	 * The signature over a link's contents.
	 *
	 * @since 1.0.0
	 * @param string $body Encoded contents.
	 * @return string
	 */
	function kaamase_cv_sign( $body ) {
		return hash_hmac( 'sha256', 'kaamase-cv|' . $body, wp_salt( 'auth' ) );
	}
}

if ( ! function_exists( 'kaamase_cv_link' ) ) {
	/**
	 * A link that opens one CV for one person, for a short while.
	 *
	 * @since 1.0.0
	 * @param int $viewer Who it is for.
	 * @param int $owner  Whose CV.
	 * @param int $app_id The application, or 0 for the owner's own.
	 * @return string Empty when that person may not open it.
	 */
	function kaamase_cv_link( $viewer, $owner, $app_id = 0 ) {

		if ( ! kaamase_cv_may_open( $viewer, $owner, $app_id ) ) {
			return '';
		}

		$body = rtrim( strtr( base64_encode( wp_json_encode( array( (int) $viewer, (int) $owner, (int) $app_id, time() + ( (int) KAAMASE_CV_LINK_MINUTES * MINUTE_IN_SECONDS ) ) ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Not hiding anything, making a URL part.

		return add_query_arg( 'kaamase_cv', $body . '.' . kaamase_cv_sign( $body ), home_url( '/' ) );
	}
}

if ( ! function_exists( 'kaamase_cv_info' ) ) {
	/**
	 * A CV as either side sees it: what it is, and a link to open it.
	 *
	 * @since 1.0.0
	 * @param int $viewer Who is looking.
	 * @param int $owner  Whose CV.
	 * @param int $app_id The application, or 0 for the owner's own.
	 * @return array|null name, type, size, uploaded_at, url, expires_at.
	 */
	function kaamase_cv_info( $viewer, $owner, $app_id = 0 ) {

		$cv  = kaamase_cv_of( $owner );
		$url = $cv ? kaamase_cv_link( $viewer, $owner, $app_id ) : '';

		if ( ! $cv || '' === $url ) {
			return null;
		}

		return array(
			'name'        => $cv['name'],
			'type'        => 'application/pdf' === $cv['type'] ? 'pdf' : 'image',
			'size'        => $cv['size'],
			'uploaded_at' => $cv['at'],
			'url'         => $url,
			'expires_at'  => time() + ( (int) KAAMASE_CV_LINK_MINUTES * MINUTE_IN_SECONDS ),
		);
	}
}

if ( ! function_exists( 'kaamase_cv_for_app' ) ) {
	/**
	 * The CV that came with an application, for whoever is looking now.
	 *
	 * @since 1.0.0
	 * @param int $app_id Application.
	 * @param int $viewer Who is looking.
	 * @return array|null
	 */
	function kaamase_cv_for_app( $app_id, $viewer ) {

		$owner = (int) get_post_field( 'post_author', (int) $app_id );

		return $owner ? kaamase_cv_info( $viewer, $owner, $app_id ) : null;
	}
}

if ( ! function_exists( 'kaamase_cv_refuse' ) ) {
	/**
	 * A plain refusal page for a CV link that does not open.
	 *
	 * @since 1.0.0
	 * @param string $message What happened.
	 * @param int    $status  HTTP status.
	 * @return void
	 */
	function kaamase_cv_refuse( $message, $status ) {

		nocache_headers();

		wp_die(
			esc_html( $message ),
			esc_html__( 'CV', 'kaamase-core' ),
			array(
				'response'  => (int) $status,
				'back_link' => true,
			)
		);
	}
}

if ( ! function_exists( 'kaamase_cv_serve' ) ) {
	/**
	 * Open a CV from a signed link, after checking everything again.
	 *
	 * Checked again on opening, not only when the link was made: an
	 * application withdrawn, or a CV deleted, five minutes after the
	 * link was drawn must still not open.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_cv_serve() {

		if ( ! isset( $_GET['kaamase_cv'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The link carries its own signature.
			return;
		}

		// Never kept by any cache, whatever happens next.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'kaamase cv' );

		if ( ! headers_sent() ) {
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
			header( 'X-Robots-Tag: noindex, nofollow' );
		}

		$token = sanitize_text_field( wp_unslash( $_GET['kaamase_cv'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$parts = explode( '.', $token );

		if ( 2 !== count( $parts ) || ! hash_equals( kaamase_cv_sign( $parts[0] ), $parts[1] ) ) {
			kaamase_cv_refuse( __( 'This link does not work.', 'kaamase-core' ), 403 );
		}

		$data = json_decode( (string) base64_decode( strtr( $parts[0], '-_', '+/' ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! is_array( $data ) || 4 !== count( $data ) ) {
			kaamase_cv_refuse( __( 'This link does not work.', 'kaamase-core' ), 403 );
		}

		list( $viewer, $owner, $app_id, $until ) = array_map( 'intval', $data );

		if ( $until < time() ) {
			kaamase_cv_refuse( __( 'This link has run out. Go back and open the CV again.', 'kaamase-core' ), 410 );
		}

		if ( ! kaamase_cv_may_open( $viewer, $owner, $app_id ) ) {
			kaamase_cv_refuse( __( 'This CV is no longer available to you.', 'kaamase-core' ), 403 );
		}

		$cv   = kaamase_cv_of( $owner );
		$path = kaamase_cv_dir() . '/' . $cv['file'];
		$pdf  = 'application/pdf' === $cv['type'];
		$who  = sanitize_file_name( (string) get_the_author_meta( 'display_name', $owner ) );
		$name = 'CV-' . ( '' !== $who ? $who : $owner ) . ( $pdf ? '.pdf' : '.jpg' );

		// Whatever a cache or another plugin buffered so far would corrupt the file.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: ' . $cv['type'] );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Security-Policy: default-src \'none\'; img-src \'self\'; style-src \'unsafe-inline\'' );
		header( 'Referrer-Policy: no-referrer' );
		// A PDF is downloaded and opened by the phone's own reader; a photo is shown.
		header( 'Content-Disposition: ' . ( $pdf ? 'attachment' : 'inline' ) . '; filename="' . $name . '"' );

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}
add_action( 'init', 'kaamase_cv_serve', 20 );


/* ==========================================================================
   3. WHAT GOES, AND WHEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_cv_on_delete_post' ) ) {
	/**
	 * A professional profile deleted takes its owner's CV.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post being deleted.
	 * @return void
	 */
	function kaamase_cv_on_delete_post( $post_id ) {

		if ( defined( 'KAAMASE_PROF_TYPE' ) && KAAMASE_PROF_TYPE === get_post_type( (int) $post_id ) ) {
			kaamase_cv_delete( (int) get_post_field( 'post_author', (int) $post_id ) );
		}
	}
}
add_action( 'before_delete_post', 'kaamase_cv_on_delete_post' );

if ( ! function_exists( 'kaamase_cv_on_delete_user' ) ) {
	/**
	 * An account deleted takes its CV.
	 *
	 * @since 1.0.0
	 * @param int $user_id Account.
	 * @return void
	 */
	function kaamase_cv_on_delete_user( $user_id ) {
		kaamase_cv_delete( $user_id );
	}
}
add_action( 'delete_user', 'kaamase_cv_on_delete_user' );

if ( ! function_exists( 'kaamase_cv_sweep' ) ) {
	/**
	 * Once a day: delete files in the folder that belong to nobody.
	 *
	 * A save interrupted halfway, or an account removed straight from the
	 * database, leaves a file nothing points at. A day's grace, so a save
	 * happening right now is never caught.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_cv_sweep() {

		$dir = kaamase_cv_dir();

		if ( ! is_dir( $dir ) ) {
			return;
		}

		global $wpdb;

		$kept = array();
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", KAAMASE_CV_META ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( (array) $rows as $row ) {

			$cv = maybe_unserialize( $row );

			if ( is_array( $cv ) && ! empty( $cv['file'] ) ) {
				$kept[ (string) $cv['file'] ] = true;
			}
		}

		foreach ( (array) glob( $dir . '/*' ) as $path ) {

			$file = basename( (string) $path );

			// Only our own names: the lock, the index and check.txt stay.
			if ( preg_match( '/^[a-f0-9]{32}(\.jpg)?$/', $file ) && ! isset( $kept[ substr( $file, 0, 32 ) ] ) && filemtime( $path ) < time() - DAY_IN_SECONDS ) {
				wp_delete_file( $path );
			}
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_cv_sweep', 30 );

if ( ! function_exists( 'kaamase_cv_register_exporter' ) ) {
	/**
	 * The CV in Tools → Export Personal Data: that there is one, and when.
	 *
	 * @since 1.0.0
	 * @param array $exporters Exporters.
	 * @return array
	 */
	function kaamase_cv_register_exporter( $exporters ) {

		$exporters['kaamase-cv'] = array(
			'exporter_friendly_name' => __( 'Kaam Ase CV', 'kaamase-core' ),
			'callback'               => 'kaamase_cv_export',
		);

		return $exporters;
	}
}
add_filter( 'wp_privacy_personal_data_exporters', 'kaamase_cv_register_exporter' );

if ( ! function_exists( 'kaamase_cv_export' ) ) {
	/**
	 * Export.
	 *
	 * @since 1.0.0
	 * @param string $email Their email.
	 * @param int    $page  Page.
	 * @return array
	 */
	function kaamase_cv_export( $email, $page = 1 ) {

		unset( $page );

		$user = get_user_by( 'email', $email );
		$cv   = $user ? kaamase_cv_of( $user->ID ) : null;

		return array(
			'data' => $cv ? array(
				array(
					'group_id'    => 'kaamase-cv',
					'group_label' => __( 'CV', 'kaamase-core' ),
					'item_id'     => 'kaamase-cv-' . $user->ID,
					'data'        => array(
						array(
							'name'  => __( 'File', 'kaamase-core' ),
							'value' => $cv['name'],
						),
						array(
							'name'  => __( 'Uploaded', 'kaamase-core' ),
							'value' => $cv['at'] ? wp_date( 'Y-m-d', $cv['at'] ) : '',
						),
					),
				),
			) : array(),
			'done' => true,
		);
	}
}

if ( ! function_exists( 'kaamase_cv_register_eraser' ) ) {
	/**
	 * The CV in Tools → Erase Personal Data.
	 *
	 * @since 1.0.0
	 * @param array $erasers Erasers.
	 * @return array
	 */
	function kaamase_cv_register_eraser( $erasers ) {

		$erasers['kaamase-cv'] = array(
			'eraser_friendly_name' => __( 'Kaam Ase CV', 'kaamase-core' ),
			'callback'             => 'kaamase_cv_erase',
		);

		return $erasers;
	}
}
add_filter( 'wp_privacy_personal_data_erasers', 'kaamase_cv_register_eraser' );

if ( ! function_exists( 'kaamase_cv_erase' ) ) {
	/**
	 * Erase.
	 *
	 * @since 1.0.0
	 * @param string $email Their email.
	 * @param int    $page  Page.
	 * @return array
	 */
	function kaamase_cv_erase( $email, $page = 1 ) {

		unset( $page );

		$user = get_user_by( 'email', $email );

		return array(
			'items_removed'  => $user ? kaamase_cv_delete( $user->ID ) : false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}


/* ==========================================================================
   4. THE APP'S ROUTES
   ========================================================================== */

if ( ! function_exists( 'kaamase_cv_routes' ) ) {
	/**
	 * The app's routes.
	 *
	 *   GET  /me/cv          your CV, with a link to open it, or null
	 *   POST /me/cv          send a new one (multipart, field "cv")
	 *   POST /me/cv/delete   delete it
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_cv_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = function_exists( 'kaamase_rest_require_login' ) ? 'kaamase_rest_require_login' : 'is_user_logged_in';

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/cv',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'kaamase_cv_rest_mine',
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => 'kaamase_cv_rest_save',
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/me/cv/delete',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_cv_rest_delete',
				'permission_callback' => $auth,
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_cv_routes' );

if ( ! function_exists( 'kaamase_cv_rest_mine' ) ) {
	/**
	 * GET /me/cv
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	function kaamase_cv_rest_mine() {

		$me = get_current_user_id();

		return new WP_REST_Response( array( 'cv' => kaamase_cv_info( $me, $me ) ), 200 );
	}
}

if ( ! function_exists( 'kaamase_cv_rest_save' ) ) {
	/**
	 * POST /me/cv
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_cv_rest_save( $request ) {

		$me     = get_current_user_id();
		$files  = $request->get_file_params();
		$result = kaamase_cv_save( $me, $files['cv'] ?? null );

		if ( is_wp_error( $result ) ) {
			return function_exists( 'kaamase_rest_error' ) ? kaamase_rest_error( $result ) : $result;
		}

		return new WP_REST_Response( array( 'cv' => kaamase_cv_info( $me, $me ) ), 201 );
	}
}

if ( ! function_exists( 'kaamase_cv_rest_delete' ) ) {
	/**
	 * POST /me/cv/delete
	 *
	 * @since 1.0.0
	 * @return WP_REST_Response
	 */
	function kaamase_cv_rest_delete() {

		kaamase_cv_delete( get_current_user_id() );

		return new WP_REST_Response( array( 'cv' => null ), 200 );
	}
}


/* ==========================================================================
   5. ON THE WEBSITE: THE PROFESSIONAL PROFILE PAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_cv_card' ) ) {
	/**
	 * "Your CV", under the professional profile form.
	 *
	 * A form of its own, so a CV is never lost to a mistake in the
	 * profile, nor the profile to a CV that is too big.
	 *
	 * @since 1.0.0
	 * @param int $user_id Owner.
	 * @return string
	 */
	function kaamase_cv_card( $user_id ) {

		$cv   = kaamase_cv_info( $user_id, $user_id );
		$back = function_exists( 'kaamase_prof_url' ) ? kaamase_prof_url( 'my_professional' ) : home_url( '/' );

		ob_start();
		?>
		<section class="ka-card ka-card--pad-lg ka-mt-6" id="your-cv">
			<h2><?php esc_html_e( 'Your CV', 'kaamase-core' ); ?></h2>

			<p class="ka-small ka-soft ka-mt-4">
				<?php esc_html_e( 'A PDF, or a photo of your paper CV, up to 5 MB. It is sent only with the applications you choose, and only that employer can open it, until a month after their job closes.', 'kaamase-core' ); ?>
			</p>

			<?php echo kaamase_cv_flash_show(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped where built. ?>

			<?php if ( $cv ) : ?>
				<p class="ka-mt-4">
					<a href="<?php echo esc_url( $cv['url'] ); ?>" rel="nofollow"><?php echo esc_html( $cv['name'] ); ?></a>
					<span class="ka-small ka-soft">
						<?php
						/* translators: 1: file size, 2: date */
						echo esc_html( sprintf( __( '%1$s, added %2$s', 'kaamase-core' ), size_format( $cv['size'] ), wp_date( 'j M Y', $cv['uploaded_at'] ) ) );
						?>
					</span>
				</p>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( $back ); ?>#your-cv" class="ka-mt-4">
				<input type="hidden" name="kaamase_action" value="save_cv">
				<?php wp_nonce_field( 'kaamase_save_cv', 'kaamase_cv_nonce' ); ?>
				<label class="ka-label" for="ka-cv-file"><?php echo esc_html( $cv ? __( 'Replace it', 'kaamase-core' ) : __( 'Add your CV', 'kaamase-core' ) ); ?></label>
				<input class="ka-input" type="file" id="ka-cv-file" name="kaamase_cv" accept="application/pdf,image/jpeg,image/png,image/webp" required>
				<button class="ka-btn ka-btn--outline ka-mt-4" type="submit"><?php esc_html_e( 'Save CV', 'kaamase-core' ); ?></button>
			</form>

			<?php if ( $cv ) : ?>
				<form method="post" action="<?php echo esc_url( $back ); ?>#your-cv" class="ka-mt-4">
					<input type="hidden" name="kaamase_action" value="delete_cv">
					<?php wp_nonce_field( 'kaamase_delete_cv', 'kaamase_cv_nonce' ); ?>
					<button class="ka-btn ka-btn--outline ka-btn--sm" type="submit"><?php esc_html_e( 'Delete my CV', 'kaamase-core' ); ?></button>
				</form>
			<?php endif; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_cv_after_profile_form' ) ) {
	/**
	 * Put "Your CV" under the professional profile form, once it exists.
	 *
	 * @since 1.0.0
	 * @param string $output Shortcode output.
	 * @param string $tag    Shortcode.
	 * @return string
	 */
	function kaamase_cv_after_profile_form( $output, $tag ) {

		if ( 'kaamase_my_professional' !== $tag || ! is_user_logged_in() || ! kaamase_cv_ready() || ! kaamase_prof_id( get_current_user_id() ) ) {
			return $output;
		}

		return $output . kaamase_cv_card( get_current_user_id() );
	}
}
add_filter( 'do_shortcode_tag', 'kaamase_cv_after_profile_form', 10, 2 );

if ( ! function_exists( 'kaamase_cv_flash_key' ) ) {
	/**
	 * Where the result of a save waits for the page.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_cv_flash_key() {
		return 'kaamase_cv_flash_' . get_current_user_id();
	}
}

if ( ! function_exists( 'kaamase_cv_flash_show' ) ) {
	/**
	 * The waiting result, once.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_cv_flash_show() {

		$flash = get_transient( kaamase_cv_flash_key() );

		if ( ! is_array( $flash ) || 2 !== count( $flash ) ) {
			return '';
		}

		delete_transient( kaamase_cv_flash_key() );

		return '<div class="ka-notice ka-notice--' . esc_attr( (string) $flash[0] ) . ' ka-mt-4"><div><span class="ka-notice__title">' . esc_html( (string) $flash[1] ) . '</span></div></div>';
	}
}

if ( ! function_exists( 'kaamase_cv_handle_forms' ) ) {
	/**
	 * Save or delete from the website.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_cv_handle_forms() {

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked below.
		$action = isset( $_POST['kaamase_action'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_action'] ) ) : '';

		if ( ! in_array( $action, array( 'save_cv', 'delete_cv' ), true ) || ! is_user_logged_in() ) {
			return;
		}

		$nonce = isset( $_POST['kaamase_cv_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_cv_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'kaamase_' . $action ) ) {
			return;
		}

		$me   = get_current_user_id();
		$back = ( function_exists( 'kaamase_prof_url' ) ? kaamase_prof_url( 'my_professional' ) : home_url( '/' ) ) . '#your-cv';

		if ( 'delete_cv' === $action ) {
			kaamase_cv_delete( $me );
			set_transient( kaamase_cv_flash_key(), array( 'ok', __( 'Your CV is deleted.', 'kaamase-core' ) ), 10 * MINUTE_IN_SECONDS );
			wp_safe_redirect( $back );
			exit;
		}

		$result = kaamase_cv_save( $me, $_FILES['kaamase_cv'] ?? null ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checked in kaamase_cv_save().
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		set_transient(
			kaamase_cv_flash_key(),
			is_wp_error( $result ) ? array( 'error', $result->get_error_message() ) : array( 'ok', __( 'Your CV is saved.', 'kaamase-core' ) ),
			10 * MINUTE_IN_SECONDS
		);

		wp_safe_redirect( $back );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_cv_handle_forms' );
