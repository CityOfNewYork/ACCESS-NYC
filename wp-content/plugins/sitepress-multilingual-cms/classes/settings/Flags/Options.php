<?php

namespace WPML\TM\Settings\Flags;

use WPML\FP\Either;
use WPML\FP\Lst;
use WPML\FP\Fns;
use WPML\LIB\WP\Option;
use function WPML\Container\make;

class Options {
	const FORMAT_OPTION = 'wpml_flags_format';

	/**
	 * @param string $format One of the values defined in the `getAllowedTypes` method.
	 *
	 * @return Either
	 */
	public static function saveFormat( $format ) {
		return Either::of( $format )
		             ->filter( Lst::includes( Fns::__, self::getAllowedFormats() ) )
		             ->map( Fns::tap( function ( $format ) {
			             Option::update( self::FORMAT_OPTION, $format );
		             } ) );
	}

	public static function getFormat() {
		/** @var \WPML\TM\Settings\Flags\FlagsRepository */
		$repo   = make( \WPML\TM\Settings\Flags\FlagsRepository::class );
		$notset = 'notset';
		$res    = Option::getOr( self::FORMAT_OPTION, $notset );

		if ( $res !== $notset ) {
			return $res;
		}

		$formats = self::getAllowedFormats();

		return ( $repo->hasSvgFlags() ) ? $formats[1] : $formats[0];
	}

	public static function getAllowedFormats() {
		return [ 'png', 'svg' ];
	}
}