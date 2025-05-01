<?php
/**
 * /premium/common.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Returns related searches.
 *
 * Returns a list of searches related to the given search query. Example:
 *
 * relevanssi_related(get_search_query(), '<h3>Related Searches:</h3><ul><li>', '</li><li>', '</li></ul>' );
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param string $query  The search query (get_search_query() is a good way to get the current query).
 * @param string $pre    What is printed before the results, default '<ul><li>'.
 * @param string $sep    The separator between individual results, default '</li><li>'.
 * @param string $post   What is printed after the results, default '</li></ul>'.
 * @param int    $number Number of related searches to show, default 5.
 *
 * @author John Blackbourn
 */
function relevanssi_related( $query, $pre = '<ul><li>', $sep = '</li><li>', $post = '</li></ul>', $number = 5 ) {
	global $wpdb, $relevanssi_variables;

	$output  = array();
	$related = array();
	$tokens  = relevanssi_tokenize( $query, true, -1, 'search_query' );
	if ( empty( $tokens ) ) {
		return;
	}

	$query_slug = sanitize_title( $query );
	$related    = get_transient( 'related-' . $query_slug );
	if ( ! $related ) {
		$related = array();
		/**
		 * Loop over each token in the query and return logged queries which:
		 *
		 *  - Contain a matching token
		 *  - Don't match the query or the token exactly
		 *  - Have at least 2 hits
		 *  - Have been queried at least twice
		 *
		 * then order by most queried with a max of $number results.
		 */
		foreach ( $tokens as $token => $count ) {
			$escaped_token = '%' . $wpdb->esc_like( "$token" ) . '%';
			$log_table     = $relevanssi_variables['log_table'];
			$results       = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT query ' .
					"FROM $log_table " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'WHERE query LIKE %s
					AND query NOT IN (%s, %s)
					AND hits > 1
					GROUP BY query
					HAVING count(query) > 1
					ORDER BY count(query) DESC
					LIMIT %d',
					$escaped_token,
					$token,
					$query,
					$number
				)
			);
			if ( is_array( $results ) ) {
				foreach ( $results as $result ) {
					$related[] = $result->query;
				}
			}
		}
		if ( empty( $related ) ) {
			return;
		} else {
			set_transient( 'related-' . $query_slug, $related, 60 * 60 * 24 * 7 );
		}
	}

	// Order results by most matching tokens then slice to a maximum of $number results.
	$related = array_keys( array_count_values( $related ) );
	$related = array_slice( $related, 0, $number );
	foreach ( $related as $rel ) {
		$url      = add_query_arg(
			array(
				's' => rawurlencode( $rel ),
			),
			home_url()
		);
		$rel      = esc_attr( $rel );
		$output[] = "<a href='$url'>$rel</a>";
	}

	echo $pre . implode( $sep, $output ) . $post; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Replaces get_posts() in a way that handles users and taxonomy terms.
 *
 * Custom-made get_posts() replacement that creates post objects for users and
 * taxonomy terms. For regular posts, the function uses get_posts() and a
 * caching mechanism.
 *
 * @global array $relevanssi_post_array The global Relevanssi post array used as
 * a cache.
 *
 * @param int|string $id      The post ID to fetch. If the ID is a string and
 * begins with 'u_', it's considered a user ID and if it begins with '**', it's
 * considered a taxonomy term.
 * @param int        $blog_id The blog ID, used to make caching work in
 * multisite environment. Defaults to -1, which means the blog id is not used.
 *
 * @return object|WP_Error $post The post object for the post ID or a WP_Error
 * object if the post ID is not found.
 */
