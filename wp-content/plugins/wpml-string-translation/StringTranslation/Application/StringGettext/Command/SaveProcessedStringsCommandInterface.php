<?php

namespace WPML\StringTranslation\Application\StringGettext\Command;

interface SaveProcessedStringsCommandInterface {
	public function run( string $domain, array $strings );
}