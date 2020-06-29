<?php

namespace WPML\FP;

trait Applicative {

	public function ap( $otherContainer ) {
		return $otherContainer->map( $this->value );
	}
}
