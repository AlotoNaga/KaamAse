<?php
/**
 * The language somebody reads in.
 *
 * Three languages ship with this platform. English, Hindi and Nagamese.
 * Until this file existed, all three were sitting on the server doing
 * nothing, because there was no way for a person to ask for one.
 *
 * Why this is not a plugin
 * -----------------------
 * The usual answer to multilingual WordPress is Polylang or WPML, and
 * both are the wrong tool here. They exist to translate CONTENT: to hold
 * a Hindi copy of a page next to its English copy and route between
 * them. Nothing on this platform works that way. A job posting is
 * written once, by an employer, in whatever language that employer
 * types in, and nobody is going to translate it. What needs translating
 * is the furniture: the buttons, the labels, the sentence that tells a
 * worker nobody may charge them for a job. That is exactly what a .po
 * file is for, and it is already done.
 *
 * So this file does the one thing that was missing. It decides which of
 * the three .mo files loads, per person, per request.
 *
 * No language in the address
 * --------------------------
 * There is no /hi/ and no /nag/. One job has one address, for ever.
 *
 * Google is explicit that translating only the template while the
 * content stays in one language is legitimate and wants hreflang for
 * it. It is still wrong here, for a reason particular to this site.
 * Google works out what language a page is in by reading it, not by
 * being told, and the readable part of a job page is the employer's own
 * words. A Hindi address would hold an English job with Hindi buttons
 * and be judged an English page anyway. What it would certainly do is
 * turn every job into three addresses, and indexing-api.php has two
 * hundred submissions a day to spend. Sixty-six jobs a day instead of
 * two hundred, to buy nothing.
 *
 * A cookie, therefore. One address, and the furniture changes.
 *
 * What is NOT here
 * ----------------
 * The admin. Whoever runs this platform reads English, the admin strings
 * were deliberately left untranslated, and a switcher that flipped the
 * admin into Nagamese because the owner was testing the front end would
 * be a bad afternoon. The filter below stands down on admin screens.
 *
 * The browser's own language, on the website. A phone set to Hindi does
 * NOT silently get a Hindi website. Google advises against deciding this
 * for people, it breaks the page cache for anyone whose phone disagrees
 * with the site, and being moved into a language you did not ask for is
 * alarming in a way that being offered it is not. The app is the
 * exception and section 8 says why.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.11.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where a person's choice is kept between visits.
 *
 * Signed out, this is the whole story. Signed in, the account below is
 * the truth and this is the copy the web server can read without asking
 * the database, which matters because the page cache reads it.
 */
define( 'KAAMASE_LOCALE_COOKIE', 'kaamase_locale' );

/**
 * Where it is kept on the account.
 *
 * Not underscore prefixed. This is a setting a person chose, not
 * plumbing, and the app is allowed to read it.
 */
define( 'KAAMASE_LOCALE_META', 'kaamase_locale' );

/** The query argument a switcher link carries. */
define( 'KAAMASE_LOCALE_QUERY', 'kaamase_lang' );

/** How long the cookie lives. Long. Nobody wants to be asked twice. */
if ( ! defined( 'KAAMASE_LOCALE_COOKIE_LIFE' ) ) {
	define( 'KAAMASE_LOCALE_COOKIE_LIFE', YEAR_IN_SECONDS );
}


/* ==========================================================================
   1. WHICH LANGUAGES EXIST
   ========================================================================== */

if ( ! function_exists( 'kaamase_locales' ) ) {
	/**
	 * The languages this platform speaks.
	 *
	 * Every label is written in its own language and script, and none of
	 * them is passed through a translation function. This is deliberate
	 * and it is the single most important detail in the file.
	 *
	 * Somebody who reads only Hindi is looking at a page of English. The
	 * word they can find is हिन्दी. If that word were translated it would
	 * say "Hindi" in English on an English page, which is the one form
	 * they cannot search for. A language menu is the only menu on a
	 * website that must never be translated.
	 *
	 * On the codes
	 * ------------
	 * en_US and hi_IN are WordPress locales in the ordinary way. nag is
	 * ISO 639-3 for Naga Pidgin, which is what Nagamese is called in the
	 * standards, and it is not a locale WordPress has ever heard of. It
	 * will not appear in Settings, Site Language, and it does not need
	 * to: nothing below goes through that dropdown.
	 *
	 * @since 1.11.0
	 * @return array Locale code => label, short label, html lang.
	 */
	function kaamase_locales() {

		$list = array(
			'en_US' => array(
				'label' => 'English',
				'short' => 'EN',
				'html'  => 'en',
			),
			'hi_IN' => array(
				'label' => 'हिन्दी',
				'short' => 'हि',
				'html'  => 'hi',
			),
			'nag'   => array(
				'label' => 'Nagamese',
				'short' => 'NAG',
				'html'  => 'nag',
			),
		);

		/**
		 * Filter the languages on offer.
		 *
		 * Adding one here is not enough on its own. The .mo files have
		 * to exist too, or the language appears in the menu and changes
		 * nothing when picked, which reads as a broken site rather than
		 * a missing translation.
		 *
		 * @since 1.11.0
		 * @param array $list Locale code => label, short, html.
		 */
		return (array) apply_filters( 'kaamase_locales', $list );
	}
}

