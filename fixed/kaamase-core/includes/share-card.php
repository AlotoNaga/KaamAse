<?php
/**
 * Share pictures.
 *
 * The picture WhatsApp shows when somebody pastes a job, worker, team or
 * employer link into a chat.
 *
 * Why this exists
 * ---------------
 * The live site runs Rank Math, and Rank Math writes the share tags. It
 * uses a page's own photograph when there is one and otherwise nothing,
 * and most jobs have no photograph. So a job pasted into a group arrived
 * as a bare link, in exactly the place where a link has to earn its tap.
 *
 * sharing.php has its own set of tags with a logo to fall back on, but it
 * stands aside when an SEO plugin is present, as it should: two sets of
 * tags in one page is worse than either.
 *
 * What this does
 * --------------
 * For a page with no photograph of its own it draws a 1200 by 630 card:
 * for a job the title, the pay, the place and the trade; for a person or
 * a team their name, trades, place, rate and the tick if they have it.
 * It hands the card to Rank Math through the hook Rank Math offers for
 * adding pictures, so Rank Math's own tags carry it, with its size.
 *
 * The order, first that exists wins:
 *
 *   the page's own photograph   Rank Math finds that itself
 *   the card                    this file
 *   Rank Math's default picture if one is set in its settings
 *   the site logo or icon       this file, the same one sharing.php uses
 *
 * Drawn once
 * ----------
 * The first request for the page draws it and saves it under
 * uploads/kaamase-share. The name carries a fingerprint of what is on it,
 * so when the title, pay or place changes the next visit draws a new one
 * under a new name. WhatsApp keeps pictures by address, so a new name is
 * what makes it fetch the new picture. The old file is removed, and so is
 * the card when the page is deleted or taken down.
 *
 * One language
 * ------------
 * Always drawn in the site's own language, whoever happens to ask first,
 * so a card cannot change language depending on who opened the page.
 *
 * The font is cut down to Latin letters, so a title typed in another
 * script, Hindi for instance, is drawn as the trade and the district
 * instead. The picture tool cannot join Devanagari letters correctly, and
 * a broken word is worse than a plain one. The words under the picture
 * are Rank Math's and keep the original.
 *
 * Never an error
 * --------------
 * No image library, no font, an uploads folder that cannot be written:
 * each simply means no card, and the fallback carries on.
 *
 * The font is Noto Sans, under the SIL Open Font License, which is in
 * assets/fonts/OFL.txt beside it.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.13.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KAAMASE_SHARE_CARD_DRAWING' ) ) {
	/**
	 * Which drawing of the card this is.
	 *
	 * Part of every fingerprint, so changing how a card looks and
	 * changing this redraws every card on its next visit.
	 */
	define( 'KAAMASE_SHARE_CARD_DRAWING', '1' );
}


/* ==========================================================================
   1. WHETHER A CARD CAN BE MADE
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_card_types' ) ) {
	/**
	 * The pages that get a card, and the word each file name starts with.
	 *
	 * @since 1.13.0
	 * @return array<string,string> Post type => file name prefix.
	 */
	function kaamase_share_card_types() {

		return array(
			'kaamase_job'      => 'job',
			'kaamase_worker'   => 'worker',
			'kaamase_gang'     => 'team',
			'kaamase_employer' => 'employer',
		);
	}
}

if ( ! function_exists( 'kaamase_share_card_font' ) ) {
	/**
	 * Where one of the two fonts is.
	 *
	 * @since 1.13.0
	 * @param bool $bold Bold or regular.
	 * @return string Path.
	 */
	function kaamase_share_card_font( $bold ) {

		$base = defined( 'KAAMASE_CORE_DIR' ) ? KAAMASE_CORE_DIR : trailingslashit( dirname( __DIR__ ) );

		return $base . 'assets/fonts/' . ( $bold ? 'NotoSans-Bold.ttf' : 'NotoSans-Regular.ttf' );
	}
}

if ( ! function_exists( 'kaamase_share_card_can_draw' ) ) {
	/**
	 * Whether this server can draw one.
	 *
	 * @since 1.13.0
	 * @return bool
	 */
	function kaamase_share_card_can_draw() {

		foreach ( array( 'imagecreatetruecolor', 'imagettftext', 'imagettfbbox', 'imagecopyresampled', 'imagepng', 'imagefilledellipse', 'imagefilledarc', 'imagefilledpolygon' ) as $needed ) {
			if ( ! function_exists( $needed ) ) {
				return false;
			}
		}

		return is_readable( kaamase_share_card_font( true ) ) && is_readable( kaamase_share_card_font( false ) );
	}
}

