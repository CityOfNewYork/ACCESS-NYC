<?php

namespace OTGS\Installer\Upgrade;

class InstallerPlugins {

	/**
	 * @var \WP_Installer $installer
	 */
	private $installer;

	/**
	 * @var array
	 */
	private $filteredInstallerPlugins;

	public function __construct( \WP_Installer $installer, \OTGS_Installer_Plugin_Finder $installerPluginsFinder ) {
		$this->installer              = $installer;
		$this->filteredInstallerPlugins = $this->filterInstallerPlugins($installerPluginsFinder);
	}

	/**
	 * @return array
	 */
	public function getFilteredInstallerPlugins() {
		return $this->filteredInstallerPlugins;
	}

	/**
	 * @return array
	 */
	private function filterInstallerPlugins(\OTGS_Installer_Plugin_Finder $installerPluginsFinder) {
		$filteredInstallerPlugins = [];
		foreach ( $installerPluginsFinder->get_otgs_installed_plugins_by_repository() as $repositoryId => $installedRepositoryPlugins ) {
			foreach ( $installedRepositoryPlugins as $installedRepositoryPlugin ) {
				$pluginObj  = $installerPluginsFinder->get_plugin( $installedRepositoryPlugin['slug'], $repositoryId );
				if ( !$pluginObj || $pluginObj->get_external_repo() && $this->installer->plugin_is_registered( $pluginObj->get_external_repo(), $installedRepositoryPlugin['slug'] ) ) {
					continue;
				}

				$filteredInstallerPlugins[ $repositoryId ][] = $installedRepositoryPlugin;
			}
		}

		return $filteredInstallerPlugins;
	}

	/**
	 * @param $repositoryId
	 * @param $pluginId
	 *
	 * @return array|null
	 */
	public function getPluginData( $repositoryId, $pluginId ) {
		return current( array_filter( $this->filteredInstallerPlugins[ $repositoryId ], function ( $plugin ) use ( $pluginId ) {
			return $plugin['id'] === $pluginId;
		} ) );
	}
}
