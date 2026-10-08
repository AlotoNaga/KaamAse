<?php
/**
 * Job photographs.
 *
 * Pictures on a job post. One for a free account, more for a paid one.
 *
 * Why a job has photographs at all
 * --------------------------------
 * A wall of text listings is a wall nobody reads. A photograph of the
 * site, the building, the shop front or the machine tells a worker in
 * one second what a paragraph takes thirty seconds to say, and it
 * survives being shared into a group chat where the text does not.
 *
 * It also does something less obvious. A photograph is the cheapest
 * proof a job is real. Anybody can type "mason wanted, good pay". Taking
 * a picture of the site means standing at the site.
 *
 * Where the files go
 * ------------------
 * Exactly where profile photographs already go: WordPress attachments,
 * parented to the job. That means media.php hides them from the Media
 * library automatically, the theme's metadata stripping runs on them,
 * and the two sizes the theme registered for job pictures are the only
 * ones generated. No new storage, no second system.
 *
 * They are never removed when a job closes. A closed job is still a page
 * on the internet that somebody searching for work in Nagaland may land
 * on next year, and a page whose pictures have been deleted is a worse
 * page. Only deleting the job itself removes them.
 *
 * The whole picture, never cropped
 * --------------------------------
 * Employers post printed flyers: a hiring drive, a training course, a
 * shop's notice. Those run tall, with the heading at the top and the
 * phone number at the bottom, and the old 960 by 540 crop on the job
 * page kept only a band across the middle. The job page and the app now
 * get the picture whole, and a tap opens it large enough to read the
 * small print. The crops are still made, and still used where a crop is
 * right: the small square on a job card and the picture in a share
 * preview.
 *
 * Uploads only, never a link
 * --------------------------
 * There is no field anywhere in this file that accepts a URL. That is
 * deliberate. A URL field is a way to put anything at all on a job page,
 * including video, and the platform cannot see what is on the other end
 * of it. Every picture here arrives as a file, is checked as an image
 * before it is accepted, and is served from this site.
 *
 * @package KaamaseCore
 * @version 1.3.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/** Where the list of attachment IDs lives. */
define( 'KAAMASE_JOB_PHOTOS_KEY', KAAMASE_META_PREFIX . 'photos' );

/** The most anybody can have, whatever their allowance says. */
define( 'KAAMASE_JOB_PHOTOS_CEILING', 6 );


/* ==========================================================================
   1. HOW MANY

   Declared as a meter rather than a number in this file, which puts it
   on the Limits screen with everything else and lets the payment plugin
   sell more of it without this file knowing what a plan is.
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photo_meter' ) ) {
	/**
	 * Add photographs to the metered things.
	 *
	 * @since 1.0.0
	 * @param array[] $meters Meters keyed by name.
	 * @return array[]
	 */
	function kaamase_job_photo_meter( $meters ) {

		$meters['job_photos'] = array(
			'label'  => __( 'Photos on a job', 'kaamase-core' ),
			'note'   => __( 'How many pictures one job post can carry. One is enough to make a listing worth looking at, which is why it is the free number. More is a reason to pay rather than something a job needs.', 'kaamase-core' ),
			'period' => 'concurrent',
			'free'   => 1,
		);

		return $meters;
	}
}
add_filter( 'kaamase_meters', 'kaamase_job_photo_meter' );

if ( ! function_exists( 'kaamase_job_photo_limit' ) ) {
	/**
	 * How many photographs this person may put on one job.
	 *
	 * Capped, because staff accounts are unmetered and come back with
	 * the largest number PHP can hold. A form cannot draw that many
	 * boxes, and no job needs more pictures than a person will look at.
	 *
	 * @since 1.0.0
	 * @param int $user_id Optional. Defaults to the current user.
	 * @return int
	 */
	function kaamase_job_photo_limit( $user_id = 0 ) {

		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		$allowed = function_exists( 'kaamase_allowance' )
			? (int) kaamase_allowance( 'job_photos', $user_id )
			: 1;

		return max( 1, min( KAAMASE_JOB_PHOTOS_CEILING, $allowed ) );
	}
}