if ( ! function_exists( 'kaamase_share_card_place' ) ) {
	/**
	 * Where the cards are kept.
	 *
	 * @since 1.13.0
	 * @return array{dir: string, url: string}|array Empty when uploads cannot be used.
	 */
	function kaamase_share_card_place() {

		$uploads = wp_upload_dir( null, false );

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return array();
		}

		return array(
			'dir' => trailingslashit( $uploads['basedir'] ) . 'kaamase-share',
			'url' => trailingslashit( $uploads['baseurl'] ) . 'kaamase-share',
		);
	}
}


/* ==========================================================================
   2. WHAT GOES ON IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_card_text' ) ) {
	/**
	 * A line of text the font can draw, or the fallback.
	 *
	 * @since 1.13.0
	 * @param mixed  $text     Anything.
	 * @param string $fallback What to use when it cannot be drawn.
	 * @return string
	 */
	function kaamase_share_card_text( $text, $fallback = '' ) {

		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
		$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );

		if ( '' === $text ) {
			return (string) $fallback;
		}

		/*
		 * Latin letters, punctuation and the rupee sign: what the bundled
		 * font carries. Anything else, or text that is not valid UTF-8,
		 * gives the fallback rather than a row of empty boxes.
		 */
		if ( 1 !== preg_match( '/^[\x{0020}-\x{007E}\x{00A0}-\x{024F}\x{2010}-\x{2027}\x{2030}-\x{203A}\x{20B9}\x{2122}]+$/u', $text ) ) {
			return (string) $fallback;
		}

		return $text;
	}
}

if ( ! function_exists( 'kaamase_share_card_trades' ) ) {
	/**
	 * Up to three trade names on a job or profile.
	 *
	 * @since 1.13.0
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	function kaamase_share_card_trades( $post_id ) {

		$terms = get_the_terms( (int) $post_id, 'kaamase_trade' );

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$names = array();

		foreach ( $terms as $term ) {

			// The groups are containers, not trades.
			if ( 0 === (int) $term->parent ) {
				continue;
			}

			$name = kaamase_share_card_text( $term->name, ucfirst( str_replace( '-', ' ', (string) $term->slug ) ) );

			if ( '' !== $name && ! in_array( $name, $names, true ) ) {
				$names[] = $name;
			}
		}

		return array_slice( $names, 0, 3 );
	}
}

if ( ! function_exists( 'kaamase_share_card_money' ) ) {
	/**
	 * A rate, as the site writes it: the figure, and the period apart.
	 *
	 * @since 1.13.0
	 * @param int    $amount Rupees.
	 * @param string $unit   day, month, hour or job.
	 * @return array{amount: string, unit: string}|array Empty for no rate.
	 */
	function kaamase_share_card_money( $amount, $unit ) {

		$amount = absint( $amount );

		if ( ! $amount ) {
			return array();
		}

		$units = array(
			'day'   => __( 'per day', 'kaamase-core' ),
			'month' => __( 'per month', 'kaamase-core' ),
			'hour'  => __( 'per hour', 'kaamase-core' ),
			'job'   => __( 'for the job', 'kaamase-core' ),
		);

		return array(
			'amount' => "\u{20B9}" . number_format( $amount ),
			'unit'   => kaamase_share_card_text( $units[ $unit ] ?? $units['day'], 'per day' ),
		);
	}
}

