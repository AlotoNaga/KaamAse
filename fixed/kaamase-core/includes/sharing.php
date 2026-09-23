<?php
/**
 * Share cards.
 *
 * The picture, headline and line of text that appear when somebody
 * pastes a link into WhatsApp, Facebook or anywhere else.
 *
 * Why this file exists
 * --------------------
 * The site had none of these tags at all. A job pasted into a WhatsApp
 * group showed a bare blue link, and a bare link in a group of two
 * hundred workers is a link almost nobody taps.
 *
 * That matters more here than on most sites. Sharing into groups is not
 * a nice extra for this platform, it is the main way a job in Nagaland
 * actually reaches the people who could do it. The job page can be
 * perfect and it will still lose to a screenshot somebody posted
 * instead, unless the link carries its own picture.
 *
 * The fallback is the point
 * -------------------------
 * A job with no picture must not share as a blank. It falls back to the
 * site logo, so every link that leaves this platform carries the Kaam
 * Ase mark. Over a year of group chats that is worth more than any
 * single job post.
 *
 * Standing aside
 * --------------
 * If a search plugin is running, it already writes these tags and two
 * sets in one page is worse than either. This file checks for the common
 * ones and writes nothing when it finds them.
 *
 * The WhatsApp button
 * -------------------
 * Section 5 puts a Share on WhatsApp button on every open job and
 * published profile, owners included, since the person most likely to
 * share a job is whoever posted it. The button only writes the name and
 * the link; the picture in the chat comes from the tags above.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHETHER TO WRITE ANYTHING
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_tags_wanted' ) ) {
	/**
	 * Whether this site needs us to write share tags.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_share_tags_wanted() {

		$taken = defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| class_exists( 'WPSEO_Options' );

		/**
		 * Filter whether Kaam Ase writes the share tags.
		 *
		 * @since 1.0.0
		 * @param bool $wanted Whether to write them.
		 */
		return (bool) apply_filters( 'kaamase_share_tags', ! $taken );
	}
}


/* ==========================================================================
   2. THE PICTURE
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_fallback_image' ) ) {
	/**
	 * The picture to use when the page has none of its own.
	 *
	 * The logo first, then the site icon. Both are already set on most
	 * sites, which means this needs no new setting to configure and no
	 * screen to forget to fill in.
	 *
	 * @since 1.0.0
	 * @return string URL, or an empty string.
	 */
	function kaamase_share_fallback_image() {

		$logo = (int) get_theme_mod( 'custom_logo' );

		if ( $logo ) {

			$src = wp_get_attachment_image_src( $logo, 'full' );

			if ( ! empty( $src[0] ) ) {
				return (string) $src[0];
			}
		}

		$icon = (int) get_option( 'site_icon' );

		if ( $icon ) {

			$src = wp_get_attachment_image_src( $icon, 'full' );

			if ( ! empty( $src[0] ) ) {
				return (string) $src[0];
			}
		}

		/**
		 * Filter the picture used when a page has none.
		 *
		 * @since 1.0.0
		 * @param string $url Image URL.
		 */
		return (string) apply_filters( 'kaamase_share_fallback_image', '' );
	}
}

if ( ! function_exists( 'kaamase_share_image' ) ) {
	/**
	 * The picture for one page.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID, or 0 for a page with no post behind it.
	 * @return array Width, height and URL. Width and height may be 0.
	 */
	function kaamase_share_image( $post_id ) {

		$post_id = (int) $post_id;

		if ( $post_id && has_post_thumbnail( $post_id ) ) {

			$id = get_post_thumbnail_id( $post_id );

			/*
			 * Which picture, by what kind of page it is.
			 *
			 * A job's first photograph is cut to kaamase-wide, 960 by 540,
			 * which WhatsApp shows as the big picture across the top of the
			 * preview. A profile photograph is never cut to that size:
			 * media.php skips it, because a face is square. Asking for it
			 * anyway does not fail, it quietly hands back the untouched
			 * original, up to 1600 pixels and often too heavy a file for
			 * WhatsApp to show at all. So a profile asks for its own square
			 * 320 instead, which WhatsApp shows as the small picture beside
			 * the name.
			 */
			$types = function_exists( 'kaamase_avatar_parent_types' )
				? kaamase_avatar_parent_types()
				: array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' );

			$sizes = in_array( get_post_type( $post_id ), $types, true )
				? array( 'kaamase-avatar-lg', 'medium', 'large' )
				: array( 'kaamase-wide', 'large', 'medium' );

			/*
			 * Only a size that was really made as its own file, which is
			 * what the fourth value says. Anything else is the original
			 * wearing a smaller name.
			 */
			foreach ( $sizes as $size ) {

				$src = wp_get_attachment_image_src( $id, $size );

				if ( ! empty( $src[0] ) && ! empty( $src[3] ) && (int) $src[1] >= 200 ) {
					return array(
						'url'    => (string) $src[0],
						'width'  => (int) $src[1],
						'height' => (int) $src[2],
					);
				}
			}

			/*
			 * No smaller copy was ever made, which happens when the upload
			 * was already small. Then the original is small too, and is the
			 * picture.
			 */
			$src = wp_get_attachment_image_src( $id, 'full' );

			if ( ! empty( $src[0] ) && (int) $src[1] >= 200 ) {
				return array(
					'url'    => (string) $src[0],
					'width'  => (int) $src[1],
					'height' => (int) $src[2],
				);
			}
		}

		return array(
			'url'    => kaamase_share_fallback_image(),
			'width'  => 0,
			'height' => 0,
		);
	}
}


