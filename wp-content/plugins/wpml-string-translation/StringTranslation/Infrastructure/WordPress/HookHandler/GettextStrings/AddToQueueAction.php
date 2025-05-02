<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler\GettextStrings;

use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;
use WPML\StringTranslation\Infrastructure\WordPress\HookHandler\AbstractActionHookHandler;

class AddToQueueAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_add_to_queue';
	const ACTION_ARGS = 4;

	/** @var GettextStringsService */
	private $gettextStringsService;

	public function __construct(
		GettextStringsService $gettextStringsService
	) {
		$this->gettextStringsService = $gettextStringsService;
	}

	protected function onAction( ...$args ) {
		list( $value, $domain, $context, $name ) = $args;
		$this->gettextStringsService->queueCustomStringAsPending( $value, $domain, $context, $name );
	}
}