if ( ! function_exists( 'kaamase_share_card_facts' ) ) {
	/**
	 * Everything that will be on one card.
	 *
	 * Also what the fingerprint is taken from, so anything drawn has to
	 * come from here and nowhere else.
	 *
	 * @since 1.13.0
	 * @param WP_Post $post Job or profile.
	 * @return array
	 */
	function kaamase_share_card_facts( $post ) {

		$id     = (int) $post->ID;
		$trades = kaamase_share_card_trades( $id );

		$district = kaamase_share_card_text(
			function_exists( 'kaamase_district_name' ) ? kaamase_district_name( (string) kaamase_read_field( $id, 'district' ) ) : ''
		);
		$town     = kaamase_share_card_text( kaamase_read_field( $id, 'town' ) );

		if ( $town && $district && 0 !== strcasecmp( $town, $district ) ) {
			$place = $town . ', ' . $district;
		} else {
			$place = $district ? $district : $town;
		}

		$facts = array(
			'kind'     => 'job',
			'title'    => '',
			'initials' => '',
			'chips'    => array(),
			'urgent'   => false,
			'verified' => false,
			'money'    => array(),
			'place'    => $place,
			'extra'    => '',
			'foot'     => kaamase_share_card_text( __( 'Jobs and workers across Nagaland', 'kaamase-core' ), 'Jobs and workers across Nagaland' ),
		);

		$raw_title = (string) get_post_field( 'post_title', $id, 'raw' );

		if ( 'kaamase_job' === $post->post_type ) {

			$trade = $trades ? $trades[0] : '';

			if ( $trade && $district ) {
				/* translators: 1: a trade, 2: a district */
				$instead = sprintf( __( '%1$s in %2$s', 'kaamase-core' ), $trade, $district );
			} else {
				$instead = $trade ? $trade : __( 'Work on Kaam Ase', 'kaamase-core' );
			}

			$facts['title']  = kaamase_share_card_text( $raw_title, kaamase_share_card_text( $instead, 'Work on Kaam Ase' ) );
			$facts['chips']  = $trade ? array( $trade ) : array();
			$facts['urgent'] = (bool) kaamase_read_field( $id, 'urgent' );
			$facts['money']  = kaamase_share_card_money( kaamase_read_field( $id, 'pay_amount' ), (string) kaamase_read_field( $id, 'pay_unit' ) );

			return $facts;
		}

		$called = function_exists( 'kaamase_has_been_called' ) && kaamase_has_been_called( (int) $post->post_author );

		$facts['kind']     = 'profile';
		$facts['verified'] = $called;

		if ( 'kaamase_employer' === $post->post_type ) {

			$types = array(
				'individual' => __( 'Individual', 'kaamase-core' ),
				'contractor' => __( 'Contractor', 'kaamase-core' ),
				'company'    => __( 'Company', 'kaamase-core' ),
			);

			$type = (string) kaamase_read_field( $id, 'employer_type' );

			$facts['title'] = kaamase_share_card_text( $raw_title, kaamase_share_card_text( __( 'Hiring on Kaam Ase', 'kaamase-core' ), 'Hiring on Kaam Ase' ) );
			$facts['chips'] = isset( $types[ $type ] ) ? array( kaamase_share_card_text( $types[ $type ], ucfirst( $type ) ) ) : array();
			$facts['extra'] = kaamase_share_card_text( __( 'Hiring on Kaam Ase', 'kaamase-core' ), 'Hiring on Kaam Ase' );

		} else {

			$team = 'kaamase_gang' === $post->post_type;

			$facts['title'] = kaamase_share_card_text(
				$raw_title,
				$team
					? kaamase_share_card_text( __( 'A team on Kaam Ase', 'kaamase-core' ), 'A team on Kaam Ase' )
					: kaamase_share_card_text( __( 'A worker on Kaam Ase', 'kaamase-core' ), 'A worker on Kaam Ase' )
			);

			$facts['chips'] = $team
				? array_merge( array( kaamase_share_card_text( __( 'Team', 'kaamase-core' ), 'Team' ) ), array_slice( $trades, 0, 2 ) )
				: $trades;

			$facts['money'] = kaamase_share_card_money( kaamase_read_field( $id, 'day_rate' ), 'day' );

			if ( ! $facts['money'] ) {
				$facts['money'] = kaamase_share_card_money( kaamase_read_field( $id, 'month_rate' ), 'month' );
			}

			$years = absint( kaamase_read_field( $id, 'years_experience' ) );

			if ( $years ) {
				$facts['extra'] = kaamase_share_card_text(
					sprintf(
						/* translators: %s: number of years */
						_n( '%s year of experience', '%s years of experience', $years, 'kaamase-core' ),
						number_format( $years )
					)
				);
			}
		}

		// Initials only from a name the font can draw.
		if ( kaamase_share_card_text( $raw_title ) === $raw_title && '' !== trim( $raw_title ) ) {

			$words = preg_split( '/\s+/u', trim( $raw_title ) );
			$first = mb_substr( (string) $words[0], 0, 1 );
			$last  = count( $words ) > 1 ? mb_substr( (string) end( $words ), 0, 1 ) : '';

			$facts['initials'] = mb_strtoupper( $first . $last );
		}

		return $facts;
	}
}


/* ==========================================================================
   3. DRAWING IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_card_px' ) ) {
	/**
	 * A font size in pixels, as the size the picture tool asks for.
	 *
	 * The tool takes points at 96 dots an inch.
	 *
	 * @since 1.13.0
	 * @param float $px Pixels.
	 * @return float
	 */
	function kaamase_share_card_px( $px ) {
		return $px * 0.75;
	}
}

