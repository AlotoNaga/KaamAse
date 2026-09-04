<?php
/**
 * Districts and towns.
 *
 * The single source of truth for every place name on the platform.
 *
 * Why this is a fixed list and not free text.
 *
 * If a worker types "Chumukedima" and a contractor searches
 * "Chümoukedima", they never find each other. Two spellings silently
 * split every result in half, and the failure is invisible: the search
 * returns results, just not all of them, so nobody reports a bug and
 * the platform quietly works badly forever. On a site whose entire
 * value is matching people by place, that is not a rough edge, it is
 * the product failing at its one job.
 *
 * So places are chosen from a list, never typed. And because people
 * will still type them into search boxes, into imports and into
 * WhatsApp links, every district carries the spellings it actually gets
 * written as, and kaamase_match_district() resolves any of them.
 *
 * Canonical spellings here follow the Nagaland government portal, which
 * writes them without diacritics. The diacritic forms are carried as
 * aliases so both match. If you want the diacritic forms shown to
 * users instead, change the name values and move the plain forms into
 * aliases. Do not change the slugs. They appear in URLs that people
 * will have shared.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. THE LIST
   ========================================================================== */

if ( ! function_exists( 'kaamase_districts' ) ) {
	/**
	 * All 17 districts of Nagaland.
	 *
	 * Meluri was notified as the seventeenth district on 2 November
	 * 2024. Shamator was the sixteenth, created January 2022. If another
	 * is created, add it here and the taxonomy reseeds on the next
	 * plugin update.
	 *
	 * The towns lists are a starting point, not a survey. They cover the
	 * headquarters and the larger settlements. Expand them from local
	 * knowledge, because a worker in a village that is not listed will
	 * pick the nearest town instead and your data quietly loses him.
	 *
	 * @since 1.0.0
	 * @return array[] District definitions keyed by slug.
	 */
	function kaamase_districts() {

		static $districts = null;

		if ( null !== $districts ) {
			return $districts;
		}

		$districts = array(

			'chumoukedima' => array(
				'name'    => 'Chumoukedima',
				'hq'      => 'Chumoukedima',
				'aliases' => array( 'Chümoukedima', 'Chumukedima', 'Chümukedima', 'Chumoukidima', 'CMD' ),
				'towns'   => array( 'Chumoukedima', 'Medziphema', 'Diphupar', 'Sethekema', 'Piphema', 'Kukidolong' ),
			),

			'dimapur' => array(
				'name'    => 'Dimapur',
				'hq'      => 'Dimapur',
				'aliases' => array( 'Dimapure', 'DMP' ),
				'towns'   => array( 'Dimapur', 'Purana Bazar', 'Rangapahar', 'Dhansiripar', 'Signal Angami', 'Half Nagarjan' ),
			),

			'kiphire' => array(
				'name'    => 'Kiphire',
				'hq'      => 'Kiphire',
				'aliases' => array( 'Kiphere', 'Khiphire' ),
				'towns'   => array( 'Kiphire', 'Pungro', 'Seyochung', 'Sitimi', 'Amahator' ),
			),

			'kohima' => array(
				'name'    => 'Kohima',
				'hq'      => 'Kohima',
				'aliases' => array( 'Kohema', 'KHM' ),
				'towns'   => array( 'Kohima', 'Jakhama', 'Viswema', 'Khuzama', 'Chiephobozou', 'Jotsoma', 'Sechu Zubza', 'Kezocha' ),
			),

			'longleng' => array(
				'name'    => 'Longleng',
				'hq'      => 'Longleng',
				'aliases' => array( 'Long Leng' ),
				'towns'   => array( 'Longleng', 'Tamlu', 'Yaongyimchen', 'Sakshi' ),
			),

			'meluri' => array(
				'name'    => 'Meluri',
				'hq'      => 'Meluri',
				'aliases' => array( 'Meluri Town', 'Melury' ),
				'towns'   => array( 'Meluri', 'Phor', 'Lephori', 'Akhegwo' ),
			),

			'mokokchung' => array(
				'name'    => 'Mokokchung',
				'hq'      => 'Mokokchung',
				'aliases' => array( 'Mokukchung', 'Mokokchang', 'MKG' ),
				'towns'   => array( 'Mokokchung', 'Tuli', 'Changtongya', 'Mangkolemba', 'Chuchuyimlang', 'Longchem', 'Alongkima', 'Merangkong' ),
			),

			'mon' => array(
				'name'    => 'Mon',
				'hq'      => 'Mon',
				'aliases' => array( 'Mon Town' ),
				'towns'   => array( 'Mon', 'Naginimora', 'Tizit', 'Aboi', 'Chen', 'Wakching', 'Phomching', 'Tobu', 'Longshen' ),
			),

			/*
			 * Aquqhnaqua is spelled correctly. Leave it alone.
			 *
			 * It is a circle of Dimapur district and that is the local
			 * spelling, confirmed by the people who live there. It looks
			 * like a typo to anybody reading this list from outside
			 * Nagaland, and it has already been queried once during a
			 * code review. Written down here so the next person does not
			 * quietly correct it into something wrong.
			 */
			'niuland' => array(
				'name'    => 'Niuland',
				'hq'      => 'Niuland',
				'aliases' => array( 'Niu Land', 'Nuiland' ),
				'towns'   => array( 'Niuland', 'Aquqhnaqua', 'Kuhuboto' ),
			),

			'noklak' => array(
				'name'    => 'Noklak',
				'hq'      => 'Noklak',
				'aliases' => array( 'Nok Lak' ),
				'towns'   => array( 'Noklak', 'Thonoknyu', 'Panso', 'Chingmei' ),
			),

			'peren' => array(
				'name'    => 'Peren',
				'hq'      => 'Peren',
				'aliases' => array( 'Perren' ),
				'towns'   => array( 'Peren', 'Jalukie', 'Tening', 'Athibung', 'Ngwalwa' ),
			),

			'phek' => array(
				'name'    => 'Phek',
				'hq'      => 'Phek',
				'aliases' => array( 'Phek Town' ),
				'towns'   => array( 'Phek', 'Pfutsero', 'Chozuba', 'Chetheba', 'Sekruzu', 'Zhavame' ),
			),

			'shamator' => array(
				'name'    => 'Shamator',
				'hq'      => 'Shamator',
				'aliases' => array( 'Shamatore', 'Samator' ),
				'towns'   => array( 'Shamator', 'Chessore', 'Kutur', 'Waoshu' ),
			),

			'tseminyu' => array(
				'name'    => 'Tseminyu',
				'hq'      => 'Tseminyu',
				'aliases' => array( 'Tseminyü', 'Tsemenyu', 'Tsminyu' ),
				'towns'   => array( 'Tseminyu', 'Nsunyu', 'Tsogin', 'Chunlikha' ),
			),

			'tuensang' => array(
				'name'    => 'Tuensang',
				'hq'      => 'Tuensang',
				'aliases' => array( 'Tuensung', 'TSG' ),
				'towns'   => array( 'Tuensang', 'Longkhim', 'Noksen', 'Chare', 'Sangsangnyu' ),
			),

			'wokha' => array(
				'name'    => 'Wokha',
				'hq'      => 'Wokha',
				'aliases' => array( 'Woka', 'Wokha Town' ),
				'towns'   => array( 'Wokha', 'Bhandari', 'Sanis', 'Ralan', 'Baghty', 'Chukitong' ),
			),

			'zunheboto' => array(
				'name'    => 'Zunheboto',
				'hq'      => 'Zunheboto',
				'aliases' => array( 'Zünheboto', 'Zunhebotto', 'Zunhebot', 'ZBT' ),
				'towns'   => array( 'Zunheboto', 'Aghunato', 'Atoizu', 'Satakha', 'Akuluto', 'Suruhuto', 'Pughoboto' ),
			),
		);

		/**
		 * Filter the district list.
		 *
		 * Present so a new district can be added without editing this
		 * file. Removing a district that already has profiles attached
		 * will orphan them, so do not.
		 *
		 * @since 1.0.0
		 * @param array[] $districts District definitions keyed by slug.
		 */
		$districts = (array) apply_filters( 'kaamase_districts', $districts );

		return $districts;
	}
}


