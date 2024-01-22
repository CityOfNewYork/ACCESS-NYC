<?php

namespace WPML\FP\Monoid;

use WPML\FP\Fns;
use function WPML\FP\curryN;

abstract class Monoid {

	public static function concat( $a = null, $b = null ) {
		return call_user_func_array( curryN( 2, [ static::class, '_concat' ] ), func_get_args() );
	}

	public static function of( array $array = null ) {
		return call_user_func_array( Fns::reduce( self::concat(), static::mempty() ), func_get_args() );
	}
}