if ( ! function_exists( 'kaamase_share_card_width' ) ) {
	/**
	 * How wide a line of text is, in pixels.
	 *
	 * @since 1.13.0
	 * @param string $text Text.
	 * @param string $font Font path.
	 * @param float  $px   Size in pixels.
	 * @return int
	 */
	function kaamase_share_card_width( $text, $font, $px ) {

		$box = imagettfbbox( kaamase_share_card_px( $px ), 0, $font, (string) $text );

		return is_array( $box ) ? (int) abs( $box[2] - $box[0] ) : 0;
	}
}

if ( ! function_exists( 'kaamase_share_card_lines' ) ) {
	/**
	 * Break text into lines that fit, at most so many.
	 *
	 * @since 1.13.0
	 * @param string $text  Text.
	 * @param string $font  Font path.
	 * @param float  $px    Size in pixels.
	 * @param int    $width Room, in pixels.
	 * @param int    $most  Most lines.
	 * @return array{lines: string[], cut: bool}
	 */
	function kaamase_share_card_lines( $text, $font, $px, $width, $most ) {

		$words = preg_split( '/\s+/u', trim( (string) $text ) );
		$lines = array();
		$line  = '';
		$cut   = false;

		foreach ( (array) $words as $word ) {

			// One word wider than the room is broken where it runs out.
			while ( kaamase_share_card_width( $word, $font, $px ) > $width && mb_strlen( $word ) > 1 ) {

				$fit = mb_strlen( $word ) - 1;

				while ( $fit > 1 && kaamase_share_card_width( mb_substr( $word, 0, $fit ), $font, $px ) > $width ) {
					--$fit;
				}

				if ( '' !== $line ) {
					$lines[] = $line;
					$line    = '';
				}

				$lines[] = mb_substr( $word, 0, $fit );
				$word    = mb_substr( $word, $fit );
			}

			$try = '' === $line ? $word : $line . ' ' . $word;

			if ( '' === $line || kaamase_share_card_width( $try, $font, $px ) <= $width ) {
				$line = $try;
			} else {
				$lines[] = $line;
				$line    = $word;
			}
		}

		if ( '' !== $line ) {
			$lines[] = $line;
		}

		if ( count( $lines ) > $most ) {

			$cut   = true;
			$lines = array_slice( $lines, 0, $most );

			/*
			 * A pattern, not rtrim(). rtrim() works byte by byte, and a
			 * dash in its list would cut into any letter that happens to
			 * end in one of the same bytes.
			 */
			$last = (string) preg_replace( '/[\s,.\-\x{2013}\x{2014}]+$/u', '', $lines[ $most - 1 ] );

			while ( '' !== $last && kaamase_share_card_width( $last . "\u{2026}", $font, $px ) > $width ) {
				$last = (string) preg_replace( '/\s+$/u', '', mb_substr( $last, 0, -1 ) );
			}

			$lines[ $most - 1 ] = $last . "\u{2026}";
		}

		return array(
			'lines' => $lines,
			'cut'   => $cut,
		);
	}
}

if ( ! function_exists( 'kaamase_share_card_pill' ) ) {
	/**
	 * A rounded label. Coordinates in card pixels; the canvas is larger.
	 *
	 * @since 1.13.0
	 * @param resource|GdImage $im    Canvas.
	 * @param int              $s     Scale of the canvas.
	 * @param int              $x     Left.
	 * @param int              $y     Top.
	 * @param int              $w     Width.
	 * @param int              $h     Height.
	 * @param int              $color Fill.
	 * @return void
	 */
	function kaamase_share_card_pill( $im, $s, $x, $y, $w, $h, $color ) {

		$r = (int) floor( $h / 2 );

		imagefilledrectangle( $im, ( $x + $r ) * $s, $y * $s, ( $x + $w - $r ) * $s, ( $y + $h ) * $s - 1, $color );
		imagefilledellipse( $im, ( $x + $r ) * $s, ( $y + $r ) * $s, 2 * $r * $s, 2 * $r * $s, $color );
		imagefilledellipse( $im, ( $x + $w - $r ) * $s, ( $y + $r ) * $s, 2 * $r * $s, 2 * $r * $s, $color );
	}
}

if ( ! function_exists( 'kaamase_share_card_write' ) ) {
	/**
	 * One line of text. Coordinates in card pixels.
	 *
	 * @since 1.13.0
	 * @param resource|GdImage $im       Canvas.
	 * @param int              $s        Scale of the canvas.
	 * @param float            $px       Size in pixels.
	 * @param int              $x        Left.
	 * @param int              $baseline Baseline.
	 * @param int              $color    Colour.
	 * @param string           $font     Font path.
	 * @param string           $text     Text.
	 * @return int The width drawn, in card pixels.
	 */
	function kaamase_share_card_write( $im, $s, $px, $x, $baseline, $color, $font, $text ) {

		imagettftext( $im, kaamase_share_card_px( $px * $s ), 0, (int) ( $x * $s ), (int) ( $baseline * $s ), $color, $font, (string) $text );

		return kaamase_share_card_width( $text, $font, $px );
	}
}

