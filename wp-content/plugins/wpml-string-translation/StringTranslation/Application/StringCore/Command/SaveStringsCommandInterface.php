<?php

namespace WPML\StringTranslation\Application\StringCore\Command;

interface SaveStringsCommandInterface {
	public function run( array $strings );
}