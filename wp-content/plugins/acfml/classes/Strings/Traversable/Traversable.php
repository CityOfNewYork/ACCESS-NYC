<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Transformer\Transformer;

interface Traversable {

	/**
	 * @param Transformer $transformer
	 * @param string|null $context
	 *
	 * @return mixed
	 */
	public function traverse( Transformer $transformer, $context = null );
}