if ( ! function_exists( 'kaamase_locale_known' ) ) {
	/**
	 * Is this one of ours.
	 *
	 * Everything that arrives from outside goes through here: the query
	 * argument, the cookie, the account, the app's header. A locale is
	 * a filename by the time WordPress is finished with it, so nothing
	 * unchecked is allowed anywhere near it.
	 *
	 * @since 1.11.0
	 * @param mixed $locale Candidate.
	 * @return bool
	 */
	function kaamase_locale_known( $locale ) {

		if ( ! is_string( $locale ) || '' === $locale ) {
			return false;
		}

		return array_key_exists( $locale, kaamase_locales() );
	}
}

if ( ! function_exists( 'kaamase_locale_default' ) ) {
	/**
	 * What somebody gets before they have chosen anything.
	 *
	 * The site's own language when that is one we speak, English when it
	 * is not. get_locale() rather than determine_locale() on purpose:
	 * determine_locale() is the function this file filters, and asking
	 * it from inside its own filter is how you get a stack overflow
	 * instead of a website.
	 *
	 * @since 1.11.0
	 * @return string
	 */
	function kaamase_locale_default() {

		$site = get_locale();

		return kaamase_locale_known( $site ) ? $site : 'en_US';
	}
}

if ( ! function_exists( 'kaamase_locale_label' ) ) {
	/**
	 * The name of a language, in that language.
	 *
	 * @since 1.11.0
	 * @param string $locale Locale code.
	 * @param string $which  label or short.
	 * @return string Empty when we do not speak it.
	 */
	function kaamase_locale_label( $locale, $which = 'label' ) {

		$all = kaamase_locales();

		if ( ! isset( $all[ $locale ][ $which ] ) ) {
			return '';
		}

		return (string) $all[ $locale ][ $which ];
	}
}


/* ==========================================================================
   2. WHO GETS WHICH
   ========================================================================== */

if ( ! function_exists( 'kaamase_locale_is_api' ) ) {
	/**
	 * Is this the app asking, rather than a browser.
	 *
	 * REST_REQUEST cannot answer this. It is defined on parse_request,
	 * and the first thing that wants to know the language is the theme
	 * loading its translations on after_setup_theme, which is earlier.
	 * So the address is read directly, the same way WordPress itself
	 * decides before the constant exists.
	 *
	 * @since 1.11.0
	 * @return bool
	 */
	function kaamase_locale_is_api() {

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// Plain permalinks put the route in a query argument instead.
		if ( isset( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$path = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );

		if ( ! is_string( $path ) || '' === $path ) {
			return false;
		}

		return str_contains( $path, '/' . rest_get_url_prefix() . '/' );
	}
}

if ( ! function_exists( 'kaamase_locale_from_app' ) ) {
	/**
	 * The language the app says the person is reading.
	 *
	 * One header, X-Kaamase-Locale, and nothing else.
	 *
	 * Accept-Language is deliberately ignored, and it is worth saying
	 * why, because honouring it looks like the obvious kindness. The app
	 * has its own language menu and its own screens are translated in
	 * the app, not here. If the server read the phone's system language
	 * while the app read its own setting, a person who set the app to
	 * English on a Hindi phone would get English screens with Hindi
	 * sentences from the server inside them. One of the two has to be in
	 * charge, and it has to be the one the person actually chose.
	 *
	 * @since 1.11.0
	 * @return string Locale code, or empty.
	 */
	function kaamase_locale_from_app() {

		if ( empty( $_SERVER['HTTP_X_KAAMASE_LOCALE'] ) ) {
			return '';
		}

		$asked = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_KAAMASE_LOCALE'] ) );

		return kaamase_locale_known( $asked ) ? $asked : '';
	}
}

if ( ! function_exists( 'kaamase_locale_of_user' ) ) {
	/**
	 * The language kept on an account.
	 *
	 * This is the copy that matters most, because it is the only one
	 * that reaches somebody who is not looking at the screen. An email
	 * sent at midnight has no cookie and no header to read. It has this.
	 *
	 * @since 1.11.0
	 * @param int $user_id User ID.
	 * @return string Locale code, or empty when they have never chosen.
	 */
	function kaamase_locale_of_user( $user_id ) {

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return '';
		}

		$saved = get_user_meta( $user_id, KAAMASE_LOCALE_META, true );

		return kaamase_locale_known( $saved ) ? (string) $saved : '';
	}
}

if ( ! function_exists( 'kaamase_locale_from_cookie' ) ) {
	/**
	 * The language this browser last asked for.
	 *
	 * @since 1.11.0
	 * @return string Locale code, or empty.
	 */
	function kaamase_locale_from_cookie() {

		if ( empty( $_COOKIE[ KAAMASE_LOCALE_COOKIE ] ) ) {
			return '';
		}

		$saved = sanitize_text_field( wp_unslash( $_COOKIE[ KAAMASE_LOCALE_COOKIE ] ) );

		return kaamase_locale_known( $saved ) ? $saved : '';
	}
}