function relevanssi_premium_get_post( $id, int $blog_id = -1 ) {
	global $relevanssi_post_array;
	$type = substr( $id, 0, 2 );
	switch ( $type ) {
		case 'u_':
			list( , $id ) = explode( '_', $id );

			$user                  = get_userdata( $id );
			$post                  = new stdClass();
			$post->post_title      = $user->display_name;
			$post->post_content    = $user->description;
			$post->post_type       = 'user';
			$post->ID              = $id;
			$post->relevanssi_link = get_author_posts_url( $id );
			$post->post_status     = 'publish';
			$post->post_date       = gmdate( 'Y-m-d H:i:s' );
			$post->post_author     = 0;
			$post->post_name       = '';
			$post->post_excerpt    = '';
			$post->comment_status  = '';
			$post->ping_status     = '';
			$post->user_id         = $id;

			/**
			 * Filters the user profile post object.
			 *
			 * After a post object is created from the user profile, it is
			 * passed through this filter so it can be modified.
			 *
			 * @param object $post The post object.
			 */
			$post = apply_filters( 'relevanssi_user_profile_to_post', $post );
			break;
		case 'p_':
			list( , $id ) = explode( '_', $id );

			$post_type_name        = relevanssi_get_post_type_by_id( $id );
			$post_type             = get_post_type_object( $post_type_name );
			$post                  = new stdClass();
			$post->post_title      = $post_type->label;
			$post->post_content    = $post_type->description;
			$post->post_type       = 'post_type';
			$post->ID              = $id;
			$post->relevanssi_link = get_post_type_archive_link( $post_type_name );
			$post->post_status     = 'publish';
			$post->post_date       = gmdate( 'Y-m-d H:i:s' );
			$post->post_author     = 0;
			$post->post_name       = '';
			$post->post_excerpt    = '';
			$post->comment_status  = '';
			$post->ping_status     = '';
			$post->post_type_id    = $post_type_name;

			/**
			 * Filters the post type post object.
			 *
			 * After a post object is created from a post type, it is passed
			 * through this filter so it can be modified.
			 *
			 * @param stdClass $post The post object.
			 */
			$post = apply_filters( 'relevanssi_post_type_to_post', $post );
			break;
		case '**':
			list( , $taxonomy, $id ) = explode( '**', $id );

			$term = get_term( $id, $taxonomy );
			if ( is_wp_error( $term ) ) {
				return new WP_Error( 'term_not_found', "Taxonomy term wasn't found." );
			}
			$post                  = new stdClass();
			$post->post_title      = $term->name;
			$post->post_content    = $term->description;
			$post->post_type       = $taxonomy;
			$post->ID              = -1;
			$post->post_status     = 'publish';
			$post->post_date       = gmdate( 'Y-m-d H:i:s' );
			$post->relevanssi_link = get_term_link( $term, $taxonomy );
			$post->post_author     = 0;
			$post->post_name       = '';
			$post->post_excerpt    = '';
			$post->comment_status  = '';
			$post->ping_status     = '';
			$post->term_id         = $id;
			$post->post_parent     = $term->parent;

			/**
			 * Filters the taxonomy term post object.
			 *
			 * After a post object is created from the taxonomy term, it is
			 * passed through this filter so it can be modified.
			 *
			 * @param Object $post The post object.
			 */
			$post = apply_filters( 'relevanssi_taxonomy_term_to_post', $post );
			break;
		default:
			$cache_id = $id;
			if ( -1 !== $blog_id ) {
				$cache_id = $blog_id . '|' . $id;
			}
			if ( isset( $relevanssi_post_array[ $cache_id ] ) ) {
				// Post exists in the cache.
				$post = $relevanssi_post_array[ $cache_id ];
			} else {
				$post = get_post( $id );

				$relevanssi_post_array[ $cache_id ] = $post;
			}
			if (
				'on' === get_option( 'relevanssi_link_pdf_files' )
				&& ! empty( $post->post_mime_type )
				) {
				/**
				 * Filters the URL to the attachment file.
				 *
				 * If you set the attachment indexing to index attachments that
				 * are stored outside the WP attachment system, use this filter
				 * to provide a link to the attachment.
				 *
				 * @param string The URL to the attachment file.
				 * @param int    The attachment post ID number.
				 */
				$post->relevanssi_link = apply_filters(
					'relevanssi_get_attachment_url',
					wp_get_attachment_url( $post->ID ),
					$post->ID
				);
			}
	}

	if ( ! $post ) {
		$post = new WP_Error( 'post_not_found', __( 'The requested post does not exist.' ) );
	}

	return $post;
}

/**
 * Returns a list of indexed taxonomies.
 *
 * This will also include "user", if user profiles are indexed, and "post_type", if
 * post type archives are indexed.
 *
 * @return array $non_post_post_types_array An array of taxonomies Relevanssi is set
 * to index (and "user" or "post_type").
 */
function relevanssi_get_non_post_post_types() {
	// These post types are not posts, ie. they are taxonomy terms and user profiles.
	$non_post_post_types_array = array();
	if ( get_option( 'relevanssi_index_taxonomies' ) ) {
		$taxonomies = get_option( 'relevanssi_index_terms' );
		if ( is_array( $taxonomies ) ) {
			$non_post_post_types_array = $taxonomies;
		}
	}
	if ( get_option( 'relevanssi_index_users' ) ) {
		$non_post_post_types_array[] = 'user';
	}
	if ( get_option( 'relevanssi_index_post_type_archives' ) ) {
		$non_post_post_types_array[] = 'post_type';
	}
	return $non_post_post_types_array;
}

/**
 * Gets the PDF content for the child posts of the post.
 *
 * @global $wpdb The WordPress database interface.
 *
 * @param int $post_id The post ID of the parent post.
 *
 * @return array $pdf_content The PDF content of the child posts.
 */
function relevanssi_get_child_pdf_content( $post_id ): array {
	global $wpdb;

	$post_id     = intval( $post_id );
	$pdf_content = '';

	if ( $post_id > 0 ) {
		/**
		 * Filters the custom field value before indexing.
		 *
		 * @param array            Custom field values.
		 * @param string $field    The custom field name.
		 * @param int    $post_id The post ID.
		 */
		return apply_filters(
			'relevanssi_custom_field_value',
			$wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta AS pm, $wpdb->posts AS p WHERE pm.post_id = p.ID AND p.post_parent = $post_id AND meta_key = '_relevanssi_pdf_content'" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'_relevanssi_pdf_content',
			$post_id
		);
		// Only user-provided variable is $post_id, and that's from Relevanssi and sanitized as an int.
	}

	return array();
}

/**
 * Provides the Premium version "Did you mean" recommendations.
 *
 * Provides a better version of "Did you mean" recommendations, using the
 * spelling corrector class to generate a correct spelling.
 *
 * @global WP_Query $wp_query The query object, used to check the number of
 * posts found.
 *
 * @param string $query The search query to correct.
 * @param string $pre   Text printed out before the suggestion.
 * @param string $post  Text printed out after the suggestion.
 * @param int    $n     Maximum number of hits before the suggestions are shown,
 * default 5.
 *
 * @return string Empty string if there's nothing to correct; otherwise a string
 * with the HTML link to the corrected search.
 */
