<?php

class WPML_TM_REST_AMS_Clients_Factory extends WPML_REST_Factory_Loader {

	/**
	 * @return \WPML_TM_REST_AMS_Clients
	 * @throws \Auryn\InjectionException
	 */
	public function create() {
		return \WPML\Container\make( '\WPML_TM_REST_AMS_Clients' );
	}
}
