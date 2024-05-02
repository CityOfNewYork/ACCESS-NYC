<?php
/**
 * /premium/class-spellcorrector.php
 *
 * @package Relevanssi_Premium
 * @author  Felipe Ribeiro <felipernb@gmail.com> with modifications by Mikko Saari
 * @see     https://www.relevanssi.com/
 */

/*
***************************************************************************
*   Copyright (C) 2008 by Felipe Ribeiro                                  *
*   felipernb@gmail.com                                                   *
*   http://www.feliperibeiro.com                                          *
*                                                                         *
*   Permission is hereby granted, free of charge, to any person obtaining *
*   a copy of this software and associated documentation files (the       *
*   "Software"), to deal in the Software without restriction, including   *
*   without limitation the rights to use, copy, modify, merge, publish,   *
*   distribute, sublicense, and/or sell copies of the Software, and to    *
*   permit persons to whom the Software is furnished to do so, subject to *
*   the following conditions:                                             *
*                                                                         *
*   The above copyright notice and this permission notice shall be        *
*   included in all copies or substantial portions of the Software.       *
*                                                                         *
*   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,       *
*   EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF    *
*   MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.*
*   IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR     *
*   OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, *
*   ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR *
*   OTHER DEALINGS IN THE SOFTWARE.                                       *
***************************************************************************
*/

/**
 * Spell correcting feature.
 *
 * This class implements the Spell correcting feature, useful for the
 * "Did you mean" functionality on the search engine. Using a dictionary of
 * words extracted from the product catalog.
 *
 * Based on the concepts of Peter Norvig: http://norvig.com/spell-correct.html
 *
 * @author Felipe Ribeiro <felipernb@gmail.com>
 * @date September 18th, 2008
 */
class Relevanssi_SpellCorrector {
	/**
	 * Dictionary of words.
	 *
	 * @var array $dictionary Array of words, containing the dictionary.
	 */
	private static $dictionary;

	/**
	 * Reads a text and extracts the list of words.
	 *
	 * @param string $text Source text for the words.
	 * @return array The list of words
	 */
	private static function words( $text ) {
		$matches = array();
		$text    = relevanssi_strtolower( $text );
		preg_match_all( '/[a-z]+/', $text, $matches );
		return $matches[0];
	}

	/**
	 * Generates a list of possible "disturbances" on the passed string.
	 *
	 * @param string $word Word to disturb.
	 *
	 * @return array A list of variations.
	 */
	private static function edits1( $word ) {
		/**
		 * Filters the alphabet used for Did you mean suggestions.
		 *
		 * In order to use the Did you mean suggestions with non-Latin alphabets
		 * (or even European languages with a wider range of characters than
		 * English), Relevanssi needs to be provided with the alphabet used.
		 *
		 * @param string A string containing the alphabet as a string of
		 * characters without spaces.
		 */
		$alphabet = apply_filters(
			'relevanssi_didyoumean_alphabet',
			'abcdefghijklmnopqrstuvwxyzäöåü'
		);
		$alphabet = preg_split( '/(?<!^)(?!$)/u', $alphabet );
		$n        = relevanssi_strlen( $word );
		$edits    = array();

		$substr_function = 'substr';
		if ( function_exists( 'mb_substr' ) ) {
			$substr_function = 'mb_substr';
		}
		for ( $i = 0; $i < $n; $i++ ) {
			// Removing one letter.
			$edits[] = call_user_func( $substr_function, $word, 0, $i )
				. call_user_func( $substr_function, $word, $i + 1 );

			// Substituting one letter.
			foreach ( $alphabet as $c ) {
				$edits[] = call_user_func( $substr_function, $word, 0, $i )
					. $c . call_user_func( $substr_function, $word, $i + 1 );
			}
		}
		for ( $i = 0; $i < $n - 1; $i++ ) {
			// Swapping character order.
			$edits[] = call_user_func( $substr_function, $word, 0, $i )
				. $word[ $i + 1 ] . $word[ $i ]
				. call_user_func( $substr_function, $word, $i + 2 );
		}

		// Inserting one character.
		for ( $i = 0; $i < $n + 1; $i++ ) {
			foreach ( $alphabet as $c ) {
				$edits[] = call_user_func( $substr_function, $word, 0, $i )
					. $c . call_user_func( $substr_function, $word, $i );
			}
		}

		return $edits;
	}

	/**
	 * Generate possible "disturbances" in a second level that exist on the
	 * dictionary.
	 *
	 * @param string $word Word to disturb.
	 *
	 * @return array Known disturbances.
	 */
	private static function known_edits2( $word ) {
		$known = array();
		foreach ( self::edits1( $word ) as $e1 ) {
			foreach ( self::edits1( $e1 ) as $e2 ) {
				if ( array_key_exists( $e2, self::$dictionary ) ) {
					$known[] = $e2;
				}
			}
		}
		return $known;
	}

	/**
	 * Given a list of words, returns the subset that is present on the
	 * dictionary.
	 *
	 * @param array $words Array of words to check.
	 * @return array Words that are in the dictionary.
	 */
	private static function known( array $words ) {
		$known = array();
		foreach ( $words as $w ) {
			if ( array_key_exists( $w, self::$dictionary ) ) {
				$known[] = $w;
			}
		}
		return $known;
	}

	/**
	 * Corrects the word.
	 *
	 * Returns the word that is present on the dictionary that is the most
	 * similar (and the most relevant) to the word passed as parameter.
	 *
	 * @param string $word Word to correct.
	 *
	 * @return string Correction suggestion, null if nothing found.
	 */
	public static function correct( $word ) {
		$word = trim( $word );

		if ( empty( $word ) ) {
			return null;
		}

		$word = relevanssi_strtolower( $word );

		if ( empty( self::$dictionary ) ) {
			self::$dictionary = relevanssi_get_words();
		}

		$candidates = array();
		if ( self::known( array( $word ) ) ) {
			// Word is in the dictionary. It's fine.
			return true;
		} else {
			$tmp_candidates = self::known( self::edits1( $word ) );
			if ( ! empty( $tmp_candidates ) ) {
				foreach ( $tmp_candidates as $candidate ) {
					$candidates[] = $candidate;
				}
			} else {
				$tmp_candidates = self::known_edits2( $word );
				if ( ! empty( $tmp_candidates ) ) {
					foreach ( $tmp_candidates as $candidate ) {
						$candidates[] = $candidate;
					}
				} else {
					return null;
				}
			}
		}
		$max = 0;

		foreach ( $candidates as $c ) {
			$value = self::$dictionary[ $c ];
			if ( $value > $max ) {
				$max  = $value;
				$word = $c;
			}
		}
		return $word;
	}
}