if ( ! function_exists( 'kaamase_share_card_draw' ) ) {
	/**
	 * Draw a card and save it as a PNG.
	 *
	 * Drawn at twice the size and scaled down, which is the simplest way
	 * to get smooth edges on the round shapes from a library that does
	 * not smooth them itself.
	 *
	 * @since 1.13.0
	 * @param array  $facts What goes on it.
	 * @param string $file  Where to save it.
	 * @return bool Whether it was saved.
	 */
	function kaamase_share_card_draw( $facts, $file ) {

		$s    = 2;
		$bold = kaamase_share_card_font( true );
		$reg  = kaamase_share_card_font( false );

		$left  = 72;
		$right = 1128;
		$room  = $right - $left;

		/*
		 * Every size is worked out before anything is drawn, so the whole
		 * block can sit in the middle of the space between the site name
		 * and the foot, instead of hanging from the top over a gap.
		 */
		$head  = 128;
		$floor = 540;

		$chips = array();

		foreach ( (array) $facts['chips'] as $chip ) {
			if ( '' !== $chip ) {
				$chips[] = array( $chip, 'chip', 'mint' );
			}
		}

		if ( $facts['urgent'] ) {
			array_unshift( $chips, array( kaamase_share_card_text( __( 'Urgent', 'kaamase-core' ), 'Urgent' ), 'amber', 'ink' ) );
		}

		$where = $facts['place'];

		if ( '' !== $facts['extra'] ) {
			$where = '' !== $where ? $where . "  \u{00B7}  " . $facts['extra'] : $facts['extra'];
		}

		$chips_h = $chips ? 46 + 28 : 0;
		$money_h = $facts['money'] ? 70 : 0;
		$where_h = '' !== $where ? 44 : 0;

		if ( 'profile' === $facts['kind'] ) {

			$nx  = $left + 192;
			$fit = null;

			foreach ( array( 60, 52, 46, 40 ) as $px ) {

				$fit       = kaamase_share_card_lines( $facts['title'], $bold, $px, $right - $nx, 2 );
				$fit['px'] = $px;

				if ( ! $fit['cut'] ) {
					break;
				}
			}

			$lh      = (int) round( $fit['px'] * 1.18 );
			$ident_h = max( 160, 8 + count( $fit['lines'] ) * $lh + ( $facts['verified'] ? 14 + 44 : 0 ) );
			$block   = $ident_h + 26 + $chips_h + $money_h + $where_h;

		} else {

			$fit = null;

			foreach ( array( array( 68, 2 ), array( 60, 2 ), array( 54, 3 ), array( 48, 3 ), array( 42, 3 ) ) as $try ) {

				$fit       = kaamase_share_card_lines( $facts['title'], $bold, $try[0], $room, $try[1] );
				$fit['px'] = $try[0];
				$lh        = (int) round( $try[0] * 1.16 );
				$block     = $chips_h + count( $fit['lines'] ) * $lh + 12 + $money_h + $where_h;

				if ( ! $fit['cut'] && $block <= $floor - $head ) {
					break;
				}
			}
		}

		$top = $head + max( 0, (int) floor( ( $floor - $head - $block ) / 2 ) );

		$im = imagecreatetruecolor( 1200 * $s, 630 * $s );

		if ( ! $im ) {
			return false;
		}

		$hex = array(
			'bg'    => '0b4527',
			'deep'  => '06301b',
			'ring'  => '10603a',
			'chip'  => '157a4a',
			'mint'  => 'dff0e6',
			'white' => 'ffffff',
			'gold'  => 'f5a623',
			'amber' => 'e8890b',
			'ink'   => '17150f',
			'tick'  => '1f9560',
		);

		$c = array();

		foreach ( $hex as $name => $value ) {
			$c[ $name ] = imagecolorallocate( $im, hexdec( substr( $value, 0, 2 ) ), hexdec( substr( $value, 2, 2 ) ), hexdec( substr( $value, 4, 2 ) ) );
		}

		// The ground, a ring in the top corner, and the band along the foot.
		imagefilledrectangle( $im, 0, 0, 1200 * $s, 630 * $s, $c['bg'] );
		imagefilledellipse( $im, 1200 * $s, 0, 460 * $s, 460 * $s, $c['ring'] );
		imagefilledellipse( $im, 1200 * $s, 0, 290 * $s, 290 * $s, $c['bg'] );
		imagefilledrectangle( $im, 0, 556 * $s, 1200 * $s, 630 * $s, $c['deep'] );

		// The name of the site, top left.
		kaamase_share_card_write( $im, $s, 40, $left, 100, $c['white'], $bold, 'Kaam Ase' );

		// A person or a team: their initials in a circle, the name beside it.
		if ( 'profile' === $facts['kind'] ) {

			$cx = $left + 80;
			$cy = $top + 80;

			imagefilledellipse( $im, $cx * $s, $cy * $s, 160 * $s, 160 * $s, $c['mint'] );

			if ( '' !== $facts['initials'] ) {
				$iw = kaamase_share_card_width( $facts['initials'], $bold, 60 );
				kaamase_share_card_write( $im, $s, 60, (int) ( $cx - $iw / 2 ), $cy + 22, $c['bg'], $bold, $facts['initials'] );
			} else {
				// No name the font can draw: a plain figure instead, head and shoulders.
				imagefilledellipse( $im, $cx * $s, ( $cy - 20 ) * $s, 52 * $s, 52 * $s, $c['chip'] );
				imagefilledarc( $im, $cx * $s, ( $cy + 60 ) * $s, 100 * $s, 84 * $s, 180, 360, $c['chip'], IMG_ARC_PIE );
			}

			$y = $top + 8;

			foreach ( $fit['lines'] as $n => $line ) {
				kaamase_share_card_write( $im, $s, $fit['px'], $nx, $y + (int) round( $fit['px'] * 0.92 ) + $n * $lh, $c['white'], $bold, $line );
			}

			$y += count( $fit['lines'] ) * $lh + 14;

			if ( $facts['verified'] ) {

				$label = kaamase_share_card_text( __( 'Verified', 'kaamase-core' ), 'Verified' );
				$lw    = kaamase_share_card_width( $label, $bold, 24 );

				kaamase_share_card_pill( $im, $s, $nx, $y, 64 + $lw, 44, $c['tick'] );

				// The tick: a white disc with a green check drawn in it.
				imagefilledellipse( $im, ( $nx + 24 ) * $s, ( $y + 22 ) * $s, 28 * $s, 28 * $s, $c['white'] );
				imagesetthickness( $im, 4 * $s );
				imageline( $im, ( $nx + 17 ) * $s, ( $y + 22 ) * $s, ( $nx + 22 ) * $s, ( $y + 27 ) * $s, $c['tick'] );
				imageline( $im, ( $nx + 22 ) * $s, ( $y + 27 ) * $s, ( $nx + 31 ) * $s, ( $y + 17 ) * $s, $c['tick'] );
				imagesetthickness( $im, 1 );

				kaamase_share_card_write( $im, $s, 24, $nx + 46, $y + 31, $c['white'], $bold, $label );
			}

			$top += $ident_h + 26;
		}

		// The labels: Urgent, a trade, a kind of employer.
		$x = $left;

		foreach ( $chips as $chip ) {

			$cw = kaamase_share_card_width( $chip[0], $bold, 24 ) + 40;

			if ( $x + $cw > $right ) {
				break;
			}

			kaamase_share_card_pill( $im, $s, $x, $top, $cw, 46, $c[ $chip[1] ] );
			kaamase_share_card_write( $im, $s, 24, $x + 20, $top + 32, $c[ $chip[2] ], $bold, $chip[0] );

			$x += $cw + 12;
		}

		$top += $chips_h;

		// A job's title, at the size chosen above.
		if ( 'profile' !== $facts['kind'] ) {

			foreach ( $fit['lines'] as $n => $line ) {
				kaamase_share_card_write( $im, $s, $fit['px'], $left, $top + (int) round( $fit['px'] * 0.92 ) + $n * $lh, $c['white'], $bold, $line );
			}

			$top += count( $fit['lines'] ) * $lh + 12;
		}

		// The rate: the figure in gold, the period beside it.
		if ( $facts['money'] ) {

			$aw = kaamase_share_card_write( $im, $s, 54, $left, $top + 50, $c['gold'], $bold, $facts['money']['amount'] );
			kaamase_share_card_write( $im, $s, 30, $left + $aw + 14, $top + 50, $c['mint'], $reg, $facts['money']['unit'] );

			$top += $money_h;
		}

		// The place, with a pin, and anything else worth a line.
		if ( '' !== $where ) {

			$tx = $left;

			if ( '' !== $facts['place'] ) {
				imagefilledellipse( $im, ( $left + 11 ) * $s, ( $top + 12 ) * $s, 22 * $s, 22 * $s, $c['mint'] );
				imagefilledpolygon( $im, array( ( $left + 1 ) * $s, ( $top + 16 ) * $s, ( $left + 21 ) * $s, ( $top + 16 ) * $s, ( $left + 11 ) * $s, ( $top + 32 ) * $s ), $c['mint'] );
				imagefilledellipse( $im, ( $left + 11 ) * $s, ( $top + 12 ) * $s, 8 * $s, 8 * $s, $c['bg'] );
				$tx = $left + 36;
			}

			$line = kaamase_share_card_lines( $where, $reg, 30, $right - $tx, 1 );

			kaamase_share_card_write( $im, $s, 30, $tx, $top + 29, $c['mint'], $reg, $line['lines'][0] ?? '' );
		}

		// The foot: what this is, and where.
		kaamase_share_card_write( $im, $s, 24, $left, 603, $c['mint'], $reg, $facts['foot'] );

		$site = 'kaamase.com';
		$sw   = kaamase_share_card_width( $site, $bold, 24 );

		kaamase_share_card_write( $im, $s, 24, $right - $sw, 603, $c['gold'], $bold, $site );

		// Down to size.
		$out = imagecreatetruecolor( 1200, 630 );

		if ( ! $out ) {
			imagedestroy( $im );
			return false;
		}

		imagecopyresampled( $out, $im, 0, 0, 0, 0, 1200, 630, 1200 * $s, 630 * $s );
		imagedestroy( $im );

		$saved = imagepng( $out, $file, 9 );

		imagedestroy( $out );

		return (bool) $saved;
	}
}