/* ==========================================================================
   2. LOOKUPS
   ========================================================================== */

if ( ! function_exists( 'kaamase_get_district' ) ) {
	/**
	 * One district by slug.
	 *
	 * @since 1.0.0
	 * @param string $slug District slug.
	 * @return array|null Definition, or null when unknown.
	 */
	function kaamase_get_district( $slug ) {

		$districts = kaamase_districts();
		$slug      = sanitize_key( $slug );

		return isset( $districts[ $slug ] ) ? $districts[ $slug ] : null;
	}
}

if ( ! function_exists( 'kaamase_district_name' ) ) {
	/**
	 * Display name for a district slug.
	 *
	 * @since 1.0.0
	 * @param string $slug District slug.
	 * @return string Name, or an empty string when unknown.
	 */
	function kaamase_district_name( $slug ) {

		$district = kaamase_get_district( $slug );

		return $district ? $district['name'] : '';
	}
}

if ( ! function_exists( 'kaamase_district_towns' ) ) {
	/**
	 * Towns in a district.
	 *
	 * @since 1.0.0
	 * @param string $slug District slug.
	 * @return string[] Town names.
	 */
	function kaamase_district_towns( $slug ) {

		$district = kaamase_get_district( $slug );

		return $district ? (array) $district['towns'] : array();
	}
}

