<?php
/**
 * /premium/body-stopwords.php
 *
 * @package Relevanssi
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_filter( 'relevanssi_match', 'relevanssi_block_body_stopwords', 10, 3 );

/**
 * Adds a stopword to the list of stopwords.
 *
 * @param string  $term    The stopword that is added.
 * @param boolean $verbose If true, print out notice. If false, be silent. Default
 * true.
 *
 * @return boolean True, if success; false otherwise.
 */
function relevanssi_add_body_stopword( $term, $verbose = true ) {
	if ( empty( $term ) ) {
		return;
	}

	$n = 0;
	$s = 0;

	$terms = explode( ',', $term );
	if ( count( $terms ) > 1 ) {
		foreach ( $terms as $term ) {
			++$n;
			$term    = trim( $term );
			$success = relevanssi_add_single_body_stopword( $term );
			if ( $success ) {
				++$s;
			}
		}
		if ( $verbose ) {
			// translators: %1$d is the successful entries, %2$d is the total entries.
			printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( 'Successfully added %1$d/%2$d terms to content stopwords!', 'relevanssi' ), intval( $s ), intval( $n ) ) );
		}
	} else {
		// Add to stopwords.
		$success = relevanssi_add_single_body_stopword( $term );

		$term = stripslashes( $term );
		$term = esc_html( $term );
		if ( $verbose ) {
			if ( $success ) {
				// Translators: %s is the stopword.
				printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( "Term '%s' added to content stopwords!", 'relevanssi' ), esc_html( stripslashes( $term ) ) ) );
			} else {
				// Translators: %s is the stopword.
				printf( "<div id='message' class='updated fade'><p>%s</p></div>", sprintf( esc_html__( "Couldn't add term '%s' to content stopwords!", 'relevanssi' ), esc_html( stripslashes( $term ) ) ) );
			}
		}
	}

	return $success;
}

/**
 * Adds a single stopword to the stopword table.
 *
 * @global object $wpdb                 The WP database interface.
 * @global array  $relevanssi_variables The global Relevanssi variables.
 *
 * @param string $term The term to add.
 *
 * @return boolean True if success, false if not.
 */
function relevanssi_add_single_body_stopword( $term ) {
	if ( empty( $term ) ) {
		return false;
	}

	$term      = stripslashes( relevanssi_strtolower( $term ) );
	$stopwords = relevanssi_fetch_body_stopwords();

	if ( in_array( $term, $stopwords, true ) ) {
		return false;
	}

	$stopwords[] = $term;

	$success = relevanssi_update_body_stopwords( $stopwords );

	if ( ! $success ) {
		return false;
	}

	global $wpdb, $relevanssi_variables;

	relevanssi_delete_term_from_all_post_content( $term );

	// Remove all lines with all zeros, ie. no matches.
	$wpdb->query(
		'DELETE FROM '
		. $relevanssi_variables['relevanssi_table']  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		. ' WHERE content + title + comment + tag + link + author + category + excerpt + taxonomy + customfield + mysqlcolumn = 0'
	);

	return true;
}

/**
 * Deletes a term from all posts in the database, language considered.
 *
 * If Polylang or WPML are used, deletes the term only from the posts matching
 * the current language.
 *
 * @param string $term The term to delete.
 */
function relevanssi_delete_term_from_all_post_content( $term ) {
	global $wpdb, $relevanssi_variables;

	if ( function_exists( 'pll_languages_list' ) ) {
		$term_id = relevanssi_get_language_term_taxonomy_id(
			relevanssi_get_current_language()
		);

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$relevanssi_variables['relevanssi_table']}
				SET content = 0
				WHERE term=%s
				AND doc IN (
					SELECT object_id
					FROM $wpdb->term_relationships
					WHERE term_taxonomy_id = %d
				)",
				$term,
				$term_id
			)
		);

		return;
	}

	if ( function_exists( 'icl_object_id' ) && ! function_exists( 'pll_is_translated_post_type' ) ) {
		$language = relevanssi_get_current_language( false );
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$relevanssi_variables['relevanssi_table']}
				SET content = 0
				WHERE term=%s
				AND doc IN (
					SELECT DISTINCT(element_id)
					FROM {$wpdb->prefix}icl_translations
					WHERE language_code = %s
				)",
				$term,
				$language
			)
		);

		return;
	}

	// No language defined, just remove from the index.
	$wpdb->query(
		$wpdb->prepare(
			'UPDATE ' . $relevanssi_variables['relevanssi_table'] . ' SET content = 0 WHERE term=%s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$term
		)
	);
}

