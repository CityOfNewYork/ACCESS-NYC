<?php


namespace OTGS\Installer\Upgrade;


class AutoUpgrade {
	/**
	 * @var \WP_Installer $installer
	 */
	private $installer;

	/**
	 * @var \OTGS_Installer_Plugin_Finder
	 */
	private $installerPluginsFinder;

	/**
	 * @var InstallerPlugins
	 */
	private $installerPlugins;

	public function __construct( \WP_Installer $installer, \OTGS_Installer_Plugin_Finder $installerPluginsFinder, InstallerPlugins $installerPlugins ) {
		$this->installer              = $installer;
		$this->installerPluginsFinder = $installerPluginsFinder;
		$this->installerPlugins       = $installerPlugins;
	}

	public function addHooks() {
		add_filter( 'pre_update_site_option_auto_update_plugins', [
			$this,
			'modifyAutoUpdatePluginsOption',
		], 10, 2 );

		add_filter( 'plugin_auto_update_setting_html', [ $this, 'modifyAutoUpdateSettingHtml' ], 10, 3 );
	}

	public function modifyAutoUpdatePluginsOption( $value, $oldValue ) {
		$enabled  = array_diff( $value, $oldValue );
		$disabled = array_diff( $oldValue, $value );

		$pluginFile = reset( $enabled ) ?: reset( $disabled );
		foreach ( $this->installerPlugins->getFilteredInstallerPlugins() as $repositoryId => $installedRepositoryPlugins ) {
			$pluginData = $this->installerPlugins->getPluginData( $repositoryId, $pluginFile );
			if ( $pluginData ) {
				$pluginObj = $this->installerPluginsFinder->get_plugin( $pluginData['slug'], $repositoryId );

				if ( ! $pluginObj || $this->installer->plugin_is_registered( $repositoryId, $pluginData['slug'] )
				                     && $pluginObj->get_external_repo() && $this->installer->plugin_is_registered( $pluginObj->get_external_repo(), $pluginData['slug'] ) ) {
					continue;
				}

				$installedRepositoryPluginIds = array_map( function ( $plugin ) {
					return $plugin['id'];
				}, $installedRepositoryPlugins );

				if ( array_intersect( $enabled, $installedRepositoryPluginIds ) ) {
					$this->updateInstallerAutoUpdateSetting( $repositoryId, true );
					$value = array_unique( array_merge( $value, $installedRepositoryPluginIds ) );
				} elseif ( array_intersect( $disabled, $installedRepositoryPluginIds ) ) {
					$this->updateInstallerAutoUpdateSetting( $repositoryId, false );
					$value = array_diff( $value, $installedRepositoryPluginIds );
				}
			}
		}

		return $value;
	}

	public function modifyAutoUpdateSettingHtml( $html, $pluginFile ) {
		foreach ( $this->installerPlugins->getFilteredInstallerPlugins() as $repositoryId => $installedRepositoryPlugins ) {
			$pluginData = $this->installerPlugins->getPluginData( $repositoryId, $pluginFile );
			if ( $pluginData ) {
				$pluginObj = $this->installerPluginsFinder->get_plugin( $pluginData['slug'], $repositoryId );

				if ( ! $this->installer->plugin_is_registered( $repositoryId, $pluginData['slug'] ) ) {
					if ( ( ! $pluginObj || $pluginObj->get_external_repo() && $this->installer->plugin_is_registered( $pluginObj->get_external_repo(), $pluginData['slug'] ) )
					     || $this->installer->plugin_is_registered( 'wpml', $pluginData['slug'] ) ) {
						continue;
					}
					$html = $this->getRegisterMessage( $repositoryId );
				}

			}
		}

		return $html;
	}

	private function updateInstallerAutoUpdateSetting( $repositoryId, $value ) {
		if ( ! isset( $this->installer->settings['repositories'][ $repositoryId ]['auto_update'] )
		     || $this->installer->settings['repositories'][ $repositoryId ]['auto_update'] !== $value ) {
			$this->installer->settings['repositories'][ $repositoryId ]['auto_update'] = $value;
			$this->installer->save_settings();
		}
	}

	/**
	 * @param string $repositoryId
	 *
	 * @return string
	 */
	private function getRegisterMessage( $repositoryId ) {
		$url      = $this->installer->menu_url() . '&repository=' . $repositoryId . '&action=register';
		$linkText = __( 'Register', 'installer' );
		$text     = __( ' to use auto-updates', 'installer' );

		return '<a href="' . esc_url( $url ) . '">' . esc_html( $linkText ) . '</a>' . esc_html( $text );
	}
}
