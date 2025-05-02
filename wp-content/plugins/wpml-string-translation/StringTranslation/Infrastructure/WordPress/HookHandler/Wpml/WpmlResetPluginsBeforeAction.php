<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\Wpml;

use WPML\StringTranslation\Application\StringGettext\Command\ClearAllStoragesCommandInterface;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class WpmlResetPluginsBeforeAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_reset_plugins_before';
	const ACTION_ARGS = 0;

	/** @var ClearAllStoragesCommandInterface */
	private $clearAllStorages;

	public function __construct(
		ClearAllStoragesCommandInterface $clearAllStorages
	) {
		$this->clearAllStorages = $clearAllStorages;
	}

	protected function onAction( ...$args ) {
		$this->clearAllStorages->run();
	}
}