/* ==========================================================================
   4. MAKING AND KEEPING THEM
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_card' ) ) {
	/**
	 * The card for one page, drawn if it is not there yet.
	 *
	 * @since 1.13.0
	 * @param int $post_id Job or profile.
	 * @return array{url: string, width: int, height: int, type: string}|array Empty when there is none.
	 */
	function kaamase_share_card( $post_id ) {

		$post  = get_post( (int) $post_id );
		$types = kaamase_share_card_types();

		if ( ! $post || 'publish' !== $post->post_status || ! isset( $types[ $post->post_type ] ) ) {
			return array();
		}

		if ( ! function_exists( 'kaamase_read_field' ) || ! kaamase_share_card_can_draw() ) {
			return array();
		}

		$place = kaamase_share_card_place();

		if ( ! $place ) {
			return array();
		}

		// The site's own language, whoever is asking.
		$switched = function_exists( 'kaamase_locale_default' ) && switch_to_locale( kaamase_locale_default() );

		try {
			$facts = kaamase_share_card_facts( $post );
		} finally {
			if ( $switched ) {
				restore_previous_locale();
			}
		}

		$name = $types[ $post->post_type ] . '-' . (int) $post->ID . '-'
			. substr( md5( (string) wp_json_encode( $facts ) . '|' . KAAMASE_SHARE_CARD_DRAWING ), 0, 12 ) . '.png';

		$file = $place['dir'] . '/' . $name;

		if ( ! file_exists( $file ) ) {

			if ( ! wp_mkdir_p( $place['dir'] ) ) {
				return array();
			}

			/*
			 * Drawn under a temporary name and then renamed, so a second
			 * request arriving at the same moment never finds half a
			 * picture under the real name.
			 */
			$temp = $file . '.' . wp_generate_password( 8, false, false ) . '.tmp';

			if ( ! kaamase_share_card_draw( $facts, $temp ) || ! rename( $temp, $file ) ) {

				if ( file_exists( $temp ) ) {
					wp_delete_file( $temp );
				}

				if ( ! file_exists( $file ) ) {
					return array();
				}
			}

			kaamase_share_card_forget( $post->ID, $name );
		}

		return array(
			'url'    => $place['url'] . '/' . $name,
			'width'  => 1200,
			'height' => 630,
			'type'   => 'image/png',
		);
	}
}

