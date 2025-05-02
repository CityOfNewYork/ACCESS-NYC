<?php

namespace WPML\StringTranslation\Application\StringGettext\Command;

interface ProcessPendingStringsCommandInterface {
	public function run( array $allPendingStrings ): bool;
}