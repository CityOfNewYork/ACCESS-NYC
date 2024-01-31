<?php

namespace WPML\ST\StringsFilter;

use WPML\ST\StringsFilter\StringEntity;

/**
 * This storage in used internally in "Translations" class. Unfortunately, I cannot use anonymous classes due to PHP Version limitation.
 */
class TranslationsObjectStorage extends \SplObjectStorage {
	/**
	 * @param StringEntity $o
	 *
	 * @return string
	 */
	#[\ReturnTypeWillChange]
	public function getHash( $o ) {
		return implode(
			'_',
			[
				$o->getValue(),
				$o->getName(),
				$o->getDomain(),
				$o->getContext(),
			]
		);
	}
}
