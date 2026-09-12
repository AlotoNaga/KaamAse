<?php
/**
 * The people who never confirmed their email.
 *
 * A list, with their phone numbers, so somebody at Kaam Ase can ring
 * them.
 *
 * Why this exists
 * ---------------
 * An account that has not confirmed its email has a profile nobody can
 * find. The person registered, so they wanted the work; they simply
 * never opened the link. Some of them do not use email at all and gave
 * an address because a form asked for one. Telling them again, by email,
 * that they should check their email is not a plan.
 *
 * There was no way to reach them. A phone number could only be read one
 * profile at a time, by opening that profile on the website and pressing
 * the reveal, and nothing anywhere said which accounts were unconfirmed.
 * So the people most in need of a phone call were the hardest to find.
 *
 * What this is not
 * ----------------
 * Not a directory of everybody's number. It lists only accounts that
 * have not confirmed, because that is the job it exists for, and a list
 * of every phone number on the platform sitting in wp-admin is a
 * liability nobody asked for. A confirmed worker's number is still read
 * the way it always was: open their profile and press reveal.
 *
 * Read through kaamase_field(), never the raw meta
 * ------------------------------------------------
 * kaamase_field() is the filtered reader with the privacy rule attached,
 * and it answers for administrators because kaamase_can_see_private()
 * says so. Going straight to post meta would work too and would be
 * wrong: it would put a second, unguarded route to a phone number into
 * the codebase, and the whole design of fields.php is that there is
 * exactly one. If those rights are ever narrowed, this screen empties
 * out on its own rather than carrying on regardless.
 *
 * No quota is spent. This does not go through kaamase_can_contact(),
 * because that counts reveals to guard against harvesting by strangers,
 * and this is the platform's own support work on its own accounts.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.4.2
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE MENU
   ========================================================================== */

if ( ! function_exists( 'kaamase_not_confirmed_menu' ) ) {
	/**
	 * Add the screen under Users.
	 *
	 * @since 1.4.2
	 * @return void
	 */
	function kaamase_not_confirmed_menu() {

		add_users_page(
			__( 'Not confirmed', 'kaamase-core' ),
			__( 'Not confirmed', 'kaamase-core' ),
			'manage_options',
			'kaamase-not-confirmed',
			'kaamase_not_confirmed_page'
		);
	}
}
add_action( 'admin_menu', 'kaamase_not_confirmed_menu' );


/* ==========================================================================
   2. FINDING THEM
   ========================================================================== */

if ( ! function_exists( 'kaamase_not_confirmed_users' ) ) {
	/**
	 * Accounts with a profile that have not confirmed their email.
	 *
	 * @since 1.4.2
	 * @param int $paged Page number.
	 * @param int $per   Rows per page.
	 * @return array {
	 *     @type array[] $rows  One row per account.
	 *     @type int     $total How many there are in all.
	 * }
	 */
	function kaamase_not_confirmed_users( $paged = 1, $per = 50 ) {

		$paged = max( 1, (int) $paged );

		/*
		 * NOT EXISTS rather than a comparison. The meta key is written
		 * the moment somebody confirms and does not exist before that,
		 * so an account that has never confirmed has no row at all
		 * rather than an empty one.
		 */
		$query = new WP_User_Query(
			array(
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'kaamase_verified_at',
						'compare' => 'NOT EXISTS',
					),
				),
				'orderby'     => 'registered',
				'order'       => 'DESC',
				'number'      => $per,
				'paged'       => $paged,
				'count_total' => true,
			)
		);

		$rows = array();

		foreach ( (array) $query->get_results() as $user ) {

			$profile = 0;
			$kind    = '';

			foreach ( array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' ) as $type ) {

				$found = kaamase_get_user_profile( $user->ID, $type );

				if ( $found ) {
					$profile = (int) $found;
					$kind    = $type;
					break;
				}
			}

			/*
			 * No profile means nothing to ring about. This also keeps
			 * staff accounts off the list without naming any role, since
			 * an administrator has no worker or employer profile.
			 */
			if ( ! $profile ) {
				continue;
			}

			$rows[] = array(
				'user_id'    => (int) $user->ID,
				'name'       => get_the_title( $profile ) ? get_the_title( $profile ) : $user->display_name,
				'kind'       => $kind,
				'profile'    => $profile,
				'phone'      => (string) kaamase_field( $profile, 'phone' ),
				'email'      => (string) $user->user_email,
				'district'   => (string) kaamase_field( $profile, 'district' ),
				'town'       => (string) kaamase_field( $profile, 'town' ),
				'registered' => (string) $user->user_registered,
				'status'     => (string) get_post_status( $profile ),
			);
		}

		return array(
			'rows'  => $rows,
			'total' => (int) $query->get_total(),
		);
	}
}


