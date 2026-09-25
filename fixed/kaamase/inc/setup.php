<?php
/**
 * Theme setup.
 *
 * Everything WordPress needs to know about what this theme can do, plus
 * the image pipeline.
 *
 * Images get more attention here than usual, and for a reason. Workers
 * will upload photos straight from a phone camera, so a single upload is
 * often 4MB and 4000 pixels wide. WordPress will happily generate seven
 * derivatives of that and then serve one that is four times larger than
 * the space it occupies. On a village connection that is the difference
 * between a page that loads and a page that gets abandoned.
 *
 * @package Kaamase
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THEME SUPPORTS
   ========================================================================== */

if ( ! function_exists( 'kaamase_setup' ) ) {
	/**
	 * Register theme supports, menus and image sizes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_setup() {

		/*
		 * Translation support.
		 *
		 * Nagamese is not a language WordPress ships a locale for, so it
		 * will be added later as a custom locale with its own .po file in
		 * this folder. Every user facing string in this theme goes through
		 * a translation function from day one so that day does not require
		 * touching every file again.
		 */
		load_theme_textdomain( 'kaamase', KAAMASE_DIR . 'languages' );

		// Let WordPress manage the document title.
		add_theme_support( 'title-tag' );

		// Featured images on posts, pages and every custom type the plugin adds.
		add_theme_support( 'post-thumbnails' );

		// Clean HTML5 markup instead of the old XHTML output.
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
				'navigation-widgets',
			)
		);

		// Logo. Wide rather than square, because the wordmark is the logo.
		add_theme_support(
			'custom-logo',
			array(
				'height'               => 48,
				'width'                => 200,
				'flex-height'          => true,
				'flex-width'           => true,
				'unlink-homepage-logo' => false,
			)
		);

		// Embeds resize to their container rather than overflowing on a phone.
		add_theme_support( 'responsive-embeds' );

		// Admin editor picks up the theme colours and type.
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/editor.css' );

		/*
		 * Deliberately NOT added:
		 *
		 * wp-block-styles       Adds around 30KB of CSS to the front end for
		 *                       block styling this theme already handles.
		 * automatic-feed-links  Nobody is subscribing to a hiring platform by
		 *                       RSS, and it adds two link tags to every page.
		 * customer-header       No decorative headers. The header is functional.
		 */

		/*
		 * Navigation.
		 *
		 * The bottom tab bar on phones is deliberately not a menu. It changes
		 * depending on whether the visitor is a worker, an employer or logged
		 * out, so it is built in code from the user role rather than being
		 * something an admin can accidentally break in the menu screen.
		 */
		register_nav_menus(
			array(
				'primary' => __( 'Primary menu, desktop header', 'kaamase' ),
				'mobile'  => __( 'Mobile menu, slide out drawer', 'kaamase' ),
				'browse'  => __( 'Footer, browse and discover', 'kaamase' ),
				'company' => __( 'Footer, about Kaam Ase', 'kaamase' ),
				'legal'   => __( 'Footer, legal and policy', 'kaamase' ),
			)
		);

		/*
		 * Image sizes.
		 *
		 * Names are prefixed so they never collide with a plugin. All are
		 * hard cropped, because a card grid with inconsistent image heights
		 * looks broken and causes layout shift while photos load.
		 */

		// Worker and employer avatar in list and card views.
		add_image_size( 'kaamase-avatar', 128, 128, true );

		// Worker avatar on a profile page, and gang leader photo.
		add_image_size( 'kaamase-avatar-lg', 320, 320, true );

		// Job photo and portfolio thumbnail inside a card.
		add_image_size( 'kaamase-card', 480, 320, true );

		// Single job header, single portfolio item, wide screens.
		add_image_size( 'kaamase-wide', 960, 540, true );

		/*
		 * Content width. Used by WordPress to constrain embedded media.
		 * Matches the narrow container in the stylesheet.
		 */
		if ( ! isset( $GLOBALS['content_width'] ) ) {
			$GLOBALS['content_width'] = 720;
		}
	}
}
add_action( 'after_setup_theme', 'kaamase_setup' );


/* ==========================================================================
   2. IMAGE WEIGHT

   This section exists because of the connection, not because of taste.
   ========================================================================== */

