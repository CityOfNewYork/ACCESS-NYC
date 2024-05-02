<?php
/**
 * /premium/search-multi.php
 *
 * Multisite searching logic.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Does multisite searches.
 *
 * Handles the multisite searching when the "searchblogs" parameter is present.
 * Has slightly limited set of options compared to the single-site searches.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the
 * database table names.
 *
 * @param array $multi_args Multisite search arguments. Possible parameters:
 * 'post_type', 'search_blogs', 'operator', 'meta_query', 'orderby', 'order'.
 *
 * @return array $results Hits found and other information about the result set.
 */
function relevanssi_search_multi( $multi_args ) {
	global $wpdb;

	$hits = array();

	/**
	 * Filters the search arguments.
	 *
	 * @param array $multi_args An associative array of the search parameters.
	 */
	$filtered_args = apply_filters( 'relevanssi_search_filters', $multi_args );

	$q = $filtered_args['q'] ?? '';
	if ( empty( $q ) ) {
		// No search term, can't proceed.
		return $hits;
	}

	$search_blogs = $filtered_args['search_blogs'] ?? '';
	$operator     = $filtered_args['operator'] ?? '';
	$meta_query   = $filtered_args['meta_query'] ?? '';
	$orderby      = $filtered_args['orderby'] ?? '';
	$order        = $filtered_args['order'] ?? '';

	$total_hits = 0;

	$match_arrays = relevanssi_initialize_match_arrays();
	$term_hits    = array();
	$hitsbyweight = array();

	if ( 'all' === $search_blogs ) {
		$raw_blog_list = get_sites( array( 'number' => 2000 ) ); // There's likely flaming death with even lower values of 'number'.
		$blog_list     = array();
		foreach ( $raw_blog_list as $blog ) {
			$blog_list[] = $blog->blog_id;
		}
		$search_blogs = implode( ',', $blog_list );
	}

	$search_blogs = explode( ',', $search_blogs );
	if ( ! is_array( $search_blogs ) ) {
		// No blogs to search, so let's quit.
		return $hits;
	}

	$post_type_weights = get_option( 'relevanssi_post_type_weights' );

	foreach ( $search_blogs as $blogid ) {
		$search_again = false;

		if ( ! relevanssi_is_blog_ok( $blogid ) ) {
			continue;
		}

		// Ok, we should have a valid blog.
		switch_to_blog( $blogid );
		$relevanssi_table = $wpdb->prefix . 'relevanssi';

		$list_of_tables = $wpdb->get_col( 'SHOW TABLES' );
		if ( ! in_array( $relevanssi_table, $list_of_tables, true ) ) {
			restore_current_blog();
			continue;
		}

		$query_data         = relevanssi_process_multi_query_args( $filtered_args );
		$query_restrictions = $query_data['query_restrictions'];
		$query_join         = $query_data['query_join'];
		$q                  = $query_data['query_query'];
		$q_no_synonyms      = $query_data['query_no_synonyms'];
		$phrase_queries     = $query_data['phrase_queries'];

		if ( 'OR' === $operator ) {
			$q = relevanssi_add_synonyms( $q );
		}

		$remove_stopwords = false;
		$terms            = relevanssi_tokenize( $q, $remove_stopwords, 1, 'search_query' );

		if ( count( $terms ) < 1 ) {
			// Tokenizer killed all the search terms.
			restore_current_blog();
			continue;
		}
		$terms = array_keys( $terms ); // Don't care about tf in query.

		/**
		 * Filters the query restrictions in Relevanssi.
		 *
		 * Approximately the same purpose as the default 'posts_where' filter hook.
		 * Can be used to add additional query restrictions to the Relevanssi query.
		 *
		 * @param string $query_restrictions MySQL added to the Relevanssi query.
		 *
		 * @author Charles St-Pierre.
		 */
		$query_restrictions = apply_filters( 'relevanssi_where', $query_restrictions );

		// Go get the count from the options, but run the full query if it's not available.
		$doc_count = get_option( 'relevanssi_doc_count' );
		if ( ! $doc_count || $doc_count < 1 ) {
			$doc_count = relevanssi_update_doc_count();
		}

		$no_matches = true;
		$doc_weight = array();
		$term_hits  = array();

		do {
			$df_counts = relevanssi_generate_df_counts(
				$terms,
				array(
					'no_terms'           => false,
					'operator'           => $operator,
					'phrase_queries'     => $phrase_queries,
					'query_join'         => $query_join,
					'query_restrictions' => $query_restrictions,
					'search_again'       => $search_again,
				)
			);

			foreach ( $df_counts as $term => $df ) {
				$this_query_restrictions = relevanssi_add_phrase_restrictions(
					$query_restrictions,
					$phrase_queries,
					$term,
					$operator
				);

				$query   = relevanssi_generate_search_query( $term, $search_again, false, $query_join, $this_query_restrictions );
				$matches = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
				if ( count( $matches ) < 1 ) {
					continue;
				} else {
					$no_matches = false;
				}

				$total_hits += count( $matches );

				$idf = log( $doc_count / ( 1 + $df ) );
				foreach ( $matches as $match ) {
					$match->doc    = relevanssi_adjust_match_doc( $match );
					$match->tf     = relevanssi_calculate_tf( $match, $post_type_weights );
					$match->weight = relevanssi_calculate_weight( $match, $idf, $post_type_weights, $q );

					/**
					 * Documented in /lib/search.php.
					 */
					$match = apply_filters( 'relevanssi_match', $match, $idf, $term );

					if ( $match->weight <= 0 ) {
						continue; // The filters killed the match.
					}

					$post_ok = true;
					/**
					 * Filters whether the user is allowed to see the post.
					 *
					 * Can this post be included in the search results? This is the hook
					 * you’ll use if you want to add support for a membership plugin, for
					 * example. Based on the post ID, your function needs to return true
					 * or false.
					 *
					 * @param boolean $post_ok Can the post be shown in results?
					 * @param int     $doc     The post ID.
					 */
					$post_ok = apply_filters( 'relevanssi_post_ok', $post_ok, $match->doc );
					if ( ! $post_ok ) {
						continue;
					}

					relevanssi_update_term_hits( $term_hits, $match_arrays, $match, $term );

					$doc_id = $blogid . '|' . $match->doc;

					$doc_terms[ $match->doc ][ $term ] = true; // Count how many terms are matched to a doc.
					if ( ! isset( $doc_weight[ $doc_id ] ) ) {
						$doc_weight[ $match->doc ] = 0;
					}
					$doc_weight[ $match->doc ] += $match->weight;
				}
			}

			if ( $no_matches ) {
				if ( $search_again ) {
					// No hits even with partial matching.
					$search_again = false;
				} elseif ( 'sometimes' === get_option( 'relevanssi_fuzzy' ) ) {
					$search_again = true;
				}
			} else {
				$search_again = false;
			}
		} while ( $search_again );

		$strip_stopwords     = true;
		$terms_without_stops = array_keys( relevanssi_tokenize( implode( ' ', $terms ), $strip_stopwords, -1, 'search_query' ) );
		$total_terms         = count( $terms_without_stops );

		if ( isset( $doc_weight ) ) {
			/**
			 * Filters the results Relevanssi finds for one site in multisite
			 * search.
			 *
			 * This is similar to 'relevanssi_results' in single site searching,
			 * but only applies to results fetched from one subsite, the ID of
			 * which can be found in the filter parameters.
			 *
			 * @param array $doc_weight An array of (post ID, weight) pairs.
			 * @param int   $blogid     The blog ID.
			 */
			$doc_weight = apply_filters( 'relevanssi_site_results', $doc_weight, $blogid );
		}

		if ( isset( $doc_weight ) && count( $doc_weight ) > 0 && ! $no_matches ) {
			arsort( $doc_weight );
			$i = 0;
			foreach ( $doc_weight as $doc => $weight ) {
				if ( count( $doc_terms[ $doc ] ) < $total_terms && 'AND' === $operator ) {
					// AND operator in action: $doc didn't match all terms, so it's discarded.
					continue;
				}

				$post_object          = relevanssi_get_multisite_post( $blogid, $doc );
				$post_object->blog_id = $blogid;

				$object_id                  = $blogid . '|' . $doc;
				$hitsbyweight[ $object_id ] = $weight;
				$post_objects[ $object_id ] = $post_object;
			}
		}
		restore_current_blog();
	}

	/**
	 * Filters all results found in the multisite search.
	 *
	 * This is similar to 'relevanssi_results', but is applied to multisite
	 * searches (where 'relevanssi_results' is not used). This filter hook
	 * filters an array of ID => weight pairs, where ID is in the format
	 * '[blog ID]|[post ID]'.
	 *
	 * You can also use 'relevanssi_site_results', which is more like the
	 * original 'relevanssi_results'; it's applied to results from a single
	 * site.
	 *
	 * @param array $hitsbyweight The ID => weight pairs.
	 */
	$hitsbyweight = apply_filters( 'relevanssi_multi_results', $hitsbyweight );
	arsort( $hitsbyweight );

	$i = 0;
	foreach ( $hitsbyweight as $hit => $weight ) {
		$hit                                   = $post_objects[ $hit ];
		$hits[ intval( $i ) ]                  = $hit;
		$hits[ intval( $i ) ]->relevance_score = round( $weight, 2 );
		++$i;
	}

	if ( count( $hits ) < 1 ) {
		if ( 'AND' === $operator && 'on' !== get_option( 'relevanssi_disable_or_fallback' ) ) {
			$or_args                     = $multi_args;
			$or_args['operator']         = 'OR';
			$return                      = relevanssi_search_multi( $or_args );
			$hits                        = $return['hits'];
			$match_arrays['body']        = $return['body_matches'];
			$match_arrays['title']       = $return['title_matches'];
			$match_arrays['tag']         = $return['tag_matches'];
			$match_arrays['category']    = $return['category_matches'];
			$match_arrays['taxonomy']    = $return['taxonomy_matches'];
			$match_arrays['comment']     = $return['comment_matches'];
			$match_arrays['link']        = $return['link_matches'];
			$match_arrays['author']      = $return['author_matches'];
			$match_arrays['customfield'] = $return['customfield_matches'];
			$match_arrays['mysqlcolumn'] = $return['mysqlcolumn_matches'];
			$match_arrays['excerpt']     = $return['excerpt_matches'];
			$term_hits                   = $return['term_hits'];
			$query                       = $return['query'];
		}
	}

	relevanssi_sort_results( $hits, $orderby, $order, $meta_query );

	$return = array(
		'hits'                => $hits,
		'body_matches'        => $match_arrays['body'],
		'title_matches'       => $match_arrays['title'],
		'tag_matches'         => $match_arrays['tag'],
		'category_matches'    => $match_arrays['category'],
		'comment_matches'     => $match_arrays['comment'],
		'taxonomy_matches'    => $match_arrays['taxonomy'],
		'link_matches'        => $match_arrays['link'],
		'customfield_matches' => $match_arrays['customfield'],
		'mysqlcolumn_matches' => $match_arrays['mysqlcolumn'],
		'author_matches'      => $match_arrays['author'],
		'excerpt_matches'     => $match_arrays['excerpt'],
		'term_hits'           => $term_hits,
		'query'               => $q,
		'query_no_synonyms'   => $q_no_synonyms,
	);

	return $return;
}