/**
 * Removes all content stopwords in specific language.
 *
 * Empties the relevanssi_body_stopwords option for particular language.
 *
 * @param string $language The language code of stopwords. If empty, removes
 * the stopwords for the current language.
 */
function relevanssi_remove_all_body_stopwords( $language = null ) {
	if ( ! $language ) {
		$language = relevanssi_get_current_language();
	}

	$stopwords = get_option( 'relevanssi_body_stopwords', array() );
	unset( $stopwords[ $language ] );
	$success = update_option( 'relevanssi_body_stopwords', $stopwords );

	if ( $success ) {
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			esc_html__( 'All content stopwords removed! Remember to re-index.', 'relevanssi' )
		);
	} else {
		printf(
			"<div id='message' class='updated fade'><p>%s</p></div>",
			esc_html__( "There was a problem, and content stopwords couldn't be removed.", 'relevanssi' )
		);
	}
}

/**
 * Updates the current language content stopwords in the stopwords option.
 *
 * Fetches the stopwords option, replaces the current language stopwords with
 * the parameter array and updates the option.
 *
 * @param array $stopwords An array of stopwords.
 *
 * @return boolean The return value from update_option().
 */
function relevanssi_update_body_stopwords( $stopwords ) {
	$current_language = relevanssi_get_current_language();
	$stopwords_option = get_option( 'relevanssi_body_stopwords', array() );

	$stopwords_option[ $current_language ] = implode( ',', array_filter( $stopwords ) );
	return update_option(
		'relevanssi_body_stopwords',
		$stopwords_option
	);
}

/**
 * Removes a single content stopword.
 *
 * @param string  $term    The stopword to remove.
 * @param boolean $verbose If true, print out a notice. Default true.
 *
 * @return boolean True if success, false if not.
 */
function relevanssi_remove_body_stopword( $term, $verbose = true ) {
	$stopwords = relevanssi_fetch_body_stopwords();
	$term      = stripslashes( $term );
	$stopwords = array_filter(
		$stopwords,
		function ( $stopword ) use ( $term ) {
			return $stopword !== $term;
		}
	);

	$success = relevanssi_update_body_stopwords( $stopwords );

	if ( $success ) {
		if ( $verbose ) {
			printf(
				"<div id='message' class='updated fade'><p>%s</p></div>",
				sprintf(
					// Translators: %s is the stopword.
					esc_html__(
						"Term '%s' removed from content stopwords! Re-index to get it back to index.",
						'relevanssi'
					),
					esc_html( stripslashes( $term ) )
				)
			);
		}
		return true;
	} else {
		if ( $verbose ) {
			printf(
				"<div id='message' class='updated fade'><p>%s</p></div>",
				sprintf(
					// Translators: %s is the stopword.
					esc_html__(
						"Couldn't remove term '%s' from content stopwords!",
						'relevanssi'
					),
					esc_html( stripslashes( $term ) )
				)
			);
		}
		return false;
	}
}

/**
 * Fetches the list of content stopwords.
 *
 * Gets the list of content stopwords from the options.
 *
 * @return array An array of stopwords.
 */
function relevanssi_fetch_body_stopwords() {
	$current_language = relevanssi_get_current_language();
	$stopwords_array  = get_option( 'relevanssi_body_stopwords', array() );
	$stopwords        = isset( $stopwords_array[ $current_language ] ) ? $stopwords_array[ $current_language ] : '';
	$stopword_list    = $stopwords ? explode( ',', $stopwords ) : array();

	return $stopword_list;
}