if ( ! function_exists( 'kaamase_locale_asked_for' ) ) {
	/**
	 * A language asked for in this very address.
	 *
	 * What a switcher link carries. It counts for this request as well
	 * as being saved, so that if the redirect afterwards fails for any
	 * reason the person still sees what they clicked rather than a page
	 * that ignored them.
	 *
	 * @since 1.11.0
	 * @return string Locale code, or empty.
	 */
	function kaamase_locale_asked_for() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET[ KAAMASE_LOCALE_QUERY ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$asked = sanitize_text_field( wp_unslash( $_GET[ KAAMASE_LOCALE_QUERY ] ) );

		return kaamase_locale_known( $asked ) ? $asked : '';
	}
}

if ( ! function_exists( 'kaamase_locale_reader_id' ) ) {
	/**
	 * Who is reading, without making WordPress decide before it is ready.
	 *
	 * get_current_user_id() looks like the obvious call here and it is a
	 * trap. WordPress works out who the caller is lazily, the first time
	 * anything asks, by running the determine_current_user filter — and
	 * on this platform that filter is where rest-auth.php checks the
	 * app's bearer token. Asking here would make THIS the thing that
	 * triggers it, at line 581 of wp-settings.php, before the theme has
	 * even loaded. Whatever came back would be cached for the rest of
	 * the request, and anything inside that filter that translated a
	 * single word would re-enter this file with the answer half made.
	 *
	 * So: use the answer if WordPress already has one, and otherwise
	 * read the sign in cookie directly, which is what core itself does
	 * for a browser and has no side effects at all.
	 *
	 * The app is not covered by the cookie, and does not need to be. It
	 * sends its language in a header, which is read before this.
	 *
	 * @since 1.11.0
	 * @return int User ID, or 0.
	 */
	function kaamase_locale_reader_id() {

		if ( ! empty( $GLOBALS['current_user'] ) && $GLOBALS['current_user'] instanceof WP_User ) {
			return (int) $GLOBALS['current_user']->ID;
		}

		// Nearly every visitor is signed out. This is where they leave.
		if ( ! defined( 'LOGGED_IN_COOKIE' ) || empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			return 0;
		}

		if ( ! function_exists( 'wp_validate_auth_cookie' ) ) {
			return 0;
		}

		return (int) wp_validate_auth_cookie( '', 'logged_in' );
	}
}

if ( ! function_exists( 'kaamase_locale_now' ) ) {
	/**
	 * The language for this request, decided once.
	 *
	 * The order, strongest first:
	 *
	 *   1. A switcher link in this address. Somebody just clicked.
	 *   2. The app's header. What that person is looking at, on that
	 *      phone, right now.
	 *   3. The account. Follows them between devices, and is the only
	 *      one an email can read.
	 *   4. The cookie. This browser, signed out.
	 *   5. The site's own language.
	 *
	 * Held in a static because this runs on every textdomain load, and
	 * there are a dozen of those on an ordinary page.
	 *
	 * @since 1.11.0
	 * @return string
	 */
	function kaamase_locale_now() {

		static $decided = null;
		static $busy    = false;

		if ( null !== $decided ) {
			return $decided;
		}

		$asked = kaamase_locale_asked_for();

		if ( '' !== $asked ) {
			$decided = $asked;
			return $decided;
		}

		if ( kaamase_locale_is_api() ) {

			$from_app = kaamase_locale_from_app();

			if ( '' !== $from_app ) {
				$decided = $from_app;
				return $decided;
			}
		}

		$cookie = kaamase_locale_from_cookie();

		/*
		 * Re-entered while working out who is reading.
		 *
		 * Answer from what is already in hand and do NOT remember it.
		 * The outer call is still running and it is the one entitled to
		 * decide; caching a half answer here would freeze the request
		 * into the wrong language for good.
		 *
		 * Both the reader lookup and the meta read are inside the guard.
		 * Either can run a filter, any filter can translate a word, and
		 * translating a word comes back here. Guarding only the first of
		 * them closes the door and leaves the window open.
		 */
		if ( $busy ) {
			return '' !== $cookie ? $cookie : kaamase_locale_default();
		}

		$busy = true;

		try {
			$user_id = kaamase_locale_reader_id();
			$mine    = $user_id ? kaamase_locale_of_user( $user_id ) : '';
		} finally {
			$busy = false;
		}

		if ( '' !== $mine ) {
			$decided = $mine;
			return $decided;
		}

		$decided = '' !== $cookie ? $cookie : kaamase_locale_default();

		return $decided;
	}
}

if ( ! function_exists( 'kaamase_locale_determine' ) ) {
	/**
	 * Hand WordPress the answer.
	 *
	 * Two things this stands down for, and both matter.
	 *
	 * The admin, because the person who runs this site reads English,
	 * the admin strings were never translated, and flipping the whole
	 * back office into Nagamese because the owner wanted to check a
	 * button on the front end would be a bad afternoon.
	 *
	 * A locale switch in progress, because that is somebody deliberately
	 * writing in a language that is not this request's. That is the
	 * whole of section 5 below, and overriding it here would quietly
	 * undo it. Priority 5 already leaves WP_Locale_Switcher the last
	 * word at 10; the explicit check is here so that stays true even if
	 * the order ever changes.
	 *
	 * @since 1.11.0
	 * @param string $locale What WordPress worked out on its own.
	 * @return string
	 */
	function kaamase_locale_determine( $locale ) {

		/*
		 * isset before the call, and it is not defensive habit.
		 *
		 * The first thing that asks for a locale is
		 * load_default_textdomain() on line 581 of wp-settings.php. The
		 * locale switcher object is created on line 605. For those
		 * twenty-four lines is_locale_switched() is a method call on
		 * null, which is a fatal error on every page of the site, and
		 * the only way to see it is to call it that early.
		 */
		if ( isset( $GLOBALS['wp_locale_switcher'] ) && is_locale_switched() ) {
			return $locale;
		}

		if ( is_admin() && ! kaamase_locale_is_api() ) {
			return $locale;
		}

		return kaamase_locale_now();
	}
}
add_filter( 'determine_locale', 'kaamase_locale_determine', 5 );