/**
 * Collects the multisite search arguments from the query variables.
 *
 * @param object $query       The WP_Query object that contains the parameters.
 * @param string $searchblogs A list of blogs to search, or 'all'.
 * @param string $q           The search query.
 *
 * @return array The multisite search parameters.
 */
function relevanssi_compile_multi_args( $query, $searchblogs, $q ) {
	$multi_args = relevanssi_compile_common_args( $query );

	$multi_args['q_no_synonyms'] = $q;
	$multi_args['q']             = $q;

	if ( isset( $query->query_vars['searchblogs'] ) ) {
		$multi_args['search_blogs'] = $query->query_vars['searchblogs'];
	} else {
		$multi_args['search_blogs'] = $searchblogs;
	}

	$query->query_vars['operator'] = $multi_args['operator'];

	return $multi_args;
}

/**
 * Checks which blogs should be searched.
 *
 * @param object $query The WP Query object to check for the
 * $query->query_vars['searchblogs'] query variable.
 *
 * @return boolean|string False, if not a multisite search; list of blogs or
 * 'all' otherwise.
 */
function relevanssi_is_multisite_search( $query ) {
	$searchblogs      = false;
	$search_multisite = false;
	if ( isset( $query->query_vars['searchblogs'] )
		&& (string) get_current_blog_id() !== $query->query_vars['searchblogs'] ) {
		$search_multisite = true;
		$searchblogs      = $query->query_vars['searchblogs'];
	}

	if ( ! isset( $query->query_vars['searchblogs'] ) && ! $search_multisite ) {
		// Is searching all blogs enabled?
		$searchblogs_all = get_option( 'relevanssi_searchblogs_all', 'off' );
		if ( 'off' === $searchblogs_all ) {
			$searchblogs_all = false;
		}
		if ( $searchblogs_all ) {
			$search_multisite = true;
			$searchblogs      = 'all';
		}
	}

	if ( ! isset( $query->query_vars['searchblogs'] ) && ! $search_multisite ) {
		// Searchblogs is not set from the query variables, check the option.
		$searchblogs_setting = get_option( 'relevanssi_searchblogs' );
		if ( $searchblogs_setting ) {
			$search_multisite = true;
			$searchblogs      = $searchblogs_setting;
		}
	}
	return $searchblogs;
}

