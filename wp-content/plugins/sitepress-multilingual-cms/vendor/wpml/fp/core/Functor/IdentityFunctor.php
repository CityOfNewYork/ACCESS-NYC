<?php

namespace WPML\FP\Functor;

class IdentityFunctor {
	use Functor;

	public function map( $callback ) {
		return new self( $callback( $this->get() ) );
	}
}