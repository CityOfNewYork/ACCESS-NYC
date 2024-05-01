<?php

namespace OTGS\Installer\Recommendations;

use OTGS_Installer_Subscription;

class RecommendationsManager {
	/**
	 * @var \OTGS_Installer_Repositories
	 */
	private $repositories;

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @var array
	 */
	private $repositoriesForRecommendation = [ 'wpml' ];

	/**
	 * @var Storage
	 */
	private $noticesStorage;

	/**
	 * RecommendationsManager constructor.
	 *
	 * @param \OTGS_Installer_Repositories $repositories
	 * @param array $settings
	 * @param Storage $settings
	 */
	public function __construct( \OTGS_Installer_Repositories $repositories, $settings, Storage $noticesStorage ) {
		$this->repositories   = $repositories;
		$this->settings       = $settings;
		$this->noticesStorage = $noticesStorage;
	}

	public function addHooks() {
		add_action( 'activated_plugin', [ $this, 'activatedPluginRecommendation' ] );
		add_action( 'deactivated_plugin', [ $this, 'deactivatedPluginRecommendation' ] );
		add_action( 'wp_ajax_installer_recommendation_success', [ $this, 'recommendationSuccess' ] );

		add_filter( 'wpml_installer_get_stored_recommendation_notices', [ $this, 'getRecommendationStoredNotices' ] );
	}

	public function activatedPluginRecommendation( $plugin ) {
		$pluginData = $this->getPluginData( $plugin );
		$gluePluginData = $pluginData->getGluePluginData();
		if ( $gluePluginData && ! $this->isGluePluginActive( $gluePluginData['glue_plugin_slug'] ) ) {
			$this->noticesStorage->save( $pluginData->getPluginSlug(), $gluePluginData );
		}
	}

	private function isGluePluginActive( $gluePluginSlug ) {
		$pluginData = isset( $this->getInstalledPlugins()[ $gluePluginSlug ] ) ? $this->getInstalledPlugins()[ $gluePluginSlug ] : null;

		return $pluginData && $pluginData['is_active'];
	}

	public function deactivatedPluginRecommendation( $plugin ) {
		$deactivatedPlugin = $this->getPluginData( $plugin );
		$gluePluginData = $deactivatedPlugin->getGluePluginData();
		if ( $gluePluginData ) {
			$this->noticesStorage->delete( $deactivatedPlugin->getPluginSlug(), $gluePluginData['repository_id'] );
		}
	}

	public function recommendationSuccess() {
		if ( array_key_exists( 'nonce', $_POST )
		     && array_key_exists( 'pluginData', $_POST )
		     && wp_verify_nonce( $_POST['nonce'], 'recommendation_success_nonce' ) ) {
			$data = json_decode( base64_decode( sanitize_text_field( $_POST['pluginData'] ) ), true );
			$this->noticesStorage->delete( $data['slug'], $data['repository_id'] );
		}

	}

	/**
	 * @param string $activatedPluginSlug
	 *
	 * @return array|null
	 */
	private function getActivatedPluginGlue( $activatedPluginSlug ) {
		$language = $this->getCurrentLanguage();

		foreach ( $this->repositoriesForRecommendation as $repositoryId ) {
			$downloads = isset( $this->settings[ $repositoryId ]['data']['downloads']['plugins'] )
				? $this->settings[ $repositoryId ]['data']['downloads']['plugins'] : [];
			foreach ( $downloads as $pluginData ) {
				$gluePluginSlug = isset( $pluginData['glue_check_slug'] ) ? $pluginData['glue_check_slug'] : false;
				if ( $gluePluginSlug && $activatedPluginSlug === $pluginData['glue_check_slug'] ) {

					return $this->prepareRecommendedPluginData( $repositoryId, $pluginData, $language );

				}
			}
		}

		return null;
	}

	private function getGluePluginData( $gluePluginSlug, $mappingData ) {

		$language = $this->getCurrentLanguage();

		foreach ( $this->repositoriesForRecommendation as $repositoryId ) {
			$downloads = isset( $this->settings[ $repositoryId ]['data']['downloads']['plugins'] )
				? $this->settings[ $repositoryId ]['data']['downloads']['plugins'] : [];
			if ( isset( $downloads[ $gluePluginSlug ] ) ) {
				$pluginData = $downloads[ $gluePluginSlug ];

				return $this->prepareRecommendedPluginData( $repositoryId, $pluginData, $language, $mappingData );
			}
		}

		return null;
	}

