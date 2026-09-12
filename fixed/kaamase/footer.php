<?php
/**
 * Footer.
 *
 * The footer on this site does more work than a footer usually does.
 *
 * A worker in Zunheboto being told about a new hiring website has one
 * question in their head, and it is not about features. It is whether
 * this is a real thing or another way to lose money. The registered
 * company name, the GST number and a real address in Dimapur answer
 * that question better than any amount of copy on the landing page.
 * Those details are why this section exists, not because a designer put
 * a footer in a template.
 *
 * The grievance line is here for the same reason, plus one more: once
 * real users are on the platform there will be wage disputes, no shows
 * and at least one bad actor. A named route to a human being is what
 * separates a platform that handles that from a platform that becomes
 * the subject of a Facebook post.
 *
 * @package Kaamase
 * @version 1.0.2
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

?>
</main><?php // Opened in header.php. ?>

<?php
/**
 * Fires immediately after the main content area.
 *
 * @since 1.0.0
 */
do_action( 'kaamase_after_main' );
?>

<footer class="ka-footer" role="contentinfo">
	<div class="ka-container">

		<div class="ka-footer__grid">

			<?php
			/* ------------------------------------------------------------
			 * Brand and purpose
			 * ---------------------------------------------------------- */
			?>
			<div class="ka-footer__brand">
				<p class="ka-footer__title"><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></p>

				<p>
					<?php
					esc_html_e(
						'Find work and find workers across Nagaland. Free for every worker, always.',
						'kaamase'
					);
					?>
				</p>
			</div>

			<?php
			/* ------------------------------------------------------------
			 * Browse
			 *
			 * Each column falls back to hardcoded links when no menu has
			 * been assigned. A fresh install should never show three
			 * empty footer columns, because the first time you show this
			 * to somebody is exactly when no menus are set up yet.
			 * ---------------------------------------------------------- */
			?>
			<div>
				<p class="ka-footer__title"><?php esc_html_e( 'Browse', 'kaamase' ); ?></p>

				<?php if ( has_nav_menu( 'browse' ) ) : ?>
					<nav aria-label="<?php esc_attr_e( 'Browse', 'kaamase' ); ?>">
						<?php kaamase_nav( 'browse', array( 'style' => 'stack', 'depth' => 1 ) ); ?>
					</nav>
				<?php else : ?>
					<ul class="ka-stack ka-stack--sm">
						<li><a href="<?php echo esc_url( home_url( '/workers/' ) ); ?>"><?php esc_html_e( 'Find workers', 'kaamase' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/jobs/' ) ); ?>"><?php esc_html_e( 'Find work', 'kaamase' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/teams/' ) ); ?>"><?php esc_html_e( 'Hire a team', 'kaamase' ); ?></a></li>
						<?php // Signed in only, because signed out it leads to a sign in wall. ?>
						<?php if ( is_user_logged_in() ) : ?>
							<li><a href="<?php echo esc_url( home_url( '/employers/' ) ); ?>"><?php esc_html_e( 'Who is hiring', 'kaamase' ); ?></a></li>
						<?php endif; ?>
						<li><a href="<?php echo esc_url( home_url( '/trades/' ) ); ?>"><?php esc_html_e( 'All trades', 'kaamase' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/districts/' ) ); ?>"><?php esc_html_e( 'All districts', 'kaamase' ); ?></a></li>
					</ul>
				<?php endif; ?>
			</div>

			<?php
			/* ------------------------------------------------------------
			 * About
			 * ---------------------------------------------------------- */
			?>
			<div>
				<p class="ka-footer__title"><?php esc_html_e( 'Kaam Ase', 'kaamase' ); ?></p>

				<?php if ( has_nav_menu( 'company' ) ) : ?>
					<nav aria-label="<?php esc_attr_e( 'About', 'kaamase' ); ?>">
						<?php kaamase_nav( 'company', array( 'style' => 'stack', 'depth' => 1 ) ); ?>
					</nav>
				<?php else : ?>
					<ul class="ka-stack ka-stack--sm">
						<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About us', 'kaamase' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/how-it-works/' ) ); ?>"><?php esc_html_e( 'How it works', 'kaamase' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/safety/' ) ); ?>"><?php esc_html_e( 'Safety', 'kaamase' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'kaamase' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/past-jobs/' ) ); ?>"><?php esc_html_e( 'Past Job', 'kaamase' ); ?></a></li>
					</ul>
				<?php endif; ?>
			</div>

			<?php
			/* ------------------------------------------------------------
			 * Help and grievance
			 *
			 * Deliberately its own column rather than a line buried in
			 * the legal row. If somebody has been cheated out of a day's
			 * wage, finding this should take one look, not three clicks.
			 * ---------------------------------------------------------- */
			?>
			<div class="ka-footer__help">
				<p class="ka-footer__title"><?php esc_html_e( 'Help', 'kaamase' ); ?></p>

				<ul class="ka-stack ka-stack--sm">
					<li>
						<a href="<?php echo esc_url( home_url( '/report/' ) ); ?>">
							<?php esc_html_e( 'Report a problem', 'kaamase' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/grievance/' ) ); ?>">
							<?php esc_html_e( 'Wage or job complaint', 'kaamase' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( home_url( '/help/' ) ); ?>">
							<?php esc_html_e( 'Help centre', 'kaamase' ); ?>
						</a>
					</li>
				</ul>

				<p class="ka-small ka-mt-4">
					<?php
					esc_html_e(
						'Kaam Ase never asks a worker for money to get a job. If anyone asks, report it.',
						'kaamase'
					);
					?>
				</p>
			</div>

		</div>

		<?php
		/* ----------------------------------------------------------------
		 * Legal and registration
		 *
		 * The company registration details are the trust signal. Leave
		 * them in.
		 * -------------------------------------------------------------- */
		?>
		<div class="ka-footer__legal">

			<?php if ( has_nav_menu( 'legal' ) ) : ?>
				<nav class="ka-cluster ka-mb-4" aria-label="<?php esc_attr_e( 'Legal', 'kaamase' ); ?>">
					<?php kaamase_nav( 'legal', array( 'depth' => 1 ) ); ?>
				</nav>
			<?php else : ?>
				<ul class="ka-cluster ka-mb-4">
					<li><a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'kaamase' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms', 'kaamase' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/safety/' ) ); ?>"><?php esc_html_e( 'Safety policy', 'kaamase' ); ?></a></li>
				</ul>
			<?php endif; ?>

			<p>
				<?php
				printf(
					/* translators: 1: current year, 2: site name */
					esc_html__( 'Copyright %1$s %2$s', 'kaamase' ),
					esc_html( gmdate( 'Y' ) ),
					esc_html( get_bloginfo( 'name', 'display' ) )
				);
				?>
			</p>

			<p>
				<?php esc_html_e( 'A venture of Nagaland Me', 'kaamase' ); ?>
				<span aria-hidden="true">&middot;</span>
				<?php esc_html_e( 'GST: 13DIHPA5679B1ZK', 'kaamase' ); ?>
				<span aria-hidden="true">&middot;</span>
				<?php esc_html_e( 'Dimapur, Nagaland, India', 'kaamase' ); ?>
			</p>

			<p>
				<?php
				esc_html_e(
					'Kaam Ase is a listing platform. We connect workers and employers. We are not the employer and we do not pay wages.',
					'kaamase'
				);
				?>
			</p>
		</div>

	</div>
</footer>

<?php
/*
 * Bottom tab bar.
 *
 * Printed last in the document so that a screen reader and a keyboard
 * user reach the page content before the navigation, while a phone user
 * sees it fixed to the bottom of the screen where their thumb is. Both
 * get the right order for how they actually use the page.
 */
if ( function_exists( 'kaamase_tabbar' ) ) {
	kaamase_tabbar();
}
?>

<?php wp_footer(); ?>
</body>
</html>