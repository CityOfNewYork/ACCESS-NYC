<?php

namespace WPML\StringTranslation\Infrastructure\StringGettext\Command;

use WPML\StringTranslation\Application\Setting\Repository\FilesystemRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Command\DeletePendingStringsCommandInterface;
use WPML\StringTranslation\Infrastructure\Factory;

class DeletePendingStringsCommand implements DeletePendingStringsCommandInterface {

	/** @var Factory */
	private $factory;

	public function __construct(
		Factory $factory
	) {
		$this->factory = $factory;
	}

	public function run( string $domain ) {
		$this->factory->getDeletePendingStringsCommand()->run( $domain );
	}
}