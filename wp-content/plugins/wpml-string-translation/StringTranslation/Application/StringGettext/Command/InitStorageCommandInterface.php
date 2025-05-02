<?php

namespace WPML\StringTranslation\Application\StringGettext\Command;

interface InitStorageCommandInterface {
	public function run( string $domain );
}