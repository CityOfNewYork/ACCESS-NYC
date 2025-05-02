<?php

namespace WPML\StringTranslation\Application\StringCore\Service;

use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;

class StringsService {

	/** @var GettextStringsService */
	private $gettextStringsService;

	public function __construct(
		GettextStringsService  $gettextStringsService
	) {
		$this->gettextStringsService = $gettextStringsService;
	}

	public function maybeProcessQueue() {
		if ( ! $this->gettextStringsService->isAutoregisterEnabled() ) {
			return;
		}

		$this->gettextStringsService->processSavedPendingStringsAndSettingsQueue();
	}
}