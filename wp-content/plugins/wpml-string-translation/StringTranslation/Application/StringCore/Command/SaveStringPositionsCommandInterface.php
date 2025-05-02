<?php

namespace WPML\StringTranslation\Application\StringCore\Command;

interface SaveStringPositionsCommandInterface {
	public function run( array $strings );
}