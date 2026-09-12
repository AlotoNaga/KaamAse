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


/* ==========================================================================
   3. THE HOME PAGE BLOCK

   The two things above only reach a person on the right browser: Apple's
   banner needs Safari, and the strip needs Android. Almost nobody in
   Nagaland opens Safari by choice, so on an iPhone in Chrome the app is
   currently invisible. This block is in the page itself, so it is on
   every device and every browser, and it can be looked at.

   Two links, one per store, rather than one link to /app. /app has to
   work out which phone it is talking to from the user agent, and that
   guess has already been wrong once. Somebody who can see both shops
   cannot be sent to the wrong one.
   ========================================================================== */

if ( ! function_exists( 'kaamase_app_store_links' ) ) {
	/**
	 * Where each shop lives.
	 *
	 * @since 1.1.0
	 * @return array<string,string> Keyed ios and android.
	 */
	function kaamase_app_store_links() {

		$id    = kaamase_app_store_id();
		$links = array(
			'android' => 'https://play.google.com/store/apps/details?id=com.kaamase.app',
		);

		/*
		 * Only when there is a number. Sticking an empty one on the end
		 * builds apps.apple.com/app/id, which is a button that looks
		 * like it works and goes nowhere. Better to show one shop than
		 * two when one of them is broken.
		 */
		if ( '' !== $id ) {
			$links['ios'] = 'https://apps.apple.com/app/id' . $id;
		}

		/**
		 * Filter the store addresses.
		 *
		 * @since 1.1.0
		 * @param array<string,string> $links Keyed ios and android.
		 */
		return (array) apply_filters( 'kaamase_app_store_links', $links );
	}
}

if ( ! function_exists( 'kaamase_app_icon_url' ) ) {
	/**
	 * The app icon to show beside the offer.
	 *
	 * Built from the uploads folder rather than written out in full, so
	 * moving the site to another address does not leave a broken image
	 * here. Falls back to the site icon if the file is ever cleared out.
	 *
	 * @since 1.1.0
	 * @return string URL, or an empty string if there is nothing to show.
	 */
	function kaamase_app_icon_url() {

		$url     = '';
		$uploads = wp_upload_dir();
		$file    = '2026/08/logo-icon-512.png';

		if ( empty( $uploads['error'] ) && file_exists( trailingslashit( $uploads['basedir'] ) . $file ) ) {
			$url = trailingslashit( $uploads['baseurl'] ) . $file;
		}

		if ( '' === $url ) {
			$url = (string) get_site_icon_url( 192 );
		}

		/**
		 * Filter the app icon shown in the home page block.
		 *
		 * @since 1.1.0
		 * @param string $url Image address, or empty for none.
		 */
		return (string) apply_filters( 'kaamase_app_icon_url', $url );
	}
}

if ( ! function_exists( 'kaamase_app_store_glyphs' ) ) {
	/**
	 * The two shop marks.
	 *
	 * Drawn rather than fetched, because rule 2 of the design system is
	 * no external requests, and because a village connection should not
	 * have to wait on two logos to read the page.
	 *
	 * @since 1.1.0
	 * @return array<string,string> Keyed ios and android, each an SVG.
	 */
	function kaamase_app_store_glyphs() {

		$open  = '<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">';
		$close = '</svg>';

		return array(
			'ios'     => $open . '<path d="M16.365 1.43c0 1.14-.417 2.2-1.25 3.05-.99 1.02-2.13 1.6-3.36 1.5-.02-.11-.03-.23-.03-.35 0-1.1.47-2.26 1.29-3.08.41-.42.94-.77 1.57-1.05.63-.27 1.22-.42 1.77-.45.01.13.01.25.01.38zM20.6 17.02c-.32.74-.7 1.42-1.14 2.05-.6.86-1.09 1.45-1.47 1.78-.59.54-1.22.82-1.9.84-.49 0-1.07-.14-1.76-.42-.69-.28-1.32-.42-1.9-.42-.6 0-1.25.14-1.95.42-.7.28-1.27.43-1.7.44-.65.03-1.3-.26-1.94-.86-.41-.36-.92-.97-1.53-1.83-.65-.92-1.19-1.98-1.6-3.19-.45-1.31-.68-2.57-.68-3.79 0-1.4.3-2.6.91-3.61.48-.81 1.11-1.45 1.91-1.92.79-.47 1.65-.71 2.58-.73.52 0 1.19.16 2.03.48.83.32 1.37.48 1.6.48.18 0 .78-.19 1.79-.56.96-.35 1.77-.49 2.43-.44 1.8.15 3.15.86 4.05 2.14-1.61.98-2.4 2.35-2.39 4.11.02 1.37.51 2.51 1.49 3.42.44.42.94.74 1.49.97-.12.35-.25.68-.38 1z"/>' . $close,
			'android' => $open . '<path d="M22.018 13.298l-3.919 2.218-3.515-3.493 3.543-3.521 3.891 2.202a1.49 1.49 0 0 1 0 2.594zM1.337.924a1.486 1.486 0 0 0-.112.568v21.017c0 .217.045.419.124.6l11.155-11.087zm12.207 10.065l3.258-3.238L3.45.195a1.466 1.466 0 0 0-.946-.179zm0 2.067l-11 10.933c.298.036.612-.016.906-.183l13.324-7.54z"/>' . $close,
		);
	}
}

if ( ! function_exists( 'kaamase_app_cta' ) ) {
	/**
	 * The block itself.
	 *
	 * The small line on each shop button says the phone, not the shop,
	 * because somebody choosing between these knows what phone is in
	 * their hand and may not know what an App Store is.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	function kaamase_app_cta() {

		$links  = kaamase_app_store_links();
		$glyphs = kaamase_app_store_glyphs();
		$icon   = kaamase_app_icon_url();

		$shops = array(
			'ios'     => __( 'iPhone', 'kaamase' ),
			'android' => __( 'Android', 'kaamase' ),
		);

		$names = array(
			'ios'     => __( 'App Store', 'kaamase' ),
			'android' => __( 'Google Play', 'kaamase' ),
		);
		?>
		<div class="ka-appcta">

			<?php if ( $icon ) : ?>
				<img class="ka-appcta__icon" src="<?php echo esc_url( $icon ); ?>"
					alt="" width="64" height="64" loading="lazy" decoding="async">
			<?php endif; ?>

			<div class="ka-appcta__words">
				<h2 class="ka-appcta__title"><?php esc_html_e( 'Get the Kaam Ase app', 'kaamase' ); ?></h2>
				<p class="ka-appcta__sub">
					<?php esc_html_e( 'Faster on your phone, and it tells you the moment work appears.', 'kaamase' ); ?>
				</p>
			</div>

			<div class="ka-appcta__stores">
				<?php foreach ( $shops as $key => $phone ) : ?>
					<?php
					// Both of these can be replaced by a filter or an
					// earlier definition, so neither is assumed.
					if ( empty( $links[ $key ] ) || empty( $glyphs[ $key ] ) ) {
						continue;
					}
					?>
					<a class="ka-store" href="<?php echo esc_url( $links[ $key ] ); ?>">
						<?php echo $glyphs[ $key ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Drawn above, not user input. ?>
						<span class="ka-store__words">
							<span class="ka-store__where"><?php echo esc_html( $phone ); ?></span>
							<span class="ka-store__name"><?php echo esc_html( $names[ $key ] ); ?></span>
						</span>
					</a>
				<?php endforeach; ?>
			</div>

		</div>
		<?php
	}
}
