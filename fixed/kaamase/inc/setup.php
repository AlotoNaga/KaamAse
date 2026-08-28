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
 * @version 1.1.0
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