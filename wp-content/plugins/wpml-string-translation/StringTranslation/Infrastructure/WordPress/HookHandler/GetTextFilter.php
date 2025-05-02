<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;
use WPML\StringTranslation\Application\WordPress\HookHandler\AutoregisterHookInterface;

class GetTextFilter extends AbstractFilterHookHandler implements AutoregisterHookInterface {
	const FILTER_NAME = 'gettext';
	const FILTER_ARGS = 3;
	const FILTER_PRIORITY = 10;

	/** @var GettextStringsService */
	private $gettextStringsService;

	public function __construct(
		GettextStringsService $gettextStringsService
	) {
		$this->gettextStringsService = $gettextStringsService;
	}

	protected function onFilter( ...$args ) {
		if ( count( $args ) === 3 ) {
			list( $translation, $text, $domain ) = $args;
		} else {
			list ( $translation, $text ) = $args;
			$domain = 'default';
		}

		if ( $translation === $text ) {
			return $this->gettextStringsService->queueStringAsPendingIfUntranslatedOrNotTracked( $text, $domain );
		}

		return $translation;
	}

	// Used to simulate gettext call from plugin from tests.
	public static function callTranslateFromPlugin( $text, $domain ) {
		__( $text, $domain );
	}
}
