<?php

namespace WPML\FP;

use WPML\Collect\Support\Collection;
use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable|array|null toArray(string ...$str) - Curried :: json -> array
 * @method static callable|Collection|null toCollection(string ...$str) Curried :: json -> null | Collection
 */
class Json {

	use Macroable;

	public static function init() {
		self::macro( 'toArray', curryN( 1, function ( $str ) {
			return json_decode( $str, true );
		} ) );

		self::macro( 'toCollection', curryN( 1, function ( $str ) {
			return Maybe::of( $str )
			            ->map( 'stripslashes' )
			            ->map( self::toArray() )
			            ->map( 'wpml_collect' )
			            ->getOrElse( null );
		} ) );
	}
}

Json::init();
