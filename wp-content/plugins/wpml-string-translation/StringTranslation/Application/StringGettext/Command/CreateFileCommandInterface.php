<?php

namespace WPML\StringTranslation\Application\StringGettext\Command;

interface CreateFileCommandInterface {
	public function run( array $queue, string $filepath );
}