/* ==========================================================================
   3. THE WORDS
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_description' ) ) {
	/**
	 * The line of text under the headline.
	 *
	 * For a job this is built rather than taken from the description,
	 * because the facts a worker decides on are the trade, the place and
	 * the rate, and those are rarely the first words an employer types.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function kaamase_share_description( $post_id ) {

		$post = get_post( $post_id );

		if ( $post && 'kaamase_job' === $post->post_type && function_exists( 'kaamase_read_field' ) ) {

			$bits = array();

			$town     = (string) kaamase_read_field( $post_id, 'town' );
			$district = function_exists( 'kaamase_district_name' )
				? (string) kaamase_district_name( (string) kaamase_read_field( $post_id, 'district' ) )
				: '';

			$where = $town && $district ? $town . ', ' . $district : ( $town ? $town : $district );

			if ( $where ) {
				$bits[] = $where;
			}

			$pay  = absint( kaamase_read_field( $post_id, 'pay_amount' ) );
			$unit = (string) kaamase_read_field( $post_id, 'pay_unit' );

			if ( $pay ) {

				$units = array(
					'day'   => __( 'Rupees %s a day', 'kaamase-core' ),
					'month' => __( 'Rupees %s a month', 'kaamase-core' ),
					'hour'  => __( 'Rupees %s an hour', 'kaamase-core' ),
					'job'   => __( 'Rupees %s for the job', 'kaamase-core' ),
				);

				/* translators: %s: amount in rupees */
				$pattern = isset( $units[ $unit ] ) ? $units[ $unit ] : __( 'Rupees %s', 'kaamase-core' );

				$bits[] = sprintf( $pattern, number_format_i18n( $pay ) );
			}

			$text = wp_strip_all_tags( (string) $post->post_content );

			if ( $text ) {
				$bits[] = wp_trim_words( $text, 20, '' );
			}

			if ( $bits ) {
				return implode( '. ', $bits );
			}
		}

		if ( $post ) {

			$text = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
			$text = wp_strip_all_tags( strip_shortcodes( (string) $text ) );

			if ( $text ) {
				return wp_trim_words( $text, 30, '' );
			}
		}

		return (string) get_bloginfo( 'description' );
	}
}


/* ==========================================================================
   4. WRITING THEM
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_tag' ) ) {
	/**
	 * One meta tag.
	 *
	 * @since 1.0.0
	 * @param string $attribute property or name.
	 * @param string $key       Tag key.
	 * @param string $value     Tag value.
	 * @return void
	 */
	function kaamase_share_tag( $attribute, $key, $value ) {

		if ( '' === trim( (string) $value ) ) {
			return;
		}

		printf(
			'<meta %1$s="%2$s" content="%3$s">' . "\n",
			esc_attr( $attribute ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}
}

if ( ! function_exists( 'kaamase_print_share_tags' ) ) {
	/**
	 * Write the share tags into the head.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_print_share_tags() {

		if ( ! kaamase_share_tags_wanted() ) {
			return;
		}

		$post_id = 0;
		$title   = '';
		$url     = '';

		if ( is_singular() ) {

			$post_id = (int) get_queried_object_id();
			$title   = wp_strip_all_tags( get_the_title( $post_id ) );
			$url     = (string) get_permalink( $post_id );

		} elseif ( is_front_page() || is_home() ) {

			$title = wp_strip_all_tags( (string) get_bloginfo( 'name' ) );
			$url   = (string) home_url( '/' );

		} else {

			return;
		}

		$image = kaamase_share_image( $post_id );

		echo "\n<!-- Kaam Ase share card -->\n";

		kaamase_share_tag( 'property', 'og:site_name', (string) get_bloginfo( 'name' ) );
		kaamase_share_tag( 'property', 'og:type', $post_id ? 'article' : 'website' );
		kaamase_share_tag( 'property', 'og:title', $title );
		kaamase_share_tag( 'property', 'og:description', kaamase_share_description( $post_id ) );
		kaamase_share_tag( 'property', 'og:url', $url );
		kaamase_share_tag( 'property', 'og:locale', (string) get_locale() );

		if ( $image['url'] ) {

			kaamase_share_tag( 'property', 'og:image', $image['url'] );

			if ( $image['width'] && $image['height'] ) {
				kaamase_share_tag( 'property', 'og:image:width', (string) $image['width'] );
				kaamase_share_tag( 'property', 'og:image:height', (string) $image['height'] );
			}
		}

		/*
		 * The large card is only claimed when the picture is really
		 * wide enough for one. A summary_large_image tag on a small
		 * logo renders as a stretched blur, which looks worse than the
		 * small card it replaced.
		 */
		kaamase_share_tag(
			'name',
			'twitter:card',
			( $image['url'] && $image['width'] >= 600 ) ? 'summary_large_image' : 'summary'
		);

		kaamase_share_tag( 'name', 'twitter:title', $title );
		kaamase_share_tag( 'name', 'twitter:description', kaamase_share_description( $post_id ) );
		kaamase_share_tag( 'name', 'twitter:image', $image['url'] );

		echo "<!-- /Kaam Ase share card -->\n\n";
	}
}
add_action( 'wp_head', 'kaamase_print_share_tags', 5 );

