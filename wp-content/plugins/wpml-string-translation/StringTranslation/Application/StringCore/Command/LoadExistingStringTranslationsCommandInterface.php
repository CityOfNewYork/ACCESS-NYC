<?php

namespace WPML\StringTranslation\Application\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

interface LoadExistingStringTranslationsCommandInterface {

	/**
	 * @param StringItem[] $strings
	 */
	public function run( array $strings );
}