if ( ! function_exists( 'kaamase_district_choices' ) ) {
	/**
	 * Districts as slug and name pairs, ready for a select field.
	 *
	 * @since 1.0.0
	 * @return string[] Names keyed by slug, sorted alphabetically.
	 */
	function kaamase_district_choices() {

		$choices = wp_list_pluck( kaamase_districts(), 'name' );

		asort( $choices );

		return $choices;
	}
}

if ( ! function_exists( 'kaamase_all_towns' ) ) {
	/**
	 * Every town in the state, with its district.
	 *
	 * Used for autocomplete and for import matching.
	 *
	 * @since 1.0.0
	 * @return array[] List of arrays with town and district keys.
	 */
	function kaamase_all_towns() {

		$out = array();

		foreach ( kaamase_districts() as $slug => $district ) {
			foreach ( (array) $district['towns'] as $town ) {
				$out[] = array(
					'town'     => $town,
					'district' => $slug,
				);
			}
		}

		return $out;
	}
}


/* ==========================================================================
   3. MATCHING

   The part that earns its keep.
   ========================================================================== */

if ( ! function_exists( 'kaamase_flatten_place' ) ) {
	/**
	 * Reduce a place name to a comparable form.
	 *
	 * Strips diacritics, punctuation, spacing and case, so that
	 * Chümoukedima, chumoukedima and CHUMU KEDIMA all collapse to the
	 * same string before comparison.
	 *
	 * @since 1.0.0
	 * @param string $value Raw place name.
	 * @return string Flattened key.
	 */
	function kaamase_flatten_place( $value ) {

		$value = remove_accents( (string) $value );
		$value = strtolower( $value );
		$value = preg_replace( '/[^a-z0-9]+/', '', $value );

		return (string) $value;
	}
}

if ( ! function_exists( 'kaamase_match_district' ) ) {
	/**
	 * Resolve any way somebody wrote a district into its slug.
	 *
	 * Tries, in order: exact slug, canonical name, known aliases, and
	 * finally the town lists, because people routinely give a town name
	 * when asked for a district. Somebody who writes Pfutsero means Phek,
	 * and rejecting that as invalid teaches them the site is stupid.
	 *
	 * @since 1.0.0
	 * @param string $value Anything a human typed or pasted.
	 * @return string District slug, or an empty string when no match.
	 */
	function kaamase_match_district( $value ) {

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$districts = kaamase_districts();

		// Already a valid slug.
		$maybe_slug = sanitize_key( $value );

		if ( isset( $districts[ $maybe_slug ] ) ) {
			return $maybe_slug;
		}

		$needle = kaamase_flatten_place( $value );

		if ( '' === $needle ) {
			return '';
		}

		$map = kaamase_place_lookup();

		return isset( $map[ $needle ] ) ? $map[ $needle ] : '';
	}
}

