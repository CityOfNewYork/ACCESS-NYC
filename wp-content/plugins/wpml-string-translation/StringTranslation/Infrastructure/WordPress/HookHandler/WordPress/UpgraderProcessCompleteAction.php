<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\WordPress;

use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;
use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsForAllStringsCommandInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class UpgraderProcessCompleteAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'upgrader_process_complete';
	const ACTION_ARGS = 2;

	/** @var LoadExistingStringTranslationsForAllStringsCommandInterface */
	private $loadExistingStringTranslationsForAllStringsCommand;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct(
		LoadExistingStringTranslationsForAllStringsCommandInterface $loadExistingStringTranslationsForAllStringsCommand,
		SettingsRepositoryInterface $settingsRepository
	) {
		$this->loadExistingStringTranslationsForAllStringsCommand = $loadExistingStringTranslationsForAllStringsCommand;
		$this->settingsRepository                                 = $settingsRepository;
	}

	protected function onAction( ...$args ) {
		$res = $args[1];
		if ( ! isset( $res['action'] ) || ! isset( $res['type'] ) ) {
			return;
		}

		if ( $res['action'] !== 'update' || $res['type'] !== 'translation' ) {
			return;
		}

		$this->loadExistingStringTranslationsForAllStringsCommand->run();
		$this->settingsRepository->setNewTranslationsWereLoadedSetting();
		$this->settingsRepository->removeKeyFromSettings( SettingsRepositoryInterface::WAS_FRONTEND_VISITED_KEY );
	}
}