	private function getNotificationForLanguage( $pluginData, $language ) {
		$default = isset( $pluginData['recommendation_notification']['en'] ) ? $pluginData['recommendation_notification']['en'] : '';

		return isset( $pluginData['recommendation_notification'][ $language ] )
			? $pluginData['recommendation_notification'][ $language ]
			: $default;
	}

	/**
	 * @return array
	 */
	public function getRepositoryPluginsRecommendations() {
		$pluginsRecommendations = [];
		$pluginsData            = [];
		$language               = $this->getCurrentLanguage();

		foreach ( $this->repositoriesForRecommendation as $repositoryId ) {
			$repository = $this->repositories->get( $repositoryId );

			if ( $this->settings[ $repositoryId ]['data']['downloads']['plugins']
			     && $this->settings[ $repositoryId ]['data']['recommendation_sections'] ) {
				$downloads = $this->settings[ $repositoryId ]['data']['downloads']['plugins'];
				$sections  = $this->settings[ $repositoryId ]['data']['recommendation_sections'];
			} else {
				continue;
			}

			$subscription = $repository->get_subscription();
			if ( ! $subscription ) {
				continue;
			}

			$available_plugins_list = $this->getAvailablePluginsForSubscription( $repository );
			$installedPlugins       = $this->getInstalledPlugins();

			foreach ( $downloads as $pluginData ) {
				if ( $pluginData['recommended']
				     && in_array( $pluginData['slug'], $available_plugins_list, true )
				     && $this->shouldBeDisplayed( $pluginData )
				) {
					$isInstalled = isset( $installedPlugins[ $pluginData['slug'] ] );
					$isActive    = $isInstalled && $installedPlugins[ $pluginData['slug'] ]['is_active'];

					if ( ! $isInstalled || ! $isActive ) {
						$recommendation = $this->preparePluginData(
							$language,
							$pluginData,
							$subscription->get_site_key(),
							$repositoryId,
							$subscription->get_site_url(),
							$isInstalled,
							$isActive
						);

						$sectionPlugin = $this->prepareSectionPlugin(
							$language,
							$pluginData,
							$isInstalled,
							$isActive
						);

						if (
							array_key_exists( 'download_recommendation_section', $pluginData ) &&
							is_string( $pluginData['download_recommendation_section'] )
						) {
							$pluginsRecommendations[ $pluginData['download_recommendation_section'] ]['plugins'][ $pluginData['slug'] ] = $sectionPlugin;
							$pluginsData[ $pluginData['slug'] ]                                                                         = $recommendation;
						}
					}
				}
			}

			$recommendationsForInstallerPlugins = $this->prepareRecommendationsForInstalledPlugins( $repositoryId, $subscription, $downloads, $installedPlugins, $pluginsRecommendations, $pluginsData );
			$pluginsData = $recommendationsForInstallerPlugins->getPluginsData();

			foreach ( $recommendationsForInstallerPlugins->getRecommendations() as $section => $plugins_recommendation ) {
				// Use current site lang if available, otherwise 'en'.
				$lang = array_key_exists( $language, $sections[ $section ] )
					? $language
					: 'en';

				$pluginsRecommendations[ $section ]['title'] = $sections[ $section ][ $lang ]['name'];
				$pluginsRecommendations[ $section ]['order'] = $sections[ $section ][ $lang ]['order'];
			}
		}

		uasort( $pluginsRecommendations, function ( $a, $b ) {
			return (int) $a['order'] - (int) $b['order'];
		} );

		return [ 'sections' => $pluginsRecommendations, 'plugins' => $pluginsData ];
	}