if ( ! function_exists( 'kaamase_share_card_forget' ) ) {
	/**
	 * Remove a page's cards, all of them or all but one.
	 *
	 * @since 1.13.0
	 * @param int    $post_id Job or profile.
	 * @param string $keep    File name to keep, if any.
	 * @return void
	 */
	function kaamase_share_card_forget( $post_id, $keep = '' ) {

		$post_id = absint( $post_id );
		$place   = kaamase_share_card_place();

		if ( ! $post_id || ! $place || ! is_dir( $place['dir'] ) ) {
			return;
		}

		foreach ( kaamase_share_card_types() as $prefix ) {

			foreach ( (array) glob( $place['dir'] . '/' . $prefix . '-' . $post_id . '-*.png' ) as $old ) {

				if ( is_string( $old ) && basename( $old ) !== $keep ) {
					wp_delete_file( $old );
				}
			}
		}
	}
}

if ( ! function_exists( 'kaamase_share_card_on_delete' ) ) {
	/**
	 * A deleted page takes its card with it.
	 *
	 * @since 1.13.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	function kaamase_share_card_on_delete( $post_id ) {

		if ( isset( kaamase_share_card_types()[ (string) get_post_type( $post_id ) ] ) ) {
			kaamase_share_card_forget( $post_id );
		}
	}
}
add_action( 'before_delete_post', 'kaamase_share_card_on_delete' );

if ( ! function_exists( 'kaamase_share_card_on_unpublish' ) ) {
	/**
	 * So does one taken down: closed, filled, hidden or drafted.
	 *
	 * @since 1.13.0
	 * @param string  $new  New status.
	 * @param string  $old  Old status.
	 * @param WP_Post $post The post.
	 * @return void
	 */
	function kaamase_share_card_on_unpublish( $new, $old, $post ) {

		if ( 'publish' === $old && 'publish' !== $new && $post instanceof WP_Post && isset( kaamase_share_card_types()[ $post->post_type ] ) ) {
			kaamase_share_card_forget( $post->ID );
		}
	}
}
add_action( 'transition_post_status', 'kaamase_share_card_on_unpublish', 10, 3 );


