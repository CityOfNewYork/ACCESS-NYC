<?php

namespace WPML\FP\Functor;

class ConstFunctor {
	use Functor;
	use Pointed;

	/**
	 * @param callable $callback
	 *
	 * @return ConstFunctor
	 */
	public function map( $callback ) {
		return $this;
	}
}
