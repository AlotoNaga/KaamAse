<?php
/**
 * Saved.
 *
 * Two lists that look similar and do different jobs.
 *
 * Saved is what somebody chose to come back to. Worked with before is
 * built from the hire record without anybody having to remember to save
 * anything, and it is the more valuable of the two.
 *
 * Most hiring is rehiring. A contractor who needs a mason in March
 * usually wants the mason he used in January, and what he actually does
 * is scroll back through his call history looking for a number he cannot
 * place. That list, sitting on one screen with a call button next to
 * each name, is worth more than any search feature on the roadmap.
 *
 * Saving does not cost a contact lookup
 * -------------------------------------
 * Deliberate. If saving burned quota, nobody would browse, and an
 * employer would only ever open the profiles they were already going to
 * call. Saving is free and private. Contacting is limited and logged.
 * Keeping those separate is what makes the save button worth having.
 *
 * The worker is never told they were saved
 * ----------------------------------------
 * Also deliberate. Being told that a stranger bookmarked you is
 * unsettling and tells you nothing useful, because most saves never
 * become a call. A contact reveal is different: that one is shown,
 * because somebody now has your number.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. STORAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_saved_limit' ) ) {
	/**
	 * How many things one account may keep saved.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	function kaamase_saved_limit() {

		/**
		 * Filter the saved item limit.
		 *
		 * @since 1.0.0
		 * @param int $limit Maximum saved items.
		 */
		return absint( apply_filters( 'kaamase_saved_limit', 200 ) );
	}
}

if ( ! function_exists( 'kaamase_get_saved' ) ) {
	/**
	 * Everything a user has saved.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return int[] Post IDs, most recently saved first.
	 */
	function kaamase_get_saved( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return array();
		}

		$saved = get_user_meta( $user_id, 'kaamase_saved', true );

		return array_values( array_filter( array_map( 'absint', kaamase_meta_array( $saved ) ) ) );
	}
}

if ( ! function_exists( 'kaamase_is_saved' ) ) {
	/**
	 * Whether something is saved.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return bool
	 */
	function kaamase_is_saved( $post_id, $user_id = 0 ) {
		return in_array( absint( $post_id ), kaamase_get_saved( $user_id ), true );
	}
}

