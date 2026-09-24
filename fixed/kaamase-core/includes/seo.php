<?php
/**
 * Search engines and AI assistants.
 *
 * What a page is called in a search result, the line under it, which
 * pages a search engine is told about, and the plain facts an AI
 * assistant needs to recommend Kaam Ase when somebody asks it where to
 * find work or workers in Nagaland.
 *
 * Why this file exists
 * --------------------
 * The site ranked for its own name and for words already on its pages,
 * and not at all for what people actually type: jobs in Nagaland,
 * workers in Nagaland, electrician in Dimapur. The homepage was titled
 * "Kaam Ase – There is work." and headed "Kaam ase.", which is a fine
 * slogan and says nothing to somebody who has never heard of it. No page
 * carried a description, so Google wrote its own from whatever text it
 * found first.
 *
 * The district and trade pages were already right underneath. Their
 * headings said "Workers and jobs in Dimapur". Only the title a search
 * result shows, and the line under it, were missing.
 *
 * What was broken rather than missing
 * -----------------------------------
 * WordPress's own sitemap listed every member at /author/<login>/. Login
 * names here are the part of the email before the @, so the sitemap
 * published the start of every member's email address to anybody who
 * opened it. security.php already refuses those author pages, so every
 * one of those URLs also answered "not found", and a sitemap full of
 * dead links is a sitemap a search engine learns to distrust. The users
 * list is removed from the sitemap entirely.
 *
 * The account pages (My account, Saved, My team, Who looked at you) were
 * in the sitemap too. They show a sign in prompt to a crawler and are
 * worthless as a search result, so they are left out and marked noindex.
 *
 * For AI assistants
 * -----------------
 * An assistant answers "where can I find a mason in Kohima" from text it
 * can read and quote. So the homepage carries plain questions and
 * answers (kaamase_home_faq()), the same answers are given to search
 * engines as FAQPage data, and /llms.txt gives a short, factual summary
 * with links, in the format assistants look for.
 *
 * Standing aside
 * --------------
 * If a search plugin is installed it writes titles, descriptions and its
 * own structured data, and two sets in one page is worse than either. So
 * those parts check for one and write nothing. The sitemap fix, the
 * account pages and /llms.txt apply either way.
 *
 * @package KaamaseCore
 * @version 1.0.0
 * @since   1.13.0
 */

defined( 'ABSPATH' ) || exit;


/* ==========================================================================
   1. WHETHER TO WRITE TITLES, DESCRIPTIONS AND STRUCTURED DATA
   ========================================================================== */

if ( ! function_exists( 'kaamase_seo_wanted' ) ) {
	/**
	 * Whether this file should write titles and descriptions.
	 *
	 * @since 1.13.0
	 * @return bool
	 */
	function kaamase_seo_wanted() {

		$taken = defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| class_exists( 'WPSEO_Options' );

		/**
		 * Filter whether Kaam Ase writes titles, descriptions and search data.
		 *
		 * @since 1.13.0
		 * @param bool $wanted Whether to write them.
		 */
		return (bool) apply_filters( 'kaamase_seo', ! $taken );
	}
}


/* ==========================================================================
   2. THE SITEMAP AND THE ACCOUNT PAGES
   ========================================================================== */

if ( ! function_exists( 'kaamase_seo_no_user_sitemap' ) ) {
	/**
	 * Remove the list of members from the sitemap.
	 *
	 * @since 1.13.0
	 * @param WP_Sitemaps_Provider|false $provider The provider.
	 * @param string                     $name     Its name.
	 * @return WP_Sitemaps_Provider|false
	 */
	function kaamase_seo_no_user_sitemap( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}
}
add_filter( 'wp_sitemaps_add_provider', 'kaamase_seo_no_user_sitemap', 10, 2 );

