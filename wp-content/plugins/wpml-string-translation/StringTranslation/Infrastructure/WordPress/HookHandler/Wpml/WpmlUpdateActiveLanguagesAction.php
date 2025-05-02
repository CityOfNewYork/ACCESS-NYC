<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\Wpml;

use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;
use WPML\StringTranslation\Application\StringCore\Command\LoadExistingStringTranslationsForAllStringsCommandInterface;

class WpmlUpdateActiveLanguagesAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'icl_update_active_languages';
	const ACTION_ARGS = 1;

	/** @var LoadExistingStringTranslationsForAllStringsCommandInterface */
	private $loadExistingStringTranslationsForAllStringsCommand;

	public function __construct(
		LoadExistingStringTranslationsForAllStringsCommandInterface $loadExistingStringTranslationsForAllStringsCommand
	) {
		$this->loadExistingStringTranslationsForAllStringsCommand = $loadExistingStringTranslationsForAllStringsCommand;
	}

	protected function onAction( ...$args ) {
		$this->loadExistingStringTranslationsForAllStringsCommand->run();
	}
}