function relevanssi_premium_didyoumean( $query, $pre, $post, $n = 5 ) {
	global $wp_query;

	$total_results = $wp_query->found_posts;
	$result        = '';

	if ( $total_results > $n ) {
		return $result;
	}

	$suggestion = relevanssi_premium_generate_suggestion( $query );
	if ( true === $suggestion ) {
		return $result;
	}
	if ( empty( $suggestion ) ) {
		$suggestion = relevanssi_simple_generate_suggestion( $query );
	}

	$result = null;
	if ( $suggestion ) {
		$url = trailingslashit( get_bloginfo( 'url' ) );
		$url = esc_attr(
			add_query_arg(
				array(
					's' => rawurlencode( $suggestion ),
				),
				$url
			)
		);
		/** This filter is documented in lib/didyoumean.php */
		$url = apply_filters( 'relevanssi_didyoumean_url', $url, $query, $suggestion );

		// Escape the suggestion to avoid XSS attacks.
		$suggestion = htmlspecialchars( $suggestion );

		/** This filter is documented in lib/didyoumean.php */
		$result = apply_filters( 'relevanssi_didyoumean_suggestion', "$pre<a href='$url'>$suggestion</a>$post" );
	}
	return $result;
}

/**
 * Generates the "Did you mean" suggestion.
 *
 * Generates "Did you mean" suggestions given a query to correct, using the
 * spelling corrector method.
 *
 * @param string $query The search query to correct.
 *
 * @return string $query Corrected query, empty string if there are no
 * corrections available and true if the query was already correct.
 */
function relevanssi_premium_generate_suggestion( $query ) {
	$corrected_query = '';

	if ( class_exists( 'Relevanssi_SpellCorrector' ) ) {
		$query  = htmlspecialchars_decode( $query, ENT_QUOTES );
		$tokens = relevanssi_tokenize( $query, true, -1, 'search_query' );

		$sc = new Relevanssi_SpellCorrector();

		$correct       = array();
		$exact_matches = 0;
		foreach ( array_keys( $tokens ) as $token ) {
			/**
			 * Filters the tokens for Did you mean suggestions.
			 *
			 * You can use this filter hook to modify the tokens before Relevanssi
			 * tries to come up with Did you mean suggestions for them. If you
			 * return an empty string, the token will be skipped and no suggestion
			 * will be made for the token.
			 *
			 * @param string $token An individual word from the search query.
			 *
			 * @return string The token.
			 */
			$token = apply_filters( 'relevanssi_didyoumean_token', trim( $token ) );
			if ( ! $token ) {
				continue;
			}
			$c = $sc->correct( $token );
			if ( true === $c ) {
				++$exact_matches;
			} elseif ( ! empty( $c ) && strval( $token ) !== $c ) {
				array_push( $correct, $c );
				$query = str_ireplace( $token, $c, $query ); // Replace misspelled word in query with suggestion.
			}
		}
		if ( count( $tokens ) === $exact_matches ) {
			// All tokens are correct.
			return true;
		}
		if ( count( $correct ) > 0 ) {
			// Strip quotes, because they are likely incorrect.
			$query           = str_replace( '"', '', $query );
			$corrected_query = $query;
		}
	}

	return $corrected_query;
}

/**
 * Multisite-friendly get_post().
 *
 * Gets a post using relevanssi_get_post() from the specified subsite.
 *
 * @param int $blogid The blog ID.
 * @param int $id     The post ID.
 *
 * @return object|WP_Error $post The post object or a WP_Error if the post
 * cannot be found.
 */
function relevanssi_get_multisite_post( $blogid, $id ) {
	switch_to_blog( $blogid );
	if ( ! is_numeric( mb_substr( $id, 0, 1 ) ) ) {
		// The post ID does not start with a number; this is a user or a
		// taxonomy term, so suspend cache addition to avoid getting garbage in
		// the cache.
		wp_suspend_cache_addition( true );
	}
	$post = relevanssi_get_post( $id, $blogid );
	restore_current_blog();
	return $post;
}

/**
 * Initializes things for Relevanssi Premium.
 *
 * Adds metaboxes, depending on settings; adds synonym indexing filter if
 * necessary and removes an unnecessary action.
 */
