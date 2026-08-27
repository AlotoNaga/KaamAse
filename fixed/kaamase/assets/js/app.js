/**
 * Kaam Ase front end script.
 *
 * Version 1.5.0
 *
 * Every function in this file is an enhancement. Nothing here is
 * required for the site to work. If this file never arrives, which on a
 * village connection is a regular event rather than an edge case, the
 * menu still opens, the filters still filter, and every form still
 * submits. That is the whole design rule and it is worth stating before
 * the first line of code, because the moment something in here becomes
 * load bearing, the site stops working for the people it was built for.
 *
 * The most important function is the double submit guard. On a slow
 * connection a person taps a button, nothing visibly happens for four
 * seconds, so they tap again. Without a guard that posts the form twice
 * and creates two identical jobs, two ratings, or two accounts. It is
 * the single most common real world bug on sites like this and almost
 * nobody handles it.
 *
 * No dependencies. No build step. Deferred, so the DOM is ready.
 */

( function () {
	'use strict';

	var config = window.kaamase || {};
	var text = config.strings || {};

	/**
	 * Shorthand for querying elements.
	 *
	 * @param {string}  selector CSS selector.
	 * @param {Element} scope    Optional root element.
	 * @return {Element[]} Matching elements.
	 */
	function all( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}


	/* ======================================================================
	   1. NAVIGATION

	   nav-walker.php renders every submenu OPEN and every toggle button
	   hidden, so that a visitor without this script sees all the links.
	   This reverses that: it reveals the buttons and collapses the lists.

	   The order matters. Reveal the button first, then collapse, so there
	   is never a moment where the list is closed and nothing can open it.
	   ====================================================================== */

	function setUpMenus() {

		all( '.ka-nav__toggle' ).forEach( function ( button ) {

			var id = button.getAttribute( 'aria-controls' );
			var submenu = id ? document.getElementById( id ) : null;

			if ( ! submenu ) {
				return;
			}

			// Reveal the control before taking the content away.
			button.removeAttribute( 'hidden' );
			button.setAttribute( 'aria-expanded', 'false' );
			submenu.hidden = true;

			button.addEventListener( 'click', function ( event ) {

				event.preventDefault();

				var open = button.getAttribute( 'aria-expanded' ) === 'true';

				button.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				submenu.hidden = open;
			} );
		} );
	}

	/**
	 * Drawer conveniences.
	 *
	 * The drawer is a details element, so it already opens and closes on
	 * its own. This only adds closing on Escape and closing when
	 * something outside it is tapped, which people expect and which the
	 * element does not do by itself.
	 */
	function setUpDrawer() {

		var drawer = document.getElementById( 'ka-drawer' );

		if ( ! drawer ) {
			return;
		}

		document.addEventListener( 'keydown', function ( event ) {

			if ( event.key !== 'Escape' || ! drawer.open ) {
				return;
			}

			drawer.open = false;

			var summary = drawer.querySelector( 'summary' );

			// Put focus back where it came from, or it lands at the top of the page.
			if ( summary ) {
				summary.focus();
			}
		} );

		document.addEventListener( 'click', function ( event ) {

			if ( drawer.open && ! drawer.contains( event.target ) ) {
				drawer.open = false;
			}
		} );
	}


	/* ======================================================================
	   2. THE DOUBLE SUBMIT GUARD

	   See the note at the top of this file. This is the one that matters.
	   ====================================================================== */

	function guardForms() {

		all( 'form' ).forEach( function ( form ) {

			// Search and filter forms are GET. Resubmitting one is harmless.
			if ( ( form.method || 'get' ).toLowerCase() !== 'post' ) {
				return;
			}

			form.addEventListener( 'submit', function () {

				var buttons = all( 'button[type="submit"], input[type="submit"]', form );

				buttons.forEach( function ( button ) {

					/*
					 * Disabled after a tick rather than immediately.
					 * Disabling a submit button inside its own submit
					 * handler stops some browsers sending the button's
					 * own name and value, which breaks any form that
					 * depends on which button was pressed. The
					 * availability control is exactly such a form.
					 */
					window.setTimeout( function () {

						button.disabled = true;
						button.setAttribute( 'aria-busy', 'true' );

						if ( button.tagName === 'BUTTON' ) {
							button.dataset.kaLabel = button.textContent;
							button.textContent = text.loading || 'Loading';
						}
					}, 0 );
				} );

				/*
				 * If the page is still here after fifteen seconds the
				 * request has probably failed rather than succeeded, and
				 * a permanently dead button is worse than a double
				 * submission. Give it back.
				 */
				window.setTimeout( function () {

					buttons.forEach( function ( button ) {

						button.disabled = false;
						button.removeAttribute( 'aria-busy' );

						if ( button.dataset.kaLabel ) {
							button.textContent = button.dataset.kaLabel;
						}
					} );
				}, 15000 );
			} );
		} );
	}


	/* ======================================================================
	   3. FORM HELP
	   ====================================================================== */

	/**
	 * Keep phone fields to digits.
	 *
	 * People paste numbers with spaces, brackets and country codes. The
	 * server normalises all of that anyway, but stripping it as they type
	 * means the field they are looking at matches what will be saved,
	 * rather than silently changing after submit.
	 */
	function tidyPhoneFields() {

		all( 'input[type="tel"]' ).forEach( function ( field ) {

			field.addEventListener( 'input', function () {

				var cleaned = field.value.replace( /[^\d+\s-]/g, '' );

				if ( cleaned !== field.value ) {
					field.value = cleaned;
				}
			} );
		} );
	}

	/**
	 * Count remaining characters on long text fields.
	 *
	 * Only once somebody is close to the limit. A counter showing 1,847
	 * remaining from the moment the page loads is noise; one appearing at
	 * 50 left is information.
	 */
	function addCharacterCounters() {

		all( 'textarea[maxlength]' ).forEach( function ( field ) {

			var limit = parseInt( field.getAttribute( 'maxlength' ), 10 );

			if ( ! limit ) {
				return;
			}

			var counter = document.createElement( 'p' );

			counter.className = 'ka-hint ka-right';
			counter.setAttribute( 'aria-live', 'polite' );
			counter.hidden = true;

			field.parentNode.appendChild( counter );

			function update() {

				var left = limit - field.value.length;

				if ( left > 50 ) {
					counter.hidden = true;
					return;
				}

				counter.hidden = false;
				counter.textContent = String( left );
				counter.style.color = left < 10 ? 'var(--ka-busy)' : '';
			}

			field.addEventListener( 'input', update );
			update();
		} );
	}

	/**
	 * Warn before leaving a form somebody has started filling in.
	 *
	 * Only on the long ones, and only once something has actually been
	 * typed. A confirmation dialogue on every page is the sort of thing
	 * that trains people to dismiss dialogues without reading them.
	 */
	function guardLongForms() {

		var forms = all( '.ka-form' ).filter( function ( form ) {
			return all( 'input, textarea, select', form ).length > 6;
		} );

		if ( ! forms.length ) {
			return;
		}

		var touched = false;
		var sending = false;

		forms.forEach( function ( form ) {

			form.addEventListener( 'input', function () {
				touched = true;
			} );

			form.addEventListener( 'submit', function () {
				sending = true;
			} );
		} );

		window.addEventListener( 'beforeunload', function ( event ) {

			if ( ! touched || sending ) {
				return;
			}

			event.preventDefault();

			// Browsers ignore the message now and show their own.
			event.returnValue = '';
		} );
	}


	/* ======================================================================
	   4. CONNECTION

	   Worth handling properly. A person filling in a profile on a bus out
	   of Kohima will lose signal, and a form that fails silently teaches
	   them the site is broken.
	   ====================================================================== */

	function watchConnection() {

		var banner = null;

		function show() {

			if ( banner ) {
				return;
			}

			banner = document.createElement( 'div' );
			banner.className = 'ka-offline';
			banner.setAttribute( 'role', 'status' );
			banner.textContent = text.offline || 'You are offline. Check your connection.';

			banner.style.cssText = [
				'position:fixed',
				'left:0',
				'right:0',
				'bottom:0',
				'z-index:500',
				'padding:12px 16px',
				'text-align:center',
				'font-size:14px',
				'background:var(--ka-busy, #c0392b)',
				'color:#fff'
			].join( ';' );

			document.body.appendChild( banner );
		}

		function hide() {

			if ( ! banner ) {
				return;
			}

			banner.remove();
			banner = null;
		}

		window.addEventListener( 'offline', show );
		window.addEventListener( 'online', hide );

		if ( navigator.onLine === false ) {
			show();
		}
	}


	/* ======================================================================
	   5. SMALL THINGS
	   ====================================================================== */

	/**
	 * Mark the current tab in the bottom bar.
	 *
	 * The server already does this for exact matches. This catches the
	 * case where somebody is on a filtered version of a page, so Jobs
	 * still looks active while they are looking at jobs in Wokha.
	 */
	function markCurrentTab() {

		var path = window.location.pathname.replace( /\/+$/, '' );

		all( '.ka-tabbar__item' ).forEach( function ( link ) {

			if ( link.hasAttribute( 'aria-current' ) ) {
				return;
			}

			var target = link.pathname.replace( /\/+$/, '' );

			if ( target && target !== '' && path.indexOf( target ) === 0 ) {
				link.setAttribute( 'aria-current', 'page' );
			}
		} );
	}

	/**
	 * Stop a stale page showing the wrong state after a back navigation.
	 *
	 * Browsers restore a page from cache when somebody taps back, buttons
	 * included. A submit button left disabled by the guard above would
	 * come back still disabled, and the person would be stuck on a form
	 * they cannot send.
	 */
	function handleBackNavigation() {

		window.addEventListener( 'pageshow', function ( event ) {

			if ( ! event.persisted ) {
				return;
			}

			all( 'button[type="submit"], input[type="submit"]' ).forEach( function ( button ) {

				button.disabled = false;
				button.removeAttribute( 'aria-busy' );

				if ( button.dataset.kaLabel ) {
					button.textContent = button.dataset.kaLabel;
				}
			} );
		} );
	}

	/**
	 * Confirm anything marked as destructive.
	 *
	 * Add data-ka-confirm to a button and it asks first. Used on account
	 * deletion, where the typed confirmation is the real defence and this
	 * is simply one more moment to stop.
	 */
	function confirmDestructive() {

		all( '[data-ka-confirm]' ).forEach( function ( element ) {

			element.addEventListener( 'click', function ( event ) {

				var message = element.getAttribute( 'data-ka-confirm' ) || text.confirm || 'Are you sure?';

				if ( ! window.confirm( message ) ) {
					event.preventDefault();
					event.stopPropagation();
				}
			} );
		} );
	}


	/* ======================================================================
	   6. START
	   ====================================================================== */

	function start() {

		/*
		 * One try per enhancement, not one around all of them.
		 *
		 * These were in a single try block, which defeated the point of
		 * having one. A throw in the first function skipped every
		 * function after it, and the first function is setUpMenus, which
		 * collapses the menus and then attaches the buttons that open
		 * them again. Throwing halfway through it produced exactly the
		 * state the comment said must never happen: half the menu
		 * collapsed and no way to open it.
		 *
		 * Each one now fails alone and the rest still run.
		 */
		var enhancements = [
			setUpMenus,
			setUpDrawer,
			guardForms,
			handleBackNavigation,
			tidyPhoneFields,
			addCharacterCounters,
			guardLongForms,
			watchConnection,
			markCurrentTab,
			confirmDestructive
		];

		enhancements.forEach( function ( enhancement ) {

			try {
				enhancement();
			} catch ( error ) {

				/*
				 * Swallowed on purpose. Every one of these is an
				 * enhancement, and the page has to keep working without
				 * it on a browser nobody tested.
				 */
				if ( window.console && window.console.warn ) {
					window.console.warn( 'Kaam Ase:', error );
				}
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}

}() );
