<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use phpDocumentor\Reflection\DocBlock\Tags\See;
use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;

class WpAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wp';
	const ACTION_ARGS = 0;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	/** @var UrlRepositoryInterface */
	private $urlRepository;

	public function __construct(
		SettingsRepositoryInterface $settingsRepository,
		UrlRepositoryInterface      $urlRepository
	) {
		$this->settingsRepository = $settingsRepository;
		$this->urlRepository      = $urlRepository;
	}

	protected function onAction( ...$args ) {
		if (
			$this->urlRepository->isFrontendRequest() &&
			// Probably this method should be renamed to more generic now as it is used also for this notice.
			! $this->settingsRepository->shouldNotAutoregisterStringsFromCurrentUrl() &&
			! $this->settingsRepository->hasKeyInSettings( SettingsRepositoryInterface::WAS_FRONTEND_VISITED_KEY )
		) {
			$this->settingsRepository->saveKeyToSettings( SettingsRepositoryInterface::WAS_FRONTEND_VISITED_KEY );
		}
	}
}
