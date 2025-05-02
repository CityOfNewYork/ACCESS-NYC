<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\StringCore\Service\StringsService;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;
use WPML\FP\Str;
use WPML\StringTranslation\Application\StringHtml\Service\HtmlStringsService;
use WPML\StringTranslation\Application\StringGettext\Service\GettextStringsService;
use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\Utilities\Lock;
use function WPML\Container\make;

class InitAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'init';
	const ACTION_ARGS = 0;
	const ACTION_PRIORITY = 0;

	/** @var StringsService */
	private $stringsService;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var UrlRepositoryInterface */
	private $urlRepository;

	/** @var HtmlStringsService */
	private $htmlStringsService;

	/** @var GettextStringsService */
	private $gettextStringsService;

	public function __construct(
		StringsService              $stringsService,
		SettingsRepositoryInterface $settingsRepository,
		UrlRepositoryInterface      $urlRepository,
		HtmlStringsService          $htmlStringsService,
		GettextStringsService       $gettextStringsService
	) {
		$this->stringsService        = $stringsService;
		$this->settingsRepository    = $settingsRepository;
		$this->urlRepository         = $urlRepository;
		$this->htmlStringsService    = $htmlStringsService;
		$this->gettextStringsService = $gettextStringsService;
	}

	protected function onAction( ...$args ) {
		/**
		 * We should ensure that current_user_can function has been executed fully at least once
		 * before enabling autoregistration hooks. Otherwise some functions in WP Core
		 * can throw some errors, for example like wp_salt function.
		 */
		$this->settingsRepository->updateIfIsCurrentUserAdminCache();

		// It is important to enable autoregistration only here.
		// Otherwise session can be not started yet on a few system gettext calls.
		// That can cause incorrect data initialisation from session.
		$this->settingsRepository->setIsAutoregistrationEnabled( true );

		if ( $this->settingsRepository->canProcessQueueInCurrentRequest() ) {
			$lock    = make( Lock::class, [ ':name' => 'processstringsqueue' ] );
			$hasLock = $lock->create( 60 );

			if ( $hasLock ) {
				$this->stringsService->maybeProcessQueue();
				$this->htmlStringsService->maybeProcessFrontendGettextStringsQueue();
				$lock->release();
			}
		}

		if (
			$this->gettextStringsService->isAutoregisterEnabled() &&
			! $this->settingsRepository->shouldNotAutoregisterStringsFromCurrentUrl()
		) {
			$this->htmlStringsService->startCapturingBuffer();
		}
	}
}
