<?php

namespace WPML\Convert;

use WPML\FP\Fns;
use WPML\FP\Lens;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\FP\Type;
use function WPML\FP\compose;
use function WPML\FP\pipe;

class Ids {

	const ANY_POST = 'any_post';
	const ANY_TERM = 'any_term';

	/**
	 * @param int|string|array|mixed $ids
	 * @param string|null            $elementType
	 * @param bool                   $fallbackToOriginal
	 * @param string|null            $targetLang
	 *
	 * @return callable|mixed|null
	 */
	public static function convert( $ids, $elementType = null, $fallbackToOriginal = false, $targetLang = null ) {
		$isId = function( $id ) {
			return is_numeric( $id ) && ! is_float( $id );
		};

		$getElementType = self::selectGetElementType( $elementType );

		/**
		 * @param int|string|mixed $id
		 *
		 * @return int|string|null|mixed
		 */
		$convertId = function( $id ) use ( $isId, $getElementType, $fallbackToOriginal, $targetLang ) {
			/** @var \SitePress $sitepress */
			global $sitepress;

			if ( ! $isId( $id ) ) {
				return $id;
			}

			$convertedId = $sitepress->get_object_id( $id, $getElementType( (int) $id ), $fallbackToOriginal, $targetLang );

			if ( $convertedId ) {
				return is_string( $id ) ? (string) $convertedId : $convertedId;
			}

			return null;
		};

		try {
			return $isId( $ids )
				? $convertId( $ids )
				: Obj::over( self::selectLens( $ids ), $convertId, $ids );
		} catch ( \Exception $e ) {
			return $ids;
		}
	}

	/**
	 * In case of multiple IDs in the same conversion, we'll consider
	 * all the IDs part of the same family, and we'll try to retrieve
	 * the family from the first ID only (for performance reasons).
	 *
	 * @param string|null $elementType
	 *
	 * @return callable int|string -> string
	 */
	private static function selectGetElementType( $elementType ) {
		$memorize = function( $getElementType ) {
			return Fns::memorizeWith( Fns::always( 'same' ), $getElementType );
		};

		if ( self::ANY_POST === $elementType ) {
			return $memorize( 'get_post_type' );
		} elseif ( self::ANY_TERM === $elementType ) {
			return $memorize( pipe( 'get_term', Obj::prop( 'taxonomy' ) ) );
		}

		return Fns::always( $elementType );
	}

	/**
	 * We'll use lenses when we possibly have multiple IDs
	 * inside the data (passed in different formats).
	 *
	 * @param int|string|array|mixed $ids
	 *
	 * @return callable
	 */
	private static function selectLens( $ids ) {
		$getLensFilteredMapped = function() {
			return compose( Lens::iso( 'array_filter', 'array_filter' ), Obj::lensMapped() );
		};

		if ( is_array( $ids ) ) {
			return $getLensFilteredMapped();
		} elseif ( Type::isSerialized( $ids ) ) {
			return compose( Lens::isoUnserialized(), $getLensFilteredMapped() );
		} elseif ( Type::isJson( $ids ) ) {
			return compose( Lens::isoJsonDecoded(), $getLensFilteredMapped() );
		} elseif ( is_string( $ids ) && $glue = self::guessGlue( $ids ) ) { // phpcs:ignore
			return compose( Lens::iso( Str::split( $glue ), Lst::join( $glue ) ), $getLensFilteredMapped() );
		}

		// Noop lens, always set the same as original.
		return Lens::iso( Fns::always( $ids ), Fns::always( $ids ) );
	}

	/**
	 * Finds the unique separator pattern between IDs or return false otherwise.
	 *
	 * @param string $string
	 *
	 * @return false|string
	 */
	public static function guessGlue( $string ) {
		preg_match_all( '/[^0-9]+/', $string, $matches );
		$uniqueGlues = array_unique( $matches[0] );

		if ( count( $uniqueGlues ) !== 1 ) {
			return false;
		}

		$glue = $uniqueGlues[0];

		if ( $glue === $string ) {
			return false;
		}

		return $glue;
	}
}
