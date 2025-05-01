<?php
/**
 * /premium/indexing.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Indexes user profiles when profile updates.
 *
 * @param object|int $user User object or user ID.
 */
function relevanssi_profile_update( $user ) {
	if ( 'on' === get_option( 'relevanssi_index_users' ) ) {
		if ( is_integer( $user ) ) {
			$user = get_user_by( 'id', $user );
		}
		/**
		 * Checks if the user can be indexed.
		 *
		 * @param boolean $index Should the user be indexed, default true.
		 * @param object  $user  The user object.
		 *
		 * @return boolean $index If false, do not index the user.
		 */
		$index_this_user = apply_filters( 'relevanssi_user_index_ok', true, $user );
		if ( $index_this_user ) {
			$update = true;
			relevanssi_index_user( $user, $update );
		} else {
			relevanssi_delete_user( $user->ID );
		}
	}
}

/**
 * Indexes taxonomy terms when term is updated.
 *
 * @param string $term             The term.
 * @param int    $taxonomy_term_id The term taxonomy ID (not used here).
 * @param string $taxonomy         The taxonomy.
 */
function relevanssi_edit_term( $term, $taxonomy_term_id, $taxonomy ) {
	$update = true;
	relevanssi_do_term_indexing( $term, $taxonomy, $update );
}

/**
 * Indexes taxonomy terms when term is added.
 *
 * @param string $term             The term.
 * @param int    $taxonomy_term_id The term taxonomy ID (not used here).
 * @param string $taxonomy         The taxonomy.
 */
function relevanssi_add_term( $term, $taxonomy_term_id, $taxonomy ) {
	$update = false;
	relevanssi_do_term_indexing( $term, $taxonomy, $update );
}

/**
 * Indexes taxonomy term, if taxonomy term indexing is enabled.
 *
 * @param string  $term     The term.
 * @param string  $taxonomy The taxonomy.
 * @param boolean $update   If true, term is updated; if false, it is added.
 */
function relevanssi_do_term_indexing( $term, $taxonomy, $update ) {
	if ( 'on' === get_option( 'relevanssi_index_taxonomies' ) ) {
		$taxonomies = get_option( 'relevanssi_index_terms' );
		if ( in_array( $taxonomy, $taxonomies, true ) ) {
			relevanssi_index_taxonomy_term( $term, $taxonomy, $update );
		}
	}
}

/**
 * Deletes an user from Relevanssi index.
 *
 * Deletes an user from the Relevanssi index. Attached to the 'delete_user' action.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param int $user User ID to delete.
 */
