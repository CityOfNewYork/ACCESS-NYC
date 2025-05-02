<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;
use WPML\StringTranslation\Application\WordPress\HookHandler\AutoregisterHookInterface;

class NGetTextFilter extends AbstractFilterHookHandler implements AutoregisterHookInterface {
	const FILTER_NAME = 'ngettext';
	const FILTER_ARGS = 5;
	const FILTER_PRIORITY = 9;

	/** @var GettextStringsService */
	private $gettextStringsService;

	public function __construct( GettextStringsService $gettextStringsService ) {
		$this->gettextStringsService = $gettextStringsService;
	}

	protected function onFilter( ...$args ) {
		if ( count( $args ) === 5 ) {
			list( $translation, $single, $plural, $number, $domain ) = $args;
		} else {
			list ( $translation, $single, $plural, $number ) = $args;
			$domain = 'default';
		}

		if ( (int) $number === 1 ) {
			$text = $single;
		} else {
			$text = $plural;
		}

		if ( $translation === $text ) {
			return $this->gettextStringsService->queueStringAsPendingIfUntranslatedOrNotTracked( $text, $domain );
		}

		return $translation;
	}
}