function relevanssi_premium_init() {
	$show_post_controls = true;
	if ( 'on' === get_option( 'relevanssi_hide_post_controls' ) ) {
		$show_post_controls = false;
		/**
		 * Adjusts the capability required to show the Relevanssi post controls
		 * for admins.
		 *
		 * @param string $capability The minimum capability required, default
		 * 'manage_options'.
		 */
		if (
			'on' === get_option( 'relevanssi_show_post_controls' ) &&
			current_user_can( apply_filters( 'relevanssi_options_capability', 'manage_options' ) )
			) {
			$show_post_controls = true;
		}
	}
	if ( $show_post_controls ) {
		add_action( 'add_meta_boxes', 'relevanssi_add_metaboxes' );
	}

	if ( 'on' === get_option( 'relevanssi_index_synonyms' ) ) {
		add_filter( 'relevanssi_indexing_tokens', 'relevanssi_add_indexing_synonyms', 10 );
	}

	// If the relevanssi_save_postdata is not disabled, scheduled publication
	// will swipe out the Relevanssi post controls settings.
	add_action(
		'future_to_publish',
		function () {
			remove_action( 'save_post', 'relevanssi_save_postdata' );
		}
	);

	if ( function_exists( 'do_blocks' ) ) {
		add_action( 'init', 'relevanssi_register_gutenberg_actions', 11 );
	}

	global $pagenow, $relevanssi_variables;
	$on_relevanssi_page = false;
	if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$page = sanitize_file_name( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$base = sanitize_file_name( wp_unslash( plugin_basename( $relevanssi_variables['file'] ) ) );
		if ( $base === $page ) {
			$on_relevanssi_page = true;
		}
	}

	if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_blog_status' ) ) {
		$public = (bool) get_blog_status( get_current_blog_id(), 'public' );
		if ( ! $public && 'options-general.php' === $pagenow && $on_relevanssi_page ) {
			add_action(
				'admin_notices',
				function () {
					printf(
						"<div id='relevanssi-warning' class='update-nag'><p><strong>%s</strong></p></div>",
						esc_html__( 'Your site is not public. By default, Relevanssi does not search private sites. If you want to be able to search on this site, either make it public or add a filter function that returns true on \'relevanssi_multisite_public_status\' filter hook.', 'relevanssi' )
					);
				}
			);
		}
	}

	add_filter( 'relevanssi_remove_punctuation', 'relevanssi_wildcards_pre', 8 );
	add_filter( 'relevanssi_remove_punctuation', 'relevanssi_wildcards_post', 12 );
	add_filter( 'relevanssi_term_where', 'relevanssi_query_wildcards', 10, 2 );

	add_filter( 'relevanssi_indexing_restriction', 'relevanssi_hide_post_restriction' );

	if ( defined( 'RELEVANSSI_API_KEY' ) ) {
		add_filter(
			'pre_option_relevanssi_api_key',
			function () {
				return RELEVANSSI_API_KEY;
			}
		);
		add_filter(
			'pre_site_option_relevanssi_api_key',
			function () {
				return RELEVANSSI_API_KEY;
			}
		);
	}

	$update_translations = false;
	if ( 'on' === get_option( 'relevanssi_update_translations' ) ) {
		$update_translations = true;
	}
	if ( 'on' === get_option( 'relevanssi_do_not_call_home' ) ) {
		$update_translations = false;
	}
	/**
	 * Filters whether to update the Relevanssi translations.
	 *
	 * @param boolean $update_translations If false, don't update translations.
	 */
	$update_translations = apply_filters( 'relevanssi_update_translations', $update_translations );

	if ( $update_translations ) {
		$t15s_updater = new Relevanssi_Language_Packs(
			'plugin',
			'relevanssi',
			'https://packages.translationspress.com/relevanssi/relevanssi/packages.json'
		);
		$t15s_updater->add_project();
	}

	add_action(
		'in_plugin_update_message-' . $relevanssi_variables['plugin_basename'],
		'relevanssi_premium_modify_plugin_update_message'
	);

	// Add the related posts filters if necessary.
	relevanssi_related_init();
}

/**
 * Adds the Relevanssi Premium hide post filter to the indexing restrictions.
 *
 * @global object $wpdb The WP database interface.
 *
 * @param array $restrictions The current set of restrictions.
 *
 * @return array The updated restrictions.
 */
function relevanssi_hide_post_restriction( $restrictions ) {
	global $wpdb;

	$restrictions['mysql']  .= " AND post.ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_hide_post' AND meta_value = 'on')";
	$restrictions['reason'] .= ' ' . __( 'Relevanssi index exclude', 'relevanssi' );

	return $restrictions;
}

/**
 * Replaces the standard permalink with $post->relevanssi_link if it exists.
 *
 * Relevanssi adds a link to the user profile or taxonomy term page to
 * $post->relevanssi_link. This function replaces permalink with that link, if
 * it exists.
 *
 * @param string $permalink The permalink to filter.
 * @param int    $post_id   The post ID.
 *
 * @return string $permalink Modified permalink.
 */
function relevanssi_post_link_replace( $permalink, $post_id ) {
	$post = relevanssi_get_post( $post_id );
	if ( property_exists( $post, 'relevanssi_link' ) ) {
		$permalink = $post->relevanssi_link;
	}
	return $permalink;
}

/**
 * Fetches a list of words from the Relevanssi database for spelling corrector.
 *
 * A helper function for the spelling corrector. Gets the word list from the
 * 'relevanssi_words' option. If the data is expired (more than a month old),
 * this function triggers an asynchronous refresh action that fetches new words
 * from the Relevanssi database to use as a source material for spelling
 * suggestions.
 *
 * @return array $words An array of words, with the word as the key and number
 * of occurrances as the value.
 */
function relevanssi_get_words() {
	$data = get_option(
		'relevanssi_words',
		array(
			'expire' => 0,
			'words'  => array(),
		)
	);

	if ( time() > $data['expire'] ) {
		relevanssi_launch_ajax_action( 'relevanssi_get_words' );
	}

	return $data['words'];
}

/**
 * Adds the Premium options.
 *
 * @global array $relevanssi_variables The global Relevanssi variables, used to set the link boost default.
 */
function relevanssi_premium_install() {
	global $relevanssi_variables;

	add_option( 'relevanssi_api_key', '' );
	add_option( 'relevanssi_click_tracking', 'on' );
	add_option( 'relevanssi_disable_shortcodes', '' );
	add_option( 'relevanssi_do_not_call_home', 'off' );
	add_option( 'relevanssi_hide_post_controls', 'off' );
	add_option( 'relevanssi_index_pdf_parent', 'off' );
	add_option( 'relevanssi_index_post_type_archives', 'off' );
	add_option( 'relevanssi_index_subscribers', 'off' );
	add_option( 'relevanssi_index_synonyms', 'off' );
	add_option( 'relevanssi_index_taxonomies', 'off' );
	add_option( 'relevanssi_index_terms', array() );
	add_option( 'relevanssi_index_users', 'off' );
	add_option( 'relevanssi_internal_links', 'noindex' );
	add_option( 'relevanssi_link_boost', $relevanssi_variables['link_boost_default'] );
	add_option( 'relevanssi_link_pdf_files', 'on' );
	add_option( 'relevanssi_max_excerpts', 1 );
	add_option( 'relevanssi_mysql_columns', '' );
	add_option( 'relevanssi_post_type_weights', '' );
	add_option( 'relevanssi_read_new_files', 'off' );
	add_option( 'relevanssi_redirects', array() );
	add_option( 'relevanssi_related_settings', relevanssi_related_default_settings() );
	add_option( 'relevanssi_related_style', relevanssi_related_default_styles() );
	add_option( 'relevanssi_send_pdf_files', 'off' );
	add_option( 'relevanssi_server_location', relevanssi_default_server_location() );
	add_option( 'relevanssi_show_post_controls', 'off' );
	add_option( 'relevanssi_spamblock', array() );
	add_option( 'relevanssi_thousand_separator', '' );
	add_option( 'relevanssi_trim_click_logs', '180' );
	add_option( 'relevanssi_update_translations', 'off' );
	add_option(
		'relevanssi_recency_bonus',
		array(
			'bonus' => '',
			'days'  => '',
		)
	);
}

