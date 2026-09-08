<?php
/**
 * Keeping a phone number back, and asking for it.
 *
 * A worker may choose not to show their number on their profile. Anybody
 * who wants it asks, the worker is told, and the worker decides. Nothing
 * is revealed until they say yes.
 *
 * Why this is off by default and stays that way
 * ---------------------------------------------
 * A number that anybody can ring is the entire point of this platform.
 * A mason who hides theirs will get fewer calls, and fewer calls means
 * less work. That is not a guess: an employer standing in a hardware
 * shop with a job starting tomorrow rings the three numbers they can
 * see, not the one they have to apply for and wait on.
 *
 * So this is opt in, it says plainly on the screen what it costs, and
 * the wording never encourages it. It exists because a small number of
 * people have a real reason to want it, and for them the alternative to
 * this feature is not putting their number on the site at all, which
 * costs them every call rather than some of them.
 *
 * Who it does not apply to
 * ------------------------
 * Somebody on a paid plan sees every number. They have paid for reach
 * on the hiring side, a hidden number defeats exactly that, and a
 * platform that sells access and then withholds it is selling
 * something it does not have. Hiding is protection from the general
 * public, not from the people who are here to hire.
 *
 * Why the request is not just a message
 * -------------------------------------
 * A message asking for a number is a message the worker has to read,
 * understand and act on, and the reply is a number typed by hand into
 * a chat by somebody who may not want to. A request has two buttons
 * and the platform does the rest, which is the difference between a
 * thing that works on a phone at a labour point and a thing that does
 * not.
 *
 * Where the decision is actually enforced
 * ---------------------------------------
 * In one place: the contact gate in contact.php, through the veto
 * filter. Every route to a number on this platform, the website screen
 * and the app endpoint alike, goes through that one function. Adding a
 * second place to enforce it would be adding a second place to get it
 * wrong.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE SETTING
   ========================================================================== */

if ( ! function_exists( 'kaamase_number_field_schema' ) ) {
	/**
	 * Register the field that holds the choice.
	 *
	 * Added through the schema filter rather than by editing fields.php,
	 * so the whole feature stays in one file.
	 *
	 * @since 1.6.0
	 * @param array[] $schema Field definitions keyed by post type.
	 * @return array[]
	 */
	function kaamase_number_field_schema( $schema ) {

		$field = array(
			'type'    => 'bool',
			'default' => false,
			'label'   => __( 'Ask before showing my number', 'kaamase-core' ),
		);

		foreach ( array( 'kaamase_worker', 'kaamase_gang' ) as $type ) {

			if ( isset( $schema[ $type ] ) && is_array( $schema[ $type ] ) ) {
				$schema[ $type ]['hide_phone'] = $field;
			}
		}

		return $schema;
	}
}
add_filter( 'kaamase_field_schema', 'kaamase_number_field_schema' );

if ( ! function_exists( 'kaamase_number_is_hidden' ) ) {
	/**
	 * Whether this profile keeps its number back.
	 *
	 * Only ever true for a worker or a team. A job post carries the
	 * number of whoever is hiring, and somebody answering an advert must
	 * be able to ring it: an advert you cannot reply to is not an
	 * advert.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile ID.
	 * @return bool
	 */
	function kaamase_number_is_hidden( $post_id ) {

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, array( 'kaamase_worker', 'kaamase_gang' ), true ) ) {
			return false;
		}

		return (bool) kaamase_read_field( $post_id, 'hide_phone' );
	}
}


/* ==========================================================================
   2. WHO SEES IT ANYWAY
   ========================================================================== */

if ( ! function_exists( 'kaamase_number_may_see' ) ) {
	/**
	 * Whether somebody gets past a hidden number without asking.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile ID.
	 * @param int $user_id The person looking.
	 * @return bool
	 */
	function kaamase_number_may_see( $post_id, $user_id ) {

		$post_id = (int) $post_id;
		$user_id = (int) $user_id;

		if ( ! $user_id ) {
			return false;
		}

		// Your own number is not hidden from you.
		if ( kaamase_user_owns( $post_id, $user_id ) ) {
			return true;
		}

		/*
		 * Paid access. Guarded because the pay plugin is a separate
		 * plugin and this site has to keep working when it is switched
		 * off; without the guard, deactivating it would take every
		 * contact reveal on the platform down with it.
		 */
		if ( function_exists( 'kaamase_pay_is_active' ) && kaamase_pay_is_active( $user_id ) ) {
			return true;
		}

		// Staff, who have to be able to answer a support message.
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$request = kaamase_number_request_for( $post_id, $user_id );

		return $request && 'approved' === $request['state'];
	}
}


