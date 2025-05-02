<?php

namespace WPML\StringTranslation\Infrastructure\StringHtml\Validator;

use WPML\StringTranslation\Application\StringHtml\Validator\IsExcludedHtmlStringValidatorInterface;

class IsExcludedHtmlStringValidator implements IsExcludedHtmlStringValidatorInterface {

	public function validate( string $text = null ): bool {
		if ( is_null( $text ) ) {
			return false;
		}

		$len = strlen( $text );
		if ( $len === 0 || is_numeric( $text ) ) {
			return false;
		}

		$wordsCount = count( explode( ' ', $text ) );
		if ( $wordsCount === 1 && $len > 50 ) {
			return false;
		}

		$digitsCount  = preg_match_all( "/[0-9]/", $text );
		$lettersCount = $len - $digitsCount;

		// Filtering out identifiers.
		if ( $wordsCount === 1 && $digitsCount >= $lettersCount ) {
			return false;
		}

		// Filtering out srcsets from page builders.
		if (
			$wordsCount > 1 &&
			(
				substr( $text, 0, 7 ) === 'http://' ||
				substr( $text, 0, 8 ) === 'https://'
			)
		) {
			return false;
		}

		// Page builders are outputting long json content.
		// In the same time we should not block short strings like '{site_title} — Built with {WooCommerce}'.
		$firstChar = substr( $text, 0,1);
		$lastChar  = substr( $text, -1 );
		if ( $firstChar === '{' && $lastChar === '}' && $len > 50 ) {
			return false;
		}

		return true;
	}
}