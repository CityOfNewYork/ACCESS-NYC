<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

abstract class AbstractActionHookHandler implements HookHandlerInterface {

	const ACTION_NAME = '';
	const ACTION_PRIORITY = 10;
	const ACTION_ARGS = -1;

	abstract protected function onAction( ...$args );

	public function load() {
		add_action( static::ACTION_NAME, function( ...$args) {
			return $this->onAction( ...$args );
		}, static::ACTION_PRIORITY, static::ACTION_ARGS );
	}
}