function relevanssi_delete_user( int $user ) {
	global $wpdb, $relevanssi_variables;
	$user = intval( $user );
	$wpdb->query( 'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE item = $user AND type = 'user'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Deletes a taxonomy term from Relevanssi index.
 *
 * Deletes a taxonomy term from the Relevanssi index. Attached to the 'delete_term' action.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param int    $term             Term ID to remove.
 * @param int    $term_taxonomy_id Term taxonomy ID (not used).
 * @param string $taxonomy         The taxonomy.
 */
function relevanssi_delete_taxonomy_term( $term, $term_taxonomy_id, $taxonomy ) {
	global $wpdb, $relevanssi_variables;
	$wpdb->query(
		$wpdb->prepare(
			'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE item = %d AND type = %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$term,
			$taxonomy
		)
	);
}

/**
 * Generates the custom field detail field for indexing.
 *
 * Premium stores more detail about custom field indexing. This function
 * generates the custom field detail.
 *
 * @param array  $insert_data Data used to generate the INSERT queries.
 * @param string $token       The indexed token.
 * @param int    $count       The number of matches.
 * @param string $field       Name of the custom field.
 *
 * @return array $insert_data New source data for the INSERT queries added.
 */
function relevanssi_customfield_detail( $insert_data, $token, $count, $field ) {
	if ( isset( $insert_data[ $token ]['customfield_detail'] ) ) {
		// Custom field detail for this token already exists.
		$custom_field_detail = json_decode( $insert_data[ $token ]['customfield_detail'], true );
	} else {
		// Nothing yet, create new.
		$custom_field_detail = array();
	}

	relevanssi_increase_value( $custom_field_detail[ $field ], $count );

	$insert_data[ $token ]['customfield_detail'] = wp_json_encode( $custom_field_detail );
	return $insert_data;
}

/**
 * Indexes custom MySQL column content.
 *
 * Generates the INSERT query base data for MySQL column content.
 *
 * @global $wpdb The WordPress database interface.
 *
 * @param array  $insert_data Data used to generate the INSERT queries.
 * @param string $post_id     Post ID.
 *
 * @return array $insert_data New source data for the INSERT queries added.
 */
function relevanssi_index_mysql_columns( $insert_data, $post_id ) {
	$custom_columns = get_option( 'relevanssi_mysql_columns' );
	if ( ! empty( $custom_columns ) ) {
		global $wpdb;

		// Get a list of possible column names.
		$column_list = wp_cache_get( 'relevanssi_column_list' );
		if ( false === $column_list ) {
			$column_list = $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->posts" );
			wp_cache_set( 'relevanssi_column_list', $column_list );
		}
		$valid_columns = array();
		foreach ( $column_list as $column ) {
			array_push( $valid_columns, $column->Field ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		// This is to remove problems where the list ends in a comma.
		$custom_column_array      = explode( ',', $custom_columns );
		$custom_column_list_array = array();
		foreach ( $custom_column_array as $column ) {
			$column = trim( $column );
			if ( in_array( $column, $valid_columns, true ) ) {
				$custom_column_list_array[] = $column;
			}
		}
		$custom_column_list = implode( ', ', $custom_column_list_array );

		$custom_column_data  = $wpdb->get_row( "SELECT $custom_column_list FROM $wpdb->posts WHERE ID=$post_id", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$remove_stopwords    = true;
		$minimum_word_length = get_option( 'relevanssi_min_word_length', 3 );
		if ( is_array( $custom_column_data ) ) {
			foreach ( $custom_column_data as $column => $data ) {
				/** This filter is documented in common/indexing.php */
				$data = apply_filters(
					'relevanssi_indexing_tokens',
					relevanssi_tokenize( $data, $remove_stopwords, $minimum_word_length, 'indexing' ),
					'mysql-content'
				);
				if ( count( $data ) > 0 ) {
					foreach ( $data as $term => $count ) {
						if ( isset( $insert_data[ $term ]['mysqlcolumn'] ) ) {
							$insert_data[ $term ]['mysqlcolumn'] += $count;
						} else {
							$insert_data[ $term ]['mysqlcolumn'] = $count;
						}
						$insert_data = relevanssi_mysqlcolumn_detail( $insert_data, $term, $count, $column );
					}
				}
			}
		}
	}
	return $insert_data;
}

/**
 * Generates the MySQL column detail field for indexing.
 *
 * This function generates the MySQL column detail.
 *
 * @param array  $insert_data Data used to generate the INSERT queries.
 * @param string $token       The indexed token.
 * @param int    $count       The number of matches.
 * @param string $column      Name of the column.
 *
 * @return array $insert_data New source data for the INSERT queries added.
 */
function relevanssi_mysqlcolumn_detail( $insert_data, $token, $count, $column ) {
	if ( isset( $insert_data[ $token ]['mysqlcolumn_detail'] ) ) {
		// Custom field detail for this token already exists.
		$mysqlcolumn_detail = json_decode( $insert_data[ $token ]['mysqlcolumn_detail'], true );
	} else {
		// Nothing yet, create new.
		$mysqlcolumn_detail = array();
	}

	relevanssi_increase_value( $mysqlcolumn_detail[ $column ], $count );

	$insert_data[ $token ]['mysqlcolumn_detail'] = wp_json_encode( $mysqlcolumn_detail );

	return $insert_data;
}

/**
 * Processes internal links.
 *
 * Process the internal links the way user wants: no indexing, indexing, or stripping.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param string $contents Post content.
 * @param int    $post_id Post ID.
 *
 * @return string $contents Contents, modified.
 */
function relevanssi_process_internal_links( $contents, $post_id ) {
	$internal_links_behaviour = get_option( 'relevanssi_internal_links', 'noindex' );

	if ( 'noindex' !== $internal_links_behaviour ) {
		global $relevanssi_variables, $wpdb;
		$min_word_length = get_option( 'relevanssi_min_word_length', 3 );

		// Index internal links.
		$internal_links = relevanssi_get_internal_links( $contents );

		if ( ! empty( $internal_links ) ) {
			foreach ( $internal_links as $link => $text ) {
				$link_id = url_to_postid( $link );
				if ( ! empty( $link_id ) ) {
					/** This filter is documented in common/indexing.php */
					$link_words = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $text, true, $min_word_length, 'indexing' ), 'internal-links' );
					if ( count( $link_words ) > 0 ) {
						foreach ( $link_words as $word => $count ) {
							$wpdb->query(
								$wpdb->prepare(
									'INSERT IGNORE INTO ' . $relevanssi_variables['relevanssi_table'] . ' (doc, term, term_reverse, link, item) VALUES (%d, %s, REVERSE(%s), %d, %d)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
									$link_id,
									$word,
									$word,
									$count,
									$post_id
								)
							);
						}
					}
				}
			}

			if ( 'strip' === $internal_links_behaviour ) {
				$contents = relevanssi_strip_internal_links( $contents );
			}
		}
	}

	return $contents;
}

/**
 * Finds internal links.
 *
 * A function to find all internal links in the parameter text.
 *
 * @param string $text Text where the links are extracted from.
 *
 * @return array $links All links in the post, or false if fails.
 */
function relevanssi_get_internal_links( $text ) {
	$links = array();
	if ( preg_match_all( '@<a[^>]*?href="(' . home_url() . '[^"]*?)"[^>]*?>(.*?)</a>@siu', $text, $m ) ) {
		foreach ( $m[1] as $i => $link ) {
			if ( ! isset( $links[ $link ] ) ) {
				$links[ $link ] = '';
			}
			$links[ $link ] .= ' ' . $m[2][ $i ];
		}
	}
	if ( preg_match_all( '@<a[^>]*?href="(/[^"]*?)"[^>]*?>(.*?)</a>@siu', $text, $m ) ) {
		foreach ( $m[1] as $i => $link ) {
			if ( ! isset( $links[ $link ] ) ) {
				$links[ $link ] = '';
			}
			$links[ $link ] .= ' ' . $m[2][ $i ];
		}
	}
	if ( count( $links ) > 0 ) {
		return $links;
	}
	return false;
}

/**
 * Strips internal links.
 *
 * A function to strip all internal links from the parameter text.
 *
 * @param string $text Text where the links are extracted from.
 *
 * @return array $links The text without the links.
 */
function relevanssi_strip_internal_links( $text ) {
	$text = preg_replace(
		array(
			'@<a[^>]*?href="' . home_url() . '[^>]*?>.*?</a>@siu',
		),
		' ',
		$text
	);
	$text = preg_replace(
		array(
			'@<a[^>]*?href="/[^>]*?>.*?</a>@siu',
		),
		' ',
		$text
	);
	return $text;
}

/**
 * Applies the thousands separator rule to text.
 *
 * Finds numbers separated by the chosen thousand separator and combine them.
 *
 * @param string $str The string to fix.
 *
 * @return string $str The fixed string.
 */
function relevanssi_apply_thousands_separator( $str ) {
	$thousands_separator = get_option( 'relevanssi_thousand_separator', '' );
	if ( ! empty( $thousands_separator ) ) {
		$pattern = '/(\d+)' . preg_quote( $thousands_separator, '/' ) . '(\d+)/u';
		$str     = preg_replace( $pattern, '$1$2', $str );
	}
	return $str;
}

/**
 * Adds a stemmer-enabling filter.
 *
 * This filter introduces a new filter hook that runs the stemmers.
 *
 * @param string $str The string that is stemmed.
 *
 * @return string $str The string after stemming.
 */
function relevanssi_enable_stemmer( $str ) {
	add_filter( 'pre_option_relevanssi_implicit_operator', 'relevanssi_return_or' );
	/**
	 * Applies stemmer to document content and search terms.
	 *
	 * @param string $str The string that is stemmed.
	 *
	 * @return string $str The string after stemming.
	 */
	$str = apply_filters( 'relevanssi_stemmer', $str );
	remove_filter( 'pre_option_relevanssi_implicit_operator', 'relevanssi_return_or' );
	return $str;
}

/**
 * Does simple English stemming.
 *
 * A simple suffix stripper that can be used to stem English texts.
 *
 * @param string $term Search term to stem.
 *
 * @return string $term The stemmed term.
 */
function relevanssi_simple_english_stemmer( $term ) {
	$len = strlen( $term );

	$end1 = substr( $term, -1, 1 );
	if ( 's' === $end1 && $len > 3 ) {
		$term = substr( $term, 0, -1 );
		--$len;
	}
	$end = substr( $term, -3, 3 );

	if ( 'ing' === $end && $len > 5 ) {
		return substr( $term, 0, -3 );
	}
	if ( 'est' === $end && $len > 5 ) {
		return substr( $term, 0, -3 );
	}

	$end = substr( $end, 1 );
	if ( 'es' === $end && $len > 3 ) {
		return substr( $term, 0, -2 );
	}
	if ( 'ie' === $end && $len > 3 ) {
		return substr( $term, 0, -1 );
	}
	if ( 'ed' === $end && $len > 3 ) {
		return substr( $term, 0, -2 );
	}
	if ( 'en' === $end && $len > 3 ) {
		return substr( $term, 0, -2 );
	}
	if ( 'er' === $end && $len > 3 ) {
		return substr( $term, 0, -2 );
	}
	if ( 'ly' === $end && $len > 4 ) {
		return substr( $term, 0, -2 );
	}

	$end = substr( $end, -1 );
	if ( 'y' === $end && $len > 3 ) {
		return substr( $term, 0, -1 ) . 'i';
	}

	return $term;
}

/**
 * Creates the synonym replacement array.
 *
 * A helper function that generates a synonym replacement array. The array
 * is then stored in a global variable, so that it only needs to generated
 * once per running the script.
 *
 * @global $relevanssi_variables The global Relevanssi variables, used to
 * store the synonym database.
 */
function relevanssi_create_synonym_replacement_array() {
	global $relevanssi_variables;

	$synonym_data     = get_option( 'relevanssi_synonyms' );
	$current_language = relevanssi_get_current_language();
	$synonyms         = array();

	if ( isset( $synonym_data[ $current_language ] ) ) {
		$synonym_data = relevanssi_strtolower( $synonym_data[ $current_language ] );
		$pairs        = explode( ';', $synonym_data );

		foreach ( $pairs as $pair ) {
			if ( empty( $pair ) ) {
				continue;
			}
			$parts = explode( '=', $pair );
			$key   = strval( trim( $parts[0] ) );
			$value = trim( $parts[1] );
			if ( ! isset( $synonyms[ $value ] ) ) {
				$synonyms[ $value ] = "$value $key";
			} else {
				$synonyms[ $value ] .= " $key";
			}
		}
	}
	$relevanssi_variables['synonyms'] = $synonyms;
}

/**
 * Adds synonyms to post content and titles for indexing.
 *
 * In order to use synonyms in AND searches, the synonyms must be indexed within the posts.
 * This function adds synonyms for post content and titles when indexing posts.
 *
 * @global $relevanssi_variables The global Relevanssi variables, used for the synonym database.
 *
 * @param array $tokens An array of tokens and their frequencies.
 *
 * @return array An array of filtered token-frequency pairs.
 */
function relevanssi_add_indexing_synonyms( $tokens ) {
	global $relevanssi_variables;

	if ( ! isset( $relevanssi_variables['synonyms'] ) ) {
		relevanssi_create_synonym_replacement_array();
	}

	$new_tokens = array();
	$synonyms   = $relevanssi_variables['synonyms'];

	foreach ( $tokens as $token => $tf ) {
		if ( isset( $synonyms[ $token ] ) ) {
			$token_and_the_synonyms = explode( ' ', $synonyms[ $token ] );
			foreach ( $token_and_the_synonyms as $new_token ) {
				$new_tokens[ $new_token ] = $tf;
			}
		} else {
			$new_tokens[ $token ] = $tf;
		}
	}

	return $new_tokens;
}

/**
 * Adds synonyms to a content.
 *
 * @global $relevanssi_variables The global Relevanssi variables, used for the synonym database.
 *
 * @param string $content The content to add synonyms to.
 *
 * @return string $content The content with synonyms.
 */
function relevanssi_prepare_indexing_content( $content ) {
	global $relevanssi_variables;

	if ( ! isset( $relevanssi_variables['synonyms'] ) ) {
		relevanssi_create_synonym_replacement_array();
	}

	$synonyms = $relevanssi_variables['synonyms'];
	$content  = relevanssi_strtolower( $content );
	$content  = preg_split( '/[\s,.()!?]/', $content );
	$ret      = array();
	$len      = count( $content );
	for ( $i = 0; $i < $len; ++$i ) {
		$val = $content[ $i ];
		if ( 0 === strlen( $val ) ) {
			continue;
		}

		if ( isset( $synonyms[ $val ] ) ) {
			$ret[] = $synonyms[ $val ];
		} else {
			$ret[] = $val;
		}
	}

	return implode( ' ', $ret );
}


/**
 * Adds ACF repeater fields to the list of custom fields.
 *
 * Goes through custom fields, finds fields that match the fieldname_%_subfieldname
 * pattern, finds the number of fields from the fieldname custom field and then
 * adds the fieldname_0_subfieldname... fields to the list of custom fields. Only
 * works one level deep.
 *
 * @param array $custom_fields The list of custom fields, used as a reference.
 * @param int   $post_id       The post ID of the current post.
 */
function relevanssi_add_repeater_fields( &$custom_fields, $post_id ) {
	global $wpdb;

	/**
	 * Filters the list of custom fields to index before the repeater fields
	 * are expanded. If you want to add repeater fields using the
	 * field_%_subfield notation from code, you can use this filter hook.
	 *
	 * @param array $custom_fields The list of custom fields. This array
	 * includes all custom fields that are to be indexed, so make sure you add
	 * new fields here and don't remove anything you want included in the index.
	 */
	$custom_fields   = apply_filters( 'relevanssi_custom_fields_before_repeaters', $custom_fields );
	$repeater_fields = array();
	foreach ( $custom_fields as $field ) {
		$number_of_levels = substr_count( $field, '%' );
		if ( $number_of_levels > 0 ) {
			$field  = str_replace( '\%', '%', $wpdb->esc_like( $field ) );
			$fields = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM $wpdb->postmeta WHERE meta_key LIKE %s AND post_id = %d", $field, $post_id ) );

			$repeater_fields = array_merge( $repeater_fields, $fields );
		} else {
			continue;
		}
	}

	$custom_fields = array_merge( $custom_fields, $repeater_fields );
}

/**
 * Adds the PDF data from child posts to parent posts.
 *
 * Takes the PDF content data from child posts for indexing purposes.
 *
 * @global $wpdb The WordPress database interface.
 *
 * @param array $insert_data The base data for INSERT queries.
 * @param int   $post_id     The post ID.
 *
 * @return array $insert_data The INSERT data with new content added.
 */
function relevanssi_index_pdf_for_parent( $insert_data, $post_id ) {
	$option = get_option( 'relevanssi_index_pdf_parent', '' );
	if ( empty( $option ) || 'off' === $option ) {
		return $insert_data;
	}

	global $wpdb;

	$post_id = intval( $post_id );
	$query   = "SELECT meta_value FROM $wpdb->postmeta AS pm, $wpdb->posts AS p WHERE pm.post_id = p.ID AND p.post_parent = $post_id AND meta_key = '_relevanssi_pdf_content'";
	/**
	 * Filters the database query that fetches the PDF content for the parent post.
	 *
	 * @param string $query   The MySQL query.
	 * @param int    $post_id The parent post ID.
	 */
	$query       = apply_filters( 'relevanssi_pdf_for_parent_query', $query, $post_id );
	$pdf_content = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( is_array( $pdf_content ) ) {
		/**
		 * Filters the custom field value before indexing.
		 *
		 * @param array            Custom field values.
		 * @param string $field    The custom field name.
		 * @param int    $post_id The post ID.
		 */
		$pdf_content = apply_filters( 'relevanssi_custom_field_value', $pdf_content, '_relevanssi_pdf_content', $post_id );
		foreach ( $pdf_content as $row ) {
			/** This filter is documented in common/indexing.php */
			$data = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $row, true, get_option( 'relevanssi_min_word_length', 3 ), 'indexing' ), 'pdf-content' );
			if ( count( $data ) > 0 ) {
				foreach ( $data as $term => $count ) {
					if ( isset( $insert_data[ $term ]['customfield'] ) ) {
						$insert_data[ $term ]['customfield'] += $count;
					} else {
						$insert_data[ $term ]['customfield'] = $count;
					}
					$insert_data = relevanssi_customfield_detail( $insert_data, $term, $count, '_relevanssi_pdf_content' );
				}
			}
		}
	}

	/**
	 * Filters the index data for the PDF contents.
	 *
	 * @param array $insert_data The data for INSERT clauses, format is
	 * $insert_data[ term ][ column ] = frequency.
	 * @param int   $post_id     The parent post ID.
	 */
	return apply_filters( 'relevanssi_pdf_for_parent_insert_data', $insert_data, $post_id );
}

/**
 * Indexes all users.
 *
 * Runs indexing on all users.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 */
function relevanssi_index_users() {
	global $wpdb, $relevanssi_variables;

	// Delete all users from the Relevanssi index first.
	$wpdb->query( 'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE type = 'user'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$users = relevanssi_get_users( array() );

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		$progress = WP_CLI\Utils\make_progress_bar( 'Indexing users', count( $users ) );
	}

	$update = false;
	foreach ( $users as $user ) {
		/**
		 * Checks if the user can be indexed.
		 *
		 * @param boolean $index Should the user be indexed, default true.
		 * @param object  $user  The user object.
		 *
		 * @return boolean $index If false, do not index the user.
		 */
		$index_this_user = apply_filters( 'relevanssi_user_index_ok', true, $user );

		if ( $index_this_user ) {
			relevanssi_index_user( $user, $update );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$progress->tick();
		}
	}
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		$progress->finish();
	}
}

