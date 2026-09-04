<?php
/**
 * KAAM ASE — ONE LINK FOR BOTH STORES (kaamase.com/app)
 * =====================================================================
 *
 * WHERE THIS GOES
 *   WPCode -> Code Snippets -> your existing "app redirect" snippet
 *          -> select all, delete, paste everything BELOW the <?php line
 *          -> Save -> make sure it is Active
 *
 *   This REPLACES the earlier snippet. Do not run both.
 *
 * ---------------------------------------------------------------------
 * WHY THE OLD ONE WORKED, THEN STOPPED, THEN WORKED AGAIN AFTER A SAVE
 *
 * A page cache.
 *
 * The redirect is PHP, and PHP only runs when the request actually
 * reaches WordPress. A page cache saves a finished copy of the HTML and
 * serves it straight from disk, so WordPress is never asked and
 * template_redirect never fires.
 *
 * Which explains the pattern exactly:
 *
 *   1. You save the page. The cache is emptied.
 *   2. You open the link on your phone. Nothing is cached, so PHP runs,
 *      and you are sent to the store. It works.
 *   3. Somebody on a DESKTOP opens it, or a bot crawls it, or you check
 *      it on your laptop. PHP runs, decides not to redirect, and the
 *      cache saves that page -- the one with the two buttons on it.
 *   4. From then on every visitor, phone or not, is handed that saved
 *      copy. No PHP, no redirect, just the buttons.
 *
 * One desktop visit poisons it for everybody, which is why it can take
 * minutes or hours and feels random.
 *
 * The JavaScript in the page did not save it either. WordPress strips
 * <script> from page content for anybody without unfiltered_html, so
 * visitors never received it. You did, because administrators keep that
 * capability -- which is exactly why it looked fine when you tested.
 *
 * ---------------------------------------------------------------------
 * WHAT THIS DOES DIFFERENTLY
 *
 *   1. Tells every cache never to store this page, so PHP always runs.
 *   2. Prints the JavaScript itself, from here, so it cannot be
 *      stripped. If a cached copy is ever served anyway, that script
 *      still sends the visitor to the right store.
 *
 * Belt and braces on purpose: the server redirect is the fast path with
 * no flash of the page, and the script is what catches the case the
 * server never saw.
 *
 * @version 2.0.0
 */

/* =====================================================================
   THE TWO LINKS. Change them here and nowhere else.
   ===================================================================== */

function kaamase_app_store_urls() {

	return array(
		'ios'     => 'https://apps.apple.com/app/id6798029518',
		'android' => 'https://play.google.com/store/apps/details?id=com.kaamase.app',
	);
}

/**
 * Is this request on the download page, and should it be redirected?
 *
 * Shared by the redirect and the script so the two can never disagree
 * about who gets sent where.
 *
 * @return string ios, android, or an empty string to leave them alone.
 */
function kaamase_app_target() {

	if ( ! is_page( 'app' ) ) {
		return '';
	}

	/*
	 * An escape hatch. kaamase.com/app?stay=1 shows the page itself,
	 * which you need in order to edit it.
	 */
	if ( isset( $_GET['stay'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}

	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

	if ( '' === $ua ) {
		return '';
	}

	/*
	 * Crawlers read the page, they do not download apps. Google needs to
	 * index it and WhatsApp needs it to build the little preview card
	 * when somebody shares the link, and bouncing either to a store
	 * ruins both.
	 */
	$bots = 'bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|'
		. 'twitterbot|linkedinbot|embedly|quora|pinterest|preview|'
		. 'google-inspection|lighthouse|headless';

	if ( preg_match( '/' . $bots . '/i', $ua ) ) {
		return '';
	}

	if ( preg_match( '/iPhone|iPad|iPod/i', $ua ) ) {
		return 'ios';
	}

	if ( preg_match( '/Android/i', $ua ) ) {
		return 'android';
	}

	// Desktop and anything unrecognised: show the buttons.
	return '';
}


/* =====================================================================
   1. KEEP THIS PAGE OUT OF EVERY CACHE

   This is the actual fix. Everything else was already right.
   ===================================================================== */

add_action(
	'template_redirect',
	function () {

		if ( ! is_page( 'app' ) ) {
			return;
		}

		/*
		 * The constant every caching plugin looks for: LiteSpeed, which
		 * is what Hostinger runs, plus WP Rocket, W3 Total Cache, WP
		 * Super Cache and the rest. Checked when they decide whether to
		 * save the finished page, which is after this runs.
		 */
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// LiteSpeed's own switch, ignored harmlessly if it is not installed.
		do_action( 'litespeed_control_set_nocache', 'kaamase app redirect' );

		nocache_headers();

		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true );

		/*
		 * And for anything between us and the visitor that caches
		 * anyway: this page's answer depends on which phone asked, so a
		 * copy made for one must not be handed to another. This header
		 * is what says so.
		 */
		header( 'Vary: User-Agent', false );
	},
	0
);


/* =====================================================================
   2. THE REDIRECT
   ===================================================================== */

add_action(
	'template_redirect',
	function () {

		$target = kaamase_app_target();

		if ( '' === $target ) {
			return;
		}

		$urls = kaamase_app_store_urls();

		/*
		 * 302, not 301. A permanent redirect is remembered by the
		 * browser more or less forever, and you could never change these
		 * links again without people being stuck on the old one.
		 */
		wp_redirect( $urls[ $target ], 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	},
	1
);


/* =====================================================================
   3. THE SAME THING IN JAVASCRIPT, PRINTED FROM HERE

   Printed by this snippet rather than typed into the page, so nothing
   can strip it. If a cached copy of the page is ever served -- by a
   cache we have not thought of, or one sitting at the network rather
   than on the site -- PHP never ran, and this is what still works.
   ===================================================================== */

add_action(
	'wp_head',
	function () {

		if ( ! is_page( 'app' ) ) {
			return;
		}

		if ( isset( $_GET['stay'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$urls = kaamase_app_store_urls();
		?>
<script>
(function () {
	var ios = <?php echo wp_json_encode( $urls['ios'] ); ?>;
	var android = <?php echo wp_json_encode( $urls['android'] ); ?>;

	var ua = navigator.userAgent || navigator.vendor || "";

	/*
	 * An iPad running iPadOS 13 or later reports itself as a Mac, so the
	 * touch test is the only way to tell one from a laptop.
	 */
	var isIOS = /iPad|iPhone|iPod/.test(ua) ||
		(navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);

	if (isIOS) {
		window.location.replace(ios);
	} else if (/Android/.test(ua)) {
		window.location.replace(android);
	}
	/* Everybody else keeps the two buttons. */
})();
</script>
		<?php
	},
	1
);