if ( ! function_exists( 'kaamase_locale_html_lang' ) ) {
	/**
	 * Say in the page what language the page is in.
	 *
	 * The lang attribute comes from get_locale(), which this file does
	 * not filter and should not: get_locale() is the SITE's language and
	 * plenty of things rightly depend on it staying that. So the
	 * attribute is corrected here instead, where it is only ever about
	 * the page in front of somebody.
	 *
	 * It is not decoration. A screen reader picks its voice from it, and
	 * Hindi read aloud by an English voice is not understandable.
	 *
	 * @since 1.11.0
	 * @param string $output The whole attribute string.
	 * @return string
	 */
	function kaamase_locale_html_lang( $output ) {

		if ( is_admin() ) {
			return $output;
		}

		$html = kaamase_locale_label( kaamase_locale_now(), 'html' );

		if ( '' === $html ) {
			return $output;
		}

		return (string) preg_replace(
			'/lang="[^"]*"/',
			'lang="' . esc_attr( $html ) . '"',
			$output
		);
	}
}
add_filter( 'language_attributes', 'kaamase_locale_html_lang' );


/* ==========================================================================
   3. CHANGING IT
   ========================================================================== */

if ( ! function_exists( 'kaamase_locale_remember' ) ) {
	/**
	 * Write a choice down so it survives the next click.
	 *
	 * Both places, always, when there is an account. They are not
	 * alternatives. The account is what an email reads and what follows
	 * somebody onto a second device; the cookie is what the page cache
	 * reads, and the page cache never asks the database. Keeping them in
	 * step also means signing out does not silently throw the choice
	 * away.
	 *
	 * @since 1.11.0
	 * @param string $locale  Locale code. Checked here, not by the caller.
	 * @param int    $user_id Account to write to, 0 for none.
	 * @return bool Whether it was accepted.
	 */
	function kaamase_locale_remember( $locale, $user_id = 0 ) {

		if ( ! kaamase_locale_known( $locale ) ) {
			return false;
		}

		$user_id = absint( $user_id );

		if ( $user_id ) {
			update_user_meta( $user_id, KAAMASE_LOCALE_META, $locale );
		}

		if ( ! headers_sent() ) {

			setcookie(
				KAAMASE_LOCALE_COOKIE,
				$locale,
				array(
					'expires'  => time() + KAAMASE_LOCALE_COOKIE_LIFE,
					'path'     => COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					/*
					 * Lax, not Strict. Strict would drop the cookie on
					 * the way in from a WhatsApp link, which is how most
					 * of this platform's traffic arrives, and somebody
					 * following a shared job would land in a language
					 * they had already changed away from.
					 */
					'samesite' => 'Lax',
				)
			);
		}

		// So anything later in this request reads the new answer.
		$_COOKIE[ KAAMASE_LOCALE_COOKIE ] = $locale;

		/**
		 * Fires when somebody settles on a language.
		 *
		 * @since 1.11.0
		 * @param string $locale  Locale code.
		 * @param int    $user_id Account, or 0.
		 */
		do_action( 'kaamase_locale_chosen', $locale, $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_locale_switch_url' ) ) {
	/**
	 * The address of the link that changes language.
	 *
	 * This address, plus the argument. Staying on the page somebody is
	 * reading is the whole point: being thrown back to the front page
	 * for changing language is how people lose the job they were looking
	 * at.
	 *
	 * @since 1.11.0
	 * @param string $locale Locale to switch to.
	 * @return string
	 */
	function kaamase_locale_switch_url( $locale ) {

		$here = '';

		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$here = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		if ( '' === $here ) {
			$here = '/';
		}

		return add_query_arg( KAAMASE_LOCALE_QUERY, rawurlencode( $locale ), $here );
	}
}

if ( ! function_exists( 'kaamase_locale_handle_switch' ) ) {
	/**
	 * Take the click, keep it, and take the argument back out of the address.
	 *
	 * The redirect is not tidiness. Without it, every language link
	 * anybody ever shared would carry a language with it, and the first
	 * person to paste a Hindi link into a WhatsApp group would set the
	 * language for everybody who tapped it. It also keeps one address
	 * per job, which is what section 57 of the README depends on.
	 *
	 * No nonce, and that is a considered choice rather than an
	 * oversight. The worst a forged request can do here is show somebody
	 * a website in Hindi, which they undo with one tap. A nonce would
	 * buy nothing and cost something real: nonces cannot be put in a
	 * cached page, and these links belong on every page on the site.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	function kaamase_locale_handle_switch() {

		$asked = kaamase_locale_asked_for();

		if ( '' === $asked ) {
			return;
		}

		kaamase_locale_remember( $asked, get_current_user_id() );

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$back = remove_query_arg(
			KAAMASE_LOCALE_QUERY,
			esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
		);

		if ( ! $back ) {
			$back = home_url( '/' );
		}

		wp_safe_redirect( $back, 302 );
		exit;
	}
}
add_action( 'init', 'kaamase_locale_handle_switch', 1 );

if ( ! function_exists( 'kaamase_locale_adopt_on_login' ) ) {
	/**
	 * Carry a signed out choice onto the account it belongs to.
	 *
	 * Somebody reads the site in Hindi for a week, then registers. Their
	 * brand new account has no language on it, so without this the first
	 * thing the platform does after they sign up is start writing to
	 * them in English.
	 *
	 * Only when the account has not chosen for itself. An account that
	 * has a language set wins over whatever browser it is being opened
	 * in, because that is the one the person set on purpose.
	 *
	 * @since 1.11.0
	 * @param string  $login User login. Unused.
	 * @param WP_User $user  The user.
	 * @return void
	 */
	function kaamase_locale_adopt_on_login( $login, $user ) {

		unset( $login );

		if ( ! $user instanceof WP_User ) {
			return;
		}

		if ( '' !== kaamase_locale_of_user( $user->ID ) ) {
			return;
		}

		$cookie = kaamase_locale_from_cookie();

		if ( '' === $cookie ) {
			return;
		}

		update_user_meta( $user->ID, KAAMASE_LOCALE_META, $cookie );
	}
}
add_action( 'wp_login', 'kaamase_locale_adopt_on_login', 10, 2 );


/* ==========================================================================
   4. THE CACHE
   ========================================================================== */

/*
 * The trap this section exists for
 * --------------------------------
 * There is a page cache in front of this site, and views-api.php already
 * says the important thing about it: it answers without starting PHP at
 * all. Not "PHP runs and returns early". PHP never runs.
 *
 * Which means every line above this point can be perfectly correct and
 * the site still shows the wrong language, because the first visitor to
 * a page decides what every later visitor gets. One person reading in
 * Hindi, and the front page is Hindi for the whole state until the cache
 * turns over. Nothing in the error log. Nothing wrong in the code. It
 * would look, from the inside, like the switcher works, because the
 * owner is signed in and signed in pages are never stored.
 *
 * Two answers, and this file uses both, on purpose.
 */

if ( ! function_exists( 'kaamase_locale_vary_cookie' ) ) {
	/**
	 * Tell LiteSpeed the language cookie changes the page.
	 *
	 * The right answer. LiteSpeed keeps one stored copy per value of
	 * this cookie, so Hindi readers get a cached Hindi page and English
	 * readers get a cached English one, and nobody waits.
	 *
	 * It is not a complete answer on its own, because it works by
	 * writing rewrite rules and those depend on the server being able to
	 * write them. When that has not happened the filter fails silently,
	 * which is the one way a cache bug can be invisible.
	 *
	 * @since 1.11.0
	 * @param array $list Cookie names LiteSpeed already varies on.
	 * @return array
	 */
	function kaamase_locale_vary_cookie( $list ) {

		$list   = (array) $list;
		$list[] = KAAMASE_LOCALE_COOKIE;

		return array_values( array_unique( $list ) );
	}
}
add_filter( 'litespeed_vary_cookies', 'kaamase_locale_vary_cookie' );

if ( ! function_exists( 'kaamase_locale_cache_rule' ) ) {
	/**
	 * And the answer that cannot fail quietly.
	 *
	 * When somebody is reading in a language that is not the site's own,
	 * the page they are looking at is not the page everybody else should
	 * get, so it is not stored. English readers, who are nearly
	 * everybody, are untouched and keep a fully cached site.
	 *
	 * This costs a little speed for Hindi and Nagamese readers, and buys
	 * the guarantee that nobody is ever served a language they did not
	 * ask for. Until the vary above is confirmed working on the live
	 * server, that is the right way round. Afterwards it can be turned
	 * off with the filter and the speed comes back.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	function kaamase_locale_cache_rule() {

		$now = kaamase_locale_now();

		/*
		 * Say the page depends on the cookie regardless. This one is for
		 * everything between here and the phone that is not LiteSpeed.
		 */
		if ( ! headers_sent() ) {
			header( 'Vary: Cookie', false );
		}

		if ( $now === kaamase_locale_default() ) {
			return;
		}

		/**
		 * Filter whether a translated page may be stored by the cache.
		 *
		 * Turn this on only once the vary cookie above is confirmed
		 * working on the live server. Getting it wrong shows the wrong
		 * language to people who never asked for one.
		 *
		 * @since 1.11.0
		 * @param bool   $allowed Whether to allow storing. Default false.
		 * @param string $now     The locale in use.
		 */
		if ( apply_filters( 'kaamase_locale_cache_translated', false, $now ) ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'kaamase translated page' );

		/*
		 * And in HTTP, for anything between here and the phone that has
		 * never heard of DONOTCACHEPAGE. That constant is understood by
		 * WordPress aware caches and by nothing else, so a proxy or a
		 * CDN in front would happily keep this page and hand it to the
		 * next person. rest-auth.php sends the same headers for the
		 * same reason on answers written for one account.
		 */
		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}
}
add_action( 'template_redirect', 'kaamase_locale_cache_rule', 1 );

if ( ! function_exists( 'kaamase_locale_api_vary' ) ) {
	/**
	 * The same problem, one layer down, with a different cause.
	 *
	 * The app does not send a cookie. It sends a header, so a stored
	 * answer has to depend on the header instead. rest-auth.php already
	 * refuses to store anything written for one account; this covers the
	 * public lists, which are shared and worth caching, and which the
	 * app now asks for in three languages.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	function kaamase_locale_api_vary() {

		if ( headers_sent() ) {
			return;
		}

		header( 'Vary: X-Kaamase-Locale', false );

		if ( kaamase_locale_now() === kaamase_locale_default() ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'kaamase translated api answer' );
	}
}
add_action( 'rest_api_init', 'kaamase_locale_api_vary' );


/* ==========================================================================
   5. WRITING TO SOMEBODY ELSE
   ========================================================================== */

/*
 * The half of this job that has nothing to do with the switcher
 * -------------------------------------------------------------
 * Everything above answers one question: what language is the person in
 * front of the screen reading. Notifications are the other question, and
 * it is a harder one, because the person being written to is almost
 * never the person who caused the writing.
 *
 * A worker sets the site to Nagamese. An employer, in English, looks up
 * their number. push.php then writes "Somebody looked at your number" —
 * in the EMPLOYER's language, because that is the request that happens
 * to be running. The one person who will read that sentence is the only
 * person whose language was not consulted.
 *
 * That was true of every notification and every email this platform has
 * ever sent, and it stayed invisible only because there was no way to
 * choose a language, so everybody's was the same.
 *
 * The rule from here: a sentence written for one named person is built
 * inside kaamase_locale_write_to(), in THEIR language, and nowhere else.
 */

if ( ! function_exists( 'kaamase_locale_switch_to_user' ) ) {
	/**
	 * Start writing in somebody's language.
	 *
	 * Prefer kaamase_locale_write_to() below. This pair is here for the
	 * few places where the writing is spread over too much code to wrap
	 * in a closure without making it worse to read.
	 *
	 * Somebody who has never chosen gets the SITE's language, not the
	 * language of whoever happens to be making the request. That
	 * distinction is the whole point and it is easy to get wrong.
	 *
	 * Standing down and writing in the request's language sounds
	 * harmless, and is not. An employer reading in Hindi looks up a
	 * worker who has never touched the language menu, and that worker
	 * gets a Hindi notification. Tomorrow a different employer looks
	 * them up and they get Nagamese. Nobody chose any of it, and from
	 * the worker's side the platform simply speaks a different language
	 * every time, which reads as broken rather than multilingual.
	 *
	 * So: the reader's language when they have one, the site's when they
	 * do not, and never a stranger's. switch_to_locale() returns false
	 * when it is already that language, which on an ordinary English
	 * request is every time, so this costs nothing in the common case.
	 *
	 * The one exception is $unknown, and it exists for a single shape of
	 * message: the ones sent TO the person who is doing the thing, in
	 * their own request, before they have an account to have chosen on.
	 *
	 * Somebody reads the site in Hindi for a week and then registers.
	 * The confirmation email is written during their own request, and
	 * the brand new account has no language on it yet, so falling back
	 * to the site's would send that one email in English — the first
	 * thing the platform ever writes to them, and the only one where the
	 * request language was right all along. Passing 'request' there
	 * means: use what they have saved if they have saved anything, and
	 * otherwise leave this request in the language it is already in.
	 *
	 * Everything else wants 'site'. A notification is written about
	 * somebody, by somebody else, and that other person's language says
	 * nothing at all about the reader.
	 *
	 * @since 1.11.0
	 * @param int    $user_id Who is going to read it.
	 * @param string $unknown What to do when they have never chosen.
	 *                        'site' for the site language, 'request' to
	 *                        leave this request's language alone.
	 * @return bool Whether a switch happened, and therefore whether the
	 *              caller owes a kaamase_locale_restore().
	 */
	function kaamase_locale_switch_to_user( $user_id, $unknown = 'site' ) {

		$want = kaamase_locale_of_user( $user_id );

		if ( '' === $want ) {

			if ( 'request' === $unknown ) {
				return false;
			}

			$want = kaamase_locale_default();
		}

		return (bool) switch_to_locale( $want );
	}
}

if ( ! function_exists( 'kaamase_locale_restore' ) ) {
	/**
	 * Stop.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	function kaamase_locale_restore() {
		restore_previous_locale();
	}
}

if ( ! function_exists( 'kaamase_locale_write_to' ) ) {
	/**
	 * Write something in the language its reader reads.
	 *
	 * Everything the callback builds — subject, title, body, the closing
	 * line about nobody being allowed to charge a worker for a job — is
	 * built while that person's language is loaded, and the language
	 * goes back to what it was afterwards whatever happens inside,
	 * including a thrown exception. A notification that fails must not
	 * leave the rest of the request writing in Nagamese.
	 *
	 * Somebody who has never chosen a language gets the site's language,
	 * which is what they got before this file existed. What they must
	 * never get is the language of whoever triggered the notification,
	 * and kaamase_locale_switch_to_user() above says why at length.
	 *
	 * @since 1.11.0
	 * @param int      $user_id Who will read it.
	 * @param callable $write   Builds and sends. Takes no arguments.
	 * @return mixed Whatever the callback returned.
	 */
	function kaamase_locale_write_to( $user_id, $write ) {

		if ( ! is_callable( $write ) ) {
			return null;
		}

		$switched = kaamase_locale_switch_to_user( $user_id );

		try {
			return $write();
		} finally {
			if ( $switched ) {
				kaamase_locale_restore();
			}
		}
	}
}


/* ==========================================================================
   6. THE PICKER
   ========================================================================== */

if ( ! function_exists( 'kaamase_locale_picker' ) ) {
	/**
	 * The control somebody uses to change language.
	 *
	 * Two shapes for two places. A dropdown where space is short, which
	 * is the top of the page, and a plain row of links where it is not,
	 * which is the menu drawer, the footer and the dashboard.
	 *
	 * No JavaScript in either. A details element opens and closes on its
	 * own, and the links are links. On a two bar connection in Mokokchung
	 * the language control is the one thing on the page that has to work
	 * before anything else has finished loading, because somebody who
	 * cannot read the page cannot wait to find out whether it will get
	 * better.
	 *
	 * Language names are printed exactly as section 1 stores them, never
	 * translated, each carrying its own lang attribute so a screen
	 * reader says हिन्दी in Hindi rather than spelling it in English.
	 *
	 * @since 1.11.0
	 * @param array $args style: menu or list. class: extra classes.
	 * @return string
	 */
	function kaamase_locale_picker( $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'style' => 'menu',
				'class' => '',
			)
		);

		$all = kaamase_locales();

		// One language is not a choice. Print nothing rather than a menu.
		if ( count( $all ) < 2 ) {
			return '';
		}

		$now   = kaamase_locale_now();
		$list  = 'list' === $args['style'];
		$class = trim( 'ka-lang ka-lang--' . ( $list ? 'list' : 'menu' ) . ' ' . $args['class'] );

		$links = '';

		foreach ( $all as $code => $language ) {

			$here = ( $code === $now );

			$links .= sprintf(
				'<li><a class="ka-lang__link%1$s" href="%2$s" hreflang="%3$s" lang="%3$s" rel="nofollow"%4$s>%5$s</a></li>',
				$here ? ' is-current' : '',
				esc_url( kaamase_locale_switch_url( $code ) ),
				esc_attr( $language['html'] ),
				$here ? ' aria-current="true"' : '',
				esc_html( $language['label'] )
			);
		}

		$globe = '<svg class="ka-lang__globe" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 15.3 0 18M12 3c-2.5 2.7-2.5 15.3 0 18"/></svg>';

		if ( $list ) {

			return sprintf(
				'<div class="%s"><ul class="ka-lang__list">%s</ul></div>',
				esc_attr( $class ),
				$links
			);
		}

		return sprintf(
			'<details class="%1$s"><summary class="ka-lang__toggle" aria-label="%2$s">%3$s<span class="ka-lang__now">%4$s</span></summary><ul class="ka-lang__list">%5$s</ul></details>',
			esc_attr( $class ),
			esc_attr__( 'Choose a language', 'kaamase-core' ),
			$globe,
			esc_html( kaamase_locale_label( $now, 'short' ) ),
			$links
		);
	}
}