/**
 * Indexes users in AJAX context.
 *
 * Runs indexing on all users in AJAX context.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param int $limit  Number of users to index on one go.
 * @param int $offset Indexing offset.
 *
 * @return array $response AJAX response, number of users indexed in the $response['indexed'].
 */
function relevanssi_index_users_ajax( $limit, $offset ) {
	$args = array(
		'number' => intval( $limit ),
		'offset' => intval( $offset ),
	);

	$users = relevanssi_get_users( $args );

	$indexed_users = 0;
	$update        = false;
	foreach ( $users as $user ) {
		/**
		 * Checks if the user can be indexed.
		 *
		 * @param boolean $index Should the user be indexed, default true.
		 * @param object  $user  The user object.
		 *
		 * @return boolean $index If false, do not index the user.
		 */
		$index_this_user = apply_filters( 'relevanssi_user_index_ok', true, $user );
		if ( $index_this_user ) {
			relevanssi_index_user( $user, $update );
			++$indexed_users;
		}
	}

	$response = array(
		'indexed' => $indexed_users,
	);

	return $response;
}

/**
 * Gets the list of users.
 *
 * @param array $args The user indexing arguments.
 *
 * @return array An array of user profiles.
 */
function relevanssi_get_users( array $args ) {
	$index_subscribers = get_option( 'relevanssi_index_subscribers' );
	if ( 'on' !== $index_subscribers ) {
		$args['role__not_in'] = array( 'subscriber' );
	}

	/**
	 * Filters the user fetching arguments.
	 *
	 * Useful to control the user role, for example: just set 'role__in' to whatever
	 * you need.
	 *
	 * @param array User fetching arguments.
	 */
	$users_list = get_users( apply_filters( 'relevanssi_user_indexing_args', $args ) );
	$users      = array();
	foreach ( $users_list as $user ) {
		$users[] = get_userdata( $user->ID );
	}

	return $users;
}

