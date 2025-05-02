<?php

namespace WPML\StringTranslation\Application\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

interface InsertStringTranslationsCommandInterface {
	/**
	 * @param StringItem[] $strings
	 */
	public function run( array $strings );
}