/* ==========================================================================
   3. THE REQUESTS

   Stored on the profile rather than in a table of their own. They are
   few, they are only ever read for one profile at a time, and a list on
   the post is one meta read where a table would be a schema, a
   migration and an upgrade path.
   ========================================================================== */

if ( ! function_exists( 'kaamase_number_requests' ) ) {
	/**
	 * Every request made of one profile, newest last.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile ID.
	 * @return array[]
	 */
	function kaamase_number_requests( $post_id ) {

		$stored = kaamase_meta_array( get_post_meta( (int) $post_id, 'kaamase_number_requests', true ) );
		$clean  = array();

		foreach ( $stored as $row ) {

			if ( ! is_array( $row ) || empty( $row['id'] ) || empty( $row['from'] ) ) {
				continue;
			}

			$clean[] = array(
				'id'      => (string) $row['id'],
				'from'    => (int) $row['from'],
				'created' => isset( $row['created'] ) ? (int) $row['created'] : 0,
				'state'   => isset( $row['state'] ) && in_array( $row['state'], array( 'pending', 'approved', 'rejected' ), true )
					? (string) $row['state']
					: 'pending',
				'decided' => isset( $row['decided'] ) ? (int) $row['decided'] : 0,
			);
		}

		return $clean;
	}
}

if ( ! function_exists( 'kaamase_number_requests_save' ) ) {
	/**
	 * Write the list back, newest kept.
	 *
	 * @since 1.6.0
	 * @param int     $post_id  Profile ID.
	 * @param array[] $requests The list.
	 * @return void
	 */
	function kaamase_number_requests_save( $post_id, $requests ) {

		/*
		 * Capped. A profile that somehow collects thousands of these
		 * would otherwise turn one meta read into a slow one on every
		 * contact check.
		 */
		$requests = array_slice( array_values( (array) $requests ), -200 );

		update_post_meta( (int) $post_id, 'kaamase_number_requests', $requests );
	}
}

if ( ! function_exists( 'kaamase_number_request_for' ) ) {
	/**
	 * The most recent request one person made of one profile.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile ID.
	 * @param int $user_id The person who asked.
	 * @return array|null
	 */
	function kaamase_number_request_for( $post_id, $user_id ) {

		$user_id = (int) $user_id;
		$found   = null;

		foreach ( kaamase_number_requests( $post_id ) as $request ) {

			if ( $request['from'] === $user_id ) {
				$found = $request;
			}
		}

		return $found;
	}
}

if ( ! function_exists( 'kaamase_number_pending' ) ) {
	/**
	 * The requests still waiting on an answer.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile ID.
	 * @return array[]
	 */
	function kaamase_number_pending( $post_id ) {

		return array_values(
			array_filter(
				kaamase_number_requests( $post_id ),
				static function ( $request ) {
					return 'pending' === $request['state'];
				}
			)
		);
	}
}