/* ==========================================================================
   5. HANDING IT TO RANK MATH
   ========================================================================== */

if ( ! function_exists( 'kaamase_share_card_page' ) ) {
	/**
	 * The job or profile this request is showing, if it is one.
	 *
	 * @since 1.13.0
	 * @return int Post ID, or 0.
	 */
	function kaamase_share_card_page() {

		if ( ! is_singular( array_keys( kaamase_share_card_types() ) ) ) {
			return 0;
		}

		return (int) get_queried_object_id();
	}
}

if ( ! function_exists( 'kaamase_share_card_for_rank_math' ) ) {
	/**
	 * Give Rank Math the card when it found no picture of its own.
	 *
	 * Runs after Rank Math has looked for the page's own photograph and
	 * before it falls back to its default picture, which is exactly the
	 * place in the order the card belongs. Once for the Facebook tags and
	 * once for the Twitter ones; the file is only drawn the first time.
	 *
	 * @since 1.13.0
	 * @param object $image Rank Math's list of pictures for this page.
	 * @return void
	 */
	function kaamase_share_card_for_rank_math( $image ) {

		if ( ! is_object( $image ) || ! method_exists( $image, 'has_images' ) || ! method_exists( $image, 'add_image' ) ) {
			return;
		}

		if ( $image->has_images() ) {
			return;
		}

		$post_id = kaamase_share_card_page();

		if ( ! $post_id ) {
			return;
		}

		$card = kaamase_share_card( $post_id );

		if ( empty( $card['url'] ) ) {
			return;
		}

		$image->add_image( $card );
	}
}
add_action( 'rank_math/opengraph/facebook/add_additional_images', 'kaamase_share_card_for_rank_math' );
add_action( 'rank_math/opengraph/twitter/add_additional_images', 'kaamase_share_card_for_rank_math' );

if ( ! function_exists( 'kaamase_share_card_rank_math_logo' ) ) {
	/**
	 * The last resort: the site logo or icon.
	 *
	 * Rank Math asks this filter one final time, with nothing, when it
	 * has no picture and no default picture either. Only then is the logo
	 * given, and only on these pages, so Rank Math's own default always
	 * comes first when somebody has set one.
	 *
	 * @since 1.13.0
	 * @param string $url The picture Rank Math is about to use.
	 * @return string
	 */
	function kaamase_share_card_rank_math_logo( $url ) {

		if ( '' !== trim( (string) $url ) || ! kaamase_share_card_page() || ! function_exists( 'kaamase_share_fallback_image' ) ) {
			return $url;
		}

		$logo = (string) kaamase_share_fallback_image();

		return '' !== $logo ? $logo : $url;
	}
}
add_filter( 'rank_math/opengraph/facebook/image', 'kaamase_share_card_rank_math_logo', 20 );
add_filter( 'rank_math/opengraph/twitter/image', 'kaamase_share_card_rank_math_logo', 20 );