if ( ! function_exists( 'kaamase_seo_user_sitemap_gone' ) ) {
	/**
	 * Answer "not found" at the old address of the members sitemap.
	 *
	 * With the list removed, WordPress would otherwise fall through and
	 * show the homepage there. Google has fetched that address before, and
	 * a plain "not found" is what tells it the list is gone for good.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	function kaamase_seo_user_sitemap_gone() {

		if ( 'users' !== get_query_var( 'sitemap' ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'kaamase_seo_user_sitemap_gone', 0 );

if ( ! function_exists( 'kaamase_seo_private_pages' ) ) {
	/**
	 * Pages that only mean anything to somebody signed in.
	 *
	 * @since 1.13.0
	 * @return int[] Page IDs.
	 */
	function kaamase_seo_private_pages() {

		static $ids = null;

		if ( null !== $ids ) {
			return $ids;
		}

		// Stored name => the slug it was created with.
		$pages  = array(
			'dashboard'  => 'dashboard',
			'saved'      => 'saved',
			'team'       => 'my-team',
			'who-looked' => 'who-looked',
		);
		$stored = (array) get_option( 'kaamase_pages', array() );
		$ids    = array();

		foreach ( $pages as $name => $slug ) {

			if ( ! empty( $stored[ $name ] ) ) {
				$ids[] = (int) $stored[ $name ];
				continue;
			}

			// An older install may not have stored this one by name.
			$page = get_page_by_path( $slug );

			if ( $page ) {
				$ids[] = (int) $page->ID;
			}
		}

		/**
		 * Filter the pages kept out of search.
		 *
		 * @since 1.13.0
		 * @param int[] $ids Page IDs.
		 */
		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) apply_filters( 'kaamase_seo_private_pages', $ids ) ) ) ) );

		return $ids;
	}
}

if ( ! function_exists( 'kaamase_seo_sitemap_skip_private' ) ) {
	/**
	 * Leave the account pages out of the page sitemap.
	 *
	 * @since 1.13.0
	 * @param array  $args      Query arguments.
	 * @param string $post_type Post type.
	 * @return array
	 */
	function kaamase_seo_sitemap_skip_private( $args, $post_type ) {

		if ( 'page' !== $post_type ) {
			return $args;
		}

		$skip = kaamase_seo_private_pages();

		if ( $skip ) {
			$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $skip );
		}

		return $args;
	}
}
add_filter( 'wp_sitemaps_posts_query_args', 'kaamase_seo_sitemap_skip_private', 10, 2 );

if ( ! function_exists( 'kaamase_seo_robots' ) ) {
	/**
	 * Mark the account pages noindex. Links on them are still followed.
	 *
	 * @since 1.13.0
	 * @param array $robots Robots directives.
	 * @return array
	 */
	function kaamase_seo_robots( $robots ) {

		if ( ! is_page() ) {
			return $robots;
		}

		$skip = kaamase_seo_private_pages();

		if ( $skip && is_page( $skip ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['max-image-preview'] );
		}

		return $robots;
	}
}
add_filter( 'wp_robots', 'kaamase_seo_robots' );


/* ==========================================================================
   3. WHAT A PAGE IS ABOUT

   Small readers used by the titles, the descriptions and llms.txt.
   ========================================================================== */

if ( ! function_exists( 'kaamase_seo_term_name' ) ) {
	/**
	 * The name of the trade or district a listing is filtered to.
	 *
	 * @since 1.13.0
	 * @param string $taxonomy kaamase_trade or kaamase_district.
	 * @return string Empty when there is no single one.
	 */
	function kaamase_seo_term_name( $taxonomy ) {

		$slug = get_query_var( $taxonomy );

		if ( ! is_string( $slug ) || '' === $slug || '0' === $slug || false !== strpos( $slug, ',' ) || false !== strpos( $slug, '+' ) ) {
			return '';
		}

		$term = get_term_by( 'slug', sanitize_title( $slug ), $taxonomy );

		return ( $term && ! is_wp_error( $term ) ) ? (string) $term->name : '';
	}
}

