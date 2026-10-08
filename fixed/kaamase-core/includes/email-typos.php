<?php
/**
 * Mistyped email addresses.
 *
 * Catches the address that can never receive the confirmation link,
 * before the account is made with it.
 *
 * Why this exists
 * ---------------
 * Reported from the live site: people were registering with gmial.com,
 * gmail.co, gamil.com and the like, and then waiting for a link that
 * went nowhere. The mail itself arrives fine; the address was simply
 * wrong. Every one of those is a person who wanted to use the platform
 * and was lost at the first step, usually without ever knowing why.
 *
 * What it does
 * ------------
 * Two checks, each for a mistake that is certain rather than likely:
 *
 *   1. The part after the @ is a misspelling of a big mail provider,
 *      or ends in something that is not a real ending at all (.con,
 *      .cmo). The reply names the address they almost certainly meant:
 *      "Did you mean raju@gmail.com?"
 *
 *   2. The address is at gmail.com but cannot be a Gmail address:
 *      fewer than 6 characters before the @, or a dash or underscore,
 *      which Gmail has never allowed.
 *
 * Nothing is ever changed silently. A suggestion is offered and the
 * person chooses. On the website the suggestion is put into the box for
 * them, because retyping an address on a phone is where the mistake
 * came from in the first place.
 *
 * Never a locked door
 * -------------------
 * A list like this will one day meet a real address it thinks is wrong.
 * So whoever types the same address a second time, exactly, gets it
 * accepted. The website remembers what it warned about; the app sends
 * email_as_typed once the person has said the address is right.
 *
 * Where it is called
 * ------------------
 * Website registration (registration.php), app registration
 * (rest-api.php) and mending an unconfirmed address from the app
 * (account-providers.php). Google and Apple sign in are not checked:
 * those addresses come from the provider and are already proven.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.12.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE LISTS
   ========================================================================== */

if ( ! function_exists( 'kaamase_email_known_domains' ) ) {
	/**
	 * Real mail domains that are never questioned.
	 *
	 * Several of these sit one letter away from a big provider (mail.com
	 * from gmail.com, ymail.com from gmail.com, email.com), which is
	 * exactly why they have to be named here.
	 *
	 * @since 1.12.0
	 * @return string[]
	 */
	function kaamase_email_known_domains() {

		$domains = array(
			'gmail.com', 'googlemail.com',
			'yahoo.com', 'yahoo.co.in', 'yahoo.in', 'yahoo.co.uk', 'ymail.com', 'rocketmail.com',
			'hotmail.com', 'hotmail.co.uk', 'outlook.com', 'outlook.in', 'live.com', 'live.in', 'msn.com',
			'icloud.com', 'me.com', 'mac.com',
			'rediffmail.com', 'rediff.com',
			'mail.com', 'email.com', 'aol.com', 'gmx.com', 'gmx.net', 'zoho.com', 'zohomail.in',
			'protonmail.com', 'proton.me', 'pm.me', 'yandex.com', 'mail.ru',
		);

		/**
		 * Filter the domains that are never questioned.
		 *
		 * @since 1.12.0
		 * @param string[] $domains Domains.
		 */
		return array_map( 'strtolower', (array) apply_filters( 'kaamase_email_known_domains', $domains ) );
	}
}

if ( ! function_exists( 'kaamase_email_providers' ) ) {
	/**
	 * The addresses a near miss is corrected towards.
	 *
	 * Only providers people here actually use. A near miss of anything
	 * else is left alone rather than guessed at.
	 *
	 * @since 1.12.0
	 * @return string[]
	 */
	function kaamase_email_providers() {

		/**
		 * Filter the domains a misspelling is corrected towards.
		 *
		 * @since 1.12.0
		 * @param string[] $providers Domains.
		 */
		return array_map(
			'strtolower',
			(array) apply_filters(
				'kaamase_email_providers',
				array( 'gmail.com', 'yahoo.com', 'yahoo.co.in', 'hotmail.com', 'outlook.com', 'live.com', 'rediffmail.com', 'icloud.com' )
			)
		);
	}
}

if ( ! function_exists( 'kaamase_email_single_homes' ) ) {
	/**
	 * Providers that live at one address only.
	 *
	 * Gmail is only ever gmail.com, so gmail.co.in or gmail.in is always
	 * a mistake. Yahoo and Hotmail are not on this list because they
	 * really do have addresses in other countries.
	 *
	 * @since 1.12.0
	 * @return array<string,string> First part of the domain => the one real domain.
	 */
	function kaamase_email_single_homes() {
		return array(
			'gmail'      => 'gmail.com',
			'rediffmail' => 'rediffmail.com',
			'icloud'     => 'icloud.com',
		);
	}
}

