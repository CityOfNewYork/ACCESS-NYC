<?php

class WPML_TM_Rest_Jobs_Package_Helper_Factory {
	/** @var WPML_Package_Helper */
	private $package_helper = false;

	/**
	 * @return null|WPML_Package_Helper
	 */
	public function create() {
		if ( false === $this->package_helper ) {
			if ( defined( 'WPML_ST_VERSION' ) && class_exists( 'WPML_Package_Helper' ) ) {
				$this->package_helper = new WPML_Package_Helper();
			} else {
				$this->package_helper = null;
			}
		}

		return $this->package_helper;
	}
}