if ( ! function_exists( 'kaamase_seo_where' ) ) {
	/**
	 * "Dimapur, Nagaland", or "Nagaland" when no district is chosen.
	 *
	 * Descriptions ask for it untranslated. They are left in English, and
	 * a Hindi place name in the middle of an English sentence reads worse
	 * than either language on its own.
	 *
	 * @since 1.13.0
	 * @param string $district  District name, or empty.
	 * @param bool   $translate False for the English wording.
	 * @return string
	 */
	function kaamase_seo_where( $district, $translate = true ) {

		if ( ! $translate ) {
			return '' !== $district ? $district . ', Nagaland' : 'Nagaland';
		}

		return '' !== $district
			/* translators: %s: district name */
			? sprintf( __( '%s, Nagaland', 'kaamase-core' ), $district )
			: __( 'Nagaland', 'kaamase-core' );
	}
}

if ( ! function_exists( 'kaamase_seo_post_trade' ) ) {
	/**
	 * The first trade on a profile or job.
	 *
	 * @since 1.13.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function kaamase_seo_post_trade( $post_id ) {

		$terms = get_the_terms( $post_id, 'kaamase_trade' );

		return ( is_array( $terms ) && ! empty( $terms ) ) ? (string) $terms[0]->name : '';
	}
}

if ( ! function_exists( 'kaamase_seo_post_district' ) ) {
	/**
	 * The district of a profile or job, by name.
	 *
	 * @since 1.13.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function kaamase_seo_post_district( $post_id ) {

		if ( function_exists( 'kaamase_read_field' ) && function_exists( 'kaamase_district_name' ) ) {

			$name = (string) kaamase_district_name( (string) kaamase_read_field( $post_id, 'district' ) );

			if ( '' !== $name ) {
				return $name;
			}
		}

		$terms = get_the_terms( $post_id, 'kaamase_district' );

		return ( is_array( $terms ) && ! empty( $terms ) ) ? (string) $terms[0]->name : '';
	}
}

if ( ! function_exists( 'kaamase_seo_districts' ) ) {
	/**
	 * Every district, by name, in order.
	 *
	 * @since 1.13.0
	 * @return WP_Term[]
	 */
	function kaamase_seo_districts() {

		if ( ! taxonomy_exists( 'kaamase_district' ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'kaamase_district',
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		return ( is_array( $terms ) ) ? $terms : array();
	}
}

if ( ! function_exists( 'kaamase_seo_trim' ) ) {
	/**
	 * Shorten text to fit under a search result, on a word boundary.
	 *
	 * @since 1.13.0
	 * @param string $text  Text.
	 * @param int    $limit Characters.
	 * @return string
	 */
	function kaamase_seo_trim( $text, $limit = 160 ) {

		$text = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );

		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}

		$cut   = mb_substr( $text, 0, $limit - 1 );
		$space = mb_strrpos( $cut, ' ' );

		if ( false !== $space && $space > $limit * 0.6 ) {
			$cut = mb_substr( $cut, 0, $space );
		}

		// A Unicode aware trim. rtrim() works on bytes and can cut a Hindi letter in half.
		return (string) preg_replace( '/[\s,.;:\-–]+$/u', '', $cut ) . '…';
	}
}


/* ==========================================================================
   4. THE QUESTIONS ON THE HOMEPAGE

   Written once and used twice: shown on the homepage, and given to search
   engines as FAQPage data. Google only accepts FAQ data that matches what
   a visitor can read on the page, so the two must never drift apart.
   ========================================================================== */