if ( ! function_exists( 'kaamase_handle_save' ) ) {
	/**
	 * Save or unsave something.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_handle_save() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'toggle_saved' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_save_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_save_nonce'] ) ), 'kaamase_save' )
		) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$post_id = isset( $_POST['kaamase_save_id'] ) ? absint( $_POST['kaamase_save_id'] ) : 0;
		$back    = isset( $_POST['kaamase_back'] ) ? esc_url_raw( wp_unslash( $_POST['kaamase_back'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, kaamase_post_types(), true ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$saved   = kaamase_get_saved( $user_id );

		if ( in_array( $post_id, $saved, true ) ) {

			$saved = array_values( array_diff( $saved, array( $post_id ) ) );

		} else {

			// Newest first, so the list reads in the order somebody worked through it.
			array_unshift( $saved, $post_id );
			$saved = array_slice( array_unique( $saved ), 0, kaamase_saved_limit() );
		}

		update_user_meta( $user_id, 'kaamase_saved', $saved );

		/*
		 * Back to wherever they were. wp_safe_redirect refuses anything
		 * off site, so a tampered value cannot bounce somebody elsewhere.
		 */
		wp_safe_redirect( $back ? $back : get_permalink( $post_id ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_save' );


/* ==========================================================================
   2. THE BUTTON
   ========================================================================== */

if ( ! function_exists( 'kaamase_save_button' ) ) {
	/**
	 * Render a save or unsave button.
	 *
	 * A form rather than a script, so it works on a browser where the
	 * JavaScript never arrived. Slower than an instant toggle and it
	 * always works, which on these connections is the better trade.
	 *
	 * @since 1.0.0
	 * @param int    $post_id     Post ID.
	 * @param bool   $compact     Whether to render the small version.
	 * @param string $saved_label What to say when it is already saved.
	 * @return string Markup.
	 */
	function kaamase_save_button( $post_id, $compact = false, $saved_label = '' ) {

		if ( ! is_user_logged_in() ) {
			return '';
		}

		$post_id = absint( $post_id );
		$saved   = kaamase_is_saved( $post_id );
		$job     = 'kaamase_job' === get_post_type( $post_id );

		if ( $saved ) {

			/*
			 * On a profile the button says Saved, because it is
			 * reporting a state and pressing it again undoes that.
			 *
			 * On the saved list itself that reading collapses:
			 * everything there is saved by definition, so a row of
			 * buttons all saying Saved says nothing, and the one thing
			 * somebody came to that page to do has no name on it. The
			 * caller passes the word it needs.
			 */
			$label = '' !== $saved_label ? $saved_label : __( 'Saved', 'kaamase-core' );
			$class = '' !== $saved_label ? 'ka-btn--outline' : 'ka-btn--primary';

		} else {
			$label = $job ? __( 'Save this job', 'kaamase-core' ) : __( 'Save', 'kaamase-core' );
			$class = 'ka-btn--outline';
		}

		ob_start();
		?>
		<?php
		/*
		 * Inline beside whatever it sits next to on a profile, block
		 * when it is a row of its own on the saved list. The old markup
		 * carried display:inline as an inline style, which no
		 * stylesheet can override without shouting, so the choice is
		 * made here where it is known.
		 */
		?>
		<form method="post" action="" class="ka-save-form"
			style="display:<?php echo '' !== $saved_label ? 'block' : 'inline'; ?>;">
			<?php wp_nonce_field( 'kaamase_save', 'kaamase_save_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="toggle_saved">
			<input type="hidden" name="kaamase_save_id" value="<?php echo esc_attr( $post_id ); ?>">
			<input type="hidden" name="kaamase_back" value="<?php echo esc_attr( kaamase_current_path_url() ); ?>">

			<button type="submit" class="ka-btn <?php echo esc_attr( $class ); ?> <?php echo $compact ? 'ka-btn--sm' : ''; ?>"
				aria-pressed="<?php echo $saved ? 'true' : 'false'; ?>">
				<?php echo esc_html( $label ); ?>
			</button>
		</form>
		<?php

		return (string) ob_get_clean();
	}
}

/**
 * Put a save button on single job and profile pages.
 *
 * The cards in listings do not carry one. That is a deliberate omission
 * rather than an oversight: a grid of cards each with a save button is a
 * grid where a thumb hits save while scrolling, and the useful decision
 * to save something is made after reading the page, not while passing it.
 *
 * @since 1.0.0
 * @param string $content Post content.
 * @return string Filtered content.
 */
function kaamase_append_save_button( $content ) {

	/*
	 * Employers belong on this list too.
	 *
	 * kaamase_post_types() has always included them, so the toggle
	 * handler and the app endpoint both accepted an employer happily.
	 * Only the website never offered the button, which left a worker
	 * able to end up with a saved employer through the app and no way
	 * to remove it from a browser.
	 */
	if ( ! is_singular( array( 'kaamase_job', 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' ) ) || ! is_main_query() ) {
		return $content;
	}

	if ( ! is_user_logged_in() || kaamase_user_owns( get_the_ID() ) ) {
		return $content;
	}

	return $content . '<div class="ka-mt-6">' . kaamase_save_button( get_the_ID() ) . '</div>';
}
add_filter( 'the_content', 'kaamase_append_save_button', 20 );


/* ==========================================================================
   3. THE REHIRE LIST
   ========================================================================== */

if ( ! function_exists( 'kaamase_worked_with' ) ) {
	/**
	 * People this user has worked with before.
	 *
	 * Built from the hire record, so nobody has to remember to save
	 * anything. Most valuable list on the platform for an employer, and
	 * for a worker it is the record of who has actually paid them.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return array[] Entries with post_id and time, newest first.
	 */
	function kaamase_worked_with( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return array();
		}

		$type = kaamase_get_user_type( $user_id );
		$out  = array();

		if ( 'employer' === $type ) {

			/*
			 * Workers this employer hired. The hire record lives on the
			 * worker's profile, so this is a meta lookup for the
			 * employer's own ID across worker profiles.
			 */
			global $wpdb;

			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta}
					WHERE meta_key = %s AND meta_value LIKE %s LIMIT 200",
					KAAMASE_META_PREFIX . 'hires',
					'%' . $wpdb->esc_like( '"employer";i:' . $user_id . ';' ) . '%'
				)
			);

			foreach ( (array) $rows as $row ) {

				$hires = maybe_unserialize( $row->meta_value );

				if ( ! is_array( $hires ) ) {
					continue;
				}

				foreach ( $hires as $hire ) {

					if ( absint( $hire['employer'] ) !== $user_id ) {
						continue;
					}

					$out[] = array(
						'post_id' => (int) $row->post_id,
						'time'    => (int) $hire['time'],
					);
				}
			}
		} else {

			// Employers this worker worked for.
			$worker = kaamase_get_user_profile( $user_id, 'kaamase_worker' );

			if ( ! $worker ) {
				return array();
			}

			$hires = kaamase_meta_array( get_post_meta( $worker, KAAMASE_META_PREFIX . 'hires', true ) );

			foreach ( $hires as $hire ) {

				$profile = kaamase_get_user_profile( absint( $hire['employer'] ), 'kaamase_employer' );

				if ( ! $profile ) {
					continue;
				}

				$out[] = array(
					'post_id' => $profile,
					'time'    => (int) $hire['time'],
				);
			}
		}

		// Newest first, one entry per person.
		usort(
			$out,
			static function ( $a, $b ) {
				return $b['time'] <=> $a['time'];
			}
		);

		$seen   = array();
		$unique = array();

		foreach ( $out as $entry ) {

			if ( isset( $seen[ $entry['post_id'] ] ) ) {
				continue;
			}

			$seen[ $entry['post_id'] ] = true;
			$unique[]                  = $entry;
		}

		return $unique;
	}
}


/* ==========================================================================
   4. THE PAGE
   ========================================================================== */

if ( ! function_exists( 'kaamase_saved_shortcode' ) ) {
	/**
	 * Render the saved page.
	 *
	 * @since 1.0.0
	 * @return string Markup.
	 */
	function kaamase_saved_shortcode() {

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="ka-notice ka-notice--info"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
				esc_html__( 'Sign in to see the things you saved.', 'kaamase-core' ),
				esc_url( wp_login_url( kaamase_page_url( 'saved' ) ) ),
				esc_html__( 'Sign in', 'kaamase-core' )
			);
		}

		$type = kaamase_get_user_type();

		ob_start();

		echo kaamase_rehire_section( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo kaamase_saved_section( $type );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return (string) ob_get_clean();
	}
}
add_shortcode( 'kaamase_saved', 'kaamase_saved_shortcode' );

if ( ! function_exists( 'kaamase_worked_hidden' ) ) {
	/**
	 * People this user has taken off their own worked with list.
	 *
	 * @since 1.6.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return int[] Profile IDs.
	 */
	function kaamase_worked_hidden( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		if ( ! $user_id ) {
			return array();
		}

		return array_map( 'absint', kaamase_meta_array( get_user_meta( $user_id, 'kaamase_worked_hidden', true ) ) );
	}
}

if ( ! function_exists( 'kaamase_worked_with_visible' ) ) {
	/**
	 * The worked with list as this person has chosen to keep it.
	 *
	 * A separate function rather than a filter inside
	 * kaamase_worked_with(), and the reason is hires.php.
	 *
	 * kaamase_pending_ratings() walks that same list to work out who
	 * somebody still owes a rating. Filtering at the source would mean
	 * that tidying a name off a list also, silently, stopped the
	 * platform ever asking about them again. Ratings are the thing this
	 * whole platform runs on and they are not a display preference.
	 *
	 * So the record stays whole and only the screen is filtered.
	 * Somebody may still be asked to rate a person they have hidden,
	 * once, which is the right way round: the list is theirs to tidy,
	 * the rating is owed to the other person.
	 *
	 * @since 1.6.0
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return array[] Entries with post_id and time, newest first.
	 */
	function kaamase_worked_with_visible( $user_id = 0 ) {

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		$hidden  = kaamase_worked_hidden( $user_id );

		if ( empty( $hidden ) ) {
			return kaamase_worked_with( $user_id );
		}

		return array_values(
			array_filter(
				kaamase_worked_with( $user_id ),
				static function ( $entry ) use ( $hidden ) {
					return ! in_array( (int) $entry['post_id'], $hidden, true );
				}
			)
		);
	}
}

if ( ! function_exists( 'kaamase_handle_worked_hide' ) ) {
	/**
	 * Take somebody off the worked with list, or put them back.
	 *
	 * Nothing is deleted. The hire itself is what ratings are allowed
	 * on, what the standing lines count, and for a worker it is the
	 * record of who has actually paid them. A button on a list must not
	 * be able to destroy that, so this writes a note against the person
	 * doing the hiding and leaves the hire exactly where it is. The
	 * other side's list is untouched.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_handle_worked_hide() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['kaamase_action'] ) || 'worked_hide' !== $_POST['kaamase_action'] ) {
			return;
		}

		if (
			! is_user_logged_in()
			|| empty( $_POST['kaamase_worked_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kaamase_worked_nonce'] ) ), 'kaamase_worked_hide' )
		) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_id = isset( $_POST['kaamase_worked_id'] ) ? absint( $_POST['kaamase_worked_id'] ) : 0;

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, kaamase_post_types(), true ) ) {
			return;
		}

		$user_id = get_current_user_id();
		$hidden  = kaamase_worked_hidden( $user_id );

		if ( ! in_array( $post_id, $hidden, true ) ) {

			$hidden[] = $post_id;

			/*
			 * Capped for the same reason the saved list is. Somebody
			 * cannot have hidden more people than they have worked
			 * with, but a stored list with no ceiling is a stored list
			 * that eventually costs something to read.
			 */
			$hidden = array_slice( array_values( array_unique( $hidden ) ), -500 );

			update_user_meta( $user_id, 'kaamase_worked_hidden', $hidden );
		}

		wp_safe_redirect( kaamase_page_url( 'saved' ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_handle_worked_hide' );

if ( ! function_exists( 'kaamase_worked_hide_button' ) ) {
	/**
	 * The control that takes one person off the list.
	 *
	 * Says Remove rather than Hide. Hide invites the question of who
	 * else can still see it, and the answer is that this was never
	 * anybody else's list to see.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile ID.
	 * @return string Markup.
	 */
	function kaamase_worked_hide_button( $post_id ) {

		if ( ! is_user_logged_in() ) {
			return '';
		}

		ob_start();
		?>
		<form method="post" action="" class="ka-save-form" style="display:block;">
			<?php wp_nonce_field( 'kaamase_worked_hide', 'kaamase_worked_nonce' ); ?>
			<input type="hidden" name="kaamase_action" value="worked_hide">
			<input type="hidden" name="kaamase_worked_id" value="<?php echo esc_attr( absint( $post_id ) ); ?>">

			<button type="submit" class="ka-btn ka-btn--outline ka-btn--sm">
				<?php esc_html_e( 'Remove', 'kaamase-core' ); ?>
			</button>
		</form>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_rehire_section' ) ) {
	/**
	 * Render the worked with before list.
	 *
	 * @since 1.0.0
	 * @param string $type User type.
	 * @return string Markup.
	 */
	function kaamase_rehire_section( $type ) {

		$entries = kaamase_worked_with_visible();

		if ( empty( $entries ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="ka-section">

			<h2>
				<?php
				echo 'employer' === $type
					? esc_html__( 'Workers you have hired before', 'kaamase-core' )
					: esc_html__( 'People you have worked for', 'kaamase-core' );
				?>
			</h2>

			<p class="ka-small ka-soft ka-mt-4">
				<?php
				echo 'employer' === $type
					? esc_html__( 'Most hiring is hiring the same person again. They are all here.', 'kaamase-core' )
					: esc_html__( 'Everybody you have done a job for through Kaam Ase.', 'kaamase-core' );
				?>
			</p>

			<div class="ka-grid ka-grid--2 ka-grid--3 ka-mt-6">
				<?php
				foreach ( array_slice( $entries, 0, 12 ) as $entry ) :

					$post = get_post( $entry['post_id'] );

					if ( ! $post || 'publish' !== $post->post_status ) {
						continue;
					}
					?>
					<div class="ka-saved-item">
						<?php
						if ( 'kaamase_gang' === $post->post_type ) {
							kaamase_gang_card( $post->ID );
						} elseif ( 'kaamase_worker' === $post->post_type ) {
							kaamase_worker_card( $post->ID );
						} else {
							kaamase_employer_card( $post->ID, $entry['time'] );
						}
						?>

						<?php
						/*
						 * Twelve at a time, and removing one lets the
						 * thirteenth up. Somebody with a long history
						 * can therefore work through the whole list
						 * even though only a dozen are ever on screen,
						 * which is why this section did not also need
						 * its cap lifting.
						 */
						?>
						<div class="ka-saved-item__act">
							<?php echo kaamase_worked_hide_button( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
					<?php

				endforeach;
				?>
			</div>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'kaamase_employer_card' ) ) {
	/**
	 * A simple card for an employer.
	 *
	 * The theme has cards for workers, teams and jobs but not employers,
	 * because employers are never browsed. They are only ever looked up,
	 * which is what this list is.
	 *
	 * @since 1.0.0
	 * @param int $post_id Employer profile ID.
	 * @param int $when    Timestamp of the hire.
	 * @return void
	 */
	function kaamase_employer_card( $post_id, $when = 0 ) {
		?>
		<article class="ka-card ka-card--link">

			<div class="ka-card__head">
				<?php echo kaamase_avatar( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<div>
					<h3>
						<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
							<?php echo esc_html( get_the_title( $post_id ) ); ?>
						</a>
					</h3>

					<?php echo kaamase_place( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php if ( $when ) : ?>
						<p class="ka-small ka-mute">
							<?php
							printf(
								/* translators: %s: how long ago */
								esc_html__( 'Worked together %s ago', 'kaamase-core' ),
								esc_html( human_time_diff( $when, time() ) )
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<div class="ka-card__foot ka-cluster ka-cluster--between">
				<?php
				echo kaamase_rating( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					kaamase_field( $post_id, 'rating_average', 0 ),
					kaamase_field( $post_id, 'rating_count', 0 )
				);
				echo kaamase_contact_button( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>

		</article>
		<?php
	}
}

if ( ! function_exists( 'kaamase_saved_section' ) ) {
	/**
	 * Render the saved list.
	 *
	 * Closed jobs are shown, greyed, rather than dropped. Somebody who
	 * saved five jobs and comes back to find two would think the site
	 * lost them. Showing the closed ones with a plain label answers the
	 * question before it gets asked.
	 *
	 * @since 1.0.0
	 * @param string $type User type.
	 * @return string Markup.
	 */
	function kaamase_saved_section( $type ) {

		$saved = kaamase_get_saved();

		ob_start();
		?>
		<section class="ka-section">

			<h2><?php esc_html_e( 'Saved', 'kaamase-core' ); ?></h2>

			<?php
			if ( empty( $saved ) ) :

				kaamase_empty(
					array(
						'title'        => __( 'Nothing saved yet', 'kaamase-core' ),
						'text'         => 'employer' === $type
							? __( 'When you find a worker you might use later, open their profile and tap Save. Saving is free and it does not use a contact lookup.', 'kaamase-core' )
							: __( 'When you see a job worth coming back to, open it and tap Save this job.', 'kaamase-core' ),
						'button_label' => 'employer' === $type
							? __( 'Find workers', 'kaamase-core' )
							: __( 'Find work', 'kaamase-core' ),
						'button_url'   => 'employer' === $type ? home_url( '/workers/' ) : home_url( '/jobs/' ),
					)
				);

			else :

				$live   = array();
				$closed = array();

				foreach ( $saved as $post_id ) {

					$post = get_post( $post_id );

					if ( ! $post ) {
						continue;
					}

					if ( 'kaamase_job' === $post->post_type && ! kaamase_job_is_open( $post_id ) ) {
						$closed[] = $post;
						continue;
					}

					if ( 'publish' !== $post->post_status ) {
						$closed[] = $post;
						continue;
					}

					$live[] = $post;
				}
				?>

				<?php if ( ! empty( $live ) ) : ?>
					<div class="ka-grid ka-grid--2 ka-grid--3 ka-mt-6">
						<?php
						foreach ( $live as $post ) :
							?>
							<div class="ka-saved-item">
								<?php
								switch ( $post->post_type ) {
									case 'kaamase_job':
										kaamase_job_card( $post->ID );
										break;
									case 'kaamase_gang':
										kaamase_gang_card( $post->ID );
										break;

									/*
									 * Employers had no case here and fell to
									 * the default, so anybody who saved one
									 * had it drawn as a worker: a day rate, a
									 * trade and an availability light, none of
									 * which an employer has. The card for them
									 * already existed a few functions up and
									 * was only ever called from the worked
									 * with list.
									 */
									case 'kaamase_employer':
										kaamase_employer_card( $post->ID );
										break;

									default:
										kaamase_worker_card( $post->ID );
										break;
								}
								?>

								<?php
								/*
								 * Removing something was only possible from
								 * the thing's own page, which meant opening a
								 * profile in order to say you did not want to
								 * keep it. The closed items below this have
								 * carried the button all along; the live ones,
								 * which are the ones anybody actually revisits,
								 * never did.
								 */
								?>
								<div class="ka-saved-item__act">
									<?php
									echo kaamase_save_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										$post->ID,
										true,
										__( 'Remove', 'kaamase-core' )
									);
									?>
								</div>
							</div>
							<?php
						endforeach;
						?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $closed ) ) : ?>
					<h3 class="ka-mt-6"><?php esc_html_e( 'No longer available', 'kaamase-core' ); ?></h3>

					<p class="ka-small ka-mute">
						<?php esc_html_e( 'These closed or were taken down. Kept here so you know what happened to them.', 'kaamase-core' ); ?>
					</p>

					<ul class="ka-stack ka-mt-4">
						<?php foreach ( $closed as $post ) : ?>
							<li class="ka-card ka-card--flat">
								<div class="ka-cluster ka-cluster--between">
									<div>
										<strong class="ka-mute"><?php echo esc_html( $post->post_title ); ?></strong>
										<p class="ka-small ka-mute">
											<?php
											echo 'kaamase_job' === $post->post_type
												? esc_html__( 'This job is closed', 'kaamase-core' )
												: esc_html__( 'This profile is not available', 'kaamase-core' );
											?>
										</p>
									</div>
									<?php echo kaamase_save_button( $post->ID, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

			<?php endif; ?>

		</section>
		<?php

		return (string) ob_get_clean();
	}
}


/* ==========================================================================
   5. HOUSEKEEPING
   ========================================================================== */

/**
 * Drop saved entries whose post no longer exists.
 *
 * Runs daily. Without it a saved list slowly fills with references to
 * deleted profiles, and every page load spends queries resolving posts
 * that are not there.
 *
 * @since 1.0.0
 * @return void
 */
function kaamase_clean_saved_lists() {

	$users = get_users(
		array(
			'meta_key' => 'kaamase_saved', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'number'   => 100,
			'fields'   => array( 'ID' ),
		)
	);

	foreach ( $users as $row ) {

		$saved = kaamase_get_saved( $row->ID );
		$kept  = array();

		foreach ( $saved as $post_id ) {
			if ( get_post( $post_id ) ) {
				$kept[] = $post_id;
			}
		}

		if ( count( $kept ) !== count( $saved ) ) {
			update_user_meta( $row->ID, 'kaamase_saved', $kept );
		}
	}
}
add_action( 'kaamase_daily', 'kaamase_clean_saved_lists' );