if ( ! function_exists( 'kaamase_email_fake_endings' ) ) {
	/**
	 * Endings that are not real, and are .com typed wrong.
	 *
	 * Deliberately not .co, .cm or .om: those are real countries.
	 *
	 * @since 1.12.0
	 * @return string[]
	 */
	function kaamase_email_fake_endings() {
		return array( 'con', 'cmo', 'ocm', 'vom', 'xom', 'cpm', 'cim', 'comm', 'coom', 'c0m', 'come' );
	}
}


/* ==========================================================================
   2. FINDING THE MISTAKE
   ========================================================================== */

if ( ! function_exists( 'kaamase_email_distance' ) ) {
	/**
	 * How many single-letter edits turn one word into the other.
	 *
	 * Counts two letters swapped as one edit, not two, because gmial is
	 * one slip of the thumb, not two.
	 *
	 * @since 1.12.0
	 * @param string $a One.
	 * @param string $b The other.
	 * @return int
	 */
	function kaamase_email_distance( $a, $b ) {

		$a = (string) $a;
		$b = (string) $b;
		$n = strlen( $a );
		$m = strlen( $b );

		if ( 0 === $n ) {
			return $m;
		}

		if ( 0 === $m ) {
			return $n;
		}

		$d = array();

		for ( $i = 0; $i <= $n; $i++ ) {
			$d[ $i ][0] = $i;
		}

		for ( $j = 0; $j <= $m; $j++ ) {
			$d[0][ $j ] = $j;
		}

		for ( $i = 1; $i <= $n; $i++ ) {
			for ( $j = 1; $j <= $m; $j++ ) {

				$cost = ( $a[ $i - 1 ] === $b[ $j - 1 ] ) ? 0 : 1;

				$d[ $i ][ $j ] = min(
					$d[ $i - 1 ][ $j ] + 1,
					$d[ $i ][ $j - 1 ] + 1,
					$d[ $i - 1 ][ $j - 1 ] + $cost
				);

				if ( $i > 1 && $j > 1 && $a[ $i - 1 ] === $b[ $j - 2 ] && $a[ $i - 2 ] === $b[ $j - 1 ] ) {
					$d[ $i ][ $j ] = min( $d[ $i ][ $j ], $d[ $i - 2 ][ $j - 2 ] + 1 );
				}
			}
		}

		return $d[ $n ][ $m ];
	}
}