if ( ! function_exists( 'kaamase_home_faq' ) ) {
	/**
	 * The homepage questions and answers.
	 *
	 * Asked the way people ask a search box or an assistant. Every answer is
	 * something the platform really does.
	 *
	 * @since 1.13.0
	 * @return array[] Each with q and a.
	 */
	function kaamase_home_faq() {

		$districts = wp_list_pluck( kaamase_seo_districts(), 'name' );
		$count     = count( $districts );

		$faq = array(
			array(
				'q' => __( 'What is Kaam Ase?', 'kaamase-core' ),
				'a' => __( 'Kaam Ase, Nagamese for "there is work", is a free job platform built for Nagaland. It connects people looking for work with people looking to hire, on the website and on the Android and iPhone apps. It is run from Dimapur by Nagaland Me.', 'kaamase-core' ),
			),
			array(
				'q' => __( 'How can I find a job in Nagaland?', 'kaamase-core' ),
				'a' => __( 'Register free on Kaam Ase with your name, district, trade and phone number. Browse the jobs posted in your district, and employers can find your profile and contact you. With the app you also get one alert a day for new work in your trade and district. Kaam Ase never asks a worker for money to get a job.', 'kaamase-core' ),
			),
			array(
				'q' => __( 'Where can I find workers in Nagaland?', 'kaamase-core' ),
				'a' => __( 'On Kaam Ase. Search for masons, electricians, plumbers, carpenters, drivers, cooks, helpers and many other trades by district, see who is available now, their ratings and who vouches for them, then get their number through Kaam Ase. You can also post a job free and let workers come to you.', 'kaamase-core' ),
			),
			array(
				'q' => __( 'Is Kaam Ase free?', 'kaamase-core' ),
				'a' => __( 'Yes. Registering, looking for work and being hired is free for workers, and always will be. Employers can post jobs free too. An optional paid plan adds extras such as more urgent posts, but it never changes what a worker pays, which is nothing.', 'kaamase-core' ),
			),
		);

		if ( $count ) {
			$faq[] = array(
				'q' => __( 'Which districts of Nagaland does Kaam Ase cover?', 'kaamase-core' ),
				'a' => sprintf(
					/* translators: 1: number of districts, 2: their names separated by commas */
					_n( 'All %1$d district: %2$s.', 'All %1$d districts: %2$s.', $count, 'kaamase-core' ),
					$count,
					implode( ', ', $districts )
				),
			);
		}

		$faq[] = array(
			'q' => __( 'Is my phone number shown publicly?', 'kaamase-core' ),
			'a' => __( 'No. Nobody\'s number is printed on a public page. An employer has to look it up through Kaam Ase, lookups are limited and recorded, and you can choose to approve every request yourself before your number is shared.', 'kaamase-core' ),
		);

		$faq[] = array(
			'q' => __( 'Is there a Kaam Ase app?', 'kaamase-core' ),
			'a' => __( 'Yes, free for Android and iPhone. Open kaamase.com/app on your phone and it takes you to the right store.', 'kaamase-core' ),
		);

		$faq[] = array(
			'q' => __( 'Which languages can I use Kaam Ase in?', 'kaamase-core' ),
			'a' => __( 'English, Hindi and Nagamese. You can change the language at any time, from the top of the website or in the app.', 'kaamase-core' ),
		);

		/**
		 * Filter the homepage questions.
		 *
		 * @since 1.13.0
		 * @param array[] $faq Each with q and a.
		 */
		return (array) apply_filters( 'kaamase_home_faq', $faq );
	}
}


/* ==========================================================================
   5. TITLES
   ========================================================================== */

