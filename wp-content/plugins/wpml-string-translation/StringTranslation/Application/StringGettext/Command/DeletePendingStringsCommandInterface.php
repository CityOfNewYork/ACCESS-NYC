<?php

namespace WPML\StringTranslation\Application\StringGettext\Command;

interface DeletePendingStringsCommandInterface {
	public function run( string $domain );
}