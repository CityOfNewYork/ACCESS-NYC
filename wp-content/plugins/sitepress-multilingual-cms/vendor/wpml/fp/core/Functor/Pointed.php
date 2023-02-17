<?php

namespace WPML\FP\Functor;

use function WPML\FP\curryN;

trait Pointed {

	/**
	 * of :: a -> M a
	 *
	 * Curried function that returns an instance of the derived class
	 * @param mixed $value (optional)
	 *
	 * @return mixed|callable
	 */
	public static function of( $value = null ) {
		$of = function( $value ) { return new static( $value ); };

		return call_user_func_array( curryN(1, $of ), func_get_args() );

	}

}
