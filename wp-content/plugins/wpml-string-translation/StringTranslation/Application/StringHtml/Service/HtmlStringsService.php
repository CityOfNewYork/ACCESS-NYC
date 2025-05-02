<?php

namespace WPML\StringTranslation\Application\StringHtml\Service;

use WPML\StringTranslation\Application\StringHtml\Repository\HtmlStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Repository\JsonStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Command\ProcessFrontendGettextStringsQueueInterface;
use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Repository\GettextStringsRepositoryInterface;
use WPML\StringTranslation\Application\StringHtml\Command\QueueGettextStringsToBeSetAsFrontendCommandInterface;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class HtmlStringsService {

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var HtmlStringsRepositoryInterface */
	private $htmlStringsRepository;

	/** @var JsonStringsRepositoryInterface */
	private $jsonStringsRepository;

	/** @var QueueRepositoryInterface */
	private $queueRepository;

	/** @var GettextStringsRepositoryInterface */
	private $gettextStringsRepository;

	/** @var QueueGettextStringsToBeSetAsFrontendCommandInterface */
	private $queueGettextStringsToBeSetAsFrontendCommand;

	/** @var ProcessFrontendGettextStringsQueueInterface */
	private $processFrontendGettextStringsQueueCommand;

	public function __construct(
		SettingsRepositoryInterface                          $settingsRepository,
		HtmlStringsRepositoryInterface                       $htmlStringsRepository,
		JsonStringsRepositoryInterface                       $jsonStringsRepository,
		QueueRepositoryInterface                             $queueRepository,
		GettextStringsRepositoryInterface                    $gettextStringsRepository,
		QueueGettextStringsToBeSetAsFrontendCommandInterface $queueGettextStringsToBeSetAsFrontendCommand,
		ProcessFrontendGettextStringsQueueInterface          $processFrontendGettextStringsQueueCommand
	) {
		$this->settingsRepository                          = $settingsRepository;
		$this->htmlStringsRepository                       = $htmlStringsRepository;
		$this->jsonStringsRepository                       = $jsonStringsRepository;
		$this->queueRepository                             = $queueRepository;
		$this->gettextStringsRepository                    = $gettextStringsRepository;
		$this->queueGettextStringsToBeSetAsFrontendCommand = $queueGettextStringsToBeSetAsFrontendCommand;
		$this->processFrontendGettextStringsQueueCommand   = $processFrontendGettextStringsQueueCommand;
	}

	public function startCapturingBuffer() {
		ob_start( [ $this, 'readHtmlFromBuffer' ] );
	}

	public function readHtmlFromBuffer( $output ) {
		if ( ( wpml_is_ajax() || wp_is_json_request() ) ) {
			$htmlStrings = [];
			if ( $this->settingsRepository->getShouldRegisterBackendStringsSetting() ) {
				$htmlStrings = $this->jsonStringsRepository->getAllStringsFromOutput( (string) $output );
			}
		} else {
			$htmlStrings = $this->extractStringsFromHtml( (string) $output );
		}

		$this->queueGettextStringsEqualToHtmlStringsAsFrontend( $htmlStrings );

		return $output;
	}

	private function extractStringsFromHtml( string $output ): array {
		return $this->htmlStringsRepository->getAllStringsFromHtml( $output );
	}

	public function queueGettextStringsEqualToHtmlStringsAsFrontend( array $htmlStrings ) {
		$gettextStrings = $this->queueRepository->getCurrentUrlStrings();
		$gettextStrings = $this->gettextStringsRepository->filterOnlyGettextStringsThatMatchesHtmlStrings( $gettextStrings, $htmlStrings );
		$this->queueGettextStringsToBeSetAsFrontendCommand->run( $gettextStrings );
	}

	public function maybeProcessFrontendGettextStringsQueue() {
		$this->processFrontendGettextStringsQueueCommand->run();
	}
}