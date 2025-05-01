<?php
/**
 * /premium/search.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Recognizes negative search terms.
 *
 * Finds all the search terms that begin with a -.
 *
 * @param string $q Search query.
 *
 * @return array $negative_terms Array of negative search terms.
 */
function relevanssi_recognize_negatives( $q ) {
	$term           = strtok( $q, ' ' );
	$negative_terms = array();
	while ( false !== $term ) {
		if ( '-' === substr( $term, 0, 1 ) ) {
			array_push( $negative_terms, substr( $term, 1 ) );
		}
		$term = strtok( ' ' );
	}
	return $negative_terms;
}

/**
 * Recognizes positive search terms.
 *
 * Finds all the search terms that begin with a +.
 *
 * @param string $q Search query.
 *
 * @return array $positive_terms Array of positive search terms.
 */
function relevanssi_recognize_positives( $q ) {
	$term           = strtok( $q, ' ' );
	$positive_terms = array();
	while ( false !== $term ) {
		if ( '+' === substr( $term, 0, 1 ) ) {
			$term_part = substr( $term, 1 );
			if ( ! empty( $term_part ) ) { // To avoid problems with just plus signs.
				array_push( $positive_terms, $term_part );
			}
		}
		$term = strtok( ' ' );
	}
	return $positive_terms;
}

/**
 * Creates SQL code for positive and negative terms.
 *
 * Creates the necessary SQL code for positive (AND) and negative (NOT) search terms.
 *
 * @param array $negative_terms Negative terms.
 * @param array $positive_terms Positive terms.
 *
 * @return string $query_restrictions MySQL code for the terms.
 */
function relevanssi_negatives_positives( $negative_terms, $positive_terms ) {
	global $relevanssi_variables;
	$relevanssi_table = $relevanssi_variables['relevanssi_table'];

	$query_restrictions = '';
	if ( $negative_terms ) {
		$size = count( $negative_terms );
		for ( $i = 0; $i < $size; $i++ ) {
			$negative_terms[ $i ] = "'" . esc_sql( $negative_terms[ $i ] ) . "'";
		}
		$negatives           = implode( ',', $negative_terms );
		$query_restrictions .= " AND doc NOT IN (SELECT DISTINCT(doc) FROM $relevanssi_table WHERE term IN ( $negatives))";
		// Clean: $negatives is escaped.
	}

	if ( $positive_terms ) {
		$size = count( $positive_terms );
		for ( $i = 0; $i < $size; $i++ ) {
			$positive_term       = esc_sql( $positive_terms[ $i ] );
			$query_restrictions .= " AND doc IN (SELECT DISTINCT(doc) FROM $relevanssi_table WHERE term = '$positive_term')";
			// Clean: $positive_term is escaped.
		}
	}
	return $query_restrictions;
}

/**
 * Gets the recency bonus option.
 *
 * Gets the recency bonus and converts the cutoff day count to time().
 *
 * @return array $recency_bonus Array( recency bonus, cutoff date ).
 */
function relevanssi_get_recency_bonus() {
	$recency_bonus_option = get_option( 'relevanssi_recency_bonus' );
	$recency_bonus        = false;
	$recency_cutoff_date  = false;

	if ( isset( $recency_bonus_option['bonus'] ) ) {
		$recency_bonus = floatval( $recency_bonus_option['bonus'] );
	}
	if ( $recency_bonus && isset( $recency_bonus_option['days'] ) ) {
		$recency_cutoff_date = time() - DAY_IN_SECONDS * $recency_bonus_option['days'];
	}

	return array(
		'bonus'  => $recency_bonus,
		'cutoff' => $recency_cutoff_date,
	);
}

/**
 * Introduces the query variables for Relevanssi Premium.
 *
 * @param array $qv The WordPress query variable array.
 */
function relevanssi_premium_query_vars( $qv ) {
	$qv[] = 'searchblogs';
	$qv[] = 'customfield_key';
	$qv[] = 'customfield_value';
	$qv[] = 'operator';
	$qv[] = 'include_attachments';
	$qv[] = 'coordinates';
	$qv[] = 'rlv_source';
	return $qv;
}

