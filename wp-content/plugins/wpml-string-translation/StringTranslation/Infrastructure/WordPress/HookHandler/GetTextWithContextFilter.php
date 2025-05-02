<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;
use WPML\StringTranslation\Application\WordPress\HookHandler\AutoregisterHookInterface;

class GetTextWithContextFilter extends AbstractFilterHookHandler implements AutoregisterHookInterface {
	const FILTER_NAME = 'gettext_with_context';
	const FILTER_ARGS = 4;
	const FILTER_PRIORITY = 10;

	/** @var GettextStringsService */
	private $gettextStringsService;

	public function __construct(
		GettextStringsService $gettextStringsService
	) {
		$this->gettextStringsService = $gettextStringsService;
	}

	protected function onFilter( ...$args ) {
		if ( count( $args ) === 4 ) {
			list( $translation, $text, $context, $domain ) = $args;
		} else {
			list( $translation, $text, $context ) = $args;
			$domain = 'default';
		}

		if ( $translation === $text ) {
			return $this->gettextStringsService->queueStringAsPendingIfUntranslatedOrNotTracked( $text, $domain, $context );
		}

		return $translation;
	}

	// Used to simulate gettext call from plugin from tests.
	public static function callTranslateFromPlugin( $text, $domain, $context ) {
		//do_action( 'gettext', $translation, $text, $domain );
		_x( $text, $context, $domain );
	}
}