	/**
	 * @param string $repositoryId
	 * @param OTGS_Installer_Subscription $subscription
	 * @param array $downloads
	 * @param array $installedPlugins
	 * @param array $pluginsRecommendations
	 * @param array $pluginsData
	 *
	 * @return RecommendationsForInstallerPlugins
	 */
	private function prepareRecommendationsForInstalledPlugins( $repositoryId, OTGS_Installer_Subscription $subscription, $downloads, $installedPlugins, $pluginsRecommendations, $pluginsData ) {
		$language = $this->getCurrentLanguage();

		if ( isset($this->settings[ $repositoryId ]['data']['glue_plugins_mapping']) ) {
			$gluePluginsMapping = $this->settings[ $repositoryId ]['data']['glue_plugins_mapping'];
		} else {
			return new RecommendationsForInstallerPlugins( $pluginsRecommendations, $pluginsData );
		}

		foreach ( $installedPlugins as $pluginSlug => $pluginData ) {

			if ( isset( $gluePluginsMapping[ $pluginSlug ] ) ) {
				$gluePluginSlug = $gluePluginsMapping[ $pluginSlug ]['glue_plugin'];
				$gluePluginData = $this->getGluePluginData( $gluePluginSlug, $gluePluginsMapping[ $pluginSlug ] );

				if ( $gluePluginData ) {
					$isGlueInstalled = isset( $installedPlugins[ $gluePluginSlug ] );
					$isGlueActive    = $isGlueInstalled && $installedPlugins[ $gluePluginSlug ]['is_active'];

					if ( ! $isGlueInstalled || ! $isGlueActive ) {
						$recommendation = $this->preparePluginData(
							$language,
							$downloads[ $gluePluginSlug ],
							$subscription->get_site_key(),
							$repositoryId,
							$subscription->get_site_url(),
							$isGlueInstalled,
							$isGlueActive
						);

						$sectionPlugin = $this->prepareSectionPlugin(
							$language,
							$downloads[ $gluePluginSlug ],
							$isGlueInstalled,
							$isGlueActive
						);

						if ( $downloads[ $gluePluginSlug ] ) {
							$pluginsRecommendations[ $downloads[ $gluePluginSlug ]['download_recommendation_section'] ]['plugins'][ $gluePluginSlug ] = $sectionPlugin;
							$pluginsData[ $gluePluginSlug ]                                                                                           = $recommendation;
						}
					}
				}
			}
		}

		return new RecommendationsForInstallerPlugins( $pluginsRecommendations, $pluginsData );
	}

	private function getCurrentLanguage() {
		global $sitepress;

		return $sitepress ? $sitepress->get_admin_language() : 'en';
	}

	/**
	 * @param \OTGS_Installer_Repository $repository
	 *
	 * @return array
	 */
	private function getAvailablePluginsForSubscription( \OTGS_Installer_Repository $repository ) {
		$product = $repository->get_product_by_subscription_type();
		if ( ! $product ) {
			$product = $repository->get_product_by_subscription_type_equivalent();
		}

		return $product->get_plugins();
	}

	/**
	 * @return array
	 */
	private function getInstalledPlugins() {
		$installed_plugins = [];

		foreach ( get_plugins() as $plugin_id => $plugin_data ) {
			$installed_plugins[ dirname( $plugin_id ) ] = [
				'is_active' => is_plugin_active( $plugin_id ),
			];
		}

		return $installed_plugins;
	}

	/**
	 * @param string $language
	 * @param array $pluginData
	 * @param string $siteKey
	 * @param string $repositoryId
	 * @param string $siteUrl
	 * @param bool $isInstalled
	 * @param bool $isActive
	 *
	 * @return array
	 */
	private function preparePluginData( $language, $pluginData, $siteKey, $repositoryId, $siteUrl, $isInstalled, $isActive ) {
		$url = $this->appendSiteKeyToDownloadUrl( $pluginData['url'], $siteKey, $siteUrl );

		$downloadData = [
			'url'           => $url,
			'slug'          => $pluginData['slug'],
			'nonce'         => wp_create_nonce( 'install_plugin_' . $url ),
			'repository_id' => $repositoryId,
		];

		return [
			'name'                    => $pluginData['name'],
			'short_description'       => isset( $pluginData['short_description'][ $language ] ) ? $pluginData['short_description'][ $language ] : '',
			'is_installed'            => $isInstalled,
			'is_active'               => $isActive,
			'slug'                    => $pluginData['slug'],
			'recommendation_icon_url' => isset( $pluginData['recommendation_icon_url'] ) ? $pluginData['recommendation_icon_url'] : '',
			'download_data'           => base64_encode( (string) json_encode( $downloadData ) ),
		];
	}

	private function prepareSectionPlugin( $language, $pluginData, $isInstalled, $isActive ) {
		return [
			'name'                    => $pluginData['name'],
			'is_installed'            => $isInstalled,
			'is_active'               => $isActive,
			'slug'                    => $pluginData['slug'],
			'short_description'       => isset( $pluginData['short_description'][ $language ] ) ? $pluginData['short_description'][ $language ] : '',
			'recommendation_icon_url' => isset( $pluginData['recommendation_icon_url'] ) ? $pluginData['recommendation_icon_url'] : '',
		];
	}