/**
 * Stop WordPress generating image sizes this theme never uses.
 *
 * Out of the box every upload produces medium_large, 1536x1536 and
 * 2048x2048 on top of everything else. A worker uploading eight photos
 * of past jobs would generate over fifty files, none of which get served.
 * That is disk, backup weight and upload time on a slow connection for
 * nothing at all.
 *
 * @since 1.0.0
 * @param array $sizes Intermediate image sizes.
 * @return array Filtered sizes.
 */
function kaamase_remove_unused_image_sizes( $sizes ) {
	unset( $sizes['medium_large'], $sizes['1536x1536'], $sizes['2048x2048'] );

	return $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', 'kaamase_remove_unused_image_sizes' );

/**
 * Cap the stored full size image.
 *
 * WordPress scales anything over 2560 pixels. That is still far larger
 * than any screen this site will be viewed on. 1600 is generous for a
 * full width photo on a large monitor and roughly a quarter of the file
 * size of a raw phone photo.
 *
 * @since 1.0.0
 * @return int Threshold in pixels on the longest side.
 */
function kaamase_big_image_threshold() {
	return 1600;
}
add_filter( 'big_image_size_threshold', 'kaamase_big_image_threshold' );

/**
 * Lower JPEG quality.
 *
 * WordPress defaults to 82. At 74 the difference is invisible on a phone
 * screen for photographs of people and building work, and files come out
 * roughly a third smaller. On a 2G connection a third is not a detail.
 *
 * @since 1.0.0
 * @return int Quality between 1 and 100.
 */
function kaamase_jpeg_quality() {
	return 74;
}
add_filter( 'jpeg_quality', 'kaamase_jpeg_quality' );
add_filter( 'wp_editor_set_quality', 'kaamase_jpeg_quality' );

/*
 * PNG photographs, made small as JPEG.
 *
 * WordPress makes every smaller copy of an upload in the format it came
 * in, and PNG is the wrong format for a photograph. Measured on this
 * site, one photograph as a 128 pixel avatar was 33.6 KB as PNG and
 * 4.8 KB as JPEG, and 163.6 KB against 19.6 KB at 320 on a profile. Some
 * phones and croppers save photographs as PNG, and every card they
 * appeared on carried seven or eight times the weight.
 *
 * So the copies of a member's PNG are made as JPEG, with four limits:
 *
 * - Member photographs only: profile pictures and job pictures, told
 *   apart by what they are attached to, the same way media.php does.
 *   The site's own images are left alone.
 * - Only a picture with nothing see-through in it. JPEG has no
 *   transparency, and WordPress would fill a logo's clear background
 *   with black. A logo that really is see-through keeps PNG copies,
 *   which for a logo are small anyway.
 * - Only the copies. The uploaded file keeps its format, because
 *   security.php re-saves it in place to strip location data.
 *   WordPress gives that save a file name and gives the copies none,
 *   which is how the two are told apart below.
 * - JPEG, not WebP. These are the pictures a shared job or profile
 *   shows, and WhatsApp does not reliably show WebP in a link preview.
 *
 * Photos uploaded before this keep their PNG copies until regenerated.
 */

if ( ! function_exists( 'kaamase_png_is_opaque' ) ) {
	/**
	 * Whether a PNG file has nothing see-through in it.
	 *
	 * The header settles most files without opening the picture: saved
	 * with neither an alpha channel nor a transparency chunk, a PNG
	 * cannot have a clear pixel. One with an alpha channel is opened and
	 * looked at, because phones and croppers routinely save an alpha
	 * channel that is solid everywhere. A grid across the picture and
	 * every pixel of its four edges, where a logo's clear background is.
	 *
	 * Anything that cannot be read counts as see-through, which leaves
	 * the file exactly as it would have been.
	 *
	 * @since 1.2.0
	 * @param string $file Path to the PNG.
	 * @return bool
	 */
	function kaamase_png_is_opaque( $file ) {

		$handle = ( is_string( $file ) && is_readable( $file ) ) ? fopen( $file, 'rb' ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading a header, not writing.

		if ( ! $handle ) {
			return false;
		}

		$head = (string) fread( $handle, 33 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		$trns = false;

		if ( strlen( $head ) < 33 || "\x89PNG\r\n\x1a\n" !== substr( $head, 0, 8 ) || 'IHDR' !== substr( $head, 12, 4 ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return false;
		}

		$color = ord( $head[25] );

		// A transparency chunk, if there is one, comes before the picture data.
		while ( ! feof( $handle ) ) {

			$chunk = (string) fread( $handle, 8 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread

			if ( 8 !== strlen( $chunk ) ) {
				break;
			}

			$type = substr( $chunk, 4, 4 );

			if ( 'tRNS' === $type ) {
				$trns = true;
				break;
			}

			if ( 'IDAT' === $type || 'IEND' === $type ) {
				break;
			}

			$length = unpack( 'N', substr( $chunk, 0, 4 ) );

			if ( 0 !== fseek( $handle, (int) $length[1] + 4, SEEK_CUR ) ) {
				break;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		// A transparency chunk is only ever there to make something clear.
		if ( $trns ) {
			return false;
		}

		// Grey, colour or palette with no alpha channel: solid by definition.
		if ( in_array( $color, array( 0, 2, 3 ), true ) ) {
			return true;
		}

		if ( ! function_exists( 'imagecreatefrompng' ) ) {
			return false;
		}

		$size = wp_getimagesize( $file );

		// Too big to open safely on a shared server. Left as PNG.
		if ( empty( $size[0] ) || empty( $size[1] ) || $size[0] * $size[1] > 25000000 ) {
			return false;
		}

		wp_raise_memory_limit( 'image' );

		$image = @imagecreatefrompng( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- An unreadable file is answered below.

		if ( ! $image ) {
			return false;
		}

		if ( ! imageistruecolor( $image ) ) {
			imagepalettetotruecolor( $image );
		}

		$width  = imagesx( $image );
		$height = imagesy( $image );
		$step   = max( 1, (int) floor( min( $width, $height ) / 200 ) );
		$solid  = true;

		$clear = static function ( $x, $y ) use ( $image ) {
			return 0 !== ( ( imagecolorat( $image, $x, $y ) >> 24 ) & 0x7F );
		};

		for ( $x = 0; $x < $width && $solid; $x++ ) {
			$solid = ! $clear( $x, 0 ) && ! $clear( $x, $height - 1 );
		}

		for ( $y = 0; $y < $height && $solid; $y++ ) {
			$solid = ! $clear( 0, $y ) && ! $clear( $width - 1, $y );
		}

		for ( $y = 0; $y < $height && $solid; $y += $step ) {
			for ( $x = 0; $x < $width; $x += $step ) {
				if ( $clear( $x, $y ) ) {
					$solid = false;
					break;
				}
			}
		}

		unset( $image );

		return $solid;
	}
}

if ( ! function_exists( 'kaamase_png_copies_as_jpeg' ) ) {
	/**
	 * Whether the picture being cut into copies right now should get
	 * JPEG copies. Set just before the copies are made, cleared after.
	 *
	 * @since 1.2.0
	 * @param bool|null $set New value, or null to only read it.
	 * @return bool
	 */
	function kaamase_png_copies_as_jpeg( $set = null ) {

		static $on = false;

		if ( null !== $set ) {
			$on = (bool) $set;
		}

		return $on;
	}
}

if ( ! function_exists( 'kaamase_png_copies_plan' ) ) {
	/**
	 * Decide, as WordPress starts on a picture's copies.
	 *
	 * @since 1.2.0
	 * @param array $sizes         Sizes about to be made. Returned untouched.
	 * @param array $metadata      Attachment metadata. Unused.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	function kaamase_png_copies_plan( $sizes, $metadata = array(), $attachment_id = 0 ) {

		unset( $metadata );

		kaamase_png_copies_as_jpeg( false );

		if ( ! $attachment_id
			|| 'image/png' !== get_post_mime_type( $attachment_id )
			|| ! function_exists( 'kaamase_attachment_parent_type' )
			|| ! function_exists( 'kaamase_user_media_parent_types' )
			|| ! in_array( kaamase_attachment_parent_type( $attachment_id ), kaamase_user_media_parent_types(), true ) ) {
			return $sizes;
		}

		kaamase_png_copies_as_jpeg( kaamase_png_is_opaque( get_attached_file( $attachment_id ) ) );

		return $sizes;
	}
}
add_filter( 'intermediate_image_sizes_advanced', 'kaamase_png_copies_plan', 30, 3 );

if ( ! function_exists( 'kaamase_png_copies_format' ) ) {
	/**
	 * Make the copies JPEG when the plan says so.
	 *
	 * Only a save with no file name, which is a copy. The metadata strip
	 * in security.php names its file, so it is never touched. If the
	 * server cannot write JPEG, WordPress ignores this and keeps PNG.
	 *
	 * @since 1.2.0
	 * @param string[]    $formats   Source mime type mapped to output mime type.
	 * @param string|null $filename  File being saved, or null for a copy.
	 * @param string|null $mime_type Mime type of the picture.
	 * @return string[]
	 */
	function kaamase_png_copies_format( $formats, $filename = null, $mime_type = null ) {

		if ( null === $filename && 'image/png' === $mime_type && kaamase_png_copies_as_jpeg() ) {
			$formats['image/png'] = 'image/jpeg';
		}

		return $formats;
	}
}
add_filter( 'image_editor_output_format', 'kaamase_png_copies_format', 10, 3 );

if ( ! function_exists( 'kaamase_png_copies_done' ) ) {
	/**
	 * Clear the plan once the copies are made.
	 *
	 * @since 1.2.0
	 * @param array $metadata Attachment metadata, returned untouched.
	 * @return array
	 */
	function kaamase_png_copies_done( $metadata ) {

		kaamase_png_copies_as_jpeg( false );

		return $metadata;
	}
}
add_filter( 'wp_generate_attachment_metadata', 'kaamase_png_copies_done', 1 );

/**
 * Expose the theme image sizes in the media picker.
 *
 * Without this the sizes exist but are invisible when inserting an image
 * into a page, so an editor picks Full every time and ships a 1600 pixel
 * photo into a 480 pixel slot.
 *
 * @since 1.0.0
 * @param array $sizes Sizes shown in the media modal.
 * @return array Filtered sizes.
 */
function kaamase_media_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'kaamase-avatar'    => __( 'Kaam Ase avatar, small', 'kaamase' ),
			'kaamase-avatar-lg' => __( 'Kaam Ase avatar, large', 'kaamase' ),
			'kaamase-card'      => __( 'Kaam Ase card', 'kaamase' ),
			'kaamase-wide'      => __( 'Kaam Ase wide', 'kaamase' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'kaamase_media_size_names' );

/**
 * Give every thumbnail a decoding hint.
 *
 * Tells the browser it may decode the image off the main thread, which
 * keeps a long list of worker cards scrolling smoothly on a cheap phone
 * rather than stuttering as each photo arrives.
 *
 * @since 1.0.0
 * @param array $attr Image attributes.
 * @return array Filtered attributes.
 */
function kaamase_image_decoding( $attr ) {
	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'kaamase_image_decoding' );


/* ==========================================================================
   3. BODY CLASSES
   ========================================================================== */

/**
 * Add theme classes to the body element.
 *
 * has-tabbar is what reserves space at the bottom of the page so the
 * fixed navigation bar never covers the last card in a list. Without it
 * the final worker in every search result is unreachable on a phone.
 *
 * @since 1.0.0
 * @param string[] $classes Existing body classes.
 * @return string[] Filtered classes.
 */
function kaamase_body_classes( $classes ) {

	if ( kaamase_is_app_screen() ) {
		$classes[] = 'has-tabbar';
		$classes[] = 'is-app';
	}

	if ( is_user_logged_in() ) {
		$classes[] = 'is-signed-in';
	} else {
		$classes[] = 'is-signed-out';
	}

	// Useful hook for styling a page that has no results yet.
	if ( ! is_singular() && ! have_posts() ) {
		$classes[] = 'is-empty';
	}

	if ( ! KAAMASE_HAS_CORE ) {
		$classes[] = 'no-core';
	}

	return $classes;
}
add_filter( 'body_class', 'kaamase_body_classes' );


/* ==========================================================================
   4. EXCERPTS
   ========================================================================== */

/**
 * Shorten excerpts.
 *
 * Job descriptions in a card need to be scannable at a glance while
 * standing at a work site, not read as prose.
 *
 * @since 1.0.0
 * @return int Word count.
 */
function kaamase_excerpt_length() {
	return 22;
}
add_filter( 'excerpt_length', 'kaamase_excerpt_length' );

/**
 * Replace the excerpt ellipsis.
 *
 * @since 1.0.0
 * @return string
 */
function kaamase_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'kaamase_excerpt_more' );


/* ==========================================================================
   5. MENU FALLBACK

   A brand new install has no menus assigned. Without a fallback the
   header renders empty and looks broken on the very first page load,
   which is exactly when you are showing it to someone.
   ========================================================================== */

if ( ! function_exists( 'kaamase_hiring_menu_item' ) ) {
	/**
	 * Put Who is hiring into an assigned header or drawer menu.
	 *
	 * The fallback below already carries the link, but the fallback only
	 * runs while no menu is assigned. The moment somebody assigns one in
	 * Appearance then Menus, the fallback stops and every link has to be
	 * added by hand -- including this one, on a site whose menus have
	 * always looked after themselves.
	 *
	 * So it is appended here as well. The two cannot both fire: this
	 * filter is only reached through wp_nav_menu, which only runs when a
	 * menu IS assigned.
	 *
	 * Header and drawer only. The footer builds its own lists and never
	 * reaches either path.
	 *
	 * @since 1.4.1
	 * @param string   $items The menu HTML so far.
	 * @param stdClass $args  wp_nav_menu arguments.
	 * @return string
	 */
	function kaamase_hiring_menu_item( $items, $args ) {

		if ( ! is_user_logged_in() ) {
			return $items;
		}

		$location = isset( $args->theme_location ) ? (string) $args->theme_location : '';

		if ( ! in_array( $location, array( 'primary', 'mobile' ), true ) ) {
			return $items;
		}

		$url = home_url( '/employers/' );

		// Already added by hand. Adding it twice is worse than not adding it.
		if ( false !== strpos( $items, esc_url( $url ) ) ) {
			return $items;
		}

		$stack = ( isset( $args->walker_style ) && 'stack' === $args->walker_style );

		return $items . sprintf(
			'<li class="ka-nav__item"><a class="%1$s" href="%2$s"><span class="ka-nav__text">%3$s</span></a></li>',
			esc_attr( $stack ? 'ka-nav__link ka-nav__link--stack' : 'ka-nav__link' ),
			esc_url( $url ),
			esc_html__( 'Who is hiring', 'kaamase' )
		);
	}
}
add_filter( 'wp_nav_menu_items', 'kaamase_hiring_menu_item', 10, 2 );

if ( ! function_exists( 'kaamase_menu_fallback' ) ) {
	/**
	 * Render a minimal menu when no menu is assigned to a location.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_menu_fallback() {
		$links = array(
			home_url( '/workers/' )  => __( 'Find workers', 'kaamase' ),
			home_url( '/jobs/' )     => __( 'Find work', 'kaamase' ),
			home_url( '/post-job/' ) => __( 'Post a job', 'kaamase' ),
		);

		/*
		 * Who is hiring, for signed in people only.
		 *
		 * The page refuses politely on its own, so linking it for
		 * everybody would work. It is left out for signed out visitors
		 * because a header link that always ends in a sign in wall
		 * teaches people the header is not worth reading.
		 */
		if ( is_user_logged_in() ) {
			$links[ home_url( '/employers/' ) ] = __( 'Who is hiring', 'kaamase' );
		}

		/*
		 * The paid plan, when there is one.
		 *
		 * Asked of the payment plugin rather than written in as a URL,
		 * so the link disappears by itself if that plugin is ever turned
		 * off. A header link to a page that no longer exists is the kind
		 * of fault nobody notices for weeks, because the person who
		 * would notice is the one who never clicks it.
		 *
		 * The name comes from the plugin too. It has already been
		 * changed once, and a copy of it hardcoded here would be the one
		 * that got missed.
		 */
		if ( function_exists( 'kaamase_pay_plans_url' ) ) {

			$plans = kaamase_pay_plans_url();

			if ( $plans ) {

				$label = function_exists( 'kaamase_plan_name' )
					? kaamase_plan_name()
					: __( 'Plans', 'kaamase' );

				$links[ $plans ] = $label;
			}
		}

		echo '<ul class="ka-nav__list">';

		foreach ( $links as $url => $label ) {
			printf(
				'<li><a class="ka-nav__link" href="%1$s">%2$s</a></li>',
				esc_url( $url ),
				esc_html( $label )
			);
		}

		if ( current_user_can( 'edit_theme_options' ) ) {
			printf(
				'<li><a class="ka-nav__link" href="%1$s">%2$s</a></li>',
				esc_url( admin_url( 'nav-menus.php' ) ),
				esc_html__( 'Set up menu', 'kaamase' )
			);
		}

		echo '</ul>';
	}
}