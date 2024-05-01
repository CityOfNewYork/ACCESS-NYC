<?php

namespace OTGS\Installer\Upgrade;

class IncludeAutoUpgrade {
	private $shouldEnableUpdates;

	public function __construct( array $settings, $repositoryId ) {
		$this->shouldEnableUpdates = isset( $settings['repositories'][ $repositoryId ]['auto_update'] ) && $settings['repositories'][ $repositoryId ]['auto_update'];
	}

	public function includeDuringInstall( $pluginId ) {
		if ( $this->shouldEnableUpdates ) {
			$auto_updates   = (array) get_site_option( 'auto_update_plugins', [] );
			$auto_updates[] = $pluginId;
			$auto_updates   = array_unique( $auto_updates );
			update_site_option( 'auto_update_plugins', $auto_updates );
		}
	}
}