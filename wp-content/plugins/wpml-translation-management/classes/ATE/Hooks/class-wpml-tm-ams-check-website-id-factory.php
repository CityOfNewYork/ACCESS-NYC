<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_AMS_Check_Website_ID_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		$options_manager = new WPML_Option_Manager();
		if (
			WPML_TM_ATE_Status::is_enabled_and_activated() &&
			! wpml_is_ajax() &&
			! $options_manager->get( 'TM-has-run', 'WPML_TM_AMS_Check_Website_ID' )
		) {

			$http      = new WP_Http();
			$auth      = new WPML_TM_ATE_Authentication();
			$endpoints = new WPML_TM_ATE_AMS_Endpoints();

			$ate_api = new WPML_TM_ATE_API( $http, $auth, $endpoints );
			$ams_api = new WPML_TM_AMS_API( $http, $auth, $endpoints );

			return new WPML_TM_AMS_Check_Website_ID( $options_manager, $ate_api, $ams_api );
		}
	}
}
