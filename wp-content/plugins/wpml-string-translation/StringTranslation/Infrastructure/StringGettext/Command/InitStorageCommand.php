<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\StringGettext\Command\InitStorageCommandInterface;
use WPML\StringTranslation\Infrastructure\Factory;

class InitStorageCommand implements InitStorageCommandInterface {

	/** @var Factory */
	private $factory;

	public function __construct(
		Factory $factory
	) {
		$this->factory = $factory;
	}

	public function run( string $domain ) {
		$this->factory->getInitStorageCommand()->run( $domain );
	}
}