/* ==========================================================================
   2. READING AND WRITING THE LIST
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photos' ) ) {
	/**
	 * The photographs on a job, in order.
	 *
	 * Checked against the attachments that actually exist, so a picture
	 * deleted from the Media screen leaves no broken slot behind.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return int[] Attachment IDs.
	 */
	function kaamase_job_photos( $job_id ) {

		$stored = get_post_meta( (int) $job_id, KAAMASE_JOB_PHOTOS_KEY, true );
		$ids    = is_array( $stored ) ? array_map( 'absint', $stored ) : array();

		$live = array();

		foreach ( $ids as $id ) {
			if ( $id && 'attachment' === get_post_type( $id ) ) {
				$live[] = $id;
			}
		}

		return $live;
	}
}

if ( ! function_exists( 'kaamase_job_photos_store' ) ) {
	/**
	 * Write the list back, and keep the featured image pointing at the
	 * first one.
	 *
	 * The featured image matters beyond the theme. It is what a share
	 * card, a search engine and any future plugin will look for, and
	 * keeping it in step here means nothing else has to remember to.
	 *
	 * @since 1.0.0
	 * @param int   $job_id Job ID.
	 * @param int[] $ids    Attachment IDs, in the order they should show.
	 * @return void
	 */
	function kaamase_job_photos_store( $job_id, $ids ) {

		$job_id = (int) $job_id;
		$ids    = array_values( array_unique( array_map( 'absint', (array) $ids ) ) );

		if ( empty( $ids ) ) {
			delete_post_meta( $job_id, KAAMASE_JOB_PHOTOS_KEY );
			delete_post_thumbnail( $job_id );

			return;
		}

		update_post_meta( $job_id, KAAMASE_JOB_PHOTOS_KEY, $ids );
		set_post_thumbnail( $job_id, $ids[0] );
	}
}


/* ==========================================================================
   3. TAKING AN UPLOAD

   Three checks before a file is kept, because the first two are cheap
   and the third is the only one that cannot be fooled.
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photo_mimes' ) ) {
	/**
	 * The only file types a job photograph may be.
	 *
	 * @since 1.0.0
	 * @return string[] Extensions mapped to mime types.
	 */
	function kaamase_job_photo_mimes() {

		return array(
			'jpg|jpeg' => 'image/jpeg',
			'png'      => 'image/png',
			'webp'     => 'image/webp',
		);
	}
}

if ( ! function_exists( 'kaamase_job_photo_accept' ) ) {
	/**
	 * The accept attribute for a file input.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_job_photo_accept() {
		return 'image/jpeg,image/png,image/webp';
	}
}

if ( ! function_exists( 'kaamase_job_photo_is_image' ) ) {
	/**
	 * Whether a file on disk really is a picture.
	 *
	 * The browser's stated type is a claim by whoever is uploading, and
	 * the file extension is a claim by whoever named it. This opens the
	 * file and reads what is actually inside it, which is the only one
	 * of the three that a person cannot simply set to whatever they
	 * like. A video renamed to end in .jpg fails here.
	 *
	 * @since 1.0.0
	 * @param string $path Temporary file path.
	 * @return bool
	 */
	function kaamase_job_photo_is_image( $path ) {

		if ( ! $path || ! file_exists( $path ) ) {
			return false;
		}

		$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- A file that cannot be read is simply not a picture.

		if ( empty( $info[2] ) ) {
			return false;
		}

		return in_array(
			(int) $info[2],
			array( IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP ),
			true
		);
	}
}

