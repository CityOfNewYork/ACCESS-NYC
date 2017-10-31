<?php

class WPML_End_User_Info_WP_User_Repository implements WPML_End_User_Info_Repository {
	/**
	 * @return WPML_End_User_Info_WP_User
	 */
	public function get_data() {
		$user_id = get_current_user_id();
		$registering_user_id = (int) WP_Installer_API::get_registering_user_id( 'wpml' );

		return new WPML_End_User_Info_WP_User(
			$user_id,
			$user_id !== $registering_user_id
		);
	}

	/**
	 * @return string
	 */
	public function get_data_id() {
		return 'wp_user_info';
	}
}
