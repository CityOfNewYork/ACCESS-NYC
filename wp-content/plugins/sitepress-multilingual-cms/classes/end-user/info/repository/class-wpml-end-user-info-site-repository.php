<?php

class WPML_End_User_Info_Site_Repository implements WPML_End_User_Info_Repository {
	/**
	 * @return WPML_End_User_Info_Site
	 */
	public function get_data() {
		$site_url = $this->get_site_base_url();
		$client_id = $this->get_client_id();
		$site_key = $this->get_site_key();

		return new WPML_End_User_Info_Site( $site_url, $client_id, $site_key );
	}

	/**
	 * @return string
	 */
	public function get_data_id() {
		return 'site_info';
	}

	/**
	 * @return string
	 */
	private function get_site_base_url() {
		return ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'];
	}

	/**
	 * @return int
	 */
	private function get_client_id() {
		return WP_Installer_API::get_ts_client_id( 'wpml' );
	}

	private function get_site_key() {
		return WP_Installer_API::get_site_key( 'wpml' );
	}
}
