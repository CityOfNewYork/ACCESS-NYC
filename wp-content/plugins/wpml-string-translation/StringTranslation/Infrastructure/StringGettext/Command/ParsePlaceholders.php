<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

class ParsePlaceholders {

	public function run( string $string ): array {
		$placeholders = [];

		$res = preg_match_all( "/%%/", $string, $matches, PREG_OFFSET_CAPTURE );
		if ( $res !== false ) {
			foreach ( $matches[0] as $matchData ) {
				$matchText   = $matchData[0];
				$matchOffset = $matchData[1];

				$placeholders[] = [
					'type'      => 'placeholder',
					'text'      => $matchText,
					'offset'    => $matchOffset,
					'offsetEnd' => $matchOffset + strlen( $matchText ) - 1,
				];
			}
		}

		$string = str_replace( '%%', '__', $string );
		if ( strstr( $string, '%' ) === false ) {
			return [];
		}

		$matches = array();
		$res     = preg_match_all( "/%(\N*)([bcdeEfFgGhHosuxX])/sU", $string, $matches, PREG_OFFSET_CAPTURE );
		if ( $res === false ) {
			return [];
		}

		foreach ( $matches[0] as $matchData ) {
			$matchText   = $matchData[0];
			$matchOffset = $matchData[1];

			$placeholders[] = [
				'type'      => 'placeholder',
				'text'      => $matchText,
				'offset'    => $matchOffset,
				'offsetEnd' => $matchOffset + strlen( $matchText ) - 1,
			];
		}

		return $placeholders;
	}
}