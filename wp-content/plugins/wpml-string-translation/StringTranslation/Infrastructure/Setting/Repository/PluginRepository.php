<?php

namespace WPML\StringTranslation\Infrastructure\Setting\Repository;

use WPML\StringTranslation\Application\Setting\Repository\PluginRepositoryInterface;
use \WPML\StringTranslation\Application\Setting\Repository\SettingsRepositoryInterface;

class PluginRepository implements PluginRepositoryInterface {

	const ACTIVE_CACHE_PLUGIN_ID_SETTINGS_KEY = 'active_cache_plugin_id';
	const ACTIVE_CACHE_PLUGIN_NAME_SETTINGS_KEY = 'active_cache_plugin_name';
	const IS_NOTICE_THAT_CACHE_PLUGIN_CAN_BLOCK_AUTOREGISTER_DISMISSED_SETTINGS_KEY = 'is_notice_that_cache_plugin_can_block_autoregister_dismissed';

	private $cachePluginFilepathes = [
		'wp-rocket/wp-rocket.php',
		'w3-total-cache/w3-total-cache.php',
		'wp-super-cache/wp-cache.php',
		'litespeed-cache/litespeed-cache.php',
		'breeze/breeze.php',
		'sg-cachepress/sg-cachepress.php',
		'comet-cache/comet-cache.php',
		'hyper-cache/plugin.php',
	];

	/** @var SettingsRepositoryInterface */
	private $settingsRepository;

	public function __construct(
		SettingsRepositoryInterface $settingsRepository
	) {
		$this->settingsRepository = $settingsRepository;
	}

	private function isAnyCachePluginActive(): bool {
		foreach ( $this->cachePluginFilepathes as $cachePluginFilepath ) {
			if ( is_plugin_active( $cachePluginFilepath ) ) {
				return true;
			}
		}

		return false;
	}

	private function readActiveCachePluginName( string $cachePluginFilepath ): string {
		$pluginIdKey = self::ACTIVE_CACHE_PLUGIN_ID_SETTINGS_KEY;
		$pluginNameKey = self::ACTIVE_CACHE_PLUGIN_NAME_SETTINGS_KEY;

		if ( $this->settingsRepository->hasKeyInSettings( $pluginNameKey ) ) {
			$pluginId = $this->settingsRepository->getKeyValueFromSettings( $pluginIdKey );
			$pluginName = $this->settingsRepository->getKeyValueFromSettings( $pluginNameKey );

			if ( $pluginId === $cachePluginFilepath ) {
				return $pluginName;
			}
		}

		$data = get_plugin_data( WPML_PLUGINS_DIR . '/' . $cachePluginFilepath, false, false );
		$pluginName = $data['Name'] ?? '';
		$this->settingsRepository->saveKeyToSettings( $pluginIdKey, $cachePluginFilepath );
		$this->settingsRepository->saveKeyToSettings( $pluginNameKey, $pluginName );

		return $pluginName;
	}

	public function getActiveCachePluginName(): string {
		foreach ( $this->cachePluginFilepathes as $cachePluginFilepath ) {
			if ( is_plugin_active( $cachePluginFilepath ) ) {
				return $this->readActiveCachePluginName( $cachePluginFilepath );
			}
		}

		return '';
	}

	public function shouldShowNoticeThatCachePluginCanBlockAutoregister(): bool {
		$key = self::IS_NOTICE_THAT_CACHE_PLUGIN_CAN_BLOCK_AUTOREGISTER_DISMISSED_SETTINGS_KEY;
		if ( $this->settingsRepository->hasKeyInSettings( $key ) ) {
			return false;
		}

		return $this->isAnyCachePluginActive();
	}

	public function setNoticeThatCachePluginCanBlockAutoregisterAsDismissed() {
		$key = self::IS_NOTICE_THAT_CACHE_PLUGIN_CAN_BLOCK_AUTOREGISTER_DISMISSED_SETTINGS_KEY;
		$this->settingsRepository->saveKeyToSettings( $key );
	}
}