/**
 * Checks to see if a blog is good to use.
 *
 * Blog must exist, it has to be public and not archived, spam or deleted. The
 * filter hook `relevanssi_multisite_public_status` can be used to allow
 * Relevanssi to search non-public blogs.
 *
 * @param int $blogid The blog ID.
 *
 * @return bool True, if blog is public.
 */
function relevanssi_is_blog_ok( $blogid ): bool {
	// Only search blogs that are publicly available (unless filter says otherwise).
	$public_status = (bool) get_blog_status( $blogid, 'public' );
	if ( null === $public_status ) {
		// Blog doesn't actually exist.
		return false;
	}

	/**
	 * Adjusts the possible values of blog public status.
	 *
	 * By default Relevanssi requires blogs to be public so they can be searched.
	 * If you want a non-public blog in the search results, make this filter
	 * return true.
	 *
	 * @param boolean $public_status Is the blog public?
	 * @param int     $blogid        Blog ID.
	 */
	if ( false === apply_filters( 'relevanssi_multisite_public_status', $public_status, $blogid ) ) {
		return false;
	}

	// Don't search blogs that are marked "archived", "spam" or "deleted".
	if ( get_blog_status( $blogid, 'archived' ) ) {
		return false;
	}
	if ( get_blog_status( $blogid, 'spam' ) ) {
		return false;
	}
	if ( get_blog_status( $blogid, 'delete' ) ) {
		return false;
	}
	return true;
}

