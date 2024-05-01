<?php

namespace OTGS\Installer\FP\Traits;

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