/* ==========================================================================
   5. THE WHATSAPP BUTTON

   The tags above decide what a link looks like once it is in a chat. This
   puts the link there in one tap, so nobody has to copy an address out of
   a browser bar on a phone to do it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_whatsapp_shareable' ) ) {
	/**
	 * Whether a page is worth sending to somebody.
	 *
	 * Only what a stranger opening the link can actually see: a published
	 * profile, and a job that is still open. A draft profile shares as a
	 * page nobody else can open, and a closed job sends people to work
	 * that has gone.
	 *
	 * @since 1.1.0
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	function kaamase_whatsapp_shareable( $post_id ) {

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! in_array( $post->post_type, array( 'kaamase_job', 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' ), true ) ) {
			return false;
		}

		if ( 'kaamase_job' === $post->post_type && function_exists( 'kaamase_job_is_open' ) && ! kaamase_job_is_open( $post->ID ) ) {
			return false;
		}

		/**
		 * Filter whether a page gets the WhatsApp button.
		 *
		 * @since 1.1.0
		 * @param bool    $shareable Whether to show it.
		 * @param WP_Post $post      The page.
		 */
		return (bool) apply_filters( 'kaamase_whatsapp_shareable', true, $post );
	}
}

if ( ! function_exists( 'kaamase_whatsapp_share_url' ) ) {
	/**
	 * The wa.me address that opens WhatsApp with the message written.
	 *
	 * The message is the page's name and its address, nothing more.
	 * WhatsApp reads the share card from the address and adds the picture,
	 * the place and the pay underneath by itself; writing them into the
	 * message as well would print everything twice.
	 *
	 * wa.me rather than whatsapp://, because wa.me also works from a
	 * computer, where it opens WhatsApp Web or the desktop app.
	 *
	 * @since 1.1.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function kaamase_whatsapp_share_url( $post_id ) {

		$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' );
		$text  = trim( $title . "\n" . get_permalink( $post_id ) );

		return 'https://wa.me/?text=' . rawurlencode( $text );
	}
}

if ( ! function_exists( 'kaamase_whatsapp_share_button' ) ) {
	/**
	 * The button.
	 *
	 * The theme's outline button with WhatsApp's mark in its own green. A
	 * solid WhatsApp-green button would be the most recognisable, but white
	 * on that green is too faint to read in sunlight, and this is read on
	 * phones outdoors.
	 *
	 * @since 1.1.0
	 * @param int $post_id Post ID.
	 * @return string Markup, or an empty string.
	 */
	function kaamase_whatsapp_share_button( $post_id ) {

		if ( ! kaamase_whatsapp_shareable( $post_id ) ) {
			return '';
		}

		return sprintf(
			'<a class="ka-btn ka-btn--outline ka-share-wa" href="%1$s" target="_blank" rel="noopener">'
				. '<svg aria-hidden="true" focusable="false" width="20" height="20" viewBox="0 0 24 24">'
				. '<path fill="#25D366" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/>'
				. '<path fill="#fff" d="M17.3 14.4c-.3-.1-1.7-.8-1.9-.9-.3-.1-.5-.1-.7.1-.2.3-.7.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.8-.8-1.4-1.7-1.6-2-.2-.3 0-.4.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.1c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.1 4.9 4.3 2.4 1 2.9.8 3.4.7.5-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3z"/>'
				. '</svg><span>%2$s</span></a>',
			esc_url( kaamase_whatsapp_share_url( $post_id ) ),
			esc_html__( 'Share on WhatsApp', 'kaamase-core' )
		);
	}
}

if ( ! function_exists( 'kaamase_append_whatsapp_share' ) ) {
	/**
	 * Put the button on job and profile pages, under the save button.
	 *
	 * For everybody, owners included. The person most likely to share a
	 * job is whoever posted it, into the groups where the workers they
	 * want already are, and a worker sharing their own profile is how a
	 * profile gets seen by somebody who has never heard of Kaam Ase.
	 *
	 * @since 1.1.0
	 * @param string $content Post content.
	 * @return string
	 */
	function kaamase_append_whatsapp_share( $content ) {

		if ( ! is_singular( array( 'kaamase_job', 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' ) ) || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		$button = kaamase_whatsapp_share_button( get_the_ID() );

		return '' === $button ? $content : $content . '<div class="ka-mt-4">' . $button . '</div>';
	}
}
add_filter( 'the_content', 'kaamase_append_whatsapp_share', 21 );
