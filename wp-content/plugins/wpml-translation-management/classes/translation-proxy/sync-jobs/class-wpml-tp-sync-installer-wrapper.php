<?php

class WPML_TM_Sync_Installer_Wrapper {
	/**
	 * @return bool
	 */
	public function is_wpml_registered() {
		return (bool) WP_Installer::instance()->get_site_key( 'wpml' );
	}
}