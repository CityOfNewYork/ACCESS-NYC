<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class GetSettingFilter extends AbstractFilterHookHandler {
	const FILTER_NAME = 'wpml_st_get_setting';
	const FILTER_ARGS = 1;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct(
		SettingsRepositoryInterface $settingsRepository
	) {
		$this->settingsRepository  = $settingsRepository;
	}

	protected function onFilter( ...$args ) {
		list( $settingName ) = $args;

		if ( $settingName === 'autoregisterStringsTypeOnlyViewedByAdmin' ) {
			return SettingsRepositoryInterface::AUTOREGISTER_STRINGS_TYPE_ONLY_VIEWED_BY_ADMIN;
		}
		if ( $settingName === 'autoregisterStringsTypeViewedByAllUsers' ) {
			return SettingsRepositoryInterface::AUTOREGISTER_STRINGS_TYPE_VIEWED_BY_ALL_USERS;
		}
		if ( $settingName === 'autoregisterStringsTypeDisabled' ) {
			return SettingsRepositoryInterface::AUTOREGISTER_STRINGS_TYPE_DISABLED;
		}
		if ( $settingName === 'isAutoregisterStringsTypeOnlyViewedByAdmin' ) {
			return $this->settingsRepository->isAutoregisterStringsTypeOnlyViewedByAdmin();
		}
		if ( $settingName === 'isAutoregisterStringsTypeViewedByAllUsers' ) {
			return $this->settingsRepository->isAutoregisterStringsTypeViewedByAllUsers();
		}
		if ( $settingName === 'isAutoregisterStringsTypeDisabled' ) {
			return $this->settingsRepository->isAutoregisterStringsTypeDisabled();
		}
		if ( $settingName === 'shouldRegisterBackendStrings' ) {
			return $this->settingsRepository->getShouldRegisterBackendStringsSetting();
		}

	}
}
