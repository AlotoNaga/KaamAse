<?php
/**
 * Get the app.
 *
 * A quiet strip along the bottom of the screen on a phone, offering the
 * app, pointing at /app so all the store routing already written there
 * does the work.
 *
 * Why a strip and not a pop up
 * ----------------------------
 * Google demotes a mobile page that covers its own content with a box
 * you have to dismiss before reading. indexes.php says the trade and
 * district pages are "the only pages on the platform whose job is to be
 * found by somebody who has never heard of Kaam Ase". A pop up on those
 * pages works directly against the reason they exist.
 *
 * The other reason is the connection. Somebody on 2G who has waited for
 * a job to load does not want to fight a box before reading it.
 *
 * Why the decision is made in the browser
 * ---------------------------------------
 * Not from PHP, and this is the whole reason the strip is written this
 * way. The site sits behind a page cache. A cached copy is a finished
 * page handed to everybody, so anything PHP decides about THIS visitor
 * gets frozen into it: the first desktop visitor after a purge would
 * cache the no-strip version and every phone after that would be handed
 * it. That exact fault is what stopped /app redirecting for hours at a
 * time.
 *
 * So the markup ships to everybody, hidden, and the browser decides. A
 * cached page cannot be wrong about who is looking at it.
 *
 * iPhone gets Apple's own banner instead
 * --------------------------------------
 * One meta tag, drawn by Safari itself at the top of the page. It is
 * worth having rather than our own strip because Safari knows whether
 * the app is already installed and says OPEN instead of GET. Nothing on
 * a web page can know that. The strip below therefore stays away from
 * iOS entirely and the two are never both on screen.
 *
 * @package Kaamase
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/** How long a dismissal lasts, in days. */
if ( ! defined( 'KAAMASE_APP_BANNER_DAYS' ) ) {
	define( 'KAAMASE_APP_BANNER_DAYS', 30 );
}


if ( ! function_exists( 'kaamase_app_store_id' ) ) {
	/**
	 * The App Store's number for the app.
	 *
	 * Only Apple's banner needs it. Everything else goes to /app, which
	 * already knows which store to send somebody to.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	function kaamase_app_store_id() {

		/**
		 * Filter the App Store id.
		 *
		 * @since 1.0.0
		 * @param string $id Numeric App Store id.
		 */
		return (string) apply_filters( 'kaamase_app_store_id', '6798029518' );
	}
}

if ( ! function_exists( 'kaamase_app_banner_wanted' ) ) {
	/**
	 * Whether this page should offer the app at all.
	 *
	 * The only checks here are ones that are true of the PAGE rather
	 * than of the visitor, because only those are safe to decide in PHP
	 * on a cached site.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	function kaamase_app_banner_wanted() {

		// The download page already is the offer.
		if ( is_page( 'app' ) ) {
			return false;
		}

		if ( is_admin() || is_feed() || is_embed() || is_404() ) {
			return false;
		}

		/**
		 * Filter whether to offer the app on this page.
		 *
		 * @since 1.0.0
		 * @param bool $wanted Whether to offer it.
		 */
		return (bool) apply_filters( 'kaamase_app_banner_wanted', true );
	}
}


/* ==========================================================================
   1. THE IPHONE BANNER

   Apple's, not ours. A meta tag, ignored everywhere except Safari on
   iOS, where it is drawn above the page by the browser itself.
   ========================================================================== */

if ( ! function_exists( 'kaamase_app_meta' ) ) {
	/**
	 * Tell Safari the app exists.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_app_meta() {

		if ( ! kaamase_app_banner_wanted() ) {
			return;
		}

		$id = kaamase_app_store_id();

		if ( '' === $id ) {
			return;
		}

		printf(
			'<meta name="apple-itunes-app" content="app-id=%s">' . "\n",
			esc_attr( $id )
		);
	}
}
add_action( 'wp_head', 'kaamase_app_meta', 5 );


/* ==========================================================================
   2. THE ANDROID STRIP

   Ours. Printed hidden for everybody and shown by the browser, so a
   cached page cannot be wrong about who is looking at it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_app_banner' ) ) {
	/**
	 * The strip, and the few lines that decide whether to show it.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_app_banner() {

		if ( ! kaamase_app_banner_wanted() ) {
			return;
		}

		$url = home_url( '/app/' );
		?>
		<div class="ka-getapp" id="ka-getapp" hidden>
			<?php
			/*
			 * Only when there is one. An img with an empty src is a
			 * broken image on the screen and a second request to the
			 * page itself in some browsers.
			 */
			$icon = get_site_icon_url( 96 );
			?>
			<?php if ( $icon ) : ?>
				<img class="ka-getapp__icon" src="<?php echo esc_url( $icon ); ?>"
					alt="" width="44" height="44" loading="lazy" decoding="async">
			<?php endif; ?>

			<div class="ka-getapp__words">
				<strong><?php esc_html_e( 'Kaam Ase app', 'kaamase' ); ?></strong>
				<span class="ka-small"><?php esc_html_e( 'Faster, and it tells you when work appears', 'kaamase' ); ?></span>
			</div>

			<a class="ka-btn ka-btn--action ka-btn--sm" href="<?php echo esc_url( $url ); ?>">
				<?php esc_html_e( 'Get it', 'kaamase' ); ?>
			</a>

			<button class="ka-getapp__close" type="button"
				aria-label="<?php esc_attr_e( 'Not now', 'kaamase' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" focusable="false" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
			</button>
		</div>

		<script>
		(function () {
			var strip = document.getElementById('ka-getapp');

			if (!strip) { return; }

			var KEY  = 'ka-getapp-hidden';
			var DAYS = <?php echo (int) KAAMASE_APP_BANNER_DAYS; ?>;
			var ua   = navigator.userAgent || '';

			/*
			 * Android only. iPhone gets Apple's own banner, drawn by
			 * Safari, which can say OPEN when the app is already there.
			 */
			if (!/Android/i.test(ua)) { return; }

			/*
			 * Already inside the app. Offering somebody the app they are
			 * reading this in is the kind of thing that makes an app feel
			 * unfinished. Costs nothing if the app never identifies
			 * itself; it simply never matches.
			 */
			if (/KaamAse/i.test(ua)) { return; }

			/*
			 * Turned down recently. Stored, not remembered for the visit,
			 * because somebody who said no on Monday should not be asked
			 * again on Tuesday. Wrapped because a browser in private mode
			 * throws rather than returning nothing.
			 */
			try {
				var until = window.localStorage.getItem(KEY);
				if (until && Date.now() < parseInt(until, 10)) { return; }
			} catch (e) { /* No storage. Show it; the close button still works. */ }

			strip.hidden = false;
			document.body.classList.add('ka-has-getapp');

			strip.querySelector('.ka-getapp__close').addEventListener('click', function () {
				strip.hidden = true;
				document.body.classList.remove('ka-has-getapp');
				try {
					window.localStorage.setItem(KEY, String(Date.now() + DAYS * 86400000));
				} catch (e) { /* Nothing to store it in. It returns next visit. */ }
			});
		})();
		</script>
		<?php
	}
}
add_action( 'wp_footer', 'kaamase_app_banner', 20 );
