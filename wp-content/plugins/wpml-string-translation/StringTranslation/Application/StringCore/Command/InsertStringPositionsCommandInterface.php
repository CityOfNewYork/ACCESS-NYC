<?php

namespace WPML\StringTranslation\Application\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringPosition;

interface InsertStringPositionsCommandInterface {
	/**
	 * @param StringPosition[] $positions
	 */
	public function run( array $positions );
}