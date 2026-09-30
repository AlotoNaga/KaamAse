<?php
/**
 * Header.
 *
 * The mobile menu here is a details element, not a JavaScript drawer.
 *
 * That is a deliberate choice. A details element opens and closes with
 * no script at all, is keyboard operable by default, and announces its
 * own expanded state to a screen reader without a single aria attribute.
 * On a connection where the script may never arrive, the menu still
 * works. JavaScript later adds closing on Escape and closing when you
 * tap outside, which are conveniences rather than requirements.
 *
 * Almost every theme builds this with a button, a hidden panel and a
 * script, and almost every one of them leaves the menu unreachable when
 * that script fails.
 *
 * @package Kaamase
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">

	<?php
	/*
	 * viewport-fit=cover lets the layout reach into the area behind the
	 * rounded corners and the home indicator on newer phones. The tab bar
	 * uses safe area insets to stay clear of it.
	 */
	?>
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

	<?php
	/*
	 * Stop iOS turning any run of digits into a call link.
	 *
	 * This matters here specifically. Wage figures, years of experience
	 * and job reference numbers all look like phone numbers to Safari,
	 * and it will happily style them as tappable calls. On a platform
	 * built around never exposing a phone number, a browser inventing
	 * call links is not acceptable.
	 */
	?>
	<meta name="format-detection" content="telephone=no">

	<?php
	// Colours the browser chrome on Android so the app feels like one piece.
	?>
	<meta name="theme-color" content="#10603a">
	<meta name="color-scheme" content="light">

	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="ka-skip" href="#ka-main"><?php esc_html_e( 'Skip to content', 'kaamase' ); ?></a>

<header class="ka-header" role="banner">
	<div class="ka-container">
		<div class="ka-header__bar">

			<?php
			/* ------------------------------------------------------------
			 * Logo
			 *
			 * Falls back to the site name as a wordmark. A missing logo
			 * should never leave an empty corner where the brand goes.
			 * ---------------------------------------------------------- */
			?>
			<?php if ( has_custom_logo() ) : ?>
				<div class="ka-header__logo">
					<?php the_custom_logo(); ?>
				</div>
			<?php else : ?>
				<a class="ka-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?>
				</a>
			<?php endif; ?>

			<?php
			/* ------------------------------------------------------------
			 * Desktop navigation
			 * ---------------------------------------------------------- */
			?>
			<nav class="ka-nav" aria-label="<?php esc_attr_e( 'Main menu', 'kaamase' ); ?>">
				<?php kaamase_nav( 'primary' ); ?>
			</nav>

			<?php
			/* ------------------------------------------------------------
			 * Account actions
			 *
			 * Post a job is the loud one because employers are the side
			 * that eventually pays. Workers reach their own actions from
			 * the tab bar at the bottom of the screen, which is where a
			 * thumb already is.
			 * ---------------------------------------------------------- */
			?>
			<div class="ka-header__actions ka-cluster ka-hide-sm">

				<?php
				/*
				 * Language first in the cluster, and an icon rather than
				 * a word, because the one person who needs this control
				 * is the one person who cannot read the word Language.
				 *
				 * Guarded because the theme must not fatal without the
				 * plugin. Clothes, not the body.
				 */
				if ( function_exists( 'kaamase_locale_picker' ) ) {
					echo kaamase_locale_picker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<?php if ( is_user_logged_in() ) : ?>

					<a class="ka-btn ka-btn--ghost ka-btn--sm" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">
						<?php esc_html_e( 'My account', 'kaamase' ); ?>
					</a>

					<a class="ka-btn ka-btn--action ka-btn--sm" href="<?php echo esc_url( home_url( '/post-job/' ) ); ?>">
						<?php esc_html_e( 'Post a job', 'kaamase' ); ?>
					</a>

				<?php else : ?>

					<a class="ka-btn ka-btn--ghost ka-btn--sm" href="<?php echo esc_url( wp_login_url() ); ?>">
						<?php esc_html_e( 'Sign in', 'kaamase' ); ?>
					</a>

					<a class="ka-btn ka-btn--action ka-btn--sm" href="<?php echo esc_url( home_url( '/post-job/' ) ); ?>">
						<?php esc_html_e( 'Post a job', 'kaamase' ); ?>
					</a>

				<?php endif; ?>

			</div>

			<?php
			/* ------------------------------------------------------------
			 * Mobile menu
			 *
			 * A details element. No script needed to open or close it.
			 * ---------------------------------------------------------- */
			?>
			<details class="ka-drawer ka-hide-lg" id="ka-drawer">

				<summary class="ka-menu-toggle" aria-label="<?php esc_attr_e( 'Menu', 'kaamase' ); ?>">
					<span class="ka-drawer__open" aria-hidden="true">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
					</span>
					<span class="ka-drawer__close" aria-hidden="true">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
					</span>
				</summary>

				<div class="ka-drawer__panel">
					<nav aria-label="<?php esc_attr_e( 'Mobile menu', 'kaamase' ); ?>">
						<?php kaamase_nav( 'mobile', array( 'style' => 'stack' ) ); ?>
					</nav>

					<div class="ka-drawer__actions ka-stack ka-mt-6">
						<?php if ( is_user_logged_in() ) : ?>

							<a class="ka-btn ka-btn--action ka-btn--block" href="<?php echo esc_url( home_url( '/post-job/' ) ); ?>">
								<?php esc_html_e( 'Post a job', 'kaamase' ); ?>
							</a>

							<a class="ka-btn ka-btn--outline ka-btn--block" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">
								<?php esc_html_e( 'My account', 'kaamase' ); ?>
							</a>

							<a class="ka-btn ka-btn--ghost ka-btn--block" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
								<?php esc_html_e( 'Sign out', 'kaamase' ); ?>
							</a>

						<?php else : ?>

							<a class="ka-btn ka-btn--action ka-btn--block" href="<?php echo esc_url( home_url( '/register/' ) ); ?>">
								<?php esc_html_e( 'Register free', 'kaamase' ); ?>
							</a>

							<a class="ka-btn ka-btn--outline ka-btn--block" href="<?php echo esc_url( wp_login_url() ); ?>">
								<?php esc_html_e( 'Sign in', 'kaamase' ); ?>
							</a>

						<?php endif; ?>
					</div>

					<?php
					/*
					 * And here, spelled out, because this is where most
					 * of this platform actually is. The header control
					 * above is hidden below the large breakpoint, so on
					 * a phone this row is the switcher, not a duplicate
					 * of it.
					 */
					if ( function_exists( 'kaamase_locale_picker' ) ) {
						echo kaamase_locale_picker( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							array(
								'style' => 'list',
								'class' => 'ka-lang--drawer ka-mt-6',
							)
						);
					}
					?>
				</div>

			</details>

		</div>
	</div>
</header>

<?php
/**
 * Fires immediately before the main content area.
 *
 * The core plugin hooks this for site wide notices, including the
 * dashboard banner that will prompt existing accounts to add a phone
 * number once phone verification ships.
 *
 * @since 1.0.0
 */
do_action( 'kaamase_before_main' );
?>

<main id="ka-main" class="ka-main" tabindex="-1">
