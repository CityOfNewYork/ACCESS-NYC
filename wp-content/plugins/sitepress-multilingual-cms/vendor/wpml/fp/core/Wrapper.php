<?php

namespace WPML\FP;

use WPML\FP\Functor\Functor;
use WPML\FP\Functor\Pointed;

class Wrapper {
	use Functor;
	use Pointed;

	/**
	 * @param callable $fn
	 *
	 * @return Wrapper
	 */
	public function map( callable $fn ) {
		return self::of( $fn( $this->value ) );
	}

	/**
	 * @param callable $fn
	 *
	 * @return mixed|null
	 */
	public function filter( $fn = null ) {
		$fn = $fn ?: Fns::identity();
		return $fn( $this->value ) ? $this->value : null;
	}

	/**
	 * @return mixed
	 */
	public function join() {
		if( ! $this->value instanceof Wrapper ) {
			return $this->value;
		}
		return $this->value->join();
	}

	/**
	 * @param mixed $value
	 *
	 * @return Wrapper
	 */
	public function ap( $value ) {
		return self::of( call_user_func( $this->value, $value ) ); // $this->value should be callable and curried
	}

	/**
	 * @return mixed
	 */
	public function get() {
		return $this->value;
	}

}