if ( ! function_exists( 'kaamase_email_fix_domain' ) ) {
	/**
	 * The domain somebody meant, or an empty string if it looks right.
	 *
	 * @since 1.12.0
	 * @param string $domain The part after the @, lower case.
	 * @return string
	 */
	function kaamase_email_fix_domain( $domain ) {

		$known     = kaamase_email_known_domains();
		$providers = kaamase_email_providers();
		$homes     = kaamase_email_single_homes();

		// Commas for dots, doubled dots, a dot at either end.
		$clean = str_replace( ',', '.', (string) $domain );
		$clean = trim( (string) preg_replace( '/\.{2,}/', '.', $clean ), '.' );

		if ( '' === $clean ) {
			return '';
		}

		if ( in_array( $clean, $known, true ) ) {
			return $clean === $domain ? '' : $clean;
		}

		/*
		 * Words that sit one letter from a provider's name but are other
		 * real providers. Never corrected on closeness alone.
		 */
		$others = array( 'mail', 'email', 'ymail', 'cloud', 'gmx', 'live', 'me' );

		// No ending at all: raju@gmail.
		if ( false === strpos( $clean, '.' ) ) {

			if ( in_array( $clean, $others, true ) ) {
				return '';
			}

			foreach ( $providers as $provider ) {
				$name = strstr( $provider, '.', true );

				if ( kaamase_email_distance( $clean, $name ) <= 1 ) {
					return $provider;
				}
			}

			return '';
		}

		$first = strstr( $clean, '.', true );
		$rest  = substr( $clean, strlen( $first ) + 1 );

		// An ending that does not exist is always .com typed wrong.
		$fixed_end = in_array( $rest, kaamase_email_fake_endings(), true );

		if ( $fixed_end ) {
			$clean = $first . '.com';
			$rest  = 'com';

			if ( in_array( $clean, $known, true ) ) {
				return $clean;
			}
		}

		// The ending typed twice: gmail.com.com, yahoo.com.in.
		if ( 'yahoo.com.in' === $clean ) {
			return 'yahoo.co.in';
		}

		foreach ( $providers as $provider ) {
			if ( 0 === strpos( $clean, $provider . '.' ) ) {
				return $provider;
			}
		}

		// A provider that only lives at one address: gmail.co.in, gmial.com.
		if ( ! in_array( $first, $others, true ) ) {
			foreach ( $homes as $name => $home ) {
				if ( $first === $name || ( strlen( $first ) >= 4 && kaamase_email_distance( $first, $name ) <= 1 ) ) {
					return $home;
				}
			}
		}

		// A near miss of a provider as a whole: yaho.com, hotmial.com, outlok.com.
		$best      = '';
		$best_dist = PHP_INT_MAX;

		foreach ( $providers as $provider ) {
			$dist = kaamase_email_distance( $clean, $provider );

			if ( $dist < $best_dist ) {
				$best      = $provider;
				$best_dist = $dist;
			}
		}

		$allowed = strlen( $clean ) >= 8 ? 2 : 1;

		/*
		 * The name spelled right and only the ending different. Yahoo and
		 * Hotmail really do have addresses in other countries (yahoo.ca,
		 * hotmail.fr), so only the endings that are .com with a letter
		 * lost are corrected here.
		 */
		if ( $first === strstr( $best, '.', true ) && ! in_array( $rest, array( 'co', 'cm', 'om' ), true ) ) {
			$allowed = 0;
		}

		if ( $best_dist > 0 && $best_dist <= $allowed ) {
			return $best;
		}

		if ( $fixed_end || $clean !== $domain ) {
			return $clean;
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_email_normal' ) ) {
	/**
	 * An address as typed, tidied only as far as comparing needs.
	 *
	 * Lower case, no spaces. Used to recognise the same address typed a
	 * second time, so it must not change anything that matters.
	 *
	 * @since 1.12.0
	 * @param string $raw As typed.
	 * @return string
	 */
	function kaamase_email_normal( $raw ) {
		return strtolower( (string) preg_replace( '/\s+/', '', (string) $raw ) );
	}
}

if ( ! function_exists( 'kaamase_email_suggest' ) ) {
	/**
	 * The whole address somebody meant, or an empty string.
	 *
	 * @since 1.12.0
	 * @param string $raw As typed.
	 * @return string
	 */
	function kaamase_email_suggest( $raw ) {

		$raw = kaamase_email_normal( $raw );

		if ( 1 !== substr_count( $raw, '@' ) ) {
			return '';
		}

		list( $local, $domain ) = explode( '@', $raw );

		if ( '' === $local || '' === $domain ) {
			return '';
		}

		$fixed = kaamase_email_fix_domain( $domain );

		if ( '' === $fixed || $fixed === $domain ) {
			return '';
		}

		$suggestion = $local . '@' . $fixed;

		return is_email( $suggestion ) ? $suggestion : '';
	}
}

if ( ! function_exists( 'kaamase_email_gmail_impossible' ) ) {
	/**
	 * Whether an address at Gmail breaks Gmail's own rules.
	 *
	 * Gmail names are letters, numbers and dots, 6 to 30 characters.
	 * Everything after a + is the owner's own label and is not checked.
	 * Only mistakes that are wrong however the rule is counted are
	 * caught, so no real address is ever refused by it.
	 *
	 * @since 1.12.0
	 * @param string $raw As typed.
	 * @return bool
	 */
	function kaamase_email_gmail_impossible( $raw ) {

		$raw = kaamase_email_normal( $raw );

		if ( 1 !== substr_count( $raw, '@' ) ) {
			return false;
		}

		list( $local, $domain ) = explode( '@', $raw );

		if ( ! in_array( $domain, array( 'gmail.com', 'googlemail.com' ), true ) ) {
			return false;
		}

		$name = strstr( $local . '+', '+', true );

		if ( ! preg_match( '/^[a-z0-9.]+$/', $name ) ) {
			return true;
		}

		return strlen( $name ) < 6 || strlen( str_replace( '.', '', $name ) ) > 30;
	}
}


/* ==========================================================================
   3. THE ANSWER THE FORMS USE
   ========================================================================== */

if ( ! function_exists( 'kaamase_email_problem' ) ) {
	/**
	 * What is wrong with an address, if anything is.
	 *
	 * @since 1.12.0
	 * @param string $raw      As typed.
	 * @param bool   $insisted The person has said this address is right.
	 * @return array{message: string, suggestion: string}|null Null when it is fine.
	 */
	function kaamase_email_problem( $raw, $insisted = false ) {

		if ( $insisted ) {
			return null;
		}

		$suggestion = kaamase_email_suggest( $raw );

		if ( '' !== $suggestion ) {
			return array(
				'message'    => sprintf(
					/* translators: %s: the email address we think they meant */
					__( 'Check your email address. Did you mean %s?', 'kaamase-core' ),
					$suggestion
				),
				'suggestion' => $suggestion,
			);
		}

		if ( kaamase_email_gmail_impossible( $raw ) ) {
			return array(
				'message'    => __( 'Check your email address. A Gmail address has at least 6 letters or numbers before the @, and never a dash or an underscore.', 'kaamase-core' ),
				'suggestion' => '',
			);
		}

		return null;
	}
}