/**
 * Makes an educated guess whether the default attachment server location should
 * be US or EU, based on the site locale setting.
 *
 * @uses get_locale()
 *
 * @return string 'eu' or 'us', depending on the locale.
 */
function relevanssi_default_server_location(): string {
	$server = 'us';
	$locale = get_locale();

	if ( strpos( $locale, '_' ) === false ) {
		$language = $locale;
	} else {
		list( $language, $country ) = explode( '_', $locale );
	}

	$eu_languages = array( 'ast', 'bel', 'ca', 'cy', 'el', 'et', 'eu', 'fi', 'fur', 'gd', 'hr', 'hsb', 'lv', 'oci', 'roh', 'sq', 'uk' );
	$eu_countries = array( 'AL', 'AT', 'BA', 'BE', 'BG', 'CH', 'CY', 'DE', 'EE', 'ES', 'FR', 'GB', 'GR', 'HR', 'HU', 'IE', 'IL', 'IS', 'IT', 'LI', 'LT', 'LU', 'LV', 'MC', 'MD', 'ME', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'RS', 'SE', 'SI', 'SK', 'UA' );

	if ( in_array( strtolower( $language ), $eu_languages, true ) ||
	in_array( strtoupper( $country ), $eu_countries, true ) ) {
		$server = 'eu';
	}

	return $server;
}

/**
 * Returns the attachment reading server URL.
 *
 * Checks the correct server from 'relevanssi_server_location' option and returns the
 * correct URL from the constants.
 *
 * @return string The attachment reading server URL.
 */
function relevanssi_get_server_url() {
	$server = RELEVANSSI_US_SERVICES_URL;
	if ( 'eu' === get_option( 'relevanssi_server_location' ) ) {
		$server = RELEVANSSI_EU_SERVICES_URL;
	}
	/**
	 * Allows changing the attachment reading server URL.
	 *
	 * @param string The server URL.
	 */
	return apply_filters( 'relevanssi_attachment_server_url', $server );
}

/**
 * Extracts taxonomy specifiers from the search query.
 *
 * Finds all {taxonomy:search term} specifiers from the query. If any are
 * found, they are stored in $relevanssi_variables global variable and the
 * filtering function is activated.
 *
 * @global array $relevanssi_variables Used to store the target data.
 *
 * @param string $query The query.
 *
 * @return string The query with the specifier tags removed.
 */
function relevanssi_extract_specifier( $query ) {
	global $relevanssi_variables;

	$targets = array();

	if ( preg_match_all( '/{(.*?):(.*?)}/', $query, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			list( $whole, $target, $keyword ) = $match;

			$phrases = relevanssi_extract_phrases( $keyword );
			if ( ! empty( $phrases ) ) {
				foreach ( $phrases as $phrase ) {
					$relevanssi_variables['phrase_targets'][ $phrase ] = $target;
				}
			} else {
				if ( is_numeric( $keyword ) ) {
					$keyword = ' ' . $keyword;
				}
				$targets[ $keyword ][] = $target;
			}

			$query = str_replace( $whole, $keyword, $query );
		}
	}

	if ( ! empty( $targets ) ) {
		$relevanssi_variables['targets'] = $targets;
		add_filter( 'relevanssi_match', 'relevanssi_target_matches' );
	}

	return $query;
}

/**
 * Filters posts by taxonomy specifiers.
 *
 * If taxonomy specifiers are found in the query, this filtering function is
 * activated and will set the post weight to 0 in the cases where the post
 * matches the search term, but not the specifiers.
 *
 * @global array $relevanssi_variables Used to store the target data.
 *
 * @param object $match_object The Relevanssi match object.
 *
 * @return object The match object, with the weight modified if necessary.
 */
