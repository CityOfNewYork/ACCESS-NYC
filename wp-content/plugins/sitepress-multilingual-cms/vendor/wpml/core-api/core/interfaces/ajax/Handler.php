<?php

namespace WPML\Ajax;

use WPML\Collect\Support\Collection;

interface IHandler {
	/**
	 * @param \WPML\Collect\Support\Collection<mixed> $data
	 *
	 * @return \WPML\FP\Either
	 */
	public function run( Collection $data );
}