if ( ! function_exists( 'kaamase_place_lookup' ) ) {
	/**
	 * Every way a district can be written, flattened, pointing at its slug.
	 *
	 * Built once and kept.
	 *
	 * Matching used to walk all seventeen districts calling
	 * remove_accents and a regex on every name, every alias and every
	 * town until something matched, and it did that again for each value
	 * being matched. For one registration form that is nothing. For an
	 * import, or a search that resolves a place on every request, it is
	 * the same work over and over on data that never changes within a
	 * request.
	 *
	 * Names and aliases are added before towns, so a place that is both a
	 * district and a town in another district resolves to the district,
	 * which is the order the old code checked in.
	 *
	 * @since 1.3.3
	 * @return string[] District slug keyed by flattened spelling.
	 */
	function kaamase_place_lookup() {

		static $map = null;

		if ( null !== $map ) {
			return $map;
		}

		$map   = array();
		$towns = array();

		foreach ( kaamase_districts() as $slug => $district ) {

			$map[ kaamase_flatten_place( $district['name'] ) ] = $slug;
			$map[ $slug ]                                      = $slug;

			/*
			 * "Kohima Town" and the like, for every district rather than
			 * for four of them.
			 *
			 * kaamase_sanitize_district's own note gives writing Kohima
			 * Town instead of Kohima as the example of what must not
			 * cost somebody their registration. Only Mon, Phek, Wokha
			 * and Meluri actually carried that alias, so the example in
			 * the documentation was one of the thirteen that did not
			 * work. Derived here so the list cannot drift again.
			 */
			$map[ kaamase_flatten_place( $district['name'] . ' Town' ) ] = $slug;

			foreach ( (array) $district['aliases'] as $alias ) {

				$key = kaamase_flatten_place( $alias );

				if ( '' !== $key && ! isset( $map[ $key ] ) ) {
					$map[ $key ] = $slug;
				}
			}

			foreach ( (array) $district['towns'] as $town ) {

				$key = kaamase_flatten_place( $town );

				if ( '' !== $key && ! isset( $towns[ $key ] ) ) {
					$towns[ $key ] = $slug;
				}
			}
		}

		// Towns last, so they never shadow a district of the same name.
		foreach ( $towns as $key => $slug ) {
			if ( ! isset( $map[ $key ] ) ) {
				$map[ $key ] = $slug;
			}
		}

		return $map;
	}
}

if ( ! function_exists( 'kaamase_match_town' ) ) {
	/**
	 * Resolve a town name against the known list.
	 *
	 * Returns the canonical spelling so that stored values stay
	 * consistent regardless of how the person wrote it.
	 *
	 * @since 1.0.0
	 * @param string $value    Town name as written.
	 * @param string $district Optional district slug to search within.
	 * @return string Canonical town name, or an empty string when unknown.
	 */
	function kaamase_match_town( $value, $district = '' ) {

		$needle = kaamase_flatten_place( $value );

		if ( '' === $needle ) {
			return '';
		}

		$districts = kaamase_districts();

		// Narrow to one district when we know it.
		if ( $district && isset( $districts[ $district ] ) ) {
			$districts = array( $district => $districts[ $district ] );
		}

		foreach ( $districts as $entry ) {
			foreach ( (array) $entry['towns'] as $town ) {
				if ( kaamase_flatten_place( $town ) === $needle ) {
					return $town;
				}
			}
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_sanitize_district' ) ) {
	/**
	 * Sanitiser for a district value coming from a form.
	 *
	 * Falls back to matching rather than rejecting. A form that throws
	 * away a submission because somebody wrote Kohima Town instead of
	 * Kohima loses the registration and the person does not come back
	 * to try again.
	 *
	 * @since 1.0.0
	 * @param string $value Submitted value.
	 * @return string Valid district slug, or an empty string.
	 */
	function kaamase_sanitize_district( $value ) {
		return kaamase_match_district( $value );
	}
}
