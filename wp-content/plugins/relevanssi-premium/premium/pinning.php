<?php
/**
 * /premium/pinning.php
 *
 * Pinning feature.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_content_to_index', 'relevanssi_add_pinned_words_to_post_content', 10, 2 );
add_filter( 'relevanssi_post_title_before_tokenize', 'relevanssi_pinning_backup', 10, 2 );
add_filter( 'relevanssi_hits_filter', 'relevanssi_pinning' );

/**
 * Adds the pinned posts to searches.
 *
 * Finds the posts that are pinned to the search terms and adds them to the search
 * results if necessary. This function is triggered from the 'relevanssi_hits_filter'
 * filter hook.
 *
 * @global $wpdb      The WordPress database interface.
 * @global $wp_filter The global filter array.
 *
 * @param array $hits The hits found.
 *
 * @return array $hits The hits, with pinned posts.
 */
function relevanssi_pinning( $hits ) {
	global $wpdb, $wp_filter;

	// Is pinning used?
	$results = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE ( meta_key = '_relevanssi_pin' OR meta_key = '_relevanssi_unpin' OR meta_key = '_relevanssi_pin_for_all' ) AND meta_value != '' LIMIT 1" );
	if ( ! is_multisite() && empty( $results ) ) {
		// No, nothing is pinned.
		return $hits;
	}

	// Disable all filter functions on 'relevanssi_stemmer'.
	if ( isset( $wp_filter['relevanssi_stemmer'] ) ) {
		$callbacks                                  = $wp_filter['relevanssi_stemmer']->callbacks;
		$wp_filter['relevanssi_stemmer']->callbacks = null;
	}

	$terms = relevanssi_tokenize( $hits[1], false, -1, 'search_query' );

	// Re-enable the removed filters.
	if ( isset( $wp_filter['relevanssi_stemmer'] ) ) {
		$wp_filter['relevanssi_stemmer']->callbacks = $callbacks;
	}

	$escaped_terms = array();
	foreach ( array_keys( $terms ) as $term ) {
		$escaped_terms[] = esc_sql( trim( $term ) );
	}

	$term_list           = array();
	$count_escaped_terms = count( $escaped_terms );
	for ( $length = 1; $length <= $count_escaped_terms; $length++ ) {
		for ( $offset = 0; $offset <= $count_escaped_terms - $length; $offset++ ) {
			$slice       = array_slice( $escaped_terms, $offset, $length );
			$term_list[] = implode( ' ', $slice );
		}
	}

	$full_search_phrase = esc_sql( trim( $hits[1] ) );
	if ( ! in_array( $full_search_phrase, $term_list, true ) ) {
		$term_list[] = $full_search_phrase;
	}

	/**
	 * Doing this instead of individual get_post_meta() calls can cut hundreds
	 * of database queries!
	 */
	$posts_pinned_for_all = array_flip(
		$wpdb->get_col(
			"SELECT post_id FROM $wpdb->postmeta
			WHERE meta_key = '_relevanssi_pin_for_all'
			AND meta_value = 'on'"
		)
	);

	$pin_weights_sql = $wpdb->get_results(
		"SELECT post_id, meta_value FROM $wpdb->postmeta
		WHERE meta_key = '_relevanssi_pin_weights'"
	);

	$pin_weights = array();
	foreach ( $pin_weights_sql as $row ) {
		$pin_weights[ $row->post_id ] = $row->meta_value;
	}
	unset( $pin_weights_sql );

	/**
	 * If the search query is "foo bar baz", $term_list now contains "foo", "bar",
	 *"baz", "foo bar", "bar baz", and "foo bar baz".
	*/
	if ( is_array( $term_list ) ) {
		$term_list_array = $term_list;

		array_multisort( array_map( 'relevanssi_strlen', $term_list_array ), SORT_DESC, $term_list_array );

		$term_list = implode( "','", $term_list );
		$term_list = "'$term_list'";

		$positive_ids = array();
		$negative_ids = array();

		$pins_fetched = false;
		$pinned_posts = array();
		$other_posts  = array();
		foreach ( $hits[0] as $hit ) {
			$object_array = relevanssi_get_an_object( $hit );
			$hit          = $object_array['object'];
			$return_value = $object_array['format'];

			$blog_id = 0;
			if ( isset( $hit->blog_id ) && function_exists( 'switch_to_blog' ) ) {
				// Multisite, so switch_to_blog() to correct blog and process
				// the pinned hits per blog.
				$blog_id = $hit->blog_id;
				switch_to_blog( $blog_id );
				if ( ! isset( $pins_fetched[ $blog_id ] ) ) {
					$positive_ids[ $blog_id ] = $wpdb->get_col( 'SELECT post_id FROM ' . $wpdb->prefix . "postmeta WHERE meta_key = '_relevanssi_pin' AND meta_value IN ( $term_list )" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$negative_ids[ $blog_id ] = $wpdb->get_col( 'SELECT post_id FROM ' . $wpdb->prefix . "postmeta WHERE meta_key = '_relevanssi_unpin' AND meta_value IN ( $term_list )" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					if ( ! is_array( $pins_fetched ) ) {
						$pins_fetched = array();
					}
					$pins_fetched[ $blog_id ] = true;
				}
				restore_current_blog();
			} elseif ( ! $pins_fetched ) { // Single site.
				$positive_ids[0] = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_pin' AND meta_value IN ( $term_list )" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$negative_ids[0] = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_relevanssi_unpin' AND meta_value IN ( $term_list )" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$pins_fetched    = true;
			}
			$hit_id = strval( $hit->ID ); // The IDs from the database are strings, the one from the post is an integer in some contexts.

			$positive_match = isset( $positive_ids[ $blog_id ] )
				&& is_array( $positive_ids[ $blog_id ] )
				&& in_array( $hit_id, $positive_ids[ $blog_id ], true );
			$negative_match = isset( $negative_ids[ $blog_id ] )
				&& is_array( $negative_ids[ $blog_id ] )
				&& in_array( $hit_id, $negative_ids[ $blog_id ], true );
			$pinned_for_all = isset( $hit->ID ) && isset( $posts_pinned_for_all[ $hit->ID ] );

			$pin_weight = 0;
			$weights    = unserialize( $pin_weights[ $hit->ID ] ?? '' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
			foreach ( $term_list_array as $term ) {
				if ( isset( $weights[ $term ] ) ) {
					$pin_weight = $weights[ $term ];
					break;
				}
			}

			if ( 0 === $pin_weight ) {
				$term       = $term_list_array[0];
				$pin_weight = 1;
			}

			if ( $hit_id && $positive_match && ! $negative_match ) {
				$hit->relevanssi_pinned                 = 1;
				$pinned_posts[ $term ][ $pin_weight ][] = relevanssi_return_value( $hit, $return_value );
			} elseif ( $pinned_for_all && ! $negative_match ) {
				$hit->relevanssi_pinned = 1;
				$pinned_posts[0][0][]   = relevanssi_return_value( $hit, $return_value );
			} elseif ( ! $negative_match ) {
				$other_posts[] = relevanssi_return_value( $hit, $return_value );
			}
		}
		array_multisort( array_map( 'relevanssi_strlen', array_keys( $pinned_posts ) ), SORT_DESC, $pinned_posts );

		$all_pinned_posts = array();
		foreach ( $pinned_posts as $term => $posts_for_term ) {
			krsort( $posts_for_term, SORT_NUMERIC );
			$posts_for_term   = call_user_func_array( 'array_merge', $posts_for_term );
			$all_pinned_posts = array_merge( $all_pinned_posts, $posts_for_term );
		}

		$hits[0] = array_merge( $all_pinned_posts, $other_posts );
	}
	return $hits;
}

/**
 * Adds pinned words to post content.
 *
 * Adds pinned terms to post content to make sure posts are found with the
 * pinned terms.
 *
 * @param string $content Post content.
 * @param object $post    The post object.
 */
function relevanssi_add_pinned_words_to_post_content( $content, $post ) {
	$pin_words = get_post_meta( $post->ID, '_relevanssi_pin', false );
	foreach ( $pin_words as $word ) {
		$content .= " $word";
	}
	return $content;
}

/**
 * Adds pinned words to post title.
 *
 * If the `relevanssi_index_content` filter hook returns `false`, ie. post
 * content is not indexed, this function will add the pinned words to the post
 * title instead to guarantee they are found in the search.
 *
 * @param string $content Titlecontent.
 * @param object $post    The post object.
 */
function relevanssi_pinning_backup( $content, $post ) {
	/**
	 * Documented in /lib/indexing.php.
	 */
	if ( false === apply_filters( 'relevanssi_index_content', true, $post ) ) {
		$content = relevanssi_add_pinned_words_to_post_content( $content, $post );
	}
	return $content;
}

/**
 * Provides the pinning functionality for the admin search.
 *
 * @param object $post  The post object.
 * @param string $query The search query.
 *
 * @return array First item is a string containing the pinning buttons, the second
 *               item is a string containing the "pinned" notice if the post is
 *               pinned.
 */
function relevanssi_admin_search_pinning( $post, $query ) {
	$pinned          = '';
	$pinning_buttons = array();

	$pinned_words = array();
	if ( isset( $post->relevanssi_pinned ) ) {
		$pinned_words = get_post_meta( $post->ID, '_relevanssi_pin' );
		$pinned       = '<strong>' . __( '(pinned)', 'relevanssi' ) . '</strong>';
	}

	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		return array( '', $pinned );
	}

	$tokens = relevanssi_tokenize( $query, true, -1, 'search_query' );
	foreach ( array_keys( $tokens ) as $token ) {
		if ( ! in_array( $token, $pinned_words, true ) ) {
			/* Translators: %s is the search term. */
			$pinning_button    = sprintf( '<button type="button" class="pin" data-postid="%1$d" data-keyword="%2$s">%3$s</button>', $post->ID, $token, sprintf( __( "Pin for '%s'", 'relevanssi' ), $token ) );
			$pinning_buttons[] = $pinning_button;
		} else {
			/* Translators: %s is the search term. */
			$pinning_button    = sprintf( '<button type="button" class="unpin" data-postid="%1$d" data-keyword="%2$s">%3$s</button>', $post->ID, $token, sprintf( __( "Unpin for '%s'", 'relevanssi' ), $token ) );
			$pinning_buttons[] = $pinning_button;
		}
	}
	$pinning_buttons = implode( ' ', $pinning_buttons );

	return array( $pinning_buttons, $pinned );
}