/**
 * Indexes one user.
 *
 * Indexes one user profile.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param object|int $user         The user object or user ID.
 * @param boolean    $remove_first Should the user be deleted first or not, default false.
 */
function relevanssi_index_user( $user, $remove_first = false ) {
	global $wpdb, $relevanssi_variables;

	if ( is_numeric( $user ) ) {
		// Not an object, make it an object.
		$user = get_userdata( $user );
		if ( false === $user ) {
			// Invalid user ID given, no user found. Exit.
			return;
		}
	}

	if ( $remove_first ) {
		relevanssi_delete_user( $user->ID );
	}

	/**
	 * Allows manipulating the user object before indexing.
	 *
	 * This filter can be used to manipulate the user object before it is
	 * processed for indexing. It's possible to add extra data (for example to
	 * user description field) or to change the existing data.
	 *
	 * @param object $user The user object.
	 */
	$user = apply_filters( 'relevanssi_user_add_data', $user );

	$insert_data      = array();
	$min_length       = get_option( 'relevanssi_min_word_length', 3 );
	$remove_stopwords = true;

	$values = relevanssi_get_user_field_content( $user->ID );
	foreach ( $values as $field => $value ) {
		/** This filter is documented in common/indexing.php */
		$tokens = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $value, $remove_stopwords, $min_length, 'indexing' ), 'user-fields' );
		foreach ( $tokens as $term => $tf ) {
			if ( isset( $insert_data[ $term ]['customfield'] ) ) {
				$insert_data[ $term ]['customfield'] += $tf;
			} else {
				$insert_data[ $term ]['customfield'] = $tf;
			}
			$insert_data = relevanssi_customfield_detail( $insert_data, $term, $tf, $field );
		}
	}

	if ( isset( $user->description ) && '' !== $user->description ) {
		/** This filter is documented in common/indexing.php */
		$tokens = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $user->description, $remove_stopwords, $min_length, 'indexing' ), 'user-description' );
		foreach ( $tokens as $term => $tf ) {
			if ( isset( $insert_data[ $term ]['content'] ) ) {
				$insert_data[ $term ]['content'] += $tf;
			} else {
				$insert_data[ $term ]['content'] = $tf;
			}
		}
	}

	if ( isset( $user->first_name ) && '' !== $user->first_name ) {
		$parts = explode( ' ', strtolower( $user->first_name ) );
		foreach ( $parts as $part ) {
			if ( empty( $part ) ) {
				continue;
			}
			if ( isset( $insert_data[ $part ]['title'] ) ) {
				++$insert_data[ $part ]['title'];
			} else {
				$insert_data[ $part ]['title'] = 1;
			}
		}
	}

	if ( isset( $user->last_name ) && ' ' !== $user->last_name ) {
		$parts = explode( ' ', strtolower( $user->last_name ) );
		foreach ( $parts as $part ) {
			if ( empty( $part ) ) {
				continue;
			}
			if ( isset( $insert_data[ $part ]['title'] ) ) {
				++$insert_data[ $part ]['title'];
			} else {
				$insert_data[ $part ]['title'] = 1;
			}
		}
	}

	if ( isset( $user->display_name ) && ' ' !== $user->display_name ) {
		$parts = explode( ' ', strtolower( $user->display_name ) );
		foreach ( $parts as $part ) {
			if ( empty( $part ) ) {
				continue;
			}
			if ( isset( $insert_data[ $part ]['title'] ) ) {
				++$insert_data[ $part ]['title'];
			} else {
				$insert_data[ $part ]['title'] = 1;
			}
		}
	}

	/**
	 * Allows the user insert data to be manipulated.
	 *
	 * This function manipulates the user insert data used to create the INSERT queries.
	 *
	 * @param array  $insert_data The source data for the INSERT queries.
	 * @param object $user        The user object.
	 */
	$insert_data = apply_filters( 'relevanssi_user_data_to_index', $insert_data, $user );

	foreach ( $insert_data as $term => $data ) {
		$fields = array( 'content', 'title', 'comment', 'tag', 'link', 'author', 'category', 'excerpt', 'taxonomy', 'customfield', 'customfield_detail' );
		foreach ( $fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				$data[ $field ] = 0;
			}
		}

		$content     = $data['content'];
		$title       = $data['title'];
		$comment     = $data['comment'];
		$tag         = $data['tag'];
		$link        = $data['link'];
		$author      = $data['author'];
		$category    = $data['category'];
		$excerpt     = $data['excerpt'];
		$taxonomy    = $data['taxonomy'];
		$customfield = $data['customfield'];
		$cf_detail   = $data['customfield_detail'];

		$wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO ' . $relevanssi_variables['relevanssi_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				' (item, doc, term, term_reverse, content, title, comment, tag, link, author, category, excerpt, taxonomy, customfield, type, customfield_detail, taxonomy_detail, mysqlcolumn_detail)
			VALUES (%d, %d, %s, REVERSE(%s), %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %s)',
				$user->ID,
				-1,
				$term,
				$term,
				$content,
				$title,
				$comment,
				$tag,
				$link,
				$author,
				$category,
				$excerpt,
				$taxonomy,
				$customfield,
				'user',
				$cf_detail,
				'',
				''
			)
		);
	}
}