if ( ! function_exists( 'kaamase_seo_title_parts' ) ) {
	/**
	 * The title a browser tab and a search result show.
	 *
	 * @since 1.13.0
	 * @param array $parts Title parts: title, page, tagline, site.
	 * @return array
	 */
	function kaamase_seo_title_parts( $parts ) {

		if ( is_admin() || is_feed() || ! kaamase_seo_wanted() ) {
			return $parts;
		}

		if ( is_front_page() ) {

			// WordPress leaves the site name off the homepage title. Put it back after the words people search for.
			return array(
				'title' => __( 'Jobs and workers in Nagaland', 'kaamase-core' ),
				'site'  => get_bloginfo( 'name', 'display' ),
			);
		}

		$trade    = kaamase_seo_term_name( 'kaamase_trade' );
		$district = kaamase_seo_term_name( 'kaamase_district' );
		$where    = kaamase_seo_where( $district );
		$title    = '';

		if ( is_post_type_archive( 'kaamase_job' ) ) {

			$title = '' !== $trade
				/* translators: 1: trade name, 2: place, such as "Dimapur, Nagaland" */
				? sprintf( __( '%1$s jobs in %2$s', 'kaamase-core' ), $trade, $where )
				/* translators: %s: place, such as "Dimapur, Nagaland" */
				: sprintf( __( 'Jobs in %s', 'kaamase-core' ), $where );

		} elseif ( is_post_type_archive( 'kaamase_gang' ) ) {

			/* translators: %s: place, such as "Dimapur, Nagaland" */
			$title = sprintf( __( 'Teams for hire in %s', 'kaamase-core' ), $where );

		} elseif ( is_post_type_archive( 'kaamase_worker' ) ) {

			$title = '' !== $trade
				/* translators: 1: trade name, 2: place, such as "Dimapur, Nagaland" */
				? sprintf( __( '%1$s in %2$s', 'kaamase-core' ), $trade, $where )
				/* translators: %s: place, such as "Dimapur, Nagaland" */
				: sprintf( __( 'Workers in %s', 'kaamase-core' ), $where );

		} elseif ( is_tax( 'kaamase_trade' ) && '' !== $trade ) {

			/* translators: 1: trade name, 2: place, such as "Dimapur, Nagaland" */
			$title = sprintf( __( '%1$s in %2$s: workers and jobs', 'kaamase-core' ), $trade, $where );

		} elseif ( is_tax( 'kaamase_district' ) && '' !== $district ) {

			/* translators: %s: place, such as "Dimapur, Nagaland" */
			$title = sprintf( __( 'Jobs and workers in %s', 'kaamase-core' ), $where );

		} elseif ( is_singular( array( 'kaamase_worker', 'kaamase_gang' ) ) ) {

			$id       = get_queried_object_id();
			$trade    = kaamase_seo_post_trade( $id );
			$district = kaamase_seo_post_district( $id );

			if ( '' !== $trade && '' !== $district ) {
				/* translators: 1: trade name, 2: district name */
				$parts = array_merge( array( 'title' => $parts['title'], 'place' => sprintf( __( '%1$s in %2$s', 'kaamase-core' ), $trade, $district ) ), $parts );
			} elseif ( '' !== $trade || '' !== $district ) {
				$parts = array_merge( array( 'title' => $parts['title'], 'place' => '' !== $trade ? $trade : $district ), $parts );
			}

			return $parts;

		} elseif ( is_singular( 'kaamase_job' ) ) {

			$id       = get_queried_object_id();
			$district = kaamase_seo_post_district( $id );

			// Only where the job's own title does not already name the place.
			if ( '' !== $district && false === mb_stripos( (string) $parts['title'], $district ) ) {
				$parts = array_merge( array( 'title' => $parts['title'], 'place' => kaamase_seo_where( $district ) ), $parts );
			}

			return $parts;
		}

		if ( '' !== $title ) {
			$parts['title'] = $title;
		}

		return $parts;
	}
}
add_filter( 'document_title_parts', 'kaamase_seo_title_parts', 20 );


/* ==========================================================================
   6. THE LINE UNDER A SEARCH RESULT
   ========================================================================== */

