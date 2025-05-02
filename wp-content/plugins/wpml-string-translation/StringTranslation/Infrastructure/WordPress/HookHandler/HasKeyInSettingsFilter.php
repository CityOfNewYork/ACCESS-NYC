<?php
namespace WPML\StringTranslation\Infrastructure\WordPress\HookHandler;

use WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class HasKeyInSettingsFilter extends AbstractFilterHookHandler {
	const FILTER_NAME = 'wpml_st_has_key_in_settings';
	const FILTER_ARGS = 1;

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct(
		SettingsRepositoryInterface $settingsRepository
	) {
		$this->settingsRepository  = $settingsRepository;
	}

	protected function onFilter( ...$args ) {
		list( $keyName ) = $args;

		return $this->settingsRepository->hasKeyInSettings( $keyName );
	}
}