/**
 * Counts users.
 *
 * Figures out how many users there are to index.
 *
 * @global $wpdb The WordPress database interface.
 *
 * @return int $count_users Number of users, -1 if user indexing is disabled.
 */
function relevanssi_count_users() {
	$index_users = get_option( 'relevanssi_index_users' );
	if ( empty( $index_users ) || 'off' === $index_users ) {
		return -1;
	}

	$args = array(
		'fields' => 'ID',
	);

	$index_subscribers = get_option( 'relevanssi_index_subscribers' );
	if ( 'on' !== $index_subscribers ) {
		$args['role__not_in'] = array( 'subscriber' );
	}

	$users = get_users(
		/**
		 * Documented in /premium/indexing.php.
		 */
		apply_filters( 'relevanssi_user_indexing_args', $args )
	);
	$count_users = count( $users );

	return $count_users;
}

/**
 * Counts taxonomy terms.
 *
 * Figures out how many taxonomy terms there are to index.
 *
 * @global $wpdb The WordPress database interface.
 *
 * @return int $count_terms Number of taxonomy terms, -1 if taxonomy term indexing is disabled.
 */
function relevanssi_count_taxonomy_terms() {
	$index_taxonomies = get_option( 'relevanssi_index_taxonomies' );
	if ( empty( $index_taxonomies ) || 'off' === $index_taxonomies ) {
		return -1;
	}

	global $wpdb;

	$taxonomies = get_option( 'relevanssi_index_terms' );
	if ( empty( $taxonomies ) ) {
		// No taxonomies chosen for indexing.
		return -1;
	}
	$count_terms = 0;
	foreach ( $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			// Non-existing taxonomy. Shouldn't be possible, but better be sure.
			continue;
		}

		/**
		 * Determines whether empty terms are indexed or not.
		 *
		 * @param boolean $hide_empty_terms If true, empty terms are not indexed. Default true.
		 */
		$hide_empty = apply_filters( 'relevanssi_hide_empty_terms', true );

		$count = '';
		if ( $hide_empty ) {
			$count = 'AND tt.count > 0';
		}

		$terms = $wpdb->get_col( "SELECT t.term_id FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt WHERE t.term_id = tt.term_id $count AND tt.taxonomy = '$taxonomy'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$count_terms += count( $terms );
	}
	return $count_terms;
}

/**
 * Returns the list of taxonomies chosen for indexing.
 *
 * Returns the list of taxonomies chosen for indexing from the 'relevanssi_index_terms' option.
 *
 * @return array $taxonomies A list of taxonomies chosen to be indexed.
 */
function relevanssi_list_taxonomies() {
	return get_option( 'relevanssi_index_terms' );
}

/**
 * Indexes taxonomy terms in AJAX context.
 *
 * Runs indexing on taxonomy terms in one taxonomy in AJAX context.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param string $taxonomy The taxonomy to index.
 * @param int    $limit    Number of users to index on one go.
 * @param int    $offset   Indexing offset.
 *
 * @return array $response AJAX response, number of taxonomy terms indexed in the
 * $response['indexed'] and a boolean value in $response['taxonomy_completed'] that
 * tells whether the taxonomy is indexed completely or not.
 */
function relevanssi_index_taxonomies_ajax( $taxonomy, $limit, $offset ) {
	global $wpdb;

	$indexed_terms = 0;
	$end_reached   = false;

	$terms = relevanssi_get_terms( $taxonomy, intval( $limit ), intval( $offset ) );

	if ( count( $terms ) < $limit ) {
		$end_reached = true;
	}

	do_action( 'relevanssi_pre_index_taxonomies' );

	foreach ( $terms as $term_id ) {
		$update = false;
		$term   = get_term( $term_id, $taxonomy );
		relevanssi_index_taxonomy_term( $term, $taxonomy, $update );
		++$indexed_terms;
	}

	do_action( 'relevanssi_post_index_taxonomies' );

	$response = array(
		'indexed'            => $indexed_terms,
		'taxonomy_completed' => 'not',
	);
	if ( $end_reached ) {
		$response['taxonomy_completed'] = 'done';
	}

	return $response;
}

/**
 * Indexes all taxonomies.
 *
 * Runs indexing on all taxonomies.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param boolean $is_ajax Whether indexing is done in the AJAX context, default false.
 *
 * @return array $response If $is_ajax is true, the function returns indexing status in an array.
 */
function relevanssi_index_taxonomies( $is_ajax = false ) {
	global $wpdb, $relevanssi_variables;

	$wpdb->query( 'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE doc = -1 AND type NOT IN ('user', 'post_type')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	do_action( 'relevanssi_pre_index_taxonomies' );

	$taxonomies    = get_option( 'relevanssi_index_terms' );
	$indexed_terms = 0;
	foreach ( $taxonomies as $taxonomy ) {
		$terms = relevanssi_get_terms( $taxonomy, 0, 0 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$progress = WP_CLI\Utils\make_progress_bar( "Indexing $taxonomy", count( $terms ) );
		}

		$update = false;
		foreach ( $terms as $term ) {
			relevanssi_index_taxonomy_term( $term, $taxonomy, $update );
			++$indexed_terms;
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				$progress->tick();
			}
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$progress->finish();
		}
	}

	do_action( 'relevanssi_post_index_taxonomies' );

	if ( $is_ajax ) {
		if ( $indexed_terms > 0 ) {
			// translators: the number of taxonomy terms.
			return sprintf( __( 'Indexed %d taxonomy terms.', 'relevanssi' ), $indexed_terms );
		} else {
			return __( 'No taxonomies to index.', 'relevanssi' );
		}
	}
}