/**
 * Displays a list of body stopwords.
 *
 * Displays the list of body stopwords and gives the controls for adding new stopwords.
 */
function relevanssi_show_body_stopwords() {
	printf(
		'<p>%s</p>',
		esc_html__( 'Post content stopwords are like stopwords, but they are only applied to the post content. These words can be used for searching and will be found in post titles, custom fields and other indexed content – just not in the post body content. Sometimes a word can be very common, but also have a more specific meaning and use on your site, and making it a content stopword will make it easier to find the specific use cases.', 'relevanssi' )
	);
	?>
<table class="form-table">
<tr>
	<th scope="row">
		<label for="addbodystopword"><p><?php esc_html_e( 'Content stopword(s) to add', 'relevanssi' ); ?>
	</th>
	<td>
		<textarea name="addbodystopword" id="addbodystopword" rows="2" cols="80"></textarea>
		<p><input type="submit" value="<?php esc_attr_e( 'Add', 'relevanssi' ); ?>" class='button' /></p>
	</td>
</tr>
</table>
<p><?php esc_html_e( "Here's a list of content stopwords in the database. Click a word to remove it from content stopwords. You need to reindex the database to get the words back in to the index.", 'relevanssi' ); ?></p>

<table class="form-table">
<tr>
	<th scope="row">
		<?php esc_html_e( 'Current content stopwords', 'relevanssi' ); ?>
	</th>
	<td>
		<ul>
	<?php
	$stopwords  = array_map( 'stripslashes', relevanssi_fetch_body_stopwords() );
	$exportlist = htmlspecialchars( implode( ', ', $stopwords ) );
	sort( $stopwords );
	array_walk(
		$stopwords,
		function ( $term ) {
			printf( '<li style="display: inline;"><input type="submit" name="removebodystopword" value="%s"/></li>', esc_attr( $term ) );
		}
	);

	?>
	</ul>
	<p><input type="submit" id="removeallbodystopwords" name="removeallbodystopwords" value="<?php esc_attr_e( 'Remove all content stopwords', 'relevanssi' ); ?>" class='button' /></p>
	</td>
</tr>
<tr>
	<th scope="row">
		<?php esc_html_e( 'Exportable list of content stopwords', 'relevanssi' ); ?>
	</th>
	<td>
		<label for="bodystopwords" class="screen-reader-text"><?php esc_html_e( 'Exportable list of content stopwords', 'relevanssi' ); ?></label>
		<textarea name="bodystopwords" id="bodystopwords" rows="2" cols="80"><?php echo esc_textarea( $exportlist ); ?></textarea>
		<p class="description"><?php esc_html_e( 'You can copy the list of content stopwords from here if you want to back up the list, copy it to a different blog or otherwise need the list.', 'relevanssi' ); ?></p>
	</td>
</tr>
</table>

	<?php
}

/**
 * Blocks body stopwords from partial matches.
 *
 * If the search term is a body stopword, all cases where all the matches are
 * in the post content are removed from the results by setting the match
 * weight to 0. This will eliminate all partial matches based on body stopwords
 * from the results.
 *
 * @param object $match_object The match object.
 * @param int    $idf          The IDF value (not used here).
 * @param string $term         The original search term.
 *
 * @return object The match object.
 */
function relevanssi_block_body_stopwords( $match_object, $idf, $term ) {
	$body_stopwords = relevanssi_fetch_body_stopwords();
	if ( in_array( $term, $body_stopwords, true ) ) {
		$sum = $match_object->content
			+ $match_object->title
			+ $match_object->comment
			+ $match_object->link
			+ $match_object->author
			+ $match_object->excerpt
			+ $match_object->customfield
			+ $match_object->mysqlcolumn
			+ $match_object->tag
			+ $match_object->taxonomy
			+ $match_object->category;
		if ( (int) $match_object->content === (int) $sum ) {
			$match_object->weight = 0;
		}
	}
	return $match_object;
}