if ( ! function_exists( 'kaamase_seo_description' ) ) {
	/**
	 * The meta description for the page being shown.
	 *
	 * @since 1.13.0
	 * @return string Empty when this page is better left to the search engine.
	 */
	function kaamase_seo_description() {

		if ( is_front_page() ) {
			return __( 'Find jobs and hire workers in Nagaland: masons, electricians, plumbers, drivers, cooks and helpers in every district, from Dimapur to Mon. Free for workers.', 'kaamase-core' );
		}

		$trade    = kaamase_seo_term_name( 'kaamase_trade' );
		$district = kaamase_seo_term_name( 'kaamase_district' );
		$where    = kaamase_seo_where( $district, false );

		if ( is_post_type_archive( 'kaamase_job' ) ) {

			return '' !== $trade
				/* translators: 1: trade name, 2: place */
				? sprintf( __( 'Latest %1$s jobs in %2$s, posted by employers on Kaam Ase. See the pay and the place, and get in touch free. Workers never pay to apply.', 'kaamase-core' ), $trade, $where )
				/* translators: %s: place */
				: sprintf( __( 'Latest jobs in %s: daily wage, monthly and contract work posted by employers. See the pay and the place, and get in touch free. Workers never pay.', 'kaamase-core' ), $where );
		}

		if ( is_post_type_archive( array( 'kaamase_worker', 'kaamase_gang' ) ) || ( is_tax( 'kaamase_trade' ) && '' !== $trade ) ) {

			return '' !== $trade
				/* translators: 1: trade name, 2: place */
				? sprintf( __( 'Find %1$s workers for hire in %2$s on Kaam Ase. See who is available now, their ratings and local vouches, then get in touch. Free to search.', 'kaamase-core' ), $trade, $where )
				/* translators: %s: place */
				: sprintf( __( 'Find workers for hire in %s: masons, electricians, plumbers, drivers and helpers. See who is available now, their ratings and local vouches.', 'kaamase-core' ), $where );
		}

		if ( is_tax( 'kaamase_district' ) && '' !== $district ) {
			/* translators: 1: district name, 2: district name again */
			return sprintf( __( 'Jobs and workers in %1$s, Nagaland. Find work near you, or hire a mason, electrician, plumber, driver or helper in %2$s on Kaam Ase. Free for workers.', 'kaamase-core' ), $district, $district );
		}

		if ( is_singular( 'kaamase_job' ) && function_exists( 'kaamase_share_description' ) ) {
			return kaamase_seo_trim( kaamase_share_description( get_queried_object_id() ) );
		}

		if ( is_singular( array( 'kaamase_worker', 'kaamase_gang', 'kaamase_employer' ) ) ) {

			$id       = get_queried_object_id();
			$post     = get_post( $id );
			$trade    = kaamase_seo_post_trade( $id );
			$district = kaamase_seo_post_district( $id );
			$bits     = array( wp_strip_all_tags( get_the_title( $id ) ) );

			if ( '' !== $trade && '' !== $district ) {
				/* translators: 1: trade name, 2: district name */
				$bits[] = sprintf( __( '%1$s in %2$s, Nagaland', 'kaamase-core' ), $trade, $district );
			} elseif ( '' !== $district ) {
				$bits[] = kaamase_seo_where( $district, false );
			}

			$about = $post ? wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ), 22, '' ) : '';

			if ( '' !== $about ) {
				$bits[] = $about;
			}

			return kaamase_seo_trim( implode( '. ', $bits ) . '. ' . __( 'On Kaam Ase.', 'kaamase-core' ) );
		}

		if ( is_singular() ) {

			$post = get_queried_object();

			if ( $post instanceof WP_Post ) {

				$text = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( (string) $post->post_content );
				$text = trim( wp_strip_all_tags( $text ) );

				if ( '' !== $text ) {
					return kaamase_seo_trim( $text );
				}
			}
		}

		return '';
	}
}