/* ==========================================================================
   7. ON THE DASHBOARD
   ========================================================================== */

if ( ! function_exists( 'kaamase_locale_dashboard_card' ) ) {
	/**
	 * The one place the choice is worth explaining.
	 *
	 * In the header it is an icon, because the header has no room for a
	 * sentence. Here there is room, and there is something worth saying:
	 * that changing it here changes what the platform writes to them as
	 * well as what they read, which is the part nobody would guess.
	 *
	 * And it is honest about the limit. Jobs and profiles are written by
	 * the people who posted them and stay in the words they used. A
	 * worker who switches to Nagamese and then finds job descriptions
	 * still in English has not hit a bug, and being told so here is
	 * better than being left to work it out.
	 *
	 * @since 1.11.0
	 * @param int    $user_id User ID.
	 * @param int    $profile Profile post ID. Unused.
	 * @param string $type    worker or employer. Unused.
	 * @return void
	 */
	function kaamase_locale_dashboard_card( $user_id, $profile = 0, $type = '' ) {

		unset( $profile, $type );

		if ( ! $user_id ) {
			return;
		}

		if ( count( kaamase_locales() ) < 2 ) {
			return;
		}
		?>
		<div class="ka-card ka-stack ka-mt-6">

			<h3><?php esc_html_e( 'Language', 'kaamase-core' ); ?></h3>

			<p>
				<?php
				esc_html_e(
					'This changes the website, and it changes the language we write to you in when we send you something.',
					'kaamase-core'
				);
				?>
			</p>

			<?php
			echo kaamase_locale_picker( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				array( 'style' => 'list' )
			);
			?>

			<p class="ka-small ka-mute">
				<?php
				esc_html_e(
					'Jobs and profiles stay in the words the person who wrote them used. We do not translate what other people have written.',
					'kaamase-core'
				);
				?>
			</p>

		</div>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_locale_dashboard_card', 70, 3 );


/* ==========================================================================
   8. THE APP
   ========================================================================== */

/*
 * What the app owns and what this does
 * ------------------------------------
 * The app's own screens are translated in the app. Nothing in
 * languages/ reaches them: a .mo file is read by PHP on this server and
 * the phone never sees one. Every button, tab and error message inside
 * the app is a string in the app's code and needs its own Hindi and
 * Nagamese.
 *
 * What the server owns is everything it writes in sentences: push
 * notifications, the emails behind them, and any text it hands over
 * ready to display. Those are translated here, and they can only be
 * right if the server knows what the person picked in the app.
 *
 * So there are two halves to keep in step, and this is the join:
 *
 *   - the app sends X-Kaamase-Locale on every request, so answers come
 *     back in the language on the screen even before anybody signs in;
 *   - and when somebody is signed in it posts the choice here once, so
 *     that a notification arriving at midnight, with no request and no
 *     header anywhere near it, still knows what to say.
 *
 * Neither half covers for the other. Header only, and midnight is in
 * English. Account only, and a signed out browse is.
 */

if ( ! function_exists( 'kaamase_locale_wire' ) ) {
	/**
	 * The language list, as the app wants it.
	 *
	 * @since 1.11.0
	 * @return array
	 */
	function kaamase_locale_wire() {

		$out = array();

		foreach ( kaamase_locales() as $code => $language ) {

			$out[] = array(
				'code'  => (string) $code,
				'label' => (string) $language['label'],
				'short' => (string) $language['short'],
				'html'  => (string) $language['html'],
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'kaamase_locale_state' ) ) {
	/**
	 * Everything the app needs to draw a language screen.
	 *
	 * saved is separate from current on purpose. current is what this
	 * answer was written in; saved is what the account actually chose,
	 * and is empty when nobody has ever chosen. The app needs the
	 * difference to know whether to show a language as picked or to fall
	 * back to the phone's own setting on first run.
	 *
	 * @since 1.11.0
	 * @param int $user_id Account, or 0.
	 * @return array
	 */
	function kaamase_locale_state( $user_id = 0 ) {

		return array(
			'current'   => kaamase_locale_now(),
			'default'   => kaamase_locale_default(),
			'saved'     => $user_id ? kaamase_locale_of_user( $user_id ) : '',
			'available' => kaamase_locale_wire(),
		);
	}
}

if ( ! function_exists( 'kaamase_locale_route' ) ) {
	/**
	 * Read it, and set it.
	 *
	 * @since 1.11.0
	 * @return void
	 */
	function kaamase_locale_route() {

		register_rest_route(
			KAAMASE_REST_NS,
			'/locale',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_locale_rest_get',
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/locale',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_locale_rest_set',
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'locale' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array_keys( kaamase_locales() ),
					),
				),
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_locale_route' );

if ( ! function_exists( 'kaamase_locale_rest_get' ) ) {
	/**
	 * What languages there are, and which one is in force.
	 *
	 * Open to anybody, because the app needs to draw its language screen
	 * before somebody has an account, and because there is nothing
	 * private in a list of three languages.
	 *
	 * @since 1.11.0
	 * @return WP_REST_Response
	 */
	function kaamase_locale_rest_get() {
		return rest_ensure_response( kaamase_locale_state( get_current_user_id() ) );
	}
}

if ( ! function_exists( 'kaamase_locale_rest_set' ) ) {
	/**
	 * Keep a choice made in the app.
	 *
	 * This is the call that makes notifications arrive in the right
	 * language. Without it the app can look perfectly translated and
	 * every push it receives still be in English, because a push is sent
	 * by a cron job hours later with nothing to read but the account.
	 *
	 * @since 1.11.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	function kaamase_locale_rest_set( $request ) {

		$user_id = get_current_user_id();
		$locale  = (string) $request->get_param( 'locale' );

		if ( ! kaamase_locale_known( $locale ) ) {
			return new WP_Error(
				'kaamase_locale_unknown',
				__( 'We do not have that language.', 'kaamase-core' ),
				array( 'status' => 400 )
			);
		}

		kaamase_locale_remember( $locale, $user_id );

		$state            = kaamase_locale_state( $user_id );
		$state['current'] = $locale;

		return rest_ensure_response( $state );
	}
}

if ( ! function_exists( 'kaamase_locale_shape_me' ) ) {
	/**
	 * Put the language on /me.
	 *
	 * /me is the one request the app waits on before it can draw
	 * anything, and rest-shape.php is explicit that a second round trip
	 * on these connections is the difference between a screen that fills
	 * in and one somebody decides is broken. A language menu that had to
	 * fetch its own options would be exactly that second trip.
	 *
	 * @since 1.11.0
	 * @param array $me      The account.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_locale_shape_me( $me, $user_id ) {

		$me['locale']  = kaamase_locale_of_user( $user_id );
		$me['locales'] = kaamase_locale_wire();

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_locale_shape_me', 10, 2 );
