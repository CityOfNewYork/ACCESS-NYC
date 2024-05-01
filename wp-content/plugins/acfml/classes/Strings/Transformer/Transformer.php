<?php

namespace ACFML\Strings\Transformer;

interface Transformer {

	/**
	 * @param string $value
	 * @param array  $stringData
	 *
	 * @return string
	 */
	public function transform( $value, $stringData );
}