if ( ! function_exists( 'kaamase_seo_print_head' ) ) {
	/**
	 * Write the description, and the canonical address of the homepage.
	 *
	 * WordPress writes a canonical link on single pages only. The homepage
	 * is also reached with ?from=paper or a shared link's tracking, and
	 * each of those is otherwise a separate page to a search engine.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	function kaamase_seo_print_head() {

		if ( ! kaamase_seo_wanted() ) {
			return;
		}

		$description = kaamase_seo_description();

		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}

		if ( is_front_page() && ! is_singular() && ! is_paged() ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( home_url( '/' ) ) );
		}
	}
}
add_action( 'wp_head', 'kaamase_seo_print_head', 2 );


/* ==========================================================================
   7. STRUCTURED DATA

   schema.php already writes the Organization on the homepage and a
   JobPosting on every open job. This adds to the Organization, and writes
   the WebSite and the questions beside it.
   ========================================================================== */

if ( ! function_exists( 'kaamase_seo_store_links' ) ) {
	/**
	 * The app's store pages, when the theme knows them.
	 *
	 * @since 1.13.0
	 * @return string[]
	 */
	function kaamase_seo_store_links() {

		$links = function_exists( 'kaamase_app_store_links' ) ? (array) kaamase_app_store_links() : array();

		return array_values(
			array_filter(
				array_map( 'strval', $links ),
				static function ( $url ) {
					return 0 === strpos( $url, 'https://' );
				}
			)
		);
	}
}

if ( ! function_exists( 'kaamase_seo_organisation' ) ) {
	/**
	 * Say plainly what Kaam Ase is, where, for whom, and in which languages.
	 *
	 * @since 1.13.0
	 * @param array $schema Organization data from schema.php.
	 * @return array
	 */
	function kaamase_seo_organisation( $schema ) {

		if ( ! kaamase_seo_wanted() ) {
			return $schema;
		}

		$schema['@id']           = home_url( '/#organization' );
		$schema['alternateName'] = 'Kaamase';
		$schema['slogan']        = 'There is work.';
		$schema['description']   = __( 'Kaam Ase is a free job platform for Nagaland. People looking for work find jobs, and employers find and hire local workers, in every district of Nagaland.', 'kaamase-core' );
		$schema['areaServed']    = array(
			'@type' => 'State',
			'name'  => 'Nagaland',
		);
		$schema['knowsLanguage'] = array( 'en', 'hi', 'nag' );
		$schema['parentOrganization'] = array(
			'@type' => 'Organization',
			'name'  => 'Nagaland Me',
		);

		$links = kaamase_seo_store_links();

		if ( $links ) {
			$schema['sameAs'] = $links;
		}

		return $schema;
	}
}
add_filter( 'kaamase_organisation_schema', 'kaamase_seo_organisation' );

if ( ! function_exists( 'kaamase_seo_print_home_schema' ) ) {
	/**
	 * The WebSite and the homepage questions.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	function kaamase_seo_print_home_schema() {

		if ( ! kaamase_seo_wanted() || ! is_front_page() || is_paged() ) {
			return;
		}

		$faq = array();

		foreach ( kaamase_home_faq() as $row ) {
			$faq[] = array(
				'@type'          => 'Question',
				'name'           => (string) $row['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => (string) $row['a'],
				),
			);
		}

		$graph = array(
			array(
				'@type'         => 'WebSite',
				'@id'           => home_url( '/#website' ),
				'url'           => home_url( '/' ),
				'name'          => get_bloginfo( 'name' ),
				'alternateName' => 'Kaamase',
				'description'   => __( 'Jobs and workers in Nagaland', 'kaamase-core' ),
				'inLanguage'    => str_replace( '_', '-', determine_locale() ),
				'publisher'     => array( '@id' => home_url( '/#organization' ) ),
			),
		);

		if ( $faq ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => home_url( '/#faq' ),
				'mainEntity' => $faq,
			);
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode(
				array(
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);
	}
}
add_action( 'wp_head', 'kaamase_seo_print_home_schema', 21 );


/* ==========================================================================
   8. /llms.txt

   A short plain summary for AI assistants, in the format they look for:
   what this is, the facts, and the pages worth reading. In English only,
   because that is the language these tools read the web in, and they
   answer people in whatever language the person asks.

   Served by WordPress rather than as a file, so it stays correct as
   districts and trades change. There is no file on disk, so the web
   server hands the request to WordPress like any other address.
   ========================================================================== */

if ( ! function_exists( 'kaamase_llms_text' ) ) {
	/**
	 * Build the text.
	 *
	 * @since 1.13.0
	 * @return string
	 */
	function kaamase_llms_text() {

		$home  = home_url( '/' );
		$lines = array();

		$lines[] = '# Kaam Ase';
		$lines[] = '';
		$lines[] = '> Kaam Ase ("there is work" in Nagamese) is a free job and worker platform for Nagaland, India, run from Dimapur. People looking for work find jobs, and employers find and hire local workers (masons, electricians, plumbers, carpenters, drivers, cooks, helpers and many other trades) in every district of Nagaland, on the website and on free Android and iPhone apps.';
		$lines[] = '';
		$lines[] = 'Facts:';
		$lines[] = '';
		$lines[] = '- Free for workers, always: no fee to register, look for work or be hired. Kaam Ase never asks a worker for money to get a job.';
		$lines[] = '- Employers post jobs free. An optional paid plan adds extras and changes nothing a worker pays.';
		$lines[] = '- Phone numbers are never shown on public pages. Lookups are limited and recorded, and a worker can require their approval for every request.';
		$lines[] = '- Every new employer\'s first job is read by a person before workers see it. Jobs below the legal minimum wage cannot be posted.';
		$lines[] = '- Ratings come only from people who actually worked together. Profiles can carry a vouch from a local authority such as a GB or church elder.';
		$lines[] = '- Languages: English, Hindi and Nagamese.';
		$lines[] = '- Apps: free for Android and iPhone at ' . $home . 'app';
		$lines[] = '- Operated by Nagaland Me, Dimapur, Nagaland, India.';

		$districts = kaamase_seo_districts();

		if ( $districts ) {
			$lines[] = '- Districts covered: ' . implode( ', ', wp_list_pluck( $districts, 'name' ) ) . '.';
		}

		$lines[] = '';
		$lines[] = '## Find jobs in Nagaland';
		$lines[] = '';

		$jobs = get_post_type_archive_link( 'kaamase_job' );

		if ( $jobs ) {
			$lines[] = '- [Latest jobs in Nagaland](' . $jobs . '): open jobs posted by employers across the state';
		}

		foreach ( $districts as $term ) {

			$link = get_term_link( $term );

			if ( ! is_wp_error( $link ) ) {
				$lines[] = '- [Jobs and workers in ' . $term->name . '](' . $link . ')';
			}
		}

		$lines[] = '';
		$lines[] = '## Find workers in Nagaland';
		$lines[] = '';

		$workers = get_post_type_archive_link( 'kaamase_worker' );

		if ( $workers ) {
			$lines[] = '- [Workers in Nagaland](' . $workers . '): workers for hire, with availability, ratings and local vouches';
		}

		$teams = get_post_type_archive_link( 'kaamase_gang' );

		if ( $teams ) {
			$lines[] = '- [Teams for hire](' . $teams . '): a leader and a crew, for work that needs several people';
		}

		if ( taxonomy_exists( 'kaamase_trade' ) ) {

			$trades = get_terms(
				array(
					'taxonomy'   => 'kaamase_trade',
					'hide_empty' => false,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'number'     => 20,
				)
			);

			if ( is_array( $trades ) ) {
				foreach ( $trades as $term ) {

					$link = get_term_link( $term );

					if ( ! is_wp_error( $link ) ) {
						$lines[] = '- [' . $term->name . ' in Nagaland](' . $link . ')';
					}
				}
			}
		}

		$lines[] = '';
		$lines[] = '## About';
		$lines[] = '';

		$pages = array(
			'how-it-works' => 'How Kaam Ase works',
			'safety'       => 'Safety',
			'about'        => 'About Kaam Ase',
			'trades'       => 'All trades',
			'districts'    => 'All districts',
			'employers'    => 'Who is hiring',
			'contact'      => 'Contact',
		);

		foreach ( $pages as $slug => $label ) {

			$page = get_page_by_path( $slug );

			if ( $page && 'publish' === $page->post_status ) {
				$lines[] = '- [' . $label . '](' . get_permalink( $page ) . ')';
			}
		}

		$lines[] = '- [Get the app](' . $home . 'app)';

		/**
		 * Filter the llms.txt text.
		 *
		 * @since 1.13.0
		 * @param string[] $lines Lines of Markdown.
		 */
		$lines = (array) apply_filters( 'kaamase_llms_lines', $lines );

		return implode( "\n", $lines ) . "\n";
	}
}

if ( ! function_exists( 'kaamase_llms_serve' ) ) {
	/**
	 * Answer /llms.txt.
	 *
	 * @since 1.13.0
	 * @param WP $wp The request.
	 * @return void
	 */
	function kaamase_llms_serve( $wp ) {

		if ( 'llms.txt' !== $wp->request ) {
			return;
		}

		/**
		 * Filter whether /llms.txt is served.
		 *
		 * @since 1.13.0
		 * @param bool $serve Whether to serve it.
		 */
		if ( ! apply_filters( 'kaamase_llms_txt', true ) ) {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );

		echo kaamase_llms_text(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text, not HTML.
		exit;
	}
}
add_action( 'parse_request', 'kaamase_llms_serve' );
