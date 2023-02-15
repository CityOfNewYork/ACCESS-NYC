<?php
namespace WPML\TM\ATE\Factories;

class Proxy extends \WPML_REST_Factory_Loader {
	/**
	 * @return \WPML\TM\ATE\Proxy
	 */
	public function create() {
		$endpoints = new \WPML_TM_ATE_AMS_Endpoints();

		return new \WPML\TM\ATE\Proxy( $endpoints );
	}
}