/**
 * Gets a list of taxonomy terms.
 *
 * @param string $taxonomy The taxonomy to index.
 * @param int    $limit    Number of users to index on one go.
 * @param int    $offset   Indexing offset.
 *
 * @return array A list of taxonomy terms.
 */
function relevanssi_get_terms( string $taxonomy, int $limit = 0, int $offset = 0 ): array {
	global $wpdb;

	/**
	 * Determines whether empty terms are indexed or not.
	 *
	 * @param boolean $hide_empty_terms If true, empty terms are not indexed. Default true.
	 */
	$hide_empty = apply_filters( 'relevanssi_hide_empty_terms', true );
	$count      = '';
	if ( $hide_empty ) {
		$count = 'AND tt.count > 0';
	}

	$limit_sql = '';
	if ( $limit && $offset ) {
		$limit_sql = $wpdb->prepare( 'LIMIT %d OFFSET %d', $limit, $offset );
	}

	$terms = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT t.term_id FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt
			WHERE t.term_id = tt.term_id $count AND tt.taxonomy = %s $limit_sql ", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$taxonomy
		)
	);

	return $terms;
}

/**
 * Indexes one taxonomy term.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param object|int $term         The term object or term ID.
 * @param string     $taxonomy     The name of the taxonomy.
 * @param boolean    $remove_first Should the term be deleted first or not, default false.
 * @param boolean    $debug        If true, print out debug information, default false.
 */
function relevanssi_index_taxonomy_term( $term, $taxonomy, $remove_first = false, $debug = false ) {
	global $wpdb, $relevanssi_variables;

	if ( is_numeric( $term ) ) {
		// Not an object, so let's get the object.
		$term = get_term( $term, $taxonomy );
	}

	/**
	 * Allows the term object to be handled before indexing.
	 *
	 * This filter can be used to add data to term objects before indexing, or to manipulate the object somehow.
	 *
	 * @param object $term     The term object.
	 * @param string $taxonomy The taxonomy.
	 */
	$term = apply_filters( 'relevanssi_term_add_data', $term, $taxonomy );

	$temp_post               = new stdClass();
	$temp_post->post_content = $term->description;
	$temp_post->post_title   = $term->name;

	/**
	 * Allows modifying the fake post for the taxonomy term.
	 *
	 * In order to index taxonomy terms, Relevanssi generates fake posts from the
	 * terms. This filter lets you modify the post object. The term description
	 * is in the post_content and the term name in the post_title.
	 *
	 * @param object $temp_post The post object.
	 * @param object $term      The term object.
	 */
	$temp_post = apply_filters( 'relevanssi_post_to_index', $temp_post, $term );

	$term->description = $temp_post->post_content;
	$term->name        = $temp_post->post_title;

	$index_this_post = true;

	/**
	 * Determines whether a term is indexed or not.
	 *
	 * If this filter returns true, this term should not be indexed.
	 *
	 * @param boolean $block    If true, do not index this post. Default false.
	 * @param WP_Term $term     The term object.
	 * @param string  $taxonomy The term taxonomy.
	 */
	if ( true === apply_filters( 'relevanssi_do_not_index_term', false, $term, $taxonomy ) ) {
		// Filter says no.
		if ( $debug ) {
			relevanssi_debug_echo( 'relevanssi_do_not_index_term returned true.' );
		}
		$index_this_post = false;
	}

	if ( $remove_first ) {
		// The 0 doesn't mean anything, but because of WP hook parameters, it needs to be there
		// so the taxonomy can be passed as the third parameter.
		relevanssi_delete_taxonomy_term( $term->term_id, 0, $taxonomy );
	}

	// This needs to be here, after the call to relevanssi_delete_taxonomy_term(), because otherwise
	// a post that's in the index but shouldn't be there won't get removed.
	if ( ! $index_this_post ) {
		return 'donotindex';
	}

	$insert_data      = array();
	$remove_stopwords = true;

	$min_length = get_option( 'relevanssi_min_word_length', 3 );
	if ( ! isset( $term->description ) ) {
		$term->description = '';
	}
	/**
	 * Allows adding extra content to the term before indexing.
	 *
	 * The term description is passed through this filter, so if you want to add
	 * extra content to the description, you can use this filter.
	 *
	 * @param string $term->description The term description.
	 * @param object $term              The term object.
	 */
	$description = apply_filters( 'relevanssi_tax_term_additional_content', $term->description, $term );
	if ( ! empty( $description ) ) {
		/** This filter is documented in common/indexing.php */
		$tokens = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $description, $remove_stopwords, $min_length, 'indexing' ), 'term-description' );
		foreach ( $tokens as $t_term => $tf ) {
			if ( ! isset( $insert_data[ $t_term ]['content'] ) ) {
				$insert_data[ $t_term ]['content'] = 0;
			}
			$insert_data[ $t_term ]['content'] += $tf;
		}
	}

	if ( isset( $term->name ) && ! empty( $term->name ) ) {
		/** This filter is documented in common/indexing.php */
		$tokens = apply_filters( 'relevanssi_indexing_tokens', relevanssi_tokenize( $term->name, $remove_stopwords, $min_length, 'indexing' ), 'term-name' );
		foreach ( $tokens as $t_term => $tf ) {
			if ( ! isset( $insert_data[ $t_term ]['title'] ) ) {
				$insert_data[ $t_term ]['title'] = 0;
			}
			$insert_data[ $t_term ]['title'] += $tf;
		}
	}

	foreach ( $insert_data as $t_term => $data ) {
		$fields = array( 'content', 'title', 'comment', 'tag', 'link', 'author', 'category', 'excerpt', 'taxonomy', 'customfield' );
		foreach ( $fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				$data[ $field ] = 0;
			}
		}

		$content     = $data['content'];
		$title       = $data['title'];
		$comment     = $data['comment'];
		$tag         = $data['tag'];
		$link        = $data['link'];
		$author      = $data['author'];
		$category    = $data['category'];
		$excerpt     = $data['excerpt'];
		$customfield = $data['customfield'];
		$t_term      = trim( $t_term ); // Numeric terms start with a space.

		$wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO ' . $relevanssi_variables['relevanssi_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				' (item, doc, term, term_reverse, content, title, comment, tag, link, author, category, excerpt, taxonomy, customfield, type, customfield_detail, taxonomy_detail, mysqlcolumn_detail)
			VALUES (%d, %d, %s, REVERSE(%s), %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %s)',
				$term->term_id,
				-1,
				$t_term,
				$t_term,
				$content,
				$title,
				$comment,
				$tag,
				$link,
				$author,
				$category,
				$excerpt,
				'',
				$customfield,
				$taxonomy,
				'',
				'',
				''
			)
		);
	}
}

/**
 * Removes a document from the index.
 *
 * This Premium version also takes care of internal linking keywords, either keeping them (in case of
 * an update) or removing them (if the post is removed).
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param int     $post_id               The post ID.
 * @param boolean $keep_internal_linking If true, do not remove internal link keywords from this post.
 */
