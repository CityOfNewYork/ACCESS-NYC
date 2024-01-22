<?php

namespace OTGS\Installer\Api;

class SiteUrl {
	/**
	 * @var boolean
	 */
	private $isRepositoriesSettingsSet;

	/**
	 * @param array $repositoriesSettings
	 */
	public function __construct( $repositoriesSettings ) {
		$this->isRepositoriesSettingsSet = isset( $repositoriesSettings );
	}

	/**
	 * @copied \WP_Installer::get_installer_site_url
	 * @copied \OTGS_Installer_Fetch_Subscription::get_installer_site_url
	 *
	 * @param string $repository_id
	 *
	 * @return mixed
	 */
	public function get( $repository_id = false ) {
		global $current_site;

		$site_url = defined( 'ATE_CLONED_SITE_URL' ) ? ATE_CLONED_SITE_URL : get_site_url();

		if ( $repository_id && is_multisite() && $this->isRepositoriesSettingsSet ) {
			$network_settings = maybe_unserialize( get_site_option( 'wp_installer_network' ) );

			if ( isset( $network_settings[ $repository_id ] ) ) {
				$site_url = get_site_url( $current_site->blog_id );
			}
		}

		$filtered_site_url = filter_var( apply_filters( 'otgs_installer_site_url', $site_url ), FILTER_SANITIZE_URL );

		return $filtered_site_url ?: $site_url;
	}
}