if ( ! function_exists( 'kaamase_number_request_add' ) ) {
	/**
	 * Ask a worker for their number.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile being asked about.
	 * @param int $user_id The person asking.
	 * @return true|WP_Error
	 */
	function kaamase_number_request_add( $post_id, $user_id ) {

		$post_id = (int) $post_id;
		$user_id = (int) $user_id;

		$post = get_post( $post_id );

		if ( ! $post || ! kaamase_number_is_hidden( $post_id ) ) {
			return new WP_Error(
				'kaamase_number_not_hidden',
				__( 'That number is not hidden, so there is nothing to ask for.', 'kaamase-core' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $user_id ) {
			return new WP_Error(
				'kaamase_signed_out',
				__( 'Sign in first, so the person you are asking can see who is asking.', 'kaamase-core' ),
				array( 'status' => 401 )
			);
		}

		if ( kaamase_user_owns( $post_id, $user_id ) ) {
			return new WP_Error(
				'kaamase_own_profile',
				__( 'That is your own profile.', 'kaamase-core' ),
				array( 'status' => 400 )
			);
		}

		if ( ! kaamase_user_is_verified( $user_id ) ) {
			return new WP_Error(
				'kaamase_unverified',
				__( 'Confirm your email first. Somebody deciding whether to give you their number should be able to see a real account.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		if ( kaamase_is_blocked( (int) $post->post_author, $user_id ) ) {
			return new WP_Error(
				'kaamase_blocked',
				__( 'You cannot contact this person.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		$existing = kaamase_number_request_for( $post_id, $user_id );

		if ( $existing && 'pending' === $existing['state'] ) {
			return new WP_Error(
				'kaamase_already_asked',
				__( 'You have already asked. They will see it the next time they open Kaam Ase.', 'kaamase-core' ),
				array( 'status' => 409 )
			);
		}

		/*
		 * Somebody already turned down is not allowed to ask again.
		 *
		 * The answer was no. A platform that lets it be asked a second
		 * time has turned a refusal into a delay, and the person doing
		 * the refusing is the one who pays for that.
		 */
		if ( $existing && 'rejected' === $existing['state'] ) {
			return new WP_Error(
				'kaamase_already_refused',
				__( 'You have asked this person before and they said no.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		if ( $existing && 'approved' === $existing['state'] ) {
			return new WP_Error(
				'kaamase_already_approved',
				__( 'They have already shared their number with you.', 'kaamase-core' ),
				array( 'status' => 409 )
			);
		}

		if ( ! kaamase_throttle_ok( kaamase_client_key( 'number_request' ), 10, HOUR_IN_SECONDS ) ) {
			return kaamase_throttle_error();
		}

		$requests   = kaamase_number_requests( $post_id );
		$requests[] = array(
			'id'      => wp_generate_password( 12, false, false ),
			'from'    => $user_id,
			'created' => time(),
			'state'   => 'pending',
			'decided' => 0,
		);

		kaamase_number_requests_save( $post_id, $requests );

		kaamase_number_tell_owner( $post_id, $user_id );

		/**
		 * Fires when somebody asks for a hidden number.
		 *
		 * @since 1.6.0
		 * @param int $post_id Profile asked about.
		 * @param int $user_id Who asked.
		 */
		do_action( 'kaamase_number_requested', $post_id, $user_id );

		return true;
	}
}

if ( ! function_exists( 'kaamase_number_request_decide' ) ) {
	/**
	 * Answer a request.
	 *
	 * @since 1.6.0
	 * @param int    $post_id    Profile ID.
	 * @param string $request_id Which request.
	 * @param int    $owner_id   Who is answering. Must own the profile.
	 * @param string $decision   approve or reject.
	 * @return true|WP_Error
	 */
	function kaamase_number_request_decide( $post_id, $request_id, $owner_id, $decision ) {

		$post_id  = (int) $post_id;
		$owner_id = (int) $owner_id;
		$decision = 'approve' === $decision ? 'approved' : 'rejected';

		if ( ! kaamase_user_owns( $post_id, $owner_id ) ) {
			return new WP_Error(
				'kaamase_not_yours',
				__( 'That is not your profile.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		$requests = kaamase_number_requests( $post_id );
		$found    = false;
		$asker    = 0;

		foreach ( $requests as $index => $request ) {

			if ( $request['id'] !== (string) $request_id ) {
				continue;
			}

			if ( 'pending' !== $request['state'] ) {
				return new WP_Error(
					'kaamase_already_decided',
					__( 'You have already answered that one.', 'kaamase-core' ),
					array( 'status' => 409 )
				);
			}

			$requests[ $index ]['state']   = $decision;
			$requests[ $index ]['decided'] = time();

			$found = true;
			$asker = $request['from'];
			break;
		}

		if ( ! $found ) {
			return new WP_Error(
				'kaamase_no_request',
				__( 'That request is no longer there.', 'kaamase-core' ),
				array( 'status' => 404 )
			);
		}

		kaamase_number_requests_save( $post_id, $requests );

		kaamase_number_tell_asker( $post_id, $asker, $decision );

		/**
		 * Fires when a request for a number is answered.
		 *
		 * @since 1.6.0
		 * @param int    $post_id  Profile ID.
		 * @param int    $asker    Who asked.
		 * @param string $decision approved or rejected.
		 */
		do_action( 'kaamase_number_decided', $post_id, $asker, $decision );

		return true;
	}
}


/* ==========================================================================
   4. TELLING PEOPLE

   The phone if they have the app, an email if they do not. Neither
   message carries a number: the approval email tells somebody to go and
   look, because an email is forwarded, printed and left open on shared
   computers, and a number that leaks that way cannot be put back.
   ========================================================================== */

if ( ! function_exists( 'kaamase_number_tell_owner' ) ) {
	/**
	 * Tell a worker that somebody has asked.
	 *
	 * @since 1.6.0
	 * @param int $post_id Profile ID.
	 * @param int $asker   Who asked.
	 * @return void
	 */
	function kaamase_number_tell_owner( $post_id, $asker ) {

		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$who = kaamase_number_describe( $asker );

		kaamase_notify(
			(int) $post->post_author,
			__( 'Somebody wants your number', 'kaamase-core' ),
			sprintf(
				/* translators: %s: who is asking, with their district when known */
				__( '%s has asked for your phone number. They may want to hire you. Open Kaam Ase to say yes or no.', 'kaamase-core' ),
				$who
			),
			array(
				'type'    => 'number_request',
				'profile' => (int) $post_id,
			),
			kaamase_page_url( 'dashboard' )
		);
	}
}

if ( ! function_exists( 'kaamase_number_tell_asker' ) ) {
	/**
	 * Tell somebody what was decided.
	 *
	 * A refusal is told plainly and without apology or explanation.
	 * Dressing it up invites a second attempt, and the answer was no.
	 *
	 * @since 1.6.0
	 * @param int    $post_id  Profile ID.
	 * @param int    $asker    Who asked.
	 * @param string $decision approved or rejected.
	 * @return void
	 */
	function kaamase_number_tell_asker( $post_id, $asker, $decision ) {

		$asker = (int) $asker;

		if ( ! $asker ) {
			return;
		}

		$name = get_the_title( $post_id );

		if ( 'approved' === $decision ) {

			kaamase_notify(
				$asker,
				__( 'You can see the number now', 'kaamase-core' ),
				sprintf(
					/* translators: %s: the worker's name */
					__( '%s has shared their phone number with you. Open their profile to see it.', 'kaamase-core' ),
					$name
				),
				array(
					'type'    => 'number_approved',
					'profile' => (int) $post_id,
				),
				(string) get_permalink( $post_id )
			);

			return;
		}

		kaamase_notify(
			$asker,
			__( 'Your request was turned down', 'kaamase-core' ),
			sprintf(
				/* translators: %s: the worker's name */
				__( '%s has decided not to share their phone number.', 'kaamase-core' ),
				$name
			),
			array(
				'type'    => 'number_rejected',
				'profile' => (int) $post_id,
			)
		);
	}
}

if ( ! function_exists( 'kaamase_number_describe' ) ) {
	/**
	 * How to name the person asking, to the person deciding.
	 *
	 * Enough to judge by and no more. A name and a place, plus whether
	 * they have hired anybody here before, which is the one fact that
	 * actually predicts whether this is a real job. Never their number:
	 * this is a request to exchange details, not an exchange.
	 *
	 * @since 1.6.0
	 * @param int $user_id Who asked.
	 * @return string
	 */
	function kaamase_number_describe( $user_id ) {

		$user = get_userdata( (int) $user_id );

		if ( ! $user ) {
			return __( 'Somebody', 'kaamase-core' );
		}

		$name    = $user->display_name ? $user->display_name : __( 'Somebody', 'kaamase-core' );
		$profile = function_exists( 'kaamase_get_user_profile' )
			? kaamase_get_user_profile( (int) $user_id, 'kaamase_employer' )
			: null;

		if ( ! $profile ) {
			return $name;
		}

		$district = (string) kaamase_read_field( $profile, 'district' );

		if ( '' === $district ) {
			return $name;
		}

		$term = get_term_by( 'slug', $district, 'kaamase_district' );

		return $term && ! is_wp_error( $term )
			? sprintf(
				/* translators: 1: person's name, 2: district */
				__( '%1$s from %2$s', 'kaamase-core' ),
				$name,
				$term->name
			)
			: $name;
	}
}


/* ==========================================================================
   5. THE GATE

   One filter, on the one function every route to a number goes through.
   ========================================================================== */

if ( ! function_exists( 'kaamase_number_contact_veto' ) ) {
	/**
	 * Refuse a hidden number, and say how to ask for it.
	 *
	 * Runs after the platform's own rules and before the daily cap, so
	 * a refusal here never costs somebody one of their lookups. They
	 * did not see a number, so they have not spent anything.
	 *
	 * @since 1.6.0
	 * @param null|WP_Error $veto    Refusal so far.
	 * @param int           $post_id Profile or job being asked about.
	 * @param int           $user_id Who is asking.
	 * @return null|WP_Error
	 */
	function kaamase_number_contact_veto( $veto, $post_id, $user_id ) {

		if ( is_wp_error( $veto ) ) {
			return $veto;
		}

		if ( ! kaamase_number_is_hidden( $post_id ) ) {
			return $veto;
		}

		if ( kaamase_number_may_see( $post_id, $user_id ) ) {
			return $veto;
		}

		$request = kaamase_number_request_for( $post_id, $user_id );

		if ( $request && 'pending' === $request['state'] ) {
			return new WP_Error(
				'kaamase_number_waiting',
				__( 'You have asked for this number. They will see it the next time they open Kaam Ase, and you will be told when they answer.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		if ( $request && 'rejected' === $request['state'] ) {
			return new WP_Error(
				'kaamase_number_refused',
				__( 'This person has decided not to share their number with you.', 'kaamase-core' ),
				array( 'status' => 403 )
			);
		}

		return new WP_Error(
			'kaamase_number_hidden',
			__( 'This person asks to be told before their number is shared. Send a request and they will say yes or no.', 'kaamase-core' ),
			array( 'status' => 403 )
		);
	}
}
add_filter( 'kaamase_contact_veto', 'kaamase_number_contact_veto', 10, 3 );


/* ==========================================================================
   6. THE DASHBOARD
   ========================================================================== */

if ( ! function_exists( 'kaamase_number_dashboard_prompt' ) ) {
	/**
	 * The people waiting on an answer, at the top of the account screen.
	 *
	 * In the prompts slot because that is where things waiting on this
	 * person go, and somebody who has hidden their number has by
	 * definition agreed to be asked.
	 *
	 * @since 1.6.0
	 * @param int    $user_id User ID.
	 * @param int    $profile Profile post ID.
	 * @param string $type    worker or employer.
	 * @return void
	 */
	function kaamase_number_dashboard_prompt( $user_id, $profile, $type ) {

		unset( $type );

		$pending = kaamase_number_pending( $profile );

		if ( empty( $pending ) ) {
			return;
		}
		?>
		<div class="ka-card ka-stack ka-mt-6">

			<h3>
				<?php
				printf(
					/* translators: %d: how many people are waiting */
					esc_html(
						_n(
							'%d person wants your number',
							'%d people want your number',
							count( $pending ),
							'kaamase-core'
						)
					),
					count( $pending )
				);
				?>
			</h3>

			<p class="ka-small ka-mute">
				<?php esc_html_e( 'They cannot see it until you say yes. Saying no tells them nothing about you.', 'kaamase-core' ); ?>
			</p>

			<?php foreach ( $pending as $request ) : ?>
				<div class="ka-row ka-stack">

					<p><strong><?php echo esc_html( kaamase_number_describe( $request['from'] ) ); ?></strong></p>

					<?php
					$standing = function_exists( 'kaamase_employer_standing' )
						? kaamase_employer_standing( $request['from'] )
						: array( 'lines' => array() );
					?>

					<?php if ( ! empty( $standing['lines'] ) ) : ?>
						<ul class="ka-small ka-mute">
							<?php foreach ( $standing['lines'] as $line ) : ?>
								<li><?php echo esc_html( $line ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<form method="post" action="" style="display:flex;gap:8px;flex-wrap:wrap;">
						<?php wp_nonce_field( 'kaamase_number_decide', 'kaamase_number_nonce' ); ?>
						<input type="hidden" name="kaamase_action" value="number_decide">
						<input type="hidden" name="kaamase_profile" value="<?php echo esc_attr( $profile ); ?>">
						<input type="hidden" name="kaamase_request" value="<?php echo esc_attr( $request['id'] ); ?>">

						<button class="ka-btn ka-btn--action" type="submit" name="kaamase_decision" value="approve">
							<?php esc_html_e( 'Share my number', 'kaamase-core' ); ?>
						</button>

						<button class="ka-btn" type="submit" name="kaamase_decision" value="reject">
							<?php esc_html_e( 'No', 'kaamase-core' ); ?>
						</button>
					</form>
				</div>
			<?php endforeach; ?>

		</div>
		<?php
	}
}
add_action( 'kaamase_dashboard_prompts', 'kaamase_number_dashboard_prompt', 10, 3 );

if ( ! function_exists( 'kaamase_number_dashboard_setting' ) ) {
	/**
	 * The switch itself, and what it costs.
	 *
	 * The cost is stated on the screen rather than buried in help,
	 * because somebody turning this on is making a decision about how
	 * much work they get and is entitled to know that before they make
	 * it rather than after.
	 *
	 * @since 1.6.0
	 * @param int    $user_id User ID.
	 * @param int    $profile Profile post ID.
	 * @param string $type    worker or employer.
	 * @return void
	 */
	function kaamase_number_dashboard_setting( $user_id, $profile, $type ) {

		unset( $user_id );

		if ( 'worker' !== $type ) {
			return;
		}

		$hidden = kaamase_number_is_hidden( $profile );
		?>
		<div class="ka-card ka-stack ka-mt-6">

			<h3><?php esc_html_e( 'Your phone number', 'kaamase-core' ); ?></h3>

			<?php if ( $hidden ) : ?>

				<p>
					<?php esc_html_e( 'Your number is hidden. People have to ask, and you decide each time.', 'kaamase-core' ); ?>
				</p>

				<p class="ka-small ka-mute">
					<?php
					esc_html_e(
						'This means fewer calls. Somebody with work starting tomorrow usually rings the numbers they can see rather than waiting on an answer.',
						'kaamase-core'
					);
					?>
				</p>

			<?php else : ?>

				<p>
					<?php esc_html_e( 'Your number is shown to signed in people who look you up, which is how work reaches you.', 'kaamase-core' ); ?>
				</p>

				<p class="ka-small ka-mute">
					<?php
					esc_html_e(
						'You can hide it and be asked first instead. Most people should not: it means fewer calls, because somebody hiring today rings the numbers they can see.',
						'kaamase-core'
					);
					?>
				</p>

			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'kaamase_number_setting', 'kaamase_number_setting_nonce' ); ?>
				<input type="hidden" name="kaamase_action" value="number_setting">
				<input type="hidden" name="kaamase_profile" value="<?php echo esc_attr( $profile ); ?>">
				<input type="hidden" name="kaamase_hide" value="<?php echo $hidden ? '0' : '1'; ?>">

				<button class="ka-btn" type="submit">
					<?php
					echo $hidden
						? esc_html__( 'Show my number again', 'kaamase-core' )
						: esc_html__( 'Ask me before showing my number', 'kaamase-core' );
					?>
				</button>
			</form>

		</div>
		<?php
	}
}
add_action( 'kaamase_dashboard_sections', 'kaamase_number_dashboard_setting', 10, 3 );

if ( ! function_exists( 'kaamase_number_handle_forms' ) ) {
	/**
	 * Handle both dashboard forms.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_number_handle_forms() {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['kaamase_action'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_action'] ) ) : '';

		if ( ! in_array( $action, array( 'number_decide', 'number_setting' ), true ) || ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$profile = isset( $_POST['kaamase_profile'] ) ? absint( wp_unslash( $_POST['kaamase_profile'] ) ) : 0;

		if ( ! kaamase_user_owns( $profile, $user_id ) ) {
			return;
		}

		if ( 'number_setting' === $action ) {

			check_admin_referer( 'kaamase_number_setting', 'kaamase_number_setting_nonce' );

			$hide = isset( $_POST['kaamase_hide'] ) && '1' === $_POST['kaamase_hide'];

			kaamase_save_field( $profile, 'hide_phone', $hide ? 1 : 0 );

			kaamase_number_flash(
				$hide
					? __( 'Your number is hidden now. People will have to ask.', 'kaamase-core' )
					: __( 'Your number is shown again.', 'kaamase-core' )
			);

			wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
			exit;
		}

		check_admin_referer( 'kaamase_number_decide', 'kaamase_number_nonce' );

		$request  = isset( $_POST['kaamase_request'] ) ? sanitize_text_field( wp_unslash( $_POST['kaamase_request'] ) ) : '';
		$decision = isset( $_POST['kaamase_decision'] ) ? sanitize_key( wp_unslash( $_POST['kaamase_decision'] ) ) : '';

		$done = kaamase_number_request_decide( $profile, $request, $user_id, $decision );

		kaamase_number_flash(
			is_wp_error( $done )
				? $done->get_error_message()
				: ( 'approve' === $decision
					? __( 'Shared. They can see your number now.', 'kaamase-core' )
					: __( 'Turned down. They have been told, and they cannot ask again.', 'kaamase-core' ) )
		);

		wp_safe_redirect( kaamase_page_url( 'dashboard' ) );
		exit;
	}
}
add_action( 'template_redirect', 'kaamase_number_handle_forms' );

if ( ! function_exists( 'kaamase_number_flash' ) ) {
	/**
	 * Keep one sentence to show after the redirect.
	 *
	 * @since 1.6.0
	 * @param string $message What to say.
	 * @return void
	 */
	function kaamase_number_flash( $message ) {

		set_transient(
			'kaamase_number_said_' . get_current_user_id(),
			(string) $message,
			MINUTE_IN_SECONDS
		);
	}
}

if ( ! function_exists( 'kaamase_number_flash_show' ) ) {
	/**
	 * Show it, once.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_number_flash_show() {

		$key     = 'kaamase_number_said_' . get_current_user_id();
		$message = get_transient( $key );

		if ( ! $message ) {
			return;
		}

		delete_transient( $key );

		printf(
			'<div class="ka-notice ka-notice--info"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
add_action( 'kaamase_dashboard_prompts', 'kaamase_number_flash_show', 5 );


/* ==========================================================================
   7. FOR THE APP
   ========================================================================== */

if ( ! function_exists( 'kaamase_number_shape_worker' ) ) {
	/**
	 * Tell the app whether a number has to be asked for.
	 *
	 * Carries no number and never has. This says only what the button
	 * on the profile should say, so the app does not have to call the
	 * contact endpoint and be refused in order to find out.
	 *
	 * @since 1.6.0
	 * @param array   $out  The shaped profile.
	 * @param WP_Post $post The profile.
	 * @return array
	 */
	function kaamase_number_shape_worker( $out, $post ) {

		if ( ! is_array( $out ) || ! $post instanceof WP_Post ) {
			return $out;
		}

		$hidden = kaamase_number_is_hidden( $post->ID );

		$out['phone_hidden'] = $hidden;
		$out['number_state'] = 'open';

		if ( ! $hidden ) {
			return $out;
		}

		$user_id = get_current_user_id();

		if ( kaamase_number_may_see( $post->ID, $user_id ) ) {
			$out['number_state'] = 'open';
			return $out;
		}

		$request = kaamase_number_request_for( $post->ID, $user_id );

		if ( $request && in_array( $request['state'], array( 'pending', 'rejected' ), true ) ) {
			$out['number_state'] = 'pending' === $request['state'] ? 'waiting' : 'refused';
			return $out;
		}

		$out['number_state'] = 'ask';

		return $out;
	}
}
add_filter( 'kaamase_shape_worker', 'kaamase_number_shape_worker', 10, 2 );

if ( ! function_exists( 'kaamase_number_shape_me' ) ) {
	/**
	 * Put the waiting count on the account, so the app can badge it.
	 *
	 * On /me rather than a call of its own, because a badge that costs
	 * a second request on every launch is a badge that gets removed
	 * later for being slow.
	 *
	 * @since 1.6.0
	 * @param array $me      The account.
	 * @param int   $user_id User ID.
	 * @return array
	 */
	function kaamase_number_shape_me( $me, $user_id ) {

		if ( ! is_array( $me ) ) {
			return $me;
		}

		$profile = isset( $me['profile_id'] ) ? (int) $me['profile_id'] : 0;

		$me['phone_hidden']            = $profile ? kaamase_number_is_hidden( $profile ) : false;
		$me['number_requests_waiting'] = $profile ? count( kaamase_number_pending( $profile ) ) : 0;

		unset( $user_id );

		return $me;
	}
}
add_filter( 'kaamase_shape_me', 'kaamase_number_shape_me', 10, 2 );

if ( ! function_exists( 'kaamase_rest_number_request' ) ) {
	/**
	 * Ask for a hidden number.
	 *
	 * @since 1.6.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_number_request( $request ) {

		$done = kaamase_number_request_add( absint( $request['id'] ), get_current_user_id() );

		if ( is_wp_error( $done ) ) {
			return kaamase_rest_error( $done );
		}

		return new WP_REST_Response(
			array(
				'state'   => 'waiting',
				'message' => __( 'Asked. They will see it the next time they open Kaam Ase, and you will be told when they answer.', 'kaamase-core' ),
			),
			201
		);
	}
}

if ( ! function_exists( 'kaamase_rest_number_waiting' ) ) {
	/**
	 * The requests waiting on the caller.
	 *
	 * @since 1.6.0
	 * @return WP_REST_Response
	 */
	function kaamase_rest_number_waiting() {

		$user_id = get_current_user_id();
		$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );
		$items   = array();

		if ( $profile && kaamase_user_owns( $profile, $user_id ) ) {

			foreach ( kaamase_number_pending( $profile ) as $request ) {

				$standing = function_exists( 'kaamase_employer_standing' )
					? kaamase_employer_standing( $request['from'] )
					: array( 'lines' => array(), 'is_new' => true );

				$items[] = array(
					'id'      => $request['id'],
					'who'     => kaamase_number_describe( $request['from'] ),
					'lines'   => isset( $standing['lines'] ) ? array_values( $standing['lines'] ) : array(),
					'is_new'  => ! empty( $standing['is_new'] ),
					'created' => $request['created'],
				);
			}
		}

		return new WP_REST_Response(
			array(
				'items'        => $items,
				'phone_hidden' => $profile ? kaamase_number_is_hidden( $profile ) : false,
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_number_decide' ) ) {
	/**
	 * Answer one request.
	 *
	 * @since 1.6.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_number_decide( $request ) {

		$user_id  = get_current_user_id();
		$profile  = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );
		$decision = sanitize_key( (string) $request->get_param( 'decision' ) );

		if ( ! in_array( $decision, array( 'approve', 'reject' ), true ) ) {
			return kaamase_rest_error(
				new WP_Error(
					'kaamase_bad_decision',
					__( 'Say approve or reject.', 'kaamase-core' ),
					array( 'status' => 400 )
				)
			);
		}

		$done = kaamase_number_request_decide(
			$profile,
			sanitize_text_field( (string) $request['rid'] ),
			$user_id,
			$decision
		);

		if ( is_wp_error( $done ) ) {
			return kaamase_rest_error( $done );
		}

		return new WP_REST_Response(
			array(
				'state'   => 'approve' === $decision ? 'approved' : 'rejected',
				'waiting' => count( kaamase_number_pending( $profile ) ),
			),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_rest_number_setting' ) ) {
	/**
	 * Turn hiding on or off from the app.
	 *
	 * @since 1.6.0
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	function kaamase_rest_number_setting( $request ) {

		$user_id = get_current_user_id();
		$profile = (int) get_user_meta( $user_id, 'kaamase_profile_id', true );

		if ( ! $profile || ! kaamase_user_owns( $profile, $user_id ) ) {
			return kaamase_rest_error(
				new WP_Error(
					'kaamase_not_yours',
					__( 'That is not your profile.', 'kaamase-core' ),
					array( 'status' => 403 )
				)
			);
		}

		$hide = (bool) $request->get_param( 'hide' );

		kaamase_save_field( $profile, 'hide_phone', $hide ? 1 : 0 );

		return new WP_REST_Response(
			array( 'phone_hidden' => kaamase_number_is_hidden( $profile ) ),
			200
		);
	}
}

if ( ! function_exists( 'kaamase_number_routes' ) ) {
	/**
	 * Register the endpoints.
	 *
	 * @since 1.6.0
	 * @return void
	 */
	function kaamase_number_routes() {

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return;
		}

		$auth = 'kaamase_rest_require_login';

		register_rest_route(
			KAAMASE_REST_NS,
			'/number/request/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_number_request',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/number/requests',
			array(
				'methods'             => 'GET',
				'callback'            => 'kaamase_rest_number_waiting',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/number/requests/(?P<rid>[A-Za-z0-9]+)',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_number_decide',
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			KAAMASE_REST_NS,
			'/number/setting',
			array(
				'methods'             => 'POST',
				'callback'            => 'kaamase_rest_number_setting',
				'permission_callback' => $auth,
			)
		);
	}
}
add_action( 'rest_api_init', 'kaamase_number_routes' );
