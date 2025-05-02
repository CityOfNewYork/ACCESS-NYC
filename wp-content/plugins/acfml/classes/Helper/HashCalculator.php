<?php

namespace ACFML\Helper;

use WPML\FP\Obj;

class HashCalculator {

	/**
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function calculate( $value ) {
		$value = self::normalize( $value );

		if ( is_string( $value ) ) {
			return self::hash( $value );
		} elseif ( is_numeric( $value ) ) {
			return self::hash( (string) $value );
		} elseif ( is_array( $value ) && self::getID( $value ) ) {
			return self::hash( (string) self::getID( $value ) );
		} elseif ( ! $value ) { // Empty field(image, repeater, etc.).
			return '';
		}

		return self::hashArray( $value );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	private static function hash( $value ) {
		return md5( $value );
	}

	/**
	 * @param array $value
	 *
	 * @return int|null
	 */
	private static function getID( array $value ) {
		return Obj::prop( 'ID', $value );
	}

	/**
	 * @param array $array
	 *
	 * @return string
	 */
	private static function hashArray( $array ) {
		return self::isArrayOfArrays( $array ) ? self::hashArrayOfArrays( $array ) : self::hashAssociativeArray( $array );
	}

	/**
	 * @param array $array
	 *
	 * @return bool
	 */
	private static function isArrayOfArrays( $array ) {
		$intIndexArrayValue = function( $val, $index ) {
			return is_int( $index ) && is_array( $val );
		};

		return count( $array ) === wpml_collect( $array )->filter( $intIndexArrayValue )->count();
	}

	/**
	 * @param array $array
	 *
	 * @return string
	 */
	private static function hashArrayOfArrays( $array ) {
		$hashes = wpml_collect( $array )->map( [ self::class, 'calculate' ] )->toArray();
		sort( $hashes ); // even if rows are reordered, we'll get the correct hash.
		return self::hash( implode( $hashes ) );
	}

	/**
	 * @param array $array
	 *
	 * @return string
	 */
	private static function hashAssociativeArray( $array ) {
		ksort( $array );
		$hashes = wpml_collect( $array )->map( [ self::class, 'calculate' ] )->toArray();
		return self::hash( implode( $hashes ) );
	}

	/**
	 * @param mixed $value
	 *
	 * @return array|int|mixed
	 */
	public static function normalize( $value ) {
		if ( is_bool( $value ) ) {
			$value = (int) $value;
		} elseif ( is_object( $value ) ) {
			$value = (array) $value;
		}

		return $value;
	}
}
