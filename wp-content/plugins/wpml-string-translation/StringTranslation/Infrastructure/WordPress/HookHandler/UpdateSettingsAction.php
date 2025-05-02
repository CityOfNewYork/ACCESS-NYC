<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;
use WPML\StringTranslation\Application\Setting\Repository\UrlRepositoryInterface;

class UpdateSettingsAction extends AbstractActionHookHandler {
	const ACTION_NAME = 'wpml_st_update_settings';
	const ACTION_ARGS = 3;

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
		$type = $args[0];

		if ( $type === 'updateUserCache' ) {
			$this->settingsRepository->updateIfIsCurrentUserAdminCache();
		} else if( $type === 'deleteCache' ) {
			$this->settingsRepository->deleteCache();
		} else if ( $type === 'enableAutoregistration' ) {
			$this->settingsRepository->setIsAutoregistrationEnabled( true );
		} else if ( $type === 'disableAutoregistration' ) {
			$this->settingsRepository->setIsAutoregistrationEnabled( false );
		} else if ( $type === 'setMaxQueuedFrontendStringsCount' ) {
			$this->settingsRepository->setMaxQueuedFrontendStringsCount( $args[1] );
		} else if ( $type === 'enableStringTracking' ) {
			$this->settingsRepository->enableStringTracking();
		} else if ( $type === 'disableStringTracking' ) {
			$this->settingsRepository->disableStringTracking();
		} else if ( $type === 'setLanguageForDomain' ) {
			if ( count( $args ) >= 3 ) {
				$domain = $args[1];
				$language = $args[2];
				if (
					is_string( $domain ) &&
					is_string( $language ) &&
					strlen( $domain ) > 0 &&
					strlen( $language ) > 0
				) {
					$this->settingsRepository->setLanguageForDomain( $domain, $language );
				}
			}
		}
	}
}