/**
 * Processes the arguments to create the query restrictions for multisite
 * searches.
 *
 * All individual parts are tested.
 *
 * @param array $args The query arguments.
 *
 * @return array An array containing `query_restriction` and `query_join`.
 */
function relevanssi_process_multi_query_args( $args ) {
	$query_restrictions = '';
	$query_join         = '';
	$query              = '';
	$query_no_synonyms  = '';

	$phrase_query_restrictions = array(
		'and' => '',
		'or'  => array(),
	);

	if ( function_exists( 'wp_encode_emoji' ) ) {
		$query             = wp_encode_emoji( $args['q'] );
		$query_no_synonyms = wp_encode_emoji( $args['q_no_synonyms'] );
	}

	if ( $args['sentence'] ) {
		$query = relevanssi_remove_quotes( $query );
		$query = '"' . $query . '"';
	}

	if ( is_array( $args['meta_query'] ) ) {
		$processed_meta      = relevanssi_process_meta_query( $args['meta_query'] );
		$query_restrictions .= $processed_meta['where'];
		$query_join         .= $processed_meta['join'];
	}

	if ( $args['date_query'] instanceof WP_Date_Query ) {
		$query_restrictions .= relevanssi_process_date_query( $args['date_query'] );
	}

	if ( $args['by_date'] ) {
		$query_restrictions .= relevanssi_process_by_date( $args['by_date'] );
	}

	$phrases = relevanssi_recognize_phrases( $query, $args['operator'] );
	if ( $phrases ) {
		$phrase_query_restrictions = $phrases;
	}

	$query_restrictions .= relevanssi_process_post_type(
		$args['post_type'],
		$args['admin_search'],
		$args['include_attachments']
	);

	if ( $args['post_status'] ) {
		$query_restrictions .= relevanssi_process_post_status( $args['post_status'] );
	}

	return array(
		'query_restrictions' => $query_restrictions,
		'query_join'         => $query_join,
		'query_query'        => $query,
		'query_no_synonyms'  => $query_no_synonyms,
		'phrase_queries'     => $phrase_query_restrictions,
	);
}