/**
 * Sets the operator parameter.
 *
 * The operator parameter is taken from $query->query_vars['operator'],
 * or from the implicit operator setting.
 *
 * @param object $query The query object.
 */
function relevanssi_set_operator( $query ) {
	if ( isset( $query->query_vars['operator'] ) ) {
		$operator = $query->query_vars['operator'];
	} else {
		$operator = get_option( 'relevanssi_implicit_operator' );
	}
	return $operator;
}

/**
 * Processes the negative and positive terms (ie. local AND and NOT operators).
 *
 * If negative terms are present, will remove them from the $terms array. If negative
 * or positive terms are present, will return the query restrictions MySQL for them.
 *
 * @param array  $terms          An array of search terms.
 * @param array  $original_terms An array of unstemmed search terms.
 * @param string $query          The search query as a string.
 *
 * @return array An array containing the updated terms and the query restrictions.
 */
function relevanssi_process_terms( $terms, $original_terms, $query ) {
	$negative_terms = relevanssi_recognize_negatives( $query );
	$positive_terms = relevanssi_recognize_positives( $query );

	if ( $negative_terms ) {
		$terms          = array_diff( $terms, $negative_terms );
		$original_terms = array_diff( $original_terms, $negative_terms );
	}

	// Clean: escaped in the function.
	$query_restrictions = relevanssi_negatives_positives( $negative_terms, $positive_terms );

	return array(
		'terms'              => $terms,
		'original_terms'     => $original_terms,
		'query_restrictions' => $query_restrictions,
	);
}

/**
 * Replaces the wildcards (?, *) with strings to let them pass intact.
 *
 * The wildcards are only allowed inside words, so they must have a word
 * character on both sides of them.
 *
 * @param string $str The query or content string to modify.
 *
 * @return string The parameter string modified.
 */
function relevanssi_wildcards_pre( $str ) {
	/**
	 * If true, enables wildcard operators (*, ?).
	 *
	 * @param boolean If true, enable wildcard operator. Default false.
	 */
	if ( apply_filters( 'relevanssi_wildcard_search', false ) ) {
		$str = preg_replace( '/(\w)\?(\w)/', '\1SINGLEWILDCARDSYMBOL\2', $str );
		$str = preg_replace( '/(\w)\*(\w)/', '\1MULTIWILDCARDSYMBOL\2', $str );
	}
	return $str;
}

/**
 * Replaces the wildcard strings with wildcards (?, *).
 *
 * @param string $str The query or content string to modify.
 *
 * @return string The parameter string modified.
 */
function relevanssi_wildcards_post( $str ) {
	/**
	 * Documented in /premium/search.php.
	 */
	if ( apply_filters( 'relevanssi_wildcard_search', false ) ) {
		$str = preg_replace( '/SINGLEWILDCARDSYMBOL/', '?', $str );
		$str = preg_replace( '/MULTIWILDCARDSYMBOL/', '*', $str );
	}
	return $str;
}

/**
 * Replaces the wildcards (?, *) with their MySQL equivalents (_, %).
 *
 * The ? is converted to _ (single character), while * is converted to %
 * (zero or more). Hooks to the relevanssi_term_where filter hook to only
 * apply this to the term WHERE condition part of the query.
 *
 * @see relevanssi_term_where
 *
 * @param string $query MySQL query to modify.
 * @param string $term  The search term.
 *
 * @return string The modified MySQL query.
 */
function relevanssi_query_wildcards( $query, $term ) {
	/**
	 * Documented in /premium/search.php.
	 */
	if ( apply_filters( 'relevanssi_wildcard_search', false ) ) {
		$query = str_replace( "= '$term'", "LIKE '$term'", $query );
		$query = str_replace( array( '?', '*' ), array( '_', '%' ), $query );
	}
	return $query;
}