if ( ! function_exists( 'kaamase_job_photo_take' ) ) {
	/**
	 * Accept one uploaded file and attach it to the job.
	 *
	 * @since 1.0.0
	 * @param array $file   One entry shaped like a $_FILES row.
	 * @param int   $job_id Job the picture belongs to.
	 * @return int|WP_Error Attachment ID, or why not.
	 */
	function kaamase_job_photo_take( $file, $job_id ) {

		if ( empty( $file['name'] ) ) {
			return new WP_Error( 'kaamase_no_file', __( 'No picture arrived.', 'kaamase-core' ) );
		}

		if ( ! empty( $file['error'] ) ) {

			$too_big = in_array( (int) $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true );

			return new WP_Error(
				'kaamase_upload_failed',
				$too_big
					? __( 'That picture was too big for the server to take. Photos straight from a phone are often very large. Try one taken at a smaller size.', 'kaamase-core' )
					: __( 'That picture did not upload. Please try again.', 'kaamase-core' )
			);
		}

		if ( ! kaamase_job_photo_is_image( isset( $file['tmp_name'] ) ? $file['tmp_name'] : '' ) ) {
			return new WP_Error(
				'kaamase_not_an_image',
				__( 'That file is not a picture. Only JPG, PNG and WEBP photos can go on a job.', 'kaamase-core' )
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		/*
		 * media_handle_upload reads from the real $_FILES, so the entry
		 * is put there under a name of our own. Everything about the
		 * upload then follows the same path as a profile photograph,
		 * including the hook that strips the camera and location data
		 * a phone hides inside the file.
		 */
		$slot = 'kaamase_job_photo_one';

		$_FILES[ $slot ] = $file;

		$attachment_id = media_handle_upload(
			$slot,
			(int) $job_id,
			array(),
			array(
				'test_form' => false,
				'mimes'     => kaamase_job_photo_mimes(),
			)
		);

		unset( $_FILES[ $slot ] );

		return $attachment_id;
	}
}

if ( ! function_exists( 'kaamase_job_photo_rows' ) ) {
	/**
	 * Turn one multiple file input into ordinary single file rows.
	 *
	 * A field named with brackets arrives from PHP as arrays of every
	 * property rather than as a list of files, which nothing in
	 * WordPress accepts. This turns it back into the shape everything
	 * else expects.
	 *
	 * @since 1.0.0
	 * @param array $field One $_FILES entry from a multiple input.
	 * @return array[] A list of single file rows.
	 */
	function kaamase_job_photo_rows( $field ) {

		if ( empty( $field['name'] ) ) {
			return array();
		}

		if ( ! is_array( $field['name'] ) ) {
			return array( $field );
		}

		$rows = array();

		foreach ( array_keys( $field['name'] ) as $i ) {

			if ( '' === $field['name'][ $i ] ) {
				continue;
			}

			$rows[] = array(
				'name'     => $field['name'][ $i ],
				'type'     => $field['type'][ $i ] ?? '',
				'tmp_name' => $field['tmp_name'][ $i ] ?? '',
				'error'    => $field['error'][ $i ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $field['size'][ $i ] ?? 0,
			);
		}

		return $rows;
	}
}


/* ==========================================================================
   4. THE MOMENT A JOB IS SAVED
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photo_note_key' ) ) {
	/**
	 * Where a message about a refused picture waits.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string
	 */
	function kaamase_job_photo_note_key( $user_id ) {
		return 'kaamase_photo_note_' . absint( $user_id );
	}
}

if ( ! function_exists( 'kaamase_job_photo_handle' ) ) {
	/**
	 * Remove what was unticked, add what was uploaded.
	 *
	 * Hooked to the save rather than built into any one form, so the
	 * website form, the staff screen and the app all get the same rules
	 * without any of them carrying a copy.
	 *
	 * @since 1.0.0
	 * @param int $job_id Job ID.
	 * @return void
	 */
	function kaamase_job_photo_handle( $job_id ) {

		$job_id  = (int) $job_id;
		$user_id = get_current_user_id();

		if ( ! $job_id || ! $user_id ) {
			return;
		}

		$photos = kaamase_job_photos( $job_id );
		$notes  = array();

		/* ---- Taking one off ---- */

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The form that owns this checked its own nonce.
		$remove = isset( $_POST['kaamase_remove_photos'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['kaamase_remove_photos'] ) ) : array();

		if ( $remove ) {

			foreach ( $remove as $id ) {

				if ( ! in_array( $id, $photos, true ) ) {
					continue;
				}

				wp_delete_attachment( $id, true );
			}

			$photos = array_values( array_diff( $photos, $remove ) );
		}

		/* ---- Putting one on ---- */

		$rows = isset( $_FILES['kaamase_photos'] ) ? kaamase_job_photo_rows( $_FILES['kaamase_photos'] ) : array();

		if ( $rows ) {

			$limit = kaamase_job_photo_limit( $user_id );

			foreach ( $rows as $row ) {

				if ( count( $photos ) >= $limit ) {

					$notes[] = sprintf(
						/* translators: %s: how many photos the account may have */
						_n(
							'Only %s picture was kept. That is what this account can put on a job.',
							'Only %s pictures were kept. That is what this account can put on a job.',
							$limit,
							'kaamase-core'
						),
						number_format_i18n( $limit )
					);

					break;
				}

				$taken = kaamase_job_photo_take( $row, $job_id );

				if ( is_wp_error( $taken ) ) {
					$notes[] = $taken->get_error_message();

					continue;
				}

				$photos[] = (int) $taken;
			}
		}

		kaamase_job_photos_store( $job_id, $photos );

		if ( $notes ) {
			set_transient( kaamase_job_photo_note_key( $user_id ), array_unique( $notes ), 10 * MINUTE_IN_SECONDS );
		}
	}
}
add_action( 'kaamase_job_saved', 'kaamase_job_photo_handle', 20 );

if ( ! function_exists( 'kaamase_job_photo_clean_up' ) ) {
	/**
	 * Delete the pictures when the job itself is deleted.
	 *
	 * Only on deletion. A job that closed, or expired, keeps everything:
	 * it is still a page people find from a search, and a page with its
	 * pictures stripped out is a worse page than one without pictures in
	 * the first place.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post being deleted.
	 * @return void
	 */
	function kaamase_job_photo_clean_up( $post_id ) {

		if ( 'kaamase_job' !== get_post_type( $post_id ) ) {
			return;
		}

		foreach ( kaamase_job_photos( $post_id ) as $id ) {
			wp_delete_attachment( $id, true );
		}
	}
}
add_action( 'before_delete_post', 'kaamase_job_photo_clean_up' );


/* ==========================================================================
   5. THE FIELD ON THE WEBSITE FORM

   Added by wrapping the shortcode rather than by editing the form.
   post-job.php is not touched, so nothing there can break and an update
   to it will not undo this.
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photo_field' ) ) {
	/**
	 * The upload box, with whatever is already on the job above it.
	 *
	 * @since 1.0.0
	 * @param int $job_id  Job being edited, or 0 for a new one.
	 * @param int $user_id Who is posting.
	 * @return string Markup.
	 */
	function kaamase_job_photo_field( $job_id, $user_id ) {

		$limit    = kaamase_job_photo_limit( $user_id );
		$existing = $job_id ? kaamase_job_photos( $job_id ) : array();
		$left     = max( 0, $limit - count( $existing ) );

		ob_start();
		?>
		<div class="ka-field">

			<label class="ka-label" for="ka-job-photos">
				<?php esc_html_e( 'Pictures', 'kaamase-core' ); ?>
			</label>

			<?php if ( $existing ) : ?>
				<div class="ka-cluster ka-mt-4">
					<?php foreach ( $existing as $photo_id ) : ?>
						<label class="ka-photo-pick">
							<?php
							echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								$photo_id,
								'thumbnail',
								false,
								array(
									'class'   => 'ka-photo-pick__img',
									'loading' => 'lazy',
									'alt'     => '',
								)
							);
							?>
							<span class="ka-check">
								<input type="checkbox" name="kaamase_remove_photos[]" value="<?php echo esc_attr( $photo_id ); ?>">
								<span><?php esc_html_e( 'Remove', 'kaamase-core' ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $left > 0 ) : ?>

				<input class="ka-input ka-mt-4" type="file" id="ka-job-photos" name="kaamase_photos[]"
					accept="<?php echo esc_attr( kaamase_job_photo_accept() ); ?>"
					<?php echo $left > 1 ? 'multiple' : ''; ?>>

				<p class="ka-hint">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: how many more pictures may be added */
							_n(
								'You can add %s picture to this job.',
								'You can add up to %s pictures to this job.',
								$left,
								'kaamase-core'
							),
							number_format_i18n( $left )
						)
					);
					?>
					<?php esc_html_e( 'A photo of the site or the shop gets a job far more answers than words alone, and it shows the work is real.', 'kaamase-core' ); ?>
				</p>

			<?php else : ?>

				<p class="ka-hint ka-mt-4">
					<?php esc_html_e( 'This job has all the pictures this account can add. Remove one to put a different one up.', 'kaamase-core' ); ?>
				</p>

			<?php endif; ?>

			<?php if ( 1 === $limit && function_exists( 'kaamase_charging_is_on' ) && kaamase_charging_is_on() ) : ?>
				<p class="ka-hint">
					<?php esc_html_e( 'Paid accounts can put more than one picture on a job.', 'kaamase-core' ); ?>
				</p>
			<?php endif; ?>

			<p class="ka-hint">
				<?php esc_html_e( 'JPG, PNG or WEBP. We remove the hidden location information phones save inside photos before anybody sees them.', 'kaamase-core' ); ?>
			</p>

		</div>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_job_photo_form' ) ) {
	/**
	 * The job form, with the upload box in it.
	 *
	 * Calls the original, then makes two changes to what came back: the
	 * form is told it may carry files, and the field is put in above the
	 * submit button. If either marker ever moves, the form still renders
	 * exactly as it did before and only the pictures go missing, which
	 * is the safe way for this to fail.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_job_photo_form() {

		$html = (string) kaamase_post_job_shortcode();

		if ( false === strpos( $html, 'kaamase_job_nonce' ) ) {
			return $html;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which job is on screen, same as the form itself.
		$job_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

		if ( $job_id && ! current_user_can( 'edit_post', $job_id ) ) {
			$job_id = 0;
		}

		$form = '<form class="ka-form ka-stack--lg" method="post" action="">';

		$html = str_replace(
			$form,
			'<form class="ka-form ka-stack--lg" method="post" action="" enctype="multipart/form-data">',
			$html
		);

		$submit = '<button class="ka-btn ka-btn--action ka-btn--lg ka-btn--block" type="submit">';

		$field = kaamase_job_photo_field( $job_id, get_current_user_id() );

		$at = strpos( $html, $submit );

		if ( false === $at ) {
			return $html;
		}

		return substr( $html, 0, $at ) . $field . substr( $html, $at );
	}
}

if ( ! function_exists( 'kaamase_job_photo_take_over_shortcode' ) ) {
	/**
	 * Put the wrapper in front of the original shortcode.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_job_photo_take_over_shortcode() {

		if ( ! function_exists( 'kaamase_post_job_shortcode' ) || ! shortcode_exists( 'kaamase_post_job' ) ) {
			return;
		}

		remove_shortcode( 'kaamase_post_job' );
		add_shortcode( 'kaamase_post_job', 'kaamase_job_photo_form' );
	}
}
add_action( 'init', 'kaamase_job_photo_take_over_shortcode', 20 );


/* ==========================================================================
   6. ON THE JOB PAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photo_styles' ) ) {
	/**
	 * The handful of rules the gallery needs.
	 *
	 * Kept here rather than in the theme stylesheet so the feature is
	 * one file and works whichever theme is running.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_job_photo_styles() {

		/*
		 * Every picture sits whole in a light frame. One picture is as
		 * wide as the column and never taller than most of the screen,
		 * so a tall flyer shows top to bottom without scrolling past it.
		 * Several share a grid of equal frames, each picture fitted
		 * inside its frame rather than cut to fill it.
		 */
		return '<style id="kaamase-job-photos">'
			. '.ka-photos{display:grid;gap:.5rem;margin:0 0 1.25rem}'
			. '.ka-photos--multi{grid-template-columns:repeat(auto-fit,minmax(140px,1fr))}'
			. '.ka-photos__item{display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:12px;background:#efece6}'
			. '.ka-photos img{display:block;width:auto;max-width:100%;height:auto;max-height:80vh}'
			. '.ka-photos--multi .ka-photos__item{aspect-ratio:4/3}'
			. '.ka-photos--multi img{width:100%;height:100%;max-height:none;object-fit:contain}'
			. '.ka-photo-pick{display:inline-block;text-align:center;margin:0 .5rem .5rem 0}'
			. '.ka-photo-pick__img{width:88px;height:88px;object-fit:cover;border-radius:10px;display:block}'
			. '</style>';
	}
}

