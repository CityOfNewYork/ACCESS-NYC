<?php

namespace WPML\StringTranslation\Application\StringGettext\Service;

use WPML\StringTranslation\Application\StringGettext\Repository\QueueRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\StringCore\Repository\TranslationsRepositoryInterface;
use WPML\StringTranslation\Application\StringGettext\Command\ProcessPendingStringsCommandInterface;
use WPML\StringTranslation\Application\StringGettext\Validator\IsExcludedDomainStringValidatorInterface;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;
use WPML\FP\Str;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;

class GettextStringsService {

	/** @var IsExcludedDomainStringValidatorInterface */
	private $isExcludedDomainStringValidator;

	/** @var TranslationsRepositoryInterface  */
	private $translationsRepository;

	/** @var QueueRepositoryInterface  */
	private $queueRepository;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var ProcessPendingStringsCommandInterface */
	private $processPendingStrings;

	/** @var UrlRepositoryInterface */
	private $urlRepository;

	/**
	 * This is required to prevent endless loops - if we will call some function from WordPress core from inside of the
	 * queueStringAsPendingIfUntranslatedOrNotTracked or queueCustomStringAsPending function it can launch
	 * some hook and some plugin can listen for this hook and require translation again which will get us here again.
	 * Example:
	 *   /StringTranslation/Application/StringGettext/Service/GettextStringsService.php
	 *       public function isAutoregisterEnabled()
	 *           if ( $this->settingsRepository->isAutoregisterStringsTypeOnlyViewedByAdmin() && ! $this->settingsRepository->getIsCurrentUserAdmin() )
	 *   getIsCurrentUserAdmin will call current_user_can which calls WP_User has_cap fn. Than function fires 'user_has_cap' hook and for example
	 *   in 'types-access' plugin for Toolset we listen for that hook and require translation again 'title' => __( 'Read post', 'wpcf-access' ),
	 *   thus it will lead to the endless loop. So, we can split all translation function calls into 2 types - 'internal' and 'external'.
	 *   'External' are the ones which are called not starting from the function calls of this class and 'internal' are ones which are called
	 *   starting from the functions from this class. We should register external ones and ignore internal to avoid endless loops.
	 *
	 * @var bool Flag to prevent infinite recursion during string processing.
	 */
	private $isProcessingString = false;

	public function __construct(
		IsExcludedDomainStringValidatorInterface $isExcludedDomainStringValidator,
		TranslationsRepositoryInterface $translationsRepository,
		QueueRepositoryInterface $queueRepository,
		SettingsRepositoryInterface $settingsRepository,
		ProcessPendingStringsCommandInterface $processPendingStringsCommand,
		UrlRepositoryInterface $urlRepository
	) {
		$this->isExcludedDomainStringValidator = $isExcludedDomainStringValidator;
		$this->translationsRepository          = $translationsRepository;
		$this->queueRepository                 = $queueRepository;
		$this->settingsRepository              = $settingsRepository;
		$this->processPendingStrings           = $processPendingStringsCommand;
		$this->urlRepository                   = $urlRepository;
	}

	public function isAutoregisterEnabled(): bool {
		// This check should always be first, at least before getIsCurrentUserAdmin which calls
		// internally current_user_can. It should be allowed only after functions in WP core were
		// fully initialised to avoid errors.
		if ( ! $this->settingsRepository->getIsAutoregistrationEnabled() ) {
			return false;
		}

		if ( $this->settingsRepository->isAutoregisterStringsTypeDisabled() ) {
			return false;
		}

		if ( $this->settingsRepository->isAutoregisterStringsTypeOnlyViewedByAdmin() && ! $this->settingsRepository->getIsCurrentUserAdmin() ) {
			return false;
		}

		return true;
	}

