<?php
/**
 * More trades.
 *
 * Work that is not construction and not paid by the day.
 *
 * What was missing
 * ----------------
 * The original list was built around daily wage manual work, which was
 * the right place to start and is no longer the whole picture. Teaching
 * jobs were already being posted with nowhere to put them. Shop work,
 * office work, hotel work, nursing and vehicle repair had no home at
 * all, and those are the jobs a Nagaland school leaver is actually
 * applying for.
 *
 * Why sixteen and not eighty
 * --------------------------
 * The obvious move is to list every occupation anybody could name. It
 * would be wrong here, and the reason is the notifications.
 *
 * A worker hears about new work when a job appears in their trade and
 * their district. Split teaching into assistant teacher, music teacher,
 * computer teacher, sports coach and pre-school teacher, and each of
 * those holds four people in Dimapur, so almost nothing ever matches and
 * almost nobody is ever told. The employer has the same problem from the
 * other side: they guess at which of five boxes their vacancy belongs
 * in, and half of them guess differently from the worker waiting for it.
 *
 * A category only works if enough people are standing in it. Eighty
 * categories on a platform this size is eighty nearly empty rooms.
 *
 * So each of these is deliberately wide. One Teacher covers the
 * assistant teacher and the music teacher and the tuition class.
 * Mechanic covers cars, bikes and generators. The description on each
 * says what it takes in, so somebody hesitating can see their job
 * listed before they pick.
 *
 * What is not touched
 * -------------------
 * Nothing existing is renamed, moved or removed. A term somebody's
 * profile is already attached to is not ours to change, and a worker who
 * chose Tutor last month should not find themselves somewhere else this
 * month without having asked for it.
 *
 * @package KaamaseCore
 * @version 1.1.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/** Bumped when this list changes, so the new terms get created once. */
define( 'KAAMASE_TRADES_VERSION', 2 );


/* ==========================================================================
   1. THE ADDITIONS
   ========================================================================== */

/*
 * The list that used to live here is now in taxonomies.php.
 *
 * It was added through the kaamase_trade_seed filter, and three of its
 * lines assigned a whole category rather than adding to one:
 *
 *     $seed['office']      = array( ... );
 *
 * That replaces everything already under that key. Once the main seed
 * grew its own Office work, Hotel and food and Computer and design
 * headings, this filter silently emptied all three and put its own two
 * or three trades there instead: sixteen trades never created. It also
 * left two headings for one idea, Teaching beside Teaching and
 * childcare and Health beside Health, and pulled Teacher, Nurse and
 * Heavy vehicle driver into the wrong one of each pair.
 *
 * Every trade it created still exists and is still seeded, by name, in
 * kaamase_trade_seed(). Nothing was dropped: the four wide ones that had
 * no equivalent -- Shop and sales, Mechanic, Design and video, Computers
 * and websites -- are listed there too, marked (general), because
 * profiles are attached to them.
 *
 * The rest of this file stays. The descriptions below are the only
 * record of what each wide trade takes in, they are editable in wp-admin,
 * and section 4 is what carries them to the app.
 */


/* ==========================================================================
   2. WHAT EACH ONE TAKES IN

   Written onto the term itself, so anybody hesitating over which box
   their job belongs in can see their own word listed. It is also the
   only place the breadth is recorded: a name that reads Teacher has to
   say somewhere that it means the tuition class as well.
   ========================================================================== */

if ( ! function_exists( 'kaamase_more_trades_covers' ) ) {
	/**
	 * The plain words each trade is meant to catch.
	 *
	 * @since 1.0.0
	 * @return string[] Keyed by slug.
	 */
	function kaamase_more_trades_covers() {

		return array(
			'teacher'          => __( 'School teacher, assistant teacher, private tuition, coaching class, music teacher, computer teacher, sports coach, pre school.', 'kaamase-core' ),
			'office-assistant' => __( 'Receptionist, front desk, office assistant, clerk, data entry, computer operator, office boy, admin work.', 'kaamase-core' ),
			'accountant'       => __( 'Accountant, accounts assistant, book keeping, Tally, billing, audit assistant.', 'kaamase-core' ),
			'shop-worker'      => __( 'Salesman, saleswoman, sales executive, shop assistant, counter staff, cashier, store keeper, godown in charge.', 'kaamase-core' ),
			'nurse'            => __( 'Nurse, ANM, GNM, ward staff, nursing assistant, home nursing.', 'kaamase-core' ),
			'health-worker'    => __( 'Pharmacist, medical shop, lab technician, clinic assistant, dispenser, physiotherapy, health worker.', 'kaamase-core' ),
			'hotel-staff'      => __( 'Waiter, waitress, kitchen helper, dishwasher, room service, housekeeping, hotel front desk, steward.', 'kaamase-core' ),
			'baker'            => __( 'Baker, bakery worker, pastry, cake making, confectioner.', 'kaamase-core' ),
			'mechanic'         => __( 'Car mechanic, bike mechanic, diesel mechanic, auto electrician, garage work, denting and painting, generator repair.', 'kaamase-core' ),
			'heavy-driver'     => __( 'Truck driver, bus driver, tipper, JCB, excavator, heavy vehicle licence.', 'kaamase-core' ),
			'designer'         => __( 'Graphic designer, poster and banner design, logo, video editor, photo editing, social media, content creator.', 'kaamase-core' ),
			'it-worker'        => __( 'Website work, software, computer programmer, IT support, networking, data work.', 'kaamase-core' ),
			'solar-technician' => __( 'Solar panel fitting, inverter, battery, UPS.', 'kaamase-core' ),
			'cctv-technician'  => __( 'CCTV camera fitting, internet and wifi, cable, dish, networking.', 'kaamase-core' ),
			'warehouse-worker' => __( 'Loader, unloading, godown work, packing, stock keeping, delivery agent, logistics helper.', 'kaamase-core' ),
			'barber'           => __( 'Barber, hairdresser, salon work, hair stylist.', 'kaamase-core' ),
			'event-staff'      => __( 'Event helper, decorator, tent work, catering staff, wedding work, sound and light.', 'kaamase-core' ),
		);
	}
}