	/**
	 * @param array $pluginData
	 *
	 * @return bool
	 */
	private function shouldBeDisplayed( $pluginData ) {
		$glueCheckType  = isset( $pluginData['glue_check_type'] ) ? $pluginData['glue_check_type'] : null;
		$glueCheckValue = isset( $pluginData['glue_check_value'] ) ? $pluginData['glue_check_value'] : null;

		if ( $glueCheckType && $glueCheckValue ) {
			switch ( $glueCheckType ) {
				case 'class':
					return class_exists( $glueCheckValue );
				case 'constant':
					return defined( $glueCheckValue );
				case 'function':
					return function_exists( $glueCheckValue );
				default:
					return false;
			}
		}

		if ( $pluginData['slug'] === 'wpml-translation-management' ) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $url
	 * @param string $siteKey
	 * @param string $siteUrl
	 *
	 * @return string
	 */
	private function appendSiteKeyToDownloadUrl( $url, $siteKey, $siteUrl ) {
		return add_query_arg(
			[
				'site_key' => $siteKey,
				'site_url' => $siteUrl,
			],
			$url
		);
	}

	public function getRecommendationStoredNotices( $existingNotices ) {
		$storedRecommendations = Storage::getAll();
		foreach ( $storedRecommendations as $repositoryId => $recommendations ) {
			$repository = $this->repositories->get( $repositoryId );

			$subscription = $repository->get_subscription();
			if ( ! $subscription ) {
				continue;
			}

			foreach ( $recommendations as $recommendationSlug => $recommendation ) {
				if (!$this->isGluePluginActive($recommendation['glue_plugin_slug'])){
					$url = $this->appendSiteKeyToDownloadUrl( $recommendation['download_data']['url'], $subscription->get_site_key(), $subscription->get_site_url() );

					$appendedDownloadData = [
						'url'           => $url,
						'slug'          => $recommendation['download_data']['slug'],
						'repository_id' => $recommendation['download_data']['repository_id'],
						'nonce'         => wp_create_nonce( 'install_plugin_' . $url ),
					];

					$storedRecommendations[ $repositoryId ][ $recommendationSlug ]['download_data'] = $appendedDownloadData;
				}else {
					Storage::delete($recommendationSlug, $repositoryId );
				}
			}
		}

		return array_merge( $existingNotices, $storedRecommendations );
	}

	/**
	 * @param string $plugin
	 *
	 * @return GluePluginData
	 */
	private function getPluginData( $plugin ) {
		$pluginSlug     = dirname( $plugin );
		$gluePluginData = $this->getActivatedPluginGlue( $pluginSlug );

		if ( ! $gluePluginData ) {
			foreach ( $this->repositoriesForRecommendation as $repositoryId ) {
				if ( isset( $this->settings[ $repositoryId ]['data']['glue_plugins_mapping'] ) ) {
					$gluePluginsMapping = $this->settings[ $repositoryId ]['data']['glue_plugins_mapping'];

					if ( isset( $gluePluginsMapping[ $pluginSlug ] ) ) {
						$gluePluginSlug = $gluePluginsMapping[ $pluginSlug ]['glue_plugin'];
						$gluePluginData = $this->getGluePluginData( $gluePluginSlug, $gluePluginsMapping[ $pluginSlug ] );
					}
				}
			}

		}

		return new GluePluginData( $pluginSlug, $gluePluginData );
	}

	/**
	 * @param $repositoryId
	 * @param $pluginData
	 * @param $language
	 *
	 * @return array|null
	 */
	private function prepareRecommendedPluginData( $repositoryId, $pluginData, $language, $mappingData = null ) {
		$repository   = $this->repositories->get( $repositoryId );
		$subscription = $repository->get_subscription();
		if ( ! $subscription ) {
			return null;
		}

		$downloadData = [
			'url'           => $pluginData['url'],
			'slug'          => $pluginData['slug'],
			'repository_id' => $repositoryId,
		];


		return [
			'repository_id'               => $repositoryId,
			'glue_check_slug'             => $mappingData ? $mappingData['glue_check_slug'] : $pluginData['glue_check_slug'],
			'glue_check_name'             => $mappingData ? $mappingData['glue_check_name'] : $pluginData['glue_check_name'],
			'glue_plugin_name'            => $pluginData['name'],
			'glue_plugin_slug'            => $pluginData['slug'],
			'recommendation_notification' => $this->getNotificationForLanguage( $mappingData ?: $pluginData, $language ),
			'download_data'               => $downloadData,
		];
	}
}