function relevanssi_target_matches( $match_object ) {
	global $relevanssi_variables;

	if ( is_numeric( $match_object->term ) ) {
		$match_object->term = ' ' . $match_object->term;
	}

	$fuzzy = get_option( 'relevanssi_fuzzy' );
	if ( 'always' === $fuzzy || 'sometimes' === $fuzzy ) {
		foreach ( $relevanssi_variables['targets'] as $term => $target ) {
			if (
				substr( $match_object->term, 0, strlen( $term ) ) === $term ||
				substr( strrev( $match_object->term ), 0, strlen( $term ) ) === strrev( $term )
			) {
				$relevanssi_variables['targets'][ $match_object->term ] =
					$relevanssi_variables['targets'][ $term ];
			}
		}
	}

	$no_matches = false;
	if ( isset( $relevanssi_variables['targets'][ $match_object->term ] ) ) {
		$no_matches = true;
		foreach ( $relevanssi_variables['targets'][ $match_object->term ] as $target ) {
			if ( isset( $match_object->$target ) && '0' !== $match_object->$target ) {
				$no_matches = false;
				break;
			}
			if ( $match_object->customfield_detail && ! is_object( $match_object->customfield_detail ) ) {
				$match_object->customfield_detail = json_decode( $match_object->customfield_detail );
			}
			if (
				! empty( $match_object->customfield_detail ) &&
				isset( $match_object->customfield_detail->$target ) &&
				'0' !== $match_object->customfield_detail->$target
				) {
				$no_matches = false;
				break;
			}
			if ( ! is_object( $match_object->taxonomy_detail ) ) {
				$match_object->taxonomy_detail = json_decode( $match_object->taxonomy_detail );
			}
			if (
				! empty( $match_object->taxonomy_detail ) &&
				isset( $match_object->taxonomy_detail->$target ) &&
				'0' !== $match_object->taxonomy_detail->$target
				) {
				$no_matches = false;
				break;
			}
			if ( ! is_object( $match_object->mysqlcolumn_detail ) ) {
				$match_object->mysqlcolumn_detail = json_decode( $match_object->mysqlcolumn_detail );
			}
			if (
				! empty( $match_object->mysqlcolumn_detail ) &&
				isset( $match_object->mysqlcolumn_detail->$target ) &&
				'0' !== $match_object->mysqlcolumn_detail->$target
				) {
				$no_matches = false;
				break;
			}
		}
	}
	if ( $no_matches ) {
		$match_object->weight = 0;
	}

	if ( is_object( $match_object->customfield_detail ) ) {
		$match_object->customfield_detail = wp_json_encode( $match_object->customfield_detail );
	}
	if ( is_object( $match_object->taxonomy_detail ) ) {
		$match_object->taxonomy_detail = wp_json_encode( $match_object->taxonomy_detail );
	}
	if ( is_object( $match_object->mysqlcolumn_detail ) ) {
		$match_object->mysqlcolumn_detail = wp_json_encode( $match_object->mysqlcolumn_detail );
	}

	return $match_object;
}

/**
 * Generates queries for targeted phrases.
 *
 * Goes through the targeted phrases from the Relevanssi global variable
 * $relevanssi_variables['phrase_targets'] and generates the queries for the
 * phrases taking note of the target restrictions. Some of this is slightly
 * hacky, as some default inclusions generated by the
 * relevanssi_generate_phrase_queries() are simply removed.
 *
 * @see relevanssi_generate_phrase_queries()
 *
 * @global array $relevanssi_variables The global Relevanssi variables.
 *
 * @param string $phrase The source phrase for the queries.
 *
 * @return array An array of queries per phrase.
 */
function relevanssi_targeted_phrases( $phrase ) {
	global $relevanssi_variables;

	$target = $relevanssi_variables['phrase_targets'][ $phrase ];

	$taxonomies = array();
	$excerpt    = 'off';
	$fields     = array();

	if ( 'excerpt' === $target ) {
		$excerpt = 'on';
	}
	if ( 'tag' === $target ) {
		$target = 'post_tag';
	}
	if ( taxonomy_exists( $target ) ) {
		$taxonomies = array( $target );
	} else {
		$fields = array( $target );
	}

	$queries = relevanssi_generate_phrase_queries(
		array( $phrase ),
		$taxonomies,
		$fields,
		$excerpt
	);

	if ( 'excerpt' === $target ) {
		$find                  = array(
			"post_content LIKE '%$phrase%' OR ",
			"post_title LIKE '%$phrase%' OR ",
		);
		$queries[ $phrase ][0] = str_replace( $find, '', $queries[ $phrase ][0] );
	} elseif ( 'title' === $target ) {
		$find                  = array(
			"post_content LIKE '%$phrase%' OR ",
		);
		$queries[ $phrase ][0] = str_replace( $find, '', $queries[ $phrase ][0] );
	} else {
		unset( $queries[ $phrase ][0] ); // Remove the generic post content or title query.
	}
	if ( $fields ) {
		// Custom field targeting, remove PDF content custom frield from the list.
		$queries[ $phrase ][1] = str_replace(
			",'_relevanssi_pdf_content'",
			'',
			$queries[ $phrase ][1]
		);
	}

	return $queries;
}

/**
 * Adds the Relevanssi Premium phrase filters for PDF content, terms and users.
 *
 * Hooks on to `relevanssi_phrase_queries` to include the phrase queries for
 * Relevanssi Premium features: looking for phrases in PDF content, taxonomy
 * term names and user fields.
 *
 * @param array  $queries The array of queries where the new queries are added.
 * @param string $phrase  The current phrase, already MySQL escaped.
 * @param string $status  MySQL escaped post status value to use in queries.
 *
 * @return array The queries, with new queries added.
 */
