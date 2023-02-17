<?php

namespace WPML\FP;

trait ConstApplicative {

	/**
	 * @param mixed $otherContainer
	 *
	 * @return mixed
	 */
	public function ap( $otherContainer ) {
		return $this;
	}
}
