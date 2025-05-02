<?php

namespace WPML\StringTranslation\Application\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

interface UpdateStringsCommandInterface {
	/**
	 * @param StringItem[] $strings
	 */
	public function run( array $strings, array $fields, array $values );
}