<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

abstract class AbstractFilterHookHandler implements HookHandlerInterface {

	const FILTER_NAME = '';
	const FILTER_ARGS = 1;
	const FILTER_PRIORITY = 10;

	abstract protected function onFilter( ...$args );

	public function load() {
		add_filter( static::FILTER_NAME, function( ...$args) {
			return $this->onFilter( ...$args );
		}, static::FILTER_PRIORITY, static::FILTER_ARGS );
	}
}