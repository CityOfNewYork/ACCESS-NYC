<?php

namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\TranslateEverything;


use WPML\StringTranslation\Infrastructure\TranslateEverything\UntranslatedStrings;
use WPML\StringTranslation\Infrastructure\TranslateEverything\UntranslatedStringsFactory;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractFilterHookHandler;

class AddUntranslatedStringsStrategyFilter extends AbstractFilterHookHandler {

	const FILTER_NAME     = 'wpml_translate_everything_untranslated_elements_strategies';
	const FILTER_ARGS     = 1;
	const FILTER_PRIORITY = 10;

	/** @var UntranslatedStrings|null */
	private $untranslatedStringsStrategy;

	protected function onFilter( ...$args ) {
		$strategies = $args[0];

		return array_merge( $strategies, [ $this->getUntranslatedStringsStrategy() ] );
	}

	private function getUntranslatedStringsStrategy(): UntranslatedStrings {
		if ( ! $this->untranslatedStringsStrategy ) {
			$this->untranslatedStringsStrategy = ( new UntranslatedStringsFactory() )->create();
		}

		return $this->untranslatedStringsStrategy;
	}
}
