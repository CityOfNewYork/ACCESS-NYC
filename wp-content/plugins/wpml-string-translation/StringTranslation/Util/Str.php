<?php

namespace WPML\StringTranslation;

class Str {
	public static function insertTextAt( string $original, int $index, string $substring ): string {
		if ( $index > strlen($original) ) {
			$index = strlen($original);
		} elseif ( $index < 0 ) {
			$index = 0;
		}

		$part1 = substr( $original, 0, $index );
		$part2 = substr( $original, $index );

		$newString = $part1 . $substring . $part2;

		return $newString;
	}

	public static function removeTextAt( string $original, int $index, int $length ): string {
		if ( $index > strlen( $original ) ) {
			$index = strlen( $original );
		} elseif ($index < 0) {
			$index = 0;
		}

		if ( $index + $length > strlen( $original ) ) {
			$length = strlen( $original ) - $index;
		}

		$part1 = substr( $original, 0, $index );
		$part2 = substr( $original, $index + $length );

		$newString = $part1 . $part2;

		return $newString;
	}

	public static function matchWithPositions( string $str, string $pattern ): array {
		preg_match_all( $pattern, $str, $matches, PREG_OFFSET_CAPTURE );
		$res = array_map(function( $match ) {
			return $match[0];
		}, $matches[0]);
		$positions = array_map(function( $match ) {
			return $match[1];
		}, $matches[0]);

		return [ $res, $positions ];
	}
}