function relevanssi_premium_phrase_queries( $queries, $phrase, $status ) {
	global $wpdb;

	$index_post_types = get_option( 'relevanssi_index_post_types', array() );
	if ( in_array( 'attachment', $index_post_types, true ) ) {
		$query = "(SELECT ID
		FROM $wpdb->posts AS p, $wpdb->postmeta AS m
		WHERE p.ID = m.post_id
		AND m.meta_key = '_relevanssi_pdf_content'
		AND m.meta_value LIKE '%$phrase%'
		AND p.post_status IN ($status))";

		$queries[] = array(
			'query'  => $query,
			'target' => 'doc',
		);
	}

	if ( 'on' === get_option( 'relevanssi_index_pdf_parent' ) ) {
		$query = "(SELECT parent.ID
		FROM $wpdb->posts AS p, $wpdb->postmeta AS m, $wpdb->posts AS parent
		WHERE p.ID = m.post_id
		AND p.post_parent = parent.ID
		AND m.meta_key = '_relevanssi_pdf_content'
		AND m.meta_value LIKE '%$phrase%'
		AND p.post_status = 'inherit')";

		$queries[] = array(
			'query'  => $query,
			'target' => 'doc',
		);
	}

	$index_taxonomies = get_option( 'relevanssi_index_terms', array() );
	if ( ! empty( $index_taxonomies ) ) {
		$taxonomies_escaped = implode( "','", array_map( 'esc_sql', $index_taxonomies ) );
		$taxonomies_sql     = "AND tt.taxonomy IN ('$taxonomies_escaped')";

		$query = "(SELECT t.term_id
		FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt
		WHERE t.term_id = tt.term_id
		AND t.name LIKE '%$phrase%'
		$taxonomies_sql)";

		$queries[] = array(
			'query'  => $query,
			'target' => 'item',
		);
	}

	$index_users = get_option( 'relevanssi_index_users', 'off' );
	if ( 'on' === $index_users ) {
		$extra_fields = get_option( 'relevanssi_index_user_fields' );
		$meta_keys    = array( 'description', 'first_name', 'last_name' );
		if ( $extra_fields ) {
			$meta_keys = array_merge( $meta_keys, explode( ',', $extra_fields ) );
		}
		$meta_keys_escaped = implode( "','", array_map( 'esc_sql', $meta_keys ) );
		$meta_keys_sql     = "um.meta_key IN ('$meta_keys_escaped')";

		$query = "(SELECT DISTINCT(u.ID)
		FROM $wpdb->users AS u LEFT JOIN $wpdb->usermeta AS um
		ON u.ID = um.user_id
		WHERE ($meta_keys_sql AND meta_value LIKE '%$phrase%')
		OR u.display_name LIKE '%$phrase%')";

		$queries[] = array(
			'query'  => $query,
			'target' => 'item',
		);
	}

	return $queries;
}

/**
 * Fetches database words to the relevanssi_words option.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the
 * database table names.
 */