/* ==========================================================================
   3. THE SCREEN
   ========================================================================== */

if ( ! function_exists( 'kaamase_not_confirmed_page' ) ) {
	/**
	 * Render the list.
	 *
	 * @since 1.4.2
	 * @return void
	 */
	function kaamase_not_confirmed_page() {

		/*
		 * Checked again here, not only when the menu was added.
		 * add_users_page() hides the link from anybody without the
		 * capability but does not stop a request typed straight at the
		 * URL, and this page prints phone numbers.
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot see this page.', 'kaamase-core' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading a page number, changes nothing.
		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$per    = 50;
		$result = kaamase_not_confirmed_users( $paged, $per );
		$pages  = (int) ceil( $result['total'] / $per );

		$kinds = array(
			'kaamase_worker'   => __( 'Worker', 'kaamase-core' ),
			'kaamase_gang'     => __( 'Team', 'kaamase-core' ),
			'kaamase_employer' => __( 'Employer', 'kaamase-core' ),
		);
		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Not confirmed', 'kaamase-core' ); ?></h1>

			<p class="description">
				<?php
				esc_html_e( 'These people registered but never opened the link in their email, so their profile is hidden and nobody can find them. Ring them, explain what the link is for, and they can confirm while you are on the phone.', 'kaamase-core' );
				?>
			</p>

			<?php if ( empty( $result['rows'] ) ) : ?>

				<div class="notice notice-success inline">
					<p><?php esc_html_e( 'Nobody is waiting. Every account with a profile has confirmed its email.', 'kaamase-core' ); ?></p>
				</div>

			<?php else : ?>

				<p>
					<?php
					printf(
						esc_html(
							/* translators: %s: number of accounts */
							_n( '%s account has not confirmed.', '%s accounts have not confirmed.', (int) $result['total'], 'kaamase-core' )
						),
						esc_html( number_format_i18n( (int) $result['total'] ) )
					);
					?>
				</p>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Kind', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Phone', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Where', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Email', 'kaamase-core' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Registered', 'kaamase-core' ); ?></th>
						</tr>
					</thead>

					<tbody>
						<?php foreach ( $result['rows'] as $row ) : ?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( (string) get_edit_post_link( $row['profile'] ) ); ?>">
											<?php echo esc_html( $row['name'] ); ?>
										</a>
									</strong>
								</td>

								<td><?php echo esc_html( $kinds[ $row['kind'] ] ?? $row['kind'] ); ?></td>

								<td>
									<?php if ( '' !== $row['phone'] ) : ?>
										<?php
										/*
										 * A tel: link, because this screen is
										 * opened in order to make a call and
										 * whoever is making it may well be on
										 * a phone themselves.
										 */
										?>
										<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $row['phone'] ) ); ?>">
											<?php echo esc_html( $row['phone'] ); ?>
										</a>
									<?php else : ?>
										<span class="description"><?php esc_html_e( 'No number given', 'kaamase-core' ); ?></span>
									<?php endif; ?>
								</td>

								<td>
									<?php
									$where = function_exists( 'kaamase_district_name' )
										? (string) kaamase_district_name( $row['district'] )
										: $row['district'];

									if ( '' !== $row['town'] ) {
										$where = $where ? $row['town'] . ', ' . $where : $row['town'];
									}

									echo esc_html( $where ? $where : '—' );
									?>
								</td>

								<td><?php echo esc_html( $row['email'] ); ?></td>

								<td>
									<?php echo esc_html( mysql2date( get_option( 'date_format' ), $row['registered'] ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<?php
							echo wp_kses_post(
								paginate_links(
									array(
										'base'    => add_query_arg( 'paged', '%#%' ),
										'format'  => '',
										'current' => $paged,
										'total'   => $pages,
									)
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>

			<?php endif; ?>

		</div>
		<?php
	}
}