	/**
	 * Queue a string as pending if it's not translated or not tracked
	 *
	 * @param string $text The text to queue.
	 * @param string $domain The domain of the text.
	 * @param string $context Optional context for the text.
	 *
	 * @return string The original text
	 */
	public function queueStringAsPendingIfUntranslatedOrNotTracked( $text, $domain, $context = '' ) {
		if ( $this->isProcessingString ) {
			return $text;
		}

		$this->isProcessingString = true;

		if (
			! $this->isAutoregisterEnabled() ||
			$this->settingsRepository->shouldNotAutoregisterStringsFromCurrentUrl() ||
			strlen( $text ) === 0 ||
			$this->isExcludedDomainStringValidator->validate( $text, $domain ) ||
			$this->translationsRepository->isTranslationAvailable( $text, $domain, $context )
		) {
			$this->isProcessingString = false;
			return $text;
		}

		$this->queueRepository->addCurrentUrlString( $text, $domain, $context );
		$requestUrl = $this->urlRepository->getClientFrontendRequestUrl();

		if ( $this->queueRepository->isStringAlreadyRegistered( $text, $domain, $context ) ) {
			$this->maybeTrackString( $text, $domain, $requestUrl, $context );
			$this->isProcessingString = false;
			return $text;
		}

		$wasQueued = $this->queueRepository->queueStringAsPending( $text, $domain, $context );
		if ( $wasQueued ) {
			$this->queueRepository->trackString( $text, $domain, $requestUrl, $context );
		}
		$this->isProcessingString = false;

		return $text;
	}

	/**
	 * Queue a custom string as pending
	 *
	 * @param string $text The text to queue.
	 * @param string $domain The domain of the text.
	 * @param string $context The context of the text.
	 * @param string $name The name of the string.
	 *
	 * @return string
	 */
	public function queueCustomStringAsPending( $text, $domain, $context, $name ) {
		if ( $this->isProcessingString ) {
			return $text;
		}

		$this->isProcessingString = true;

		if (
			$this->settingsRepository->isAutoregisterStringsTypeDisabled() ||
			$this->settingsRepository->shouldNotAutoregisterStringsFromCurrentUrl() ||
			strlen( $text ) === 0 ||
			$this->isExcludedDomainStringValidator->validate( $text, $domain )
		) {
			$this->isProcessingString = false;
			return $text;
		}

		$this->queueRepository->addCurrentUrlString( $text, $domain, $context );
		$requestUrl = $this->urlRepository->getClientFrontendRequestUrl();

		if ( $this->queueRepository->isStringAlreadyRegistered( $text, $domain, $context, $name ) ) {
			$this->maybeTrackString( $text, $domain, $requestUrl, $context );
			$this->isProcessingString = false;
			return $text;
		}

		$wasQueued = $this->queueRepository->queueStringAsPending( $text, $domain, $context, $name );
		if (
			$wasQueued &&
			! $this->queueRepository->isStringAlreadyTrackedOnUrl( $text, $domain, $requestUrl, $context )
		) {
			$this->queueRepository->trackString( $text, $domain, $requestUrl, $context );
		}

		$this->isProcessingString = false;
		return $text;
	}

	private function maybeTrackString( string $text, string $domain, string $requestUrl, ?string $context = null ) {
		if (
			$this->queueRepository->isStringAlreadyTrackedOnUrl( $text, $domain, $requestUrl, $context ) ||
			! $this->queueRepository->canTrackString( $text, $domain, $context )
		) {
			return;
		}

		$this->queueRepository->trackString( $text, $domain, $requestUrl, $context );
	}

	/**
	 * Save the pending strings queue
	 *
	 * @return void
	 */
	public function savePendingStringsQueue() {
		if ( ! $this->isAutoregisterEnabled() ) {
			return;
		}

		$this->queueRepository->savePendingStringsQueue();
	}

	/**
	 * Process saved pending strings and settings queue
	 *
	 * @return void
	 */
	public function processSavedPendingStringsAndSettingsQueue() {
		$hasCompleted = $this->processPendingStrings->run( $this->queueRepository->loadPendingStrings() );
		if ( $hasCompleted ) {
			$this->queueRepository->markPendingStringsAsProcessed();
		}
	}
}