if ( ! function_exists( 'kaamase_job_photo_open_url' ) ) {
	/**
	 * The picture a tap on the job page opens.
	 *
	 * The stored picture, which the site keeps at no more than 1600 on
	 * its longest side, so a flyer's small print can be zoomed and read.
	 * Except a PNG: WordPress never shrinks those, so the stored file can
	 * be many megabytes, and the 1024 copy stands in for it.
	 *
	 * @since 1.3.0
	 * @param int $photo_id Attachment ID.
	 * @return string URL, or an empty string.
	 */
	function kaamase_job_photo_open_url( $photo_id ) {

		$size = ( 'image/png' === get_post_mime_type( $photo_id ) ) ? 'large' : 'full';
		$src  = wp_get_attachment_image_src( $photo_id, $size );

		return ! empty( $src[0] ) ? (string) $src[0] : '';
	}
}

if ( ! function_exists( 'kaamase_job_photo_gallery' ) ) {
	/**
	 * Put the pictures above the description.
	 *
	 * @since 1.0.0
	 * @param string $content The job description.
	 * @return string
	 */
	function kaamase_job_photo_gallery( $content ) {

		if ( ! is_singular( 'kaamase_job' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		/*
		 * Not while the contact screen is up. Somebody who tapped for a
		 * phone number wants the number, and a gallery pushed above it
		 * makes them scroll for the one thing they asked for.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which screen was asked for.
		if ( ! empty( $_GET['kaamase_contact'] ) ) {
			return $content;
		}

		$job_id = get_the_ID();
		$photos = kaamase_job_photos( $job_id );
		$note   = '';

		/*
		 * A refused picture is reported here rather than on the form.
		 * The form redirects to the job the moment it saves, so a
		 * message left behind on the form is a message nobody sees.
		 */
		if ( get_current_user_id() && (int) get_post_field( 'post_author', $job_id ) === get_current_user_id() ) {

			$waiting = get_transient( kaamase_job_photo_note_key( get_current_user_id() ) );

			if ( $waiting ) {

				delete_transient( kaamase_job_photo_note_key( get_current_user_id() ) );

				$note = '<div class="ka-notice ka-notice--warn"><div><p>'
					. esc_html( implode( ' ', (array) $waiting ) )
					. '</p></div></div>';
			}
		}

		if ( empty( $photos ) ) {
			return $note . $content;
		}

		$class = ( count( $photos ) > 1 ) ? 'ka-photos ka-photos--multi' : 'ka-photos';

		$out = kaamase_job_photo_styles() . '<div class="' . esc_attr( $class ) . '">';

		/*
		 * "large" is WordPress's own uncropped copy, at most 1024 on its
		 * longest side. The browser may pick that or smaller, never the
		 * stored original, which for a PNG can be several megabytes; the
		 * tap is how somebody asks for more.
		 */
		$cap_width = 1024;

		$cap = static function () use ( &$cap_width ) {
			return $cap_width;
		};

		add_filter( 'max_srcset_image_width', $cap );

		foreach ( $photos as $photo_id ) {

			$large     = image_get_intermediate_size( $photo_id, 'large' );
			$cap_width = ! empty( $large['width'] ) ? (int) $large['width'] : 1024;

			$image = wp_get_attachment_image(
				$photo_id,
				'large',
				false,
				array(
					'loading' => 'lazy',
					'alt'     => the_title_attribute( array( 'echo' => false ) ),
				)
			);

			if ( '' === $image ) {
				continue;
			}

			$open = kaamase_job_photo_open_url( $photo_id );

			$out .= '' !== $open
				? '<a class="ka-photos__item" href="' . esc_url( $open ) . '" aria-label="' . esc_attr__( 'See the whole picture', 'kaamase-core' ) . '">' . $image . '</a>'
				: '<span class="ka-photos__item">' . $image . '</span>';
		}

		remove_filter( 'max_srcset_image_width', $cap );

		$out .= '</div>';

		return $note . $out . $content;
	}
}
add_filter( 'the_content', 'kaamase_job_photo_gallery', 12 );


/* ==========================================================================
   7. THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photo_shape' ) ) {
	/**
	 * Send the pictures with the job.
	 *
	 * The image key is rewritten as well as added to. The shared shaper
	 * asks for the two avatar crops, which are deliberately not
	 * generated for job pictures, so left alone it would hand the app a
	 * full size photograph to draw in a list.
	 *
	 * "small" and "large" are crops, 480 by 320 and 960 by 540, and stay
	 * exactly as they were so nothing in the app changes by itself.
	 * "whole" is the same picture uncropped, at most 1024 on its longest
	 * side, with its real width and height, for the screen that shows
	 * one job: a flyer's heading and phone number are at the edges a
	 * crop removes.
	 *
	 * @since 1.0.0
	 * @param array   $out  Shaped job.
	 * @param WP_Post $post The job.
	 * @return array
	 */
	function kaamase_job_photo_shape( $out, $post ) {

		$photos = kaamase_job_photos( $post->ID );

		$out['photos'] = array();

		foreach ( $photos as $photo_id ) {

			$card  = wp_get_attachment_image_src( $photo_id, 'kaamase-card' );
			$wide  = wp_get_attachment_image_src( $photo_id, 'kaamase-wide' );
			$whole = wp_get_attachment_image_src( $photo_id, 'large' );

			if ( ! $card && ! $wide ) {
				continue;
			}

			$out['photos'][] = array(
				'small'        => $card ? $card[0] : $wide[0],
				'large'        => $wide ? $wide[0] : $card[0],
				'whole'        => $whole ? $whole[0] : ( $wide ? $wide[0] : $card[0] ),
				'whole_width'  => $whole ? (int) $whole[1] : 0,
				'whole_height' => $whole ? (int) $whole[2] : 0,
			);
		}

		$out['image'] = $out['photos'] ? $out['photos'][0] : null;

		return $out;
	}
}
add_filter( 'kaamase_shape_job', 'kaamase_job_photo_shape', 18, 2 );

if ( ! function_exists( 'kaamase_job_photo_shape_me' ) ) {
	/**
	 * Tell the app how many pictures this account may add.
	 *
	 * Sent with the account rather than asked for separately, so the
	 * phone can draw the right number of slots on a form it has not
	 * submitted yet. Without it the app can only find out by uploading
	 * something and being refused, which is a bad way to learn a limit.
	 *
	 * @since 1.1.0
	 * @param array $me      The account object.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_job_photo_shape_me( $me, $user_id ) {

		$me['job_photo_limit'] = kaamase_job_photo_limit( $user_id );

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_job_photo_shape_me', 20, 2 );

if ( ! function_exists( 'kaamase_job_photo_route' ) ) {
	/**
	 * Let the app add a picture to a job it owns.
	 *
	 * A route of its own rather than a change to the jobs endpoint, so
	 * rest-api.php is left alone.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_job_photo_route() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		register_rest_route(
			KAAMASE_REST_NS,
			'/jobs/(?P<id>\d+)/photos',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_job_photo_rest',
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_job_photo_route' );

if ( ! function_exists( 'kaamase_job_photo_rest' ) ) {
	/**
	 * Take one picture from the app.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_job_photo_rest( $request ) {

		$job_id = absint( $request->get_param( 'id' ) );
		$files  = $request->get_file_params();

		$fail = static function ( $error ) {
			return function_exists( 'kaamase_rest_error' )
				? kaamase_rest_error( $error )
				: $error;
		};

		if ( 'kaamase_job' !== get_post_type( $job_id )
			|| (int) get_post_field( 'post_author', $job_id ) !== get_current_user_id()
			|| ! current_user_can( 'edit_post', $job_id ) ) {

			return $fail(
				new WP_Error( 'kaamase_forbidden', __( 'That job is not yours.', 'kaamase-core' ), array( 'status' => 403 ) )
			);
		}

		$photos = kaamase_job_photos( $job_id );
		$limit  = kaamase_job_photo_limit( get_current_user_id() );

		if ( count( $photos ) >= $limit ) {
			return $fail(
				new WP_Error(
					'kaamase_limit',
					function_exists( 'kaamase_limit_reached_message' )
						? kaamase_limit_reached_message( 'job_photos' )
						: __( 'This job already has all the pictures this account can add.', 'kaamase-core' ),
					array( 'status' => 400 )
				)
			);
		}

		if ( empty( $files['photo'] ) ) {
			return $fail(
				new WP_Error( 'kaamase_no_file', __( 'No picture arrived. Please try again.', 'kaamase-core' ), array( 'status' => 400 ) )
			);
		}

		$taken = kaamase_job_photo_take( $files['photo'], $job_id );

		if ( is_wp_error( $taken ) ) {
			$taken->add_data( array( 'status' => 400 ) );

			return $fail( $taken );
		}

		$photos[] = (int) $taken;

		kaamase_job_photos_store( $job_id, $photos );

		return rest_ensure_response(
			array(
				'ok'     => true,
				'photos' => count( kaamase_job_photos( $job_id ) ),
			)
		);
	}
}


/* ==========================================================================
   8. KEEPING THE FILES SMALL

   WordPress keeps the original at up to 2560 pixels wide. A job picture
   is never shown above 960, so the extra is disk nobody looks at. This
   brings the stored original down to a size that is still good enough to
   re-crop from later.
   ========================================================================== */

if ( ! function_exists( 'kaamase_job_photo_threshold' ) ) {
	/**
	 * Cap the size of the stored original.
	 *
	 * @since 1.0.0
	 * @param int $threshold Pixels on the longest side.
	 * @return int
	 */
	function kaamase_job_photo_threshold( $threshold ) {

		unset( $threshold );

		return 1600;
	}
}
add_filter( 'big_image_size_threshold', 'kaamase_job_photo_threshold' );