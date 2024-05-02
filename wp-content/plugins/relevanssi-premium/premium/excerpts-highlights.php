<?php
/**
 * /premium/excerpts-highlights.php
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

/**
 * Extracts multiple excerpts from the full text.
 *
 * Finds all the parts of the full text where the terms appear. The function
 * splits the content by spaces and takes slices of the content, counts the
 * terms in the content and if the count is > 0, adds the excerpt to the list
 * of excerpts. To avoid overlapping excerpts, similarity is calculated against
 * other excerpts and only those excerpts with a similarity percentage of less
 * than 50 are accepted.
 *
 * @param array  $terms          An array of relevant words.
 * @param string $content        The source text.
 * @param int    $excerpt_length The length of the excerpt, default 30 words.
 *
 * @return array An array of excerpts. In each excerpt, there are following
 * parts: 'text' has the excerpt text, 'hits' the number of keyword matches in
 * the excerpt, 'start' is true if the excerpt is from the beginning of the
 * content.
 */
function relevanssi_extract_multiple_excerpts( $terms, $content, $excerpt_length = 30 ) {
	if ( $excerpt_length < 1 ) {
		return array( '', 0, false );
	}

	$words       = array_filter( explode( ' ', $content ) );
	$offset      = 0;
	$tries       = 0;
	$count_words = count( $words );
	$start       = false;
	$gap         = 0;

	$excerpts = array();

	$excerpt_candidates = $count_words / $excerpt_length;
	if ( $excerpt_candidates > 200 ) {
		/**
		 * Adjusts the gap between excerpt candidates.
		 *
		 * The default value for the gap is number of words / 200 minus the
		 * excerpt length, which means Relevanssi tries to create 200 excerpts.
		 *
		 * @param int The gap between excerpt candidates.
		 * @param int $count_words    The number of words in the content.
		 * @param int $excerpt_length The length of the excerpt.
		 */
		$gap = apply_filters(
			'relevanssi_excerpt_gap',
			floor( $count_words / 200 - $excerpt_length ),
			$count_words,
			$excerpt_length
		);
	}

	while ( $offset < $count_words ) {
		if ( $offset + $excerpt_length > $count_words ) {
			$offset = $count_words - $excerpt_length;
			if ( $offset < 0 ) {
				$offset = 0;
			}
		}
		$excerpt_slice = array_slice( $words, $offset, $excerpt_length );
		$excerpt_slice = ' ' . implode( ' ', $excerpt_slice );
		$count_matches = relevanssi_count_matches( $terms, $excerpt_slice );
		if ( $count_matches > 0 ) {
			if ( 0 === $offset ) {
				$start = true;
			} else {
				$start = false;
			}

			$similarity = 0;
			array_walk(
				$excerpts,
				function ( $item ) use ( &$similarity, $excerpt_slice ) {
					similar_text( $item['text'], $excerpt_slice, $percentage );
					if ( $percentage > $similarity ) {
						$similarity = $percentage;
					}
				}
			);
			if ( $similarity < 50 ) {
				$excerpt    = array(
					'hits'  => $count_matches,
					'text'  => trim( $excerpt_slice ),
					'start' => $start,
				);
				$excerpts[] = $excerpt;
			}
		}
		++$tries;

		/**
		 * Enables the excerpt optimization.
		 *
		 * If your posts are very long, building excerpts can be really slow.
		 * To speed up the process, you can enable optimization, which means
		 * Relevanssi only creates 50 excerpt candidates.
		 *
		 * @param boolean Return true to enable optimization, default false.
		 */
		if ( apply_filters( 'relevanssi_optimize_excerpts', false ) ) {
			if ( $tries > 50 ) {
				// An optimization trick: try only 50 times.
				break;
			}
		}

		$offset += $excerpt_length + $gap;
	}

	if ( empty( $excerpts ) && $gap > 0 ) {
		$result = relevanssi_get_first_match( $words, $terms, $excerpt_length );

		if ( ! empty( $result['excerpt'] ) ) {
			$excerpts[] = array(
				'text'  => $result['excerpt'],
				'hits'  => $result['best_excerpt_term_hits'],
				'start' => $result['start'],
			);
		}
	}

	if ( empty( $excerpts ) ) {
		/**
		 * Nothing found, take the beginning of the post. +2, because the first
		 * index is an empty space and the last index is the rest of the post.
		 */
		$words = explode( ' ', $content, $excerpt_length + 2 );
		array_pop( $words );
		$text       = implode( ' ', $words );
		$excerpt    = array(
			'text'  => $text,
			'hits'  => 0,
			'start' => true,
		);
		$excerpts[] = $excerpt;
	}

	return $excerpts;
}

/**
 * Adds the "source" attribute to the excerpts in the array.
 *
 * @param array  $excerpts The excerpts array, passed as a reference.
 * @param string $source   The source value to add to the excerpts.
 */
function relevanssi_add_source_to_excerpts( &$excerpts, $source ) {
	if ( ! is_array( $excerpts ) ) {
		return;
	}
	array_walk(
		$excerpts,
		function ( &$item ) use ( $source ) {
			$item['source'] = $source;
		}
	);
}

/**
 * Combines multiple arrays of excerpts together, sorts them and slices them.
 *
 * Returns a slice with the n excerpts with most term hits.
 *
 * @param int   $post_id            The current post ID.
 * @param array ...$excerpt_sources Arrays containing excerpts.
 *
 * @return array An array containing the n best excerpts.
 */
function relevanssi_combine_excerpts( $post_id, ...$excerpt_sources ) {
	$excerpts = array_merge( ...$excerpt_sources );
	usort(
		$excerpts,
		function ( $a, $b ) {
			return $b['hits'] - $a['hits'];
		}
	);
	$number_of_excerpts = get_option( 'relevanssi_max_excerpts', 1 );

	$excerpts_with_hits = array_filter(
		$excerpts,
		function ( $excerpt ) {
			return $excerpt['hits'] > 0;
		}
	);

	if ( count( $excerpts_with_hits ) > 0 ) {
		$excerpts = $excerpts_with_hits;
	}

	$excerpts = array_slice(
		/**
		 * Filters the excerpt.
		 *
		 * Filters the post excerpts generated by Relevanssi before the
		 * highlighting is applied.
		 *
		 * @param array $excerpt  An array of excerpts.
		 * @param int   $post->ID The post ID.
		 */
		apply_filters( 'relevanssi_excerpts', $excerpts, $post_id ),
		0,
		$number_of_excerpts
	);

	return $excerpts;
}