function relevanssi_update_words_option() {
	global $wpdb, $relevanssi_variables;

	/**
	 * The minimum limit of occurrances to include a word.
	 *
	 * To save resources, only words with more than this many occurrances are
	 * fed to the spelling corrector. If there are problems with the spelling
	 * corrector, increasing this value may fix those problems.
	 *
	 * @param int $number The number of occurrances must be more than this
	 * value, default 2.
	 */
	$count = apply_filters( 'relevanssi_get_words_having', 2 );
	if ( ! is_numeric( $count ) ) {
		$count = 2;
	}
	$q = 'SELECT term,
		SUM(title + content + comment + tag + link + author + category + excerpt + taxonomy + customfield)
		AS c FROM ' . $relevanssi_variables['relevanssi_table'] .
		" GROUP BY term HAVING c > $count"; // Safe: $count is numeric.

	$results = $wpdb->get_results( $q ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$words = array();
	foreach ( $results as $result ) {
		$words[ $result->term ] = $result->c;
	}

	$expire = time() + MONTH_IN_SECONDS;
	$data   = array(
		'expire' => $expire,
		'words'  => $words,
	);

	update_option( 'relevanssi_words', $data, false );
}

/**
 * Adds the "Must have" part for the missing terms list.
 *
 * Assumes there's just one missing term (this is checked outside this
 * function).
 *
 * @param WP_Post $post The post object.
 *
 * @return string A string containing the "Must have" link.
 */
function relevanssi_add_must_have( $post ) {
	$query_string    = $GLOBALS['wp']->query_string ?? '';
	$request         = $GLOBALS['request'] ?? '/';
	$search_term     = implode( '', $post->relevanssi_hits['missing_terms'] );
	$search_page_url = add_query_arg( $query_string, '', home_url( $request ) );
	$search_page_url = str_replace( rawurlencode( $search_term ), '%2B' . $search_term, $search_page_url );

	return apply_filters(
		'relevanssi_missing_terms_must_have',
		' | ' . __( 'Must have', 'relevanssi' ) . ': <a href="' . $search_page_url . '">' . $search_term . '</a>'
	);
}

/**
 * Updates the $term_hits array used for showing how many hits were found for
 * each term.
 *
 * @param array    $term_hits    The term hits array (passed as reference).
 * @param array    $match_arrays The matches array (passed as reference).
 * @param stdClass $match_object The match object.
 * @param string   $term         The search term.
 */
function relevanssi_premium_update_term_hits( &$term_hits, &$match_arrays, $match_object, $term ) {
	relevanssi_increase_value( $match_arrays['mysqlcolumn'][ $match_object->doc ], $match_object->mysqlcolumn );

	$match_arrays['customfield_detail'][ $match_object->doc ] = array();
	$match_arrays['taxonomy_detail'][ $match_object->doc ]    = array();
	$match_arrays['mysqlcolumn_detail'][ $match_object->doc ] = array();

	if ( ! empty( $match_object->customfield_detail ) ) {
		$match_arrays['customfield_detail'][ $match_object->doc ][ $term ] = $match_object->customfield_detail;
	}
	if ( ! empty( $match_object->taxonomy_detail ) ) {
		$match_arrays['taxonomy_detail'][ $match_object->doc ][ $term ] = $match_object->taxonomy_detail;
	}
	if ( ! empty( $match_object->mysqlcolumn_detail ) ) {
		$match_arrays['mysqlcolumn_detail'][ $match_object->doc ][ $term ] = $match_object->mysqlcolumn_detail;
	}
}

/**
 * Adds Premium features to the $return array from $match_arrays.
 *
 * @param array $return_value The search return value array, passed as a
 * reference.
 * @param array $match_arrays The match array for source data.
 */
function relevanssi_premium_update_return_array( &$return_value, $match_arrays ) {
	$match_arrays['mysqlcolumn_matches'] = $match_arrays['mysqlcolumn_matches'] ?? '';
	$match_arrays['customfield_detail']  = $match_arrays['customfield_detail'] ?? '';
	$match_arrays['taxonomy_detail']     = $match_arrays['taxonomy_detail'] ?? '';
	$match_arrays['mysqlcolumn_detail']  = $match_arrays['mysqlcolumn_detail'] ?? '';

	$additions = array(
		'mysqlcolumn'        => $match_arrays['mysqlcolumn_matches'],
		'customfield_detail' => $match_arrays['customfield_detail'],
		'taxonomy_detail'    => $match_arrays['taxonomy_detail'],
		'mysqlcolumn_detail' => $match_arrays['mysqlcolumn_detail'],
	);

	$return_value = array_merge( $return_value, $additions );
}

/**
 * Adds Premium features to the $post->relevanssi_hits source array.
 *
 * @param array $hits    The search hits array.
 * @param array $data    The source data.
 * @param int   $post_id The post ID.
 */
function relevanssi_premium_add_matches( &$hits, $data, $post_id ) {
	$hits['mysqlcolumn']        = $data['mysqlcolumn_matches'][ $post_id ] ?? 0;
	$hits['customfield_detail'] = $data['customfield_detail'][ $post_id ] ?? array();
	$hits['taxonomy_detail']    = $data['taxonomy_detail'][ $post_id ] ?? array();
	$hits['mysqlcolumn_detail'] = $data['mysqlcolumn_detail'][ $post_id ] ?? array();

	$hits['customfield_detail'] = array_map(
		function ( $value ) {
			return (array) json_decode( $value );
		},
		$hits['customfield_detail']
	);
}

/**
 * Returns a string of custom field content for the user.
 *
 * Fetches the user custom field content based on the field indexing settings
 * and concatenates it as a single space-separated string.
 *
 * @uses relevanssi_get_user_field_content
 *
 * @param string $user_id The ID of the user.
 *
 * @return string The custom field content.
 */
function relevanssi_get_user_custom_field_content( $user_id ): string {
	$custom_field_content = '';

	$fields = relevanssi_get_user_field_content( $user_id );
	if ( ! empty( $fields ) ) {
		$custom_field_content = implode( ' ', array_values( $fields ) );
	}

	return $custom_field_content;
}

/**
 * Returns an array of user custom field names.
 *
 * Gets the indexed user field names from relevanssi_index_user_fields and
 * relevanssi_index_user_meta options and returns an array of field names.
 *
 * @return array Array of user custom field names.
 */
function relevanssi_generate_list_of_user_fields(): array {
	$user_fields = array();

	$user_fields_option = get_option( 'relevanssi_index_user_fields' );
	if ( $user_fields_option ) {
		$user_fields = explode( ',', $user_fields_option );
	}

	$user_meta = get_option( 'relevanssi_index_user_meta' );
	if ( $user_meta ) {
		$user_fields = array_merge( $user_fields, explode( ',', $user_meta ) );
	}

	$user_fields = array_map( 'trim', $user_fields );

	return $user_fields;
}

/**
 * Returns an array of user custom field content.
 *
 * Gets the indexed user field content from the fields specified in the user
 * field indexing options.
 *
 * @uses relevanssi_generate_list_of_user_fields
 *
 * @param string $user_id The ID of the user.
 *
 * @return array An array of (field, value) pairs.
 */
function relevanssi_get_user_field_content( $user_id ): array {
	$fields    = relevanssi_generate_list_of_user_fields();
	$user      = get_user_by( 'id', $user_id );
	$user_vars = get_object_vars( $user );
	$values    = array();
	foreach ( $fields as $field ) {
		$field_value = '';
		if ( isset( $user_vars[ $field ] ) ) {
			$field_value = $user_vars[ $field ];
		}
		if ( empty( $field_value ) && isset( $user_vars['data']->$field ) ) {
			$field_value = $user_vars['data']->$field;
		}
		if ( empty( $field_value ) ) {
			$field_value = get_user_meta( $user_id, $field, true );
		}
		$values[ $field ] = $field_value;
	}
	return $values;
}

/**
 * Validates a source string using the filter hook.
 *
 * @param string $source The source identifier.
 *
 * @return string Validated identifier or an empty string.
 */
function relevanssi_validate_source( string $source ) : string {
	/**
	 * Filters an array to provide a list of valid source identifiers.
	 *
	 * Return an array with strings that are valid source identifiers. All other
	 * values will be ignored.
	 *
	 * @param array An empty array.
	 */
	$valid_sources = apply_filters( 'relevanssi_valid_sources', array() );

	if ( ! in_array( $source, $valid_sources, true ) ) {
		return '';
	}

	return $source;
}

/**
 * Generates the source dropdown to User Searches page.
 *
 * @param string $source The source identifier.
 *
 * @return string The HTML code for the source dropdown.
 */
function relevanssi_generate_source_select( string $source ) : string {
	global $wpdb, $relevanssi_variables;

	$sources = $wpdb->get_results(
		'SELECT DISTINCT(source) ' .
		"FROM {$relevanssi_variables['log_table']} ", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		ARRAY_A
	);

	sort( $sources );

	$source_options = '';
	foreach ( $sources as $row ) {
		if ( empty( $row['source'] ) ) {
			continue;
		}
		$selected = '';
		if ( $source === $row['source'] ) {
			$selected = 'selected="selected"';
		}
		$source_options .= "<option $selected>{$row['source']}</option>";
	}

	$select = '<p>' . __( 'Source', 'relevanssi' ) . ': <select name="source">'
		. '<option>' . __( 'All', 'relevanssi' ) . '</option>'
	    . $source_options
		. '</select></p>';

	return $select;
}