if ( ! function_exists( 'kaamase_more_trades_describe' ) ) {
	/**
	 * Write the descriptions onto the terms.
	 *
	 * Only ever fills in a description that is empty, so anything
	 * written by hand in wp-admin is left alone.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_more_trades_describe() {

		if ( ! taxonomy_exists( 'kaamase_trade' ) ) {
			return;
		}

		foreach ( kaamase_more_trades_covers() as $slug => $covers ) {

			$term = get_term_by( 'slug', $slug, 'kaamase_trade' );

			if ( ! $term instanceof WP_Term || '' !== trim( (string) $term->description ) ) {
				continue;
			}

			wp_update_term( $term->term_id, 'kaamase_trade', array( 'description' => $covers ) );
		}
	}
}


/* ==========================================================================
   3. CREATING THEM ONCE

   The seeding runs on activation and after a plugin update, neither of
   which happens when a file is simply added. So it is asked for once,
   here, and then never again.

   Nothing is deleted or renamed by it. Every seeding function in this
   plugin adds what is missing and leaves the rest alone.
   ========================================================================== */

if ( ! function_exists( 'kaamase_more_trades_install' ) ) {
	/**
	 * Create the new terms the first time this file runs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function kaamase_more_trades_install() {

		if ( (int) get_option( 'kaamase_trades_version', 1 ) >= KAAMASE_TRADES_VERSION ) {
			return;
		}

		if ( ! function_exists( 'kaamase_seed_trades' ) ) {
			return;
		}

		kaamase_seed_trades();
		kaamase_more_trades_describe();

		update_option( 'kaamase_trades_version', KAAMASE_TRADES_VERSION, false );
	}
}
add_action( 'admin_init', 'kaamase_more_trades_install', 5 );


/* ==========================================================================
   4. SENDING THE DESCRIPTIONS TO THE APP

   The reference endpoint hands the app a slug, a name and a group for
   every trade, and stops there. Writing descriptions onto the terms
   therefore achieved nothing anybody could see: the website does not
   display them and the app was never told they existed.

   Added onto the finished response rather than by rewriting the
   endpoint, so that function stays exactly as it is and this file can be
   removed without taking the districts and languages with it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_more_trades_map' ) ) {
	/**
	 * Every trade description, by slug.
	 *
	 * Read from the terms rather than from the list in this file, so a
	 * description edited in wp-admin is the one the app receives. The
	 * list here is a starting point, not the authority.
	 *
	 * @since 1.1.0
	 * @return string[]
	 */
	function kaamase_more_trades_map() {

		static $map = null;

		if ( null !== $map ) {
			return $map;
		}

		$map = array();

		if ( ! taxonomy_exists( 'kaamase_trade' ) ) {
			return $map;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'kaamase_trade',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $map;
		}

		foreach ( $terms as $term ) {

			$text = trim( (string) $term->description );

			if ( '' !== $text ) {
				$map[ $term->slug ] = $text;
			}
		}

		return $map;
	}
}

if ( ! function_exists( 'kaamase_more_trades_in_reference' ) ) {
	/**
	 * Put the descriptions into the reference response.
	 *
	 * Only that one route, and only when it looks the way it is expected
	 * to. Anything unfamiliar is handed back untouched, so a change to
	 * that endpoint makes this quietly stop rather than corrupt what the
	 * app receives.
	 *
	 * @since 1.1.0
	 * @param WP_REST_Response $response The response.
	 * @param WP_REST_Server   $server   The server.
	 * @param WP_REST_Request  $request  The request.
	 * @return WP_REST_Response
	 */
	function kaamase_more_trades_in_reference( $response, $server, $request ) {

		unset( $server );

		if ( ! defined( 'KAAMASE_REST_NS' ) ) {
			return $response;
		}

		if ( '/' . KAAMASE_REST_NS . '/reference' !== $request->get_route() ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! is_array( $data ) || empty( $data['trades'] ) || ! is_array( $data['trades'] ) ) {
			return $response;
		}

		$map = kaamase_more_trades_map();

		if ( empty( $map ) ) {
			return $response;
		}

		foreach ( $data['trades'] as $at => $trade ) {

			if ( empty( $trade['slug'] ) || empty( $map[ $trade['slug'] ] ) ) {
				continue;
			}

			$data['trades'][ $at ]['description'] = $map[ $trade['slug'] ];
		}

		$response->set_data( $data );

		return $response;
	}
}
add_filter( 'rest_post_dispatch', 'kaamase_more_trades_in_reference', 10, 3 );