function relevanssi_premium_remove_doc( $post_id, $keep_internal_linking ) {
	global $wpdb, $relevanssi_variables;

	$post_id = intval( $post_id );
	if ( empty( $post_id ) ) {
		// No post ID specified.
		return;
	}

	$internal_links = '';
	if ( $keep_internal_linking ) {
		$internal_links = 'AND link = 0';
	}

	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . " WHERE doc=%s $internal_links", $post_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! $keep_internal_linking ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE link > 0 AND doc=%s', $post_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

/**
 * Deletes an item (user or taxonomy term) from the index.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the database table names.
 *
 * @param int    $item_id The item ID number.
 * @param string $type    The item type.
 */
function relevanssi_remove_item( $item_id, $type ) {
	global $wpdb, $relevanssi_variables;

	$item_id = intval( $item_id );

	if ( 0 === $item_id && 'post' === $type ) {
		// Security measures.
		return;
	}

	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . ' WHERE item = %d AND type = %s', $item_id, $type ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Checks if post is hidden.
 *
 * Used in indexing process to check if post is hidden. Checks the
 * '_relevanssi_hide_post' custom field.
 *
 * @param int $post_id The post ID to check.
 *
 * @return boolean $hidden Is the post hidden?
 */
function relevanssi_hide_post( $post_id ) {
	$hidden      = false;
	$field_value = get_post_meta( $post_id, '_relevanssi_hide_post', true );
	if ( 'on' === $field_value ) {
		$hidden = true;
	}
	return $hidden;
}

/**
 * Indexes post type archive pages.
 *
 * Goes through all the post type archive pages and indexes them using
 * relevanssi_index_post_type_archive().
 *
 * @see relevanssi_index_post_type_archive()
 * @since 2.2
 *
 * @global object $wpdb The WordPress database object.
 */
function relevanssi_index_post_type_archives() {
	if ( 'on' === get_option( 'relevanssi_index_post_type_archives' ) ) {
		global $wpdb, $relevanssi_variables;

		// Delete all post types from the Relevanssi index first.
		$wpdb->query(
			'DELETE FROM ' . $relevanssi_variables['relevanssi_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			" WHERE type = 'post_type'"
		);

		$post_types = relevanssi_get_indexed_post_type_archives();
		if ( ! empty( $post_types ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				$progress = WP_CLI\Utils\make_progress_bar(
					'Indexing post type archives',
					count( $post_types )
				);
			}
			foreach ( $post_types as $post_type ) {
				relevanssi_index_post_type_archive( $post_type );
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					$progress->tick();
				}
			}
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				$progress->finish();
			}
		} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( 'No post types available for post type archive indexing.' );
		}
	} elseif ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::error( 'Post type archive indexing disabled.' );
	}
}

/**
 * Indexes post type archive pages in AJAX context.
 *
 * Runs indexing on all post type archives in AJAX context.
 *
 * @return array $response AJAX response, number of post type archives indexed
 * in the$response['indexed'].
 */
function relevanssi_index_post_type_archives_ajax() {
	$post_types = relevanssi_get_indexed_post_type_archives();

	if ( empty( $post_types ) ) {
		$response = array(
			'indexed' => 0,
		);
		return $response;
	}

	$indexed_post_types = 0;
	foreach ( $post_types as $post_type ) {
		relevanssi_index_post_type_archive( $post_type );
		++$indexed_post_types;
	}

	$response = array(
		'indexed' => $indexed_post_types,
	);

	return $response;
}

/**
 * Assigns numeric IDs for post types.
 *
 * Relevanssi requires numeric IDs for post types for indexing purposes. This
 * function assigns numbers for each post type, in alphabetical order. This is a
 * bit of a hack, and fails if new post types are added, but hopefully that
 * doesn't happen too often. The assigned numbers are stored in the option
 * relevanssi_post_type_ids.
 *
 * @since 2.2
 *
 * @return array The post type ID arrays (by ID and by name).
 */
function relevanssi_assign_post_type_ids() {
	$post_types = relevanssi_get_indexed_post_type_archives();
	sort( $post_types );

	$post_type_ids_by_id   = array();
	$post_type_ids_by_name = array();

	$id = 1;
	foreach ( $post_types as $post_type ) {
		$post_type_ids_by_id[ $id ]          = $post_type;
		$post_type_ids_by_name[ $post_type ] = $id;
		++$id;
	}
	update_option(
		'relevanssi_post_type_ids',
		array(
			'by_id'   => $post_type_ids_by_id,
			'by_name' => $post_type_ids_by_name,
		)
	);

	return array(
		'by_id'   => $post_type_ids_by_id,
		'by_name' => $post_type_ids_by_name,
	);
}

/**
 * Gets the post type ID by post type name.
 *
 * Fetches the post type ID from the relevanssi_post_type_ids option by the post
 * type name. If the option is empty, will populate it with values. If the post
 * type can't be found in the list, the function tries to regenerate the list in
 * case there's a new post type Relevanssi doesn't know.
 *
 * @see relevanssi_assign_post_type_ids()
 * @see relevanssi_get_post_type_by_id()
 * @since 2.2
 *
 * @param string $post_type The name of the post type.
 *
 * @return integer|null The post type ID number or null if not a valid post
 * type.
 */
function relevanssi_get_post_type_by_name( $post_type ) {
	$post_type_ids = get_option( 'relevanssi_post_type_ids', false );
	if ( empty( $post_type_ids ) ) {
		$post_type_ids = relevanssi_assign_post_type_ids();
	}
	if ( ! isset( $post_type_ids['by_name'][ $post_type ] ) ) {
		$post_type_ids = relevanssi_assign_post_type_ids();
	}
	if ( isset( $post_type_ids['by_name'][ $post_type ] ) ) {
		return $post_type_ids['by_name'][ $post_type ];
	} else {
		return null;
	}
}

/**
 * Gets the post type name by post type ID.
 *
 * Fetches the post type name from the relevanssi_post_type_ids option by the
 * post type ID. If the option is empty, will populate it with values. If the
 * post type can't be found in the list, the function tries to regenerate the
 * list in case there's a new post type Relevanssi doesn't know.
 *
 * @see relevanssi_assign_post_type_ids()
 * @see relevanssi_get_post_type_by_name()
 * @since 2.2
 *
 * @param integer $id The ID number of the post type.
 *
 * @return string|null The post type name or null if not a valid post type.
 */
function relevanssi_get_post_type_by_id( $id ) {
	$post_type_ids = get_option( 'relevanssi_post_type_ids', false );
	if ( empty( $post_type_ids ) ) {
		$post_type_ids = relevanssi_assign_post_type_ids();
	}
	if ( ! isset( $post_type_ids['by_id'][ $id ] ) ) {
		$post_type_ids = relevanssi_assign_post_type_ids();
	}
	if ( isset( $post_type_ids['by_id'][ $id ] ) ) {
		return $post_type_ids['by_id'][ $id ];
	} else {
		return null;
	}
}

