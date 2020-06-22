<?php

namespace WPML\FP\Functor;

class ConstFunctor {
	use Functor;

	public function map( $callback ) {
		return $this;
	}
}