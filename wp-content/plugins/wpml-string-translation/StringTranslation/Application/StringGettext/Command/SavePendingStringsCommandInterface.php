<?php

namespace WPML\StringTranslation\Application\StringGettext\Command;

interface SavePendingStringsCommandInterface {
	public function run( string $domain, array $strings );
}