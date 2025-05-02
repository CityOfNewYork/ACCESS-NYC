<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\StringGettext\Command\SavePendingStringsCommandInterface;
use WPML\StringTranslation\Infrastructure\Factory;

class SavePendingStringsCommand implements SavePendingStringsCommandInterface {

	/** @var Factory */
	private $factory;

	public function __construct(
		Factory $factory
	) {
		$this->factory = $factory;
	}

	public function run( string $domain, array $strings ) {
		$this->factory->getSavePendingStringsCommand()->run( $domain, $strings );
	}
}