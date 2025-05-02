<?php

namespace WPML\StringTranslation\Infrastructure;

use WPML\Auryn\Injector;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\QueueStorageInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Repository\QueuePhpFileStorage;
use WPML\StringTranslation\Application\StringGettext\Command\InitStorageCommandInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\InitStoragePhpCommand;
use WPML\StringTranslation\Application\StringGettext\Command\DeletePendingStringsCommandInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\DeletePendingStringsPhpCommand;
use WPML\StringTranslation\Application\StringGettext\Command\SavePendingStringsCommandInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\SavePendingStringsPhpCommand;
use WPML\StringTranslation\Application\StringGettext\Command\SaveProcessedStringsCommandInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Command\SaveProcessedStringsPhpCommand;
use WPML\StringTranslation\Application\StringGettext\Repository\FrontendQueueRepositoryInterface;
use WPML\StringTranslation\Infrastructure\StringGettext\Repository\FrontendQueueJsonRepository;

class Factory {

	/** @var Injector */
	private $injector;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var array */
	private $instances = [];

	public function __construct(
		Injector $injector
	) {
		$this->injector = $injector;
	}

	private function getBySetting( $forOnlyViewedByAdmin, $forAllUsers ) {
		if ( is_null( $this->settingsRepository ) ) {
			$this->settingsRepository = $this->injector->make( SettingsRepositoryInterface::class );
		}

		return $this->settingsRepository->isAutoregisterStringsTypeOnlyViewedByAdmin()
			? $forOnlyViewedByAdmin
			: $forAllUsers;
	}

	private function make( $className ) {
		if ( isset( $this->instances[ $className ] ) ) {
			return $this->instances[ $className ];
		}

		$this->instances[ $className ] = $this->injector->make( $className );

		return $this->instances[ $className ];
	}

	public function getGettextStringsQueueStorage(): QueueStorageInterface {
		return $this->getBySetting(
			$this->make( QueuePhpFileStorage::class ),
			$this->make( QueuePhpFileStorage::class )
		);
	}

	public function getInitStorageCommand(): InitStorageCommandInterface {
		return $this->getBySetting(
			$this->make( InitStoragePhpCommand::class ),
			$this->make( InitStoragePhpCommand::class )
		);
	}

	public function getDeletePendingStringsCommand(): DeletePendingStringsCommandInterface {
		return $this->getBySetting(
			$this->make( DeletePendingStringsPhpCommand::class ),
			$this->make( DeletePendingStringsPhpCommand::class )
		);
	}

	public function getSavePendingStringsCommand(): SavePendingStringsCommandInterface {
		return $this->getBySetting(
			$this->make( SavePendingStringsPhpCommand::class ),
			$this->make( SavePendingStringsPhpCommand::class )
		);
	}

	public function getSaveProcessedStringsCommand(): SaveProcessedStringsCommandInterface {
		return $this->getBySetting(
			$this->make( SaveProcessedStringsPhpCommand::class ),
			$this->make( SaveProcessedStringsPhpCommand::class )
		);
	}

	public function getFrontendQueueRepository(): FrontendQueueRepositoryInterface {
		return $this->getBySetting(
			$this->make( FrontendQueueJsonRepository::class ),
			$this->make( FrontendQueueJsonRepository::class )
		);
	}
}