/**
 * Indexes a post type archive page.
 *
 * Indexes a post type archive page, indexing the archive label and the
 * description which can be set when the post type is registered. The filter
 * hook relevanssi_post_type_additional_content can be used to add additional
 * content to the post type archive description.
 *
 * @since 2.2
 *
 * @param string  $post_type    The name of the post type.
 * @param boolean $remove_first Should the post type be removed first from the
 * index.
 *
 * @global object $wpdb                 The WordPress database object.
 * @global array  $relevanssi_variables The Relevanssi global variables.
 */
function relevanssi_index_post_type_archive( $post_type, $remove_first = true ) {
	$post_type_object = get_post_type_object( $post_type );
	global $wpdb, $relevanssi_variables;

	/**
	 * Allows excluding post type archives from the index.
	 *
	 * If this filter hook returns false, the post type archive won't be
	 * indexed and if it's already indexed, it will be removed from the index.
	 *
	 * @param boolean If true, index the archive. Default true.
	 * @param object  The post type object.
	 */
	if ( ! apply_filters( 'relevanssi_post_type_archive_ok', true, $post_type_object ) ) {
		relevanssi_delete_post_type_object( $post_type );
		return;
	}

	$temp_post               = new stdClass();
	$temp_post->post_content = $post_type_object->description;
	$temp_post->post_title   = $post_type_object->name;

	/**
	 * Allows modifying the fake post for the post type archive.
	 *
	 * In order to index post type archives, Relevanssi generates fake posts
	 * from the post types. This filter lets you modify the post object. The
	 * post type description is in the post_content and the post type name in
	 * the post_title.
	 *
	 * @param object $temp_post The post object.
	 * @param object $post_type The post type object.
	 */
	$temp_post = apply_filters(
		'relevanssi_post_to_index',
		$temp_post,
		$post_type_object
	);

	$post_type_object->description = $temp_post->post_content;
	$post_type_object->name        = $temp_post->post_title;

	if ( $remove_first ) {
		relevanssi_delete_post_type_object( $post_type );
	}

	$insert_data      = array();
	$remove_stopwords = true;

	$min_length = get_option( 'relevanssi_min_word_length', 3 );
	if ( ! isset( $post_type_object->description ) ) {
		$post_type_object->description = '';
	}
	/**
	 * Allows adding extra content to the post type before indexing.
	 *
	 * The post type description is passed through this filter, so if you want
	 * to add extra content to the description, you can use this filter.
	 *
	 * @param string $post_type_object->description The post type description.
	 * @param object $post_type_object              The post type object.
	 */
	$description = apply_filters(
		'relevanssi_post_type_additional_content',
		$post_type_object->description,
		$post_type_object
	);
	if ( ! empty( $description ) ) {
		/** This filter is documented in lib/indexing.php */
		$tokens = apply_filters(
			'relevanssi_indexing_tokens',
			relevanssi_tokenize( $description, $remove_stopwords, $min_length, 'indexing' ),
			'posttype-description'
		);
		foreach ( $tokens as $t_term => $tf ) {
			if ( ! isset( $insert_data[ $t_term ]['content'] ) ) {
				$insert_data[ $t_term ]['content'] = 0;
			}
			$insert_data[ $t_term ]['content'] += $tf;
		}
	}

	if ( isset( $post_type_object->name ) && ! empty( $post_type_object->name ) ) {
		/** This filter is documented in lib/indexing.php */
		$tokens = apply_filters(
			'relevanssi_indexing_tokens',
			relevanssi_tokenize( $post_type_object->label, $remove_stopwords, $min_length, 'indexing' ),
			'posttype-name'
		);
		foreach ( $tokens as $t_term => $tf ) {
			if ( ! isset( $insert_data[ $t_term ]['title'] ) ) {
				$insert_data[ $t_term ]['title'] = 0;
			}
			$insert_data[ $t_term ]['title'] += $tf;
		}
	}

	$post_type_id = relevanssi_get_post_type_by_name( $post_type );
	foreach ( $insert_data as $t_term => $data ) {
		$fields = array( 'content', 'title', 'comment', 'tag', 'link', 'author', 'category', 'excerpt', 'taxonomy', 'customfield' );
		foreach ( $fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				$data[ $field ] = 0;
			}
		}

		$content     = $data['content'];
		$title       = $data['title'];
		$comment     = $data['comment'];
		$tag         = $data['tag'];
		$link        = $data['link'];
		$author      = $data['author'];
		$category    = $data['category'];
		$excerpt     = $data['excerpt'];
		$customfield = $data['customfield'];
		$t_term      = trim( $t_term ); // Numeric terms start with a space.

		$wpdb->query(
			$wpdb->prepare(
				'INSERT IGNORE INTO ' . $relevanssi_variables['relevanssi_table'] . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				' (item, doc, term, term_reverse, content, title, comment, tag, link, author, category, excerpt, taxonomy, customfield, type, customfield_detail, taxonomy_detail, mysqlcolumn_detail)
			VALUES (%d, %d, %s, REVERSE(%s), %d, %d, %d, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %s)',
				$post_type_id,
				-1,
				$t_term,
				$t_term,
				$content,
				$title,
				$comment,
				$tag,
				$link,
				$author,
				$category,
				$excerpt,
				'',
				$customfield,
				'post_type',
				'',
				'',
				''
			)
		);
	}
}

/**
 * Deletes a post type archive from Relevanssi index.
 *
 * @global $wpdb The WordPress database interface.
 * @global $relevanssi_variables The global Relevanssi variables, used for the
 * database table names.
 *
 * @param string $post_type Name of the post type to remove.
 */
function relevanssi_delete_post_type_object( $post_type ) {
	global $wpdb, $relevanssi_variables;
	$id = relevanssi_get_post_type_by_name( $post_type );
	if ( $id ) {
		$wpdb->query(
			'DELETE FROM ' .
			$relevanssi_variables['relevanssi_table'] . " WHERE item = $id " . // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"AND type = 'post_type'"
		);
	}
}

/**
 * Returns the list of post type archives indexed.
 *
 * Returns a list of post types that have _builtin set to false and has_archive
 * set to true. The list can be adjusted with the
 * relevanssi_indexed_post_type_archives filter hook.
 *
 * @return array An array of post types.
 */
function relevanssi_get_indexed_post_type_archives() {
	$args       = array(
		'_builtin'    => false,
		'has_archive' => true,
	);
	$post_types = get_post_types( $args );
	/**
	 * Filters the list of post type archives that are indexed by Relevanssi.
	 *
	 * @param array An array of post types.
	 *
	 * @return array An array of post types.
	 */
	return apply_filters( 'relevanssi_indexed_post_type_archives', $post_types );
}

/**
 * Runs taxonomy, user and post type archive indexing if necessary.
 */
function relevanssi_premium_indexing() {
	if ( 'on' === get_option( 'relevanssi_index_taxonomies' ) ) {
		relevanssi_index_taxonomies();
	}
	if ( 'on' === get_option( 'relevanssi_index_users' ) ) {
		relevanssi_index_users();
	}
	if ( 'on' === get_option( 'relevanssi_index_post_type_archives' ) ) {
		relevanssi_index_post_type_archives();
	}
}
