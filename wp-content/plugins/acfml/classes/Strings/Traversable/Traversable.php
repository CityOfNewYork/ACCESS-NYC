<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Transformer\Transformer;

interface Traversable {

	/**
	 * @param Transformer $transformer
	 *
	 * @return mixed
	 */
	public function traverse( Transformer $transformer );
}
